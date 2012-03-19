<?php

namespace SlmMail\Service;

use Zend\Mail\Message,
    Zend\Http\Client,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Json\Json,
    Zend\Mail\Exception\RuntimeException;

class SendGrid
{
    const API_URI = 'https://sendgrid.com/api/';

    protected $username;
    protected $password;
    protected $client;

    public function __construct ($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /** Mail */
    public function sendMail (Message $message)
    {
        $params = array(
            'subject'  => $message->getSubject(),
            'html'     => $message->getBody(),
            'text'     => $message->getBodyText(),
        );
        
        foreach ($message->to() as $address) {
            $params['to'][]    = $address->getEmail();
            $params['names'][] = $address->getName();
        }
        foreach ($message->cc() as $address) {
            $params['to'][]    = $address->getEmail();
            $params['names'][] = $address->getName();
        }
        
        if (count($message->bcc())) {
            foreach ($message->bcc() as $address) {
                $params['bcc'][] = $address->getEmail();
            }
        }
        
        $from = $message->from();
        if (1 !== count($from)) {
            throw new RuntimeException('SendGrid requires exactly one from address');
        }
        $from->rewind();
        $from = $from->current();
        $params['from']     = $from->getEmail();
        $params['fromname'] = $from->getName();
        
        $replyTo = $message->replyTo();
        if (1 < count($replyTo)) {
            throw new RuntimeException('SendGrid has only support for one reply-to address');
        } elseif (count($replyTo)) {
            $replyTo->rewind();
            $replyTo = $replyTo->current();
            
            $params['replyto'] = $replyTo->getEmail();
        }
        
        /**
         * @todo Handling attachments for emails
         */
        
        $response = $this->prepareHttpClient('mail.send', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    /** Blocks */
    public function getBlocks ($date, $days, $start_date, $end_date)
    {
        $params   = compact($date, $days, $start_date, $end_date);
        $response = $this->prepareHttpClient('blocks.get', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function deleteBlock ($email)
    {
        $params   = compact($email);
        $response = $this->prepareHttpClient('blocks.delete', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    /** Bounces */
    public function getBounces ($date, $days, $start_date, $end_date, $limit, $offset, $type, $email)
    {
        $params   = compact($date, $days, $start_date, $end_date, $limit, $offset, $type, $email);
        $response = $this->prepareHttpClient('bounces.get', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function deleteBounces ($start_date, $end_date, $type, $email)
    {
        $params   = compact($start_date, $end_date, $type, $email);
        $response = $this->prepareHttpClient('bounces.delete', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function countBounces ($start_date, $end_date, $type)
    {
        $params   = compact($start_date, $end_date, $type);
        $response = $this->prepareHttpClient('bounces.count', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    /** Email parse settings */
    public function getParseSettings ()
    {
        $response = $this->prepareHttpClient('parse.get')
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function addParseSetting ($hostname, $url, $spam_check)
    {
        $params   = compact($hostname, $url, $spam_check);
        $response = $this->prepareHttpClient('parse.set', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function editParseSetting ($hostname, $url, $spam_check)
    {
        $params   = compact($hostname, $url, $spam_check);
        $response = $this->prepareHttpClient('parse.set', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function deleteParseSetting ($hostname)
    {
        $params   = compact($hostname);
        $response = $this->prepareHttpClient('parse.delete', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    /** Events */
    public function getEventPostUrl ()
    {
        $response = $this->prepareHttpClient('eventposturl.get')
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function setEventPostUrl ($url)
    {
        $params   = compact($url);
        $response = $this->prepareHttpClient('eventposturl.set', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function deleteEventPostUrl ()
    {
        $response = $this->prepareHttpClient('eventposturl.delete')
                         ->send();
        
        return $this->parseResponse($response);
    }

    /** Filters */
    public function getFilters () {}
    public function activateFilters () {}
    public function deactivateFilters () {}
    public function setupFilters () {}
    public function getFilterSettings () {}

    /** Invalid emails */
    public function getInvalidEmails ($date, $days, $start_date, $end_date, $limit, $offset, $email)
    {
        $params   = compact($date, $days, $start_date, $end_date, $limit, $offset, $email);
        $response = $this->prepareHttpClient('invalidemails.get', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function deleteInvalidEmails ($start_date, $end_date, $email)
    {
        $params   = compact($start_date, $end_date, $email);
        $response = $this->prepareHttpClient('invalidemails.delete', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function countInvalidEmails ($start_date, $end_date)
    {
        $params   = compact($start_date, $end_date);
        $response = $this->prepareHttpClient('invalidemails.count', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    /** Profile */
    public function getProfile ()
    {
        $response = $this->prepareHttpClient('profile.get')
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function updateProfile ($firstname, $lastname, $address, $city, $state, $country, $zip, $phone, $website)
    {
        $params   = compact($firstname, $lastname, $address, $city, $state, $country, $zip, $phone, $website);
        $response = $this->prepareHttpClient('profile.set', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    /**
     * This is disabled for now because of potential problems with Zend\Di
     * 
     * @todo Fix method call
     */
    public function setUsername ($new_username)
    {
        $params   = array('username' => $new_username);
        $response = $this->getHttpClient('profile.setUsername')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function setPassword ($new_password)
    {
        $params   = array('password' => $new_password, 'confirm_password' => $new_password);
        $response = $this->getHttpClient('password.set')
                         ->setParameterGet($params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    public function setEmail ($email)
    {
        $params   = compact($email);
        $response = $this->prepareHttpClient('profile.setEmail', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    /** Spam reports */
    public function getSpamReports ($date, $days, $start_date, $end_date, $limit, $offset, $email)
    {
        $params   = compact($date, $days, $start_date, $end_date, $limit, $offset, $email);
        $response = $this->prepareHttpClient('spamreports.get', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function deleteSpamReports ($start_date, $end_date, $email)
    {
        $params   = compact($start_date, $end_date, $email);
        $response = $this->prepareHttpClient('spamreports.delete', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function countSpamReports ($start_date, $end_date)
    {
        $params   = compact($start_date, $end_date);
        $response = $this->prepareHttpClient('spamreports.count', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    /** Stats */
    public function getStats ($days, $start_date, $end_date)
    {
        $params   = compact($days, $start_date, $end_date);
        $response = $this->prepareHttpClient('stats.get', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function getStatsAggregate ()
    {
        $params   = array('aggregate' => '1');
        $response = $this->prepareHttpClient('stats.get', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function getCategoryList ()
    {
        $params   = array('list' => 'true');
        $response = $this->prepareHttpClient('stats.get', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function getCategoryStats ($category, $days, $start_date, $end_date)
    {    
        $params   = compact($category, $days, $start_date, $end_date);
        $response = $this->prepareHttpClient('stats.get', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function getCategoryAggregate ($category, $days, $start_date)
    {
        $params   = compact($category, $days, $start_date) + array('aggregate' => '1');
        $response = $this->prepareHttpClient('stats.get', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }

    /** Unsubscribes */
    public function getUnsubscribes ($date, $days, $start_date, $end_date, $limit, $offset, $email)
    {
        $params   = compact($date, $days, $start_date, $end_date, $limit, $offset, $email);
        $response = $this->prepareHttpClient('unsubscribes.get', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function addUnsubscribes ($email)
    {
        $params   = compact($email);
        $response = $this->prepareHttpClient('unsubscribes.add', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function deleteUnsubscribes ($start_date, $end_date, $email)
    {
        $params   = compact($start_date, $end_date, $email);
        $response = $this->prepareHttpClient('unsubscribes.delete', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function countUnsubscribes ($start_date, $end_date)
    {
        $params   = compact($start_date, $end_date);
        $response = $this->prepareHttpClient('unsubscribes.count', $params)
                         ->send();
        
        return $this->parseResponse($response);
    }
    
    public function getHttpClient ()
    {
        if (null === $this->client) {
            $this->client = new Client;
        }
        
        return $this->client;
    }
    
    public function setHttpClient (Client $client)
    {
        $this->client = $client;
    }
    
    /**
     * Get a http client instance
     * 
     * @param string $path
     * @return Client
     */
    protected function prepareHttpClient ($path, array $params = array())
    {
        $params = $params + array('api_user' => $this->username, 'api_key'  => $this->password);
        
        return $this->getHttpClient()
                    ->setMethod(Request::METHOD_GET)
                    ->setUri(self::API_URI . $path . '.json')
                    ->setParameterGet($params);
    }
    
    /**
     * Filter null values from the array
     * 
     * Because parameters get interpreted when they are send, remove them 
     * from the list before the request is sent.
     * 
     * @param array $params
     * @param array $exceptions
     * @return array
     */
    protected function filterNullParams (array $params, array $exceptions = array())
    {
        $return = array();
        foreach ($params as $key => $value) {
            if (null !== $value || in_array($key, $exceptions)) {
                $return[$key] = $value;
            }
        }
        
        return $return;
    }

    protected function parseResponse (Response $response)
    {
        if (!$response->isSuccess()) {
            if ($response->isClientError()) {
                $error = Json::decode($response->getBody());
                
                if (isset($error->errors) && is_array($error->errors)) {
                    $message = implode(', ', $error->errors);
                } elseif (isset($error->error)) {
                    $message = $error->error;
                } else {
                    $message = 'Unknown error';
                }
                
                throw new RuntimeException(sprintf(
                                'Could not send request: api errors (%s)',
                                $message));
            } elseif ($response->isServerError()) {
                throw new RuntimeException('Could not send request: Sendgrid server error');
            } else {
                throw new RuntimeException('Unknown error during request to SendGrid server');
            }
        }

        return Json::decode($response->getBody());
    }
}
<?php

namespace Kaliop\Queueing\Plugins\StompBundle\Adapter\Stomp;

use FuseSource\Stomp\Stomp as Client;

class Stomp
{
    protected $client;
    protected $username;
    protected $password;
    protected $queueName;
    protected $connected = false;

    /**
     * @param string $connectString
     */
    public function __construct($connectString)
    {
        $this->client = new Client($connectString);
    }

    public function setCredentials($username, $password)
    {
        $this->username = $username;
        $this->password = $password;

        return $this;
    }

    /**
     * @param string $queueName NB: queue name as used by Stomp, including the prefix used by the broker to specify the
     *                          message delivery pattern
     * @return Producer
     */
    public function setQueueName($queueName)
    {
        $this->queueName = $queueName;

        return $this;
    }

    /**
     * @return string the queue name as used by Stomp
     */
    public function getQueueName()
    {
        return $this->queueName;
    }

    /**
     * Connects to the Stomp server. Attempts the connection only once by default, regardless of results
     *
     * @param bool $onlyOnce
     * @throws \FuseSource\Stomp\Exception\StompException
     */
    protected function connect($onlyOnce = true) {
        if ($onlyOnce && $this->connected) {
            return;
        }
        $this->client->connect($this->username, $this->password);
        $this->connected = true;
    }

}
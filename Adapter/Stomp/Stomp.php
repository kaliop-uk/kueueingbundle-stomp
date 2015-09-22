<?php

namespace Kaliop\Queueing\Plugins\StompBundle\Adapter\Stomp;

/// @todo find a better name for this class
class Stomp
{
    protected $client;
    protected $username;
    protected $password;
    protected $queueName;
    protected $connected = false;

    /**
     * @param array $connectionConfig keys:
     *                                - connect_string
     *                                - credentials (optional, must be an array with 'user' and 'password'
     */
    public function __construct(array $connectionConfig)
    {
        $connectString = $connectionConfig['connect_string'];
        $this->client = new Client($connectString);
        if (isset($connectionConfig['credentials'])) {
            $this->setCredentials($connectionConfig['credentials']['user'], $connectionConfig['credentials']['password']);
        }
    }

    public function __destruct()
    {
        if ($this->connected) {
            $this->client->disconnect();
        }
    }

    public function setCredentials($username, $password)
    {
        $this->username = $username;
        $this->password = $password;

        return $this;
    }

    /**
     * @param bool
     * @return Consumer
     */
    public function setDebug($debug)
    {
        $this->client->debug = $debug;

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
     * Returns the full queue name to be used in stomp messages.
     * Apache Apollo supports using routing keys at end of queue name @see https://activemq.apache.org/apollo/documentation/stomp-manual.html
     * RabbitMQ... @see https://www.rabbitmq.com/stomp.html
     * @param string $routingKey
     * @return string;
     */
    protected function getFullQueueName($routingKey = '')
    {
        $queueName = $this->queueName;
        if ($routingKey != '') {
            $routingKey = str_replace('#', '**', $routingKey);
            $queueName = rtrim($queueName, '.') . '.' . ltrim($routingKey, '.');
        }
        return $queueName;
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
<?php

namespace Kaliop\Queueing\Plugins\StompBundle\Adapter\Stomp;

use Kaliop\QueueingBundle\Queue\MessageConsumerInterface;
use Kaliop\QueueingBundle\Queue\ConsumerInterface;
use Psr\Log\LoggerInterface;

class Consumer extends Stomp implements ConsumerInterface
{
    protected $callback;
    protected $routingKey;
    protected $logger;
    protected $debug = false;

    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Does nothing
     * @param int $limit
     * @return Consumer
     */
    public function setMemoryLimit($limit)
    {
        return $this;
    }

    public function setSubscriptionName($name)
    {
        $this->client->clientId = $name;
    }

    /**
     * @param string $key
     * @return Consumer
     */
    public function setRoutingKey($key)
    {
        $this->routingKey = (string)$key;

        return $this;
    }

    /**
     * @param MessageConsumerInterface $callback
     * @return Consumer
     */
    public function setCallback($callback)
    {
        if (! $callback instanceof \Kaliop\QueueingBundle\Queue\MessageConsumerInterface) {
            throw new \RuntimeException('Can not set callback to Stomp Consumer, as it is not a MessageConsumerInterface');
        }
        $this->callback = $callback;

        return $this;
    }

    /**
     * @param int $amount
     * @param int $timeout seconds
     * @return nothing
     */
    public function consume($amount, $timeout=0)
    {
        $toConsume = $amount;
        if ($timeout > 0) {
            $startTime = time();
            $remaining = $timeout;
        }

        $this->connect();

        $this->client->clientId = 'hello';
        /// @todo shall we subscribe only once? (and reset the flag if changing routing key / queue name)
        $this->client->subscribe(
            $this->getFullQueueName($this->routingKey),
            $this->getClientProperties(array(), 'SUBSCRIBE'),
            true
        );

        while(true) {
            if ($timeout > 0) {
                $this->client->setReadTimeout($remaining);
            }

            $message = $this->client->readFrame();

            if ($message !== false) {
                switch($message->command)
                {
                    case 'MESSAGE':
                        $this->client->ack($message);
                        $this->callback->receive(new Message($message->body, $message->headers));

                        $toConsume--;
                        if ($toConsume == 0) {
                            return;
                        }
                        break;

                    case 'ERROR':
                        throw new \RuntimeException("Stomp server sent error frame: ".$message->body);

                    case 'RECEIPT':
                        // do nothing
                }
            }

            if ($timeout > 0 && ($remaining = ($startTime + $timeout - time())) <= 0) {
                return;
            }

        }
    }

    protected function getClientProperties(array $additionalProperties = array(), $command='')
    {
        $result = $additionalProperties;

        switch($command)
        {
            case 'SUBSCRIBE';
                $result = array_merge(array('persistent' => 'true'), $result);
                break;
        }

        return $result;
    }

}

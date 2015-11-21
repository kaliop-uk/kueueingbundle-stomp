<?php

namespace Kaliop\Queueing\Plugins\StompBundle\Adapter\Stomp;

use Kaliop\QueueingBundle\Queue\MessageConsumerInterface;
use Kaliop\QueueingBundle\Queue\ConsumerInterface;
use Kaliop\QueueingBundle\Queue\SignalHandlingConsumerInterface;
use Kaliop\QueueingBundle\Adapter\ForcedStopException;
use Psr\Log\LoggerInterface;

class Consumer extends Stomp implements ConsumerInterface, SignalHandlingConsumerInterface
{
    protected $callback;
    protected $routingKey;
    protected $logger;
    protected $subscribed = false;
    protected $queueName;
    protected $subscriptionName;
    protected $label;
    protected $forceStop = false;
    protected $dispatchSignals = false;

    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Does nothing
     * @param int $limit
     * @return $this
     */
    public function setMemoryLimit($limit)
    {
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setSubscriptionName($name)
    {
        $this->subscriptionName = $name;
        $this->setClientId();

        return $this;
    }

    public function setLabel($label)
    {
        $this->label = $label;
        $this->setClientId();

        return $this;
    }

    protected function setClientId()
    {
        $newId = $this->subscriptionName . ($this->label != '' ? '_' . $this->label : '');
        if ($newId != $this->client->clientId) {
            $this->client->clientId = $newId;
            $this->subscribed = false;
        }
    }

    /**
     * NB: when changing this, you should change the subscription name as well, otherwise you will get an error for
     * trying to create a double subscription
     *
     * @param string $key
     * @return $this
     */
    public function setRoutingKey($key)
    {
        $this->routingKey = (string)$key;
        $this->subscribed = false;

        return $this;
    }

    /**
     * @param MessageConsumerInterface $callback
     * @return $this
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
     * @param string $queueName
     * @return $this
     */
    public function setQueueName($queueName)
    {
        $this->queueName = $queueName;

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

        $this->subscribe();

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

            $this->maybeStopConsumer();

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
                //$result = array_merge(array('persistent' => 'true'), $result);
                break;
        }

        return $result;
    }

    protected function subscribe()
    {
        if (!$this->subscribed) {

            $this->client->subscribe(
                $this->getFullQueueName($this->routingKey),
                $this->getClientProperties(array(), 'SUBSCRIBE'),
                true
            );

            $this->subscribed = true;
        }
    }

    public function setHandleSignals($doHandle)
    {
        $this->dispatchSignals = $doHandle;
        $this->client->setHandleSignals($doHandle);
    }


    public function forceStop()
    {
        $this->forceStop = true;
        $this->client->forceStop();
    }

    /**
     * Dispatches signals and throws an exception if user wants to stop. To be called at execution points when there is no data loss
     *
     * @param string $message
     * @throws ForcedStopException
     */
    protected function maybeStopConsumer($message = '')
    {
        if ($this->dispatchSignals) {
            pcntl_signal_dispatch();
        }

        if ($this->forceStop) {
            throw new ForcedStopException($message);
        }
    }
}

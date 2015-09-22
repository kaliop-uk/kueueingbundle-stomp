<?php

namespace Kaliop\Queueing\Plugins\StompBundle\Adapter\Stomp;

use Kaliop\QueueingBundle\Adapter\DriverInterface;
use Kaliop\QueueingBundle\Queue\MessageConsumerInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @todo inject Debug flag in both consumers and producers
 */
class Driver extends ContainerAware implements DriverInterface
{
    protected $debug;
    protected $connections;

    /**
     * @param string $queueName
     * @return \Kaliop\QueueingBundle\Queue\ProducerInterface
     */
    public function getProducer($queueName)
    {
        return $this->container->get("kaliop_queueing.stomp.{$queueName}_producer")->setDebug($this->debug);
    }

    /**
     * @param string $queueName
     * @return object
     */
    public function getConsumer($queueName)
    {
        return $this->container->get("kaliop_queueing.stomp.{$queueName}_consumer")->setDebug($this->debug);
    }

    public function acceptMessage($message)
    {
        return $message instanceof \Kaliop\Queueing\Plugins\StompBundle\Adapter\Stomp\Message;
    }

    /**
     * Unlike the RabbitMQ driver, we do not have to deal with a native message type from the underlying library.
     * So we just let the Producer create messages of the good type, and decoding them becomes a no-op
     *
     * @param \Kaliop\Queueing\Plugins\StompBundle\Adapter\Stomp\Message $message
     * @return \Kaliop\Queueing\Plugins\StompBundle\Adapter\Stomp\Message
     */
    public function decodeMessage($message)
    {
        return $message;
    }

    /**
     * @param string $queueName
     * @return \Kaliop\QueueingBundle\Queue\QueueManagerInterface
     */
    public function getQueueManager($queueName)
    {
        $mgr = $this->container->get('kaliop_queueing.stomp.queue_manager');
        $mgr->setQueueName($queueName);
        return $mgr;
    }

    public function setDebug($debug)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * @param string $connectionId
     * @param array $params
     */
    public function registerConnection($connectionId, array $params)
    {
        $this->connections[$connectionId] = $params;
    }

    protected function getConnectionConfig($connectionId)
    {
        if (!isset($this->connections[$connectionId])) {
            throw new \RuntimeException("Connection '$connectionId' is not registered with Stomp driver");
        }

        return $this->connections[$connectionId];
    }

    /**
     * Dynamically creates a producer, with no need for configuration except for the connection configuration
     *
     * @param string $queueName        the name to be used later when asking the producer to the driver
     * @param string $queueDestination the stomp destination
     * @param string $connectionId     the name of a stomp connection as set in configuration
     * @param string $scope
     * @return Producer
     */
    public function createProducer($queueName, $queueDestination, $connectionId, $scope=ContainerInterface::SCOPE_CONTAINER)
    {
        $class = $this->container->getParameter('kaliop_queueing.stomp.producer.class');
        $producer = new $class($this->getConnectionConfig($connectionId));
        $producer->setQueueName($queueDestination);
        $this->container->set("kaliop_queueing.stomp.{$queueName}_producer", $producer, $scope);
        return $producer;
    }

    /**
     * Dynamically creates a consumer, with no need for configuration except for the connection configuration
     *
     * @param string $queueName        the name to be used later when asking the producer to the driver
     * @param string $queueDestination the stomp destination
     * @param string $connectionId     the name of a stomp connection as set in configuration
     * @param string $subscriptionName the name of the subscription to be used to connect to the broker
     * @param MessageConsumerInterface $callback
     * @param string $routingKey
     * @param string $scope
     * @return Consumer
     */
    public function createConsumer($queueName, $queueDestination, $connectionId, $subscriptionName, $callback=null, $routingKey=null, $scope=ContainerInterface::SCOPE_CONTAINER)
    {
        $class = $this->container->getParameter('kaliop_queueing.stomp.consumer.class');
        $consumer = new $class($this->getConnectionConfig($connectionId));
        $consumer->setSubscriptionName($subscriptionName)->setQueueName($queueDestination)->setRoutingKey($routingKey);
        if ($callback != null) {
            $consumer->setCallBack($callback);
        }
        $this->container->set("kaliop_queueing.stomp.{$queueName}_consumer", $consumer, $scope);
        return $consumer;
    }
}

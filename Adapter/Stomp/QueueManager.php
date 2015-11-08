<?php

namespace Kaliop\Queueing\Plugins\StompBundle\Adapter\Stomp;

use Kaliop\QueueingBundle\Service\MessageProducer as BaseMessageProducer;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use InvalidArgumentException;
use Kaliop\QueueingBundle\Queue\Queue;
use Kaliop\QueueingBundle\Queue\QueueManagerInterface;

/**
 * Since STOMP does not provide commands for Queue management, all this does is allow listing of configured queues
 */
class QueueManager implements ContainerAwareInterface, QueueManagerInterface
{
    protected $queueName;
    protected $container;
    protected $registeredProducers = array();
    protected $registeredConsumers = array();

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Does nothing
     * @param string $queue
     * @return $this
     */
    public function setQueueName($queue)
    {
        return $this;
    }

    public function listActions()
    {
        return array('list-configured');
    }

    public function executeAction($action, array $arguments=array())
    {
        switch ($action) {
            case 'list-configured':
                return $this->listConfiguredQueues();

            default:
                throw new InvalidArgumentException("Action $action not supported");
        }
    }

    protected function listConfiguredQueues($type = Queue::TYPE_ANY)
    {
        $out = array();
        if ($type = Queue::TYPE_PRODUCER || $type = Queue::TYPE_ANY) {
            foreach ($this->registeredProducers as $queueName) {
                $out[$queueName] = Queue::TYPE_PRODUCER;
            }
        }
        if ($type = Queue::TYPE_CONSUMER || $type = Queue::TYPE_ANY) {
            foreach ($this->registeredConsumers as $queueName) {
                if (isset($out[$queueName])) {
                    $out[$queueName] = Queue::TYPE_ANY;
                } else {
                    $out[$queueName] = Queue::TYPE_CONSUMER;
                }
            }
        }
        return $out;
    }

    /**
     * Used to keep track of the queues which are available (configured in the bundle)
     * @param string $queueName
     */
    public function registerProducer($queueName) {
        $this->registeredProducers[] = $queueName;
    }

    /**
     * Used to keep track of the queues which are available (configured in the bundle)
     * @param string $queueName
     */
    public function registerConsumer($queueName) {
        $this->registeredConsumers[] = $queueName;
    }
}

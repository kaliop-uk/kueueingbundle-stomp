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
    protected $registeredQueues = array();

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

    protected function listConfiguredQueues()
    {
        if (count($this->registeredQueues) == 0) {
            return array();
        }
        return array_combine($this->registeredQueues, array_fill(0, count($this->registeredQueues), Queue::TYPE_ANY));
    }

    public function registerQueue($queueName)
    {
        $this->registeredQueues[]=$queueName;
    }
}

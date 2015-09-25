<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class KStompTest extends WebTestCase
{
    static protected $queueCounter = 1;
    //protected $createdQueues = array();

    protected function setUp()
    {
        if (null !== static::$kernel) {
            static::$kernel->shutdown();
        }
        $options = array();
        static::$kernel = static::createKernel($options);
        static::$kernel->boot();
    }

    protected function getContainer()
    {
        return static::$kernel->getContainer();
    }

    protected function getDriver()
    {
        return $this->getContainer()->get('kaliop_queueing.drivermanager')->getDriver('stomp');
    }

    /*protected function getQueueManager($queueName= '')
    {
        return $this->getDriver()->getQueueManager($queueName);
    }*/

    protected function getConsumer($queueName, $callback='')
    {
        $consumer = $this->getDriver()->getConsumer($queueName);
        if ($callback != '') {
            $consumer->setCallback($this->getContainer()->get($callback));
        }
        return $consumer;
    }

    protected function getMsgProducer($queueName, $msgProducerServiceId)
    {
        return $this->getContainer()->get($msgProducerServiceId)
            ->setDriver($this->getDriver())
            ->setQueueName($queueName)
        ;
    }

    /**
     * Does nothing for the moment
     * @param string $queueName
     * @return null
     */
    protected function removeQueue($queueName)
    {
        //unset($this->createdQueues[$queueName]);
        /*return static::$kernel->
        getContainer()->
        get('kaliop_queueing.drivermanager')->
        getDriver('sqs')->
        getQueueManager($queueName)->
        executeAction('delete');*/
    }

    /**
     * Does nothing but generate a unique queue name, since queues are auto-created by the broker on demand
     * @return string
     */
    protected function createQueue($withConsumer = true)
    {
        $queueName = $this->getNewQueueName();
        $driver = $this->getDriver();

        $queueUrl = '/topic/'.$queueName;
        $driver->createProducer($queueName, $queueUrl, 'default');
        if ($withConsumer) {
            $driver->createConsumer($queueName, $queueUrl, 'default', 'default_subscription_'.self::$queueCounter);
        }

        // save the id of the created queue
        //$this->createdQueues[$queueName] = time();

        return $queueName;
    }

    protected function createConsumer($queueName, $subscriptionName, $queueUrl='')
    {
        $driver = $this->getDriver();

        if ($queueUrl == '') {
            $queueUrl = '/topic/'.$queueName;
        }
        $driver->createConsumer($queueName, $queueUrl, 'default', $subscriptionName);

        // save the id of the created queue
        //$this->createdQueues[$queueName] = time();

        return $queueName;
    }

    protected function createConsumers($queueName, $count)
    {
        $names = array();
        for ($i = 1; $i <= $count; $i++) {
            $names[] = $this->createConsumer($queueName . '_' . $i, $queueName . '_' .  $i, '/topic/' . $queueName);
        }
        return $names;
    }

    protected function getNewQueueName()
    {
        $buildId = 'travis_test_' . getenv('TRAVIS_JOB_NUMBER');
        if ($buildId == 'travis_test_') {
            $buildId = 'test_' . gethostname() . '_' . getmypid();
        }

        $buildId .= '_' . self::$queueCounter;
        self::$queueCounter++;
        return str_replace( '/', '_', $buildId );
    }
}

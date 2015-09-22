<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class KStompTest extends WebTestCase
{
    static protected $queueCounter = 1;
    protected $createdQueues = array();

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

    protected function getConsumer($queueName)
    {
        return $this->getDriver()->getConsumer($queueName);
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
        unset($this->createdQueues[$queueName]);
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
    protected function createQueue()
    {
        $queueName = $this->getNewQueueName();
        $driver = $this->getDriver();

        $queueUrl = '/topic/'.$queueName;
        $driver->createProducer($queueName, $queueUrl, 'default');
        $driver->createConsumer($queueName, $queueUrl, 'default');

        // save the id of the created queue
        $this->createdQueues[$queueName] = time();

        return $queueName;
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

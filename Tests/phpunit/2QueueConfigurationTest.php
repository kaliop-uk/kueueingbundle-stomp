<?php

require_once(__DIR__.'/KStompTest.php');

class QueueConfigurationTests extends KStompTest
{
    public function testListConfiguredQueues()
    {
        /*$queueName = $this->CreateQueue();
        $queueManager = $this->getDriver()->getQueueManager($queueName);
        $producer = $this->getDriver()->getProducer($queueName);
        $this->assertArrayHasKey($producer->getQueueUrl(), $queueManager->executeAction('list-available'));*/
    }
}

<?php

require_once(__DIR__.'/KStompTest.php');

class QueueConfigurationTest extends KStompTest
{
    public function testListConfiguredQueues()
    {
        $queueManager = $this->getDriver()->getQueueManager(null);
        $queues = $queueManager->executeAction('list-configured');
        $this->assertArrayHasKey('travis_test_p', $queues);
        $this->assertArrayHasKey('travis_test_c', $queues);
    }
}

<?php

require_once(__DIR__.'/KStompTest.php');

class ServicesTest extends KStompTest
{
    /**
     * Minimalistic test: check that all known services can be loaded
     */
    public function testKnownServices()
    {
        $container = $this->getContainer();
        $service = $container->get('kaliop_queueing.driver.stomp');
        $service = $container->get('test_alias.kaliop_queueing.stomp.queue_manager');
    }
}

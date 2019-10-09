<?php

require_once(__DIR__.'/KStompTest.php');

/**
 * @todo It seems that there are still random failures in all 'receive'
 */
class MessagesTest extends KStompTest
{
    // maximum number of seconds to wait for the queue when consuming
    protected $timeout = 10;

    public function testSendAndReceiveMessage()
    {
        $queueName = $this->createQueue();

        $consumer = $this->getConsumer($queueName, 'test_alias.kaliop_queueing.message_consumer.noop');
        // warm up the topic by creating the durable subscription
        $consumer->consume(1, 1);

        $msgProducer = $this->getMsgProducer($queueName, 'test_alias.kaliop_queueing.message_producer.generic_message');
        $msgProducer->publish('{"hello":"world"}');

        $consumer->consume(1, $this->timeout);

        $accumulator = $this->getContainer()->get('kaliop_queueing.message_consumer.filter.accumulator');
        $this->assertContains('world', $accumulator->getConsumptionResult());
    }

    public function testSendAndReceiveMessages()
    {
        $queueName = $this->createQueue();

        $consumer = $this->getConsumer($queueName, 'test_alias.kaliop_queueing.message_consumer.noop');
        // warm up the topic by creating the durable subscription
        $consumer->consume(1, 1);

        $msgProducer = $this->getMsgProducer($queueName, 'test_alias.kaliop_queueing.message_producer.generic_message');
        $msgProducer->publish('{"hello":"world"}');
        $msgProducer->publish('{"bonjour":"monde"}');
        $msgProducer->publish('{"ciao":"mondo"}');

        $consumer->consume(4, $this->timeout);

        $accumulator = $this->getContainer()->get('kaliop_queueing.message_consumer.filter.accumulator');
        $this->assertEquals(3, $accumulator->countConsumptionResult());
    }

    public function testSendAndReceiveMessageWithRouting()
    {
        $queueName = $this->createQueue(false);
        list ($cQueueName1, $cQueueName2) = $this->createConsumers($queueName, 2);
        $c1 = $this->getConsumer($cQueueName1, 'test_alias.kaliop_queueing.message_consumer.noop')->setRoutingkey('hello.world');
        $c2 = $this->getConsumer($cQueueName2, 'test_alias.kaliop_queueing.message_consumer.noop')->setRoutingkey('bonjour.monde');

        // warm up the topic by creating the durable subscriptions
        $c1->consume(1, 1);
        $c2->consume(1, 1);

        $msgProducer = $this->getMsgProducer($queueName, 'test_alias.kaliop_queueing.message_producer.generic_message');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"fre"}', null, 'bonjour.monde');

        $accumulator = $this->getContainer()->get('kaliop_queueing.message_consumer.filter.accumulator');

        $c1->consume(1, $this->timeout);
        $this->assertContains('eng', $accumulator->getConsumptionResult());
        $c2->consume(1, $this->timeout);
        $this->assertContains('fre', $accumulator->getConsumptionResult());
    }

    public function testSendAndReceiveMessageWithRoutingWildcard()
    {
        $queueName = $this->createQueue(false);
        list ($cQueueName1, $cQueueName2) = $this->createConsumers($queueName, 2);
        $c1 = $this->getConsumer($cQueueName1, 'test_alias.kaliop_queueing.message_consumer.noop')->setRoutingkey('*.world');
        $c2 = $this->getConsumer($cQueueName2, 'test_alias.kaliop_queueing.message_consumer.noop')->setRoutingkey('hello.*');

        // warm up the topic by creating the durable subscriptions
        $c1->consume(1, 1);
        $c2->consume(1, 1);

        $msgProducer = $this->getMsgProducer($queueName, 'test_alias.kaliop_queueing.message_producer.generic_message');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"fre"}', null, 'bonjour.monde');

        $accumulator = $this->getContainer()->get('kaliop_queueing.message_consumer.filter.accumulator');

        $c1->consume(1, $this->timeout);
        $this->assertContains('eng', $accumulator->getConsumptionResult());

        $c2->consume(1, $this->timeout);
        $this->assertContains('eng', $accumulator->getConsumptionResult());
    }

    public function testSendAndReceiveMessageWithRoutingHash()
    {
        $queueName = $this->createQueue(false);
        list ($cQueueName1, $cQueueName2, $cQueueName3) = $this->createConsumers($queueName, 3);
        $c1 = $this->getConsumer($cQueueName1, 'test_alias.kaliop_queueing.message_consumer.noop')->setRoutingkey('hello.#');
        $c2 = $this->getConsumer($cQueueName2, 'test_alias.kaliop_queueing.message_consumer.noop')->setRoutingkey('#.world');
        $c3 = $this->getConsumer($cQueueName3, 'test_alias.kaliop_queueing.message_consumer.noop')->setRoutingkey('#');

        // warm up the topic by creating the durable subscriptions
        $c1->consume(1, 1);
        $c2->consume(1, 1);
        $c3->consume(1, 1);

        $msgProducer = $this->getMsgProducer($queueName, 'test_alias.kaliop_queueing.message_producer.generic_message');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"fre"}', null, 'bonjour.monde');

        $accumulator = $this->getContainer()->get('kaliop_queueing.message_consumer.filter.accumulator');

        $c1->consume(1, $this->timeout);
        $this->assertContains('eng', $accumulator->getConsumptionResult());
        $c2->consume(1, $this->timeout);
        $this->assertContains('eng', $accumulator->getConsumptionResult());

        $accumulator->reset();
        // this could give us back either message, as order of delivery is not guaranteed
        $c3->consume(2, $this->timeout);
        $this->assertEquals(2, $accumulator->countConsumptionResult());
        $this->assertThat(
            $accumulator->getConsumptionResult(),
            $this->logicalOr(
                $this->contains('eng'),
                $this->contains('fre')
            )
        );
    }
}

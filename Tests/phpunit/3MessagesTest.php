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

        $consumer = $this->getConsumer($queueName, 'kaliop_queueing.message_consumer.noop');
        // warm up the topic by creating the durable subscription
        $consumer->consume(1, 1);

        $msgProducer = $this->getMsgProducer($queueName, 'kaliop_queueing.message_producer.generic_message');
        $msgProducer->publish('{"hello":"world"}');

        $consumer->consume(1, $this->timeout);

        $accumulator = $this->getContainer()->get('kaliop_queueing.message_consumer.filter.accumulator');
        $this->assertContains('world', $accumulator->getConsumptionResult());
    }

    /*public function testSendAndReceiveMessageWithRouting()
    {
        $queueName = $this->createQueue();

        $msgProducer = $this->getMsgProducer($queueName, 'kaliop_queueing.message_producer.generic_message');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"fre"}', null, 'bonjour.monde');

        $accumulator = $this->getContainer()->get('kaliop_queueing.message_consumer.filter.accumulator');
        $consumer = $this->getConsumer($queueName, 'kaliop_queueing.message_consumer.noop');

        $consumer->setRoutingkey('hello.world')->consume(1, $this->timeout);
        $this->assertContains('eng', $accumulator->getConsumptionResult());

        $consumer->setRoutingkey('bonjour.monde')->consume(1, $this->timeout);
        $this->assertContains('fre', $accumulator->getConsumptionResult());
    }

    public function testSendAndReceiveMessageWithRoutingWildcard()
    {
        $queueName = $this->createQueue();

        $msgProducer = $this->getMsgProducer($queueName, 'kaliop_queueing.message_producer.generic_message');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"fre"}', null, 'bonjour.monde');

        $accumulator = $this->getContainer()->get('kaliop_queueing.message_consumer.filter.accumulator');
        $consumer = $this->getConsumer($queueName, 'kaliop_queueing.message_consumer.noop');

        $consumer->setRoutingkey('*.world')->consume(1, $this->timeout);
        $this->assertContains('eng', $accumulator->getConsumptionResult());

        $consumer->setRoutingkey('hello.*')->consume(1, $this->timeout);
        $this->assertContains('eng', $accumulator->getConsumptionResult());
    }

    public function testSendAndReceiveMessageWithRoutingHash()
    {
        $queueName = $this->createQueue();

        $msgProducer = $this->getMsgProducer($queueName, 'kaliop_queueing.message_producer.generic_message');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"eng"}', null, 'hello.world');
        $msgProducer->publish('{"hello":"fre"}', null, 'bonjour.monde');

        $accumulator = $this->getContainer()->get('kaliop_queueing.message_consumer.filter.accumulator');
        $consumer = $this->getConsumer($queueName, 'kaliop_queueing.message_consumer.noop');

        $consumer->setRoutingkey('hello.#')->consume(1, $this->timeout);
        $this->assertContains('eng', $accumulator->getConsumptionResult());

        $consumer->setRoutingkey('#.world')->consume(1, $this->timeout);
        $this->assertContains('eng', $accumulator->getConsumptionResult());

        // this could give us back either message, as order of delivery is not guaranteed
        $consumer->setRoutingkey('#')->consume(2, $this->timeout);
        $this->assertThat(
            $accumulator->getConsumptionResult(),
            $this->logicalOr(
                $this->contains('eng'),
                $this->contains('fre')
            )
        );
    }*/
}

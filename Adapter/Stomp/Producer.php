<?php

namespace Kaliop\Queueing\Plugins\StompBundle\Adapter\Stomp;

use Kaliop\QueueingBundle\Queue\ProducerInterface;

class Producer extends Stomp implements ProducerInterface
{
    protected $debug = false;
    protected $contentType = 'text/plain';

    /**
     * @param string $contentType
     * @return Producer
     * @throws \Exception if unsupported contentType is used
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * Publishes the message
     *
     * @param string $msgBody
     * @param string $routingKey
     * @param array $additionalProperties
     *
     * @todo support custom message attributes
     */
    public function publish($msgBody, $routingKey = '', $additionalProperties = array())
    {
        $this->connect();
        if (! $this->client->send(
            $this->getFullQueueName($routingKey),
            $msgBody,
            $this->getClientProperties($additionalProperties),
            // at least when talking to Apollo we need this flag on or no exception will be thrown on errors
            true
        )) {
            throw new \RuntimeException('Message not delivered to Stomp broker');
        }
    }

    /**
     * @param array $messages
     */
    public function batchPublish(array $messages)
    {
        $this->connect();

        foreach($messages as $message) {
            if (! $this->client->send(
                $this->getFullQueueName(@$message['routingKey']),
                $message['msgBody'],
                $this->getClientProperties(@$message['additionalProperties']),
                // at least when talking to Apollo we need this flag on or no exception will be thrown on errors
                true
            )) {
                throw new \RuntimeException('Message not delivered to Stomp broker');
            }
        }
    }

    /**
     * Prepares the extra headers to be injected into the requests
     * @param array $additionalProperties
     * @return array
     */
    protected function getClientProperties(array $additionalProperties = array())
    {
        $result = array_merge(array('content-type' => $this->contentType), $additionalProperties);

        return $result;
    }

}

<?php

namespace Kaliop\Queueing\Plugins\StompBundle\Adapter\Stomp;

use Kaliop\QueueingBundle\Queue\ProducerInterface;

class Producer extends Stomp implements ProducerInterface
{

    //protected $queuePrefix = 'topic';
    protected $debug = false;
    protected $contentType = 'text/plain';

    /**
     * At the moment does nothing
     *
     * @param bool $debug
     * @return $this
     *
     * @todo
     */
    public function setDebug($debug)
    {
        /*if ($debug == $this->debug) {
            return $this;
        }
        if ($debug) {
            $handlerList = $this->client->getHandlerList();
            $handlerList->interpose(new TraceMiddleware($debug === true ? [] : $debug));
        }*/

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
        ) ) {
            throw new \RuntimeException('Message not delivered to Stomp broker');
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

    /**
     * @param array $messages
     */
        public function batchPublish(array $messages)
    {
        $j = 0;
        for ($i = 0; $i < count($messages); $i += 10) {
            $entries = array();
            $toSend = array_slice($messages, $i, 10);
            foreach($toSend as $message) {
                $entries[] = array_merge(
                    array(
                        'MessageBody' => $message['msgBody'],
                        'Id' => $j++
                    ),
                    $this->getClientParams(@$message['routingKey'], @$message['additionalProperties'])
                );
            }

            $result = $this->client->sendMessageBatch(
                array(
                    'QueueUrl' => $this->queueUrl,
                    'Entries' => $entries,
                )
            );

            if (($ok = count($result->get('Successful'))) != ($tot = count($toSend))) {
                throw new \RuntimeException("Batch sending of messages failed - $ok ok out of $tot");
            }
        }
    }

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
}

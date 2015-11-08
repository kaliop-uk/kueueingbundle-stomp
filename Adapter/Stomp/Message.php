<?php

namespace Kaliop\Queueing\Plugins\StompBundle\Adapter\Stomp;

use Kaliop\QueueingBundle\Queue\MessageInterface;

class Message implements MessageInterface
{
    protected $body;
    protected $headers = array();
    protected $contentType;
    protected $queueName;

    public function __construct($body, array $headers = array(), $queueName = '')
    {
        $this->body = $body;
        $this->headers = $headers;
        /// @todo throw exception if content type not set
        $this->contentType = $headers['content-type'];
        $this->queueName = $queueName;
    }

    public function getBody()
    {
        return $this->body;
    }

    /**
     * This is hardcoded because
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @return string
     */
    public function getQueueName()
    {
        return $this->queueName;
    }

    /**
     * Check whether a property exists in the 'properties' dictionary
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->headers[$name]);
    }

    /**
     * @param string $name
     * @throws \OutOfBoundsException
     * @return mixed
     */
    public function get($name)
    {
        return $this->headers[$name];
    }

    /**
     * Returns the properties content
     * @return array
     */
    public function getProperties()
    {
        return $this->headers;
    }
}

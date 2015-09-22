<?php

namespace Kaliop\Queueing\Plugins\StompBundle\Adapter\Stomp;

use FuseSource\Stomp\Stomp as BaseClient;
use FuseSource\Stomp\Frame;
use FuseSource\Stomp\Exception\StompException;

class Client extends BaseClient
{
    public $debug = false;

    /**
     * Connect to server. Reimplemented to sniff out Apollo
     *
     * @param string $username
     * @param string $password
     * @return boolean
     * @throws StompException
     */
    public function connect ($username = '', $password = '')
    {
        $this->_makeConnection();
        if ($username != '') {
            $this->_username = $username;
        }
        if ($password != '') {
            $this->_password = $password;
        }
        $headers = array('login' => $this->_username , 'passcode' => $this->_password);
        if ($this->clientId != null) {
            $headers["client-id"] = $this->clientId;
        }
        $frame = new Frame("CONNECT", $headers);
        $this->_writeFrame($frame);
        $frame = $this->readFrame();

        if ($frame instanceof Frame && $frame->command == 'CONNECTED') {
            $this->_sessionId = $frame->headers["session"];
            if (isset($frame->headers['server']) && false !== stristr(trim($frame->headers['server']), 'rabbitmq')) {
                $this->brokerVendor = 'RMQ';
            }
            if (isset($frame->headers['server']) && false !== strpos($frame->headers['server'], 'apache-apollo/')) {
                $this->brokerVendor = 'Apollo';
            }
            return true;
        } else {
            if ($frame instanceof Frame) {
                throw new StompException("Unexpected command: {$frame->command}", 0, $frame->body);
            } else {
                throw new StompException("Connection not acknowledged");
            }
        }
    }

    /**
     * Register to listen to a given destination. Reimplemented to support Apollo
     *
     * @param string $destination Destination queue
     * @param array $properties
     * @param boolean $sync Perform request synchronously
     * @return boolean
     * @throws StompException
     */
    public function subscribe ($destination, $properties = null, $sync = null)
    {
        $headers = array('ack' => 'client');
        if ($this->brokerVendor == 'AMQ') {
            $headers['activemq.prefetchSize'] = $this->prefetchSize;
        } else if ($this->brokerVendor == 'RMQ') {
            $headers['prefetch-count'] = $this->prefetchSize;
        }

        if ($this->clientId != null) {
            if ($this->brokerVendor == 'AMQ') {
                $headers['activemq.subscriptionName'] = $this->clientId;
            } else if ($this->brokerVendor == 'RMQ' || $this->brokerVendor == 'Apollo') {
                $headers['id'] = $this->clientId;
            }
        }

        if (isset($properties)) {
            foreach ($properties as $name => $value) {
                $headers[$name] = $value;
            }
        }
        $headers['destination'] = $destination;
        $frame = new Frame('SUBSCRIBE', $headers);
        $this->_prepareReceipt($frame, $sync);
        $this->_writeFrame($frame);
        if ($this->_waitForReceipt($frame, $sync) == true) {
            $this->_subscriptions[$destination] = $properties;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Write frame to server. Reimplemented to add debug support
     *
     * @param Frame $stompFrame
     * @throws StompException
     */
    protected function _writeFrame (Frame $stompFrame)
    {
        if (!is_resource($this->_socket)) {
            throw new StompException('Socket connection hasn\'t been established');
        }

        $data = $stompFrame->__toString();
        $r = fwrite($this->_socket, $data, strlen($data));
        if ($r === false || $r == 0) {
            $this->_reconnect();
            $this->_writeFrame($stompFrame);
        } else {
            if ($this->debug) {
                echo "STOMP FRAME SENT:\n  ".str_replace("\n", "\n  ", $data)."\n";
            }
        }
    }

    /**
     * Read response frame from server
     *
     * @return Frame False when no frame to read
     */
    public function readFrame ()
    {
        if (!empty($this->_waitbuf)) {
            return array_shift($this->_waitbuf);
        }

        if (!$this->hasFrameToRead()) {
            return false;
        }

        $rb = 1024;
        $data = '';
        $end = false;

        do {
            $read = fgets($this->_socket, $rb);
            if ($read === false || $read === "") {
                $this->_reconnect();
                return $this->readFrame();
            }
            $data .= $read;
            if (strpos($data, "\x00") !== false) {
                $end = true;
                $data = trim($data, "\n");
            }
            $len = strlen($data);
        } while ($len < 2 || $end == false);

        if ($this->debug) {
            echo "STOMP FRAME RECEIVED:\n  ".str_replace("\n", "\n  ", $data)."\n";
        }

        list ($header, $body) = explode("\n\n", $data, 2);
        $header = explode("\n", $header);
        $headers = array();
        $command = null;
        foreach ($header as $v) {
            if (isset($command)) {
                list ($name, $value) = explode(':', $v, 2);
                $headers[$name] = $value;
            } else {
                $command = $v;
            }
        }
        $frame = new Frame($command, $headers, trim($body));
        if (isset($frame->headers['transformation']) && $frame->headers['transformation'] == 'jms-map-json') {
            return new Map($frame);
        } else {
            return $frame;
        }
        return $frame;
    }

}
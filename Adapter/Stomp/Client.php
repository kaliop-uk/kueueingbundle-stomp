<?php

namespace Kaliop\Queueing\Plugins\StompBundle\Adapter\Stomp;

use FuseSource\Stomp\Stomp as BaseClient;
use FuseSource\Stomp\Frame;
use FuseSource\Stomp\Message\Map;
use FuseSource\Stomp\Exception\StompException;
use Kaliop\QueueingBundle\Adapter\ForcedStopException;

class Client extends BaseClient
{
    public $debug = false;
    protected $forceStop = false;
    protected $forceStopReason;
    protected $dispatchSignals = false;

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
     * Register to listen to a given destination.
     * Reimplemented to support Apollo, and subscriptions to both ActiveMQ queues and topics
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
                if (strpos($destination, '/queue/') !== 0) {
                    $headers['activemq.subscriptionName'] = $this->clientId;
                }
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

    /**
     * Make socket connection to the server
     * Reimplemented to support forcestop
     *
     * @throws StompException
     */
    protected function _makeConnection()
    {
        if (count($this->_hosts) == 0) {
            throw new StompException("No broker defined");
        }

        // force disconnect, if previous established connection exists
        $this->disconnect();

        $i = $this->_currentHost;
        $att = 0;
        $connected = false;
        $connect_errno = null;
        $connect_errstr = null;

        while (! $connected && $att ++ < $this->_attempts) {
            if (isset($this->_params['randomize']) && $this->_params['randomize'] == 'true') {
                $i = rand(0, count($this->_hosts) - 1);
            } else {
                $i = ($i + 1) % count($this->_hosts);
            }
            $broker = $this->_hosts[$i];
            $host = $broker[0];
            $port = $broker[1];
            $scheme = $broker[2];
            if ($port == null) {
                $port = $this->_defaultPort;
            }
            if ($this->_socket != null) {
                fclose($this->_socket);
                $this->_socket = null;
            }

            $this->_socket = @fsockopen($scheme . '://' . $host, $port, $connect_errno, $connect_errstr, $this->_connect_timeout_seconds);

            $this->maybeStopClient();

            if (!is_resource($this->_socket) && $att >= $this->_attempts && !array_key_exists($i + 1, $this->_hosts)) {
                throw new StompException("Could not connect to $host:$port ($att/{$this->_attempts})");
            } else if (is_resource($this->_socket)) {
                $connected = true;
                $this->_currentHost = $i;
                break;
            }
        }
        if (! $connected) {
            throw new StompException("Could not connect to a broker");
        }
    }

    public function setHandleSignals($doHandle)
    {
        $this->dispatchSignals = $doHandle;
    }

    public function forceStop($reason = '')
    {
        $this->forceStop = true;
        $this->forceStopReason = $reason;
    }

    /**
     * Dispatches signals and throws an exception if user wants to stop. To be called at execution points when there is no data loss
     *
     * @throws ForcedStopException
     */
    protected function maybeStopClient()
    {
        if ($this->dispatchSignals) {
            pcntl_signal_dispatch();
        }

        if ($this->forceStop) {
            throw new ForcedStopException($this->forceStopReason);
        }
    }
}

<?php
namespace Phpsmpp\Transport;
//require_once $GLOBALS['SMPP_ROOT'].'/Transport/ttransport.class.php';
//require_once $GLOBALS['SMPP_ROOT'].'/Transport/texception.class.php';
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * Sockets implementation of the TTransport interface.
 *
 * @package thrift.Transport
 */
class TSocket extends TTransport {

	/**
	 * Handle to PHP socket
	 *
	 * @var resource
	 */
	private $handle_ = null;

	/**
	 * Remote hostname
	 *
	 * @var string
	 */
	protected $host_ = 'localhost';

	/**
	 * Remote port
	 *
	 * @var int
	 */
	protected $port_ = '9090';

    /**
     * Connection timeout. Combined with connectionTimeoutUSec_.
     * To be distinguished from $sendTimeoutSec_ and $recvTimeoutSec_.
     * When $connectionTimeoutSec_ is reached, the TCP connection is closed.
     *
     * @var int
     */
	private $connectionTimeoutSec_ = 0;

    /**
     * Connection timeout.
     * @var int
     */
    private $connectionTimeoutUsec_ = 300000;

	/**
	 * Send timeout in seconds.
	 *
	 * Combined with sendTimeoutUsec this is used for send timeouts.
     * When reached, the TCP connection is not closed, and no exception is thrown.
     * This gives the chance to send enquire_link messages to preserve the connection before $connectionTimeoutSec_ is reached.
	 *
	 * @var int
	 */
	private $sendTimeoutSec_ = 0;

	/**
	 * Send timeout in microseconds.
	 *
	 * Combined with sendTimeoutSec this is used for send timeouts.
	 *
	 * @var int
	 */
	private $sendTimeoutUsec_ = 100000;

	/**
	 * Recv timeout in seconds
	 *
	 * Combined with recvTimeoutUsec this is used for recv timeouts.
	 *
	 * @var int
	 */
	private $recvTimeoutSec_ = 0;

	/**
	 * Recv timeout in microseconds
	 *
	 * Combined with recvTimeoutSec this is used for recv timeouts.
     * When reached, the TCP connection is not closed, and no exception is thrown.
     * This gives the chance to send enquire_link messages to preserve the connection before $connectionTimeoutSec_ is reached.
	 *
	 * @var int
	 */
	private $recvTimeoutUsec_ = 750000;

	/**
	 * Persistent socket or plain?
	 *
	 * @var bool
	 */
	protected $persist_ = FALSE;

    protected $logger = null;

	/**
	 * Socket constructor
	 *
	 * @param string $host         Remote hostname
	 * @param int    $port         Remote port
	 * @param bool   $persist      Whether to use a persistent socket
	 * @param string $debugHandler Function to call for error logging
	 */
	public function __construct($host='localhost',$port=9090,$persist=FALSE, LoggerInterface $logger=null) {
		$this->host_ = $host;
		$this->port_ = $port;
		$this->persist_ = $persist;
        $this->logger = $logger;

        if($logger == null) {
            $this->logger = new Logger('smpp');
            $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
        }
	}

	/**
	 * @param resource $handle
	 * @return void
	 */
	public function setHandle($handle) {
		$this->handle_ = $handle;
	}

    /**
     * @param $timeout int Timeout in milliseconds
     */
	public function setConnectionTimeout($timeout) {
        $this->connectionTimeoutSec_ = floor($timeout / 1000);
        $this->connectionTimeoutUsec_ = ($timeout - ($this->connectionTimeoutSec_ * 1000)) * 1000;
    }

	/**
	 * Sets the send timeout.
	 *
	 * @param int $timeout  Timeout in milliseconds.
	 */
	public function setSendTimeout($timeout) {
		$this->sendTimeoutSec_ = floor($timeout / 1000);
		$this->sendTimeoutUsec_ =
		($timeout - ($this->sendTimeoutSec_ * 1000)) * 1000;
	}

	/**
	 * Sets the receive timeout.
	 *
	 * @param int $timeout  Timeout in milliseconds.
	 */
	public function setRecvTimeout($timeout) {
		$this->recvTimeoutSec_ = floor($timeout / 1000);
		$this->recvTimeoutUsec_ = ($timeout - ($this->recvTimeoutSec_ * 1000)) * 1000;
	}
	/**
	 * Get the host that this socket is connected to
	 *
	 * @return string host
	 */
	public function getHost() {
		return $this->host_;
	}

	/**
	 * Get the remote port that this socket is connected to
	 *
	 * @return int port
	 */
	public function getPort() {
		return $this->port_;
	}

	/**
	 * Tests whether this is open
	 *
	 * @return bool true if the socket is open
	 */
	public function isOpen() {
		return is_resource($this->handle_);
	}

	/**
	 * Connects the socket.
	 */
	public function open() {
		if ($this->isOpen()) {
			throw new TTransportException('Socket already connected', TTransportException::ALREADY_OPEN);
		}

		if (empty($this->host_)) {
			throw new TTransportException('Cannot open null host', TTransportException::NOT_OPEN);
		}

		if ($this->port_ <= 0) {
			throw new TTransportException('Cannot open without port', TTransportException::NOT_OPEN);
		}

		if ($this->persist_) {
			$this->handle_ = @pfsockopen($this->host_,
			$this->port_,
			$errno,
			$errstr,
			$this->connectionTimeoutSec_ + ($this->connectionTimeoutUsec_ / 1000000));
		} else {
			$this->handle_ = @fsockopen($this->host_,
			$this->port_,
			$errno,
			$errstr,
			$this->connectionTimeoutSec_ + ($this->connectionTimeoutUsec_ / 1000000));
		}

		// Connect failed?
		if ($this->handle_ === FALSE) {
			$error = 'TSocket: Could not connect to '.$this->host_.':'.$this->port_.' ('.$errstr.' ['.$errno.'])';
            $this->logger->error($error);

			throw new TException($error);
		}
	}

	/**
	 * Closes the socket.
	 */
	public function close() {
		if (!$this->persist_) {
			@fclose($this->handle_);
			$this->handle_ = null;
		}
	}

	/**
	 * Read from the socket at most $len bytes.
	 *
	 * This method will not wait for all the requested data, it will return as
	 * soon as any data is received.
	 *
	 * @param int $len Maximum number of bytes to read.
	 * @return string Binary data
	 */
	public function read($len) {
		$null = null;
		$read = array($this->handle_);
		$readable = @stream_select($read, $null, $null, $this->recvTimeoutSec_, $this->recvTimeoutUsec_);

		if ($readable > 0) {
			$data = @stream_socket_recvfrom($this->handle_, $len);
			if ($data === false) {
				throw new TTransportException('TSocket: Could not read '.$len.' bytes from '.
				$this->host_.':'.$this->port_);
			} elseif($data == '' && feof($this->handle_)) {
				throw new TTransportException('TSocket read 0 bytes');
			}

			return $data;
		} else if ($readable === 0) {
		    return null;
			//throw new TTransportException('TSocket: timed out reading '.$len.' bytes from '. $this->host_.':'.$this->port_);
		} else {
			throw new TTransportException('TSocket: Could not read '.$len.' bytes from '.
			$this->host_.':'.$this->port_);
		}
	}

	/**
	 * Write to the socket.
	 *
	 * @param string $buf The data to write
	 */
	public function write($buf) {
		$null = null;
		$write = array($this->handle_);

		// keep writing until all the data has been written
		while (strlen($buf) > 0) {
			// wait for stream to become available for writing
			$writable = @stream_select($null, $write, $null, $this->sendTimeoutSec_, $this->sendTimeoutUsec_);
			if ($writable > 0) {
				// write buffer to stream
				$written = @stream_socket_sendto($this->handle_, $buf);
				if ($written === -1 || $written === false) {
					throw new TTransportException('TSocket: Could not write '.strlen($buf).' bytes '.
					$this->host_.':'.$this->port_);
				}
				// determine how much of the buffer is left to write
				$buf = substr($buf, $written);
			} else if ($writable === 0) {
				throw new TTransportException('TSocket: timed out writing '.strlen($buf).' bytes from '.
				$this->host_.':'.$this->port_);
			} else {
				throw new TTransportException('TSocket: Could not write '.strlen($buf).' bytes '.
				$this->host_.':'.$this->port_);
			}
		}
	}

	/**
	 * Flush output to the socket.
	 *
	 * Since read(), readAll() and write() operate on the sockets directly,
	 * this is a no-op
	 *
	 * If you wish to have flushable buffering behaviour, wrap this TSocket
	 * in a TBufferedTransport.
	 */
	public function flush() {
		// no-op
	}
}
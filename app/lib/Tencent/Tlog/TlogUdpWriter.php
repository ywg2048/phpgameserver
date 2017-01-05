<?php
class TlogUdpWriter extends TlogWriter {
	private $so = null;
	private $tlog_host = null;
	private $tlog_port = null;
	
	/**
	 *
	 * @param string $host        	
	 * @param number $port        	
	 */
	function __construct($host, $port) {
		$this->tlog_host = $host;
		$this->tlog_port = $port;
	}
	
	/**
	 */
	function __destruct() {
		$this->closeSo ();
	}
	
	/**
	 * send message
	 *
	 * @param string $msg        	
	 */
	public function write($msg) {
		$msg .= "\n";
		$so = $this->createSo ();
		$host = $this->tlog_host;
		$port = $this->tlog_port;
		$result = socket_sendto ( $so, $msg, strlen ( $msg ), 0, $host, $port );
		if (! $result) {
			throw new Exception ( 'send error' );
		}
	}
	
	/**
	 * create socket
	 *
	 * @return socket
	 */
	private function createSo() {
		if ($this->so == null) {
			$this->so = socket_create ( AF_INET, SOCK_DGRAM, SOL_UDP );
		}
		return $this->so;
	}
	
	/**
	 * close socket
	 */
	private function closeSo() {
		if ($this->so != null) {
			socket_close ( $this->so );
		}
		$this->so = null;
	}
}

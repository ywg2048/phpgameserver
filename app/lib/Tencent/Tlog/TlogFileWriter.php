<?php
class TlogFileWriter extends TlogWriter {
	protected $log_file = null;
	
	/**
	 *
	 * @param string $logfile        	
	 */
	function __construct($logfile) {
		$this->log_file = $logfile;
	}
	
	/**
	 * write message
	 *
	 * @param string $msg        	
	 */
	public function write($msg) {
	// TODO: いったんコメントアウト
	/*
		$msg .= "\n";
		$file = fopen ( $this->log_file, "a" ) or die ( "Unable to open file!" );
		fwrite ( $file, $msg );
		fclose ( $file );
	*/
	}
}

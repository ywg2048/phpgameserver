<?php
/**
 * #PADC#
 */
class MsdkApiException extends Exception {
	const ERROR_TRY_LATER = 3000111;
	const ERROR_DUPLICATE = 1002215;
	private $result = null;
	
	/**
	 *
	 * @param number $code        	
	 * @param string $message        	
	 * @param array $result        	
	 */
	public function __construct($code = 0, $message = '', $result = null) {
		parent::__construct ( $message, $code, null );
		
		$this->result = $result;
	}
	
	/**
	 *
	 * @return array
	 */
	public function getResult() {
		return $this->result;
	}
}

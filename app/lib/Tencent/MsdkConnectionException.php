<?php
/**
 * #PADC#
 */
class MsdkConnectionException extends Exception {
	public function __construct($message = '') {
		parent::__construct ( $message, 0, null );
	}
}

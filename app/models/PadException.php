<?php
/**
 * PAD例外クラス
 */
class PadException extends Exception
{
    public function __construct($code = 0, $message = '', Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

}
?>
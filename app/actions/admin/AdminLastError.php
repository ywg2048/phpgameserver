<?php
/**
 * Admin用：魔法石付与
 */
class AdminLastError extends AdminBaseAction {
	public function action($params) {
		$html = isset ( $params ['html'] ) ? $params ['html'] : 1;
		
		$file = fopen ( Padc_Log_Log::getErrorLogFile (), "r" ) or die ( "Unable to open file!" );
		$err_log = '';
		while ( ($line = fgets ( $file )) !== FALSE ) {
			if (substr ( $line, 0, 1 ) != ' ' && strpos ( $line, ' Exception: ' ) !== FALSE) {
				$err_log = '';
			}
			$line = rtrim ( $line );
			if ($html) {
				$err_log .= str_replace ( ' ', '&nbsp;', $line ) . '<br>';
			} else {
				$err_log .= $line . "\n";
			}
		}
		fclose ( $file );
		
		return $err_log;
	}
}

<?php
class SendTlog extends BaseAction {
	// このアクションへのコールはログイン必要なし
	const LOGIN_REQUIRED = FALSE;
	
	// Tlog側のバージョン
	const TLOG_TUTORIAL_MINI_VER = 0;
	const TLOG_TUTORIAL_FULL_VER = 1;
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see BaseAction::action()
	 */
	public function action($params) {
		$tver = isset ( $params ['tver'] ) ? $params ['tver'] : 0;
		$fullVer = self::getTutoVer ( $tver );
		Padc_Log_Log::sendGuideFlow ( $params ['t'], $params ['ten_oid'], $params ['send_type'], $fullVer, $params ['pt'] );
		return json_encode ( array (
				'res' => RespCode::SUCCESS 
		) );
	}
	
	/**
	 *
	 * @param number $ver        	
	 * @return number
	 */
	private function getTutoVer($ver) {
		if ($ver == 1) {
			return self::TLOG_TUTORIAL_MINI_VER;
		} elseif ($ver == 2) {
			return self::TLOG_TUTORIAL_FULL_VER;
		} else {
			// default full ver
			return self::TLOG_TUTORIAL_FULL_VER;
		}
	}
}

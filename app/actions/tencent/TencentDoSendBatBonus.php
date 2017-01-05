<?php
/**
 * Tencent用：賞品センターでまとめて贈呈
 */
class TencentDoSendBatBonus extends TencentBaseAction {
	/**
	 *
	 * @see TencentBaseAction::action()
	 */
	public function action($params) {
		$area_id = isset($params ['AreaId']) ? $params ['AreaId'] : 0;
		$ptype = static::convertArea($area_id);
		$type = $params ['PlatId'];
		$count = $params ['BatItemList_count'];
		$items = $params ['BatItemList'];
		$source = isset ( $params ['Source'] ) ? $params ['Source'] : null;
		$serial = isset ( $params ['Serial'] ) ? $params ['Serial'] : null;
		$cmd = $params['Cmdid'];
		$mail_title = $params['MailTitle'];
		$mail_content = $params['MailContent'];

		$idip_data = array(
			'area' => $area_id,
			'ptype' => $ptype,
			'source' => $source,
			'serial' => $serial,
			'cmd' => $cmd,
		);

		if($ptype == 0){
			throw new PadException(static::ERR_INVALID_REQ, 'Unknown area!' );
		}
		
		if(!static::checkItemList($items)){
			throw new PadException(static::ERR_INVALID_REQ, 'Invalid request!' );
		}
		
		$pdo_share = Env::getDbConnectionForShare ();
		try {
			$pdo_share->beginTransaction ();
			foreach ( $items as $item ) {
				$tencent_bonus = new TencentBonus ();
				$tencent_bonus->device_type = $type;
				$tencent_bonus->ptype = $ptype;
				$tencent_bonus->idip_data = json_encode($idip_data); // #PADC_DY# IDIP数据用来记录 IDIPFLOW TLog
				$tencent_bonus->title = $mail_title;
				$tencent_bonus->message = $mail_content;
				$tencent_bonus->bonus_id = $item ['ItemId'];
				$tencent_bonus->amount = $item ['ItemNum'];
				if ($item ['ItemId'] == BaseBonus::PIECE_ID) {
					$tencent_bonus->piece_id = $item ['Uuid'];
				} else {
					$tencent_bonus->piece_id = 0;
				}
				
				$tencent_bonus->create ( $pdo_share );
			}
			$pdo_share->commit ();
		} catch ( Exception $e ) {
			if ($pdo_share->inTransaction ()) {
				$pdo_share->rollback ();
			}
			throw $e;
		}
		
		TencentBonus::removeCache ( $type, $ptype );
		
		return json_encode ( array (
				'res' => 0,
				'msg' => 'OK',
				'Result' => 0,
				'RetMsg' => 'OK' 
		) );
	}
}

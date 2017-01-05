<?php
/**
 * Tencent用：賞品センターでアイテムの贈呈
 */
class TencentDoSendItem extends TencentBaseAction {
	/**
	 *
	 * @see TencentBaseAction::action()
	 */
	public function action($params) {
		$area_id = isset($params ['AreaId'])?$params ['AreaId']:0;
		$ptype = static::convertArea($params ['AreaId']);
		$type = $params ['PlatId'];
		$openid = $params ['OpenId'];
		$count = $params ['ItemList_count'];
		$items = $params ['ItemList'];
		$source = $params ['Source'];
		$serial = $params ['Serial'];
		$mail_title = $params ['MailTitle'];
		$mail_content = $params ['MailContent'];
		$cmd = $params ['Cmdid'];
		
		if($ptype == 0){
			throw new PadException(static::ERR_INVALID_REQ, 'Unknown area!' );
		}
		
		$user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
		
		$pdo = Env::getDbConnectionForUserWrite ( $user_id );
		
		if(!static::checkItemList($items)){
			throw new PadException(static::ERR_INVALID_REQ, 'Invalid request!' );
		}
		
		foreach ( $items as $item ) {
			$item_id = $item ['ItemId'];
			$item_num = $item ['ItemNum'];
			$uuid = $item ['Uuid'];
			
			if($item_id == BaseBonus::COIN_ID 
					|| $item_id == BaseBonus::MAGIC_STONE_ID 
					|| $item_id == BaseBonus::FRIEND_POINT_ID
					|| $item_id == BaseBonus::ROUND_ID
					|| $item_id == BaseBonus::USER_EXP
					|| $item_id == BaseBonus::USER_VIP_EXP
					|| $item_id == BaseBonus::STAMINA_RECOVER_ID ){
				UserMail::sendAdminMailMessage ($user_id, UserMail::TYPE_ADMIN_BONUS, $item_id, $item_num, $pdo, $mail_content, null, 0, $mail_title);
			}else if($item_id == BaseBonus::PIECE_ID){
				$piece = Piece::find ( $uuid );
				if ($piece) {
					UserMail::sendAdminMailMessage ($user_id, UserMail::TYPE_ADMIN_BONUS, $item_id, $item_num, $pdo, $mail_content, null, $uuid, $mail_title);
				}else{
					throw new PadException(RespCode::UNKNOWN_ERROR, "Piece id not found:" . $uuid);
				}
			}else{
				throw new PadException(RespCode::UNKNOWN_ERROR, "Unsupported item id");
			}
			
			Padc_Log_Log::sendIDIPFlow ( $area_id, $openid, $item_id, $item_num, $serial, $source, $cmd, $uuid, $ptype );
		}
		
		return json_encode ( array (
				'res' => 0,
				'msg' => 'OK',
				'Result' => 0,
				'RetMsg' => 'OK' 
		) );
	}
}

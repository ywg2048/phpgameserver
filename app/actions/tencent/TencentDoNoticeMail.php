<?php

/**
 * #PADC_DY#
 * 全服邮件
 */
class TencentDoNoticeMail extends TencentBaseAction {

	/**
	 *
	 * @see TencentBaseAction::action()
	 */
	public function action($params) {
		$area_id = isset($params['AreaId']) ? $params['AreaId'] : 0;
		$ptype = static::convertArea($params['AreaId']);
		$type = $params['PlatId'];
		$source = $params['Source'];
		$serial = $params['Serial'];
		$mail_title = $params['MailTitle'];
		$mail_content = $params['MailContent'];

		if ($ptype == 0) {
			throw new PadException(static::ERR_INVALID_REQ, 'Unknown area!');
		}

		$pdo_share = Env::getDbConnectionForShare();
		try {
			$tencent_bonus = new TencentBonus();
			$tencent_bonus->device_type = $type;
			$tencent_bonus->ptype = $ptype;
			$tencent_bonus->title = $mail_title;
			$tencent_bonus->message = $mail_content;
			$tencent_bonus->bonus_id = null;
			$tencent_bonus->amount = null;
			$tencent_bonus->piece_id = null;
			$tencent_bonus->create($pdo_share);
		} catch (Exception $e) {
			throw $e;
		}

		TencentBonus::removeCache($type, $ptype);

		return json_encode(array(
			'res' => 0,
			'msg' => 'success',
			'Result' => 0,
			'RetMsg' => 'success'
		));
	}

}

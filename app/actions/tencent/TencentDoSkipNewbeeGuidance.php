<?php

/**
 * #PADC_DY#
 * 跳过新手引导
 */
class TencentDoSkipNewbeeGuidance extends TencentBaseAction {

	/**
	 *
	 * @see TencentBaseAction::action()
	 */
	public function action($params) {
		$ptype = static::convertArea($params['AreaId']);
		$type = $params['PlatId'];
		$openid = $params['OpenId'];
		$source = $params['Source'];
		$serial = $params['Serial'];

		return json_encode(array(
			'res' => 0,
			'msg' => 'success',
			'Result' => -1,
			'RetMsg' => 'Not Implemented'
		));
	}

}

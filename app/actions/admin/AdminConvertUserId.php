<?php
/**
 * Admin用:ユーザID変換
 */
class AdminConvertUserId extends AdminBaseAction
{
	static $textData = array();

	public function action($params)
	{
		$type	= $params['t'];

		// ユーザID→表示ID
		if($type)
		{
			$user_id		= $params['disp_id'];
			$device_type	= $params['dt'];
			$ptype			= $params['pt'];
			$pre_pid		= UserDevice::getPreUserId($device_type, $ptype);
			$disp_id		= UserDevice::convertPlayerIdToDispId($pre_pid, $user_id);
		}
		// 表示ID→ユーザID
		else
		{
			$disp_id	= $params['disp_id'];
			$user_id	= UserDevice::convertDispIdToPlayerId($disp_id);
		}
		//--------------------------------------------------
		// レスポンス整形
		//--------------------------------------------------
		$result = array(
			'ユーザID'	=> $user_id,
			'表示ID'		=> $disp_id,
		);
		return json_encode($result);
	}
}
<?php
/**
 * Admin用：ユーザーカウントデータ確認
 */
class AdminCheckUserCount extends AdminBaseAction {
	public function action($params) {
		if(isset($params['pid']))
		{
			$user_id	= $params['pid'];
			$userDeviceData = UserDevice::getUserDeviceFromRedis($user_id);// 指定のユーザが存在しているかチェック
		}
		else
		{
			$openid		= $params['ten_oid'];
			$type		= $params['t'];
			$user_id	= UserDevice::getUserIdFromUserOpenId($type,$openid);
		}
		$unregistered = '<span class="caution">未登録</span>';

		//--------------------------------------------------
		// PDO
		//--------------------------------------------------

		$pdo_user	= Env::getDbConnectionForUserRead($user_id);
		$pdo_share	= Env::getDbConnectionForShareRead();

		//--------------------------------------------------
		// ユーザ詳細情報を取得
		//--------------------------------------------------
		$user = User::find($user_id,$pdo_user,true);
		if($user == false)
		{
			$userInfo = array(
					'ユーザ詳細情報' => $unregistered,
			);
		}
		else {
			$userInfo = array(
					'プレイヤー名'			=> $user->name,
			);
		}

		// ユーザーカウントデータ取得
		$user_count = UserCount::findBy(array("user_id"=>$user_id), $pdo_user);
		if ($user_count) {
			$user_count_array = array(
			
					'ノーマルダンジョンクリア回数（累計）'			=> array($user_count->clear_normal, 'clear_normal'),
					'ノーマルダンジョンクリア回数（デイリー）'		=> array($user_count->clear_normal_daily, 'clear_normal_daily'),
					'スペシャルダンジョンクリア回数（累計）'		=> array($user_count->clear_special, 'clear_special'),
					'スペシャルダンジョンクリア回数（デイリー）'	=> array($user_count->clear_special_daily, 'clear_special_daily'),
					'友情ポイントガチャ回数（累計）'				=> array($user_count->gacha_friend, 'gacha_friend'),
					'友情ポイントガチャ回数（デイリー）'			=> array($user_count->gacha_friend_daily, 'gacha_friend_daily'),
					'魔法石ガチャ回数（累計）'						=> array($user_count->gacha_gold, 'gacha_gold'),
					'魔法石ガチャ回数（デイリー）'					=> array($user_count->gacha_gold_daily, 'gacha_gold_daily'),
					'強化合成回数（累計）'							=> array($user_count->card_composite, 'card_composite'),
					'強化合成回数（デイリー）'						=> array($user_count->card_composite_daily, 'card_composite_daily'),
					'進化合成回数（累計）'							=> array($user_count->card_evolve, 'card_evolve'),
					'進化合成回数（デイリー）'						=> array($user_count->card_evolve_daily, 'card_evolve_daily'),
					'モンスター生成回数（累計）'					=> array($user_count->card_create, 'card_create'),
					'モンスター生成回数（デイリー）'				=> array($user_count->card_create_daily, 'card_create_daily'),
					'プラス合成回数（累計）'						=> array($user_count->card_plus, 'card_plus'),
					'プラス合成回数（デイリー）'					=> array($user_count->card_plus_daily, 'card_plus_daily'),
					'スキルアップ合成回数（累計）'					=> array($user_count->card_skill_up, 'card_skill_up'),
					'スキルアップ合成回数（デイリー）'				=> array($user_count->card_skill_up_daily, 'card_skill_up_daily'),
					'スタミナプレゼント回数（累計）'				=> array($user_count->stamina_present, 'stamina_present'),
					'スタミナプレゼント回数（デイリー）'			=> array($user_count->stamina_present_daily, 'stamina_present_daily'),
					'デイリーカウントリセット日時'					=> array($user_count->daily_reset_at, 'daily_reset_at'),
			
			);
		}
		else {
			$user_count_array = array();
		}
		
		//--------------------------------------------------
		// レスポンス整形
		//--------------------------------------------------
		$result = array(
				'format' => 'array',
				'ユーザ情報'				=> $userInfo,
				'カウントデータ'			=> array(
						'format' => 'html',
						self::getFormList($user_id, $user_count_array),
				),
		);


		return json_encode ( $result );
	}

	static private function getFormList($user_id, $requestParams)
	{
		$formList	= '<form action="'.REQUEST_URL_ADMIN.'" method="post">';
		$formList	.= '<input type="hidden" name="action" value="admin_update_user_count" />';
		$formList	.= '<input type="hidden" name="pid" value="'. $user_id .'" />';
		$formList	.= '<table border="1" style="margin:10px 5px 10px 10px;">';
		
		$formList	.=  '<tr style="background:#ffffcc;">'
					. '<th>項目</th>'
					. '<th>値</th>'
					. '</tr>';
		
		foreach($requestParams as $_key => $_value)
		{
			$name			= $_key;
			$value			= $_value[0];
			$column_name	= $_value[1];
			
			$td		= '<td>';
			$tdend	= '</td>';
				
			$value	= '<input type="text"'
						. ' name="' . $column_name .'"'
						. ' value="' . $value . '"'
						. ' style="width:100%"></input>';
			
			$formList	.= '<tr>'
						. $td . $name . $tdend
						. $td . $value . $tdend
						. '</tr>';
		}
		$formList	.= '<tr><td><input type="hidden" name="backlink" value="1" /><input type="submit" value="更新" /></td></tr>'
					. '</table>';
		$formList .= '</form>';
		return $formList;
	}
}

<?php
/**
 * Admin用：ユーザーカードデータ確認
 */
class AdminCheckUserCard extends AdminBaseAction {
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

		// ユーザーカードデータ取得
		$user_cards = UserCard::findAllBy(array("user_id"=>$user_id), null, null, $pdo_user);
		if ($user_cards) {
			$user_count_array = array(
				array('ID', 'モンスターID', 'cuid', 'LV', 'SLV', '+HP', '+攻撃', '+回復'),
			);
			
			foreach($user_cards as $user_card) {
				$user_count_array[] = array(
					$user_card->id,
					$user_card->card_id,
					$user_card->cuid,
					$user_card->lv,
					$user_card->slv,
					$user_card->equip1,
					$user_card->equip2,
					$user_card->equip3,
				);
			}
			
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
				'所持モンスターデータ'			=> array(
						'format' => 'html',
						self::getFormList($user_id, $user_count_array),
				),
		);


		return json_encode ( $result );
	}

	static private function getFormList($user_id, $requestParams)
	{
		// モンスター名取得
		$card = new Card();
		$cardNames = self::getNamesByDao($card);
		
		// 翻訳データ
		$textData = ConvertText::getConvertTextArrayByTextKey();
		
		
		$formList	= '<table border="1" style="margin:10px 5px 10px 10px;">';
		
		foreach($requestParams as $_key => $_value)
		{
			$id			= $_value[0];
			$card_id	= $_value[1];
			$cuid		= $_value[2];
			$lv			= $_value[3];
			$slv		= $_value[4];
			$plus_hp	= $_value[5];
			$plus_atk	= $_value[6];
			$plus_def	= $_value[7];
			$update_btn	= '';
				
			if ($_key == 0) {
				$tr		= '<tr style="background:#ffffcc;">';
				$trend	= '</tr>';
				$td		= '<th>';
				$tdend	= '</th>';
			}
			else {
				$tr		= '<tr>';
				$tr		.= '<form action="'.REQUEST_URL_ADMIN.'" method="post">';
				$tr		.= '<input type="hidden" name="action" value="admin_update_user_card" />';
				$tr		.= '<input type="hidden" name="pid" value="'. $user_id .'" />';
				$tr		.= '<input type="hidden" name="cuid" value="'. $cuid .'" />';
				$trend	= '</form></tr>';
				
				$td		= '<td>';
				$tdend	= '</td>';
				
				$card_id	= self::getNameFromArray($card_id, $cardNames, $textData);
				
				$lv	= '<input type="text"'
						. ' name="lv"'
						. ' value="' . $lv . '"'
						. ' style="text-align:right"></input>';
					
				$slv	= '<input type="text"'
						. ' name="slv"'
						. ' value="' . $slv . '"'
						. ' style="text-align:right"></input>';
					
				$plus_hp	= '<input type="text"'
							. ' name="plus_hp"'
							. ' value="' . $plus_hp . '"'
							. ' style="text-align:right"></input>';
					
				$plus_atk	= '<input type="text"'
							. ' name="plus_atk"'
							. ' value="' . $plus_atk . '"'
							. ' style="text-align:right"></input>';
					
				$plus_def	= '<input type="text"'
							. ' name="plus_def"'
							. ' value="' . $plus_def . '"'
							. ' style="text-align:right"></input>';
				
				$update_btn = '<input type="hidden" name="backlink" value="1" /><input type="submit" value="更新" />';
			}
				
			$formList	.= $tr
						. $td . $id . $tdend
						. $td . $card_id . $tdend
						. $td . $cuid . $tdend
						. $td . $lv . $tdend
						. $td . $slv . $tdend
						. $td . $plus_hp . $tdend
						. $td . $plus_atk . $tdend
						. $td . $plus_def . $tdend
						. $td . $update_btn . $tdend
						. $trend;
		}
		$formList .= '</table>';
		return $formList;
	}
}

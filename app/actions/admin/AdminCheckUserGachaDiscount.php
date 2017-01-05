<?php
/**
 * Admin用：ユーザーガチャ初回割引利用データ確認
 */
class AdminCheckUserGachaDiscount extends AdminBaseAction {
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
					'プレイヤー名'				=> $user->name,
			);
		}

 		// ガチャ割引データ全取得
		$gacha_discounts = GachaDiscount::getAll();
		
		// ユーザーガチャ初回割引利用データ取得
		$get_user_gacha_discounts = UserGachaDiscount::findAllBy(array('user_id' => $user_id), null, null, $pdo_user);
		$user_gacha_discounts = array();
		foreach ($get_user_gacha_discounts as $ugd) {
			$user_gacha_discounts[$ugd->discount_id] = $ugd;
		}

		$user_gacha_discount_array = array(
				array('id', 'ガチャタイプ', 'ガチャ回数', '割引率', '開始日時', '終了日時', '利用日時', '削除ボタン'),
		);
		foreach ($gacha_discounts as $gd) {
			$discount_id = $gd->id;
			$user_gacha_discount_array[] = array(
					$discount_id,
					$gd->gacha_type,
					$gd->gacha_count,
					$gd->ratio,
					$gd->begin_at,
					$gd->finish_at,
					array_key_exists($discount_id, $user_gacha_discounts) ? $user_gacha_discounts[$discount_id]->created_at : null,
					'',
			);
		}

		//--------------------------------------------------
		// レスポンス整形
		//--------------------------------------------------
		$result = array(
				'format' => 'array',
				'ユーザ情報'				=> $userInfo,
				'ガチャ初回割引データ'		=> array(
						'format' => 'html',
						self::getFormList($user_id, $user_gacha_discount_array),
				),
				
		);

		return json_encode ( $result );
	}

	static private function getFormList($user_id, $requestParams)
	{
		$formList	= '<form action="'.REQUEST_URL_ADMIN.'" method="get">';
		$formList	.= '<input type="hidden" name="action" value="admin_delete_user_gacha_discount" />';
		$formList	.= '<input type="hidden" name="pid" value="'. $user_id .'" />';
		$formList	.= '<input type="hidden" name="backlink" value="1" />';
		$formList	.= '<table border="1" style="margin:10px 5px 10px 10px;">';
		foreach($requestParams as $_key => $_value)
		{
			$discount_id		= $_value[0];
			$gacha_type			= $_value[1];
			$gacha_count		= $_value[2];
			$ratio				= $_value[3];
			$begin_at			= $_value[4];
			$finish_at			= $_value[5];
			$used_at			= $_value[6];
			$delete_button		= $_value[7];
				
			if ($_key == 0) {
				$formList	.=  '<tr style="background:#ffffcc;">';
				$td		= '<th>';
				$tdend	= '</th>';
			}
			else {
				$formList	.= '<tr>';
				$td		= '<td>';
				$tdend	= '</td>';
				
				if (is_null($used_at)) {
					$used_at			= '-';
				}
				else {
					$delete_button = '<button type="submit" name="dis_id" value="'.$discount_id.'" />利用データ削除</button>';
				}
			}
		
			$formList	.= $td . $discount_id . $tdend
						. $td . $gacha_type . $tdend
						. $td . $gacha_count . $tdend
						. $td . $ratio . $tdend
						. $td . $begin_at . $tdend
						. $td . $finish_at . $tdend
						. $td . $used_at . $tdend
						. $td . $delete_button . $tdend
						. '</tr>';
		}
		$formList .= '</table>';
		$formList .= '</form>';
		return $formList;
	}
}

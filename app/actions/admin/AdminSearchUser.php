<?php
/**
 * Admin用:ユーザデータ検索
 */
class AdminSearchUser extends AdminBaseAction
{
	const ADMIN_UPDATE_ACTION_NAME = "admin_update_user";

	static $textData = array();

	public function action($params)
	{
		if(isset($params['pid']))
		{
			$user_id	= $params['pid'];
			$userDeviceData = UserDevice::getUserDeviceFromRedis($user_id);// 指定のユーザが存在しているかチェック
		}
		else if(isset($params['ten_oid']))
		{
			$openid		= $params['ten_oid'];
			$type		= $params['t'];
			$user_id	= UserDevice::getUserIdFromUserOpenId($type,$openid);
		}
		else if(isset($params['disp_id']))
		{
			$disp_id	= $params['disp_id'];
			$user_id	= UserDevice::convertDispIdToPlayerId($disp_id);
			$userDeviceData = UserDevice::getUserDeviceFromRedis($user_id);// 指定のユーザが存在しているかチェック
		}
		

		$unregistered = '<span class="caution">未登録</span>';

		//--------------------------------------------------
		// PDO
		//--------------------------------------------------

		$pdo_user	= Env::getDbConnectionForUserRead($user_id);
		$pdo_share	= Env::getDbConnectionForShareRead();

		//--------------------------------------------------
		// 各種名称を取得
		//--------------------------------------------------
		// ダンジョン名取得
		$dungeon = new Dungeon();
		$dungeonNames = self::getNamesByDao($dungeon);
		// ダンジョンフロア名取得
		$dungeonFloor = new DungeonFloor();
		$dungeonFloorNames = self::getNamesByDao($dungeonFloor);
		// モンスター名取得
		$card = new Card();
		$cardNames = self::getNamesByDao($card);
		// 欠片名取得
		$piece = new Piece();
		$pieceNames = self::getNamesByDao($piece);

		// 翻訳データ
		self::$textData = ConvertText::getConvertTextArrayByTextKey();

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
		else
		{
			/*
			$userInfo = array(
				'プレイヤー名'				=> $user->name,
				'初期モンスター'				=> $user->camp,
				'プレイヤーLV（未使用）'		=> $user->lv,
				'プレイヤーEXP（未使用）'		=> $user->exp,
				'スタミナ'					=> $user->stamina . ' / ' . $user->stamina_max,
				'スタミナ回復時間'			=> $user->stamina_recover_time,
				'魔法石（無料分）'			=> $user->gold,
				'魔法石'					=> $user->pgold,
				'コイン'					=> $user->coin,
				'友情ポイント'				=> $user->fripnt,
				'pbflg'					=> $user->pbflg,
				'カード所持上限（未使用）'		=> $user->card_max,
				'最終ログイン時間'			=> $user->li_last,
				'連続ログイン回数'			=> $user->li_str,
				'連続ログイン最大数'			=> $user->li_max,
				'総ログイン回数'			=> $user->li_cnt,
				'累計ログイン回数'			=> $user->li_days,
				'コスト上限'				=> $user->cost_max,
				'フレンド数'				=> $user->fricnt,
				'お礼返し保持数（未使用）'	=> $user->pback_cnt,
				'フレンド上限'				=> $user->friend_max,
				'リーダーモンスター'			=> $user->lc,
				'リーダーデッキ'				=> $user->ldeck,
				'ビットフィールド'				=> $user->us,
				'メダル'					=> $user->medal,
				'最終アクセス日時'			=> $user->accessed_at,
				'最終アクセス日'			=> $user->accessed_on,
				'機種'					=> $user->dev,
				'OSバージョン'				=> $user->osv,
				'デバイスタイプ'				=> $user->device_type,
				'エリアID'					=> $user->area_id,
				'削除ステータス'				=> $user->del_status,
				'クリアダンジョン数'			=> $user->clear_dungeon_cnt,
				'最終クリアダンジョン'			=> self::getNameFromArray($user->last_clear_dungeon_id,$dungeonNames),
				'ユーザ作成日'				=> $user->created_on,
				'ユーザ作成日時'			=> $user->created_at,
				'情報更新日時'			=> $user->updated_at,
			);
			*/

			// 項目名、値、編集可能か否か、カラム名
			$userInfo = array(
				array('player名', $user->name, true, 'name'),
				array('初期Monster', $user->camp, true, 'camp'),
				array('playerLV（修改后EXP也需要修改）', $user->lv, true, 'lv'),
				array('playerEXP（修改LV同时也要修改经验值）', $user->exp, true, 'exp'),
				array('体力', $user->stamina, true, 'stamina'),
				array('体力最大値', $user->stamina_max, true, 'stamina_max'),
				array('体力回復時間', $user->stamina_recover_time, true, 'stamina_recover_time'),
				array('魔法石（免费）', $user->gold, false, ''),
				array('魔法石', $user->pgold, false, ''),
				array('金币', $user->coin, true, 'coin'),
				array('友情点数', $user->fripnt, true, 'fripnt'),
				array('交换点数', $user->exchange_point, true, 'exchange_point'),
				array('ranking point', $user->ranking_point, true, 'ranking_point'),
				array('カード所持上限（未使用）', $user->card_max, false, ''),
				array('最后login时间', $user->li_last, true, 'li_last'),
				array('連続login回数', $user->li_str, true, 'li_str'),
				array('連続login最大数', $user->li_max, true, 'li_max'),
				array('総login回数', $user->li_cnt, true, 'li_cnt'),
				array('累計login日数', $user->li_days, true, 'li_days'),
				array('当月累計login日数', $user->li_mdays, true, 'li_mdays'),
				array('連続loginミッション報酬受け取り日時（未使用）', $user->li_mission_date, false, 'li_mission_date'),
				array('login期間ID', $user->li_period, true, 'li_period'),
				array('コスト上限（未使用）', $user->cost_max, false, ''),
				array('friend数', $user->fricnt, false, ''),
				array('friend上限', $user->friend_max, true, 'friend_max'),
				array('friend申請メール数', $user->fr_cnt, false, ''),
				array('お礼返しを送ったユーザid（未使用）', $user->pbflg, false, ''),
				array('お礼返し保持数（未使用）', $user->pback_cnt, false, ''),
				array('leader信息', $user->lc, false, ''),
				array('leader编队信息', $user->ldeck, false, ''),
				array('ユーザー設定（ビットフィールド）', $user->us, true, 'us'),
				array('最終アクセス日時', $user->accessed_at, false, ''),
				array('最終アクセス日', $user->accessed_on, false, ''),
				array('機種', $user->dev, false, ''),
				array('OSバージョン', $user->osv, false, ''),
				array('デバイスタイプ', $user->device_type, false, ''),
				array('エリアID', $user->area_id, false, ''),
				array('削除ステータス', $user->del_status, true, 'del_status'),
				array('クリアダンジョン数', $user->clear_dungeon_cnt, false, ''),
				array('最終クリアダンジョン', self::getNameFromArray($user->last_clear_dungeon_id,$dungeonNames), false, ''),
				array('VIPレベル', $user->vip_lv, true, 'vip_lv'),
				array('総計魔法石購入数', $user->tp_gold, true, 'tp_gold'),
				array('扫荡券数', $user->round, true, 'round'),
				array('無料コンティニュー回数', $user->cont, true, 'cont'),
				array('月卡结束时间', $user->tss_end, true, 'tss_end'),
				array('図鑑登録数', $user->book_cnt, false, ''),
				array('最後VIP weeklybonusを取得する時間', $user->last_vip_weekly, true, 'last_vip_weekly'),
				array('最後月額課金デーリーbonusを取得する時間', $user->last_subs_daily, true, 'last_subs_daily'),
				array('体力赠送受取る毎日上限', $user->present_receive_max, true, 'present_receive_max'),
				array('直近でクリアしたノーマルダンジョンID', $user->last_clear_normal_dungeon_id, false, ''),
				array('直近でクリアしたスペシャルダンジョンID', $user->last_clear_sp_dungeon_id, false, ''),
				array('IDIP付与された魔法石', $user->reserve_gold, false, ''),
				array('最終レベルアップ時間', $user->last_lv_up, false, ''),
				array('loginチャンネル', $user->login_channel, false, ''),
				array('デバイスID', $user->device_id, false, ''),
				array('QQ会員状態 0:会員ではない 1:会員 2:超級会員', $user->qq_vip, false, ''),
				array('QQ会員ギフト受け取り状態', $user->qq_vip_gift, false, ''),
				array('QQ会員切れ時間', $user->qq_vip_expire, false, ''),
				array('QQ超級会員切れ時間', $user->qq_svip_expire, false, ''),
				array('QQ会員loginbonus最終受け取り日数', $user->lqvdb_days, false, ''),
				array('ユーザ作成日', $user->created_on, false, ''),
				array('ユーザ作成日時', $user->created_at, false, ''),
				array('情報更新日時', $user->updated_at, false, ''),
			);
		}

		//--------------------------------------------------
		// ユーザ情報を取得
		//--------------------------------------------------
		$userDevice = new UserDevice();
		$user_device = $userDevice->findBy(array('id' => $user_id),$pdo_share);
		if($userDevice == false)
		{
			$userDeviceInfo = array(
				'ユーザ情報' => $unregistered,
			);
		}
		else
		{
			if (!isset($disp_id)) {
				$pre_pid = UserDevice::getPreUserId($user_device->type, $user_device->ptype);
				$disp_id = UserDevice::convertPlayerIdToDispId($pre_pid, $user_id);
			}
			$userDeviceInfo = array(
				'playerID'		=> $user_device->id,
				'デバイスタイプ'		=> $user_device->type,
				'UUID'			=> $user_device->uuid,
				'DBID'			=> $user_device->dbid,
				'openID'		=> $user_device->oid,
				'プラットフォームタイプ'	=> $user_device->ptype,
				'バージョン'		=> $user_device->version,
				'表示ID'		=> $disp_id,
			);
		}

		//--------------------------------------------------
		// cuidのカウントを取得
		//--------------------------------------------------
		$cuidCnt = $unregistered;
		$userCardSeq = UserCardSeq::findBy(array('user_id' => $user_id),$pdo_user);
		if($userCardSeq)
		{
			$cuidCnt = $userCardSeq->max_cuid;
		}

		//--------------------------------------------------
		// ダンジョン進行状況
		//--------------------------------------------------
		$tmpKeys = array(
			'dungeon_id'		=> array('ダンジョンID',$dungeonNames),
			'dungeon_floor_id'	=> array('ダンジョンフロアID',$dungeonFloorNames),
			'cleared_at'		=> array('クリア日時',array()),
			'sneak_time'		=> array('潜入開始時間',array()),
		);
		$userDungeonData = self::getDataByUserId('UserDungeon', $tmpKeys, $user_id);

		//--------------------------------------------------
		// ダンジョンフロア進行状況
		//--------------------------------------------------
		$tmpKeys = array(
			'dungeon_id'		=> array('ダンジョンID',$dungeonNames),
			'dungeon_floor_id'	=> array('ダンジョンフロアID',$dungeonFloorNames),
			'first_played_at'	=> array('初回プレイ日時',array()),
			'cleared_at'		=> array('クリア日時',array()),
		);
		$userDungeonFloorData = self::getDataByUserId('UserDungeonFloor', $tmpKeys, $user_id);

		//--------------------------------------------------
		// コンティニュー情報
		//--------------------------------------------------
		$tmpKeys = array(
			'hash'	=> array('Hash',array()),
			'used'	=> array('used',array()),
			'data'	=> array('data',array()),
		);
		$userContinueData = self::getDataByUserId('UserContinue', $tmpKeys, $user_id);

		//--------------------------------------------------
		// 所持欠片
		//--------------------------------------------------
		$tmpKeys = array(
			'id'			=> array('ID',array()),
			'piece_id'		=> array('欠片ID',$pieceNames),
			'num'			=> array('所持数',array()),
			'create_card'	=> array('生成済みか',array()),
		);
		$userPieceData = self::getDataByUserId('UserPiece', $tmpKeys, $user_id);

		//--------------------------------------------------
		// 所持モンスター
		//--------------------------------------------------
		$tmpKeys = array(
			'id'		=> array('ID',array()),
			'card_id'	=> array('モンスターID',$cardNames),
			'cuid'		=> array('cuid',array()),
			'exp'		=> array('EXP',array()),
			'lv'		=> array('LV',array()),
			'slv'		=> array('SLV',array()),
			'equip1'	=> array('HP',array()),
			'equip2'	=> array('攻撃',array()),
			'equip3'	=> array('回復',array()),
		);
		$userCardData = self::getDataByUserId('UserCard', $tmpKeys, $user_id);

		//--------------------------------------------------
		// メール
		//--------------------------------------------------
		$tmpKeys = array(
			'id'		=> array('ID',array()),
			'type'		=> array('メール種別',array()),
			'user_id'	=> array('受信者ユーザID',array()),
			'sender_id'	=> array('送信者ユーザID',array()),
			'title'		=> array('タイトル',array()),
			'message'	=> array('メッセージ',array()),
			'data'		=> array('データ',array()),
			'bonus_id'	=> array('bonusID',array()),
			'amount'	=> array('bonus個数',array()),
			'piece_id'	=> array('欠片ID',array()),
			'slv'		=> array('スキルレベル',array()),
			'plus_hp'	=> array('+値HP',array()),
			'plus_atk'	=> array('+値ATK',array()),
			'plus_rec'	=> array('+値REC',array()),
			'offered'	=> array('bonus付与済み',array()),
			'fav'		=> array('お気に入りフラグ',array()),
		);
		$userMailData = self::getDataByUserId('UserMail', $tmpKeys, $user_id);

		//--------------------------------------------------
		// スタミナプレゼント
		//--------------------------------------------------

		//------------------------------
		// プレゼント送信
		//------------------------------
		$tmpKeys = array(
			'id'			=> array('ID',array()),
			'sender_id'		=> array('送信ユーザID',array()),
			'receiver_id'	=> array('受信ユーザID',array()),
			'created_at'	=> array('送信時間',array()),
		);

		$cond = array('sender_id' => $user_id);
		$userPresentsSendData = UserPresentsSend::findAllBy($cond,null,null,$pdo_user);

		$userPresentsSendData = self::getDataByData($userPresentsSendData,$tmpKeys);

		//------------------------------
		// プレゼント受信
		//------------------------------
		$tmpKeys = array(
			'id'			=> array('ID',array()),
			'sender_id'		=> array('送信ユーザID',array()),
			'receiver_id'	=> array('受信ユーザID',array()),
			'status'		=> array('ステータス',array()),
			'created_at'	=> array('送信時間',array()),
			'expire_at'		=> array('有効期限',array()),
		);

		$cond = array('receiver_id' => $user_id);
		$tmpData = UserPresentsReceive::findAllBy($cond,null,null,$pdo_user);

		$userPresentsReceiveData = self::getDataByData($tmpData,$tmpKeys);

		//--------------------------------------------------
		// フレンド情報
		//--------------------------------------------------
		$sql = "SELECT * FROM ". Friend::TABLE_NAME ." WHERE user_id1 = ? ORDER BY id ASC";
		$bind_param = array($user_id);
		$stmt = $pdo_user->prepare($sql);
		$stmt->execute($bind_param);
		$values = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$tmpData = array('format' => 'array',);
		$tmpData[] = array(
			'ID',	'相手のユーザID',	'SNSフレンドかどうか',	'更新時間',	'作成時間',
		);
		foreach($values as $_data)
		{
			$tmpData[] = array(
				$_data['id'],
				$_data['user_id2'],
				$_data['snsflag'],
				$_data['created_at'],
				$_data['updated_at'],
			);
		}
		$friendData = $tmpData;

		//--------------------------------------------------
		// 全ユーザbonus受取履歴
		//--------------------------------------------------
		$tmpKeys = array(
			'id'				=> array('ID',array()),
			'user_id'			=> array('ユーザID',array()),
			'all_user_bonus_id'	=> array('全ユーザbonusID',array()),
		);
		$allUserBonusHistoryData = self::getDataByUserId('AllUserBonusHistory', $tmpKeys, $user_id);

		//--------------------------------------------------
		// Tencent全ユーザbonus受取履歴
		//--------------------------------------------------
		$tmpKeys = array(
			'id'				=> array('ID',array()),
			'user_id'			=> array('ユーザID',array()),
			'tencent_bonus_id'	=> array('Tencent全ユーザbonusID',array()),
		);
		$userTencentBonusHistoryData = self::getDataByUserId('UserTencentBonusHistory', $tmpKeys, $user_id);

		//--------------------------------------------------
		// BANメッセージ
		//--------------------------------------------------
		$tmpKeys = array(
			'id'						=> array('ID',array()),
			'user_id'					=> array('ユーザID', array()),
			'message'					=> array('BANメッセージ', array()),
			'end_time'					=> array('BAN有効期間', array()),
			'play_ban_normal_message'	=> array('ノーマルダンジョン禁止メッセージ',array()),
			'play_ban_normal_end_time'	=> array('ノーマルダンジョン禁止期間', array()),
			'play_ban_special_message'	=> array('スペシャルダンジョン禁止メッセージ', array()),
			'play_ban_special_end_time'	=> array('スペシャルダンジョン禁止期間', array()),
			'play_ban_ranking_message'	=> array('ランキングダンジョン禁止メッセージ', array()),
			'play_ban_ranking_end_time'	=> array('ランキングダンジョン禁止期間', array()),
			'play_ban_buydung_message'	=> array('購入ダンジョン禁止メッセージ', array()),
			'play_ban_buydung_end_time'	=> array('購入ダンジョン禁止期間', array()),
			'zeroprofit_message'		=> array('零収益メッセージ', array()),
			'zeroprofit_end_time'		=> array('零収益期間', array()),
			'updated_at'				=> array('更新時間', array()),
			'created_at'				=> array('作成時間', array()),
		);
		$userBanMessageData = self::getDataByUserId('UserBanMessage', $tmpKeys, $user_id);

		//--------------------------------------------------
		// 購入ダンジョン
		//--------------------------------------------------
		$tmpKeys = array(
			'id'			=> array('ID',array()),
			'user_id'		=> array('ユーザID',array()),
			'dungeon_id'	=> array('ダンジョンID',$dungeonNames),
			'expire_at'		=> array('有効期間',array()),
			'buy_at'		=> array('購入時間',array()),
			'created_at'	=> array('作成時間',array()),
			'updated_at'	=> array('更新時間',array()),
		);
		$userBuyDungeonData = self::getDataByUserId('UserBuyDungeon', $tmpKeys, $user_id);

		//--------------------------------------------------
		// VIP報酬受取状況
		//--------------------------------------------------
		$tmpKeys = array(
			'id'			=> array('ID',array()),
			'user_id'		=> array('ユーザID',array()),
			'lv'			=> array('VIPLV',array()),
			'created_at'	=> array('作成時間',array()),
			'updated_at'	=> array('更新時間',array()),
		);
		$userVipBonusData = self::getDataByUserId('UserVipBonus', $tmpKeys, $user_id);

		//--------------------------------------------------
		// 図鑑状況
		//--------------------------------------------------
		$tmpKeys = array(
			'id'			=> array('ID',array()),
			'user_id'		=> array('ユーザID',array()),
			'card_ids'		=> array('カードID',array()),
			'created_at'	=> array('作成時間',array()),
			'updated_at'	=> array('更新時間',array()),
		);
		$userBookData = self::getDataByUserId('UserBook', $tmpKeys, $user_id);
		$_card_list = '';
		if(isset($userBookData[1][2]))
		{
			$_card_ids = json_decode($userBookData[1][2]);
			foreach($_card_ids as $_card_id)
			{
				$_card_list .= self::getNameFromArray($_card_id,$cardNames,self::$textData) . '<br />';
			}
		}
		$userBookData[1][2] = $_card_list;

		//--------------------------------------------------
		// レスポンス整形
		//--------------------------------------------------
		$result = array(
			'format' => 'array',
			'ユーザ情報'					=> $userDeviceInfo,
			//'ユーザ詳細情報'			=> $userInfo,
			'ユーザ詳細情報'				=> array('format' => 'html', self::getFormList($user_id, $userInfo)),
			'cuidのカウント'				=> array('cuid' => $cuidCnt,),
			'ダンジョン情報'				=> $userDungeonData,
			'ダンジョンフロア情報'			=> $userDungeonFloorData,
			'ダンジョンコンティニュー情報'		=> $userContinueData,
			'モンスター所持情報'			=> $userCardData,
			'欠片所持情報'				=> $userPieceData,
			'メール'						=> $userMailData,
			'体力赠送（送信）'		=> $userPresentsSendData,
			'体力赠送（受信）'		=> $userPresentsReceiveData,
			'friend情報'					=> $friendData,
			'全ユーザbonus履歴'			=> $allUserBonusHistoryData,
			'Tencent全ユーザbonus履歴'	=> $userTencentBonusHistoryData,
			'ユーザBANメッセージ'			=> $userBanMessageData,
			'ダンジョン購入情報'			=> $userBuyDungeonData,
			'VIP報酬受取情報'			=> $userVipBonusData,
			'図鑑情報'					=> $userBookData,
		);

		return json_encode($result);
	}

	/**
	 * ==================================================
	 * メールタイプ一覧取得
	 * @return multitype:string
	 * ==================================================
	 */
	static private function getMailTypes()
	{
		$list = array(
			1	=> 'friend申請',
			2	=> 'お礼返し',
			3	=> 'friend間メール',
			4	=> '管理者からユーザへのメール（本編）',
			5	=> '管理者からユーザへのbonus付与（本編）',
			6	=> 'オールユーザbonus（本編）',
			7	=> '管理者からユーザへのメール（共通）',
			8	=> '管理者からユーザへのbonus付与（共通）',
			9	=> 'オールユーザbonus（共通）',
			10	=> '管理者からユーザへのメール（W）',
			11	=> '管理者からユーザへのbonus付与（W）',
			12	=> 'オールユーザbonus（W）',
		);
		return $list;
	}

	/**
	 * ==================================================
	 * @param int $user_id
	 * @param array $requestParams
	 * @return string
	 * ==================================================
	 */
	static private function getFormList($user_id, $requestParams)
	{
		$formList	= '<form action="'.REQUEST_URL_ADMIN.'" method="post">';
		$formList	.= '<input type="hidden" name="action" value="'.self::ADMIN_UPDATE_ACTION_NAME.'" />';
		$formList	.= '<input type="hidden" name="pid" value="'. $user_id .'" />';
		$formList	.= '<table border="1" style="margin:10px 5px 10px 10px;">';

		$formList	.=  '<tr style="background:#ffffcc;">'
					. '<th>項目</th>'
					. '<th>詳細</th>'
					. '</tr>';

		foreach($requestParams as $_key => $_value)
		{
			$name		= $_value[0];
			$value		= $_value[1];
			$editable	= $_value[2];
			$key		= $_value[3];

			$td		= '<td>';
			$tdend	= '</td>';

			if ($editable)
			{
				$temp_value	= '<input type="text"'
							. ' name="' . 'user['. $key .']"'
							. ' value="' . $value . '"'
							. ($editable ? '':' disabled')
							. ' style="width:100%"></input>';

				$value = $temp_value;
			}
			else {
				$td		= '<td style="background:#eeeeee;">';
			}

			$formList	.= '<tr>'
						. $td . $name . $tdend
						. $td . $value . $tdend
						. '</tr>';
		}
		$formList	.= '<tr><td><input type="hidden" name="backlink" value="1" /><input type="submit" value="更新" /></td></tr>'
					. '</form>';
		$formList .= '</table>';
		return $formList;
	}

	/**
	 * ==================================================
	 * @param string $className
	 * @param array $columns
	 * @param int $user_id
	 * @return multitype:string multitype:Ambigous multitype:unknown
	 * ==================================================
	 */
	static private function getDataByUserId($className,$columns,$user_id)
	{
		$data = $className::findAllBy(array('user_id'=>$user_id));
		$tmpData = self::getDataByData($data,$columns);
		return $tmpData;
	}

	/**
	 * ==================================================
	 * @param array $data
	 * @param array $columns
	 * @return multitype:string multitype:Ambigous multitype:unknown
	 * ==================================================
	 */
	static private function getDataByData($data,$columns)
	{
		$_tmpKeys		= array();
		$_tmpNames		= array();
		$_tmpViewNames	= array();
		foreach($columns as $key => $value)
		{
			$_tmpKeys[] = $key;
			$_tmpNames[] = $value[0];
			$_tmpViewNames[$key] = $value[1];
		}

		$tmpData = array('format' => 'array',);
		$tmpData[] = $_tmpNames;

		$tmpData2 = self::getReturnDataByData($data,$_tmpKeys,$_tmpViewNames);
		foreach($tmpData2 as $_tmpData)
		{
			$tmpData[] = $_tmpData;
		}

		return $tmpData;
	}

	/**
	 * ==================================================
	 * @param array $data
	 * @param array $keys
	 * @param array $viewNames
	 * @return multitype:multitype:Ambigous <multitype:NULL , string>
	 * ==================================================
	 */
	static private function getReturnDataByData($data,$keys,$viewNames)
	{
		$tmpData = array();
		foreach($data as $_data)
		{
			$_tmpData = array();
			foreach($keys as $_key)
			{
				$_tmpValue = $_data->$_key;

				// IDに紐付く名称をセットする
				if(count($viewNames[$_key]) > 0)
				{
					$_tmpValue = self::getNameFromArray($_tmpValue,$viewNames[$_key],self::$textData);
				}

				$_tmpData[] = $_tmpValue;
			}

			$tmpData[] = $_tmpData;
		}
		return $tmpData;
	}
}
<?php
/**
 * アクションクラスのベースクラス.
 */
abstract class AdminBaseAction
{
	public $post_params;
	public $decode_params;
	
	const ADMIN_EDIT_ACTION_NAME = "admin_edit_share_data";
	
	public function process($get_params, $request_uri, $post_params = null)
	{
		if($this->isValidRequest($get_params, $request_uri) === TRUE || Env::CHECK_REQ_SUM === FALSE)
		{
			$this->post_params = $post_params;
			$this->decode_params = array();
			if(isset($get_params['e'])){
				$this->decode_params = Cipher::decode($get_params['e']);
			}
			$api_revision = isset($get_params['r']) ? (int)$get_params['r'] : 0;
			Env::setRev($api_revision);

			Tencent_Tlog::init($_SERVER["SERVER_ADDR"], Env::TLOG_ZONEID);
			if(Env::TLOG_SERVER != null && Env::TLOG_PORT != null){
				Tencent_Tlog::setServer(Env::TLOG_SERVER, Env::TLOG_PORT);
			}
			
			$response = $this->action($get_params);
		}else{
			throw new PadException(RespCode::INVALID_REQUEST_CHECKSUM);
		}

		return $response;
	}

	/**
	 * action base
	 * @param array $get_params
	 * @throws Exception
	 */
	public function action($get_params){
		throw new Exception('Unimplemented action');
	}

	/**
	 * request check
	 * @param array $get_params
	 * @param string $request_uri
	 * @return boolean
	 */
	private function isValidRequest($get_params, $request_uri){
		$result = true;
		return $result;
	}

	public function createDummyDataForUser($user, $pdo) {
		// 何もしない. 子クラスごとに実装されていればそれを実行する.
	}


	/**
	 * 該当の配列から名称取得
	 * @return multitype:NULL
	 */
	static public function getNameFromArray($id,$array,$textData=array())
	{
		$name = '<span class="caution">-Undefined-</span>';
		if(isset($array[$id]))
		{
			$name = self::getTextDataWithJpText($textData,$array[$id]);
		}
		$result = $id . '：【' . $name . '】';
		return $result;
	}

	/**
	 * 指定のキーと合致するテキストを取得
	 * @param array $array
	 * @param string $key bit形式
	 * @return string
	 */
	static public function getRuleListByKey($array,$key)
	{
		$tmpText = '';
		foreach($array as $_k => $_v)
		{
			if($_k & $key)
			{
				$tmpText .= '【<span class="text_view">'.$_v.'</span>】';
			}
		}
		if(!$tmpText)
		{
			$tmpText = '【<span class="text_view">特殊ルールなし</span>】';
		}
		return $tmpText;
	}

	/**
	 * 該当データからIDをキーとして指定のキーをセットしたものを取得
	 * @return multitype:NULL
	 */
	static public function getDatasByDao($dao,$key)
	{
		$names = array();
		$allData = $dao->findAllBy(array());
		foreach ($allData as $_data)
		{
			$names[$_data->id] = $_data->$key;
		}
		return $names;
	}

	/**
	 * 該当データからIDをキーとして名前をセットしたものを取得
	 * @return multitype:NULL
	 */
	static public function getNamesByDao($dao)
	{
		return self::getDatasByDao($dao, 'name');
	}

	/**
	 * 該当テーブルの内容一式を取得
	 * @param string $className 参照するクラス名
	 * @param array $columnNames カラムの名称配列
	 * @return multitype:string multitype:NULL
	 */
	static public function getDataList($className=null,$columnNames=null,$columns=null,$order=null,$limit_args=null,$pdo=null)
	{
		if(!$pdo)
		{
			$pdo = Env::getDbConnectionForShareRead();
		}
		$list = $className::findAllBy(array(),$order,$limit_args,$pdo);
		if(!$columns)
		{
			$columns = $className::getColumns();
		}

		// カラム名表示用の調整（※指定がなければテーブルのカラム名、名称指定があれば差し替え）
		$_tmpColumnNames = array();
		foreach($columns as $_column)
		{
			$_columnName = $_column;
			if(isset($columnNames[$_column]))
			{
				$_columnName = $columnNames[$_column];
			}
			$_tmpColumnNames[] = $_columnName;
		}
		$columnNames = $_tmpColumnNames;

		//------------------------------
		// マスターデータ取得
		//------------------------------
		$cardNames			= array();
		$pieceNames			= array();
		$dungeonNames		= array();
		$dungeonFloorNames	= array();
		$skillNames			= array();
		$waveDatas			= array();
		
		$attrNames = self::getAttrNames();
		$mtNames = self::getMonsterTypeNames();
		$sizeNames = self::getMonsterSizeNames();
		$dkindNames = self::getDungeonKindNames();
		$extTypes = self::getExtTypes();
		$eflagTypes = self::getEflagTypes();
		$ruleTypes = self::getRuleTypes();
		$rankingRules = self::getLimitedRankingRules();
		$pieceTypes = self::getPieceTypes();
		$bonusTypes = self::getBonusIdNames();
		$limitedBonusTypes  = LimitedBonus::getLimiteBonusTypes();

		$maintenanceTypes = array(
			DebugUser::MAINTENANCE_FREE		=> 'メンテ突破',
			DebugUser::MAINTENANCE_NORMAL	=> '通常通りメンテ',
		);
		$cheatcheckTypes = array(
			DebugUser::CHEAT_CHECK_FREE		=> 'チート判定スルー',
			DebugUser::CHEAT_CHECK_NORMAL	=> '通常通り',
		);
		$dropchangeTypes = array(
			DebugUser::DROP_CHANGE_ON		=> 'DROP内容変更判定あり',
			DebugUser::DROP_CHANGE_OFF		=> '通常通り',
		);
		$skillupchangeTypes = array(
			DebugUser::SKILL_UP_CHANGE_ON		=> 'スキルアップ確率変更あり',
			DebugUser::SKILL_UP_CHANGE_OFF		=> '通常通り',
		);
		
		// 翻訳データ
		$textData = ConvertText::getConvertTextArrayByTextKey();

		if(in_array('card_id', $columns) || in_array('cid', $columns))
		{
			$card = new Card();
			$cardNames = self::getNamesByDao($card);
		}
		if(in_array('piece_id', $columns))
		{
			$piece = new Piece();
			$pieceNames = self::getNamesByDao($piece);
		}

		// ランキングダンジョン
		if(preg_match('/Ranking/',$className))
		{
			if(in_array('dungeon_id', $columns) || in_array('dungeon_id1', $columns) || in_array('ranking_dungeon_id', $columns))
			{
				$dungeon = new RankingDungeon();
				$dungeonNames = self::getNamesByDao($dungeon);
			}
			if(in_array('dungeon_floor_id', $columns) || in_array('prev_dungeon_floor_id', $columns))
			{
				$dungeonFloor = new RankingDungeonFloor();
				$dungeonFloorNames = self::getNamesByDao($dungeonFloor);
			}
			if(in_array('wave_id', $columns))
			{
				$wave = new RankingWave();
				$waveDatas = self::getDatasByDao($wave,'id');
			}
		}
		// 通常ダンジョン
		else
		{
			if(in_array('dungeon_id', $columns) || in_array('dungeon_id1', $columns))
			{
				$dungeon = new Dungeon();
				$dungeonNames = self::getNamesByDao($dungeon);
			}
			if(in_array('dungeon_floor_id', $columns) || in_array('prev_dungeon_floor_id', $columns))
			{
				$dungeonFloor = new DungeonFloor();
				$dungeonFloorNames = self::getNamesByDao($dungeonFloor);
			}
			if(in_array('wave_id', $columns))
			{
				$wave = new Wave();
				$waveDatas = self::getDatasByDao($wave,'id');
			}
		}

		if($className == 'Card' || $className == 'TutorialCard')
		{
			$piece = new Piece();
			$pieceNames = self::getNamesByDao($piece);
			$skill = new Skill();
			$skillNames = self::getNamesByDao($skill);
		}
		//------------------------------

		$tmpData = array('format' => 'array',);
		$tmpData[] = $columnNames;

		foreach($list as $_data)
		{
			$_tmpArray = array();
			foreach($columns as $_column)
			{
				$textJP = self::getJpTextByText($textData,$_data->$_column);

				switch($_column)
				{
					case 'card_id':
					case 'cid':
						$_value = self::getNameFromArray($_data->$_column,$cardNames);
						break;
					case 'piece_id':
					case 'drop_card_id1':
					case 'drop_card_id2':
					case 'drop_card_id3':
					case 'drop_card_id4':
					case 'gup_piece_id':
						$_value = self::getNameFromArray($_data->$_column,$pieceNames);
						break;
					case 'dungeon_id':
					case 'dungeon_id1':
					case 'dungeon_id2':
					case 'dungeon_id3':
					case 'dungeon_id4':
					case 'dungeon_id5':
					case 'dungeon_id6':
					case 'ranking_dungeon_id':
						$_value = self::getNameFromArray($_data->$_column,$dungeonNames);
						break;
					case 'dungeon_floor_id':
					case 'prev_dungeon_floor_id':
						$_value = self::getNameFromArray($_data->$_column,$dungeonFloorNames);
						break;
					case 'skill':
						$_value = self::getNameFromArray($_data->$_column,$skillNames);
						break;
					case 'wave_id':
						$_value = self::getNameFromArray($_data->$_column,$waveDatas);
						break;
					case 'attr':
					case 'sattr':
						if($className == 'Piece' || preg_match('/Card/',$className))
						{
							$_value = self::getNameFromArray($_data->$_column,$attrNames);
						}
						else
						{
							$_value = $_data->$_column;
						}
						break;
					case 'mt':
					case 'mt2':
						$_value = self::getNameFromArray($_data->$_column,$mtNames);
						break;
					case 'size':
						$_value = self::getNameFromArray($_data->$_column,$sizeNames);
						break;
					case 'dkind':
						$_value = self::getNameFromArray($_data->$_column,$dkindNames);
						break;
					case 'ext':
						$tmpText = self::getRuleListByKey($extTypes, $_data->$_column);
						$_value = $_data->$_column . '：' . $tmpText . '';
						break;
					case 'eflag':
						$tmpText = self::getRuleListByKey($eflagTypes, $_data->$_column);
						$_value = $_data->$_column . '：' . $tmpText . '';
						break;
					case 'fr':
						$_value = self::getNameFromArray($_data->$_column,$ruleTypes);
						break;
					case 'ranking_rule':
						$tmpText = self::getRuleListByKey($rankingRules, $_data->$_column);
						$_value = $_data->$_column . '：' . $tmpText . '';
						break;
					case 'type':
						if($className == 'Piece')
						{
							$_value = self::getNameFromArray($_data->$_column,$pieceTypes);
						}
						else
						{
							$_value = $_data->$_column;
						}
						break;
					case 'bonus_id':
					case 'award_id':
						$_value = self::getNameFromArray($_data->$_column,$bonusTypes);
						break;
					case 'bonus_type':
						if(preg_match('/LimitedBonus/',$className))
						{
							$_value = self::getNameFromArray($_data->$_column,$limitedBonusTypes);
						}
						else
						{
							$_value = $_data->$_column;
						}
						break;
					case 'maintenance_flag':
						$_value = self::getNameFromArray($_data->$_column,$maintenanceTypes);
						break;
					case 'cheatcheck_flag':
						$_value = self::getNameFromArray($_data->$_column,$cheatcheckTypes);
						break;
					case 'dropchange_flag':
						$_value = self::getNameFromArray($_data->$_column,$dropchangeTypes);
						break;
					case 'skillup_change_flag':
						$_value = self::getNameFromArray($_data->$_column,$skillupchangeTypes);
						break;
					default:
						$_value = $_data->$_column;
						break;
				}

				if($textJP)
				{
					$_value = $_value . '<span class="text_jp">（'.$textJP.'）</span>';
				}
				$_tmpArray[] = $_value;
			}
			$tmpData[] = $_tmpArray;
		}
		return $tmpData;
	}

	/**
	 * テキストデータから和訳を取得
	 * @param array $textData
	 * @param string $text
	 * @return Ambigous <string, unknown>
	 */
	static public function getJpTextByText($textData,$text)
	{
		$textJP = '';
		if($text !== '*****')
		{
			$text = preg_replace('/的碎片/','',$text);
			if(isset($textData[$text]))
			{
				$textJP = $textData[$text];
			}
		}
		return $textJP;
	}

	/**
	 * ==================================================
	 * 指定のテキストの翻訳データがあればセットして返す
	 * @param array $textData
	 * @param string $name
	 * ==================================================
	 */
	static public function getTextDataWithJpText($textData,$name)
	{
		$_tmpName = $name;
		$textJP = getJpTextByText($textData, $name);
		if($textJP)
		{
			$_tmpName = $name . '<span class="text_jp">（' . $textJP . '）</span>';
		}
		return $_tmpName;
	}
	
	
	static public function getAttrNames() {
		$array = array(
			-1	=> '属性なし',
			0	=> '火',
			1	=> '水',
			2	=> '木',
			3	=> '光',
			4	=> '闇',
		);
		return $array;
	}

	static public function getMonsterTypeNames() {
		$array = array(
			-1	=> 'タイプなし',
			0 => '進化用',
			1 => 'バランス',
			2 => '体力',
			3 => '回復',
			4 => 'ドラゴン',
			5 => '神',
			6 => '攻撃',
			7 => '悪魔',
			12 => '覚醒',
			13 => '特別保護',
			14 => '強化合成用',
		);
		return $array;
	}
	
	static public function getMonsterSizeNames() {
		$array = array(
			0 => 'S',
			1 => 'SM',
			2 => 'M',
			3 => 'ML',
			4 => 'L',
			5 => 'LL',
		);
		return $array;
	}
	
	static public function getDungeonTypeNames() {
		$array = array(
			Dungeon::DUNG_TYPE_NORMAL => 'ノーマル',
			Dungeon::DUNG_TYPE_EVENT => 'イベント',
			Dungeon::DUNG_TYPE_TECHNICAL => 'テクニカル',
			Dungeon::DUNG_TYPE_LEGEND => 'レジェンド',
		);
		return $array;
	}
	
	static public function getDungeonKindNames() {
		$array = array(
			Dungeon::DUNG_KIND_NONE => '未分類',
			Dungeon::DUNG_KIND_ADVENT => '降臨ダンジョン',
			Dungeon::DUNG_KIND_SUDDEN => '突発系ダンジョン',
			Dungeon::DUNG_KIND_COLLABO => 'コラボダンジョン',
			Dungeon::DUNG_KIND_GUERRILLA => 'ゲリラダンジョン',
			Dungeon::DUNG_KIND_DAILY => 'デイリーダンジョン',
			Dungeon::DUNG_KIND_BUY => '購入ダンジョン',
			Dungeon::DUNG_KIND_MUGEN => '無限回廊ダンジョン',
		);
		return $array;
	}
	
	static public function getExtTypes() {
		$array = array(
			0b0000000001 => '火',
			0b0000000010 => '水',
			0b0000000100 => '樹',
			0b0000001000 => '光',
			0b0000010000 => '闇',
			0b0000100000 => '光ブロックなし',
			0b0001000000 => '闇ブロックなし',
			0b0010000000 => '敵がスキルを使用',
			0b0100000000 => '開始時からスキル使用可',
			0b1000000000 => 'スコアあり',
		);
		return $array;
	}
	
	static public function getEflagTypes() {
		$array = array(
			0b0000000001 => '火ブロックなし',
			0b0000000010 => '水ブロックなし',
			0b0000000100 => '樹ブロックなし',
			0b0000001000 => '光ブロックなし',
			0b0000010000 => '闇ブロックなし',
			0b0000100000 => '回復ブロックなし',
			0b0001000000 => 'スキル無効',
			0b0010000000 => 'リーダースキル無効',
			0b0100000000 => 'コンティニュー無効',
			0b1000000000 => 'ドロップ無',
		);
		return $array;
	}
	
	static public function getRuleTypes() {
		$array = array(
			0 => 'なし',
			1 => '総コスト ＠１ 以下のパーティーのみ',
			2 => 'コスト ＠１ 以下のモンスターのみ',
			3 => '総レアリティーが ＠１ 以下のパーティーのみ',
			4 => 'レアリティーが ＠１ 以下のモンスターのみ',
			5 => '＠１～＠８ タイプのモンスターは入れない',
			6 => '＠１～＠８ 属性のモンスターは入れない',
			7 => '＠１～＠８ タイプのモンスターのみ入れます',
			8 => '＠１～＠８ 属性のモンスターのみ入れます',
			9 => '＠１～＠８ 属性のモンスターが必要',
			10 => '同じモンスターは連れて行けない',
		);
		return $array;
	}
	
	static public function getLimitedRankingRules() {
		$array = array(
			0b000000000001 => 'コンボ数',
			0b000000000010 => 'クリアタイム',
			0b000000000100 => 'クリアターン',
			0b000000001000 => '平均レアリティ',
			0b000000010000 => '予備：16',
			0b000000100000 => '予備：32',
			0b000001000000 => '予備：64',
			0b000010000000 => '予備：128',
			0b000100000000 => '予備：256',
			0b001000000000 => '予備：512',
			0b010000000000 => '予備：1024',
			0b100000000000 => '予備：2048',
		);
		return $array;
	}
	
	static public function getPieceTypes() {
		$array = array(
			Piece::PIECE_TYPE_MONSTER	=> 'Monster',
			Piece::PIECE_TYPE_STRENGTH	=> '強化用',
			Piece::PIECE_TYPE_EVOLUTION	=> '進化用',
			Piece::PIECE_TYPE_PLUS_HP	=> 'Plus（HP)',
			Piece::PIECE_TYPE_PLUS_ATK	=> 'Plus（攻击)',
			Piece::PIECE_TYPE_PLUS_REC	=> 'Plus（恢复)',
			Piece::PIECE_TYPE_SKILL		=> 'Skill Up',
			Piece::PIECE_TYPE_ULTIMATE	=> '中级进化',
		);
		return $array;
	}
	
	static public function getBonusIdNames() {
		$array = array(
			BaseBonus::COIN_ID					=> 'Coin',
			BaseBonus::MAGIC_STONE_ID			=> '魔法石(無料)',
			BaseBonus::FRIEND_POINT_ID			=> '友情点',
			BaseBonus::STAMINA_ID				=> '体力',
			//BaseBonus::COST_ID					=> 'チームコスト',
			BaseBonus::FRIEND_MAX_ID			=> '好友数上限',
			BaseBonus::EXP_ID					=> '经验值',
			BaseBonus::PREMIUM_MAGIC_STONE_ID	=> '魔法石(有料)',
			//BaseBonus::MEDAL_ID					=> 9909; 'メダル',
			BaseBonus::AVATAR_ID				=> 'Avatar',
			BaseBonus::STAMINA_RECOVER_ID		=> '体力恢复',
			BaseBonus::RANKING_POINT_ID			=> 'Ranking Point',
			BaseBonus::CONTINUE_ID				=> '無料Continue次数恢复',
			BaseBonus::ROUND_ID					=> '扫荡卷',
			BaseBonus::PIECE_ID					=> '碎片',
			BaseBonus::MAIL_ID					=> 'Mail',
		);
		return $array;
	}
	
	static public function getMissionConditionTypes() {
		$array = array(
			Mission::CONDITION_TYPE_TOTAL_LOGIN			=> '总登陆次数',
			Mission::CONDITION_TYPE_USER_RANK			=> '用户等级',
			Mission::CONDITION_TYPE_DUNGEON_CLEAR		=> '特定关卡通关',
			//Mission::CONDITION_TYPE_FLOOR_CLEAR			=> '特定关卡FLoor通关',
			Mission::CONDITION_TYPE_LOGIN_STREAK			=> '连续登陆次数',
			Mission::CONDITION_TYPE_BOOK_COUNT			=> '图鉴登录数',
			Mission::CONDITION_TYPE_CARD_EVOLVE			=> '进化合成次数',
			Mission::CONDITION_TYPE_CARD_COMPOSITE		=> '強化合成次数',
				
			Mission::CONDITION_TYPE_DAILY_FLOOR_CLEAR			=> '特定关卡Floor通关（每日）',
			//Mission::CONDITION_TYPE_DAILY_FLOOR_CLEAR_RANKING	=> '特定フロアクリア（デイリー、ランキングダンジョン）',
			Mission::CONDITION_TYPE_DAILY_CLEAR_COUNT_NORMAL		=> '普通关卡Floor通关次数（每日）',
			Mission::CONDITION_TYPE_DAILY_CLEAR_COUNT_SPECIAL	=> '特殊关卡Floor通关次数（每日）',
			Mission::CONDITION_TYPE_DAILY_GACHA_FRIEND			=> '友情扭蛋次数（每日）',
			Mission::CONDITION_TYPE_DAILY_GACHA_GOLD				=> '魔法石扭蛋次数（每日）',
			Mission::CONDITION_TYPE_DAILY_CARD_COMPOSITE			=> '強化合成次数（每日）',
			Mission::CONDITION_TYPE_DAILY_CARD_EVOLVE			=> '進化合成次数（每日）',
			//Mission::CONDITION_TYPE_DAILY_CARD_CREATE			=> 'Monster合成次数（每日）',
			//Mission::CONDITION_TYPE_DAILY_CARD_PLUS				=> 'Plus合成次数（每日）',
			//Mission::CONDITION_TYPE_DAILY_CARD_SKILL_UP			=> 'Skill up合成回数（每日）',
			//Mission::CONDITION_TYPE_DAILY_STAMINA_PRESENT		=> 'Stamina 礼物次数（デイリー）',
			//Mission::CONDITION_TYPE_DAILY_ALL_CLEAR				=> '全每日任务达成',
			Mission::CONDITION_TYPE_DAILY_LOGIN_STREAK			=> '连续登录次数（每日）'
		);
		return $array;
	}
	
	static public function getMissionTabCategiries() {
		$array = array(
			Mission::TAB_CATEGORY_NORMAL			=> '通常',
			Mission::TAB_CATEGORY_SPECIAL			=> '特殊',
		);
		return $array;
	}
	
	static public function getMissionTypes() {
		$array = array(
			Mission::MISSION_TYPE_NORMAL			=> '普通',
			Mission::MISSION_TYPE_LIMIT				=> '紧急（限定时间达成）',
			Mission::MISSION_TYPE_DAILY				=> '每日任务',
			Mission::MISSION_TYPE_SPECIAL			=> '特殊（教程）',
		);
		return $array;
	}
	
	static public function getMissionTransitionIds() {
		$array = array(
			Mission::TRANSITION_ID_NONE						=> '无跳转',
			Mission::TRANSITION_ID_SELECT_FLOOR_1			=> 'Floor选择（Dungeon ID）',
			Mission::TRANSITION_ID_SELECT_FLOOR_2			=> 'Floor選択（Floor ID）',
			Mission::TRANSITION_ID_SELECT_BASE_CARD			=> 'Base card选择',
			Mission::TRANSITION_ID_SELECT_NORMAL_DUNGEON	=> '普通Dungeon选择',
			Mission::TRANSITION_ID_SELECT_SPECIAL_DUNGEON	=> '特殊Dungeon选择',
			Mission::TRANSITION_ID_GACHA					=> '扭蛋',
		);
		return $array;
	}
	
	static public function getListNames($class_name) {
		$class = new $class_name();
		$listNames = self::getNamesByDao($class);
		return self::addUndefined($listNames);
	}
	
	static public function addUndefined(Array $listNames) {
		if (!isset($listNames[0])) {
			// ID:0を未定義として追加
			$listNames[0] = '<span class="caution">-Undefined-</span>';
			ksort($listNames);
		}
		return $listNames;
	}
	
	/**
	 * 該当テーブルの内容一式を取得
	 */
	static public function getDataListEditForm($action_name,$request_type,$model_name,$columnNames=null,$order=null,$limit_args=null,$pdo=null)
	{
		if(!$pdo)
		{
			$pdo = Env::getDbConnectionForShareRead();
		}
		$datalist = $model_name::findAllBy(array(),$order,$limit_args,$pdo);
		//$datalist = $model_name::findAllBy(array(),$order,array('limit'=>2),$pdo);
		$columns = $model_name::getColumns();
		// カラム配列にidが含まれていなければ追加する
		if (!in_array('id', $columns)) {
			$columns = array_merge(array('id'), $columns);
		}
		
		// カラム名表示用の調整（※指定がなければテーブルのカラム名、名称指定があれば差し替え）
		$_tmpColumnNames = array();
		foreach($columns as $_column)
		{
			$_columnName = $_column;
			if(isset($columnNames[$_column]))
			{
				$_columnName = $columnNames[$_column];
			}
			$_tmpColumnNames[] = $_columnName;
		}
		$columnNames = $_tmpColumnNames;
		
		
		$last_id = 1;
		
		$formList	= '<form action="'.REQUEST_URL_ADMIN.'" method="get">';
		$formList	.= '<input type="hidden" name="request_type" value="'.TYPE_EDIT_DB_SHARE_DATA.'" />';
		$formList	.= '<input type="hidden" name="action" value="'.self::ADMIN_EDIT_ACTION_NAME.'" />';
		
		$formList	.= '<input type="hidden" name="from_action" value="'.$action_name.'" />';
		$formList	.= '<input type="hidden" name="from_request_type" value="'.$request_type.'" />';
		$formList	.= '<input type="hidden" name="model" value="'.$model_name.'" />';
		
		$formList	.= '<table border="1" style="margin:10px 5px 10px 10px;">';
		if ($datalist) {
			$formList	.= '<tr style="background:#ffffcc;">';
			foreach($columnNames as $column) {
				$formList	.= '<th>' . $column . '</th>';
			}
			$formList	.= '</tr>';
			
			
			foreach($datalist as $params) {
			
				$err_flg = false;
				// ミッションの場合
				if($model_name == 'Mission') {
					$err_flg = self::checkMissionParam($params);
				}
				
				// データの削除フラグ
				// INFO:del_flgカラムを削除フラグとして扱う（今のところMissionテーブルにしか存在しない）
				$del_flg = isset($params->del_flg) ? $params->del_flg : 0;
				
				$formList	.= '<tr>';
				foreach($columns as $column) {
					
					$_key = $column;
					$_value = $params->$column;
					//Padc_Log_Log::writeLog('key:'.$_key.' value:'.$_value, Zend_Log::DEBUG);
					
					$temp	= $_value;
					switch($_key) {
						case 'id':
							$temp	= '<input type="submit" name="base_id" value="'.$_value.'" style="min-width:100%"/></input>';
							if ($last_id <= $_value) {
								$last_id = $_value + 1;
							}
							break;
							
						case 'name':
							if (!isset($textData)) {
								// 翻訳データ
								$textData = ConvertText::getConvertTextArrayByTextKey();
							}
							$textJP = self::getJpTextByText($textData,$_value);
							if($textJP)
							{
								$temp = $temp . '<span class="text_jp">（'.$textJP.'）</span>';
							}
							break;
							
						case 'card_id':
						case 'cid':
							if (!isset($cardNames)) {
								$cardNames = self::getListNames('Card');
							}
							$temp = self::getNameFromArray($_value, $cardNames);
							break;
							
						case 'piece_id':
						case 'drop_card_id1':
						case 'drop_card_id2':
						case 'drop_card_id3':
						case 'drop_card_id4':
						case 'gup_piece_id':
							if (!isset($pieceNames)) {
								$pieceNames = self::getListNames('Piece');
							}
							$temp = self::getNameFromArray($_value, $pieceNames);
							break;
							
						case 'dungeon_id':
						case 'dungeon_id1':
						case 'dungeon_id2':
						case 'dungeon_id3':
						case 'dungeon_id4':
						case 'dungeon_id5':
						case 'dungeon_id6':
						case 'ranking_dungeon_id':
							if (!isset($dungeonNames)) {
								if(preg_match('/Ranking/',$model_name)) {
									$dungeonNames = self::getListNames('RankingDungeon');
								}
								else {
									$dungeonNames = self::getListNames('Dungeon');
								}
							}
							$temp = self::getNameFromArray($_value, $dungeonNames);
							break;
							
						case 'dungeon_floor_id':
						case 'prev_dungeon_floor_id':
						case 'ranking_floor_id':
							if (!isset($dungeonFloorNames)) {
								if(preg_match('/Ranking/',$model_name)) {
									$dungeonFloorNames = self::getListNames('RankingDungeonFloor');
								}
								else {
									$dungeonFloorNames = self::getListNames('DungeonFloor');
								}
							}
							$temp = self::getNameFromArray($_value, $dungeonFloorNames);
							break;
							
						case 'skill':
							if (!isset($skillNames)) {
								$skillNames = self::getListNames('Skill');
							}
							$temp = self::getNameFromArray($_value, $skillNames);
							break;
							
						case 'wave_id':
							if (!isset($waveDatas)) {
								if(preg_match('/Ranking/',$model_name)) {
									$wave = new RankingWave();
								}
								else {
									$wave = new Wave();
								}
								$waveDatas = self::getDatasByDao($wave,'id');
							}
							$temp = self::getNameFromArray($_value, $waveDatas);
							break;
							
						case 'attr':
						case 'sattr':
							if($model_name == 'Piece' || preg_match('/Card/',$model_name)) {
								if (!isset($attrNames)) {
									$attrNames = self::getAttrNames();
								}
								$temp = self::getNameFromArray($_value, $attrNames);
							}
							break;
							
						case 'mt':
						case 'mt2':
							if (!isset($mtNames)) {
								$mtNames = self::getMonsterTypeNames();
							}
							$temp = self::getNameFromArray($_value, $mtNames);
							break;
						
						case 'size':
							if (!isset($sizeNames)) {
								$sizeNames = self::getMonsterSizeNames();
							}
							$temp = self::getNameFromArray($_value, $sizeNames);
							break;
							
						case 'dtype':
							if (!isset($dtypeNames)) {
								$dtypeNames = self::getDungeonTypeNames();
							}
							$temp = self::getNameFromArray($_value, $dtypeNames);
							break;
							
						case 'dkind':
							if (!isset($dkindNames)) {
								$dkindNames = self::getDungeonKindNames();
							}
							$temp = self::getNameFromArray($_value, $dkindNames);
							break;
							
						case 'ext':
							if (!isset($extTypes)) {
								$extTypes = self::getExtTypes();
							}
							$temp = $_value . ':' . self::getRuleListByKey($extTypes, $_value);
							break;
							
						case 'eflag':
							if (!isset($eflagTypes)) {
								$eflagTypes = self::getEflagTypes();
							}
							$temp = $_value . ':' . self::getRuleListByKey($eflagTypes, $_value);
							break;
							
						case 'fr':
							if (!isset($ruleTypes)) {
								$ruleTypes = self::getRuleTypes();
							}
							$temp = self::getNameFromArray($_value, $ruleTypes);
							break;
							
						case 'ranking_rule':
							if (!isset($rankingRules)) {
								$rankingRules = self::getLimitedRankingRules();
							}
							$temp = $_value . ':' . self::getRuleListByKey($rankingRules, $_value);
							break;
							
						case 'type':
							if($model_name == 'Piece') {
								if (!isset($pieceTypes)) {
									$pieceTypes = self::getPieceTypes();
								}
								$temp = self::getNameFromArray($_value, $pieceTypes);
							}
							break;
							
						case 'bonus_id':
						case 'award_id':
							if (!isset($bonusIdNames)) {
								$bonusIdNames = self::getBonusIdNames();
							}
							$temp = self::getNameFromArray($_value, $bonusIdNames);
							break;
							
						case 'bonus_type':
							if(preg_match('/LimitedBonus/',$model_name))
							{
								if (!isset($limitedBonusTypes)) {
									$limitedBonusTypes  = LimitedBonus::getLimiteBonusTypes();
								}
								$temp = self::getNameFromArray($_value, $limitedBonusTypes);
							}
							break;
							
						case 'tab_category':
							if($model_name == 'Mission') {
								if (!isset($missionTabCategories)) {
									$missionTabCategories = self::getMissionTabCategiries();
								}
								$temp = self::getNameFromArray($_value, $missionTabCategories);
							}
							break;
							
						case 'condition_type':
							if($model_name == 'Mission') {
								if (!isset($missionConditionTypes)) {
									$missionConditionTypes = self::getMissionConditionTypes();
								}
								$temp = self::getNameFromArray($_value, $missionConditionTypes);
							}
							break;
							
						case 'mission_type':
							if($model_name == 'Mission') {
								if (!isset($missionTypes)) {
									$missionTypes = self::getMissionTypes();
								}
								$temp = self::getNameFromArray($_value, $missionTypes);
							}
							break;
							
						case 'transition_id':
							if($model_name == 'Mission') {
								if (!isset($missionTransitionIds)) {
									$missionTransitionIds = self::getMissionTransitionIds();
								}
								$temp = self::getNameFromArray($_value, $missionTransitionIds);
							}
							break;
									
							//case 'maintenance_flag':
						//	break;
						//case 'cheatcheck_flag':
						//	break;
						//case 'dropchange_flag':
						//	break;
							
						default:
							break;
					}
					
					if ($del_flg) {
						$formList	.= '<td style="background:#999999;">'.$temp.'</td>';
					}
					else if ($err_flg) {
						$formList	.= '<td style="background:#ff0000;">'.$temp.'</td>';
					}
					else {
						$formList	.= '<td>'.$temp.'</td>';
					}
				}
				$formList	.= '</tr>';
			}
		}
		
 		// データ新規追加ボタン
		$formList	.= '<tr>';
		$formList	.= '<td><button type="submit" style="width:100%"/>追加</button></td>';
		$formList	.= '<td colspan="'.(count($columnNames)-1).'">';
		$formList	.= '新規 id : <input type="text" name="add_id" value="' . $last_id . '" ></input>';
		$formList	.= '</td>';
		$formList	.= '</tr>';
		
		$formList	.= '</table>';
		$formList	.= '</form>';
		
		return $formList;
	}
	
	/**
	 * ミッションデータとしておかしい部分が無いかチェックする
	 * 厳密なものではなく最低限のチェック
	 */
	static private function checkMissionParam($params) {
		$conditon_type = $params->condition_type;
		$mission_type = $params->mission_type;
		$clear_condition = json_decode($params->clear_condition);
		$prev_id = $params->prev_id;

		// カテゴリチェック
		switch ($conditon_type) {
			case Mission::CONDITION_TYPE_TOTAL_LOGIN:
				if (!array_key_exists(Mission::CONDITION_KEY_TOTAL_LOGIN, $clear_condition)) {
					//$params->conditon_type = self::decorateWarningText($params->conditon_type);
					//$params->clear_condition = self::decorateWarningText($params->clear_condition);
					return true;
				}
				break;
			case Mission::CONDITION_TYPE_USER_RANK:
				if (!array_key_exists(Mission::CONDITION_KEY_USER_RANK, $clear_condition)) {
					return true;
				}
				break;
			case Mission::CONDITION_TYPE_DUNGEON_CLEAR:
				if (!array_key_exists(Mission::CONDITION_KEY_DUNGEON_CLEAR, $clear_condition)) {
					return true;
				}
				break;
			case Mission::CONDITION_TYPE_LOGIN_STREAK:
				if (!array_key_exists(Mission::CONDITION_KEY_LOGIN_STREAK, $clear_condition)) {
					return true;
				}
				break;
			case Mission::CONDITION_TYPE_BOOK_COUNT:
				if (!array_key_exists(Mission::CONDITION_KEY_BOOK_COUNT, $clear_condition)) {
					return true;
				}
				break;
			case Mission::CONDITION_TYPE_CARD_EVOLVE:
				if (!array_key_exists(Mission::CONDITION_KEY_CARD_EVOLVE, $clear_condition)) {
					return true;
				}
				break;
				
			case Mission::CONDITION_TYPE_DAILY_FLOOR_CLEAR:
				if (!array_key_exists(Mission::CONDITION_KEY_DAILY_FLOOR_CLEAR, $clear_condition)) {
					return true;
				}
				break;
			case Mission::CONDITION_TYPE_DAILY_CLEAR_COUNT_NORMAL:
				if (!array_key_exists(Mission::CONDITION_KEY_DAILY_CLEAR_COUNT_NORMAL, $clear_condition)) {
					return true;
				}
				break;
			case Mission::CONDITION_TYPE_DAILY_CLEAR_COUNT_SPECIAL:
				if (!array_key_exists(Mission::CONDITION_KEY_DAILY_CLEAR_COUNT_SPECIAL, $clear_condition)) {
					return true;
				}
				break;
			case Mission::CONDITION_TYPE_DAILY_GACHA_FRIEND:
				if (!array_key_exists(Mission::CONDITION_KEY_DAILY_GACHA_FRIEND, $clear_condition)) {
					return true;
				}
				break;
			case Mission::CONDITION_TYPE_DAILY_GACHA_GOLD:
				if (!array_key_exists(Mission::CONDITION_KEY_DAILY_GACHA_GOLD, $clear_condition)) {
					return true;
				}
				break;
			case Mission::CONDITION_TYPE_DAILY_CARD_COMPOSITE:
				if (!array_key_exists(Mission::CONDITION_KEY_DAILY_CARD_COMPOSITE, $clear_condition)) {
					return true;
				}
				break;
			case Mission::CONDITION_TYPE_DAILY_CARD_EVOLVE:
				if (!array_key_exists(Mission::CONDITION_KEY_DAILY_CARD_EVOLVE, $clear_condition)) {
					return true;
				}
				break;
			case Mission::CONDITION_TYPE_DAILY_LOGIN_STREAK:
				if (!array_key_exists(Mission::CONDITION_KEY_DAILY_LOGIN_STREAK, $clear_condition)) {
					return true;
				}
				break;
				
			default:
				break;
		}
		
		
		// ミッションカテゴリ2設定チェック
		if ($conditon_type >= Mission::CONDITION_TYPE_DAILY_FLOOR_CLEAR && $mission_type != Mission::MISSION_TYPE_DAILY) {
			return true;
		}

		// 開放前IDチェック
		if ($conditon_type == Mission::CONDITION_TYPE_LOGIN_STREAK ^ $prev_id < 0) {
			return true;
		}
		
		return false;
	}
	
	static private function decorateWarningText($value) {
		return '<div style="color:#ffffff;background-color:#ff0000;">'.$value.'</div>';
	}
	
	
}

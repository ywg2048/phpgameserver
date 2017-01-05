<?php
/**
 * ユーザーモデルのベースクラス.
 * author akamiya@gungho.jp
 */
abstract class BaseUserModel extends BaseModel {

	// memcachedからのデータ取得用（getCacheFriendData()でarrayから連想配列に変換する）
	const FORMAT_REV_1 = 1;
	const FORMAT_REV_2 = 2;
	
	// #PADC# ----------begin----------
	// friend data index
	const FRIEND_VER = 0;
	const FRIEND_PID = 1;
	const FRIEND_NAME = 2;
	const FRIEND_LV = 3;
	const FRIEND_CAMP = 4;
	const FRIEND_ACCTIME = 5;
	const FRIEND_FRI = 6;
	const FRIEND_FRIMAX = 7;
	const FRIEND_EQ1_ID = 8;
	const FRIEND_EQ1_LV = 9;
	const FRIEND_EQ2_ID = 10;
	const FRIEND_EQ2_LV = 11;
	const FRIEND_EQ3_ID = 12;
	const FRIEND_EQ3_LV = 13;
	const FRIEND_CLEAR_CNT = 14;
	const FRIEND_SNS = 15;
	const FRIEND_DEVICE_TYPE = 16;
	const FRIEND_LAST_CLEAR_ID = 17;
	const FRIEND_LAST_PRESENT_TIME = 18;
	const FRIEND_OID = 19;	//openid
	const FRIEND_OPT_MSG = 20;	//option enable accept qq/wechat message
	const FRIEND_VIP_LV = 21;	//vip level
	const FRIEND_QQ_VIP = 22;	//qq vip
	const FRIEND_PREPID = 23;	//pre user id
	// ...
	// #PADC# ----------end----------

	/**
	 * ID を指定してデータベースからレコードを取得する.
	 * @param mixed $id ID
	 * @param PDO $pdo トランザクション内で実行するときは、PDOオブジェクトを指定すること.
	 * @param boolean $forUpdate 更新用クエリにするかどうか.
	 * @return  モデルオブジェクト.
	 */
	public static function find($id, $pdo = null, $forUpdate = FALSE) {
		if($pdo == null) {
			$pdo = Env::getDbConnectionForUserRead($id);
		}
		$sql = "SELECT * FROM " . static::TABLE_NAME . " WHERE id = ?";
		if($forUpdate) {
			$sql .= " FOR UPDATE";
		}
		$stmt = $pdo->prepare($sql);
		$stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
		$stmt->bindParam(1, $id);
		$stmt->execute();
		$obj = $stmt->fetch(PDO::FETCH_CLASS);
		if($obj  === FALSE) {
			return $obj;
		}
		// 変更前の値を'_'を付けて保存
		$org_value = array();
		foreach($obj as $key => $value){
			$org_value['_'.$key] = $value;
		}
		foreach($org_value as $key => $value){
			$obj->$key = $value;
		}
		unset($org_value);
		if(Env::ENV !== "production"){
			global $logger;
			$logger->log(("sql_query: ".$sql."; bind: ".$id), Zend_Log::DEBUG);
		}
		return $obj;
	}

	/**
	 * オブジェクトを更新する.
	 * @param PDO $pdo
	 */
	public function update($pdo = null) {
		if(!isset($this->id)) {
			throw new Exception('The ' . get_called_class() . ' is not saved yet.');
		}
		if(is_null($pdo)) {
			$pdo = Env::getDbConnectionForUserWrite($this->id);
		}
		// SQLを構築.
		list($columns, $values) = $this->getValuesForUpdate();
		$sql = 'UPDATE ' . static::TABLE_NAME . ' SET ';
		$setStmts = array();
		foreach($columns as $column) {
			$setStmts[] = $column . '=?';
		}
		$setStmts[] = 'updated_at=now()';
		$sql .= join(',', $setStmts);
		$sql .= ' WHERE id = ?';
		$stmt = $pdo->prepare($sql);
		$values = array_merge($values, array($this->id));
		$result = $stmt->execute($values);
		// memcachedに保存する.
		$this->CachetFriendData();
		// #PADC# ----------begin----------
		// お勧めヘルパーの候補リストを更新(フレンド登録がMAXでない、かつダンジョンクリア数2以上(チュートリアルダンジョンクリア済み)、かつアクセス時間が前回から5分経過時)
		if(mb_strpos($sql, 'accessed_on')) {
			// PADCではリセマラが不可能なのでダンジョンクリア数の条件はカット
			if($this->fricnt < $this->friend_max /*&& $this->clear_dungeon_cnt >= GameConstant::TUTORIAL_DUNGEON_COUNT*/&& strtotime($this->accessed_at) >= (strtotime($this->_accessed_at) + 300)) {
				// #PADC_DY# ----------begin----------
				// RecommendedHelperUtil::updateHelpersOfLevel($this->id, $this->clear_dungeon_cnt);
				RecommendedHelperUtil::updateHelpersOfLevel($this->id, $this->lv);
				// #PADC_DY# ----------end----------
			}
		}
		// #PADC# ----------end----------
		if(Env::ENV !== "production"){
			global $logger;
			$logger->log(("sql_query: ".$sql."; bind: ".join(",", $values)), Zend_Log::DEBUG);
		}
		return $result;
	}

	/**
	 * $columns に対応する値の配列を返す.
	 * @return array カラムの配列、値の配列からなる配列.
	 */
	protected function getValuesForUpdate() {
		$values = array();
		$columns = array();
		foreach (static::$columns as $column) {
			$_key = '_'.$column;
			// 元の値から変化しているも項目のみ更新する
			if(isset($this->$_key) && $this->$column === $this->$_key) continue;
			$columns[] = $column;
			$values[] = $this->$column;
		}
		return array($columns, $values);
	}

	/**
	 * ユーザ情報を取得してフレンド形式でmemcachedに保存する.
	 */
	public static function getCacheFriendData($user_id, $rev, $pdo = null){
		// #PADC# ----------begin----------memcache→redisに切り替え
		$rRedis = Env::getRedisForUserRead();
		
		$key = CacheKey::getUserFriendDataFormatKey2($user_id);
		$value = $rRedis->get($key);
		// #PADC# ----------end----------
		if(FALSE === $value || (isset($value[0]) && $value[0] != 1005)) {
			// キャッシュにない場合はDBから.
			$user = User::find($user_id, $pdo);
			if($user === FALSE) {
				return FALSE;
			}
			$value = $user->CachetFriendData();
		}
		if($rev < 2){
			// 以前のフォーマットに変換.
			$value = self::convOldFriendFormat($value);
		}
		return $value;
	}

	/**
	 * ユーザ情報をまとめて取得してフレンド形式でmemcachedに保存する.
	 */
	public static function getMultiCacheFriendData($user_ids, $rev, $pdo = null){
		
		// #PADC# ----------begin----------memcache→redisに切り替え
		$rRedis = Env::getRedisForUserRead();
		$values = array();
		foreach($user_ids as $user_id){
			//キャッシュが無い場合、falseが入ります
			$keys = CacheKey::getUserFriendDataFormatKey2($user_id);
			$values[$keys] = $rRedis->get($keys);
		}
		// #PADC# ----------end----------
		$return = array();
		foreach($user_ids as $user_id){
			$keys = CacheKey::getUserFriendDataFormatKey2($user_id);
			// #PADC# ----------begin----------
			$value = false;
			if(isset($values[$keys]) && $values[$keys]){// issetでチェックしたのち、値をチェック
				$value = $values[$keys];
			}else{
				// キャッシュにない場合はDBから.
				$user = User::find($user_id, $pdo);
				if($user) {
					$value = $user->CachetFriendData();
				}
			}
			// ユーザデータがなければ次へ
			if($value === false){
				continue;
			}
			// #PADC# ----------end----------
			if($rev < 2){
				// 以前のフォーマットに変換.
				$value = self::convOldFriendFormat($value);
			}
			$return[] = $value;
		}
		return $return;
	}

	public function getLeaderCardsData(){
		$lc = array();
		list(
			$lc['cuid'][0],
			$lc['id'][0],
			$lc['lv'][0],
			$lc['slv'][0],
			$lc['hp'][0],
			$lc['atk'][0],
			$lc['rec'][0],
			$lc['psk'][0],
			$lc['cuid'][1],
			$lc['id'][1],
			$lc['lv'][1],
			$lc['slv'][1],
			$lc['hp'][1],
			$lc['atk'][1],
			$lc['rec'][1],
			$lc['psk'][1],
		) = explode(",", $this->lc);
		return (object)$lc;
	}
	/**
	 * #PADC#
	 * リーダーデッキデータを返す
	 * （リーダーだけでなくデッキ全体の情報）
	 */
	public function getLeaderDecksData(){
		return json_decode($this->ldeck, true);
	}

	/**
	 * ユーザ情報をフレンド形式でmemcachedに保存する.
	 * @param $users ユーザ情報の配列
	 * https://61.215.220.70/redmine-pad/projects/pad/wiki/%E3%83%95%E3%83%AC%E3%83%B3%E3%83%89%E3%83%87%E3%83%BC%E3%82%BF
	 */
	protected function CachetFriendData(){
		$v = 4;  // クライアント側パース用のバージョン番号。4固定
		$v += 1000;  // #PADC# PADC版フォーマットとして判別できるよう値を追加
		$v += 1; // #PADC_DY# 增加好友的leader卡牌的觉醒数据
		$pid = (int)$this->id;
		if($this->del_status == User::STATUS_DEL){
			// #PADC# ----------begin----------
			// アカウント削除済ユーザの名前は空とする
			$name = '';
			/*
			if(Env::REGION == 'JP'){
				$name = 'アカウント削除済';
			}elseif(Env::REGION == 'KR'){
				$name = '계정 삭제됨';
			}elseif(Env::REGION == 'NA' || Env::REGION == 'EU' ){
				$name = 'Account Revoked';
			}
			*/
			// #PADC# ----------end----------
		}elseif($this->del_status == User::STATUS_BAN || $this->del_status == User::STATUS_BAN_GS || $this->del_status == User::STATUS_BAN_CR){
			// #PADC# ----------begin----------
			// アカウント停止中ユーザの名前は空とする
			$name = '';
			/*
			if(Env::REGION == 'JP'){
				$name = 'アカウント停止中';
			}elseif(Env::REGION == 'KR'){
				$name = '계정 정지';
			}elseif(Env::REGION == 'NA' || Env::REGION == 'EU'){
				$name = 'Account Halted';
			}
			*/
			// #PADC# ----------end----------
		}else{
			$name = $this->name;
		}
		$lv = (int)$this->lv;
		$camp = (int)$this->camp;
		$acctime = date('ymdHis',strtotime($this->accessed_at));
		$fri = (int)$this->fricnt;
		$friMax = (int)$this->friend_max;
		$lc = $this->getLeaderCardsData();
		// #PADC# ----------begin----------
		$clear_dungeon_count = (int)$this->clear_dungeon_cnt;
		$sns_friend = 0;
		$device_type = (int)$this->device_type;
		$last_clear_dungeon_id = (int)$this->last_clear_dungeon_id;
		$ldeck = $this->getLeaderDecksData();
		$last_present_time = date('ymdHis', 0);
		$open_id = 0;
		$opt_msg = $this->checkRejectUserSnsMsg()? 1 : 0;
		$vip_lv = (int)$this->vip_lv;
		$qq_vip = (int)$this->qq_vip;
		$userDeviceData = UserDevice::getUserDeviceFromRedis($pid);
		$ptype = $userDeviceData['pt'];
		$pre_pid = UserDevice::getPreUserId($device_type,$ptype);
		$game_center = (int)$this->game_center;
		// #PADC# ----------end----------

		// #PADC# ----------begin----------
		// たまドラの情報は全て0にする（アプリ側では何もしない）
		$eq1_id = 0;
		$eq1_lv = 0;
		$eq2_id = 0;
		$eq2_lv = 0;
		$eq3_id = 0;
		$eq3_lv = 0;
		// #PADC# ----------end----------

		$friend = array(
			$v,
			$pid,
			$name,
			$lv,
			$camp,
			$acctime,
			$fri,
			$friMax,
			$eq1_id,
			$eq1_lv,
			$eq2_id,
			$eq2_lv,
			$eq3_id,
			$eq3_lv,
		// #PADC# ----------begin----------
			$clear_dungeon_count,	// ダンジョンクリア数
			$sns_friend,	// SNSフレンド
			$device_type,	// iOS or Android
			$last_clear_dungeon_id,	// 直近でクリアしたダンジョンID
			$last_present_time,	// 最後にスタミナプレゼントを送った時間
			$open_id, //openid
			$opt_msg, //メッセージ送信禁止設定
			$vip_lv, //VIP level
			$qq_vip,
			$pre_pid,// 先頭ユーザID
			$game_center,
		// 後に使用デッキモンスター情報を追加するため、リーダーモンスターの数を追加
			count($lc->id),
			(int)$lc->id[0],
			(int)$lc->lv[0],
			(int)$lc->slv[0],
			(int)$lc->hp[0],
			(int)$lc->atk[0],
			(int)$lc->rec[0],
			(int)$lc->psk[0],
			(int)$lc->id[1],
			(int)$lc->lv[1],
			(int)$lc->slv[1],
			(int)$lc->hp[1],
			(int)$lc->atk[1],
			(int)$lc->rec[1],
			(int)$lc->psk[1],
		// #PADC# ----------end----------
		);

		// #PADC# ----------begin----------
		// 使用デッキモンスター情報も最初にモンスターの数を渡す
		$friend[] = count($ldeck);
		foreach($ldeck as $ld){
			$friend[] = (int)$ld[1];
			$friend[] = (int)$ld[2];
			$friend[] = (int)$ld[3];
			$friend[] = (int)$ld[4];
			$friend[] = (int)$ld[5];
			$friend[] = (int)$ld[6];
			$friend[] = (int)$ld[7];
		}
		// #PADC# ----------end----------
        if (empty($this->lc_ps)) {
			$lc_info = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
        } else {
            $lc_info = json_decode($this->lc_ps, true);
        }
        $friend = array_merge($friend, $lc_info);
		// #PADC# ----------begin----------memcache→redisに切り替え
		$redis = Env::getRedisForUser();
		$key = CacheKey::getUserFriendDataFormatKey2($this->id);
		$redis->set($key, $friend, static::MEMCACHED_EXPIRE);	// 10日間保存.
		// #PADC# ----------end----------
		
		return $friend;
	}

	/**
	 * #PADC#
	 * 以前のフォーマットに変換.
	 */
	private static function convOldFriendFormat($friend){
		$user = array();
		list(
			$user['v'],
			$user['pid'],
			$user['name'],
			$user['lv'],
			$user['at'],
			$user['acctime'],
			$user['fri'],
			$user['friMax'],
			$user['eq1_id'],
			$user['eq1_lv'],
			$user['eq2_id'],
			$user['eq2_lv'],
			$user['eq3_id'],
			$user['eq3_lv'],
			$user['clear_dun'],
			$sns_friend,
			$device_type,
			$last_clear_dungeon_id,
			$last_present_time,
			$leader_count,
			$user['card'],
			$user['clv'],
			$user['slv'],
			$plus0,
			$plus1,
			$plus2,
			$plus3,
		) = $friend;

		$user['plus'] = array($plus0, $plus1, $plus2, $plus3);
		$user['v'] = 2;  // クライアント側パース用のバージョン番号。2固定
		return $user;

	}

}

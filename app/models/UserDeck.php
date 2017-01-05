<?php
/**
 *   プレイヤーがダンジョンに連れて行けるカード.
 */
class UserDeck extends BaseModel {
	const TABLE_NAME = "user_deck";

	protected static $columns = array(
		'user_id',
		'deck_num',
		'decks',
	);

	/**
	 * デッキに指定のuser_card cuidが含まれているときに限りTRUEを返す.
	 */
	public function hasUserCardCuid($user_card_cuid) {
		// デッキセット内も精査.
		if(!empty($this->decks)){
			$decks = json_decode($this->decks, TRUE);
			foreach($decks as $deck){
				foreach($deck as $key => $cuids){
					foreach($cuids as $cuid){
						if($cuid == $user_card_cuid) {
							return TRUE;
						}
					}
				}
			}
		}
		return FALSE;
	}

	/**
	 * cuidをカンマ区切りの文字列で返す
	 */
	public function toCuidsCS(){
		$decks = json_decode($this->decks, TRUE);
		$setname = sprintf("set_%02s", $this->deck_num);
		$ret_cuids = array();
		foreach($decks as $deck){
			foreach($deck as $key => $cuids){
				if($key == $setname){
					$ret_cuids = $cuids;
				}
			}
		}
		$cs = join($ret_cuids ,",");
		return $cs;
	}

	/**
	 * デッキセット取得.
	 *
	 * ７枠目以降のチーム枠は、ランク100毎に１つずつ増加していく。
	 * https://61.215.220.70/redmine-pad/issues/3028
	 * ランク1～99　⇒　チーム枠6
	 * ランク100到達　⇒　チーム枠7
	 * ランク200到達　⇒　チーム枠8
	 * ランク300到達　⇒　チーム枠9
	 * ランク400到達　⇒　チーム枠10
	 */
	public function getUserDecks($rank){
		$decks = array();
		$new_decks = array();
		if(!empty($this->decks)){
			$decks = json_decode($this->decks);
		}
		// #PADC# ----------begin----------
		// PADCではランクでデッキ枠が増えたりしないので固定にしておく
		$deck_max = User::INIT_DECKS_MAX;// + floor($rank / 100);
		// #PADC# ----------end----------
		// 最大デッキ数まで満たない場合はデッキ1のリーダーをコピーする.
		$deck1cuid = 0;
		for ($i = 0; $i <= ($deck_max - 1); $i++) {
			$setname = sprintf("set_%02s",$i);
			$flg = FALSE;
			foreach($decks as $key=>$value){
				foreach($value as $key2=>$value2){
					if($key2 == $setname){
						$new_decks[] = array($setname => $value2);
						if( $i == 0 ) {
							$deck1cuid = $new_decks[0];
						}
						$flg = TRUE;
					}
				}
			}
			if($flg === FALSE){
				$new_decks[] = array($setname => array(
					(int)$deck1cuid,
					0,
					0,
					0,
					0,
				));
			}
		}
		return $new_decks;
	}

	/**
	 * デッキ編集２(4.3～).
	 * @param $user_id アカウントID
	 * @param $curdeck 現在選択中のデッキ番号
	 * @param $decks デッキリストのJSON文字列
	 */
	public static function setDecks($user_id, $curdeck, $decks, $totalPower, $params = null){
		try{
			$pdo = Env::getDbConnectionForUserWrite($user_id);
			$pdo->beginTransaction();
			$user_deck = UserDeck::findBy(array('user_id'=>$user_id), $pdo, TRUE);

			$deck_list = array();
			foreach($decks as $deck){
				foreach($deck as $key => $value){
					$deck_list[$key] = $value;
				}
			}

			// デッキセット保存.
			$user_deck->decks = json_encode($decks);
			// カレントデッキ保存.
			$user_deck->deck_num = $curdeck;

			$leader_card_cuids = array();

			$setname = sprintf("set_%02s", $curdeck);
			$cur_deck_cuid = $deck_list[$setname][0];

			// サブリーダー(デッキ1)のcuidも取得.
			$deck1_cuids = $deck_list["set_00"];
			$sub_deck_cuid = $deck1_cuids[0];

			// #PADC# ----------begin----------
			// カレントデッキとチーム1デッキリーダーのcuidを取得
			$cuids = array();
			foreach($deck_list[$setname] as $cuid){
				$cuids[] = $cuid;
			}
			$cuids[] = $sub_deck_cuid;
			$cuids = array_unique($cuids);
			$user_cards = UserCard::findByCuids($user_id, $cuids, $pdo);
			// #PADC# ----------end----------

			$cuid_cards = array();
			foreach($user_cards as $user_card){
				$cuid_cards[$user_card->cuid] = $user_card;
			}

/* チートされても被害が少ないのでコストの計算やめてみる. 140430 Ver6.5～
			$cnt_cuids = count($cuids);
			$cnt_cuids_without_empty_slot = count($cuids_without_empty_slot);
			$cnt_user_cards = count($user_cards);

			if( $cnt_cuids_without_empty_slot != $cnt_user_cards ){
				throw new PadException(RespCode::UNKNOWN_ERROR, "The number of cuids and the number of cards fetched don't match. user_id=$user_id, num cuids=$cnt_cuids_without_empty_slot, num cards fetched=$cnt_user_cards");
			}

			$user = User::find($user_id, $pdo, TRUE);
			if( $user->cost_max < $cost ){
				throw new PadException(RespCode::EXCEEDED_MAX_COST);
			}
*/

			$user = User::find($user_id, $pdo, TRUE);
			// リーダーとサブリーダーのカード情報をusersに保存.
			$lc = array(
				$cuid_cards[$cur_deck_cuid]->cuid,
				$cuid_cards[$cur_deck_cuid]->card_id,
				$cuid_cards[$cur_deck_cuid]->lv,
				$cuid_cards[$cur_deck_cuid]->slv,
				$cuid_cards[$cur_deck_cuid]->equip1,
				$cuid_cards[$cur_deck_cuid]->equip2,
				$cuid_cards[$cur_deck_cuid]->equip3,
				$cuid_cards[$cur_deck_cuid]->equip4,
				$cuid_cards[$sub_deck_cuid]->cuid,
				$cuid_cards[$sub_deck_cuid]->card_id,
				$cuid_cards[$sub_deck_cuid]->lv,
				$cuid_cards[$sub_deck_cuid]->slv,
				$cuid_cards[$sub_deck_cuid]->equip1,
				$cuid_cards[$sub_deck_cuid]->equip2,
				$cuid_cards[$sub_deck_cuid]->equip3,
				$cuid_cards[$sub_deck_cuid]->equip4,
			);
			$user->lc = join(",", $lc);

			// #PADC# ----------begin----------
			$deck = array();
			foreach($deck_list[$setname] as $cuid){
				if ($cuid > 0) {
					$deck[] = array(
							(int)$cuid_cards[$cuid]->cuid,
							(int)$cuid_cards[$cuid]->card_id,
							(int)$cuid_cards[$cuid]->lv,
							(int)$cuid_cards[$cuid]->slv,
							(int)$cuid_cards[$cuid]->equip1,
							(int)$cuid_cards[$cuid]->equip2,
							(int)$cuid_cards[$cuid]->equip3,
							(int)$cuid_cards[$cuid]->equip4,
					);
				}
				else {
					// 空
					$deck[] = array(0,0,0,0,0,0,0,0);
				}
			}
			$user->ldeck = json_encode($deck);
			// #PADC# ----------end----------

			// #PADC_DY# ----------begin----------
			$cur_deck_ps_status = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
			if (!empty($cuid_cards[$cur_deck_cuid]->ps)) {
				$cur_deck_ps_status = json_decode($cuid_cards[$cur_deck_cuid]->ps, true);
			}

			$sub_deck_ps_status = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
			if (!empty($cuid_cards[$sub_deck_cuid]->ps)) {
				$sub_deck_ps_status = json_decode($cuid_cards[$sub_deck_cuid]->ps, true);
			}
			$lc_ps = array_merge($cur_deck_ps_status,$cur_deck_ps_status);

			$user->lc_ps = json_encode($lc_ps);

			// #PADC_DY# ----------end----------

			$user->accessed_at = User::timeToStr(time());
			$user->accessed_on = $user->accessed_at;
			$user->update($pdo);

			$user_deck->update($pdo);

			UserTlog::sendTlogDeckFlow($user_id, $deck_list, $pdo, $totalPower);
		        $token = Tencent_MsdkApi::checkToken ( $params );
                        if ($token) {
                            $user->reportScore(Tencent_MsdkApi::SCORE_TYPE_TEAM_POWER, $token, $totalPower);
                        }	
			$pdo->commit();
		}catch(Exception $e){
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			throw $e;
		}
		return $user_deck;
	}

	/**
	 * カレントデッキ変更
	 * @param $user_id アカウントID
	 * @param $curdeck 現在選択中のデッキ番号
	 */
	public static function changeCurrentDeck($user, $curdeck, $pdo){
		$user_deck = UserDeck::findBy(array('user_id'=>$user->id), $pdo, TRUE);
		$decks = $user_deck->getUserDecks((int)$user->lv);
		if($user_deck->deck_num != $curdeck){
			// カレントデッキ変更.
			$user_deck->deck_num = $curdeck;
			$deck_list = array();
			foreach($decks as $deck){
				foreach($deck as $key => $value){
					$deck_list[$key] = $value;
				}
			}
			$setname = sprintf("set_%02s", $curdeck);
			$cur_deck_cuid = $deck_list[$setname][0];
			$sub_deck_cuid = $deck_list["set_00"][0];

			// リーダーカード (デッキの先頭のカードがリーダになる)
			$user_card = UserCard::findBy(array('user_id' => $user->id, 'cuid' => $cur_deck_cuid), $pdo);
			if($cur_deck_cuid !== $sub_deck_cuid){
				$user_card_sub = UserCard::findBy(array('user_id' => $user->id, 'cuid' => $sub_deck_cuid), $pdo);
			}else{
				// デッキ1がカレントデッキの場合.
				$user_card_sub = $user_card;
			}
			$lc = array(
				$user_card->cuid,
				$user_card->card_id,
				$user_card->lv,
				$user_card->slv,
				$user_card->equip1,
				$user_card->equip2,
				$user_card->equip3,
				$user_card->equip4,
				$user_card_sub->cuid,
				$user_card_sub->card_id,
				$user_card_sub->lv,
				$user_card_sub->slv,
				$user_card_sub->equip1,
				$user_card_sub->equip2,
				$user_card_sub->equip3,
				$user_card_sub->equip4,
			);
			$user->lc = join(",", $lc);

			// #PADC# ----------begin----------
			// deck内容を更新
			$cuids = array();
			foreach($deck_list[$setname] as $cuid){
				$cuids[] = $cuid;
			}
			$user_cards = UserCard::findByCuids($user->id, $cuids, $pdo);
			$cuid_cards = array();
			foreach($user_cards as $user_card){
				$cuid_cards[$user_card->cuid] = $user_card;
			}

			$deck = array();
			foreach($deck_list[$setname] as $cuid){
				if ($cuid > 0) {
					$deck[] = array(
							(int)$cuid_cards[$cuid]->cuid,
							(int)$cuid_cards[$cuid]->card_id,
							(int)$cuid_cards[$cuid]->lv,
							(int)$cuid_cards[$cuid]->slv,
							(int)$cuid_cards[$cuid]->equip1,
							(int)$cuid_cards[$cuid]->equip2,
							(int)$cuid_cards[$cuid]->equip3,
							(int)$cuid_cards[$cuid]->equip4,
					);
				}
				else {
					// 空
					$deck[] = array(0,0,0,0,0,0,0,0);
				}
			}
			$user->ldeck = json_encode($deck);
			// #PADC# ----------end----------

			// #PADC_DY# ----------begin----------
			$cur_deck_ps_status = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
			if (!empty($cuid_cards[$cur_deck_cuid]->ps)) {
				$cur_deck_ps_status = json_decode($cuid_cards[$cur_deck_cuid]->ps, true);
			}

			$lc_ps = array_merge($cur_deck_ps_status,$cur_deck_ps_status);

			$user->lc_ps = json_encode($lc_ps);

			// #PADC_DY# ----------end----------

			$user->accessed_at = User::timeToStr(time());
			$user->accessed_on = $user->accessed_at;
			$user->update($pdo);
			$user_deck->update($pdo);

			$redis = Env::getRedisForUser();
			$key = CacheKey::getUserFriendDataFormatKey2($user->id);
			$redis_data =  $redis->get($key);	// 10日間保存.
			global $logger;
			$logger->log("***redis_data***".json_encode($redis_data),Zend_Log::DEBUG);
			$redis_data = array_slice($redis_data, 0,76);
			$redis_data = array_merge($redis_data,$lc_ps);
			$redis->set($key, $redis_data, Version::MEMCACHED_EXPIRE);	// 10日間保存.
		}
		return $user;
	}

	// データスナップショット作成
	public static function getsnapshots($user_id,$log_date) {
		$pdo = Env::getDbConnectionForUserRead($user_id);
		$sql = "SELECT * FROM ". self::TABLE_NAME ." WHERE user_id = ?";
		$bind_param = array($user_id);
		list($result, $stmt) = self::prepare_execute($sql, $bind_param, $pdo);
		$values = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$snapshot_writer = new Zend_Log_Writer_Stream(Env::SNAPSHOT_LOG_PATH.Env::ENV."_deck_snapshot.log");
		$snapshot_format = '%message%'.PHP_EOL;
		$snapshot_formatter = new Zend_Log_Formatter_Simple($snapshot_format);
		$snapshot_writer->setFormatter($snapshot_formatter);
		$snapshot_logger = new Zend_Log($snapshot_writer);
		foreach($values as $value) {
				$value=preg_replace('/"/', '""',$value);
				$snapshot_logger->log($log_date.",".implode(",",$value), Zend_Log::DEBUG);
		}
	}
	public function getCurrentDeck(){
		return $this->deck_num;
	}
}

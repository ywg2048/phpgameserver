<?php
class TencentBonus extends BaseMasterModel {
	const TABLE_NAME = "padc_tencent_bonuses";
	const VER_KEY_GROUP = "padc_tencent_b";
	const MEMCACHED_EXPIRE = 86400; // 24時間.
	protected static $columns = array (
			'id',
			'device_type',
			'ptype',
			'idip_data',
			'title',
			'message',
			'bonus_id',
			'amount',
			'piece_id' 
	);
	
	/**
	 *
	 * @param User $user        	
	 * @param PDO $pdo        	
	 */
	public static function applyBonuses($user, $pdo) {
		$userDeviceData = UserDevice::getUserDeviceFromRedis ( $user->id );
		$type = $userDeviceData ['t'];
		$ptype = $userDeviceData ['pt'];
		$bonuses = self::getActiveBonuses ( $type, $ptype );
		
		$histories = UserTencentBonusHistory::findAllBy ( array (
				'user_id' => $user->id 
		), null, null, $pdo, null );
		
		$history_ids = array ();
		foreach ( $histories as $history ) {
			$history_ids [$history->id] = $history->tencent_bonus_id;
		}
		
		foreach ( $bonuses as $bonus ) {
			if (in_array ( $bonus->id, $history_ids )) {
				// 既に該当ボーナスを付与済み
				continue;
			}
			$user_tencent_bonus_history = new UserTencentBonusHistory ();
			$user_tencent_bonus_history->user_id = $user->id;
			$user_tencent_bonus_history->tencent_bonus_id = $bonus->id;
			$user_tencent_bonus_history->create ( $pdo );
			self::apply($user->id, $userDeviceData, $bonus, $pdo);
		}
		// 不要な付与履歴を削除.
		self::delExpirationHistory ( $bonuses, $history_ids, $pdo );
	}
	
	/**
	 *
	 * @param number $user_id        	
	 * @param obj $bonus        	
	 * @param PDO $pdo        	
	 */
	public static function apply($user_id, $user_device, $bonus, $pdo) {
		$bonus_id = $bonus->bonus_id;
		$bonus_num = $bonus->amount;
		$piece_id = $bonus->piece_id;
        $title = empty($bonus->title) ? null : $bonus->title;
		$message = empty($bonus->message) ? GameConstant::getParam("TencentIdipBonusMessage") : $bonus->message;

		// #PADC_DY# ----------begin----------
		$idip_data = null;
		if ($bonus->idip_data) {
			// IDIP数据用来记录 IDIPFLOW TLog
			$idip_data = json_decode($bonus->idip_data, true);
		}

		if($bonus_id == NULL) {
			UserMail::sendAdminMailMessage($user_id, UserMail::TYPE_ADMIN_MESSAGE_NORMAL, null, null, $pdo, $message, null, 0, $title);
			// 全局邮件暂时不需要发送IDIPFLow Tlog
			return;
		}
		// #PADC_DY# ----------end----------

		if($bonus_num <= 0){
			return;
		}
		
		if ($bonus_id == BaseBonus::PIECE_ID) {
			// 欠片
			$piece = Piece::find ( $piece_id );
			if ($piece) {
				UserMail::sendAdminMailMessage ( $user_id, UserMail::TYPE_ADMIN_BONUS, BaseBonus::PIECE_ID, $bonus_num, $pdo, $message, null, $piece_id, $title );
			}
		} else {
			UserMail::sendAdminMailMessage ( $user_id, UserMail::TYPE_ADMIN_BONUS, $bonus_id, $bonus_num, $pdo, $message, null, 0, $title );
		}


		// #PADC_DY# ----------begin----------
		// 道具发送IDIPFLow Tlog
		if ($idip_data) {
			Padc_Log_Log::sendIDIPFlow(
				isset($idip_data['area']) ? $idip_data['area'] : 0,
				$user_device['oid'],
				$bonus_id,
				$bonus_num,
				isset($idip_data['serial']) ? $idip_data['serial'] : 0,
				isset($idip_data['source']) ? $idip_data['source'] : 0,
				isset($idip_data['cmd']) ? $idip_data['cmd'] : 0,
				$piece_id,
				isset($idip_data['ptype']) ? $idip_data['ptype'] : 0
			);
		}
		// #PADC_DY# ----------end----------
	}
	
	/**
	 *
	 * @param number $type        	
	 * @param number $ptype        	
	 * @return array
	 */
	public static function getActiveBonuses($type, $ptype) {
		// 有効期限内のボーナスの連想配列
		$bonuses = self::getBonuses ( $type, $ptype );
		$time = time ();
		$b = array ();
		foreach ( $bonuses as $bonus ) {
// FIXME: CBT1に向けてひとまず有効期限をなくす
//			if ($time - strtotime ( $bonus->created_at ) < GameConstant::TENCENT_BONUS_EXPIRE) {
				$b [] = $bonus;
//			}
		}
		return $b;
	}
	
	/**
	 *
	 * @param number $type        	
	 * @param number $ptype        	
	 * @return array
	 */
	public static function getBonuses($type, $ptype) {
		// $key = MasterCacheKey::getTencentBonuses ( $type, $ptype );
		// $b = apc_fetch ( $key );
		// if (FALSE === $b) {
		// 有効期限内のボーナスの連想配列
		/*
		 * #PADC_DY# 修复apc缓存不同步导致重复接收bonus的BUG
		$b = TencentBonus::getAllBy ( array (
				'device_type' => $type,
				'ptype' => $ptype 
			) );
		*/
		// apc_store ( $key, $b, static::MEMCACHED_EXPIRE + static::add_apc_expire () );
		// }
		$b = TencentBonus::getAllByForMemcache(array(
			'device_type' => $type,
			'ptype' => $ptype
		));

		return $b;
	}
	
	/**
	 *
	 * @param array $active_bonuses        	
	 * @param array $history_ids        	
	 * @param PDO $pdo        	
	 */
	private static function delExpirationHistory($active_bonuses, $history_ids, $pdo) {
		$active_bonus_ids = array ();
		foreach ( $active_bonuses as $bonus ) {
			$active_bonus_ids [] = $bonus->id;
		}
		$del_ids = array ();
		foreach ( $history_ids as $id => $all_user_bonus_id ) {
			if (! in_array ( $all_user_bonus_id, $active_bonus_ids )) {
				$del_ids [] = $id;
			}
		}
		if (count ( $del_ids ) > 0) {
			UserTencentBonusHistory::delete_ids ( $del_ids, $pdo );
		}
	}
	
	/**
	 *
	 * @param array $ids        	
	 * @param PDO $pdo        	
	 * @return boolean
	 */
	public static function delete_ids($ids, $pdo = null) {
		if (is_null ( $pdo )) {
			$pdo = Env::getDbConnectionForUserWrite ( $this->user_id );
		}
		$sql = 'DELETE FROM ' . static::TABLE_NAME;
		$sql .= ' WHERE id IN (' . str_repeat ( '?,', count ( $ids ) - 1 ) . '?)';
		$stmt = $pdo->prepare ( $sql );
		$result = $stmt->execute ( $ids );
		
		if (Env::ENV !== "production") {
			global $logger;
			$logger->log ( ("sql_query: " . $sql . "; bind: " . join ( ",", $ids )), Zend_Log::DEBUG );
		}
		
		return $result;
	}
	
	/**
	 */
	public static function removeCache($type, $ptype) {
		$key = static::getAllByKey ( array (
				'device_type' => $type,
				'ptype' => $ptype 
		) );
		$redis = Env::getRedisForShare();
		if ($redis->exists ( $key )) {
			$redis->del ( $key );
		}
		
		/*
		 * #PADC_DY# 修复apc缓存不同步导致重复接收bonus的BUG
		if(apc_exists($key))
		{
			apc_delete($key);
		}
		 */
	}
}

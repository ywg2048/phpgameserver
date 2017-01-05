<?php

/**
 * #PADC_DY#
 * 活动配置
 */
class Activity extends BaseMasterModel {

	const TABLE_NAME = "padc_activities";
	const VER_KEY_GROUP = "padcactivity";
	const MEMCACHED_EXPIRE = 86400; // 24小时
	const REWARD_SEND_STATUS_INIT = 0;
	const REWARD_SEND_STATUS_SENT = 1;

	// 活动类型
	const ACTIVITY_TYPE_NORMAL = 0; // 普通
	const ACTIVITY_TYPE_LIMIT = 1; // 仅限一次
	const ACTIVITY_TYPE_DAILY = 2; // 每日
	const ACTIVITY_TYPE_SPECIAL = 3; // 特殊
	const ACTIVITY_TYPE_FIRST_CHARGE = 4; // 首充送礼
	const ACTIVITY_TYPE_SHARE = 5; // 分享
	const ACTIVITY_TYPE_FIRST_CHARGE_DOUBLE = 6; // 首充双倍
	const ACTIVITY_TYPE_1YG = 7; // 1元购
	const ACTIVITY_TYPE_TOTAL_CHARGE = 8; // 累计充值
	const ACTIVITY_TYPE_DAILY_CHARGE = 9; // 每日充值
	const ACTIVITY_TYPE_DAILY_CHARGE_EXTENDED = 10; // 连续每日充值
	const ACTIVITY_TYPE_TOTAL_CONSUM = 11; // 累计消费
	const ACTIVITY_TYPE_POWER = 12; // 战斗力目标达成
	const ACTIVITY_TYPE_DAILY_LOGIN = 13; // 每日登陆
	const ACTIVITY_TYPE_MONTHCARD = 14; // 月卡奖励
	const ACTIVITY_TYPE_EXCHANGE_ITEM = 20; // 物品兑换物品活动类型
	const ACTIVITY_TYPE_EXCHANGE_TYPE_PIECE = 'piece'; // 物品兑换物品消耗物类型碎片
	const ACTIVITY_TYPE_EXCHANGE_TYPE_COIN = 'coin'; // 物品兑换物品消耗物类型金币
	const ACTIVITY_TYPE_EXCHANGE_TYPE_GOLD = 'gold'; // 物品兑换物品消耗物类型魔法石
	const ACTIVITY_TYPE_CONSUM_POINT_RANKING = 30; // 魔法石充值限时积分
	const ACTIVITY_TYPE_COIN_CONSUM = 41; // 期间金币累计消费
	const ACTIVITY_TYPE_STA_BUY_COUNT = 42; // 期间购买体力次数
	const ACTIVITY_TYPE_GACHA_COUNT = 43; // 期间魔法石扭蛋次数
	const ACTIVITY_TYPE_CARD_EVO_COUNT = 44; // 期间卡牌进化次数
	const ACTIVITY_TYPE_SKILL_AWAKE_COUNT =45; // 期间技能觉醒次数

	protected static $columns = array(
		'id',
		'activity_type',
		'name',
		'description',
		'seq',
		'reward_text',
		'reward_img',
		'reward_condition',
		'vip_lv',
		'bonus_id1',
		'amount1',
		'piece_id1',
		'bonus_id2',
		'amount2',
		'piece_id2',
		'bonus_id3',
		'amount3',
		'piece_id3',
		'bonus_id4',
		'amount4',
		'piece_id4',
		'sort_id',
		'template',
		'tip_pos',
		'tip',
		'time_pos',
		'bg_img',
		'icon_img',
		'btn_img1',
		'btn_img2',
		'btn_img3',
		'btn_skip',
		'bottom',
		'show_condition',
		'begin_at',
		'finish_at',
		'del_flg',
		'reward_mail_title',
		'reward_mail_message',
		'reward_send_at',
		'reward_send_status',
		'exchange_times',
	);

	/**
	 * 是否在有效期内
	 */
	public function isEnabled($time) {
		// 活动无效
		if ($this->del_flg) {
			return false;
		}

		// 是否在有效期内
		$begin_at = static::strToTime($this->begin_at);
		$finish_at = static::strToTime($this->finish_at);
		if ($begin_at > 0 && $finish_at > 0) {
			if ($begin_at <= $time && $time <= $finish_at) {
				return true;
			}
			return false;
		}

		return true;
	}

	/**
	 * 根据类型获取单个活动
	 */
	public static function getByType($type) {
		$activities = self::getAllBy(array(
			'activity_type' => $type,
			'del_flg' => 0
		));

		$now = time();
		foreach ($activities as $activity) {
			if ($activity->isEnabled($now)) {
				return $activity;
			}
		}

		return null;
	}

	/**
	 * 获取活动条件
	 */
	public function getCondition() {
		$condition = null;
		if(!empty($this->reward_condition)) {
			$condition = json_decode($this->reward_condition, true);
		}
		return $condition;
	}

	/**
	 * 判断活动是否满足领取条件
	 */
	public function checkCondition($val, $vip_lv = 0) {
		// 活动不再有限期或者用户级别未达到
		if(!$this->isEnabled(time()) || $vip_lv < (int) $this->vip_lv) {
			return false;
		}

		// 不需要额外条件
		$condition = $this->getCondition();
		if(empty($condition)) {
			return true;
		}

		// 满足活动类型和相应条件
		if(($this->activity_type == self::ACTIVITY_TYPE_TOTAL_CHARGE && isset($condition['tp_gold_period']) && $val >= (int) $condition['tp_gold_period'])
			|| ($this->activity_type == self::ACTIVITY_TYPE_DAILY_CHARGE && isset($condition['pgold']) && $val >= (int) $condition['pgold'])
			|| ($this->activity_type == self::ACTIVITY_TYPE_DAILY_CHARGE_EXTENDED && isset($condition['count_p6']) && ($val % (int) $condition['count_p6'] == 0))
			|| ($this->activity_type == self::ACTIVITY_TYPE_TOTAL_CONSUM && isset($condition['tc_gold_period']) && $val >= (int) $condition['tc_gold_period'])
			|| ($this->activity_type == self::ACTIVITY_TYPE_POWER && isset($condition['power']) && $val >= (int) $condition['power'])) {
			return true;
		}

		// 新增累计类活动条件check
		// 累计金币消费
		if($this->activity_type == self::ACTIVITY_TYPE_COIN_CONSUM){
			if(isset($condition['coin_total']) && $val >= (int)$condition['coin_total']){
				return true;
			}
		}
		// 累计体力购买
		if($this->activity_type == self::ACTIVITY_TYPE_STA_BUY_COUNT){
			if(isset($condition['stamina_buy']) && $val >= (int)$condition['stamina_buy']){
				return true;
			}
		}
		// 累计扭蛋次数
		if($this->activity_type == self::ACTIVITY_TYPE_GACHA_COUNT){
			if(isset($condition['gacha_cnt']) && $val >= (int)$condition['gacha_cnt']){
				return true;
			}
		}
		// 累计卡牌进化次数
		if($this->activity_type == self::ACTIVITY_TYPE_CARD_EVO_COUNT){
			if(isset($condition['card_evolve']) && $val >= (int)$condition['card_evolve']){
				return true;
			}
		}
		// 累计技能觉醒次数
		if($this->activity_type == self::ACTIVITY_TYPE_SKILL_AWAKE_COUNT){
			if(isset($condition['skill_awake']) && $val >= (int)$condition['skill_awake']){
				return true;
			}
		}
		return false;
	}

	/**
	 * 兑换类活动，获得兑换用物品信息
	 * 例如：{"piece10001":5}表示，兑换奖励的物品需要碎片（piece），碎片id为10001，需要的数量为5.
	 * 该方法就是提取该字符串里面的信息
	 * By.David.Zhang
	 */
	public function getExchangeItem() {
		$partten_type = '/[A-Za-z]+/';
		$partten_piece_id = '/[0-9]+/';
		$condition = null;
		$cost_type = '';
		$cost_amount = 0;
		$cost_piece_id = 0;
		if (!empty($this->reward_condition)) {
			$condition = json_decode($this->reward_condition, true);
			$keys = array_keys($condition);
			$cost_amount = (int)$condition[$keys[0]];
			if (preg_match($partten_type, $keys[0], $matches)) {
				$cost_type = $matches[0];
			}
			if (preg_match($partten_piece_id, $keys[0], $matches)) {
				$cost_piece_id = (int)$matches[0];
			}
		}
		return array($cost_type, $cost_piece_id, $cost_amount);
	}

	public function sendRewardsToUser($user_id, $pdo = null)
	{
		$user_activity = UserActivity::findBy(array(
			'user_id' => $user_id,
			'activity_id' => $this->id
		), $pdo);
		if ($user_activity && $user_activity->status == UserActivity::STATE_RECEIVED) {
			// 奖励已经发放
			return;
		}

		$bonus_id1 = (int) $this->bonus_id1;
		$amount1 = (int) $this->amount1;
		$piece_id1 = (int) $this->piece_id1;
		$bonus_id2 = (int) $this->bonus_id2;
		$amount2 = (int) $this->amount2;
		$piece_id2 = (int) $this->piece_id2;
		$bonus_id3 = (int) $this->bonus_id3;
		$amount3 = (int) $this->amount3;
		$piece_id3 = (int) $this->piece_id3;
		$bonus_id4 = (int) $this->bonus_id4;
		$amount4 = (int) $this->amount4;
		$piece_id4 = (int) $this->piece_id4;
		
		$title = $this->reward_mail_title;
		$message = $this->reward_mail_message;

		if ($bonus_id1 && $amount1) {
			$this->sendRewardByMail($user_id, $bonus_id1, $amount1, $piece_id1, $title, $message, $pdo);
		}
		if ($bonus_id2 && $amount2) {
			$this->sendRewardByMail($user_id, $bonus_id2, $amount2, $piece_id2, $title, $message, $pdo);
		}
		if ($bonus_id3 && $amount3) {
			$this->sendRewardByMail($user_id, $bonus_id3, $amount3, $piece_id3, $title, $message, $pdo);
		}
		if ($bonus_id4 && $amount4) {
			$this->sendRewardByMail($user_id, $bonus_id4, $amount4, $piece_id4, $title, $message, $pdo);
		}
	}

	private function sendRewardByMail($user_id, $bonus_id, $amount, $piece_id, $title, $message, $pdo = null)
	{
		if ($bonus_id == BaseBonus::PIECE_ID) {
			$piece = Piece::find($piece_id);
			if ($piece) {
				UserMail::sendAdminMailMessage($user_id, UserMail::TYPE_ADMIN_BONUS, BaseBonus::PIECE_ID, $amount, $pdo, $message, null, $piece_id, $title);
			}
		} else {
			UserMail::sendAdminMailMessage($user_id, UserMail::TYPE_ADMIN_BONUS, $bonus_id, $amount, $pdo, $message, null, 0, $title);
		}
	}


}

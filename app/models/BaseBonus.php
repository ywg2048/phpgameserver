<?php
/**
 * 汎用ボーナスクラス
 */

abstract class BaseBonus {
	public $item_id = null;
	public $amount = null;
	public $plus_hp = null;
	public $plus_atk = null;
	public $plus_rec = null;
	public $avatar_id = null;
	public $avatar_lv = null;
	// #PADC# ----------begin----------
	public $piece_id = null;
	// #PADC# ----------end----------

	const MAX_CARD_ID = 9899; // ver6.1～カード.
//  const MAX_CARD_ID = 899; // カード.
	const COIN_ID = 9900; // コイン.
	const MAGIC_STONE_ID = 9901; // 魔法石(無料).
	const FRIEND_POINT_ID = 9902; // 友情ポイント.
	const STAMINA_ID = 9903; // スタミナ.
	const COST_ID = 9904; // チームコスト.
	const FRIEND_MAX_ID = 9905; // フレンド数上限.
	const EXP_ID = 9906; // 経験値.
	const PREMIUM_MAGIC_STONE_ID = 9908; // 魔法石(有料).
	const MEDAL_ID = 9909; // メダル.
	const AVATAR_ID = 9910; // アバター
	// #PADC_DY# ----------begin----------
	const USER_EXP = 9992; // 玩家经验值
	const USER_VIP_EXP = 9993; // vip等级经验（累计充值记录）
	// #PADC_DY# -----------end-----------

	// #PADC# ----------begin----------
	const STAMINA_RECOVER_ID = 9994; // スタミナ回復
	const RANKING_POINT_ID = 9995; // ランキングポイント
	const CONTINUE_ID = 9996;//無料コンティニュー回数回復
	const ROUND_ID = 9997;//無料周回
	const PIECE_ID = 9998; // カケラ.
	// #PADC# ----------end----------
	const MAIL_ID = 9999; // メール.

//管理ツール補填用ステータス
	const ADD_CARDS_DIRECT_ID = 99991; // 通常版 BOX直接カード追加(CSV)
	const CLEAR_RESET_ID = 99992; // 通常版 ダンジョンクリアリセット
	const UUID_RESET_ID = 99993; // UUIDリセット
	const MAKE_DEVICE_CODE_ID = 99994; // 機種変コード発行
	const SECRET_CODE_RESET_ID = 99995; // 秘密のコードリセット
	const AUTH_DATA_RESET_ID = 99996; // Google認証情報リセット
	const USER_BAN_ID = 99997; // 一括メール.
	const CHANGE_STATUS_ID = 99998; // ステータス変更.
	const CHANGE_USER_ID = 99999; // ログイン情報付け替え.

//  const OLD_MAX_CARD_ID = 899;
	const OLD_COIN_ID = 900;
//  const OLD_MAGIC_STONE_ID = 901;
//  const OLD_FRIEND_POINT_ID = 902;

//  const OLD_STAMINA_ID = 903;
//  const OLD_COST_ID = 904;
//  const OLD_FRIEND_MAX_ID = 905;


//  const OLD_EXP_ID = 906;
//  const OLD_LEVEL_ID = 907;
//  const OLD_PREMIUM_MAGIC_STONE_ID = 908;
//  const OLD_MAIL_ID = 999;

	/**
	 * ボーナスの配列を指定ユーザに適用する.
	 * @return 適用後のUserオブジェクト.
	 */
	// #PADC# パラメータ追加
	public static function applyBonuses($user, $bonuses, $pdo, $token) {
		foreach($bonuses as $bonus) {
			// #PADC# パラメータ追加
			$bonus->apply($user, $pdo, null, $token);
		}
		return $user;
	}

	/**
	 * ボーナスをユーザに適用する.
	 * @return 適用後のUserオブジェクトまたは付与されたUserCardオブジェクト.
	 */
	// #PADC# 無料魔法石付与の為にパラメータ追加
	public function apply($user, $pdo, $next_cuid = null, $token) {
		$result = null;
		if($this->item_id <= BaseBonus::MAX_CARD_ID) {
			// カードボーナス. スキルレベルは固定で1.
			$result = UserCard::addCardToUser(
				$user->id,
				$this->item_id,
				$this->amount,
				UserCard::DEFAULT_SKILL_LEVEL,
				$pdo,
				$this->plus_hp,
				$this->plus_atk,
				$this->plus_rec,
				0, // psk
				$next_cuid
			);
		} else if($this->item_id == BaseBonus::COIN_ID) {
			// コインボーナス.
			$user->addCoin($this->amount);
			$result = $user;
		} else if($this->item_id == BaseBonus::MAGIC_STONE_ID) {
			// (無料)魔石ボーナス.
			// #PADC# ----------begin---------- Tencentサーバーに無料魔法石追加
			$user->presentGold($this->amount, $token);
			// #PADC# ----------end----------
			$result = $user;
		} else if($this->item_id == BaseBonus::PREMIUM_MAGIC_STONE_ID) {
			// (有料)魔石ボーナス.
			$user->addPGold($this->amount);
			$result = $user;
		} else if($this->item_id == BaseBonus::FRIEND_POINT_ID) {
			// 友情ポイントボーナス.
			// not used yet.
		} else if($this->item_id == BaseBonus::MEDAL_ID) {
			// メダル.
			$user->addMedal($this->amount);
			$result = $user;
		} else if($this->item_id == BaseBonus::AVATAR_ID) {
			$result = WUserAitem::addBonusAvatar(
				$user,
				$item_id,
				$this->avatar_id,
				$this->avatar_lv
			);
			$result = $user;
		}
		// #PADC# ----------begin----------
		else if($this->item_id == BaseBonus::PIECE_ID) {
			// カケラ
			$result = UserPiece::addUserPieceToUser(
				$user->id,
				$this->piece_id,
				$this->amount,
				$pdo
			);
			if(isset($result["card"])){
				// 図鑑登録数の更新
				$user_book = UserBook::getByUserId($user->id, $pdo);
				$user->book_cnt = $user_book->getCountIds();
			}
		}
		else if($this->item_id == BaseBonus::ROUND_ID) {
			// 周回チケット.
			$user->addRound($this->amount);
			$result = $user;
		}
		// #PADC# ----------end----------

		return $result;
	}

	/**
	 * ボーナスの名前を返す.
	 * @return ボーナス名の連想配列.
	 */
	public static function getKinds() {
		$bonus_kinds = array(
			'0' => 'なし',
			BaseBonus::COIN_ID => 'コイン',
			BaseBonus::MAGIC_STONE_ID => '(無料)魔法石',
			BaseBonus::FRIEND_POINT_ID => '友情ポイント',
			BaseBonus::STAMINA_ID => 'スタミナ',
			BaseBonus::COST_ID => 'チームコスト',
			BaseBonus::FRIEND_MAX_ID => 'フレンド数上限',
			BaseBonus::EXP_ID => '経験値',
			BaseBonus::PREMIUM_MAGIC_STONE_ID => '(有料)魔法石',
			BaseBonus::MEDAL_ID => 'メダル',
			BaseBonus::AVATAR_ID => 'アバター',
			BaseBonus::MAIL_ID => 'メール送信のみ',
			BaseBonus::USER_BAN_ID => 'ユーザステータス変更',
			BaseBonus::CHANGE_STATUS_ID => 'ユーザステータス変更',
			BaseBonus::CHANGE_USER_ID => 'ユーザログイン情報付け替え',
			BaseBonus::MAKE_DEVICE_CODE_ID => '機種変コード発行',
			BaseBonus::SECRET_CODE_RESET_ID => '秘密のコードリセット',
			BaseBonus::AUTH_DATA_RESET_ID => 'Google認証情報リセット',
			BaseBonus::UUID_RESET_ID => 'UUIDリセット',
			BaseBonus::CLEAR_RESET_ID => '通常版 ダンジョンクリアリセット',
			BaseBonus::ADD_CARDS_DIRECT_ID => '通常版 BOX直接カード追加(CSV)',
			// #PADC# ----------begin----------
			BaseBonus::PIECE_ID => '欠片',
			// #PADC# ----------end----------
		);
		return $bonus_kinds;
	}

	/**
	 * ボーナスの名前を返す(モンスター名も返すバージョン).
	 * @return ボーナス名.
	 */
	public static function getName($bonus_id) {
		if($bonus_id > self::MAX_CARD_ID){
			$kinds = self::getKinds();
			$name = $kinds[$bonus_id];
		}else{
			static $bonus_card_name;
			if(isset($bonus_card_name[$bonus_id])){
				$name = $bonus_card_name[$bonus_id];
			}else{
				$card = Card::get($bonus_id);
				$name = isset($card->name) ? $card->name : 'なし';
				$bonus_card_name[$bonus_id] = $name;
			}
		}
		return $name;
	}
}

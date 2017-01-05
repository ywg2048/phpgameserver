<?php
/**
 * ダンジョン.
 */

class Dungeon extends BaseMasterModel {
	const TABLE_NAME = "dungeons";
	const VER_KEY_GROUP = "dung";
	const MEMCACHED_EXPIRE = 86400; // 24時間.

	// ドロップ無しの数値（2進数で0100000000=512）
	const NONE_DROP = 512;

	protected static $columns = array(
		'id',
		'name',
		// #PADC# ----------begin----------
		'panel_id', // #PADC# フロア看板ID
		// #PADC# ----------end----------
		'attr',
		'dtype',
		// #PADC# ----------begin----------
		'dkind', // #PADC# SPダンジョン種類
		// #PADC# ----------end----------
		'dwday',
		'dsort',
		'reward_gold',
		// #PADC# ----------begin----------
		'rankup_flag', // #PADC# ランクアップするダンジョンかどうか
		'url_flag', // #PADC# コンティニュー時の遷移先URLがあるかどうか
		'share_file',// #PADC# シェア画像
		// #PADC# ----------end----------
	);

	const DUNG_TYPE_NORMAL = 0;
	const DUNG_TYPE_EVENT = 1;
	const DUNG_TYPE_TECHNICAL = 2;
	const DUNG_TYPE_LEGEND = 3;

	// スペシャルダンジョンでの種類
	const DUNG_KIND_NONE = 0;	// 未分類
	const DUNG_KIND_ADVENT = 1;	// 降臨ダンジョン
	const DUNG_KIND_SUDDEN = 2;	// 突発系ダンジョン
	const DUNG_KIND_COLLABO = 3;	// コラボダンジョン
	const DUNG_KIND_GUERRILLA = 4;	// ゲリラダンジョン
	const DUNG_KIND_DAILY = 5;	// デイリーダンジョン
	const DUNG_KIND_BUY = 6;	// 購入ダンジョン
	const DUNG_KIND_MUGEN = 7;	// 無限回廊ダンジョン

	// #PADC# ----------begin----------
	// ダンジョンのパネル色指定用 https://padcn-redmine-tgiyfm.gungho.jp/projects/pad/wiki/Padc_image_dungeon_panel
	const DUNGEON_PANEL_NORMAL	= 1;
	const DUNGEON_PANEL_LIMIT	= 4;
	const DUNGEON_PANEL_SKILL	= 2;
	// #PADC# ----------end----------

	// #PADC# ----------begin----------
	// MY : ランキングダンジョンで、アクセスするクラスを変更する必要があるクラス。
	const DUNGEON_FLOOR_CLASS = 'DungeonFloor';
	// #PADC# ----------end----------
	/**
	 * このイベントダンジョンが開放曜日, ボーナス開放条件ともに考慮した上で、
	 * 開放されているのであればTRUEを返す.
	 * イベントダンジョンでなければ例外を投げる.
	 */
	public function isOpened($active_bonuses) {
		// イベントダンジョンチェック.
		if($this->isNormalDungeon()) {
			throw new PadException(RespCode::UNKNOWN_ERROR, "normal dungeon is always opened.");
		}
		// #PADC# ----------begin----------
		// 曜日ダンジョンは出す予定が無いので曜日チェック処理は一旦コメントアウトでカット
		// 今後出したいという話が出たら改めて検討する
		//$dwday = date("N", time());
		//// 開放曜日チェック. date("N", time()) で月曜1〜日曜7を取得.dwday=8は土日開放とみなす
		//// https://61.215.220.70/redmine-pad/issues/1135
		//if($this->dwday && ($this->dwday == $dwday || ($this->dwday == 8 && ($dwday == 6 || $dwday == 7)))) {
		//	return TRUE;
		//}
		//else 
		// #PADC# ----------end----------
		{
			// ボーナス開放チェック.
			$open_bonus = LimitedBonus::getActiveOpenedForDungeon($this, $active_bonuses);
			if($open_bonus) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * このダンジョンの全てのフロアを返す.
	 */
	public function getFloors() {
		// #PADC# 継承先で参照するDBを変更するため、DungeonFloorを動的クラスに変更。
		$dungeon_floor_class = static::DUNGEON_FLOOR_CLASS;
		return $dungeon_floor_class::findAllBy(array('dungeon_id'=>$this->id));
	}
	/**
	 * このダンジョンの全フロア数を返す.
	 */
	public function getFloorCount() {
		// #PADC# 継承先で参照するDBを変更するため、DungeonFloorを動的クラスに変更。
		$dungeon_floor_class = static::DUNGEON_FLOOR_CLASS;
		return $dungeon_floor_class::countAllBy(array('dungeon_id'=>$this->id));
	}

	/**
	 * このダンジョンがノーマルタイプ（テクニカルダンジョン）のダンジョンであるときに限りTRUEを返す.
	 */
	public function isNormalDungeon() {
		return in_array($this->dtype, array(self::DUNG_TYPE_NORMAL, self::DUNG_TYPE_TECHNICAL, self::DUNG_TYPE_LEGEND));
	}

	/**
	 * このダンジョンがレジェンドダンジョンであるときに限りTRUEを返す.
	 */
	public function isLegendDungeon() {
		return ($this->dtype == self::DUNG_TYPE_LEGEND);
	}

	/**
	 * このダンジョンがイベントダンジョンであるときに限りTRUEを返す.
	 */
	public function isEventDungeon() {
		return ($this->dtype == self::DUNG_TYPE_EVENT);
	}

	/**
	 * このダンジョンがデイリーダンジョンであるときに限りTRUEを返す.
	 */
	public function isDailyDungeon() {
		return (($this->dtype == self::DUNG_TYPE_EVENT) && ($this->dkind == self::DUNG_KIND_DAILY));
	}

	/**
	 * #PADC_DY# 判断是否是特殊关卡，排名关卡等非普通的关卡
	 */
	public function isSpecialDungeon() {
		return ($this->dkind != self::DUNG_KIND_NONE);
	}
}

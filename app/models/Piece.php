<?php
/**
 * #PADC#
 * カケラ.
 */

class Piece extends BaseMasterModel {
	const TABLE_NAME = "padc_pieces";
	const VER_KEY_GROUP = "padcpiece";
	const MEMCACHED_EXPIRE = 86400; // 24時間.

	// カケラ種類
	const PIECE_TYPE_MONSTER = 0;		// モンスターカケラ
	const PIECE_TYPE_STRENGTH = 1;		// 強化用カケラ
	const PIECE_TYPE_EVOLUTION = 2;		// 進化用カケラ
	const PIECE_TYPE_PLUS_HP = 3;		// プラスのカケラ（HP）
	const PIECE_TYPE_PLUS_ATK = 4;		// プラスのカケラ（攻撃）
	const PIECE_TYPE_PLUS_REC = 5;		// プラスのカケラ（回復）
	const PIECE_TYPE_SKILL = 6;
	const PIECE_TYPE_ULTIMATE = 7;		// 究極進化のカケラ
	//const PIECE_TYPE_AROUSAL = 6;		// 覚醒用カケラ（※今後追加予定）


	// モンスターカケラ以外のID
	/** 「＋」の欠片 */
	const PIECE_ID_PLUS_HP  = 10008;		// プラスのカケラ（HP）
	const PIECE_ID_PLUS_ATK = 10009;		// プラスのカケラ（攻撃）
	const PIECE_ID_PLUS_REC = 10010;		// プラスのカケラ（回復）
	/** 強化の欠片 */
	const PIECE_ID_STRENGTH = 10001;

	// ID、カケラの種類、カケラの名前、生成カードID、生成に必要な個数、合成経験値、売却コイン
	protected static $columns = array(
		'id',
		'name',
		'category',
		'type',
		'cid',
		'gcnt',
		'attr',
		'sattr',
		'mt',
		'mt2',
		'rare',
		'mexp',
		'eexp',
		'scost',
		'tcost',
		'sort_no',
	);

	public function isTypeMonster()
	{
		return $this->type == Piece::PIECE_TYPE_MONSTER;
	}
	public function isTypeStrength()
	{
		return $this->type == Piece::PIECE_TYPE_STRENGTH;
	}
	public function isTypeEvolution()
	{
		return $this->type == Piece::PIECE_TYPE_EVOLUTION;
	}
	public function isTypeHPPlus()
	{
		return $this->type == Piece::PIECE_TYPE_PLUS_HP;
	}
	public function isTypeATKPlus()
	{
		return $this->type == Piece::PIECE_TYPE_PLUS_ATK;
	}
	public function isTypeRECPlus()
	{
		return $this->type == Piece::PIECE_TYPE_PLUS_REC;
	}

	public function isTypeUltimateEvolution()
	{
		return $this->type == Piece::PIECE_TYPE_ULTIMATE;
	}
	/**
	 * 強化合成で経験値が取得できる欠片の判定。
	 */
	public function isTypeCompositeExp()
	{
		return $this->type == Piece::PIECE_TYPE_MONSTER || $this->type == Piece::PIECE_TYPE_STRENGTH;
	}
	/**
	 * 進化合成に使用可能な欠片、モンスターか進化の欠片
	 */
	public function isTypeCompositeEvolution()
	{
		return $this->type == Piece::PIECE_TYPE_MONSTER || $this->type == Piece::PIECE_TYPE_EVOLUTION;
	}

	/**
	 * プラスの欠片判定。
	 */
	public function isTypeCompositePlus()
	{
		return $this->isTypeHPPlus() || $this->isTypeATKPlus() || $this->isTypeRECPlus();
	}

	/**
	 * スキルの欠片判定。
	 */
	public function isTypeSkillPlus()
	{
		return $this->type == Piece::PIECE_TYPE_SKILL;
	}
	/**
	 * 欠片情報を一式取得（欠片IDをキーとした連想配列として整形）
	 * @param string $key
	 * @return multitype:unknown
	 */
	public static function getPiecesWithSetKey($key='id')
	{
		$params	= array();
		$pieces	= self::findAllBy($params);
		$result	= array();
		foreach($pieces as $_piece)
		{
			$result[$_piece->$key] = $_piece;
		}
		return $result;
	}
}

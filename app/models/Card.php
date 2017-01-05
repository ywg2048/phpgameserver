<?php
/**
 * カード.
 */

class Card extends BaseMasterModel {
	const TABLE_NAME = "cards";
	const VER_KEY_GROUP = "card";
	const MEMCACHED_EXPIRE = 86400; // 24時間.

	const SIZE_S = 0;
	const SIZE_SM = 1;
	const SIZE_M = 2;
	const SIZE_ML = 3;
	const SIZE_L = 4;
	const SIZE_LL = 5;

// #PADC# ----------begin----------
	const MAX_RARE = 8; // 最大レア度
	const MAX_MLV = 99; // 最大レベル上限
// #PADC# ----------end----------
	const MONSTER_TYPE_EVOLUTION = 0;       // 進化用モンスター
	const MONSTER_TYPE_BALANCE = 1;         // バランスタイプ
	const MONSTER_TYPE_HP = 2;              // 体力タイプ
	const MONSTER_TYPE_REC = 3;             // 回復タイプ
	const MONSTER_TYPE_DRAGON = 4;          // ドラゴンタイプ
	const MONSTER_TYPE_GOT = 5;             // 神タイプ
	const MONSTER_TYPE_ATK = 6;             // 攻撃タイプ
	const MONSTER_TYPE_RESERVE_0 = 7;       // 予備
	const MONSTER_TYPE_RESERVE_1 = 8;       // 予備
	const MONSTER_TYPE_RESERVE_2 = 9;       // 予備
	const MONSTER_TYPE_RESERVE_3 = 10;      // 予備
	const MONSTER_TYPE_RESERVE_4 = 11;      // 予備
	const MONSTER_TYPE_RESERVE_5 = 12;      // 予備
	const MONSTER_TYPE_RESERVE_6 = 13;      // 特別保護タイプ（チョコボ等）
	const MONSTER_TYPE_FEED = 14;           // 強化合成用モンスター
	const MONSTER_TYPE_MONEY = 15;          // 換金用モンスター

	// モンスターサイズ番号とピクセル数の対応.
	public static $size_to_width = array(
		Card::SIZE_S  => 80,
		Card::SIZE_SM => 120,
		Card::SIZE_M  => 144,
		Card::SIZE_ML => 200,
		Card::SIZE_L  => 256,
		Card::SIZE_LL => 512,
	);

	protected static $columns = array(
		'id',
		// #PADC# ----------begin----------
		'padc_id',
		// #PADC# ----------end----------
		'name',
		'attr',
		'sattr',
		'spup',
		'mt',
		'mt2',
		'rare',
		'grp',
		'cost',
		'size',
		'mlv',
		'mcost',
		'ccost',
		'scost',
		'pmhpa',
		'pmhpb',
		'pmhpc',
		'pmhpd',
		'patka',
		'patkb',
		'patkc',
		'patkd',
		'preca',
		'precb',
		'precc',
		'precd',
		'pexpa',
		'pexpb',
		'pexpc',
		'pexpd',
		'skill',
		// #PADC# ----------begin----------
		'sklmin',
		'sklmax',
		'pskl',
		// #PADC# ----------end----------
		'ska',
		'skb',
		'lskill',
		'lska',
		'lskb',
		'acyc',
		'drop_card_id1',
		'drop_prob1',
		'drop_card_id2',
		'drop_prob2',
		'drop_card_id3',
		'drop_prob3',
		'drop_card_id4',
		'drop_prob4',
		'emhpa',
		'emhpb',
		'emhpc',
		'emhpd',
		'eatka',
		'eatkb',
		'eatkc',
		'eatkd',
		'edefa',
		'edefb',
		'edefc',
		'edefd',
		'coink',
		'expk',
		'gupc',
		// #PADC# ----------begin----------
		'gupc2',
		'gupc3',
		'gupc4',
		'gupc5',
		// #PADC# ----------end----------
		'gup1',
		'gup2',
		'gup3',
		'gup4',
		'gup5',
		'dev1',
		'dev2',
		'dev3',
		'dev4',
		'dev5',
		// #PADC# ----------begin----------
		'gup_piece_id',
		'gup_exp',
		'gup_exp2',
		'gup_exp3',
		'gup_exp4',
		'gup_exp5',
		'gup_final',
		// #PADC# ----------end----------
		'estu',
		'esturn2',
		// #PADC_DY# ----------begin----------
		// 究极进化材料数量
		'ult_piece_id1',
		'ult_piece_num1',
		'ult_piece_id2',
		'ult_piece_num2',
		'ult_piece_id3',
		'ult_piece_num3',
		'ult_piece_id4',
		'ult_piece_num4',
		'ult_piece_id5',
		'ult_piece_num5',
		// #PADC_DY# -----------end-----------
		'aip0',
		'aip1',
		'aip2',
		'aip3',
		'aip4',
		'ai0num',
		'ai0aip',
		'ai0rnd',
		'ai1num',
		'ai1aip',
		'ai1rnd',
		'ai2num',
		'ai2aip',
		'ai2rnd',
		'ai3num',
		'ai3aip',
		'ai3rnd',
		'ai4num',
		'ai4aip',
		'ai4rnd',
		'ai5num',
		'ai5aip',
		'ai5rnd',
		'ai6num',
		'ai6aip',
		'ai6rnd',
		'ai7num',
		'ai7aip',
		'ai7rnd',
		'ai8num',
		'ai8aip',
		'ai8rnd',
		'ai9num',
		'ai9aip',
		'ai9rnd',
		'ai10num',
		'ai10aip',
		'ai10rnd',
		'ai11num',
		'ai11aip',
		'ai11rnd',
		'ai12num',
		'ai12aip',
		'ai12rnd',
		'ai13num',
		'ai13aip',
		'ai13rnd',
		'ai14num',
		'ai14aip',
		'ai14rnd',
		'ai15num',
		'ai15aip',
		'ai15rnd',
		'ai16num',
		'ai16aip',
		'ai16rnd',
		'ai17num',
		'ai17aip',
		'ai17rnd',
		'ai18num',
		'ai18aip',
		'ai18rnd',
		'ai19num',
		'ai19aip',
		'ai19rnd',
		'ai20num',
		'ai20aip',
		'ai20rnd',
		'ai21num',
		'ai21aip',
		'ai21rnd',
		'ai22num',
		'ai22aip',
		'ai22rnd',
		'ai23num',
		'ai23aip',
		'ai23rnd',
		'ai24num',
		'ai24aip',
		'ai24rnd',
		'ai25num',
		'ai25aip',
		'ai25rnd',
		'ai26num',
		'ai26aip',
		'ai26rnd',
		'ai27num',
		'ai27aip',
		'ai27rnd',
		'ai28num',
		'ai28aip',
		'ai28rnd',
		'ai29num',
		'ai29aip',
		'ai29rnd',
		'ai30num',
		'ai30aip',
		'ai30rnd',
		'ai31num',
		'ai31aip',
		'ai31rnd',
		'ps0',
		'ps1',
		'ps2',
		'ps3',
		'ps4',
		'ps5',
		'ps6',
		'ps7',
		'ps8',
		'ps9',
		'gs',
		'mg',
		'exchange_point' // #PADC_DY#
	);

	/**
	 * 指定されたレベルに達するのに必要な経験値を返す.
	 * カードを指定レベルで取得するときの初期経験値としても使用する.
	 * 異常値(0以下のレベルや、最大値以上のレベル)が指定された場合は例外.
	 */
	public function getExpOnLevel($level) {
		if($level == 0 || $level > $this->mlv) {
			throw new PadException(RespCode::UNKNOWN_ERROR, "valid level is between 1 and mlv.");
		}
		// pow((レベル - 1) / 98), 係数「モンスターデータ(AB列)」) × 最大経験値「モンスターデータ(AA列)」
		return round(pow(($level-1)/98.0, $this->pexpb) * $this->pexpa);
	}

	/**
	 * 指定されたベースカードから、このカードに進化可能である場合に限りTRUEを返す.
	 */

	public function canBeEvolvedFrom($base_card, $index) {
		// #PADC# ----------begin----------
		$gupc_property_name = $this->getGupcPropertyName($index);
		if($base_card instanceof Card && isset($base_card->id) && $this->$gupc_property_name == $base_card->id) {
			return TRUE;
		}
		// #PADC# ----------end----------
		return FALSE;
	}

	/**
	 * このカードに進化させるために必要なカードIDのリストを返す.
	 */
	public function getRequiredCardIdsToEvolve() {
		$card_ids = array();
		if($this->gup1) { $card_ids[] = $this->gup1; }
		if($this->gup2) { $card_ids[] = $this->gup2; }
		if($this->gup3) { $card_ids[] = $this->gup3; }
		if($this->gup4) { $card_ids[] = $this->gup4; }
		if($this->gup5) { $card_ids[] = $this->gup5; }
		return $card_ids;
	}

	/**
	 * このカードが指定のカードと同じスキルを有しているときに限りTRUEを返す.
	 * スキル無し(null)同士の場合についてはFALSEを返す.
	 */
	public function hasSameSkill($card) {
		if($this->skill && $this->skill == $card->skill) {
			return TRUE;
		}
		return FALSE;
	}

	// #PADC# ----------begin----------

	public function getGupcIndex($base_card_id)
	{
		$ret = 1;
		for($i = 1; $i <= 5; $i++)
		{
			$gupc_property = $this->getGupcPropertyName($i);
			if($this->$gupc_property == $base_card_id)
			{
				$ret = $i;
			}
		}
		return $ret;
	}

	public function getGupcPropertyName($index)
	{
		return $index > 1 ? 'gupc'.$index : 'gupc';
	}
	public function getGupPieceExpPropertyName($index)
	{
		return $index > 1 ? 'gup_exp'.$index : 'gup_exp';
	}

	public function canUseEvolvePiece(Piece $piece)
	{
		$ret = FALSE;
		// MY : 通常進化と究極進化では使える欠片の判定が異なる。
		if($this->spup == 0)
		{
			if($piece->isTypeCompositeEvolution())
			{
				if($piece->isTypeMonster())
				{
					if($piece->id == $this->gup_piece_id)
					{
						$ret = TRUE;
					}
				}
				if($piece->isTypeEvolution())
				{
					// MY : attrは-1の場合無属性。
					if($piece->attr == -1 || $piece->attr == $this->attr)
					{
						$ret = TRUE;
					}
				}
			}
		}
		else
		{
			if($piece->isTypeUltimateEvolution())
			{
				if($piece->id == $this->gup_piece_id)
				{
					$ret = TRUE;
				}
			}
		}
		return $ret;
	}
	public function getRequiredEvolveExp($index = 1)
	{
		$gup_exp = $this->getGupPieceExpPropertyName($index);
		return $this->$gup_exp;
	}
	/**
	 * 進化経験値が加算できる欠片かどうかチェックする。
	 */
	public function canChargeEvolveExp($piece_id)
	{
		$pdo = Env::getDbConnectionForShareRead();
		$ret = false;
		$sql = "SELECT id FROM ".self::TABLE_NAME." WHERE gup_piece_id = ? AND ( ? IN(gupc,gupc2,gupc3,gupc4,gupc5))";
		$param = array($piece_id,$this->id);
		list($result, $stmt) = self::prepare_execute($sql, $param, $pdo);
		$find_result = $stmt->fetchAll(PDO::FETCH_CLASS,get_called_class());
		if(count($find_result) > 0)
		{
			$ret = true;
		}
		return $ret;
	}
	// #PADC# ----------end----------

	/**
	 * カードのステータス数値を返す
	 * @param unknown $lv
	 * @param unknown $mlv
	 * @param unknown $a
	 * @param unknown $b
	 * @param unknown $c
	 * @return number
	 */
	static public function getCardParam($lv, $mlv, $a, $b, $c) {
		if( $mlv <= 1 )
			return round($a);
		$flk = pow(($lv-1) / floatval($mlv-1), $c);
		$fad = ($b - $a) * $flk + $a;
		return round($fad);
	}

}

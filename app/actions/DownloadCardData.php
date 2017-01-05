<?php
/**
 * 31. カードデータダウンロード
 */
class DownloadCardData extends BaseAction {
	// http://pad.localhost/api.php?action=download_card_data&pid=1&sid=1
	const MEMCACHED_EXPIRE = 86400; // 24時間.
	const KEY_A = 0xd3955be7;
	const KEY_B = 0x7b53bc57;
	// #PADC#
	const MAIL_RESPONSE = FALSE;
	const ENCRYPT_RESPONSE = FALSE;
	public function action($params){
		$r = isset($params["r"]) ? $params["r"] : 0;
		if  ($r >= 2) {
			// ver4 (7.0～)
			$key = MasterCacheKey::getDownloadCardData(DownloadMasterData::ID_CARDS_VER4);
			$value = apc_fetch($key);
			if(FALSE === $value) {
				$value = DownloadMasterData::find(DownloadMasterData::ID_CARDS_VER4)->gzip_data;
				apc_store($key, $value, static::MEMCACHED_EXPIRE + DownloadMasterData::add_apc_expire());
			}
		} else {
			// 新バージョンver3（6.0～） 処理削除
		}
		return $value;
	}

	public static function getCheckSum($cards, $ver) {
		$sum = 0;
		foreach($cards as $card) {
			$card_sum = 0;
			$card_sum = ($card_sum + (int)$card->pmhpa * 1) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->pmhpb * 2) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->pmhpc * 3) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->patka * 4) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->patkb * 5) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->patkc * 6) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->preca * 7) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->precb * 8) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->precc * 9) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->pexpa * 10) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->pexpb * 11) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->skill * 12) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->lskill * 13) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->acyc * 14) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->emhpa * 15) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->emhpb * 16) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->emhpc * 17) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->eatka * 18) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->eatkb * 19) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->eatkc * 20) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->edefa * 21) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->edefb * 22) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->edefc * 23) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->edefd * 24) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->estu * 25) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->esturn2 * 26) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->aip0 * 27) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->aip1 * 28) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->aip2 * 29) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai0num * 30) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai0aip * 31) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai0rnd * 32) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai1num * 33) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai1aip * 34) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai1rnd * 35) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai2num * 36) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai2aip * 37) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai2rnd * 38) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai3num * 39) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai3aip * 40) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai3rnd * 41) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai4num * 42) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai4aip * 43) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai4rnd * 44) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai5num * 45) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai5aip * 46) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai5rnd * 47) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai6num * 48) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai6aip * 49) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai6rnd * 50) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai7num * 51) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai7aip * 52) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai7rnd * 53) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai8num * 54) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai8aip * 55) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai8rnd * 56) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai9num * 57) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai9aip * 58) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai9rnd * 59) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai10num * 60) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai10aip * 61) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai10rnd * 62) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai11num * 63) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai11aip * 64) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai11rnd * 65) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai12num * 66) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai12aip * 67) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai12rnd * 68) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai13num * 69) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai13aip * 70) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai13rnd * 71) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai14num * 72) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai14aip * 73) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai14rnd * 74) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai15num * 75) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai15aip * 76) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai15rnd * 77) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai16num * 78) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai16aip * 79) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai16rnd * 80) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai17num * 81) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai17aip * 82) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai17rnd * 83) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai18num * 84) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai18aip * 85) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai18rnd * 86) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai19num * 87) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai19aip * 88) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai19rnd * 89) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai20num * 90) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai20aip * 91) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai20rnd * 92) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai21num * 93) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai21aip * 94) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai21rnd * 95) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai22num * 96) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai22aip * 97) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai22rnd * 98) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai23num * 99) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai23aip * 100) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai23rnd * 101) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai24num * 102) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai24aip * 103) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai24rnd * 104) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai25num * 105) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai25aip * 106) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai25rnd * 107) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai26num * 108) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai26aip * 109) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai26rnd * 110) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai27num * 111) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai27aip * 112) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai27rnd * 113) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai28num * 114) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai28aip * 115) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai28rnd * 116) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai29num * 117) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai29aip * 118) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai29rnd * 119) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai30num * 120) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai30aip * 121) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai30rnd * 122) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai31num * 123) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai31aip * 124) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ai31rnd * 125) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->attr * 126) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->sattr * 127) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->mt * 128) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->mt2 * 129) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->rare * 130) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->cost * 131) & 0xFFFFFFFF;

			$card_sum = ($card_sum + (int)$card->aip3 * 132) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->aip4 * 133) & 0xFFFFFFFF;

			// #PADC# ----------begin----------
			// PADCリリース時点では覚醒スキルは存在しないためチェックサム対象から除外（270行目とセットで調整）

			$card_sum = ($card_sum + (int)$card->ps0 * 134) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ps1 * 135) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ps2 * 136) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ps3 * 137) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ps4 * 138) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ps5 * 139) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ps6 * 140) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ps7 * 141) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ps8 * 142) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->ps9 * 143) & 0xFFFFFFFF;
			// #PADC# ----------end----------

			$card_sum = ($card_sum + (int)$card->mg * 144) & 0xFFFFFFFF;

			// #PADC# ----------begin----------
			$card_sum = ($card_sum + (int)$card->padc_id * 145) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->gup_piece_id * 146) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->gup_exp * 147) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->sklmin * 148) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->sklmax * 149) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->pskl * 150) & 0xFFFFFFFF;

			$card_sum = ($card_sum + (int)$card->gupc2 * 151) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->gupc3 * 152) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->gupc4 * 153) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->gupc5 * 154) & 0xFFFFFFFF;

			$card_sum = ($card_sum + (int)$card->gup_exp2 * 155) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->gup_exp3 * 156) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->gup_exp4 * 157) & 0xFFFFFFFF;
			$card_sum = ($card_sum + (int)$card->gup_exp5 * 158) & 0xFFFFFFFF;

			$card_sum = ($card_sum + (int)$card->gup_final * 159) & 0xFFFFFFFF;
			// #PADC# ----------end----------

			$sum = ($sum + $card_sum * (int)$card->id) & 0xFFFFFFFF;
		}
		return (($sum ^ static::KEY_A) + static::KEY_B) & 0xFFFFFFFF;

	}

	public static function arrangeColumns($cards) {
		$mapper = array();
		foreach ($cards as $card) {
			$c = array();
			$c[] = $card->name;
			$c[] = (int)$card->attr;
			$c[] = is_null($card->sattr) ? -1 : (int)$card->sattr;
			$c[] = (int)$card->spup;
			$c[] = (int)$card->mt;
			$c[] = is_null($card->mt2) ? -1 : (int)$card->mt2;
			$c[] = (int)$card->rare;
			$c[] = (int)$card->cost;
			$c[] = (int)$card->size;
			$c[] = (int)$card->mlv;
			$c[] = (int)$card->mcost;
			$c[] = (int)$card->ccost;
			$c[] = (int)$card->scost;
			$c[] = (int)$card->pmhpa;
			$c[] = (int)$card->pmhpb;
			$c[] = (float)$card->pmhpc;
			$c[] = (int)$card->patka;
			$c[] = (int)$card->patkb;
			$c[] = (float)$card->patkc;
			$c[] = (int)$card->preca;
			$c[] = (int)$card->precb;
			$c[] = (float)$card->precc;
			$c[] = (int)$card->pexpa;
			$c[] = (float)$card->pexpb;
			$c[] = (int)$card->skill;
			$c[] = (int)$card->lskill;
			$c[] = (int)$card->acyc;
			$c[] = (int)$card->emhpa;
			$c[] = (int)$card->emhpb;
			$c[] = (float)$card->emhpc;
			$c[] = (int)$card->eatka;
			$c[] = (int)$card->eatkb;
			$c[] = (float)$card->eatkc;
			$c[] = (int)$card->edefa;
			$c[] = (int)$card->edefb;
			$c[] = (float)$card->edefc;
			$c[] = (int)$card->edefd;
			$c[] = (int)$card->coink;
			$c[] = (int)$card->expk;
			$c[] = (int)$card->gupc;
			$c[] = (int)$card->gup1;
			$c[] = (int)$card->gup2;
			$c[] = (int)$card->gup3;
			$c[] = (int)$card->gup4;
			$c[] = (int)$card->gup5;
			$c[] = (int)$card->dev1;
			$c[] = (int)$card->dev2;
			$c[] = (int)$card->dev3;
			$c[] = (int)$card->dev4;
			$c[] = (int)$card->dev5;
			$c[] = (int)$card->estu;
			$c[] = (int)$card->aip0;
			$c[] = (int)$card->aip1;
			$c[] = (int)$card->aip2;
			$c[] = (int)$card->aip3;
			$c[] = (int)$card->aip4;

			// AIパラメータ
			$aic = 0;
			$ai = array();
			for ($i=31;$i>=0;$i--) {
				$num = "ai".$i."num";
				$aip = "ai".$i."aip";
				$rnd = "ai".$i."rnd";
				if ($card->$num != 0 || $card->$aip != 0 || $card->$rnd != 0 || $aic > 0) {
					$aic++;
					array_unshift($ai, (int)$card->$num, (int)$card->$aip, (int)$card->$rnd);
				}
			}
			$c[] = (int)$aic;
			$c = array_merge($c, $ai);

			// 覚醒スキル
			$psc = 0;
			$ps = array();
			// #PADC# ----------begin----------
			// PADCリリース時点では覚醒スキルは存在しないため0で固定としておく（167行目とセットで調整）
			for ($i=9;$i>=0;$i--) {
				$pskill = "ps".$i;
				if ($card->$pskill != 0 || $psc > 0) {
					$psc++;
					array_unshift($ps, (int)$card->$pskill);
				}
			}
			// #PADC# ----------end----------

			$c[] = $psc;
			$c = array_merge($c, $ps);
			$c[] = (int)$card->mg;
			$c[] = (int)$card->gs;

			// #PADC# ----------begin----------
			$c[] = (int)$card->padc_id;
			$c[] = (int)$card->gup_piece_id;
			$c[] = (int)$card->gup_exp;
			$c[] = (int)$card->sklmin;
			$c[] = (int)$card->sklmax;
			$c[] = (int)$card->pskl;
			$c[] = (int)$card->id;

			//----------
			// DROPする欠片種類数
			// DROPする欠片ID
			//----------
			$_dropCardCnt	= 0;
			$_tmpDropCards	= array();
			
			// 設定されているDROP情報をチェック
			for($i=1;$i<=4;$i++)
			{
				$_drop_card_id	= sprintf("%s%d",'drop_card_id',$i);
				$_drop_prob		= sprintf("%s%d",'drop_prob',$i);

				// DROP対象が設定されていて、入手率が1以上の場合セット
				if($card->$_drop_card_id && $card->$_drop_prob > 0)
				{
					$_dropCardCnt++;
					$_tmpDropCards[] = $card->$_drop_card_id;
				}
			}

			// 設定されている分を追加
			$c[] = $_dropCardCnt;
			for($i=0;$i<count($_tmpDropCards);$i++)
			{
				$c[] = (int)$_tmpDropCards[$i];
			}

			$c[] = (int)$card->gupc2;
			$c[] = (int)$card->gupc3;
			$c[] = (int)$card->gupc4;
			$c[] = (int)$card->gupc5;

			$c[] = (int)$card->gup_exp2;
			$c[] = (int)$card->gup_exp3;
			$c[] = (int)$card->gup_exp4;
			$c[] = (int)$card->gup_exp5;

			$c[] = (int)$card->gup_final;
			// #PADC# ----------end----------
			// #PADC_DY# ----------begin----------
			$c[] = (int)$card->exchange_point; // 交换点数
			// 究极进化材料和数量
			$c[] = (int)$card->ult_piece_id1;
			$c[] = (int)$card->ult_piece_num1;
			$c[] = (int)$card->ult_piece_id2;
			$c[] = (int)$card->ult_piece_num2;
			$c[] = (int)$card->ult_piece_id3;
			$c[] = (int)$card->ult_piece_num3;
			$c[] = (int)$card->ult_piece_id4;
			$c[] = (int)$card->ult_piece_num4;
			$c[] = (int)$card->ult_piece_id5;
			$c[] = (int)$card->ult_piece_num5;
			// #PADC_DY# -----------end-----------

			$mapper[] = $c;
		}
		return $mapper;
	}

}

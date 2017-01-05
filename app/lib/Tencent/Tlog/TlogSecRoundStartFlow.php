<?php
class TlogSecRoundStartFlow extends TlogBase {
	const EVENT = 'SecRoundStartFlow';
	protected static $columns = array (
			'event',
			'GameSvrId',
			'dtEventTime',
			'GameAppID',
			'OpenID',
			'PlatID',
			'AreaID',
			'ZoneID',
			'BattleID',
			'ClientStartTime',
			'UserName',
			'SvrUserMoney1',
			'SvrUserMoney2',
			'UserLevel',
			'UserVipLevel',
			'UserMoney3',
			'SvrRoundType',
			'SvrMapid',
			'SvrTopMapid',
			'SvrUserTroopNum',
			'SvrUserPower',
			'SvrUserHP',
			'SvrUserAttack1',
			'SvrUserAttack2',
			'SvrUserAttack3',
			'SvrUserAttack4',
			'SvrUserAttack5',
			'SvrUserResilience',
			'SvrUserCardNum1',
			'SvrUserCardLevel1',
			'SvrUserCardInfoA1',
			'SvrUserCardInfoB1',
			'SvrUserCardInfoC1',
			'SvrUserCardInfoD1',
			'SvrUserCardAtt1',
			'SvrUserCardHP1',
			'SvrUserCardResilience1',
			'UserCardSkill1',
			'SvrUserCardNum2',
			'SvrUserCardLevel2',
			'SvrUserCardInfoA2',
			'SvrUserCardInfoB2',
			'SvrUserCardInfoC2',
			'SvrUserCardInfoD2',
			'SvrUserCardAtt2',
			'SvrUserCardHP2',
			'SvrUserCardResilience2',
			'UserCardSkill2',
			'SvrUserCardNum3',
			'SvrUserCardLevel3',
			'SvrUserCardInfoA3',
			'SvrUserCardInfoB3',
			'SvrUserCardInfoC3',
			'SvrUserCardInfoD3',
			'SvrUserCardAtt3',
			'SvrUserCardHP3',
			'SvrUserCardResilience3',
			'UserCardSkill3',
			'SvrUserCardNum4',
			'SvrUserCardLevel4',
			'SvrUserCardInfoA4',
			'SvrUserCardInfoB4',
			'SvrUserCardInfoC4',
			'SvrUserCardInfoD4',
			'SvrUserCardAtt4',
			'SvrUserCardHP4',
			'SvrUserCardResilience4',
			'UserCardSkill4',
			'SvrUserCardNum5',
			'SvrUserCardLevel5',
			'SvrUserCardInfoA5',
			'SvrUserCardInfoB5',
			'SvrUserCardInfoC5',
			'SvrUserCardInfoD5',
			'SvrUserCardAtt5',
			'SvrUserCardHP5',
			'SvrUserCardResilience5',
			'UserCardSkill5',
			'SvrUserCardNum6',
			'SvrUserCardLevel6',
			'SvrUserCardInfoA6',
			'SvrUserCardInfoB6',
			'SvrUserCardInfoC6',
			'SvrUserCardInfoD6',
			'SvrUserCardAtt6',
			'SvrUserCardHP6',
			'SvrUserCardResilience6',
			'UserCardSkill6',
			'SvrMonsterBatch',
			'SvrMonsterNum',
			'SvrMonsterHPMax',
			'SvrMonsterHPMin',
			'SvrMonsterHPTotal',
			'SvrMonsterAttMax',
			'SvrMonsterAttMin',
			'SvrMonsterAttCDMax',
			'SvrMonsterAttCDMin',
			'SvrMonsterLevelMax',
			'SvrMonsterLevelMin',
			'SvrBossNum',
			'SvrBossHPMax',
			'SvrBossHPMin',
			'SvrBossHPTotal',
			'SvrBossAttMax',
			'SvrBossAttMin',
			'SvrBossAttCDMax',
			'SvrBossAttCDMin',
			'SvrBossLevelMax',
			'SvrBossLevelMin',
			'SvrBossSkillNum',
			'SvrMonsterSkillDemageMax',
			'SvrMonsterSkillDemageMin',
			'TeammateOpenID',
			'SrvUserCard1RareClass',
			'SrvUserCard2RareClass',
			'SrvUserCard3RareClass',
			'SrvUserCard4RareClass',
			'SrvUserCard5RareClass',
			'SrvUserCard6RareClass',
			'SrvTeamCardRareClass',
			'SrvUserCard1AwakenSkill',
			'SrvUserCard2AwakenSkill',
			'SrvUserCard3AwakenSkill',
			'SrvUserCard4AwakenSkill',
			'SrvUserCard5AwakenSkill',
			'SrvUserCard6AwakenSkill',
			'SrvRoundStage',
	);
	public static function generateMessage($params) {
		$timeBattleID = $params ['BattleID'];
		$datas = array (
				static::EVENT,
				static::getGameSvrId (), // GameSvrId
				strftime ( '%Y-%m-%d %H:%M:%S', $timeBattleID ), // dtEventTime
				$params ['appid'],
				$params ['OpenID'],
				$params ['PlatID'],
				$params ['AreaID'],
				static::getZoneId (),
				$timeBattleID, // BattleID
				$params ['ClientStartTime'], // -from app
				$params ['UserName'],
				$params ['SvrUserMoney1'], // coin
				$params ['SvrUserMoney2'], // gold
				$params ['UserLevel'],
				$params ['UserVipLevel'],
				$params ['UserMoney3'], // stamina
				$params ['SvrRoundType'], // 1.normal 2.spec 3.time limit 4.ranking
				$params ['SvrMapid'], // floorid
				$params ['SvrTopMapid'], // normal dung count
				$params ['SvrUserTroopNum'], // deck id
				$params ['SvrUserPower'], // total power -from app
				$params ['SvrUserHP'], // team total hp
				$params ['SvrUserAttack1'], // team total attack 火
				$params ['SvrUserAttack2'], // team total attack 水
				$params ['SvrUserAttack3'], // team total attack 木
				$params ['SvrUserAttack4'], // team total attack 光
				$params ['SvrUserAttack5'], // team total attack 闇
				$params ['SvrUserResilience'], // team total recovery
				/* card1 leader */
				$params ['SvrUserCardNum1'], // card1 id
				$params ['SvrUserCardLevel1'], // card1 level
				$params ['SvrUserCardInfoA1'], // card1 type1
				$params ['SvrUserCardInfoB1'], // card1 type2
				$params ['SvrUserCardInfoC1'], // card1 attack type 1火2水3木4光5闇
				$params ['SvrUserCardInfoD1'], // card1 attack type2 1火2水3木4光5闇
				$params ['SvrUserCardAtt1'], // card1 attack
				$params ['SvrUserCardHP1'], // card1 HP
				$params ['SvrUserCardResilience1'], // card1 recovery
				$params ['UserCardSkill1'], // card1 skill: "|skill id,level,cd,leader skill id|"
				/* card2 */
				$params ['SvrUserCardNum2'], // card2 id
				$params ['SvrUserCardLevel2'], // card2 level
				$params ['SvrUserCardInfoA2'], // card2 type1
				$params ['SvrUserCardInfoB2'], // card2 type2
				$params ['SvrUserCardInfoC2'], // card2 attack type 1火2水3木4光5闇
				$params ['SvrUserCardInfoD2'], // card2 attack type2 1火2水3木4光5闇
				$params ['SvrUserCardAtt2'], // card2 attack
				$params ['SvrUserCardHP2'], // card2 HP
				$params ['SvrUserCardResilience2'], // card2 recovery
				$params ['UserCardSkill2'], // card2 skill: "|skill id,level,cd,leader skill id|"
				/* card3 */
				$params ['SvrUserCardNum3'], // card3 id
				$params ['SvrUserCardLevel3'], // card3 level
				$params ['SvrUserCardInfoA3'], // card3 type1
				$params ['SvrUserCardInfoB3'], // card3 type2
				$params ['SvrUserCardInfoC3'], // card3 attack type 1火2水3木4光5闇
				$params ['SvrUserCardInfoD3'], // card3 attack type2 1火2水3木4光5闇
				$params ['SvrUserCardAtt3'], // card3 attack
				$params ['SvrUserCardHP3'], // card3 HP
				$params ['SvrUserCardResilience3'], // card3 recovery
				$params ['UserCardSkill3'], // card3 skill: "|skill id,level,cd,leader skill id|"
				/* card4 */
				$params ['SvrUserCardNum4'], // card4 id
				$params ['SvrUserCardLevel4'], // card4 level
				$params ['SvrUserCardInfoA4'], // card4 type1
				$params ['SvrUserCardInfoB4'], // card4 type2
				$params ['SvrUserCardInfoC4'], // card4 attack type 1火2水3木4光5闇
				$params ['SvrUserCardInfoD4'], // card4 attack type2 1火2水3木4光5闇
				$params ['SvrUserCardAtt4'], // card4 attack
				$params ['SvrUserCardHP4'], // card4 HP
				$params ['SvrUserCardResilience4'], // card4 recovery
				$params ['UserCardSkill4'], // card4 skill: "|skill id,level,cd,leader skill id|"
				/* card5 */
				$params ['SvrUserCardNum5'], // card5 id
				$params ['SvrUserCardLevel5'], // card5 level
				$params ['SvrUserCardInfoA5'], // card5 type1
				$params ['SvrUserCardInfoB5'], // card5 type2
				$params ['SvrUserCardInfoC5'], // card5 attack type 1火2水3木4光5闇
				$params ['SvrUserCardInfoD5'], // card5 attack type2 1火2水3木4光5闇
				$params ['SvrUserCardAtt5'], // card5 attack
				$params ['SvrUserCardHP5'], // card5 HP
				$params ['SvrUserCardResilience5'], // card5 recovery
				$params ['UserCardSkill5'], // card5 skill: "|skill id,level,cd,leader skill id|"
				/* card6 helper */
				$params ['SvrUserCardNum6'], // card6 id
				$params ['SvrUserCardLevel6'], // card6 level
				$params ['SvrUserCardInfoA6'], // card6 type1
				$params ['SvrUserCardInfoB6'], // card6 type2
				$params ['SvrUserCardInfoC6'], // card6 attack type 1火2水3木4光5闇
				$params ['SvrUserCardInfoD6'], // card6 attack type2 1火2水3木4光5闇
				$params ['SvrUserCardAtt6'], // card6 attack
				$params ['SvrUserCardHP6'], // card6 HP
				$params ['SvrUserCardResilience6'], // card6 recovery
				$params ['UserCardSkill6'], // card6 skill: "|skill id,level,cd,leader skill id|"
				/* enemy */
				$params ['SvrMonsterBatch'], // waves count
				$params ['SvrMonsterNum'], // total monster count (include boss)
				$params ['SvrMonsterHPMax'], // single monster hp max
				$params ['SvrMonsterHPMin'], // single monster hp min
				$params ['SvrMonsterHPTotal'], // monster total hp (no boss)
				$params ['SvrMonsterAttMax'], // single monster attack max
				$params ['SvrMonsterAttMin'], // single monster attack min
				$params ['SvrMonsterAttCDMax'], // single monster cd max
				$params ['SvrMonsterAttCDMin'], // single monster cd min
				$params ['SvrMonsterLevelMax'], // single monster level max
				$params ['SvrMonsterLevelMin'], // single monster level min
				/* boss */
				$params ['SvrBossNum'], // boss count
				$params ['SvrBossHPMax'], // single boss hp max
				$params ['SvrBossHPMin'], // single boss hp min
				$params ['SvrBossHPTotal'], // boss total hp
				$params ['SvrBossAttMax'], // single boss attack max
				$params ['SvrBossAttMin'], // single boss attack min
				$params ['SvrBossAttCDMax'], // single boss cd max
				$params ['SvrBossAttCDMin'], // single boss cd min
				$params ['SvrBossLevelMax'], // single boss level max
				$params ['SvrBossLevelMin'], // single boss level min
				$params ['SvrBossSkillNum'], // boss skill: "|boss1, boss2, boss3|"
				$params ['SvrMonsterSkillDemageMax'], // boss skill demage max
				$params ['SvrMonsterSkillDemageMin'], // boss skill demage min
				/* helper openid */
				$params ['TeammateOpenID'],

				$params['SrvUserCard1RareClass'],
				$params['SrvUserCard2RareClass'],
				$params['SrvUserCard3RareClass'],
				$params['SrvUserCard4RareClass'],
				$params['SrvUserCard5RareClass'],
				$params['SrvUserCard6RareClass'],
				$params['SrvTeamCardRareClass'],
				$params['SrvUserCard1AwakenSkill'],
				$params['SrvUserCard2AwakenSkill'],
				$params['SrvUserCard3AwakenSkill'],
				$params['SrvUserCard4AwakenSkill'],
				$params['SrvUserCard5AwakenSkill'],
				$params['SrvUserCard6AwakenSkill'],
				$params['SrvRoundStage'],
		);
		return static::generateMessageFromArray ( $datas );
	}
}

<?php
/**
 * 敵スキル.
 */

class EnemySkill extends BaseMasterModel {
  const TABLE_NAME = "enemy_skills";
  const VER_KEY_GROUP = "e_skl";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  protected static $columns = array(
    'id',
    'name',
    'help',
    'type',
    'skp1',
    'skp2',
    'skp3',
    'skp4',
    'skp5',
    'skp6',
    'skp7',
    'skp8',
    'ratio',
    'aip0',
    'aip1',
    'aip2',
    'aip3',
    'aip4',
  );
  
  // ダメージが発生する敵スキルID
  const ENEMY_SKILL_REPEAT_ATK = 15;	//	15:@1～@2回連続攻撃
  const ENEMY_SKILL_47 = 47;	//	47:@1%の確率で、@2%の攻撃力で先制攻撃
  const ENEMY_SKILL_48 = 48;	//	48:@1%で攻撃しつつ、@2色のドロップをお邪魔ドロップに差し替え
  const ENEMY_SKILL_50 = 50;	//	50:プレイヤーのHPが@1%になるダメージ
  const ENEMY_SKILL_62 = 62;	//	62:@1%で攻撃しつつ、盤面くらやみ
  const ENEMY_SKILL_63 = 63;	//	63:@1%で攻撃しつつ、@2～@3ターンの間、@4(0:指定無し、1:リーダー、2:助っ人、3:リーダーと助っ人、4:サブ)@5体（@4が0、3、4の時に効果）
  const ENEMY_SKILL_64 = 64;	//	64:@1%で攻撃しつつ、@2個の毒ドロップを作成　@3に1で回復を除外 @4 が１の時猛毒

}

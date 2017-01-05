<?php
/**
 * 51. スキルデータダウンロード
 */
class DownloadEnemySkillData extends BaseAction {
  // http://pad.localhost/api.php?action=download_enemy_skill_data&pid=1&sid=1
  const MEMCACHED_EXPIRE = 86400; // 24時間.
  // #PADC#
  const MAIL_RESPONSE = FALSE;
  const ENCRYPT_RESPONSE = FALSE;
  public function action($params){
    $key = MasterCacheKey::getDownloadEnemySkillData();
    $value = apc_fetch($key);
    if(FALSE === $value) {
      $value = DownloadMasterData::find(DownloadMasterData::ID_ENEMY_SKILLS)->gzip_data;
      apc_store($key, $value, static::MEMCACHED_EXPIRE + DownloadMasterData::add_apc_expire());
    }
    return $value;
  }

  /**
   * Skillのリストを調整し、APIクライアントが要求するキー名のデータにマッピングする.
   */
  public static function arrangeColumns($enemy_skills) {
    // マッパー関数. TODO チューニング..
    $mapper = function($enemy_skill) {
//      unset($enemy_skill->id);
      $enemy_skill->id = (int)$enemy_skill->id;
      unset($enemy_skill->created_at);
      unset($enemy_skill->updated_at);
      $enemy_skill->type = (int)$enemy_skill->type;
      $enemy_skill->skp1 = (int)$enemy_skill->skp1;
      $enemy_skill->skp2 = (int)$enemy_skill->skp2;
      $enemy_skill->skp3 = (int)$enemy_skill->skp3;
      $enemy_skill->skp4 = (int)$enemy_skill->skp4;
      $enemy_skill->skp5 = (int)$enemy_skill->skp5;
      $enemy_skill->skp6 = (int)$enemy_skill->skp6;
      $enemy_skill->skp7 = (int)$enemy_skill->skp7;
      $enemy_skill->skp8 = (int)$enemy_skill->skp8;
      $enemy_skill->ratio = (int)$enemy_skill->ratio;
      $enemy_skill->aip0 = (int)$enemy_skill->aip0;
      $enemy_skill->aip1 = (int)$enemy_skill->aip1;
      $enemy_skill->aip2 = (int)$enemy_skill->aip2;
      $enemy_skill->aip3 = (int)$enemy_skill->aip3;
      $enemy_skill->aip4 = (int)$enemy_skill->aip4;
      return $enemy_skill;
    };
    return array_map($mapper, $enemy_skills);
  }

}

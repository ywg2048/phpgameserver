<?php
/**
 * 34. スキルデータダウンロード
 */
class DownloadSkillData extends BaseAction {
  // http://pad.localhost/api.php?action=download_skill_data&pid=1&sid=1
  const MEMCACHED_EXPIRE = 86400; // 24時間.
  const KEY_A = 0x67633ede;
  const KEY_B = 0xabe5b797;

  // #PADC#
  const MAIL_RESPONSE = FALSE;
  const ENCRYPT_RESPONSE = FALSE;
  public function action($params){
    $key = MasterCacheKey::getDownloadSkillData();
    $value = apc_fetch($key);
    if(FALSE === $value) {
      $value = DownloadMasterData::find(DownloadMasterData::ID_SKILLS)->gzip_data;
      apc_store($key, $value, static::MEMCACHED_EXPIRE + DownloadMasterData::add_apc_expire());
    }
    return $value;
  }

  public static function getCheckSum($skills) {
    $sum = 0;
    foreach($skills as $skill) {
      $skill_sum = 0;
      $skill_sum = ($skill_sum + (int)$skill->sktp * 1) & 0xFFFFFFFF;
      $skill_sum = ($skill_sum + (int)$skill->skp1 * 2) & 0xFFFFFFFF;
      $skill_sum = ($skill_sum + (int)$skill->skp2 * 3) & 0xFFFFFFFF;
      $skill_sum = ($skill_sum + (int)$skill->skp3 * 4) & 0xFFFFFFFF;
      $skill_sum = ($skill_sum + (int)$skill->skp4 * 5) & 0xFFFFFFFF;
      $skill_sum = ($skill_sum + (int)$skill->skp5 * 6) & 0xFFFFFFFF;
      $skill_sum = ($skill_sum + (int)$skill->skp6 * 7) & 0xFFFFFFFF;
      $skill_sum = ($skill_sum + (int)$skill->skp7 * 8) & 0xFFFFFFFF;
      $skill_sum = ($skill_sum + (int)$skill->skp8 * 9) & 0xFFFFFFFF;
      $skill_sum = ($skill_sum + (int)$skill->lcap * 10) & 0xFFFFFFFF;
      $skill_sum = ($skill_sum + (int)$skill->ctbs * 11) & 0xFFFFFFFF;
      $skill_sum = ($skill_sum + (int)$skill->ctel * 12) & 0xFFFFFFFF;
      $sum = ($sum + $skill_sum * (int)$skill->id) & 0xFFFFFFFF;
    }
    return (($sum ^ static::KEY_A) + static::KEY_B) & 0xFFFFFFFF;
  }

  /**
   * Skillのリストを調整し、APIクライアントが要求するキー名のデータにマッピングする.
   */
  public static function arrangeColumns($skills) {
    // マッパー関数. TODO チューニング..
    $mapper = function($skill) {
//      unset($skill->id);
      $skill->id = (int)$skill->id;
      unset($skill->created_at);
      unset($skill->updated_at);
      $skill->sktp = (int)$skill->sktp;
      $skill->skp1 = (int)$skill->skp1;
      $skill->skp2 = (int)$skill->skp2;
      $skill->skp3 = (int)$skill->skp3;
      $skill->skp4 = (int)$skill->skp4;
      $skill->skp5 = (int)$skill->skp5;
      $skill->skp6 = (int)$skill->skp6;
      $skill->skp7 = (int)$skill->skp7;
      $skill->skp8 = (int)$skill->skp8;
      $skill->lcap = (int)$skill->lcap;
      $skill->ctbs = (int)$skill->ctbs;
      $skill->ctel = (int)$skill->ctel;
      if($skill->skp1 == 0) unset($skill->skp1);
      if($skill->skp2 == 0) unset($skill->skp2);
      if($skill->skp3 == 0) unset($skill->skp3);
      if($skill->skp4 == 0) unset($skill->skp4);
      if($skill->skp5 == 0) unset($skill->skp5);
      if($skill->skp6 == 0) unset($skill->skp6);
      if($skill->skp7 == 0) unset($skill->skp7);
      if($skill->skp8 == 0) unset($skill->skp8);
      
      return $skill;
    };
    return array_map($mapper, $skills);
  }

}

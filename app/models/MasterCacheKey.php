<?php
/**
 マスターのバージョンが関係するキャッシュキー.
 **/
class MasterCacheKey {

  public static function getVersionKey($id) {
    return Env::MEMCACHE_PREFIX . 'version_' . $id;
  }

  public static function getCastedGameConstantKey($id) {
    $ver_key = "_" . Version::getVersion(GameConstant::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'casted_all_game_constant_' . $id;
  }

  public static function getDownloadDungeonDataOld() {
    $ver_key = "_" . Version::getVersion(Dungeon::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'download_dungeon_data';
  }

  public static function getDownloadDungeonData() {
    $ver_key = "_" . Version::getVersion(Dungeon::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'download_dungeon_data2';
  }

  public static function getDownloadCardData($ver) {
    $ver_key = "_" . Version::getVersion(Card::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'download_card_data_'.$ver;
  }

  public static function getDownloadSkillData() {
    $ver_key = "_" . Version::getVersion(Skill::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'download_skill_data';
  }

  public static function getDownloadDungeonSaleData() {
    $ver_key = "_" . Version::getVersion(DungeonSale::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'download_dungeon_sale_data';
  }

  public static function getDownloadEnemySkillData() {
    $ver_key = "_" . Version::getVersion(EnemySkill::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'download_enemy_skill_data';
  }

  public static function getDungeonFloorsPrevKey() {
    $ver_key = "_" . Version::getVersion(DungeonFloor::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'dungeon_floors_prev';
  }

  public static function getWDungeonFloorsPrevKey() {
    $ver_key = "_" . Version::getVersion(WDungeonFloor::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'w_dungeon_floors_prev';
  }

  public static function getDownloadWDungeonData() {
    $ver_key = "_" . Version::getVersion(WDungeon::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'download_w_dungeon_data';
  }

  public static function getDownloadWAvatarItemData() {
    $ver_key = "_" . Version::getVersion(WAvatarItem::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'download_w_avatar_item_data';
  }

  public static function getGachaPrizeSumProbKey($gacha_id){
    $ver_key = "_" . Version::getVersion(GachaPrize::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'gachaPrizeSumProb_' . $gacha_id;
  }

  public static function getWGachaPrizeSumProbKey($gacha_id){
    $ver_key = "_" . Version::getVersion(WGacha::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'w_gachaPrizeSumProb_' . $gacha_id;
  }

  public static function getAllLimitedBonusWithMargin() {
    $ver_key = "_" . Version::getVersion(LimitedBonus::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'allActiveBonusWithMargin';
  }

  public static function getAllLimitedBonusGroupWithMargin() {
    $ver_key = "_" . Version::getVersion(LimitedBonusGroup::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'allActiveBonusWithMarginGroup';
  }

  public static function getAllLimitedBonusOpenDungeonWithMargin() {
    $ver_key = "_" . Version::getVersion(LimitedBonusOpenDungeon::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'allActiveBonusOpenDungeonWithMargin';
  }

  public static function getAllLimitedBonusDungeonBonusWithMargin() {
    $ver_key = "_" . Version::getVersion(LimitedBonusDungeonBonus::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'allActiveBonusDungeonBonusWithMargin';
  }

  public static function getTodayAllLimitedBonusesKey($wday) {
    $ver_key = "_" . Version::getVersion(LimitedBonus::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'today_all_limited_bonuses_' . $wday;
  }

  public static function getDungeonOpenLimitedBonusesKey() {
    $ver_key = "_" . Version::getVersion(LimitedBonus::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'dungeon_open_limited_bonuses';
  }

  public static function getDungeonOpenLimitedBonusesGroupKey() {
    $ver_key = "_" . Version::getVersion(LimitedBonusGroup::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'dungeon_open_limited_bonuses_group';
  }

  public static function getDungeonOpenLimitedBonusesOpenDungeonKey() {
    $ver_key = "_" . Version::getVersion(LimitedBonusOpenDungeon::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'dungeon_open_limited_bonuses_open_dungeon';
  }

  public static function getDungeonBonusLimitedBonusesDungeonBonusKey() {
    $ver_key = "_" . Version::getVersion(LimitedBonusDungeonBonus::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'dungeon_bonus_limited_bonuses_dungeon_bonus';
  }

  public static function getAllCampaignSerialCode() {
    $ver_key = "_" . Version::getVersion(CampaignSerialCode::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'all_campaign_serial_code';
  }

  public static function getAllCampaignSerialItem() {
    $ver_key = "_" . Version::getVersion(CampaignSerialItem::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'all_campaign_serial_item';
  }

  public static function getEnableAllUserBonuses() {
    $ver_key = "_" . Version::getVersion(AllUserBonus::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'enable_all_user_bonuses';
  }

  // #PADC# ----------begin----------
  public static function getDownloadPieceData() {
  	$ver_key = "_" . Version::getVersion(Piece::VER_KEY_GROUP);
  	return Env::MEMCACHE_PREFIX . $ver_key . 'download_piece_data';
  }
  public static function getDownloadSceneData() {
  	$ver_key = "_" . Version::getVersion(Scene::VER_KEY_GROUP);
  	return Env::MEMCACHE_PREFIX . $ver_key . 'download_scene_data';
  }
  //use padc_vip_bonuses table and padc_vip_bench to generate a gzip data,and store in memcache
  public static function getDownloadVipData(){
  	$ver_key = "_" . Version::getVersion(VipBonus::VER_KEY_GROUP);
  	return Env::MEMCACHE_PREFIX . $ver_key . 'download_vip_data';
  }

  public static function getDownloadRankingData()
  {
    $ver_key = '_' . Version::getVersion(LimitedRanking::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'download_ranking_data';
  }

  public static function getDownloadRankingDungeonData()
  {
    $ver_key = '' . Version::getVersion(RankingDungeon::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'download_ranking_dungeon_data';
  }

  //get login bonuses data for login total count bonuses
  public static function getDownloadLoginBonusData(){
  	$ver_key = "_" . Version::getVersion(LoginTotalCountBonus::VER_KEY_GROUP);
  	return Env::MEMCACHE_PREFIX . $ver_key . 'download_login_bonus_data';
  }
  
  public static function getDownloadQqVipData(){
  	$ver_key = "_" . Version::getVersion(QqVipBonus::VER_KEY_GROUP);
  	return Env::MEMCACHE_PREFIX . $ver_key . 'download_qq_vip_data';
  }
  
  public static function getDownloadMissionData() {
    $ver_key = "_" . Version::getVersion(Mission::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'download_mission_data';
  }

  public static function getExchangeLineup() {
    $ver_key = "_" . Version::getVersion(ExchangeLineUp::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'exchange_lineup';
  }

  public static function getExchangeItems() {
    $ver_key = "_" . Version::getVersion(ExchangeItem::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'exchange_items';
  }
  
  // #PADC# ----------end----------

  // #PADC_DY# ----------begin----------
  // get roadmap unlock config info
  public static function getDownloadRoadmapData() {
    $ver_key = "_" . Version::getVersion(Roadmap::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'download_roadmap_data';
  }
  
  // 活动配置
  public static function getDownloadActivityData() {
    $ver_key = "_" . Version::getVersion(Activity::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'download_activity_data';
  }

  // gacha lineup
  public static function getDownloadGachaLineupData() {
    $ver_key = "_" . Version::getVersion(RecommendDeck::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'get_gacha_lineup';
  }

  public static function getSumProbByProductType($type){
    $ver_key = "_" . Version::getVersion(ExchangeProduct::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'productSumProb_' . $type;
  }

  public static function getSumProbByMagicStoneProductType($type){
    $ver_key = "_" . Version::getVersion(ExchangeMagicStoneProduct::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'magicstoneproductSumProb_' . $type;
  }
  // 觉醒技能消耗
  public static function getPassiveSkillData(){
    $ver_key = "_" . Version::getVersion(PassiveSkill::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . 'passive_skill';
  }

  //##新手嘉年华
  public static function getCarnivalData(){
    $ver_key = "_" . Version::getVersion(CarnivalPrize::VER_KEY_GROUP);
    return Env::MEMCACHE_PREFIX . $ver_key . "greenhorn_carnival";
  }
  // #PADC_DY# ----------end----------
}

?>

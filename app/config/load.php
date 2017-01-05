<?php
// ディレクトリ定数.
define('ROOT_DIR',    realpath(__DIR__ . '/../..'));
define('APP_DIR',     realpath(ROOT_DIR . '/app'));
define('CONFIG_DIR',  realpath(ROOT_DIR . '/app/config'));
define('LIB_DIR',     realpath(ROOT_DIR . '/app/lib'));
define('MODEL_DIR',   realpath(ROOT_DIR . '/app/models'));
define('ACTION_DIR',   realpath(ROOT_DIR . '/app/actions'));
define('FILTER_DIR',  realpath(ROOT_DIR . '/app/filters'));
define('VIEW_DIR',  realpath(ROOT_DIR . '/app/views'));
define('ALL_CONFIG_DIR',  realpath(ROOT_DIR . '/global'));

require_once("application.php");

set_include_path(get_include_path() . PATH_SEPARATOR . LIB_DIR);

// AppStore 
set_include_path(get_include_path() . PATH_SEPARATOR . LIB_DIR. '/AppStore');
require_once("AppStoreUtil.php");

// GooglePlay
set_include_path(get_include_path() . PATH_SEPARATOR . LIB_DIR. '/GooglePlay');
require_once("GooglePlayUtil.php");

// Amazon
set_include_path(get_include_path() . PATH_SEPARATOR . LIB_DIR. '/Amazon');
require_once("AmazonUtil.php");

// Fluentd
set_include_path(get_include_path() . PATH_SEPARATOR . LIB_DIR. '/fluent-logger-php');
require_once("Fluent/Autoloader.php");
Fluent\Autoloader::register();

// ベンチマーク設定
set_include_path(get_include_path() . PATH_SEPARATOR . LIB_DIR . '/Benchmark-1.2.8');
require_once("Benchmark/Timer.php");
$timer = new Benchmark_Timer();
$timer->start();

// require_once(LIB_DIR . '/foo/bar.php');

$timer->setMarker('loaded libs');

require_once(MODEL_DIR . "/RespCode.php");
require_once(MODEL_DIR . "/PadException.php");
require_once(MODEL_DIR . "/CacheKey.php");
require_once(MODEL_DIR . "/RedisCacheKey.php");
// require_once(MODEL_DIR . "/utils.php");

require_once(MODEL_DIR . "/BaseEnv.php");
require_once(MODEL_DIR . "/BaseModel.php");
require_once(MODEL_DIR . "/BaseMasterModel.php");
require_once(MODEL_DIR . "/BaseUserModel.php");
require_once(MODEL_DIR . "/BaseUserCardModel.php");
require_once(MODEL_DIR . "/BaseBonus.php");
require_once(MODEL_DIR . "/User.php");
require_once(MODEL_DIR . "/UserDevice.php");
require_once(MODEL_DIR . "/Card.php");
require_once(MODEL_DIR . "/UserCard.php");
require_once(MODEL_DIR . "/Dungeon.php");
require_once(MODEL_DIR . "/UserDungeon.php");
require_once(MODEL_DIR . "/DungeonFloor.php");
require_once(MODEL_DIR . "/UserDungeonFloor.php");
require_once(MODEL_DIR . "/Treasure.php");
require_once(MODEL_DIR . "/Wave.php");
require_once(MODEL_DIR . "/UserWave.php");
require_once(MODEL_DIR . "/WaveMonster.php");
require_once(MODEL_DIR . "/UserWaveMonster.php");
require_once(MODEL_DIR . "/LevelUp.php");
require_once(MODEL_DIR . "/BeatBonus.php");
require_once(MODEL_DIR . "/UserDeck.php");
require_once(MODEL_DIR . "/UserMail.php");
require_once(MODEL_DIR . "/LimitedBonus.php");
require_once(MODEL_DIR . "/Skill.php");
require_once(MODEL_DIR . "/Friend.php");
require_once(MODEL_DIR . "/RecommendedHelperUtil.php");
require_once(MODEL_DIR . "/GachaPrize.php");
require_once(MODEL_DIR . "/Gacha.php");
require_once(MODEL_DIR . "/LoginTotalCountBonus.php");
require_once(MODEL_DIR . "/LoginStreakBonus.php");
require_once(MODEL_DIR . "/Shop.php");
require_once(MODEL_DIR . "/GameConstant.php");
require_once(MODEL_DIR . "/PadTestLogger.php");
require_once(MODEL_DIR . "/Helper.php");
require_once(MODEL_DIR . "/Version.php");
require_once(MODEL_DIR . "/MagicStoneBonus.php");
require_once(MODEL_DIR . "/AllUserBonus.php");
require_once(MODEL_DIR . "/AllUserBonusHistory.php");
require_once(MODEL_DIR . "/LoginMessage.php");
require_once(MODEL_DIR . "/GachaMessage.php");
require_once(MODEL_DIR . "/PurchaseLog.php");
require_once(MODEL_DIR . "/UserContinue.php");
require_once(MODEL_DIR . "/UserLogClearDungeon.php");
require_once(MODEL_DIR . "/UserLogModifyCards.php");
require_once(MODEL_DIR . "/UserLogLogin.php");
require_once(MODEL_DIR . "/UserCardSeq.php");
require_once(MODEL_DIR . "/UserLogRareGacha.php");
require_once(MODEL_DIR . "/UserLogExtraGacha.php");
require_once(MODEL_DIR . "/UserLogDeleteMail.php");
require_once(MODEL_DIR . "/UserLogBuyStamina.php");
require_once(MODEL_DIR . "/UserLogExpandNumCards.php");
require_once(MODEL_DIR . "/PlusEgg.php");
require_once(MODEL_DIR . "/DungeonPlusDrop.php");
require_once(MODEL_DIR . "/LimitedBonusGroup.php");
require_once(MODEL_DIR . "/UserLogAddGold.php");
require_once(MODEL_DIR . "/UserLogBuyFriendMax.php");
require_once(MODEL_DIR . "/EnemySkill.php");
require_once(MODEL_DIR . "/UserCollaboration.php");
require_once(MODEL_DIR . "/CampaignSerialCode.php");
require_once(MODEL_DIR . "/UserSerial.php");
require_once(MODEL_DIR . "/SerialMaker.php");
require_once(MODEL_DIR . "/ConvertText.php");
require_once(MODEL_DIR . "/ExtraGacha.php");
require_once(MODEL_DIR . "/CampaignSerialItem.php");
require_once(MODEL_DIR . "/UserUploadData.php");
require_once(MODEL_DIR . "/DownloadMasterData.php");
require_once(MODEL_DIR . "/SupportData.php");
require_once(MODEL_DIR . "/ProductList.php");
require_once(MODEL_DIR . "/ChangeDeviceData.php");
require_once(MODEL_DIR . "/UserDungeonScore.php");
require_once(MODEL_DIR . "/Cipher.php");
require_once(MODEL_DIR . "/LimitedBonusOpenDungeon.php");
require_once(MODEL_DIR . "/LimitedBonusDungeonBonus.php");

require_once(MODEL_DIR . "/AdrPurchaseLog.php");
require_once(MODEL_DIR . "/AdrIgnoredPurchaseLog.php");

require_once(MODEL_DIR . "/AmzPurchaseLog.php");
require_once(MODEL_DIR . "/AmzIgnoredPurchaseLog.php");
require_once(MODEL_DIR . "/AccessBlockLogData.php");
require_once(MODEL_DIR . "/Price.php");
require_once(MODEL_DIR . "/UserLogChangeName.php");
require_once(MODEL_DIR . "/UserLogChangeDevice.php");
require_once(MODEL_DIR . "/UserLogSneakDungeon.php");

require_once(MODEL_DIR . "/WUserAitem.php");
require_once(MODEL_DIR . "/WUserDungeon.php");
require_once(MODEL_DIR . "/WUserDungeonFloor.php");
require_once(MODEL_DIR . "/WDungeon.php");
require_once(MODEL_DIR . "/WDungeonFloor.php");
require_once(MODEL_DIR . "/WDungeonFloorBlock.php");
require_once(MODEL_DIR . "/WWaveEgg.php");
require_once(MODEL_DIR . "/WAvatarItem.php");
require_once(MODEL_DIR . "/WGacha.php");
require_once(MODEL_DIR . "/WGachaPrize.php");
require_once(MODEL_DIR . "/WUserLogMedalGacha.php");
require_once(MODEL_DIR . "/WUserLogRareGacha.php");
require_once(MODEL_DIR . "/WUserLogModifyAvatarItems.php");

// #PADC_DY# roadmap model
require_once(MODEL_DIR . "/Roadmap.php");

$timer->setMarker('loaded models');

require_once(ACTION_DIR . "/BaseAction.php");
require_once(ACTION_DIR . "/Login.php");
require_once(ACTION_DIR . "/Signup.php");
require_once(ACTION_DIR . "/DownloadCardData.php");
require_once(ACTION_DIR . "/GetUserCards.php");
require_once(ACTION_DIR . "/DownloadDungeonData.php");
require_once(ACTION_DIR . "/ConfirmLevelUp.php");
require_once(ACTION_DIR . "/SneakDungeon.php");
require_once(ACTION_DIR . "/RequestFriend.php");
require_once(ACTION_DIR . "/GetUserMails.php");
require_once(ACTION_DIR . "/GetIdParameter.php");
require_once(ACTION_DIR . "/DownloadSkillData.php");
require_once(ACTION_DIR . "/DownloadLimitedBonusData.php");
require_once(ACTION_DIR . "/GetUserDungeonFloors.php");
require_once(ACTION_DIR . "/AcceptFriendRequest.php");
require_once(ACTION_DIR . "/ClearDungeon.php");
require_once(ACTION_DIR . "/SellUserCards.php");
require_once(ACTION_DIR . "/GetHelpers.php");
require_once(ACTION_DIR . "/PurchaseGold.php");
require_once(ACTION_DIR . "/CompositeUserCards.php");
require_once(ACTION_DIR . "/EvolveUserCard.php");
require_once(ACTION_DIR . "/UpdateAccessTime.php");
require_once(ACTION_DIR . "/GetRecommendedHelpers.php");
require_once(ACTION_DIR . "/PlayGacha.php");
require_once(ACTION_DIR . "/SendThankYouGift.php");
require_once(ACTION_DIR . "/ReceiveThankYouGift.php");
require_once(ACTION_DIR . "/BuyStamina.php");
require_once(ACTION_DIR . "/DoContinue.php");
require_once(ACTION_DIR . "/DoContinueAck.php");
require_once(ACTION_DIR . "/QuitFriend.php");
require_once(ACTION_DIR . "/ChangeName.php");
require_once(ACTION_DIR . "/ExpandNumCards.php");
require_once(ACTION_DIR . "/CreateDummyData.php");
require_once(ACTION_DIR . "/GetIdsParameter.php");
require_once(ACTION_DIR . "/SneakDungeonAck.php");
require_once(ACTION_DIR . "/GetUserMail.php");
require_once(ACTION_DIR . "/DeleteUserMail.php");
require_once(ACTION_DIR . "/SendUserMail.php");
require_once(ACTION_DIR . "/GetPlayerData.php");
require_once(ACTION_DIR . "/GetPlayerInfo.php");
require_once(ACTION_DIR . "/BuyFriendMax.php");
require_once(ACTION_DIR . "/DownloadEnemySkillData.php");
require_once(ACTION_DIR . "/Collaboration.php");
require_once(ACTION_DIR . "/SaveDecks.php");
require_once(ACTION_DIR . "/PlayGachaCnt.php");
require_once(ACTION_DIR . "/UploadData.php");
require_once(ACTION_DIR . "/DownloadData.php");
require_once(ACTION_DIR . "/ChangeDevice.php");
require_once(ACTION_DIR . "/GetSpid.php");
require_once(ACTION_DIR . "/SetAuthData.php");
require_once(ACTION_DIR . "/FavUserMail.php");
require_once(ACTION_DIR . "/UserSetting.php");

require_once(ACTION_DIR . "/GetProductList.php");
require_once(ACTION_DIR . "/AdrPurchaseGold.php");
require_once(ACTION_DIR . "/AmzPurchaseGold.php");

require_once(ACTION_DIR . "/DownloadWDungeonData.php");
require_once(ACTION_DIR . "/DownloadWAvatarItemData.php");
require_once(ACTION_DIR . "/PwMdtdl.php");
require_once(ACTION_DIR . "/PwGacha.php");

// #PADC_DY# download roadmap action
require_once(ACTION_DIR . "/DownloadRoadmapData.php");

// Debug API
require_once(ACTION_DIR . "/DeleteUser.php");

$timer->setMarker('loaded actions');

require_once(FILTER_DIR . "/environment.php");


$timer->setMarker('loaded filters');

// エラーハンドラ. エラーが発生したら例外をスローする.
function errorHandler($errno, $errstr, $errfile, $errline) {
  throw new Exception($errfile . "[" . $errline . "]:" . $errstr, $errno);
}
set_error_handler('errorHandler');

<?php
/**
 * ダンジョン潜入のログモデル.
 */
class UserLogSneakDungeon extends BaseModel {
  const TABLE_NAME = "user_log_sneak_dungeon";
  const HAS_UPDATED_AT = FALSE;
  const HAS_CREATED_AT = FALSE;

  protected static $columns = array(
    'user_id',
    'dungeon_floor_id',
    'device_type',
    'area_id',
    'sneaked_at'
  );

  public static function log($user_id, $dungeon_floor_id, $device_type, $area_id, $sneaked_at) {

    $entity = new self();
    $entity->user_id = $user_id;
    $entity->dungeon_floor_id = $dungeon_floor_id;
    $entity->device_type = $device_type;
    $entity->area_id = $area_id;
    $entity->sneaked_at = $sneaked_at;

    if ( Env::ENV == "production" ) {
        static::postLog((array) $entity);
    } else {
      // 本番環境以外はDBへのINSERTに
      $pdo = Env::getDbConnectionForDungeonLog();
      $entity->create( $pdo );
    }
  }
}
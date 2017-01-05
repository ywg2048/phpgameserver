<?php
/**
 * 機種変更のログモデル.
 */
class UserLogChangeDevice extends BaseModel {
  const TABLE_NAME = "user_log_change_device";
  const MEMCACHED_EXPIRE = 86400; // 24時間.

  protected static $columns = array(
    'user_id',
    'changed_user_id',
    'data',
    'admin_id',
    'created_at',
  );

  public static function log($user_id, $changed_user_id, $data = array(), $admin_id = null, $pdo = null) {

/*
dataの内容
  cd_code VARCHAR(12), -- 機種変更コード（管理ツールからの場合はNULL）
  gold INT, -- 機種変更時の無償魔法石
  pgold INT, -- 機種変更時の有償魔法石
  old_accessed_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', -- 変更前最終アクセス時間
  old_uuid VARCHAR(255) NOT NULL, -- 変更前uuid
  old_dev VARCHAR(255), -- 変更前機種名
  old_osv VARCHAR(255), -- 変更前OSバージョン
  old_type TINYINT NOT NULL DEFAULT 0, -- 変更前デバイスタイプ # iOS=0, Android=1, kindle=2
  old_version VARCHAR(255) NOT NULL, -- 変更前アプリバージョン
  new_uuid VARCHAR(255) NOT NULL,
  new_dev VARCHAR(255), -- 変更後機種名
  new_osv VARCHAR(255), -- 変更後OSバージョン
  new_type TINYINT NOT NULL DEFAULT 0, -- 変更後デバイスタイプ # iOS=0, Android=1, kindle=2
  new_version VARCHAR(255) NOT NULL, -- 変更後アプリバージョン
*/

    $l = new UserLogChangeDevice();
    $l->user_id = $user_id;
    $l->changed_user_id = $changed_user_id;
    $l->data = json_encode($data);
    $l->admin_id = $admin_id;
    $l->create($pdo);
  }
}

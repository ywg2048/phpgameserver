<?php
/**
 * 	ユーザーコラボレーション（付与履歴）のモデル.
 */
class UserCollaboration extends BaseModel {
  const TABLE_NAME = "user_collaboration";

  protected static $columns = array(
    'user_id',
    'type',
  );

}

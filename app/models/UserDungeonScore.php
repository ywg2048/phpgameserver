<?php
/**
 * ユーザーダンジョンスコア. 
 */

class UserDungeonScore extends BaseModel {
  const TABLE_NAME = "user_dungeon_scores";

  protected static $columns = array(
    'user_id',
    'dungeon_floor_id',
    'high_score',
    'high_score_at',
    'srank_at',
  );

  // ハイスコアの更新とSランク達成チェック.
  public static function updateHighScore($user_id, $dungeon_floor_id, $sr, $score, $pdo){
    $srank_flg = FALSE;
    $score_data = self::findBy(array('user_id' => $user_id, 'dungeon_floor_id' => $dungeon_floor_id), $pdo);
    if($score_data) {
      if($score > $score_data->high_score){
        $score_data->high_score = $score;
        $score_data->high_score_at = static::timeToStr(time());
        if($score_data->srank_at === NULL && $score >= $sr){
          $srank_flg = TRUE; // Sランク達成でたまドラあげる.
          $score_data->srank_at = static::timeToStr(time());
        }
        $score_data->update($pdo);      
      }
    }else{
      $score_data = new UserDungeonScore();
      $score_data->user_id = $user_id;
      $score_data->dungeon_floor_id = $dungeon_floor_id;
      $score_data->high_score = $score;
      $score_data->high_score_at = static::timeToStr(time());
      if($score >= $sr){
        $srank_flg = TRUE; // Sランク達成でたまドラあげる.
        $score_data->srank_at = static::timeToStr(time());
      }else{
        $score_data->srank_at = null;
      }
      $score_data->create($pdo);
    }
    return $srank_flg;
  }
}
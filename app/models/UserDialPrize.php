<?php
class UserDialPrize extends BaseModel {
    const TABLE_NAME = "user_dial_prize";

    protected static $columns = array(
        'id',
        'user_id',
        'prize_id',
        'sequence',
        'created_at',
        'updated_at',
    );

    public static function insertPrizeRecord($user_id,$prizeid,$sequence,$pdo=null){
        if(null == $pdo){
            $pdo = Env::getDbConnectionForUserWrite($user_id);
        }

        try{
            $pdo->beginTransaction();
            $userDialPrize = new UserDialPrize();
            $userDialPrize->user_id = $user_id;
            $userDialPrize->prize_id = $prizeid;
            $userDialPrize->sequence = $sequence;
            $userDialPrize->create_at = BaseModel::timeToStr(time());
            $userDialPrize->create($pdo);
            $pdo->commit();
        }catch(Exception $e){
            if($pdo->inTransaction()){
                $pdo->rollBack();
            }
            throw new PadException(RespCode::UNKNOWN_ERROR,$e->getMessage());
        }

        $pdo =null;
        return true;
    }
}

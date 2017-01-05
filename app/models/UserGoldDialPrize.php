<?php
class UserGoldDialPrize extends BaseModel {
    const TABLE_NAME = "user_gold_dial_prize";

    protected static $columns = array(
        'id',
        'user_id',
        'prize_id',
        'created_at',
        'updated_at',
    );

    public static function insertPrizeRecord($user_id,$prizeId,$pdo=null){
        if(null == $pdo){
            $pdo = Env::getDbConnectionForUserWrite($user_id);
        }

        try{
            $pdo->beginTransaction();
            $userGoldDialPrize = new UserGoldDialPrize();
            $userGoldDialPrize->user_id = $user_id;
            $userGoldDialPrize->prize_id = $prizeId;
            $userGoldDialPrize->create_at = BaseModel::timeToStr(time());
            $userGoldDialPrize->create($pdo);
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

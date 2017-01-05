<?php
class UserDialInfo extends BaseModel {
    const TABLE_NAME = "user_dial_info";

    protected static $columns = array(
        'id',
        'user_id',
        'events_id',
        'prizelist',          //玩家转盘的奖品列表：奖品的ID、是否得到奖品的标志、奖品的得到概率
        'sequence',           //玩家刷新的次数
        'flag1',              //首次转盘的标志
        'flag2',              //首次重置的标志
        'created_at',
        'updated_at',
    );

    //更新玩家的转盘的状态
    public static function updatePrizeItemsById($id,$user_id,$probForDialPosition,$pdo = null){

        if(null == $pdo){
            $pdo = Env::getDbConnectionForUserWrite($user_id);
        }
        $pdo->beginTransaction();
        $info = null;
        try{
            //根据设置的好的概率从每个档次的奖品中，取出两个
            $prizeItems = DialPrize::getPrizeItems(DialPrize::DIAL_TYPE_LUCKY);
            $i = 0;
            $userPrizeItemList = null;
            foreach($prizeItems as $prizeItem){
                $userPrizeItemList[] = array(
                    $prizeItem->id,
                    0,                                   //是否已经抽取到
                    $probForDialPosition[$i++],          //抽奖得到的概率
                );
            }
            $prizelist = json_encode($userPrizeItemList);

            $info = UserDialInfo::find($id,$pdo,true);
            $info->prizelist = $prizelist;
            $info->sequence = 0;
            $info->updated_at = BaseModel::timeToStr(time());
            $info->update($pdo);

            $pdo->commit();
        }catch(Exception $e){
            if($pdo->inTransaction()){
                $pdo->rollBack();
            }
            throw new PadException(RespCode::UNKNOWN_ERROR,$e->getMessage());
        }


        return $info;
    }
}

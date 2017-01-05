<?php
class DialPrize extends BaseMasterModel {
    const TABLE_NAME = "padc_dial_prize";
    const MEMCACHED_EXPIRE = 86400; // 24時間.
    const DIAL_TYPE_LUCKY  = 2;     //幸运转盘的奖品（dial_type）
    const DIAL_TYPE_GOLD  = 1;     //魔法转盘对应的奖品(dial_type)

    protected static $columns = array(
        'id',
        'bonus_id',
        'amount',
        'piece_id',
        'level',
        'prob',
        'dial_type',
        );

    /**
     * 有一定概率取出奖品的列表，每一个档次取两个商品
     */
    public static function getPrizeItems($dialType = 0)
    {

        if(0 == $dialType){
            throw new PadException(RespCode::UNKNOWN_ERROR,"Call function getPrizeItems without dialtype!");
        }

        //取出奖池的所有物品，根据level字段分类
        $groupItems = array();
        $groupCount = array();
        $order = "id asc";
        $prizeItems = self::findAllBy(array('dial_type'=> $dialType),$order);   //这里按档次的大小、每个档次的抽出概率来排列，只取出id、prob、level，待修改

        //每个档次取几个奖品
        $eachLevelGet = null;
        switch($dialType){
            case self::DIAL_TYPE_GOLD:
                $eachLevelGet = 1;
                break;
            case self::DIAL_TYPE_LUCKY:
                $eachLevelGet = 1;
                break;
        }

        $maxLevel = 0;
        foreach ($prizeItems as $item) {
            $maxLevel = $maxLevel < $item->level?$item->level:$maxLevel;
            $group = (int)$item->level;
            $groupItems[$group][] = $item;

            if (isset($groupCount[$group])) {
                $groupCount[$group] += 1;
            } else {
                $groupCount[$group] = 1;
            }
        }

        $userPrizesItems = array();
        $sum_prob = 0;                     //概率总和
        foreach($groupItems as $key=>$values){

            //每档取$dialType个奖品：该值和DIAL_TYPE_XX的值相关
            for($i=0;$i<$eachLevelGet;$i++){
                //抽取第一个商品
                foreach($values as $value){
                    $sum_prob += $value->prob;

                }
                $seed = mt_rand(0,$sum_prob);
                $grad_sum = 0;
                foreach($values as $rid=>$value){
                    $grad_sum += $value->prob;
                    $seed -= $grad_sum;
                    //抽中商品，进行处理
                    if($seed <= 0){
                        // array_push($userPrizesItems,$value);
                        $userPrizesItems[$value->id] = $value;
                        unset($values[$rid]);        //移除已抽到的商品
                        break;
                    }
                }
                $sum_prob = 0;
            }
        }

        //根据每个档次取出多少个奖品来计算总奖品数量
        if(count($userPrizesItems) != $eachLevelGet*$maxLevel){
            throw new PadException(RespCode::INVALID_EXCHANGE_ITEM,"Failed to get the prizes!");
        }
        ksort($userPrizesItems);
        return $userPrizesItems;
    }

    //魔法转盘要求：第一次取到的奖品，所有的玩家是相同的
    public static function getFirstPrizeItemsForGoldDial(){

        $sql = "SELECT * FROM ".static::TABLE_NAME." WHERE dial_type =".self::DIAL_TYPE_GOLD." GROUP BY level";
        $pdo_share = Env::getDbConnectionForShareRead();
        $stmt = $pdo_share->prepare($sql);
        $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());
        $stmt->execute();
        $goldPrizeItems = $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
        $pdo_share = null;

        return $goldPrizeItems;
    }
}

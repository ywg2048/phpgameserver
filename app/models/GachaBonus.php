<?php

// #PADC_DY#
class GachaBonus extends BaseMasterModel{
    const TABLE_NAME = "padc_gacha_bonus";
    const VER_KEY_GROUP = "padcgb";
    const MEMCACHED_EXPIRE = 86400; // 24時間.

    protected static $columns = array(
        'id',
        'gacha_id',
        'bonus_id',
        'piece_id',
        'amount',
    );

    // 魔法石gacha的时候，购买获得的道具目前只有扫荡券
    public static function applyBonuses($user, $gacha_bonus){
        $result = $user;
        $amount = $gacha_bonus->amount;
        if($gacha_bonus->bonus_id == BaseBonus::ROUND_ID) {
            // 周回チケット.
            $user->addRound($amount);
            $result = $user;
        }
        return $result;
    }


}
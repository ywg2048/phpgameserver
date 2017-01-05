<?php

// #PADC_DY#
class GachaLineupConfig extends BaseMasterModel {
    const TABLE_NAME = "padc_gacha_lineup_config";
    const VER_KEY_GROUP = "padcglc";
    const MEMCACHED_EXPIRE = 86400; // 24時間.

    protected static $columns = array(
        'id',
        'type',
        'prifix',
        'gacha_id',
        'is_on',
    );

    public static function getAllLineupData() {
        $lineups = array();
        $all_lineups = self::getAll();
        foreach($all_lineups as $lineup){
            if($lineup->is_on != 0){
                $lineups[$lineup->prifix] = DownloadGachaLineupData::arrangeColumns($lineup->gacha_id);
            }
        }
        return $lineups;
    }
}
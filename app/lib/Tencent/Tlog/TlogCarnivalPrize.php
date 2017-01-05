<?php

/**
 * #PADC_DY#
 * 新手嘉年华领取奖励时，对应任务类型的记录
 */
class TlogCarnivalPrize extends TlogBase {

    const EVENT = "CarnivalPrize";

    protected static $columns = array(
        'event',
        'GameSvrId',
        'dtEventTime',
        'vGameAppid',
        'PlatID',
        'iZoneAreaID',
        'vopenid',
        'prizeid',
        'mtype',
        'carnivalDesc',
    );

    /**
     * 新手嘉年华
     * @param $appId
     * @param $platId
     * @param $openId
     * @param $prizeid
     * @param $mtype
     * @return string
     * @throws Exception
     */
    public static function generateMessage($appId, $platId, $openId,$prizeid,$mtype,$desc) {
        $params = array(
            static::EVENT,
            static::getGameSvrId(),
            static::makeTime(),
            $appId,
            $platId,
            static::getZoneId(),
            $openId,
            $prizeid,
            $mtype,
            $desc,
        );
        return static::generateMessageFromArray($params);
    }
}

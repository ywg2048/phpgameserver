<?php

/**
 * #PADC_DY#
 * 兑换商店
 */
class TlogExchangeItem extends TlogBase {

    const EVENT = "ExchangeItem";

    protected static $columns = array(
        'event',
        'GameSvrId',
        'dtEventTime',
        'vGameAppid',
        'PlatID',
        'iZoneAreaID',
        'vopenid',
        'Money',
        'ExchangeType'
    );

    /**
     * 兑换商店
     *
     * @param string $appId
     *        	(必填)游戏APPID
     * @param number $platId
     *        	(必填)ios 0/android 1
     * @param string $openId
     *        	(必填)用户OPENID号
     * @param number $money
     *        	(必填)动作涉及的金钱数
     * @param number $exchangeType
     *        	(必填)兑换的类型ExchangeType
     */
    public static function generateMessage($appId, $platId, $openId, $money, $exchangeType) {
        $params = array(
            static::EVENT,
            static::getGameSvrId(),
            static::makeTime(),
            $appId,
            $platId,
            static::getZoneId(),
            $openId,
            $money,
            $exchangeType
        );
        return static::generateMessageFromArray($params);
    }

}

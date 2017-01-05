<?php

/**
 * #PADC_DY#
 * 购买更多次数
 */
class BuyFloorTimes extends BaseAction {

    // http://pad.localhost/api.php?action=buy_floor_times&pid=1&sid=1&dung=1&floor=1
    public function action($params) {
        if (!isset($params['pid']) || !isset($params['dung']) || !isset($params['floor'])) {
            throw new PadException(RespCode::INVALID_PARAMS, "Error params. pid: {$params['pid']} dung: {$params['dung']} floor: {$params['floor']}");
        }
        
        $token = Tencent_MsdkApi::checkToken($params);
        if(!isset($params['ranking'])){
            throw new PadException(RespCode::INVALID_PARAMS, "NO ranking");
        }
        $res = Shop::buyFloorContinue((int) $params['pid'], (int) $params['dung'], (int) $params['floor'],$params['ranking'], $token);
        
        if($res) {
            return json_encode(array(
                'res' => RespCode::SUCCESS,
                'buyed_floor_times' => $res['buyed_floor_times'],
                'daily_recovered_times' => $res['daily_recovered_times'],
                'daily_left_recovery_times' => $res['daily_left_recovery_times'],
                'gold' => $res['gold']
            ));
        } else {
            throw new PadException(RespCode::UNKNOWN_ERROR, "Error params. pid: {$params['pid']} dung: {$params['dung']} floor: {$params['floor']}");
        }
    }

}

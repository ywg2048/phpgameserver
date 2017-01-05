<?php

/**
 * #PADC_DY#
 *
 * 获取交换商品列表
 */
class ResetUserDialPrizeInfo extends BaseAction
{
    public function action($params)
    {
        $user_id = $params['pid'];

      
		$token = Tencent_MsdkApi::checkToken($params);
		if (!$token) {
			return json_encode(array(
				'res' => RespCode::TENCENT_TOKEN_ERROR
			));
		}
		try {
			User::getUserBalance($user_id, $token);
		} catch (PadException $e) {
			if ($e->getCode() != RespCode::TENCENT_NETWORK_ERROR && $e->getCode() != RespCode::TENCENT_API_ERROR) {
				throw $e;
			}
		}
        
        //转盘的三个参数：转盘价格、重置价格、8个位置对应的概率
        $gameConstant = GameConstant::getDialParameter();
        $probForDialPosition = json_decode($gameConstant['ProbForDialPosition'],true);
        $priceForTurnDial = json_decode($gameConstant['PriceForTurnDial'],true);
        $priceForResetDial = json_decode($gameConstant['PriceForResetDial'],true);
        $maxResetNumForDial = $gameConstant['MaxResetNumForDial'];

        $res = array();
        $pdo_user = Env::getDbConnectionForUserWrite($user_id);
        $resetGold = 0;
        $hasResetNum = 0;
        $prizes = array();

        try{
            $pdo_user->beginTransaction();
            $userDialInfo = UserDialInfo::findBy(array('user_id'=>$user_id),$pdo_user);
            $user = User::find($user_id,$pdo_user);
            $before_gold = $user->gold;
            $before_pgold = $user->pgold;

            $hasResetNum = $userDialInfo->sequence;
            //此次重置需要付费的魔法石
            if($maxResetNumForDial == $hasResetNum){
                return json_encode(RespCode::UNKNOWN_ERROR);
            }
            //首次的重置
            if(0 == $userDialInfo->flag2)
            {
                $resetGold = 0;
                $userDialInfo->flag2 = 1;
            }
            else{
                $resetGold = $priceForResetDial[$hasResetNum];
            }
            if($user->checkHavingGold($resetGold) === FALSE){
                global $logger;
                $logger->log(("not enough gold"), Zend_Log::DEBUG);
                return json_encode(RespCode::NOT_ENOUGH_MONEY);
            }

            //刷新用户转盘的信息
            $userPrizeItemList = array();
            $prizeItems = DialPrize::getPrizeItems(DialPrize::DIAL_TYPE_LUCKY);     //根据设置的好的概率从每个档次的奖品中，取出两个
            $i = 0;
            foreach($prizeItems as $prizeItem){
                $prizes[] = array(
                    'id'        => $prizeItem->id,
                    'bonus_id'  => $prizeItem->bonus_id,
                    'amount'    => $prizeItem->amount,
                    'piece_id'  => $prizeItem->piece_id,
                    'level'     => $prizeItem->level,
                    'isacquired'=> 0,
                );

                $userPrizeItemList[] = array(
                    $prizeItem->id,
                    0,                                                   //是否已经抽取到
                    $probForDialPosition[$i++],                          //抽奖得到的概率
                );
            }
            $res['prizes'] = $prizes;
            $hasResetNum += 1;
            $prizelist = json_encode($userPrizeItemList);
            $userDialInfo->prizelist = $prizelist;
            $userDialInfo->sequence  = $userDialInfo->sequence+1;
            $res['seq'] = $userDialInfo->sequence;
            if(0 == $userDialInfo->flag1)
                $res['gold'] = 0;
            else
                $res['gold'] = $priceForTurnDial[0];

            $userDialInfo->update_at = BaseModel::timeToStr(0);
            $userDialInfo->update($pdo_user);

            $user->payGold($resetGold, $token, $pdo_user);              //玩家魔法石付费
            $res['pgold'] = $resetGold;
            $res['lgold'] = ($before_pgold+$before_gold)-$resetGold;    //玩家剩余的魔法石

            $user->accessed_at = User::timeToStr(time());
            $user->accessed_on = $user->accessed_at;

            $user->update($pdo_user);
            $pdo_user->commit();

            $after_gold = $user->gold + $user->pgold;
            //TODO
            UserTlog::sendTlogMoneyFlow($user, $after_gold - ($before_gold + $before_pgold), Tencent_Tlog::REASON_LUNCKY_DIAL_RESET, Tencent_Tlog::MONEY_TYPE_DIAMOND,abs($user->gold - $before_gold),abs($user->pgold - $before_pgold),0,0);
        }catch(Exception $e){
            if($pdo_user->inTransaction()){
                $pdo_user->rollBack();
                throw new PadException(RespCode::UNKNOWN_ERROR,"Failed to reset UserDialPrizeInfo!");
            }
        }

        $isForbidedReset = 0;
        if($maxResetNumForDial == $hasResetNum){
            $res['rgold'] = 0;
            $isForbidedReset = 1;
        }
        else
            $res['rgold'] = $priceForResetDial[$hasResetNum];

        $res['res'] = RespCode::SUCCESS;
        $res['pgold'] = $resetGold;
        $res['isforbid'] = $isForbidedReset;                  //是否被禁止重置
        $res['mreset'] = $maxResetNumForDial;                 //最大重置次数

        $t1 = $userDialInfo->flag1 == 0?1:0;       //首次转盘免费
        $t2 = $userDialInfo->flag2 == 0?1:0;       //首次重置转盘免费
        $res['dial_lucky_total'] = $t1 + $t2;


        return json_encode($res);
    }
}
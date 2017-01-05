<?php
/**
 * Tencent用：ユーザデータを検索
 */
class TencentQueryUsrInfo extends TencentBaseAction {
    /**
     *
     * @see TencentBaseAction::action()
     */
    public function action($params) {
        $openid = $params ['OpenId'];
        $type = $params ['PlatId'];

        $user_id = UserDevice::getUserIdFromUserOpenId ( $type, $openid );
        $user = User::find ( $user_id );
        if (empty ( $user )) {
            throw new PadException ( RespCode::USER_NOT_FOUND, 'User not found!' );
        }

        $pdo_share = Env::getDbConnectionForShare();
        $user_device = UserDevice::findBy(array('id' => $user_id), $pdo_share);
        $pre_pid = UserDevice::getPreUserId($user_device->type, $user_device->ptype);
        $disp_id = UserDevice::convertPlayerIdToDispId($pre_pid, $user_id);

        $card_num = UserCard::countUserCards($user_id);


        $pdo_user=Env::getDbConnectionForUserRead($user_id);
        $user_ranking = UserRanking::findBy(array('user_id'=>$user_id),$pdo_user);
        $level_ranking_score = null;
        $level_ranking = null;
        if( False == $user_ranking ){
            $level_ranking_score = 0;
            $level_ranking = 0;
        }else{
            $level_ranking_score = $user_ranking->score;                              //玩家的排名榜上的分数
            $level_ranking = Ranking::getScoreRanking($level_ranking_score,$pdo_share);  //玩家排名名次
        }
        $pdo_user = null;

        $result = array_merge(array (
            'res' => 0,
            'msg' => 'OK'
        ), static::arrangeColumns($user, $openid, $disp_id, $card_num,$level_ranking_score,$level_ranking)
        );

        return json_encode ( $result );
    }

    /**
     *
     * @param User $user
     * @param string $openid
     * @return array
     */
    public static function arrangeColumns($user, $openid, $disp_id, $card_num,$level_ranking_score,$level_ranking) {
        $mapper = array ();
        $mapper ['OpenId'] = $openid;
        $mapper ['RoleName'] = urlencode($user->name);
        // #PADC_DY# ----------begin----------
        // $mapper ['Level'] = $user->clear_dungeon_cnt;
        $mapper ['Level'] = $user->lv;
        // #PADC_DY# ----------end----------
        $mapper ['Money'] = $user->coin;
        $mapper ['Physical'] = $user->getStamina();
        $mapper ['Diamond'] = ($user->gold + $user->pgold);
        $mapper ['Exp'] = 0;
        $mapper ['Fight'] = 0;
        $mapper ['CreateTime'] = strtotime ( $user->created_at );
        $mapper ['IsOnline'] = 0;
        $mapper ['LastLoginTime'] = strtotime ( $user->li_last );
        $mapper ['LastLogoutTime'] = 'NULL';
        $mapper ['MaxPass'] = $user->last_clear_normal_dungeon_id;
        $mapper ['Vip'] = $user->vip_lv;
        $mapper ['OnlineTime'] = 0;
        $mapper ['Camp'] = $user->camp;
        // #PADC_DY# ----------begin----------
        $mapper['MonthCard'] = (isset($user) && $user->duringSubscription()) ? 1 : 0; // 月卡是否开通
        $mapper['CurrentFriendNum'] = isset($user) ? $user->fripnt : 0; // 当前友情点个数
        $mapper['CurrentClearNum'] = isset($user) ? $user->round : 0; // 当前拥有扫荡券个数
        $mapper['NowCardNum'] = $card_num;
        $mapper['PlayerId'] = $disp_id;
        $mapper['PlayerUserId'] = $user->id;
        // #PADC_DY# -----------end-----------

        $mapper['RankRoundPoint'] = $level_ranking_score;
        $mapper['RankRoundNum'] = $level_ranking;

        return $mapper;
    }
}

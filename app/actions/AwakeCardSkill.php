<?php

// #PADC_DY# 卡牌技能觉醒
class AwakeCardSkill extends BaseAction {

    public function action($params) {
        $pid = $params['pid'];
        $cuid = $params['cuid'];
        $skill_index = $params['pskill_index'];

        list($succeed, $coin, $ret_piece) = UserCard::awakeCardSkill($pid, $cuid, $skill_index);
        $rcard = GetUserCards::getOneUserCard($pid, $cuid);

        $return = array (
            'res' => ($succeed ? RespCode::SUCCESS : RespCode::FAILED_AWAKE_SKILL),
            'coin' => $coin,
            'rcard' => $rcard,
            'rpieces' => $ret_piece
        );

        return json_encode($return);
    }
}
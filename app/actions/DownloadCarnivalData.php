<?php
//##新手嘉年华
class DownloadCarnivalData extends BaseAction {
    const MEMCACHED_EXPIRE = 86400; // 24時間.
    const MAIL_RESPONSE = FALSE;
    const ENCRYPT_RESPONSE = FALSE;

    public function action($params) {
        $key = MasterCacheKey::getCarnivalData();
        $value = apc_fetch($key);
        if (FALSE === $value) {
            $value = DownloadMasterData::find(DownloadMasterData::ID_CARNIVAL)->gzip_data;
            apc_store($key, $value, static::MEMCACHED_EXPIRE + DownloadMasterData::add_apc_expire());
        }

        return $value;
    }

    public static function arrangeColumns($carnivals) {
        //新手嘉年华的Tab文字说明
        $group_text = GameConstant::getCarnivalTabDes();
        $group = array();
        $group['box']     = array();
        $group['mission'] = array();
        foreach ($carnivals as $carnival) {
            $tmp = array();
            $tmp['id']         = (int)$carnival->id;
            //$tmp['mission_id'] = (int)$carnival->mission_id;
            $tmp['bonus_id1']  = (int)$carnival->bonus_id1;
            $tmp['amount1']    = (int)$carnival->amount1;
            $tmp['piece_id1']  = (int)$carnival->piece_id1;
            $tmp['bonus_id2']  = (int)$carnival->bonus_id2;
            $tmp['amount2']    = (int)$carnival->amount2;
            $tmp['piece_id2']  = (int)$carnival->piece_id2;
            $tmp['bonus_id3']  = (int)$carnival->bonus_id3;
            $tmp['amount3']    = (int)$carnival->amount3;
            $tmp['piece_id3']  = (int)$carnival->piece_id3;
            $tmp['reward_text']= $carnival->reward_text;

            $group_id= (int)$carnival->group_id;

            //宝箱的数据
            if(0 == $group_id){
                unset($tmp['reward_text']);
                $group['box'][] = $tmp;
                continue;
            }

            if(!isset($group['mission'][$group_id])){
                $group['mission'][$group_id]   = array();
                $group['mission'][$group_id]['group_text']= $group_text[$group_id];
                $group['mission'][$group_id]['group_list'][] = $tmp;
            }else{
                $group['mission'][$group_id]['group_list'][] = $tmp;
            }

        }

        return $group;
    }
}
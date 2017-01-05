<?php

// #PADC_DY# 新增获得Gacha界面限定一览API
class DownloadGachaLineupData extends BaseAction {

    const MEMCACHED_EXPIRE = 86400; // 24時間.
    // #PADC#
    const MAIL_RESPONSE = FALSE;
    const ENCRYPT_RESPONSE = FALSE;
    public function action($params) {
        $key = MasterCacheKey::getDownloadGachaLineupData();
        $value = apc_fetch($key);
        if (FALSE === $value) {
            $value = DownloadMasterData::find(DownloadMasterData::ID_GACHA_LINEUP)->gzip_data;
            apc_store($key, $value, static::MEMCACHED_EXPIRE + DownloadMasterData::add_apc_expire());
        }
        return $value;
    }

    public static function arrangeColumns($gacha_id){
        $lineup = array();
        $gacha_prizes = GachaPrize::getAllBy(array("gacha_id" => $gacha_id), "prob ASC");

        foreach ($gacha_prizes as $gacha_prize) {
            $unit = array();
            $unit['pid'] = $gacha_prize->piece_id;
            $unit['amount'] = $gacha_prize->piece_num;
            $unit['event'] = $gacha_prize->event;

            // 按优先度取出数据
            $recommend_deck_leader = RecommendDeckLeader::getAllBy(array("piece_id" => $gacha_prize->piece_id), "priority DESC");
            if($recommend_deck_leader) {
                $unit['pos'] = $recommend_deck_leader[0]->pos;
                $recommend_deck = RecommendDeck::get($recommend_deck_leader[0]->deck_id);
                $unit['lid'] = $recommend_deck->leader;
                $unit['des'] = $recommend_deck->description;
                $unit['sub1'] = $recommend_deck->card_id1;
                $unit['sub2'] = $recommend_deck->card_id2;
                $unit['sub3'] = $recommend_deck->card_id3;
                $unit['sub4'] = $recommend_deck->card_id4;

                $lineup[] = $unit;
            }
        }
        return $lineup;
    }

}
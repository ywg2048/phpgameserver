<?php

/**
 * #PADC#
 * ランキングダンジョン.
 */
class RankingDungeon extends Dungeon {
    const TABLE_NAME = "padc_ranking_dungeons";
    const VER_KEY_GROUP = "padcrankingdung";
    const MEMCACHED_EXPIRE = 86400; // 24時間.
    const DUNGEON_FLOOR_CLASS = 'RankingDungeonFloor';

    // #PADC# ----------begin----------
    // チケット #11442に対しての暫定対応
    protected static $columns = array(
        'id',
        'name',
        'panel_id', // #PADC# フロア看板ID
        'attr',
        'dtype',
        'dkind', // #PADC# SPダンジョン種類
        'dwday',
        'dsort',
        'reward_gold',
        'rankup_flag', // #PADC# ランクアップするダンジョンかどうか
        'url_flag', // #PADC# コンティニュー時の遷移先URLがあるかどうか
        'share_file',//分享图片
    );
    // #PADC# ----------end----------
    // #PADC_DY# 排名关卡的判断
    public function isSpecialDungeon() {
        return true;
    }
}

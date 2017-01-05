<?php

class DownloadPassiveSkillData extends BaseAction {
    const MEMCACHED_EXPIRE = 86400; // 24時間.
    const MAIL_RESPONSE = FALSE;
    const ENCRYPT_RESPONSE = FALSE;

    public function action($params) {
        $key = MasterCacheKey::getPassiveSkillData();
        $value = apc_fetch($key);
        if (FALSE === $value) {
            $value = DownloadMasterData::find(DownloadMasterData::ID_PASSIVE_SKILL)->gzip_data;
            apc_store($key, $value, static::MEMCACHED_EXPIRE + DownloadMasterData::add_apc_expire());
        }
        return $value;
    }


    public static function arrangeColumns($pskills) {
        $mapper = array();
        foreach ($pskills as $pskill) {
            $array = array();
            $array['id'] = (int)$pskill->id;
            $array['psid'] = (int)$pskill->pskill_id;
            $array['name'] = $pskill->name;
            $array['des'] = $pskill->desc;
            $array['piece_id'] = (int)$pskill->awake_piece_id;
            $array['num'] = (int)$pskill->num;
            $array['cost'] = (int)$pskill->cost;
            $mapper[] = $array;
        }
        return $mapper;
    }
}
<?php

/**
 * Class DownloadRoadmapData
 * #PADC_DY# unlock user's roadmap
 */
class DownloadRoadmapData extends BaseAction
{
    // http://pad.localhost/api.php?action=download_roadmap_data&pid=1&sid=1
    const MEMCACHED_EXPIRE = 86400; // 24時間.
    const MAIL_RESPONSE = FALSE;
    const ENCRYPT_RESPONSE = FALSE;

    public function action($params){
        $key = MasterCacheKey::getDownloadRoadmapData();
        $value = apc_fetch($key);
        if(FALSE === $value) {
            $value = DownloadMasterData::find(DownloadMasterData::ID_ROADMAP)->gzip_data;
            apc_store($key, $value, static::MEMCACHED_EXPIRE + DownloadMasterData::add_apc_expire());
        }
        return $value;
    }

    public static function arrangeColumns($roadmap) {
        $mapper = array();
        foreach ($roadmap as $rm) {
            $array = array();

            $array['id']		= (int)$rm->id;
            $array['rank']		= (int)$rm->lv;
            $array['ut']	    = (int)$rm->unlock_type;
            $array['ui']		= (int)$rm->unlock_id;
            $array['uc']        = (int)$rm->unlock_icon;
            $array['des']		= $rm->description;

            $mapper[] = $array;
        }
        return $mapper;
    }

    public static function arrangeLevelUpColumns($lvup)
    {
        $mapper = array();
        foreach ($lvup as $lv) {
            $array = array();

            $array['id']		= (int)$lv->id;
            $array['lv']		= (int)$lv->level;
            $array['re']	    = (int)$lv->required_experience;
            $array['bi']		= (int)$lv->bonus_id;
            $array['cnt']       = (int)$lv->amount;
            $mapper[] = $array;
        }
        return $mapper;
    }

}
<?php
/**
 *  シーンデータダウンロード
 */
class DownloadSceneData extends BaseAction {
	// http://pad.localhost/api.php?action=download_scene_data&pid=1&sid=1
	const MEMCACHED_EXPIRE = 86400; // 24時間.

  	// #PADC#
  	const MAIL_RESPONSE = FALSE;
  	const ENCRYPT_RESPONSE = FALSE;
	public function action($params){
		$key = MasterCacheKey::getDownloadSceneData(DownloadMasterData::ID_SCENES);
		$value = apc_fetch($key);
		if(FALSE === $value) {
			$value = DownloadMasterData::find(DownloadMasterData::ID_SCENES)->gzip_data;
			apc_store($key, $value, static::MEMCACHED_EXPIRE + DownloadMasterData::add_apc_expire());
		}
		return $value;
	}

	/**
	 * 
	 * @param array Scene $scenes
	 */
	public static function arrangeSceneColumns($scenes) {
		$mapper = array();
		foreach ($scenes as $scene) {
			$array = array();
			$array['id']			= (int)$scene->id;
			$array['start_zpos']	= (int)$scene->start_zpos;
			$array['end_zpos']		= (int)$scene->end_zpos;
			$array['bg_filename']	= $scene->bg_filename;
			for($i=1;$i<=6;$i++)
			{
				$_dongeon_id	= sprintf("dungeon_id%d",$i);
				$_iconx			= sprintf("iconx%d",$i);
				$_icony			= sprintf("icony%d",$i);

				$array[$_dongeon_id]	= (int)$scene->$_dongeon_id;
				$array[$_iconx]			= (int)$scene->$_iconx;
				$array[$_icony]			= (int)$scene->$_icony;
			}

			$mapper[] = $array;
		}
		return $mapper;
	}

	/**
	 * 
	 * @param array ScenePart $sceneparts
	 */
	public static function arrangeScenePartColumns($sceneparts) {
		$mapper = array();
		foreach ($sceneparts as $scenepart) {
			$array = array();
			$array['id']		= (int)$scenepart->id;
			$array['filename']	= $scenepart->filename;
			$array['zpos']		= (int)$scenepart->zpos;
			$mapper[] = $array;
		}
		return $mapper;
	}
}

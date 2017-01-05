<?php
/**
 *  欠片データダウンロード
 */
class DownloadPieceData extends BaseAction {
	// http://pad.localhost/api.php?action=download_piece_data&pid=1&sid=1
	const MEMCACHED_EXPIRE = 86400; // 24時間.
  	// #PADC#
  	const MAIL_RESPONSE = FALSE;
  	const ENCRYPT_RESPONSE = FALSE;
	public function action($params){
		$key = MasterCacheKey::getDownloadPieceData(DownloadMasterData::ID_PIECES);
		$value = apc_fetch($key);
		if(FALSE === $value) {
			$value = DownloadMasterData::find(DownloadMasterData::ID_PIECES)->gzip_data;
			apc_store($key, $value, static::MEMCACHED_EXPIRE + DownloadMasterData::add_apc_expire());
		}
		return $value;
	}

	/**
	 * 
	 * @param array Piece $pieces
	 */
	public static function arrangeColumns($pieces) {
		$mapper = array();
		foreach ($pieces as $piece) {
			$array = array();

			$array['id']		= (int)$piece->id;
			$array['name']		= $piece->name;
			$array['category']	= (int)$piece->category;
			$array['type']		= (int)$piece->type;
			$array['cid']		= (int)$piece->cid;
			$array['gcnt']		= (int)$piece->gcnt;
			$array['attr']		= (int)$piece->attr;
			$array['sattr']		= (int)$piece->sattr;
			$array['mt']		= (int)$piece->mt;
			$array['mt2']		= (int)$piece->mt2;
			$array['rare']		= (int)$piece->rare;
			$array['mexp']		= (int)$piece->mexp;
			$array['eexp']		= (int)$piece->eexp;
			$array['scost']		= (int)$piece->scost;
			$array['tcost']		= (int)$piece->tcost;
			$array['sort_no']	= (int)$piece->sort_no;

			$mapper[] = $array;
		}
		return $mapper;
	}
}

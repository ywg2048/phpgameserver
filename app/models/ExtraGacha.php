<?php
/**
 * 追加ガチャ
 */
class ExtraGacha extends BaseMasterModel {
	const TABLE_NAME = "extra_gacha";
	const VER_KEY_GROUP = "ex_gacha";
	const MEMCACHED_EXPIRE = 86400; // 24時間.

	const TYPE_CHARGE = 0; // 有償ガチャ.
	const TYPE_FRIEND = 1; // 友情ポイントガチャ.

	protected static $columns = array(
		'id',
		'begin_at',
		'finish_at',
		'title',
		'message',
		// #PADC# ----------begin----------
		'message10',
		'footer_txt',
		// #PADC# ----------end----------
		'bs',
		'gacha_id',
		'gacha_type',
		'price',
		// #PADC# ----------begin----------
		'price10',
		'rare',
		'file',
		// #PADC# ----------end----------
		'color',
		'gtype',
		'area',
		// #PADC# ----------begin----------
		'gachadra_name',
		'gachabg_name',
		// #PADC# ----------end----------

	);

	public static $areas = array(
		'JP' => 1,
		'HT' => 2,
	);

	/**
	 * 追加ガチャメッセージを返す.
	*/
	public static function getMessage($area_id = null){
		$time = time();
		$messages = self::getAll();
		$res = array();
		if($messages){
			$areas = array(null, 0, 4);
			foreach($messages as $mes){
				if($time <= static::strToTime($mes->finish_at) && count($res) < 2){
					if (!empty($mes->area) && $areas[$mes->area] != $area_id) {
						continue;
					}
					$r = array();
					// #PADC# ----------begin----------
					$r['title'] = $mes->title;
					$r['msg'] = $mes->message;
					$r['msg10'] = $mes->message10;
					$r['footer'] = $mes->footer_txt;
					$r['start'] = strftime("%y%m%d%H%M%S", strtotime($mes->begin_at));
					$r['end'] = strftime("%y%m%d%H%M%S", strtotime($mes->finish_at));
					$r['price'] = (int)$mes->price;
					$r['price10'] = (int)$mes->price10;
					$r['price_type'] = (int)$mes->gacha_type;
					$r['grow'] = (int)$mes->id;
					$r['banner'] = $mes->file;
					$r['gacha_dra'] = $mes->gachadra_name;
					$r['gacha_bg'] = $mes->gachabg_name;
					// #PADC# ----------end----------
					$res[] = $r;
				}
			}
		}
		return $res;
	}

	// 現在有効な追加ガチャを返す.
	public static function getActiveExtraGacha() {
		$time = time();
		$objs = self::filterServiceArea(self::getAll());
		if($objs){
			foreach($objs as $obj){
				if(static::strToTime($obj->begin_at) <= $time && $time <= static::strToTime($obj->finish_at)){
					return $obj;
				}
			}
		}
		return false;
	}

	/**
	 * サービスエリアでフィルタをかける
	 */
	private static function filterServiceArea($objs) {
		if (defined('Env::SERVICE_AREA')) {
			$values = array();
			foreach ($objs as $obj) {
				if (empty($obj->area) || $obj->area == self::$areas[Env::SERVICE_AREA]) {
					$values[] = $obj;
				}
			}
			return $values;
		}
		return $objs;
	}


}

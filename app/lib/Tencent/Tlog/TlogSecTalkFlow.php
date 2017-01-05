<?php
class TlogSecTalkFlow extends TlogBase {
	const EVENT = 'SecTalkFlow';
	protected static $columns = array (
			'event',
			'GameSvrId',
			'dtEventTime',
			'GameAppID',
			'OpenID',
			'PlatID',
			'AreaID',
			'ZoneID',
			'RoleLevel',
			'UserIP',
			'ReceiverOpenID',
			'ReceiverRoleLevel',
			'ReceiverIP',
			'ChatType',
			'TitleContents',
			'ChatContents',
			'RoleName',
			'ReceiverName',
	);
	public static function generateMessage($params) {
		$datas = array (
				static::EVENT,
				static::getGameSvrId (), // GameSvrId
				static::makeTime (),
				$params ['appid'],
				$params ['OpenID'],
				$params ['PlatID'],
				$params ['AreaID'],
				static::getZoneId (),
				$params ['RoleLevel'],
				$params ['UserIP'],
				$params ['ReceiverOpenID'],
				$params ['ReceiverRoleLevel'],
				$params ['ReceiverIP'],
				$params ['ChatType'],
				$params ['TitleContents'],
				$params ['ChatContents'],
				$params ['RoleName'],
			    $params ['ReceiverName'],
		);
		return static::generateMessageFromArray ( $datas );
	}
}

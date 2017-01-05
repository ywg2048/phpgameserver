<?php
/**
 * Admin用:カレンダー
 */
class AdminViewCalendar extends AdminBaseAction
{
	// 指定の月を全日表示/今日分だけ表示
	const VIEW_TYPE_ALL		= 0;
	const VIEW_TYPE_TODAY	= 1;

	// 表示ON/OFF
	const VIEW_ON	= 0;
	const VIEW_OFF	= 1;

	// 各種イベント定義を定義
	const KEY_LIMITEDRANKING	= 'limitedranking';
	const KEY_LOGINMESSAGE		= 'loginmessage';
	const KEY_ALLUSERBONUS		= 'alluserbonus';
	const KEY_EXTRAGACHA		= 'extragacha';

	public function action($params)
	{
		$prm_year		= isset($params['p_year']) ? $params['p_year'] : 0;
		$prm_month		= isset($params['p_month']) ? $params['p_month'] : 0;
		$prm_bonus_type	= isset($params['p_bonus_type']) ? $params['p_bonus_type'] : 0;
		$prm_view_type	= isset($params['p_view_type']) ? $params['p_view_type'] : self::VIEW_TYPE_ALL;
		$prm_lim_rank	= isset($params['p_lim_rank']) ? $params['p_lim_rank'] : self::VIEW_ON;
		$prm_login_mes	= isset($params['p_login_mes']) ? $params['p_login_mes'] : self::VIEW_ON;
		$prm_alluserbonus	= isset($params['p_alluserbonus']) ? $params['p_alluserbonus'] : self::VIEW_ON;
		$prm_extragacha	= isset($params['p_extragacha']) ? $params['p_extragacha'] : self::VIEW_ON;

		$wdays			= array('Sun','Mon','Tue','Wed','Thu','Fri','Sat');
		$view_wdays		= array('日','月','火','水','木','金','土');
		$wdays_line		= array('sun_line','date_line','date_line','date_line','date_line','date_line','sat_line');
		$schedule_width = 1000;

		//----------------------------------------------------------------------
		// 表示フィルタ用項目
		//----------------------------------------------------------------------

		// 今日
		$date		= getdate();
		$now_year	= $date['year']; 
		$now_month	= $date['mon'];
		$now_day	= $date['mday'];
		
		$year	= $now_year;
		$month	= $now_month;

		// 指定の日付
		if($prm_view_type == self::VIEW_TYPE_ALL)
		{
			if($prm_year && $prm_month)
			{
				$year	= $prm_year;
				$month	= $prm_month;
			}
		}

		// 前月、次月、前年、次年を取得
		$last_year	= $year;
		$last_month	= $month - 1;
		$next_year	= $year;
		$next_month	= $month + 1;
		if($month == 12)
		{
			$next_year++;
			$next_month	= 1;
		}
		elseif($month == 1)
		{
			$last_year--;
			$last_month	= 12;
		}
		$before_year	= $year - 1;
		$after_year		= $year + 1;

		// 該当月の末日を取得
		$endday	= date("d",mktime(0,0,0,$next_month,0,$next_year));

		// 月初の曜日を取得
		$wday	= date("w",mktime(0,0,0,$month,1,$year));

		// 月初の曜日に合わせてカレンダーの曜日の基点を調整
		$cnt = 0;
		if($wday)
		{
			for($i=0;$i<$wday;$i++)
			{
				$cnt++;
			}
		}

		//--------------------------------------------------
		// 各種データの取得
		//--------------------------------------------------
		$limitedBonusTypes	= LimitedBonus::getLimiteBonusTypes();
		$convertText		= ConvertText::getConvertTextArrayByTextKey();
		$dungeonData		= $this->getArrayBySetKey(Dungeon::getAll());
		$dungeonFloorData	= $this->getArrayBySetKey(DungeonFloor::getAll());
		$cardData			= $this->getArrayBySetKey(Card::getAll());

		//--------------------------------------------------
		// フィルタリング用のプルダウン作成
		//--------------------------------------------------

		// LimitedBonus
		$filterLimitedBonusTypes = array();
		$filterLimitedBonusTypes[0] = '全表示';
		$filterLimitedBonusTypes = array_merge($filterLimitedBonusTypes,$limitedBonusTypes);
		$filterLimitedBonusTypes[-1] = '全非表示';
		$filterLink = $this->getFormSelectData($filterLimitedBonusTypes,$prm_bonus_type);

		// 全日分/今日のみ表示
		$viewTypes = array(
			self::VIEW_TYPE_ALL		=> '全日',
			self::VIEW_TYPE_TODAY	=> '今日のみ',
		);
		$viewFilterLink = $this->getFormRadioData($viewTypes,$prm_view_type,'p_view_type');

		// 表示設定
		$viewTypes = array(
			self::VIEW_ON	=> '表示',
			self::VIEW_OFF	=> '非表示',
		);
		// リミテッドランキングの表示設定
		$viewLimitedRankingFilterLink = $this->getFormRadioData($viewTypes,$prm_lim_rank,'p_lim_rank');
		// ログインメッセージの表示設定
		$viewLoginMessageFilterLink = $this->getFormRadioData($viewTypes,$prm_login_mes,'p_login_mes');
		// 全ユーザボーナスの表示設定
		$viewAllUserBonusFilterLink = $this->getFormRadioData($viewTypes,$prm_alluserbonus,'p_alluserbonus');
		// 追加ガチャの表示設定
		$viewExtraGachaFilterLink = $this->getFormRadioData($viewTypes,$prm_extragacha,'p_extragacha');

		//----------------------------------------------------------------------
		// 各種データをセット
		//----------------------------------------------------------------------
		$data = array();
		
		//--------------------------------------------------
		// limitedbonusを各日にセット
		//--------------------------------------------------
		$limitedBonusData = LimitedBonus::findAllBy(array());
		foreach($limitedBonusData as $_limitedBonusData)
		{
			$bonusType	= $_limitedBonusData->bonus_type;

			// ボーナスタイプでのフィルタリング
			if($prm_bonus_type)
			{
				if($prm_bonus_type != $bonusType)
				{
					continue;
				}
			}

			// limitedbonusの設定内容を整形して取得
			$bonusInfo	= $this->getBonusInfo($_limitedBonusData,$limitedBonusTypes,$convertText,$cardData,$dungeonData,$dungeonFloorData);
			$startData	= $this->getKeyAndDate($_limitedBonusData->begin_at);
			$endData	= $this->getKeyAndDate($_limitedBonusData->finish_at);

			// 期間チェックして日毎にセット
			$data = $this->setEventData($data,$startData,$endData,$year,$month,$next_year,$next_month,$bonusInfo,$bonusType);
		}

		//--------------------------------------------------
		// limited_rankingを各日にセット
		//--------------------------------------------------
		if($prm_lim_rank == self::VIEW_ON)
		{
			$rankingDungeonData			= $this->getArrayBySetKey(RankingDungeon::getAll());
			$rankingDungeonFloorData	= $this->getArrayBySetKey(RankingDungeonFloor::getAll());
			$limitedRankingData			= LimitedRanking::getAll();
			foreach($limitedRankingData as $_limitedRankingData)
			{
				$_rankingDungeonId		= $_limitedRankingData->ranking_dungeon_id;
				$_rankingDungeonFloorId	= ($_rankingDungeonId * 1000) + $_limitedRankingData->ranking_floor_id;
	
				$bonusInfo	= '<td>' . $_limitedRankingData->id . '</td>'
							. '<td>' . $_limitedRankingData->ranking_id . '</td>'
							. '<td>' . $this->getValueFromArrayWithJpText($rankingDungeonData,$_rankingDungeonId,$convertText,'ダンジョン') . '</td>'
							. '<td>' . $this->getValueFromArrayWithJpText($rankingDungeonFloorData,$_rankingDungeonFloorId,$convertText,'ダンジョンフロア') . '</td>';
	
				$startData	= $this->getKeyAndDate($_limitedRankingData->start_time);
				$endData	= $this->getKeyAndDate($_limitedRankingData->end_time);

				// 期間チェックして日毎にセット
				$data = $this->setEventData($data,$startData,$endData,$year,$month,$next_year,$next_month,$bonusInfo,self::KEY_LIMITEDRANKING);
			}
		}

		//--------------------------------------------------
		// ログインメッセージを各日にセット
		//--------------------------------------------------
		if($prm_login_mes == self::VIEW_ON)
		{
			$loginMessageData = LoginMessage::getAll();
			foreach($loginMessageData as $_loginMessage)
			{
				$startData		= $this->getKeyAndDate($_loginMessage->begin_at);
				$endData		= $this->getKeyAndDate($_loginMessage->finish_at);

				$_tdlist		= '<td>' . $_loginMessage->id . '</td>'
								. '<td>' . $_loginMessage->message . '</td>'
								. '<td>' . $_loginMessage->one_message . '</td>';

				// 期間チェックして日毎にセット
				$data = $this->setEventData($data,$startData,$endData,$year,$month,$next_year,$next_month,$_tdlist,self::KEY_LOGINMESSAGE);
			}
		}

		//--------------------------------------------------
		// 全ユーザボーナスを各日にセット
		//--------------------------------------------------
		if($prm_alluserbonus == self::VIEW_ON)
		{
			$tmpData = AllUserBonus::getAll();
			foreach($tmpData as $_tmpData)
			{
				$startData		= $this->getKeyAndDate($_tmpData->begin_at);
				$endData		= $this->getKeyAndDate($_tmpData->finish_at);

				$_tdlist		= '<td>' . $_tmpData->id . '</td>'
								. '<td>' . $_tmpData->device_type . '</td>'
								. '<td>' . $_tmpData->bonus_id . '</td>'
								. '<td>' . $_tmpData->amount . '</td>'
								. '<td>' . $_tmpData->piece_id . '</td>'
								. '<td>' . $_tmpData->title . '</td>'
								. '<td>' . $_tmpData->message . '</td>';

				// 期間チェックして日毎にセット
				$data = $this->setEventData($data,$startData,$endData,$year,$month,$next_year,$next_month,$_tdlist,self::KEY_ALLUSERBONUS);
			}
		}

		//--------------------------------------------------
		// extra_gachasを各日にセット
		//--------------------------------------------------
		if($prm_extragacha == self::VIEW_ON)
		{
			$tmpData = ExtraGacha::getAll();
			foreach($tmpData as $_tmpData)
			{
				$startData		= $this->getKeyAndDate($_tmpData->begin_at);
				$endData		= $this->getKeyAndDate($_tmpData->finish_at);

				$_tdlist		= '<td>' . $_tmpData->id . '</td>'
								. '<td>' . $_tmpData->title . '</td>'
								. '<td>' . $_tmpData->message . '</td>'
								. '<td>' . $_tmpData->message10 . '</td>'
								. '<td>' . $_tmpData->footer_txt . '</td>';
								
				// 期間チェックして日毎にセット
				$data = $this->setEventData($data,$startData,$endData,$year,$month,$next_year,$next_month,$_tdlist,self::KEY_EXTRAGACHA);
			}
		}

		//--------------------------------------------------
		// 表示内容別のカラム調整
		//--------------------------------------------------
		$dafaultColumns			= array('開始日時','終了日時','ID');
		$dafaultColumnList		= $this->getColumnListByArray($dafaultColumns);
		$dungeonColumns			= array('開始日時','終了日時','ID','ダンジョン');
		$dungeonColumnList		= $this->getColumnListByArray($dungeonColumns);
		$gachaColumns			= array('開始日時','終了日時','ID','ガチャID','メッセージ');
		$gachaColumnList		= $this->getColumnListByArray($gachaColumns);
		$notoriousColumns		= array('開始日時','終了日時','ID','ダンジョン','ダンジョンフロア','モンスター');
		$notoriousColumnList	= $this->getColumnListByArray($notoriousColumns);
		
		$limitedBonusColumnList = array(
			LimitedBonus::BONUS_TYPE_FLOOR_NOTORIOUS	=> $notoriousColumnList,
			LimitedBonus::BONUS_TYPE_DUNG_OPEN			=> $dungeonColumnList,
			LimitedBonus::BONUS_TYPE_DUNG_EGGUP			=> $dungeonColumnList,
			LimitedBonus::BONUS_TYPE_DUNG_EXPUP			=> $dungeonColumnList,
			LimitedBonus::BONUS_TYPE_DUNG_COINUP		=> $dungeonColumnList,
			LimitedBonus::BONUS_TYPE_DUNG_STAMINA		=> $dungeonColumnList,
			LimitedBonus::BONUS_TYPE_FRIEND_GACHA		=> $gachaColumnList,
			LimitedBonus::BONUS_TYPE_CHARGE_GACHA		=> $gachaColumnList,
			LimitedBonus::BONUS_TYPE_PREMIUM_GACHA		=> $gachaColumnList,
		);

		$rankingDungeonColumns		= array('開始日時','終了日時','ID','ランキングID','ダンジョン','ダンジョンフロア');
		$rankingDungeonColumnList	= $this->getColumnListByArray($rankingDungeonColumns);
		$loginMessageColumns		= array('開始日時','終了日時','ID','メッセージ','一言メッセージ');
		$loginMessageColumnList		= $this->getColumnListByArray($loginMessageColumns);
		$allUserBonusColumns		= array('開始日時','終了日時','ID','デバイスタイプ','ボーナスID','個数','欠片ID','タイトル','本文');
		$allUserBonusColumnList		= $this->getColumnListByArray($allUserBonusColumns);
		$extraGachaColumns			= array('開始日時','終了日時','ID','タイトル','メッセージ','10連メッセージ','フッターテキスト');
		$extraGachaColumnList		= $this->getColumnListByArray($extraGachaColumns);
		
		$eventData = array(
			array(
				'name'			=> 'ランキングダンジョン',
				'key'			=> self::KEY_LIMITEDRANKING,
				'columnlist'	=> $rankingDungeonColumnList,
				'cssclass'		=> 'ranking_dungeon_line'
			),
			array(
				'name'			=> 'ログインメッセージ',
				'key'			=> self::KEY_LOGINMESSAGE,
				'columnlist'	=> $loginMessageColumnList,
				'cssclass'		=> 'login_message_line'
			),
			array(
				'name'			=> '全ユーザボーナス',
				'key'			=> self::KEY_ALLUSERBONUS,
				'columnlist'	=> $allUserBonusColumnList,
				'cssclass'		=> 'alluserbonus_line'
			),
			array(
				'name'			=> '追加ガチャ',
				'key'			=> self::KEY_EXTRAGACHA,
				'columnlist'	=> $extraGachaColumnList,
				'cssclass'		=> 'extragacha_line'
			),
		);

		//--------------------------------------------------
		// 日毎の表示整形
		//--------------------------------------------------
		$table = '';
		for($i=1;$i<=$endday;$i++)
		{
			$_keydate	= sprintf("%04d%02d%02d",$year,$month,$i);
			$classname	= $wdays_line[$cnt];

			// 当日のみ表示する場合
			if($prm_view_type == self::VIEW_TYPE_TODAY)
			{
				// 当日
				if($month == $now_month && $i == $now_day)
				{
					$classname = 'today_line';
				}
				else
				{
					continue;
				}
			}

			//------------------------------
			// ボーナスタイプ別に表示
			//------------------------------
			$value = '';
			foreach($limitedBonusTypes as $_bonusType => $_bonusTypeName)
			{
				$_bonusKey = $_bonusType . '-' . $_keydate;
				$_limitedBonusColumnList = $dafaultColumnList;
				if(isset($limitedBonusColumnList[$_bonusType]))
				{
					$_limitedBonusColumnList = $limitedBonusColumnList[$_bonusType];
				}
				$value .= $this->getEventDataStr($data,$_bonusKey,$_bonusTypeName,$_limitedBonusColumnList,'bonus_type_line');
			}

			//------------------------------
			// 各種イベントデータをチェックしてセット
			//------------------------------
			foreach($eventData as $_eventData)
			{
				$_bonusKey = $_eventData['key'] . '-' . $_keydate;
				$value .= $this->getEventDataStr($data,$_bonusKey,$_eventData['name'],$_eventData['columnlist'],$_eventData['cssclass']);
			}

			//------------------------------
			// 日別にセット
			//------------------------------
			$table	.= '<tr align="center">'
					. '<td class="'.$classname.' base_td" width="50">' . $i . '</td>'
					. '<td class="base_td" align="left" width="'.$schedule_width.'">' . $value . '</td>'
					. '</tr>' . "\n";

			$cnt++;
			if($cnt >= 7)
			{
				$cnt = 0;
			}
		}

		//----------------------------------------------------------------------
		// 説明
		//----------------------------------------------------------------------
		$explain	= '';

		//--------------------------------------------------
		// 前後リンク
		//--------------------------------------------------
		$view_this_month = sprintf("%04d年%02d月",$year,$month);
		$thisurl	= 'api_admin.php?request_type=112&action=admin_view_calendar&inputform=0'
					. '&p_bonus_type=' . $prm_bonus_type
					. '&p_view_type=' . $prm_view_type
					. '&p_lim_rank=' . $prm_lim_rank
					. '&p_login_mes=' . $prm_login_mes
					. '&p_alluserbonus=' . $prm_alluserbonus
					. '&p_extragacha=' . $prm_extragacha
					. '';
		$monthLink	= '<div class="month_link">'
					. "<a href=\"$thisurl&p_year=$before_year&p_month=$month\">≪</a>"
					. "<a href=\"$thisurl&p_year=$last_year&p_month=$last_month\">＜</a>"
					. '<span style="font-weight:bold;">' . $view_this_month . '</span>'
					. "<a href=\"$thisurl&p_year=$next_year&p_month=$next_month\" style=\"color:#666666;\">＞</a>"
					. "<a href=\"$thisurl&p_year=$after_year&p_month=$month\" style=\"color:#666666;\">≫</a>"
					. '</div>';

		//--------------------------------------------------
		// フィルタリング用プルダウン
		//--------------------------------------------------
		$filterForm	= '<table border="1">'
						. '<form action="' . $thisurl . '" method="get">'
							. '<tr>' . '<th class="common_td common">ボーナスタイプ</th>' . '<td><select name="p_bonus_type">' . $filterLink . '</select></td>' . '</tr>'
							. '<tr>' . '<th class="common_td common">全日/今日のみ</th>' . '<td>' . $viewFilterLink . '</td>' . '</tr>'
							. '<tr>' . '<th class="common_td common">ランキングダンジョン</th>'. '<td>' . $viewLimitedRankingFilterLink . '</td>' . '</tr>'
							. '<tr>' . '<th class="common_td common">ログインメッセージ</th>'. '<td>' . $viewLoginMessageFilterLink . '</td>' . '</tr>'
							. '<tr>' . '<th class="common_td common">全ユーザボーナス</th>'. '<td>' . $viewAllUserBonusFilterLink . '</td>' . '</tr>'
							. '<tr>' . '<th class="common_td common">追加ガチャ</th>'. '<td>' . $viewExtraGachaFilterLink . '</td>' . '</tr>'
							. '<tr>'
								. '<td>'
								. '<input type="submit" value="絞り込み" />'
								. '<input type="hidden" name="request_type" value="112" />'
								. '<input type="hidden" name="action" value="admin_view_calendar" />'
								. '<input type="hidden" name="inputform" value="0" />'
								. '<input type="hidden" name="p_year" value="' . $year . '" />'
								. '<input type="hidden" name="p_month" value="' . $month . '" />'
								. '</td>'
							. '</tr>'
						. '</form>'
					. '</table>';

		//----------------------------------------------------------------------
		// 上部表示
		//----------------------------------------------------------------------
		$table_list = '';
		$table_list	.= '<table>'
					. '<tr><td>' . $monthLink . '</td><td rowspan="2">' . $explain . '</td></tr>'
					. '<tr><td>' . $filterForm . '</td></tr>'
					. '</table>'
					. '<hr />';

		//--------------------------------------------------
		// 一覧表示
		//--------------------------------------------------
		$table_list .= '<div class="month_link">'
					. '<table border="1">'
					. '<tr>'
					. '<th class="common_td base_td common">日</td>'
					. '<th class="common_td base_td common">スケジュール</td>'
					. '</tr>'
					. $table
					. '</table>'
					. '</div>';

		// レスポンスにセット
		$result = array(
			'format' => 'array',
			$view_this_month => array('format' => 'html', $table_list),
		);

		return json_encode($result);
	}

	/**
	 * 日付にセットするためのキーと表示用の日付を取得
	 * @param unknown $date
	 * @return multitype:string
	 */
	function getKeyAndDate($date)
	{
		$result = array(
			'key'		=> date("Ymd",strtotime($date)),
			'date'		=> date("Y-m-d",strtotime($date)),
			'datetime'	=> date("Y/m/d H:i:s",strtotime($date)),
		);
		return $result;
	}

	/**
	 * ボーナス情報を取得
	 * @param array $LimitedBonusTypes
	 * @param string $bonusType
	 * @return string
	 */
	function getBonusInfo($limitedBonus,$limitedBonusTypes,$convertText,$cardData,$dungeonData,$dungeonFloorData)
	{
		$id				= $limitedBonus->id;
		$bonusType		= $limitedBonus->bonus_type;
		$dungeonId		= $limitedBonus->dungeon_id;
		$dungeonFloorId	= $limitedBonus->dungeon_floor_id;
		$targetId		= $limitedBonus->target_id;
		$amount			= $limitedBonus->amount;
		$message		= $limitedBonus->message;

		$td_header	= '<td class="default_td">';
		$td_footer	= '</td>';

		// ID
		$info	= $td_header . $id . $td_footer;

		// ボーナスタイプ別の表示調整
		if(isset($limitedBonusTypes[$bonusType]))
		{
			$dungeonBonuses = array(
				LimitedBonus::BONUS_TYPE_DUNG_OPEN,
				LimitedBonus::BONUS_TYPE_DUNG_EGGUP,
				LimitedBonus::BONUS_TYPE_DUNG_EXPUP,
				LimitedBonus::BONUS_TYPE_DUNG_COINUP,
				LimitedBonus::BONUS_TYPE_DUNG_STAMINA,
			);
			$gachaBonuses = array(
				LimitedBonus::BONUS_TYPE_FRIEND_GACHA,
				LimitedBonus::BONUS_TYPE_CHARGE_GACHA,
				LimitedBonus::BONUS_TYPE_PREMIUM_GACHA,
			);

			// ダンジョン関連
			if(in_array($bonusType, $dungeonBonuses))
			{
				$info .= $td_header . $this->getValueFromArrayWithJpText($dungeonData,$dungeonId,$convertText,'ダンジョン') . $td_footer;
			}
			// ノートリアス
			elseif($bonusType == LimitedBonus::BONUS_TYPE_FLOOR_NOTORIOUS)
			{
				$info .= $td_header . $this->getValueFromArrayWithJpText($dungeonData,$dungeonId,$convertText,'ダンジョン') . $td_footer;
				$info .= $td_header . $this->getValueFromArrayWithJpText($dungeonFloorData,$dungeonFloorId,$convertText,'ダンジョンフロア') . $td_footer;
				$info .= $td_header . $this->getValueFromArrayWithJpText($cardData,$targetId,$convertText,'モンスター') . $td_footer;
			}
			// ガチャ関連
			elseif(in_array($bonusType,$gachaBonuses))
			{
				$info	.= $td_header . $targetId . $td_footer
						. $td_header . $message . $td_footer;
			}
			// それ以外
			else
			{
//				$info .= $td_header . $limitedBonusTypes[$bonusType] . $td_footer;
			}
		}

		return $info;
	}

	/**
	 * 指定の配列から指定のIDのデータの名称を取得
	 * @param array $data
	 * @param int $id
	 * @param array $convertText
	 * @param string $keyname
	 * @return string
	 */
	function getValueFromArrayWithJpText($data,$id,$convertText,$keyname)
	{
		$value = '<div class="caution">存在しない'. $keyname .'が設定されています。</div>';
		if(isset($data[$id]))
		{
			$value = $this->getNameWithJpName($data[$id]->name,$convertText);
		}
		return $value;
	}

	/**
	 * 指定の名称に紐付く、翻訳データがあれば文字列に追加して返す
	 * @param string $name
	 * @param array $convertText
	 * @return string
	 */
	function getNameWithJpName($name,$convertText)
	{
		$textJP = self::getJpTextByText($convertText,$name);
		if($textJP)
		{
			$name = $name . '<span class="text_jp">（'.$textJP.'）</span>';
		}
		return $name;
	}

	/**
	 * 指定のキーを配列のキーとしてobjectをセット
	 * @param array $data
	 * @param string $key
	 * @return multitype:unknown
	 */
	function getArrayBySetKey($data,$key='id')
	{
		$returnData = array();
		foreach($data as $_data)
		{
			$returnData[$_data->$key] = $_data;
		}
		return $returnData;
	}

	/**
	 * 指定のキーの配列に文字列を追加する
	 * @param array $array
	 * @param string $key
	 * @param string $value
	 * @return array
	 */
	function setArrayValueByKey($array,$key,$value)
	{
		$value = '<div class="common">' . $value . '</div>';

		if(isset($array[$key]))
		{
			$array[$key] .= $value;
		}
		else
		{
			$array[$key] = $value;
		}
		return $array;
	}

	/**
	 * 指定のタイプをプルダウンフォーマットで整形
	 * @param array $types
	 * @param int $type
	 * @return string
	 */
	function getFormSelectData($types,$type)
	{
		$list = '';
		foreach($types as $_type => $_typeName)
		{
			$selected = '';
			if($type == $_type)
			{
				$selected = ' selected';
			}
			$list .= '<option value="' . $_type . '"' . $selected . '>' . $_typeName . '</option>';
		}
		return $list;
	}

	/**
	 * 指定のタイプをラジオボタンフォーマットで整形
	 * @param array $types
	 * @param int $type
	 * @return string
	 */
	function getFormRadioData($types,$type,$name)
	{
		$list = '';
		foreach($types as $_type => $_typeName)
		{
			$selected = '';
			if($type == $_type)
			{
				$selected = ' checked';
			}
			$list .= '<input type="radio" name="' . $name . '" value="' . $_type . '"' . $selected . '>' . $_typeName;
		}
		return $list;
	}

	/**
	 * 表示する時間の調整
	 * @param array $startData
	 * @param array $endData
	 * @param int $type
	 * @return string
	 */
	function getTimeStr($startData,$endData,$date)
	{
		$startmark	= 'default_mark';
		$endtmark	= 'default_mark';
		if($startData['key'] == $date)
		{
			$startmark	= 'start_mark';
		}
		if($endData['key'] == $date)
		{
			$endtmark	= 'end_mark';
		}

		$startdate	= '<span class="' . $startmark . '">' . $startData['datetime'] . '</span>';
		$enddate	= '<span class="' . $endtmark . '">' . $endData['datetime'] . '</span>';

		$result = array(
			'startdate' => $startdate,
			'enddate' => $enddate,
		);
		
		return $result;
	}

	/**
	 * 配列をtableのthにセット
	 * @param array $array
	 * @return string
	 */
	function getColumnListByArray($array)
	{
		$list = '';
		foreach($array as $_value)
		{
			$list .= '<th class="common_td common">' . $_value . '</th>';
		}
		return $list;
	}

	/**
	 * 設定期間（日数）を取得
	 * @param array $startData
	 * @param array $endData
	 */
	function getDiffDay($startData,$endData)
	{
		return (strtotime($endData['date']) - strtotime($startData['date'])) / (3600 * 24);
	}

	/**
	 * イベントデータを日毎にセット
	 * @param array $data
	 * @param array $startData
	 * @param array $endData
	 * @param int $year
	 * @param int $month
	 * @param int $next_year
	 * @param int $next_month
	 * @param string $list
	 * @param string $keyName
	 */
	function setEventData($data,$startData,$endData,$year,$month,$next_year,$next_month,$list,$keyName)
	{
		$diff_day = $this->getDiffDay($startData,$endData);
		for($i=0;$i<=$diff_day;$i++)
		{
			$_date = date('Ymd',strtotime("+ $i days",strtotime($startData['datetime'])));

			// 前月分はスルー
			if($_date < sprintf("%04d%02d%02d",$year,$month,1))
			{
				continue;
			}
			// 次月入ったら終了
			if($_date >= sprintf("%04d%02d%02d",$next_year,$next_month,1))
			{
				break;
			}

			$timedata	= $this->getTimeStr($startData,$endData,$_date);
			$value		= '<tr>'
						. '<td>' . $timedata['startdate'] . '</td>'
						. '<td>' . $timedata['enddate'] . '</td>'
						. $list
						. '</tr>';
			$bonusKey	= $keyName . '-' . $_date;
			$data		= $this->setArrayValueByKey($data,$bonusKey,$value);
		}
	
		return $data;
	}

	function getEventDataStr($data,$key,$title,$columnlist,$cssClassName)
	{
		$value = '';
		if(isset($data[$key]))
		{
			$value	.= '<div class="' . $cssClassName . '">■' . $title . '</div>'
					. '<div class="bonus_info">'
					. '<table border="1">'
					. '<tr>' . $columnlist . '</tr>'
					. $data[$key]
					. '</table>'
					. '</div>';
		}
		return $value;
	}
}
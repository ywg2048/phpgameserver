<?php
/**
 * Admin用:DBデータ編集フォーム
 */
class AdminEditShareData extends AdminBaseAction
{
	const ADMIN_UPDATE_ACTION_NAME = "admin_update_share_data";
	const ADMIN_INSERT_ACTION_NAME = "admin_insert_share_data";
	
	public function action($params)
	{
		$model = isset($params['model']) ? $params['model'] : '';
		
		$result = array(
				'format'	=> 'array',
				$model	=> array('format' => 'html', self::getFormList($params)),
		);
		return json_encode($result);
	}
	
	static private function getFormList($params)
	{
		global $logger;
		
		if (!isset($params['base_id']) && !isset($params['add_id'])) {
			throw new PadException(RespCode::UNKNOWN_ERROR, "data id is none.");
		}
		
		$base_id = isset($params['base_id']) ? $params['base_id'] : $params['add_id'];
		$model = isset($params['model']) ? $params['model'] : '';
		if (!$model) {
			throw new PadException(RespCode::UNKNOWN_ERROR, "model name is none.");
		}
		
		$is_create = false;
		$columns = $model::getColumns();
		$data = $model::find($base_id);
		if (!$data) {
			// データが見つからなかったら新規追加
			$data = new $model();
			foreach($columns as $_column) {
				$data->$_column = '';
			}
			$data->id = $base_id;
			
			$is_create = true;
		}
		
		$formList	= '<form action="'.REQUEST_URL_ADMIN.'" method="post">';
		if ($is_create) {
			$formList	.= '<input type="hidden" name="action" value="'.self::ADMIN_INSERT_ACTION_NAME.'" />';
		}
		else {
			$formList	.= '<input type="hidden" name="action" value="'.self::ADMIN_UPDATE_ACTION_NAME.'" />';
		}
		$formList	.= '<input type="hidden" name="base_id" value="'. $base_id .'" />';
		$formList	.= '<input type="hidden" name="model" value="'. $model .'" />';
		if (isset($params['from_action'])) {
			$formList	.= '<input type="hidden" name="from_action" value="'. $params['from_action'] .'" />';
		}
		if (isset($params['from_request_type'])) {
			$formList	.= '<input type="hidden" name="from_request_type" value="'. $params['from_request_type'] .'" />';
		}
		
		
		$formList	.= '<table border="1" style="margin:10px 5px 10px 10px;">';
		$formList	.= '<tr style="background:#ffffcc;">'
					. '<th>項目</th>'
					. '<th>詳細</th>'
					. '</tr>';
		
		$td		= '<td>';
		$tdend	= '</td>';
		
		foreach($data as $_key => $_value) {
				
			//Padc_Log_Log::writeLog('key:'.$_key.' value:'.$_value, Zend_Log::DEBUG);
			$temp	= $_value;
			if (in_array($_key, $columns)) {
				
				// 入力フォーム
				$temp	= '<input type="text" name="' . $_key .'" value="' . htmlspecialchars($_value) . '" style="width:100%"></input>';
				
				switch ($_key) {
					case 'id':
						$temp	= $_value;
						break;
						
					case 'name':
						// マスターデータの名前は編集できないように
						if ($model == 'Version' && !$is_create) {
							$temp	= $_value;
						}
						break;
						
					case 'card_id':
					case 'cid':
						if (!isset($cardNames)) {
							$cardNames = self::getListNames('Card');
						}
						$temp	= self::getSelectForm($_key, $_value, $cardNames);
						break;
						
					case 'piece_id':
					case 'drop_card_id1':
					case 'drop_card_id2':
					case 'drop_card_id3':
					case 'drop_card_id4':
					case 'gup_piece_id':
						if (!isset($pieceNames)) {
							$pieceNames = self::getListNames('Piece');
						}
						$temp	= self::getSelectForm($_key, $_value, $pieceNames);
						break;
						
					case 'dungeon_id':
					case 'dungeon_id1':
					case 'dungeon_id2':
					case 'dungeon_id3':
					case 'dungeon_id4':
					case 'dungeon_id5':
					case 'dungeon_id6':
					case 'ranking_dungeon_id':
						if (!isset($dungeonNames)) {
							if(preg_match('/Ranking/',$model)) {
								$dungeonNames = self::getListNames('RankingDungeon');
							}
							else {
								$dungeonNames = self::getListNames('Dungeon');
							}
						}
						$temp	= self::getSelectForm($_key, $_value, $dungeonNames);
						break;
						
					case 'dungeon_floor_id':
					case 'prev_dungeon_floor_id':
					case 'ranking_floor_id':
						if (!isset($dungeonFloorNames)) {
							if(preg_match('/Ranking/',$model)) {
								$dungeonFloorNames = self::getListNames('RankingDungeonFloor');
							}
							else {
								$dungeonFloorNames = self::getListNames('DungeonFloor');
							}
						}
						$temp	= self::getSelectForm($_key, $_value, $dungeonFloorNames);
						break;
						
					case 'skill':
						if (!isset($skillNames)) {
							$skillNames = self::getListNames('Skill');
						}
						$temp	= self::getSelectForm($_key, $_value, $skillNames);
						break;
						
					case 'wave_id':
						if (!isset($waveDatas)) {
							if(preg_match('/Ranking/',$model)) {
								$wave = new RankingWave();
							}
							else {
								$wave = new Wave();
							}
							$waveDatas = self::getDatasByDao($wave,'id');
						}
						$temp	= self::getSelectForm($_key, $_value, $waveDatas);
						break;
						
					case 'attr':
					case 'sattr':
						if($model == 'Piece' || preg_match('/Card/',$model)) {
							if (!isset($attrNames)) {
								$attrNames = self::getAttrNames();
							}
							$temp	= self::getSelectForm($_key, $_value, $attrNames);
						}
						break;
						
					case 'mt':
					case 'mt2':
						if (!isset($mtNames)) {
							$mtNames = self::getMonsterTypeNames();
						}
						$temp	= self::getSelectForm($_key, $_value, $mtNames);
						break;
					
					case 'size':
						if (!isset($sizeNames)) {
							$sizeNames = self::getMonsterSizeNames();
						}
						$temp	= self::getSelectForm($_key, $_value, $sizeNames);
						break;
						
					case 'dtype':
						if (!isset($dtypeNames)) {
							$dtypeNames = self::getDungeonTypeNames();
						}
						$temp	= self::getSelectForm($_key, $_value, $dtypeNames);
						break;
						
					case 'dkind':
						if (!isset($dkindNames)) {
							$dkindNames = self::getDungeonKindNames();
						}
						$temp	= self::getSelectForm($_key, $_value, $dkindNames);
						break;
						
					case 'fr':
						if (!isset($ruleTypes)) {
							$ruleTypes = self::getRuleTypes();
						}
						$temp	= self::getSelectForm($_key, $_value, $ruleTypes);
						break;
						
					case 'type':
						if($model == 'Piece') {
							if (!isset($pieceTypes)) {
								$pieceTypes = self::getPieceTypes();
							}
							$temp	= self::getSelectForm($_key, $_value, $pieceTypes);
						}
						break;
						
					case 'bonus_id':
					case 'award_id':
						if (!isset($bonusIdNames)) {
							$bonusIdNames = self::addUndefined(self::getBonusIdNames());
						}
						$temp	= self::getSelectForm($_key, $_value, $bonusIdNames);
						break;
						
					case 'bonus_type':
						if(preg_match('/LimitedBonus/',$model))
						{
							if (!isset($limitedBonusTypes)) {
								$limitedBonusTypes  = self::addUndefined(LimitedBonus::getLimiteBonusTypes());
							}
							$temp	= self::getSelectForm($_key, $_value, $limitedBonusTypes);
						}
						break;
						
					case 'tab_category':
						if($model == 'Mission') {
							if (!isset($missionTabCategories)) {
								$missionTabCategories = self::getMissionTabCategiries();
							}
							$temp	= self::getSelectForm($_key, $_value, $missionTabCategories);
						}
						break;
						
					case 'condition_type':
						if($model == 'Mission') {
							if (!isset($missionConditionTypes)) {
								$missionConditionTypes = self::getMissionConditionTypes();
							}
							$temp	= self::getSelectForm($_key, $_value, $missionConditionTypes);
						}
						break;
							
					case 'mission_type':
						if($model == 'Mission') {
							if (!isset($missionTypes)) {
								$missionTypes = self::getMissionTypes();
							}
							$temp	= self::getSelectForm($_key, $_value, $missionTypes);
						}
						break;
						
					case 'transition_id':
						if($model == 'Mission') {
							if (!isset($missionTransitionIds)) {
								$missionTransitionIds = self::getMissionTransitionIds();
							}
							$temp	= self::getSelectForm($_key, $_value, $missionTransitionIds);
						}
						break;
						
						
					default:
						break;
				}
				
			}
			
			$formList	.= '<tr>'
			. $td . $_key . $tdend
			. $td . $temp . $tdend
			. '</tr>';
				
		}
		
		if ($is_create) {
			// 追加ボタン
			$formList	.= '<tr><td><button type="submit"/>追加</button></td></tr>';
		}
		else {
			// 更新ボタン
			$formList	.= '<tr><td><button type="submit"/>更新</button></td></tr>';
		}
		
		$formList	.= '</table>';
		$formList	.= '</form>';
		
		return $formList;
	}
	
	static private function getSelectForm($_key, $_value, Array $names) {
		$form	= '<select name="' . $_key .'" style="width:100%">';
		foreach($names as $_id => $_name) {
			$label = $_id . '：【' . $_name . '】';
			if ($_value == $_id) {
				$form .= '<option value="'.$_id.'" selected>'.$label.'</option>';
			}
			else {
				$form .= '<option value="'.$_id.'">'.$label.'</option>';
			}
		}
		$form	.= '</select>';
		return $form;
	}
	
}

<?php

/**
 * Admin用:マスターDLデータ作成
 */
class AdminSetMasterDownloadData extends AdminBaseAction {

    public function action($params) {
        $pass = isset($params['pass']) ? $params['pass'] : '';
        $targetTables = isset($params['target_tables']) ? $params['target_tables'] : array();

        // パスワードチェック
        if (!($pass && $pass == 'padc')) {
            $result = array(
                'result' => 'password is not correct!',
            );
            return json_encode($result);
        }

        $return = array();

        // どのデータも選択されていなかったらエラー終了
        if (1 > count($targetTables)) {
            $return = array(
                'res' => 'target un checked',
            );
            return json_encode($return);
        }

        //====================================================================================================
        // Cardデータ
        //====================================================================================================
        if (in_array(DownloadMasterData::ID_CARDS_VER4, $targetTables)) {
            $gzipdata = $this->getGzipMasterData(DownloadMasterData::ID_CARDS_VER4);
            $result = $this->addDownloadMasterData(DownloadMasterData::ID_CARDS_VER4, $gzipdata);
            if ($result) {
                $return[] = array(
                    DownloadMasterData::ID_CARDS_VER4,
                    'card data set',
                );
            }
        }

        //====================================================================================================
        // Dungeonデータ
        //====================================================================================================
        if (in_array(DownloadMasterData::ID_DUNGEONS_VER2, $targetTables)) {
            $gzipdata = $this->getGzipMasterData(DownloadMasterData::ID_DUNGEONS_VER2);
            $result = $this->addDownloadMasterData(DownloadMasterData::ID_DUNGEONS_VER2, $gzipdata);
            if ($result) {
                $return[] = array(
                    DownloadMasterData::ID_DUNGEONS_VER2,
                    'dungeon data set',
                );
            }
        }

        //====================================================================================================
        // Skillデータ
        //====================================================================================================
        if (in_array(DownloadMasterData::ID_SKILLS, $targetTables)) {
            $gzipdata = $this->getGzipMasterData(DownloadMasterData::ID_SKILLS);
            $result = $this->addDownloadMasterData(DownloadMasterData::ID_SKILLS, $gzipdata);
            if ($result) {
                $return[] = array(
                    DownloadMasterData::ID_SKILLS,
                    'skill data set',
                );
            }
        }

        //====================================================================================================
        // EnemySkillデータ
        //====================================================================================================
        if (in_array(DownloadMasterData::ID_ENEMY_SKILLS, $targetTables)) {
            $gzipdata = $this->getGzipMasterData(DownloadMasterData::ID_ENEMY_SKILLS);
            $result = $this->addDownloadMasterData(DownloadMasterData::ID_ENEMY_SKILLS, $gzipdata);
            if ($result) {
                $return[] = array(
                    DownloadMasterData::ID_ENEMY_SKILLS,
                    'enemy skill data set',
                );
            }
        }

        //====================================================================================================
        // DungeonSaleデータ
        //====================================================================================================
        if (in_array(DownloadMasterData::ID_DUNGEON_SALES, $targetTables)) {
            $gzipdata = $this->getGzipMasterData(DownloadMasterData::ID_DUNGEON_SALES);
            $result = $this->addDownloadMasterData(DownloadMasterData::ID_DUNGEON_SALES, $gzipdata);
            if ($result) {
                $return[] = array(
                    DownloadMasterData::ID_DUNGEON_SALES,
                    'dungeon sale data set',
                );
            }
        }

        //====================================================================================================
        // Pieceデータ
        //====================================================================================================
        if (in_array(DownloadMasterData::ID_PIECES, $targetTables)) {
            $gzipdata = $this->getGzipMasterData(DownloadMasterData::ID_PIECES);
            $result = $this->addDownloadMasterData(DownloadMasterData::ID_PIECES, $gzipdata);
            if ($result) {
                $return[] = array(
                    DownloadMasterData::ID_PIECES,
                    'piece data set',
                );
            }
        }

        //====================================================================================================
        // Sceneデータ
        //====================================================================================================
        if (in_array(DownloadMasterData::ID_SCENES, $targetTables)) {
            $gzipdata = $this->getGzipMasterData(DownloadMasterData::ID_SCENES);
            $result = $this->addDownloadMasterData(DownloadMasterData::ID_SCENES, $gzipdata);
            if ($result) {
                $return[] = array(
                    DownloadMasterData::ID_SCENES,
                    'scene data set',
                );
            }
        }

        //====================================================================================================
        // Missionデータ
        //====================================================================================================
        if (in_array(DownloadMasterData::ID_MISSIONS, $targetTables)) {
            $gzipdata = $this->getGzipMasterData(DownloadMasterData::ID_MISSIONS);
            $result = $this->addDownloadMasterData(DownloadMasterData::ID_MISSIONS, $gzipdata);
            if ($result) {
                $return[] = array(
                    DownloadMasterData::ID_MISSIONS,
                    'mission data set',
                );
            }
        }

		// #PADC_DY# ----------begin----------
		//====================================================================================================
		// Roadmapデータ
		//====================================================================================================
		if (in_array(DownloadMasterData::ID_ROADMAP, $targetTables)) {
			$gzipdata = $this->getGzipMasterData(DownloadMasterData::ID_ROADMAP);
			$result = $this->addDownloadMasterData(DownloadMasterData::ID_ROADMAP,$gzipdata);
			if ($result) {
				$return[] = array(
                    DownloadMasterData::ID_ROADMAP,
					'roadmap data set',
				);
			}
		}
        
        if(in_array(DownloadMasterData::ID_ACTIVITY, $targetTables)) {
			$gzipdata = $this->getGzipMasterData(DownloadMasterData::ID_ACTIVITY);
			$result = $this->addDownloadMasterData(DownloadMasterData::ID_ACTIVITY, $gzipdata);
			if($result) {
				$return[] = array(
                    DownloadMasterData::ID_ACTIVITY,
                    'activity data set'
				);
			}
		}

        if(in_array(DownloadMasterData::ID_GACHA_LINEUP, $targetTables)) {
            $gzipdata = $this->getGzipMasterData(DownloadMasterData::ID_GACHA_LINEUP);
            $result = $this->addDownloadMasterData(DownloadMasterData::ID_GACHA_LINEUP, $gzipdata);
            if ($result) {
                $return[] = array(
                    DownloadMasterData::ID_GACHA_LINEUP,
                    'Gacha lineup data set'
                );
            }
        }

        if(in_array(DownloadMasterData::ID_PASSIVE_SKILL, $targetTables)) {
            $gzipdata = $this->getGzipMasterData(DownloadMasterData::ID_PASSIVE_SKILL);
            $result = $this->addDownloadMasterData(DownloadMasterData::ID_PASSIVE_SKILL, $gzipdata);
            if ($result) {
                $return[] = array(
                    DownloadMasterData::ID_PASSIVE_SKILL,
                    'passive skill data set'
                );
            }
        }

        //##新手嘉年华
        if(in_array(DownloadMasterData::ID_CARNIVAL, $targetTables)) {
            $gzipdata = $this->getGzipMasterData(DownloadMasterData::ID_CARNIVAL);
            $result = $this->addDownloadMasterData(DownloadMasterData::ID_CARNIVAL, $gzipdata);
            if ($result) {
                $return[] = array(
                    DownloadMasterData::ID_CARNIVAL,
                    'carnival data set',
                );
            }
        }
		// #PADC_DY# ----------end----------

        // データ作成されたものが1つでもあれば結果を返す
        if (count($return) > 0) {
            $_return = array(
                'format' => 'array',
                array('ID', '内容',),
            );
            foreach ($return as $value) {
                $_return[] = $value;
            }
            $return = $_return;
        }

        $result = array(
            'format' => 'array',
            '反映結果' => $return,
        );
        return json_encode($result);
    }

    /**
     * ==================================================================================================== 
     * 各データのマスターデータをGzip圧縮した値を取得
     * @param int $downloadMasterDataId
     * ==================================================================================================== 
     */
    public static function getGzipMasterData($downloadMasterDataId) {
        //==================================================
        // 各マスターデータのフォーマットバージョン（※データのバージョンではない）
        //==================================================
        $dungeonFormatVer = 1002;
        $dungeonSaleFormatVer = 1;
        $cardFormatVer = 1004;

        // 結果セット用
        $gzipdata = null;
        $masterData = null;
        $params = array();

        //--------------------------------------------------
        // カード（モンスター）
        //--------------------------------------------------
        if ($downloadMasterDataId == DownloadMasterData::ID_CARDS_VER4) {
            $cards = Card::findAllBy($params);
            $checkSum = DownloadCardData::getCheckSum($cards, $cardFormatVer);
            $cardData = DownloadCardData::arrangeColumns($cards);
            $masterData = array(
                'res' => RespCode::SUCCESS,
                'v' => $cardFormatVer,
                'card' => $cardData,
                'ckey' => $checkSum,
            );
        }
        //--------------------------------------------------
        // ダンジョン
        //--------------------------------------------------
        elseif ($downloadMasterDataId == DownloadMasterData::ID_DUNGEONS_VER2) {
            $waveData = array();
            $floorMonsters = array();
            $dungeonPlusDropProbData = array();

            // wavesのidと欠片DROP率をもとに該当のフロアに出現するモンスター一式を取得
            $waves = Wave::findAllBy($params);
            foreach ($waves as $_wave) {
                $waveData[$_wave->dungeon_floor_id][] = array(
                    'id' => $_wave->id,
                    'egg_prob' => $_wave->egg_prob,
                );
            }

            $pdo = Env::getDbConnectionForShare();
            foreach ($waveData as $_dungeon_floor_id => $_waveData) {
                $waveIds = array();
                foreach ($_waveData as $_value) {
                    if ($_value['egg_prob'] > 0) {
                        $waveIds[] = $_value['id'];
                    }
                }
                if (count($waveIds) < 1) {
                    continue;
                }

                $cond = join(',', $waveIds);

                // まとめて取得するとメモリ足らなくなるので個々に参照（※FIXME: DB参照回数多いため良い方法あれば調整したい）
                $sql = 'select distinct card_id from ' . WaveMonster::TABLE_NAME . ' where wave_id in (' . $cond . ');';
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $result = $stmt->fetchAll();
                $cardIds = array();
                foreach ($result as $_result) {
                    $cardIds[] = (int) $_result['card_id'];
                }

                $floorMonsters[$_dungeon_floor_id] = $cardIds;
            }

            // プラス欠片のDROP判定
            $dungeonPlusDropData = DungeonPlusDrop::findAllBy($params);
            foreach ($dungeonPlusDropData as $_data) {
                // ダンジョンIDごとにプラス欠片DROP率をセットしておく
                $dungeonPlusDropProbData[$_data->id] = $_data->drop_prob;
            }

            // ダンジョン、ダンジョンフロア情報をまとめる
            $dungeons = Dungeon::findAllBy($params);
            $dungeonFloors = DungeonFloor::findAllBy($params);
            $dungeonBounues = DungeonReward::findAllBy($params);
            foreach ($dungeons as $_dungeon) {
                $tmpDungeonFloors = array();
                foreach ($dungeonFloors as $_dungeonFloor) {
                    if ($_dungeon->id == $_dungeonFloor->dungeon_id) {
                        if (isset($floorMonsters[$_dungeonFloor->id])) {
                            $_dungeonFloor->padc_cids = $floorMonsters[$_dungeonFloor->id];
                        }
                        $tmpDungeonFloors[] = $_dungeonFloor;
                    }
                }
                $_dungeon->floors = $tmpDungeonFloors;

                $tmpDungeonBonuses = array();
                foreach ($dungeonBounues as $_dungeonBonus) {
                    if ($_dungeon->id == $_dungeonBonus->dungeon_id) {
                        $tmpDungeonBonuses[] = $_dungeonBonus;
                    }
                }
                $_dungeon->bonus = $tmpDungeonBonuses;

                $_dungeon->padc_p = ($dungeonPlusDropProbData[$_dungeon->id] > 0) ? 1 : 0;
            }

            $dungeonData = DownloadDungeonData::arrangeColumns($dungeons);
            $masterData = array(
                'res' => RespCode::SUCCESS,
                'v' => $dungeonFormatVer,
                'dungeons' => $dungeonData,
            );
        }
        //--------------------------------------------------
        // スキル
        //--------------------------------------------------
        elseif ($downloadMasterDataId == DownloadMasterData::ID_SKILLS) {
            $skills = Skill::findAllBy($params);
            $checkSum = DownloadSkillData::getCheckSum($skills);
            $skillData = DownloadSkillData::arrangeColumns($skills);
            $masterData = array(
                'res' => RespCode::SUCCESS,
                'skill' => $skillData,
                'ckey' => $checkSum,
            );
        }
        //--------------------------------------------------
        // 敵スキル
        //--------------------------------------------------
        elseif ($downloadMasterDataId == DownloadMasterData::ID_ENEMY_SKILLS) {
            $enemySkills = EnemySkill::findAllBy($params);
            $enemySkillData = DownloadEnemySkillData::arrangeColumns($enemySkills);
            $masterData = array(
                'res' => RespCode::SUCCESS,
                'enemy_skills' => $enemySkillData,
            );
        }
        //--------------------------------------------------
        // 購入ダンジョン
        //--------------------------------------------------
        elseif ($downloadMasterDataId == DownloadMasterData::ID_DUNGEON_SALES) {
            $dungeonSales = DungeonSale::findAllBy($params);
            $dungeonSaleCommodities = DungeonSaleCommodity::findAllBy($params);
            foreach ($dungeonSales as $_dungeonSale) {
                $tmpDungeonSaleCommodity = array();
                foreach ($dungeonSaleCommodities as $_dungeonSaleCommodity) {
                    if ($_dungeonSale->id == $_dungeonSaleCommodity->dungeon_sale_id) {
                        $tmpDungeonSaleCommodity[] = $_dungeonSaleCommodity;
                    }
                }
                $_dungeonSale->commodities = $tmpDungeonSaleCommodity;
            }
            $tmpData = GetDungSale::arrangeColumns($dungeonSales);
            $masterData = array(
                'res' => RespCode::SUCCESS,
                'v' => $dungeonSaleFormatVer,
                'd' => $tmpData,
            );
        }
        //--------------------------------------------------
        // 欠片
        //--------------------------------------------------
        elseif ($downloadMasterDataId == DownloadMasterData::ID_PIECES) {
            $pieces = Piece::findAllBy($params);
            $tmpData = DownloadPieceData::arrangeColumns($pieces);
            $masterData = array(
                'res' => RespCode::SUCCESS,
                'pieces' => $tmpData,
            );
        }
        //--------------------------------------------------
        // シーン
        //--------------------------------------------------
        elseif ($downloadMasterDataId == DownloadMasterData::ID_SCENES) {
            $scenes = Scene::findAllBy($params);
            $sceneData = DownloadSceneData::arrangeSceneColumns($scenes);
            $sceneParts = ScenePart::findAllBy($params);
            $scenePartData = DownloadSceneData::arrangeScenePartColumns($sceneParts);
            $spScenes = SpScene::findAllBy($params);
            $spSceneData = DownloadSceneData::arrangeSceneColumns($spScenes);
            $spSceneParts = SpScenePart::findAllBy($params);
            $spScenePartData = DownloadSceneData::arrangeScenePartColumns($spSceneParts);
            $masterData = array(
                'res' => RespCode::SUCCESS,
                'scenes' => $sceneData,
                'sceneparts' => $scenePartData,
                'spscenes' => $spSceneData,
                'spsceneparts' => $spScenePartData,
            );
        }
        //--------------------------------------------------
        // ミッション
        //--------------------------------------------------
        elseif ($downloadMasterDataId == DownloadMasterData::ID_MISSIONS) {
            $missions = Mission::findAllBy($params);
            // 削除フラグが立ったデータがあれば配列からカット
            foreach ($missions as $key => $mission) {
                if ($mission->del_flg) {
                    unset($missions[$key]);
                }
            }

            $checkSum = DownloadMissionData::getCheckSum($missions, DownloadMissionData::FORMAT_VER);
            $missionData = DownloadMissionData::arrangeColumns($missions);
            $masterData = array(
                'res' => RespCode::SUCCESS,
                'v' => DownloadMissionData::FORMAT_VER,
                'ckey' => $checkSum,
                'missions' => $missionData,
            );
        }

		// #PADC_DY# ----------begin----------
		// both include roadmap info and levelup_experience info
		elseif($downloadMasterDataId == DownloadMasterData::ID_ROADMAP)
		{
			$roadmap = Roadmap::findAllBy($params);
			$roadmapData = DownloadRoadmapData::arrangeColumns($roadmap);
			$lvup = LevelUp::findAllBy($params);
			$lvupData = DownloadRoadmapData::arrangeLevelUpColumns($lvup);
			$masterData = array(
                'res'		=> RespCode::SUCCESS,
                'roadmap'	=> $roadmapData,
                'lvup'		=> $lvupData,
			);
		}
        // activity data
		elseif($downloadMasterDataId == DownloadMasterData::ID_ACTIVITY)
		{
            $params['del_flg'] = 0;
            $activities = Activity::findAllBy($params);
            $activityData = DownloadActivityData::arrangeColumns($activities);
            $activityDataTest = DownloadActivityData::arrangeColumnsTest($activities);
            $masterData = array(
                'res' => RespCode::SUCCESS,
                'activities' => $activityData,
                'activitiesTest' => $activityDataTest,
            );
		}

        elseif($downloadMasterDataId == DownloadMasterData::ID_GACHA_LINEUP){
            $lineups = GachaLineupConfig::getAllLineupData();
            $masterData = array(
                'res'       => RespCode::SUCCESS,
                'lineups'   => $lineups,
            );
        }

        elseif($downloadMasterDataId == DownloadMasterData::ID_PASSIVE_SKILL){
            $passive_skills = PassiveSkill::findAllBy($params);
            $pskill_data = DownloadPassiveSkillData::arrangeColumns($passive_skills);
            $masterData = array(
                'res'       => RespCode::SUCCESS,
                'pskill'    => $pskill_data,
            );
        }

        //##新手嘉年华
        elseif($downloadMasterDataId == DownloadMasterData::ID_CARNIVAL){
            $carnivals   = CarnivalPrize::findAllBy($params);
            $carnival_data = DownloadCarnivalData::arrangeColumns($carnivals);

            $obj = GameConstant::findBy(array('name'=>'CarnivalDescription'));


            $masterData = array(
                'res'       => RespCode::SUCCESS,
                'carnivals' => array_values($carnival_data['mission']),
                'box'       => $carnival_data['box'],
                'desc'      => $obj->value,
            );
        }

        // #PADC_DY# ----------end----------

        $gzipdata = self::getEncodedGzipData($masterData);

        return $gzipdata;
    }

    /**
     * ==================================================================================================== 
     * 配列をjsonencodeしたものをgzip圧縮
     * @param array $tmpData
     * @return multitype:multitype:NULL
     * ==================================================================================================== 
     */
    public static function getEncodedGzipData($tmpData) {
        $json_encoded_data = json_encode($tmpData);
        $gzipdata = gzencode($json_encoded_data);

        return $gzipdata;
    }

    /**
     * ==================================================================================================== 
     * DownloadMasterdataテーブルに追加・更新
     * @param int $id
     * @param binary $gzipdata
     * ==================================================================================================== 
     */
    private function addDownloadMasterData($id, $gzipdata) {
        $pdo = Env::getDbConnectionForShare();
        $pdo->beginTransaction();

        $downloadMasterData = new DownloadMasterData();
        $params = array(
            'id' => $id,
        );
        $downloadMasterData->id = $id;
        $downloadMasterData->gzip_data = $gzipdata;
        $downloadMasterData->length = strlen($gzipdata);

        $targetDownloadMasterData = $downloadMasterData->findBy($params, $pdo);
        if ($targetDownloadMasterData) {
            $downloadMasterData->update($pdo);
        } else {
            $downloadMasterData->create($pdo);
        }
        $pdo->commit();

        return true;
    }

    /**
     * ==================================================================================================== 
     * jsonフォーマット出力
     * @param array $data
     * ==================================================================================================== 
     */
    private function jsonOutput($data) {
        header("Content-type: application/json; charset=utf-8;");
        echo(json_encode($data));
        exit;
    }

}

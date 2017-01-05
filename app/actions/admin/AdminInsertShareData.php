<?php
/**
 * Admin用:データベース追加
 */
class AdminInsertShareData extends AdminBaseAction
{
	public function action($params)
	{
		$base_id = isset($params['base_id']) ? $params['base_id'] : 0;
		$model = isset($params['model']) ? $params['model'] : '';
		if (!$model) {
			throw new PadException(RespCode::UNKNOWN_ERROR, "model name is none.");
		}
		
		try{
			$pdo = Env::getDbConnectionForShare();
			$pdo->beginTransaction();
		
			$columns = $model::getColumns();
			
			$data = new $model;
			foreach($columns as $_key => $_column) {
				if (isset($params[$_column]) && $params[$_column] !== '') {
					$data->$_column = $params[$_column];
				}
			}
			$data->id = $base_id;
			Padc_Log_Log::debugToolEditDBLog( "InsertShareData [" . $model . "] " . BaseModel::timeToStr(time()) . " " . json_encode($data) );
				
			$data->create($pdo);
		
			$pdo->commit();
		
		}catch(Exception $e){
			if ($pdo->inTransaction()) {
				$pdo->rollback();
			}
			throw $e;
		}
		Padc_Log_Log::debugToolEditDBLog( "End Success");
		
		if (isset($params['from_action']) && isset($params['from_request_type'])) {
			$from_action = $params['from_action'];
			$from_request_type = $params['from_request_type'];
			header( 'Location: ./api_admin.php?action='.$from_action.'&request_type='.$from_request_type );
		}
		return json_encode ( array('res' => RespCode::SUCCESS) );
	}
}

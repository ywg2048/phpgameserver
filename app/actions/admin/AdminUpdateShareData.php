<?php
/**
 * Admin用:データベース更新
 */
class AdminUpdateShareData extends AdminBaseAction
{
	public function action($params)
	{
		$base_id = isset($params['base_id']) ? $params['base_id'] : 0;
		$model = isset($params['model']) ? $params['model'] : '';
		
		try{
			$pdo = Env::getDbConnectionForShare();
			$pdo->beginTransaction();
		
			$columns = $model::getColumns();
			$data = $model::find($base_id, $pdo, TRUE);
			if (!$data) {
				throw new PadException(RespCode::UNKNOWN_ERROR, "target data is none.");
			}
			
			$b_data = clone $data;
			foreach($columns as $_key => $_column) {
				if (isset($params[$_column]) && $data->$_column !== $params[$_column]) {
					$data->$_column = ($params[$_column] !== '') ? $params[$_column] : null;
				}
			}
			Padc_Log_Log::debugToolEditDBLog( "UpdateShareData [" . $model . "] " . BaseModel::timeToStr(time()) . " " . json_encode($b_data) . " => " . json_encode($data) );
			
			$data->update($pdo);
		
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

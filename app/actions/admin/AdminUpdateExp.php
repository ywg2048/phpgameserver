<?php
//tencent用:
class TencentDoUpdateExp extends TencentBaseAction
{

	public function action($params){
		$openId = $params['OpenId'];
		$platId = $params['PlatId'];
		$value = $params['Value'];
		
		$user_id = UserDevice::getUserIdFromUserOpenId($platId, $openId);
		$expData = static::updateExp($user_id,$value);
		$result = array(
				'res' => 0,
				'msg' => "success",
				'Result' =>0,
				'RetMsg' => "success",
				'Begin_Value' => $expData['beginValue'],
				'EndValue' => $expData['endValue']
		);
		return json_encode($result);
	}
	/**
	 * update user's exp value
	 * @param string $user_id
	 * @param int(32) $value
	 * @throws PadException
	 * @throws PDOException
	 * @return array
	 */
	public static function updateExp($user_id,$value){
		$pdo = Env::getDbConnectionForUserWrite($user_id);
		$user = User::find($user_id,$pdo,true);
		if($user == false){
			throw new PadException(RespCode::USER_NOT_FOUND,"not found user");
		}
		if($user->exp == $value){
			$beginValue = $value;
			$endValue = $value;
		}else{
			$pdo->beginTransaction();
			try {
				$beginValue = $user->exp;
				$user->exp = $value + $beginValue;
				//the lowest exp is 0
				if($user->exp < 0){
					$user->exp = 0;
				}
				$endValue = $user->exp;
				if(!$user->update($pdo)){
					throw new PDOException();
				}
				
				$pdo->commit();
			}catch(Exception $e){
				if($pdo->inTransaction()){
					$pdo->rollback();
				}
				throw $e;
			}
		}
		$result = array(
				'beginValue' => $beginValue,
				'endValue' => $endValue
		);
		
		return $result;
	}
	
	
	
	
}
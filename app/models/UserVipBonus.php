<?php
/**
 * class vip
 */
class UserVipBonus extends BaseModel
{
	const TABLE_NAME = "user_vip_bonus";
	
	protected static $columns=array(
		'id',
		'user_id',
		'lv'
	);

	/**
	 * record whether or not player get lv up bonuses
	 * @param int $user_id
	 * @param int $vip_lv
	 * @param string $pdo
	 */
	public function createUserBonusRecord($user_id,$vip_lv,$pdo = null){
		if(!$pdo){
			$pdo = Env::getDbConnectionForUserWrite($user_id);
		}
		$this->user_id = $user_id;
		$this->lv = $vip_lv;
		$this->create($pdo);
	}
	/**
	 * get player recieved bonuses
	 * @param int $user_id
	 * @param object $pdo
	 * @return false | array:
	 */
	public static function getReceivedBonuses($user_id,$pdo = null){
		if(!$pdo){$pdo = Env::getDbConnectionForUserRead($user_id);}
		$received_bonuses = static::findAllBy(array("user_id"=>$user_id),null,null,$pdo);
		if($received_bonuses){
			foreach ($received_bonuses as $received_bonus){
				$received[] = (int)$received_bonus->lv;
			}
			return $received;
		}else{
			return null;
		}
	}
	/**
	 * check whether or not user cheat vip bonuses
	 * @param int $user_id
	 * @param int $blv
	 * @throws PadException
	 * @return multitype:モデルオブジェクト. PDO
	 */
	public function checkBonuses($user_id,$blv){
		$pdo = Env::getDbConnectionForUserWrite($user_id);
		$user = User::find($user_id,$pdo);
		if(empty($user)) throw new PadException(RespCode::USER_NOT_FOUND,"user not find");
		//
		if($blv > $user->vip_lv || $blv <= 0)throw new PadException(RespCode::UNKNOWN_ERROR,"user theat bonuses");
		$received = static::getReceivedBonuses($user_id);
		if($received && in_array($blv,$received)){
			throw new PadException(RespCode::UNKNOWN_ERROR,"user theat bonuses");
		}
		return array($user,$pdo);
	}
}
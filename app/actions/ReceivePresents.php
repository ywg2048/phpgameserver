<?php
/**
 * #PADC#
 */
class ReceivePresents extends BaseAction {
	/**
	 *
	 * @see BaseAction::action()
	 */
	public function action($params) {
		//fetch receiver id and presents id
		$receiver_id = $params ['pid'];
		$present_ids = array_unique(explode ( ',', $params ['pres'] ));
		
		//do find receiver object,if not find throw a PADCException
		$pdo_receiver = Env::getDbConnectionForUserWrite ( $receiver_id );
		$receiver = User::find ( $receiver_id, $pdo_receiver, TRUE );
		if (! $receiver) {
			throw new PadException ( RespCode::UNKNOWN_ERROR, 'Receiver not found!' );
		}
		
		//get receveied presents id on today and count how many received presents(only stamina)
		$add_stamina_count = 0;
		$stamina_full = 0;
		$receive_remain = 0;
		$receive_ids = array ();
 		$today_received_ids = UserPresentsReceive::getReceivedPresentIdsOnToday($receiver_id); 
 		$receive_count = count($today_received_ids);
		$share = Env::getDbConnectionForShare();
		$u1_device = UserDevice::find($receiver_id,$share);
		//get each presents
		foreach ( $present_ids as $present_id ) {
			$present = UserPresentsReceive::find ( $present_id, $pdo_receiver, TRUE );
			if (! $present) {
				throw new PadException ( RespCode::UNKNOWN_ERROR, 'Present not found!' );
			}
			if ($present->receiver_id != $receiver_id) {
				throw new PadException ( RespCode::UNKNOWN_ERROR, 'Not belong user!' );
			}
			if ($present->status == UserPresentsReceive::STATE_RECEIVED){
				throw new PadException(RespCode::UNKNOWN_ERROR,'Present had already got!');
			}

			$u2_device = UserDevice::find($present->sender_id,$share);

			$unmatch_dbid = true;
			if($u2_device->dbid == $u1_device->dbid)
			{
				$unmatch_dbid = false;
				$pdo_sender = $pdo_receiver;
			}
			else
			{
				$pdo_sender = Env::getDbConnectionForUserWrite ( $present->sender_id );
			}
			
			try {
				if($unmatch_dbid)
				{
					$pdo_sender->beginTransaction ();
				}
				$pdo_receiver->beginTransaction ();
				
				//if not exceed max received number and max stamina number,do get presents,else break cycle
				if (($receive_count < (int)$receiver->present_receive_max || $receiver->present_receive_max == 0)) {
					if($receiver->addPresentStamina ()){
						$receive_ids[] = (int)$present->id;
						//record today received ids for store in memcache,convenience for next use
						$today_received_ids[] = (int)$present->id;
						$add_stamina_count ++;
						//get one present,received number add one
						$receive_count++;
						$receive_remain = ($receiver->present_receive_max > 0)? ($receiver->present_receive_max - $receive_count) : 1;
					}else{
						//represent stamina is full,can't add stamina anymore
						$stamina_full = 0;
						break;
					}
				} else {
					//represent can't add stamina any more
					$receive_remain = 0;//$receive_count;
					break;
				}
				
				//write user object changes to db
				$receiver->update ( $pdo_receiver );
				//send back present to sender
				if ($present->status == UserPresentsReceive::STATE_UNRECEIVED) {
					UserPresentsReceive::sendPresent ( $receiver_id, $present->sender_id, $pdo_sender, true );
				}
				
				//reset status and status update at options,then write to user present receive table
				$present->receive ( $pdo_receiver );
				$present->update ( $pdo_receiver );

				$pdo_receiver->commit ();
				if($unmatch_dbid)
				{
					$pdo_sender->commit ();	
				}				
			} catch ( Exception $e ) {
				if ($pdo_sender->inTransaction ()) {
					$pdo_sender->rollback ();
				}
				if ($pdo_receiver->inTransaction ()) {
					$pdo_receiver->rollback ();
				}
				throw $e;
			}
		}
		
		//set cache today received ids 
		UserPresentsReceive::cacheReceivedIds($receiver_id, $today_received_ids);
		// #PADC# Tlog
		UserTlog::sendTlogSnsFlow ( $receiver_id, $add_stamina_count, Tencent_Tlog::SNSTYPE_RECEIVEHEART );
		
		$result = array (
				'res' => RespCode::SUCCESS,
				//'add' => $add_stamina_count,
				'full' => $stamina_full,
				'limit' =>$receive_remain,
				'limit_max' => $receiver->present_receive_max,
				'sta' => $receiver->getStamina (),
				'sta_time' => strftime("%y%m%d%H%M%S", strtotime($receiver->stamina_recover_time)),
				'ids' => $receive_ids
		);
		
		return json_encode ($result);
	}
}
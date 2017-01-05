<?php
/**
 * #PADC#
 * 手に入れたことのあるカードID.
 */
class UserBook extends BaseModel {
	const TABLE_NAME = "user_book";

	private $card_array = null;

	protected static $columns = array(
		'user_id',
		'card_ids',
	);

	/**
	 * 指定のユーザーのデータを取得する
	 */
	public static function getByUserId($user_id, $pdo = null)
	{
		$obj = self::findBy(array('user_id' => $user_id), $pdo);
		if ($obj) {
			// card_idsを配列に変換
			$obj->card_array = json_decode($obj->card_ids, true);
		}
		else {
			$obj = new UserBook();
			$obj->user_id = $user_id;
			$obj->card_ids = json_encode(array());
			$obj->card_array = array();
			$obj->create($pdo);
		}

		return $obj;
	}


	/**
	 * 指定IDのカードを所持データに追加する.
	 *
	 */
	public function addCardId($card_id)
	{
		if (!in_array($card_id, $this->card_array)) {
			$this->card_array[] = (int)$card_id;
			$this->card_array = array_unique($this->card_array);
			sort($this->card_array);
			$this->card_ids = json_encode($this->card_array);
		}
	}

	/**
	 * 指定IDのカードを所持したことがあるか返す.
	 */
	public function checkCardId($card_id)
	{
		return in_array($card_id, $this->card_array);
	}

	/**
	 * 所持したカードの数を返す.
	 */
	public function getCountIds()
	{
		return count($this->card_array);
	}

	/**
	 * 所持したカードIDの配列データを返す.
	 */
	public function getCardArray()
	{
		return $this->card_array;
	}

}

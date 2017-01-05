<?php
/**
 * Admin用:チュートリアル用カードデータ確認
 */
class AdminViewTutorialCardData extends AdminBaseAction
{
	public function action($params)
	{
		$cardFormatVer = 1004;

		$params = array();
		$cards = TutorialCard::findAllBy($params);
		$checkSum = DownloadCardData::getCheckSum($cards,$cardFormatVer);
		$cardData = DownloadCardData::arrangeColumns($cards);
		$cardMasterData = array(
			'res'	=> RespCode::SUCCESS,
			'v'		=> $cardFormatVer,
			'card'	=> $cardData,
			'ckey'	=> $checkSum,
		);

		echo json_encode($cardMasterData);
		exit;
	}
}
<?php
/**
 * 
 * @param string $class
 * @return multitype:string
 */
function tencent_tlog_autoload($class = NULL) {
	static $classes = NULL;
	static $path = NULL;

	if ($classes === NULL) {
		$classes = array(
				//classname       file path
				'tencent_tlog' => '/Tlog.php',
				'tlogbase' => '/Tlog/TlogBase.php',
				'tlogdeckflow' => '/Tlog/TlogDeckFlow.php',
				'tlogfilewriter' => '/Tlog/TlogFileWriter.php',
				'tloggamesvrstate' => '/Tlog/TlogGameSvrState.php',
				'tlogguideflow' => '/Tlog/TlogGuideFlow.php',
				'tlogidipflow' => '/Tlog/TlogIDIPFlow.php',
				'tlogitemflow' => '/Tlog/TlogItemFlow.php',
				'tlogmoneyflow' => '/Tlog/TlogMoneyFlow.php',
				'tlogplayerexpflow' => '/Tlog/TlogPlayerExpFlow.php',
				'tlogplayerlogin' => '/Tlog/TlogPlayerLogin.php',
				'tlogplayerregister' => '/Tlog/TlogPlayerRegister.php',
				'tlogroundflow' => '/Tlog/TlogRoundFlow.php',
				'tlogsneakdungeon' => '/Tlog/TlogSneakDungeon.php',
				'tlogsnsflow' => '/Tlog/TlogSnsFlow.php',
				'tlogudpwriter' => '/Tlog/TlogUdpWriter.php',
				'tlogviplevelup' => '/Tlog/TlogVipLevelUp.php',
				'tlogwriter' => '/Tlog/TlogWriter.php',
				'tlogmonthlyreward' => '/Tlog/TlogMonthlyReward.php',
				'tlogmissionflow'	=> '/Tlog/TlogMissionFlow.php',
				'tlogcomposite' => '/Tlog/TlogComposite.php',
				'tlogevolution' => '/Tlog/TlogEvolution.php',
				'tlogshareflow' => '/Tlog/TlogShareFlow.php',
				'tlogranking' => '/Tlog/TlogRanking.php',
				'tlogfailedsneak' => '/Tlog/TlogFailedSneak.php',
				'tlogplayerlogout' => '/Tlog/TlogPlayerLogout.php',
				'tlogsecroundstartflow' => '/Tlog/TlogSecRoundStartFlow.php',
				'tlogsecroundendflow' => '/Tlog/TlogSecRoundEndFlow.php',
				'tlogsectalkflow' => '/Tlog/TlogSecTalkFlow.php',
				'tlogchangename' => '/Tlog/TlogChangeName.php',
				'tlogmonthlycard' => '/Tlog/TlogMonthlyCard.php',
				'tloglogantidata' => '/Tlog/TlogLogAntiData.php',
                'tlogexchangeitem' => '/Tlog/TlogExchangeItem.php',
                'tlogawakeskill' => '/Tlog/TlogAwakeSkill.php',
				'tlogcarnivalprize'=>'/Tlog/TlogCarnivalPrize.php',
		);

		$path = dirname(dirname(__FILE__));
	}

	if ($class === NULL) {
		$result = array(__FILE__);

		foreach ($classes as $file) {
			$result[] = $path . $file;
		}

		return $result;
	}

	$cn = strtolower($class);

	if (isset($classes[$cn])) {
		require $path . $classes[$cn];
	}
}

spl_autoload_register('tencent_tlog_autoload');

<?php
/**
 * Admin用：リソースディレクトリチェック
 */
class AdminCheckResourceDirectory extends AdminBaseAction
{
	public function action($params)
	{
		$tmppath = isset($params['tmppath']) ? $params['tmppath'] : '';

		$baseDirName	= 'resource';
		$basePath		= './' . $baseDirName;
		$backFlag		= 1;

		// 検索対象ディレクトリのパス調整
		$path = $basePath;
		if($tmppath)
		{
			$_tmpPath	= realpath($tmppath);
			$_tmpPaths	= explode($baseDirName, $_tmpPath);
			$path		= $basePath . $_tmpPaths[1];
		}

		// 「..（一階層上へ）」リンク調整
		$realBasePath		= realpath($basePath);
		$realPath			= realpath($path);
		$realBasePathLength	= strlen($realBasePath);
		$realPathLength		= strlen($realPath);
		if($realBasePathLength >= $realPathLength)
		{
			$backFlag = 0;
		}

		$files = array();
		$dirs = array();

		$fh = opendir($path);
		while(false !== ($_file = readdir($fh)))
		{
			// 同階層パスは表示しない
			if($_file == '.')
			{
				continue;
			}

			// 基本ディレクトリの場合、上の階層へのパスは非表示
			if($backFlag == 0 && $_file == '..')
			{
				continue;
			}

			$filepath = $path . '/' . $_file;
			$linkname = $_file;
				
			
			if(is_file($filepath))
			{
				$_file = '<a href="' . $filepath . '" target="_blank">' . $linkname . '</a>';
				$files[] = $_file;
			}
			else
			{
				$tmpParam	= '?action=' . $params['action']
							. '&request_type=' . $params['request_type']
							. '';
				// リンク名調整
				if($_file == '..')
				{
					$linkname = '一階層上へ';
				}

				$_file = '<a href="api_admin.php' . $tmpParam . '&tmppath=' . $filepath . '">' . $linkname . '</a>';
				$dirs[] = $_file;
			}
		}	
		closedir($fh);

		sort($files);
		sort($dirs);

		$result = array(
			'format'	=> 'array',
			'dirs'		=> $dirs,
			'files'		=> $files,
		);
		
		return json_encode($result);
	}
}

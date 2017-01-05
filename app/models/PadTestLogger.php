<?php

/**
 * PHPUnit実行時にZendLoggerがログ出力できずに落ちるので、
 * test環境でのみ暫定的に、こちらのダミーロガーに差し替える. TODO 調査.
 */
class PadTestLogger {
  function log($resource = null, $level = null) {
  }
}

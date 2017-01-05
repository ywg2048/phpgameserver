<?php

/**
 * クラスローダー
 *
 */
class ClassLoader {

    /**
     * ディレクトリ格納
     * @var array
     */
    private $dirs = array();

    /**
     * コンストラクタ
     */
    public function __construct() {
        spl_autoload_register(array($this, 'load'));
    }

    private function load($classname) {
        if (class_exists($classname, false) || interface_exists($classname, false)) {
            return false;
        }

        foreach ($this->dirs as $dir) {
            $file = $dir . '/' .  $classname . '.php';
            if (is_file($file)) {
                require $file;
                return true;
            }
        }

        return false;
    }
    /**
     * ディレクトリを登録
     *
     * @param string $dir
     */
    public function registerDir($dir) {
        if(!is_dir($dir)) {
            throw new Exception("Directory Not Found. ($dir)");
        }

        $this->dirs[] = $dir;
    }
}
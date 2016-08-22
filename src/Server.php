<?php
/**
 * Created by PhpStorm.
 * User: lixin65535@126.com
 * Date: 2016/8/17
 * Time: 15:36
 */

namespace Phpxin\Safeport ;
use \Phpxin\Safeport\SafePortException ;

class Server{

    private $primaryKeyPath ;    //非对称加密pk路径

    //这三个值都是被base64编码的
    private $mcryptIv = '' ;  //特征向量
    private $mKey ;
    private $mData ;

    private $mcryptObj = null ;
    private $mcryptType ;       //对称加密类型    例： MCRYPT_RIJNDAEL_256
    private $algorithmDirectory = '' ;
    private $mode = MCRYPT_MODE_NOFB ;
    private $modeDirectory = '' ;

    //解密后的数据
    private $key ;
    private $data ;
    private $iv ;

    public function __construct($mcryptType, $primaryKeyPath, $mKey, $miv, $mData)
    {
        $this->mcryptType = $mcryptType ;
        $this->primaryKeyPath = $primaryKeyPath ;
        $this->mKey = $mKey ;
        $this->mData = $mData ;
        $this->mcryptIv = $miv ;
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        if ($this->mcryptObj){
            mcrypt_generic_deinit($this->mcryptObj);
            mcrypt_module_close($this->mcryptObj);
        }
    }

    /**
     * 设置对称加密参数
     * @param $dir 指示加密模块的位置。 如果你提供此参数，则使用你指定的值。 如果将此参数设置为空字符串（""），将使用 php.ini 中的 mcrypt.algorithms_dir 。
     * @param $mode MCRYPT_MODE_modename 常量中的一个
     * @param $modeDir 指示加密模式的位置。 如果你提供此参数，则使用你指定的值。 如果将此参数设置为空字符串（""），将使用 php.ini 中的 mcrypt.modes_dir 。
     */
    public function setMcryptOpts($dir, $mode, $modeDir){
        $this->algorithmDirectory = $dir ;
        $this->mode = $mode ;
        $this->modeDirectory = $modeDir ;
    }

    /**
     * 初始化对称加密
     */
    public function initMcrypt(){
        $this->mcryptObj = mcrypt_module_open($this->mcryptType, $this->algorithmDirectory, $this->mode, $this->modeDirectory);
    }

    private function decryptRSA($encrypted){
        $priKey = file_get_contents($this->primaryKeyPath) ;
        $decrypted = '';
        openssl_private_decrypt($encrypted, $decrypted, $priKey) ;
        return $decrypted ;
    }

    private function decryptData(){
        $this->initMcrypt() ;
        $key = base64_decode($this->mKey) ;
        $key = $this->decryptRSA($key) ;
        $this->key = $key ;

        $iv = base64_decode($this->mcryptIv);

        $iv = $this->decryptRSA($iv) ;
        $this->iv = $iv ;

        mcrypt_generic_init($this->mcryptObj, $key, $iv);

        $encrypted = base64_decode($this->mData) ;

        $decrypted = mdecrypt_generic($this->mcryptObj, $encrypted);

        $this->data = $decrypted ;
        return $decrypted ;
    }

    public function getData(){
        return $this->data ;
    }

    public function getKey(){
        return $this->key ;
    }

    public function getIv(){
        return $this->iv ;
    }

    /**
     * 获取解密后的结果
     */
    public function execute(){

        $this->decryptData() ;

    }

    /**
     * 根据客户端提供的一次一密码和向量，加密返回数据
     * @param string $data 需要加密的数据
     * @return string 加密后的数据
     */
    public function encryptData($data){
        $encrypted = mcrypt_generic($this->mcryptObj, $data);
        $encrypted = base64_encode($encrypted) ;
        return $encrypted ;
    }
}
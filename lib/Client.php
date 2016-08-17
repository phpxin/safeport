<?php
/**
 * 加密协议：客户端类
 * Created by PhpStorm.
 * User: lixin65535@126.com
 * Date: 2016/8/17
 * Time: 14:02
 *
 * 依赖 mcrypt openssl base64
 */
namespace safeport ;
use \safeport\SafePortException ;

include rtrim(dirname(__FILE__), ',') . '/SafePortException.php' ;

class Client{

    private $publicKeyPath ;    //非对称加密pk路径
    private $mcryptKey ;
    private $data ;

    private $mcryptObj = null ;
    private $mcryptType ;       //对称加密类型    例： MCRYPT_RIJNDAEL_256
    private $algorithmDirectory = '' ;
    private $mode = MCRYPT_MODE_NOFB ;
    private $modeDirectory = '' ;

    //加密后的数据，这三个值都会经过Base64编码
    private $mData = '' ;
    private $mKey = '' ;
    private $mcryptIv = '' ;  //特征向量

    /**
     * Client constructor.
     * @param $mcryptType 对称加密类型，MCRYPT_ciphername 常量中的一个，或者是字符串值的算法名称。
     * @param $publicKeyPath 非对称加密public key 路径
     * @param $data 需要加密的数据
     */
    public function __construct($mcryptType, $publicKeyPath, $data)
    {
        $this->mcryptType = $mcryptType ;
        $this->publicKeyPath = $publicKeyPath ;
        $this->data = $data ;
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        if ($this->mcryptObj){
            mcrypt_generic_deinit($this->mcryptObj);
            mcrypt_module_close($this->mcryptObj);
        }
    }

    public function getMData()
    {
        return $this->mData ;
    }

    public function getMKey(){
        return $this->mKey ;
    }

    public function getIv(){
        return $this->mcryptIv ;
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


    /**
     * 生成对称加密key，用于数据加密
     */
    private function createMcryptKey(){
        $keyLen = mcrypt_enc_get_key_size($this->mcryptObj);
        $key = substr(md5(uniqid()), 0, $keyLen);
        return $key ;
    }

    /**
     * 生成加密向量
     * @return string
     * @throws \safeport\SafePortException
     */
    private function createIv(){
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($this->mcryptObj));

        $this->mcryptIv = $iv ;
        $this->mcryptIv = $this->encryptRSA($this->mcryptIv) ;
        $this->mcryptIv = base64_encode($this->mcryptIv) ;

        return $iv;
    }

    /**
     * 加密数据
     */
    private function encryptData($key){
        $iv = $this->createIv() ;
        mcrypt_generic_init($this->mcryptObj, $key, $iv);

        $encrypted = mcrypt_generic($this->mcryptObj, $this->data);
        $encrypted = base64_encode($encrypted);

        return $encrypted ;
    }

    /**
     * rsa 公钥 加密
     */
    private function encryptRSA($data){

        $encrypted = '' ;
        $pubKey = file_get_contents($this->publicKeyPath);
        if (!openssl_public_encrypt($data, $encrypted, $pubKey)) {
            throw new SafePortException ('加密失败', SafePortException::ENCODE_FAILED) ;
        }
        return $encrypted ;
    }


    /**
     * 获取加密后的结果
     */
    public function execute(){

        $this->initMcrypt() ;
        $mcryptKey = $this->createMcryptKey();
        $this->mData = $this->encryptData($mcryptKey) ;
        $this->mKey = $this->encryptRSA($mcryptKey) ;
        $this->mKey = base64_encode($this->mKey) ;

    }


    /**
     * 解密服务端返回数据
     * @param string $data base64+对称加密的数据
     * @return string 解密后的字符串数据
     */
    public function decryptData($data){
        $data = base64_decode($data) ;
        $decrypted = mdecrypt_generic($this->mcryptObj, $data);
        return $decrypted ;
    }

}
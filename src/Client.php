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
namespace Phpxin\Safeport ;
use Phpxin\Safeport\SafePortException;

class Client{

    private $publicKeyPath ;    //非对称加密pk路径
    private $mcryptKey ;
    private $data ;

    private $mcryptObj = null ;
    private $mcryptType ;       //对称加密类型，仅支持AES128/256加密（MCRYPT_RIJNDAEL_128/MCRYPT_RIJNDAEL_256）
    private $algorithmDirectory = '' ;
    private $mode = MCRYPT_MODE_NOFB ;
    private $modeDirectory = '' ;
    private $padding ;

    //加密后的数据，这三个值都会经过Base64编码
    private $mData = '' ;
    private $mKey = '' ;
    private $mcryptIv = '' ;  //特征向量

    /**
     * Client constructor.
     * @param int $mcryptType 对称加密类型，MCRYPT_ciphername 常量中的一个，或者是字符串值的算法名称。
     * @param string $publicKeyPath 非对称加密public key 路径
     * @param string $data 需要加密的数据
     * @throws SafePortException
     */
    public function __construct($mcryptType, $publicKeyPath, $data, $padding = false)
    {
        if ($mcryptType != MCRYPT_RIJNDAEL_128 && $mcryptType != MCRYPT_RIJNDAEL_256){
            throw new SafePortException('不支持的算法', SafePortException::ALG_DISABLE);
        }

        $this->mcryptType = $mcryptType ;
        $this->publicKeyPath = $publicKeyPath ;
        $this->data = $data ;
        $this->padding = $padding ;
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
     * @param string $dir 指示加密模块的位置。 如果你提供此参数，则使用你指定的值。 如果将此参数设置为空字符串（""），将使用 php.ini 中的 mcrypt.algorithms_dir 。
     * @param int $mode MCRYPT_MODE_modename 常量中的一个
     * @param string $modeDir 指示加密模式的位置。 如果你提供此参数，则使用你指定的值。 如果将此参数设置为空字符串（""），将使用 php.ini 中的 mcrypt.modes_dir 。
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

        if ($this->mcryptType == MCRYPT_RIJNDAEL_128){
            $keyLen = Tools::KEY_SIZE_128 ;
        }
        if ($this->mcryptType == MCRYPT_RIJNDAEL_256){
            $keyLen = Tools::KEY_SIZE_256 ;
        }

        $key = substr(md5(uniqid()), 0, $keyLen);

        return $key ;
    }

    /**
     * 生成加密向量
     * @return string
     * @throws \Phpxin\Safeport\SafePortException
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

        $data = $this->data ;

        if ($this->padding){
            $blockSize = mcrypt_enc_get_block_size($this->mcryptObj);
            $data = Tools::pkcs5_pad($data, $blockSize) ;
        }

        $encrypted = mcrypt_generic($this->mcryptObj, $data);
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

        if ($this->padding){
            $decrypted = Tools::pkcs5_unpad($decrypted) ;
        }

        return $decrypted ;
    }

}
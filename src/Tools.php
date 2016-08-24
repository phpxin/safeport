<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/8/24
 * Time: 15:24
 */
namespace Phpxin\Safeport ;

class Tools{

    const KEY_SIZE_128 = 16 ;
    const KEY_SIZE_256 = 32 ;

    /**
     * 生成一个对称加密key
     * @param int $keySize key大小 KEY_SIZE_128/KEY_SIZE_256
     * @return string
     */
    public static function generateKey($keySize){

        $key = substr(md5(uniqid()), 0, $keySize);

        return $key;
    }

    public static function pkcs5_pad ($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    public static function pkcs5_unpad($text)
    {
        $pad = ord($text{strlen($text)-1});
        if ($pad > strlen($text)) return false;
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;
        return substr($text, 0, -1 * $pad);
    }
}
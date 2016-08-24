<?php
/**
 * Created by PhpStorm.
 * User: lixin65535@126.com
 * Date: 2016/8/17
 * Time: 15:05
 */
namespace Phpxin\Safeport ;
use Exception ;


class SafePortException extends Exception{
    
    const ENCODE_FAILED = 10000 ;
    const ALG_DISABLE = 10002 ; //不支持的算法
    
}
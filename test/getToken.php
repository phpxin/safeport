<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/8/23
 * Time: 10:17
 */


$req = curl_init('http://api.utrips.com.cn/getToken') ;
curl_setopt($req, CURLOPT_RETURNTRANSFER, 1) ;
$ret = curl_exec($req) ;

echo $ret;
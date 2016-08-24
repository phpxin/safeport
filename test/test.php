<?php
$mem = memory_get_usage(true);

include '../src/SafePortException.php' ;
include '../src/Server.php' ;
include '../src/Client.php' ;
include '../src/Tools.php' ;

use \Phpxin\Safeport\Client ;

$data = 'hello world' ;

$cli = new Client(MCRYPT_RIJNDAEL_128, 'E:\\testks\\pub_key.pem', $data, true) ;
$cli->execute() ;


$m = [
    'data' => $cli->getMData() ,
    'key' => $cli->getMKey() ,
    'iv' => $cli->getIv() ,
] ;

//var_dump($m) ;

$req = curl_init('http://127.0.0.1/git/safeport/test/testserv.php') ;
//$req = curl_init('http://api.utrips.com.cn/test') ;
curl_setopt($req, CURLOPT_POST, 1) ;
curl_setopt($req, CURLOPT_RETURNTRANSFER, 1) ;
curl_setopt($req, CURLOPT_POST, 1);
curl_setopt($req, CURLOPT_POSTFIELDS, $m);
$ret = curl_exec($req) ;


if (curl_errno($req)){
    echo curl_error($req) ;
    exit();
}

//var_dump($ret) ;
//exit();

$ret = $cli->decryptData($ret) ;


$ret = json_decode($ret, true) ;

var_dump($ret) ;


$mem_end = memory_get_usage(true);

echo '<hr />' , ($mem_end-$mem)/1024 ;
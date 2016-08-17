<?php
$mem = memory_get_usage(true);
include '../lib/Client.php' ;

$data = 'hello world' ;

$cli = new \safeport\Client(MCRYPT_RIJNDAEL_256, 'E:\\testks\\pub_key.pem', $data) ;
$cli->execute() ;


$m = [
    'data' => $cli->getMData() ,
    'key' => $cli->getMKey() ,
    'iv' => $cli->getIv()
] ;

//var_dump($m) ;

$req = curl_init('http://192.168.2.143/git/safeport/test/testserv.php') ;
curl_setopt($req, CURLOPT_POST, 1) ;
curl_setopt($req, CURLOPT_RETURNTRANSFER, 1) ;
curl_setopt($req, CURLOPT_POST, 1);
curl_setopt($req, CURLOPT_POSTFIELDS, $m);
$ret = curl_exec($req) ;



if (curl_errno($req)){
    echo curl_error($req) ;
    exit();
}

$ret = $cli->decryptData($ret) ;


$ret = json_decode($ret, true) ;

var_dump($ret) ;


$mem_end = memory_get_usage(true);

echo '<hr />' , ($mem_end-$mem)/1024 ;
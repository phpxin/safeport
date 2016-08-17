<?php

$mem = memory_get_usage(true);

include '../lib/Server.php' ;

$mKey = $_POST['key'];
$mData = $_POST['data'];
$miv = $_POST['iv'];

$cli = new \safeport\Server(MCRYPT_RIJNDAEL_256, 'E:\\testks\\pri_key.pem', $mKey, $miv, $mData) ;
$cli->execute() ;

//
//$m = [
//    'data' => $cli->getData() ,
//    'key' => $cli->getKey()
//] ;

$pdo = new PDO('mysql:host=127.0.0.1;dbname=businesstravel', 'root', 'lixinxin') ;
$pdo->query('set names utf8') ;
$q = $pdo->query('select * from user_city');
$rows = $q->fetchAll(PDO::FETCH_ASSOC);


$data  = $cli->getData() ;
$data = [
    'code' => 0 ,
    'data' => $rows
] ;


$ret = $cli->encryptData(json_encode($data)) ;


echo $ret ;

$mem_end = memory_get_usage(true);

file_put_contents('e:/phplog.txt', ($mem_end-$mem)/1024 );
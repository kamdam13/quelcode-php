<?php
$limit = $_GET['target'];

$dsn = 'mysql:dbname=test;host=mysql';
$dbuser = 'test';
$dbpassword = 'test';

header('Content-Type: application/json');

//bit全探索
function solve($array,$target){
	$size = count($array);
	$res = array();
	for($i=0;$i < 1<<$size; $i++){
		$sum = 0;
		$tmpArray = array();
		for ($j=0; $j < $size; $j++) { 
			if($i & (1<<$j)){
				$sum += $array[$j];
				array_push($tmpArray,intval($array[$j]));
			}
		}
		if($sum === $target){
			array_push($res,$tmpArray);
		}
	}
	return $res;
}

// DBに接続する、エラー時は500response
try{
	$db = new PDO($dsn,$dbuser,$dbpassword);
	$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
}catch(PDOException $e){
	http_response_code(500);
}

//GETパラメータの検証、数字のみ && 0より大きいか、エラー時は400response
if(!preg_match("/^[0-9]+$/",$limit)|| $limit <= 0){
	http_response_code(400);
}

//DBから値を取ってくる
$record = $db->prepare('SELECT value FROM prechallenge3 WHERE value<=? ORDER BY value');
$record->bindParam(1,$limit,PDO::PARAM_INT);
$record->execute();

//クエリ結果を配列にしてbit全探索
$values = $record->fetchAll(PDO::FETCH_COLUMN);
$ans = solve($values,intval($limit));

$json = json_encode($ans);
if(json_last_error() === JSON_ERROR_NONE){
	echo $json;
}else{
	http_response_code(500);
}

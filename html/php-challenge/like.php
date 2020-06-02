<?php
session_start();
require './dbconnect.php';

if(is_numeric($_GET['id']) && isset($_SESSION['id'])){
	$postId = $_GET['id'];
	$record = $db->prepare('SELECT COUNT(*) AS cnt FROM likes WHERE post_id=? AND liked_member_id=?');
	$insert = $db->prepare('INSERT INTO likes SET post_id=?, liked_member_id=?');
	$delete = $db->prepare('DELETE FROM likes WHERE post_id=? AND liked_member_id=?');
	$record->execute(array(
		$postId,
		$_SESSION['id']
	));
	$cnt = $record->fetch()['cnt'];
	if($cnt == 0){
		$insert->execute(array(
			$postId,
			$_SESSION['id']
		));
	}else{
		$delete->execute(array(
			$postId,
			$_SESSION['id']
		));
	}
}
header('Location: index.php');
exit();

?>
<?php
session_start();
require './dbconnect.php';

if(is_numeric($_GET['id']) && isset($_SESSION['id'])){
	$postId = $_GET['id'];
	$record = $db->prepare('SELECT COUNT(*) AS cnt FROM retweet WHERE post_id=? AND retweeted_member_id=?');
	$insert = $db->prepare('INSERT INTO retweet SET post_id=?, retweeted_member_id=?,created=NOW()');
	$delete = $db->prepare('DELETE FROM retweet WHERE post_id=? AND retweeted_member_id=?');
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

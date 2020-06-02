<?php
session_start();
require('dbconnect.php');

if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
	// ログインしている
	$_SESSION['time'] = time();

	$members = $db->prepare('SELECT * FROM members WHERE id=?');
	$members->execute(array($_SESSION['id']));
	$member = $members->fetch();
} else {
	// ログインしていない
	header('Location: login.php');
	exit();
}

// 投稿を記録する
if (!empty($_POST)) {
	if ($_POST['message'] != '') {
		$message = $db->prepare('INSERT INTO posts SET member_id=?, message=?, reply_post_id=?, created=NOW()');
		$message->execute(array(
			$member['id'],
			$_POST['message'],
			$_POST['reply_post_id']
		));

		header('Location: index.php');
		exit();
	}
}

// 投稿を取得する
$page = $_REQUEST['page'];
if ($page == '') {
	$page = 1;
}
$page = max($page, 1);

// 最終ページを取得する
$counts = $db->query('SELECT COUNT(*) AS cnt FROM (SELECT created FROM posts UNION ALL SELECT created FROM retweet) AS temp');
$cnt = $counts->fetch();
$maxPage = ceil($cnt['cnt'] / 5);
$page = min($page, $maxPage);

$start = ($page - 1) * 5;
$start = max(0, $start);

// 投稿データを取得するSQL($_SESSION['id'],$_SESSION['id'],$start)
$postDataSql = "SELECT m.name, m.picture , postdata.*, rtcount.rtcnt, myrtcount.myrtcnt,likecount.likecnt, mylikecount.mylikecnt FROM members m
								INNER JOIN 
								(SELECT p.id,p.message,p.member_id,p.reply_post_id,p.created ,p.created AS postTime, '' AS retweeted_member_name FROM posts p LEFT JOIN retweet r ON p.id=r.post_id 
								UNION 
								SELECT p.id,p.message,p.member_id,p.reply_post_id,p.created,r.created AS postTime, m.name AS retweet_member_name FROM posts p RIGHT JOIN retweet r ON p.id=r.post_id  LEFT JOIN members m ON r.retweeted_member_id=m.id) AS postdata 
								ON m.id = postdata.member_id
								LEFT JOIN
								(SELECT r.post_id, COUNT(*) AS rtcnt FROM retweet r GROUP BY r.post_id) AS rtcount
								ON postdata.id = rtcount.post_id
								LEFT JOIN
								(SELECT r.post_id, COUNT(*) AS myrtcnt FROM retweet r WHERE r.retweeted_member_id=? GROUP BY r.post_id) AS myrtcount
								ON postdata.id = myrtcount.post_id
								LEFT JOIN
								(SELECT l.post_id, COUNT(*) AS likecnt FROM likes l GROUP BY l.post_id) AS likecount
								ON postdata.id = likecount.post_id
								LEFT JOIN
								(SELECT l.post_id, COUNT(*) AS mylikecnt FROM likes l WHERE l.liked_member_id=? GROUP BY l.post_id) AS mylikecount
								ON postdata.id = mylikecount.post_id
								ORDER BY postTime DESC
								LIMIT ?, 5";

$posts = $db->prepare($postDataSql);
$posts->bindParam(1, $_SESSION['id']);
$posts->bindParam(2, $_SESSION['id']);
$posts->bindParam(3, $start, PDO::PARAM_INT);
$posts->execute();

// 返信の場合
if (isset($_REQUEST['res'])) {
	$response = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id AND p.id=? ORDER BY p.created DESC');
	$response->execute(array($_REQUEST['res']));

	$table = $response->fetch();
	$message = '@' . $table['name'] . ' ' . $table['message'];
}

// htmlspecialcharsのショートカット
function h($value)
{
	return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// 本文内のURLにリンクを設定します
function makeLink($value)
{
	return mb_ereg_replace("(https?)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)", '<a href="\1\2">\1\2</a>', $value);
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>ひとこと掲示板</title>

	<link rel="stylesheet" href="style.css" />
	<link href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" rel="stylesheet">
</head>

<body>
	<div id="wrap">
		<div id="head">
			<h1>ひとこと掲示板</h1>
		</div>
		<div id="content">
			<div style="text-align: right"><a href="logout.php">ログアウト</a></div>
			<form action="" method="post">
				<dl>
					<dt><?php echo h($member['name']); ?>さん、メッセージをどうぞ</dt>
					<dd>
						<textarea name="message" cols="50" rows="5"><?php echo h($message); ?></textarea>
						<input type="hidden" name="reply_post_id" value="<?php echo h($_REQUEST['res']); ?>" />
					</dd>
				</dl>
				<div>
					<p>
						<input type="submit" value="投稿する" />
					</p>
				</div>
			</form>

			<?php
			foreach ($posts as $post) :
			?>
				<div class="msg">
					<img src="member_picture/<?php echo h($post['picture']); ?>" width="48" height="48" alt="<?php echo h($post['name']); ?>" />
					<?php if(!empty($post['retweeted_member_name'])): ?>
					<p style="font-size: small;"><i class="fas fa-retweet"></i> <?php echo h($post['retweeted_member_name']);?>さんがリツイートしました</p>
					<?php endif; ?>
					<p><?php echo makeLink(h($post['message'])); ?><span class="name">（<?php echo h($post['name']); ?>）</span>[<a href="index.php?res=<?php echo h($post['id']); ?>">Re</a>]</p>
					<p class="day"><a href="view.php?id=<?php echo h($post['id']); ?>"><?php echo h($post['created']); ?></a>
						<?php
						if ($post['reply_post_id'] > 0) :
						?>
							<a href="view.php?id=<?php echo
																			h($post['reply_post_id']); ?>">
								返信元のメッセージ</a>
						<?php
						endif;
						?>
						<?php
						if ($_SESSION['id'] == $post['member_id']) :
						?>
							[<a href="delete.php?id=<?php echo h($post['id']); ?>" style="color: #F33;">削除</a>]
						<?php
						endif;
						?>
						<a href="like.php?id=<?php echo h($post['id']) ?>"><i class="fas fa-heart" <?php if(!empty($post['mylikecnt'])){echo 'style="color:#E0245E ;"';} ?> ></i></a>
						<?php if(!empty($post['likecnt'])){echo h($post['likecnt']);} ?>
						<a href="retweet.php?id=<?php echo h($post['id']) ?>"><i class="fas fa-retweet" <?php if(!empty($post['myrtcnt'])){echo 'style="color:#19BF63 ;"';} ?> ></i></a>
						<?php if(!empty($post['rtcnt'])){echo h($post['rtcnt']);} ?>
					</p>
				</div>
			<?php
			endforeach;
			?>

			<ul class="paging">
				<?php
				if ($page > 1) {
				?>
					<li><a href="index.php?page=<?php print($page - 1); ?>">前のページへ</a></li>
				<?php
				} else {
				?>
					<li>前のページへ</li>
				<?php
				}
				?>
				<?php
				if ($page < $maxPage) {
				?>
					<li><a href="index.php?page=<?php print($page + 1); ?>">次のページへ</a></li>
				<?php
				} else {
				?>
					<li>次のページへ</li>
				<?php
				}
				?>
			</ul>
		</div>
	</div>
</body>

</html>
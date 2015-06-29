<?php
date_default_timezone_set("Asia/Manila");
session_start();
require('dbconnect.php');

if(isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time ()){
	//ログインしてる
	$_SESSION['time'] = time();

	$sql = sprintf('SELECT * FROM members WHERE id=%d',
		mysqli_real_escape_string($db, $_SESSION['id'])
		);
	$record = mysqli_query($db,$sql) or die(mysqli_error($db));
	$member = mysqli_fetch_assoc($record);
} else {
	//ログインしてない
	header('Location: login.php');
	exit();
}

//投稿を記録する
if (!empty($_POST)) {
	if ($_POST['message'] != '') {
		$sql = sprintf('INSERT INTO posts SET member_id=%d,message="%s",reply_post_id=%d,created=NOW()',
			mysqli_real_escape_string($db, $member['id']),
			mysqli_real_escape_string($db, $_POST['message']),
			mysqli_real_escape_string($db, $_POST['reply_post_id'])
			);
		mysqli_query($db,$sql) or die(mysqli_error($db));

		header('Location: index2.php');
		exit();
	}
}

//投稿を取得する
$page = $_REQUEST['page'];
if ($page == ''){
	$page = 1;
}
$page = max($page, 1);

//最終ページを取得する
$sql = 'SELECT COUNT(*) AS cnt FROM posts';
$recordSet = mysqli_query($db,$sql);
$table = mysqli_fetch_assoc($recordSet);
$maxPage = ceil($table['cnt'] / 5);
$page = min($page, $maxPage);

$start = ($page - 1) * 5;
$start = max(0,$start);

$sql = sprintf('SELECT m.name, m.picture, p.* FROM members m,posts p WHERE m.id=p.member_id ORDER BY p.created DESC LIMIT %d,5',$start);
$posts = mysqli_query($db,$sql) or die(mysqli_error($db));

//返信の場合
if (isset($_REQUEST['res'])){
	$sql = sprintf('SELECT m.name, m.picture, p.* FROM members m,posts p WHERE m.id=p.member_id AND p.id=%d ORDER BY p.created DESC',mysqli_real_escape_string($db,$_REQUEST['res'])
		);
	$record = mysqli_query($db,$sql) or die(mysqli_error($db));
	$table = mysqli_fetch_assoc($record);
	$message = '@' . $table['name'] . '' . $table['message'];
}

//htmlspecialcharsのショートカット
function h($value){
	return htmlspecialchars($value,ENT_QUOTES,'UTF-8');
}

//本文内のURLにリンクを設定します
function makeLink($value){
	return mb_ereg_replace("(https?|ftp|news)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)","<a href=\"\\1\\2\" target=\"_blank\">\\1\\2</a>",$value);
}

?>

<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel="stylesheet" type="text/css" href="style.css">
	<title>ひとこと掲示板</title>
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
					<dt><?php echo htmlspecialchars($member['name']); ?>さん、メッセージをどうぞ</dt>
					<dd>
						<textarea name="message" cols="50" rows="5"><?php echo htmlspecialchars($message,ENT_QUOTES,'UTF-8'); ?></textarea>
						<input type="hidden" name="reply_post_id" value="<?php echo htmlspecialchars($_REQUEST['res'],ENT_QUOTES,'UTF-8'); ?>" />
					</dd>
				</dl>
				<div>
					<input type="submit" value="投稿する" />
				</div>
			</form>
			<?php
			while ($post = mysqli_fetch_assoc($posts)):
			?>
			<div class="msg">
				<img src="member_picture/<?php echo h($post['picture'],ENT_QUOTES,'UTF-8'); ?>" width="48" height="48" alt="<?php echo h($post['name'],ENT_QUOTES,'UTF-8'); ?>" />
				<p><?php echo makeLink(h($post['message'])); ?><span class="name">(<?php echo h($post['name'],ENT_QUOTES,'UTF-8'); ?>)</span>[<a href="index2.php?res=<?php echo h($post['id'],ENT_QUOTES,'UTF-8'); ?>">Re</a>]</p>
				<p class="day"><a href="view.php?id=<?php echo h($post['id'],ENT_QUOTES,'UTF-8'); ?>"><?php echo h($post['created'],ENT_QUOTES,'UTF-8'); ?></a>
				<?php if ($post['reply_post_id'] > 0): ?>
				<a href="view.php?id=<?php echo h($post['reply_post_id'],ENT_QUOTES,'UTF-8'); ?>">返信元のメッセージ</a>
				<?php endif;?>
				<?php if ($_SESSION['id'] == $post['member_id']): ?>
				[<a href="delete.php?id=<?php echo h($post['id']); ?>" style="color: #F33;">削除</a>]
				<?php endif; ?>
 				</p>
			</div>
		<?php endwhile;?>
		<ul class="paging">
		<?php if ($page > 1) { ?>
		<li><a href="index2.php?page=<?php print($page - 1); ?>">前のページへ</a></li>
		<?php }else{ ?>
		<li>前のページへ</li>
		<?php } ?>
		<?php if ($page < $maxPage) { ?>
		<li><a href="index2.php?page=<?php print($page + 1); ?>">次のページへ</a></li>
		<?php } else { ?>
		<li>次のページへ</li>
		<?php } ?>
		</ul>
		</div>
		<div id="foot">
			<p></p>
		</div>
	</div>
</body>
</html>
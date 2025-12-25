<?php
session_start();
include "db_connection.php";

$id = intval($_GET['id']);
$redirectTarget = 'watch.php?id=' . $id;
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php?redirect=' . urlencode($redirectTarget));
	exit;
}
$stmt = $conn->prepare("SELECT * FROM movies WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$m = $stmt->get_result()->fetch_assoc();
if (!$m) {
	header('Location: index.php');
	exit;
}

// Increment view count (requires movies.view_count INT DEFAULT 0)
// Count a view only once per user
$views = isset($m['view_count']) ? (int)$m['view_count'] : 0;
$insView = $conn->prepare("INSERT IGNORE INTO movie_views (user_id, movie_id, viewed_at) VALUES (?, ?, NOW())");
$insView->bind_param('ii', $_SESSION['user_id'], $id);
$insView->execute();
if ($insView->affected_rows > 0) {
	$inc = $conn->prepare("UPDATE movies SET view_count = IFNULL(view_count,0) + 1 WHERE id=?");
	$inc->bind_param('i', $id);
	$inc->execute();
	$views++;
}

// Ratings summary
$avgRating = null; $ratingCount = 0;
$rStmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as c FROM ratings WHERE movie_id=?");
$rStmt->bind_param('i', $id);
$rStmt->execute();
$rStmt->bind_result($avgRating, $ratingCount);
$rStmt->fetch();
$rStmt->close();
$avgRating = $avgRating ? round($avgRating, 1) : 0;
?>
<!DOCTYPE html>
<html>
<head>
<title><?= $m['title'] ?></title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
	<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
		<div>
			<h1 style="margin:0"><?= htmlspecialchars($m['title']) ?></h1>
			<div class="help" style="margin-top:6px">Views: <?= $views ?></div>
			<div class="help" style="margin-top:4px">Rating: <?= $ratingCount ? $avgRating . ' / 5 (' . $ratingCount . ' reviews)' : 'No ratings yet' ?></div>
			<?php if(!empty($m['genre'])): ?>
				<div class="help" style="margin-top:8px"><strong>Genre:</strong> <?= htmlspecialchars($m['genre']) ?></div>
			<?php endif; ?>
		</div>
		<a class="btn btn-outline btn-sm" href="index.php">Back</a>
	</div>

	<div class="card" style="margin-top:16px">
		<h3>Description</h3>
		<div style="color:var(--muted)"><?= nl2br(htmlspecialchars($m['description'])) ?></div>
	</div>

<?php
// Ensure we embed Drive file ID only. Accept either an ID or a full URL stored in DB.
function driveIdFromStored($stored){
	$s = trim($stored);
	if (preg_match('/^[a-zA-Z0-9_-]{10,}$/', $s)) return $s;
	if (preg_match('#/file/d/([a-zA-Z0-9_-]+)#', $s, $mm)) return $mm[1];
	if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $s, $mm)) return $mm[1];
	// fallback: attempt parse_url path last segment
	$p = parse_url($s, PHP_URL_PATH);
	if ($p) {
		$parts = explode('/', trim($p, '/'));
		$last = end($parts);
		if (preg_match('/^[a-zA-Z0-9_-]{10,}$/', $last)) return $last;
	}
	return $s;
}
$driveId = driveIdFromStored($m['video_link']);
?>
<div class="video-wrap" style="margin-top:16px">
	<iframe src="https://drive.google.com/file/d/<?= htmlspecialchars($driveId) ?>/preview" allowfullscreen></iframe>
</div>

<?php if(isset($_SESSION['user_id'])): ?>
<form method="post" action="rate_movie.php" class="card" style="margin-top:16px">
	<h3>Rate this movie</h3>
	<input type="hidden" name="movie_id" value="<?= $id ?>">
	<select name="rating">
		<option value="1">★</option>
		<option value="2">★★</option>
		<option value="3">★★★</option>
		<option value="4">★★★★</option>
		<option value="5">★★★★★</option>
	</select>
	<button class="btn btn-block" type="submit">Rate</button>
</form>
<?php endif; ?>

<?php
// Show delete button if admin or uploader
if (isset($_SESSION['user_id'])){
	$isAdmin = ($_SESSION['role'] === 'admin');
	$isOwner = ($_SESSION['user_id'] == $m['uploaded_by']);
	if ($isAdmin || $isOwner){
		echo '<form method="get" action="delete_movie.php" class="card" style="margin-top:16px" onsubmit="return confirm(\'Delete this movie?\')">'.
			 '<h3>Danger zone</h3>'.
			 '<input type="hidden" name="id" value="'.intval($m['id']).'">'.
			 '<button class="btn btn-outline btn-block" type="submit">Delete Movie</button>'.
			 '</form>';
	}
}
?>

</div>

</body>
</html>

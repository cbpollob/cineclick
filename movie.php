<?php
session_start();
include 'db_connection.php';
$movie_id = intval($_GET['id'] ?? 0);
if (!isset($_SESSION['user_id'])){
  header('Location: login.php?redirect=' . urlencode('movie.php?id='.$movie_id));
  exit;
}
$stmt = $conn->prepare("SELECT title, description, video_link FROM movies WHERE id = ?");
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$stmt->bind_result($title, $description, $video_link);
$stmt->fetch();
$stmt->close();

// Calculate average rating
$avg = 0;
$ratingStmt = $conn->prepare("SELECT AVG(rating) AS avg_rating FROM ratings WHERE movie_id = ?");
$ratingStmt->bind_param("i", $movie_id);
$ratingStmt->execute();
$ratingStmt->bind_result($avg_rating);
$ratingStmt->fetch();
$avg = round($avg_rating, 1);
$ratingStmt->close();

// Rating count
$rcStmt = $conn->prepare("SELECT COUNT(*) FROM ratings WHERE movie_id=?");
$rcStmt->bind_param("i", $movie_id);
$rcStmt->execute();
$rcStmt->bind_result($rating_count);
$rcStmt->fetch();
$rcStmt->close();

// Increment view count (requires movies.view_count)
// Only count one view per user
$insView = $conn->prepare("INSERT IGNORE INTO movie_views (user_id, movie_id, viewed_at) VALUES (?, ?, NOW())");
$insView->bind_param('ii', $_SESSION['user_id'], $movie_id);
$insView->execute();
if ($insView->affected_rows > 0) {
  $inc = $conn->prepare("UPDATE movies SET view_count = IFNULL(view_count,0) + 1 WHERE id=?");
  $inc->bind_param('i', $movie_id);
  $inc->execute();
}
?>
<!DOCTYPE html>
<html>
<head>
  <title><?php echo htmlspecialchars($title); ?></title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <h2 style="margin:0"><?php echo htmlspecialchars($title); ?></h2>
    <a class="btn btn-outline btn-sm" href="index.php">Back</a>
  </div>

  <div class="card" style="margin-top:16px">
    <div style="color:var(--muted)"><?php echo nl2br(htmlspecialchars($description)); ?></div>
  </div>

  <?php
  function driveIdFromStored($stored){
    $s = trim((string)$stored);
    if (preg_match('/^[a-zA-Z0-9_-]{10,}$/', $s)) return $s;
    if (preg_match('#/file/d/([a-zA-Z0-9_-]+)#', $s, $m)) return $m[1];
    if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $s, $m)) return $m[1];
    $p = parse_url($s, PHP_URL_PATH);
    if ($p) {
      $parts = explode('/', trim($p, '/'));
      $last = end($parts);
      if (preg_match('/^[a-zA-Z0-9_-]{10,}$/', $last)) return $last;
    }
    return $s;
  }
  $driveId = driveIdFromStored($video_link);
  ?>

  <div class="video-wrap" style="margin-top:16px">
    <iframe src="https://drive.google.com/file/d/<?php echo htmlspecialchars($driveId); ?>/preview" allow="autoplay" allowfullscreen></iframe>
  </div>

  <div class="card" style="margin-top:16px">
    <h3>Average Rating</h3>
    <div style="color:var(--muted)">
      <?php
        if ($rating_count > 0) {
          echo htmlspecialchars($avg) . ' / 5 (' . intval($rating_count) . ' reviews)';
        } else {
          echo 'No ratings yet';
        }
      ?>
    </div>
  </div>

  <?php if(isset($_SESSION['user_id'])): ?>
  <form method="post" action="rate_movie.php" class="card" style="margin-top:16px">
    <h3>Rate this movie</h3>
    <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
    <select name="rating">
      <option value="1">★</option>
      <option value="2">★★</option>
      <option value="3">★★★</option>
      <option value="4">★★★★</option>
      <option value="5">★★★★★</option>
    </select>
    <button class="btn btn-block" type="submit">Submit Rating</button>
  </form>
  <?php else: ?>
  <div class="card" style="margin-top:16px">
    <div class="help" style="margin:0">Log in to rate this movie.</div>
  </div>
  <?php endif; ?>
</div>
</body>
</html>

<?php
session_start();
include "db_connection.php";

// Load distinct genres for filter dropdown
$genresRes = $conn->query("SELECT DISTINCT genre FROM movies WHERE genre <> '' ORDER BY genre ASC");
$genres = $genresRes ? $genresRes->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CineClick</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
  <header class="navbar">
    <a href="index.php" class="logo">🎬 CineClick</a>
    <nav class="nav-links">
      <form class="search-form" method="get" action="index.php" style="gap:6px;flex-wrap:wrap">
        <input type="search" name="q" placeholder="Search movies..." value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
        <select name="genre" style="width:140px">
          <option value="">All genres</option>
          <?php foreach($genres as $g): $gName = $g['genre']; ?>
            <option value="<?= htmlspecialchars($gName) ?>" <?= (isset($_GET['genre']) && $_GET['genre'] === $gName) ? 'selected' : '' ?>><?= htmlspecialchars($gName) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="sort" style="width:150px">
          <?php $sortSel = $_GET['sort'] ?? 'newest'; ?>
          <option value="newest" <?= $sortSel==='newest'?'selected':''; ?>>Newest</option>
          <option value="views" <?= $sortSel==='views'?'selected':''; ?>>Most viewed</option>
          <option value="rating" <?= $sortSel==='rating'?'selected':''; ?>>Top rated</option>
        </select>
        <button type="submit">Search</button>
        <?php if(!empty($_GET)): ?>
          <a class="btn btn-outline btn-sm" href="index.php">Clear</a>
        <?php endif; ?>
      </form>
      <?php if(isset($_SESSION['user_id'])): ?>
        <span style="color:var(--muted)">Hello, <?= htmlspecialchars($_SESSION['name']) ?></span>
        <?php if($_SESSION['role'] === 'user'): ?>
          <a href="request_uploader.php">Request Uploader</a>
        <?php endif; ?>
        <?php if(in_array($_SESSION['role'], ['admin','uploader'])): ?>
          <a href="upload_movie.php">Upload</a>
        <?php endif; ?>
        <?php if($_SESSION['role'] === 'admin'): ?>
          <a href="admin_users.php">Manage Users</a>
        <?php endif; ?>
        <a href="logout.php">Logout</a>
      <?php else: ?>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
      <?php endif; ?>
    </nav>
  </header>

  <section class="hero">
    <div>
      <h1 class="title">Latest Movies</h1>
      <div class="subtitle">Browse recent uploads and enjoy free streaming.</div>
    </div>
  </section>

  <?php
  function shortText($text){
    $text = (string)$text;
    if (function_exists('mb_strimwidth')) {
      return mb_strimwidth($text, 0, 80, '...');
    }
    return (strlen($text) > 80) ? substr($text, 0, 77) . '...' : $text;
  }

  $q = trim($_GET['q'] ?? '');
  $genre = trim($_GET['genre'] ?? '');
  $sort = $_GET['sort'] ?? 'newest';

  $sql =
    "SELECT m.*, IFNULL(AVG(r.rating),0) avg_rating, COUNT(r.id) rating_count
     FROM movies m
     LEFT JOIN ratings r ON r.movie_id = m.id";

  $where = [];
  $params = [];
  $types = '';

  if ($q !== '') {
    $where[] = "(m.title LIKE ? OR m.description LIKE ? OR m.genre LIKE ?)";
    $search = "%" . $q . "%";
    $params[] = $search; $types .= 's';
    $params[] = $search; $types .= 's';
    $params[] = $search; $types .= 's';
  }
  if ($genre !== '') {
    $where[] = "m.genre = ?";
    $params[] = $genre; $types .= 's';
  }

  if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
  }

  // Sort options
  switch($sort){
    case 'views':
      $order = " ORDER BY IFNULL(m.view_count,0) DESC, m.uploaded_at DESC";
      break;
    case 'rating':
      $order = " ORDER BY avg_rating DESC, rating_count DESC, m.uploaded_at DESC";
      break;
    default:
      $order = " ORDER BY m.uploaded_at DESC";
  }

  $sql .= " GROUP BY m.id" . $order;

  if ($types !== '') {
    $stmt = $conn->prepare($sql);
    // bind_param requires references
    $bindParams = [];
    $bindParams[] = & $types;
    foreach ($params as $k => $v) {
      $bindParams[] = & $params[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
    $stmt->execute();
    $res = $stmt->get_result();
  } else {
    $res = $conn->query($sql);
  }

  if ($res === false): ?>
    <div class="no-movies">Could not load movies right now.</div>
  <?php elseif ($res->num_rows === 0): ?>
    <div class="no-movies">No movies yet. Be the first to <a href="upload_movie.php">upload</a>!</div>
  <?php else: ?>
  <div class="movies">
  <?php while($m = $res->fetch_assoc()): ?>
    <?php $views = isset($m['view_count']) ? (int)$m['view_count'] : 0; $avgRating = isset($m['avg_rating']) ? round((float)$m['avg_rating'],1) : 0; $ratingCount = isset($m['rating_count']) ? (int)$m['rating_count'] : 0; ?>
    <article class="movie-card">
      <a class="movie-link" href="watch.php?id=<?= $m['id'] ?>">
        <img src="<?= htmlspecialchars($m['thumbnail_link']) ?>" alt="<?= htmlspecialchars($m['title']) ?> thumbnail" onerror="this.src='https://via.placeholder.com/400x300?text=No+Image'">
        <div class="movie-info">
          <h3><?= htmlspecialchars($m['title']) ?></h3>
          <p style="margin:4px 0;color:var(--muted)"><?= $views ?> views</p>
          <p style="margin:4px 0;color:var(--muted)"><?= $ratingCount ? ($avgRating . ' / 5 · ' . $ratingCount . ' reviews') : 'No ratings yet' ?></p>
          <?php if(!empty($m['genre'])): ?>
            <p><?= htmlspecialchars($m['genre']) ?></p>
          <?php endif; ?>
          <p><?= htmlspecialchars(shortText($m['description'])) ?></p>
        </div>
      </a>
    </article>
  <?php endwhile; ?>
  </div>
  <?php endif; ?>

</div>
</body>
</html>

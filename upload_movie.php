<?php
session_start();
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','uploader'])){
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Movie | CineClick</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <h2 style="margin:0">Add New Movie</h2>
        <a class="btn btn-outline btn-sm" href="index.php">Back</a>
    </div>

    <form class="card" action="save_movie.php" method="POST" enctype="multipart/form-data" style="margin-top:16px">
            <label>Movie Title</label>
            <input type="text" name="title" required>

            <label>Description</label>
            <textarea name="description" required></textarea>

            <label>Genre</label>
            <input type="text" name="genre" placeholder="e.g. Action, Comedy" required>

            <label>Thumbnail (choose one)</label>
            <input type="file" name="thumbnail" accept="image/*">
            <div class="help">Or paste a Google Drive image link/ID</div>
            <input type="url" name="thumbnail_drive" placeholder="Google Drive link or file ID">

            <label>Movie Video Link</label>
            <input type="url" name="video_link" required>

            <button class="btn btn-block" type="submit">Add Movie</button>
    </form>
</div>

</body>
</html>

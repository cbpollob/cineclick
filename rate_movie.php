<?php
session_start();
include "db_connection.php";

if (!isset($_SESSION['user_id'])) die("Login required");

$stmt = $conn->prepare(
 "INSERT INTO ratings(user_id,movie_id,rating)
  VALUES(?,?,?)
  ON DUPLICATE KEY UPDATE rating=VALUES(rating)"
);

$stmt->bind_param(
 "iii",
 $_SESSION['user_id'],
 $_POST['movie_id'],
 $_POST['rating']
);

$stmt->execute();
header("Location: watch.php?id=".$_POST['movie_id']);
exit;

<?php
session_start();
include "db_connection.php";

if(!isset($_SESSION['user_id'])){
    die('Unauthorized');
}

if (!isset($_GET['id'])) {
    die('Missing movie id');
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT uploaded_by, thumbnail_link FROM movies WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) die('Movie not found');
$m = $res->fetch_assoc();

$isAdmin = ($_SESSION['role'] === 'admin');
$isOwner = ($_SESSION['user_id'] == $m['uploaded_by']);

if (!($isAdmin || $isOwner)) {
    die('Forbidden');
}

// delete DB row
$del = $conn->prepare("DELETE FROM movies WHERE id=?");
$del->bind_param("i", $id);
$del->execute();

// remove thumbnail file only if it's a local uploads path
if (!empty($m['thumbnail_link'])){
    $link = (string)$m['thumbnail_link'];
    $isRemote = preg_match('#^https?://#i', $link);
    if (!$isRemote && strpos($link, 'uploads/') === 0){
        $possible = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($link, '/'));
        if (file_exists($possible)) @unlink($possible);
    }
}

header('Location: index.php');
exit;

?>

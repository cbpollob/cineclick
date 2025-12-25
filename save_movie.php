<?php
session_start();
include "db_connection.php";

if(!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','uploader'])){
    die("Unauthorized");
}

$title = $_POST['title'];
$desc  = $_POST['description'];
$genre = trim($_POST['genre'] ?? '');
$video = $_POST['video_link'];
$admin = $_SESSION['user_id'];

// Automatically append uploader name to description
$uploaderName = trim($_SESSION['name'] ?? '');
if ($uploaderName !== '') {
  $tag = "Uploaded by: " . $uploaderName;
  if (stripos($desc, 'Uploaded by:') === false) {
    $desc = rtrim($desc) . "\n\n" . $tag;
  }
}

if ($genre === '') {
  die('Genre is required.');
}

// Normalize Google Drive link: extract file ID if a full URL was provided
function extractDriveId($input){
  $input = trim($input);
  // If it already looks like an ID, return it
  if (preg_match('/^[a-zA-Z0-9_-]{10,}$/', $input)) return $input;
  // Common patterns: /file/d/ID/, id=ID
  if (preg_match('#/file/d/([a-zA-Z0-9_-]+)#', $input, $m)) return $m[1];
  if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $input, $m)) return $m[1];
  // fallback: last path segment
  $parts = explode('/', rtrim(parse_url($input, PHP_URL_PATH), '/'));
  $last = end($parts);
  if (preg_match('/^[a-zA-Z0-9_-]{10,}$/', $last)) return $last;
  return $input; // give back original if nothing found
}

$video = extractDriveId($video);


// Thumbnail: either local upload OR Google Drive link/ID
$thumbDriveInput = trim($_POST['thumbnail_drive'] ?? '');
$thumbnailLink = '';

if ($thumbDriveInput !== '') {
  $thumbId = extractDriveId($thumbDriveInput);
  // Use Drive thumbnail endpoint (requires the file to be shared publicly)
  $thumbnailLink = "https://drive.google.com/thumbnail?id=" . urlencode($thumbId) . "&sz=w400-h300";
} else {
  // Local thumbnail upload
  $uploadDirWeb = 'uploads/thumbnails/';
  $uploadDirFs = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'thumbnails' . DIRECTORY_SEPARATOR;
  if (!is_dir($uploadDirFs)) {
    if (!mkdir($uploadDirFs, 0755, true)) {
      die("Failed to create upload directory.");
    }
  }

  if (!isset($_FILES['thumbnail']) || $_FILES['thumbnail']['error'] !== UPLOAD_ERR_OK) {
    die("Please upload a thumbnail OR provide a Google Drive thumbnail link.");
  }

  $originalName = isset($_FILES['thumbnail']['name']) ? basename($_FILES['thumbnail']['name']) : '';
  $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
  $imgName = time() . "_" . $safeName;
  $fsPath = $uploadDirFs . $imgName;
  $webPath = $uploadDirWeb . $imgName;

  if (!move_uploaded_file($_FILES['thumbnail']['tmp_name'], $fsPath)) {
    die("Failed to move uploaded thumbnail.");
  }

  $thumbnailLink = $webPath;
}

$stmt = $conn->prepare(
  "INSERT INTO movies (title, description, genre, thumbnail_link, video_link, uploaded_by)
   VALUES (?, ?, ?, ?, ?, ?)"
);

$stmt->bind_param("sssssi", $title, $desc, $genre, $thumbnailLink, $video, $admin);
$stmt->execute();

header("Location: index.php");

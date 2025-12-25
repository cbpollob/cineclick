<?php
session_start();
include "db_connection.php";

if (!isset($_SESSION['user_id'])) {
		header('Location: login.php');
		exit;
}

// Admins are already effectively uploaders; uploaders don't need to request.
if (in_array($_SESSION['role'], ['admin', 'uploader'])) {
		header('Location: index.php');
		exit;
}

$userId = intval($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$message = trim($_POST['message'] ?? '');

	// Store request in requests table (hosting-safe: does not require users.uploader_request column)
	// Keep only one active request per user by removing older rows.
	$delOld = $conn->prepare("DELETE FROM requests WHERE user_id=?");
	$delOld->bind_param('i', $userId);
	$delOld->execute();

	$ins = $conn->prepare("INSERT INTO requests (user_id, message) VALUES (?, ?)");
	$ins->bind_param('is', $userId, $message);
	$ins->execute();

		header('Location: index.php?requested=1');
		exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Request Uploader</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
	<h2>Request Uploader Access</h2>
	<p style="color:var(--muted)">Send a request to the admin to allow you to upload movies.</p>

	<form method="post" class="card">
		<label>Message (optional)</label>
		<textarea name="message" placeholder="Why do you want to become an uploader?"></textarea>
		<button class="btn btn-block" type="submit">Send Request</button>
	</form>

	<div class="back-row"><a class="btn btn-outline btn-sm" href="index.php">Back</a></div>
</div>
</body>
</html>


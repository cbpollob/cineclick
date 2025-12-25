<?php
// Temporary helper: resets pollob admin password to pollob123 and verifies it.
$mysqli = new mysqli('localhost', 'root', '', 'cineclick_db');
if ($mysqli->connect_error) {
    die('DB connection failed: ' . $mysqli->connect_error);
}

$username = 'pollob';
$email = 'pollob@example.com';
$passwordPlain = 'pollob123';
$hash = password_hash($passwordPlain, PASSWORD_DEFAULT);

// Create row if missing, else update
$stmt = $mysqli->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin') ON DUPLICATE KEY UPDATE password=VALUES(password), role='admin', username=VALUES(username), email=VALUES(email)");
$stmt->bind_param('sss', $username, $email, $hash);
$stmt->execute();

$stmt2 = $mysqli->prepare("SELECT id, username, email, role, password FROM users WHERE username=? OR email=? LIMIT 1");
$stmt2->bind_param('ss', $username, $email);
$stmt2->execute();
$stmt2->bind_result($id, $u, $e, $r, $dbHash);
$stmt2->fetch();

echo "id={$id} username={$u} email={$e} role={$r}\n";
echo password_verify($passwordPlain, $dbHash) ? "verify_ok\n" : "verify_fail\n";

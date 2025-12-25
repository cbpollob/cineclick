<?php
// One-time admin seed/reset (protect this on hosting!).
include "db_connection.php";

// IMPORTANT (hosting): set a secret key and call create_admin.php?key=YOUR_KEY
// Then delete this file.
$seedKey = 'CHANGE_THIS_KEY';
if (!isset($_GET['key']) || $_GET['key'] !== $seedKey) {
    http_response_code(403);
    die('Forbidden');
}

// Optional: prevent re-running without deleting the file
$lockFile = __DIR__ . DIRECTORY_SEPARATOR . '.admin_seed.lock';
if (file_exists($lockFile)) {
    die('Already seeded. Delete this file if you need to run again.');
}

$username = 'pollob';
$email = 'pollob@example.com';
$passwordPlain = 'pollob123';
$password = password_hash($passwordPlain, PASSWORD_DEFAULT);
$role = 'admin';

$check = $conn->prepare("SELECT id FROM users WHERE username=? OR email=?");
$check->bind_param("ss", $username, $email);
$check->execute();

// Avoid get_result() so this works even without mysqlnd
$check->store_result();
$existingId = null;
$check->bind_result($existingId);
$hasExisting = ($check->num_rows > 0) && $check->fetch();

if ($hasExisting) {
    // If user exists, reset credentials/role to ensure you can log in.
    $id = intval($existingId);
    $upd = $conn->prepare("UPDATE users SET username=?, email=?, password=?, role=? WHERE id=?");
    $upd->bind_param("ssssi", $username, $email, $password, $role, $id);
    $upd->execute();
    echo "Admin 'pollob' already existed. Password reset to '{$passwordPlain}'. Please delete or change this file after running.";
    exit;
}

$stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $username, $email, $password, $role);
$stmt->execute();

echo "Admin 'pollob' created with password '{$passwordPlain}'. Please delete or change this file after running.";

@file_put_contents($lockFile, 'seeded');

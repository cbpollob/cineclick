<?php
// Dev helper: verify pollob login logic matches login.php.
$conn = new mysqli('localhost','root','','cineclick_db');
if ($conn->connect_error) die("connfail\n");

$login = $argv[1] ?? 'pollob';
$pass = $argv[2] ?? 'pollob123';

$stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE email=? OR username=? LIMIT 1");
$stmt->bind_param('ss', $login, $login);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($id, $username, $hash, $role);

if ($stmt->num_rows !== 1) {
    echo "not_found\n";
    exit;
}
$stmt->fetch();

echo password_verify($pass, $hash) ? "verify_ok\n" : "verify_fail\n";
echo "id={$id} username={$username} role={$role}\n";

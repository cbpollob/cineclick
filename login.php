<?php
session_start();
include "db_connection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Optional redirect target (only allow local paths)
    $redirect = trim($_POST["redirect"] ?? 'index.php');
    if (preg_match('/^https?:\/\//i', $redirect) || strpos($redirect, '//') === 0) {
        $redirect = 'index.php';
    }

    // Accept either email or username in the same field
    $login = trim($_POST["login"] ?? ($_POST["email"] ?? ''));
    $pass  = $_POST["password"];

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE email=? OR username=? LIMIT 1");
    $stmt->bind_param("ss", $login, $login);
    $stmt->execute();
    $stmt->store_result();

    $id = null;
    $username = null;
    $hash = null;
    $role = null;
    $stmt->bind_result($id, $username, $hash, $role);

    if ($stmt->num_rows !== 1) {
        $error = "User not found.";
    } elseif (!$stmt->fetch()) {
        $error = "Login failed. Please try again.";
    } elseif (!password_verify($pass, $hash)) {
        $error = "Wrong password.";
    } else {
        $_SESSION["user_id"] = $id;
        $_SESSION["name"]    = $username;
        $_SESSION["role"]    = $role;
        header("Location: " . $redirect);
        exit;
    }
}

// Support redirect param on GET as well
$redirect = $_GET['redirect'] ?? 'index.php';
if (preg_match('/^https?:\/\//i', $redirect) || strpos($redirect, '//') === 0) {
    $redirect = 'index.php';
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Login</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="style.css">
</head>
<body class="auth">
<div class="auth-stack">
    <div class="auth-brand">
        <div class="brand">CineClick</div>
        <div class="tagline">Login to continue</div>
    </div>
    <form method="post" class="card auth-card">
        <h2>Login</h2>
        <?= isset($error) ? "<p class='error'>".htmlspecialchars($error)."</p>" : "" ?>
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
        <input type="text" name="login" placeholder="Email or Username" autocomplete="username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button class="btn btn-block" type="submit">Login</button>
        <p class="help" style="margin:10px 0 0;text-align:center">No account? <a href="register.php">Register</a></p>
        <div class="back-row">
            <a class="btn btn-outline btn-block" href="index.php">Back to Home</a>
        </div>
    </form>
</div>
</body>
</html>

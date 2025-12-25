<?php
session_start();
include "db_connection.php";

$loginType = isset($_POST['login_type']) ? $_POST['login_type'] : (isset($_GET['type']) ? $_GET['type'] : 'user');

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
    } elseif ($loginType === 'admin' && $role !== 'admin') {
        $error = "Access denied. Admin credentials required.";
    } else {
        $_SESSION["user_id"] = $id;
        $_SESSION["name"]    = $username;
        $_SESSION["role"]    = $role;
        
        // Redirect admin to admin panel, users to their destination
        if ($role === 'admin' && $loginType === 'admin') {
            header("Location: admin_users.php");
        } else {
            header("Location: " . $redirect);
        }
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
<title>Login - CineClick</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="style.css">
</head>
<body class="auth">
<div class="auth-stack">
    <div class="auth-brand">
        <div class="brand">🎬 CineClick</div>
        <div class="tagline">Your gateway to endless entertainment</div>
    </div>
    
    <div class="auth-tabs">
        <button type="button" class="auth-tab <?= $loginType !== 'admin' ? 'active' : '' ?>" onclick="switchTab('user')">
            <span class="auth-tab-icon">👤</span>User Login
        </button>
        <button type="button" class="auth-tab <?= $loginType === 'admin' ? 'active' : '' ?>" onclick="switchTab('admin')">
            <span class="auth-tab-icon">🔐</span>Admin Login
        </button>
    </div>
    
    <form method="post" class="card auth-card" id="loginForm">
        <h2><?= $loginType === 'admin' ? '🛡️ Admin Access' : '🎥 Welcome Back' ?></h2>
        <?= isset($error) ? "<p class='error'>".htmlspecialchars($error)."</p>" : "" ?>
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
        <input type="hidden" name="login_type" id="loginType" value="<?= htmlspecialchars($loginType) ?>">
        <input type="text" name="login" placeholder="<?= $loginType === 'admin' ? 'Admin Username or Email' : 'Email or Username' ?>" autocomplete="username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button class="btn btn-block" type="submit"><?= $loginType === 'admin' ? 'Access Admin Panel' : 'Sign In' ?></button>
        
        <?php if($loginType !== 'admin'): ?>
        <p class="help" style="margin:14px 0 0;text-align:center">New to CineClick? <a href="register.php">Create Account</a></p>
        <?php else: ?>
        <p class="help" style="margin:14px 0 0;text-align:center;font-size:12px">🔒 Authorized personnel only</p>
        <?php endif; ?>
        
        <div class="back-row">
            <a class="btn btn-outline btn-block" href="index.php">← Back to Movies</a>
        </div>
    </form>
    
    <div class="auth-decoration">
        <span>🎬 Movies</span>
        <span>•</span>
        <span>📽️ Streaming</span>
        <span>•</span>
        <span>⭐ Reviews</span>
    </div>
</div>

<script>
function switchTab(type) {
    document.getElementById('loginType').value = type;
    window.location.href = 'login.php?type=' + type;
}
</script>
</body>
</html>

<?php
include "db_connection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name  = $_POST["name"];
    $email = $_POST["email"];
    $pass  = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // Always register new signups as 'user'. Admins/uploaders are created by the preset admin.
    $role = 'user';

    $check = $conn->prepare("SELECT id FROM users WHERE email=?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "Email already exists";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO users (username, email, password, role)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("ssss", $name, $email, $pass, $role);
        $stmt->execute();

        header("Location: login.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Register - CineClick</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="style.css">
</head>
<body class="auth">

<div class="auth-stack">
    <div class="auth-brand">
        <div class="brand">🎬 CineClick</div>
        <div class="tagline">Join the movie community</div>
    </div>

    <form method="post" class="card auth-card no-tabs">
        <h2>🎟️ Create Account</h2>

        <?= isset($error) ? "<p class='error'>".htmlspecialchars($error)."</p>" : "" ?>

        <input name="name" placeholder="Choose a username" required>
        <input type="email" name="email" placeholder="Email address" required>
        <input type="password" name="password" placeholder="Create a password" required>

        <!-- New registrations are users by default -->
        <input type="hidden" name="role" value="user">

        <button class="btn btn-block" type="submit">🚀 Start Watching</button>
        <p class="help" style="margin:14px 0 0;text-align:center">Already have an account? <a href="login.php">Sign In</a></p>
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

</body>
</html>

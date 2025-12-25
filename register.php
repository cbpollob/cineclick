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
<title>Register</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="style.css">
</head>
<body class="auth">

<div class="auth-stack">
    <div class="auth-brand">
        <div class="brand">CineClick</div>
        <div class="tagline">Create your account</div>
    </div>

    <form method="post" class="card auth-card">
        <h2>Register</h2>

        <?= isset($error) ? "<p class='error'>".htmlspecialchars($error)."</p>" : "" ?>

        <input name="name" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>

        <!-- New registrations are users by default -->
        <input type="hidden" name="role" value="user">

        <button class="btn btn-block" type="submit">Create Account</button>
        <p class="help" style="margin:10px 0 0;text-align:center">Already have an account? <a href="login.php">Login</a></p>
        <div class="back-row">
            <a class="btn btn-outline btn-block" href="index.php">Back to Home</a>
        </div>
    </form>
</div>

</body>
</html>

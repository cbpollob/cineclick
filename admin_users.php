<?php
session_start();
include "db_connection.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header('Location: login.php');
    exit;
}

// handle deletion or role change
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    // approve/deny uploader requests (stored in requests table)
    if (isset($_POST['approve_request_user_id'])){
      $id = intval($_POST['approve_request_user_id']);
      $role = 'uploader';
      $stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
      $stmt->bind_param('si', $role, $id);
      $stmt->execute();

      $delReq = $conn->prepare("DELETE FROM requests WHERE user_id=?");
      $delReq->bind_param('i', $id);
      $delReq->execute();
    }
    if (isset($_POST['deny_request_user_id'])){
      $id = intval($_POST['deny_request_user_id']);
      $delReq = $conn->prepare("DELETE FROM requests WHERE user_id=?");
      $delReq->bind_param('i', $id);
      $delReq->execute();
    }

  // create uploader (producer)
  if (isset($_POST['create_uploader'])){
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $passwordPlain = $_POST['password'] ?? '';

    if ($username === '' || $email === '' || $passwordPlain === ''){
      $error = "All fields are required to create an uploader.";
    } else {
      $check = $conn->prepare("SELECT id FROM users WHERE email=? OR username=?");
      $check->bind_param("ss", $email, $username);
      $check->execute();
      $check->store_result();

      if ($check->num_rows > 0){
        $error = "Username or email already exists.";
      } else {
        $hash = password_hash($passwordPlain, PASSWORD_DEFAULT);
        $role = 'uploader';
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $hash, $role);
        $stmt->execute();
      }
    }
  }

    if (isset($_POST['delete_id'])){
        $id = intval($_POST['delete_id']);
    // Never delete admins (keeps preset admin safe)
    $chk = $conn->prepare("SELECT role FROM users WHERE id=?");
    $chk->bind_param("i", $id);
    $chk->execute();
    $chk->store_result();
    $chk->bind_result($existingRole);
    $hasRole = ($chk->num_rows > 0) && $chk->fetch();
    if ($hasRole && $existingRole !== 'admin'){
      $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
    } else {
      $error = "Cannot delete an admin account.";
    }
    }
    if (isset($_POST['promote_id']) && in_array($_POST['new_role'], ['user','uploader','admin'])){
        $id = intval($_POST['promote_id']);
        $role = $_POST['new_role'];
    // Keep exactly one admin: block promoting others to admin
    if ($role === 'admin'){
      $error = "Admin role is reserved for the preset admin.";
    } else {
      $stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
      $stmt->bind_param("si", $role, $id);
      $stmt->execute();
    }
    }
  // Only redirect on success; show $error on same page when something fails.
  if (!isset($error)) {
    header('Location: admin_users.php');
    exit;
  }
}

// Pending uploader requests (requests table)
$pending = $conn->query(
  "SELECT r.user_id, u.username, u.email, r.message, r.created_at\n"
  . "FROM requests r JOIN users u ON u.id=r.user_id\n"
  . "ORDER BY r.created_at DESC"
);

// Your DB schema doesn't include created_at, so sort by id desc
$res = $conn->query("SELECT id, username, email, role FROM users ORDER BY id DESC");
$loadError = ($res === false) ? "Failed to load users list." : null;
?>
<!DOCTYPE html>
<html>
<head>
<title>Manage Users</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <h2 style="margin:0">Manage Users</h2>
    <a class="btn btn-outline btn-sm" href="index.php">Back</a>
  </div>

  <div class="card" style="margin:16px 0">
    <h3 style="margin-top:0">Uploader Requests</h3>
    <?php if($pending && $pending->num_rows > 0): ?>
      <table class="table">
        <thead>
          <tr><th>User</th><th>Email</th><th>Message</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php while($p = $pending->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($p['username']) ?></td>
            <td><?= htmlspecialchars($p['email']) ?></td>
            <td><?= htmlspecialchars($p['message'] ?? '') ?></td>
            <td>
              <div class="actions">
                <form method="post">
                  <input type="hidden" name="approve_request_user_id" value="<?= $p['user_id'] ?>">
                  <button class="btn btn-sm" type="submit">Approve</button>
                </form>
                <form method="post">
                  <input type="hidden" name="deny_request_user_id" value="<?= $p['user_id'] ?>">
                  <button class="btn btn-outline btn-sm" type="submit">Deny</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p style="color:var(--muted);margin:0">No pending requests.</p>
    <?php endif; ?>
  </div>

  <div class="card" style="margin:16px 0">
    <h3 style="margin-top:0">Create Uploader</h3>
    <?php if(isset($error)): ?>
      <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if(isset($loadError)): ?>
      <p class="error"><?= htmlspecialchars($loadError) ?></p>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="create_uploader" value="1">
      <input name="username" placeholder="Uploader name" required>
      <input type="email" name="email" placeholder="Uploader email" required>
      <input type="password" name="password" placeholder="Temporary password" required>
      <button class="btn btn-block" type="submit">Create Uploader</button>
    </form>
  </div>

  <div class="card" style="margin:16px 0">
    <h3 style="margin-top:0">All Users</h3>
    <table class="table">
      <thead>
        <tr><th>User</th><th>Email</th><th>Role</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php if($res): while($u = $res->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($u['username']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><?= htmlspecialchars($u['role']) ?></td>
          <td>
            <div class="actions">
              <form method="post">
                <input type="hidden" name="delete_id" value="<?= $u['id'] ?>">
                <button class="btn btn-outline btn-sm" type="submit" onclick="return confirm('Delete user?')">Delete</button>
              </form>
              <form method="post" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <input type="hidden" name="promote_id" value="<?= $u['id'] ?>">
                <select name="new_role" class="table-select">
                  <option value="user" <?= $u['role']==='user' ? 'selected' : '' ?>>user</option>
                  <option value="uploader" <?= $u['role']==='uploader' ? 'selected' : '' ?>>uploader</option>
                  <option value="admin" <?= $u['role']==='admin' ? 'selected' : '' ?>>admin</option>
                </select>
                <button class="btn btn-sm" type="submit">Change</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>

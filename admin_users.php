<?php
session_start();
include "db_connection.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header('Location: login.php?type=admin');
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

// Get stats for dashboard
$statsQuery = $conn->query("SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as users,
    SUM(CASE WHEN role = 'uploader' THEN 1 ELSE 0 END) as uploaders,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins
    FROM users");
$stats = $statsQuery ? $statsQuery->fetch_assoc() : ['total_users' => 0, 'users' => 0, 'uploaders' => 0, 'admins' => 0];

$movieStats = $conn->query("SELECT COUNT(*) as total_movies FROM movies");
$movieCount = $movieStats ? $movieStats->fetch_assoc()['total_movies'] : 0;

$pendingCount = $pending ? $pending->num_rows : 0;
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard - CineClick</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="style.css">
<style>
.admin-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    flex-wrap:wrap;
    margin-bottom:24px;
    padding-bottom:20px;
    border-bottom:1px solid rgba(255,255,255,0.1);
}
.admin-title{
    display:flex;
    align-items:center;
    gap:12px;
}
.admin-title h1{
    margin:0;
    font-size:24px;
    color:var(--accent);
}
.admin-badge{
    background:linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
    color:#fff;
    padding:6px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
}
.admin-nav{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}
.stats-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));
    gap:16px;
    margin-bottom:24px;
}
.stat-card{
    background:linear-gradient(135deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02));
    border:1px solid rgba(255,255,255,0.1);
    border-radius:12px;
    padding:20px;
    text-align:center;
}
.stat-icon{font-size:28px;margin-bottom:8px}
.stat-number{font-size:32px;font-weight:700;color:var(--accent)}
.stat-label{color:var(--muted);font-size:13px;margin-top:4px}
.section-title{
    display:flex;
    align-items:center;
    gap:10px;
    margin:0 0 16px;
    font-size:18px;
}
.section-title span{font-size:20px}
.maintenance-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));
    gap:12px;
    margin-bottom:24px;
}
.maintenance-btn{
    display:flex;
    align-items:center;
    gap:10px;
    padding:16px;
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:10px;
    color:inherit;
    text-decoration:none;
    transition:all 0.2s;
}
.maintenance-btn:hover{
    background:rgba(255,255,255,0.08);
    border-color:var(--accent);
    text-decoration:none;
}
.maintenance-btn .icon{font-size:24px}
.maintenance-btn .text{text-align:left}
.maintenance-btn .text strong{display:block;margin-bottom:2px}
.maintenance-btn .text small{color:var(--muted);font-size:12px}
.role-badge{
    display:inline-block;
    padding:4px 10px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
}
.role-admin{background:linear-gradient(135deg, #7c3aed, #6d28d9);color:#fff}
.role-uploader{background:linear-gradient(135deg, #059669, #047857);color:#fff}
.role-user{background:rgba(255,255,255,0.1);color:var(--muted)}
</style>
</head>
<body>
<div class="container">
  <div class="admin-header">
    <div class="admin-title">
      <h1>🎬 Admin Dashboard</h1>
      <span class="admin-badge">🛡️ <?= htmlspecialchars($_SESSION['name']) ?></span>
    </div>
    <nav class="admin-nav">
      <a class="btn btn-sm" href="upload_movie.php">📤 Upload Movie</a>
      <a class="btn btn-outline btn-sm" href="index.php">🏠 View Site</a>
      <a class="btn btn-outline btn-sm" href="logout.php">🚪 Logout</a>
    </nav>
  </div>

  <!-- Quick Stats -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">👥</div>
      <div class="stat-number"><?= $stats['total_users'] ?></div>
      <div class="stat-label">Total Users</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🎬</div>
      <div class="stat-number"><?= $movieCount ?></div>
      <div class="stat-label">Movies</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">📤</div>
      <div class="stat-number"><?= $stats['uploaders'] ?></div>
      <div class="stat-label">Uploaders</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">📋</div>
      <div class="stat-number"><?= $pendingCount ?></div>
      <div class="stat-label">Pending Requests</div>
    </div>
  </div>

  <!-- Quick Actions / Maintenance -->
  <div class="card" style="margin-bottom:20px">
    <h3 class="section-title"><span>🔧</span> Quick Actions & Maintenance</h3>
    <div class="maintenance-grid">
      <a href="upload_movie.php" class="maintenance-btn">
        <span class="icon">📤</span>
        <div class="text">
          <strong>Upload Movie</strong>
          <small>Add new content</small>
        </div>
      </a>
      <a href="index.php" class="maintenance-btn">
        <span class="icon">🎥</span>
        <div class="text">
          <strong>Browse Movies</strong>
          <small>View all content</small>
        </div>
      </a>
      <a href="#create-uploader" class="maintenance-btn scroll-link" data-target="create-uploader">
        <span class="icon">➕</span>
        <div class="text">
          <strong>Create Uploader</strong>
          <small>Add new producer</small>
        </div>
      </a>
      <a href="#pending-requests" class="maintenance-btn scroll-link" data-target="pending-requests">
        <span class="icon">📋</span>
        <div class="text">
          <strong>Review Requests</strong>
          <small><?= $pendingCount ?> pending</small>
        </div>
      </a>
    </div>
  </div>

  <!-- Pending Requests -->
  <div class="card" style="margin:16px 0" id="pending-requests">
    <h3 class="section-title"><span>📋</span> Uploader Requests</h3>
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
                  <button class="btn btn-sm" type="submit">✓ Approve</button>
                </form>
                <form method="post">
                  <input type="hidden" name="deny_request_user_id" value="<?= $p['user_id'] ?>">
                  <button class="btn btn-outline btn-sm" type="submit">✗ Deny</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p style="color:var(--muted);margin:0;padding:20px;text-align:center">✓ No pending requests</p>
    <?php endif; ?>
  </div>

  <!-- Create Uploader -->
  <div class="card" style="margin:16px 0" id="create-uploader">
    <h3 class="section-title"><span>➕</span> Create Uploader Account</h3>
    <?php if(isset($error)): ?>
      <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if(isset($loadError)): ?>
      <p class="error"><?= htmlspecialchars($loadError) ?></p>
    <?php endif; ?>
    <form method="post" style="max-width:400px">
      <input type="hidden" name="create_uploader" value="1">
      <input name="username" placeholder="Uploader username" required>
      <input type="email" name="email" placeholder="Uploader email" required>
      <input type="password" name="password" placeholder="Temporary password" required>
      <button class="btn btn-block" type="submit">🎬 Create Uploader</button>
    </form>
  </div>

  <!-- All Users -->
  <div class="card" style="margin:16px 0">
    <h3 class="section-title"><span>👥</span> All Users</h3>
    <table class="table">
      <thead>
        <tr><th>User</th><th>Email</th><th>Role</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php if($res): while($u = $res->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($u['username']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td>
            <span class="role-badge role-<?= htmlspecialchars($u['role']) ?>">
              <?= $u['role'] === 'admin' ? '🛡️' : ($u['role'] === 'uploader' ? '📤' : '👤') ?>
              <?= htmlspecialchars($u['role']) ?>
            </span>
          </td>
          <td>
            <div class="actions">
              <?php if($u['role'] !== 'admin'): ?>
              <form method="post">
                <input type="hidden" name="delete_id" value="<?= $u['id'] ?>">
                <button class="btn btn-outline btn-sm" type="submit" onclick="return confirm('Delete user?')">🗑️ Delete</button>
              </form>
              <form method="post" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <input type="hidden" name="promote_id" value="<?= $u['id'] ?>">
                <select name="new_role" class="table-select">
                  <option value="user" <?= $u['role']==='user' ? 'selected' : '' ?>>👤 user</option>
                  <option value="uploader" <?= $u['role']==='uploader' ? 'selected' : '' ?>>📤 uploader</option>
                </select>
                <button class="btn btn-sm" type="submit">Change</button>
              </form>
              <?php else: ?>
              <span style="color:var(--muted);font-size:12px">Protected account</span>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle smooth scroll links
    document.querySelectorAll('.scroll-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var targetId = this.getAttribute('data-target');
            var target = document.getElementById(targetId);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
});
</script>
</body>
</html>

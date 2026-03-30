<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
require_once '../database/connection.php';
require_once '../database/layout.php';

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';

    if ($act === 'change_password') {
        $current  = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (!$current || !$new_pass || !$confirm) {
            $error = "All fields are required.";
        } elseif ($new_pass !== $confirm) {
            $error = "New passwords do not match.";
        } elseif (strlen($new_pass) < 6) {
            $error = "Password must be at least 6 characters.";
        } else {
            $id = $_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row && password_verify($current, $row['password'])) {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE users SET password=? WHERE id=?");
                $upd->bind_param("si", $hashed, $id);
                $upd->execute() ? $success = "Password changed successfully." : $error = "Update failed.";
                $upd->close();
            } else {
                $error = "Current password is incorrect.";
            }
        }
    } elseif ($act === 'add_user') {
        $uname = trim($_POST['username'] ?? '');
        $pass  = $_POST['password'] ?? '';
        if ($uname && strlen($pass) >= 6) {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $uname, $hashed);
            $stmt->execute() ? $success = "User '$uname' added." : $error = "Username may already exist.";
            $stmt->close();
        } else { $error = "Username and password (min 6 chars) required."; }
    } elseif ($act === 'delete_user') {
        $del_id = (int)$_POST['user_id'];
        if ($del_id == $_SESSION['user_id']) {
            $error = "You cannot delete your own account.";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
            $stmt->bind_param("i", $del_id);
            $stmt->execute() ? $success = "User deleted." : $error = "Delete failed.";
            $stmt->close();
        }
    }
}

$users = $conn->query("SELECT id, username, created_at FROM users ORDER BY id");
$db_size_res = $conn->query("SELECT ROUND(SUM(data_length + index_length) / 1024, 2) AS size FROM information_schema.tables WHERE table_schema = 'school_timetable'");
$db_size = $db_size_res ? ($db_size_res->fetch_assoc()['size'] ?? '—') : '—';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings — EduSchedule</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="layout">
  <?php render_sidebar(); ?>
  <div class="main">
    <?php render_topbar('Settings', 'Manage account, users and system preferences'); ?>
    <div class="page-content">
      <?php if ($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error">✗ <?= htmlspecialchars($error) ?></div><?php endif; ?>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

        <!-- Change Password -->
        <div class="card">
          <div class="card-header"><div class="card-title">Change Password</div></div>
          <form method="POST">
            <input type="hidden" name="action" value="change_password">
            <div class="form-group" style="margin-bottom:12px">
              <label>Current Password</label>
              <input type="password" name="current_password" required>
            </div>
            <div class="form-group" style="margin-bottom:12px">
              <label>New Password</label>
              <input type="password" name="new_password" required placeholder="Min. 6 characters">
            </div>
            <div class="form-group" style="margin-bottom:16px">
              <label>Confirm New Password</label>
              <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Password</button>
          </form>
        </div>

        <!-- Add User -->
        <div class="card">
          <div class="card-header"><div class="card-title">Add Admin User</div></div>
          <form method="POST">
            <input type="hidden" name="action" value="add_user">
            <div class="form-group" style="margin-bottom:12px">
              <label>Username</label>
              <input type="text" name="username" required placeholder="New username">
            </div>
            <div class="form-group" style="margin-bottom:16px">
              <label>Password</label>
              <input type="password" name="password" required placeholder="Min. 6 characters">
            </div>
            <button type="submit" class="btn btn-primary">Add User</button>
          </form>
        </div>

        <!-- User List -->
        <div class="card">
          <div class="card-header"><div class="card-title">System Users</div></div>
          <div class="table-wrap">
            <table>
              <thead><tr><th>#</th><th>Username</th><th>Created</th><th style="text-align:right">Actions</th></tr></thead>
              <tbody>
              <?php $i=1; while ($u = $users->fetch_assoc()): ?>
                <tr>
                  <td class="text-muted"><?= $i++ ?></td>
                  <td>
                    <strong><?= htmlspecialchars($u['username']) ?></strong>
                    <?= ($u['id'] == $_SESSION['user_id']) ? ' <span class="badge badge-green">You</span>' : '' ?>
                  </td>
                  <td class="text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                  <td style="text-align:right">
                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete user <?= htmlspecialchars(addslashes($u['username'])) ?>?')">
                      <input type="hidden" name="action" value="delete_user">
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                      <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                    <?php else: ?><span class="text-muted" style="font-size:12px">Current</span><?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- System Info -->
        <div class="card">
          <div class="card-header"><div class="card-title">System Information</div></div>
          <div style="display:flex;flex-direction:column;gap:12px">
            <?php
            $info = [
              'Application'   => 'EduSchedule v1.0',
              'PHP Version'   => phpversion(),
              'Database'      => 'school_timetable (MySQL)',
              'DB Size'       => $db_size . ' KB',
              'Logged in as'  => $_SESSION['username'],
              'Server'        => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            ];
            foreach ($info as $k => $v): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border)">
              <span style="font-size:13px;font-weight:600;color:var(--text-secondary)"><?= $k ?></span>
              <span style="font-size:13px;color:var(--text-primary)"><?= htmlspecialchars($v) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
</body>
</html>

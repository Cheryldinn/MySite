<?php
session_start();
require_once '../database/connection.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username && $password) {
            $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    header("Location: dashboard.php");
                    exit;
                }
            }
            $error = "Invalid username or password.";
            $stmt->close();
        } else {
            $error = "Please fill in all fields.";
        }
        $tab = 'login';
    } elseif ($action === 'signup') {
        $username = trim($_POST['signup_username'] ?? '');
        $password = $_POST['signup_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!$username || !$password || !$confirm_password) {
            $error = "All fields are required.";
        } elseif (strlen($username) < 3) {
            $error = "Username must be at least 3 characters.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashed_password);
            
            if ($stmt->execute()) {
                $success = "Account created successfully! Please log in.";
                $tab = 'login';
            } else {
                if ($conn->errno === 1062) {
                    $error = "Username already exists.";
                } else {
                    $error = "Error creating account. Please try again.";
                }
            }
            $stmt->close();
        }
        if (!$success) {
            $tab = 'signup';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — EduSchedule</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.tab-content {
  display: none;
}
.tab-content.active {
  display: block;
}
.form-group { margin-bottom: 16px; }
input { width: 100%; }
.btn { width: 100%; justify-content: center; padding: 12px; font-size: 15px; margin-top: 8px; }
</style>
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <div class="icon">
        <svg width="28" height="28" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
          <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
      </div>
      <h1>EduSchedule</h1>
      <p>School Timetable Management System</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
      <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <!-- Login Form -->
    <div id="login-tab" class="tab-content <?= $tab === 'login' ? 'active' : '' ?>">
      <form method="POST" class="login-form">
        <input type="hidden" name="action" value="login">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" placeholder="Enter your username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autocomplete="username">
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
          Sign In
        </button>
        <div style="text-align: center; margin-top: 20px; font-size: 14px; color: var(--text-secondary);">
          Don't have an account? <a href="#" onclick="switchTab('signup'); return false;" style="color: var(--accent); font-weight: 600; text-decoration: none;">sign up here</a>
        </div>
      </form>
    </div>

    <!-- Signup Form -->
    <div id="signup-tab" class="tab-content <?= $tab === 'signup' ? 'active' : '' ?>">
      <form method="POST" class="login-form">
        <input type="hidden" name="action" value="signup">
        <div class="form-group">
          <label for="signup_username">Username</label>
          <input type="text" id="signup_username" name="signup_username" placeholder="Choose a username (min. 3 characters)"
                 value="<?= htmlspecialchars($_POST['signup_username'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="signup_password">Password</label>
          <input type="password" id="signup_password" name="signup_password" placeholder="Create a password (min. 6 characters)">
        </div>
        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password">
        </div>
        <button type="submit" class="btn btn-primary">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Create Account
        </button>
        <div style="text-align: center; margin-top: 20px; font-size: 14px; color: var(--text-secondary);">
          If you have an account, <a href="#" onclick="switchTab('login'); return false;" style="color: var(--accent); font-weight: 600; text-decoration: none;">login here</a>
        </div>
      </form>
    </div>

  </div>
</div>

<script>
function switchTab(tab) {
  document.getElementById('login-tab').classList.remove('active');
  document.getElementById('signup-tab').classList.remove('active');
  document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
  
  if (tab === 'login') {
    document.getElementById('login-tab').classList.add('active');
    document.querySelectorAll('.auth-tab')[0].classList.add('active');
  } else {
    document.getElementById('signup-tab').classList.add('active');
    document.querySelectorAll('.auth-tab')[1].classList.add('active');
  }
}
</script>

      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon green">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <div>
            <div class="stat-value"><?= $stats['classes'] ?></div>
            <div class="stat-label">Classes</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon blue">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          </div>
          <div>
            <div class="stat-value"><?= $stats['subjects'] ?></div>
            <div class="stat-label">Subjects</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon amber">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </div>
          <div>
            <div class="stat-value"><?= $stats['teachers'] ?></div>
            <div class="stat-label">Teachers</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon red">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          </div>
          <div>
            <div class="stat-value"><?= $stats['slots'] ?></div>
            <div class="stat-label">Scheduled Slots</div>
          </div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">Recent Schedule Entries</div>
              <div class="card-subtitle">Last 8 timetable records</div>
            </div>
            <a href="view.php" class="btn btn-outline btn-sm">View All</a>
          </div>
          <?php if ($recent_timetable->num_rows > 0): ?>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Class</th><th>Subject</th><th>Day</th><th>Time</th></tr></thead>
              <tbody>
              <?php while ($row = $recent_timetable->fetch_assoc()): ?>
                <tr>
                  <td><span class="badge badge-green"><?= htmlspecialchars($row['class_name'].' '.$row['section']) ?></span></td>
                  <td><?= htmlspecialchars($row['subject_name']) ?></td>
                  <td><?= htmlspecialchars($row['day']) ?></td>
                  <td><span class="badge badge-blue"><?= htmlspecialchars($row['time_slot']) ?></span></td>
                </tr>
              <?php endwhile; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div class="empty-state">
            <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <h3>No timetable yet</h3>
            <p>Go to Generate to create your first schedule.</p>
          </div>
          <?php endif; ?>
        </div>

        <div class="card">
          <div class="card-header">
            <div class="card-title">Quick Actions</div>
          </div>
          <div style="display:flex;flex-direction:column;gap:10px">
            <a href="generate.php" class="btn btn-primary" style="justify-content:flex-start">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
              Generate Timetable
            </a>
            <a href="class_list.php" class="btn btn-outline" style="justify-content:flex-start">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
              Manage Classes
            </a>
            <a href="subjects.php" class="btn btn-outline" style="justify-content:flex-start">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
              Manage Subjects
            </a>
            <a href="teachers.php" class="btn btn-outline" style="justify-content:flex-start">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              Manage Teachers
            </a>
            <a href="view.php" class="btn btn-outline" style="justify-content:flex-start">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              View Timetable
            </a>
            <a href="edit.php" class="btn btn-outline" style="justify-content:flex-start">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              Edit Schedule
            </a>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
</body>
</html>

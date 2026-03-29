<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require_once '../database/connection.php';
require_once '../database/layout.php';

$stats = [
    'classes'  => $conn->query("SELECT COUNT(*) as c FROM classes")->fetch_assoc()['c'],
    'subjects' => $conn->query("SELECT COUNT(*) as c FROM subjects")->fetch_assoc()['c'],
    'teachers' => $conn->query("SELECT COUNT(*) as c FROM teachers")->fetch_assoc()['c'],
    'slots'    => $conn->query("SELECT COUNT(*) as c FROM timetable")->fetch_assoc()['c'],
];

$recent_timetable = $conn->query("
    SELECT t.*, c.class_name, c.section, s.subject_name, te.teacher_name
    FROM timetable t
    JOIN classes c ON t.class_id = c.id
    JOIN subjects s ON t.subject_id = s.id
    JOIN teachers te ON t.teacher_id = te.id
    ORDER BY t.created_at DESC LIMIT 8
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — EduSchedule</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="layout">
  <?php render_sidebar(); ?>
  <div class="main">
    <?php render_topbar('Dashboard', 'Welcome back — here\'s your school overview'); ?>
    <div class="page-content">

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

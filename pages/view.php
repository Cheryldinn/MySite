<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
require_once '../database/connection.php';
require_once '../database/layout.php';

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$time_slots = ['7:30–8:30', '8:30–9:30', '9:30–10:30', '10:30–11:30', '11:30–12:30', '13:00–14:00', '14:00–15:00', '15:00–16:00'];

$classes = $conn->query("SELECT * FROM classes ORDER BY class_name, section");
$selected_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// Default to first class if none selected
if (!$selected_class && $classes->num_rows > 0) {
    $first = $classes->fetch_assoc();
    $selected_class = $first['id'];
    $classes->data_seek(0);
}

$class_info = null;
if ($selected_class) {
    $res = $conn->prepare("SELECT * FROM classes WHERE id=?");
    $res->bind_param("i", $selected_class);
    $res->execute();
    $class_info = $res->get_result()->fetch_assoc();
    $res->close();
}

// Build timetable grid
$grid = [];
if ($selected_class) {
    $stmt = $conn->prepare("
        SELECT t.day, t.time_slot, s.subject_name, s.subject_code, te.teacher_name
        FROM timetable t
        JOIN subjects s ON t.subject_id = s.id
        JOIN teachers te ON t.teacher_id = te.id
        WHERE t.class_id = ?
    ");
    $stmt->bind_param("i", $selected_class);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $grid[$row['day']][$row['time_slot']] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Timetable — EduSchedule</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.class-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:24px; }
.class-tab { padding:7px 16px; border-radius:100px; font-size:13px; font-weight:600; text-decoration:none; border:1px solid var(--border-dark); color:var(--text-secondary); transition:all .15s; }
.class-tab:hover { background:var(--cream); }
.class-tab.active { background:var(--accent); color:white; border-color:var(--accent); }
.tt-wrap { overflow-x:auto; }
.tt { border-collapse:separate; border-spacing:5px; width:100%; min-width:700px; }
.tt-head { background:var(--accent); color:white; padding:10px 14px; text-align:center; font-size:12px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; border-radius:6px; }
.tt-day { background:var(--cream); border:1px solid var(--border); border-radius:6px; padding:10px 8px; text-align:center; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:var(--text-secondary); }
.tt-cell { background:white; border:1px solid var(--border); border-radius:6px; padding:10px 12px; min-width:110px; vertical-align:top; min-height:78px; }
.tt-cell:hover { border-color:var(--accent-border); box-shadow:0 2px 10px rgba(45,80,22,.08); }
.tt-cell .subj { font-weight:700; color:var(--accent); font-size:13px; line-height:1.3; }
.tt-cell .code { font-size:10px; font-weight:700; letter-spacing:.06em; color:var(--accent-light); opacity:.7; }
.tt-cell .teacher { font-size:11px; color:var(--text-muted); margin-top:5px; }
.tt-cell.empty { background:var(--off-white); }
.tt-slot { font-size:10px; font-weight:700; color:var(--text-muted); text-align:center; padding:4px; letter-spacing:.04em; }
@media print {
  .sidebar, .topbar, .class-tabs { display:none; }
  .main { margin:0; }
  .page-content { padding:0; }
}
</style>
</head>
<body>
<div class="layout">
  <?php render_sidebar(); ?>
  <div class="main">
    <?php render_topbar('View Timetable', $class_info ? 'Viewing: ' . $class_info['class_name'] . ' ' . $class_info['section'] : 'Select a class below'); ?>
    <div class="page-content">

      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
        <div style="font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text-muted)">Filter by Class</div>
        <div style="display:flex;gap:8px">
          <a href="generate.php" class="btn btn-outline btn-sm">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            Generate
          </a>
          <a href="edit.php?class_id=<?= $selected_class ?>" class="btn btn-outline btn-sm">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit
          </a>
          <button onclick="window.print()" class="btn btn-outline btn-sm">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print
          </button>
        </div>
      </div>

      <div class="class-tabs">
        <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
        <a href="?class_id=<?= $c['id'] ?>" class="class-tab <?= ($c['id'] == $selected_class) ? 'active' : '' ?>">
          <?= htmlspecialchars($c['class_name'] . ' ' . $c['section']) ?>
        </a>
        <?php endwhile; ?>
      </div>

      <?php if ($selected_class): ?>
      <div class="card" style="padding:20px">
        <?php if (empty($grid)): ?>
        <div class="empty-state">
          <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          <h3>No timetable generated</h3>
          <p>Click Generate to create a schedule for this class.</p>
          <a href="generate.php" class="btn btn-primary mt-4" style="display:inline-flex">Generate Now</a>
        </div>
        <?php else: ?>
        <div class="tt-wrap">
          <table class="tt">
            <thead>
              <tr>
                <th class="tt-head" style="width:80px">Time</th>
                <?php foreach ($days as $day): ?>
                <th class="tt-head"><?= $day ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($time_slots as $slot): ?>
              <tr>
                <td class="tt-day"><?= htmlspecialchars($slot) ?></td>
                <?php foreach ($days as $day): ?>
                <?php if (isset($grid[$day][$slot])): $e = $grid[$day][$slot]; ?>
                <td class="tt-cell">
                  <?php if ($e['subject_code']): ?><div class="code"><?= htmlspecialchars($e['subject_code']) ?></div><?php endif; ?>
                  <div class="subj"><?= htmlspecialchars($e['subject_name']) ?></div>
                  <div class="teacher">
                    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="vertical-align:middle"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <?= htmlspecialchars($e['teacher_name']) ?>
                  </div>
                </td>
                <?php else: ?>
                <td class="tt-cell empty"></td>
                <?php endif; ?>
                <?php endforeach; ?>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>
</body>
</html>

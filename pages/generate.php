<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require_once '../database/connection.php';
require_once '../database/layout.php';

$success = $error = '';

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$time_slots = ['7:30–8:30', '8:30–9:30', '9:30–10:30', '10:30–11:30', '11:30–12:30', '13:00–14:00', '14:00–15:00', '15:00–16:00'];

// SMART AUTO-GENERATION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'generate') {
        $class_id = (int)$_POST['class_id'];
        $clear_existing = isset($_POST['clear_existing']);

        if (!$class_id) { $error = "Please select a class."; goto done; }

        $classes_res  = $conn->query("SELECT * FROM classes WHERE id = $class_id");
        $subjects_res = $conn->query("SELECT * FROM subjects ORDER BY id");
        $teachers_res = $conn->query("SELECT * FROM teachers ORDER BY id");

        if ($subjects_res->num_rows == 0 || $teachers_res->num_rows == 0) {
            $error = "Please add subjects and teachers before generating.";
            goto done;
        }

        // Optionally clear existing
        if ($clear_existing) {
            $conn->query("DELETE FROM timetable WHERE class_id = $class_id");
        }

        $subjects = [];
        while ($s = $subjects_res->fetch_assoc()) $subjects[] = $s;

        $teachers = [];
        while ($t = $teachers_res->fetch_assoc()) $teachers[] = $t;

        $teacher_usage = []; // track teacher slots to avoid double booking

        $generated = 0;
        $subj_idx = 0;

        foreach ($days as $day) {
            foreach ($time_slots as $slot) {
                // Check if slot already filled
                $check = $conn->prepare("SELECT id FROM timetable WHERE class_id=? AND day=? AND time_slot=?");
                $check->bind_param("iss", $class_id, $day, $slot);
                $check->execute();
                if ($check->get_result()->num_rows > 0) { $check->close(); continue; }
                $check->close();

                // Pick next subject (round-robin)
                $subj = $subjects[$subj_idx % count($subjects)];
                $subj_idx++;

                // Find a teacher not already teaching at this day+slot
                $teacher = null;
                $tried = 0;
                $t_idx = array_search($subj['id'], array_column($teachers, 'subject_id'));
                $t_idx = ($t_idx !== false) ? $t_idx : 0;

                for ($ti = 0; $ti < count($teachers); $ti++) {
                    $candidate = $teachers[($t_idx + $ti) % count($teachers)];
                    $key = $day . '_' . $slot . '_' . $candidate['id'];
                    if (!isset($teacher_usage[$key])) {
                        $teacher = $candidate;
                        $teacher_usage[$key] = true;
                        break;
                    }
                }

                if (!$teacher) $teacher = $teachers[0]; // fallback

                $stmt = $conn->prepare("INSERT INTO timetable (class_id, subject_id, teacher_id, day, time_slot) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiiss", $class_id, $subj['id'], $teacher['id'], $day, $slot);
                if ($stmt->execute()) $generated++;
                $stmt->close();
            }
        }

        $success = "✓ Generated $generated time slots for the selected class.";
    }

    elseif ($_POST['action'] === 'clear') {
        $class_id = (int)$_POST['class_id'];
        if ($class_id) {
            $conn->query("DELETE FROM timetable WHERE class_id = $class_id");
            $success = "Timetable cleared for selected class.";
        } elseif (isset($_POST['clear_all'])) {
            $conn->query("DELETE FROM timetable");
            $success = "All timetable data has been cleared.";
        }
    }
}

done:
$classes = $conn->query("SELECT * FROM classes ORDER BY class_name, section");
$subject_count = $conn->query("SELECT COUNT(*) as c FROM subjects")->fetch_assoc()['c'];
$teacher_count = $conn->query("SELECT COUNT(*) as c FROM teachers")->fetch_assoc()['c'];
$tt_count = $conn->query("SELECT COUNT(*) as c FROM timetable")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Generate Timetable — EduSchedule</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.info-panel { background: var(--info-pale); border: 1px solid #b8d8e8; border-radius: var(--radius); padding: 16px 20px; margin-bottom: 24px; }
.info-panel h3 { color: var(--info); font-size: 14px; font-weight: 700; margin-bottom: 8px; }
.info-panel ul { list-style: none; font-size: 13px; color: var(--info); display: flex; flex-direction: column; gap: 4px; }
.info-panel ul li::before { content: "✓ "; font-weight: 700; }
.slot-preview { display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; margin-top: 16px; }
.slot-day { background: var(--accent); color: white; border-radius: var(--radius-sm); padding: 6px 10px; text-align: center; font-size: 12px; font-weight: 700; }
.slot-time { background: var(--cream); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 5px 8px; text-align: center; font-size: 11px; color: var(--text-secondary); }
.danger-zone { background: var(--danger-pale); border: 1px solid #f0c4c0; border-radius: var(--radius); padding: 20px 24px; margin-top: 24px; }
.danger-zone h3 { color: var(--danger); font-size: 15px; font-weight: 700; margin-bottom: 4px; }
.danger-zone p { font-size: 13px; color: var(--text-secondary); margin-bottom: 16px; }
</style>
</head>
<body>
<div class="layout">
  <?php render_sidebar(); ?>
  <div class="main">
    <?php render_topbar('Generate Timetable', 'Auto-generate schedules using smart assignment algorithm'); ?>
    <div class="page-content">
      <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error">✗ <?= htmlspecialchars($error) ?></div><?php endif; ?>

      <?php if ($subject_count == 0 || $teacher_count == 0): ?>
      <div class="alert alert-error">
        ⚠ You need at least 1 subject and 1 teacher before generating.
        <a href="subjects.php" style="margin-left:8px;font-weight:700;color:var(--danger)">Add Subjects</a> |
        <a href="teachers.php" style="margin-left:4px;font-weight:700;color:var(--danger)">Add Teachers</a>
      </div>
      <?php endif; ?>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

        <div class="card">
          <div class="card-header"><div class="card-title">Generate Schedule</div></div>

          <div class="info-panel">
            <h3>Smart Algorithm Features</h3>
            <ul>
              <li>Distributes subjects evenly across days</li>
              <li>Avoids teacher double-booking per time slot</li>
              <li>Prefers subject-matched teachers</li>
              <li>Fills <?= count($days) ?> days × <?= count($time_slots) ?> periods = <?= count($days) * count($time_slots) ?> slots</li>
            </ul>
          </div>

          <form method="POST">
            <input type="hidden" name="action" value="generate">
            <div class="form-group" style="margin-bottom:14px">
              <label>Select Class to Schedule</label>
              <select name="class_id" required>
                <option value="">— Choose a class —</option>
                <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name'] . ' ' . $c['section']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;padding:12px;background:var(--cream);border-radius:var(--radius-sm)">
              <input type="checkbox" name="clear_existing" id="clear_existing" value="1" style="width:16px;height:16px;accent-color:var(--accent)">
              <label for="clear_existing" style="font-size:14px;margin:0;cursor:pointer;color:var(--text-secondary)">Clear existing schedule for this class first</label>
            </div>
            <button type="submit" class="btn btn-primary w-full" style="justify-content:center;padding:12px">
              <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
              Generate Timetable
            </button>
          </form>
        </div>

        <div class="card">
          <div class="card-header"><div class="card-title">Schedule Overview</div></div>
          <div class="stats-grid" style="grid-template-columns:1fr 1fr;gap:12px;margin-bottom:0">
            <div class="stat-card">
              <div class="stat-icon green"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
              <div><div class="stat-value"><?= $tt_count ?></div><div class="stat-label">Total Slots</div></div>
            </div>
            <div class="stat-card">
              <div class="stat-icon blue"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
              <div><div class="stat-value"><?= $teacher_count ?></div><div class="stat-label">Teachers</div></div>
            </div>
            <div class="stat-card">
              <div class="stat-icon amber"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></div>
              <div><div class="stat-value"><?= $subject_count ?></div><div class="stat-label">Subjects</div></div>
            </div>
            <div class="stat-card">
              <div class="stat-icon red"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
              <div><div class="stat-value"><?= count($days) * count($time_slots) ?></div><div class="stat-label">Slots/Class</div></div>
            </div>
          </div>

          <div style="margin-top:20px">
            <div style="font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text-muted);margin-bottom:10px">Time Slots Per Day</div>
            <div style="display:flex;flex-direction:column;gap:4px">
              <?php foreach ($time_slots as $i => $slot): ?>
              <div style="display:flex;align-items:center;gap:8px">
                <span style="font-size:11px;font-weight:700;color:var(--text-muted);width:20px"><?= $i+1 ?></span>
                <span style="flex:1;background:var(--cream);border:1px solid var(--border);border-radius:4px;padding:5px 10px;font-size:12px;color:var(--text-secondary)"><?= $slot ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

      </div>

      <div class="danger-zone">
        <h3>⚠ Danger Zone</h3>
        <p>These actions are irreversible. Use with caution.</p>
        <div style="display:flex;gap:12px">
          <form method="POST" onsubmit="return confirm('Clear timetable for this class?')">
            <input type="hidden" name="action" value="clear">
            <select name="class_id" style="padding:8px 12px;border:1px solid #f0c4c0;border-radius:var(--radius-sm);font-family:inherit;font-size:13px;background:white;margin-right:6px">
              <option value="">— Select Class —</option>
              <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name'] . ' ' . $c['section']) ?></option>
              <?php endwhile; ?>
            </select>
            <button type="submit" class="btn btn-danger btn-sm">Clear Class Schedule</button>
          </form>
          <form method="POST" onsubmit="return confirm('CLEAR ALL timetable data? This cannot be undone!')">
            <input type="hidden" name="action" value="clear">
            <input type="hidden" name="clear_all" value="1">
            <input type="hidden" name="class_id" value="0">
            <button type="submit" class="btn btn-danger btn-sm">Clear All Timetables</button>
          </form>
        </div>
      </div>

      <div style="margin-top:20px;text-align:center">
        <a href="view.php" class="btn btn-outline">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          View Generated Timetable
        </a>
      </div>

    </div>
  </div>
</div>
</body>
</html>

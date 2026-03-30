<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
require_once '../database/connection.php';
require_once '../database/layout.php';

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$time_slots = ['7:30–8:30', '8:30–9:30', '9:30–10:30', '10:30–11:30', '11:30–12:30', '13:00–14:00', '14:00–15:00', '15:00–16:00'];

$success = $error = '';

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    if ($act === 'update') {
        $id       = (int)$_POST['id'];
        $subj_id  = (int)$_POST['subject_id'];
        $teach_id = (int)$_POST['teacher_id'];
        $day      = $_POST['day'];
        $slot     = $_POST['time_slot'];
        $class_id = (int)$_POST['class_id'];

        // Check no conflict (same class, day, slot, different id)
        $chk = $conn->prepare("SELECT id FROM timetable WHERE class_id=? AND day=? AND time_slot=? AND id!=?");
        $chk->bind_param("issi", $class_id, $day, $slot, $id);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = "That day/time slot is already taken for this class.";
        } else {
            $stmt = $conn->prepare("UPDATE timetable SET subject_id=?, teacher_id=?, day=?, time_slot=? WHERE id=?");
            $stmt->bind_param("iissi", $subj_id, $teach_id, $day, $slot, $id);
            $stmt->execute() ? $success = "Entry updated successfully." : $error = $conn->error;
            $stmt->close();
        }
        $chk->close();
    } elseif ($act === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM timetable WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute() ? $success = "Entry deleted." : $error = $conn->error;
        $stmt->close();
    } elseif ($act === 'add') {
        $class_id = (int)$_POST['class_id'];
        $subj_id  = (int)$_POST['subject_id'];
        $teach_id = (int)$_POST['teacher_id'];
        $day      = $_POST['day'];
        $slot     = $_POST['time_slot'];

        $chk = $conn->prepare("SELECT id FROM timetable WHERE class_id=? AND day=? AND time_slot=?");
        $chk->bind_param("iss", $class_id, $day, $slot);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = "That slot is already assigned. Edit the existing entry instead.";
        } else {
            $stmt = $conn->prepare("INSERT INTO timetable (class_id, subject_id, teacher_id, day, time_slot) VALUES (?,?,?,?,?)");
            $stmt->bind_param("iiiss", $class_id, $subj_id, $teach_id, $day, $slot);
            $stmt->execute() ? $success = "New entry added." : $error = $conn->error;
            $stmt->close();
        }
        $chk->close();
    }
}

$selected_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : (int)($_POST['class_id'] ?? 0);

$classes  = $conn->query("SELECT * FROM classes ORDER BY class_name, section");
$subjects = $conn->query("SELECT * FROM subjects ORDER BY subject_name");
$teachers = $conn->query("SELECT * FROM teachers ORDER BY teacher_name");

// Build subjects/teachers option lists
$subj_opts = '';
while ($s = $subjects->fetch_assoc()) $subj_opts .= "<option value='{$s['id']}'>".htmlspecialchars($s['subject_name'])."</option>";
$teach_opts = '';
while ($t = $teachers->fetch_assoc()) $teach_opts .= "<option value='{$t['id']}'>".htmlspecialchars($t['teacher_name'])."</option>";

// Get entries for selected class
$entries = [];
if ($selected_class) {
    $stmt = $conn->prepare("
        SELECT t.*, s.subject_name, te.teacher_name
        FROM timetable t
        JOIN subjects s ON t.subject_id = s.id
        JOIN teachers te ON t.teacher_id = te.id
        WHERE t.class_id=?
        ORDER BY FIELD(t.day,'Monday','Tuesday','Wednesday','Thursday','Friday'), t.time_slot
    ");
    $stmt->bind_param("i", $selected_class);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $entries[] = $row;
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Schedule — EduSchedule</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="layout">
  <?php render_sidebar(); ?>
  <div class="main">
    <?php render_topbar('Edit Schedule', 'Add, modify or remove individual timetable entries'); ?>
    <div class="page-content">
      <?php if ($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error">✗ <?= htmlspecialchars($error) ?></div><?php endif; ?>

      <!-- Class filter -->
      <div class="card" style="margin-bottom:20px;padding:16px 24px">
        <form method="GET" style="display:flex;align-items:center;gap:12px">
          <label style="font-size:13px;font-weight:700;white-space:nowrap">Select Class:</label>
          <select name="class_id" style="flex:1;max-width:260px" onchange="this.form.submit()">
            <option value="">— Choose Class —</option>
            <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>" <?= ($c['id'] == $selected_class) ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['class_name'] . ' ' . $c['section']) ?>
            </option>
            <?php endwhile; ?>
          </select>
          <a href="view.php?class_id=<?= $selected_class ?>" class="btn btn-outline btn-sm">View Grid</a>
        </form>
      </div>

      <?php if ($selected_class): ?>
      <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

        <div class="card">
          <div class="card-header">
            <div><div class="card-title">Schedule Entries</div>
            <div class="card-subtitle"><?= count($entries) ?> entries</div></div>
          </div>
          <?php if ($entries): ?>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Day</th><th>Time Slot</th><th>Subject</th><th>Teacher</th><th style="text-align:right">Actions</th></tr></thead>
              <tbody>
              <?php foreach ($entries as $e): ?>
                <tr>
                  <td><span class="badge badge-green"><?= htmlspecialchars($e['day']) ?></span></td>
                  <td class="text-muted" style="font-size:13px"><?= htmlspecialchars($e['time_slot']) ?></td>
                  <td><strong><?= htmlspecialchars($e['subject_name']) ?></strong></td>
                  <td class="text-muted"><?= htmlspecialchars($e['teacher_name']) ?></td>
                  <td style="text-align:right">
                    <div class="flex gap-2" style="justify-content:flex-end">
                      <button class="btn btn-outline btn-sm" onclick="openEdit(<?= htmlspecialchars(json_encode($e)) ?>)">Edit</button>
                      <form method="POST" style="display:inline" onsubmit="return confirm('Delete this entry?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                        <input type="hidden" name="class_id" value="<?= $selected_class ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Del</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div class="empty-state">
            <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <h3>No entries</h3><p>Generate a timetable or add entries manually.</p>
          </div>
          <?php endif; ?>
        </div>

        <!-- Add entry form -->
        <div class="card">
          <div class="card-header"><div class="card-title">Add Entry</div></div>
          <form method="POST">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="class_id" value="<?= $selected_class ?>">
            <div class="form-group" style="margin-bottom:12px">
              <label>Day</label>
              <select name="day" required>
                <?php foreach ($days as $d): ?><option><?= $d ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="form-group" style="margin-bottom:12px">
              <label>Time Slot</label>
              <select name="time_slot" required>
                <?php foreach ($time_slots as $s): ?><option><?= $s ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="form-group" style="margin-bottom:12px">
              <label>Subject</label>
              <select name="subject_id" required>
                <option value="">— Select —</option>
                <?= $subj_opts ?>
              </select>
            </div>
            <div class="form-group" style="margin-bottom:16px">
              <label>Teacher</label>
              <select name="teacher_id" required>
                <option value="">— Select —</option>
                <?= $teach_opts ?>
              </select>
            </div>
            <button type="submit" class="btn btn-primary w-full" style="justify-content:center">
              <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Add Entry
            </button>
          </form>
        </div>

      </div>
      <?php else: ?>
      <div class="empty-state" style="padding:80px 20px">
        <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        <h3>Select a class to edit</h3>
        <p>Use the dropdown above to choose a class.</p>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit Entry</div>
      <button class="modal-close" onclick="closeEdit()">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="edit_id">
      <input type="hidden" name="class_id" value="<?= $selected_class ?>">
      <div class="form-grid" style="margin-bottom:14px">
        <div class="form-group">
          <label>Day</label>
          <select name="day" id="edit_day">
            <?php foreach ($days as $d): ?><option value="<?= $d ?>"><?= $d ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Time Slot</label>
          <select name="time_slot" id="edit_slot">
            <?php foreach ($time_slots as $s): ?><option value="<?= $s ?>"><?= $s ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group" style="margin-bottom:12px">
        <label>Subject</label>
        <select name="subject_id" id="edit_subject">
          <?= $subj_opts ?>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:20px">
        <label>Teacher</label>
        <select name="teacher_id" id="edit_teacher">
          <?= $teach_opts ?>
        </select>
      </div>
      <div class="flex gap-2">
        <button type="button" class="btn btn-outline" onclick="closeEdit()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(e) {
  document.getElementById('edit_id').value = e.id;
  document.getElementById('edit_day').value = e.day;
  document.getElementById('edit_slot').value = e.time_slot;
  document.getElementById('edit_subject').value = e.subject_id;
  document.getElementById('edit_teacher').value = e.teacher_id;
  document.getElementById('editModal').classList.add('open');
}
function closeEdit() { document.getElementById('editModal').classList.remove('open'); }
document.getElementById('editModal').addEventListener('click', e => { if (e.target === document.getElementById('editModal')) closeEdit(); });
</script>
</body>
</html>

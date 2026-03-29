<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require_once '../database/connection.php';
require_once '../database/layout.php';

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name  = trim($_POST['teacher_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subj  = (int)$_POST['subject_id'];
        if ($name) {
            $stmt = $conn->prepare("INSERT INTO teachers (teacher_name, email, subject_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $name, $email, $subj);
            $stmt->execute() ? $success = "Teacher added successfully." : $error = $conn->error;
            $stmt->close();
        } else { $error = "Teacher name is required."; }
    } elseif ($_POST['action'] === 'edit') {
        $id   = (int)$_POST['id'];
        $name  = trim($_POST['teacher_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subj  = (int)$_POST['subject_id'];
        if ($id && $name) {
            $stmt = $conn->prepare("UPDATE teachers SET teacher_name=?, email=?, subject_id=? WHERE id=?");
            $stmt->bind_param("ssii", $name, $email, $subj, $id);
            $stmt->execute() ? $success = "Teacher updated." : $error = $conn->error;
            $stmt->close();
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM teachers WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute() ? $success = "Teacher deleted." : $error = $conn->error;
        $stmt->close();
    }
}

$teachers = $conn->query("SELECT t.*, s.subject_name FROM teachers t LEFT JOIN subjects s ON t.subject_id = s.id ORDER BY t.teacher_name");
$subjects = $conn->query("SELECT * FROM subjects ORDER BY subject_name");
$subj_options = '';
while ($s = $subjects->fetch_assoc()) {
    $subj_options .= "<option value='{$s['id']}'>" . htmlspecialchars($s['subject_name']) . "</option>";
}
$subjects->data_seek(0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Teachers — EduSchedule</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="layout">
  <?php render_sidebar(); ?>
  <div class="main">
    <?php render_topbar('Teachers', 'Manage teaching staff and subject assignments'); ?>
    <div class="page-content">
      <?php if ($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error">✗ <?= htmlspecialchars($error) ?></div><?php endif; ?>

      <div style="display:grid;grid-template-columns:360px 1fr;gap:20px;align-items:start">
        <div class="card">
          <div class="card-header"><div class="card-title">Add Teacher</div></div>
          <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group" style="margin-bottom:14px">
              <label>Full Name</label>
              <input type="text" name="teacher_name" placeholder="e.g. Mr. Jean Nkomo" required>
            </div>
            <div class="form-group" style="margin-bottom:14px">
              <label>Email <span class="text-muted">(optional)</span></label>
              <input type="email" name="email" placeholder="teacher@school.cm">
            </div>
            <div class="form-group" style="margin-bottom:16px">
              <label>Primary Subject</label>
              <select name="subject_id">
                <option value="0">— Select Subject —</option>
                <?= $subj_options ?>
              </select>
            </div>
            <button type="submit" class="btn btn-primary w-full" style="justify-content:center">
              <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Add Teacher
            </button>
          </form>
        </div>

        <div class="card">
          <div class="card-header">
            <div><div class="card-title">All Teachers</div>
            <div class="card-subtitle"><?= $teachers->num_rows ?> teacher<?= $teachers->num_rows != 1 ? 's' : '' ?></div></div>
          </div>
          <?php if ($teachers->num_rows > 0): ?>
          <div class="table-wrap">
            <table>
              <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Subject</th><th style="text-align:right">Actions</th></tr></thead>
              <tbody>
              <?php $i = 1; while ($row = $teachers->fetch_assoc()): ?>
                <tr>
                  <td class="text-muted"><?= $i++ ?></td>
                  <td><strong><?= htmlspecialchars($row['teacher_name']) ?></strong></td>
                  <td class="text-muted"><?= $row['email'] ? htmlspecialchars($row['email']) : '—' ?></td>
                  <td><?= $row['subject_name'] ? '<span class="badge badge-amber">'.htmlspecialchars($row['subject_name']).'</span>' : '<span class="text-muted">—</span>' ?></td>
                  <td style="text-align:right">
                    <div class="flex gap-2" style="justify-content:flex-end">
                      <button class="btn btn-outline btn-sm" onclick="openEdit(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['teacher_name'])) ?>', '<?= htmlspecialchars(addslashes($row['email'])) ?>', <?= $row['subject_id'] ?? 0 ?>)">Edit</button>
                      <form method="POST" style="display:inline" onsubmit="return confirm('Delete this teacher?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div class="empty-state">
            <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <h3>No teachers yet</h3><p>Add your first teacher using the form.</p>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit Teacher</div>
      <button class="modal-close" onclick="closeEdit()">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit_id">
      <div class="form-group" style="margin-bottom:14px">
        <label>Full Name</label>
        <input type="text" name="teacher_name" id="edit_name" required>
      </div>
      <div class="form-group" style="margin-bottom:14px">
        <label>Email</label>
        <input type="email" name="email" id="edit_email">
      </div>
      <div class="form-group" style="margin-bottom:20px">
        <label>Primary Subject</label>
        <select name="subject_id" id="edit_subject">
          <option value="0">— Select Subject —</option>
          <?= $subj_options ?>
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
function openEdit(id, name, email, subjId) {
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_name').value = name;
  document.getElementById('edit_email').value = email;
  document.getElementById('edit_subject').value = subjId;
  document.getElementById('editModal').classList.add('open');
}
function closeEdit() { document.getElementById('editModal').classList.remove('open'); }
document.getElementById('editModal').addEventListener('click', e => { if (e.target === document.getElementById('editModal')) closeEdit(); });
</script>
</body>
</html>

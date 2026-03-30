<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
require_once '../database/connection.php';
require_once '../database/layout.php';

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = trim($_POST['subject_name'] ?? '');
        $code = trim($_POST['subject_code'] ?? '');
        if ($name) {
            $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $code);
            $stmt->execute() ? $success = "Subject added successfully." : $error = $conn->error;
            $stmt->close();
        } else { $error = "Subject name is required."; }
    } elseif ($_POST['action'] === 'edit') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['subject_name'] ?? '');
        $code = trim($_POST['subject_code'] ?? '');
        if ($id && $name) {
            $stmt = $conn->prepare("UPDATE subjects SET subject_name=?, subject_code=? WHERE id=?");
            $stmt->bind_param("ssi", $name, $code, $id);
            $stmt->execute() ? $success = "Subject updated." : $error = $conn->error;
            $stmt->close();
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM subjects WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute() ? $success = "Subject deleted." : $error = $conn->error;
        $stmt->close();
    }
}

$subjects = $conn->query("SELECT * FROM subjects ORDER BY subject_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Subjects — EduSchedule</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="layout">
  <?php render_sidebar(); ?>
  <div class="main">
    <?php render_topbar('Subjects', 'Manage school subjects and course codes'); ?>
    <div class="page-content">
      <?php if ($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error">✗ <?= htmlspecialchars($error) ?></div><?php endif; ?>

      <div style="display:grid;grid-template-columns:340px 1fr;gap:20px;align-items:start">
        <div class="card">
          <div class="card-header"><div class="card-title">Add Subject</div></div>
          <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group" style="margin-bottom:14px">
              <label>Subject Name</label>
              <input type="text" name="subject_name" placeholder="e.g. Mathematics" required>
            </div>
            <div class="form-group" style="margin-bottom:16px">
              <label>Subject Code <span class="text-muted">(optional)</span></label>
              <input type="text" name="subject_code" placeholder="e.g. MATH">
            </div>
            <button type="submit" class="btn btn-primary w-full" style="justify-content:center">
              <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Add Subject
            </button>
          </form>
        </div>

        <div class="card">
          <div class="card-header">
            <div><div class="card-title">All Subjects</div>
            <div class="card-subtitle"><?= $subjects->num_rows ?> subject<?= $subjects->num_rows != 1 ? 's' : '' ?></div></div>
          </div>
          <?php if ($subjects->num_rows > 0): ?>
          <div class="table-wrap">
            <table>
              <thead><tr><th>#</th><th>Subject Name</th><th>Code</th><th style="text-align:right">Actions</th></tr></thead>
              <tbody>
              <?php $i = 1; while ($row = $subjects->fetch_assoc()): ?>
                <tr>
                  <td class="text-muted"><?= $i++ ?></td>
                  <td><strong><?= htmlspecialchars($row['subject_name']) ?></strong></td>
                  <td><?= $row['subject_code'] ? '<span class="badge badge-blue">'.htmlspecialchars($row['subject_code']).'</span>' : '<span class="text-muted">—</span>' ?></td>
                  <td style="text-align:right">
                    <div class="flex gap-2" style="justify-content:flex-end">
                      <button class="btn btn-outline btn-sm" onclick="openEdit(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['subject_name'])) ?>', '<?= htmlspecialchars(addslashes($row['subject_code'])) ?>')">Edit</button>
                      <form method="POST" style="display:inline" onsubmit="return confirm('Delete this subject?')">
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
            <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            <h3>No subjects yet</h3><p>Add your first subject using the form.</p>
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
      <div class="modal-title">Edit Subject</div>
      <button class="modal-close" onclick="closeEdit()">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit_id">
      <div class="form-group" style="margin-bottom:14px">
        <label>Subject Name</label>
        <input type="text" name="subject_name" id="edit_name" required>
      </div>
      <div class="form-group" style="margin-bottom:20px">
        <label>Subject Code</label>
        <input type="text" name="subject_code" id="edit_code">
      </div>
      <div class="flex gap-2">
        <button type="button" class="btn btn-outline" onclick="closeEdit()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>
<script>
function openEdit(id, name, code) {
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_name').value = name;
  document.getElementById('edit_code').value = code;
  document.getElementById('editModal').classList.add('open');
}
function closeEdit() { document.getElementById('editModal').classList.remove('open'); }
document.getElementById('editModal').addEventListener('click', e => { if (e.target === document.getElementById('editModal')) closeEdit(); });
</script>
</body>
</html>

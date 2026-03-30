<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
require_once '../database/connection.php';
require_once '../database/layout.php';

$success = $error = '';

// Add class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = trim($_POST['class_name'] ?? '');
        $section = trim($_POST['section'] ?? '');
        if ($name) {
            $stmt = $conn->prepare("INSERT INTO classes (class_name, section) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $section);
            $stmt->execute() ? $success = "Class added successfully." : $error = "Error: " . $conn->error;
            $stmt->close();
        } else { $error = "Class name is required."; }
    } elseif ($_POST['action'] === 'edit') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['class_name'] ?? '');
        $section = trim($_POST['section'] ?? '');
        if ($id && $name) {
            $stmt = $conn->prepare("UPDATE classes SET class_name=?, section=? WHERE id=?");
            $stmt->bind_param("ssi", $name, $section, $id);
            $stmt->execute() ? $success = "Class updated." : $error = "Error: " . $conn->error;
            $stmt->close();
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM classes WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute() ? $success = "Class deleted." : $error = "Error: " . $conn->error;
        $stmt->close();
    }
}

$classes = $conn->query("SELECT * FROM classes ORDER BY class_name, section");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Classes — EduSchedule</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="layout">
  <?php render_sidebar(); ?>
  <div class="main">
    <?php render_topbar('Classes', 'Manage school classes and sections'); ?>
    <div class="page-content">

      <?php if ($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error">✗ <?= htmlspecialchars($error) ?></div><?php endif; ?>

      <div style="display:grid;grid-template-columns:340px 1fr;gap:20px;align-items:start">

        <div class="card">
          <div class="card-header"><div class="card-title">Add New Class</div></div>
          <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group mb-6" style="margin-bottom:14px">
              <label>Class Name</label>
              <input type="text" name="class_name" placeholder="e.g. Form 1, Grade 5" required>
            </div>
            <div class="form-group" style="margin-bottom:16px">
              <label>Section <span class="text-muted">(optional)</span></label>
              <input type="text" name="section" placeholder="e.g. A, B, Science">
            </div>
            <button type="submit" class="btn btn-primary w-full" style="justify-content:center">
              <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Add Class
            </button>
          </form>
        </div>

        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">All Classes</div>
              <div class="card-subtitle"><?= $classes->num_rows ?> class<?= $classes->num_rows != 1 ? 'es' : '' ?> registered</div>
            </div>
          </div>
          <?php if ($classes->num_rows > 0): ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr><th>#</th><th>Class Name</th><th>Section</th><th style="text-align:right">Actions</th></tr>
              </thead>
              <tbody>
              <?php $i = 1; while ($row = $classes->fetch_assoc()): ?>
                <tr>
                  <td class="text-muted"><?= $i++ ?></td>
                  <td><strong><?= htmlspecialchars($row['class_name']) ?></strong></td>
                  <td><?= $row['section'] ? '<span class="badge badge-green">'.htmlspecialchars($row['section']).'</span>' : '<span class="text-muted">—</span>' ?></td>
                  <td style="text-align:right">
                    <div class="flex gap-2" style="justify-content:flex-end">
                      <button class="btn btn-outline btn-sm" onclick="openEdit(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['class_name'])) ?>', '<?= htmlspecialchars(addslashes($row['section'])) ?>')">Edit</button>
                      <form method="POST" style="display:inline" onsubmit="return confirm('Delete this class?')">
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
            <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            <h3>No classes yet</h3><p>Add your first class using the form.</p>
          </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit Class</div>
      <button class="modal-close" onclick="closeEdit()">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit_id">
      <div class="form-group" style="margin-bottom:14px">
        <label>Class Name</label>
        <input type="text" name="class_name" id="edit_class_name" required>
      </div>
      <div class="form-group" style="margin-bottom:20px">
        <label>Section</label>
        <input type="text" name="section" id="edit_section">
      </div>
      <div class="flex gap-2">
        <button type="button" class="btn btn-outline" onclick="closeEdit()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(id, name, section) {
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_class_name').value = name;
  document.getElementById('edit_section').value = section;
  document.getElementById('editModal').classList.add('open');
}
function closeEdit() {
  document.getElementById('editModal').classList.remove('open');
}
document.getElementById('editModal').addEventListener('click', function(e) {
  if (e.target === this) closeEdit();
});
</script>
</body>
</html>

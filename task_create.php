<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

require_login();
require_role(['MANAGER', 'HR_ADMIN']);

$user = current_user();
$db = get_db();

$employeesStmt = $db->prepare("SELECT userId, fullName FROM users WHERE role = 'EMPLOYEE' ORDER BY fullName");
$employeesStmt->execute();
$employees = $employeesStmt->fetchAll();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? '';
    $assignedTo = (int) ($_POST['assignedTo'] ?? 0);
    $dueDate = $_POST['dueDate'] ?? '';

    if ($title === '' || $description === '' || $assignedTo <= 0 || $dueDate === '') {
        $message = 'Please fill in all required fields.';
    } elseif (!in_array($priority, ['LOW', 'MEDIUM', 'HIGH'], true)) {
        $message = 'Invalid priority selection.';
    } else {
        $insert = $db->prepare('INSERT INTO tasks (createdBy, assignedTo, title, description, priority, status, dueDate) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $insert->execute([$user['userId'], $assignedTo, $title, $description, $priority, 'PENDING', $dueDate]);

        $taskId = (int) $db->lastInsertId();
        $note = $db->prepare('INSERT INTO notifications (userId, taskId, type, message, createdDate) VALUES (?, ?, ?, ?, NOW())');
        $note->execute([$assignedTo, $taskId, 'NEW_TASK', 'New task assigned: ' . $title]);

        $message = 'Task created successfully.';
    }
}

render_layout_start('Create New Task');
?>
  <?php if ($message): ?>
    <div class="notice"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <div class="card" style="max-width: 720px;">
    <h3 style="margin-top: 0;">Create New Task</h3>
    <form method="POST">
      <div class="form-group">
        <label>Task Title</label>
        <input type="text" name="title" required>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="4" required></textarea>
      </div>
      <div class="form-group">
        <label>Assign To</label>
        <select name="assignedTo" required>
          <option value="">Select employee</option>
          <?php foreach ($employees as $employee): ?>
            <option value="<?php echo $employee['userId']; ?>"><?php echo htmlspecialchars($employee['fullName']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Priority</label>
        <select name="priority" required>
          <option value="LOW">Low</option>
          <option value="MEDIUM">Medium</option>
          <option value="HIGH">High</option>
        </select>
      </div>
      <div class="form-group">
        <label>Due Date</label>
        <input type="date" name="dueDate" required>
      </div>
      <div class="form-actions">
        <button class="button primary" type="submit">Create Task</button>
      </div>
    </form>
  </div>
<?php
render_layout_end();

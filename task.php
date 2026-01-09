<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

require_login();

$user = current_user();
$db = get_db();
$taskId = (int) ($_GET['id'] ?? 0);

if ($taskId <= 0) {
    header('Location: /tasks.php');
    exit();
}

$taskStmt = $db->prepare('SELECT tasks.*, creator.fullName AS creatorName, assignee.fullName AS assigneeName FROM tasks JOIN users creator ON tasks.createdBy = creator.userId JOIN users assignee ON tasks.assignedTo = assignee.userId WHERE tasks.taskId = ?');
$taskStmt->execute([$taskId]);
$task = $taskStmt->fetch();

if (!$task) {
    header('Location: /tasks.php');
    exit();
}

if ($user['role'] === 'EMPLOYEE' && (int) $task['assignedTo'] !== (int) $user['userId']) {
    header('Location: /dashboard.php');
    exit();
}

if ($user['role'] === 'MANAGER' && (int) $task['createdBy'] !== (int) $user['userId']) {
    header('Location: /dashboard.php');
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_status') {
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['PENDING', 'IN_PROGRESS', 'COMPLETED'], true)) {
            $message = 'Invalid status selection.';
        } else {
            $completedAt = $status === 'COMPLETED' ? date('Y-m-d') : null;
            $update = $db->prepare('UPDATE tasks SET status = ?, completed_at = ? WHERE taskId = ?');
            $update->execute([$status, $completedAt, $taskId]);
            $message = 'Task status updated.';
        }
    }

    if ($action === 'add_note') {
        $note = trim($_POST['note'] ?? '');
        if ($note === '') {
            $message = 'Note cannot be empty.';
        } else {
            $insert = $db->prepare('INSERT INTO task_notes (authorId, taskId, noteText, createdDate) VALUES (?, ?, ?, NOW())');
            $insert->execute([$user['userId'], $taskId, $note]);
            $message = 'Note added.';
        }
    }

    $taskStmt->execute([$taskId]);
    $task = $taskStmt->fetch();
}

$notesStmt = $db->prepare('SELECT task_notes.noteText, task_notes.createdDate, users.fullName FROM task_notes JOIN users ON task_notes.authorId = users.userId WHERE task_notes.taskId = ? ORDER BY task_notes.createdDate DESC');
$notesStmt->execute([$taskId]);
$notes = $notesStmt->fetchAll();

$progress = match ($task['status']) {
    'PENDING' => 25,
    'IN_PROGRESS' => 60,
    'COMPLETED' => 100,
    default => 0,
};

render_layout_start('Task Details', $user['role'] !== 'EMPLOYEE');
?>
  <?php if ($message): ?>
    <div class="notice"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <div class="panel-grid">
    <div class="card">
      <h2 style="margin-top: 0;"><?php echo htmlspecialchars($task['title']); ?></h2>
      <p style="color: var(--muted); margin-top: 4px;">Created by <?php echo htmlspecialchars($task['creatorName']); ?> · Assigned to <?php echo htmlspecialchars($task['assigneeName']); ?></p>
      <p><?php echo htmlspecialchars($task['description']); ?></p>
      <div class="progress-bar" style="margin: 16px 0 8px;">
        <span style="width: <?php echo $progress; ?>%;"></span>
      </div>
      <div style="display: flex; gap: 12px; align-items: center;">
        <span class="badge <?php echo status_badge_class($task['status']); ?>"><?php echo status_label($task['status']); ?></span>
        <span class="badge <?php echo priority_badge_class($task['priority']); ?>"><?php echo htmlspecialchars($task['priority']); ?></span>
        <span style="color: var(--muted);">Due <?php echo htmlspecialchars($task['dueDate']); ?></span>
      </div>
      <form method="POST" style="margin-top: 16px; display: flex; gap: 12px; align-items: center;">
        <input type="hidden" name="action" value="update_status">
        <select name="status">
          <option value="PENDING" <?php echo $task['status'] === 'PENDING' ? 'selected' : ''; ?>>To Do</option>
          <option value="IN_PROGRESS" <?php echo $task['status'] === 'IN_PROGRESS' ? 'selected' : ''; ?>>In Progress</option>
          <option value="COMPLETED" <?php echo $task['status'] === 'COMPLETED' ? 'selected' : ''; ?>>Done</option>
        </select>
        <button class="button primary" type="submit">Update Status</button>
      </form>
    </div>

    <div class="card">
      <h3 style="margin-top: 0;">Notes</h3>
      <ul style="padding-left: 18px;">
        <?php foreach ($notes as $note): ?>
          <li style="margin-bottom: 12px;">
            <strong><?php echo htmlspecialchars($note['fullName']); ?></strong>
            <div style="color: var(--muted); font-size: 12px;"><?php echo htmlspecialchars($note['createdDate']); ?></div>
            <div><?php echo htmlspecialchars($note['noteText']); ?></div>
          </li>
        <?php endforeach; ?>
      </ul>
      <form method="POST">
        <input type="hidden" name="action" value="add_note">
        <div class="form-group">
          <textarea name="note" rows="3" placeholder="Add a note..."></textarea>
        </div>
        <button class="button secondary" type="submit">Add Note</button>
      </form>
    </div>

    <div class="card">
      <h3 style="margin-top: 0;">Activity Log</h3>
      <p style="color: var(--muted);">Recent updates for this task.</p>
      <ul style="padding-left: 18px;">
        <li>Status: <?php echo status_label($task['status']); ?></li>
        <li>Priority: <?php echo htmlspecialchars($task['priority']); ?></li>
        <li>Due: <?php echo htmlspecialchars($task['dueDate']); ?></li>
      </ul>
    </div>

    <div class="card">
      <h3 style="margin-top: 0;">Statistics</h3>
      <div style="display: grid; gap: 12px;">
        <div><strong>Progress</strong> <div style="color: var(--muted);"><?php echo $progress; ?>% complete</div></div>
        <div><strong>Assigned</strong> <div style="color: var(--muted);"><?php echo htmlspecialchars($task['assigneeName']); ?></div></div>
        <div><strong>Created</strong> <div style="color: var(--muted);"><?php echo htmlspecialchars($task['creatorName']); ?></div></div>
      </div>
    </div>
  </div>
<?php
render_layout_end();

<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

require_login();

$user = current_user();
$db = get_db();

$where = '';
$params = [];
if ($user['role'] === 'EMPLOYEE') {
    $where = 'WHERE tasks.assignedTo = ?';
    $params[] = $user['userId'];
} elseif ($user['role'] === 'MANAGER') {
    $where = 'WHERE tasks.createdBy = ?';
    $params[] = $user['userId'];
}

$totalStmt = $db->prepare("SELECT COUNT(*) FROM tasks {$where}");
$totalStmt->execute($params);
$totalTasks = (int) $totalStmt->fetchColumn();

$statusStmt = $db->prepare("SELECT status, COUNT(*) as count FROM tasks {$where} GROUP BY status");
$statusStmt->execute($params);
$statusCounts = ['PENDING' => 0, 'IN_PROGRESS' => 0, 'COMPLETED' => 0];
foreach ($statusStmt->fetchAll() as $row) {
    $statusCounts[$row['status']] = (int) $row['count'];
}

$overdueStmt = $db->prepare("SELECT COUNT(*) FROM tasks {$where} " . ($where ? 'AND' : 'WHERE') . " status != 'COMPLETED' AND dueDate < CURDATE()");
$overdueStmt->execute($params);
$overdueCount = (int) $overdueStmt->fetchColumn();

$recentStmt = $db->prepare("SELECT tasks.taskId, tasks.title, tasks.status, tasks.priority, tasks.dueDate, users.fullName AS assignedName FROM tasks JOIN users ON tasks.assignedTo = users.userId {$where} ORDER BY tasks.dueDate ASC LIMIT 5");
$recentStmt->execute($params);
$recentTasks = $recentStmt->fetchAll();

render_layout_start('Dashboard', $user['role'] !== 'EMPLOYEE');
?>
  <div class="card-grid">
    <div class="card">
      <h3>Total Tasks</h3>
      <div class="value"><?php echo $totalTasks; ?></div>
    </div>
    <div class="card">
      <h3>In Progress</h3>
      <div class="value"><?php echo $statusCounts['IN_PROGRESS']; ?></div>
    </div>
    <div class="card">
      <h3>Completed</h3>
      <div class="value"><?php echo $statusCounts['COMPLETED']; ?></div>
    </div>
    <div class="card">
      <h3>Overdue</h3>
      <div class="value"><?php echo $overdueCount; ?></div>
    </div>
  </div>

  <div class="card">
    <h3 style="margin-top: 0;">Upcoming Tasks</h3>
    <table class="table">
      <thead>
        <tr>
          <th>Task</th>
          <th>Status</th>
          <th>Priority</th>
          <th>Due Date</th>
          <?php if ($user['role'] !== 'EMPLOYEE'): ?>
            <th>Assigned To</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recentTasks as $task): ?>
          <tr>
            <td><a href="/task.php?id=<?php echo $task['taskId']; ?>"><?php echo htmlspecialchars($task['title']); ?></a></td>
            <td><span class="badge <?php echo status_badge_class($task['status']); ?>"><?php echo status_label($task['status']); ?></span></td>
            <td><span class="badge <?php echo priority_badge_class($task['priority']); ?>"><?php echo htmlspecialchars($task['priority']); ?></span></td>
            <td><?php echo htmlspecialchars($task['dueDate']); ?></td>
            <?php if ($user['role'] !== 'EMPLOYEE'): ?>
              <td><?php echo htmlspecialchars($task['assignedName']); ?></td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php
render_layout_end();

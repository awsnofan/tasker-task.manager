<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

require_login();

$user = current_user();
$db = get_db();

$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$dueDate = $_GET['dueDate'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$conditions = [];
$params = [];

if ($user['role'] === 'EMPLOYEE') {
    $conditions[] = 'tasks.assignedTo = ?';
    $params[] = $user['userId'];
} elseif ($user['role'] === 'MANAGER') {
    $conditions[] = 'tasks.createdBy = ?';
    $params[] = $user['userId'];
}

if ($status !== '') {
    $conditions[] = 'tasks.status = ?';
    $params[] = $status;
}

if ($priority !== '') {
    $conditions[] = 'tasks.priority = ?';
    $params[] = $priority;
}

if ($dueDate !== '') {
    $conditions[] = 'tasks.dueDate = ?';
    $params[] = $dueDate;
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$countStmt = $db->prepare("SELECT COUNT(*) FROM tasks {$where}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($total / $limit);

$query = "SELECT tasks.taskId, tasks.title, tasks.status, tasks.priority, tasks.dueDate, users.fullName AS assignedName FROM tasks JOIN users ON tasks.assignedTo = users.userId {$where} ORDER BY tasks.dueDate ASC LIMIT {$limit} OFFSET {$offset}";
$stmt = $db->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

render_layout_start('Tasks', $user['role'] !== 'EMPLOYEE');
?>
  <form class="filters" method="GET">
    <select name="status">
      <option value="">Status</option>
      <option value="PENDING" <?php echo $status === 'PENDING' ? 'selected' : ''; ?>>To Do</option>
      <option value="IN_PROGRESS" <?php echo $status === 'IN_PROGRESS' ? 'selected' : ''; ?>>In Progress</option>
      <option value="COMPLETED" <?php echo $status === 'COMPLETED' ? 'selected' : ''; ?>>Done</option>
    </select>
    <select name="priority">
      <option value="">Priority</option>
      <option value="LOW" <?php echo $priority === 'LOW' ? 'selected' : ''; ?>>Low</option>
      <option value="MEDIUM" <?php echo $priority === 'MEDIUM' ? 'selected' : ''; ?>>Medium</option>
      <option value="HIGH" <?php echo $priority === 'HIGH' ? 'selected' : ''; ?>>High</option>
    </select>
    <input type="date" name="dueDate" value="<?php echo htmlspecialchars($dueDate); ?>">
    <button class="button secondary" type="submit">Apply Filters</button>
  </form>

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
      <?php foreach ($tasks as $task): ?>
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

  <div class="pagination">
    <?php for ($i = 1; $i <= max(1, $totalPages); $i++): ?>
      <a class="page <?php echo $i === $page ? 'active' : ''; ?>" href="?<?php echo http_build_query(['status' => $status, 'priority' => $priority, 'dueDate' => $dueDate, 'page' => $i]); ?>"><?php echo $i; ?></a>
    <?php endfor; ?>
  </div>
<?php
render_layout_end();

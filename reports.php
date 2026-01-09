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

$filters = [
    'employee' => $_REQUEST['employee'] ?? '',
    'status' => $_REQUEST['status'] ?? '',
    'priority' => $_REQUEST['priority'] ?? '',
    'start_date' => $_REQUEST['start_date'] ?? '',
    'end_date' => $_REQUEST['end_date'] ?? '',
];

function build_report_query(array $filters, array $user): array {
    $conditions = [];
    $params = [];

    if ($user['role'] === 'MANAGER') {
        $conditions[] = 'tasks.createdBy = ?';
        $params[] = $user['userId'];
    }

    if ($filters['employee'] !== '') {
        $conditions[] = 'tasks.assignedTo = ?';
        $params[] = (int) $filters['employee'];
    }

    if ($filters['status'] !== '') {
        $conditions[] = 'tasks.status = ?';
        $params[] = $filters['status'];
    }

    if ($filters['priority'] !== '') {
        $conditions[] = 'tasks.priority = ?';
        $params[] = $filters['priority'];
    }

    if ($filters['start_date'] !== '' && $filters['end_date'] !== '') {
        $conditions[] = 'tasks.dueDate BETWEEN ? AND ?';
        $params[] = $filters['start_date'];
        $params[] = $filters['end_date'];
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $sql = "SELECT tasks.taskId, tasks.title, tasks.status, tasks.priority, tasks.dueDate, creator.fullName AS managerName, assignee.fullName AS assigneeName FROM tasks JOIN users creator ON tasks.createdBy = creator.userId JOIN users assignee ON tasks.assignedTo = assignee.userId {$where} ORDER BY tasks.dueDate DESC";
    return [$sql, $params];
}

function fetch_report_rows(PDO $db, array $filters, array $user): array {
    [$sql, $params] = build_report_query($filters, $user);
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

if (isset($_GET['export'])) {
    $format = strtoupper($_GET['export']);
    $rows = fetch_report_rows($db, $filters, $user);
    $filtersJson = json_encode($filters);
    $insert = $db->prepare('INSERT INTO report_requests (generatedByUserId, format, filters_json, created_at) VALUES (?, ?, ?, NOW())');
    $insert->execute([$user['userId'], $format === 'PDF' ? 'PDF' : 'EXCEL', $filtersJson]);

    if ($format === 'EXCEL') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="task_report.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Task ID', 'Title', 'Status', 'Priority', 'Due Date', 'Manager', 'Assigned To']);
        foreach ($rows as $row) {
            fputcsv($output, [$row['taskId'], $row['title'], $row['status'], $row['priority'], $row['dueDate'], $row['managerName'], $row['assigneeName']]);
        }
        fclose($output);
        exit();
    }

    if ($format === 'PDF') {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="task_report.pdf"');
        echo "<html><head><style>body{font-family:Arial;} table{width:100%; border-collapse:collapse;} th,td{border:1px solid #ccc; padding:6px; font-size:12px;}</style></head><body>";
        echo "<h2>Task Report</h2>";
        echo "<table><thead><tr><th>ID</th><th>Title</th><th>Status</th><th>Priority</th><th>Due Date</th><th>Manager</th><th>Assigned To</th></tr></thead><tbody>";
        foreach ($rows as $row) {
            echo "<tr><td>{$row['taskId']}</td><td>{$row['title']}</td><td>{$row['status']}</td><td>{$row['priority']}</td><td>{$row['dueDate']}</td><td>{$row['managerName']}</td><td>{$row['assigneeName']}</td></tr>";
        }
        echo "</tbody></table></body></html>";
        exit();
    }
}

$results = [];
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $results = fetch_report_rows($db, $filters, $user);
    $filtersJson = json_encode($filters);
    $insert = $db->prepare('INSERT INTO report_requests (generatedByUserId, format, filters_json, created_at) VALUES (?, ?, ?, NOW())');
    $insert->execute([$user['userId'], 'PDF', $filtersJson]);
    $message = 'Report generated successfully.';
}

render_layout_start('Reports');
?>
  <?php if ($message): ?>
    <div class="notice"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <div class="card" style="margin-bottom: 20px;">
    <form method="POST" class="filters" style="align-items: flex-end;">
      <div class="form-group">
        <label>Employee</label>
        <select name="employee">
          <option value="">All Employees</option>
          <?php foreach ($employees as $employee): ?>
            <option value="<?php echo $employee['userId']; ?>" <?php echo (string) $filters['employee'] === (string) $employee['userId'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($employee['fullName']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status">
          <option value="">Any</option>
          <option value="PENDING" <?php echo $filters['status'] === 'PENDING' ? 'selected' : ''; ?>>To Do</option>
          <option value="IN_PROGRESS" <?php echo $filters['status'] === 'IN_PROGRESS' ? 'selected' : ''; ?>>In Progress</option>
          <option value="COMPLETED" <?php echo $filters['status'] === 'COMPLETED' ? 'selected' : ''; ?>>Done</option>
        </select>
      </div>
      <div class="form-group">
        <label>Priority</label>
        <select name="priority">
          <option value="">Any</option>
          <option value="LOW" <?php echo $filters['priority'] === 'LOW' ? 'selected' : ''; ?>>Low</option>
          <option value="MEDIUM" <?php echo $filters['priority'] === 'MEDIUM' ? 'selected' : ''; ?>>Medium</option>
          <option value="HIGH" <?php echo $filters['priority'] === 'HIGH' ? 'selected' : ''; ?>>High</option>
        </select>
      </div>
      <div class="form-group">
        <label>Start Date</label>
        <input type="date" name="start_date" value="<?php echo htmlspecialchars($filters['start_date']); ?>">
      </div>
      <div class="form-group">
        <label>End Date</label>
        <input type="date" name="end_date" value="<?php echo htmlspecialchars($filters['end_date']); ?>">
      </div>
      <div class="form-group">
        <label>Manager</label>
        <input type="text" value="<?php echo htmlspecialchars($user['fullName']); ?>" disabled>
      </div>
      <button class="button primary" type="submit">Generate Report</button>
    </form>
    <div style="display: flex; gap: 12px; margin-top: 16px;">
      <a class="button secondary" href="?<?php echo http_build_query(array_merge($filters, ['export' => 'pdf'])); ?>">Export PDF</a>
      <a class="button outline" href="?<?php echo http_build_query(array_merge($filters, ['export' => 'excel'])); ?>">Export Excel</a>
    </div>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>Task</th>
        <th>Status</th>
        <th>Priority</th>
        <th>Due Date</th>
        <th>Manager</th>
        <th>Assigned To</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($results as $row): ?>
        <tr>
          <td><?php echo htmlspecialchars($row['title']); ?></td>
          <td><?php echo status_label($row['status']); ?></td>
          <td><?php echo htmlspecialchars($row['priority']); ?></td>
          <td><?php echo htmlspecialchars($row['dueDate']); ?></td>
          <td><?php echo htmlspecialchars($row['managerName']); ?></td>
          <td><?php echo htmlspecialchars($row['assigneeName']); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php
render_layout_end();

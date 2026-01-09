<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

require_login();
require_role(['HR_ADMIN']);

$user = current_user();
$db = get_db();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_user') {
        $fullName = trim($_POST['fullName'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';
        $password = $_POST['password'] ?? '';
        $managerId = $_POST['manager_id'] !== '' ? (int) $_POST['manager_id'] : null;

        if ($fullName === '' || $username === '' || $email === '' || $password === '') {
            $message = 'All fields are required.';
        } elseif (!in_array($role, ['HR_ADMIN', 'MANAGER', 'EMPLOYEE'], true)) {
            $message = 'Invalid role selection.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('INSERT INTO users (fullName, username, passwordHash, email, role, manager_id) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$fullName, $username, $hash, $email, $role, $managerId]);
            $message = 'User created successfully.';
        }
    }

    if ($action === 'edit_user') {
        $userId = (int) ($_POST['userId'] ?? 0);
        $fullName = trim($_POST['fullName'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';
        $password = $_POST['password'] ?? '';
        $managerId = $_POST['manager_id'] !== '' ? (int) $_POST['manager_id'] : null;

        if ($userId <= 0 || $fullName === '' || $email === '') {
            $message = 'Please provide valid user information.';
        } else {
            $params = [$fullName, $email, $role, $managerId, $userId];
            $query = 'UPDATE users SET fullName = ?, email = ?, role = ?, manager_id = ?';
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $query .= ', passwordHash = ?';
                $params = [$fullName, $email, $role, $managerId, $hash, $userId];
            }
            $query .= ' WHERE userId = ?';

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $message = 'User updated successfully.';
        }
    }
}

$usersStmt = $db->prepare('SELECT users.userId, users.fullName, users.username, users.email, users.role, users.manager_id, managers.fullName AS managerName FROM users LEFT JOIN users managers ON users.manager_id = managers.userId ORDER BY users.fullName');
$usersStmt->execute();
$users = $usersStmt->fetchAll();

$managersStmt = $db->prepare("SELECT userId, fullName FROM users WHERE role IN ('HR_ADMIN', 'MANAGER') ORDER BY fullName");
$managersStmt->execute();
$managers = $managersStmt->fetchAll();

render_layout_start('User Management');
?>
  <?php if ($message): ?>
    <div class="notice"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
    <h3 style="margin: 0;">Users</h3>
    <button class="button primary" data-modal-open="createUserModal">Create User</button>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>Name</th>
        <th>Username</th>
        <th>Email</th>
        <th>Role</th>
        <th>Manager</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $entry): ?>
        <tr>
          <td><?php echo htmlspecialchars($entry['fullName']); ?></td>
          <td><?php echo htmlspecialchars($entry['username']); ?></td>
          <td><?php echo htmlspecialchars($entry['email']); ?></td>
          <td><?php echo htmlspecialchars($entry['role']); ?></td>
          <td><?php echo htmlspecialchars($entry['managerName'] ?? ''); ?></td>
          <td>
            <button class="button secondary" data-edit-user
              data-user-id="<?php echo $entry['userId']; ?>"
              data-full-name="<?php echo htmlspecialchars($entry['fullName']); ?>"
              data-email="<?php echo htmlspecialchars($entry['email']); ?>"
              data-role="<?php echo htmlspecialchars($entry['role']); ?>"
              data-manager-id="<?php echo htmlspecialchars((string) $entry['manager_id']); ?>"
              data-modal-open="editUserModal">Edit</button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="modal" id="createUserModal">
    <div class="modal-content">
      <h3>Create User</h3>
      <form method="POST">
        <input type="hidden" name="action" value="create_user">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="fullName" required>
        </div>
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" required>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" required>
        </div>
        <div class="form-group">
          <label>Role</label>
          <select name="role" required>
            <option value="HR_ADMIN">HR Admin</option>
            <option value="MANAGER">Manager</option>
            <option value="EMPLOYEE">Employee</option>
          </select>
        </div>
        <div class="form-group">
          <label>Manager</label>
          <select name="manager_id">
            <option value="">None</option>
            <?php foreach ($managers as $manager): ?>
              <option value="<?php echo $manager['userId']; ?>"><?php echo htmlspecialchars($manager['fullName']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Temporary Password</label>
          <input type="password" name="password" required>
        </div>
        <div class="form-actions">
          <button class="button secondary" data-modal-close="createUserModal" type="button">Cancel</button>
          <button class="button primary" type="submit">Create</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal" id="editUserModal">
    <div class="modal-content">
      <h3>Edit User</h3>
      <form method="POST">
        <input type="hidden" name="action" value="edit_user">
        <input type="hidden" name="userId" id="edit-user-id">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="fullName" id="edit-full-name" required>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" id="edit-email" required>
        </div>
        <div class="form-group">
          <label>Role</label>
          <select name="role" id="edit-role" required>
            <option value="HR_ADMIN">HR Admin</option>
            <option value="MANAGER">Manager</option>
            <option value="EMPLOYEE">Employee</option>
          </select>
        </div>
        <div class="form-group">
          <label>Manager</label>
          <select name="manager_id" id="edit-manager">
            <option value="">None</option>
            <?php foreach ($managers as $manager): ?>
              <option value="<?php echo $manager['userId']; ?>"><?php echo htmlspecialchars($manager['fullName']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Reset Password (optional)</label>
          <input type="password" name="password">
        </div>
        <div class="form-actions">
          <button class="button secondary" data-modal-close="editUserModal" type="button">Cancel</button>
          <button class="button primary" type="submit">Save</button>
        </div>
      </form>
    </div>
  </div>
<?php
render_layout_end();

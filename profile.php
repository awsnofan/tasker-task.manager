<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

require_login();
$user = current_user();

render_layout_start('Profile');
?>
  <div class="card" style="max-width: 640px;">
    <h3 style="margin-top: 0;">Profile Details</h3>
    <div class="form-group">
      <label>Full Name</label>
      <input type="text" value="<?php echo htmlspecialchars($user['fullName']); ?>" disabled>
    </div>
    <div class="form-group">
      <label>Username</label>
      <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
    </div>
    <div class="form-group">
      <label>Email</label>
      <input type="text" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
    </div>
    <div class="form-group">
      <label>Role</label>
      <input type="text" value="<?php echo htmlspecialchars($user['role']); ?>" disabled>
    </div>
  </div>
<?php
render_layout_end();

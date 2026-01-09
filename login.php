<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $db = get_db();
        $stmt = $db->prepare('SELECT userId, fullName, username, passwordHash, email, role FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['passwordHash'])) {
            login_user($user);
            header('Location: /dashboard.php');
            exit();
        }
        $error = 'Invalid credentials. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tasker - Login</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <div class="login-page">
    <form class="login-card" method="POST">
      <h2>Welcome back</h2>
      <p style="color: var(--muted);">Sign in to continue to Tasker.</p>
      <?php if ($error): ?>
        <div class="notice"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <div class="form-group">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" required>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>
      </div>
      <button class="button primary" type="submit" style="width: 100%;">Login</button>
      <div class="footer-links">
        <span>Demo: hradmin / manager / employee</span>
      </div>
    </form>
  </div>
</body>
</html>

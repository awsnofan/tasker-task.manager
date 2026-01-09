<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

require_login();

$user = current_user();
$db = get_db();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_all_read') {
        $stmt = $db->prepare('UPDATE notifications SET isRead = 1 WHERE userId = ?');
        $stmt->execute([$user['userId']]);
        $message = 'All notifications marked as read.';
    }

    if ($action === 'mark_read') {
        $notificationId = (int) ($_POST['notificationId'] ?? 0);
        $stmt = $db->prepare('UPDATE notifications SET isRead = 1 WHERE notificationId = ? AND userId = ?');
        $stmt->execute([$notificationId, $user['userId']]);
        $message = 'Notification marked as read.';
    }

    if ($action === 'dismiss') {
        $notificationId = (int) ($_POST['notificationId'] ?? 0);
        $stmt = $db->prepare('DELETE FROM notifications WHERE notificationId = ? AND userId = ?');
        $stmt->execute([$notificationId, $user['userId']]);
        $message = 'Notification dismissed.';
    }
}

$notificationsStmt = $db->prepare('SELECT notifications.*, tasks.title AS taskTitle FROM notifications LEFT JOIN tasks ON notifications.taskId = tasks.taskId WHERE notifications.userId = ? ORDER BY notifications.createdDate DESC');
$notificationsStmt->execute([$user['userId']]);
$notifications = $notificationsStmt->fetchAll();

$groups = [
    'all' => $notifications,
    'unread' => array_filter($notifications, fn($item) => !$item['isRead']),
    'tasks' => array_filter($notifications, fn($item) => in_array($item['type'], ['NEW_TASK', 'REMINDER'], true)),
    'comments' => array_filter($notifications, fn($item) => $item['type'] === 'OVERDUE'),
    'mentions' => [],
];

render_layout_start('Notifications', $user['role'] !== 'EMPLOYEE');
?>
  <?php if ($message): ?>
    <div class="notice"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
    <div class="tabs">
      <div class="tab active" data-tab="all">All</div>
      <div class="tab" data-tab="unread">Unread</div>
      <div class="tab" data-tab="tasks">Tasks</div>
      <div class="tab" data-tab="comments">Comments</div>
      <div class="tab" data-tab="mentions">Mentions</div>
    </div>
    <div style="display: flex; gap: 8px;">
      <button class="button secondary" type="button">Filter</button>
      <form method="POST">
        <input type="hidden" name="action" value="mark_all_read">
        <button class="button primary" type="submit">Mark All as Read</button>
      </form>
    </div>
  </div>

  <?php foreach ($groups as $key => $items): ?>
    <div data-tab-content="<?php echo $key; ?>" class="<?php echo $key === 'all' ? '' : 'hidden'; ?>">
      <?php if (empty($items)): ?>
        <div class="card">No notifications found.</div>
      <?php endif; ?>
      <?php foreach ($items as $note): ?>
        <div class="notification-card">
          <div>
            <strong><?php echo htmlspecialchars($note['message']); ?></strong>
            <div style="color: var(--muted); font-size: 12px;"><?php echo htmlspecialchars($note['createdDate']); ?></div>
            <?php if (!empty($note['taskTitle'])): ?>
              <div style="margin-top: 6px; color: var(--muted);">Task: <?php echo htmlspecialchars($note['taskTitle']); ?></div>
            <?php endif; ?>
          </div>
          <div class="notification-actions">
            <?php if ($note['taskId']): ?>
              <a class="button outline" href="/task.php?id=<?php echo $note['taskId']; ?>">View Task</a>
            <?php endif; ?>
            <?php if (!$note['isRead']): ?>
              <form method="POST">
                <input type="hidden" name="action" value="mark_read">
                <input type="hidden" name="notificationId" value="<?php echo $note['notificationId']; ?>">
                <button class="button secondary" type="submit">Dismiss</button>
              </form>
            <?php endif; ?>
            <form method="POST">
              <input type="hidden" name="action" value="dismiss">
              <input type="hidden" name="notificationId" value="<?php echo $note['notificationId']; ?>">
              <button class="icon-button" data-confirm="Delete this notification?" type="submit">🗑️</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
<?php
render_layout_end();

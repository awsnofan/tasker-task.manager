<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

require_login();

render_layout_start('Settings');
?>
  <div class="card" style="max-width: 640px;">
    <h3 style="margin-top: 0;">Settings</h3>
    <p style="color: var(--muted);">Settings will be available in a future release.</p>
  </div>
<?php
render_layout_end();

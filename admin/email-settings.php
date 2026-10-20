<?php
session_start();
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

// Ensure emailsetting_master table exists (idempotent, cheap).
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS emailsetting_master (
  `key`   VARCHAR(60)  NOT NULL PRIMARY KEY,
  `value` TEXT         NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB");

function setting_get($conn, $key, $fallback = '') {
  $s = mysqli_prepare($conn, 'SELECT `value` FROM emailsetting_master WHERE `key` = ?');
  mysqli_stmt_bind_param($s, 's', $key);
  mysqli_stmt_execute($s);
  $r = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
  return $r['value'] ?? $fallback;
}
function setting_set($conn, $key, $value) {
  $s = mysqli_prepare($conn, 'INSERT INTO emailsetting_master (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
  mysqli_stmt_bind_param($s, 'ss', $key, $value);
  mysqli_stmt_execute($s);
}

$err = '';
$ok  = '';
$cur = [
  'smtp_name'     => setting_get($conn, 'smtp_name'),
  'smtp_email'    => setting_get($conn, 'smtp_email'),
  'smtp_app_pass' => setting_get($conn, 'smtp_app_pass'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $cur['smtp_name']     = trim($_POST['smtp_name']     ?? '');
  $cur['smtp_email']    = trim($_POST['smtp_email']    ?? '');
  $cur['smtp_app_pass'] = trim($_POST['smtp_app_pass'] ?? '');

  if ($cur['smtp_name'] === '')                                             $err = 'Please enter a sender name.';
  elseif (!filter_var($cur['smtp_email'], FILTER_VALIDATE_EMAIL))           $err = 'That sending email doesn\'t look right.';
  elseif ($cur['smtp_app_pass'] === '')                                     $err = 'App password is required.';
  else {
    foreach ($cur as $k => $v) setting_set($conn, $k, $v);
    $ok = 'Email settings saved.';
  }
}

$page_title = 'Email settings';
$page_crumb = 'Account';
include __DIR__ . '/header.php';
?>

<style>
  .es-wrap{max-width:100%}
  .es-wrap .admin-card{padding:32px 34px}
  .es-wrap h3{display:flex;align-items:center;justify-content:center;gap:14px;margin:0 0 4px}
  .es-wrap h3::before,.es-wrap h3::after{content:"";display:block;width:28px;height:1px;background:var(--gold);opacity:.5}
  .es-wrap .sub{text-align:center;font-size:12.5px;color:var(--ink-2);margin:0 0 22px}
</style>

<div class="es-wrap">
  <div class="admin-card">
    <h3>Email settings</h3>
    <p class="sub">Used as the sender of all outgoing emails</p>

    <?php if ($ok): ?><div class="notice ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="notice err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <form method="post" action="email-settings.php" novalidate>
      <div class="form-grid">
        <div class="form-row full">
          <label for="smtp_name">Name</label>
          <div class="field">
            <input type="text" id="smtp_name" name="smtp_name" required
                   placeholder="Friends Club"
                   value="<?= htmlspecialchars($cur['smtp_name']) ?>">
          </div>
        </div>
        <div class="form-row full">
          <label for="smtp_email">Sending email</label>
          <div class="field">
            <input type="email" id="smtp_email" name="smtp_email" required
                   placeholder="noreply@friendsclub.local"
                   value="<?= htmlspecialchars($cur['smtp_email']) ?>">
          </div>
        </div>
        <div class="form-row full">
          <label for="smtp_app_pass">App password</label>
          <div class="field">
            <input type="password" id="smtp_app_pass" name="smtp_app_pass" required
                   placeholder="16-character app password"
                   value="<?= htmlspecialchars($cur['smtp_app_pass']) ?>"
                   autocomplete="new-password" spellcheck="false">
            <button type="button" class="pw-toggle" aria-label="Show password"
              onclick="var i=document.getElementById('smtp_app_pass');var o=this.querySelector('svg use');var h=i.type==='password';i.type=h?'text':'password';o.setAttribute('href',h?'#i-eye-off':'#i-eye');">
              <svg><use href="#i-eye"/></svg>
            </button>
          </div>
          <div style="font-size:12px;color:var(--ink-2);margin-top:4px">Gmail users: create an app password at myaccount.google.com → Security → App passwords.</div>
        </div>
      </div>

      <button type="submit" class="btn-solid form-submit">
        <span>Save settings</span>
        <svg width="14" height="14"><use href="#i-arrow-ur"/></svg>
      </button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>

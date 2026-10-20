<?php
session_start();
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$admin_id = (int)$_SESSION['admin_id'];
$err = '';
$ok  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $new  = $_POST['new']     ?? '';
  $conf = $_POST['confirm'] ?? '';

  if ($new === '' || $conf === '')       $err = 'Please fill both fields.';
  elseif (strlen($new) < 8)              $err = 'Password needs at least 8 characters.';
  elseif ($new !== $conf)                $err = 'New password and confirm password don\'t match.';
  else {
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $u = mysqli_prepare($conn, 'UPDATE admins SET password = ? WHERE id = ?');
    mysqli_stmt_bind_param($u, 'si', $hash, $admin_id);
    mysqli_stmt_execute($u);
    $ok = 'Password updated.';
  }
}

$page_title = 'Change password';
$page_crumb = 'Account';
include __DIR__ . '/header.php';
?>

<style>
  .cp-wrap{max-width:100%}
  .cp-wrap .admin-card{padding:32px 34px}
  .cp-wrap h3{display:flex;align-items:center;justify-content:center;gap:14px;margin:0 0 4px}
  .cp-wrap h3::before,.cp-wrap h3::after{content:"";display:block;width:28px;height:1px;background:var(--gold);opacity:.5}
  .cp-wrap .sub{text-align:center;font-size:12.5px;color:var(--ink-2);margin:0 0 22px}
</style>

<div class="cp-wrap">
  <div class="admin-card">
    <h3>Change password</h3>
    <p class="sub">Use at least 8 characters</p>

    <?php if ($ok): ?><div class="notice ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="notice err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <form method="post" action="change-password.php" novalidate id="cpForm">
      <div class="form-grid">
        <div class="form-row full">
          <label for="new">New password</label>
          <div class="field">
            <input type="password" id="new" name="new" required minlength="8" autocomplete="new-password" placeholder="At least 8 characters">
          </div>
        </div>
        <div class="form-row full">
          <label for="confirm">Confirm new password</label>
          <div class="field">
            <input type="password" id="confirm" name="confirm" required minlength="8" autocomplete="new-password" placeholder="Type the password again">
          </div>
          <div id="matchHint" style="font-size:12.5px;color:var(--ink-2);margin-top:4px"></div>
        </div>
      </div>

      <button type="submit" class="btn-solid form-submit">
        <span>Update password</span>
        <svg width="14" height="14"><use href="#i-arrow-ur"/></svg>
      </button>
    </form>
  </div>
</div>

<script>
(function(){
  var newEl   = document.getElementById('new');
  var confEl  = document.getElementById('confirm');
  var hint    = document.getElementById('matchHint');
  var form    = document.getElementById('cpForm');

  function check(){
    if (!confEl.value){ hint.textContent=''; hint.style.color='var(--ink-2)'; return; }
    if (newEl.value === confEl.value){
      hint.textContent = 'Passwords match.';
      hint.style.color = 'var(--forest)';
    } else {
      hint.textContent = 'Passwords do not match.';
      hint.style.color = 'var(--brand-ink)';
    }
  }
  newEl.addEventListener('input', check);
  confEl.addEventListener('input', check);

  form.addEventListener('submit', function(e){
    if (newEl.value !== confEl.value){
      e.preventDefault();
      hint.textContent = 'Passwords do not match.';
      hint.style.color = 'var(--brand-ink)';
      confEl.focus();
    }
  });
})();
</script>

<?php include __DIR__ . '/footer.php'; ?>

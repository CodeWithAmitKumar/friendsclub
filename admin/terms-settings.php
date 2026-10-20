<?php
session_start();
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS websetting_master (
  `key`      VARCHAR(60)  NOT NULL PRIMARY KEY,
  `value`    MEDIUMTEXT   NULL,
  updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB");

function ws_get($conn, $key, $fallback = '') {
  $s = mysqli_prepare($conn, 'SELECT `value` FROM websetting_master WHERE `key` = ?');
  mysqli_stmt_bind_param($s, 's', $key);
  mysqli_stmt_execute($s);
  $r = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
  return $r['value'] ?? $fallback;
}
function ws_set($conn, $key, $value) {
  $s = mysqli_prepare($conn, 'INSERT INTO websetting_master (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
  mysqli_stmt_bind_param($s, 'ss', $key, $value);
  mysqli_stmt_execute($s);
}

$err = '';
$ok  = '';
$cur = ws_get($conn, 'terms_content');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $body = $_POST['terms'] ?? '';
  if (trim(strip_tags($body)) === '') {
    $err = 'Please write your terms first.';
    $cur = $body;
  } else {
    ws_set($conn, 'terms_content', $body);
    $cur = ws_get($conn, 'terms_content');
    $ok  = 'Terms &amp; Conditions saved. Members see this when they tap the link on sign up.';
  }
}

$page_title = 'Terms & Conditions';
$page_crumb = 'Website';
include __DIR__ . '/header.php';
?>

<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js" defer></script>
<style>
  .ts-wrap{max-width:100%}
  .ts-wrap .admin-card{padding:32px 34px}
  .ts-wrap h3{display:flex;align-items:center;justify-content:center;gap:14px;margin:0 0 4px}
  .ts-wrap h3::before,.ts-wrap h3::after{content:"";display:block;width:28px;height:1px;background:var(--gold);opacity:.5}
  .ts-wrap .sub{text-align:center;font-size:12.5px;color:var(--ink-2);margin:0 0 22px}

  .ck-editor__editable_inline{
    min-height:360px;background:var(--paper)!important;color:var(--ink);
    border:1px solid var(--rule)!important;border-radius:0 0 10px 10px!important;
    padding:16px 18px!important;
  }
  .ck.ck-toolbar{
    background:var(--paper)!important;border:1px solid var(--rule)!important;
    border-radius:10px 10px 0 0!important;
  }
  .ck-focused.ck-editor__editable_inline{
    border-color:var(--ink)!important;box-shadow:0 0 0 3px var(--brand-soft)!important;
  }
</style>

<div class="ts-wrap">
  <div class="admin-card">
    <h3>Terms &amp; Conditions</h3>
    <p class="sub">Shown as a popup when members tap the terms link on sign up</p>

    <?php if ($ok): ?><div class="notice ok"><?= $ok ?></div><?php endif; ?>
    <?php if ($err): ?><div class="notice err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <form method="post" action="terms-settings.php">
      <div class="form-grid">
        <div class="form-row full">
          <label for="terms">Content</label>
          <textarea id="terms" name="terms"><?= htmlspecialchars($cur) ?></textarea>
        </div>
      </div>

      <button type="submit" class="btn-solid form-submit">
        <span>Save terms</span>
        <svg width="14" height="14"><use href="#i-arrow-ur"/></svg>
      </button>
    </form>
  </div>
</div>

<script>
  window.addEventListener('load', function(){
    if (window.ClassicEditor) {
      ClassicEditor
        .create(document.querySelector('#terms'), {
          toolbar: ['heading','|','bold','italic','link','bulletedList','numberedList','|','blockQuote','undo','redo']
        })
        .catch(function(e){ console.error(e); });
    }
  });
</script>

<?php include __DIR__ . '/footer.php'; ?>

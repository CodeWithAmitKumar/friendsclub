<?php
session_start();
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS websetting_master (
  `key`      VARCHAR(60)  NOT NULL PRIMARY KEY,
  `value`    TEXT         NULL,
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

$fields = [
  'social_instagram' => ['Instagram', 'i-ig', 'https://instagram.com/your-handle'],
  'social_facebook'  => ['Facebook',  'i-fb', 'https://facebook.com/your-page'],
  'social_twitter'   => ['Twitter / X','i-tw', 'https://x.com/your-handle'],
  'social_youtube'   => ['YouTube',   'i-yt', 'https://youtube.com/@your-channel'],
];

$err = '';
$ok  = '';
$cur = [];
foreach ($fields as $k => $_) $cur[$k] = ws_get($conn, $k);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($fields as $k => $_) $cur[$k] = trim($_POST[$k] ?? '');

  // Light validation: if filled, must look like a URL
  foreach ($cur as $k => $v) {
    if ($v !== '' && !preg_match('~^https?://~i', $v)) {
      $err = $fields[$k][0] . ' link must start with http:// or https://';
      break;
    }
  }
  if (!$err) {
    foreach ($cur as $k => $v) ws_set($conn, $k, $v);
    $ok = 'Footer links saved. The site footer updates instantly.';
  }
}

// Pull admin contact (shown on public footer "Find us")
$adm = null;
$r = mysqli_query($conn, 'SELECT email, mobile FROM admins ORDER BY id LIMIT 1');
if ($r) $adm = mysqli_fetch_assoc($r);

$page_title = 'Footer settings';
$page_crumb = 'Website';
include __DIR__ . '/header.php';
?>

<style>
  .fs-wrap{max-width:100%}
  .fs-wrap .admin-card{padding:32px 34px}
  .fs-wrap h3{display:flex;align-items:center;justify-content:center;gap:14px;margin:0 0 4px}
  .fs-wrap h3::before,.fs-wrap h3::after{content:"";display:block;width:28px;height:1px;background:var(--gold);opacity:.5}
  .fs-wrap .sub{text-align:center;font-size:12.5px;color:var(--ink-2);margin:0 0 22px}

  .field-ico{
    display:inline-grid;place-items:center;
    width:40px;height:100%;flex-shrink:0;color:var(--ink-2);
    border-right:1px solid var(--rule);
  }
  .field-ico svg{width:16px;height:16px}

  .info-note{
    display:flex;gap:12px;align-items:flex-start;
    background:rgba(184,150,80,.08);border:1px solid rgba(184,150,80,.25);
    border-radius:10px;padding:14px 16px;margin:22px 0 0;
    font-size:13px;color:var(--ink-2);line-height:1.55;
  }
  .info-note b{color:var(--ink)}
  .info-note svg{color:var(--gold-deep);flex-shrink:0;margin-top:2px}
  .info-note .vals{margin-top:6px;color:var(--ink)}
  .info-note .vals span{display:inline-block;margin-right:16px}
  .info-note a{color:var(--brand);text-decoration:underline;text-decoration-color:rgba(139,58,28,.35);text-underline-offset:3px}
</style>

<div class="fs-wrap">
  <div class="admin-card">
    <h3>Footer settings</h3>
    <p class="sub">Social links shown in the public footer</p>

    <?php if ($ok): ?><div class="notice ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="notice err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <form method="post" action="footer-settings.php" novalidate>
      <div class="form-grid">
        <?php foreach ($fields as $k => [$label, $icon, $placeholder]): ?>
          <div class="form-row full">
            <label for="<?= $k ?>"><?= htmlspecialchars($label) ?> link</label>
            <div class="field">
              <span class="field-ico"><svg><use href="#<?= $icon ?>"/></svg></span>
              <input type="url" id="<?= $k ?>" name="<?= $k ?>"
                     placeholder="<?= htmlspecialchars($placeholder) ?>"
                     value="<?= htmlspecialchars($cur[$k]) ?>"
                     autocomplete="off" spellcheck="false">
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="info-note">
        <svg width="18" height="18"><use href="#i-mail"/></svg>
        <div>
          <b>Find us</b> on the public footer pulls from your admin profile.
          <div class="vals">
            <span>Email: <strong><?= htmlspecialchars($adm['email'] ?? 'not set') ?></strong></span>
            <span>Mobile: <strong><?= htmlspecialchars($adm['mobile'] ?? 'not set') ?></strong></span>
          </div>
          <div style="margin-top:6px;font-size:12.5px">Update them from <a href="profile.php">your profile</a>.</div>
        </div>
      </div>

      <button type="submit" class="btn-solid form-submit">
        <span>Save links</span>
        <svg width="14" height="14"><use href="#i-arrow-ur"/></svg>
      </button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>

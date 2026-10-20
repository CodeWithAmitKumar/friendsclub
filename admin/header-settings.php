<?php
session_start();
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

// websetting_master: generic key/value for site chrome (logo, phone, etc.)
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

// Logo shrink that preserves aspect ratio + transparency when possible.
function squashLogo($srcPath, $destDir, $maxBytes = 500 * 1024, $maxDim = 500) {
  $info = @getimagesize($srcPath);
  if (!$info) return null;
  [$w, $h] = $info;
  $mime = $info['mime'];

  $src = match($mime) {
    'image/jpeg','image/jpg' => imagecreatefromjpeg($srcPath),
    'image/png'              => imagecreatefrompng($srcPath),
    'image/webp'             => imagecreatefromwebp($srcPath),
    'image/gif'              => imagecreatefromgif($srcPath),
    default                  => null,
  };
  if (!$src) return null;

  $keepPng = in_array($mime, ['image/png','image/webp'], true);
  $ext = $keepPng ? 'png' : 'jpg';

  // Fit within maxDim box
  $scale = min(1.0, $maxDim / max($w, $h));
  $quality = 90;

  do {
    $nw = max(1, (int)($w * $scale));
    $nh = max(1, (int)($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);

    if ($keepPng) {
      imagealphablending($dst, false);
      imagesavealpha($dst, true);
      $trans = imagecolorallocatealpha($dst, 0, 0, 0, 127);
      imagefilledrectangle($dst, 0, 0, $nw, $nh, $trans);
    } else {
      $white = imagecolorallocate($dst, 255, 255, 255);
      imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    $tmp = tempnam(sys_get_temp_dir(), 'logo') . '.' . $ext;
    if ($keepPng) imagepng($dst, $tmp, 6);
    else          imagejpeg($dst, $tmp, $quality);
    imagedestroy($dst);

    if (filesize($tmp) <= $maxBytes) break;
    @unlink($tmp);

    if (!$keepPng && $quality > 55) { $quality -= 10; continue; }
    $scale *= 0.85;
  } while ($scale > 0.2);

  imagedestroy($src);

  $filename = 'logo_' . bin2hex(random_bytes(5)) . '.' . $ext;
  $destPath = rtrim($destDir, '/\\') . '/' . $filename;
  if (!@rename($tmp, $destPath)) {
    @copy($tmp, $destPath);
    @unlink($tmp);
  }
  return $filename;
}

$logoDir = __DIR__ . '/uploads/logos';
if (!is_dir($logoDir)) @mkdir($logoDir, 0775, true);

$err = '';
$ok  = '';
$current_logo = ws_get($conn, 'site_logo');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Clear logo
  if (!empty($_POST['clear_logo']) && $current_logo) {
    $old = $logoDir . '/' . basename($current_logo);
    if (is_file($old)) @unlink($old);
    ws_set($conn, 'site_logo', '');
    $current_logo = '';
    $ok = 'Logo removed.';
  }
  // Upload new logo
  elseif (!empty($_FILES['logo']['tmp_name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $mime = $_FILES['logo']['type'];
    $allowed = ['image/png','image/jpeg','image/jpg','image/webp','image/gif','image/svg+xml'];
    if (!in_array($mime, $allowed, true)) {
      $err = 'Please upload a PNG, JPG, WebP, GIF or SVG.';
    } else {
      $newName = null;
      if ($mime === 'image/svg+xml') {
        if ($_FILES['logo']['size'] > 500 * 1024) {
          $err = 'SVG is over 500 KB — please optimise and retry.';
        } else {
          $newName = 'logo_' . bin2hex(random_bytes(5)) . '.svg';
          move_uploaded_file($_FILES['logo']['tmp_name'], $logoDir . '/' . $newName);
        }
      } else {
        $newName = squashLogo($_FILES['logo']['tmp_name'], $logoDir, 500 * 1024);
        if (!$newName) $err = 'That image couldn\'t be processed. Try a PNG or JPG.';
      }
      if ($newName) {
        // Remove old logo file
        if ($current_logo) {
          $old = $logoDir . '/' . basename($current_logo);
          if (is_file($old)) @unlink($old);
        }
        ws_set($conn, 'site_logo', $newName);
        $current_logo = $newName;
        $ok = 'Logo updated. Your website header now shows it.';
      }
    }
  } else {
    $err = 'Choose a file first.';
  }
}

$page_title = 'Header settings';
$page_crumb = 'Website';
include __DIR__ . '/header.php';
?>

<style>
  .hs-wrap{max-width:100%}
  .hs-wrap .admin-card{padding:32px 34px}
  .hs-wrap h3{display:flex;align-items:center;justify-content:center;gap:14px;margin:0 0 4px}
  .hs-wrap h3::before,.hs-wrap h3::after{content:"";display:block;width:28px;height:1px;background:var(--gold);opacity:.5}
  .hs-wrap .sub{text-align:center;font-size:12.5px;color:var(--ink-2);margin:0 0 24px}

  .logo-strip{
    display:flex;align-items:center;gap:22px;padding:18px 0 22px;
    border-bottom:1px solid var(--rule-soft);margin-bottom:22px;
  }
  .logo-preview{
    width:200px;height:88px;flex-shrink:0;
    border:1px dashed rgba(26,21,17,.2);border-radius:10px;
    background:var(--paper);
    display:grid;place-items:center;overflow:hidden;padding:10px;
  }
  .logo-preview img{max-width:100%;max-height:100%;display:block;object-fit:contain}
  .logo-preview .ph{
    font-size:11px;letter-spacing:.24em;text-transform:uppercase;color:var(--ink-3);
  }
  .logo-actions{display:flex;flex-direction:column;gap:10px;align-items:flex-start}
  .pick{
    display:inline-flex;align-items:center;gap:8px;padding:10px 16px;
    border:1px solid var(--rule);border-radius:10px;background:transparent;color:var(--ink);
    font-size:13px;font-weight:600;cursor:pointer;
  }
  .pick:hover{border-color:var(--ink);background:rgba(26,21,17,.04)}
  .pick input{display:none}
  .logo-actions .hint{font-size:12.5px;color:var(--ink-2);max-width:46ch;line-height:1.5}
  .fname{font-size:12px;color:var(--ink-2);display:none}
</style>

<div class="hs-wrap">
  <div class="admin-card">
    <h3>Header settings</h3>
    <p class="sub">Upload a logo to make the public website header dynamic</p>

    <?php if ($ok): ?><div class="notice ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="notice err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <form method="post" action="header-settings.php" enctype="multipart/form-data">
      <div class="logo-strip">
        <div class="logo-preview">
          <?php if ($current_logo): ?>
            <img src="uploads/logos/<?= htmlspecialchars($current_logo) ?>?v=<?= time() ?>" alt="Current logo">
          <?php else: ?>
            <span class="ph">No logo yet</span>
          <?php endif; ?>
        </div>
        <div class="logo-actions">
          <label class="pick">
            <?= $current_logo ? 'Replace logo' : 'Upload logo' ?>
            <input type="file" name="logo" id="logoFile" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml"
                   onchange="var n=document.getElementById('fname');if(this.files&&this.files[0]){n.textContent=this.files[0].name;n.style.display='block';}">
          </label>
          <span class="fname" id="fname"></span>
          <div class="hint">PNG with transparent background looks cleanest. Max 500&nbsp;KB — larger files are auto-resized to fit, old logo is removed from the folder automatically.</div>
        </div>
      </div>

      <button type="submit" class="btn-solid form-submit">
        <span>Save logo</span>
        <svg width="14" height="14"><use href="#i-arrow-ur"/></svg>
      </button>

      <?php if ($current_logo): ?>
        <button type="submit" name="clear_logo" value="1" class="btn-ghost"
                style="display:inline-flex;margin:12px auto 0;width:fit-content"
                onclick="return confirm('Remove the current logo? The site will show the text wordmark again.');">
          <span>Remove logo</span>
        </button>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>

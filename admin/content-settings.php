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

// Resize helper — preserves aspect ratio, keeps PNG/WebP transparency.
function squashContent($srcPath, $destDir, $maxBytes = 600 * 1024, $maxDim = 1600) {
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

  $keepAlpha = in_array($mime, ['image/png','image/webp'], true);
  $ext = $keepAlpha ? 'png' : 'jpg';

  $scale = min(1.0, $maxDim / max($w, $h));
  $quality = 85;

  do {
    $nw = max(1, (int)($w * $scale));
    $nh = max(1, (int)($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    if ($keepAlpha) {
      imagealphablending($dst, false);
      imagesavealpha($dst, true);
      $t = imagecolorallocatealpha($dst, 0, 0, 0, 127);
      imagefilledrectangle($dst, 0, 0, $nw, $nh, $t);
    } else {
      $white = imagecolorallocate($dst, 255, 255, 255);
      imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    $tmp = tempnam(sys_get_temp_dir(), 'cnt') . '.' . $ext;
    if ($keepAlpha) imagepng($dst, $tmp, 6);
    else            imagejpeg($dst, $tmp, $quality);
    imagedestroy($dst);

    if (filesize($tmp) <= $maxBytes) break;
    @unlink($tmp);
    if (!$keepAlpha && $quality > 55) { $quality -= 10; continue; }
    $scale *= 0.85;
  } while ($scale > 0.2);

  imagedestroy($src);
  $filename = 'content_' . bin2hex(random_bytes(5)) . '.' . $ext;
  $destPath = rtrim($destDir, '/\\') . '/' . $filename;
  if (!@rename($tmp, $destPath)) { @copy($tmp, $destPath); @unlink($tmp); }
  return $filename;
}

$contentDir = __DIR__ . '/uploads/content';
if (!is_dir($contentDir)) @mkdir($contentDir, 0775, true);

$err = '';
$ok  = '';
$cur = [
  'content_heading'     => ws_get($conn, 'content_heading'),
  'content_description' => ws_get($conn, 'content_description'),
  'content_image'       => ws_get($conn, 'content_image'),
  'content_marquee'     => ws_get($conn, 'content_marquee'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $heading = trim($_POST['heading'] ?? '');
  $desc    = $_POST['description'] ?? '';       // raw HTML from CKEditor (admin-trusted)
  $marquee = trim($_POST['marquee'] ?? '');

  if ($heading === '')                              $err = 'Please enter a main heading.';
  elseif (trim(strip_tags($desc)) === '')           $err = 'Please enter a description.';
  elseif ($marquee === '')                          $err = 'Please enter the scrolling text.';

  // Image upload (optional)
  $newImageName = null;
  if (!$err && !empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $mime = $_FILES['image']['type'];
    $allowed = ['image/png','image/jpeg','image/jpg','image/webp','image/gif'];
    if (!in_array($mime, $allowed, true)) {
      $err = 'Please upload a PNG, JPG, WebP or GIF.';
    } else {
      $newImageName = squashContent($_FILES['image']['tmp_name'], $contentDir, 600 * 1024);
      if (!$newImageName) {
        $err = 'That image couldn\'t be processed.';
      } else if (!empty($cur['content_image'])) {
        $old = $contentDir . '/' . basename($cur['content_image']);
        if (is_file($old)) @unlink($old);
      }
    }
  }

  if (!$err) {
    ws_set($conn, 'content_heading', $heading);
    ws_set($conn, 'content_description', $desc);
    ws_set($conn, 'content_marquee', $marquee);
    if ($newImageName) ws_set($conn, 'content_image', $newImageName);
    foreach ($cur as $k => $_) $cur[$k] = ws_get($conn, $k);
    $ok = 'Content saved. Your home page is updated.';
  } else {
    // keep user's typed values for re-render
    $cur['content_heading']     = $heading;
    $cur['content_description'] = $desc;
    $cur['content_marquee']     = $marquee;
  }
}

$page_title = 'Content & pages';
$page_crumb = 'Website';
include __DIR__ . '/header.php';
?>

<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js" defer></script>
<style>
  .cs-wrap{max-width:100%}
  .cs-wrap .admin-card{padding:32px 34px}
  .cs-wrap h3{display:flex;align-items:center;justify-content:center;gap:14px;margin:0 0 4px}
  .cs-wrap h3::before,.cs-wrap h3::after{content:"";display:block;width:28px;height:1px;background:var(--gold);opacity:.5}
  .cs-wrap .sub{text-align:center;font-size:12.5px;color:var(--ink-2);margin:0 0 22px}

  /* CKEditor skin tweaks so it matches our form field language */
  .ck-editor__editable_inline{
    min-height:220px;background:var(--paper)!important;color:var(--ink);
    border:1px solid var(--rule)!important;border-radius:0 0 10px 10px!important;
    padding:14px 16px!important;
  }
  .ck.ck-toolbar{
    background:var(--paper)!important;border:1px solid var(--rule)!important;
    border-radius:10px 10px 0 0!important;
  }
  .ck-focused.ck-editor__editable_inline,
  .ck-editor__editable_inline:focus{
    border-color:var(--ink)!important;box-shadow:0 0 0 3px var(--brand-soft)!important;
  }

  .img-strip{
    display:flex;align-items:center;gap:22px;padding:14px 0 18px;
    border-bottom:1px solid var(--rule-soft);margin-bottom:4px;
  }
  .img-preview{
    width:220px;aspect-ratio:4/3;flex-shrink:0;
    border:1px dashed rgba(26,21,17,.2);border-radius:10px;
    background:var(--paper);
    display:grid;place-items:center;overflow:hidden;padding:6px;
  }
  .img-preview img{max-width:100%;max-height:100%;display:block;object-fit:contain;border-radius:6px}
  .img-preview .ph{font-size:11px;letter-spacing:.24em;text-transform:uppercase;color:var(--ink-3)}
  .img-actions{display:flex;flex-direction:column;gap:8px;align-items:flex-start}
  .pick{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border:1px solid var(--rule);border-radius:10px;background:transparent;color:var(--ink);font-size:13px;font-weight:600;cursor:pointer}
  .pick:hover{border-color:var(--ink);background:rgba(26,21,17,.04)}
  .pick input{display:none}
  .img-actions .hint{font-size:12.5px;color:var(--ink-2);max-width:46ch;line-height:1.5}
  .fname{font-size:12px;color:var(--ink-2);display:none}
</style>

<div class="cs-wrap">
  <div class="admin-card">
    <h3>Content &amp; pages</h3>
    <p class="sub">Hero copy, image and the scrolling tagline</p>

    <?php if ($ok): ?><div class="notice ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="notice err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <form method="post" action="content-settings.php" enctype="multipart/form-data">
      <div class="form-grid">
        <div class="form-row full">
          <label for="heading">Main heading</label>
          <div class="field">
            <input type="text" id="heading" name="heading" required
                   placeholder="Build what a neighbourhood can be, together."
                   value="<?= htmlspecialchars($cur['content_heading']) ?>">
          </div>
        </div>

        <div class="form-row full">
          <label for="description">Description</label>
          <textarea id="description" name="description"><?= htmlspecialchars($cur['content_description']) ?></textarea>
        </div>

        <div class="form-row full">
          <label>Main image</label>
          <div class="img-strip">
            <div class="img-preview">
              <?php if (!empty($cur['content_image'])): ?>
                <img src="uploads/content/<?= htmlspecialchars($cur['content_image']) ?>?v=<?= time() ?>" alt="Current main image">
              <?php else: ?>
                <span class="ph">No image yet</span>
              <?php endif; ?>
            </div>
            <div class="img-actions">
              <label class="pick">
                <?= !empty($cur['content_image']) ? 'Replace image' : 'Upload image' ?>
                <input type="file" name="image" id="imgFile" accept="image/png,image/jpeg,image/webp,image/gif"
                       onchange="var n=document.getElementById('ifname');if(this.files&&this.files[0]){n.textContent=this.files[0].name;n.style.display='block';}">
              </label>
              <span class="fname" id="ifname"></span>
              <div class="hint">Max 600&nbsp;KB. Larger files are auto-resized (max 1600px long side). The old image is removed from the folder on save.</div>
            </div>
          </div>
        </div>

        <div class="form-row full">
          <label for="marquee">Scrolling text</label>
          <div class="field">
            <input type="text" id="marquee" name="marquee" required
                   placeholder="Together For a Better Community"
                   value="<?= htmlspecialchars($cur['content_marquee']) ?>">
          </div>
        </div>
      </div>

      <button type="submit" class="btn-solid form-submit">
        <span>Save content</span>
        <svg width="14" height="14"><use href="#i-arrow-ur"/></svg>
      </button>
    </form>
  </div>
</div>

<script>
  window.addEventListener('load', function(){
    if (window.ClassicEditor) {
      ClassicEditor
        .create(document.querySelector('#description'), {
          toolbar: ['heading','|','bold','italic','link','bulletedList','numberedList','|','blockQuote','undo','redo']
        })
        .catch(function(e){ console.error(e); });
    }
  });
</script>

<?php include __DIR__ . '/footer.php'; ?>

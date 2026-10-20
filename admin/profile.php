<?php
session_start();
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$admin_id   = (int)$_SESSION['admin_id'];
$err = '';
$ok  = '';

// Avatar dir
$avatarDir = __DIR__ . '/uploads/avatars';
if (!is_dir($avatarDir)) @mkdir($avatarDir, 0775, true);

// Load current row
$stmt = mysqli_prepare($conn, 'SELECT username, name, email, mobile, avatar FROM admins WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $admin_id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$row) { session_destroy(); header('Location: index.php'); exit; }

// ------- helpers -------
function squashUnder($srcPath, $destPath, $maxBytes = 500 * 1024) {
  $info = @getimagesize($srcPath);
  if (!$info) return false;
  [$w, $h] = $info;
  $src = match($info['mime']) {
    'image/jpeg','image/jpg' => imagecreatefromjpeg($srcPath),
    'image/png'              => imagecreatefrompng($srcPath),
    'image/gif'              => imagecreatefromgif($srcPath),
    'image/webp'             => imagecreatefromwebp($srcPath),
    default                  => null,
  };
  if (!$src) return false;

  $quality = 88;
  $scale   = 1.0;
  // Pre-shrink huge images
  $maxDim  = 800;
  if (max($w,$h) > $maxDim) $scale = $maxDim / max($w,$h);

  do {
    $nw  = max(1,(int)($w * $scale));
    $nh  = max(1,(int)($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    // white background in case of transparency flattened to JPEG
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagejpeg($dst, $destPath, $quality);
    imagedestroy($dst);

    if (filesize($destPath) <= $maxBytes) break;
    if ($quality > 55) $quality -= 10;
    else $scale *= 0.85;
  } while ($scale > 0.25);

  imagedestroy($src);
  return true;
}

// ------- handle POST -------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name   = trim($_POST['name']   ?? '');
  $email  = trim($_POST['email']  ?? '');
  $mobile = trim($_POST['mobile'] ?? '');
  $cropped = $_POST['cropped_data'] ?? '';   // base64 data URL from CropperJS

  if ($name === '')                                        $err = 'Please enter your name.';
  elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'That email doesn\'t look right.';
  elseif ($mobile !== '' && !preg_match('/^[0-9+\-\s]{6,20}$/', $mobile)) $err = 'That mobile number looks off.';

  // Avatar: cropped base64 wins; else raw file upload fallback
  $newAvatarName = null;
  if (!$err) {
    $tmpSrc = null;

    if ($cropped && str_starts_with($cropped, 'data:image/')) {
      if (preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,(.+)$/', $cropped, $m)) {
        $tmpSrc = tempnam(sys_get_temp_dir(), 'ava');
        file_put_contents($tmpSrc, base64_decode($m[2]));
      }
    } elseif (!empty($_FILES['avatar']['tmp_name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
      $tmpSrc = $_FILES['avatar']['tmp_name'];
    }

    if ($tmpSrc) {
      $newAvatarName = 'a' . $admin_id . '_' . bin2hex(random_bytes(5)) . '.jpg';
      $destPath = $avatarDir . '/' . $newAvatarName;
      if (!squashUnder($tmpSrc, $destPath, 500 * 1024)) {
        $err = 'That image couldn\'t be processed. Try a JPG or PNG.';
        $newAvatarName = null;
      } else {
        // Remove old avatar file if present
        if (!empty($row['avatar'])) {
          $oldPath = $avatarDir . '/' . basename($row['avatar']);
          if (is_file($oldPath)) @unlink($oldPath);
        }
      }
      if (str_starts_with($tmpSrc, sys_get_temp_dir())) @unlink($tmpSrc);
    }
  }

  if (!$err) {
    if ($newAvatarName !== null) {
      $u = mysqli_prepare($conn, 'UPDATE admins SET name=?, email=?, mobile=?, avatar=? WHERE id=?');
      mysqli_stmt_bind_param($u, 'ssssi', $name, $email, $mobile, $newAvatarName, $admin_id);
    } else {
      $u = mysqli_prepare($conn, 'UPDATE admins SET name=?, email=?, mobile=? WHERE id=?');
      mysqli_stmt_bind_param($u, 'sssi', $name, $email, $mobile, $admin_id);
    }
    if (mysqli_stmt_execute($u)) {
      $ok = 'Profile updated.';
      // refresh row for display
      mysqli_stmt_execute($stmt);
      $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    } else {
      $err = 'Database error: ' . mysqli_error($conn);
    }
  }
}

$page_title = 'Profile';
$page_crumb = 'Account';
include __DIR__ . '/header.php';
?>

<!-- CropperJS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js" defer></script>

<style>
  .profile-wrap{max-width:100%}
  .profile-wrap .admin-card{padding:32px 34px}
  .profile-wrap h3{display:flex;align-items:center;justify-content:center;gap:14px;margin:0 0 4px}
  .profile-wrap h3::before,.profile-wrap h3::after{content:"";display:block;width:28px;height:1px;background:var(--gold);opacity:.5}
  .profile-wrap .sub{text-align:center;font-size:12.5px;color:var(--ink-2);margin:0 0 22px}

  .avatar-strip{
    display:flex;align-items:center;gap:20px;padding:18px 0 22px;
    border-bottom:1px solid var(--rule-soft);margin-bottom:22px;
  }
  .avatar-strip .preview{
    width:88px;height:88px;border-radius:999px;overflow:hidden;flex-shrink:0;
    background:linear-gradient(145deg,#d6b874,#b89650 55%,#876c33);
    color:#0b1710;display:grid;place-items:center;
    font-family:'Fraunces',serif;font-weight:700;font-size:32px;letter-spacing:-.02em;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.4),inset 0 -2px 4px rgba(0,0,0,.18),0 0 0 1px rgba(184,150,80,.35);
  }
  .avatar-strip .preview img{width:100%;height:100%;object-fit:cover;display:block}
  .avatar-strip .actions{display:flex;flex-direction:column;gap:8px;align-items:flex-start}
  .avatar-strip .pick{
    display:inline-flex;align-items:center;gap:8px;padding:9px 14px;
    border:1px solid var(--rule);border-radius:10px;background:transparent;color:var(--ink);
    font-size:13px;font-weight:600;cursor:pointer;
  }
  .avatar-strip .pick:hover{border-color:var(--ink);background:rgba(26,21,17,.04)}
  .avatar-strip .hint{font-size:12px;color:var(--ink-2)}
  .avatar-strip input[type=file]{display:none}

  /* Crop modal */
  .crop-modal{
    position:fixed;inset:0;background:rgba(11,23,16,.72);z-index:100;
    display:none;align-items:center;justify-content:center;padding:20px;
  }
  .crop-modal.open{display:flex}
  .crop-box{
    background:var(--card);border-radius:14px;max-width:560px;width:100%;
    box-shadow:0 40px 80px -20px rgba(0,0,0,.55);
    overflow:hidden;border:1px solid rgba(184,150,80,.3);
  }
  .crop-box .top{padding:16px 20px;border-bottom:1px solid var(--rule-soft);
    display:flex;align-items:center;justify-content:space-between;
    font-family:'Fraunces',serif;font-size:17px;color:var(--ink);
  }
  .crop-box .top .close{background:transparent;border:0;cursor:pointer;color:var(--ink-2);font-size:22px;line-height:1}
  .crop-stage{max-height:60vh;background:#111;display:flex;align-items:center;justify-content:center}
  .crop-stage img{max-width:100%;display:block}
  .crop-foot{padding:14px 20px;display:flex;justify-content:flex-end;gap:10px;border-top:1px solid var(--rule-soft)}
</style>

<div class="profile-wrap">
  <div class="admin-card">
    <h3>Update profile</h3>
    <p class="sub">Edit your name, contact and portrait</p>

    <?php if ($ok): ?><div class="notice ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="notice err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <form method="post" action="profile.php" enctype="multipart/form-data" id="profileForm">
      <div class="avatar-strip">
        <div class="preview" id="avaPreview">
          <?php if (!empty($row['avatar'])): ?>
            <img src="uploads/avatars/<?= htmlspecialchars($row['avatar']) ?>?v=<?= time() ?>" alt="">
          <?php else: ?>
            <?= htmlspecialchars(strtoupper(substr($row['name'] ?: $row['username'],0,1))) ?>
          <?php endif; ?>
        </div>
        <div class="actions">
          <label class="pick">
            Choose photo
            <input type="file" name="avatar" id="avaFile" accept="image/jpeg,image/png,image/webp">
          </label>
          <div class="hint">Square crops look best. Max 500 KB — larger files are auto-resized.</div>
        </div>
        <input type="hidden" name="cropped_data" id="croppedData" value="">
      </div>

      <div class="form-grid">
        <div class="form-row">
          <label for="username">Username</label>
          <div class="field">
            <input type="text" id="username" value="<?= htmlspecialchars($row['username']) ?>" readonly disabled style="color:var(--ink-2)">
          </div>
        </div>
        <div class="form-row">
          <label for="name">Admin name</label>
          <div class="field">
            <input type="text" id="name" name="name" required value="<?= htmlspecialchars($row['name'] ?? '') ?>" placeholder="Your full name">
          </div>
        </div>
        <div class="form-row">
          <label for="email">Email ID</label>
          <div class="field">
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($row['email'] ?? '') ?>" placeholder="you@example.com">
          </div>
        </div>
        <div class="form-row">
          <label for="mobile">Mobile number</label>
          <div class="field">
            <input type="tel" id="mobile" name="mobile" value="<?= htmlspecialchars($row['mobile'] ?? '') ?>" placeholder="+91 00000 00000">
          </div>
        </div>
      </div>

      <button type="submit" class="btn-solid form-submit">
        <span>Save profile</span>
        <svg width="14" height="14"><use href="#i-arrow-ur"/></svg>
      </button>
    </form>
  </div>
</div>

<!-- Crop modal -->
<div class="crop-modal" id="cropModal" role="dialog" aria-modal="true" aria-label="Crop photo">
  <div class="crop-box">
    <div class="top">
      <span>Crop your photo</span>
      <button type="button" class="close" id="cropCancel" aria-label="Cancel">&times;</button>
    </div>
    <div class="crop-stage"><img id="cropImg" alt=""></div>
    <div class="crop-foot">
      <button type="button" class="btn-ghost" id="cropCancel2">Cancel</button>
      <button type="button" class="btn-solid" id="cropOk">
        <span>Use this</span>
        <svg width="14" height="14"><use href="#i-arrow-ur"/></svg>
      </button>
    </div>
  </div>
</div>

<script>
(function(){
  var fileInput = document.getElementById('avaFile');
  var modal     = document.getElementById('cropModal');
  var cropImg   = document.getElementById('cropImg');
  var preview   = document.getElementById('avaPreview');
  var hidden    = document.getElementById('croppedData');
  var okBtn     = document.getElementById('cropOk');
  var cancel1   = document.getElementById('cropCancel');
  var cancel2   = document.getElementById('cropCancel2');
  var cropper   = null;

  function openCrop(dataUrl){
    cropImg.src = dataUrl;
    modal.classList.add('open');
    if (cropper) { cropper.destroy(); cropper = null; }
    cropImg.onload = function(){
      cropper = new Cropper(cropImg, {
        aspectRatio: 1, viewMode: 1, autoCropArea: 1,
        background: false, movable: true, zoomable: true, rotatable: false,
      });
    };
  }
  function closeCrop(){
    modal.classList.remove('open');
    if (cropper) { cropper.destroy(); cropper = null; }
    fileInput.value = '';
  }

  fileInput.addEventListener('change', function(){
    if (!this.files || !this.files[0]) return;
    var reader = new FileReader();
    reader.onload = function(e){ openCrop(e.target.result); };
    reader.readAsDataURL(this.files[0]);
  });

  cancel1.addEventListener('click', closeCrop);
  cancel2.addEventListener('click', closeCrop);
  modal.addEventListener('click', function(e){ if (e.target === modal) closeCrop(); });

  okBtn.addEventListener('click', function(){
    if (!cropper) return;
    var canvas = cropper.getCroppedCanvas({ width: 400, height: 400, imageSmoothingQuality: 'high' });
    var dataUrl = canvas.toDataURL('image/jpeg', 0.88);
    hidden.value = dataUrl;
    preview.innerHTML = '<img src="' + dataUrl + '" alt="">';
    modal.classList.remove('open');
    if (cropper) { cropper.destroy(); cropper = null; }
  });
})();
</script>

<?php include __DIR__ . '/footer.php'; ?>

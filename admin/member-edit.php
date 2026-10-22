<?php
session_start();
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) { header('Location: members.php'); exit; }

// Load member
$load = mysqli_prepare($conn, 'SELECT * FROM members WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($load, 'i', $id);
mysqli_stmt_execute($load);
$m = mysqli_fetch_assoc(mysqli_stmt_get_result($load));
if (!$m) { header('Location: members.php'); exit; }

$countries = ['India','United States','United Kingdom','Canada','Australia','United Arab Emirates','Singapore','Germany','France','Netherlands','Japan','New Zealand','South Africa','Nepal','Sri Lanka','Bangladesh','Pakistan','Other'];
$isdCodes  = ['+91','+1','+44','+61','+971','+65','+49','+33','+31','+81','+64','+27','+977','+94','+880','+92'];

function generate_t_password(): string {
  return chr(random_int(97, 122)) . str_pad((string)random_int(0, 99999), 5, '0', STR_PAD_LEFT);
}

$err = '';
$ok  = '';
$newLoginPass = '';
$newTxnPass   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $act = $_POST['act'] ?? 'save';

  if ($act === 'reset_login') {
    $newLoginPass = chr(random_int(97, 122)) . str_pad((string)random_int(0, 99999), 5, '0', STR_PAD_LEFT) . chr(random_int(97, 122));
    $hash = password_hash($newLoginPass, PASSWORD_DEFAULT);
    $s = mysqli_prepare($conn, 'UPDATE members SET password = ? WHERE id = ?');
    mysqli_stmt_bind_param($s, 'si', $hash, $id);
    mysqli_stmt_execute($s);
    $ok = 'Login password has been reset. Share the new one with the member below:';
  } elseif ($act === 'reset_txn') {
    $newTxnPass = generate_t_password();
    $hash = password_hash($newTxnPass, PASSWORD_DEFAULT);
    $s = mysqli_prepare($conn, 'UPDATE members SET t_password = ? WHERE id = ?');
    mysqli_stmt_bind_param($s, 'si', $hash, $id);
    mysqli_stmt_execute($s);
    $ok = 'Transaction password has been reset. New one shown below:';
  } else {
    // Save field changes
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $country = trim($_POST['country'] ?? '');
    $isd     = trim($_POST['isd']     ?? '');
    $mobile  = trim($_POST['mobile']  ?? '');
    $status  = $_POST['status']       ?? 'active';
    $wallet  = (float)($_POST['wallet'] ?? 0);

    if ($name === '')                                            $err = 'Name is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))          $err = 'Email doesn\'t look right.';
    elseif ($country === '')                                     $err = 'Country is required.';
    elseif (!preg_match('/^\d{6,15}$/', $mobile))                $err = 'Mobile should be 6–15 digits.';
    elseif (!in_array($status, ['active','inactive'], true))     $err = 'Invalid status.';

    // Uniqueness (excluding self)
    if (!$err) {
      $s = mysqli_prepare($conn, 'SELECT id FROM members WHERE email = ? AND id <> ? LIMIT 1');
      mysqli_stmt_bind_param($s, 'si', $email, $id);
      mysqli_stmt_execute($s);
      if (mysqli_fetch_assoc(mysqli_stmt_get_result($s))) $err = 'Another member already uses that email.';
    }
    if (!$err) {
      $s = mysqli_prepare($conn, 'SELECT id FROM members WHERE isd = ? AND mobile = ? AND id <> ? LIMIT 1');
      mysqli_stmt_bind_param($s, 'ssi', $isd, $mobile, $id);
      mysqli_stmt_execute($s);
      if (mysqli_fetch_assoc(mysqli_stmt_get_result($s))) $err = 'Another member already uses that mobile.';
    }

    if (!$err) {
      $u = mysqli_prepare($conn,
        'UPDATE members SET name=?, email=?, country=?, isd=?, mobile=?, status=?, wallet=? WHERE id = ?');
      mysqli_stmt_bind_param($u, 'ssssssdi', $name, $email, $country, $isd, $mobile, $status, $wallet, $id);
      if (mysqli_stmt_execute($u)) {
        $ok = 'Member updated.';
      } else {
        $err = 'Database error: ' . mysqli_error($conn);
      }
    }

    // Refresh row
    mysqli_stmt_execute($load);
    $m = mysqli_fetch_assoc(mysqli_stmt_get_result($load));
  }
}

$page_title = 'Edit member';
$page_crumb = 'Members';
include __DIR__ . '/header.php';
?>

<style>
  .me-wrap{max-width:100%}
  .me-wrap .admin-card{padding:30px 32px}
  .me-head{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin:0 0 20px}
  .me-head .l{
    font-size:10.5px;letter-spacing:.26em;text-transform:uppercase;color:var(--gold-deep);font-weight:500;margin:0 0 4px;
  }
  .me-head h3{
    display:flex;align-items:center;gap:14px;margin:0;
    font-family:'Fraunces',serif;font-weight:500;font-size:24px;color:var(--ink);letter-spacing:-.015em;
  }
  .me-head h3::before,.me-head h3::after{content:"";display:block;width:28px;height:1px;background:var(--gold);opacity:.5}
  .me-head .uid{
    font-family:ui-monospace,'SF Mono','Menlo','Consolas',monospace;color:var(--brand);font-weight:600;letter-spacing:.02em;
  }
  .me-head .back{
    display:inline-flex;align-items:center;gap:6px;color:var(--ink-2);font-size:12.5px;text-decoration:underline;
    text-underline-offset:3px;text-decoration-color:var(--ink-3);
  }
  .me-head .back:hover{color:var(--brand);text-decoration-color:var(--brand)}

  .pw-reset{
    display:flex;flex-direction:column;gap:12px;padding:18px 20px;margin-top:22px;
    border:1px dashed rgba(184,150,80,.4);border-radius:12px;background:rgba(184,150,80,.05);
  }
  .pw-reset h4{
    display:flex;align-items:center;gap:10px;margin:0;
    font-family:'Fraunces',serif;font-weight:500;font-size:16px;color:var(--ink);
  }
  .pw-reset h4::before{content:"";width:14px;height:1px;background:var(--gold)}
  .pw-reset p{margin:0;font-size:12.5px;color:var(--ink-2);line-height:1.5}
  .pw-reset .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .pw-reset button{
    background:var(--ink);color:var(--paper);border:0;padding:9px 16px;border-radius:999px;
    font:inherit;font-weight:600;font-size:12.5px;letter-spacing:.01em;cursor:pointer;
    transition:background .2s ease, transform .15s ease;
  }
  .pw-reset button:hover{background:var(--brand);transform:translateY(-1px)}
  .pw-pill{
    display:inline-flex;align-items:center;gap:8px;background:var(--ink);color:var(--paper);
    padding:8px 14px;border-radius:999px;font-family:ui-monospace,monospace;letter-spacing:2px;font-size:13px;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.1);
  }
  .pw-pill .l{font-family:'Inter',sans-serif;font-size:10px;letter-spacing:.3em;text-transform:uppercase;color:var(--gold);letter-spacing:.26em}
</style>

<div class="me-wrap">
  <div class="admin-card">
    <div class="me-head">
      <div>
        <p class="l">Edit member</p>
        <h3><?= htmlspecialchars($m['name']) ?> <span class="uid"><?= htmlspecialchars($m['user_id']) ?></span></h3>
      </div>
      <a class="back" href="members.php">&larr; Back to members</a>
    </div>

    <?php if ($ok): ?>
      <div class="notice ok">
        <?= htmlspecialchars($ok) ?>
        <?php if ($newLoginPass): ?>
          <div style="margin-top:8px"><span class="pw-pill"><span class="l">New login</span> <?= htmlspecialchars($newLoginPass) ?></span></div>
        <?php endif; ?>
        <?php if ($newTxnPass): ?>
          <div style="margin-top:8px"><span class="pw-pill"><span class="l">New txn</span> <?= htmlspecialchars($newTxnPass) ?></span></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <?php if ($err): ?><div class="notice err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <form method="post" action="member-edit.php?id=<?= (int)$id ?>">
      <input type="hidden" name="id" value="<?= (int)$id ?>">
      <input type="hidden" name="act" value="save">

      <div class="form-grid">
        <div class="form-row">
          <label>User ID</label>
          <div class="field">
            <input type="text" value="<?= htmlspecialchars($m['user_id']) ?>" readonly disabled style="color:var(--ink-2);font-family:ui-monospace,monospace">
          </div>
        </div>
        <div class="form-row">
          <label for="name">Full name</label>
          <div class="field">
            <input type="text" id="name" name="name" required value="<?= htmlspecialchars($m['name']) ?>">
          </div>
        </div>

        <div class="form-row">
          <label for="email">Email</label>
          <div class="field">
            <input type="email" id="email" name="email" required value="<?= htmlspecialchars($m['email']) ?>">
          </div>
        </div>
        <div class="form-row">
          <label for="country">Country</label>
          <div class="field">
            <select id="country" name="country" required>
              <?php foreach ($countries as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>" <?= $m['country']===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row full">
          <label for="mobile">Mobile number</label>
          <div class="field">
            <select class="isd" name="isd" aria-label="Country code">
              <?php foreach ($isdCodes as $code): ?>
                <option value="<?= $code ?>" <?= $m['isd']===$code?'selected':'' ?>><?= $code ?></option>
              <?php endforeach; ?>
            </select>
            <input type="tel" id="mobile" name="mobile" required inputmode="numeric" pattern="\d{6,15}" value="<?= htmlspecialchars($m['mobile']) ?>">
          </div>
        </div>

        <div class="form-row">
          <label for="status">Status</label>
          <div class="field">
            <select id="status" name="status">
              <option value="active"   <?= $m['status']==='active'?'selected':'' ?>>Active</option>
              <option value="inactive" <?= $m['status']==='inactive'?'selected':'' ?>>Inactive</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <label for="wallet">Wallet ($)</label>
          <div class="field">
            <input type="number" id="wallet" name="wallet" step="0.01" min="0" value="<?= htmlspecialchars(number_format((float)$m['wallet'], 2, '.', '')) ?>" style="font-variant-numeric:tabular-nums">
          </div>
        </div>

        <div class="form-row">
          <label>Referred by</label>
          <div class="field">
            <input type="text" value="<?= htmlspecialchars($m['referral_id'] ?: '—') ?>" readonly disabled style="color:var(--ink-2);font-family:ui-monospace,monospace">
          </div>
        </div>
        <div class="form-row">
          <label>Joined</label>
          <div class="field">
            <input type="text" value="<?= htmlspecialchars(date('d M Y, H:i', strtotime($m['created_at']))) ?>" readonly disabled style="color:var(--ink-2)">
          </div>
        </div>
      </div>

      <button type="submit" class="btn-solid form-submit">
        <span>Save changes</span>
        <svg width="14" height="14"><use href="#i-arrow-ur"/></svg>
      </button>
    </form>

    <div class="pw-reset">
      <h4>Passwords</h4>
      <p>Resetting generates a new password immediately. Copy it before leaving this page — it won't be shown again.</p>
      <div class="row">
        <form method="post" action="member-edit.php?id=<?= (int)$id ?>" style="display:inline" onsubmit="return confirm('Generate a new LOGIN password for this member?');">
          <input type="hidden" name="id" value="<?= (int)$id ?>">
          <input type="hidden" name="act" value="reset_login">
          <button type="submit">Reset login password</button>
        </form>
        <form method="post" action="member-edit.php?id=<?= (int)$id ?>" style="display:inline" onsubmit="return confirm('Generate a new TRANSACTION password for this member?');">
          <input type="hidden" name="id" value="<?= (int)$id ?>">
          <input type="hidden" name="act" value="reset_txn">
          <button type="submit">Reset transaction password</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>

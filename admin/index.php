<?php
// Admin login
session_start();
require_once __DIR__ . '/../config.php';

$err = '';
$u   = '';

// Pull the uploaded site logo (if any) for brand + favicon
$site_logo = '';
$_lr = @mysqli_query($conn, "SELECT `value` FROM websetting_master WHERE `key` = 'site_logo' LIMIT 1");
if ($_lr && ($_lx = @mysqli_fetch_assoc($_lr))) $site_logo = $_lx['value'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = trim($_POST['username'] ?? '');
  $p = trim($_POST['password'] ?? '');

  if ($u === '' || $p === '') {
    $err = 'Please enter both username and password.';
  } else {
    // Lookup — expects an `admins` table: id, username, password (bcrypt hashed)
    $stmt = mysqli_prepare($conn, 'SELECT id, password FROM admins WHERE username = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $u);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($row && password_verify($p, $row['password'])) {
      $_SESSION['admin_id']       = (int)$row['id'];
      $_SESSION['admin_username'] = $u;
      header('Location: dashboard.php');
      exit;
    }
    $err = 'Invalid username or password.';
  }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login — Friends Club</title>
<?php if ($site_logo): $ext = pathinfo($site_logo, PATHINFO_EXTENSION); $mime = $ext==='svg'?'svg+xml':($ext==='jpg'?'jpeg':$ext); ?>
  <link rel="icon" type="image/<?= $mime ?>" href="uploads/logos/<?= htmlspecialchars($site_logo) ?>">
  <link rel="apple-touch-icon" href="uploads/logos/<?= htmlspecialchars($site_logo) ?>">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,ital,wght@9..144,0,500;9..144,0,600;9..144,0,800;9..144,1,500;9..144,1,600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/site.css">
<svg xmlns="http://www.w3.org/2000/svg" style="display:none">
  <symbol id="i-arrow-ur" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></symbol>
  <symbol id="i-eye"      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></symbol>
  <symbol id="i-eye-off"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17.9 17.9A10.4 10.4 0 0 1 12 19c-6.5 0-10-7-10-7a18.4 18.4 0 0 1 4.1-5.1"/><path d="M9.9 4.2A10.9 10.9 0 0 1 12 4c6.5 0 10 7 10 7a18.6 18.6 0 0 1-2.4 3.4"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/><path d="M2 2l20 20"/></symbol>
  <symbol id="i-shield"   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 5v7c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V5z"/></symbol>
</svg>
<style>
  body{
    display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px;
    background:
      radial-gradient(1200px 700px at 15% 0%, rgba(184,150,80,.14), transparent 60%),
      radial-gradient(900px 600px at 85% 100%, rgba(94,26,40,.18), transparent 55%),
      linear-gradient(180deg, #0b1710 0%, #122018 60%, #0b1710 100%);
    color:var(--paper);
  }
  /* Deep paper grain on dark */
  body::before{
    content:"";position:fixed;inset:0;pointer-events:none;z-index:0;opacity:.5;
    background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='240' height='240'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/><feColorMatrix values='0 0 0 0 1 0 0 0 0 1 0 0 0 0 1 0 0 0 0.045 0'/></filter><rect width='100%25' height='100%25' filter='url(%23n)'/></svg>");
  }
  .admin-shell{width:100%;max-width:460px;position:relative;z-index:1}

  .admin-brand{
    display:flex;flex-direction:column;align-items:center;gap:10px;margin:0 0 26px;
  }
  .admin-crest{
    width:56px;height:56px;border-radius:999px;
    background:linear-gradient(145deg, #d6b874 0%, #b89650 55%, #876c33 100%);
    color:#0b1710;display:grid;place-items:center;
    box-shadow:
      inset 0 1px 0 rgba(255,255,255,.4),
      inset 0 -2px 4px rgba(0,0,0,.2),
      0 10px 30px rgba(0,0,0,.4),
      0 0 0 1px rgba(184,150,80,.35);
  }
  .admin-crest svg{width:26px;height:26px}
  .admin-brand .lockup{
    font-family:'Fraunces',serif;font-weight:600;font-size:22px;letter-spacing:-.02em;color:var(--paper);
    margin-top:4px;
  }
  .admin-brand .lockup .dot{color:var(--gold);margin:0 2px}
  .admin-brand .rule{
    display:flex;align-items:center;gap:10px;
    font-size:10px;letter-spacing:.32em;text-transform:uppercase;color:var(--gold);font-weight:500;
  }
  .admin-brand .rule::before,
  .admin-brand .rule::after{
    content:"";display:block;width:28px;height:1px;background:linear-gradient(90deg, transparent, var(--gold), transparent);
  }
  .admin-logo{
    display:block;height:72px;max-height:72px;width:auto;max-width:240px;
    object-fit:contain;margin:0 0 4px;
    filter:drop-shadow(0 8px 20px rgba(0,0,0,.4));
  }

  /* Light cream card floats on the dark surface */
  .admin-shell .form-card{
    background:var(--card);
    border:1px solid rgba(184,150,80,.22);
    box-shadow:
      0 0 0 1px rgba(184,150,80,.1),
      0 40px 80px -20px rgba(0,0,0,.55),
      0 20px 40px -16px rgba(0,0,0,.4);
    padding:40px 36px 32px;
  }
  .admin-shell .form-card h2{
    display:flex;align-items:center;justify-content:center;gap:14px;
    margin-bottom:4px;
  }
  .admin-shell .form-card h2::before,
  .admin-shell .form-card h2::after{
    content:"";display:block;width:28px;height:1px;background:var(--gold);opacity:.5;
  }
  .admin-sub{
    text-align:center;font-size:12.5px;color:var(--ink-2);letter-spacing:.02em;
    margin:0 0 24px;
  }

  /* Royal footer line under the shell */
  .admin-foot{
    display:flex;align-items:center;justify-content:center;gap:10px;
    margin-top:22px;font-size:11.5px;letter-spacing:.22em;text-transform:uppercase;
    color:rgba(184,150,80,.6);
  }
  .admin-foot .dot{width:4px;height:4px;border-radius:50%;background:var(--gold);opacity:.6}
</style>
</head>
<body>
  <div class="admin-shell">
    <div class="admin-brand">
      <?php if ($site_logo): ?>
        <img class="admin-logo" src="uploads/logos/<?= htmlspecialchars($site_logo) ?>" alt="FriendsClub">
      <?php else: ?>
        <div class="admin-crest"><svg><use href="#i-shield"/></svg></div>
        <div class="lockup">Friends<span class="dot">·</span>Club</div>
      <?php endif; ?>
      <div class="rule">Admin Console</div>
    </div>

    <form class="form-card centered" method="post" action="index.php" novalidate>
      <h2>Sign in</h2>
      <p class="admin-sub">Authorised personnel only</p>

      <?php if ($err): ?>
        <div class="notice err"><?= htmlspecialchars($err) ?></div>
      <?php endif; ?>

      <div class="form-grid">
        <div class="form-row full">
          <label for="username">Username</label>
          <div class="field">
            <input type="text" id="username" name="username" required autocomplete="username"
                   placeholder="Your admin username" value="<?= htmlspecialchars($u) ?>">
          </div>
        </div>

        <div class="form-row full">
          <label for="password">Password</label>
          <div class="field">
            <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Your password">
            <button type="button" class="pw-toggle" aria-label="Show password"
              onclick="var i=document.getElementById('password');var o=this.querySelector('svg use');var h=i.type==='password';i.type=h?'text':'password';o.setAttribute('href',h?'#i-eye-off':'#i-eye');">
              <svg><use href="#i-eye"/></svg>
            </button>
          </div>
        </div>
      </div>

      <div class="form-aux">
        <label class="form-check inline">
          <input type="checkbox" name="remember">
          <span class="box"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m4 12 6 6 10-14"/></svg></span>
          <span>Remember me</span>
        </label>
        <a class="link-quiet" href="forgot.php">Forgot password?</a>
      </div>

      <button type="submit" class="btn-solid form-submit">
        <span>Sign in</span>
        <svg width="14" height="14"><use href="#i-arrow-ur"/></svg>
      </button>

    </form>

    <div class="admin-foot">
      <span>Secured</span>
      <span class="dot"></span>
      <span>v1.0</span>
    </div>
  </div>
</body>
</html>

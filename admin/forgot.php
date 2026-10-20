<?php
session_start();
require_once __DIR__ . '/../config.php';

$err = '';
$ok  = '';
$email_in = '';
$shownPassword = ''; // only populated if SMTP wasn't configured

// Pull site logo for brand + favicon
$site_logo = '';
$_lr = @mysqli_query($conn, "SELECT `value` FROM websetting_master WHERE `key` = 'site_logo' LIMIT 1");
if ($_lr && ($_lx = @mysqli_fetch_assoc($_lr))) $site_logo = $_lx['value'];

/* ---------- Minimal SMTP sender over STARTTLS (works with Gmail app passwords) ---------- */
function send_smtp_mail(string $host, int $port, string $user, string $pass,
                       string $from, string $fromName, string $to,
                       string $subject, string $html): void {
  $sock = @fsockopen($host, $port, $errno, $errstr, 15);
  if (!$sock) throw new RuntimeException("SMTP connect failed: $errstr ($errno)");
  stream_set_timeout($sock, 20);

  $expect = function (string $code) use ($sock): string {
    $buf = '';
    while (($line = fgets($sock, 515)) !== false) {
      $buf .= $line;
      if (preg_match('/^\d{3} /', $line)) break;
    }
    if (!str_starts_with($buf, $code)) throw new RuntimeException("SMTP wanted $code, got: " . trim($buf));
    return $buf;
  };
  $send = fn(string $s) => fwrite($sock, $s . "\r\n");

  $expect('220');
  $send('EHLO localhost'); $expect('250');
  $send('STARTTLS');       $expect('220');
  if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
    throw new RuntimeException('STARTTLS crypto handshake failed');
  }
  $send('EHLO localhost'); $expect('250');
  $send('AUTH LOGIN');     $expect('334');
  $send(base64_encode($user)); $expect('334');
  $send(base64_encode($pass)); $expect('235');
  $send('MAIL FROM: <' . $from . '>'); $expect('250');
  $send('RCPT TO: <' . $to . '>');     $expect('250');
  $send('DATA');                       $expect('354');

  $headers = [
    'From: ' . mb_encode_mimeheader($fromName) . ' <' . $from . '>',
    'To: <' . $to . '>',
    'Subject: ' . mb_encode_mimeheader($subject),
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
    'Date: ' . date('r'),
  ];
  // RFC 5321: dot-stuffing on lines starting with "."
  $html = preg_replace('/^\./m', '..', $html);
  fwrite($sock, implode("\r\n", $headers) . "\r\n\r\n" . $html . "\r\n.\r\n");
  $expect('250');
  $send('QUIT');
  fclose($sock);
}

function build_reset_email(string $siteName, string $adminName, string $newPassword, string $loginUrl): string {
  return <<<HTML
<!doctype html>
<html>
<body style="margin:0;padding:0;background:#f1ebda;font-family:Georgia,'Times New Roman',serif;color:#1a1511;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1ebda;padding:40px 20px;">
    <tr><td align="center">
      <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;background:#faf3dd;border:1px solid #d9c993;border-radius:14px;overflow:hidden;box-shadow:0 20px 50px -20px rgba(15,22,18,.3);">
        <tr><td style="padding:36px 40px 16px;text-align:center;border-bottom:1px solid rgba(184,150,80,.3);">
          <div style="display:inline-block;letter-spacing:.28em;font-size:11px;text-transform:uppercase;color:#876c33;font-family:Arial,sans-serif;">— {$siteName} Admin Console —</div>
          <h1 style="font-family:'Georgia',serif;font-weight:500;font-size:28px;letter-spacing:-.01em;color:#1a1511;margin:14px 0 6px;">Your new password is ready</h1>
          <p style="margin:0;font-size:14px;color:#5a4e3f;">Hello {$adminName}, we've reset your admin password as you requested.</p>
        </td></tr>
        <tr><td style="padding:28px 40px 20px;text-align:center;">
          <div style="display:inline-block;padding:18px 28px;background:#1f3d2b;color:#f1ebda;border-radius:12px;box-shadow:inset 0 1px 0 rgba(255,255,255,.07), 0 10px 30px -10px rgba(31,61,43,.5);">
            <div style="font-family:Arial,sans-serif;font-size:10px;letter-spacing:.3em;text-transform:uppercase;color:#b89650;margin-bottom:8px;">Temporary password</div>
            <div style="font-family:'Courier New',monospace;font-size:22px;letter-spacing:3px;color:#f1ebda;">{$newPassword}</div>
          </div>
          <p style="margin:22px 0 0;font-size:14px;color:#5a4e3f;line-height:1.6;">Use it to sign in, then change it immediately from the admin console (<em>Avatar → Change password</em>). This email is the only copy of this password.</p>
        </td></tr>
        <tr><td align="center" style="padding:0 40px 32px;">
          <a href="{$loginUrl}" style="display:inline-block;background:#1a1511;color:#f1ebda;text-decoration:none;padding:12px 24px;border-radius:999px;font-family:Arial,sans-serif;font-size:14px;font-weight:600;letter-spacing:.02em;">Sign in to admin &nbsp;↗</a>
        </td></tr>
        <tr><td style="padding:18px 40px;background:#ebe1cb;border-top:1px solid rgba(184,150,80,.25);text-align:center;font-family:Arial,sans-serif;font-size:12px;color:#5a4e3f;">
          If you didn't request this reset, please contact the site owner at once.
        </td></tr>
      </table>
      <p style="margin:16px 0 0;font-family:Arial,sans-serif;font-size:11px;letter-spacing:.2em;text-transform:uppercase;color:#876c33;">&copy; {$siteName}</p>
    </td></tr>
  </table>
</body>
</html>
HTML;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email_in = trim($_POST['email'] ?? '');

  if ($email_in === '' || !filter_var($email_in, FILTER_VALIDATE_EMAIL)) {
    $err = 'Please enter a valid email address.';
  } else {
    $stmt = mysqli_prepare($conn, 'SELECT id, username, name FROM admins WHERE email = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $email_in);
    mysqli_stmt_execute($stmt);
    $admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$admin) {
      $err = 'No admin account is registered with ' . htmlspecialchars($email_in) . '. Double-check the email and try again.';
    } else {
      // Fresh random password: 12 chars, alphanumeric with a few symbols
      $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@#$';
      $newPass  = '';
      for ($i = 0; $i < 12; $i++) $newPass .= $alphabet[random_int(0, strlen($alphabet) - 1)];

      // Update DB first — never leave the admin locked out if email fails
      $hash = password_hash($newPass, PASSWORD_DEFAULT);
      $u    = mysqli_prepare($conn, 'UPDATE admins SET password = ? WHERE id = ?');
      mysqli_stmt_bind_param($u, 'si', $hash, $admin['id']);
      mysqli_stmt_execute($u);

      // Load SMTP settings
      $es = mysqli_query($conn, "SELECT `key`,`value` FROM emailsetting_master WHERE `key` IN ('smtp_name','smtp_email','smtp_app_pass')");
      $smtp = ['smtp_name' => '', 'smtp_email' => '', 'smtp_app_pass' => ''];
      if ($es) while ($r = mysqli_fetch_assoc($es)) $smtp[$r['key']] = $r['value'] ?? '';

      $siteName = $smtp['smtp_name'] ?: 'Friends Club';
      $loginUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
                . '://' . $_SERVER['HTTP_HOST']
                . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/index.php';
      $adminDisplay = htmlspecialchars($admin['name'] ?: $admin['username']);
      $htmlBody = build_reset_email($siteName, $adminDisplay, htmlspecialchars($newPass), $loginUrl);

      if ($smtp['smtp_email'] && $smtp['smtp_app_pass']) {
        try {
          send_smtp_mail(
            'smtp.gmail.com', 587,
            $smtp['smtp_email'], $smtp['smtp_app_pass'],
            $smtp['smtp_email'], $siteName,
            $email_in,
            'Your new ' . $siteName . ' admin password',
            $htmlBody
          );
          $ok = 'We\'ve emailed a new password to ' . htmlspecialchars($email_in) . '. Check your inbox (and spam folder).';
        } catch (Throwable $e) {
          $err = 'Password was reset, but we couldn\'t send the email: ' . htmlspecialchars($e->getMessage())
               . '. Your new password is shown below — copy it and sign in.';
          $shownPassword = $newPass;
        }
      } else {
        $err = 'Email delivery isn\'t configured yet (fill in Email settings in admin). Your new password is shown below — copy it to sign in.';
        $shownPassword = $newPass;
      }
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset Password — Friends Club Admin</title>
<?php if ($site_logo): $ext = pathinfo($site_logo, PATHINFO_EXTENSION); $mime = $ext==='svg'?'svg+xml':($ext==='jpg'?'jpeg':$ext); ?>
  <link rel="icon" type="image/<?= $mime ?>" href="uploads/logos/<?= htmlspecialchars($site_logo) ?>">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,ital,wght@9..144,0,500;9..144,0,600;9..144,0,800;9..144,1,500;9..144,1,600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/site.css">
<svg xmlns="http://www.w3.org/2000/svg" style="display:none">
  <symbol id="i-arrow-ur" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></symbol>
  <symbol id="i-shield"   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 5v7c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V5z"/></symbol>
  <symbol id="i-mail"     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/></symbol>
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
  body::before{
    content:"";position:fixed;inset:0;pointer-events:none;z-index:0;opacity:.5;
    background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='240' height='240'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/><feColorMatrix values='0 0 0 0 1 0 0 0 0 1 0 0 0 0 1 0 0 0 0.045 0'/></filter><rect width='100%25' height='100%25' filter='url(%23n)'/></svg>");
  }
  .admin-shell{width:100%;max-width:460px;position:relative;z-index:1}

  .admin-brand{display:flex;flex-direction:column;align-items:center;gap:10px;margin:0 0 26px}
  .admin-crest{
    width:56px;height:56px;border-radius:999px;
    background:linear-gradient(145deg,#d6b874,#b89650 55%,#876c33);
    color:#0b1710;display:grid;place-items:center;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.4),inset 0 -2px 4px rgba(0,0,0,.2),0 10px 30px rgba(0,0,0,.4),0 0 0 1px rgba(184,150,80,.35);
  }
  .admin-crest svg{width:26px;height:26px}
  .admin-brand .lockup{font-family:'Fraunces',serif;font-weight:600;font-size:22px;letter-spacing:-.02em;color:var(--paper);margin-top:4px}
  .admin-brand .lockup .dot{color:var(--gold);margin:0 2px}
  .admin-brand .rule{display:flex;align-items:center;gap:10px;font-size:10px;letter-spacing:.32em;text-transform:uppercase;color:var(--gold);font-weight:500}
  .admin-brand .rule::before,.admin-brand .rule::after{content:"";display:block;width:28px;height:1px;background:linear-gradient(90deg, transparent, var(--gold), transparent)}
  .admin-logo{display:block;height:72px;max-height:72px;width:auto;max-width:240px;object-fit:contain;margin:0 0 4px;filter:drop-shadow(0 8px 20px rgba(0,0,0,.4))}

  .admin-shell .form-card{
    background:var(--card);
    border:1px solid rgba(184,150,80,.22);
    box-shadow:0 0 0 1px rgba(184,150,80,.1),0 40px 80px -20px rgba(0,0,0,.55),0 20px 40px -16px rgba(0,0,0,.4);
    padding:40px 36px 32px;
  }
  .admin-shell .form-card h2{display:flex;align-items:center;justify-content:center;gap:14px;margin-bottom:4px}
  .admin-shell .form-card h2::before,.admin-shell .form-card h2::after{content:"";display:block;width:28px;height:1px;background:var(--gold);opacity:.5}
  .admin-sub{text-align:center;font-size:12.5px;color:var(--ink-2);margin:0 0 24px}

  .new-pass-box{
    margin:14px 0 0;padding:16px 18px;border-radius:12px;
    background:#1a1511;color:var(--paper);text-align:center;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.07), 0 0 0 1px rgba(184,150,80,.25);
  }
  .new-pass-box .l{font-size:10px;letter-spacing:.3em;text-transform:uppercase;color:var(--gold);margin:0 0 8px}
  .new-pass-box .p{font-family:ui-monospace,'SF Mono','Menlo','Consolas',monospace;font-size:20px;letter-spacing:3px;color:var(--paper);margin:0}

  .admin-foot{display:flex;align-items:center;justify-content:center;gap:10px;margin-top:22px;font-size:11.5px;letter-spacing:.22em;text-transform:uppercase;color:rgba(184,150,80,.6)}
  .admin-foot a{color:var(--gold);text-decoration:none;border-bottom:1px solid rgba(184,150,80,.3)}
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

    <form class="form-card centered" method="post" action="forgot.php" novalidate>
      <h2>Reset password</h2>
      <p class="admin-sub">Enter your admin email — we'll send a new password</p>

      <?php if ($ok): ?><div class="notice ok"><?= $ok ?></div><?php endif; ?>
      <?php if ($err): ?><div class="notice err"><?= $err ?></div><?php endif; ?>

      <?php if ($shownPassword): ?>
        <div class="new-pass-box">
          <p class="l">New password</p>
          <p class="p"><?= htmlspecialchars($shownPassword) ?></p>
        </div>
      <?php endif; ?>

      <div class="form-grid">
        <div class="form-row full">
          <label for="email">Admin email</label>
          <div class="field">
            <input type="email" id="email" name="email" required autocomplete="email"
                   placeholder="you@example.com" value="<?= htmlspecialchars($email_in) ?>">
          </div>
        </div>
      </div>

      <button type="submit" class="btn-solid form-submit">
        <svg width="14" height="14"><use href="#i-mail"/></svg>
        <span>Send new password</span>
      </button>

      <div class="form-foot">Remembered it? <a href="index.php">Back to login</a>.</div>
    </form>

    <div class="admin-foot">
      <a href="index.php">Return to sign in</a>
    </div>
  </div>
</body>
</html>

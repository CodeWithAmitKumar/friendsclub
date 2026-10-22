<?php
$title = 'Sign Up — Friends Club';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/_mailer.php';

// Ensure members table exists (idempotent).
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS members (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     VARCHAR(12) NOT NULL UNIQUE,
  referral_id VARCHAR(12) NULL,
  name        VARCHAR(100) NOT NULL,
  email       VARCHAR(120) NOT NULL UNIQUE,
  country     VARCHAR(60) NOT NULL,
  isd         VARCHAR(10) NOT NULL,
  mobile      VARCHAR(20) NOT NULL,
  password    VARCHAR(255) NOT NULL,
  t_password  VARCHAR(255) NULL,
  wallet      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status      VARCHAR(20) NOT NULL DEFAULT 'active',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (referral_id), INDEX (email), INDEX (mobile)
) ENGINE=InnoDB");
// Add t_password column for installs that pre-dated it (MariaDB IF NOT EXISTS syntax).
@mysqli_query($conn, "ALTER TABLE members ADD COLUMN IF NOT EXISTS t_password VARCHAR(255) NULL AFTER password");
@mysqli_query($conn, "ALTER TABLE members ADD INDEX IF NOT EXISTS idx_mobile (mobile)");

$err = '';
$ok  = false;
$successPayload = null;    // populated on successful signup
$shownTempPass  = '';      // fallback if email send fails

$in  = [
  'referral' => '',
  'name'     => '',
  'email'    => '',
  'country'  => '',
  'isd'      => '+91',
  'mobile'   => '',
  'password' => '',
  'terms'    => false,
];

function generate_temp_password(): string {
  // Format: lowercase letter + 5 digits, e.g. c12346
  return chr(random_int(97, 122)) . str_pad((string)random_int(0, 99999), 5, '0', STR_PAD_LEFT);
}

/**
 * Random 6-digit user ID with FC prefix, e.g. FC482917.
 * Not sequential so IDs can't be guessed by incrementing.
 * Loops with uniqueness check in case of collision.
 */
function generate_user_id(mysqli $conn): string {
  $check = mysqli_prepare($conn, 'SELECT 1 FROM members WHERE user_id = ? LIMIT 1');
  for ($tries = 0; $tries < 40; $tries++) {
    $uid = 'FC' . random_int(100000, 999999);
    mysqli_stmt_bind_param($check, 's', $uid);
    mysqli_stmt_execute($check);
    if (!mysqli_fetch_assoc(mysqli_stmt_get_result($check))) return $uid;
  }
  throw new RuntimeException('Could not allocate a unique user ID — too many collisions.');
}

function member_welcome_email(string $siteName, string $name, string $userId, string $txnPass, string $loginUrl, string $referrerName = ''): string {
  $byLine = $referrerName
    ? '<p style="margin:6px 0 0;font-size:13.5px;color:#5a4e3f;">You were welcomed by <strong style="color:#1a1511;">' . htmlspecialchars($referrerName) . '</strong></p>'
    : '';
  return <<<HTML
<!doctype html>
<html>
<body style="margin:0;padding:0;background:#f1ebda;font-family:Georgia,'Times New Roman',serif;color:#1a1511;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1ebda;padding:40px 20px;">
    <tr><td align="center">
      <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;background:#faf3dd;border:1px solid #d9c993;border-radius:14px;overflow:hidden;box-shadow:0 20px 50px -20px rgba(15,22,18,.3);">
        <tr><td style="padding:36px 40px 18px;text-align:center;border-bottom:1px solid rgba(184,150,80,.3);">
          <div style="display:inline-block;letter-spacing:.28em;font-size:11px;text-transform:uppercase;color:#876c33;font-family:Arial,sans-serif;">— Welcome to {$siteName} —</div>
          <h1 style="font-family:Georgia,serif;font-weight:500;font-size:28px;letter-spacing:-.01em;color:#1a1511;margin:14px 0 6px;">Hello {$name}</h1>
          <p style="margin:0;font-size:14.5px;color:#5a4e3f;line-height:1.6;">Your membership is live. Sign in with the login password you chose; keep the transaction password below safe &mdash; you'll need it to authorise transactions inside the member area.</p>
          {$byLine}
        </td></tr>
        <tr><td style="padding:26px 40px 18px;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td style="padding:0 8px 10px 0;width:50%;vertical-align:top;">
                <div style="padding:14px 16px;background:#f1ebda;border:1px solid rgba(184,150,80,.3);border-radius:10px;text-align:center;">
                  <div style="font-family:Arial,sans-serif;font-size:10px;letter-spacing:.3em;text-transform:uppercase;color:#876c33;">User ID</div>
                  <div style="font-family:'Courier New',monospace;font-size:20px;letter-spacing:2px;color:#1f3d2b;font-weight:700;margin-top:6px;">{$userId}</div>
                </div>
              </td>
              <td style="padding:0 0 10px 8px;width:50%;vertical-align:top;">
                <div style="padding:14px 16px;background:#1a1511;border-radius:10px;text-align:center;box-shadow:inset 0 1px 0 rgba(255,255,255,.07);">
                  <div style="font-family:Arial,sans-serif;font-size:10px;letter-spacing:.3em;text-transform:uppercase;color:#b89650;">Transaction password</div>
                  <div style="font-family:'Courier New',monospace;font-size:20px;letter-spacing:3px;color:#f1ebda;margin-top:6px;">{$txnPass}</div>
                </div>
              </td>
            </tr>
          </table>
          <p style="margin:16px 0 0;font-size:13.5px;color:#5a4e3f;line-height:1.6;text-align:center;">Keep your transaction password private &mdash; it protects actions like withdrawals and transfers in the member area.</p>
        </td></tr>
        <tr><td style="padding:0 40px 22px;text-align:center;">
          <div style="display:inline-block;padding:10px 16px;background:rgba(184,150,80,.15);border:1px dashed rgba(184,150,80,.5);border-radius:999px;color:#876c33;font-family:Arial,sans-serif;font-size:12.5px;letter-spacing:.08em;">
            <strong style="color:#1a1511;">\$1.00</strong> starter credit added to your wallet
          </div>
        </td></tr>
        <tr><td align="center" style="padding:0 40px 32px;">
          <a href="{$loginUrl}" style="display:inline-block;background:#1a1511;color:#f1ebda;text-decoration:none;padding:12px 24px;border-radius:999px;font-family:Arial,sans-serif;font-size:14px;font-weight:600;letter-spacing:.02em;">Sign in now &nbsp;&rarr;</a>
        </td></tr>
        <tr><td style="padding:18px 40px;background:#ebe1cb;border-top:1px solid rgba(184,150,80,.25);text-align:center;font-family:Arial,sans-serif;font-size:12px;color:#5a4e3f;">
          You're receiving this because you signed up at {$siteName}.
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
  foreach (['referral','name','email','country','isd','mobile','password'] as $k) {
    $in[$k] = trim($_POST[$k] ?? '');
  }
  $in['terms'] = !empty($_POST['terms']);

  $referrerName = '';

  if ($in['name'] === '')                                             $err = 'Please enter your full name.';
  elseif (!filter_var($in['email'], FILTER_VALIDATE_EMAIL))           $err = 'That email doesn\'t look right.';
  elseif ($in['country'] === '')                                      $err = 'Pick your country.';
  elseif (!preg_match('/^\d{6,15}$/', $in['mobile']))                 $err = 'Mobile number should be 6–15 digits.';
  elseif (strlen($in['password']) < 8)                                $err = 'Password needs at least 8 characters.';
  elseif (!$in['terms'])                                              $err = 'Please accept the terms to continue.';

  // Referral validation (optional field)
  if (!$err && $in['referral'] !== '') {
    $ref = strtoupper($in['referral']);
    if (!preg_match('/^FC\d{3,10}$/', $ref)) {
      $err = 'The referral ID should look like FC000001.';
    } else {
      $s = mysqli_prepare($conn, 'SELECT name FROM members WHERE user_id = ? AND status = "active" LIMIT 1');
      mysqli_stmt_bind_param($s, 's', $ref);
      mysqli_stmt_execute($s);
      $r = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
      if (!$r) {
        $err = 'That referral ID doesn\'t match any active member.';
      } else {
        $referrerName = $r['name'];
        $in['referral'] = $ref;
      }
    }
  }

  // Email uniqueness
  if (!$err) {
    $s = mysqli_prepare($conn, 'SELECT id FROM members WHERE email = ? LIMIT 1');
    mysqli_stmt_bind_param($s, 's', $in['email']);
    mysqli_stmt_execute($s);
    if (mysqli_fetch_assoc(mysqli_stmt_get_result($s))) {
      $err = 'That email is already registered. Try logging in instead.';
    }
  }

  // Mobile uniqueness (compare full ISD+number so +1-555 and +91-555 are distinct)
  if (!$err) {
    $s = mysqli_prepare($conn, 'SELECT id FROM members WHERE isd = ? AND mobile = ? LIMIT 1');
    mysqli_stmt_bind_param($s, 'ss', $in['isd'], $in['mobile']);
    mysqli_stmt_execute($s);
    if (mysqli_fetch_assoc(mysqli_stmt_get_result($s))) {
      $err = 'That mobile number is already registered.';
    }
  }

  if (!$err) {
    try {
      $userId       = generate_user_id($conn);
      $txnPass      = generate_temp_password();                     // transaction password (c12346)
      $loginHash    = password_hash($in['password'], PASSWORD_DEFAULT);
      $txnHash      = password_hash($txnPass, PASSWORD_DEFAULT);
      $referralCol  = $in['referral'] !== '' ? $in['referral'] : null;
      $wallet       = 1.00;

      $ins = mysqli_prepare($conn,
        'INSERT INTO members (user_id, referral_id, name, email, country, isd, mobile, password, t_password, wallet)
         VALUES (?,?,?,?,?,?,?,?,?,?)');
      mysqli_stmt_bind_param($ins, 'sssssssssd',
        $userId, $referralCol, $in['name'], $in['email'], $in['country'], $in['isd'], $in['mobile'], $loginHash, $txnHash, $wallet);
      $inserted = mysqli_stmt_execute($ins);
    } catch (Throwable $e) {
      $err = $e->getMessage();
      $inserted = false;
    }

    if (!empty($err) || !$inserted) {
      if (empty($err)) $err = 'Could not create account: ' . mysqli_error($conn);
    } else {
      $ok = true;
      $successPayload = ['name' => $in['name'], 'userId' => $userId, 'txnPass' => $txnPass, 'referrer' => $referrerName];

      // Build + send email
      $loginUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
                . '://' . $_SERVER['HTTP_HOST']
                . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/login.php';
      $cfg      = smtp_settings($conn);
      $siteName = $cfg['smtp_name'] ?: 'Friends Club';
      $html     = member_welcome_email(
        $siteName,
        htmlspecialchars($in['name']),
        $userId,
        htmlspecialchars($txnPass),
        $loginUrl,
        $referrerName
      );

      $mailErr = null;
      $sent    = send_mail_via_settings($conn, $in['email'], 'Welcome to ' . $siteName . ' — your member details', $html, $mailErr);
      if (!$sent) {
        // Account exists; show details on screen so user isn't locked out of transactions.
        $shownTempPass = $txnPass;
      }
    }
  }
}

include __DIR__ . '/_header.php';
?>

<main>
  <section class="page narrow">
      <form class="form-card centered" method="post" action="signup.php" novalidate>
        <div class="form-brand">
          <?php if (!empty($site_logo)): ?>
            <img class="brand-logo" src="admin/uploads/logos/<?= htmlspecialchars($site_logo) ?>" alt="FriendsClub">
          <?php else: ?>
            <span class="mark">FriendsClub</span>
          <?php endif; ?>
          <span class="tag">New member sign up</span>
        </div>
        <h2>Create your account</h2>

        <?php if ($ok && $successPayload): ?>
          <?php if ($shownTempPass): ?>
            <div class="notice err">
              Account created, but we couldn't send the welcome email. Please save these:
              <br>Member ID: <strong style="font-family:ui-monospace,monospace"><?= htmlspecialchars($successPayload['userId']) ?></strong>
              &middot; Transaction password: <strong style="font-family:ui-monospace,monospace;letter-spacing:2px"><?= htmlspecialchars($shownTempPass) ?></strong>
            </div>
          <?php else: ?>
            <div class="notice ok">You've signed up successfully — check your email for your member ID and transaction password.</div>
          <?php endif; ?>
        <?php elseif ($err): ?>
          <div class="notice err"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <div class="form-grid">
          <div class="form-row full">
            <label for="referral">Referral ID <span class="opt">optional</span></label>
            <div class="field">
              <input type="text" id="referral" name="referral" placeholder="e.g. FC000003"
                     value="<?= htmlspecialchars($in['referral']) ?>" autocomplete="off"
                     style="font-family:ui-monospace,monospace;letter-spacing:.03em;text-transform:uppercase">
            </div>
            <div id="refStatus" class="ref-status" aria-live="polite"></div>
          </div>

          <div class="form-row full">
            <label for="name">Full name</label>
            <div class="field">
              <input type="text" id="name" name="name" required autocomplete="name" placeholder="Your full name" value="<?= htmlspecialchars($in['name']) ?>">
            </div>
          </div>

          <div class="form-row">
            <label for="email">Email address</label>
            <div class="field">
              <input type="email" id="email" name="email" required autocomplete="email" placeholder="you@example.com" value="<?= htmlspecialchars($in['email']) ?>">
            </div>
          </div>

          <div class="form-row">
            <label for="country">Country</label>
            <div class="field">
              <select id="country" name="country" required>
                <option value="" disabled <?= $in['country']==='' ? 'selected':'' ?>>Select your country</option>
                <?php
                $countries = ['India','United States','United Kingdom','Canada','Australia','United Arab Emirates','Singapore','Germany','France','Netherlands','Japan','New Zealand','South Africa','Nepal','Sri Lanka','Bangladesh','Pakistan','Other'];
                foreach ($countries as $c): ?>
                  <option value="<?= htmlspecialchars($c) ?>" <?= $in['country']===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-row full">
            <label for="mobile">Mobile number</label>
            <div class="field">
              <select class="isd" name="isd" aria-label="Country code">
                <?php
                $isdCodes = ['+91','+1','+44','+61','+971','+65','+49','+33','+31','+81','+64','+27','+977','+94','+880','+92'];
                foreach ($isdCodes as $code): ?>
                  <option value="<?= $code ?>" <?= $in['isd']===$code?'selected':'' ?>><?= $code ?></option>
                <?php endforeach; ?>
              </select>
              <input type="tel" id="mobile" name="mobile" required inputmode="numeric" pattern="\d{6,15}"
                     autocomplete="tel-national" placeholder="Mobile number" value="<?= htmlspecialchars($in['mobile']) ?>">
            </div>
          </div>

          <div class="form-row full">
            <label for="password">Password</label>
            <div class="field">
              <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" placeholder="Minimum 8 characters">
              <button type="button" class="pw-toggle" aria-label="Show password"
                onclick="var i=document.getElementById('password');var o=this.querySelector('svg use');var h=i.type==='password';i.type=h?'text':'password';o.setAttribute('href',h?'#i-eye-off':'#i-eye');">
                <svg><use href="#i-eye"/></svg>
              </button>
            </div>
          </div>
        </div>

        <label class="form-check">
          <input type="checkbox" id="terms" name="terms" <?= $in['terms']?'checked':'' ?>>
          <span class="box"><svg><use href="#i-check"/></svg></span>
          <span>I agree to the <a href="#" onclick="document.getElementById('termsModal').classList.add('open');document.body.style.overflow='hidden';return false;">Terms &amp; Conditions</a>.</span>
        </label>

        <button type="submit" class="btn-solid form-submit">
          <span>Create account</span>
          <svg width="14" height="14"><use href="#i-arrow-ur"/></svg>
        </button>

        <div class="form-foot">Already a member? <a href="login.php">Log in instead</a>.</div>
      </form>
  </section>
</main>

<div class="tc-modal" id="termsModal" role="dialog" aria-modal="true" aria-label="Terms and Conditions"
     onclick="if(event.target===this){this.classList.remove('open');document.body.style.overflow='';}">
  <div class="tc-box">
    <div class="tc-head">
      <div>
        <div class="eyebrow">Please read</div>
        <h3>Terms &amp; Conditions</h3>
      </div>
      <button type="button" class="tc-close" aria-label="Close"
        onclick="document.getElementById('termsModal').classList.remove('open');document.body.style.overflow='';">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="tc-body">
      <?php if (!empty($terms_content)): ?>
        <?= $terms_content /* admin-trusted HTML */ ?>
      <?php else: ?>
        <p style="color:var(--ink-2)">The Terms &amp; Conditions haven't been set yet. Please check back shortly.</p>
      <?php endif; ?>
    </div>
    <div class="tc-foot">
      <button type="button" class="btn-solid"
        onclick="document.getElementById('terms').checked=true;document.getElementById('termsModal').classList.remove('open');document.body.style.overflow='';">
        <span>I understand</span>
        <svg width="14" height="14"><use href="#i-arrow-ur"/></svg>
      </button>
    </div>
  </div>
</div>
<script>
  // Esc closes terms modal
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') {
      var m = document.getElementById('termsModal');
      if (m && m.classList.contains('open')) {
        m.classList.remove('open');
        document.body.style.overflow = '';
      }
    }
  });

  // Live referral lookup — debounced on input + on blur
  (function(){
    var input = document.getElementById('referral');
    var status = document.getElementById('refStatus');
    if (!input || !status) return;
    var timer = null, lastQuery = '';

    function render(state, text){
      status.className = 'ref-status ' + state;
      status.innerHTML = text;
    }
    function check(){
      var v = input.value.trim().toUpperCase();
      if (v === '') { render('', ''); return; }
      if (!/^FC\d{3,10}$/.test(v)) { render('bad', 'Format should be FC followed by digits.'); return; }
      if (v === lastQuery) return;
      lastQuery = v;
      render('loading', 'Checking…');
      fetch('check-referral.php?id=' + encodeURIComponent(v))
        .then(function(r){ return r.json(); })
        .then(function(j){
          if (j && j.ok) {
            render('good', 'Referred by <strong>' + j.name.replace(/[<>]/g,'') + '</strong> ✓');
          } else {
            render('bad', 'No active member found with that ID.');
          }
        })
        .catch(function(){ render('', ''); });
    }
    input.addEventListener('input', function(){
      clearTimeout(timer); timer = setTimeout(check, 420);
    });
    input.addEventListener('blur', check);
    if (input.value.trim()) check();
  })();
</script>

<?php include __DIR__ . '/_footer.php'; ?>

<?php
$title = 'Sign Up — Friends Club';

// Minimal validation; no DB wiring yet.
$err = '';
$ok  = false;
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach (['referral','name','email','country','isd','mobile','password'] as $k) {
    $in[$k] = trim($_POST[$k] ?? '');
  }
  $in['terms'] = !empty($_POST['terms']);

  if ($in['name'] === '')                                             $err = 'Please enter your full name.';
  elseif (!filter_var($in['email'], FILTER_VALIDATE_EMAIL))           $err = 'That email doesn\'t look right.';
  elseif ($in['country'] === '')                                      $err = 'Pick your country.';
  elseif (!preg_match('/^\d{6,15}$/', $in['mobile']))                 $err = 'Mobile number should be 6–15 digits.';
  elseif (strlen($in['password']) < 8)                                $err = 'Password needs at least 8 characters.';
  elseif (!$in['terms'])                                              $err = 'Please accept the terms to continue.';
  else                                                                $ok  = true;
}

$countries = [
  'India','United States','United Kingdom','Canada','Australia','United Arab Emirates',
  'Singapore','Germany','France','Netherlands','Japan','New Zealand','South Africa',
  'Nepal','Sri Lanka','Bangladesh','Pakistan','Other',
];
$isdCodes = ['+91','+1','+44','+61','+971','+65','+49','+33','+31','+81','+64','+27','+977','+94','+880','+92'];

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

        <?php if ($ok): ?>
          <div class="notice ok">Thanks, <?= htmlspecialchars($in['name']) ?> — your account is ready. Check your email to confirm.</div>
        <?php elseif ($err): ?>
          <div class="notice err"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <div class="form-grid">
          <div class="form-row full">
            <label for="referral">Referral ID <span class="opt">optional</span></label>
            <div class="field">
              <input type="text" id="referral" name="referral" placeholder="e.g. FC-23FR8" value="<?= htmlspecialchars($in['referral']) ?>">
            </div>
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
                <?php foreach ($countries as $c): ?>
                  <option value="<?= htmlspecialchars($c) ?>" <?= $in['country']===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-row full">
            <label for="mobile">Mobile number</label>
            <div class="field">
              <select class="isd" name="isd" aria-label="Country code">
                <?php foreach ($isdCodes as $code): ?>
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
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') {
      var m = document.getElementById('termsModal');
      if (m && m.classList.contains('open')) {
        m.classList.remove('open');
        document.body.style.overflow = '';
      }
    }
  });
</script>

<?php include __DIR__ . '/_footer.php'; ?>

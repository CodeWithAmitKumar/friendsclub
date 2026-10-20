<?php
$title = 'Login — Friends Club';

$err = '';
$ok  = false;
$in  = ['userid' => '', 'password' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $in['userid']   = trim($_POST['userid']   ?? '');
  $in['password'] = trim($_POST['password'] ?? '');

  if ($in['userid'] === '')               $err = 'Please enter your User ID.';
  elseif ($in['password'] === '')         $err = 'Please enter your password.';
  else                                    $ok  = true;
}

include __DIR__ . '/_header.php';
?>

<main>
  <section class="page narrow">
      <form class="form-card centered" method="post" action="login.php" novalidate>
        <div class="form-brand">
          <?php if (!empty($site_logo)): ?>
            <img class="brand-logo" src="admin/uploads/logos/<?= htmlspecialchars($site_logo) ?>" alt="FriendsClub">
          <?php else: ?>
            <span class="mark">FriendsClub</span>
          <?php endif; ?>
          <span class="tag">Member login</span>
        </div>
        <h2>Welcome back</h2>

        <?php if ($ok): ?>
          <div class="notice ok">Signed in as <?= htmlspecialchars($in['userid']) ?>.</div>
        <?php elseif ($err): ?>
          <div class="notice err"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <div class="form-grid">
          <div class="form-row full">
            <label for="userid">User ID</label>
            <div class="field">
              <input type="text" id="userid" name="userid" required autocomplete="username"
                     placeholder="Your member ID or email" value="<?= htmlspecialchars($in['userid']) ?>">
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
            <input type="checkbox" name="remember" <?= !empty($_POST['remember'])?'checked':'' ?>>
            <span class="box"><svg><use href="#i-check"/></svg></span>
            <span>Remember me</span>
          </label>
          <a class="link-quiet" href="forgot.php">Forgot password?</a>
        </div>

        <button type="submit" class="btn-solid form-submit">
          <span>Sign in</span>
          <svg width="14" height="14"><use href="#i-arrow-ur"/></svg>
        </button>

        <div class="form-foot">New here? <a href="signup.php">Create an account</a>.</div>
      </form>
  </section>
</main>

<?php include __DIR__ . '/_footer.php'; ?>

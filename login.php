<?php
$title = 'Login — Friends Club';
require_once __DIR__ . '/config.php';
session_start();

// Already logged in? Jump to dashboard.
if (!empty($_SESSION['member_id'])) {
  header('Location: member/dashboard.php');
  exit;
}

$err = '';
$in  = ['userid' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $in['userid'] = trim($_POST['userid']  ?? '');
  $password     = $_POST['password']     ?? '';
  $remember     = !empty($_POST['remember']);

  if ($in['userid'] === '' || $password === '') {
    $err = 'Please enter both your User ID and password.';
  } else {
    $s = mysqli_prepare($conn, 'SELECT id, user_id, name, email, password, status FROM members WHERE user_id = ? OR email = ? LIMIT 1');
    $u = strtoupper($in['userid']); // allow lowercase typing of FC prefix
    mysqli_stmt_bind_param($s, 'ss', $u, $in['userid']);
    mysqli_stmt_execute($s);
    $m = mysqli_fetch_assoc(mysqli_stmt_get_result($s));

    if (!$m) {
      $err = 'No member account found with that User ID or email.';
    } elseif ($m['status'] !== 'active') {
      $err = 'Your account is inactive. Please contact support.';
    } elseif (!password_verify($password, $m['password'])) {
      $err = 'The password doesn\'t match our records.';
    } else {
      // Success
      $_SESSION['member_id']      = (int)$m['id'];
      $_SESSION['member_user_id'] = $m['user_id'];
      $_SESSION['member_name']    = $m['name'];
      if ($remember) {
        // Extend session cookie to 30 days
        $p = session_get_cookie_params();
        setcookie(session_name(), session_id(), time() + 60*60*24*30, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
      }
      header('Location: member/dashboard.php');
      exit;
    }
  }
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

        <?php if ($err): ?>
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

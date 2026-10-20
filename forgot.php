<?php
$title = 'Forgot Password — Friends Club';

$err = '';
$ok  = false;
$in  = ['userid' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $in['userid'] = trim($_POST['userid'] ?? '');
  if ($in['userid'] === '') $err = 'Please enter your User ID.';
  else                      $ok  = true;
}

include __DIR__ . '/_header.php';
?>

<main>
  <section class="page narrow">
      <form class="form-card centered" method="post" action="forgot.php" novalidate>
        <div class="form-brand">
          <?php if (!empty($site_logo)): ?>
            <img class="brand-logo" src="admin/uploads/logos/<?= htmlspecialchars($site_logo) ?>" alt="FriendsClub">
          <?php else: ?>
            <span class="mark">FriendsClub</span>
          <?php endif; ?>
          <span class="tag">Reset your password</span>
        </div>
        <h2>Forgot your password?</h2>

        <?php if ($ok): ?>
          <div class="notice ok">If an account exists for <?= htmlspecialchars($in['userid']) ?>, a reset link is on its way.</div>
        <?php elseif ($err): ?>
          <div class="notice err"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <div class="form-grid">
          <div class="form-row full">
            <label for="userid">Enter your User ID</label>
            <div class="field">
              <input type="text" id="userid" name="userid" required autocomplete="username"
                     placeholder="Your member ID or email" value="<?= htmlspecialchars($in['userid']) ?>">
            </div>
          </div>
        </div>

        <button type="submit" class="btn-solid form-submit">
          <span>Send reset link</span>
          <svg width="14" height="14"><use href="#i-arrow-ur"/></svg>
        </button>

        <div class="form-foot">Remembered it? <a href="login.php">Back to login</a>.</div>
      </form>
  </section>
</main>

<?php include __DIR__ . '/_footer.php'; ?>

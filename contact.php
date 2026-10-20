<?php
$title = 'Contact Us — Friends Club';

$err = '';
$ok  = false;
$in  = ['name'=>'','email'=>'','subject'=>'','message'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach (['name','email','subject','message'] as $k) {
    $in[$k] = trim($_POST[$k] ?? '');
  }
  if ($in['name'] === '')                                        $err = 'Please tell us your name.';
  elseif (!filter_var($in['email'], FILTER_VALIDATE_EMAIL))      $err = 'That email doesn\'t look right.';
  elseif ($in['subject'] === '')                                 $err = 'Please add a subject.';
  elseif (strlen($in['message']) < 10)                           $err = 'A line or two more, please — at least 10 characters.';
  else                                                           $ok  = true;
}

include __DIR__ . '/_header.php';
?>

<main>
  <section class="page narrow">
    <form class="form-card centered" method="post" action="contact.php" novalidate>
      <div class="form-brand">
        <?php if (!empty($site_logo)): ?>
          <img class="brand-logo" src="admin/uploads/logos/<?= htmlspecialchars($site_logo) ?>" alt="FriendsClub">
        <?php else: ?>
          <span class="mark">FriendsClub</span>
        <?php endif; ?>
        <span class="tag">Get in touch</span>
      </div>
      <h2>Contact us</h2>

      <?php if ($ok): ?>
        <div class="notice ok">Thanks, <?= htmlspecialchars($in['name']) ?> — we'll reply within one working day.</div>
      <?php elseif ($err): ?>
        <div class="notice err"><?= htmlspecialchars($err) ?></div>
      <?php endif; ?>

      <div class="form-grid">
        <div class="form-row">
          <label for="name">Your name</label>
          <div class="field">
            <input type="text" id="name" name="name" required autocomplete="name"
                   placeholder="Full name" value="<?= htmlspecialchars($in['name']) ?>">
          </div>
        </div>

        <div class="form-row">
          <label for="email">Email address</label>
          <div class="field">
            <input type="email" id="email" name="email" required autocomplete="email"
                   placeholder="you@example.com" value="<?= htmlspecialchars($in['email']) ?>">
          </div>
        </div>

        <div class="form-row full">
          <label for="subject">Subject</label>
          <div class="field">
            <input type="text" id="subject" name="subject" required
                   placeholder="What's it about?" value="<?= htmlspecialchars($in['subject']) ?>">
          </div>
        </div>

        <div class="form-row full">
          <label for="message">Message</label>
          <div class="field">
            <textarea id="message" name="message" required rows="6"
                      placeholder="Tell us what's on your mind…"><?= htmlspecialchars($in['message']) ?></textarea>
          </div>
        </div>
      </div>

      <button type="submit" class="btn-solid form-submit">
        <span>Send message</span>
        <svg width="14" height="14"><use href="#i-arrow-ur"/></svg>
      </button>

    </form>
  </section>
</main>

<?php include __DIR__ . '/_footer.php'; ?>

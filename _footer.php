<footer>
  <div class="foot">
    <div>
      <a class="brand foot-brand" href="index.php">
        <?php if (!empty($site_logo)): ?>
          <img class="brand-logo foot-logo" src="admin/uploads/logos/<?= htmlspecialchars($site_logo) ?>" alt="FriendsClub">
        <?php else: ?>
          <span class="mono">FriendsClub</span>
        <?php endif; ?>
      </a>
      <?php
        $socialIcons = [
          'instagram' => ['Instagram','i-ig'],
          'facebook'  => ['Facebook', 'i-fb'],
          'twitter'   => ['Twitter',  'i-tw'],
          'youtube'   => ['YouTube',  'i-yt'],
        ];
        $visibleSocials = array_filter($socials ?? []);
      ?>
      <?php if ($visibleSocials): ?>
      <div class="socials" aria-label="Social links">
        <?php foreach ($socialIcons as $key => [$label, $icon]): if (empty($socials[$key])) continue; ?>
          <a href="<?= htmlspecialchars($socials[$key]) ?>" target="_blank" rel="noopener" aria-label="<?= $label ?>">
            <svg><use href="#<?= $icon ?>"/></svg>
          </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <div>
      <h4>Explore</h4>
      <a href="index.php">Home</a>
      <a href="plans.php">Plan Details</a>
      <a href="payment.php">Payment Status</a>
    </div>
    <div>
      <h4>Account</h4>
      <a href="login.php">Login</a>
      <a href="signup.php">Sign Up</a>
      <a href="contact.php">Contact Us</a>
    </div>
    <div>
      <h4>Find us</h4>
      <?php if (!empty($contact['email'])): ?>
        <a href="mailto:<?= htmlspecialchars($contact['email']) ?>"><?= htmlspecialchars($contact['email']) ?></a>
      <?php else: ?>
        <p>hello@friendsclub.local</p>
      <?php endif; ?>
      <?php if (!empty($contact['mobile'])): ?>
        <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $contact['mobile'])) ?>"><?= htmlspecialchars($contact['mobile']) ?></a>
      <?php else: ?>
        <p>+91 00000 00000</p>
      <?php endif; ?>
    </div>
  </div>
  <div class="legal">
    <span>&copy; <?= date('Y') ?> Friends Club &middot; All rights reserved.</span>
  </div>
</footer>

<script>
(function(){
  var track = document.getElementById('mqTrack');
  if (!track) return;
  var run = track.firstElementChild;
  var x = 0;
  function tick(){
    x -= 1.2;
    var w = run.offsetWidth;
    if (w && -x >= w) x += w;
    track.style.transform = 'translate3d(' + x + 'px,0,0)';
    requestAnimationFrame(tick);
  }
  (document.fonts && document.fonts.ready || Promise.resolve()).then(function(){
    requestAnimationFrame(tick);
  });
})();
</script>
</body>
</html>

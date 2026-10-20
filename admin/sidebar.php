<?php
$current = basename($_SERVER['PHP_SELF'] ?? '');
$items = [
  ['dashboard.php',       'Dashboard',        'i-dash'],
  ['members.php',         'Members',          'i-members'],
  ['website-settings.php','Website settings', 'i-settings'],
  ['plans.php',           'Plans',            'i-plan'],
  ['payments.php',        'Payments',         'i-pay'],
  ['messages.php',        'Messages',         'i-chat'],
];
?>
<div class="sidebar-scrim" id="sidebarScrim" onclick="document.querySelector('.sidebar').classList.remove('open');this.classList.remove('open');"></div>

<aside class="sidebar">
  <div class="sidebar-head">
    <?php if (!empty($site_logo)): ?>
      <img class="sidebar-logo" src="uploads/logos/<?= htmlspecialchars($site_logo) ?>" alt="FriendsClub">
    <?php else: ?>
      <div class="sidebar-crest"><svg><use href="#i-shield"/></svg></div>
      <div class="name">Friends<span class="dot">·</span>Club</div>
    <?php endif; ?>
  </div>

  <nav class="sidebar-nav" aria-label="Admin">
    <?php foreach ($items as [$href,$label,$icon]):
      $active = $current === $href ? ' active' : ''; ?>
      <a class="nav-item<?= $active ?>" href="<?= htmlspecialchars($href) ?>">
        <svg><use href="#<?= $icon ?>"/></svg><span><?= htmlspecialchars($label) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
</aside>

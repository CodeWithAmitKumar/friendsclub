<?php
$nav = [
  'Home'           => 'index.php',
  'Plan Details'   => 'plans.php',
  'Payment Status' => 'payment.php',
  'Contact Us'     => 'contact.php',
];
$title = $title ?? 'Friends Club — Together For a Better Community';

// Pull dynamic chrome bits from DB (silently fail if DB/table missing).
$site_logo = '';
$socials   = ['instagram' => '', 'facebook' => '', 'twitter' => '', 'youtube' => ''];
$contact   = ['email' => '', 'mobile' => ''];
$content   = ['heading' => '', 'description' => '', 'image' => '', 'marquee' => ''];
$terms_content = '';
if (@include_once __DIR__ . '/config.php') {
  $wsRes = @mysqli_query($conn, "SELECT `key`,`value` FROM websetting_master WHERE `key` IN
    ('site_logo','social_instagram','social_facebook','social_twitter','social_youtube',
     'content_heading','content_description','content_image','content_marquee','terms_content')");
  if ($wsRes) {
    while ($row = mysqli_fetch_assoc($wsRes)) {
      switch ($row['key']) {
        case 'site_logo':           $site_logo = $row['value']; break;
        case 'social_instagram':    $socials['instagram'] = $row['value']; break;
        case 'social_facebook':     $socials['facebook']  = $row['value']; break;
        case 'social_twitter':      $socials['twitter']   = $row['value']; break;
        case 'social_youtube':      $socials['youtube']   = $row['value']; break;
        case 'content_heading':     $content['heading']     = $row['value']; break;
        case 'content_description': $content['description'] = $row['value']; break;
        case 'content_image':       $content['image']       = $row['value']; break;
        case 'content_marquee':     $content['marquee']     = $row['value']; break;
        case 'terms_content':       $terms_content          = $row['value']; break;
      }
    }
  }
  $cRes = @mysqli_query($conn, "SELECT email, mobile FROM admins ORDER BY id LIMIT 1");
  if ($cRes && ($cr = mysqli_fetch_assoc($cRes))) {
    $contact['email']  = $cr['email']  ?? '';
    $contact['mobile'] = $cr['mobile'] ?? '';
  }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($title) ?></title>
<?php if ($site_logo): ?>
  <link rel="icon" type="image/<?= pathinfo($site_logo, PATHINFO_EXTENSION) === 'svg' ? 'svg+xml' : (pathinfo($site_logo, PATHINFO_EXTENSION) === 'jpg' ? 'jpeg' : pathinfo($site_logo, PATHINFO_EXTENSION)) ?>" href="admin/uploads/logos/<?= htmlspecialchars($site_logo) ?>">
  <link rel="apple-touch-icon" href="admin/uploads/logos/<?= htmlspecialchars($site_logo) ?>">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,ital,wght@9..144,0,500;9..144,0,600;9..144,0,800;9..144,1,500;9..144,1,600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<svg xmlns="http://www.w3.org/2000/svg" style="display:none">
  <symbol id="i-arrow-ur" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></symbol>
  <symbol id="i-arrow-r"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></symbol>
  <symbol id="i-msg"      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></symbol>
  <symbol id="i-sparkles" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/></symbol>
  <symbol id="i-users"    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></symbol>
  <symbol id="i-menu"     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"/></symbol>
  <symbol id="i-close"    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></symbol>
  <symbol id="i-home"     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></symbol>
  <symbol id="i-plan"     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 10h8M8 14h5"/></symbol>
  <symbol id="i-pay"      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></symbol>
  <symbol id="i-mail"     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/></symbol>
  <symbol id="i-login"    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5M15 12H3"/></symbol>
  <symbol id="i-eye"      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></symbol>
  <symbol id="i-eye-off"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17.9 17.9A10.4 10.4 0 0 1 12 19c-6.5 0-10-7-10-7a18.4 18.4 0 0 1 4.1-5.1"/><path d="M9.9 4.2A10.9 10.9 0 0 1 12 4c6.5 0 10 7 10 7a18.6 18.6 0 0 1-2.4 3.4"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/><path d="M2 2l20 20"/></symbol>
  <symbol id="i-check"    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m4 12 6 6 10-14"/></symbol>
  <symbol id="i-clock"    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></symbol>
  <symbol id="i-x"        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></symbol>
  <symbol id="i-search"   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></symbol>
  <symbol id="i-ig" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></symbol>
  <symbol id="i-fb" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></symbol>
  <symbol id="i-tw" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4l7.5 10L4.5 20H7l5.6-5.3L17 20h3l-7.9-10.6L19.5 4H17l-5.3 5L8 4z"/></symbol>
  <symbol id="i-yt" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></symbol>
</svg>
<link rel="stylesheet" href="assets/site.css">
</head>
<body>

<header class="site-header">
  <nav class="nav" aria-label="Primary">
    <a class="brand" href="index.php">
      <?php if ($site_logo): ?>
        <img class="brand-logo" src="admin/uploads/logos/<?= htmlspecialchars($site_logo) ?>" alt="FriendsClub">
      <?php else: ?>
        <span class="mono">FriendsClub</span>
      <?php endif; ?>
    </a>
    <ul class="links" id="menu">
      <li class="drawer-head">
        <?php if ($site_logo): ?>
          <img class="brand-logo" src="admin/uploads/logos/<?= htmlspecialchars($site_logo) ?>" alt="FriendsClub">
        <?php else: ?>
          <span class="mono">FriendsClub</span>
        <?php endif; ?>
        <button type="button" class="drawer-close" aria-label="Close menu"
          onclick="toggleMenu()">
          <svg width="16" height="16"><use href="#i-close"/></svg>
        </button>
      </li>
      <li class="drawer-eyebrow">Navigate</li>
      <?php foreach ($nav as $label => $href): ?>
        <li>
          <a href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars($label) ?></a>
        </li>
      <?php endforeach; ?>
      <li class="drawer-foot">
        <div class="drawer-section">Account</div>
        <a class="btn-ghost drawer-btn" href="login.php">
          <svg width="14" height="14"><use href="#i-login"/></svg>
          <span>Login</span>
        </a>
        <a class="btn-solid drawer-btn" href="signup.php">
          <span>Sign Up</span>
          <svg width="14" height="14"><use href="#i-arrow-ur"/></svg>
        </a>
        <div class="drawer-contact-line">
          <a href="mailto:hello@friendsclub.local">hello@friendsclub.local</a>
          <span class="dot">·</span>
          <span>+91 00000 00000</span>
        </div>
      </li>
    </ul>
    <div class="actions">
      <a class="btn-ghost" href="login.php">
        <svg width="14" height="14"><use href="#i-login"/></svg>
        <span>Login</span>
      </a>
      <a class="btn-solid" href="signup.php">
        <span>Sign Up</span>
        <svg width="14" height="14"><use href="#i-arrow-ur"/></svg>
      </a>
    </div>
    <button class="burger" id="burger" aria-label="Menu"
      onclick="toggleMenu()">
      <svg width="22" height="22"><use href="#i-menu"/></svg>
    </button>
  </nav>
</header>

<div id="scrim" class="scrim" onclick="toggleMenu()"></div>
<script>
  function toggleMenu(){
    ['menu','scrim','burger'].forEach(id=>document.getElementById(id).classList.toggle('open'));
    document.body.classList.toggle('no-scroll');
  }
</script>

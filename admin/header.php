<?php
// Admin chrome header. Expects session_start() + an admin session set by the including page.
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

require_once __DIR__ . '/../config.php';

// Pull site logo (used for favicon in admin as well).
$site_logo = '';
$_lr = @mysqli_query($conn, "SELECT `value` FROM websetting_master WHERE `key` = 'site_logo' LIMIT 1");
if ($_lr && ($_lx = @mysqli_fetch_assoc($_lr))) $site_logo = $_lx['value'];

// Load current admin row for avatar + display name
$_ah = mysqli_prepare($conn, 'SELECT username, name, email, avatar FROM admins WHERE id = ?');
mysqli_stmt_bind_param($_ah, 'i', $_SESSION['admin_id']);
mysqli_stmt_execute($_ah);
$admin = mysqli_fetch_assoc(mysqli_stmt_get_result($_ah));
if (!$admin) { session_destroy(); header('Location: index.php'); exit; }

$admin_username = $admin['username'];
$admin_display  = $admin['name'] ?: $admin['username'];
$admin_email    = $admin['email'] ?: 'admin@friendsclub.local';
$admin_avatar   = $admin['avatar'] ?? '';
$admin_initial  = strtoupper(substr($admin_display, 0, 1));

$page_title ??= 'Dashboard';
$page_crumb ??= 'Admin console';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($page_title) ?> — Friends Club Admin</title>
<?php if ($site_logo): $ext = pathinfo($site_logo, PATHINFO_EXTENSION); $mime = $ext==='svg'?'svg+xml':($ext==='jpg'?'jpeg':$ext); ?>
  <link rel="icon" type="image/<?= $mime ?>" href="uploads/logos/<?= htmlspecialchars($site_logo) ?>">
  <link rel="apple-touch-icon" href="uploads/logos/<?= htmlspecialchars($site_logo) ?>">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,ital,wght@9..144,0,500;9..144,0,600;9..144,0,800;9..144,1,500;9..144,1,600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/site.css">
<link rel="stylesheet" href="../assets/admin.css">
<svg xmlns="http://www.w3.org/2000/svg" style="display:none">
  <symbol id="i-shield"   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 5v7c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V5z"/></symbol>
  <symbol id="i-dash"     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></symbol>
  <symbol id="i-members"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></symbol>
  <symbol id="i-plan"     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 10h8M8 14h5"/></symbol>
  <symbol id="i-pay"      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></symbol>
  <symbol id="i-chat"     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></symbol>
  <symbol id="i-settings" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06A2 2 0 1 1 7.04 4.36l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></symbol>
  <symbol id="i-user"     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></symbol>
  <symbol id="i-mail"     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/></symbol>
  <symbol id="i-logout"   viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/></symbol>
  <symbol id="i-key"      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.7 12.3 10.3-10.3M15 7l3 3M13 9l3 3"/></symbol>
  <symbol id="i-eye"      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></symbol>
  <symbol id="i-eye-off"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17.9 17.9A10.4 10.4 0 0 1 12 19c-6.5 0-10-7-10-7a18.4 18.4 0 0 1 4.1-5.1"/><path d="M9.9 4.2A10.9 10.9 0 0 1 12 4c6.5 0 10 7 10 7a18.6 18.6 0 0 1-2.4 3.4"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/><path d="M2 2l20 20"/></symbol>
  <symbol id="i-layout-top"    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18"/></symbol>
  <symbol id="i-layout-bottom" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 15h18"/></symbol>
  <symbol id="i-doc"           viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></symbol>
  <symbol id="i-ig" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></symbol>
  <symbol id="i-fb" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></symbol>
  <symbol id="i-tw" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4l7.5 10L4.5 20H7l5.6-5.3L17 20h3l-7.9-10.6L19.5 4H17l-5.3 5L8 4z"/></symbol>
  <symbol id="i-yt" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></symbol>
  <symbol id="i-arrow-ur" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></symbol>
  <symbol id="i-chevron"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></symbol>
  <symbol id="i-menu"     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"/></symbol>
</svg>
</head>
<body class="admin">
<div class="admin-layout">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <div class="admin-main">
    <div class="topbar">
      <button type="button" class="sidebar-toggle" aria-label="Menu"
        onclick="document.querySelector('.sidebar').classList.toggle('open');document.getElementById('sidebarScrim').classList.toggle('open');">
        <svg><use href="#i-menu"/></svg>
      </button>

      <div class="topbar-right">
        <button type="button" class="avatar-btn ava-only" id="avaBtn" aria-haspopup="true" aria-expanded="false"
          onclick="var m=document.getElementById('avaMenu');var o=m.classList.toggle('open');this.setAttribute('aria-expanded',o);">
          <span class="ava">
            <?php if ($admin_avatar): ?>
              <img src="uploads/avatars/<?= htmlspecialchars($admin_avatar) ?>" alt="">
            <?php else: ?>
              <?= htmlspecialchars($admin_initial) ?>
            <?php endif; ?>
          </span>
        </button>
        <div class="avatar-menu" id="avaMenu" role="menu">
          <div class="head">
            <p class="n"><?= htmlspecialchars($admin_display) ?></p>
            <p class="e"><?= htmlspecialchars($admin_email) ?></p>
          </div>
          <a href="profile.php" role="menuitem">
            <svg><use href="#i-user"/></svg><span>Update profile</span>
          </a>
          <a href="email-settings.php" role="menuitem">
            <svg><use href="#i-mail"/></svg><span>Email settings</span>
          </a>
          <a href="change-password.php" role="menuitem">
            <svg><use href="#i-key"/></svg><span>Change password</span>
          </a>
          <div class="sep"></div>
          <a href="logout.php" class="danger" role="menuitem">
            <svg><use href="#i-logout"/></svg><span>Log out</span>
          </a>
        </div>
      </div>
    </div>

    <main class="admin-content">
<script>
  // close avatar menu when clicking outside
  document.addEventListener('click', function(e){
    var btn = document.getElementById('avaBtn');
    var menu = document.getElementById('avaMenu');
    if (!btn || !menu) return;
    if (!btn.contains(e.target) && !menu.contains(e.target)) {
      menu.classList.remove('open');
      btn.setAttribute('aria-expanded','false');
    }
  });
</script>

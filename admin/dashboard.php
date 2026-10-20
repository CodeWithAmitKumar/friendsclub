<?php
$page_title = 'Dashboard';
$page_crumb = 'Overview';
include __DIR__ . '/header.php';

$hour = (int)date('G');
$greet = $hour < 5  ? 'Still up'
       : ($hour < 12 ? 'Good morning'
       : ($hour < 17 ? 'Good afternoon'
       : ($hour < 21 ? 'Good evening' : 'Good evening')));
?>

<style>
  .welcome{
    display:flex;align-items:center;gap:22px;padding:32px 34px;
    background:var(--card);color:var(--ink);
    border:1px solid rgba(184,150,80,.22);border-radius:14px;
    box-shadow:0 0 0 1px rgba(184,150,80,.08), 0 24px 48px -20px rgba(0,0,0,.4);
    position:relative;overflow:hidden;
  }
  .welcome::before{
    content:"";position:absolute;top:-80px;right:-80px;width:260px;height:260px;border-radius:999px;
    background:radial-gradient(closest-side, rgba(184,150,80,.22), transparent);
    pointer-events:none;
  }
  .welcome .ava-lg{
    width:72px;height:72px;border-radius:999px;flex-shrink:0;overflow:hidden;
    background:linear-gradient(145deg,#d6b874,#b89650 55%,#876c33);
    color:#0b1710;display:grid;place-items:center;
    font-family:'Fraunces',serif;font-weight:700;font-size:28px;letter-spacing:-.02em;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.4),inset 0 -2px 4px rgba(0,0,0,.18),0 0 0 1px rgba(184,150,80,.35);
  }
  .welcome .ava-lg img{width:100%;height:100%;object-fit:cover;display:block}
  .welcome .ltitle{
    font-size:10.5px;letter-spacing:.26em;text-transform:uppercase;color:var(--gold-deep);margin:0 0 6px;font-weight:600;
  }
  .welcome h2{
    font-family:'Fraunces',serif;font-weight:500;font-size:28px;line-height:1.1;letter-spacing:-.015em;color:var(--ink);margin:0 0 6px;
  }
  .welcome h2 em{color:var(--brand);font-style:italic}
  .welcome .msg{color:var(--ink-2);font-size:14.5px;margin:0;max-width:52ch}

  @media (max-width:560px){
    .welcome{flex-direction:column;align-items:flex-start;padding:22px;gap:16px}
    .welcome h2{font-size:22px}
  }
</style>

<div class="welcome">
  <div class="ava-lg">
    <?php if ($admin_avatar): ?>
      <img src="uploads/avatars/<?= htmlspecialchars($admin_avatar) ?>" alt="">
    <?php else: ?>
      <?= htmlspecialchars($admin_initial) ?>
    <?php endif; ?>
  </div>
  <div>
    <p class="ltitle"><?= date('l, j F Y') ?></p>
    <h2><?= htmlspecialchars($greet) ?>, <em><?= htmlspecialchars($admin_display) ?></em>.</h2>
    <p class="msg">Welcome back to the console. The club's quiet so far today — have a cup of tea and we'll take it slow.</p>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>

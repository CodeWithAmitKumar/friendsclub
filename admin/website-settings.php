<?php
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

$page_title = 'Website settings';
$page_crumb = 'Manage';
include __DIR__ . '/header.php';

$cards = [
  [
    'href'  => 'header-settings.php',
    'icon'  => 'i-layout-top',
    'title' => 'Header settings',
  ],
  [
    'href'  => 'footer-settings.php',
    'icon'  => 'i-layout-bottom',
    'title' => 'Footer settings',
  ],
  [
    'href'  => 'content-settings.php',
    'icon'  => 'i-doc',
    'title' => 'Content & pages',
  ],
  [
    'href'  => 'terms-settings.php',
    'icon'  => 'i-key',
    'title' => 'Terms & Conditions',
  ],
];
?>

<style>
  .ws-head{display:flex;align-items:center;justify-content:center;gap:14px;margin:0 0 6px}
  .ws-head::before,.ws-head::after{content:"";display:block;width:28px;height:1px;background:var(--gold);opacity:.5}
  .ws-head h3{font-family:'Fraunces',serif;font-weight:500;font-size:22px;letter-spacing:-.015em;color:var(--paper);margin:0}
  .ws-sub{text-align:center;font-size:12.5px;color:rgba(241,235,218,.65);margin:0 0 26px}

  .ws-grid{
    display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px;
  }
  .ws-card{
    display:flex;flex-direction:column;gap:14px;
    background:var(--card);color:var(--ink);text-decoration:none;
    border:1px solid rgba(184,150,80,.22);border-radius:14px;
    padding:28px 26px;
    box-shadow:0 0 0 1px rgba(184,150,80,.08), 0 24px 48px -20px rgba(0,0,0,.4);
    transition:transform .28s ease, box-shadow .28s ease, border-color .28s ease;
    position:relative;overflow:hidden;
  }
  .ws-card::after{
    content:"";position:absolute;top:-60px;right:-60px;width:160px;height:160px;border-radius:999px;
    background:radial-gradient(closest-side, rgba(184,150,80,.14), transparent);
    pointer-events:none;transition:transform .4s ease;
  }
  .ws-card:hover{
    transform:translateY(-4px);
    border-color:rgba(184,150,80,.4);
    box-shadow:0 0 0 1px rgba(184,150,80,.18), 0 36px 60px -20px rgba(0,0,0,.55);
  }
  .ws-card:hover::after{transform:scale(1.2)}
  .ws-card .ic{
    width:48px;height:48px;border-radius:12px;
    background:linear-gradient(145deg,#d6b874,#b89650 55%,#876c33);
    color:#0b1710;display:grid;place-items:center;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.4),inset 0 -2px 3px rgba(0,0,0,.18),0 0 0 1px rgba(184,150,80,.35);
  }
  .ws-card .ic svg{width:22px;height:22px}
  .ws-card h4{
    font-family:'Fraunces',serif;font-weight:500;font-size:19px;letter-spacing:-.01em;color:var(--ink);margin:0;
  }
  .ws-card p{font-size:14px;color:var(--ink-2);margin:0;line-height:1.5;flex:1}
  .ws-card .go{
    display:inline-flex;align-items:center;gap:8px;
    font-size:12px;font-weight:600;letter-spacing:.18em;text-transform:uppercase;
    color:var(--brand);margin-top:4px;
  }
  .ws-card .go svg{width:14px;height:14px;transition:transform .25s ease}
  .ws-card:hover .go svg{transform:translate(3px,-3px)}

  @media (max-width:960px){.ws-grid{grid-template-columns:repeat(2,1fr)}}
  @media (max-width:620px){.ws-grid{grid-template-columns:1fr}}
</style>

<div class="ws-head"><h3>Website settings</h3></div>
<p class="ws-sub">Choose what to manage</p>

<div class="ws-grid">
  <?php foreach ($cards as $c): ?>
    <a class="ws-card" href="<?= htmlspecialchars($c['href']) ?>">
      <div class="ic"><svg><use href="#<?= $c['icon'] ?>"/></svg></div>
      <h4><?= htmlspecialchars($c['title']) ?></h4>
      <!-- <p><?= htmlspecialchars($c['desc']) ?></p> -->
      <span class="go">Manage <svg><use href="#i-arrow-ur"/></svg></span>
    </a>
  <?php endforeach; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>

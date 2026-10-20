<?php
$title = 'Friends Club — Together For a Better Community';
include __DIR__ . '/_header.php';

// Fallbacks when the admin hasn't filled content yet
$heading = $content['heading'] !== ''
  ? $content['heading']
  : 'Build what a neighbourhood <span class="ital">can be</span>, together.';
$desc = $content['description'] !== ''
  ? $content['description']
  : '<p class="lede">Friends Club is a quietly ambitious community programme — simple plans, honest payments, and the people who make a place feel like home.</p>';
$heroImg = $content['image'] !== ''
  ? 'admin/uploads/content/' . $content['image']
  : 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=1200&q=80';
$marquee = $content['marquee'] !== '' ? $content['marquee'] : 'Together For a Better Community';
?>

<main>
  <section class="hero">
    <div class="hero-copy">
      <div class="kicker"><span class="dash"></span> A members' circle, since day one</div>
      <h1><?= $heading /* HTML — admin trusted */ ?></h1>
      <?php
        // If description came from CKEditor it already has <p> tags; otherwise it's raw text we wrap.
        $descOut = (strpos($desc, '<') !== false) ? $desc : '<p class="lede">' . htmlspecialchars($desc) . '</p>';
        // Ensure the ".lede" class is on at least one paragraph for consistent styling.
        if (strpos($descOut, 'lede') === false) {
          $descOut = preg_replace('/<p(\s|>)/i', '<p class="lede"$1', $descOut, 1);
        }
        echo '<div class="hero-desc">' . $descOut . '</div>';
      ?>
      <div class="cta-row">
        <a class="btn btn-primary" href="login.php">Login <svg><use href="#i-arrow-r"/></svg></a>
      </div>
      <div class="hero-meta">
        <div class="stat"><div class="n">2,400+</div><div class="l">Members</div></div>
        <div class="stat"><div class="n">11 yrs</div><div class="l">Running strong</div></div>
      </div>
    </div>

    <div class="hero-media">
      <div class="badge"><svg width="14" height="14"><use href="#i-sparkles"/></svg> New season open</div>
      <div class="frame">
        <img src="<?= htmlspecialchars($heroImg) ?>"
             alt="<?= htmlspecialchars(strip_tags($heading)) ?>"
             onerror="this.onerror=null;this.src='https://picsum.photos/1000/1200';">
      </div>
      <div class="card-float">
        <div class="ic"><svg width="18" height="18"><use href="#i-users"/></svg></div>
        <div class="t"><b>Monthly meet-up</b>Open to all members this Saturday</div>
      </div>
    </div>
  </section>

  <div class="marquee" aria-label="<?= htmlspecialchars($marquee) ?>">
    <div class="marquee-track" id="mqTrack">
      <div class="marquee-run">
        <span><?= htmlspecialchars($marquee) ?></span><span class="star">✦</span>
        <span><?= htmlspecialchars($marquee) ?></span><span class="star">✦</span>
        <span><?= htmlspecialchars($marquee) ?></span><span class="star">✦</span>
      </div>
      <div class="marquee-run" aria-hidden="true">
        <span><?= htmlspecialchars($marquee) ?></span><span class="star">✦</span>
        <span><?= htmlspecialchars($marquee) ?></span><span class="star">✦</span>
        <span><?= htmlspecialchars($marquee) ?></span><span class="star">✦</span>
      </div>
    </div>
  </div>
</main>

<?php include __DIR__ . '/_footer.php'; ?>

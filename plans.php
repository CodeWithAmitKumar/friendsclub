<?php
$title = 'Plan Details — Friends Club';

$plans = [
  [
    'name'     => 'Visitor',
    'what'     => 'For the curious — read along, no commitment.',
    'price'    => '0',
    'unit'     => '/ month',
    'cta'      => 'Create a free account',
    'featured' => false,
    'features' => [
      'Monthly newsletter',
      'Read all public updates',
      'See upcoming meet-ups',
      'One guest pass per year',
    ],
  ],
  [
    'name'     => 'Member',
    'what'     => 'The real deal — join a chapter, show up.',
    'price'    => '499',
    'unit'     => '/ month',
    'cta'      => 'Become a member',
    'featured' => true,
    'features' => [
      'Everything in Visitor',
      'Join or start a local chapter',
      'All member meet-ups',
      'Vote on chapter decisions',
      'Member-only directory',
    ],
  ],
  [
    'name'     => 'Patron',
    'what'     => 'Keep the lights on for everyone.',
    'price'    => '1,499',
    'unit'     => '/ month',
    'cta'      => 'Become a patron',
    'featured' => false,
    'features' => [
      'Everything in Member',
      '6 guest passes a year',
      'Annual patrons retreat',
      'Early access to new chapters',
      'Named in our thank-you page',
    ],
  ],
];

include __DIR__ . '/_header.php';
?>

<main>
  <section class="page">
    <div class="page-intro">
      <div class="kicker"><span class="dash"></span> Plans &amp; pricing</div>
      <h1>Membership that <span class="ital">fits</span>.</h1>
      <p class="lede">Three plans, no tricks. Switch any time, cancel in a click. All prices in INR, billed monthly.</p>
    </div>

    <div class="plans">
      <?php foreach ($plans as $p): ?>
        <article class="plan<?= $p['featured'] ? ' featured' : '' ?>">
          <?php if ($p['featured']): ?><span class="pop">Most popular</span><?php endif; ?>
          <h3><?= htmlspecialchars($p['name']) ?></h3>
          <p class="what"><?= htmlspecialchars($p['what']) ?></p>
          <div class="price">
            <span class="n">&#8377;<?= htmlspecialchars($p['price']) ?></span>
            <span class="u"><?= htmlspecialchars($p['unit']) ?></span>
          </div>
          <ul class="feat">
            <?php foreach ($p['features'] as $f): ?>
              <li><svg width="15" height="15"><use href="#i-check"/></svg><span><?= htmlspecialchars($f) ?></span></li>
            <?php endforeach; ?>
          </ul>
          <a class="btn-solid plan-cta" href="signup.php">
            <span><?= htmlspecialchars($p['cta']) ?></span>
            <svg width="14" height="14"><use href="#i-arrow-ur"/></svg>
          </a>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</main>

<?php include __DIR__ . '/_footer.php'; ?>

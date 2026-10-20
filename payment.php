<?php
$title = 'Payment Status — Friends Club';

// Demo lookup table — swap for a real DB query when billing is wired.
$demoPayments = [
  'FC-PAY-2026-10-15-ABCD' => [
    'status'  => 'paid',
    'amount'  => '499',
    'date'    => '15 Oct 2026, 09:12 IST',
    'method'  => 'UPI · friendsclub@okaxis',
    'plan'    => 'Member — monthly',
    'name'    => 'Amit Patel',
    'ref'     => 'TXN8743HGF',
  ],
  'FC-PAY-2026-10-14-WXYZ' => [
    'status'  => 'pending',
    'amount'  => '1,499',
    'date'    => '14 Oct 2026, 18:40 IST',
    'method'  => 'Net banking · HDFC',
    'plan'    => 'Patron — monthly',
    'name'    => 'R. Iyer',
    'ref'     => 'TXN8740PND',
  ],
  'FC-PAY-2026-06-15-MNOP' => [
    'status'  => 'failed',
    'amount'  => '499',
    'date'    => '15 Jun 2026, 09:14 IST',
    'method'  => 'UPI · friendsclub@okaxis',
    'plan'    => 'Member — monthly',
    'name'    => 'Amit Patel',
    'ref'     => 'TXN7291ERR',
  ],
];

$statusMap = [
  'paid'    => ['label' => 'Payment received', 'msg' => 'Your payment is confirmed. A receipt is on its way.', 'tone' => 'ok',   'icon' => 'i-check'],
  'pending' => ['label' => 'Awaiting bank',    'msg' => 'The bank is still processing. This usually clears within a few minutes.', 'tone' => 'warn', 'icon' => 'i-clock'],
  'failed'  => ['label' => 'Payment failed',   'msg' => 'The bank declined this payment. Please try again or use a different method.', 'tone' => 'err',  'icon' => 'i-x'],
];

$queryId = '';
$result  = null;
$notFound = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['id'])) {
  $queryId = trim($_POST['payment_id'] ?? $_GET['id'] ?? '');
  if ($queryId !== '') {
    if (isset($demoPayments[strtoupper($queryId)])) {
      $result = $demoPayments[strtoupper($queryId)];
    } else {
      $notFound = true;
    }
  }
}

include __DIR__ . '/_header.php';
?>

<main>
  <section class="page narrow">
    <form class="form-card centered" method="post" action="payment.php" novalidate>
      <div class="form-brand">
        <?php if (!empty($site_logo)): ?>
          <img class="brand-logo" src="admin/uploads/logos/<?= htmlspecialchars($site_logo) ?>" alt="FriendsClub">
        <?php else: ?>
          <span class="mark">FriendsClub</span>
        <?php endif; ?>
        <span class="tag">Payment lookup</span>
      </div>
      <h2>Check your payment</h2>

      <?php if ($notFound): ?>
        <div class="notice err">We couldn't find a payment with that ID. Double-check the ID in your confirmation email.</div>
      <?php endif; ?>

      <div class="form-grid">
        <div class="form-row full">
          <label for="payment_id">Payment ID</label>
          <div class="field">
            <input type="text" id="payment_id" name="payment_id" required
                   placeholder="e.g. FC-PAY-2026-10-15-ABCD"
                   value="<?= htmlspecialchars($queryId) ?>"
                   autocomplete="off" spellcheck="false"
                   style="font-family:ui-monospace,'SF Mono','Menlo','Consolas',monospace;letter-spacing:.02em">
          </div>
        </div>
      </div>

      <button type="submit" class="btn-solid form-submit">
        <svg width="14" height="14"><use href="#i-search"/></svg>
        <span>Check status</span>
      </button>

      <div class="form-foot">Can't find your Payment ID? <a href="contact.php">Contact us</a>.</div>
    </form>

    <?php if ($result): $s = $statusMap[$result['status']]; ?>
      <article class="pay-result">
        <div class="pay-banner <?= htmlspecialchars($s['tone']) ?>">
          <div class="ic"><svg><use href="#<?= $s['icon'] ?>"/></svg></div>
          <div>
            <p class="ltitle">Status</p>
            <p class="st"><?= htmlspecialchars($s['label']) ?></p>
            <p class="msg"><?= htmlspecialchars($s['msg']) ?></p>
          </div>
        </div>
        <div class="pay-grid">
          <div class="cell">
            <p class="l">Amount</p>
            <p class="v">&#8377;<?= htmlspecialchars($result['amount']) ?></p>
          </div>
          <div class="cell">
            <p class="l">Date</p>
            <p class="v sm"><?= htmlspecialchars($result['date']) ?></p>
          </div>
          <div class="cell">
            <p class="l">Plan</p>
            <p class="v sm"><?= htmlspecialchars($result['plan']) ?></p>
          </div>
          <div class="cell">
            <p class="l">Method</p>
            <p class="v sm"><?= htmlspecialchars($result['method']) ?></p>
          </div>
        </div>
        <div class="pay-foot">
          <span>Payment ID <span class="mono"><?= htmlspecialchars($queryId) ?></span> &middot; Bank ref <span class="mono"><?= htmlspecialchars($result['ref']) ?></span></span>
          <?php if ($result['status'] === 'paid'): ?>
            <a class="link-quiet" href="#">Download receipt</a>
          <?php elseif ($result['status'] === 'failed'): ?>
            <a class="link-quiet" href="plans.php">Try again</a>
          <?php endif; ?>
        </div>
      </article>
    <?php endif; ?>
  </section>
</main>

<?php include __DIR__ . '/_footer.php'; ?>

<?php
session_start();
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['admin_id'])) { header('Location: index.php'); exit; }

// Actions — PRG pattern so pagination/search survive POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (int)($_POST['id'] ?? 0);
  $action = $_POST['action'] ?? '';
  $msg = '';
  if ($id && $action === 'toggle') {
    mysqli_query($conn, "UPDATE members
                         SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END
                         WHERE id = $id");
    $msg = 'Member status updated.';
  } elseif ($id && $action === 'delete') {
    mysqli_query($conn, "DELETE FROM members WHERE id = $id");
    $msg = 'Member deleted.';
  }
  if ($msg !== '') {
    $qs = [];
    if (!empty($_GET['q']))    $qs['q']    = $_GET['q'];
    if (!empty($_GET['page'])) $qs['page'] = $_GET['page'];
    $qs['msg'] = $msg;
    header('Location: members.php?' . http_build_query($qs));
    exit;
  }
}
$flash = $_GET['msg'] ?? '';

// CSV export — respects the current search filter, ignores pagination
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  $q = trim($_GET['q'] ?? '');
  $where = ''; $params = []; $types = '';
  if ($q !== '') {
    $like = '%' . $q . '%';
    $where  = "WHERE user_id LIKE ? OR name LIKE ? OR email LIKE ? OR mobile LIKE ?";
    $params = [$like, $like, $like, $like];
    $types  = 'ssss';
  }
  $sql = "SELECT user_id, name, email, isd, mobile, country, referral_id, wallet, status, created_at
          FROM members $where ORDER BY id DESC";
  $stmt = mysqli_prepare($conn, $sql);
  if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);

  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=members_' . date('Ymd_Hi') . '.csv');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['SL No', 'User ID', 'Name', 'Email', 'Mobile', 'Country', 'Referred By', 'Wallet ($)', 'Status', 'Joined']);
  $sl = 1;
  while ($r = mysqli_fetch_assoc($res)) {
    fputcsv($out, [
      $sl++,
      $r['user_id'],
      $r['name'],
      $r['email'],
      $r['isd'] . ' ' . $r['mobile'],
      $r['country'],
      $r['referral_id'] ?: '',
      number_format((float)$r['wallet'], 2, '.', ''),
      ucfirst($r['status']),
      date('Y-m-d H:i', strtotime($r['created_at'])),
    ]);
  }
  fclose($out);
  exit;
}

// Search + pagination
$q       = trim($_GET['q'] ?? '');
$perPage = 20;
$page    = max(1, (int)($_GET['page'] ?? 1));

$where = '';
$params = [];
$types  = '';
if ($q !== '') {
  $like   = '%' . $q . '%';
  $where  = "WHERE user_id LIKE ? OR name LIKE ? OR email LIKE ? OR mobile LIKE ?";
  $params = [$like, $like, $like, $like];
  $types  = 'ssss';
}

// Filtered count for pagination
$cntSql = "SELECT COUNT(*) c FROM members $where";
$cntStmt = mysqli_prepare($conn, $cntSql);
if ($params) mysqli_stmt_bind_param($cntStmt, $types, ...$params);
mysqli_stmt_execute($cntStmt);
$totalFiltered = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($cntStmt))['c'] ?? 0);

$totalPages = max(1, (int)ceil($totalFiltered / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

$sql = "SELECT id, user_id, referral_id, name, email, country, isd, mobile, wallet, status, created_at
        FROM members $where
        ORDER BY id DESC
        LIMIT $perPage OFFSET $offset";
$stmt = mysqli_prepare($conn, $sql);
if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];

$totalAll = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM members"))['c'] ?? 0);

// Build base query string for pagination links (preserves search)
function page_url($n, $q) {
  $qs = ['page' => $n];
  if ($q !== '') $qs['q'] = $q;
  return 'members.php?' . http_build_query($qs);
}

$page_title = 'Members';
$page_crumb = 'Manage';
include __DIR__ . '/header.php';
?>

<style>
  .mb-head{
    display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;
    margin-bottom:16px;
  }
  .mb-head h3{display:flex;align-items:center;gap:14px;margin:0;color:var(--paper)}
  .mb-head h3::before,.mb-head h3::after{content:"";display:block;width:28px;height:1px;background:var(--gold);opacity:.5}
  .mb-head .count{
    font-size:11px;letter-spacing:.22em;text-transform:uppercase;color:rgba(184,150,80,.65);
  }

  .mb-tools{
    display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap;
  }
  .mb-search{
    flex:1;min-width:220px;max-width:420px;
    background:var(--card);border:1px solid rgba(184,150,80,.3);border-radius:10px;
    display:flex;align-items:center;gap:0;overflow:hidden;
  }
  .mb-search svg{width:16px;height:16px;color:var(--ink-2);margin:0 4px 0 12px}
  .mb-search input{
    flex:1;background:transparent;border:0;outline:0;color:var(--ink);
    padding:11px 14px 11px 6px;font:inherit;
  }
  .mb-search input::placeholder{color:var(--ink-3)}
  .mb-search button{
    background:var(--ink);color:var(--paper);border:0;padding:11px 16px;
    font:inherit;font-weight:600;font-size:13px;cursor:pointer;
  }
  .mb-search button:hover{background:var(--brand)}
  .mb-tools .clear-link{color:rgba(184,150,80,.75);font-size:12.5px;text-decoration:underline;text-underline-offset:3px}
  .mb-tools .clear-link:hover{color:var(--gold)}
  .mb-export{
    display:inline-flex;align-items:center;gap:8px;margin-left:auto;
    padding:10px 16px;border-radius:10px;background:var(--card);color:var(--ink);
    border:1px solid rgba(184,150,80,.3);text-decoration:none;
    font-size:13px;font-weight:600;letter-spacing:.01em;
    transition:background .2s ease, border-color .2s ease, transform .2s ease;
  }
  .mb-export svg{width:14px;height:14px;color:var(--gold-deep)}
  .mb-export:hover{background:var(--gold);border-color:var(--gold);color:var(--ink);transform:translateY(-1px)}
  .mb-export:hover svg{color:var(--ink)}

  .mb-wrap{
    background:var(--card);color:var(--ink);
    border:1px solid rgba(184,150,80,.22);border-radius:14px;overflow:hidden;
    box-shadow:0 0 0 1px rgba(184,150,80,.08), 0 24px 48px -20px rgba(0,0,0,.4);
  }
  .mb-flash{
    padding:12px 20px;background:rgba(47,63,40,.1);color:var(--forest);
    border-bottom:1px solid rgba(47,63,40,.2);font-size:13px;
  }

  .mb-table{width:100%;border-collapse:collapse;font-size:13.5px}
  .mb-table th,.mb-table td{text-align:left;padding:14px 16px;border-bottom:1px solid rgba(26,21,17,.06);vertical-align:middle}
  .mb-table th{
    font-weight:600;font-size:10.5px;letter-spacing:.18em;text-transform:uppercase;color:var(--ink-2);
    background:var(--paper);position:sticky;top:0;
  }
  .mb-table tbody tr:last-child td{border-bottom:0}
  .mb-table tbody tr:hover td{background:rgba(184,150,80,.05)}
  .mb-table .sl{
    font-family:'Fraunces',serif;font-weight:500;color:var(--ink-2);font-variant-numeric:tabular-nums;
    text-align:center;
  }
  .mb-table .uid{font-family:ui-monospace,'SF Mono','Menlo','Consolas',monospace;color:var(--brand);font-weight:600;letter-spacing:.02em}
  .mb-table .nm{font-family:'Fraunces',serif;font-weight:500;font-size:14.5px;color:var(--ink)}
  .mb-table .em{color:var(--ink-2);font-size:12.5px}
  .mb-table .mob{font-variant-numeric:tabular-nums;color:var(--ink-2);font-size:13px;white-space:nowrap}
  .mb-table .ref{font-family:ui-monospace,monospace;font-size:12.5px;color:var(--ink-3)}
  .mb-table .ref.has{color:var(--brand-ink)}
  .mb-table .wa{font-family:'Fraunces',serif;font-weight:600;color:var(--ink);font-variant-numeric:tabular-nums}
  .mb-table .dt{font-size:12.5px;color:var(--ink-3);white-space:nowrap}

  .mb-actions{display:inline-flex;gap:6px}
  .mb-icon-btn{
    width:32px;height:32px;border-radius:8px;border:1px solid rgba(26,21,17,.14);
    background:transparent;color:var(--ink-2);display:inline-grid;place-items:center;cursor:pointer;
    transition:background .15s ease, color .15s ease, border-color .15s ease, transform .15s ease;
  }
  .mb-icon-btn:hover{background:var(--ink);color:var(--paper);border-color:var(--ink);transform:translateY(-1px)}
  .mb-icon-btn.danger:hover{background:var(--wine);color:var(--paper);border-color:var(--wine)}
  .mb-icon-btn svg{width:14px;height:14px}

  .empty{padding:60px 20px;text-align:center;color:var(--ink-2)}
  .empty .big{font-family:'Fraunces',serif;font-size:20px;color:var(--ink);margin-bottom:4px}

  /* Mobile — card layout */
  @media (max-width:860px){
    .mb-table thead{display:none}
    .mb-table,.mb-table tbody,.mb-table tr,.mb-table td{display:block;width:100%}
    .mb-table tr{padding:14px 18px;border-bottom:1px solid rgba(26,21,17,.08)}
    .mb-table td{padding:4px 0;border:0;display:flex;justify-content:space-between;gap:12px}
    .mb-table td::before{
      content:attr(data-l);
      font-size:10.5px;letter-spacing:.18em;text-transform:uppercase;color:var(--ink-2);
      font-weight:600;flex-shrink:0;
    }
    .mb-table td.full::before{display:none}
  }
</style>

<div class="mb-head">
  <h3>Members</h3>
  <div class="count">
    <?= $totalFiltered ?><?= $q !== '' ? ' / ' . $totalAll : '' ?> on roll
  </div>
</div>

<form class="mb-tools" method="get" action="members.php">
  <div class="mb-search">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
    <input type="search" name="q" placeholder="Search by name, email, mobile or ID" value="<?= htmlspecialchars($q) ?>">
    <button type="submit">Search</button>
  </div>
  <?php if ($q !== ''): ?>
    <a class="clear-link" href="members.php">Clear</a>
  <?php endif; ?>
  <a class="mb-export" href="members.php?export=csv<?= $q !== '' ? '&q=' . urlencode($q) : '' ?>" title="Download as CSV">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
    <span>Export CSV</span>
  </a>
</form>

<div class="mb-wrap">
  <?php if ($flash): ?>
    <div class="mb-flash"><?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>

  <?php if (!$rows): ?>
    <div class="empty">
      <div class="big"><?= $q !== '' ? 'No matches' : 'No members yet' ?></div>
      <div><?= $q !== '' ? 'Try a different search term.' : 'Signups will show up here.' ?></div>
    </div>
  <?php else: ?>
    <table class="mb-table">
      <thead>
        <tr>
          <th style="width:52px">SL</th>
          <th>User ID</th>
          <th>Member</th>
          <th>Mobile</th>
          <th>Country</th>
          <th>Referred by</th>
          <th>Wallet</th>
          <th>Status</th>
          <th>Joined</th>
          <th style="text-align:right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $sl = $offset; foreach ($rows as $r):
          $sl++;
          $isActive = ($r['status'] === 'active');
        ?>
          <tr>
            <td data-l="SL No" class="sl"><?= $sl ?></td>
            <td data-l="User ID" class="uid"><?= htmlspecialchars($r['user_id']) ?></td>
            <td data-l="Member">
              <div class="nm"><?= htmlspecialchars($r['name']) ?></div>
              <div class="em"><?= htmlspecialchars($r['email']) ?></div>
            </td>
            <td data-l="Mobile" class="mob"><?= htmlspecialchars($r['isd'] . ' ' . $r['mobile']) ?></td>
            <td data-l="Country"><?= htmlspecialchars($r['country']) ?></td>
            <td data-l="Referred by" class="ref<?= $r['referral_id'] ? ' has' : '' ?>">
              <?= $r['referral_id'] ? htmlspecialchars($r['referral_id']) : '—' ?>
            </td>
            <td data-l="Wallet" class="wa">&#36;<?= number_format((float)$r['wallet'], 2) ?></td>
            <td data-l="Status">
              <span class="pill <?= $isActive ? 'ok' : 'err' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span>
            </td>
            <td data-l="Joined" class="dt"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
            <td data-l="Actions" style="text-align:right">
              <div class="mb-actions">
                <a class="mb-icon-btn" href="member-edit.php?id=<?= (int)$r['id'] ?>" title="Edit">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                </a>
                <form method="post" style="display:inline">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <input type="hidden" name="action" value="toggle">
                  <button type="submit" class="mb-icon-btn" title="<?= $isActive ? 'Deactivate' : 'Activate' ?>">
                    <?php if ($isActive): ?>
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17.9 17.9A10.4 10.4 0 0 1 12 19c-6.5 0-10-7-10-7a18.4 18.4 0 0 1 4.1-5.1"/><path d="M9.9 4.2A10.9 10.9 0 0 1 12 4c6.5 0 10 7 10 7a18.6 18.6 0 0 1-2.4 3.4"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/><path d="M2 2l20 20"/></svg>
                    <?php else: ?>
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                    <?php endif; ?>
                  </button>
                </form>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($r['name'])) ?> (<?= htmlspecialchars($r['user_id']) ?>)? This cannot be undone.');">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <input type="hidden" name="action" value="delete">
                  <button type="submit" class="mb-icon-btn danger" title="Delete">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
  <nav class="mb-pager" aria-label="Pages">
    <?php
      // Build a trimmed page range (first, around current, last)
      $show = [1, $totalPages];
      for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++) $show[] = $i;
      $show = array_values(array_unique($show));
      sort($show);
    ?>
    <?php if ($page > 1): ?>
      <a class="pg-nav" href="<?= page_url($page - 1, $q) ?>" aria-label="Previous">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        <span>Prev</span>
      </a>
    <?php else: ?>
      <span class="pg-nav disabled" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        <span>Prev</span>
      </span>
    <?php endif; ?>

    <?php $prev = 0; foreach ($show as $n):
      if ($prev && $n - $prev > 1) echo '<span class="pg-dots">…</span>';
      $prev = $n;
      $active = $n === $page;
    ?>
      <?php if ($active): ?>
        <span class="pg-num active"><?= $n ?></span>
      <?php else: ?>
        <a class="pg-num" href="<?= page_url($n, $q) ?>"><?= $n ?></a>
      <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($page < $totalPages): ?>
      <a class="pg-nav" href="<?= page_url($page + 1, $q) ?>" aria-label="Next">
        <span>Next</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
      </a>
    <?php else: ?>
      <span class="pg-nav disabled" aria-hidden="true">
        <span>Next</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
      </span>
    <?php endif; ?>

    <span class="pg-info">Page <?= $page ?> of <?= $totalPages ?> &middot; <?= $totalFiltered ?> total</span>
  </nav>
<?php endif; ?>

<style>
  .mb-pager{
    display:flex;align-items:center;gap:6px;flex-wrap:wrap;
    margin:18px 0 4px;padding:14px 18px;
    background:var(--card);border:1px solid rgba(184,150,80,.22);border-radius:12px;
  }
  .mb-pager .pg-num,
  .mb-pager .pg-nav{
    display:inline-flex;align-items:center;gap:6px;
    min-width:34px;height:34px;padding:0 10px;border-radius:8px;
    text-decoration:none;color:var(--ink);font-size:13px;font-weight:600;letter-spacing:.01em;
    border:1px solid transparent;background:transparent;
    transition:background .15s ease, color .15s ease, border-color .15s ease;
  }
  .mb-pager .pg-num{justify-content:center;font-variant-numeric:tabular-nums}
  .mb-pager .pg-num:hover,
  .mb-pager .pg-nav:hover{background:rgba(184,150,80,.1);border-color:rgba(184,150,80,.3)}
  .mb-pager .pg-num.active{
    background:var(--ink);color:var(--paper);border-color:var(--ink);
    box-shadow:0 2px 6px rgba(26,21,17,.2);
  }
  .mb-pager .pg-nav.disabled{color:var(--ink-3);cursor:not-allowed;opacity:.5}
  .mb-pager .pg-nav.disabled:hover{background:transparent;border-color:transparent}
  .mb-pager .pg-nav svg{width:14px;height:14px}
  .mb-pager .pg-dots{color:var(--ink-3);padding:0 4px}
  .mb-pager .pg-info{
    margin-left:auto;font-size:11.5px;letter-spacing:.18em;text-transform:uppercase;color:var(--ink-2);
  }
  @media (max-width:560px){
    .mb-pager{justify-content:center}
    .mb-pager .pg-info{margin-left:0;width:100%;text-align:center;margin-top:6px}
  }
</style>

<?php include __DIR__ . '/footer.php'; ?>

<?php
// Tiny JSON endpoint: GET /check-referral.php?id=FC000003
// Returns {ok: true, name: "..."} or {ok: false}
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$id = strtoupper(trim($_GET['id'] ?? ''));
if ($id === '' || !preg_match('/^FC\d{3,10}$/', $id)) {
  echo json_encode(['ok' => false]);
  exit;
}

$s = @mysqli_prepare($conn, 'SELECT name FROM members WHERE user_id = ? AND status = "active" LIMIT 1');
if (!$s) { echo json_encode(['ok' => false]); exit; }
mysqli_stmt_bind_param($s, 's', $id);
mysqli_stmt_execute($s);
$row = @mysqli_fetch_assoc(mysqli_stmt_get_result($s));

echo json_encode($row ? ['ok' => true, 'name' => $row['name']] : ['ok' => false]);

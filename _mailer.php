<?php
// Shared SMTP helpers. Used by signup.php, admin/forgot.php, etc.
// Reads SMTP creds from `emailsetting_master` and sends over Gmail-compatible STARTTLS.

function smtp_settings(mysqli $conn): array {
  $cfg = ['smtp_name' => '', 'smtp_email' => '', 'smtp_app_pass' => ''];
  $res = @mysqli_query($conn, "SELECT `key`,`value` FROM emailsetting_master WHERE `key` IN ('smtp_name','smtp_email','smtp_app_pass')");
  if ($res) while ($r = mysqli_fetch_assoc($res)) $cfg[$r['key']] = $r['value'] ?? '';
  return $cfg;
}

/**
 * Minimal SMTP sender over STARTTLS (Gmail app-password compatible).
 * Throws RuntimeException on any failure.
 */
function send_smtp_mail(string $host, int $port, string $user, string $pass,
                       string $from, string $fromName, string $to,
                       string $subject, string $html): void {
  $sock = @fsockopen($host, $port, $errno, $errstr, 15);
  if (!$sock) throw new RuntimeException("SMTP connect failed: $errstr ($errno)");
  stream_set_timeout($sock, 20);

  $expect = function (string $code) use ($sock): string {
    $buf = '';
    while (($line = fgets($sock, 515)) !== false) {
      $buf .= $line;
      if (preg_match('/^\d{3} /', $line)) break;
    }
    if (!str_starts_with($buf, $code)) throw new RuntimeException("SMTP wanted $code, got: " . trim($buf));
    return $buf;
  };
  $send = fn(string $s) => fwrite($sock, $s . "\r\n");

  $expect('220');
  $send('EHLO localhost'); $expect('250');
  $send('STARTTLS');       $expect('220');
  if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
    throw new RuntimeException('STARTTLS handshake failed');
  }
  $send('EHLO localhost'); $expect('250');
  $send('AUTH LOGIN');     $expect('334');
  $send(base64_encode($user)); $expect('334');
  $send(base64_encode($pass)); $expect('235');
  $send('MAIL FROM: <' . $from . '>'); $expect('250');
  $send('RCPT TO: <' . $to . '>');     $expect('250');
  $send('DATA');                       $expect('354');

  $headers = [
    'From: ' . mb_encode_mimeheader($fromName) . ' <' . $from . '>',
    'To: <' . $to . '>',
    'Subject: ' . mb_encode_mimeheader($subject),
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
    'Date: ' . date('r'),
  ];
  $html = preg_replace('/^\./m', '..', $html);
  fwrite($sock, implode("\r\n", $headers) . "\r\n\r\n" . $html . "\r\n.\r\n");
  $expect('250');
  $send('QUIT');
  fclose($sock);
}

/**
 * Convenience: load SMTP settings and send. Returns true on success,
 * sets $error (by reference) on failure. Caller decides fallback.
 */
function send_mail_via_settings(mysqli $conn, string $to, string $subject, string $html, ?string &$error = null): bool {
  $cfg = smtp_settings($conn);
  if ($cfg['smtp_email'] === '' || $cfg['smtp_app_pass'] === '') {
    $error = 'Email delivery is not configured yet.';
    return false;
  }
  try {
    send_smtp_mail(
      'smtp.gmail.com', 587,
      $cfg['smtp_email'], $cfg['smtp_app_pass'],
      $cfg['smtp_email'], $cfg['smtp_name'] ?: 'Friends Club',
      $to, $subject, $html
    );
    return true;
  } catch (Throwable $e) {
    $error = $e->getMessage();
    return false;
  }
}

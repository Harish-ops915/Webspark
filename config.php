<?php
// config.php — Webspark (Mailtrap via PHP mail(), MySQLi utf8mb4)

// Prevent multiple inclusions
if (defined('CONFIG_LOADED')) { return; }
define('CONFIG_LOADED', true);

// Session
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// PHP runtime (dev friendly)
ini_set('memory_limit', '256M');
date_default_timezone_set('Asia/Kolkata');

// Optional security headers (safe for local dev)
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// ================================
// Database connection
// ================================
$host = "localhost";
$username = "root";
$password = "";
$database = "webspark";

$mysqli = @new mysqli($host, $username, $password, $database);
if ($mysqli->connect_errno) {
  error_log("DB connect error: ".$mysqli->connect_error);
  die("Database connection failed.");
}
$mysqli->set_charset("utf8mb4");

// ================================
// App settings
// ================================
define('IS_DEVELOPMENT', true);                 // keep true on localhost
define('SITE_URL', 'http://localhost/Webspark'); // IMPORTANT: case-sensitive folder name
define('SITE_NAME', 'Webspark');

// Email verification flags
define('EMAIL_VERIFICATION_ENABLED', true);
define('REQUIRE_EMAIL_VERIFICATION', true);

// Mail “From”
define('MAIL_FROM_EMAIL', 'no-reply@webspark.local'); // captured by Mailtrap
define('MAIL_FROM_NAME',  'Webspark');

// CAPTCHA (disabled on localhost)
define('CAPTCHA_ENABLED', false);
define('RECAPTCHA_SITE_KEY', '');
define('RECAPTCHA_SECRET_KEY', '');

// ================================
// Utility functions
// ================================
function sanitizeInput($input) {
  return htmlspecialchars(strip_tags(trim((string)$input)), ENT_QUOTES, 'UTF-8');
}

function generateSecureToken($length = 32) {
  // returns 64-hex chars when length=32
  return bin2hex(random_bytes($length));
}

function verifyRecaptcha($response) {
  if (!CAPTCHA_ENABLED) return true;
  if (empty($response)) return false;
  $secret = RECAPTCHA_SECRET_KEY;
  $ch = curl_init("https://www.google.com/recaptcha/api/siteverify");
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
      'secret' => $secret,
      'response' => $response,
      'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT => 10,
  ]);
  $result = curl_exec($ch);
  $http   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($result === false || $http !== 200) return false;
  $json = json_decode($result, true);
  return !empty($json['success']);
}

// ================================
// Email functions (Mailtrap via PHP mail())
// Ensure C:\xampp\php\php.ini has:
//   sendmail_path = "C:\xampp\sendmail\sendmail.exe -t -i"
// And C:\xampp\sendmail\sendmail.ini points to Mailtrap sandbox.
// ================================
function sendEmail($to, $subject, $html, $from_name = MAIL_FROM_NAME, $from_email = MAIL_FROM_EMAIL) {
  // DO NOT short-circuit in development; actually send so Mailtrap captures it.
  $headers = "From: {$from_name} <{$from_email}>\r\n" .
             "Reply-To: {$from_email}\r\n" .
             "MIME-Version: 1.0\r\n" .
             "Content-Type: text/html; charset=UTF-8\r\n";

  $ok = mail($to, $subject, $html, $headers);
  error_log('sendEmail to='.$to.' subject="'. $subject .'" result=' . ($ok ? 'OK' : 'FAIL'));
  return $ok;
}

function sendVerificationEmail($user_id, $email, $username, $verification_code) {
  global $mysqli;

  $verification_link = SITE_URL . "/verify_email.php?code=" . urlencode($verification_code);

  // Simple, robust HTML body
  $message = '
  <html><body style="font-family:Arial,sans-serif">
    <h2>Verify your Webspark email</h2>
    <p>Hello '. htmlspecialchars($username) .',</p>
    <p>Please click the link below to activate your account:</p>
    <p><a href="'. $verification_link .'">Verify Email</a></p>
    <p>If the button doesn’t work, copy this URL:<br>'. $verification_link .'</p>
  </body></html>';

  // Debug: confirm payload is not empty
  error_log('VERIFY email to='.$email.' code='.$verification_code.' link='.$verification_link);

  $result = sendEmail($email, "Verify Your Email - ".SITE_NAME, $message);

  // Optional email log (safe-guarded)
  try {
    if ($stmt = $mysqli->prepare("INSERT INTO email_logs (user_id, email_type, recipient_email, subject, status, created_at) VALUES (?, 'verification', ?, ?, ?, NOW())")) {
      $status = $result ? 'sent' : 'failed';
      $subj   = "Verify Your Email - ".SITE_NAME;
      $stmt->bind_param("isss", $user_id, $email, $subj, $status);
      $stmt->execute();
    }
  } catch (Throwable $e) {
    error_log("Email log error: ".$e->getMessage());
  }

  return $result;
}

// ================================
// Simple session rate limiting
// ================================
function checkRateLimit($action, $limit = 5, $window = 300) {
  if (IS_DEVELOPMENT) { $limit = 20; $window = 60; } // easier on localhost

  $ip  = $_SERVER['REMOTE_ADDR'] ?? 'local';
  $key = $action . '_' . $ip;

  if (!isset($_SESSION['rate_limit'])) $_SESSION['rate_limit'] = [];
  if (!isset($_SESSION['rate_limit'][$key])) $_SESSION['rate_limit'][$key] = [];

  $now = time();
  // keep only recent timestamps
  $_SESSION['rate_limit'][$key] = array_values(array_filter(
    $_SESSION['rate_limit'][$key],
    fn($ts) => ($now - $ts) < $window
  ));

  if (count($_SESSION['rate_limit'][$key]) >= $limit) return false;

  $_SESSION['rate_limit'][$key][] = $now;
  return true;
}

function clearRateLimit($action = null) {
  if (!IS_DEVELOPMENT) return false;
  if (!isset($_SESSION['rate_limit'])) return true;
  if ($action) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'local';
    $key = $action . '_' . $ip;
    unset($_SESSION['rate_limit'][$key]);
  } else {
    unset($_SESSION['rate_limit']);
  }
  return true;
}

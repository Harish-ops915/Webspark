<?php
// registration_success.php — Webspark
require_once __DIR__ . "/config.php";

/*
 Behavior:
  - Requires $_SESSION['registration_success'] with 'email' and 'username' set by register.php on successful send.
  - Provides a Resend button that re-sends verification if email_verified=0.
  - Shows real outcome messages based on sendVerificationEmail return value.
*/

// Ensure session data is present
if (!isset($_SESSION['registration_success']['email'], $_SESSION['registration_success']['username'])) {
  header("Location: register.php");
  exit;
}

$email    = $_SESSION['registration_success']['email'];
$username = $_SESSION['registration_success']['username'];

$resend_msg = "";

// Handle resend
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['resend_email'])) {
  if (!checkRateLimit('resend_verification', 5, 300)) {
    $resend_msg = '<div class="alert alert-danger">Too many resend attempts. Please try again in a few minutes.</div>';
  } else {
    // Fetch current code for this email (still unverified)
    if ($stmt = $mysqli->prepare("SELECT id, username, verification_code FROM users WHERE email=? AND email_verified=0 LIMIT 1")) {
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($u = $res->fetch_assoc()) {
        $ok = sendVerificationEmail($u['id'], $email, $u['username'], $u['verification_code']);
        error_log('resend verification to='.$email.' uid='.$u['id'].' result=' . ($ok?'OK':'FAIL'));
        $resend_msg = $ok
          ? '<div class="alert alert-success">Verification email re‑sent. Check your Mailtrap inbox.</div>'
          : '<div class="alert alert-danger">Resend failed. Please check server logs.</div>';
      } else {
        $resend_msg = '<div class="alert alert-info">This account may already be verified or not found.</div>';
      }
    } else {
      $resend_msg = '<div class="alert alert-danger">Unable to process request. Please try again.</div>';
    }
  }
}

// IMPORTANT: Do NOT unset the session yet if you want multiple resends.
// If you prefer to clear it once page loads initially, move unset AFTER all logic
// and keep a hidden input with the email; here we retain it to enable multiple resends.
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Registration Successful - Webspark</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f5f7fa; }
    .card { border: none; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,.08); }
    .card-header { border-radius: 12px 12px 0 0; }
  </style>
</head>
<body>
<?php if (file_exists(__DIR__."/navbar.php")) include __DIR__."/navbar.php"; ?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-7">
      <div class="card">
        <div class="card-header bg-success text-white text-center py-4">
          <i class="bi bi-check-circle" style="font-size: 3rem;"></i>
          <h4 class="mb-0 mt-2">Registration Successful!</h4>
          <small>Please verify your email to continue</small>
        </div>

        <div class="card-body p-4">
          <div class="text-center mb-4">
            <h5>Welcome to Webspark, <?php echo htmlspecialchars($username); ?>! 🎉</h5>
            <p class="text-muted">Your account has been created successfully.</p>
          </div>

          <?php echo $resend_msg; ?>

          <div class="alert alert-info">
            <div class="d-flex align-items-start">
              <i class="bi bi-envelope-check me-3 mt-1" style="font-size: 1.5rem;"></i>
              <div>
                <h6 class="mb-2">📧 Verification Email Sent</h6>
                <p class="mb-2">We've sent a verification email to:</p>
                <strong class="text-primary"><?php echo htmlspecialchars($email); ?></strong>
                <p class="mt-2 mb-0 small">Click the verification link in the email to activate your account.</p>
              </div>
            </div>
          </div>

          <div class="row mt-4">
            <div class="col-md-6">
              <div class="d-flex align-items-center mb-3">
                <i class="bi bi-check-circle text-success me-2"></i>
                <small>Account created</small>
              </div>
              <div class="d-flex align-items-center mb-3">
                <i class="bi bi-envelope text-primary me-2"></i>
                <small>Verification email sent</small>
              </div>
              <div class="d-flex align-items-center mb-3">
                <i class="bi bi-hourglass text-warning me-2"></i>
                <small>Email verification pending</small>
              </div>
            </div>
            <div class="col-md-6">
              <div class="bg-light p-3 rounded">
                <h6><i class="bi bi-gift text-success me-2"></i>What you'll get:</h6>
                <ul class="small mb-0">
                  <li>10 free credits</li>
                  <li>Access to traffic exchange</li>
                  <li>Real-time analytics</li>
                  <li>Website management tools</li>
                </ul>
              </div>
            </div>
          </div>

          <div class="text-center mt-4">
            <h6>Didn't receive the email? 📬</h6>
            <p class="small text-muted mb-3">Check your spam/junk folder, or request a new verification email:</p>
            <form method="post" class="d-inline">
              <button type="submit" name="resend_email" class="btn btn-outline-primary">
                <i class="bi bi-arrow-clockwise"></i> Resend Verification Email
              </button>
            </form>
          </div>
        </div>

        <div class="card-footer bg-light text-center">
          <small class="text-muted">
            <i class="bi bi-clock"></i> The verification link will expire in 24 hours for security.<br>
            Need help? Contact us at <strong>support@webspark.com</strong>
          </small>
        </div>
      </div>

      <div class="card mt-3">
        <div class="card-body">
          <h6><i class="bi bi-list-check text-primary me-2"></i>Next Steps:</h6>
          <ol class="small">
            <li>Open your Mailtrap Sandbox inbox to see the captured email</li>
            <li>Click the verification link</li>
            <li>Login to your account</li>
            <li>Add your first website</li>
            <li>Start exchanging traffic</li>
          </ol>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Optional: Once everything is confirmed, you can clear the registration_success session -->
<?php
// Uncomment after you confirm resend works as expected:
// unset($_SESSION['registration_success']);
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

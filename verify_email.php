<?php
include "config.php";

$msg = "";
$success = false;

if (isset($_GET['code']) && !empty($_GET['code'])) {
    $verification_code = sanitizeInput($_GET['code']);
    
    // Find user with this verification code
    $stmt = $mysqli->prepare("SELECT id, username, email, email_verified FROM users WHERE verification_code=? AND email_verified=0");
    $stmt->bind_param("s", $verification_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        // Verify the email
        $stmt = $mysqli->prepare("UPDATE users SET email_verified=1, verification_code=NULL WHERE id=?");
        $stmt->bind_param("i", $user['id']);
        
        if ($stmt->execute()) {
            $success = true;
            $msg = '<div class="alert alert-success">
                      <h5><i class="bi bi-check-circle"></i> Email Verified Successfully!</h5>
                      <p>Welcome to Webspark, ' . htmlspecialchars($user['username']) . '! Your email has been verified and your account is now active.</p>
                      <p>You can now login and start using all features.</p>
                   </div>';
            
            // Log the verification
            $stmt = $mysqli->prepare("INSERT INTO email_logs (user_id, email_type, recipient_email, status) VALUES (?, 'verification_success', ?, 'completed')");
            $stmt->bind_param("is", $user['id'], $user['email']);
            $stmt->execute();
            
        } else {
            $msg = '<div class="alert alert-danger">
                      <h5><i class="bi bi-exclamation-triangle"></i> Verification Failed</h5>
                      <p>There was an error verifying your email. Please try again or contact support.</p>
                   </div>';
        }
    } else {
        $msg = '<div class="alert alert-warning">
                  <h5><i class="bi bi-exclamation-triangle"></i> Invalid Verification Link</h5>
                  <p>This verification link is either invalid, expired, or the email has already been verified.</p>
               </div>';
    }
} else {
    $msg = '<div class="alert alert-danger">
              <h5><i class="bi bi-exclamation-triangle"></i> No Verification Code</h5>
              <p>No verification code provided. Please check your email for the correct link.</p>
           </div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Email Verification - Webspark</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include "navbar.php"; ?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow">
        <div class="card-header bg-<?= $success ? 'success' : 'primary' ?> text-white text-center py-4">
          <h4 class="mb-0">Email Verification</h4>
          <small><?= $success ? 'Account Successfully Verified' : 'Verifying Your Email Address' ?></small>
        </div>
        
        <div class="card-body text-center p-4">
          <?= $msg ?>
          
          <?php if ($success): ?>
            <div class="verification-success mt-4">
              <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
              <h3 class="text-success mt-3">Welcome to Webspark!</h3>
              <p class="text-muted">Your account is now fully activated. You can access all features including:</p>
              
              <div class="row mt-4">
                <div class="col-md-4">
                  <i class="bi bi-globe text-primary" style="font-size: 2rem;"></i>
                  <h6 class="mt-2">Add Websites</h6>
                </div>
                <div class="col-md-4">
                  <i class="bi bi-arrow-left-right text-success" style="font-size: 2rem;"></i>
                  <h6 class="mt-2">Traffic Exchange</h6>
                </div>
                <div class="col-md-4">
                  <i class="bi bi-graph-up text-warning" style="font-size: 2rem;"></i>
                  <h6 class="mt-2">Analytics</h6>
                </div>
              </div>
              
              <div class="mt-4">
                <a href="login.php" class="btn btn-success btn-lg me-2">
                  <i class="bi bi-box-arrow-in-right"></i> Login Now
                </a>
                <a href="index.php" class="btn btn-outline-primary">
                  <i class="bi bi-house"></i> Go Home
                </a>
              </div>
            </div>
          <?php else: ?>
            <div class="mt-4">
              <a href="login.php" class="btn btn-primary me-2">
                <i class="bi bi-arrow-left"></i> Back to Login
              </a>
              <a href="register.php" class="btn btn-outline-primary">
                <i class="bi bi-person-plus"></i> Register Again
              </a>
            </div>
          <?php endif; ?>
        </div>
        
        <?php if (!$success): ?>
        <div class="card-footer bg-light">
          <small class="text-muted">
            <strong>Need help?</strong> If you're having trouble verifying your email, please contact our support team.
          </small>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

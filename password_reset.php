<?php
include "config.php";

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!checkRateLimit('password_reset', 3, 1800)) { // 3 attempts per 30 minutes
        $msg = '<div class="alert alert-danger">Too many password reset attempts. Please try again later.</div>';
    } else {
        $email = sanitizeInput($_POST["email"]);
        
        // Check if email exists and is verified
        $stmt = $mysqli->prepare("SELECT id, username, email FROM users WHERE email=? AND email_verified=1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            // Generate reset token
            $reset_token = generateSecureToken();
            $reset_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store reset token
            $stmt = $mysqli->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?");
            $stmt->bind_param("ssi", $reset_token, $reset_expires, $user['id']);
            $stmt->execute();
            
            // Send reset email
            $reset_link = "http://localhost/webspark/reset_password.php?token=" . $reset_token;
            $email_subject = "Reset Your Password - Webspark";
            $email_message = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #0d6efd;'>Password Reset Request</h2>
                    <p>Hello " . htmlspecialchars($user['username']) . ",</p>
                    <p>We received a request to reset your password for your Webspark account.</p>
                    <p>Click the button below to reset your password:</p>
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='$reset_link' style='background: #0d6efd; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a>
                    </p>
                    <p>Or copy this link: <a href='$reset_link'>$reset_link</a></p>
                    <p><strong>This link will expire in 1 hour for security.</strong></p>
                    <p>If you didn't request this, please ignore this email.</p>
                    <hr>
                    <small>This is an automated message from Webspark.</small>
                </div>
            </body>
            </html>
            ";
            
            sendEmail($email, $email_subject, $email_message);
            
            $msg = '<div class="alert alert-success">
                      <h5><i class="bi bi-check-circle"></i> Reset Email Sent!</h5>
                      <p>If an account with that email exists, we\'ve sent you a password reset link.</p>
                      <p>Please check your email (including spam folder) and click the link within 1 hour.</p>
                   </div>';
        } else {
            // Don't reveal if email exists or not (security)
            $msg = '<div class="alert alert-success">
                      <h5><i class="bi bi-check-circle"></i> Reset Email Sent!</h5>
                      <p>If an account with that email exists, we\'ve sent you a password reset link.</p>
                      <p>Please check your email (including spam folder) and click the link within 1 hour.</p>
                   </div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Password Reset - Webspark</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php include "navbar.php"; ?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow">
        <div class="card-header bg-primary text-white text-center py-4">
          <h4 class="mb-0"><i class="bi bi-key"></i> Reset Password</h4>
          <small>Enter your email to receive reset instructions</small>
        </div>
        
        <div class="card-body p-4">
          <?= $msg ?>
          
          <div class="alert alert-info">
            <div class="d-flex align-items-start">
              <i class="bi bi-info-circle me-2 mt-1"></i>
              <small>Enter the email address associated with your account and we'll send you a secure link to reset your password.</small>
            </div>
          </div>
          
          <form method="post" id="resetForm">
            <div class="mb-3">
              <label class="form-label">Email Address *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" required class="form-control form-control-lg" 
                       placeholder="Enter your email address">
              </div>
              <div class="form-text">We'll send reset instructions to this email</div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 btn-lg" id="resetBtn">
              <i class="bi bi-send"></i> Send Reset Link
            </button>
          </form>
        </div>
        
        <div class="card-footer bg-light text-center">
          <small>
            Remember your password? <a href="login.php" class="text-primary">Back to Login</a><br>
            Don't have an account? <a href="register.php" class="text-primary">Sign up here</a>
          </small>
        </div>
      </div>
      
      <!-- Security Info -->
      <div class="card mt-3">
        <div class="card-header bg-warning text-dark">
          <small><i class="bi bi-shield-check"></i> Security Information</small>
        </div>
        <div class="card-body">
          <small>
            • Reset links expire in 1 hour for security<br>
            • Only verified email addresses can reset passwords<br>
            • You can only request 3 resets per 30 minutes<br>
            • Check your spam folder if you don't see the email
          </small>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('resetForm').addEventListener('submit', function(e) {
  const submitBtn = document.getElementById('resetBtn');
  const originalText = submitBtn.innerHTML;
  
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
  submitBtn.disabled = true;
  
  // Re-enable after timeout
  setTimeout(() => {
    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
  }, 5000);
});
</script>
</body>
</html>

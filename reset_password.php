<?php
include "config.php";

$msg = "";
$valid_token = false;
$user = null;

// Check if token is provided and valid
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = sanitizeInput($_GET['token']);
    
    // Find user with valid, non-expired token
    $stmt = $mysqli->prepare("SELECT id, username, email FROM users WHERE reset_token=? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        $valid_token = true;
    } else {
        $msg = '<div class="alert alert-danger">
                  <h5><i class="bi bi-exclamation-triangle"></i> Invalid or Expired Link</h5>
                  <p>This password reset link is either invalid or has expired. Please request a new one.</p>
               </div>';
    }
}

// Process password reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid_token) {
    $new_password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    
    if ($new_password !== $confirm_password) {
        $msg = '<div class="alert alert-danger">Passwords do not match.</div>';
    } elseif (strlen($new_password) < 8) {
        $msg = '<div class="alert alert-danger">Password must be at least 8 characters long.</div>';
    } else {
        // Hash new password and update
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $mysqli->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL, failed_login_attempts=0 WHERE id=?");
        $stmt->bind_param("si", $password_hash, $user['id']);
        
        if ($stmt->execute()) {
            $msg = '<div class="alert alert-success">
                      <h5><i class="bi bi-check-circle"></i> Password Reset Successful!</h5>
                      <p>Your password has been updated successfully. You can now login with your new password.</p>
                   </div>';
            
            // Log the password reset
            $stmt = $mysqli->prepare("INSERT INTO security_logs (user_id, event_type, success, ip_address) VALUES (?, 'password_reset', 1, ?)");
            $stmt->bind_param("is", $user['id'], $_SERVER['REMOTE_ADDR']);
            $stmt->execute();
            
            $valid_token = false; // Hide form after successful reset
        } else {
            $msg = '<div class="alert alert-danger">Failed to update password. Please try again.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password - Webspark</title>
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
          <h4 class="mb-0"><i class="bi bi-shield-lock"></i> Reset Your Password</h4>
          <small>Create a new secure password</small>
        </div>
        
        <div class="card-body p-4">
          <?= $msg ?>
          
          <?php if ($valid_token && $user): ?>
            <div class="alert alert-info">
              <div class="d-flex align-items-center">
                <i class="bi bi-person-circle me-2"></i>
                <div>
                  <strong>Resetting password for:</strong><br>
                  <small><?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['email']) ?>)</small>
                </div>
              </div>
            </div>
            
            <form method="post" id="resetPasswordForm">
              <div class="mb-3">
                <label class="form-label">New Password *</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-lock"></i></span>
                  <input type="password" name="password" required class="form-control form-control-lg" 
                         minlength="8" id="password">
                  <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
                <div class="form-text">Minimum 8 characters with uppercase, lowercase, and number</div>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Confirm New Password *</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                  <input type="password" name="confirm_password" required class="form-control form-control-lg" 
                         minlength="8" id="confirmPassword">
                  <button type="button" class="btn btn-outline-secondary" id="toggleConfirmPassword">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>
              
              <div class="mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="logoutOtherDevices">
                  <label class="form-check-label" for="logoutOtherDevices">
                    Log out all other devices (recommended)
                  </label>
                </div>
              </div>
              
              <button type="submit" class="btn btn-primary w-100 btn-lg" id="resetBtn">
                <i class="bi bi-check-circle"></i> Update Password
              </button>
            </form>
          
          <?php elseif (!isset($_GET['token'])): ?>
            <div class="text-center">
              <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
              <h5 class="mt-3">No Reset Token Provided</h5>
              <p class="text-muted">Please use the reset link from your email.</p>
              <a href="password_reset.php" class="btn btn-primary">Request New Reset Link</a>
            </div>
          <?php else: ?>
            <div class="text-center">
              <a href="password_reset.php" class="btn btn-primary">Request New Reset Link</a>
              <a href="login.php" class="btn btn-outline-primary ms-2">Back to Login</a>
            </div>
          <?php endif; ?>
        </div>
        
        <?php if ($valid_token): ?>
        <div class="card-footer bg-light">
          <small class="text-muted">
            <i class="bi bi-shield-check"></i> This reset link will expire soon. Complete the process now for security.
          </small>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Password visibility toggles
document.getElementById('togglePassword').addEventListener('click', function() {
  const password = document.getElementById('password');
  const icon = this.querySelector('i');
  
  if (password.type === 'password') {
    password.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    password.type = 'password';
    icon.className = 'bi bi-eye';
  }
});

document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
  const password = document.getElementById('confirmPassword');
  const icon = this.querySelector('i');
  
  if (password.type === 'password') {
    password.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    password.type = 'password';
    icon.className = 'bi bi-eye';
  }
});

// Form validation
document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
  const password = document.getElementById('password').value;
  const confirmPassword = document.getElementById('confirmPassword').value;
  
  if (password !== confirmPassword) {
    e.preventDefault();
    alert('Passwords do not match!');
    return false;
  }
  
  const submitBtn = document.getElementById('resetBtn');
  const originalText = submitBtn.innerHTML;
  
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
  submitBtn.disabled = true;
  
  setTimeout(() => {
    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
  }, 5000);
});
</script>
</body>
</html>

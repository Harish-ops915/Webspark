<?php
include "config.php";
$msg = "";

if (isset($_SESSION['success'])) {
    $msg = '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!checkRateLimit('login', 5, 300)) {
        $msg = '<div class="alert alert-danger">Too many login attempts. Please try again later.</div>';
    } else {
        // Skip CAPTCHA verification since it's disabled
        $captcha_valid = true;
        if (CAPTCHA_ENABLED) {
            if (empty($_POST['g-recaptcha-response']) || !verifyRecaptcha($_POST['g-recaptcha-response'])) {
                $captcha_valid = false;
                $msg = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Please complete the CAPTCHA verification.</div>';
            }
        }
        
        if ($captcha_valid) {
            $username = sanitizeInput($_POST["username"]);
            $password = $_POST["password"];
            
            $stmt = $mysqli->prepare("SELECT id, password, username, email, email_verified, failed_login_attempts, account_locked, verification_code FROM users WHERE username=? OR email=?");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($row = $res->fetch_assoc()) {
                if ($row['account_locked']) {
                    $msg = '<div class="alert alert-danger">
                              <h6><i class="bi bi-lock"></i> Account Locked</h6>
                              <p>Your account has been locked due to suspicious activity. Please contact support.</p>
                           </div>';
                } elseif (!$row['email_verified'] && REQUIRE_EMAIL_VERIFICATION) {
                    // EMAIL VERIFICATION REQUIRED!
                    $msg = '<div class="alert alert-warning">
                              <h6><i class="bi bi-envelope-exclamation"></i> Email Verification Required</h6>
                              <p>You must verify your email address before logging in. Please check your email for the verification link.</p>
                              <form method="post" class="d-inline">
                                <input type="hidden" name="resend_verification" value="1">
                                <input type="hidden" name="user_email" value="' . htmlspecialchars($row['email']) . '">
                                <input type="hidden" name="user_id" value="' . $row['id'] . '">
                                <input type="hidden" name="verification_code" value="' . $row['verification_code'] . '">
                                <button type="submit" class="btn btn-sm btn-outline-warning mt-2">
                                  <i class="bi bi-arrow-clockwise"></i> Resend Verification Email
                                </button>
                              </form>
                           </div>';
                } elseif (password_verify($password, $row['password'])) {
                    // Reset failed attempts and login successfully
                    $mysqli->query("UPDATE users SET failed_login_attempts=0, last_login_attempt=NOW() WHERE id=" . $row['id']);
                    
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    
                    // Log successful login
                    $stmt = $mysqli->prepare("INSERT INTO security_logs (user_id, event_type, success, ip_address) VALUES (?, 'login_success', 1, ?)");
                    $stmt->bind_param("is", $row['id'], $_SERVER['REMOTE_ADDR']);
                    $stmt->execute();
                    
                    header("Location: dashboard.php");
                    exit;
                } else {
                    // Increment failed attempts
                    $failed_attempts = $row['failed_login_attempts'] + 1;
                    $lock_account = $failed_attempts >= 5 ? 1 : 0;
                    
                    $stmt = $mysqli->prepare("UPDATE users SET failed_login_attempts=?, account_locked=?, last_login_attempt=NOW() WHERE id=?");
                    $stmt->bind_param("iii", $failed_attempts, $lock_account, $row['id']);
                    $stmt->execute();
                    
                    $remaining = 5 - $failed_attempts;
                    $msg = '<div class="alert alert-danger">Invalid credentials. ' . max(0, $remaining) . ' attempts remaining before account lockout.</div>';
                    
                    // Log failed login
                    $stmt = $mysqli->prepare("INSERT INTO security_logs (user_id, event_type, success, ip_address) VALUES (?, 'failed_login', 0, ?)");
                    $stmt->bind_param("is", $row['id'], $_SERVER['REMOTE_ADDR']);
                    $stmt->execute();
                }
            } else {
                $msg = '<div class="alert alert-danger">Invalid username or password.</div>';
            }
        }
    }
}

// Handle resend verification email
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['resend_verification'])) {
    if (!checkRateLimit('resend_verification', 3, 300)) {
        $msg = '<div class="alert alert-danger">Too many resend attempts. Please try again later.</div>';
    } else {
        $user_id = intval($_POST['user_id']);
        $user_email = $_POST['user_email'];
        $verification_code = $_POST['verification_code'];
        
        // Get username for email
        $stmt = $mysqli->prepare("SELECT username FROM users WHERE id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            $email_sent = sendVerificationEmail($user_id, $user_email, $user['username'], $verification_code);
            
            if ($email_sent) {
                $msg = '<div class="alert alert-success">
                          <h6><i class="bi bi-check-circle"></i> Verification Email Sent!</h6>
                          <p>Please check your email (including spam folder) and click the verification link.</p>
                       </div>';
            } else {
                $msg = '<div class="alert alert-danger">Failed to send verification email. Please contact support.</div>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - Webspark</title>
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
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0">Login to Your Account</h4>
          <small>📧 Email verification required for new accounts</small>
        </div>
        <div class="card-body">
          <?= $msg ?>
          
          <form method="post" id="loginForm">
            <div class="mb-3">
              <label class="form-label">Username or Email *</label>
              <input type="text" name="username" required class="form-control" 
                     value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Password *</label>
              <div class="input-group">
                <input type="password" name="password" required class="form-control" id="password">
                <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="rememberMe">
                <label class="form-check-label" for="rememberMe">Remember me</label>
              </div>
              <a href="password_reset.php" class="text-primary">Forgot password?</a>
            </div>
            
            <button type="submit" class="btn btn-primary w-100" id="loginBtn">
              <i class="bi bi-box-arrow-in-right"></i> Login
            </button>
          </form>
        </div>
        <div class="card-footer text-center">
          <small>Don't have an account? <a href="register.php">Register here</a></small>
        </div>
      </div>
      
      <!-- Demo Credentials - Only for verified accounts -->
      <div class="card mt-3">
        <div class="card-header bg-success text-white">
          <small><i class="bi bi-play-circle"></i> Demo Credentials (Pre-verified accounts)</small>
        </div>
        <div class="card-body">
          <div class="row text-center">
            <div class="col-6">
              <button type="button" class="btn btn-outline-success btn-sm w-100 mb-2" onclick="fillDemo('johndoe', 'password')">
                <strong>Demo User 1</strong><br>
                <small>johndoe / password</small>
              </button>
            </div>
            <div class="col-6">
              <button type="button" class="btn btn-outline-success btn-sm w-100 mb-2" onclick="fillDemo('janedoe', 'password')">
                <strong>Demo User 2</strong><br>
                <small>janedoe / password</small>
              </button>
            </div>
          </div>
          <div class="text-center">
            <small class="text-muted">
              <i class="bi bi-info-circle"></i> These demo accounts are pre-verified for testing
            </small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Password visibility toggle
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

function fillDemo(username, password) {
  document.querySelector('input[name="username"]').value = username;
  document.querySelector('input[name="password"]').value = password;
  
  // Visual feedback
  event.target.classList.add('btn-success');
  event.target.classList.remove('btn-outline-success');
  
  setTimeout(() => {
    event.target.classList.remove('btn-success');
    event.target.classList.add('btn-outline-success');
  }, 1000);
}

// Form submission handling
document.getElementById('loginForm').addEventListener('submit', function(e) {
  const submitBtn = document.getElementById('loginBtn');
  const originalText = submitBtn.innerHTML;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signing In...';
  submitBtn.disabled = true;
  
  setTimeout(() => {
    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
  }, 5000);
});
</script>
</body>
</html>

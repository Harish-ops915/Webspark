<?php
// register.php — Webspark (schema without first_name/last_name)
require_once __DIR__ . "/config.php";

/*
 Requires in config.php:
   $mysqli  (mysqli connection, utf8mb4)
   sanitizeInput($str)
   generateSecureToken($len=32)
   sendVerificationEmail($user_id,$email,$username,$verification_code)
   define('SITE_URL', 'http://localhost/Webspark');
 XAMPP sendmail.ini must point to Mailtrap (you already tested mail_test.php).
*/

$msg = "";
$prefill = [
  'username' => '',
  'email'    => '',
  'country'  => '',
  'niche'    => ''
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  // Lightweight rate-limit per IP (10 minutes window, 12 attempts)
  if (!isset($_SESSION['reg_attempts'])) $_SESSION['reg_attempts'] = [];
  $_SESSION['reg_attempts'] = array_filter(
    $_SESSION['reg_attempts'],
    fn($ts) => (time() - $ts) < 600
  );
  if (count($_SESSION['reg_attempts']) >= 12) {
    $msg = '<div class="alert alert-warning">Too many registration attempts. Please wait and try again.</div>';
  } else {

    // Collect + sanitize
    $username = $prefill['username'] = sanitizeInput($_POST['username'] ?? '');
    $email    = $prefill['email']    = sanitizeInput($_POST['email'] ?? '');
    $country  = $prefill['country']  = sanitizeInput($_POST['country'] ?? '');
    $niche    = $prefill['niche']    = sanitizeInput($_POST['niche'] ?? '');
    $passwordRaw = $_POST['password'] ?? '';
    $confirmRaw  = $_POST['confirmPassword'] ?? '';

    // Basic validations
    $errors = [];
    if (strlen($username) < 3) $errors[] = "Username must be at least 3 characters.";
    if (!preg_match('/^[A-Za-z0-9_]+$/', $username)) $errors[] = "Username can contain letters, numbers and underscore only.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email address.";
    if (strlen($passwordRaw) < 8) $errors[] = "Password must be at least 8 characters.";
    if ($passwordRaw !== $confirmRaw) $errors[] = "Passwords do not match.";
    if (!$country) $errors[] = "Country is required.";
    if (!$niche)   $errors[] = "Primary niche is required.";

    if ($errors) {
      $msg = '<div class="alert alert-danger"><ul class="mb-0"><li>'.implode('</li><li>', $errors).'</li></ul></div>';
    } else {
      // Unique checks
      $stmt = $mysqli->prepare("SELECT id FROM users WHERE username=? OR email=? LIMIT 1");
      $stmt->bind_param("ss", $username, $email);
      $stmt->execute();
      if ($stmt->get_result()->num_rows > 0) {
        $msg = '<div class="alert alert-danger">Username or Email already exists. Please choose different credentials.</div>';
      } else {
        // Create user (email_verified=0)
        $passwordHash = password_hash($passwordRaw, PASSWORD_DEFAULT);
        $verification_code = generateSecureToken(); // 64 hex chars
        $referral_code = 'REF_'.strtoupper(substr(generateSecureToken(8), 0, 8));
        $referred_by = null; // or detect via ?ref=

        $stmt = $mysqli->prepare("
          INSERT INTO users
            (username,email,password,country,niche,credits,verification_code,email_verified,referral_code,referred_by,created_at)
          VALUES
            (?,?,?,?,?,10,?,0,?,?,NOW())
        ");
        $stmt->bind_param(
          "sssssssi",
          $username, $email, $passwordHash, $country, $niche,
          $verification_code, $referral_code, $referred_by
        );

        if ($stmt->execute()) {
          $new_user_id = $mysqli->insert_id;

          // Build + log link for quick debugging
          $verification_link = SITE_URL . "/verify_email.php?code=" . urlencode($verification_code);
          error_log("VERIFY code=$verification_code link=$verification_link to=$email uid=$new_user_id");

          // Send verification email; only proceed when OK
          $sent = sendVerificationEmail($new_user_id, $email, $username, $verification_code);
          error_log('verification send for uid='.$new_user_id.' email='.$email.' result=' . ($sent?'OK':'FAIL'));

          if ($sent) {
            $_SESSION['registration_success'] = [
              'email'    => $email,
              'username' => $username
            ];
            $_SESSION['reg_attempts'][] = time();
            header("Location: registration_success.php");
            exit;
          } else {
            $msg = '<div class="alert alert-warning">
                      Account created, but we could not send your verification email.
                      Please click the Resend button on the next page or check server logs.
                    </div>';
          }
        } else {
          $msg = '<div class="alert alert-danger">Registration failed. Please try again.</div>';
        }
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - Webspark</title>
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
    <div class="col-lg-7">
      <div class="card">
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0">Create Your Account</h4>
          <small>📧 Email verification required • 🎉 10 free credits</small>
        </div>
        <div class="card-body">
          <?php echo $msg; ?>

          <div class="alert alert-warning">
            <i class="bi bi-envelope-check me-2"></i>
            You'll need to verify your email before logging in. A link will be sent to your email address.
          </div>

          <form method="post" id="registerForm" novalidate>
            <div class="mb-3">
              <label class="form-label">Username *</label>
              <input type="text" name="username" class="form-control" required minlength="3" maxlength="20" pattern="[a-zA-Z0-9_]+" value="<?php echo htmlspecialchars($prefill['username']); ?>">
              <div class="form-text">3–20 characters; letters, numbers, underscore</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Email Address *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($prefill['email']); ?>">
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Country *</label>
                  <select name="country" class="form-select" required>
                    <option value="">Select Country</option>
                    <?php
                      $countries = ["Australia","USA","UK","Canada","India","Germany","France","Japan","Brazil","Other"];
                      foreach ($countries as $c) {
                        $sel = ($prefill['country']===$c)?'selected':'';
                        echo "<option value=\"$c\" $sel>$c</option>";
                      }
                    ?>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Primary Niche *</label>
                  <select name="niche" class="form-select" required>
                    <?php
                      $niches = ["Technology","Health","Business","Education","Entertainment","Travel","Food","Fashion","Sports","Finance"];
                      echo '<option value="">Select Niche</option>';
                      foreach ($niches as $n) {
                        $sel = ($prefill['niche']===$n)?'selected':'';
                        echo "<option value=\"$n\" $sel>$n</option>";
                      }
                    ?>
                  </select>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Password *</label>
                  <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control" required minlength="8" placeholder="Min 8 chars">
                    <button type="button" class="btn btn-outline-secondary" id="togglePassword"><i class="bi bi-eye"></i></button>
                  </div>
                  <div class="form-text">Use upper/lowercase, number, and symbol</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Confirm Password *</label>
                  <div class="input-group">
                    <input type="password" name="confirmPassword" id="confirmPassword" class="form-control" required minlength="8">
                    <button type="button" class="btn btn-outline-secondary" id="toggleConfirm"><i class="bi bi-eye"></i></button>
                  </div>
                </div>
              </div>
            </div>

            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" id="terms" required>
              <label class="form-check-label" for="terms">
                I agree to the <a href="#" target="_blank">Terms of Service</a> and <a href="#" target="_blank">Privacy Policy</a> *
              </label>
            </div>

            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-envelope-check"></i> Create Account & Send Verification
            </button>
          </form>

          <hr>
          <div class="text-center">
            <small>Already have an account? <a href="login.php">Login here</a></small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('togglePassword').addEventListener('click', function(){
  const p = document.getElementById('password');
  const i = this.querySelector('i');
  if (p.type==='password'){ p.type='text'; i.className='bi bi-eye-slash'; }
  else { p.type='password'; i.className='bi bi-eye'; }
});
document.getElementById('toggleConfirm').addEventListener('click', function(){
  const p = document.getElementById('confirmPassword');
  const i = this.querySelector('i');
  if (p.type==='password'){ p.type='text'; i.className='bi bi-eye-slash'; }
  else { p.type='password'; i.className='bi bi-eye'; }
});
</script>
</body>
</html>

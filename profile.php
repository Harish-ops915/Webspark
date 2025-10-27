<?php
include "config.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$msg = "";

// Handle website addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_website'])) {
    if (!checkRateLimit('add_website', 10, 3600)) {
        $msg = '<div class="alert alert-danger">Too many website additions. Please try again later.</div>';
    } else {
        $url = sanitizeInput($_POST["url"]);
        $niche = sanitizeInput($_POST["niche"]);
        $country = sanitizeInput($_POST["country"]);
        
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $msg = '<div class="alert alert-danger">Please enter a valid URL.</div>';
        } else {
            // Check if URL already exists
            $stmt = $mysqli->prepare("SELECT id FROM websites WHERE url=?");
            $stmt->bind_param("s", $url);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                $msg = '<div class="alert alert-danger">This website is already registered.</div>';
            } else {
                $stmt = $mysqli->prepare("INSERT INTO websites(user_id, url, niche, country, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->bind_param("isss", $user_id, $url, $niche, $country);
                
                if ($stmt->execute()) {
                    $msg = '<div class="alert alert-success">Website added successfully! It will be reviewed and activated soon.</div>';
                } else {
                    $msg = '<div class="alert alert-danger">Failed to add website. Please try again.</div>';
                }
            }
        }
    }
}

// Handle social media account linking
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['link_social'])) {
    $platform = sanitizeInput($_POST["platform"]);
    $account_id = sanitizeInput($_POST["account_id"]);
    
    $stmt = $mysqli->prepare("INSERT INTO social_accounts(user_id, platform, account_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE account_id=VALUES(account_id)");
    $stmt->bind_param("iss", $user_id, $platform, $account_id);
    
    if ($stmt->execute()) {
        $msg = '<div class="alert alert-success">Social media account linked successfully!</div>';
    } else {
        $msg = '<div class="alert alert-danger">Failed to link social media account.</div>';
    }
}

// Get user info
$stmt = $mysqli->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();

// Get user's websites
$stmt = $mysqli->prepare("SELECT * FROM websites WHERE user_id=? ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$websites = $stmt->get_result();

// Get linked social accounts
$stmt = $mysqli->prepare("SELECT * FROM social_accounts WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$social_accounts = $stmt->get_result();
$linked_platforms = [];
while ($account = $social_accounts->fetch_assoc()) {
    $linked_platforms[$account['platform']] = $account['account_id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile - Webspark</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="dashboard-page">
<?php include "navbar.php"; ?>

<div class="container py-4">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2>Profile Management</h2>
      <p class="text-muted mb-0">Manage your account, websites, and social media connections</p>
    </div>
    <a href="dashboard.php" class="btn btn-outline-primary">
      <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
  </div>

  <?= $msg ?>

  <div class="row">
    <!-- Profile Information -->
    <div class="col-md-4">
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0"><i class="bi bi-person"></i> Profile Information</h5>
        </div>
        <div class="card-body">
          <div class="text-center mb-4">
            <div class="profile-avatar">
              <i class="bi bi-person-circle" style="font-size: 4rem; color: #6c757d;"></i>
            </div>
            <h5 class="mt-2"><?= $user_info['username'] ?></h5>
            <p class="text-muted"><?= $user_info['email'] ?></p>
            <span class="badge bg-<?= $user_info['subscription'] == 'free' ? 'secondary' : ($user_info['subscription'] == 'premium' ? 'primary' : 'success') ?>">
              <?= ucfirst($user_info['subscription']) ?> Member
            </span>
          </div>
          
          <div class="profile-stats">
            <div class="row text-center">
              <div class="col-4">
                <div class="stat-number"><?= $user_info['credits'] ?></div>
                <div class="stat-label">Credits</div>
              </div>
              <div class="col-4">
                <div class="stat-number"><?php
                  $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM websites WHERE user_id=?");
                  $stmt->bind_param("i", $user_id);
                  $stmt->execute();
                  echo $stmt->get_result()->fetch_assoc()['count'];
                ?></div>
                <div class="stat-label">Websites</div>
              </div>
              <div class="col-4">
                <div class="stat-number"><?php
                  $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM visits WHERE visitor_user_id=?");
                  $stmt->bind_param("i", $user_id);
                  $stmt->execute();
                  echo $stmt->get_result()->fetch_assoc()['count'];
                ?></div>
                <div class="stat-label">Visits</div>
              </div>
            </div>
          </div>
          
          <hr>
          
          <form id="profileUpdateForm">
            <div class="form-group mb-3">
              <label class="form-label">Country</label>
              <select class="form-select" name="country">
                <option value="Australia" <?= $user_info['country'] == 'Australia' ? 'selected' : '' ?>>Australia</option>
                <option value="USA" <?= $user_info['country'] == 'USA' ? 'selected' : '' ?>>United States</option>
                <option value="UK" <?= $user_info['country'] == 'UK' ? 'selected' : '' ?>>United Kingdom</option>
                <option value="Canada" <?= $user_info['country'] == 'Canada' ? 'selected' : '' ?>>Canada</option>
                <option value="India" <?= $user_info['country'] == 'India' ? 'selected' : '' ?>>India</option>
              </select>
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Primary Niche</label>
              <select class="form-select" name="niche">
                <option value="Technology" <?= $user_info['niche'] == 'Technology' ? 'selected' : '' ?>>Technology</option>
                <option value="Health" <?= $user_info['niche'] == 'Health' ? 'selected' : '' ?>>Health & Fitness</option>
                <option value="Business" <?= $user_info['niche'] == 'Business' ? 'selected' : '' ?>>Business</option>
                <option value="Education" <?= $user_info['niche'] == 'Education' ? 'selected' : '' ?>>Education</option>
                <option value="Entertainment" <?= $user_info['niche'] == 'Entertainment' ? 'selected' : '' ?>>Entertainment</option>
              </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-save"></i> Update Profile
            </button>
          </form>
        </div>
      </div>

      <!-- Social Media Integration -->
      <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
          <h5 class="mb-0"><i class="bi bi-share"></i> Social Media Analytics</h5>
        </div>
        <div class="card-body">
          <p class="text-muted small">Connect your social media accounts to track traffic from social platforms.</p>
          
          <?php
          $platforms = [
            'facebook' => ['name' => 'Facebook', 'icon' => 'facebook', 'color' => 'primary'],
            'twitter' => ['name' => 'Twitter/X', 'icon' => 'twitter', 'color' => 'info'],
            'instagram' => ['name' => 'Instagram', 'icon' => 'instagram', 'color' => 'danger'],
            'linkedin' => ['name' => 'LinkedIn', 'icon' => 'linkedin', 'color' => 'primary']
          ];
          
          foreach ($platforms as $key => $platform):
          ?>
          <div class="social-platform mb-3">
            <div class="d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center">
                <i class="bi bi-<?= $platform['icon'] ?> text-<?= $platform['color'] ?> me-2"></i>
                <span><?= $platform['name'] ?></span>
              </div>
              
              <?php if (isset($linked_platforms[$key])): ?>
                <span class="badge bg-success">
                  <i class="bi bi-check"></i> Connected
                </span>
              <?php else: ?>
                <button class="btn btn-sm btn-outline-<?= $platform['color'] ?>" 
                        onclick="connectSocial('<?= $key ?>', '<?= $platform['name'] ?>')">
                  Connect
                </button>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
          
          <div class="mt-3">
            <button class="btn btn-outline-secondary btn-sm w-100" onclick="refreshSocialData()" <?= empty($linked_platforms) ? 'disabled' : '' ?>>
              <i class="bi bi-arrow-repeat"></i> Refresh Analytics
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Website Management -->
    <div class="col-md-8">
      <!-- Add New Website -->
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white">
          <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Add New Website</h5>
        </div>
        <div class="card-body">
          <form method="post">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Website URL *</label>
                <input type="url" name="url" class="form-control" placeholder="https://example.com" required>
                <div class="form-text">Must be a valid, accessible website</div>
              </div>
              <div class="col-md-3">
                <label class="form-label">Niche *</label>
                <select name="niche" class="form-select" required>
                  <option value="">Select...</option>
                  <option value="Technology">Technology</option>
                  <option value="Health">Health & Fitness</option>
                  <option value="Business">Business</option>
                  <option value="Education">Education</option>
                  <option value="Entertainment">Entertainment</option>
                  <option value="Travel">Travel</option>
                  <option value="Food">Food & Cooking</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Target Country *</label>
                <select name="country" class="form-select" required>
                  <option value="">Select...</option>
                  <option value="Australia">Australia</option>
                  <option value="USA">USA</option>
                  <option value="UK">UK</option>
                  <option value="Canada">Canada</option>
                  <option value="India">India</option>
                  <option value="Germany">Germany</option>
                </select>
              </div>
              <div class="col-12">
                <button type="submit" name="add_website" class="btn btn-success">
                  <i class="bi bi-plus"></i> Add Website
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Existing Websites -->
      <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark">
          <h5 class="mb-0"><i class="bi bi-globe"></i> Your Websites</h5>
        </div>
        <div class="card-body p-0">
          <?php if ($websites->num_rows > 0): ?>
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Website URL</th>
                    <th>Niche</th>
                    <th>Country</th>
                    <th>Status</th>
                    <th>Visits</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while($website = $websites->fetch_assoc()): ?>
                  <tr>
                    <td>
                      <div class="website-info">
                        <a href="<?= $website['url'] ?>" target="_blank" class="text-decoration-none">
                          <i class="bi bi-globe text-primary me-1"></i>
                          <?= parse_url($website['url'], PHP_URL_HOST) ?>
                        </a>
                        <div class="small text-muted"><?= $website['url'] ?></div>
                      </div>
                    </td>
                    <td><span class="badge bg-secondary"><?= $website['niche'] ?></span></td>
                    <td><span class="badge bg-info"><?= $website['country'] ?></span></td>
                    <td>
                      <?php
                      $status_class = $website['status'] == 'active' ? 'success' : ($website['status'] == 'pending' ? 'warning' : 'danger');
                      ?>
                      <span class="badge bg-<?= $status_class ?>"><?= ucfirst($website['status']) ?></span>
                    </td>
                    <td>
                      <?php
                      $stmt = $mysqli->prepare("SELECT COUNT(*) as visits FROM visits v JOIN websites w ON v.website_id = w.id WHERE w.id=?");
                      $stmt->bind_param("i", $website['id']);
                      $stmt->execute();
                      $visit_count = $stmt->get_result()->fetch_assoc()['visits'];
                      echo $visit_count;
                      ?>
                    </td>
                    <td>
                      <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="editWebsite(<?= $website['id'] ?>)">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-info" onclick="viewAnalytics(<?= $website['id'] ?>)">
                          <i class="bi bi-bar-chart"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="deleteWebsite(<?= $website['id'] ?>)">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="text-center py-5">
              <i class="bi bi-globe display-1 text-muted"></i>
              <h5 class="text-muted mt-3">No websites added yet</h5>
              <p class="text-muted">Add your first website to start receiving traffic</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Social Media Connection Modal -->
<div class="modal fade" id="socialModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Connect Social Media Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form method="post">
          <input type="hidden" name="platform" id="platformInput">
          <div class="mb-3">
            <label class="form-label">Platform</label>
            <input type="text" class="form-control" id="platformDisplay" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Account ID/Username</label>
            <input type="text" name="account_id" class="form-control" placeholder="Enter your account ID or username" required>
            <div class="form-text">This will be used to fetch analytics data</div>
          </div>
          <button type="submit" name="link_social" class="btn btn-primary w-100">
            <i class="bi bi-link"></i> Connect Account
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function connectSocial(platform, platformName) {
  document.getElementById('platformInput').value = platform;
  document.getElementById('platformDisplay').value = platformName;
  new bootstrap.Modal(document.getElementById('socialModal')).show();
}

function editWebsite(websiteId) {
  // Implement edit functionality
  alert('Edit functionality for website ID: ' + websiteId);
}

function viewAnalytics(websiteId) {
  // Implement analytics view
  window.open('website_analytics.php?id=' + websiteId, '_blank');
}

function deleteWebsite(websiteId) {
  if (confirm('Are you sure you want to delete this website? This action cannot be undone.')) {
    fetch('delete_website.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'website_id=' + websiteId
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        location.reload();
      } else {
        alert('Failed to delete website: ' + data.error);
      }
    });
  }
}

function refreshSocialData() {
  const btn = event.target;
  const originalText = btn.innerHTML;
  btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Refreshing...';
  btn.disabled = true;
  
  fetch('refresh_social_analytics.php', {
    method: 'POST'
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Social media analytics refreshed successfully!');
    } else {
      alert('Failed to refresh data: ' + data.error);
    }
    btn.innerHTML = originalText;
    btn.disabled = false;
  });
}

// Profile update form
document.getElementById('profileUpdateForm').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const formData = new FormData(this);
  formData.append('update_profile', '1');
  
  fetch('update_profile.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Profile updated successfully!');
    } else {
      alert('Failed to update profile: ' + data.error);
    }
  });
});
</script>

<style>
.profile-avatar {
  margin-bottom: 1rem;
}

.stat-number {
  font-size: 1.5rem;
  font-weight: bold;
  color: #0d6efd;
}

.stat-label {
  font-size: 0.875rem;
  color: #6c757d;
}

.social-platform {
  padding: 0.5rem 0;
  border-bottom: 1px solid #f8f9fa;
}

.social-platform:last-child {
  border-bottom: none;
}

.website-info a:hover {
  text-decoration: underline !important;
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
</body>
</html>

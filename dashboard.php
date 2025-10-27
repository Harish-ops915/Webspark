<?php
include "config.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user stats
$stmt = $mysqli->prepare("
    SELECT 
        SUM(dwell_time) as total_dwell, 
        AVG(bounce) as avg_bounce, 
        COUNT(*) as total_visits,
        COUNT(DISTINCT DATE(visit_time)) as active_days
    FROM visits 
    WHERE visitor_user_id=?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get user info with referral data
$stmt = $mysqli->prepare("
    SELECT u.*, 
           (SELECT COUNT(*) FROM referrals WHERE referrer_id = u.id) as total_referrals,
           (SELECT SUM(bonus_credits) FROM referrals WHERE referrer_id = u.id) as referral_earnings
    FROM users u 
    WHERE u.id=?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();

// Get weekly traffic data for chart
$weekly_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $mysqli->prepare("SELECT COUNT(*) as visits FROM visits WHERE visitor_user_id=? AND DATE(visit_time)=?");
    $stmt->bind_param("is", $user_id, $date);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $weekly_data[] = $result['visits'];
}

// Get user's websites
$stmt = $mysqli->prepare("SELECT * FROM websites WHERE user_id=? ORDER BY id DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$websites = $stmt->get_result();

// Generate referral link
$referral_link = "http://localhost/webspark/register.php?ref=" . base64_encode($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Webspark</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="dashboard-page">
<?php include "navbar.php"; ?>

<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <div class="col-md-3 col-lg-2 sidebar">
      <div class="d-flex flex-column p-3">
        <ul class="nav nav-pills flex-column mb-auto">
          <li class="nav-item">
            <a href="dashboard.php" class="nav-link active">
              <i class="bi bi-speedometer2"></i> Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a href="exchange.php" class="nav-link">
              <i class="bi bi-arrow-left-right"></i> Traffic Exchange
            </a>
          </li>
          <li class="nav-item">
            <a href="profile.php" class="nav-link">
              <i class="bi bi-globe"></i> My Websites
            </a>
          </li>
          <li class="nav-item">
            <a href="#referralModal" class="nav-link" data-bs-toggle="modal">
              <i class="bi bi-people"></i> Referrals
            </a>
          </li>
          <li class="nav-item">
            <a href="upgrade.php" class="nav-link">
              <i class="bi bi-star"></i> Upgrade Plan
            </a>
          </li>
        </ul>
      </div>
    </div>

    <!-- Main Content -->
    <div class="col-md-9 col-lg-10 main-content">
      <div class="container-fluid py-4">
        <!-- Welcome Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h2 class="mb-1">Welcome back, <?= $_SESSION['username']; ?>! 👋</h2>
            <p class="text-muted mb-0">Here's your traffic performance overview</p>
          </div>
          <div class="credits-display">
            <div class="badge bg-success fs-5">
              <i class="bi bi-coin"></i> <?= $user_info['credits'] ?> Credits
            </div>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
          <div class="col-md-3">
            <div class="stats-card card border-0 shadow-sm">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="stats-icon bg-primary">
                    <i class="bi bi-eye-fill text-white"></i>
                  </div>
                  <div class="ms-3">
                    <div class="stats-number"><?= $stats['total_visits'] ?: 0 ?></div>
                    <div class="stats-label">Total Visits</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-md-3">
            <div class="stats-card card border-0 shadow-sm">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="stats-icon bg-success">
                    <i class="bi bi-clock-fill text-white"></i>
                  </div>
                  <div class="ms-3">
                    <div class="stats-number"><?= round(($stats['total_dwell'] ?: 0) / max(1, $stats['total_visits'] ?: 1)) ?>s</div>
                    <div class="stats-label">Avg. Dwell Time</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-md-3">
            <div class="stats-card card border-0 shadow-sm">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="stats-icon bg-warning">
                    <i class="bi bi-people-fill text-white"></i>
                  </div>
                  <div class="ms-3">
                    <div class="stats-number"><?= $user_info['total_referrals'] ?: 0 ?></div>
                    <div class="stats-label">Total Referrals</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-md-3">
            <div class="stats-card card border-0 shadow-sm">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="stats-icon bg-info">
                    <i class="bi bi-star-fill text-white"></i>
                  </div>
                  <div class="ms-3">
                    <div class="stats-number"><?= ucfirst($user_info['subscription']) ?></div>
                    <div class="stats-label">Current Plan</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
          <div class="col-md-8">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white">
                <h5 class="mb-0">Traffic Overview (Last 7 Days)</h5>
              </div>
              <div class="card-body">
                <canvas id="trafficChart" height="100"></canvas>
              </div>
            </div>
          </div>
          
          <div class="col-md-4">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white">
                <h5 class="mb-0">Quick Actions</h5>
              </div>
              <div class="card-body d-grid gap-2">
                <a href="exchange.php" class="btn btn-primary">
                  <i class="bi bi-arrow-left-right"></i> Start Exchanging
                </a>
                <a href="profile.php" class="btn btn-outline-primary">
                  <i class="bi bi-plus-circle"></i> Add Website
                </a>
                <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#referralModal">
                  <i class="bi bi-people"></i> Invite Friends
                </button>
                <a href="upgrade.php" class="btn btn-outline-warning">
                  <i class="bi bi-star"></i> Upgrade Plan
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Websites Table -->
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white">
            <h5 class="mb-0">Your Websites</h5>
          </div>
          <div class="card-body">
            <?php if ($websites->num_rows > 0): ?>
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Website URL</th>
                      <th>Niche</th>
                      <th>Country</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while($site = $websites->fetch_assoc()): ?>
                      <tr>
                        <td><a href="<?= $site['url'] ?>" target="_blank"><?= $site['url'] ?></a></td>
                        <td><span class="badge bg-secondary"><?= $site['niche'] ?></span></td>
                        <td><span class="badge bg-info"><?= $site['country'] ?></span></td>
                        <td><span class="badge bg-success"><?= ucfirst($site['status']) ?></span></td>
                        <td>
                          <button class="btn btn-sm btn-outline-primary">Edit</button>
                          <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div class="text-center py-4">
                <i class="bi bi-globe display-1 text-muted"></i>
                <h5 class="text-muted">No websites added yet</h5>
                <p class="text-muted">Add your first website to start getting traffic</p>
                <a href="profile.php" class="btn btn-primary">Add Website</a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Referral Modal -->
<div class="modal fade" id="referralModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">
          <i class="bi bi-people"></i> Referral Program
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-4">
          <h4>Earn 5 Credits for Each Referral!</h4>
          <p class="text-muted">Share your referral link and both you and your friend get bonus credits when they sign up.</p>
        </div>
        
        <div class="row text-center mb-4">
          <div class="col-6">
            <div class="referral-stat">
              <h3 class="text-success"><?= $user_info['total_referrals'] ?: 0 ?></div>
              <small>Total Referrals</small>
            </div>
          </div>
          <div class="col-6">
            <div class="referral-stat">
              <h3 class="text-warning"><?= $user_info['referral_earnings'] ?: 0 ?></div>
              <small>Credits Earned</small>
            </div>
          </div>
        </div>
        
        <div class="form-group">
          <label class="form-label">Your Referral Link:</label>
          <div class="input-group">
            <input type="text" class="form-control" id="referralLink" value="<?= $referral_link ?>" readonly>
            <button class="btn btn-outline-secondary" type="button" onclick="copyReferralLink()">
              <i class="bi bi-clipboard"></i> Copy
            </button>
          </div>
        </div>
        
        <div class="social-share mt-3">
          <p class="small text-muted">Share via:</p>
          <div class="btn-group w-100">
            <button class="btn btn-outline-primary" onclick="shareWhatsApp()">
              <i class="bi bi-whatsapp"></i> WhatsApp
            </button>
            <button class="btn btn-outline-info" onclick="shareTwitter()">
              <i class="bi bi-twitter"></i> Twitter
            </button>
            <button class="btn btn-outline-dark" onclick="shareEmail()">
              <i class="bi bi-envelope"></i> Email
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Traffic Chart
const ctx = document.getElementById('trafficChart').getContext('2d');
const trafficData = <?= json_encode($weekly_data) ?>;
const labels = [];
for (let i = 6; i >= 0; i--) {
  const date = new Date();
  date.setDate(date.getDate() - i);
  labels.push(date.toLocaleDateString('en-US', {weekday: 'short'}));
}

const trafficChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Website Visits',
            data: trafficData,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Referral functions
function copyReferralLink() {
  const linkField = document.getElementById('referralLink');
  linkField.select();
  document.execCommand('copy');
  
  // Show feedback
  const btn = event.target.closest('button');
  const originalText = btn.innerHTML;
  btn.innerHTML = '<i class="bi bi-check"></i> Copied!';
  setTimeout(() => {
    btn.innerHTML = originalText;
  }, 2000);
}

function shareWhatsApp() {
  const link = document.getElementById('referralLink').value;
  const message = `Join me on Webspark and get free website traffic! We both get bonus credits when you sign up: ${link}`;
  window.open(`https://wa.me/?text=${encodeURIComponent(message)}`, '_blank');
}

function shareTwitter() {
  const link = document.getElementById('referralLink').value;
  const message = `Join me on Webspark for free website traffic! 🚀 ${link}`;
  window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(message)}`, '_blank');
}

function shareEmail() {
  const link = document.getElementById('referralLink').value;
  const subject = 'Join Webspark - Free Website Traffic';
  const body = `Hi!\\n\\nI wanted to share Webspark with you - it's a great platform for getting real visitors to your website.\\n\\nWhen you sign up using my referral link, we both get bonus credits to start with:\\n${link}\\n\\nCheck it out!`;
  window.location.href = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
}
</script>
</body>
</html>

<?php
include "config.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user info and credits
$stmt = $mysqli->prepare("SELECT credits, subscription FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();

if ($user_info['credits'] <= 0) {
    $no_credits_msg = true;
}

// Get available websites for exchange (excluding user's own websites)
$stmt = $mysqli->prepare("
    SELECT w.*, u.username, u.country as user_country,
           COALESCE(wa.visits_count, 0) as total_visits,
           COALESCE(wa.unique_visitors, 0) as unique_visitors
    FROM websites w 
    JOIN users u ON w.user_id = u.id 
    LEFT JOIN website_analytics wa ON w.id = wa.website_id AND wa.date = CURDATE()
    WHERE w.user_id != ? AND w.status = 'active' 
    ORDER BY RAND() 
    LIMIT 20
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$available_websites = $stmt->get_result();

// Handle visit processing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['start_visit'])) {
    $website_id = intval($_POST['website_id']);
    
    // Verify user has credits
    if ($user_info['credits'] > 0) {
        // Verify website exists and is not user's own
        $stmt = $mysqli->prepare("SELECT * FROM websites WHERE id=? AND user_id != ? AND status='active'");
        $stmt->bind_param("ii", $website_id, $user_id);
        $stmt->execute();
        $website = $stmt->get_result()->fetch_assoc();
        
        if ($website) {
            // Store visit session
            $_SESSION['current_visit'] = [
                'website_id' => $website_id,
                'start_time' => time(),
                'required_time' => 30 // 30 seconds minimum
            ];
            
            header("Location: visit.php?id=" . $website_id);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Traffic Exchange - Webspark</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <style>
    .exchange-card {
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      cursor: pointer;
    }
    .exchange-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    .website-thumbnail {
      width: 100%;
      height: 150px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 2rem;
      border-radius: 8px;
    }
    .credits-indicator {
      position: fixed;
      top: 80px;
      right: 20px;
      z-index: 1000;
    }
    .filter-section {
      background: #f8f9fa;
      border-radius: 10px;
      padding: 1.5rem;
      margin-bottom: 2rem;
    }
    .website-stats {
      font-size: 0.875rem;
      color: #6c757d;
    }
    .loading-spinner {
      display: none;
    }
    .lazy-load {
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    .lazy-load.loaded {
      opacity: 1;
    }
  </style>
</head>
<body class="dashboard-page">
<?php include "navbar.php"; ?>

<!-- Credits Indicator -->
<div class="credits-indicator">
  <div class="card shadow-sm">
    <div class="card-body p-3 text-center">
      <div class="credits-display">
        <i class="bi bi-coin text-warning"></i>
        <strong><?= $user_info['credits'] ?></strong> Credits
      </div>
      <div class="small text-muted">Available</div>
    </div>
  </div>
</div>

<div class="container py-4">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2><i class="bi bi-arrow-left-right text-primary"></i> Traffic Exchange</h2>
      <p class="text-muted mb-0">Visit websites to earn credits, then use credits to get visitors</p>
    </div>
    <div class="text-end">
      <a href="dashboard.php" class="btn btn-outline-primary">
        <i class="bi bi-speedometer2"></i> Dashboard
      </a>
      <a href="profile.php" class="btn btn-outline-success ms-2">
        <i class="bi bi-plus-circle"></i> Add Website
      </a>
    </div>
  </div>

  <?php if (isset($no_credits_msg)): ?>
  <div class="alert alert-warning" role="alert">
    <h5><i class="bi bi-exclamation-triangle"></i> No Credits Available</h5>
    <p class="mb-3">You need credits to start visiting websites. Here's how to get them:</p>
    <div class="row">
      <div class="col-md-4">
        <div class="text-center p-3 border rounded">
          <i class="bi bi-gift text-success" style="font-size: 2rem;"></i>
          <h6 class="mt-2">Invite Friends</h6>
          <p class="small text-muted">Get 5 credits per referral</p>
          <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#referralModal">Invite Now</button>
        </div>
      </div>
      <div class="col-md-4">
        <div class="text-center p-3 border rounded">
          <i class="bi bi-star text-warning" style="font-size: 2rem;"></i>
          <h6 class="mt-2">Upgrade Plan</h6>
          <p class="small text-muted">Get monthly credits</p>
          <a href="upgrade.php" class="btn btn-sm btn-warning">Upgrade</a>
        </div>
      </div>
      <div class="col-md-4">
        <div class="text-center p-3 border rounded">
          <i class="bi bi-coin text-primary" style="font-size: 2rem;"></i>
          <h6 class="mt-2">Buy Credits</h6>
          <p class="small text-muted">Purchase credit packages</p>
          <a href="upgrade.php#credits" class="btn btn-sm btn-primary">Buy Now</a>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Filter Section -->
  <div class="filter-section">
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label small fw-semibold">Filter by Niche</label>
        <select id="nicheFilter" class="form-select form-select-sm">
          <option value="">All Niches</option>
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
        <label class="form-label small fw-semibold">Filter by Country</label>
        <select id="countryFilter" class="form-select form-select-sm">
          <option value="">All Countries</option>
          <option value="Australia">Australia</option>
          <option value="USA">USA</option>
          <option value="UK">UK</option>
          <option value="Canada">Canada</option>
          <option value="India">India</option>
          <option value="Germany">Germany</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold">Sort By</label>
        <select id="sortFilter" class="form-select form-select-sm">
          <option value="random">Random</option>
          <option value="newest">Newest First</option>
          <option value="popular">Most Popular</option>
          <option value="least_visited">Least Visited</option>
        </select>
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <button class="btn btn-primary btn-sm w-100" onclick="applyFilters()">
          <i class="bi bi-funnel"></i> Apply Filters
        </button>
      </div>
    </div>
  </div>

  <!-- Exchange Grid -->
  <div class="row g-4" id="websitesGrid">
    <?php if ($available_websites->num_rows > 0): ?>
      <?php while($website = $available_websites->fetch_assoc()): ?>
      <div class="col-lg-4 col-md-6 website-item lazy-load" 
           data-niche="<?= $website['niche'] ?>" 
           data-country="<?= $website['country'] ?>">
        <div class="card exchange-card h-100 shadow-sm border-0">
          <div class="website-thumbnail">
            <i class="bi bi-globe"></i>
          </div>
          <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <h6 class="card-title mb-0 text-truncate" title="<?= htmlspecialchars($website['url']) ?>">
                <?= parse_url($website['url'], PHP_URL_HOST) ?>
              </h6>
              <span class="badge bg-primary"><?= $website['niche'] ?></span>
            </div>
            
            <p class="card-text small text-muted mb-3">
              <i class="bi bi-person"></i> By <?= $website['username'] ?> 
              <span class="ms-2"><i class="bi bi-geo-alt"></i> <?= $website['country'] ?></span>
            </p>
            
            <div class="website-stats mb-3">
              <div class="row text-center">
                <div class="col-6">
                  <div class="stat-number small fw-bold"><?= $website['total_visits'] ?></div>
                  <div class="stat-label small">Visits Today</div>
                </div>
                <div class="col-6">
                  <div class="stat-number small fw-bold"><?= $website['unique_visitors'] ?></div>
                  <div class="stat-label small">Unique Visitors</div>
                </div>
              </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center">
              <div class="visit-info">
                <small class="text-success fw-semibold">
                  <i class="bi bi-coin"></i> Earn 1 Credit
                </small>
              </div>
              <?php if ($user_info['credits'] > 0): ?>
              <form method="post" class="d-inline">
                <input type="hidden" name="website_id" value="<?= $website['id'] ?>">
                <button type="submit" name="start_visit" class="btn btn-success btn-sm">
                  <i class="bi bi-play-fill"></i> Visit (30s)
                </button>
              </form>
              <?php else: ?>
              <button class="btn btn-secondary btn-sm" disabled>
                <i class="bi bi-lock"></i> No Credits
              </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
    <?php else: ?>
    <div class="col-12">
      <div class="text-center py-5">
        <i class="bi bi-globe display-1 text-muted"></i>
        <h5 class="text-muted mt-3">No websites available for exchange</h5>
        <p class="text-muted">Check back later or add your own website to start the exchange</p>
        <a href="profile.php" class="btn btn-primary">Add Your Website</a>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Load More Button -->
  <div class="text-center mt-4">
    <button class="btn btn-outline-primary" id="loadMoreBtn" onclick="loadMoreWebsites()">
      <span class="loading-spinner spinner-border spinner-border-sm me-2" role="status"></span>
      Load More Websites
    </button>
  </div>
</div>

<!-- Referral Modal -->
<div class="modal fade" id="referralModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Invite Friends & Earn Credits</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-4">
          <i class="bi bi-gift display-4 text-success"></i>
          <h4 class="mt-2">Get 5 Credits Per Referral!</h4>
          <p class="text-muted">Share your referral link and earn credits when friends join</p>
        </div>
        
        <?php
        $referral_link = "http://localhost/webspark/register.php?ref=" . base64_encode($user_id);
        ?>
        
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
          <p class="small text-muted">Quick Share:</p>
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
let currentPage = 1;
let isLoading = false;

// Lazy loading implementation
document.addEventListener('DOMContentLoaded', function() {
  const lazyElements = document.querySelectorAll('.lazy-load');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('loaded');
        observer.unobserve(entry.target);
      }
    });
  });
  
  lazyElements.forEach(element => observer.observe(element));
});

function applyFilters() {
  const niche = document.getElementById('nicheFilter').value;
  const country = document.getElementById('countryFilter').value;
  const sort = document.getElementById('sortFilter').value;
  
  const items = document.querySelectorAll('.website-item');
  
  items.forEach(item => {
    const itemNiche = item.dataset.niche;
    const itemCountry = item.dataset.country;
    
    let show = true;
    
    if (niche && itemNiche !== niche) show = false;
    if (country && itemCountry !== country) show = false;
    
    item.style.display = show ? 'block' : 'none';
  });
}

function loadMoreWebsites() {
  if (isLoading) return;
  
  isLoading = true;
  const btn = document.getElementById('loadMoreBtn');
  const spinner = btn.querySelector('.loading-spinner');
  
  btn.disabled = true;
  spinner.style.display = 'inline-block';
  
  fetch('load_more_websites.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `page=${currentPage + 1}&user_id=<?= $user_id ?>`
  })
  .then(response => response.json())
  .then(data => {
    if (data.success && data.websites.length > 0) {
      const grid = document.getElementById('websitesGrid');
      data.websites.forEach(website => {
        const websiteHTML = createWebsiteCard(website);
        grid.insertAdjacentHTML('beforeend', websiteHTML);
      });
      currentPage++;
    } else {
      btn.textContent = 'No more websites';
      btn.disabled = true;
    }
  })
  .catch(error => {
    console.error('Error:', error);
  })
  .finally(() => {
    isLoading = false;
    btn.disabled = false;
    spinner.style.display = 'none';
  });
}

function createWebsiteCard(website) {
  return `
    <div class="col-lg-4 col-md-6 website-item lazy-load" data-niche="${website.niche}" data-country="${website.country}">
      <div class="card exchange-card h-100 shadow-sm border-0">
        <div class="website-thumbnail">
          <i class="bi bi-globe"></i>
        </div>
        <div class="card-body p-3">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="card-title mb-0 text-truncate" title="${website.url}">
              ${new URL(website.url).hostname}
            </h6>
            <span class="badge bg-primary">${website.niche}</span>
          </div>
          
          <p class="card-text small text-muted mb-3">
            <i class="bi bi-person"></i> By ${website.username} 
            <span class="ms-2"><i class="bi bi-geo-alt"></i> ${website.country}</span>
          </p>
          
          <div class="d-flex justify-content-between align-items-center">
            <small class="text-success fw-semibold">
              <i class="bi bi-coin"></i> Earn 1 Credit
            </small>
            <form method="post" class="d-inline">
              <input type="hidden" name="website_id" value="${website.id}">
              <button type="submit" name="start_visit" class="btn btn-success btn-sm">
                <i class="bi bi-play-fill"></i> Visit (30s)
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  `;
}

// Referral functions
function copyReferralLink() {
  const linkField = document.getElementById('referralLink');
  linkField.select();
  document.execCommand('copy');
  
  const btn = event.target.closest('button');
  const originalText = btn.innerHTML;
  btn.innerHTML = '<i class="bi bi-check"></i> Copied!';
  setTimeout(() => {
    btn.innerHTML = originalText;
  }, 2000);
}

function shareWhatsApp() {
  const link = document.getElementById('referralLink').value;
  const message = `Join Webspark and get free website traffic! We both get bonus credits: ${link}`;
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
  const body = `Hi!\\n\\nI wanted to share Webspark with you - it's a great platform for getting real visitors to your website.\\n\\nWhen you sign up using my referral link, we both get bonus credits:\\n${link}\\n\\nCheck it out!`;
  window.location.href = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
}
</script>
</body>
</html>

<?php
include "config.php";
if (!isset($_SESSION['user_id']) || !isset($_SESSION['current_visit'])) {
    header("Location: exchange.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$visit_session = $_SESSION['current_visit'];
$website_id = $visit_session['website_id'];

// Get website details
$stmt = $mysqli->prepare("SELECT w.*, u.username FROM websites w JOIN users u ON w.user_id = u.id WHERE w.id=?");
$stmt->bind_param("i", $website_id);
$stmt->execute();
$website = $stmt->get_result()->fetch_assoc();

if (!$website) {
    header("Location: exchange.php");
    exit;
}

$required_time = $visit_session['required_time'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Visiting: <?= parse_url($website['url'], PHP_URL_HOST) ?> - Webspark</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .visit-container {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 1rem;
      z-index: 9999;
      box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }
    .visit-frame {
      margin-top: 80px;
      height: calc(100vh - 80px);
      border: none;
      width: 100%;
    }
    .progress-ring {
      transform: rotate(-90deg);
    }
    .progress-ring__circle {
      stroke-dasharray: 283;
      stroke-dashoffset: 283;
      transition: stroke-dashoffset 1s linear;
    }
    .visit-controls {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .timer-display {
      font-size: 1.5rem;
      font-weight: bold;
    }
    .website-info h5 {
      margin: 0;
      font-size: 1.1rem;
    }
    .complete-button {
      opacity: 0.5;
      cursor: not-allowed;
    }
    .complete-button.enabled {
      opacity: 1;
      cursor: pointer;
    }
  </style>
</head>
<body>
  <div class="visit-container">
    <div class="container-fluid">
      <div class="visit-controls">
        <div class="website-info">
          <h5><i class="bi bi-globe"></i> <?= parse_url($website['url'], PHP_URL_HOST) ?></h5>
          <small class="opacity-75">By <?= $website['username'] ?> • <?= $website['niche'] ?></small>
        </div>
        
        <div class="timer-section d-flex align-items-center">
          <div class="progress-ring me-3">
            <svg width="50" height="50">
              <circle class="progress-ring__circle" 
                      stroke="rgba(255,255,255,0.3)" 
                      stroke-width="3" 
                      fill="transparent" 
                      r="45" 
                      cx="25" 
                      cy="25"/>
              <circle class="progress-ring__circle" 
                      id="progressCircle"
                      stroke="white" 
                      stroke-width="3" 
                      fill="transparent" 
                      r="45" 
                      cx="25" 
                      cy="25"/>
            </svg>
          </div>
          <div class="timer-info text-center">
            <div class="timer-display" id="timerDisplay"><?= $required_time ?></div>
            <small>seconds left</small>
          </div>
        </div>
        
        <div class="visit-actions">
          <button class="btn btn-light complete-button me-2" id="completeBtn" disabled>
            <i class="bi bi-check-circle"></i> Complete Visit
          </button>
          <a href="exchange.php" class="btn btn-outline-light">
            <i class="bi bi-x-circle"></i> Skip
          </a>
        </div>
      </div>
    </div>
  </div>
  
  <iframe src="<?= $website['url'] ?>" class="visit-frame" id="websiteFrame"></iframe>
  
  <!-- Visit completion overlay -->
  <div id="completionOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-none" style="background: rgba(0,0,0,0.8); z-index: 10000;">
    <div class="d-flex align-items-center justify-content-center h-100">
      <div class="card shadow-lg">
        <div class="card-body text-center p-4">
          <i class="bi bi-check-circle-fill text-success display-1"></i>
          <h4 class="mt-3">Visit Completed!</h4>
          <p class="text-muted">You've earned 1 credit for visiting this website</p>
          <div class="btn-group mt-3">
            <a href="exchange.php" class="btn btn-primary">
              <i class="bi bi-arrow-left-right"></i> Continue Exchange
            </a>
            <a href="dashboard.php" class="btn btn-outline-primary">
              <i class="bi bi-speedometer2"></i> Dashboard
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    let timeLeft = <?= $required_time ?>;
    let startTime = Date.now();
    let timerInterval;
    let visitCompleted = false;
    
    // Timer functionality
    function startTimer() {
      timerInterval = setInterval(() => {
        timeLeft--;
        updateDisplay();
        
        if (timeLeft <= 0) {
          completeVisit();
        }
      }, 1000);
    }
    
    function updateDisplay() {
      document.getElementById('timerDisplay').textContent = timeLeft;
      
      // Update progress ring
      const circumference = 2 * Math.PI * 45;
      const progress = ((<?= $required_time ?> - timeLeft) / <?= $required_time ?>) * circumference;
      document.getElementById('progressCircle').style.strokeDashoffset = circumference - progress;
      
      // Enable complete button when timer is done
      if (timeLeft <= 0) {
        const completeBtn = document.getElementById('completeBtn');
        completeBtn.disabled = false;
        completeBtn.classList.add('enabled');
        completeBtn.onclick = completeVisit;
      }
    }
    
    function completeVisit() {
      if (visitCompleted) return;
      visitCompleted = true;
      
      clearInterval(timerInterval);
      
      const dwellTime = Math.floor((Date.now() - startTime) / 1000);
      
      // Send visit completion to server
      fetch('process_visit.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `website_id=<?= $website_id ?>&dwell_time=${dwellTime}&completed=1`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          document.getElementById('completionOverlay').classList.remove('d-none');
        } else {
          alert('Error completing visit: ' + data.error);
          window.location.href = 'exchange.php';
        }
      })
      .catch(error => {
        console.error('Error:', error);
        window.location.href = 'exchange.php';
      });
    }
    
    // Prevent page reload/close during visit
    window.addEventListener('beforeunload', function(e) {
      if (!visitCompleted && timeLeft > 0) {
        e.preventDefault();
        e.returnValue = 'Are you sure you want to leave? Your visit will not be counted.';
      }
    });
    
    // Track user activity
    let userActive = true;
    let inactiveTime = 0;
    
    document.addEventListener('visibilitychange', function() {
      if (document.hidden) {
        userActive = false;
      } else {
        userActive = true;
        inactiveTime = 0;
      }
    });
    
    // Check for iframe load errors
    document.getElementById('websiteFrame').addEventListener('error', function() {
      alert('Website failed to load. You will be redirected back to exchange.');
      window.location.href = 'exchange.php';
    });
    
    // Start the timer when page loads
    window.addEventListener('load', function() {
      startTimer();
    });
  </script>
</body>
</html>

<?php
include "config.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $mysqli->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();

$success_msg = "";
if (isset($_GET['success']) && $_GET['success'] == 'payment') {
    $success_msg = '<div class="alert alert-success">Payment successful! Your plan has been upgraded.</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Upgrade Plan - Webspark</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <script src="https://js.stripe.com/v3/"></script>
</head>
<body class="dashboard-page">
<?php include "navbar.php"; ?>

<div class="container py-5">
  <?= $success_msg ?>
  
  <!-- Page Header -->
  <div class="text-center mb-5">
    <h2 class="display-5 fw-bold">Choose Your Plan</h2>
    <p class="lead text-muted">Upgrade your account to unlock more features and credits</p>
    <div class="current-plan">
      Current Plan: <span class="badge bg-primary fs-6"><?= ucfirst($user_info['subscription']) ?> Plan</span> | 
      Credits: <span class="badge bg-success fs-6"><?= $user_info['credits'] ?> Credits</span>
    </div>
  </div>

  <!-- Pricing Plans -->
  <div class="row g-4 justify-content-center">
    <div class="col-lg-4 col-md-6">
      <div class="pricing-card">
        <div class="card h-100 border-0 shadow <?= $user_info['subscription'] == 'free' ? 'border-primary' : '' ?>">
          <div class="card-header bg-light text-center py-4">
            <?php if ($user_info['subscription'] == 'free'): ?>
              <div class="badge bg-primary mb-2">Current Plan</div>
            <?php endif; ?>
            <h4>Free</h4>
            <div class="price-display">
              <span class="price">$0</span>
              <span class="period">/month</span>
            </div>
          </div>
          <div class="card-body p-4">
            <ul class="feature-list">
              <li><i class="bi bi-check text-success"></i> 10 Free Credits on Signup</li>
              <li><i class="bi bi-check text-success"></i> Basic Analytics Dashboard</li>
              <li><i class="bi bi-check text-success"></i> Up to 3 Websites</li>
              <li><i class="bi bi-check text-success"></i> Standard Support</li>
              <li><i class="bi bi-x text-danger"></i> Geo-targeting</li>
              <li><i class="bi bi-x text-danger"></i> Advanced Analytics</li>
              <li><i class="bi bi-x text-danger"></i> Priority Support</li>
            </ul>
          </div>
          <div class="card-footer bg-light text-center">
            <?php if ($user_info['subscription'] == 'free'): ?>
              <button class="btn btn-outline-secondary" disabled>Current Plan</button>
            <?php else: ?>
              <button class="btn btn-outline-primary" onclick="downgradePlan('free')">Downgrade</button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4 col-md-6">
      <div class="pricing-card featured-plan">
        <div class="card h-100 border-primary shadow-lg position-relative">
          <?php if ($user_info['subscription'] == 'premium'): ?>
            <div class="badge bg-primary position-absolute top-0 start-50 translate-middle">Current Plan</div>
          <?php else: ?>
            <div class="popular-badge">Most Popular</div>
          <?php endif; ?>
          <div class="card-header bg-primary text-white text-center py-4">
            <h4>Premium</h4>
            <div class="price-display">
              <span class="price">$19</span>
              <span class="period">/month</span>
            </div>
          </div>
          <div class="card-body p-4">
            <ul class="feature-list">
              <li><i class="bi bi-check text-success"></i> 100 Credits Monthly</li>
              <li><i class="bi bi-check text-success"></i> Advanced Analytics</li>
              <li><i class="bi bi-check text-success"></i> Up to 10 Websites</li>
              <li><i class="bi bi-check text-success"></i> Geo-targeting Options</li>
              <li><i class="bi bi-check text-success"></i> Priority Support</li>
              <li><i class="bi bi-check text-success"></i> Custom Referrer Settings</li>
              <li><i class="bi bi-check text-success"></i> Email Notifications</li>
            </ul>
          </div>
          <div class="card-footer bg-light text-center">
            <?php if ($user_info['subscription'] == 'premium'): ?>
              <button class="btn btn-outline-secondary" disabled>Current Plan</button>
            <?php else: ?>
              <button class="btn btn-primary btn-lg" onclick="selectPlan('premium', 1900)">
                <i class="bi bi-star"></i> Upgrade Now
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4 col-md-6">
      <div class="pricing-card">
        <div class="card h-100 border-0 shadow <?= $user_info['subscription'] == 'pro' ? 'border-success' : '' ?>">
          <div class="card-header bg-dark text-white text-center py-4">
            <?php if ($user_info['subscription'] == 'pro'): ?>
              <div class="badge bg-success mb-2">Current Plan</div>
            <?php endif; ?>
            <h4>Pro</h4>
            <div class="price-display">
              <span class="price">$49</span>
              <span class="period">/month</span>
            </div>
          </div>
          <div class="card-body p-4">
            <ul class="feature-list">
              <li><i class="bi bi-check text-success"></i> 500 Credits Monthly</li>
              <li><i class="bi bi-check text-success"></i> Premium Analytics</li>
              <li><i class="bi bi-check text-success"></i> Unlimited Websites</li>
              <li><i class="bi bi-check text-success"></i> Advanced Targeting</li>
              <li><i class="bi bi-check text-success"></i> 24/7 VIP Support</li>
              <li><i class="bi bi-check text-success"></i> API Access</li>
              <li><i class="bi bi-check text-success"></i> White-label Options</li>
              <li><i class="bi bi-check text-success"></i> Custom Analytics</li>
            </ul>
          </div>
          <div class="card-footer bg-light text-center">
            <?php if ($user_info['subscription'] == 'pro'): ?>
              <button class="btn btn-outline-secondary" disabled>Current Plan</button>
            <?php else: ?>
              <button class="btn btn-dark btn-lg" onclick="selectPlan('pro', 4900)">
                <i class="bi bi-rocket"></i> Go Pro
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Credit Packages -->
  <div class="row mt-5">
    <div class="col-12">
      <div class="text-center mb-4">
        <h3>Need More Credits?</h3>
        <p class="text-muted">Buy additional credits without changing your plan</p>
      </div>
      
      <div class="row g-3 justify-content-center">
        <div class="col-md-3 col-sm-6">
          <div class="credit-package">
            <div class="card text-center h-100 shadow-sm">
              <div class="card-body">
                <i class="bi bi-coin text-warning display-4 mb-3"></i>
                <h5>50 Credits</h5>
                <div class="price mb-3">$5</div>
                <button class="btn btn-outline-warning btn-sm" onclick="buyCreditPackage(50, 500)">Buy Now</button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
          <div class="credit-package">
            <div class="card text-center h-100 shadow-sm">
              <div class="card-body">
                <i class="bi bi-coin text-warning display-4 mb-3"></i>
                <h5>200 Credits</h5>
                <div class="price mb-3">$15</div>
                <div class="savings-badge badge bg-success mb-2">Save $5</div>
                <button class="btn btn-outline-warning btn-sm" onclick="buyCreditPackage(200, 1500)">Buy Now</button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
          <div class="credit-package">
            <div class="card text-center h-100 shadow-sm">
              <div class="card-body">
                <i class="bi bi-coin text-warning display-4 mb-3"></i>
                <h5>500 Credits</h5>
                <div class="price mb-3">$30</div>
                <div class="savings-badge badge bg-success mb-2">Save $20</div>
                <button class="btn btn-outline-warning btn-sm" onclick="buyCreditPackage(500, 3000)">Buy Now</button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
          <div class="credit-package">
            <div class="card text-center h-100 shadow-sm">
              <div class="card-body">
                <i class="bi bi-coin text-warning display-4 mb-3"></i>
                <h5>1000 Credits</h5>
                <div class="price mb-3">$50</div>
                <div class="savings-badge badge bg-success mb-2">Save $50</div>
                <button class="btn btn-warning btn-sm" onclick="buyCreditPackage(1000, 5000)">Best Value</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Complete Your Purchase</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-4">
          <h4 id="selectedPlan">Premium Plan</h4>
          <p class="text-muted" id="planDescription">You're upgrading to Premium for $19/month</p>
        </div>
        
        <!-- Payment Methods -->
        <div class="row">
          <div class="col-md-6">
            <div class="card payment-method-card" onclick="selectPaymentMethod('stripe')">
              <div class="card-body text-center">
                <i class="bi bi-credit-card display-4 text-primary"></i>
                <h5 class="mt-2">Credit Card</h5>
                <p class="text-muted">Visa, Mastercard, Amex</p>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card payment-method-card" onclick="selectPaymentMethod('paypal')">
              <div class="card-body text-center">
                <i class="bi bi-paypal display-4 text-warning"></i>
                <h5 class="mt-2">PayPal</h5>
                <p class="text-muted">Pay with PayPal account</p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Stripe Payment Form -->
        <div id="stripePaymentForm" class="payment-form" style="display: none;">
          <form id="payment-form">
            <div id="card-element" class="form-control mb-3" style="padding: 12px;">
              <!-- Stripe Elements will create form elements here -->
            </div>
            <div id="card-errors" role="alert" class="text-danger mb-3"></div>
            <button type="submit" id="submit-payment" class="btn btn-primary w-100">
              <span id="button-text">Complete Payment</span>
              <div id="spinner" class="spinner-border spinner-border-sm d-none ms-2"></div>
            </button>
          </form>
        </div>
        
        <!-- PayPal Payment Form -->
        <div id="paypalPaymentForm" class="payment-form text-center" style="display: none;">
          <div id="paypal-button-container"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Stripe initialization
const stripe = Stripe('<?= STRIPE_PUBLISHABLE_KEY ?>');
const elements = stripe.elements();
let selectedPlanType = '';
let selectedAmount = 0;

// Create card element
const card = elements.create('card', {
  style: {
    base: {
      fontSize: '16px',
      color: '#424770',
      '::placeholder': {
        color: '#aab7c4',
      },
    },
  },
});

let currentPaymentMethod = 'stripe';

function selectPlan(planName, amount) {
  selectedPlanType = planName;
  selectedAmount = amount;
  
  document.getElementById('selectedPlan').textContent = planName.charAt(0).toUpperCase() + planName.slice(1) + ' Plan';
  document.getElementById('planDescription').textContent = `You're upgrading to ${planName} for $${amount/100}/month`;
  
  new bootstrap.Modal(document.getElementById('paymentModal')).show();
  
  // Mount card element when modal is shown
  setTimeout(() => {
    if (!card._mounted) {
      card.mount('#card-element');
    }
  }, 500);
}

function buyCreditPackage(credits, amount) {
  selectedPlanType = 'credits';
  selectedAmount = amount;
  
  document.getElementById('selectedPlan').textContent = credits + ' Credits Package';
  document.getElementById('planDescription').textContent = `You're purchasing ${credits} credits for $${amount/100}`;
  
  new bootstrap.Modal(document.getElementById('paymentModal')).show();
  
  setTimeout(() => {
    if (!card._mounted) {
      card.mount('#card-element');
    }
  }, 500);
}

function selectPaymentMethod(method) {
  currentPaymentMethod = method;
  
  // Hide all payment forms
  document.querySelectorAll('.payment-form').forEach(form => {
    form.style.display = 'none';
  });
  
  // Show selected payment form
  if (method === 'stripe') {
    document.getElementById('stripePaymentForm').style.display = 'block';
  } else if (method === 'paypal') {
    document.getElementById('paypalPaymentForm').style.display = 'block';
    // Initialize PayPal here if needed
  }
  
  // Update card styling
  document.querySelectorAll('.payment-method-card').forEach(card => {
    card.classList.remove('border-primary');
  });
  event.currentTarget.classList.add('border-primary');
}

// Handle form submission
const form = document.getElementById('payment-form');
form.addEventListener('submit', async (event) => {
  event.preventDefault();

  const {token, error} = await stripe.createToken(card);

  if (error) {
    document.getElementById('card-errors').textContent = error.message;
  } else {
    // Send token to server
    processStripePayment(token);
  }
});

function processStripePayment(token) {
  const submitButton = document.getElementById('submit-payment');
  const spinner = document.getElementById('spinner');
  const buttonText = document.getElementById('button-text');
  
  submitButton.disabled = true;
  spinner.classList.remove('d-none');
  buttonText.textContent = 'Processing...';
  
  // Create form data
  const formData = new FormData();
  formData.append('stripeToken', token.id);
  formData.append('plan', selectedPlanType);
  formData.append('amount', selectedAmount);
  
  fetch('payment_processing.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      window.location.href = 'upgrade.php?success=payment';
    } else {
      document.getElementById('card-errors').textContent = data.error;
      submitButton.disabled = false;
      spinner.classList.add('d-none');
      buttonText.textContent = 'Complete Payment';
    }
  })
  .catch(error => {
    console.error('Error:', error);
    document.getElementById('card-errors').textContent = 'Payment failed. Please try again.';
    submitButton.disabled = false;
    spinner.classList.add('d-none');
    buttonText.textContent = 'Complete Payment';
  });
}

// Select default payment method
document.addEventListener('DOMContentLoaded', function() {
  selectPaymentMethod('stripe');
});
</script>

<style>
.popular-badge {
  position: absolute;
  top: -15px;
  left: 50%;
  transform: translateX(-50%);
  background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
  color: #000;
  padding: 0.5rem 1.5rem;
  border-radius: 25px;
  font-size: 0.9rem;
  font-weight: bold;
  box-shadow: 0 3px 10px rgba(0,0,0,0.2);
}

.pricing-card:hover {
  transform: translateY(-10px);
  transition: transform 0.3s ease;
}

.payment-method-card {
  cursor: pointer;
  transition: all 0.3s ease;
}

.payment-method-card:hover {
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.feature-list {
  list-style: none;
  padding: 0;
}

.feature-list li {
  padding: 0.5rem 0;
  border-bottom: 1px solid #f8f9fa;
}

.feature-list li:last-child {
  border-bottom: none;
}
</style>
</body>
</html>

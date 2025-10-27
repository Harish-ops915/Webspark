<?php include "config.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Webspark - Traffic Exchange Network</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { 
      background-color: #f8f9fa; 
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .hero-section { 
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
      color: white; 
      min-height: 100vh; 
      display: flex; 
      align-items: center; 
    }
    
    .feature-card { 
      transition: transform 0.3s ease, box-shadow 0.3s ease; 
      padding: 2rem; 
      border-radius: 15px; 
      background: white;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      border: none;
      height: 100%;
    }
    
    .feature-card:hover { 
      transform: translateY(-10px); 
      box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    }
    
    .navbar-brand { 
      font-size: 1.5rem; 
      font-weight: bold; 
    }
    
    .step-circle {
      width: 70px;
      height: 70px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      font-weight: bold;
      margin-bottom: 1.5rem;
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .pricing-card { 
      transition: transform 0.3s ease; 
    }
    
    .pricing-card:hover { 
      transform: translateY(-10px); 
    }
    
    .pricing-card .price {
      font-size: 3rem;
      font-weight: bold;
      line-height: 1;
    }
    
    .pricing-card .price span {
      font-size: 1.2rem;
      color: #6c757d;
      font-weight: normal;
    }
    
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
    
    .stats-section .stat-number {
      font-size: 3rem;
      font-weight: bold;
      color: rgba(255,255,255,0.9);
    }
    
    .stats-section .stat-label {
      font-size: 1.1rem;
      color: rgba(255,255,255,0.7);
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    
    .btn-custom {
      padding: 0.75rem 2rem;
      font-weight: 600;
      border-radius: 50px;
      transition: all 0.3s ease;
    }
    
    .btn-custom:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .section-padding {
      padding: 5rem 0;
    }
    
    .navbar {
      backdrop-filter: blur(10px);
      background-color: rgba(13, 110, 253, 0.9) !important;
      box-shadow: 0 2px 20px rgba(0,0,0,0.1);
    }
    
    .feature-icon {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
  </style>
</head>
<body>
  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container">
      <a class="navbar-brand fw-bold" href="index.php">🚀 Webspark</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="#features">Features</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#how-it-works">How It Works</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#pricing">Pricing</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="register.php">Sign Up</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="login.php">Login</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="container">
      <div class="row align-items-center min-vh-100">
        <div class="col-lg-6">
          <h1 class="display-4 fw-bold mb-4">Grow Your Website With Real Visitors</h1>
          <p class="lead mb-4">Webspark helps website owners attract targeted, real human visitors using a proven traffic exchange system.</p>
          <div class="hero-buttons mb-5">
            <a href="register.php" class="btn btn-light btn-lg btn-custom me-3">Get Started Free</a>
            <a href="#features" class="btn btn-outline-light btn-lg btn-custom">Learn More</a>
          </div>
          
          <!-- Hero Stats -->
          <div class="stats-section">
            <div class="row text-center">
              <div class="col-4">
                <div class="stat-number">10K+</div>
                <div class="stat-label">Active Users</div>
              </div>
              <div class="col-4">
                <div class="stat-number">1M+</div>
                <div class="stat-label">Visits Exchanged</div>
              </div>
              <div class="col-4">
                <div class="stat-number">50K+</div>
                <div class="stat-label">Websites</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="hero-image text-center">
            <div class="dashboard-preview">
              <i class="bi bi-graph-up display-1 text-light opacity-75"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section id="features" class="section-padding">
    <div class="container">
      <div class="text-center mb-5">
        <h2 class="display-5 fw-bold">Powerful Features</h2>
        <p class="lead text-muted">Everything you need to grow your website traffic</p>
      </div>
      <div class="row g-4">
        <div class="col-md-4">
          <div class="feature-card text-center">
            <i class="bi bi-bar-chart-line-fill display-4 feature-icon mb-3"></i>
            <h4>Real-Time Analytics</h4>
            <p class="text-muted">Track visits, dwell time, and bounce rates with comprehensive dashboard analytics and detailed insights.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="feature-card text-center">
            <i class="bi bi-globe display-4 text-success mb-3"></i>
            <h4>Geo-targeted Traffic</h4>
            <p class="text-muted">Select traffic by specific regions and niches for maximum engagement and conversion rates.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="feature-card text-center">
            <i class="bi bi-award-fill display-4 text-warning mb-3"></i>
            <h4>Quality Assurance</h4>
            <p class="text-muted">Advanced anti-bot protection and session tracking ensure genuine human visitors to your website.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- How It Works -->
  <section id="how-it-works" class="section-padding bg-light">
    <div class="container">
      <div class="text-center mb-5">
        <h2 class="display-5 fw-bold">How It Works</h2>
        <p class="lead text-muted">Simple 4-step process to start getting traffic</p>
      </div>
      <div class="row text-center">
        <div class="col-md-3 mb-4">
          <div class="step-circle">1</div>
          <h5 class="mt-3">Register Free</h5>
          <p class="text-muted">Create your account and get started with free credits</p>
        </div>
        <div class="col-md-3 mb-4">
          <div class="step-circle">2</div>
          <h5 class="mt-3">Add Websites</h5>
          <p class="text-muted">Submit your websites for traffic exchange</p>
        </div>
        <div class="col-md-3 mb-4">
          <div class="step-circle">3</div>
          <h5 class="mt-3">Visit Others</h5>
          <p class="text-muted">Browse and visit other members' websites to earn credits</p>
        </div>
        <div class="col-md-3 mb-4">
          <div class="step-circle">4</div>
          <h5 class="mt-3">Get Visitors</h5>
          <p class="text-muted">Use earned credits to drive traffic to your sites</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Pricing Section -->
  <section id="pricing" class="section-padding">
    <div class="container">
      <div class="text-center mb-5">
        <h2 class="display-5 fw-bold">Choose Your Plan</h2>
        <p class="lead text-muted">Flexible pricing for every need</p>
      </div>
      <div class="row g-4 justify-content-center">
        <div class="col-md-4">
          <div class="pricing-card">
            <div class="card h-100 border-0 shadow-lg">
              <div class="card-header bg-light text-center py-4">
                <h4>Free</h4>
                <div class="price">$0<span>/month</span></div>
              </div>
              <div class="card-body">
                <ul class="list-unstyled">
                  <li class="py-2"><i class="bi bi-check text-success me-2"></i> 10 Free Credits</li>
                  <li class="py-2"><i class="bi bi-check text-success me-2"></i> Basic Analytics</li>
                  <li class="py-2"><i class="bi bi-check text-success me-2"></i> Up to 3 Websites</li>
                  <li class="py-2"><i class="bi bi-check text-success me-2"></i> Standard Support</li>
                  <li class="py-2"><i class="bi bi-x text-danger me-2"></i> Geo-targeting</li>
                  <li class="py-2"><i class="bi bi-x text-danger me-2"></i> Advanced Analytics</li>
                </ul>
              </div>
              <div class="card-footer bg-light text-center">
                <a href="register.php" class="btn btn-outline-primary btn-custom">Get Started</a>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-4">
          <div class="pricing-card">
            <div class="card h-100 border-primary shadow-lg position-relative">
              <div class="popular-badge">Most Popular</div>
              <div class="card-header bg-primary text-white text-center py-4">
                <h4>Premium</h4>
                <div class="price text-white">$19<span>/month</span></div>
              </div>
              <div class="card-body">
                <ul class="list-unstyled">
                  <li class="py-2"><i class="bi bi-check text-success me-2"></i> 100 Credits Monthly</li>
                  <li class="py-2"><i class="bi bi-check text-success me-2"></i> Advanced Analytics</li>
                  <li class="py-2"><i class="bi bi-check text-success me-2"></i> Up to 10 Websites</li>
                  <li class="py-2"><i class="bi bi-check text-success me-2"></i> Geo-targeting</li>
                  <li class="py-2"><i class="bi bi-check text-success me-2"></i> Priority Support</li>
                  <li class="py-2"><i class="bi bi-check text-success me-2"></i> Custom Referrer</li>
                </ul>
              </div>
              <div class="card-footer bg-light text-center">
                <a href="register.php" class="btn btn-primary btn-custom">Start Premium</a>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-4">
          <div class="pricing-card">
            <div class="card h-100 border-0 shadow-lg">
              <div class="card-header bg-dark text-white text-center py-4">
                <h4>Pro</h4>
                <div class="price text-white">$49<span>/month</span></div>
              </div>
              <div class="card-body">
                <ul class="list-unstyled">
                  <li class="py-2"><i class="bi bi-check text-success me-2"></i> 500 Credits Monthly</li>
                  <li class="py-2"><i class="bi bi-check text-success me-2"></i> Premium Analytics</li>
                  <li class="py-2"><i class="bi bi-check text-success me-2"></i> Unlimited Websites</li>
                  <li class="py-2"><i class="bi bi-check text-success me-2"></i> Advanced Targeting</li>
                  <li class="py-2"><i class="bi bi-check text-success me-2"></i> API Access</li>
                  <li class="py-2"><i class="bi bi-check text-success me-2"></i> 24/7 VIP Support</li>
                </ul>
              </div>
              <div class="card-footer bg-light text-center">
                <a href="register.php" class="btn btn-dark btn-custom">Go Pro</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Testimonials Section -->
  <section class="section-padding bg-light">
    <div class="container">
      <div class="text-center mb-5">
        <h2 class="display-5 fw-bold">What Our Users Say</h2>
        <p class="lead text-muted">Trusted by thousands of website owners worldwide</p>
      </div>
      <div class="row">
        <div class="col-md-4 mb-4">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
              <div class="mb-3">
                <i class="bi bi-star-fill text-warning"></i>
                <i class="bi bi-star-fill text-warning"></i>
                <i class="bi bi-star-fill text-warning"></i>
                <i class="bi bi-star-fill text-warning"></i>
                <i class="bi bi-star-fill text-warning"></i>
              </div>
              <p class="text-muted">"Webspark helped me increase my website traffic by 300% in just 2 months. The quality of visitors is excellent!"</p>
              <strong>Sarah Johnson</strong>
              <div class="small text-muted">Tech Blogger</div>
            </div>
          </div>
        </div>
        <div class="col-md-4 mb-4">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
              <div class="mb-3">
                <i class="bi bi-star-fill text-warning"></i>
                <i class="bi bi-star-fill text-warning"></i>
                <i class="bi bi-star-fill text-warning"></i>
                <i class="bi bi-star-fill text-warning"></i>
                <i class="bi bi-star-fill text-warning"></i>
              </div>
              <p class="text-muted">"The geo-targeting feature is amazing. I can get visitors from specific countries that match my target audience perfectly."</p>
              <strong>Mike Chen</strong>
              <div class="small text-muted">E-commerce Owner</div>
            </div>
          </div>
        </div>
        <div class="col-md-4 mb-4">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
              <div class="mb-3">
                <i class="bi bi-star-fill text-warning"></i>
                <i class="bi bi-star-fill text-warning"></i>
                <i class="bi bi-star-fill text-warning"></i>
                <i class="bi bi-star-fill text-warning"></i>
                <i class="bi bi-star-fill text-warning"></i>
              </div>
              <p class="text-muted">"Best traffic exchange platform I've ever used. Real visitors, detailed analytics, and excellent support team."</p>
              <strong>Emma Davis</strong>
              <div class="small text-muted">Digital Marketer</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="section-padding bg-primary text-white">
    <div class="container text-center">
      <h2 class="display-5 fw-bold mb-4">Ready to Boost Your Website Traffic?</h2>
      <p class="lead mb-4">Join thousands of website owners who are already growing their audience with Webspark</p>
      <div class="cta-buttons">
        <a href="register.php" class="btn btn-light btn-lg btn-custom me-3">Start Free Today</a>
        <a href="#pricing" class="btn btn-outline-light btn-lg btn-custom">View Pricing</a>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-dark text-white py-5">
    <div class="container">
      <div class="row">
        <div class="col-md-4 mb-4">
          <h5>🚀 Webspark</h5>
          <p>The leading traffic exchange network for growing your website audience with real, targeted visitors.</p>
          <div class="social-links">
            <a href="#" class="text-light me-3"><i class="bi bi-facebook"></i></a>
            <a href="#" class="text-light me-3"><i class="bi bi-twitter"></i></a>
            <a href="#" class="text-light me-3"><i class="bi bi-linkedin"></i></a>
            <a href="#" class="text-light"><i class="bi bi-instagram"></i></a>
          </div>
        </div>
        <div class="col-md-4 mb-4">
          <h5>Quick Links</h5>
          <ul class="list-unstyled">
            <li><a href="index.php" class="text-light text-decoration-none">Home</a></li>
            <li><a href="#features" class="text-light text-decoration-none">Features</a></li>
            <li><a href="#pricing" class="text-light text-decoration-none">Pricing</a></li>
            <li><a href="register.php" class="text-light text-decoration-none">Sign Up</a></li>
            <li><a href="#" class="text-light text-decoration-none">Terms of Service</a></li>
            <li><a href="#" class="text-light text-decoration-none">Privacy Policy</a></li>
          </ul>
        </div>
        <div class="col-md-4 mb-4">
          <h5>Contact Info</h5>
          <p><i class="bi bi-geo-alt me-2"></i> Sydney, Australia</p>
          <p><i class="bi bi-envelope me-2"></i> hello@webspark.com</p>
          <p><i class="bi bi-phone me-2"></i> +61 2 1234 5678</p>
          <p><i class="bi bi-clock me-2"></i> Mon-Fri 9AM-6PM AEST</p>
        </div>
      </div>
      <hr class="my-4">
      <div class="row align-items-center">
        <div class="col-md-6">
          <p class="mb-0">&copy; 2025 Webspark Traffic Network. All rights reserved.</p>
        </div>
        <div class="col-md-6 text-md-end">
          <small class="text-muted">Made with ❤️ in Sydney, Australia</small>
        </div>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });

    // Navbar background on scroll
    window.addEventListener('scroll', function() {
      const navbar = document.querySelector('.navbar');
      if (window.scrollY > 50) {
        navbar.style.backgroundColor = 'rgba(13, 110, 253, 0.95)';
      } else {
        navbar.style.backgroundColor = 'rgba(13, 110, 253, 0.9)';
      }
    });

    // Animation on scroll (simple)
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.animation = 'fadeInUp 0.6s ease forwards';
        }
      });
    }, observerOptions);

    // Observe feature cards and pricing cards
    document.querySelectorAll('.feature-card, .pricing-card').forEach(card => {
      observer.observe(card);
    });
  </script>

  <style>
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</body>
</html>

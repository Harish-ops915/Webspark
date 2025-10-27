<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">🚀 Webspark</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <?php if (isset($_SESSION['user_id'])): ?>
          <!-- Logged in user menu -->
          <li class="nav-item">
            <a class="nav-link" href="dashboard.php">
              <i class="bi bi-speedometer2"></i> Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="exchange.php">
              <i class="bi bi-arrow-left-right"></i> Exchange
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="profile.php">
              <i class="bi bi-globe"></i> My Websites
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="upgrade.php">
              <i class="bi bi-star"></i> Upgrade
            </a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle"></i> <?= $_SESSION['username'] ?>
            </a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Profile</a></li>
              <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear"></i> Settings</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <!-- Guest user menu -->
          <li class="nav-item">
            <a class="nav-link" href="index.php#features">Features</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="index.php#pricing">Pricing</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="register.php">
              <i class="bi bi-person-plus"></i> Sign Up
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="login.php">
              <i class="bi bi-box-arrow-in-right"></i> Login
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

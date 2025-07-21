<?php
session_start();

// Include database connection
include '../connect.php';

// Check if user is logged in and redirect based on role
if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header('Location: PHP/ADMIN/dashboardAdmin.php');
        exit();
    } elseif ($role === 'eo') {
        header('Location: PHP/EO/dashboardEO.php');
        exit();
    }
    // If role is 'player', stay on index.php (no redirect)
}

// Query untuk mengambil 4 turnamen terbaru berdasarkan id terbesar (turnamen terbaru)
$query = "SELECT *from turnamen ORDER BY id_turnamen";
$result = $conn->query($query);
$new_turnamen = [];
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $new_turnamen[] = $row;
  }
}

?>

<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tournaments - BrackIt</title>
    <link rel="stylesheet" href="../../CSS/PLAYER/navbar.css" />
    <link rel="stylesheet" href="../../CSS/PLAYER/tournament.css" />
    <link rel="stylesheet" href="../../CSS/PLAYER/tournament-modal.css" />
    <link rel="stylesheet" href="../../CSS/PLAYER/tournament-registration.css" />
  </head>
  <body>
    <header class="header">
      <div class="logo" onclick="window.location.href='../../index.php'" style="cursor: pointer;"></div>
      <nav class="nav">
        <ul class="nav-menu">
          <li><a href="menuTournament.php">Tournaments</a></li>
          <li><a href="menuTeams.php">Teams</a></li>
        </ul>
      </nav>
      <div class="nav-right">
        <div class="user-section" id="userSection">
          <?php if (isset($_SESSION['username']) && $_SESSION['role'] === 'player'): ?>
            <div class="user-info" id="userInfo">
              <span class="username" id="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
              <span class="dropdown-arrow">▼</span>
              <div class="user-dropdown">
                <a href="profile.php" class="dropdown-item">Profile</a>
                <button class="dropdown-item" id="logoutBtn">Logout</button>
              </div>
            </div>
          <?php else: ?>
            <span class="login-text" id="loginText">
              <a href="../../PHP/LOGIN/login.php">Login</a>
            </span>
          <?php endif; ?>
        </div>
        <div class="hamburger-menu" id="hamburgerMenu">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
    </header>

    <div class="container">
      <!-- Title -->
      <div class="title">
        <h1>TOURNAMENTS</h1>
      </div>

      <!-- Search Bar -->
      <div class="search-container">
        <div class="search-bar">
          <input type="text" placeholder="Search Tournaments..." />
          <div class="search-icon">
            <svg
              width="20"
              height="20"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <circle cx="11" cy="11" r="8"></circle>
              <path d="m21 21-4.35-4.35"></path>
            </svg>
          </div>
        </div>
      </div>

      <!-- Tournament Cards -->
      <div class="tournaments">
        <?php if (!empty($new_turnamen)): ?>
            <?php foreach ($new_turnamen as $index => $turnamen): ?>
              <div class="tournament-card" data-tournament-id="<?php echo htmlspecialchars($turnamen['id_turnamen']); ?>">
                <div class="card-image" style="background-image: url('../../ASSETS/LOGO.png');">
                  <div class="mobile-legends-logo">
                    <?php echo htmlspecialchars($turnamen['format']); ?>
                  </div>
                </div>
                <div class="card-content">
                  <h3 class="card-title"><?php echo htmlspecialchars($turnamen['nama_turnamen']); ?></h3>
                  <div class="card-meta">
                    <div class="meta-item">
                      <div class="meta-icon"></div>
                      <span>8+</span>
                    </div>
                  </div>
                </div>
              </div>
        <?php endforeach; ?>
              <?php else: ?>
                <div class="no-teams-message">
                  <p>Tidak ada data turnamen tersedia</p>
                </div>
              <?php endif; ?>

      </div>
    </div>

    <!-- Tournament Registration Modal -->
    <div id="registrationModal" class="registration-modal">
      <div class="registration-modal-content">
        <button class="modal-close" id="closeRegistrationModal">&times;</button>
        <div class="modal-header">
          <h2 class="modal-title">Daftar Turnamen</h2>
        </div>
        <div id="modalContent">
          <!-- Content will be loaded dynamically -->
        </div>
      </div>
    </div>

    <script src="../../SCRIPT/PLAYER/navbar.js"></script>
    <script src="../../SCRIPT/PLAYER/tournament-modal.js"></script>
    <script src="../../SCRIPT/PLAYER/tournament-registration.js"></script>
  </body>
</html>

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

// Query untuk mengambil team terbaru berdasarkan id terbesar (team terbaru)
$query = "SELECT * FROM team ORDER BY id_team";
$result = $conn->query($query);
$new_teams = [];
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $new_teams[] = $row;
  }
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Teams - BrackIt</title>
    <link rel="stylesheet" href="../../CSS/PLAYER/navbar.css" />
    <link rel="stylesheet" href="../../CSS/PLAYER/teams.css" />
  </head>
  <body>
    <!-- Background overlay -->
    <div class="background-overlay"></div>

    <!-- Header Navigation -->
    <header class="header">
      <div class="logo"></div>
      <nav class="nav">
        <ul class="nav-menu">
          <li><a href="../../index.php">Home</a></li>
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
                <a href="#" class="dropdown-item">Profile</a>
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

    <!-- Main Content -->
    <main>
      <!-- Page Title -->
      <section class="title-section">
        <h1 class="page-title">TEAMS</h1>
      </section>

      <!-- Search Bar -->
      <div class="search-container">
        <div class="search-bar">
          <input type="text" placeholder="Search Team..." />
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

      <!-- Teams Grid -->
      <section class="teams-section">
        <div class="teams-grid">
          <?php if (!empty($new_teams)): ?>
            <?php foreach ($new_teams as $index => $team): ?>
              <div class="team-card">
                <div class="team-logo">
                  <img src="../../ASSETS/LOGO_TEAM/<?php echo htmlspecialchars($team['logo_team']); ?>" alt="<?php echo htmlspecialchars($team['nama_team']); ?> Logo" />
                </div>
                <div class="team-info">
                  <h3 class="team-name"><?php echo htmlspecialchars($team['nama_team']); ?></h3>
                  <!-- <p class="team-members">(<?php echo htmlspecialchars($team['jumlah_member']); ?> member)</p> -->
                </div>
              </div>
           <?php endforeach; ?>
              <?php else: ?>
                <div class="no-teams-message">
                  <p>Tidak ada data tim tersedia</p>
                </div>
              <?php endif; ?>
        </div>
      </section>
    </main>

    <script src="SCRIPT/navbar.js"></script>
    <script>
      // Logout functionality
      document.addEventListener('DOMContentLoaded', function() {
          const logoutBtn = document.getElementById('logoutBtn');
          
          if (logoutBtn) {
              logoutBtn.addEventListener('click', function() {
                  if (confirm('Apakah Anda yakin ingin logout?')) {
                      // Redirect to logout script
                      window.location.href = '../../PHP/LOGIN/logout.php';
                  }
              });
          }
      });
    </script>
  </body>
</html>

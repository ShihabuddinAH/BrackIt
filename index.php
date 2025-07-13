<?php
session_start();

// Include database connection
include 'PHP/connect.php';

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

// Query untuk mengambil 3 team dengan point terbanyak
$query = "SELECT nama_team, win, point FROM team ORDER BY point DESC LIMIT 3";
$result = $conn->query($query);
$top_teams = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $top_teams[] = $row;
    }
}

// Query untuk mengambil 4 team terbaru berdasarkan id terbesar (team terbaru)
$query = "SELECT * FROM team ORDER BY id_team DESC LIMIT 4";
$result = $conn->query($query);
$new_teams = [];
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $new_teams[] = $row;
  }
}

// Query untuk mengambil 4 turnamen terbaru berdasarkan id terbesar (turnamen terbaru)
$query = "SELECT *from turnamen ORDER BY id_turnamen DESC LIMIT 4";
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
    <title>Welcome to BrackIt</title>
    <link rel="stylesheet" href="CSS/PLAYER/navbar.css" />
    <link rel="stylesheet" href="CSS/PLAYER/index.css" />
  </head>
  <body>
    <header class="header">
      <div class="logo"></div>
      <nav class="nav">
        <ul class="nav-menu">
          <li><a href="#tournament">Tournament</a></li>
          <li><a href="#teams">Teams</a></li>
          <li><a href="#klasemen">Klasemen</a></li>
          <li><a href="#contactus">Contact Us</a></li>
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
            <span class="login-text" id="loginText"
              ><a href="PHP/LOGIN/login.php">Login</a></span
            >
          <?php endif; ?>
        </div>
        <div class="hamburger-menu" id="hamburgerMenu">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
    </header>

    <section class="section">
      <div class="title">
        <h1>Welcome to BrackIt</h1>
        <p>Your ultimate tournament management platform</p>
      </div>
      <div class="tournament" id="tournament">
        <div class="section-header">
          <h2 class="section-subtitle">TOURNAMENTS</h2>
          <a href="PHP/PLAYER/menuTournament.php" class="section-link">Learn More</a>
        </div>
        <div class="tournament-cards">
          <?php if (!empty($new_turnamen)): ?>
            <?php foreach ($new_turnamen as $index => $turnamen): ?>
              <div class="tournament-card">
                <div class="tournament-logo">
                  <img src="ASSETS/LOGO.png" alt="Unisi Cup" />
                </div>
                <h1><?php echo htmlspecialchars($turnamen['nama_turnamen']); ?></h1>
              </div>
          <?php endforeach; ?>
              <?php else: ?>
                <div class="no-teams-message">
                  <p>Tidak ada data turnamen tersedia</p>
                </div>
              <?php endif; ?>
        </div>
      </div>

      <div class="teams" id="teams">
        <div class="section-header">
          <h2 class="section-subtitle">TEAMS</h2>
          <a href="PHP/PLAYER/menuTeams.php" class="section-link">See More</a>
        </div>
        <div class="team-cards">
          <?php if (!empty($new_teams)): ?>
            <?php foreach ($new_teams as $index => $team): ?>
              <div class="team-card">
                      <div class="team-logo">
                        <img src="ASSETS/LOGO_TEAM/<?php echo htmlspecialchars($team['logo_team']); ?>" alt="RRQ Team" />
                      </div>
                      <h1><?php echo htmlspecialchars($team['nama_team']); ?></h1>
                      <p><?php echo htmlspecialchars($team['deskripsi_team']); ?></p>
              </div>
          <?php endforeach; ?>
              <?php else: ?>
                <div class="no-teams-message">
                  <p>Tidak ada data tim tersedia</p>
                </div>
              <?php endif; ?>
        </div>
      </div>
      <div class="klasemen" id="klasemen">
        <h1>Klasemen</h1>
        <div class="klasemen-container">
          <table class="klasemen-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Tim</th>
                <th>W</th>
                <th>PTS</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($top_teams)): ?>
                <?php foreach ($top_teams as $index => $team): ?>
                  <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($team['nama_team']); ?></td>
                    <td><?php echo htmlspecialchars($team['win']); ?></td>
                    <td><?php echo htmlspecialchars($team['point']); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="4">Tidak ada data tim tersedia</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="contactus" id="contactus">
        <h1>Contact Us</h1>
        <div class="contact-content">
          <div class="contact-info">
            <div class="contact-item">
              <h3>📧 Email</h3>
              <p>info@brackit.com</p>
            </div>
            <div class="contact-item">
              <h3>📱 Phone</h3>
              <p>+62 812-3456-7890</p>
            </div>
            <div class="contact-item">
              <h3>📍 Address</h3>
              <p>Jl. Teknologi No. 123<br />Jakarta, Indonesia 12345</p>
            </div>
            <div class="contact-item">
              <h3>🕒 Working Hours</h3>
              <p>
                Monday - Friday: 9:00 AM - 6:00 PM<br />Saturday: 9:00 AM - 2:00
                PM
              </p>
            </div>
          </div>
          <div class="social-media">
            <h3>Follow Us</h3>
            <div class="social-links">
              <a href="#" class="social-link instagram">
                <div class="social-icon">📷</div>
                <span>Instagram</span>
              </a>
              <a href="#" class="social-link twitter">
                <div class="social-icon">🐦</div>
                <span>Twitter</span>
              </a>
              <a href="#" class="social-link facebook">
                <div class="social-icon">📘</div>
                <span>Facebook</span>
              </a>
              <a href="#" class="social-link youtube">
                <div class="social-icon">📺</div>
                <span>YouTube</span>
              </a>
              <a href="#" class="social-link discord">
                <div class="social-icon">🎮</div>
                <span>Discord</span>
              </a>
              <a href="#" class="social-link tiktok">
                <div class="social-icon">🎵</div>
                <span>TikTok</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Chatbot Container -->
    <div class="chatbot-container">
      <div class="chatbot-button" id="chatbotButton">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2ZM20 16H5.17L4 17.17V4H20V16Z" fill="white"/>
          <circle cx="7" cy="10" r="1" fill="white"/>
          <circle cx="12" cy="10" r="1" fill="white"/>
          <circle cx="17" cy="10" r="1" fill="white"/>
        </svg>
      </div>
      
      <!-- Chatbot Window (Hidden by default) -->
      <div class="chatbot-window" id="chatbotWindow">
        <div class="chatbot-header">
          <h4>BrackIt Assistant</h4>
          <button class="chatbot-close" id="chatbotClose">&times;</button>
        </div>
        <div class="chatbot-messages" id="chatbotMessages">
          <div class="message bot-message">
            <p>Halo! Saya BrackIt Assistant. Ada yang bisa saya bantu?</p>
          </div>
        </div>
        <div class="chatbot-input">
          <input type="text" id="chatbotInput" placeholder="Ketik pesan Anda...">
          <button id="chatbotSend">Kirim</button>
        </div>
      </div>
    </div>

    <script src="SCRIPT/navbar.js"></script>
    <script src="SCRIPT/chatbot.js"></script>
    
    <?php
    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }
    ?>
  </body>
</html>

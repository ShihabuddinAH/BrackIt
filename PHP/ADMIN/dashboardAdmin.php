<?php
include '../LOGIN/session.php';
include '../connect.php';

// Function to get dashboard statistics
function getDashboardStats($conn) {
    $stats = [];
    
    // Total Players
    $result = $conn->query("SELECT COUNT(*) as total FROM player");
    $stats['total_players'] = $result->fetch_assoc()['total'];
    
    // Total EOs
    $result = $conn->query("SELECT COUNT(*) as total FROM eo");
    $stats['total_eos'] = $result->fetch_assoc()['total'];
    
    // Total Teams
    $result = $conn->query("SELECT COUNT(*) as total FROM team");
    $stats['total_teams'] = $result->fetch_assoc()['total'];
    
    // Active Teams (teams with players)
    $result = $conn->query("
        SELECT COUNT(DISTINCT t.id_team) as active 
        FROM team t 
        INNER JOIN team_player tp ON t.id_team = tp.id_team
    ");
    $stats['active_teams'] = $result->fetch_assoc()['active'];
    
    // Active Tournaments
    $result = $conn->query("SELECT COUNT(*) as active FROM turnamen WHERE status = 'aktif'");
    $stats['active_tournaments'] = $result->fetch_assoc()['active'];
    
    // New tournaments this week
    $result = $conn->query("
        SELECT COUNT(*) as count 
        FROM turnamen 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stats['new_tournaments_week'] = $result->fetch_assoc()['count'];
    
    // Calculate growth (last 30 days vs previous 30 days)
    // Players growth
    $current_month_players = $conn->query("
        SELECT COUNT(*) as count 
        FROM player 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetch_assoc()['count'];
    
    $previous_month_players = $conn->query("
        SELECT COUNT(*) as count 
        FROM player 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
        AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetch_assoc()['count'];
    
    $stats['players_growth'] = $previous_month_players > 0 ? 
        round((($current_month_players - $previous_month_players) / $previous_month_players) * 100, 1) : 0;
    
    // EOs growth
    $current_month_eos = $conn->query("
        SELECT COUNT(*) as count 
        FROM eo 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetch_assoc()['count'];
    
    $previous_month_eos = $conn->query("
        SELECT COUNT(*) as count 
        FROM eo 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
        AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetch_assoc()['count'];
    
    $stats['eos_growth'] = $previous_month_eos > 0 ? 
        round((($current_month_eos - $previous_month_eos) / $previous_month_eos) * 100, 1) : 0;
    
    // EOs growth
    $current_month_eos = $conn->query("
        SELECT COUNT(*) as count 
        FROM eo 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetch_assoc()['count'];
    
    $previous_month_eos = $conn->query("
        SELECT COUNT(*) as count 
        FROM eo 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
        AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetch_assoc()['count'];
    
    $stats['eos_growth'] = $previous_month_eos > 0 ? 
        round((($current_month_eos - $previous_month_eos) / $previous_month_eos) * 100, 1) : 0;
    
    // Tournaments growth (weekly)
    $current_week_tournaments = $conn->query("
        SELECT COUNT(*) as count 
        FROM turnamen 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ")->fetch_assoc()['count'];
    
    $stats['new_tournaments_week'] = $current_week_tournaments;
    
    return $stats;
}

// Function to get chart data for visualizations
function getChartData($conn) {
    $chartData = [];
    
    // Monthly user login statistics for the last 6 months
    $monthlyLoginData = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthName = date('M Y', strtotime("-$i months"));
        
        // Players who logged in this month
        $players_login = $conn->query("
            SELECT COUNT(DISTINCT id_player) as count 
            FROM player 
            WHERE DATE_FORMAT(last_login, '%Y-%m') = '$month'
            AND last_login IS NOT NULL
        ")->fetch_assoc()['count'];
        
        // EOs who logged in this month
        $eos_login = $conn->query("
            SELECT COUNT(DISTINCT id_eo) as count 
            FROM eo 
            WHERE DATE_FORMAT(last_login, '%Y-%m') = '$month'
            AND last_login IS NOT NULL
        ")->fetch_assoc()['count'];
        
        // Admins who logged in this month
        $admins_login = $conn->query("
            SELECT COUNT(DISTINCT id_admin) as count 
            FROM admin 
            WHERE DATE_FORMAT(last_login, '%Y-%m') = '$month'
            AND last_login IS NOT NULL
        ")->fetch_assoc()['count'];
        
        $monthlyLoginData[] = [
            'month' => $monthName,
            'players' => (int)$players_login,
            'eos' => (int)$eos_login,
            'admins' => (int)$admins_login
        ];
    }
    $chartData['monthly_logins'] = $monthlyLoginData;
    
    // Monthly user registrations for the last 6 months
    $monthlyRegistrationData = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthName = date('M Y', strtotime("-$i months"));
        
        // Players registered this month
        $players = $conn->query("
            SELECT COUNT(*) as count 
            FROM player 
            WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'
        ")->fetch_assoc()['count'];
        
        // EOs registered this month
        $eos = $conn->query("
            SELECT COUNT(*) as count 
            FROM eo 
            WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'
        ")->fetch_assoc()['count'];
        
        $monthlyRegistrationData[] = [
            'month' => $monthName,
            'players' => (int)$players,
            'eos' => (int)$eos
        ];
    }
    $chartData['monthly_registrations'] = $monthlyRegistrationData;
    
    // Tournament status distribution
    $tournamentStatus = $conn->query("
        SELECT status, COUNT(*) as count 
        FROM turnamen 
        GROUP BY status
    ");
    $statusData = [];
    while ($row = $tournamentStatus->fetch_assoc()) {
        $statusData[] = [
            'status' => ucfirst($row['status']),
            'count' => (int)$row['count']
        ];
    }
    $chartData['tournament_status'] = $statusData;
    
    // Team vs Individual tournament distribution
    $formatData = $conn->query("
        SELECT format, COUNT(*) as count 
        FROM turnamen 
        GROUP BY format
    ");
    $formatDistribution = [];
    while ($row = $formatData->fetch_assoc()) {
        $formatDistribution[] = [
            'format' => ucfirst($row['format']),
            'count' => (int)$row['count']
        ];
    }
    $chartData['tournament_format'] = $formatDistribution;
    
    // Daily login statistics for the last 7 days
    $dailyLoginData = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dayName = date('D', strtotime("-$i days"));
        
        // Count unique logins per day
        $players_login = $conn->query("
            SELECT COUNT(DISTINCT id_player) as count 
            FROM player 
            WHERE DATE(last_login) = '$date'
            AND last_login IS NOT NULL
        ")->fetch_assoc()['count'];
        
        $eos_login = $conn->query("
            SELECT COUNT(DISTINCT id_eo) as count 
            FROM eo 
            WHERE DATE(last_login) = '$date'
            AND last_login IS NOT NULL
        ")->fetch_assoc()['count'];
        
        $dailyLoginData[] = [
            'day' => $dayName,
            'players' => (int)$players_login,
            'eos' => (int)$eos_login,
            'total_logins' => (int)$players_login + (int)$eos_login
        ];
    }
    $chartData['daily_logins'] = $dailyLoginData;
    
    // Daily registrations for the last 7 days
    $dailyRegistrationData = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dayName = date('D', strtotime("-$i days"));
        
        $players = $conn->query("
            SELECT COUNT(*) as count 
            FROM player 
            WHERE DATE(created_at) = '$date'
        ")->fetch_assoc()['count'];
        
        $eos = $conn->query("
            SELECT COUNT(*) as count 
            FROM eo 
            WHERE DATE(created_at) = '$date'
        ")->fetch_assoc()['count'];
        
        $dailyRegistrationData[] = [
            'day' => $dayName,
            'players' => (int)$players,
            'eos' => (int)$eos,
            'total_registrations' => (int)$players + (int)$eos
        ];
    }
    $chartData['daily_registrations'] = $dailyRegistrationData;
    
    // Top performing teams (by points)
    $topTeams = $conn->query("
        SELECT nama_team, point, win, lose 
        FROM team 
        ORDER BY point DESC 
        LIMIT 5
    ");
    $teamsData = [];
    while ($row = $topTeams->fetch_assoc()) {
        $teamsData[] = [
            'name' => $row['nama_team'],
            'points' => (int)$row['point'],
            'wins' => (int)$row['win'],
            'losses' => (int)$row['lose']
        ];
    }
    $chartData['top_teams'] = $teamsData;
    
    // Recent activity stats
    $recentActivity = [];
    
    // New users today
    $today = date('Y-m-d');
    $newPlayersToday = $conn->query("
        SELECT COUNT(*) as count 
        FROM player 
        WHERE DATE(created_at) = '$today'
    ")->fetch_assoc()['count'];
    
    $newEOsToday = $conn->query("
        SELECT COUNT(*) as count 
        FROM eo 
        WHERE DATE(created_at) = '$today'
    ")->fetch_assoc()['count'];
    
    $recentActivity['new_users_today'] = (int)$newPlayersToday + (int)$newEOsToday;
    
    // Active tournaments today
    $activeTournamentsToday = $conn->query("
        SELECT COUNT(*) as count 
        FROM turnamen 
        WHERE status = 'aktif' 
        AND DATE(tanggal_mulai) <= '$today' 
        AND (tanggal_selesai IS NULL OR DATE(tanggal_selesai) >= '$today')
    ")->fetch_assoc()['count'];
    
    $recentActivity['active_tournaments_today'] = (int)$activeTournamentsToday;
    
    $chartData['recent_activity'] = $recentActivity;
    
    return $chartData;
}

// Function to get users data
function getUsersData($conn) {
    $users = [];
    
    // Get all admins
    $admin_query = "SELECT id_admin as id, username, email, status, role, created_at, last_login FROM admin ORDER BY created_at DESC";
    $admin_result = $conn->query($admin_query);
    $users['admins'] = [];
    while ($row = $admin_result->fetch_assoc()) {
        $users['admins'][] = $row;
    }
    
    // Get top 10 EOs
    $eo_query = "SELECT id_eo as id, username, email, organisasi, status, role, created_at, last_login FROM eo ORDER BY created_at DESC LIMIT 10";
    $eo_result = $conn->query($eo_query);
    $users['eos'] = [];
    while ($row = $eo_result->fetch_assoc()) {
        $users['eos'][] = $row;
    }
    
    // Get top 10 players
    $player_query = "SELECT id_player as id, username, email, nickname, idGame, status, role, created_at, last_login FROM player ORDER BY created_at DESC LIMIT 10";
    $player_result = $conn->query($player_query);
    $users['players'] = [];
    while ($row = $player_result->fetch_assoc()) {
        $users['players'][] = $row;
    }
    
    return $users;
}

// Function to get tournament data
function getTournamentData($conn) {
    $data = [];
    
    // Get pending tournaments (status = 'akan datang' atau custom pending status)
    $pending_query = "
        SELECT t.id_turnamen, t.nama_turnamen, e.username as eo_name, e.organisasi, 
               t.format, t.tanggal_mulai, t.hadiah_turnamen, t.max_participants, 
               t.current_participants, t.created_at, t.status, t.deskripsi_turnamen,
               t.biaya_turnamen, t.tanggal_selesai, t.aturan
        FROM turnamen t 
        LEFT JOIN eo e ON t.id_eo = e.id_eo 
        WHERE t.status = 'akan datang'
        ORDER BY t.created_at DESC 
        LIMIT 10
    ";
    $pending_result = $conn->query($pending_query);
    $data['pending'] = [];
    while ($row = $pending_result->fetch_assoc()) {
        $data['pending'][] = $row;
    }
    
    // Get all tournaments (latest 10 for dashboard display)
    $all_query = "
        SELECT t.id_turnamen, t.nama_turnamen, e.username as eo_name, e.organisasi, 
               t.format, t.tanggal_mulai, t.tanggal_selesai, t.hadiah_turnamen, 
               t.max_participants, t.current_participants, t.created_at, t.status,
               t.deskripsi_turnamen, t.biaya_turnamen, t.aturan
        FROM turnamen t 
        LEFT JOIN eo e ON t.id_eo = e.id_eo 
        ORDER BY t.created_at DESC 
        LIMIT 10
    ";
    $all_result = $conn->query($all_query);
    $data['all'] = [];
    while ($row = $all_result->fetch_assoc()) {
        $data['all'][] = $row;
    }
    
    // Get total count for display
    $count_result = $conn->query("SELECT COUNT(*) as total FROM turnamen");
    $data['total_count'] = $count_result->fetch_assoc()['total'];
    
    return $data;
}

// Function to get all tournaments for management page
function getAllTournamentsData($conn, $search = '', $limit = 20, $offset = 0) {
    $searchCondition = '';
    $params = [];
    
    if (!empty($search)) {
        $searchCondition = "WHERE (t.nama_turnamen LIKE ? OR e.username LIKE ? OR e.organisasi LIKE ? OR t.status LIKE ?)";
        $searchParam = "%{$search}%";
        $params = [$searchParam, $searchParam, $searchParam, $searchParam];
    }
    
    // Get tournaments with search and pagination
    $query = "
        SELECT t.id_turnamen, t.nama_turnamen, e.username as eo_name, e.organisasi, 
               t.format, t.tanggal_mulai, t.tanggal_selesai, t.hadiah_turnamen, 
               t.max_participants, t.current_participants, t.created_at, t.status,
               t.deskripsi_turnamen, t.biaya_turnamen, t.aturan
        FROM turnamen t 
        LEFT JOIN eo e ON t.id_eo = e.id_eo 
        {$searchCondition}
        ORDER BY t.created_at DESC 
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $conn->prepare($query);
    if (!empty($search)) {
        $params[] = $limit;
        $params[] = $offset;
        $stmt->bind_param("ssssii", ...$params);
    } else {
        $stmt->bind_param("ii", $limit, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tournaments = [];
    while ($row = $result->fetch_assoc()) {
        $tournaments[] = $row;
    }
    
    // Get total count for pagination
    $countQuery = "
        SELECT COUNT(*) as total 
        FROM turnamen t 
        LEFT JOIN eo e ON t.id_eo = e.id_eo 
        {$searchCondition}
    ";
    
    if (!empty($search)) {
        $countStmt = $conn->prepare($countQuery);
        $countStmt->bind_param("ssss", $searchParam, $searchParam, $searchParam, $searchParam);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
    } else {
        $countResult = $conn->query($countQuery);
    }
    
    $totalCount = $countResult->fetch_assoc()['total'];
    
    return [
        'tournaments' => $tournaments,
        'total' => $totalCount,
        'current_page' => floor($offset / $limit) + 1,
        'total_pages' => ceil($totalCount / $limit)
    ];
}

// Function to format date for display
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

// Function to get game name from format (you can expand this)
function getGameName($format) {
    // This is a placeholder - you might want to add a games table
    $games = [
        'team' => 'Mobile Legends',
        'individu' => 'PUBG Mobile'
    ];
    return $games[$format] ?? ucfirst($format);
}

// Function to format prize money
function formatPrize($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Function to get party team data
function getPartyTeamData($conn) {
    $data = [];
    
    // Get top 10 parties
    $party_query = "SELECT p.id_party as id, p.nama_party, p.win, p.lose, (p.win + p.lose) as total_match, p.created_at,
                           pl.username as leader_username, pl.nickname as leader_nickname,
                           (SELECT COUNT(*) FROM party_player pp WHERE pp.id_party = p.id_party) as member_count
                    FROM party p 
                    LEFT JOIN player pl ON p.id_leader = pl.id_player 
                    ORDER BY p.created_at DESC LIMIT 10";
    $party_result = $conn->query($party_query);
    $data['parties'] = [];
    while ($row = $party_result->fetch_assoc()) {
        $data['parties'][] = $row;
    }
    
    // Get top 10 teams (note: team table doesn't have created_at column)
    $team_query = "SELECT t.id_team as id, t.nama_team, t.logo_team, t.win, t.lose, (t.win + t.lose) as total_match, t.point, t.deskripsi_team,
                          pl.username as leader_username, pl.nickname as leader_nickname,
                          (SELECT COUNT(*) FROM team_player tp WHERE tp.id_team = t.id_team) as member_count,
                          t.created_at
                   FROM team t 
                   LEFT JOIN player pl ON t.id_leader = pl.id_player 
                   ORDER BY t.id_team DESC LIMIT 10";
    $team_result = $conn->query($team_query);
    $data['teams'] = [];
    while ($row = $team_result->fetch_assoc()) {
        $data['teams'][] = $row;
    }
    
    return $data;
}

// Function to format date
function formatDateTime($datetime) {
    return date('d M Y H:i', strtotime($datetime));
}

// Get data
$stats = getDashboardStats($conn);
$chartData = getChartData($conn);
$users = getUsersData($conn);
$partyTeamData = getPartyTeamData($conn);
$tournamentData = getTournamentData($conn);
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard Admin - BrackIt</title>
    <link rel="stylesheet" href="../../CSS/ADMIN/dashboardAdmin.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  </head>
  <body>
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="logo">
          <img src="../../ASSETS/LOGO.png" alt="BrackIt Logo" />
          <h2>BrackIt Admin</h2>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
          <i class="fas fa-bars"></i>
        </button>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li class="nav-item active">
            <a href="#dashboard" class="nav-link" data-section="dashboard">
              <i class="fas fa-tachometer-alt"></i>
              <span>Dashboard</span>
            </a>
          </li>
          <li class="nav-item">
            <a
              href="adminManajemenPengguna.html"
              class="nav-link"
              data-section="users"
            >
              <i class="fas fa-users"></i>
              <span>Manajemen Pengguna</span>
            </a>
          </li>
          <li class="nav-item">
            <a
              href="adminManajemenTim.html"
              class="nav-link"
              data-section="teams"
            >
              <i class="fas fa-users-cog"></i>
              <span>Manajemen Tim & Party</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="#tournaments" class="nav-link" data-section="tournaments">
              <i class="fas fa-trophy"></i>
              <span>Manajemen Turnamen</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="#reports" class="nav-link" data-section="reports">
              <i class="fas fa-chart-bar"></i>
              <span>Laporan & Statistik</span>
            </a>
          </li>
        </ul>
      </nav>
      <div class="sidebar-footer">
        <div class="user-profile">
          <img src="../../ASSETS/user.png" alt="User Avatar" />
          <div class="user-info">
            <h4>Administrator</h4>
            <!-- <p>Admin Dashboard</p> -->
          </div>
        </div>
        <button class="logout-btn" id="logoutBtn">
          <i class="fas fa-sign-out-alt"></i>
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <!-- Header -->
      <header class="main-header">
        <div class="header-left">
          <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
          </button>
          <h1 id="pageTitle">Dashboard Admin</h1>
        </div>
        <div class="header-right">
          <div class="search-box">
            <input type="text" placeholder="Cari data..." />
            <i class="fas fa-search"></i>
          </div>
          <div class="notifications">
            <i class="fas fa-bell"></i>
            <span class="notification-count">5</span>
          </div>
          <div class="user-menu">
            <img src="../../ASSETS/user.png" alt="User Avatar" />
            <i class="fas fa-chevron-down"></i>
          </div>
        </div>
      </header>

      <!-- Dashboard Content -->
      <div class="content-wrapper">
        <!-- Dashboard Section -->
        <section id="dashboard-section" class="content-section active">
          <div class="section-header">
            <h2>Dashboard Analytics</h2>
            <div class="header-actions">
              <button class="refresh-btn" onclick="manualRefreshCharts()" title="Refresh Data">
                <i class="fas fa-sync-alt"></i>
                <span>Refresh Data</span>
              </button>
            </div>
          </div>
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-users"></i>
              </div>
              <div class="stat-content">
                <h3>Total Player</h3>
                <p class="stat-number"><?php echo number_format($stats['total_players']); ?></p>
                <span class="stat-change <?php echo $stats['players_growth'] >= 0 ? 'positive' : 'negative'; ?>">
                  <?php echo $stats['players_growth'] >= 0 ? '+' : ''; ?><?php echo $stats['players_growth']; ?>% bulan ini
                </span>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-user-tie"></i>
              </div>
              <div class="stat-content">
                <h3>Total EO</h3>
                <p class="stat-number"><?php echo number_format($stats['total_eos']); ?></p>
                <span class="stat-change <?php echo $stats['eos_growth'] >= 0 ? 'positive' : 'negative'; ?>">
                  <?php echo $stats['eos_growth'] >= 0 ? '+' : ''; ?><?php echo $stats['eos_growth']; ?>% bulan ini
                </span>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-user-friends"></i>
              </div>
              <div class="stat-content">
                <h3>Total Tim</h3>
                <p class="stat-number"><?php echo number_format($stats['total_teams']); ?></p>
                <span class="stat-change neutral">Total tim terdaftar</span>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-users-cog"></i>
              </div>
              <div class="stat-content">
                <h3>Tim Aktif</h3>
                <p class="stat-number"><?php echo number_format($stats['active_teams']); ?></p>
                <span class="stat-change neutral">Tim dengan anggota</span>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-trophy"></i>
              </div>
              <div class="stat-content">
                <h3>Turnamen Berlangsung</h3>
                <p class="stat-number"><?php echo number_format($stats['active_tournaments']); ?></p>
                <span class="stat-change positive">+<?php echo $stats['new_tournaments_week']; ?> minggu ini</span>
              </div>
            </div>
          </div>

          <div class="dashboard-grid">
            <!-- Login Statistics Section -->
            <div class="chart-container">
              <div class="chart-header">
                <h3>Statistik Login Bulanan</h3>
                <select class="chart-filter">
                  <option>6 Bulan Terakhir</option>
                  <option>3 Bulan Terakhir</option>
                  <option>1 Tahun Terakhir</option>
                </select>
              </div>
              <div class="chart-content">
                <canvas id="userLoginStatsChart"></canvas>
              </div>
            </div>

            <div class="chart-container">
              <div class="chart-header">
                <h3>Login Harian (7 Hari Terakhir)</h3>
              </div>
              <div class="chart-content">
                <canvas id="dailyLoginsChart"></canvas>
              </div>
            </div>

            <!-- Registration Statistics Section - Moved to left -->
            <div class="chart-container">
              <div class="chart-header">
                <h3>Registrasi Pengguna Bulanan</h3>
                <select class="chart-filter">
                  <option>6 Bulan Terakhir</option>
                  <option>3 Bulan Terakhir</option>
                  <option>1 Tahun Terakhir</option>
                </select>
              </div>
              <div class="chart-content">
                <canvas id="userRegistrationStatsChart"></canvas>
              </div>
            </div>

            <div class="chart-container">
              <div class="chart-header">
                <h3>Registrasi Harian (7 Hari Terakhir)</h3>
              </div>
              <div class="chart-content">
                <canvas id="dailyRegistrationsChart"></canvas>
              </div>
            </div>

            <!-- Tournament Charts Section - Status moved down -->
            <div class="chart-container">
              <div class="chart-header">
                <h3>Status Turnamen</h3>
              </div>
              <div class="chart-content">
                <canvas id="tournamentStatusChart"></canvas>
              </div>
            </div>

            <div class="chart-container">
              <div class="chart-header">
                <h3>Format Turnamen</h3>
              </div>
              <div class="chart-content">
                <canvas id="tournamentFormatChart"></canvas>
              </div>
            </div>

            <div class="chart-container">
              <div class="chart-header">
                <h3>Tim Teratas (Berdasarkan Poin)</h3>
              </div>
              <div class="chart-content">
                <canvas id="topTeamsChart"></canvas>
              </div>
            </div>

            <div class="recent-tournaments">
              <div class="section-header">
                <h3>Pendaftaran Turnamen Terbaru</h3>
                <a href="#tournaments" class="view-all">Lihat Semua</a>
              </div>
              <div class="tournament-list">
                <div class="tournament-item">
                  <div class="tournament-info">
                    <h4>Mobile Legends Championship 2025</h4>
                    <p>ESports Indonesia - 28 Jul 2025</p>
                    <span class="status pending">Menunggu Persetujuan</span>
                  </div>
                  <div class="tournament-stats">
                    <span>64 Tim</span>
                    <span>Rp 10M</span>
                  </div>
                </div>
                <div class="tournament-item">
                  <div class="tournament-info">
                    <h4>PUBG Mobile Tournament</h4>
                    <p>Gaming Community - 27 Jul 2025</p>
                    <span class="status approved">Disetujui</span>
                  </div>
                  <div class="tournament-stats">
                    <span>32 Tim</span>
                    <span>Rp 7.5M</span>
                  </div>
                </div>
                <div class="tournament-item">
                  <div class="tournament-info">
                    <h4>Free Fire National Cup</h4>
                    <p>FireStorm Events - 26 Jul 2025</p>
                    <span class="status active">Berlangsung</span>
                  </div>
                  <div class="tournament-stats">
                    <span>48 Tim</span>
                    <span>Rp 8M</span>
                  </div>
                </div>
                <div class="tournament-item">
                  <div class="tournament-info">
                    <h4>Valorant Masters Cup</h4>
                    <p>Riot Games Indonesia - 25 Jul 2025</p>
                    <span class="status pending">Menunggu Persetujuan</span>
                  </div>
                  <div class="tournament-stats">
                    <span>16 Tim</span>
                    <span>Rp 15M</span>
                  </div>
                </div>
                <div class="tournament-item">
                  <div class="tournament-info">
                    <h4>Call of Duty Championship</h4>
                    <p>Gaming Pro League - 24 Jul 2025</p>
                    <span class="status approved">Disetujui</span>
                  </div>
                  <div class="tournament-stats">
                    <span>24 Tim</span>
                    <span>Rp 5M</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Reports Section -->
          <div class="reports-section">
            <div class="section-header">
              <h3>Notifikasi Laporan Terbaru</h3>
              <div class="filter-container">
                <input
                  type="text"
                  class="filter-input"
                  id="reportFilter"
                  placeholder="Filter laporan..."
                />
              </div>
            </div>
            <div class="reports-table">
              <table>
                <thead>
                  <tr>
                    <th>Tanggal</th>
                    <th>Pelapor</th>
                    <th>Kategori</th>
                    <th>Deskripsi</th>
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody id="reportsTable">
                  <tr>
                    <td>30 Juni 2025</td>
                    <td>gamer_pro123</td>
                    <td>Perilaku</td>
                    <td>Melaporkan perilaku toxic dalam turnamen MLBB</td>
                    <td><span class="status pending">Pending</span></td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn-view" title="Detail">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-approve" title="Setujui">
                          <i class="fas fa-check"></i>
                        </button>
                        <button class="btn-reject" title="Tolak">
                          <i class="fas fa-times"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td>29 Juni 2025</td>
                    <td>esports_fan</td>
                    <td>Cheating</td>
                    <td>Dugaan cheating dalam match PUBG Mobile</td>
                    <td>
                      <span class="status investigating">Investigasi</span>
                    </td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn-view" title="Detail">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-approve" title="Setujui">
                          <i class="fas fa-check"></i>
                        </button>
                        <button class="btn-reject" title="Tolak">
                          <i class="fas fa-times"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td>29 Juni 2025</td>
                    <td>tournament_watcher</td>
                    <td>Teknis</td>
                    <td>Masalah teknis pada platform streaming</td>
                    <td><span class="status resolved">Resolved</span></td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn-view" title="Detail">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-archive" title="Arsip">
                          <i class="fas fa-archive"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- Users Management Section -->
        <section id="users-section" class="content-section">
          <div class="section-header">
            <h2>Manajemen Pengguna</h2>
            <div class="header-actions">
              <button class="btn-secondary" id="exportUsersBtn">
                <i class="fas fa-download"></i>
                Export Data
              </button>
              <button class="btn-primary" id="addUserBtn" onclick="openAddUserModal()">
                <i class="fas fa-plus"></i>
                Tambah Pengguna
              </button>
            </div>
          </div>

          <!-- Admins Table -->
          <div class="user-table-section">
            <div class="table-header">
              <h3><i class="fas fa-user-shield"></i> Administrator (<?php echo count($users['admins']); ?>)</h3>
            </div>
            <div class="users-table">
              <table>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Dibuat</th>
                    <th>Login Terakhir</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($users['admins'] as $admin): ?>
                  <tr>
                    <td><?php echo $admin['id']; ?></td>
                    <td>
                      <div class="user-info">
                        <i class="fas fa-user-shield user-icon"></i>
                        <span><?php echo htmlspecialchars($admin['username']); ?></span>
                      </div>
                    </td>
                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                    <td>
                      <span class="status <?php echo $admin['status']; ?>">
                        <?php echo ucfirst($admin['status']); ?>
                      </span>
                    </td>
                    <td><?php echo formatDateTime($admin['created_at']); ?></td>
                    <td><?php echo formatDateTime($admin['last_login']); ?></td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn-view" title="Detail" onclick="openEditUserModal('admin', <?php echo $admin['id']; ?>)">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-edit" title="Edit" onclick="openEditUserModal('admin', <?php echo $admin['id']; ?>)">
                          <i class="fas fa-edit"></i>
                        </button>
                        <?php if ($admin['id'] != 1): // Don't allow deleting main admin ?>
                        <button class="btn-delete" title="Hapus" onclick="deleteUser('admin', <?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['username']); ?>')">
                          <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  
                  <?php if (count($users['admins']) == 0): ?>
                  <tr>
                    <td colspan="7" style="text-align: center; padding: 2rem;">
                      <p>Belum ada admin yang terdaftar.</p>
                    </td>
                  </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- EOs Table -->
          <div class="user-table-section">
            <div class="table-header">
              <h3><i class="fas fa-user-tie"></i> Event Organizer (<?php echo count($users['eos']); ?>/10 terbaru)</h3>
              <div class="table-actions">
                <button class="btn-secondary btn-sm" onclick="viewAllUsers('eo')">
                  <i class="fas fa-list"></i> Lihat Semua
                </button>
              </div>
            </div>
            <div class="users-table">
              <table>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Organisasi</th>
                    <th>Status</th>
                    <th>Dibuat</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($users['eos'] as $eo): ?>
                  <tr>
                    <td><?php echo $eo['id']; ?></td>
                    <td>
                      <div class="user-info">
                        <i class="fas fa-user-tie user-icon"></i>
                        <span><?php echo htmlspecialchars($eo['username']); ?></span>
                      </div>
                    </td>
                    <td><?php echo htmlspecialchars($eo['email']); ?></td>
                    <td><?php echo htmlspecialchars($eo['organisasi'] ?? '-'); ?></td>
                    <td>
                      <span class="status <?php echo $eo['status']; ?>">
                        <?php echo ucfirst($eo['status']); ?>
                      </span>
                    </td>
                    <td><?php echo formatDateTime($eo['created_at']); ?></td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn-view" title="Detail" onclick="openEditUserModal('eo', <?php echo $eo['id']; ?>)">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-edit" title="Edit" onclick="openEditUserModal('eo', <?php echo $eo['id']; ?>)">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-delete" title="Hapus" onclick="deleteUser('eo', <?php echo $eo['id']; ?>, '<?php echo htmlspecialchars($eo['username']); ?>')">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  
                  <?php if (count($users['eos']) == 0): ?>
                  <tr>
                    <td colspan="7" style="text-align: center; padding: 2rem;">
                      <p>Belum ada EO yang terdaftar.</p>
                    </td>
                  </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Players Table -->
          <div class="user-table-section">
            <div class="table-header">
              <h3><i class="fas fa-gamepad"></i> Player (<?php echo count($users['players']); ?>/10 terbaru)</h3>
              <div class="table-actions">
                <button class="btn-secondary btn-sm" onclick="viewAllUsers('player')">
                  <i class="fas fa-list"></i> Lihat Semua
                </button>
              </div>
            </div>
            <div class="users-table">
              <table>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Nickname</th>
                    <th>ID Game</th>
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($users['players'] as $player): ?>
                  <tr>
                    <td><?php echo $player['id']; ?></td>
                    <td>
                      <div class="user-info">
                        <i class="fas fa-gamepad user-icon"></i>
                        <span><?php echo htmlspecialchars($player['username']); ?></span>
                      </div>
                    </td>
                    <td><?php echo htmlspecialchars($player['email']); ?></td>
                    <td><?php echo htmlspecialchars($player['nickname'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($player['idGame'] ?? '-'); ?></td>
                    <td>
                      <span class="status <?php echo $player['status']; ?>">
                        <?php echo ucfirst($player['status']); ?>
                      </span>
                    </td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn-view" title="Detail" onclick="openEditUserModal('player', <?php echo $player['id']; ?>)">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-edit" title="Edit" onclick="openEditUserModal('player', <?php echo $player['id']; ?>)">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-delete" title="Hapus" onclick="deleteUser('player', <?php echo $player['id']; ?>, '<?php echo htmlspecialchars($player['username']); ?>')">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  
                  <?php if (count($users['players']) == 0): ?>
                  <tr>
                    <td colspan="7" style="text-align: center; padding: 2rem;">
                      <p>Belum ada player yang terdaftar.</p>
                    </td>
                  </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- Teams Management Section -->
        <section id="teams-section" class="content-section">
          <div class="section-header">
            <h2>Manajemen Tim & Party</h2>
            <div class="header-actions">
              <button class="btn-secondary" id="exportPartyTeamBtn">
                <i class="fas fa-download"></i>
                Export Data
              </button>
              <button class="btn-primary" id="addPartyBtn">
                <i class="fas fa-plus"></i>
                Tambah Party
              </button>
              <button class="btn-primary" id="addTeamBtn">
                <i class="fas fa-plus"></i>
                Tambah Tim
              </button>
            </div>
          </div>

          <!-- Parties Table -->
          <div class="user-table-section">
            <div class="table-header">
              <h3><i class="fas fa-users"></i> Party (<?php echo count($partyTeamData['parties']); ?>/10 terbaru)</h3>
              <div class="table-actions">
                <button class="btn-secondary btn-sm" onclick="viewAllParties()">
                  <i class="fas fa-list"></i> Lihat Semua
                </button>
              </div>
            </div>
            <div class="users-table">
              <table>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nama Party</th>
                    <th>Leader</th>
                    <th>Win</th>
                    <th>Lose</th>
                    <th>Total Match</th>
                    <th>Anggota</th>
                    <th>Dibuat</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($partyTeamData['parties'] as $party): ?>
                  <tr>
                    <td><?php echo $party['id']; ?></td>
                    <td>
                      <div class="user-info">
                        <i class="fas fa-users user-icon"></i>
                        <span><?php echo htmlspecialchars($party['nama_party']); ?></span>
                      </div>
                    </td>
                    <td><?php echo htmlspecialchars($party['leader_nickname'] ?? $party['leader_username'] ?? '-'); ?></td>
                    <td><?php echo $party['win'] ?? 0; ?></td>
                    <td><?php echo $party['lose'] ?? 0; ?></td>
                    <td><?php echo $party['total_match'] ?? 0; ?></td>
                    <td><?php echo $party['member_count'] ?? 0; ?>/5</td>
                    <td><?php echo formatDateTime($party['created_at']); ?></td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn-view" title="Detail" onclick="partyTeamManager.viewDetail('party', <?php echo $party['id']; ?>)">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-edit" title="Edit" onclick="partyTeamManager.openEditModal('party', <?php echo $party['id']; ?>)">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-delete" title="Hapus" onclick="partyTeamManager.deleteData('party', <?php echo $party['id']; ?>, '<?php echo htmlspecialchars($party['nama_party']); ?>')">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  
                  <?php if (count($partyTeamData['parties']) == 0): ?>
                  <tr>
                    <td colspan="9" style="text-align: center; padding: 2rem;">
                      <p>Belum ada party yang terdaftar.</p>
                    </td>
                  </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Teams Table -->
          <div class="user-table-section">
            <div class="table-header">
              <h3><i class="fas fa-shield-alt"></i> Team (<?php echo count($partyTeamData['teams']); ?>/10 terbaru)</h3>
              <div class="table-actions">
                <button class="btn-secondary btn-sm" onclick="viewAllTeams()">
                  <i class="fas fa-list"></i> Lihat Semua
                </button>
              </div>
            </div>
            <div class="users-table">
              <table>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nama Team</th>
                    <th>Leader</th>
                    <th>Win</th>
                    <th>Lose</th>
                    <th>Total Match</th>
                    <th>Point</th>
                    <th>Anggota</th>
                    <th>Dibuat</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($partyTeamData['teams'] as $team): ?>
                  <tr>
                    <td><?php echo $team['id']; ?></td>
                    <td>
                      <div class="user-info">
                        <i class="fas fa-shield-alt user-icon"></i>
                        <span><?php echo htmlspecialchars($team['nama_team']); ?></span>
                      </div>
                    </td>
                    <td><?php echo htmlspecialchars($team['leader_nickname'] ?? $team['leader_username'] ?? '-'); ?></td>
                    <td><?php echo $team['win'] ?? 0; ?></td>
                    <td><?php echo $team['lose'] ?? 0; ?></td>
                    <td><?php echo $team['total_match'] ?? 0; ?></td>
                    <td><?php echo $team['point'] ?? 0; ?></td>
                    <td><?php echo $team['member_count'] ?? 0; ?>/5</td>
                    <td><?php echo $team['created_at'] ? formatDateTime($team['created_at']) : '-'; ?></td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn-view" title="Detail" onclick="partyTeamManager.viewDetail('team', <?php echo $team['id']; ?>)">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-edit" title="Edit" onclick="partyTeamManager.openEditModal('team', <?php echo $team['id']; ?>)">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-delete" title="Hapus" onclick="partyTeamManager.deleteData('team', <?php echo $team['id']; ?>, '<?php echo htmlspecialchars($team['nama_team']); ?>')">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  
                  <?php if (count($partyTeamData['teams']) == 0): ?>
                  <tr>
                    <td colspan="10" style="text-align: center; padding: 2rem;">
                      <p>Belum ada team yang terdaftar.</p>
                    </td>
                  </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- Tournaments Management Section -->
        <section id="tournaments-section" class="content-section">
          <div class="section-header">
            <h2>Manajemen Turnamen</h2>
            <div class="header-actions">
              <button class="btn-secondary" id="exportTournamentBtn">
                <i class="fas fa-download"></i>
                Export Data
              </button>
              <button class="btn-primary" id="approveAllBtn">
                <i class="fas fa-check-circle"></i>
                Approve All Pending
              </button>
            </div>
          </div>

          <!-- Tournament Requests Table (Pending Approval) -->
          <div class="user-table-section">
            <div class="table-header">
              <h3><i class="fas fa-hourglass-half"></i> Request Turnamen Pending (5 request)</h3>
              <div class="table-actions">
                <span class="pending-count">5 request menunggu approval</span>
              </div>
            </div>
            <div class="users-table">
              <table>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nama Turnamen</th>
                    <th>Event Organizer</th>
                    <th>Game</th>
                    <th>Tanggal Mulai</th>
                    <th>Prize Pool</th>
                    <th>Max Tim</th>
                    <th>Dibuat</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>T001</td>
                    <td>
                      <div class="tournament-info">
                        <i class="fas fa-trophy tournament-icon"></i>
                        <span>Mobile Legends Championship 2025</span>
                      </div>
                    </td>
                    <td>ESports Indonesia</td>
                    <td>Mobile Legends</td>
                    <td>28 Jul 2025</td>
                    <td>Rp 10.000.000</td>
                    <td>64</td>
                    <td>20 Jul 2025</td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn-approve" title="Approve" onclick="approveTournament('T001', 'Mobile Legends Championship 2025')">
                          <i class="fas fa-check"></i>
                        </button>
                        <button class="btn-view" title="Detail" onclick="viewTournamentDetail('T001')">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-reject" title="Reject" onclick="rejectTournament('T001', 'Mobile Legends Championship 2025')">
                          <i class="fas fa-times"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td>T002</td>
                    <td>
                      <div class="tournament-info">
                        <i class="fas fa-trophy tournament-icon"></i>
                        <span>PUBG Mobile Summer Cup</span>
                      </div>
                    </td>
                    <td>Gaming Community</td>
                    <td>PUBG Mobile</td>
                    <td>30 Jul 2025</td>
                    <td>Rp 7.500.000</td>
                    <td>32</td>
                    <td>19 Jul 2025</td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn-approve" title="Approve" onclick="approveTournament('T002', 'PUBG Mobile Summer Cup')">
                          <i class="fas fa-check"></i>
                        </button>
                        <button class="btn-view" title="Detail" onclick="viewTournamentDetail('T002')">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-reject" title="Reject" onclick="rejectTournament('T002', 'PUBG Mobile Summer Cup')">
                          <i class="fas fa-times"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td>T003</td>
                    <td>
                      <div class="tournament-info">
                        <i class="fas fa-trophy tournament-icon"></i>
                        <span>Free Fire National Championship</span>
                      </div>
                    </td>
                    <td>FireStorm Events</td>
                    <td>Free Fire</td>
                    <td>02 Agu 2025</td>
                    <td>Rp 8.000.000</td>
                    <td>48</td>
                    <td>18 Jul 2025</td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn-approve" title="Approve" onclick="approveTournament('T003', 'Free Fire National Championship')">
                          <i class="fas fa-check"></i>
                        </button>
                        <button class="btn-view" title="Detail" onclick="viewTournamentDetail('T003')">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-reject" title="Reject" onclick="rejectTournament('T003', 'Free Fire National Championship')">
                          <i class="fas fa-times"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td>T004</td>
                    <td>
                      <div class="tournament-info">
                        <i class="fas fa-trophy tournament-icon"></i>
                        <span>Valorant Indonesia Open</span>
                      </div>
                    </td>
                    <td>Riot Games Indonesia</td>
                    <td>Valorant</td>
                    <td>05 Agu 2025</td>
                    <td>Rp 15.000.000</td>
                    <td>16</td>
                    <td>17 Jul 2025</td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn-approve" title="Approve" onclick="approveTournament('T004', 'Valorant Indonesia Open')">
                          <i class="fas fa-check"></i>
                        </button>
                        <button class="btn-view" title="Detail" onclick="viewTournamentDetail('T004')">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-reject" title="Reject" onclick="rejectTournament('T004', 'Valorant Indonesia Open')">
                          <i class="fas fa-times"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td>T005</td>
                    <td>
                      <div class="tournament-info">
                        <i class="fas fa-trophy tournament-icon"></i>
                        <span>Call of Duty Mobile Battle Royale</span>
                      </div>
                    </td>
                    <td>Activision Indonesia</td>
                    <td>COD Mobile</td>
                    <td>08 Agu 2025</td>
                    <td>Rp 5.000.000</td>
                    <td>24</td>
                    <td>16 Jul 2025</td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn-approve" title="Approve" onclick="approveTournament('T005', 'Call of Duty Mobile Battle Royale')">
                          <i class="fas fa-check"></i>
                        </button>
                        <button class="btn-view" title="Detail" onclick="viewTournamentDetail('T005')">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-reject" title="Reject" onclick="rejectTournament('T005', 'Call of Duty Mobile Battle Royale')">
                          <i class="fas fa-times"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- All Tournaments Table -->
          <div class="user-table-section">
            <div class="table-header">
              <h3><i class="fas fa-trophy"></i> Semua Turnamen (<?php echo count($tournamentData['all']); ?>/<?php echo $tournamentData['total_count']; ?> terbaru)</h3>
              <div class="table-actions">
                <button class="btn-secondary btn-sm" onclick="viewAllTournaments()">
                  <i class="fas fa-list"></i> Lihat Semua
                </button>
              </div>
            </div>
            <div class="users-table">
              <table>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nama Turnamen</th>
                    <th>Event Organizer</th>
                    <th>Game</th>
                    <th>Status</th>
                    <th>Tanggal Mulai</th>
                    <th>Prize Pool</th>
                    <th>Peserta</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($tournamentData['all'] as $tournament): ?>
                  <tr>
                    <td><?php echo $tournament['id_turnamen']; ?></td>
                    <td>
                      <div class="tournament-info">
                        <i class="fas fa-trophy tournament-icon"></i>
                        <span><?php echo htmlspecialchars($tournament['nama_turnamen']); ?></span>
                      </div>
                    </td>
                    <td><?php echo htmlspecialchars($tournament['organisasi'] ?? $tournament['eo_name']); ?></td>
                    <td><?php echo getGameName($tournament['format']); ?></td>
                    <td>
                      <span class="status <?php 
                        echo ($tournament['status'] == 'aktif') ? 'active' : 
                             (($tournament['status'] == 'akan datang') ? 'approved' : 
                             (($tournament['status'] == 'selesai') ? 'completed' : 'inactive')); 
                      ?>">
                        <?php 
                        echo ($tournament['status'] == 'aktif') ? 'Berlangsung' : 
                             (($tournament['status'] == 'akan datang') ? 'Akan Datang' : 
                             (($tournament['status'] == 'selesai') ? 'Selesai' : ucfirst($tournament['status']))); 
                        ?>
                      </span>
                    </td>
                    <td><?php echo formatDate($tournament['tanggal_mulai']); ?></td>
                    <td><?php echo formatPrize($tournament['hadiah_turnamen']); ?></td>
                    <td><?php echo $tournament['current_participants']; ?>/<?php echo $tournament['max_participants']; ?></td>
                    <td>
                      <div class="action-buttons">
                        <button class="btn-view" title="Detail" onclick="viewTournamentDetail('T<?php echo str_pad($tournament['id_turnamen'], 3, '0', STR_PAD_LEFT); ?>')">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-delete" title="Hapus" onclick="deleteTournament('T<?php echo str_pad($tournament['id_turnamen'], 3, '0', STR_PAD_LEFT); ?>', '<?php echo htmlspecialchars($tournament['nama_turnamen']); ?>')">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  
                  <?php if (count($tournamentData['all']) == 0): ?>
                  <tr>
                    <td colspan="9" style="text-align: center; padding: 2rem;">
                      <p>Belum ada turnamen yang terdaftar.</p>
                    </td>
                  </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- Reports Section -->
        <section id="reports-section" class="content-section">
          <div class="section-header">
            <h2>Laporan & Statistik</h2>
            <div class="header-actions">
              <button class="btn-secondary">
                <i class="fas fa-download"></i>
                Export Report
              </button>
              <button class="btn-primary">
                <i class="fas fa-chart-line"></i>
                Generate Report
              </button>
            </div>
          </div>

          <div class="reports-grid">
            <div class="chart-container">
              <div class="chart-header">
                <h3>Aktivitas Platform</h3>
                <select class="chart-filter">
                  <option>7 Hari Terakhir</option>
                  <option>30 Hari Terakhir</option>
                  <option>3 Bulan Terakhir</option>
                </select>
              </div>
              <div class="chart-content">
                <canvas id="activityChart"></canvas>
              </div>
            </div>

            <div class="chart-container">
              <div class="chart-header">
                <h3>Game Populer</h3>
              </div>
              <div class="chart-content">
                <canvas id="popularGamesChart"></canvas>
              </div>
            </div>
          </div>
        </section>
      </div>
    </main>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal hidden">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Tambah Pengguna Baru</h3>
          <button class="modal-close" data-modal-hide="addUserModal" type="button">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form id="addUserForm">
            <!-- Role Selection - Made more prominent -->
            <div class="form-group role-selection">
              <label class="form-label">
                <i class="fas fa-user-tag"></i>
                Pilih Role Pengguna
              </label>
              <select id="userType" name="userType" class="form-select role-select" required>
                <option value="">-- Pilih Role --</option>
                <option value="admin">
                  <i class="fas fa-user-shield"></i> Administrator
                </option>
                <option value="eo">
                  <i class="fas fa-user-tie"></i> Event Organizer
                </option>
                <option value="player">
                  <i class="fas fa-gamepad"></i> Player
                </option>
              </select>
              <p class="role-description" id="roleDescription">Pilih role untuk menentukan jenis pengguna yang akan ditambahkan</p>
            </div>
            
            <div class="form-divider"></div>
            
            <div class="form-group">
              <label class="form-label">Username</label>
              <input type="text" id="username" name="username" class="form-input" required placeholder="Masukkan username">
            </div>
            
            <div class="form-group">
              <label class="form-label">Email</label>
              <input type="email" id="email" name="email" class="form-input" required placeholder="Masukkan email">
            </div>
            
            <div class="form-group">
              <label class="form-label">Password</label>
              <input type="password" id="password" name="password" class="form-input" required placeholder="Masukkan password">
            </div>
            
            <div class="form-group">
              <label class="form-label">Konfirmasi Password</label>
              <input type="password" id="confirmPassword" name="confirmPassword" class="form-input" required placeholder="Konfirmasi password">
            </div>
            
            <!-- EO specific fields -->
            <div id="organisasiField" class="form-group role-specific-field" style="display:none;">
              <label class="form-label">
                <i class="fas fa-building"></i>
                Nama Organisasi
              </label>
              <input type="text" id="organisasi" name="organisasi" class="form-input" placeholder="Masukkan nama organisasi">
            </div>
            
            <!-- Player specific fields -->
            <div id="nicknameField" class="form-group role-specific-field" style="display:none;">
              <label class="form-label">
                <i class="fas fa-user-ninja"></i>
                Nickname
              </label>
              <input type="text" id="nickname" name="nickname" class="form-input" placeholder="Masukkan nickname">
            </div>
            
            <div id="idGameField" class="form-group role-specific-field" style="display:none;">
              <label class="form-label">
                <i class="fas fa-id-card"></i>
                ID Game
              </label>
              <input type="text" id="idGame" name="idGame" class="form-input" placeholder="Masukkan ID game">
            </div>
            
            <div class="form-group">
              <label class="form-label">Status</label>
              <select id="status" name="status" class="form-select" required>
                <option value="active">Aktif</option>
                <option value="inactive">Tidak Aktif</option>
              </select>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-modal-hide="addUserModal">
            Batal
          </button>
          <button type="submit" form="addUserForm" class="btn btn-primary">
            <i class="fas fa-save"></i>
            Simpan Pengguna
          </button>
        </div>
      </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal hidden">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Edit Pengguna</h3>
          <button class="modal-close" data-modal-hide="editUserModal" type="button">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form id="editUserForm">
            <div class="form-group">
              <label class="form-label">Tipe Pengguna</label>
              <select id="editUserType" name="userType" class="form-select" disabled>
                <option value="admin">Administrator</option>
                <option value="eo">Event Organizer</option>
                <option value="player">Player</option>
              </select>
            </div>
            
            <div class="form-group">
              <label class="form-label">Username</label>
              <input type="text" id="editUsername" name="username" class="form-input" required placeholder="Masukkan username">
            </div>
            
            <div class="form-group">
              <label class="form-label">Email</label>
              <input type="email" id="editEmail" name="email" class="form-input" required placeholder="Masukkan email">
            </div>
            
            <div class="form-group password-field">
              <label class="form-label">Password Baru (opsional)</label>
              <input type="password" id="editPassword" name="password" class="form-input" placeholder="Kosongkan jika tidak ingin mengubah password">
              <p class="password-note">Kosongkan jika tidak ingin mengubah password</p>
            </div>
            
            <!-- EO specific fields -->
            <div id="editOrganisasiField" class="form-group" style="display:none;">
              <label class="form-label">Nama Organisasi</label>
              <input type="text" id="editOrganisasi" name="organisasi" class="form-input" placeholder="Masukkan nama organisasi">
            </div>
            
            <!-- Player specific fields -->
            <div id="editNicknameField" class="form-group" style="display:none;">
              <label class="form-label">Nickname</label>
              <input type="text" id="editNickname" name="nickname" class="form-input" placeholder="Masukkan nickname">
            </div>
            
            <div id="editIdGameField" class="form-group" style="display:none;">
              <label class="form-label">ID Game</label>
              <input type="text" id="editIdGame" name="idGame" class="form-input" placeholder="Masukkan ID game">
            </div>
            
            <div class="form-group">
              <label class="form-label">Status</label>
              <select id="editStatus" name="status" class="form-select" required>
                <option value="active">Aktif</option>
                <option value="inactive">Tidak Aktif</option>
              </select>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-modal-hide="editUserModal">
            Batal
          </button>
          <button type="submit" form="editUserForm" class="btn btn-primary">
            <i class="fas fa-save"></i>
            Update
          </button>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../../SCRIPT/ADMIN/dashboardAdmin.js"></script>
    <script src="../../SCRIPT/ADMIN/userManagement.js"></script>
    <script src="../../SCRIPT/ADMIN/partyTeamManagement.js"></script>
    <script>
      // Chart data from PHP
      let chartData = <?php echo json_encode($chartData); ?>;
      
      // Add custom styles for better layout
      const customStyles = `
        <style>
          :root {
            --primary-color: #ff0000;
            --secondary-color: #950101;
            --background: #000000;
            --surface: #1a1a1a;
            --surface-light: #2a2a2a;
            --text-color: #ffffff;
            --text-muted: #cccccc;
            --border-color: rgba(255, 255, 255, 0.1);
          }
          
          .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
          }
          
          .chart-container {
            background: var(--surface);
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            min-height: 350px;
          }
          
          .recent-tournaments {
            background: var(--surface);
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            grid-column: span 1;
          }
          
          .tournament-list {
            max-height: 300px;
            overflow-y: auto;
          }
          
          .tournament-item {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
          }
          
          .tournament-item:last-child {
            border-bottom: none;
          }
          
          .tournament-info h4 {
            color: var(--text-color);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
          }
          
          .tournament-info p {
            color: var(--text-muted);
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
          }
          
          .tournament-stats {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.25rem;
          }
          
          .tournament-stats span {
            background: var(--surface-light);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            color: var(--text-color);
          }
          
          .status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
          }
          
          .status.pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid #ffc107;
          }
          
          .status.approved {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid #28a745;
          }
          
          .status.active {
            background: rgba(23, 162, 184, 0.2);
            color: #17a2b8;
            border: 1px solid #17a2b8;
          }
          
          .view-all {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
          }
          
          .view-all:hover {
            text-decoration: underline;
          }
          
          .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
          }
          
          .section-header h3 {
            color: var(--text-color);
            font-size: 1.1rem;
            font-weight: 600;
          }
          
          /* Ensure charts are properly sized */
          .chart-content {
            position: relative;
            height: 280px;
            width: 100%;
          }
          
          .chart-content canvas {
            max-height: 280px !important;
          }
          
          /* Fix for grid layout */
          @media (max-width: 1200px) {
            .dashboard-grid {
              grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            }
          }
          
          @media (max-width: 768px) {
            .dashboard-grid {
              grid-template-columns: 1fr;
            }
          }
          
          /* Tournament Modal Styles - Match Team Modal Style */
          .team-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.8);
            z-index: 10000;
            display: flex;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(3px);
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
          }
          
          .team-modal-content {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
            max-height: calc(100vh - 40px);
            width: 100%;
            max-width: 1200px;
            overflow: hidden;
            border: 1px solid #333;
            display: flex;
            flex-direction: column;
            position: relative;
            transform: translateX(0) translateY(0);
          }
          
          .team-modal-header {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            padding: 1rem 1.5rem;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #ef4444;
          }
          
          .team-modal-header h3 {
            margin: 0;
            color: white;
            font-size: 1.2rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
          }
          
          .team-modal-close {
            background: none;
            border: none;
            font-size: 2rem;
            color: white;
            cursor: pointer;
            padding: 0;
            line-height: 1;
            font-weight: 300;
            transition: all 0.2s ease;
          }
          
          .team-modal-close:hover {
            color: #fca5a5;
            transform: scale(1.1);
          }
          
          .team-modal-body {
            padding: 1.5rem;
            background: #1a1a1a;
            overflow-y: auto;
            flex: 1;
          }
          
          .team-search-container {
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: center;
          }
          
          .team-search-input {
            background: #2d2d2d;
            border: 1px solid #404040;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            color: white;
            font-size: 1rem;
            width: 100%;
            max-width: 400px;
            transition: all 0.2s ease;
          }
          
          .team-search-input:focus {
            outline: none;
            border-color: #dc2626;
            box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.2);
          }
          
          .team-search-input::placeholder {
            color: #999;
          }
          
          .team-table-container {
            max-height: 500px;
            overflow-y: auto;
            border-radius: 8px;
            border: 1px solid #333;
          }
          
          .team-table {
            width: 100%;
            border-collapse: collapse;
            background: #2d2d2d;
          }
          
          .team-table thead {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            position: sticky;
            top: 0;
            z-index: 10;
          }
          
          .team-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 700;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.875rem;
            border-bottom: 2px solid #ef4444;
          }
          
          .team-table td {
            padding: 1rem;
            border-bottom: 1px solid #404040;
            color: white;
            vertical-align: middle;
          }
          
          .team-table tbody tr {
            background: #2d2d2d;
            transition: all 0.2s ease;
          }
          
          .team-table tbody tr:hover {
            background: #3a3a3a;
          }
          
          .team-table tbody tr:nth-child(even) {
            background: #252525;
          }
          
          .team-table tbody tr:nth-child(even):hover {
            background: #3a3a3a;
          }
          
          .team-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
          }
          
          .team-icon {
            color: #fbbf24;
            font-size: 1.1rem;
          }
          
          .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
          }
          
          .status-badge.active {
            background: #059669;
            color: white;
          }
          
          .status-badge.approved {
            background: #2563eb;
            color: white;
          }
          
          .status-badge.pending {
            background: #d97706;
            color: white;
          }
          
          .status-badge.completed {
            background: #6b7280;
            color: white;
          }
          
          .status-badge.inactive {
            background: #dc2626;
            color: white;
          }
          
          .action-buttons {
            display: flex;
            gap: 0.5rem;
          }
          
          .action-btn {
            background: none;
            border: 1px solid;
            border-radius: 6px;
            padding: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
          }
          
          .view-btn {
            border-color: #fbbf24;
            color: #fbbf24;
          }
          
          .view-btn:hover {
            background: #fbbf24;
            color: #1a1a1a;
          }
          
          .delete-btn {
            border-color: #dc2626;
            color: #dc2626;
          }
          
          .delete-btn:hover {
            background: #dc2626;
            color: white;
          }
          
          .team-pagination {
            margin-top: 1.5rem;
            display: flex;
            justify-content: center;
          }
          
          .pagination-controls {
            display: flex;
            gap: 0.5rem;
            align-items: center;
          }
          
          .pagination-btn {
            background: #2d2d2d;
            border: 1px solid #404040;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.875rem;
          }
          
          .pagination-btn:hover:not(:disabled) {
            background: #dc2626;
            border-color: #dc2626;
          }
          
          .pagination-btn.active {
            background: #dc2626;
            border-color: #dc2626;
            cursor: not-allowed;
          }
          
          .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
          }
          
          .team-modal-footer {
            padding: 1rem 1.5rem;
            background: #1a1a1a;
            border-top: 1px solid #333;
            border-radius: 0 0 12px 12px;
            display: flex;
            justify-content: flex-end;
          }
          
          .team-close-btn {
            background: #6b7280;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
          }
          
          .team-close-btn:hover {
            background: #4b5563;
          }
          
          /* Detail Modal Specific Styles */
          .detail-card {
            background: #2d2d2d;
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid #404040;
          }
          
          .detail-card h4 {
            margin: 0 0 1rem 0;
            color: #fbbf24;
            font-size: 1.1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #fbbf24;
            padding-bottom: 0.5rem;
          }
          
          .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #404040;
          }
          
          .detail-item:last-child {
            border-bottom: none;
          }
          
          .detail-label {
            font-weight: 600;
            color: #d1d5db;
            min-width: 150px;
          }
          
          .detail-value {
            color: white;
            text-align: right;
          }
          
          .description-box {
            background: #1a1a1a;
            border-radius: 6px;
            padding: 1rem;
            color: #d1d5db;
            border: 1px solid #404040;
            min-height: 80px;
          }
          
          .participants-container {
            max-height: 300px;
            overflow-y: auto;
            border-radius: 8px;
            border: 1px solid #404040;
          }
          
          .delete-tournament-btn {
            background: #dc2626;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
          }
          
          .delete-tournament-btn:hover {
            background: #b91c1c;
          }
          
          /* Button Styles */
          .btn-view {
            background: var(--info-color);
            color: white;
            border: none;
            padding: 0.375rem 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.875rem;
          }
          
          .btn-view:hover {
            background: var(--info-hover);
            transform: translateY(-1px);
          }
          
          .btn-delete {
            background: var(--danger-color);
            color: white;
            border: none;
            padding: 0.375rem 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.875rem;
          }
          
          .btn-delete:hover {
            background: var(--danger-hover);
            transform: translateY(-1px);
          }
          
          .btn-sm {
            font-size: 0.875rem;
            padding: 0.375rem 0.75rem;
          }
          
          .btn-primary {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
          }
          
          .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
          }
          
          .btn-primary:disabled {
            background: var(--primary-color);
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
          }
          
          .btn-secondary {
            background: var(--text-muted);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
          }
          
          .btn-secondary:hover {
            background: var(--text-color);
            transform: translateY(-1px);
          }
          
          .btn-danger {
            background: var(--danger-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
          }
          
          .btn-danger:hover {
            background: var(--danger-hover);
            transform: translateY(-1px);
          }
          
          /* Status styles */
          .status {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: capitalize;
          }
          
          .status.active {
            background: rgba(34, 197, 94, 0.1);
            color: #059669;
          }
          
          .status.approved {
            background: rgba(59, 130, 246, 0.1);
            color: #2563eb;
          }
          
          .status.pending {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
          }
          
          .status.completed {
            background: rgba(107, 114, 128, 0.1);
            color: #6b7280;
          }
          
          .status.inactive {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
          }
          
          .status.rejected {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
          }
          
          /* All Users Table Styles for Tournament Modal */
          .all-users-table {
            background: var(--surface);
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--border-color);
          }
          
          .all-users-table table {
            width: 100%;
            border-collapse: collapse;
            background: var(--surface);
          }
          
          .all-users-table th {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.875rem;
            border-bottom: 2px solid var(--primary-color);
          }
          
          .all-users-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-color);
            vertical-align: middle;
          }
          
          .all-users-table tbody tr {
            background: var(--surface);
            transition: all 0.2s ease;
          }
          
          .all-users-table tbody tr:hover {
            background: var(--surface-light);
          }
          
          .all-users-table tbody tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.02);
          }
          
          .all-users-table tbody tr:nth-child(even):hover {
            background: var(--surface-light);
          }
          
          .item-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
          }
          
          .item-icon {
            color: #fbbf24;
            font-size: 1.1rem;
          }
          
          .table-controls {
            padding: 1rem;
            background: var(--surface);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: center;
          }
          
          .search-input {
            background: var(--surface-light);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            color: var(--text-color);
            font-size: 1rem;
            width: 100%;
            max-width: 400px;
            transition: all 0.2s ease;
          }
          
          .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 0, 0, 0.2);
          }
          
          .search-input::placeholder {
            color: var(--text-muted);
          }
          
          .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-muted);
          }
          
          .empty-state i {
            display: block;
            margin-bottom: 1rem;
          }
          
          .empty-state p {
            margin: 0;
            font-size: 1.1rem;
          }
          
          .action-buttons {
            display: flex;
            gap: 0.5rem;
          }
          
          .btn-view, .btn-edit, .btn-delete {
            background: none;
            border: 1px solid;
            border-radius: 6px;
            padding: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
          }
          
          .btn-view {
            border-color: #3b82f6;
            color: #3b82f6;
          }
          
          .btn-view:hover {
            background: #3b82f6;
            color: white;
          }
          
          .btn-edit {
            border-color: #f59e0b;
            color: #f59e0b;
          }
          
          .btn-edit:hover {
            background: #f59e0b;
            color: white;
          }
          
          .btn-delete {
            border-color: #ef4444;
            color: #ef4444;
          }
          
          .btn-delete:hover {
            background: #ef4444;
            color: white;
          }
        </style>
      `;
      
      // Inject custom styles
      document.head.insertAdjacentHTML('beforeend', customStyles);
      
      // Initialize all charts when DOM is loaded
      document.addEventListener('DOMContentLoaded', function() {
        // Set global reference for charts access
        window.chartData = chartData;
        
        // Charts will be initialized by the DashboardAdmin class
        // No need to initialize them here to avoid duplicate initialization
        
        // Auto-refresh data every 5 minutes
        setInterval(refreshChartData, 300000);
        
        if (typeof initializeUserManagement === 'function') {
          initializeUserManagement();
        }
        
        // Ensure layout is properly rendered
        setTimeout(() => {
          console.log('Dashboard layout initialized');
          // Force a repaint to ensure everything is rendered correctly
          document.body.style.display = 'none';
          document.body.offsetHeight; // Trigger reflow
          document.body.style.display = '';
        }, 500);
      });
      
      // Function to refresh chart data from API
      async function refreshChartData() {
        try {
          const response = await fetch('dashboard_chart_api.php?action=refresh');
          const newData = await response.json();
          
          if (!newData.error) {
            chartData = newData;
            updateAllCharts();
            console.log('Chart data refreshed at:', newData.timestamp);
          }
        } catch (error) {
          console.error('Error refreshing chart data:', error);
        }
      }
      
      // Function to update all chart instances with new data
      function updateAllCharts() {
        // Use window.charts to access the charts from the DashboardAdmin class
        const charts = window.charts;
        
        if (!charts) {
          console.warn('Charts not available for update');
          return;
        }
        
        // Update login statistics
        if (charts.userLoginStats) {
          charts.userLoginStats.data.labels = chartData.monthly_logins.map(item => item.month);
          charts.userLoginStats.data.datasets[0].data = chartData.monthly_logins.map(item => item.players);
          charts.userLoginStats.data.datasets[1].data = chartData.monthly_logins.map(item => item.eos);
          charts.userLoginStats.data.datasets[2].data = chartData.monthly_logins.map(item => item.admins);
          charts.userLoginStats.update();
        }
        
        if (charts.dailyLogins) {
          charts.dailyLogins.data.labels = chartData.daily_logins.map(item => item.day);
          charts.dailyLogins.data.datasets[0].data = chartData.daily_logins.map(item => item.players);
          charts.dailyLogins.data.datasets[1].data = chartData.daily_logins.map(item => item.eos);
          charts.dailyLogins.update();
        }
        
        // Update registration statistics
        if (charts.userRegistrationStats) {
          charts.userRegistrationStats.data.labels = chartData.monthly_registrations.map(item => item.month);
          charts.userRegistrationStats.data.datasets[0].data = chartData.monthly_registrations.map(item => item.players);
          charts.userRegistrationStats.data.datasets[1].data = chartData.monthly_registrations.map(item => item.eos);
          charts.userRegistrationStats.update();
        }
        
        if (charts.dailyRegistrations) {
          charts.dailyRegistrations.data.labels = chartData.daily_registrations.map(item => item.day);
          charts.dailyRegistrations.data.datasets[0].data = chartData.daily_registrations.map(item => item.players);
          charts.dailyRegistrations.data.datasets[1].data = chartData.daily_registrations.map(item => item.eos);
          charts.dailyRegistrations.update();
        }
        
        // Update tournament charts
        if (charts.tournamentStatus) {
          charts.tournamentStatus.data.labels = chartData.tournament_status.map(item => item.status);
          charts.tournamentStatus.data.datasets[0].data = chartData.tournament_status.map(item => item.count);
          charts.tournamentStatus.update();
        }
        
        if (charts.tournamentFormat) {
          charts.tournamentFormat.data.labels = chartData.tournament_format.map(item => item.format);
          charts.tournamentFormat.data.datasets[0].data = chartData.tournament_format.map(item => item.count);
          charts.tournamentFormat.update();
        }
        
        if (charts.topTeams) {
          charts.topTeams.data.labels = chartData.top_teams.map(item => item.name);
          charts.topTeams.data.datasets[0].data = chartData.top_teams.map(item => item.points);
          charts.topTeams.update();
        }
      }
      
      // Manual refresh button function
      function manualRefreshCharts() {
        // Call refresh directly which will update existing charts
        refreshChartData();
        
        // Optionally, if charts need to be completely reinitialized
        // if (window.dashboardAdminInstance) {
        //   window.dashboardAdminInstance.initializeCharts();
        // }
      }
      
      // Tournament Management Functions
      function approveTournament(tournamentId, tournamentName) {
        if (confirm(`Apakah Anda yakin ingin menyetujui turnamen "${tournamentName}"?`)) {
          // Add approve logic here
          console.log(`Approving tournament: ${tournamentId}`);
          // You can add AJAX call to approve tournament
          alert(`Turnamen "${tournamentName}" berhasil disetujui!`);
          // Remove from pending table or refresh
        }
      }
      
      function rejectTournament(tournamentId, tournamentName) {
        const reason = prompt(`Alasan menolak turnamen "${tournamentName}":`);
        if (reason) {
          // Add reject logic here
          console.log(`Rejecting tournament: ${tournamentId} - Reason: ${reason}`);
          // You can add AJAX call to reject tournament
          alert(`Turnamen "${tournamentName}" berhasil ditolak!`);
          // Remove from pending table or refresh
        }
      }
      
      function viewTournamentDetail(tournamentId) {
        // Add view detail logic here
        console.log(`Viewing tournament detail: ${tournamentId}`);
        // You can open a modal or redirect to detail page
        alert(`Menampilkan detail turnamen ID: ${tournamentId}`);
      }
      
      function editTournament(tournamentId) {
        // Add edit logic here
        console.log(`Editing tournament: ${tournamentId}`);
        // You can open edit modal
        alert(`Edit turnamen ID: ${tournamentId}`);
      }
      
      function suspendTournament(tournamentId) {
        if (confirm(`Apakah Anda yakin ingin suspend turnamen ID: ${tournamentId}?`)) {
          // Add suspend logic here
          console.log(`Suspending tournament: ${tournamentId}`);
          alert(`Turnamen ID: ${tournamentId} berhasil di-suspend!`);
        }
      }
      
      function archiveTournament(tournamentId) {
        if (confirm(`Apakah Anda yakin ingin mengarsipkan turnamen ID: ${tournamentId}?`)) {
          // Add archive logic here
          console.log(`Archiving tournament: ${tournamentId}`);
          alert(`Turnamen ID: ${tournamentId} berhasil diarsipkan!`);
        }
      }
      
      function viewAllTournaments() {
        // Create and show modal for all tournaments
        showAllTournamentsModal();
      }
      
      // Function to show all tournaments in a modal
      function showAllTournamentsModal() {
        // Create modal HTML with improved styling to match other modals
        const modalHTML = 
          '<div id="allTournamentsModal" class="modal" style="display: flex !important;">' +
            '<div class="modal-content" style="max-width: 90vw; width: 1200px;">' +
              '<div class="modal-header">' +
                '<h3>SEMUA TURNAMEN (<span id="tournamentCountHeader">0</span>)</h3>' +
                '<button class="modal-close" onclick="closeAllTournamentsModal()" type="button">' +
                  '<i class="fas fa-times"></i>' +
                '</button>' +
              '</div>' +
              '<div class="modal-body">' +
                '<div class="table-controls">' +
                  '<input type="text" id="tournamentSearchInput" placeholder="Cari turnamen..." class="search-input" oninput="searchTournamentsRealtime()">' +
                '</div>' +
                '<div id="allTournamentsTableContainer" class="all-users-table">' +
                  '<div style="text-align: center; padding: 2rem; color: #fff;">' +
                    '<i class="fas fa-spinner fa-spin"></i> Loading tournaments...' +
                  '</div>' +
                '</div>' +
              '</div>' +
              '<div class="modal-footer">' +
                '<button type="button" class="btn btn-secondary" onclick="closeAllTournamentsModal()">Tutup</button>' +
              '</div>' +
            '</div>' +
          '</div>';
        
        // Remove existing modal if any
        const existingModal = document.getElementById('allTournamentsModal');
        if (existingModal) {
          existingModal.remove();
        }
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Load tournaments data
        loadAllTournaments();
      }
      
      // Function to close all tournaments modal
      function closeAllTournamentsModal() {
        const modal = document.getElementById('allTournamentsModal');
        if (modal) {
          modal.remove();
        }
      }
      
      // Function to load all tournaments data
      async function loadAllTournaments() {
        try {
          const response = await fetch('tournament_api.php?action=get_all&limit=1000'); // Get all data at once
          
          console.log('API Response Status:', response.status);
          
          const responseText = await response.text();
          console.log('Raw Response:', responseText);
          
          let data;
          try {
            data = JSON.parse(responseText);
          } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.error('Response Text:', responseText);
            throw new Error('Invalid JSON response: ' + responseText.substring(0, 100));
          }
          
          if (data.success) {
            // Store all tournaments globally for search
            window.allTournaments = data.tournaments;
            displayTournamentsTable(data.tournaments);
            updateTournamentCount(data.tournaments.length);
          } else {
            console.error('Error loading tournaments:', data.message);
            document.getElementById('allTournamentsTableContainer').innerHTML = 
              '<div style="text-align: center; padding: 2rem; color: var(--danger-color);">Error loading tournaments: ' + data.message + '</div>';
          }
        } catch (error) {
          console.error('Error:', error);
          document.getElementById('allTournamentsTableContainer').innerHTML = 
            '<div style="text-align: center; padding: 2rem; color: var(--danger-color);">Error loading tournaments: ' + error.message + '</div>';
        }
      }
      
      // Function to display tournaments table
      function displayTournamentsTable(tournaments) {
        console.log('Displaying tournaments:', tournaments);
        
        if (tournaments.length === 0) {
          document.getElementById('allTournamentsTableContainer').innerHTML = `
            <div class="empty-state">
              <i class="fas fa-trophy" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
              <p>Belum ada turnamen yang terdaftar.</p>
            </div>`;
          return;
        }
        
        let tableHTML = 
          '<table>' +
            '<thead>' +
              '<tr>' +
                '<th>ID</th>' +
                '<th>NAMA</th>' +
                '<th>ORGANIZER</th>' +
                '<th>STATUS</th>' +
                '<th>PRIZE POOL</th>' +
                '<th>PESERTA</th>' +
                '<th>DIBUAT</th>' +
                '<th>AKSI</th>' +
              '</tr>' +
            '</thead>' +
            '<tbody>';
            
        tournaments.forEach((tournament, index) => {
          // Use the real id_turnamen from database, not formatted
          const tournamentId = tournament.id_turnamen;
          console.log(`Tournament ${index}: ID = ${tournamentId} (type: ${typeof tournamentId})`);
          
          const statusClass = getStatusClass(tournament.status);
          const statusText = getStatusText(tournament.status);
          
          tableHTML += 
            '<tr>' +
              '<td>' + tournamentId + '</td>' +
              '<td>' +
                '<div class="item-info">' +
                  '<i class="fas fa-trophy item-icon"></i>' +
                  '<span>' + tournament.nama_turnamen + '</span>' +
                '</div>' +
              '</td>' +
              '<td>' + (tournament.eo_name || tournament.organisasi || '-') + '</td>' +
              '<td><span class="status ' + statusClass + '">' + statusText + '</span></td>' +
              '<td>Rp ' + formatNumber(tournament.hadiah_turnamen) + '</td>' +
              '<td>' + tournament.current_participants + '/' + tournament.max_participants + '</td>' +
              '<td>' + formatDate(tournament.tanggal_mulai) + '</td>' +
              '<td>' +
                '<div class="action-buttons">' +
                  '<button class="btn-view" title="Detail" onclick="viewTournamentDetail(\'' + tournamentId + '\')">' +
                    '<i class="fas fa-eye"></i>' +
                  '</button>' +
                  '<button class="btn-delete" title="Hapus" onclick="deleteTournament(\'' + tournamentId + '\', \'' + tournament.nama_turnamen + '\')">' +
                    '<i class="fas fa-trash"></i>' +
                  '</button>' +
                '</div>' +
              '</td>' +
            '</tr>';
        });
        
        tableHTML += '</tbody></table>';
        
        document.getElementById('allTournamentsTableContainer').innerHTML = tableHTML;
      }
      
      // Helper functions
      function getStatusClass(status) {
        switch(status) {
          case 'aktif': return 'active';
          case 'akan datang': return 'approved';
          case 'selesai': return 'completed';
          default: return 'inactive';
        }
      }
      
      function getStatusText(status) {
        switch(status) {
          case 'aktif': return 'Berlangsung';
          case 'akan datang': return 'Akan Datang';
          case 'selesai': return 'Selesai';
          default: return status;
        }
      }
      
      function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', { 
          day: '2-digit', 
          month: 'short', 
          year: 'numeric' 
        });
      }
      
      function formatDateTime(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', { 
          day: '2-digit', 
          month: 'short', 
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });
      }
      
      function formatNumber(number) {
        return new Intl.NumberFormat('id-ID').format(number);
      }
      
      // Realtime search function
      function searchTournamentsRealtime() {
        const searchTerm = document.getElementById('tournamentSearchInput').value.toLowerCase();
        
        if (!window.allTournaments) {
          return;
        }
        
        const filteredTournaments = window.allTournaments.filter(tournament => 
          tournament.nama_turnamen.toLowerCase().includes(searchTerm) ||
          (tournament.eo_name && tournament.eo_name.toLowerCase().includes(searchTerm)) ||
          (tournament.organisasi && tournament.organisasi.toLowerCase().includes(searchTerm)) ||
          tournament.status.toLowerCase().includes(searchTerm) ||
          tournament.format.toLowerCase().includes(searchTerm)
        );
        
        displayTournamentsTable(filteredTournaments);
        updateTournamentCount(filteredTournaments.length);
      }
      
      function updateTournamentCount(total) {
        document.getElementById('tournamentCountHeader').textContent = total;
      }
      
      // Function to view tournament detail
      function viewTournamentDetail(tournamentId) {
        // Create detail modal
        showTournamentDetailModal(tournamentId);
      }
      
      // Function to show tournament detail modal
      async function showTournamentDetailModal(tournamentId) {
        try {
          const response = await fetch('tournament_api.php?action=get_detail&id=' + tournamentId);
          const data = await response.json();
          
          if (data.success) {
            const tournament = data.tournament;
            
            // Calculate registration period
            const regStart = new Date(tournament.pendaftaran_mulai);
            const regEnd = new Date(tournament.pendaftaran_selesai);
            const now = new Date();
            
            let regStatus = '';
            if (now < regStart) {
              regStatus = '<span style="color: #d97706;">Belum Dibuka</span>';
            } else if (now > regEnd) {
              regStatus = '<span style="color: #dc2626;">Sudah Ditutup</span>';
            } else {
              regStatus = '<span style="color: #059669;">Sedang Berjalan</span>';
            }
            
            const modalHTML = 
              '<div id="tournamentDetailModal" class="modal" style="display: flex !important;">' +
                '<div class="modal-content" style="max-width: 1000px; width: 95%;">' +
                  '<div class="modal-header">' +
                    '<h3>DETAIL TURNAMEN - ' + tournament.nama_turnamen.toUpperCase() + '</h3>' +
                    '<button class="modal-close" onclick="closeTournamentDetailModal()" type="button">' +
                      '<i class="fas fa-times"></i>' +
                    '</button>' +
                  '</div>' +
                  '<div class="modal-body">' +
                    '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">' +
                      '<div class="detail-card">' +
                        '<h4><i class="fas fa-trophy"></i> Informasi Turnamen</h4>' +
                        '<div class="detail-item"><span class="detail-label">ID Turnamen:</span><span class="detail-value">' + tournament.id_turnamen + '</span></div>' +
                        '<div class="detail-item"><span class="detail-label">Nama Turnamen:</span><span class="detail-value">' + tournament.nama_turnamen + '</span></div>' +
                        '<div class="detail-item"><span class="detail-label">Event Organizer:</span><span class="detail-value">' + (tournament.eo_name || '-') + '</span></div>' +
                        '<div class="detail-item"><span class="detail-label">Organisasi:</span><span class="detail-value">' + (tournament.organisasi || '-') + '</span></div>' +
                        '<div class="detail-item"><span class="detail-label">Format:</span><span class="detail-value">' + (tournament.format === 'team' ? 'Tim' : 'Individu') + '</span></div>' +
                        '<div class="detail-item"><span class="detail-label">Status:</span><span class="detail-value"><span class="status-badge ' + getStatusClass(tournament.status) + '">' + getStatusText(tournament.status) + '</span></span></div>' +
                        '<div class="detail-item"><span class="detail-label">Dibuat:</span><span class="detail-value">' + formatDateTime(tournament.created_at) + '</span></div>' +
                      '</div>' +
                      '<div class="detail-card">' +
                        '<h4><i class="fas fa-coins"></i> Detail Finansial & Peserta</h4>' +
                        '<div class="detail-item"><span class="detail-label">Prize Pool:</span><span class="detail-value" style="color: #fbbf24; font-weight: bold;">Rp ' + formatNumber(tournament.hadiah_turnamen) + '</span></div>' +
                        '<div class="detail-item"><span class="detail-label">Biaya Pendaftaran:</span><span class="detail-value">Rp ' + formatNumber(tournament.biaya_turnamen) + '</span></div>' +
                        '<div class="detail-item"><span class="detail-label">Max Peserta:</span><span class="detail-value">' + tournament.max_participants + '</span></div>' +
                        '<div class="detail-item"><span class="detail-label">Peserta Terdaftar:</span><span class="detail-value" style="color: #059669;">' + tournament.current_participants + '</span></div>' +
                        '<div class="detail-item"><span class="detail-label">Slot Tersisa:</span><span class="detail-value">' + (tournament.max_participants - tournament.current_participants) + '</span></div>' +
                        '<div class="detail-item"><span class="detail-label">Total Pendapatan:</span><span class="detail-value">Rp ' + formatNumber(tournament.pendapatan || 0) + '</span></div>' +
                      '</div>' +
                    '</div>' +
                    '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">' +
                      '<div class="detail-card">' +
                        '<h4><i class="fas fa-calendar-alt"></i> Jadwal Turnamen</h4>' +
                        '<div class="detail-item"><span class="detail-label">Pendaftaran Mulai:</span><span class="detail-value">' + formatDateTime(tournament.pendaftaran_mulai) + '</span></div>' +
                        '<div class="detail-item"><span class="detail-label">Pendaftaran Selesai:</span><span class="detail-value">' + formatDateTime(tournament.pendaftaran_selesai) + '</span></div>' +
                        '<div class="detail-item"><span class="detail-label">Status Registrasi:</span><span class="detail-value">' + regStatus + '</span></div>' +
                        '<div class="detail-item"><span class="detail-label">Turnamen Mulai:</span><span class="detail-value">' + formatDateTime(tournament.tanggal_mulai) + '</span></div>' +
                        '<div class="detail-item"><span class="detail-label">Turnamen Selesai:</span><span class="detail-value">' + (tournament.tanggal_selesai ? formatDateTime(tournament.tanggal_selesai) : 'Belum Selesai') + '</span></div>' +
                      '</div>' +
                      '<div class="detail-card">' +
                        '<h4><i class="fas fa-cogs"></i> Pengaturan Lainnya</h4>' +
                        '<div class="detail-item"><span class="detail-label">Slot Total:</span><span class="detail-value">' + tournament.slot + '</span></div>' +
                        '<div class="detail-item"><span class="detail-label">ID EO:</span><span class="detail-value">' + tournament.id_eo + '</span></div>' +
                        '<div class="detail-item"><span class="detail-label">Logo Turnamen:</span><span class="detail-value">' + (tournament.logo_turnamen || 'Default') + '</span></div>' +
                        '<div class="detail-item"><span class="detail-label">Last Update:</span><span class="detail-value">' + formatDateTime(tournament.updated_at) + '</span></div>' +
                      '</div>' +
                    '</div>' +
                    '<div class="detail-card">' +
                      '<h4><i class="fas fa-info-circle"></i> Deskripsi Turnamen</h4>' +
                      '<div class="description-box">' +
                        (tournament.deskripsi_turnamen || 'Tidak ada deskripsi tersedia untuk turnamen ini.') +
                      '</div>' +
                    '</div>' +
                    '<div class="detail-card">' +
                      '<h4><i class="fas fa-scroll"></i> Aturan Turnamen</h4>' +
                      '<div class="description-box">' +
                        (tournament.aturan || 'Aturan standar turnamen berlaku.') +
                      '</div>' +
                    '</div>' +
                    '<div class="detail-card" style="margin-top: 2rem;">' +
                      '<h4><i class="fas fa-users"></i> Daftar Peserta (' + tournament.current_participants + '/' + tournament.max_participants + ')</h4>' +
                      '<div class="participants-container">' +
                        generateParticipantsTable(tournament.participants || []) +
                      '</div>' +
                    '</div>' +
                  '</div>' +
                  '<div class="modal-footer">' +
                    '<button type="button" class="btn btn-danger" onclick="deleteTournament(\'' + tournamentId + '\', \'' + tournament.nama_turnamen + '\')">' +
                      '<i class="fas fa-trash"></i> Hapus Turnamen' +
                    '</button>' +
                    '<button type="button" class="btn btn-secondary" onclick="closeTournamentDetailModal()">' +
                      'Tutup' +
                    '</button>' +
                  '</div>' +
                '</div>' +
              '</div>';
            
            // Remove existing modal if any
            const existingModal = document.getElementById('tournamentDetailModal');
            if (existingModal) {
              existingModal.remove();
            }
            
            // Add modal to body
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
          } else {
            alert('Error loading tournament detail: ' + data.message);
          }
          
        } catch (error) {
          console.error('Error:', error);
          alert('Error loading tournament detail: ' + error.message);
        }
      }
      
      // Function to generate participants table
      function generateParticipantsTable(participants) {
        if (!participants || participants.length === 0) {
          return '<div style="text-align: center; padding: 2rem; color: #999; background: #2d2d2d; border-radius: 8px; border: 1px solid #404040;">' +
                   '<i class="fas fa-users" style="font-size: 2rem; margin-bottom: 1rem; color: #666;"></i><br>' +
                   '<strong>Belum Ada Peserta</strong><br>' +
                   '<span style="font-size: 0.9rem;">Belum ada peserta yang mendaftar untuk turnamen ini</span>' +
                 '</div>';
        }
        
        let tableHTML = 
          '<table class="team-table" style="margin: 0;">' +
            '<thead>' +
              '<tr>' +
                '<th style="width: 50px;">#</th>' +
                '<th>Nama Peserta</th>' +
                '<th>Tim/Username</th>' +
                '<th>Status</th>' +
                '<th>Tanggal Daftar</th>' +
                '<th>Aksi</th>' +
              '</tr>' +
            '</thead>' +
            '<tbody>';
        
        participants.forEach((participant, index) => {
          const statusClass = participant.status === 'approved' ? 'active' : 
                            participant.status === 'pending' ? 'pending' : 'inactive';
          const statusText = participant.status === 'approved' ? 'Disetujui' : 
                           participant.status === 'pending' ? 'Menunggu' : 'Ditolak';
          
          tableHTML += 
            '<tr>' +
              '<td>' + (index + 1) + '</td>' +
              '<td>' +
                '<div class="team-info">' +
                  '<i class="fas fa-user team-icon"></i>' +
                  '<span>' + (participant.name || participant.username || 'Unknown') + '</span>' +
                '</div>' +
              '</td>' +
              '<td>' + (participant.team_name || participant.username || '-') + '</td>' +
              '<td><span class="status-badge ' + statusClass + '">' + statusText + '</span></td>' +
              '<td>' + formatDateTime(participant.created_at || participant.tanggal_daftar) + '</td>' +
              '<td>' +
                '<div class="action-buttons">' +
                  '<button onclick="viewParticipantDetail(\'' + (participant.id || participant.user_id) + '\')" class="action-btn view-btn" title="Detail Peserta">' +
                    '<i class="fas fa-eye"></i>' +
                  '</button>' +
                '</div>' +
              '</td>' +
            '</tr>';
        });
        
        tableHTML += '</tbody></table>';
        return tableHTML;
      }
      
      // Function to close tournament detail modal
      function closeTournamentDetailModal() {
        const modal = document.getElementById('tournamentDetailModal');
        if (modal) {
          modal.remove();
        }
      }
      
      // Function to view participant detail
      function viewParticipantDetail(participantId) {
        alert('Fitur detail peserta akan segera tersedia. ID: ' + participantId);
      }
      // Function to delete tournament
      async function deleteTournament(tournamentId, tournamentName) {
        if (confirm('Apakah Anda yakin ingin menghapus turnamen "' + tournamentName + '"? Tindakan ini tidak dapat dibatalkan.')) {
          try {
            const formData = new FormData();
            formData.append('id', tournamentId);
            
            const response = await fetch('tournament_api.php?action=delete', {
              method: 'POST',
              body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
              alert('Turnamen "' + tournamentName + '" berhasil dihapus!');
              
              // Close detail modal if open
              const detailModal = document.getElementById('tournamentDetailModal');
              if (detailModal) {
                detailModal.remove();
              }
              
              // Refresh the tournament list
              if (document.getElementById('allTournamentsModal')) {
                loadAllTournaments();
              }
              
              // Refresh dashboard data
              if (typeof loadTournamentData === 'function') {
                loadTournamentData();
              }
              
            } else {
              alert('Error menghapus turnamen: ' + data.message);
            }
            
          } catch (error) {
            console.error('Error:', error);
            alert('Error menghapus turnamen');
          }
        }
      }
      
      // Approve all pending tournaments
      document.getElementById('approveAllBtn')?.addEventListener('click', function() {
        if (confirm('Apakah Anda yakin ingin menyetujui semua request turnamen yang pending?')) {
          // Add approve all logic here
          console.log('Approving all pending tournaments');
          alert('Semua request turnamen berhasil disetujui!');
        }
      });
    </script>
  </body>
</html>

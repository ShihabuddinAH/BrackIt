<?php
include '../LOGIN/session.php';
include '../connect.php';

// Get current EO ID from session
$eo_id = $_SESSION['user_id'] ?? 1; // Default to 1 if not set

// Function to get dashboard statistics
function getDashboardStats($conn, $eo_id) {
    $stats = [];
    
    // Total Tournaments by EO
    $result = $conn->query("SELECT COUNT(*) as total FROM turnamen WHERE id_eo = $eo_id");
    $stats['total_tournaments'] = $result->fetch_assoc()['total'];
    
    // Active Tournaments
    $result = $conn->query("SELECT COUNT(*) as active FROM turnamen WHERE id_eo = $eo_id AND status = 'aktif'");
    $stats['active_tournaments'] = $result->fetch_assoc()['active'];
    
    // Total Revenue (sum of registration fees * registered participants)
    $result = $conn->query("
        SELECT SUM(CAST(biaya_turnamen AS UNSIGNED) * CAST(pendaftar AS UNSIGNED)) as revenue 
        FROM turnamen 
        WHERE id_eo = $eo_id
    ");
    $revenue = $result->fetch_assoc()['revenue'] ?? 0;
    $stats['total_revenue'] = $revenue;
    
    // Total Participants
    $result = $conn->query("SELECT SUM(CAST(pendaftar AS UNSIGNED)) as participants FROM turnamen WHERE id_eo = $eo_id");
    $stats['total_participants'] = $result->fetch_assoc()['participants'] ?? 0;
    
    return $stats;
}

// Function to get recent tournaments
function getRecentTournaments($conn, $eo_id, $limit = 3) {
    $query = "
        SELECT nama_turnamen, tanggal_mulai, status, slot, pendaftar, hadiah_turnamen 
        FROM turnamen 
        WHERE id_eo = $eo_id 
        ORDER BY created_at DESC 
        LIMIT $limit
    ";
    return $conn->query($query);
}

// Function to get all tournaments for table
function getAllTournaments($conn, $eo_id) {
    $query = "
        SELECT id_turnamen, nama_turnamen, format, tanggal_mulai, slot, pendaftar, 
               hadiah_turnamen, status, logo_turnamen, biaya_turnamen
        FROM turnamen 
        WHERE id_eo = $eo_id 
        ORDER BY tanggal_mulai DESC
    ";
    return $conn->query($query);
}

// Function to get monthly revenue data for chart
function getMonthlyRevenue($conn, $eo_id) {
    $query = "
        SELECT 
            MONTH(created_at) as month,
            YEAR(created_at) as year,
            SUM(CAST(biaya_turnamen AS UNSIGNED) * CAST(pendaftar AS UNSIGNED)) as revenue
        FROM turnamen 
        WHERE id_eo = $eo_id 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY YEAR(created_at), MONTH(created_at)
        ORDER BY year, month
    ";
    return $conn->query($query);
}

// Function to get tournament status distribution
function getTournamentStatusData($conn, $eo_id) {
    $query = "
        SELECT status, COUNT(*) as count 
        FROM turnamen 
        WHERE id_eo = $eo_id 
        GROUP BY status
    ";
    return $conn->query($query);
}

// Function to get tournament format distribution
function getTournamentFormatData($conn, $eo_id) {
    $query = "
        SELECT format, COUNT(*) as count 
        FROM turnamen 
        WHERE id_eo = $eo_id 
        GROUP BY format
    ";
    return $conn->query($query);
}

// Function to get participation trend
function getParticipationTrend($conn, $eo_id) {
    $query = "
        SELECT 
            MONTH(created_at) as month,
            YEAR(created_at) as year,
            SUM(CAST(pendaftar AS UNSIGNED)) as participants
        FROM turnamen 
        WHERE id_eo = $eo_id 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY YEAR(created_at), MONTH(created_at)
        ORDER BY year, month
    ";
    return $conn->query($query);
}

// Function to get top tournaments by participants
function getTopTournaments($conn, $eo_id, $limit = 5) {
    $query = "
        SELECT nama_turnamen, pendaftar, slot, hadiah_turnamen
        FROM turnamen 
        WHERE id_eo = $eo_id 
        ORDER BY CAST(pendaftar AS UNSIGNED) DESC 
        LIMIT $limit
    ";
    return $conn->query($query);
}

// Get data
$stats = getDashboardStats($conn, $eo_id);
$recentTournaments = getRecentTournaments($conn, $eo_id);
$allTournaments = getAllTournaments($conn, $eo_id);
$monthlyRevenue = getMonthlyRevenue($conn, $eo_id);
$tournamentStatus = getTournamentStatusData($conn, $eo_id);
$tournamentFormat = getTournamentFormatData($conn, $eo_id);
$participationTrend = getParticipationTrend($conn, $eo_id);
$topTournaments = getTopTournaments($conn, $eo_id);

// Prepare revenue data for JavaScript
$revenueData = [];
$revenueLabels = [];
while ($row = $monthlyRevenue->fetch_assoc()) {
    $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $revenueLabels[] = $monthNames[$row['month'] - 1] . ' ' . $row['year'];
    $revenueData[] = $row['revenue'];
}

// Prepare participation trend data
$participationData = [];
$participationLabels = [];
$participationTrend->data_seek(0); // Reset pointer
while ($row = $participationTrend->fetch_assoc()) {
    $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $participationLabels[] = $monthNames[$row['month'] - 1] . ' ' . $row['year'];
    $participationData[] = $row['participants'];
}

// Prepare status distribution data
$statusLabels = [];
$statusData = [];
$statusColors = [
    'aktif' => '#10b981',
    'selesai' => '#6b7280',
    'akan datang' => '#f59e0b'
];
$pieColors = [];
$tournamentStatus->data_seek(0); // Reset pointer
while ($row = $tournamentStatus->fetch_assoc()) {
    $statusLabels[] = ucwords(str_replace('_', ' ', $row['status']));
    $statusData[] = $row['count'];
    $pieColors[] = $statusColors[$row['status']] ?? '#8b5cf6';
}

// Prepare format distribution data
$formatLabels = [];
$formatData = [];
$tournamentFormat->data_seek(0); // Reset pointer
while ($row = $tournamentFormat->fetch_assoc()) {
    $formatLabels[] = ucfirst($row['format']);
    $formatData[] = $row['count'];
}

// Prepare top tournaments data
$topTournamentNames = [];
$topTournamentParticipants = [];
$topTournaments->data_seek(0); // Reset pointer
while ($row = $topTournaments->fetch_assoc()) {
    $topTournamentNames[] = $row['nama_turnamen'];
    $topTournamentParticipants[] = (int)$row['pendaftar'];
}

// Add default data if empty to prevent empty charts
if (empty($revenueLabels)) {
    $revenueLabels = ['Belum ada data'];
    $revenueData = [0];
}

if (empty($participationLabels)) {
    $participationLabels = ['Belum ada data'];
    $participationData = [0];
}

if (empty($statusLabels)) {
    $statusLabels = ['Belum ada turnamen'];
    $statusData = [1];
    $pieColors = ['#6b7280'];
}

if (empty($formatLabels)) {
    $formatLabels = ['Belum ada data'];
    $formatData = [1];
}

if (empty($topTournamentNames)) {
    $topTournamentNames = ['Belum ada turnamen'];
    $topTournamentParticipants = [0];
}

// Format revenue for display
function formatRupiah($amount) {
    if ($amount >= 1000000) {
        return 'Rp ' . number_format($amount / 1000000, 1) . 'M';
    } elseif ($amount >= 1000) {
        return 'Rp ' . number_format($amount / 1000, 1) . 'K';
    } else {
        return 'Rp ' . number_format($amount);
    }
}

// Format status for display
function formatStatus($status) {
    switch ($status) {
        case 'aktif':
            return 'Aktif';
        case 'selesai':
            return 'Selesai';
        case 'akan datang':
            return 'Akan Datang';
        default:
            return ucwords($status);
    }
}

// Format date
function formatDate($date) {
    return date('d M Y', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard EO - BrackIt</title>
    <link rel="stylesheet" href="../../CSS/EO/dashboardEO.css" />
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
          <h2>BrackIt EO</h2>
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
            <a href="#tournaments" class="nav-link" data-section="tournaments">
              <i class="fas fa-trophy"></i>
              <span>Turnamen</span>
            </a>
          </li>
        </ul>
      </nav>
      <div class="sidebar-footer">
        <div class="user-profile">
          <img src="../../ASSETS/user.png" alt="User Avatar" />
          <div class="user-info">
            <h4>Event Organizer</h4>
            <p>EO Dashboard</p>
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
          <h1 id="pageTitle">Dashboard</h1>
        </div>
        <div class="header-right">
          <div class="search-box">
            <input type="text" placeholder="Cari turnamen..." />
            <i class="fas fa-search"></i>
          </div>
          <div class="notifications">
            <i class="fas fa-bell"></i>
            <span class="notification-count">3</span>
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
          <!-- Stats Cards -->
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-trophy"></i>
              </div>
              <div class="stat-content">
                <h3>Total Turnamen</h3>
                <p class="stat-number"><?php echo $stats['total_tournaments']; ?></p>
                <span class="stat-change positive">Total yang dibuat</span>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-play-circle"></i>
              </div>
              <div class="stat-content">
                <h3>Turnamen Aktif</h3>
                <p class="stat-number"><?php echo $stats['active_tournaments']; ?></p>
                <span class="stat-change neutral">Sedang berlangsung</span>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-money-bill-wave"></i>
              </div>
              <div class="stat-content">
                <h3>Total Pendapatan</h3>
                <p class="stat-number"><?php echo formatRupiah($stats['total_revenue']); ?></p>
                <span class="stat-change positive">Dari semua turnamen</span>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-users"></i>
              </div>
              <div class="stat-content">
                <h3>Total Peserta</h3>
                <p class="stat-number"><?php echo number_format($stats['total_participants']); ?></p>
                <span class="stat-change positive">Total pendaftar</span>
              </div>
            </div>
          </div>

          <!-- Charts Grid -->
          <div class="dashboard-grid">
            <!-- Revenue Chart -->
            <div class="chart-container">
              <div class="chart-header">
                <h3>Pendapatan Bulanan</h3>
                <select class="chart-filter">
                  <option>6 Bulan Terakhir</option>
                  <option>3 Bulan Terakhir</option>
                  <option>1 Tahun Terakhir</option>
                </select>
              </div>
              <div class="chart-content">
                <canvas id="revenueChart"></canvas>
              </div>
            </div>

            <!-- Participation Chart -->
            <div class="chart-container">
              <div class="chart-header">
                <h3>Trend Partisipasi</h3>
                <select class="chart-filter">
                  <option>6 Bulan Terakhir</option>
                  <option>3 Bulan Terakhir</option>
                  <option>1 Tahun Terakhir</option>
                </select>
              </div>
              <div class="chart-content">
                <canvas id="participationChart"></canvas>
              </div>
            </div>

            <!-- Status Chart -->
            <div class="chart-container">
              <div class="chart-header">
                <h3>Status Turnamen</h3>
              </div>
              <div class="chart-content">
                <canvas id="statusChart"></canvas>
              </div>
            </div>

            <!-- Format Chart -->
            <div class="chart-container">
              <div class="chart-header">
                <h3>Format Turnamen</h3>
              </div>
              <div class="chart-content">
                <canvas id="formatChart"></canvas>
              </div>
            </div>

            <!-- Top Tournaments Chart -->
            <div class="chart-container">
              <div class="chart-header">
                <h3>Top 5 Turnamen (Peserta)</h3>
              </div>
              <div class="chart-content">
                <canvas id="topTournamentsChart"></canvas>
              </div>
            </div>

            <!-- Recent Tournaments -->
            <div class="recent-tournaments">
              <div class="section-header">
                <h3>Turnamen Terbaru</h3>
                <a href="#tournaments" class="view-all">Lihat Semua</a>
              </div>
              <div class="tournament-list">
                <?php while ($tournament = $recentTournaments->fetch_assoc()): ?>
                <div class="tournament-item">
                  <div class="tournament-info">
                    <h4><?php echo htmlspecialchars($tournament['nama_turnamen']); ?></h4>
                    <p><?php echo formatDate($tournament['tanggal_mulai']); ?></p>
                    <span class="status <?php echo str_replace(' ', '_', $tournament['status']); ?>"><?php echo formatStatus($tournament['status']); ?></span>
                  </div>
                  <div class="tournament-stats">
                    <span><?php echo $tournament['pendaftar']; ?>/<?php echo $tournament['slot']; ?> Tim</span>
                    <span><?php echo formatRupiah($tournament['hadiah_turnamen']); ?></span>
                  </div>
                </div>
                <?php endwhile; ?>
                
                <?php if ($recentTournaments->num_rows == 0): ?>
                <div class="tournament-item">
                  <div class="tournament-info">
                    <h4>Belum ada turnamen</h4>
                    <p>Mulai buat turnamen pertama Anda</p>
                    <span class="status neutral">-</span>
                  </div>
                  <div class="tournament-stats">
                    <span>-</span>
                    <span>-</span>
                  </div>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </section>

        <!-- Tournaments Section -->
        <section id="tournaments-section" class="content-section">
          <div class="section-header">
            <h2>Manajemen Turnamen</h2>
            <button class="btn-primary" id="addTournamentBtn">
              <i class="fas fa-plus"></i>
              Tambah Turnamen
            </button>
          </div>

          <div class="filters-bar">
            <div class="filter-group">
              <select class="filter-select">
                <option>Semua Status</option>
                <option>Aktif</option>
                <option>Selesai</option>
                <option>Akan Datang</option>
              </select>
              <select class="filter-select">
                <option>Semua Game</option>
                <option>Mobile Legends</option>
                <option>PUBG Mobile</option>
                <option>Free Fire</option>
              </select>
              <input type="date" class="filter-date" />
            </div>
            <div class="search-filter">
              <input
                type="text"
                placeholder="Cari turnamen..."
                class="search-input"
              />
              <button class="search-btn">
                <i class="fas fa-search"></i>
              </button>
            </div>
          </div>

          <div class="tournaments-table">
            <table>
              <thead>
                <tr>
                  <th>Nama Turnamen</th>
                  <th>Game</th>
                  <th>Tanggal</th>
                  <th>Tim</th>
                  <th>Prize Pool</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($tournament = $allTournaments->fetch_assoc()): ?>
                <tr>
                  <td>
                    <div class="tournament-name">
                      <img src="../../ASSETS/LOGO_TOURNAMENT/<?php echo $tournament['logo_turnamen']; ?>" alt="Tournament" />
                      <span><?php echo htmlspecialchars($tournament['nama_turnamen']); ?></span>
                    </div>
                  </td>
                  <td><?php echo ucfirst($tournament['format']); ?></td>
                  <td><?php echo formatDate($tournament['tanggal_mulai']); ?></td>
                  <td><?php echo $tournament['pendaftar']; ?>/<?php echo $tournament['slot']; ?></td>
                  <td><?php echo formatRupiah($tournament['hadiah_turnamen']); ?></td>
                  <td><span class="status <?php echo str_replace(' ', '_', $tournament['status']); ?>"><?php echo formatStatus($tournament['status']); ?></span></td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn-edit" title="Edit" onclick="editTournament(<?php echo $tournament['id_turnamen']; ?>)">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn-view" title="Detail" onclick="viewTournament(<?php echo $tournament['id_turnamen']; ?>)">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button class="btn-delete" title="Hapus" onclick="deleteTournament(<?php echo $tournament['id_turnamen']; ?>)">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endwhile; ?>
                
                <?php if ($allTournaments->num_rows == 0): ?>
                <tr>
                  <td colspan="7" style="text-align: center; padding: 2rem;">
                    <p>Belum ada turnamen yang dibuat. <a href="#" onclick="document.getElementById('addTournamentBtn').click()">Buat turnamen pertama</a></p>
                  </td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </main>

    <!-- Add Tournament Modal -->
    <div id="addTournamentModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Tambah Turnamen Baru</h2>
          <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
          <form id="addTournamentForm">
            <div class="form-row">
              <div class="form-group">
                <label for="tournamentName">Nama Turnamen</label>
                <input
                  type="text"
                  id="tournamentName"
                  name="tournamentName"
                  required
                />
              </div>
              <div class="form-group">
                <label for="gameType">Jenis Game</label>
                <select id="gameType" name="gameType" required>
                  <option value="">Pilih Game</option>
                  <option value="mobile-legends">Mobile Legends</option>
                  <option value="pubg-mobile">PUBG Mobile</option>
                  <option value="free-fire">Free Fire</option>
                  <option value="valorant">Valorant</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="startDate">Tanggal Mulai</label>
                <input
                  type="datetime-local"
                  id="startDate"
                  name="startDate"
                  required
                />
              </div>
              <div class="form-group">
                <label for="endDate">Tanggal Selesai</label>
                <input
                  type="datetime-local"
                  id="endDate"
                  name="endDate"
                  required
                />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="maxTeams">Maksimal Tim</label>
                <input
                  type="number"
                  id="maxTeams"
                  name="maxTeams"
                  min="2"
                  max="128"
                  required
                />
              </div>
              <div class="form-group">
                <label for="prizePool">Prize Pool (Rp)</label>
                <input
                  type="number"
                  id="prizePool"
                  name="prizePool"
                  min="0"
                  required
                />
              </div>
            </div>
            <div class="form-group">
              <label for="description">Deskripsi</label>
              <textarea id="description" name="description" rows="4"></textarea>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="registrationFee">Biaya Registrasi (Rp)</label>
                <input
                  type="number"
                  id="registrationFee"
                  name="registrationFee"
                  min="0"
                  required
                />
              </div>
              <div class="form-group">
                <label for="tournamentType">Tipe Turnamen</label>
                <select id="tournamentType" name="tournamentType" required>
                  <option value="">Pilih Tipe</option>
                  <option value="single-elimination">Single Elimination</option>
                  <option value="double-elimination">Double Elimination</option>
                  <option value="round-robin">Round Robin</option>
                </select>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" onclick="closeModal()">
            Batal
          </button>
          <button type="submit" form="addTournamentForm" class="btn-primary">
            Simpan Turnamen
          </button>
        </div>
      </div>
    </div>

    <!-- Scripts -->
    <script>
      // Chart data from PHP
      const chartData = {
        revenueLabels: <?php echo json_encode($revenueLabels); ?>,
        revenueData: <?php echo json_encode($revenueData); ?>,
        participationLabels: <?php echo json_encode($participationLabels); ?>,
        participationData: <?php echo json_encode($participationData); ?>,
        statusLabels: <?php echo json_encode($statusLabels); ?>,
        statusData: <?php echo json_encode($statusData); ?>,
        statusColors: <?php echo json_encode($pieColors); ?>,
        formatLabels: <?php echo json_encode($formatLabels); ?>,
        formatData: <?php echo json_encode($formatData); ?>,
        topTournamentNames: <?php echo json_encode($topTournamentNames); ?>,
        topTournamentParticipants: <?php echo json_encode($topTournamentParticipants); ?>
      };

      // Tournament action functions
      function editTournament(id) {
        alert('Edit turnamen ID: ' + id);
        // TODO: Implement edit functionality
      }
      
      function viewTournament(id) {
        alert('View turnamen ID: ' + id);
        // TODO: Implement view functionality
      }
      
      function deleteTournament(id) {
        if (confirm('Apakah Anda yakin ingin menghapus turnamen ini?')) {
          // TODO: Implement delete functionality
          alert('Delete turnamen ID: ' + id);
        }
      }

      // Initialize charts when page loads
      document.addEventListener('DOMContentLoaded', function() {
        window.dashboardCharts.initializeAll(chartData);
      });
    </script>
    <script src="../../SCRIPT/EO/dashboardEOCharts.js"></script>
    <script src="../../SCRIPT/EO/dashboardEO.js"></script>
  </body>
</html>

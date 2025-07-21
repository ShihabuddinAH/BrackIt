<?php
include '../LOGIN/session.php';
include '../connect.php';

// Get current EO ID from session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'eo') {
    echo "<script>alert('Session tidak valid. Silakan login kembali.'); window.location.href = '../LOGIN/login.php';</script>";
    exit();
}

$eo_id = $_SESSION['user_id'];

// Function to get dashboard statistics
function getDashboardStats($conn, $eo_id) {
    $stats = [];
    
    // Total Tournaments by EO
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM turnamen WHERE id_eo = ?");
    $stmt->bind_param("i", $eo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['total_tournaments'] = $result->fetch_assoc()['total'];
    $stmt->close();
    
    // Active Tournaments
    $stmt = $conn->prepare("SELECT COUNT(*) as active FROM turnamen WHERE id_eo = ? AND status = 'aktif'");
    $stmt->bind_param("i", $eo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['active_tournaments'] = $result->fetch_assoc()['active'];
    $stmt->close();
    
    // Total Revenue (sum of registration fees * registered participants)
    $stmt = $conn->prepare("
        SELECT SUM(CAST(biaya_turnamen AS UNSIGNED) * CAST(pendaftar AS UNSIGNED)) as revenue 
        FROM turnamen 
        WHERE id_eo = ?
    ");
    $stmt->bind_param("i", $eo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $revenue = $result->fetch_assoc()['revenue'] ?? 0;
    $stats['total_revenue'] = $revenue;
    $stmt->close();
    
    // Total Participants
    $stmt = $conn->prepare("SELECT SUM(CAST(pendaftar AS UNSIGNED)) as participants FROM turnamen WHERE id_eo = ?");
    $stmt->bind_param("i", $eo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['total_participants'] = $result->fetch_assoc()['participants'] ?? 0;
    $stmt->close();
    
    return $stats;
}

// Function to get recent tournaments
function getRecentTournaments($conn, $eo_id, $limit = 3) {
    $stmt = $conn->prepare("
        SELECT nama_turnamen, tanggal_mulai, status, slot, pendaftar, hadiah_turnamen 
        FROM turnamen 
        WHERE id_eo = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->bind_param("ii", $eo_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}

// Function to get all tournaments for table
function getAllTournaments($conn, $eo_id) {
    $stmt = $conn->prepare("
        SELECT id_turnamen, nama_turnamen, format, tanggal_mulai, slot, pendaftar, 
               hadiah_turnamen, status, logo_turnamen, biaya_turnamen
        FROM turnamen 
        WHERE id_eo = ? 
        ORDER BY tanggal_mulai DESC
    ");
    $stmt->bind_param("i", $eo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}

// Function to get monthly revenue data for chart
function getMonthlyRevenue($conn, $eo_id) {
    $stmt = $conn->prepare("
        SELECT 
            MONTH(created_at) as month,
            YEAR(created_at) as year,
            SUM(CAST(biaya_turnamen AS UNSIGNED) * CAST(pendaftar AS UNSIGNED)) as revenue
        FROM turnamen 
        WHERE id_eo = ? 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY YEAR(created_at), MONTH(created_at)
        ORDER BY year, month
    ");
    $stmt->bind_param("i", $eo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}

// Function to get tournament status distribution
function getTournamentStatusData($conn, $eo_id) {
    $stmt = $conn->prepare("
        SELECT status, COUNT(*) as count 
        FROM turnamen 
        WHERE id_eo = ? 
        GROUP BY status
    ");
    $stmt->bind_param("i", $eo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}

// Function to get tournament format distribution
function getTournamentFormatData($conn, $eo_id) {
    $stmt = $conn->prepare("
        SELECT format, COUNT(*) as count 
        FROM turnamen 
        WHERE id_eo = ? 
        GROUP BY format
    ");
    $stmt->bind_param("i", $eo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}

// Function to get participation trend
function getParticipationTrend($conn, $eo_id) {
    $stmt = $conn->prepare("
        SELECT 
            MONTH(created_at) as month,
            YEAR(created_at) as year,
            SUM(CAST(pendaftar AS UNSIGNED)) as participants
        FROM turnamen 
        WHERE id_eo = ? 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY YEAR(created_at), MONTH(created_at)
        ORDER BY year, month
    ");
    $stmt->bind_param("i", $eo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}

// Function to get top tournaments by participants
function getTopTournaments($conn, $eo_id, $limit = 5) {
    $stmt = $conn->prepare("
        SELECT nama_turnamen, pendaftar, slot, hadiah_turnamen
        FROM turnamen 
        WHERE id_eo = ? 
        ORDER BY CAST(pendaftar AS UNSIGNED) DESC 
        LIMIT ?
    ");
    $stmt->bind_param("ii", $eo_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
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

// Debug: Check if tournaments are loaded
$tournament_count = $allTournaments->num_rows;
if ($tournament_count == 0) {
    error_log("No tournaments found for EO ID: " . $eo_id);
} else {
    error_log("Found $tournament_count tournaments for EO ID: " . $eo_id);
}

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
            <h4><?php echo htmlspecialchars($_SESSION['username']); ?></h4>
            <p>EO ID: <?php echo $eo_id; ?></p>
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
                      <img src="../../ASSETS/LOGO.png" alt="Tournament" />
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
      <div class="modal-content add-tournament-modal">
        <div class="modal-header">
          <h2><i class="fas fa-plus-circle"></i> Tambah Turnamen Baru</h2>
          <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
          <form id="addTournamentForm">
            <div class="form-section">
              <h3><i class="fas fa-info-circle"></i> Informasi Dasar</h3>
              <div class="form-row">
                <div class="form-group">
                  <label for="tournamentName">
                    <i class="fas fa-trophy"></i> Nama Turnamen
                  </label>
                  <input
                    type="text"
                    id="tournamentName"
                    name="tournamentName"
                    placeholder="Masukkan nama turnamen"
                    required
                  />
                </div>
                <div class="form-group">
                  <label for="tournamentFormat">
                    <i class="fas fa-sitemap"></i> Format Turnamen
                  </label>
                  <select id="tournamentFormat" name="tournamentFormat" required>
                    <option value="">Pilih Format</option>
                    <option value="individu">Individu</option>
                    <option value="team">Tim</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="form-section">
              <h3><i class="fas fa-calendar-alt"></i> Jadwal Turnamen</h3>
              <div class="form-row">
                <div class="form-group">
                  <label for="startDate">
                    <i class="fas fa-play"></i> Tanggal Mulai
                  </label>
                  <input
                    type="datetime-local"
                    id="startDate"
                    name="startDate"
                    required
                  />
                </div>
                <div class="form-group">
                  <label for="tournamentSlot">
                    <i class="fas fa-users"></i> Slot Peserta
                  </label>
                  <input
                    type="number"
                    id="tournamentSlot"
                    name="tournamentSlot"
                    min="2"
                    max="128"
                    placeholder="Contoh: 16"
                    required
                  />
                  <small class="form-helper">Jumlah maksimal peserta yang dapat berpartisipasi</small>
                </div>
              </div>
            </div>

            <div class="form-section">
              <h3><i class="fas fa-money-bill-wave"></i> Informasi Finansial</h3>
              <div class="form-row">
                <div class="form-group">
                  <label for="prizePool">
                    <i class="fas fa-trophy"></i> Prize Pool (Rp)
                  </label>
                  <input
                    type="number"
                    id="prizePool"
                    name="prizePool"
                    min="0"
                    placeholder="Contoh: 1000000"
                    required
                  />
                  <small class="form-helper">Total hadiah yang akan diberikan</small>
                </div>
                <div class="form-group">
                  <label for="registrationFee">
                    <i class="fas fa-credit-card"></i> Biaya Registrasi (Rp)
                  </label>
                  <input
                    type="number"
                    id="registrationFee"
                    name="registrationFee"
                    min="0"
                    placeholder="Contoh: 50000"
                    required
                  />
                  <small class="form-helper">Biaya pendaftaran per peserta</small>
                </div>
              </div>
            </div>

            <div class="form-section">
              <h3><i class="fas fa-file-text"></i> Deskripsi & Aturan</h3>
              <div class="form-group full-width">
                <label for="description">
                  <i class="fas fa-align-left"></i> Deskripsi Turnamen
                </label>
                <textarea 
                  id="description" 
                  name="description" 
                  rows="3"
                  placeholder="Jelaskan detail turnamen, format pertandingan, dll."
                ></textarea>
                <small class="form-helper">Berikan informasi detail tentang turnamen</small>
              </div>
              <div class="form-group full-width">
                <label for="rules">
                  <i class="fas fa-gavel"></i> Aturan Turnamen
                </label>
                <textarea 
                  id="rules" 
                  name="rules" 
                  rows="4"
                  placeholder="Masukkan aturan khusus turnamen, seperti: - Dilarang toxic, - Hero banned: Fanny, Ling, dll."
                  required
                ></textarea>
                <small class="form-helper">Aturan yang harus diikuti peserta selama turnamen</small>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" onclick="closeModal()">
            <i class="fas fa-times"></i> Batal
          </button>
          <button type="submit" form="addTournamentForm" class="btn-primary">
            <i class="fas fa-save"></i> Simpan Turnamen
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Tournament Modal -->
    <div id="editTournamentModal" class="modal">
      <div class="modal-content edit-tournament-modal">
        <div class="modal-header">
          <h2><i class="fas fa-edit"></i> Edit Turnamen</h2>
          <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <div class="modal-body">
          <form id="editTournamentForm">
            <input type="hidden" id="editTournamentId" name="tournament_id" />
            
            <div class="form-section">
              <h3><i class="fas fa-info-circle"></i> Informasi Dasar</h3>
              <div class="form-row">
                <div class="form-group">
                  <label for="editTournamentName">
                    <i class="fas fa-trophy"></i> Nama Turnamen
                  </label>
                  <input
                    type="text"
                    id="editTournamentName"
                    name="tournamentName"
                    placeholder="Masukkan nama turnamen"
                    required
                  />
                </div>
                <div class="form-group">
                  <label for="editTournamentFormat">
                    <i class="fas fa-sitemap"></i> Format Turnamen
                  </label>
                  <select id="editTournamentFormat" name="tournamentFormat" required>
                    <option value="">Pilih Format</option>
                    <option value="individu">Individu</option>
                    <option value="team">Tim</option>
                  </select>
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label for="editStatus">
                    <i class="fas fa-flag"></i> Status Turnamen
                  </label>
                  <select id="editStatus" name="status" required>
                    <option value="akan datang">Akan Datang</option>
                    <option value="aktif">Aktif</option>
                    <option value="selesai">Selesai</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="editTournamentSlot">
                    <i class="fas fa-users"></i> Slot Peserta
                  </label>
                  <input
                    type="number"
                    id="editTournamentSlot"
                    name="tournamentSlot"
                    min="2"
                    max="128"
                    placeholder="Contoh: 16"
                    required
                  />
                </div>
              </div>
            </div>

            <div class="form-section">
              <h3><i class="fas fa-calendar-alt"></i> Jadwal Turnamen</h3>
              <div class="form-row">
                <div class="form-group">
                  <label for="editStartDate">
                    <i class="fas fa-play"></i> Tanggal Mulai
                  </label>
                  <input
                    type="datetime-local"
                    id="editStartDate"
                    name="startDate"
                    required
                  />
                </div>
                <div class="form-group">
                  <label for="editEndDate">
                    <i class="fas fa-stop"></i> Tanggal Selesai
                  </label>
                  <input
                    type="datetime-local"
                    id="editEndDate"
                    name="endDate"
                    readonly
                    style="background: #333; color: #999;"
                  />
                  <small class="form-helper">Otomatis terisi saat status diubah ke "Selesai"</small>
                </div>
              </div>
            </div>

            <div class="form-section">
              <h3><i class="fas fa-money-bill-wave"></i> Informasi Finansial</h3>
              <div class="form-row">
                <div class="form-group">
                  <label for="editPrizePool">
                    <i class="fas fa-trophy"></i> Prize Pool (Rp)
                  </label>
                  <input
                    type="number"
                    id="editPrizePool"
                    name="prizePool"
                    min="0"
                    placeholder="Contoh: 1000000"
                    required
                  />
                  <small class="form-helper">Total hadiah yang akan diberikan</small>
                </div>
                <div class="form-group">
                  <label for="editRegistrationFee">
                    <i class="fas fa-credit-card"></i> Biaya Registrasi (Rp)
                  </label>
                  <input
                    type="number"
                    id="editRegistrationFee"
                    name="registrationFee"
                    min="0"
                    placeholder="Contoh: 50000"
                    required
                  />
                  <small class="form-helper">Biaya pendaftaran per peserta</small>
                </div>
              </div>
            </div>

            <div class="form-section">
              <h3><i class="fas fa-file-text"></i> Deskripsi & Aturan</h3>
              <div class="form-group full-width">
                <label for="editDescription">
                  <i class="fas fa-align-left"></i> Deskripsi Turnamen
                </label>
                <textarea 
                  id="editDescription" 
                  name="description" 
                  rows="3"
                  placeholder="Jelaskan detail turnamen, format pertandingan, dll."
                ></textarea>
                <small class="form-helper">Berikan informasi detail tentang turnamen</small>
              </div>
              <div class="form-group full-width">
                <label for="editRules">
                  <i class="fas fa-gavel"></i> Aturan Turnamen
                </label>
                <textarea 
                  id="editRules" 
                  name="rules" 
                  rows="4"
                  placeholder="Masukkan aturan khusus turnamen"
                  required
                ></textarea>
                <small class="form-helper">Aturan yang harus diikuti peserta selama turnamen</small>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" onclick="closeEditModal()">
            <i class="fas fa-times"></i> Batal
          </button>
          <button type="submit" form="editTournamentForm" class="btn-primary">
            <i class="fas fa-save"></i> Update Turnamen
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
        // Fetch tournament data
        fetch(`tournament_edit_api.php?action=get&id=${id}`)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Populate edit form
              const tournament = data.data;
              document.getElementById('editTournamentId').value = tournament.id_turnamen;
              document.getElementById('editTournamentName').value = tournament.nama_turnamen;
              document.getElementById('editTournamentFormat').value = tournament.format;
              document.getElementById('editStartDate').value = tournament.tanggal_mulai.replace(' ', 'T');
              document.getElementById('editTournamentSlot').value = tournament.slot;
              document.getElementById('editPrizePool').value = tournament.hadiah_turnamen;
              document.getElementById('editRegistrationFee').value = tournament.biaya_turnamen;
              document.getElementById('editStatus').value = tournament.status;
              document.getElementById('editDescription').value = tournament.deskripsi_turnamen || '';
              document.getElementById('editRules').value = tournament.aturan || '';
              
              // Show modal
              document.getElementById('editTournamentModal').style.display = 'block';
            } else {
              showNotification('Error: ' + data.message, 'error');
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to fetch tournament data', 'error');
          });
      }
      
      function closeEditModal() {
        document.getElementById('editTournamentModal').style.display = 'none';
      }
      
      function closeModal() {
        document.getElementById('addTournamentModal').style.display = 'none';
      }
      
      // Show add tournament modal
      document.getElementById('addTournamentBtn').addEventListener('click', function() {
        document.getElementById('addTournamentModal').style.display = 'block';
      });
      
      // Close modals when clicking outside
      window.addEventListener('click', function(e) {
        const addModal = document.getElementById('addTournamentModal');
        const editModal = document.getElementById('editTournamentModal');
        
        if (e.target === addModal) {
          addModal.style.display = 'none';
        }
        if (e.target === editModal) {
          editModal.style.display = 'none';
        }
      });
      
      // Handle edit form submission
      document.getElementById('editTournamentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'update');
        
        fetch('tournament_edit_api.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showNotification('Turnamen berhasil diupdate!', 'success');
            closeEditModal();
            setTimeout(() => location.reload(), 1000); // Refresh to show updated data
          } else {
            showNotification('Error: ' + data.message, 'error');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showNotification('Failed to update tournament', 'error');
        });
      });
      
      // Handle add form submission
      document.getElementById('addTournamentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const tournamentData = {
          tournamentName: formData.get('tournamentName'),
          tournamentFormat: formData.get('tournamentFormat'),
          startDate: formData.get('startDate'),
          tournamentSlot: formData.get('tournamentSlot'),
          prizePool: formData.get('prizePool'),
          registrationFee: formData.get('registrationFee'),
          description: formData.get('description'),
          rules: formData.get('rules')
        };
        
        fetch('tournament_api.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(tournamentData)
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            showNotification('Turnamen berhasil dibuat!', 'success');
            closeModal();
            document.getElementById('addTournamentForm').reset();
            setTimeout(() => location.reload(), 1000); // Refresh to show new data
          } else {
            showNotification('Error: ' + (data.error || data.message), 'error');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showNotification('Failed to create tournament', 'error');
        });
      });
      
      // Notification function
      function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
          <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
              <i class="fas fa-times"></i>
            </button>
          </div>
        `;
        
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
          notification.classList.remove('show');
          setTimeout(() => notification.remove(), 300);
        }, 5000);
      }
      
      function viewTournament(id) {
        // Fetch tournament data and show in a detailed modal
        fetch(`tournament_edit_api.php?action=get&id=${id}`)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              showTournamentDetailModal(data.data);
            } else {
              alert('Error: ' + data.message);
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('Failed to fetch tournament details');
          });
      }
      
      // Function to show tournament detail modal
      function showTournamentDetailModal(tournament) {
        const formatStatus = {
          'aktif': 'Aktif',
          'selesai': 'Selesai', 
          'akan datang': 'Akan Datang'
        };
        
        const formatRupiah = (amount) => {
          if (amount >= 1000000) {
            return 'Rp ' + (amount / 1000000).toFixed(1) + 'M';
          } else if (amount >= 1000) {
            return 'Rp ' + (amount / 1000).toFixed(1) + 'K';
          } else {
            return 'Rp ' + parseInt(amount).toLocaleString();
          }
        };
        
        const formatDate = (dateString) => {
          if (!dateString) return 'Belum ditentukan';
          return new Date(dateString).toLocaleDateString('id-ID', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
          });
        };
        
        const getStatusClass = (status) => {
          switch(status) {
            case 'aktif': return 'active';
            case 'selesai': return 'completed';
            case 'akan datang': return 'upcoming';
            default: return 'inactive';
          }
        };
        
        // Extract game type from description
        const getGameType = (description) => {
          if (description.includes('Mobile Legends')) return 'Mobile Legends';
          if (description.includes('PUBG Mobile')) return 'PUBG Mobile';
          if (description.includes('Free Fire')) return 'Free Fire';
          if (description.includes('Valorant')) return 'Valorant';
          return tournament.format.charAt(0).toUpperCase() + tournament.format.slice(1);
        };
        
        const gameType = getGameType(tournament.deskripsi_turnamen || '');
        const cleanDescription = tournament.deskripsi_turnamen ? 
          tournament.deskripsi_turnamen.replace(/^Game: [^.]*\. /, '') : 
          'Tidak ada deskripsi tersedia untuk turnamen ini.';
        
        const modalHTML = `
          <div id="tournamentDetailModal" class="modal" style="display: flex !important;">
            <div class="modal-content tournament-detail-modal">
              <div class="modal-header">
                <h2><i class="fas fa-trophy"></i> Detail Turnamen - ${tournament.nama_turnamen}</h2>
                <button class="modal-close" onclick="closeTournamentDetailModal()" type="button">
                  <i class="fas fa-times"></i>
                </button>
              </div>
              <div class="modal-body">
                <div class="detail-grid">
                  <div class="detail-card">
                    <h3><i class="fas fa-info-circle"></i> Informasi Umum</h3>
                    <div class="detail-item">
                      <span class="detail-label">ID Turnamen:</span>
                      <span class="detail-value">T${String(tournament.id_turnamen).padStart(3, '0')}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Nama Turnamen:</span>
                      <span class="detail-value">${tournament.nama_turnamen}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Jenis Game:</span>
                      <span class="detail-value">${gameType}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Status:</span>
                      <span class="status ${getStatusClass(tournament.status)}">${formatStatus[tournament.status] || tournament.status}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Dibuat:</span>
                      <span class="detail-value">${formatDate(tournament.created_at)}</span>
                    </div>
                  </div>
                  
                  <div class="detail-card">
                    <h3><i class="fas fa-users"></i> Peserta & Kapasitas</h3>
                    <div class="detail-item">
                      <span class="detail-label">Slot Total:</span>
                      <span class="detail-value">${tournament.slot} Tim</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Peserta Terdaftar:</span>
                      <span class="detail-value highlight">${tournament.pendaftar || 0} Tim</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Slot Tersisa:</span>
                      <span class="detail-value">${tournament.slot - (tournament.pendaftar || 0)} Tim</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Tingkat Partisipasi:</span>
                      <div class="progress-bar">
                        <div class="progress-fill" style="width: ${((tournament.pendaftar || 0) / tournament.slot * 100)}%"></div>
                        <span class="progress-text">${Math.round((tournament.pendaftar || 0) / tournament.slot * 100)}%</span>
                      </div>
                    </div>
                  </div>
                  
                  <div class="detail-card">
                    <h3><i class="fas fa-money-bill-wave"></i> Informasi Finansial</h3>
                    <div class="detail-item">
                      <span class="detail-label">Prize Pool:</span>
                      <span class="detail-value prize">${formatRupiah(tournament.hadiah_turnamen)}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Biaya Registrasi:</span>
                      <span class="detail-value">${formatRupiah(tournament.biaya_turnamen)}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Total Pendapatan:</span>
                      <span class="detail-value revenue">${formatRupiah((tournament.biaya_turnamen || 0) * (tournament.pendaftar || 0))}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Potensi Max:</span>
                      <span class="detail-value">${formatRupiah((tournament.biaya_turnamen || 0) * tournament.slot)}</span>
                    </div>
                  </div>
                  
                  <div class="detail-card">
                    <h3><i class="fas fa-calendar-alt"></i> Jadwal Turnamen</h3>
                    <div class="detail-item">
                      <span class="detail-label">Tanggal Mulai:</span>
                      <span class="detail-value">${formatDate(tournament.tanggal_mulai)}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Tanggal Selesai:</span>
                      <span class="detail-value">${formatDate(tournament.tanggal_selesai)}</span>
                    </div>
                    <div class="detail-item">
                      <span class="detail-label">Durasi:</span>
                      <span class="detail-value">${tournament.tanggal_selesai ? 
                        Math.ceil((new Date(tournament.tanggal_selesai) - new Date(tournament.tanggal_mulai)) / (1000 * 60 * 60 * 24)) + ' hari' 
                        : 'Belum ditentukan'}</span>
                    </div>
                  </div>
                </div>
                
                ${cleanDescription ? `
                <div class="detail-card full-width">
                  <h3><i class="fas fa-file-text"></i> Deskripsi Turnamen</h3>
                  <div class="description-content">
                    ${cleanDescription}
                  </div>
                </div>
                ` : ''}
                
                ${tournament.aturan ? `
                <div class="detail-card full-width">
                  <h3><i class="fas fa-list-alt"></i> Aturan Turnamen</h3>
                  <div class="description-content">
                    ${tournament.aturan}
                  </div>
                </div>
                ` : ''}
              </div>
              <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeTournamentDetailModal()">
                  <i class="fas fa-times"></i> Tutup
                </button>
                <button type="button" class="btn-primary" onclick="closeTournamentDetailModal(); editTournament(${tournament.id_turnamen})">
                  <i class="fas fa-edit"></i> Edit Turnamen
                </button>
              </div>
            </div>
          </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('tournamentDetailModal');
        if (existingModal) {
          existingModal.remove();
        }
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
      }
      
      function closeTournamentDetailModal() {
        const modal = document.getElementById('tournamentDetailModal');
        if (modal) {
          modal.remove();
        }
      }
      
      function closeEditModal() {
        document.getElementById('editTournamentModal').style.display = 'none';
      }
      
      function closeModal() {
        document.getElementById('addTournamentModal').style.display = 'none';
      }
      
      // Show add tournament modal
      document.getElementById('addTournamentBtn').addEventListener('click', function() {
        document.getElementById('addTournamentModal').style.display = 'block';
      });
      
      // Close modals when clicking outside
      window.addEventListener('click', function(e) {
        const addModal = document.getElementById('addTournamentModal');
        const editModal = document.getElementById('editTournamentModal');
        
        if (e.target === addModal) {
          addModal.style.display = 'none';
        }
        if (e.target === editModal) {
          editModal.style.display = 'none';
        }
      });
      
      // Handle status change to auto-set end date
      document.getElementById('editStatus').addEventListener('change', function() {
        const endDateField = document.getElementById('editEndDate');
        if (this.value === 'selesai') {
          // Set current datetime when tournament is marked as finished
          const now = new Date();
          const formattedDateTime = now.toISOString().slice(0, 16);
          endDateField.value = formattedDateTime;
        }
      });
      
      function deleteTournament(id) {
        if (confirm('Apakah Anda yakin ingin menghapus turnamen ini?')) {
          fetch('tournament_edit_api.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete&tournament_id=${id}`
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              alert('Turnamen berhasil dihapus!');
              location.reload(); // Refresh to show updated data
            } else {
              alert('Error: ' + data.message);
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete tournament');
          });
        }
      }

      // Initialize charts when page loads
      document.addEventListener('DOMContentLoaded', function() {
        window.dashboardCharts.initializeAll(chartData);
        
        // Initialize navigation
        initializeNavigation();
      });
      
      // Navigation functionality
      function initializeNavigation() {
        // Get all navigation links
        const navLinks = document.querySelectorAll('.nav-link');
        const sections = document.querySelectorAll('.content-section');
        const pageTitle = document.getElementById('pageTitle');
        
        // Section titles
        const sectionTitles = {
          'dashboard': 'Dashboard',
          'tournaments': 'Manajemen Turnamen'
        };
        
        // Handle navigation clicks
        navLinks.forEach(link => {
          link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetSection = this.getAttribute('data-section');
            
            // Remove active class from all nav items and sections
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            sections.forEach(section => section.classList.remove('active'));
            
            // Add active class to clicked nav item
            this.parentElement.classList.add('active');
            
            // Show target section
            const targetSectionElement = document.getElementById(targetSection + '-section');
            if (targetSectionElement) {
              targetSectionElement.classList.add('active');
            }
            
            // Update page title
            if (sectionTitles[targetSection]) {
              pageTitle.textContent = sectionTitles[targetSection];
            }
          });
        });
        
        // Handle "Lihat Semua" link in recent tournaments
        const viewAllLink = document.querySelector('.view-all');
        if (viewAllLink) {
          viewAllLink.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Switch to tournaments section
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            sections.forEach(section => section.classList.remove('active'));
            
            // Activate tournaments section
            document.querySelector('[data-section="tournaments"]').parentElement.classList.add('active');
            document.getElementById('tournaments-section').classList.add('active');
            pageTitle.textContent = 'Manajemen Turnamen';
          });
        }
        
        // Sidebar toggle functionality
        const sidebarToggle = document.getElementById('sidebarToggle');
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (sidebarToggle) {
          sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
          });
        }
        
        if (menuToggle) {
          menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
          });
        }
        
        // Logout functionality
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
          logoutBtn.addEventListener('click', function() {
            if (confirm('Apakah Anda yakin ingin logout?')) {
              window.location.href = '../LOGIN/logout.php';
            }
          });
        }
        
        // Modal close buttons
        document.querySelectorAll('.modal-close').forEach(button => {
          button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
              modal.style.display = 'none';
            }
          });
        });
      }
    </script>
    <script src="../../SCRIPT/EO/dashboardEOCharts.js"></script>
  </body>
</html>

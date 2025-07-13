<?php
include '../LOGIN/session.php';
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
            <p>Admin Dashboard</p>
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
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-users"></i>
              </div>
              <div class="stat-content">
                <h3>Total Pengguna</h3>
                <p class="stat-number">1,247</p>
                <span class="stat-change positive">+12% bulan ini</span>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-user-friends"></i>
              </div>
              <div class="stat-content">
                <h3>Total Party</h3>
                <p class="stat-number">89</p>
                <span class="stat-change positive">+5 party baru</span>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-users-cog"></i>
              </div>
              <div class="stat-content">
                <h3>Tim Aktif</h3>
                <p class="stat-number">156</p>
                <span class="stat-change neutral">Saat ini aktif</span>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-trophy"></i>
              </div>
              <div class="stat-content">
                <h3>Turnamen Berlangsung</h3>
                <p class="stat-number">12</p>
                <span class="stat-change positive">+2 minggu ini</span>
              </div>
            </div>
          </div>

          <div class="dashboard-grid">
            <div class="chart-container">
              <div class="chart-header">
                <h3>Statistik Pengguna Bulanan</h3>
                <select class="chart-filter">
                  <option>6 Bulan Terakhir</option>
                  <option>3 Bulan Terakhir</option>
                  <option>1 Tahun Terakhir</option>
                </select>
              </div>
              <div class="chart-content">
                <canvas id="userStatsChart"></canvas>
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
                    <p>ESports Indonesia - 28 Juni 2025</p>
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
                    <p>Gaming Community - 27 Juni 2025</p>
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
                    <p>FireStorm Events - 26 Juni 2025</p>
                    <span class="status active">Berlangsung</span>
                  </div>
                  <div class="tournament-stats">
                    <span>48 Tim</span>
                    <span>Rp 8M</span>
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
            <button class="btn-primary" id="addUserBtn">
              <i class="fas fa-plus"></i>
              Tambah Pengguna
            </button>
          </div>
          <!-- Content akan dimuat dari adminManajemenPengguna.html -->
        </section>

        <!-- Teams Management Section -->
        <section id="teams-section" class="content-section">
          <div class="section-header">
            <h2>Manajemen Tim & Party</h2>
            <button class="btn-primary" id="addTeamBtn">
              <i class="fas fa-plus"></i>
              Tambah Tim
            </button>
          </div>
          <!-- Content akan dimuat dari adminManajemenTim.html -->
        </section>

        <!-- Tournaments Management Section -->
        <section id="tournaments-section" class="content-section">
          <div class="section-header">
            <h2>Manajemen Turnamen</h2>
            <div class="header-actions">
              <button class="btn-secondary">
                <i class="fas fa-download"></i>
                Export Data
              </button>
              <button class="btn-primary">
                <i class="fas fa-check-circle"></i>
                Approve All
              </button>
            </div>
          </div>

          <div class="filters-bar">
            <div class="filter-group">
              <select class="filter-select">
                <option>Semua Status</option>
                <option>Pending</option>
                <option>Approved</option>
                <option>Active</option>
                <option>Completed</option>
              </select>
              <select class="filter-select">
                <option>Semua EO</option>
                <option>ESports Indonesia</option>
                <option>Gaming Community</option>
                <option>FireStorm Events</option>
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
                  <th>Event Organizer</th>
                  <th>Game</th>
                  <th>Tanggal</th>
                  <th>Prize Pool</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>
                    <div class="tournament-name">
                      <img src="../../ASSETS/LOGO.png" alt="Tournament" />
                      <span>Mobile Legends Championship 2025</span>
                    </div>
                  </td>
                  <td>ESports Indonesia</td>
                  <td>Mobile Legends</td>
                  <td>28 Jun 2025</td>
                  <td>Rp 10.000.000</td>
                  <td><span class="status pending">Pending</span></td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn-approve" title="Approve">
                        <i class="fas fa-check"></i>
                      </button>
                      <button class="btn-view" title="Detail">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button class="btn-reject" title="Reject">
                        <i class="fas fa-times"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td>
                    <div class="tournament-name">
                      <img src="../../ASSETS/LOGO.png" alt="Tournament" />
                      <span>PUBG Mobile Tournament</span>
                    </div>
                  </td>
                  <td>Gaming Community</td>
                  <td>PUBG Mobile</td>
                  <td>27 Jun 2025</td>
                  <td>Rp 7.500.000</td>
                  <td><span class="status approved">Approved</span></td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn-view" title="Detail">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button class="btn-edit" title="Edit">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn-suspend" title="Suspend">
                        <i class="fas fa-pause"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../../SCRIPT/ADMIN/dashboardAdmin.js"></script>
  </body>
</html>

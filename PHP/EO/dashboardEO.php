<?php
include '../LOGIN/session.php';
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
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-trophy"></i>
              </div>
              <div class="stat-content">
                <h3>Total Turnamen</h3>
                <p class="stat-number">12</p>
                <span class="stat-change positive">+2 bulan ini</span>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-play-circle"></i>
              </div>
              <div class="stat-content">
                <h3>Turnamen Aktif</h3>
                <p class="stat-number">3</p>
                <span class="stat-change neutral">Sedang berlangsung</span>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-money-bill-wave"></i>
              </div>
              <div class="stat-content">
                <h3>Total Pendapatan</h3>
                <p class="stat-number">Rp 45.2M</p>
                <span class="stat-change positive">+15% dari bulan lalu</span>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-users"></i>
              </div>
              <div class="stat-content">
                <h3>Total Peserta</h3>
                <p class="stat-number">1,248</p>
                <span class="stat-change positive">+8% minggu ini</span>
              </div>
            </div>
          </div>

          <div class="dashboard-grid">
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

            <div class="recent-tournaments">
              <div class="section-header">
                <h3>Turnamen Terbaru</h3>
                <a href="#tournaments" class="view-all">Lihat Semua</a>
              </div>
              <div class="tournament-list">
                <div class="tournament-item">
                  <div class="tournament-info">
                    <h4>Mobile Legends Championship</h4>
                    <p>15 Januari 2025</p>
                    <span class="status active">Aktif</span>
                  </div>
                  <div class="tournament-stats">
                    <span>64 Tim</span>
                    <span>Rp 5.2M</span>
                  </div>
                </div>
                <div class="tournament-item">
                  <div class="tournament-info">
                    <h4>PUBG Mobile Tournament</h4>
                    <p>10 Januari 2025</p>
                    <span class="status completed">Selesai</span>
                  </div>
                  <div class="tournament-stats">
                    <span>32 Tim</span>
                    <span>Rp 3.8M</span>
                  </div>
                </div>
                <div class="tournament-item">
                  <div class="tournament-info">
                    <h4>Free Fire Championship</h4>
                    <p>5 Januari 2025</p>
                    <span class="status completed">Selesai</span>
                  </div>
                  <div class="tournament-stats">
                    <span>48 Tim</span>
                    <span>Rp 4.1M</span>
                  </div>
                </div>
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
                <tr>
                  <td>
                    <div class="tournament-name">
                      <img src="../../ASSETS/LOGO.png" alt="Tournament" />
                      <span>Mobile Legends Championship</span>
                    </div>
                  </td>
                  <td>Mobile Legends</td>
                  <td>15 Jan 2025</td>
                  <td>64/64</td>
                  <td>Rp 10.000.000</td>
                  <td><span class="status active">Aktif</span></td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn-edit" title="Edit">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn-view" title="Detail">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button class="btn-delete" title="Hapus">
                        <i class="fas fa-trash"></i>
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
                  <td>PUBG Mobile</td>
                  <td>10 Jan 2025</td>
                  <td>32/32</td>
                  <td>Rp 7.500.000</td>
                  <td><span class="status completed">Selesai</span></td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn-edit" title="Edit">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn-view" title="Detail">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button class="btn-delete" title="Hapus">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td>
                    <div class="tournament-name">
                      <img src="../../ASSETS/LOGO.png" alt="Tournament" />
                      <span>Free Fire Championship</span>
                    </div>
                  </td>
                  <td>Free Fire</td>
                  <td>20 Jan 2025</td>
                  <td>0/48</td>
                  <td>Rp 8.000.000</td>
                  <td><span class="status upcoming">Akan Datang</span></td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn-edit" title="Edit">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn-view" title="Detail">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button class="btn-delete" title="Hapus">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../../SCRIPT/EO/dashboardEO.js"></script>
  </body>
</html>

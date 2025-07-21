// Dashboard EO JavaScript
document.addEventListener("DOMContentLoaded", function () {
  // Initialize dashboard
  initializeDashboard();
  // Remove chart initialization from here - handled by dashboardEOCharts.js
  initializeEventListeners();
  initializeLogout();
  updateDateTime();

  // Update datetime every minute
  setInterval(updateDateTime, 60000);
});

// Initialize logout functionality
function initializeLogout() {
  const logoutBtn = document.getElementById("logoutBtn");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", () => {
      if (confirm("Apakah Anda yakin ingin logout?")) {
        handleLogout();
      }
    });
  }
}

// Handle logout
function handleLogout() {
  // Send logout request to server
  fetch("../LOGIN/logout.php", {
    method: "POST",
    headers: {
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Clear localStorage
        localStorage.removeItem("isLoggedIn");
        localStorage.removeItem("username");
        localStorage.removeItem("userEmail");
        localStorage.removeItem("userId");
        localStorage.removeItem("userRole");

        alert("Logout berhasil!");
        window.location.href = "../LOGIN/login.php";
      }
    })
    .catch((error) => {
      console.error("Logout error:", error);
      // Fallback: clear local data anyway
      localStorage.removeItem("isLoggedIn");
      localStorage.removeItem("username");
      localStorage.removeItem("userEmail");
      localStorage.removeItem("userId");
      localStorage.removeItem("userRole");
      window.location.href = "../LOGIN/login.php";
    });
}

// Dashboard Initialization
function initializeDashboard() {
  // Set active section
  showSection("dashboard");

  // Load initial data
  loadDashboardStats();
  loadRecentTournaments();
}

// Date and Time Update
function updateDateTime() {
  const now = new Date();
  const options = {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  };

  const headerRight = document.querySelector(".header-right .user-menu");
  if (headerRight) {
    const timeDisplay =
      headerRight.querySelector(".current-time") ||
      document.createElement("div");
    timeDisplay.className = "current-time";
    timeDisplay.textContent = now.toLocaleDateString("id-ID", options);
    if (!headerRight.querySelector(".current-time")) {
      headerRight.insertBefore(timeDisplay, headerRight.firstChild);
    }
  }
}

// Event Listeners
function initializeEventListeners() {
  // Sidebar toggle
  const sidebarToggle = document.getElementById("sidebarToggle");
  const menuToggle = document.getElementById("menuToggle");
  const sidebar = document.querySelector(".sidebar");

  if (sidebarToggle) {
    sidebarToggle.addEventListener("click", toggleSidebar);
  }

  if (menuToggle) {
    menuToggle.addEventListener("click", toggleSidebar);
  }

  // Navigation links
  const navLinks = document.querySelectorAll(".nav-link");
  navLinks.forEach((link) => {
    link.addEventListener("click", handleNavClick);
  });

  // View all tournaments link
  const viewAllLink = document.querySelector(".view-all");
  if (viewAllLink) {
    viewAllLink.addEventListener("click", function (e) {
      e.preventDefault();
      showSection("tournaments");
      updatePageTitle("tournaments");

      // Update active nav item
      document.querySelectorAll(".nav-item").forEach((item) => {
        item.classList.remove("active");
      });
      document
        .querySelector('[data-section="tournaments"]')
        .closest(".nav-item")
        .classList.add("active");
    });
  }

  // Add tournament button
  const addTournamentBtn = document.getElementById("addTournamentBtn");
  if (addTournamentBtn) {
    addTournamentBtn.addEventListener("click", openAddTournamentModal);
  }

  // Modal close buttons
  const modalCloses = document.querySelectorAll(".modal-close");
  modalCloses.forEach((close) => {
    close.addEventListener("click", closeModal);
  });

  // Form submission
  const addTournamentForm = document.getElementById("addTournamentForm");
  if (addTournamentForm) {
    addTournamentForm.addEventListener("submit", handleAddTournament);
  }

  // Search functionality
  const searchInputs = document.querySelectorAll(
    ".search-input, .header-right .search-box input"
  );
  searchInputs.forEach((input) => {
    input.addEventListener("input", handleSearch);
  });

  // Filter functionality
  const filterSelects = document.querySelectorAll(".filter-select");
  filterSelects.forEach((select) => {
    select.addEventListener("change", handleFilter);
  });

  // Action buttons
  document.addEventListener("click", handleActionButtons);

  // Logout button
  const logoutBtn = document.querySelector(".logout-btn");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", handleLogout);
  }
}

// Sidebar Toggle
function toggleSidebar() {
  const sidebar = document.querySelector(".sidebar");
  const mainContent = document.querySelector(".main-content");

  sidebar.classList.toggle("collapsed");
  mainContent.classList.toggle("expanded");
}

// Navigation Handler
function handleNavClick(e) {
  e.preventDefault();

  const section = e.currentTarget.getAttribute("data-section");

  // Update active nav item
  document.querySelectorAll(".nav-item").forEach((item) => {
    item.classList.remove("active");
  });
  e.currentTarget.closest(".nav-item").classList.add("active");

  // Show corresponding section
  showSection(section);

  // Update page title
  updatePageTitle(section);
}

// Show Section
function showSection(sectionName) {
  // Hide all sections
  document.querySelectorAll(".content-section").forEach((section) => {
    section.classList.remove("active");
  });

  // Show target section
  const targetSection = document.getElementById(`${sectionName}-section`);
  if (targetSection) {
    targetSection.classList.add("active");
  }
}

// Update Page Title
function updatePageTitle(section) {
  const pageTitle = document.getElementById("pageTitle");
  const titles = {
    dashboard: "Dashboard",
    tournaments: "Manajemen Turnamen",
  };

  if (pageTitle && titles[section]) {
    pageTitle.textContent = titles[section];
  }
}

// Charts Initialization - DEPRECATED
// Chart initialization is now handled by dashboardEOCharts.js
// function initializeCharts() {
//   // This function is no longer used
// }

// Revenue Chart - DEPRECATED
// Chart initialization is now handled by dashboardEOCharts.js
/*
function initializeRevenueChart() {
  const ctx = document.getElementById("revenueChart");
  if (!ctx) {
    console.error("RevenueChart canvas not found");
    return;
  }

  try {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
      console.error("Chart.js library not loaded");
      return;
    }

    // Use data from PHP if available, otherwise use default data
    const revenueLabels = window.chartData?.revenueLabels || [
      "Jul",
      "Agu",
      "Sep",
      "Okt",
      "Nov",
      "Des",
    ];
    const revenueData = window.chartData?.revenueData || [
      12, 15, 8, 22, 18, 25,
    ];

    window.revenueChart = new Chart(ctx, {
      type: "line",
      data: {
        labels: revenueLabels,
        datasets: [
          {
            label: "Pendapatan (Juta Rp)",
            data: revenueData,
            borderColor: "#4285f4",
            backgroundColor: "rgba(66, 133, 244, 0.1)",
            borderWidth: 3,
            fill: true,
            tension: 0.4,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: "rgba(0,0,0,0.1)",
            },
          },
          x: {
            grid: {
              display: false,
            },
          },
        },
      },
    });
  } catch (error) {
    console.error("Error initializing RevenueChart:", error);
  }
}
*/

// Tournament Status Chart (Pie Chart) - DEPRECATED
// Chart initialization is now handled by dashboardEOCharts.js
/*
function initializeTournamentStatusChart() {
  const pieCtx = document.createElement("canvas");
  pieCtx.id = "tournamentStatusChart";

  // Add chart to dashboard if space available
  const chartContainer = document.querySelector(".chart-container");
  if (chartContainer && !document.getElementById("tournamentStatusChart")) {
    const pieContainer = document.createElement("div");
    pieContainer.className = "chart-container pie-chart";
    pieContainer.innerHTML = "<h3>Status Turnamen</h3>";
    pieContainer.appendChild(pieCtx);

    const dashboardGrid = document.querySelector(".dashboard-grid");
    if (dashboardGrid) {
      dashboardGrid.appendChild(pieContainer);
    }
  }

  if (pieCtx) {
    // Use data from PHP if available, otherwise use default data
    const statusLabels = window.chartData?.statusLabels || [
      "Aktif",
      "Selesai",
      "Akan Datang",
    ];
    const statusData = window.chartData?.statusData || [3, 8, 1];

    window.statusChart = new Chart(pieCtx, {
      type: "doughnut",
      data: {
        labels: statusLabels,
        datasets: [
          {
            data: statusData,
            backgroundColor: ["#4CAF50", "#2196F3", "#FF9800"],
            borderWidth: 0,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "bottom",
          },
        },
      },
    });
  }
}
*/

// Load Dashboard Stats
function loadDashboardStats() {
  // Simulate API call - in real app, fetch from backend
  const stats = {
    totalTournaments: 12,
    activeTournaments: 3,
    totalRevenue: "Rp 45.2M",
    totalParticipants: 1248,
  };

  updateStatCard(
    ".stat-card:nth-child(1) .stat-number",
    stats.totalTournaments
  );
  updateStatCard(
    ".stat-card:nth-child(2) .stat-number",
    stats.activeTournaments
  );
  updateStatCard(".stat-card:nth-child(3) .stat-number", stats.totalRevenue);
  updateStatCard(
    ".stat-card:nth-child(4) .stat-number",
    stats.totalParticipants
  );
}

// Update Stat Card
function updateStatCard(selector, value) {
  const element = document.querySelector(selector);
  if (element) {
    element.textContent = value;
  }
}

// Load Recent Tournaments
function loadRecentTournaments() {
  // This would typically fetch from an API
  const tournaments = [
    {
      name: "Mobile Legends Championship",
      date: "15 Januari 2025",
      status: "active",
      teams: 64,
      revenue: "Rp 5.2M",
    },
    {
      name: "PUBG Mobile Tournament",
      date: "10 Januari 2025",
      status: "completed",
      teams: 32,
      revenue: "Rp 3.8M",
    },
    {
      name: "Free Fire Championship",
      date: "5 Januari 2025",
      status: "completed",
      teams: 48,
      revenue: "Rp 4.1M",
    },
  ];

  // Update tournament list in dashboard
  const tournamentList = document.querySelector(".tournament-list");
  if (tournamentList) {
    // Already populated in HTML, could be dynamic here
  }
}

// Modal Functions
function openAddTournamentModal() {
  const modal = document.getElementById("addTournamentModal");
  if (modal) {
    modal.style.display = "flex";
    document.body.style.overflow = "hidden";
  }
}

function closeModal() {
  const modals = document.querySelectorAll(".modal");
  modals.forEach((modal) => {
    modal.style.display = "none";
  });
  document.body.style.overflow = "auto";

  // Reset form
  const form = document.getElementById("addTournamentForm");
  if (form) {
    form.reset();
  }
}

// Add Tournament Handler
function handleAddTournament(e) {
  e.preventDefault();

  const form = e.target;
  const formData = new FormData(form);

  // Convert FormData to JSON
  const data = {};
  for (let [key, value] of formData.entries()) {
    data[key] = value;
  }

  // Check if this is edit mode
  const isEditMode = form.dataset.mode === "edit";
  const tournamentId = form.dataset.tournamentId;

  // Validate dates
  const startDate = new Date(data.startDate);
  const endDate = new Date(data.endDate);

  if (endDate <= startDate) {
    showNotification("Tanggal selesai harus setelah tanggal mulai", "error");
    return;
  }

  // Show loading state
  const submitBtn = form.querySelector('button[type="submit"]');
  const originalText = submitBtn.textContent;
  submitBtn.textContent = isEditMode ? "Mengupdate..." : "Menyimpan...";
  submitBtn.disabled = true;

  // Prepare request
  const url = "tournament_api.php";
  const method = isEditMode ? "PUT" : "POST";

  if (isEditMode) {
    data.id = tournamentId;
  }

  // Send data to server
  fetch(url, {
    method: method,
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(data),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.success) {
        const message = isEditMode
          ? "Turnamen berhasil diupdate!"
          : "Turnamen berhasil ditambahkan!";
        showNotification(message, "success");
        closeModal();
        form.reset();
        // Reset form mode
        form.dataset.mode = "";
        form.dataset.tournamentId = "";
        document.querySelector(".modal-header h2").textContent =
          "Tambah Turnamen Baru";
        // Reload page to show changes
        setTimeout(() => window.location.reload(), 1000);
      } else {
        showNotification("Error: " + result.error, "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification("Terjadi kesalahan saat menyimpan turnamen", "error");
    })
    .finally(() => {
      // Reset button state
      submitBtn.textContent = originalText;
      submitBtn.disabled = false;
    });
}

// Validate Tournament Form
function validateTournamentForm(data) {
  if (!data.name || !data.game || !data.startDate || !data.endDate) {
    showNotification("Semua field wajib harus diisi!", "error");
    return false;
  }

  const startDate = new Date(data.startDate);
  const endDate = new Date(data.endDate);

  if (startDate >= endDate) {
    showNotification("Tanggal selesai harus setelah tanggal mulai!", "error");
    return false;
  }

  if (startDate < new Date()) {
    showNotification("Tanggal mulai tidak boleh di masa lalu!", "error");
    return false;
  }

  return true;
}

// Add Tournament to Table
function addTournamentToTable(data) {
  const tbody = document.querySelector(".tournaments-table tbody");
  if (!tbody) return;

  const row = document.createElement("tr");
  row.innerHTML = `
        <td>
            <div class="tournament-name">
                <img src="ASSETS/LOGO.png" alt="Tournament">
                <span>${data.name}</span>
            </div>
        </td>
        <td>${getGameDisplayName(data.game)}</td>
        <td>${formatDate(data.startDate)}</td>
        <td>0/${data.maxTeams}</td>
        <td>Rp ${formatCurrency(data.prizePool)}</td>
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
    `;

  tbody.insertBefore(row, tbody.firstChild);
}

// Helper Functions
function getGameDisplayName(gameType) {
  const gameNames = {
    "mobile-legends": "Mobile Legends",
    "pubg-mobile": "PUBG Mobile",
    "free-fire": "Free Fire",
    valorant: "Valorant",
  };
  return gameNames[gameType] || gameType;
}

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString("id-ID", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
}

function formatCurrency(amount) {
  return new Intl.NumberFormat("id-ID").format(amount);
}

// Search Handler
function handleSearch(e) {
  const searchTerm = e.target.value.toLowerCase();
  const rows = document.querySelectorAll(".tournaments-table tbody tr");

  rows.forEach((row) => {
    const tournamentName = row
      .querySelector(".tournament-name span")
      .textContent.toLowerCase();
    const game = row.cells[1].textContent.toLowerCase();

    if (tournamentName.includes(searchTerm) || game.includes(searchTerm)) {
      row.style.display = "";
    } else {
      row.style.display = "none";
    }
  });
}

// Filter Handler
function handleFilter(e) {
  const filterType = e.target.classList.contains("filter-select")
    ? e.target.selectedIndex === 0
      ? "status"
      : "game"
    : "status";
  const filterValue = e.target.value.toLowerCase();

  const rows = document.querySelectorAll(".tournaments-table tbody tr");

  rows.forEach((row) => {
    let shouldShow = true;

    if (filterValue) {
      if (filterType === "status") {
        const status = row.querySelector(".status").textContent.toLowerCase();
        shouldShow = status.includes(filterValue);
      } else if (filterType === "game") {
        const game = row.cells[1].textContent.toLowerCase();
        shouldShow = game.includes(filterValue);
      }
    }

    row.style.display = shouldShow ? "" : "none";
  });
}

// Action Buttons Handler
function handleActionButtons(e) {
  if (e.target.closest(".btn-edit")) {
    handleEditTournament(e.target.closest("tr"));
  } else if (e.target.closest(".btn-view")) {
    handleViewTournament(e.target.closest("tr"));
  } else if (e.target.closest(".btn-delete")) {
    handleDeleteTournament(e.target.closest("tr"));
  }
}

// Edit Tournament
function handleEditTournament(row) {
  const tournamentName = row.querySelector(".tournament-name span").textContent;
  showNotification(`Edit tournament: ${tournamentName}`, "info");
  // Implement edit functionality
}

// View Tournament
function handleViewTournament(row) {
  const tournamentName = row.querySelector(".tournament-name span").textContent;
  showNotification(`View tournament: ${tournamentName}`, "info");
  // Implement view functionality
}

// Delete Tournament
function handleDeleteTournament(row) {
  const tournamentName = row.querySelector(".tournament-name span").textContent;

  if (confirm(`Yakin ingin menghapus turnamen "${tournamentName}"?`)) {
    row.remove();
    showNotification("Turnamen berhasil dihapus!", "success");
  }
}

// Logout Handler
function handleLogout() {
  // Send logout request to server
  fetch("../LOGIN/logout.php", {
    method: "POST",
    headers: {
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Clear localStorage
        localStorage.removeItem("isLoggedIn");
        localStorage.removeItem("username");
        localStorage.removeItem("userEmail");
        localStorage.removeItem("userId");
        localStorage.removeItem("userRole");

        alert("Logout berhasil!");
        window.location.href = "../../index.php";
      }
    })
    .catch((error) => {
      console.error("Logout error:", error);
      // Fallback: clear local data anyway
      localStorage.removeItem("isLoggedIn");
      localStorage.removeItem("username");
      localStorage.removeItem("userEmail");
      localStorage.removeItem("userId");
      localStorage.removeItem("userRole");
      window.location.href = "../../index.php";
    });
}

// Notification System
function showNotification(message, type = "info") {
  // Create notification element
  const notification = document.createElement("div");
  notification.className = `notification notification-${type}`;
  notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
            <button class="notification-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

  // Add to page
  document.body.appendChild(notification);

  // Show notification
  setTimeout(() => {
    notification.classList.add("show");
  }, 100);

  // Auto hide after 5 seconds
  setTimeout(() => {
    hideNotification(notification);
  }, 5000);

  // Close button handler
  notification
    .querySelector(".notification-close")
    .addEventListener("click", () => {
      hideNotification(notification);
    });
}

function getNotificationIcon(type) {
  const icons = {
    success: "check-circle",
    error: "exclamation-circle",
    warning: "exclamation-triangle",
    info: "info-circle",
  };
  return icons[type] || "info-circle";
}

function hideNotification(notification) {
  notification.classList.remove("show");
  setTimeout(() => {
    if (notification.parentNode) {
      notification.parentNode.removeChild(notification);
    }
  }, 300);
}

// Window resize handler
window.addEventListener("resize", function () {
  const sidebar = document.querySelector(".sidebar");
  const mainContent = document.querySelector(".main-content");

  if (window.innerWidth <= 768) {
    sidebar.classList.add("collapsed");
    mainContent.classList.add("expanded");
  } else if (window.innerWidth > 1024) {
    sidebar.classList.remove("collapsed");
    mainContent.classList.remove("expanded");
  }
});

// Click outside modal to close
window.addEventListener("click", function (e) {
  const modal = document.getElementById("addTournamentModal");
  if (e.target === modal) {
    closeModal();
  }
});

// Escape key to close modal
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") {
    closeModal();
  }
});

// Edit tournament function - DEPRECATED
// This function is now handled in dashboardEO.php
/*
window.editTournament = function (id) {
  fetch(`tournament_api.php?id=${id}`)
    .then((response) => response.json())
    .then((result) => {
      if (result.success) {
        // Populate form with tournament data
        const tournament = result.tournament;
        document.getElementById("tournamentName").value =
          tournament.nama_turnamen;
        document.getElementById("gameType").value = tournament.format;
        document.getElementById("startDate").value =
          tournament.tanggal_mulai.replace(" ", "T");
        document.getElementById("endDate").value = tournament.tanggal_selesai
          ? tournament.tanggal_selesai.replace(" ", "T")
          : "";
        document.getElementById("maxTeams").value = tournament.slot;
        document.getElementById("prizePool").value = tournament.hadiah_turnamen;
        document.getElementById("description").value =
          tournament.deskripsi_turnamen;
        document.getElementById("registrationFee").value =
          tournament.biaya_turnamen;

        // Change form to edit mode
        const form = document.getElementById("addTournamentForm");
        form.dataset.mode = "edit";
        form.dataset.tournamentId = id;

        // Change modal title
        document.querySelector(".modal-header h2").textContent =
          "Edit Turnamen";

        // Open modal
        openAddTournamentModal();
      } else {
        showNotification("Error: " + result.error, "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification(
        "Terjadi kesalahan saat mengambil data turnamen",
        "error"
      );
    });
};
*/

// View tournament function - DEPRECATED
// This function is now handled in dashboardEO.php
// window.viewTournament = function (id) {
//   // Deprecated - see dashboardEO.php for current implementation
// };

// Delete tournament function - DEPRECATED
// This function is now handled in dashboardEO.php
// window.deleteTournament = function (id) {
//   // Deprecated - see dashboardEO.php for current implementation
// };

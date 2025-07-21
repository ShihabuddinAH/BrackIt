// Dashboard Admin JavaScript

class DashboardAdmin {
  constructor() {
    if (window.dashboardAdminInstance) {
      return window.dashboardAdminInstance;
    }

    this.reportFilter = null;
    this.reportsTable = null;
    this.charts = {};
    this.initializeDashboard();

    window.dashboardAdminInstance = this;
  }

  // Initialize dashboard specific functionality
  initializeDashboard() {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", () => {
        this.setupDashboard();
      });
    } else {
      this.setupDashboard();
    }
  }

  setupDashboard() {
    this.initializeDashboardElements();
    this.initializeDashboardEventListeners();
    this.initializeNavigation();
    this.initializeLogout();
    this.initializeSidebar();
    // Initialize charts using class method
    setTimeout(() => {
      this.initializeCharts();
    }, 100);
  }

  // Initialize navigation
  initializeNavigation() {
    const navLinks = document.querySelectorAll(".nav-link");
    const sections = document.querySelectorAll(".content-section");

    navLinks.forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault();

        // Remove active class from all nav items
        document.querySelectorAll(".nav-item").forEach((item) => {
          item.classList.remove("active");
        });

        // Add active class to clicked nav item
        link.parentElement.classList.add("active");

        // Hide all sections
        sections.forEach((section) => {
          section.classList.remove("active");
        });

        // Show target section
        const targetSection = link.getAttribute("data-section");
        const targetElement = document.getElementById(
          targetSection + "-section"
        );
        if (targetElement) {
          targetElement.classList.add("active");
        }

        // Update page title
        const pageTitle = document.getElementById("pageTitle");
        if (pageTitle) {
          pageTitle.textContent = link.querySelector("span").textContent;
        }
      });
    });
  }

  // Initialize sidebar toggle
  initializeSidebar() {
    const sidebarToggle = document.getElementById("sidebarToggle");
    const menuToggle = document.getElementById("menuToggle");
    const sidebar = document.querySelector(".sidebar");
    const mainContent = document.querySelector(".main-content");

    if (sidebarToggle) {
      sidebarToggle.addEventListener("click", () => {
        sidebar.classList.toggle("collapsed");
      });
    }

    if (menuToggle) {
      menuToggle.addEventListener("click", () => {
        sidebar.classList.toggle("active");
      });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener("click", (e) => {
      if (window.innerWidth <= 768) {
        if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
          sidebar.classList.remove("active");
        }
      }
    });
  }

  // Initialize logout functionality
  initializeLogout() {
    const logoutBtn = document.getElementById("logoutBtn");
    if (logoutBtn) {
      logoutBtn.addEventListener("click", () => {
        if (confirm("Apakah Anda yakin ingin logout?")) {
          this.handleLogout();
        }
      });
    }
  }

  // Handle logout
  handleLogout() {
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

  // Initialize charts
  initializeCharts() {
    // Initialize all charts using class methods
    this.initializeUserLoginStatsChart();
    this.initializeDailyLoginsChart();
    this.initializeUserRegistrationStatsChart();
    this.initializeDailyRegistrationsChart();
    this.initializeTournamentStatusChart();
    this.initializeTournamentFormatChart();
    this.initializeTopTeamsChart();

    // Store charts reference globally for refresh functionality
    window.charts = this.charts;
    console.log(
      "DashboardAdmin: Chart initialization completed. Charts:",
      Object.keys(this.charts)
    );
  }

  // Method to destroy all charts
  destroyAllCharts() {
    Object.keys(this.charts).forEach((chartKey) => {
      if (
        this.charts[chartKey] &&
        typeof this.charts[chartKey].destroy === "function"
      ) {
        this.charts[chartKey].destroy();
      }
    });
    this.charts = {};
  }

  // Initialize user login statistics chart
  initializeUserLoginStatsChart() {
    const ctx = document.getElementById("userLoginStatsChart");
    if (ctx) {
      try {
        // Destroy existing chart if it exists
        if (this.charts.userLoginStats) {
          this.charts.userLoginStats.destroy();
        }

        // Get chart data from global variable if available
        const monthlyLogins = window.chartData?.monthly_logins || [];
        const labels = monthlyLogins.map((item) => item.month);
        const playersData = monthlyLogins.map((item) => item.players);
        const eosData = monthlyLogins.map((item) => item.eos);
        const adminsData = monthlyLogins.map((item) => item.admins);

        this.charts.userLoginStats = new Chart(ctx, {
          type: "line",
          data: {
            labels: labels.length
              ? labels
              : ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
            datasets: [
              {
                label: "Players Login",
                data: playersData.length
                  ? playersData
                  : [45, 52, 38, 67, 73, 89],
                borderColor: "#3b82f6",
                backgroundColor: "rgba(59, 130, 246, 0.1)",
                tension: 0.4,
              },
              {
                label: "EOs Login",
                data: eosData.length ? eosData : [12, 15, 8, 18, 22, 25],
                borderColor: "#10b981",
                backgroundColor: "rgba(16, 185, 129, 0.1)",
                tension: 0.4,
              },
              {
                label: "Admins Login",
                data: adminsData.length ? adminsData : [2, 3, 1, 4, 3, 2],
                borderColor: "#f59e0b",
                backgroundColor: "rgba(245, 158, 11, 0.1)",
                tension: 0.4,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                labels: {
                  color: "#ffffff",
                },
              },
              title: {
                display: true,
                text: "Statistik Login Pengguna",
                color: "#ffffff",
              },
            },
            scales: {
              x: {
                ticks: {
                  color: "#ffffff",
                },
                grid: {
                  color: "rgba(255, 255, 255, 0.1)",
                },
              },
              y: {
                ticks: {
                  color: "#ffffff",
                },
                grid: {
                  color: "rgba(255, 255, 255, 0.1)",
                },
              },
            },
          },
        });
      } catch (error) {
        console.error("Error initializing UserLoginStatsChart:", error);
      }
    } else {
      console.error("UserLoginStatsChart canvas not found");
    }
  }

  // Initialize daily logins chart
  initializeDailyLoginsChart() {
    const ctx = document.getElementById("dailyLoginsChart");
    if (ctx) {
      try {
        // Destroy existing chart if it exists
        if (this.charts.dailyLogins) {
          this.charts.dailyLogins.destroy();
        }

        const dailyLogins = window.chartData?.daily_logins || [];
        const labels = dailyLogins.map((item) => item.day);
        const playersData = dailyLogins.map((item) => item.players);
        const eosData = dailyLogins.map((item) => item.eos);

        this.charts.dailyLogins = new Chart(ctx, {
          type: "bar",
          data: {
            labels: labels.length
              ? labels
              : ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
            datasets: [
              {
                label: "Players",
                data: playersData.length
                  ? playersData
                  : [25, 19, 30, 15, 22, 28, 35],
                backgroundColor: "rgba(59, 130, 246, 0.8)",
                borderColor: "#3b82f6",
                borderWidth: 1,
              },
              {
                label: "EOs",
                data: eosData.length ? eosData : [8, 5, 12, 3, 7, 9, 11],
                backgroundColor: "rgba(16, 185, 129, 0.8)",
                borderColor: "#10b981",
                borderWidth: 1,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                labels: {
                  color: "#ffffff",
                },
              },
            },
            scales: {
              x: {
                ticks: {
                  color: "#ffffff",
                },
                grid: {
                  color: "rgba(255, 255, 255, 0.1)",
                },
              },
              y: {
                ticks: {
                  color: "#ffffff",
                },
                grid: {
                  color: "rgba(255, 255, 255, 0.1)",
                },
              },
            },
          },
        });
      } catch (error) {
        console.error("Error initializing DailyLoginsChart:", error);
      }
    } else {
      console.error("DailyLoginsChart canvas not found");
    }
  }

  // Initialize user registration statistics chart
  initializeUserRegistrationStatsChart() {
    const ctx = document.getElementById("userRegistrationStatsChart");
    if (ctx) {
      try {
        // Destroy existing chart if it exists
        if (this.charts.userRegistrationStats) {
          this.charts.userRegistrationStats.destroy();
        }

        const monthlyRegistrations =
          window.chartData?.monthly_registrations || [];
        const labels = monthlyRegistrations.map((item) => item.month);
        const playersData = monthlyRegistrations.map((item) => item.players);
        const eosData = monthlyRegistrations.map((item) => item.eos);

        this.charts.userRegistrationStats = new Chart(ctx, {
          type: "line",
          data: {
            labels: labels.length
              ? labels
              : ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
            datasets: [
              {
                label: "Players Registered",
                data: playersData.length
                  ? playersData
                  : [150, 200, 180, 250, 300, 280],
                borderColor: "#ef4444",
                backgroundColor: "rgba(239, 68, 68, 0.1)",
                tension: 0.4,
                fill: true,
              },
              {
                label: "EOs Registered",
                data: eosData.length ? eosData : [20, 25, 18, 35, 40, 38],
                borderColor: "#8b5cf6",
                backgroundColor: "rgba(139, 92, 246, 0.1)",
                tension: 0.4,
                fill: true,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                labels: {
                  color: "#ffffff",
                },
              },
              title: {
                display: true,
                text: "Statistik Registrasi Pengguna",
                color: "#ffffff",
              },
            },
            scales: {
              x: {
                ticks: {
                  color: "#ffffff",
                },
                grid: {
                  color: "rgba(255, 255, 255, 0.1)",
                },
              },
              y: {
                ticks: {
                  color: "#ffffff",
                },
                grid: {
                  color: "rgba(255, 255, 255, 0.1)",
                },
              },
            },
          },
        });
      } catch (error) {
        console.error("Error initializing UserRegistrationStatsChart:", error);
      }
    } else {
      console.error("UserRegistrationStatsChart canvas not found");
    }
  }

  // Initialize daily registrations chart
  initializeDailyRegistrationsChart() {
    const ctx = document.getElementById("dailyRegistrationsChart");
    if (ctx) {
      try {
        // Destroy existing chart if it exists
        if (this.charts.dailyRegistrations) {
          this.charts.dailyRegistrations.destroy();
        }

        const dailyRegistrations = window.chartData?.daily_registrations || [];
        const labels = dailyRegistrations.map((item) => item.day);
        const playersData = dailyRegistrations.map((item) => item.players);
        const eosData = dailyRegistrations.map((item) => item.eos);

        this.charts.dailyRegistrations = new Chart(ctx, {
          type: "bar",
          data: {
            labels: labels.length
              ? labels
              : ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
            datasets: [
              {
                label: "Players",
                data: playersData.length
                  ? playersData
                  : [25, 19, 3, 5, 2, 3, 9],
                backgroundColor: "rgba(239, 68, 68, 0.8)",
                borderColor: "#ef4444",
                borderWidth: 1,
              },
              {
                label: "EOs",
                data: eosData.length ? eosData : [3, 2, 1, 1, 0, 1, 2],
                backgroundColor: "rgba(139, 92, 246, 0.8)",
                borderColor: "#8b5cf6",
                borderWidth: 1,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                labels: {
                  color: "#ffffff",
                },
              },
            },
            scales: {
              x: {
                ticks: {
                  color: "#ffffff",
                },
                grid: {
                  color: "rgba(255, 255, 255, 0.1)",
                },
              },
              y: {
                ticks: {
                  color: "#ffffff",
                },
                grid: {
                  color: "rgba(255, 255, 255, 0.1)",
                },
              },
            },
          },
        });
      } catch (error) {
        console.error("Error initializing DailyRegistrationsChart:", error);
      }
    } else {
      console.error("DailyRegistrationsChart canvas not found");
    }
  }

  // Initialize tournament status chart
  initializeTournamentStatusChart() {
    const ctx = document.getElementById("tournamentStatusChart");
    if (ctx) {
      try {
        // Destroy existing chart if it exists
        if (this.charts.tournamentStatus) {
          this.charts.tournamentStatus.destroy();
        }

        const tournamentStatus = window.chartData?.tournament_status || [];
        const labels = tournamentStatus.map((item) => item.status);
        const data = tournamentStatus.map((item) => item.count);

        this.charts.tournamentStatus = new Chart(ctx, {
          type: "pie",
          data: {
            labels: labels.length ? labels : ["Active", "Pending", "Completed"],
            datasets: [
              {
                data: data.length ? data : [5, 3, 8],
                backgroundColor: [
                  "#3b82f6",
                  "#f59e0b",
                  "#10b981",
                  "#ef4444",
                  "#8b5cf6",
                ],
                borderWidth: 2,
                borderColor: "#1a1a1a",
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: "bottom",
                labels: {
                  color: "#ffffff",
                  padding: 20,
                },
              },
            },
          },
        });
      } catch (error) {
        console.error("Error initializing TournamentStatusChart:", error);
      }
    } else {
      console.error("TournamentStatusChart canvas not found");
    }
  }

  // Initialize tournament format chart
  initializeTournamentFormatChart() {
    const ctx = document.getElementById("tournamentFormatChart");
    if (ctx) {
      try {
        // Destroy existing chart if it exists
        if (this.charts.tournamentFormat) {
          this.charts.tournamentFormat.destroy();
        }

        const tournamentFormat = window.chartData?.tournament_format || [];
        const labels = tournamentFormat.map((item) => item.format);
        const data = tournamentFormat.map((item) => item.count);

        this.charts.tournamentFormat = new Chart(ctx, {
          type: "doughnut",
          data: {
            labels: labels.length ? labels : ["Team", "Individual"],
            datasets: [
              {
                data: data.length ? data : [12, 4],
                backgroundColor: ["#3b82f6", "#10b981", "#f59e0b"],
                borderWidth: 2,
                borderColor: "#1a1a1a",
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: "bottom",
                labels: {
                  color: "#ffffff",
                  padding: 20,
                },
              },
            },
          },
        });
      } catch (error) {
        console.error("Error initializing TournamentFormatChart:", error);
      }
    } else {
      console.error("TournamentFormatChart canvas not found");
    }
  }

  // Initialize top teams chart
  initializeTopTeamsChart() {
    const ctx = document.getElementById("topTeamsChart");
    if (ctx) {
      try {
        // Destroy existing chart if it exists
        if (this.charts.topTeams) {
          this.charts.topTeams.destroy();
        }

        const topTeams = window.chartData?.top_teams || [];
        const labels = topTeams.map((item) => item.name);
        const data = topTeams.map((item) => item.points);

        this.charts.topTeams = new Chart(ctx, {
          type: "bar",
          data: {
            labels: labels.length
              ? labels
              : [
                  "Team Alpha",
                  "Team Beta",
                  "Team Gamma",
                  "Team Delta",
                  "Team Epsilon",
                ],
            datasets: [
              {
                label: "Points",
                data: data.length ? data : [18, 15, 12, 9, 6],
                backgroundColor: [
                  "#3b82f6",
                  "#10b981",
                  "#f59e0b",
                  "#ef4444",
                  "#8b5cf6",
                ],
                borderWidth: 1,
                borderColor: "#ffffff",
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: "y", // This replaces horizontalBar type
            plugins: {
              legend: {
                display: false,
              },
            },
            scales: {
              x: {
                beginAtZero: true,
                ticks: {
                  color: "#ffffff",
                },
                grid: {
                  color: "rgba(255, 255, 255, 0.1)",
                },
              },
              y: {
                ticks: {
                  color: "#ffffff",
                },
                grid: {
                  color: "rgba(255, 255, 255, 0.1)",
                },
              },
            },
          },
        });
      } catch (error) {
        console.error("Error initializing TopTeamsChart:", error);
      }
    } else {
      console.error("TopTeamsChart canvas not found");
    }
  }

  // Initialize activity chart
  initializeActivityChart() {
    const ctx = document.getElementById("activityChart");
    if (ctx) {
      try {
        // Destroy existing chart if it exists
        if (this.charts.activity) {
          this.charts.activity.destroy();
        }

        this.charts.activity = new Chart(ctx, {
          type: "bar",
          data: {
            labels: [
              "Login",
              "Tournament Join",
              "Team Create",
              "Reports",
              "Matches",
            ],
            datasets: [
              {
                label: "Aktivitas",
                data: [1200, 800, 300, 50, 600],
                backgroundColor: [
                  "#ff0000",
                  "#950101",
                  "#3d0000",
                  "#ff4444",
                  "#cc0000",
                ],
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                labels: {
                  color: "#ffffff",
                },
              },
            },
            scales: {
              x: {
                ticks: {
                  color: "#ffffff",
                },
                grid: {
                  color: "rgba(255, 255, 255, 0.1)",
                },
              },
              y: {
                ticks: {
                  color: "#ffffff",
                },
                grid: {
                  color: "rgba(255, 255, 255, 0.1)",
                },
              },
            },
          },
        });
        console.log("ActivityChart initialized successfully");
      } catch (error) {
        console.error("Error initializing ActivityChart:", error);
      }
    } else {
      console.error("ActivityChart canvas not found");
    }
  }

  // Initialize popular games chart
  initializePopularGamesChart() {
    const ctx = document.getElementById("popularGamesChart");
    console.log("PopularGamesChart element:", ctx);
    if (ctx) {
      try {
        // Destroy existing chart if it exists
        if (this.charts.popularGames) {
          this.charts.popularGames.destroy();
        }

        this.charts.popularGames = new Chart(ctx, {
          type: "doughnut",
          data: {
            labels: [
              "Mobile Legends",
              "PUBG Mobile",
              "Free Fire",
              "Valorant",
              "Others",
            ],
            datasets: [
              {
                data: [35, 25, 20, 15, 5],
                backgroundColor: [
                  "#ff0000",
                  "#950101",
                  "#3d0000",
                  "#ff4444",
                  "#cc0000",
                ],
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: "bottom",
                labels: {
                  color: "#ffffff",
                },
              },
            },
          },
        });
      } catch (error) {
        console.error("Error initializing PopularGamesChart:", error);
      }
    } else {
      console.error("PopularGamesChart canvas not found");
    }
  }

  // Initialize dashboard DOM elements
  initializeDashboardElements() {
    this.reportFilter = document.getElementById("reportFilter");
    this.reportsTable = document.getElementById("reportsTable");
  }

  // Initialize dashboard event listeners
  initializeDashboardEventListeners() {
    // Report filter
    if (this.reportFilter && this.reportsTable) {
      this.reportFilter.addEventListener("input", (e) => {
        this.filterReports(e.target.value);
      });
    }

    // Action buttons
    this.initializeActionButtons();
  }

  // Initialize action buttons
  initializeActionButtons() {
    document.addEventListener("click", (e) => {
      if (e.target.closest(".btn-approve")) {
        this.handleApprove(e.target.closest("tr"));
      } else if (e.target.closest(".btn-reject")) {
        this.handleReject(e.target.closest("tr"));
      } else if (e.target.closest(".btn-view")) {
        this.handleView(e.target.closest("tr"));
      } else if (e.target.closest(".btn-edit")) {
        this.handleEdit(e.target.closest("tr"));
      } else if (e.target.closest(".btn-delete")) {
        this.handleDelete(e.target.closest("tr"));
      }
    });
  }

  // Filter reports
  filterReports(searchTerm) {
    if (!this.reportsTable) return;

    const rows = this.reportsTable.querySelectorAll("tr");
    rows.forEach((row) => {
      const text = row.textContent.toLowerCase();
      if (text.includes(searchTerm.toLowerCase())) {
        row.style.display = "";
      } else {
        row.style.display = "none";
      }
    });
  }

  // Action handlers
  handleApprove(row) {
    if (confirm("Approve this item?")) {
      // Add approve logic here
    }
  }

  handleReject(row) {
    if (confirm("Reject this item?")) {
      // Add reject logic here
    }
  }

  handleView(row) {
    // Add view logic here
  }

  handleEdit(row) {
    // Add edit logic here
  }

  handleDelete(row) {
    if (confirm("Delete this item?")) {
      // Add delete logic here
      row.remove();
    }
  }

  // Override showSection from base class for dashboard
  showSection(section) {
    // Hide all sections
    document
      .querySelectorAll(".dashboard-section")
      .forEach((s) => (s.style.display = "none"));

    if (section === "dashboard") {
      document
        .querySelectorAll(".dashboard-section")
        .forEach((s) => (s.style.display = "block"));
    }
    // Add more sections as needed for dashboard
  }

  // Dashboard specific utility functions
  addTournamentRow(name, organizer, date) {
    const table = document.getElementById("tournamentTable");
    if (table) {
      const row = table.insertRow();
      row.innerHTML = `
        <td>${name}</td>
        <td>${organizer}</td>
        <td>${date}</td>
      `;
    }
  }

  addReportRow(date, username, description) {
    if (this.reportsTable) {
      const row = this.reportsTable.insertRow(0); // Insert at the beginning
      row.innerHTML = `
        <td>${date}</td>
        <td>${username}</td>
        <td>${description}</td>
      `;
    }
  }

  clearFilter() {
    if (this.reportFilter) {
      this.reportFilter.value = "";
      this.filterTable("", this.reportsTable);
    }
  }
}

// Initialize dashboard when DOM is loaded - only if not already initialized
if (!window.dashboardAdminInitialized) {
  window.dashboardAdminInitialized = true;

  // Single initialization method to ensure it works
  function initializeDashboardAdmin() {
    if (!window.dashboardAdminInstance) {
      console.log("Creating DashboardAdmin instance...");
      window.dashboardAdminInstance = new DashboardAdmin();
    } else {
      console.log("DashboardAdmin instance already exists");
    }
  }

  // Initialize based on document state
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeDashboardAdmin);
  } else {
    initializeDashboardAdmin();
  }

  // Tournament Management Functions
  function viewAllTournaments() {
    alert("Navigating to all tournaments page...");
    // window.location.href = 'all_tournaments.php';
  }

  function viewTournamentDetail(tournamentId) {
    alert(`Viewing details for tournament: ${tournamentId}`);
    // window.location.href = `tournament_detail.php?id=${tournamentId}`;
  }

  function editTournament(tournamentId) {
    alert(`Editing tournament: ${tournamentId}`);
    // window.location.href = `edit_tournament.php?id=${tournamentId}`;
  }

  function suspendTournament(tournamentId) {
    if (
      confirm(`Are you sure you want to suspend tournament ${tournamentId}?`)
    ) {
      alert(`Tournament ${tournamentId} has been suspended.`);
      // Add AJAX call to suspend tournament
    }
  }

  function archiveTournament(tournamentId) {
    if (
      confirm(`Are you sure you want to archive tournament ${tournamentId}?`)
    ) {
      alert(`Tournament ${tournamentId} has been archived.`);
      // Add AJAX call to archive tournament
    }
  }

  function approveTournament(tournamentId) {
    if (confirm(`Approve tournament request ${tournamentId}?`)) {
      alert(`Tournament ${tournamentId} has been approved.`);
      // Add AJAX call to approve tournament
      // location.reload(); // Refresh page after action
    }
  }

  function rejectTournament(tournamentId) {
    if (confirm(`Reject tournament request ${tournamentId}?`)) {
      const reason = prompt("Please provide a reason for rejection:");
      if (reason && reason.trim() !== "") {
        alert(
          `Tournament ${tournamentId} has been rejected. Reason: ${reason}`
        );
        // Add AJAX call to reject tournament with reason
        // location.reload(); // Refresh page after action
      }
    }
  }

  function bulkAction() {
    const action = document.getElementById("bulkAction").value;
    const checkboxes = document.querySelectorAll(
      'input[name="tournament_ids[]"]:checked'
    );

    if (checkboxes.length === 0) {
      alert("Please select at least one tournament request.");
      return;
    }

    if (action === "") {
      alert("Please select an action.");
      return;
    }

    const tournamentIds = Array.from(checkboxes).map((cb) => cb.value);
    const actionText = action === "approve" ? "approve" : "reject";

    if (
      confirm(
        `Are you sure you want to ${actionText} ${tournamentIds.length} tournament request(s)?`
      )
    ) {
      alert(
        `${tournamentIds.length} tournament request(s) have been ${actionText}ed.`
      );
      // Add AJAX call to perform bulk action
      // location.reload(); // Refresh page after action
    }
  }

  function toggleAll(source) {
    const checkboxes = document.querySelectorAll(
      'input[name="tournament_ids[]"]'
    );
    checkboxes.forEach((checkbox) => {
      checkbox.checked = source.checked;
    });
  }

  // Make tournament functions globally available
  window.viewAllTournaments = viewAllTournaments;
  window.viewTournamentDetail = viewTournamentDetail;
  window.editTournament = editTournament;
  window.suspendTournament = suspendTournament;
  window.archiveTournament = archiveTournament;
  window.approveTournament = approveTournament;
  window.rejectTournament = rejectTournament;
  window.bulkAction = bulkAction;
  window.toggleAll = toggleAll;
}

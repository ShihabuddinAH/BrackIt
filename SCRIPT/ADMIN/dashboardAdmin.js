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
    // Add delay for charts to ensure DOM is fully ready
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
    console.log("Initializing charts...");
    this.initializeUserStatsChart();
    this.initializeActivityChart();
    this.initializePopularGamesChart();
  }

  // Initialize user statistics chart
  initializeUserStatsChart() {
    const ctx = document.getElementById("userStatsChart");
    console.log("UserStatsChart element:", ctx);
    if (ctx) {
      try {
        this.charts.userStats = new Chart(ctx, {
          type: "line",
          data: {
            labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
            datasets: [
              {
                label: "Pengguna Baru",
                data: [150, 200, 180, 250, 300, 280],
                borderColor: "#ff0000",
                backgroundColor: "rgba(255, 0, 0, 0.1)",
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
        console.log("UserStatsChart initialized successfully");
      } catch (error) {
        console.error("Error initializing UserStatsChart:", error);
      }
    } else {
      console.error("UserStatsChart canvas not found");
    }
  }

  // Initialize activity chart
  initializeActivityChart() {
    const ctx = document.getElementById("activityChart");
    console.log("ActivityChart element:", ctx);
    if (ctx) {
      try {
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
        console.log("PopularGamesChart initialized successfully");
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
      console.log("Approved:", row);
    }
  }

  handleReject(row) {
    if (confirm("Reject this item?")) {
      // Add reject logic here
      console.log("Rejected:", row);
    }
  }

  handleView(row) {
    // Add view logic here
    console.log("View:", row);
  }

  handleEdit(row) {
    // Add edit logic here
    console.log("Edit:", row);
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

  // Multiple initialization methods to ensure it works
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
      new DashboardAdmin();
    });
  } else {
    new DashboardAdmin();
  }

  // Backup initialization
  window.addEventListener("load", () => {
    if (!window.dashboardAdminInstance) {
      window.dashboardAdminInstance = new DashboardAdmin();
    }
  });
}

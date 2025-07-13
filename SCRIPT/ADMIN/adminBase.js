// Base Admin JavaScript - Shared functionality between all admin pages

// Global DOM Elements
let toggleBtn, sidebar, mainContent, navLinks;

// Base Admin Class
class AdminBase {
  constructor() {
    this.initializeElements();
    this.initialize();
  }

  // Initialize DOM elements
  initializeElements() {
    toggleBtn = document.getElementById("toggleBtn");
    sidebar = document.getElementById("sidebar");
    mainContent = document.getElementById("mainContent");
    navLinks = document.querySelectorAll(".nav-link");
  }

  // Initialize the application
  initialize() {
    document.addEventListener("DOMContentLoaded", () => {
      this.initializeResponsiveState();
      this.initializeEventListeners();
    });
  }

  // Initialize responsive state based on screen size
  initializeResponsiveState() {
    if (window.innerWidth <= 768) {
      sidebar.classList.add("collapsed");
      mainContent.classList.add("expanded");
    }
  }

  // Initialize all event listeners
  initializeEventListeners() {
    // Toggle sidebar
    if (toggleBtn) {
      toggleBtn.addEventListener("click", this.toggleSidebar);
    }

    // Navigation links
    navLinks.forEach((link) => {
      link.addEventListener("click", this.handleNavigation.bind(this));
    });

    // Window resize handler
    window.addEventListener("resize", this.handleWindowResize);

    // Click outside sidebar to close (mobile)
    document.addEventListener("click", this.handleOutsideClick);
  }

  // Toggle Sidebar Function
  toggleSidebar() {
    sidebar.classList.toggle("open");
    sidebar.classList.toggle("collapsed");
    mainContent.classList.toggle("expanded");
  }

  // Handle Navigation
  handleNavigation(e) {
    e.preventDefault();

    // Remove active class from all links
    navLinks.forEach((l) => l.classList.remove("active"));

    // Add active class to clicked link
    e.currentTarget.classList.add("active");

    // Show/hide sections based on navigation
    const section = e.currentTarget.getAttribute("data-section");
    this.showSection(section);

    // Close sidebar on mobile after navigation
    if (window.innerWidth <= 768) {
      sidebar.classList.remove("open");
      sidebar.classList.add("collapsed");
      mainContent.classList.add("expanded");
    }
  }

  // Show/Hide Sections - Override in child classes
  showSection(section) {
    // Base implementation - to be overridden
    console.log(`Showing section: ${section}`);
  }

  // Handle Window Resize
  handleWindowResize() {
    if (window.innerWidth > 768) {
      // Desktop view
      sidebar.classList.remove("open", "collapsed");
      mainContent.classList.remove("expanded");
    } else {
      // Mobile view
      sidebar.classList.add("collapsed");
      mainContent.classList.add("expanded");
    }
  }

  // Handle clicks outside sidebar on mobile
  handleOutsideClick(e) {
    if (window.innerWidth <= 768) {
      if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
        sidebar.classList.remove("open");
        sidebar.classList.add("collapsed");
        mainContent.classList.add("expanded");
      }
    }
  }

  // Animated Counter Function
  animateCounter(element, target, duration = 2000) {
    if (!element) return;

    const start = 0;
    const increment = target / (duration / 16);
    let current = start;

    const timer = setInterval(() => {
      current += increment;
      if (current >= target) {
        current = target;
        clearInterval(timer);
      }
      element.textContent = Math.floor(current).toLocaleString();
    }, 16);
  }

  // Generic filter function
  filterTable(filterValue, tableElement) {
    if (!tableElement) {
      console.warn("Table element not found for filtering");
      return;
    }

    let rows;

    // Handle both table and tbody elements
    if (tableElement.tagName === "TBODY") {
      // If we're given a tbody, get its rows directly
      rows = tableElement.getElementsByTagName("tr");
    } else if (tableElement.tagName === "TABLE") {
      // If we're given a table, get tbody rows or all rows
      const tbody = tableElement.querySelector("tbody");
      if (tbody) {
        rows = tbody.getElementsByTagName("tr");
      } else {
        // If no tbody, get all table rows and skip header
        rows = tableElement.getElementsByTagName("tr");
      }
    } else {
      console.warn("Element provided is not a table or tbody");
      return;
    }

    const filterValueLower = filterValue.toLowerCase().trim();

    Array.from(rows).forEach((row, index) => {
      // For tbody, all rows are data rows
      // For table without tbody, skip first row if it's in thead
      const isHeaderRow =
        tableElement.tagName === "TABLE" &&
        index === 0 &&
        (row.parentElement.tagName === "THEAD" ||
          row.getElementsByTagName("th").length > 0);

      if (isHeaderRow) {
        row.style.display = "";
        return;
      }

      // Filter content rows
      if (filterValueLower === "") {
        // Show all rows when filter is empty
        row.style.display = "";
      } else {
        const text = row.textContent.toLowerCase();
        if (text.includes(filterValueLower)) {
          row.style.display = "";
        } else {
          row.style.display = "none";
        }
      }
    });
  }

  // Show no results message
  showNoResultsMessage(
    tableElement,
    message = "Tidak ada hasil yang ditemukan"
  ) {
    // Remove existing no-results message
    this.hideNoResultsMessage(tableElement);

    let containerElement = tableElement;
    if (tableElement.tagName === "TBODY") {
      containerElement =
        tableElement.closest(".table-container") || tableElement.parentElement;
    }

    const noResultsDiv = document.createElement("div");
    noResultsDiv.className = "no-results-message";
    noResultsDiv.style.cssText = `
      text-align: center;
      padding: 40px 20px;
      color: rgba(255, 255, 255, 0.6);
      font-style: italic;
      background: rgba(255, 255, 255, 0.05);
      margin: 10px 0;
      border-radius: 8px;
      border: 1px dashed rgba(255, 255, 255, 0.2);
    `;
    noResultsDiv.textContent = message;

    containerElement.parentElement.appendChild(noResultsDiv);
  }

  // Hide no results message
  hideNoResultsMessage(tableElement) {
    let containerElement = tableElement;
    if (tableElement.tagName === "TBODY") {
      containerElement =
        tableElement.closest(".table-container") || tableElement.parentElement;
    }

    const existingMessage = containerElement.parentElement.querySelector(
      ".no-results-message"
    );
    if (existingMessage) {
      existingMessage.remove();
    }
  }

  // Enhanced filter function with visual feedback
  filterTableWithFeedback(filterValue, tableElement, noResultsMessage) {
    this.filterTable(filterValue, tableElement);

    // Check if any rows are visible (excluding header)
    let rows;
    if (tableElement.tagName === "TBODY") {
      rows = tableElement.getElementsByTagName("tr");
    } else {
      const tbody = tableElement.querySelector("tbody");
      rows = tbody
        ? tbody.getElementsByTagName("tr")
        : tableElement.getElementsByTagName("tr");
    }

    let hasVisibleRows = false;
    Array.from(rows).forEach((row) => {
      if (row.style.display !== "none") {
        hasVisibleRows = true;
      }
    });

    if (!hasVisibleRows && filterValue.trim() !== "") {
      this.showNoResultsMessage(tableElement, noResultsMessage);
    } else {
      this.hideNoResultsMessage(tableElement);
    }
  }
}

// Utility Functions
const AdminUtils = {
  // Function to update statistics
  updateStatistic: function (elementId, newValue) {
    const element = document.getElementById(elementId);
    if (element) {
      const adminInstance = new AdminBase();
      adminInstance.animateCounter(element, newValue);
    }
  },

  // Function to show notification
  showNotification: function (message, type = "info") {
    // Simple alert for now - can be enhanced with toast notifications
    alert(message);
  },

  // Function to confirm action
  confirmAction: function (message) {
    return confirm(message);
  },

  // Function to format date
  formatDate: function (dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString("id-ID");
  },
};

// Export for use in other files
window.AdminBase = AdminBase;
window.AdminUtils = AdminUtils;

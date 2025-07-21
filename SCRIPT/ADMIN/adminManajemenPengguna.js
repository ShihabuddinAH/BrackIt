// Admin User Management JavaScript

class AdminUserManagement extends AdminBase {
  constructor() {
    super();
    this.userFilter = null;
    this.usersTable = null;
    this.initializeUserManagement();
  }

  // Initialize user management specific functionality
  initializeUserManagement() {
    document.addEventListener("DOMContentLoaded", () => {
      this.initializeUserElements();
      this.initializeUserEventListeners();
    });
  }

  // Initialize user management DOM elements
  initializeUserElements() {
    this.userFilter = document.getElementById("userFilter");
    this.usersTable = document.getElementById("usersTable");
  }

  // Initialize user management event listeners
  initializeUserEventListeners() {
    // Add user button
    const addUserBtn = document.getElementById("addUserBtn");
    if (addUserBtn) {
      addUserBtn.addEventListener("click", this.addUser.bind(this));
    }

    // User filter with debounce for better performance
    if (this.userFilter && this.usersTable) {
      let filterTimeout;
      this.userFilter.addEventListener("input", (e) => {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(() => {
          this.filterTableWithFeedback(
            e.target.value,
            this.usersTable,
            "Tidak ada pengguna yang cocok dengan pencarian"
          );
        }, 300); // 300ms delay for better performance
      });
    }
  }

  // Override showSection from base class - not needed for dedicated user management page
  showSection(section) {
    // Since this is a dedicated user management page, we don't need section switching
  }

  // User Management Functions
  editUser(userId) {
    AdminUtils.showNotification(
      `Edit user dengan ID: ${userId}\n\nFitur ini akan membuka form edit pengguna.`
      // Here you would typically open an edit modal or redirect to edit page
    );
  }

  deleteUser(userId) {
    if (
      AdminUtils.confirmAction(
        "Apakah Anda yakin ingin menghapus pengguna ini?"
      )
    ) {
      // Find and remove the row
      const row = event.target.closest("tr");
      if (row) {
        row.style.transition = "all 0.3s ease";
        row.style.opacity = "0";
        row.style.transform = "translateX(-100%)";

        setTimeout(() => {
          row.remove();
          AdminUtils.showNotification(
            `Pengguna dengan ID ${userId} berhasil dihapus.`
          );
        }, 300);
      }
    }
  }

  // Add User Function
  addUser() {
    AdminUtils.showNotification(
      "Tambah Pengguna Baru\n\nFitur ini akan membuka form untuk menambahkan pengguna baru."
      // Here you would typically open an add user modal or redirect to add user page
    );
  }

  // Add new user to table
  addUserToTable(userData) {
    if (!this.usersTable) return;

    const row = this.usersTable.insertRow();
    row.innerHTML = `
      <td>${userData.id}</td>
      <td>${userData.username}</td>
      <td>${userData.email}</td>
      <td>${userData.fullName}</td>
      <td><span class="status-badge ${userData.status.toLowerCase()}">${
      userData.status
    }</span></td>
      <td>${AdminUtils.formatDate(userData.registrationDate)}</td>
      <td>
        <div class="action-buttons">
          <button class="btn-edit" onclick="userManagement.editUser('${
            userData.id
          }')">
            Edit
          </button>
          <button class="btn-delete" onclick="userManagement.deleteUser('${
            userData.id
          }')">
            Hapus
          </button>
        </div>
      </td>
    `;
  }

  // Update user status
  updateUserStatus(userId, newStatus) {
    const rows = this.usersTable.getElementsByTagName("tr");

    Array.from(rows).forEach((row) => {
      const cells = row.getElementsByTagName("td");
      if (cells.length > 0 && cells[0].textContent === userId) {
        const statusCell = cells[4];
        const statusBadge = statusCell.querySelector(".status-badge");
        if (statusBadge) {
          statusBadge.className = `status-badge ${newStatus.toLowerCase()}`;
          statusBadge.textContent = newStatus;
        }
      }
    });
  }

  // Get user statistics
  getUserStatistics() {
    if (!this.usersTable) return null;

    const rows = this.usersTable.getElementsByTagName("tr");
    const stats = {
      total: rows.length,
      active: 0,
      inactive: 0,
      suspended: 0,
    };

    Array.from(rows).forEach((row) => {
      const statusBadge = row.querySelector(".status-badge");
      if (statusBadge) {
        const status = statusBadge.textContent.toLowerCase();
        if (stats.hasOwnProperty(status)) {
          stats[status]++;
        }
      }
    });

    return stats;
  }
}

// Global functions for onclick handlers
window.editUser = function (userId) {
  if (window.userManagement) {
    window.userManagement.editUser(userId);
  }
};

window.deleteUser = function (userId) {
  if (window.userManagement) {
    window.userManagement.deleteUser(userId);
  }
};

// Initialize user management when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  window.userManagement = new AdminUserManagement();
});

// Export for external use
window.AdminUserManagement = AdminUserManagement;

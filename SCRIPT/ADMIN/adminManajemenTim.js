// Admin Team & Party Management JavaScript

class AdminTeamManagement extends AdminBase {
  constructor() {
    super();
    this.teamFilter = null;
    this.teamsTable = null;
    this.partyFilter = null;
    this.partiesTable = null;
    this.initializeTeamManagement();
  }

  // Initialize team & party management specific functionality
  initializeTeamManagement() {
    document.addEventListener("DOMContentLoaded", () => {
      this.initializeTeamElements();
      this.initializeTeamEventListeners();
    });
  }

  // Initialize team & party management DOM elements
  initializeTeamElements() {
    this.teamFilter = document.getElementById("teamFilter");
    this.teamsTable = document.getElementById("teamsTable");
    this.partyFilter = document.getElementById("partyFilter");
    this.partiesTable = document.getElementById("partiesTable");
  }

  // Initialize team & party management event listeners
  initializeTeamEventListeners() {
    // Add team button
    const addTeamBtn = document.getElementById("addTeamBtn");
    if (addTeamBtn) {
      addTeamBtn.addEventListener("click", this.addTeam.bind(this));
    }

    // Add party button
    const addPartyBtn = document.getElementById("addPartyBtn");
    if (addPartyBtn) {
      addPartyBtn.addEventListener("click", this.addParty.bind(this));
    }

    // Team filter with debounce for better performance
    if (this.teamFilter && this.teamsTable) {
      let teamFilterTimeout;
      this.teamFilter.addEventListener("input", (e) => {
        clearTimeout(teamFilterTimeout);
        teamFilterTimeout = setTimeout(() => {
          this.filterTableWithFeedback(
            e.target.value,
            this.teamsTable,
            "Tidak ada tim yang cocok dengan pencarian"
          );
        }, 300); // 300ms delay for better performance
      });
    }

    // Party filter with debounce for better performance
    if (this.partyFilter && this.partiesTable) {
      let partyFilterTimeout;
      this.partyFilter.addEventListener("input", (e) => {
        clearTimeout(partyFilterTimeout);
        partyFilterTimeout = setTimeout(() => {
          this.filterTableWithFeedback(
            e.target.value,
            this.partiesTable,
            "Tidak ada party yang cocok dengan pencarian"
          );
        }, 300); // 300ms delay for better performance
      });
    }
  }

  // Override showSection from base class - not needed for dedicated team management page
  showSection(section) {
    // Since this is a dedicated team management page, we don't need section switching
    console.log(`Team management page - section: ${section}`);
  }

  // Team Management Functions
  editTeam(teamId) {
    AdminUtils.showNotification(
      `Edit tim dengan ID: ${teamId}\n\nFitur ini akan membuka form edit tim.`
    );
    // Here you would typically open an edit modal or redirect to edit page
  }

  deleteTeam(teamId) {
    if (
      AdminUtils.confirmAction("Apakah Anda yakin ingin menghapus tim ini?")
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
            `Tim dengan ID ${teamId} berhasil dihapus.`
          );
        }, 300);
      }
    }
  }

  // Add Team Function
  addTeam() {
    AdminUtils.showNotification(
      "Tambah Tim Baru\n\nFitur ini akan membuka form untuk menambahkan tim baru."
    );
    // Here you would typically open an add team modal or redirect to add team page
  }

  // Party Management Functions
  editParty(partyId) {
    AdminUtils.showNotification(
      `Edit party dengan ID: ${partyId}\n\nFitur ini akan membuka form edit party.`
    );
    // Here you would typically open an edit modal or redirect to edit page
  }

  deleteParty(partyId) {
    if (
      AdminUtils.confirmAction("Apakah Anda yakin ingin menghapus party ini?")
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
            `Party dengan ID ${partyId} berhasil dihapus.`
          );
        }, 300);
      }
    }
  }

  // Add Party Function
  addParty() {
    AdminUtils.showNotification(
      "Tambah Party Baru\n\nFitur ini akan membuka form untuk menambahkan party baru."
    );
    // Here you would typically open an add party modal or redirect to add party page
  }

  // Add new team to table
  addTeamToTable(teamData) {
    if (!this.teamsTable) return;

    const row = this.teamsTable.insertRow();
    row.innerHTML = `
      <td>${teamData.id}</td>
      <td>${teamData.name}</td>
      <td>${teamData.captain}</td>
      <td>${teamData.memberCount}</td>
      <td>${teamData.game}</td>
      <td><span class="status-badge ${teamData.status.toLowerCase()}">${
      teamData.status
    }</span></td>
      <td>${AdminUtils.formatDate(teamData.createdDate)}</td>
      <td>
        <div class="action-buttons">
          <button class="btn-edit" onclick="teamManagement.editTeam('${
            teamData.id
          }')">
            Edit
          </button>
          <button class="btn-delete" onclick="teamManagement.deleteTeam('${
            teamData.id
          }')">
            Hapus
          </button>
        </div>
      </td>
    `;
  }

  // Add new party to table
  addPartyToTable(partyData) {
    if (!this.partiesTable) return;

    const row = this.partiesTable.insertRow();
    row.innerHTML = `
      <td>${partyData.id}</td>
      <td>${partyData.name}</td>
      <td>${partyData.leader}</td>
      <td>${partyData.memberCount}</td>
      <td>${partyData.game}</td>
      <td><span class="status-badge ${partyData.status.toLowerCase()}">${
      partyData.status
    }</span></td>
      <td>${AdminUtils.formatDate(partyData.createdDate)}</td>
      <td>
        <div class="action-buttons">
          <button class="btn-edit" onclick="teamManagement.editParty('${
            partyData.id
          }')">
            Edit
          </button>
          <button class="btn-delete" onclick="teamManagement.deleteParty('${
            partyData.id
          }')">
            Hapus
          </button>
        </div>
      </td>
    `;
  }

  // Update team status
  updateTeamStatus(teamId, newStatus) {
    const rows = this.teamsTable.getElementsByTagName("tr");

    Array.from(rows).forEach((row) => {
      const cells = row.getElementsByTagName("td");
      if (cells.length > 0 && cells[0].textContent === teamId) {
        const statusCell = cells[5];
        const statusBadge = statusCell.querySelector(".status-badge");
        if (statusBadge) {
          statusBadge.className = `status-badge ${newStatus.toLowerCase()}`;
          statusBadge.textContent = newStatus;
        }
      }
    });
  }

  // Update party status
  updatePartyStatus(partyId, newStatus) {
    const rows = this.partiesTable.getElementsByTagName("tr");

    Array.from(rows).forEach((row) => {
      const cells = row.getElementsByTagName("td");
      if (cells.length > 0 && cells[0].textContent === partyId) {
        const statusCell = cells[5];
        const statusBadge = statusCell.querySelector(".status-badge");
        if (statusBadge) {
          statusBadge.className = `status-badge ${newStatus.toLowerCase()}`;
          statusBadge.textContent = newStatus;
        }
      }
    });
  }

  // Get team statistics
  getTeamStatistics() {
    if (!this.teamsTable) return null;

    const rows = this.teamsTable.getElementsByTagName("tr");
    const stats = {
      total: rows.length,
      active: 0,
      recruiting: 0,
      disbanded: 0,
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

  // Get party statistics
  getPartyStatistics() {
    if (!this.partiesTable) return null;

    const rows = this.partiesTable.getElementsByTagName("tr");
    const stats = {
      total: rows.length,
      active: 0,
      recruiting: 0,
      inactive: 0,
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
window.editTeam = function (teamId) {
  if (window.teamManagement) {
    window.teamManagement.editTeam(teamId);
  }
};

window.deleteTeam = function (teamId) {
  if (window.teamManagement) {
    window.teamManagement.deleteTeam(teamId);
  }
};

window.editParty = function (partyId) {
  if (window.teamManagement) {
    window.teamManagement.editParty(partyId);
  }
};

window.deleteParty = function (partyId) {
  if (window.teamManagement) {
    window.teamManagement.deleteParty(partyId);
  }
};

// Initialize team management when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  window.teamManagement = new AdminTeamManagement();
});

// Export for external use
window.AdminTeamManagement = AdminTeamManagement;

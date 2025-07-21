// Party & Team Management JavaScript

class PartyTeamManagement {
  constructor() {
    this.currentDataType = null;
    this.currentEditId = null;
    this.initialize();
  }

  initialize() {
    this.setupEventListeners();
    this.setupModals();
  }

  setupEventListeners() {
    // View all buttons
    document.addEventListener("click", (e) => {
      if (
        e.target.matches('[onclick*="viewAllParties"]') ||
        e.target.closest('[onclick*="viewAllParties"]')
      ) {
        e.preventDefault();
        this.viewAllData("party");
      }
      if (
        e.target.matches('[onclick*="viewAllTeams"]') ||
        e.target.closest('[onclick*="viewAllTeams"]')
      ) {
        e.preventDefault();
        this.viewAllData("team");
      }
    });

    // Add buttons
    const addPartyBtn = document.getElementById("addPartyBtn");
    const addTeamBtn = document.getElementById("addTeamBtn");

    if (addPartyBtn) {
      addPartyBtn.addEventListener("click", () => this.openAddModal("party"));
    }
    if (addTeamBtn) {
      addTeamBtn.addEventListener("click", () => this.openAddModal("team"));
    }

    // Form submissions
    const addForm = document.getElementById("addPartyTeamForm");
    const editForm = document.getElementById("editPartyTeamForm");

    if (addForm) {
      addForm.addEventListener("submit", (e) => {
        e.preventDefault();
        this.handleAdd();
      });
    }

    if (editForm) {
      editForm.addEventListener("submit", (e) => {
        e.preventDefault();
        this.handleEdit();
      });
    }
  }

  setupModals() {
    // Setup modal close handlers
    document.addEventListener("click", (e) => {
      // Handle close button clicks
      if (
        e.target.matches("[data-modal-hide]") ||
        e.target.closest("[data-modal-hide]")
      ) {
        const closeBtn = e.target.matches("[data-modal-hide]")
          ? e.target
          : e.target.closest("[data-modal-hide]");
        const modalId = closeBtn.getAttribute("data-modal-hide");
        this.hideModal(modalId);
      }

      // Handle backdrop clicks
      if (
        e.target.classList.contains("modal") &&
        !e.target.classList.contains("hidden")
      ) {
        this.hideModal(e.target.id);
      }
    });

    // Handle ESC key
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        // Find visible modals and close them
        const visibleModals = document.querySelectorAll(".modal:not(.hidden)");
        visibleModals.forEach((modal) => {
          this.hideModal(modal.id);
        });
      }
    });
  }

  // View all data function
  viewAllData(dataType) {
    this.currentDataType = dataType;

    // Show loading
    this.showAlert("Loading", `Memuat semua data ${dataType}...`, "info");

    // Fetch all data
    fetch(`party_team_api.php?action=getAll&type=${dataType}`)
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
      })
      .then((text) => {
        try {
          const data = JSON.parse(text);

          if (data.success) {
            this.openAllDataModal(dataType, data.data);
          } else {
            console.error(`API error:`, data.error);
            this.showAlert("Error", data.error || "Gagal memuat data", "error");
          }
        } catch (parseError) {
          console.error(`JSON parse error:`, parseError);
          console.error(`Response text:`, text);
          this.showAlert(
            "Error",
            "Invalid response format from server",
            "error"
          );
        }
      })
      .catch((error) => {
        console.error("Fetch error:", error);
        this.showAlert(
          "Error",
          "Terjadi kesalahan saat memuat data: " + error.message,
          "error"
        );
      });
  }

  // Open modal to show all data
  openAllDataModal(dataType, items) {
    let modal = document.getElementById("allPartyTeamModal");
    if (!modal) {
      this.createAllDataModal();
      modal = document.getElementById("allPartyTeamModal");
    }

    const modalTitle = modal.querySelector(".modal-header h3");
    const modalBody = modal.querySelector(".modal-body");

    const typeNames = {
      party: "Party",
      team: "Team",
    };
    modalTitle.textContent = `Semua ${typeNames[dataType]} (${items.length})`;

    modalBody.innerHTML = this.createDataTable(dataType, items);
    modal.classList.remove("hidden");
  }

  // Create all data modal
  createAllDataModal() {
    const modalHTML = `
      <div id="allPartyTeamModal" class="modal hidden">
        <div class="modal-content" style="max-width: 90vw; width: 1200px;">
          <div class="modal-header">
            <h3>Semua Data</h3>
            <button class="modal-close" data-modal-hide="allPartyTeamModal" type="button">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <!-- Content will be populated by JavaScript -->
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-hide="allPartyTeamModal">
              Tutup
            </button>
          </div>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML("beforeend", modalHTML);
  }

  // Create data table HTML
  createDataTable(dataType, items) {
    if (items.length === 0) {
      return `<div class="empty-state">
        <i class="fas fa-users" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
        <p>Belum ada ${dataType} yang terdaftar.</p>
      </div>`;
    }

    // Define columns based on data type
    let columns = ["ID", "Nama"];
    if (dataType === "party") {
      columns.push("Leader", "Jumlah Menang", "Anggota", "Dibuat");
    } else if (dataType === "team") {
      columns.push("Leader", "Win", "Point", "Anggota", "Dibuat");
    }
    columns.push("Aksi");

    let tableHTML = `
      <div class="all-users-table">
        <div class="table-controls">
          <input type="text" id="dataSearchInput" placeholder="Cari ${dataType}..." class="search-input">
        </div>
        <table>
          <thead>
            <tr>
              ${columns.map((col) => `<th>${col}</th>`).join("")}
            </tr>
          </thead>
          <tbody id="allDataTableBody">
    `;

    items.forEach((item) => {
      // Handle null/undefined created_at for teams
      const createdDate = item.created_at
        ? new Date(item.created_at).toLocaleDateString("id-ID")
        : "-";

      if (dataType === "party") {
        tableHTML += `<tr>
          <td>${item.id}</td>
          <td>
            <div class="item-info">
              <i class="fas fa-users item-icon"></i>
              <span>${item.nama_party || "-"}</span>
            </div>
          </td>
          <td>${item.leader_nickname || item.leader_username || "-"}</td>
          <td>${item.jml_menang || 0}</td>
          <td>${item.member_count || 0}/5</td>
          <td>${createdDate}</td>
          <td>
            <div class="action-buttons">
              <button class="btn-view" title="Detail" onclick="partyTeamManager.viewDetail('${dataType}', ${
          item.id
        })">
                <i class="fas fa-eye"></i>
              </button>
              <button class="btn-edit" title="Edit" onclick="partyTeamManager.openEditModal('${dataType}', ${
          item.id
        })">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn-delete" title="Hapus" onclick="partyTeamManager.deleteData('${dataType}', ${
          item.id
        }, '${item.nama_party}')">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </td>
        </tr>`;
      } else if (dataType === "team") {
        tableHTML += `<tr>
          <td>${item.id}</td>
          <td>
            <div class="item-info">
              <i class="fas fa-shield-alt item-icon"></i>
              <span>${item.nama_team || "-"}</span>
            </div>
          </td>
          <td>${item.leader_nickname || item.leader_username || "-"}</td>
          <td>${item.win || 0}</td>
          <td>${item.point || 0}</td>
          <td>${item.member_count || 0}/5</td>
          <td>${createdDate}</td>
          <td>
            <div class="action-buttons">
              <button class="btn-view" title="Detail" onclick="partyTeamManager.viewDetail('${dataType}', ${
          item.id
        })">
                <i class="fas fa-eye"></i>
              </button>
              <button class="btn-edit" title="Edit" onclick="partyTeamManager.openEditModal('${dataType}', ${
          item.id
        })">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn-delete" title="Hapus" onclick="partyTeamManager.deleteData('${dataType}', ${
          item.id
        }, '${item.nama_team}')">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </td>
        </tr>`;
      }
    });

    tableHTML += `
          </tbody>
        </table>
      </div>
    `;

    return tableHTML;
  }

  // Open add modal
  openAddModal(dataType) {
    this.currentDataType = dataType;

    let modal = document.getElementById("addPartyTeamModal");
    if (!modal) {
      this.createAddModal();
      modal = document.getElementById("addPartyTeamModal");
    }

    const modalTitle = modal.querySelector(".modal-header h3");
    const form = document.getElementById("addPartyTeamForm");

    modalTitle.textContent = `Tambah ${
      dataType === "party" ? "Party" : "Team"
    } Baru`;

    // Reset form
    form.reset();

    // Show/hide specific fields
    this.toggleDataTypeFields(dataType, "add");

    modal.classList.remove("hidden");
  }

  // Create add modal
  createAddModal() {
    const modalHTML = `
      <div id="addPartyTeamModal" class="modal hidden">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Tambah Data Baru</h3>
            <button class="modal-close" data-modal-hide="addPartyTeamModal" type="button">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <form id="addPartyTeamForm">
              <div class="form-group">
                <label class="form-label">Nama</label>
                <input type="text" id="addName" name="name" class="form-input" required placeholder="Masukkan nama">
              </div>
              
              <div class="form-group">
                <label class="form-label">Leader (Player ID)</label>
                <input type="number" id="addLeader" name="leader" class="form-input" required placeholder="Masukkan Player ID">
              </div>
              
              <!-- Team specific fields -->
              <div id="addLogoField" class="form-group data-specific-field" style="display:none;">
                <label class="form-label">Logo Team</label>
                <input type="text" id="addLogo" name="logo" class="form-input" placeholder="default.png">
              </div>
              
              <div id="addDescriptionField" class="form-group data-specific-field" style="display:none;">
                <label class="form-label">Deskripsi</label>
                <textarea id="addDescription" name="description" class="form-input" rows="3" placeholder="Deskripsi team"></textarea>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-hide="addPartyTeamModal">
              Batal
            </button>
            <button type="submit" form="addPartyTeamForm" class="btn btn-primary">
              <i class="fas fa-save"></i>
              Simpan
            </button>
          </div>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML("beforeend", modalHTML);
  }

  // Toggle fields based on data type
  toggleDataTypeFields(dataType, mode) {
    const logoField = document.getElementById(`${mode}LogoField`);
    const descriptionField = document.getElementById(`${mode}DescriptionField`);

    if (dataType === "team") {
      if (logoField) logoField.style.display = "block";
      if (descriptionField) descriptionField.style.display = "block";
    } else {
      if (logoField) logoField.style.display = "none";
      if (descriptionField) descriptionField.style.display = "none";
    }
  }

  // Handle add
  handleAdd() {
    const form = document.getElementById("addPartyTeamForm");
    const formData = new FormData(form);

    const data = {
      id_leader: parseInt(formData.get("leader")),
    };

    if (this.currentDataType === "party") {
      data.nama_party = formData.get("name");
    } else if (this.currentDataType === "team") {
      data.nama_team = formData.get("name");
      data.logo_team = formData.get("logo") || "default.png";
      data.deskripsi_team = formData.get("description") || "";
    }

    fetch(`party_team_api.php?type=${this.currentDataType}`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(data),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          this.showAlert("Success", data.message, "success");
          this.hideModal("addPartyTeamModal");
          // Refresh the page or update the table
          location.reload();
        } else {
          this.showAlert("Error", data.error, "error");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        this.showAlert(
          "Error",
          "Terjadi kesalahan saat menyimpan data",
          "error"
        );
      });
  }

  // Open edit modal
  openEditModal(dataType, id) {
    this.currentDataType = dataType;
    this.currentEditId = id;

    // Fetch current data
    fetch(`party_team_api.php?type=${dataType}&id=${id}`)
      .then((response) => response.json())
      .then((result) => {
        if (result.success) {
          this.populateEditModal(result.data);
        } else {
          this.showAlert("Error", result.error, "error");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        this.showAlert("Error", "Gagal memuat data", "error");
      });
  }

  // Populate edit modal
  populateEditModal(data) {
    let modal = document.getElementById("editPartyTeamModal");
    if (!modal) {
      this.createEditModal();
      modal = document.getElementById("editPartyTeamModal");
    }

    const modalTitle = modal.querySelector(".modal-header h3");
    modalTitle.textContent = `Edit ${
      this.currentDataType === "party" ? "Party" : "Team"
    }`;

    // Populate form fields
    if (this.currentDataType === "party") {
      document.getElementById("editName").value = data.nama_party || "";
    } else if (this.currentDataType === "team") {
      document.getElementById("editName").value = data.nama_team || "";
      document.getElementById("editLogo").value = data.logo_team || "";
      document.getElementById("editDescription").value =
        data.deskripsi_team || "";
    }

    // Show/hide specific fields
    this.toggleDataTypeFields(this.currentDataType, "edit");

    modal.classList.remove("hidden");
  }

  // Create edit modal
  createEditModal() {
    const modalHTML = `
      <div id="editPartyTeamModal" class="modal hidden">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Edit Data</h3>
            <button class="modal-close" data-modal-hide="editPartyTeamModal" type="button">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <form id="editPartyTeamForm">
              <div class="form-group">
                <label class="form-label">Nama</label>
                <input type="text" id="editName" name="name" class="form-input" required placeholder="Masukkan nama">
              </div>
              
              <!-- Team specific fields -->
              <div id="editLogoField" class="form-group data-specific-field" style="display:none;">
                <label class="form-label">Logo Team</label>
                <input type="text" id="editLogo" name="logo" class="form-input" placeholder="default.png">
              </div>
              
              <div id="editDescriptionField" class="form-group data-specific-field" style="display:none;">
                <label class="form-label">Deskripsi</label>
                <textarea id="editDescription" name="description" class="form-input" rows="3" placeholder="Deskripsi team"></textarea>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-hide="editPartyTeamModal">
              Batal
            </button>
            <button type="submit" form="editPartyTeamForm" class="btn btn-primary">
              <i class="fas fa-save"></i>
              Update
            </button>
          </div>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML("beforeend", modalHTML);
  }

  // Handle edit
  handleEdit() {
    const form = document.getElementById("editPartyTeamForm");
    const formData = new FormData(form);

    const data = {
      id: this.currentEditId,
    };

    if (this.currentDataType === "party") {
      data.nama_party = formData.get("name");
    } else if (this.currentDataType === "team") {
      data.nama_team = formData.get("name");
      data.logo_team = formData.get("logo") || "default.png";
      data.deskripsi_team = formData.get("description") || "";
    }

    fetch(`party_team_api.php?type=${this.currentDataType}`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(data),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          this.showAlert("Success", data.message, "success");
          this.hideModal("editPartyTeamModal");
          // Refresh the page or update the table
          location.reload();
        } else {
          this.showAlert("Error", data.error, "error");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        this.showAlert(
          "Error",
          "Terjadi kesalahan saat menyimpan data",
          "error"
        );
      });
  }

  // Delete data
  deleteData(dataType, id, name) {
    if (confirm(`Apakah Anda yakin ingin menghapus ${dataType} "${name}"?`)) {
      fetch(`party_team_api.php?type=${dataType}`, {
        method: "DELETE",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ id: id }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            this.showAlert("Success", data.message, "success");
            // Refresh the page or update the table
            location.reload();
          } else {
            this.showAlert("Error", data.error, "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          this.showAlert(
            "Error",
            "Terjadi kesalahan saat menghapus data",
            "error"
          );
        });
    }
  }

  // View detail
  viewDetail(dataType, id) {
    fetch(`party_team_api.php?type=${dataType}&id=${id}`)
      .then((response) => response.json())
      .then((result) => {
        if (result.success) {
          this.showDetailModal(dataType, result.data);
        } else {
          this.showAlert("Error", result.error, "error");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        this.showAlert("Error", "Gagal memuat detail", "error");
      });
  }

  // Show detail modal
  showDetailModal(dataType, data) {
    const typeNames = {
      party: "Party",
      team: "Team",
    };

    // Handle null created_at for teams
    const createdDate = data.created_at
      ? new Date(data.created_at).toLocaleDateString("id-ID", {
          year: "numeric",
          month: "long",
          day: "numeric",
          hour: "2-digit",
          minute: "2-digit",
        })
      : "-";

    const detailHTML =
      dataType === "party"
        ? this.createPartyDetailHTML(data, createdDate)
        : this.createTeamDetailHTML(data, createdDate);

    // Create or update detail modal
    let modal = document.getElementById("detailModal");
    if (!modal) {
      const modalHTML = `
        <div id="detailModal" class="modal hidden">
          <div class="modal-content detail-modal-content">
            <div class="modal-header detail-modal-header">
              <div class="detail-header-content">
                <div class="detail-icon">
                  <i class="fas fa-${
                    dataType === "party" ? "users" : "shield-alt"
                  }"></i>
                </div>
                <div class="detail-title">
                  <h3>Detail ${typeNames[dataType]}</h3>
                  <span class="detail-subtitle">${
                    dataType === "party" ? data.nama_party : data.nama_team
                  }</span>
                </div>
              </div>
              <button class="modal-close" data-modal-hide="detailModal" type="button">
                <i class="fas fa-times"></i>
              </button>
            </div>
            <div class="modal-body detail-modal-body">
              ${detailHTML}
            </div>
            <div class="modal-footer detail-modal-footer">
              <button type="button" class="btn btn-secondary" data-modal-hide="detailModal">
                <i class="fas fa-times"></i>
                Tutup
              </button>
              <button type="button" class="btn btn-primary" onclick="partyTeamManager.openEditModal('${dataType}', ${
        data.id_party || data.id_team
      })">
                <i class="fas fa-edit"></i>
                Edit ${typeNames[dataType]}
              </button>
            </div>
          </div>
        </div>
      `;
      document.body.insertAdjacentHTML("beforeend", modalHTML);
      modal = document.getElementById("detailModal");
    } else {
      modal.querySelector(".modal-body").innerHTML = detailHTML;
      modal.querySelector(".detail-subtitle").textContent =
        dataType === "party" ? data.nama_party : data.nama_team;
      modal.querySelector(
        ".detail-title h3"
      ).textContent = `Detail ${typeNames[dataType]}`;
      modal.querySelector(".detail-icon i").className = `fas fa-${
        dataType === "party" ? "users" : "shield-alt"
      }`;

      // Update edit button
      const editBtn = modal.querySelector(".btn-primary");
      editBtn.onclick = () =>
        this.openEditModal(dataType, data.id_party || data.id_team);
      editBtn.innerHTML = `<i class="fas fa-edit"></i> Edit ${typeNames[dataType]}`;
    }

    modal.classList.remove("hidden");
  }

  // Create Party Detail HTML
  createPartyDetailHTML(data, createdDate) {
    return `
      <div class="detail-content">
        <div class="detail-grid">
          <div class="detail-section">
            <div class="detail-card">
              <div class="detail-card-header">
                <i class="fas fa-info-circle"></i>
                <h4>Informasi Umum</h4>
              </div>
              <div class="detail-card-body">
                <div class="detail-row">
                  <span class="detail-label">ID Party:</span>
                  <span class="detail-value">#${data.id_party}</span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Nama Party:</span>
                  <span class="detail-value detail-highlight">${
                    data.nama_party
                  }</span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Tanggal Dibuat:</span>
                  <span class="detail-value">${createdDate}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="detail-section">
            <div class="detail-card">
              <div class="detail-card-header">
                <i class="fas fa-user-crown"></i>
                <h4>Kepemimpinan</h4>
              </div>
              <div class="detail-card-body">
                <div class="detail-row">
                  <span class="detail-label">Leader:</span>
                  <span class="detail-value">
                    <div class="leader-info">
                      <i class="fas fa-user"></i>
                      <span>${
                        data.leader_nickname || data.leader_username || "-"
                      }</span>
                    </div>
                  </span>
                </div>
                ${
                  data.leader_nickname && data.leader_username
                    ? `
                <div class="detail-row">
                  <span class="detail-label">Username:</span>
                  <span class="detail-value">${data.leader_username}</span>
                </div>
                `
                    : ""
                }
              </div>
            </div>
          </div>

          <div class="detail-section">
            <div class="detail-card">
              <div class="detail-card-header">
                <i class="fas fa-chart-line"></i>
                <h4>Statistik</h4>
              </div>
              <div class="detail-card-body">
                <div class="detail-row">
                  <span class="detail-label">Menang:</span>
                  <span class="detail-value">
                    <div class="stat-badge win-badge">
                      <i class="fas fa-trophy"></i>
                      ${data.win || 0}
                    </div>
                  </span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Kalah:</span>
                  <span class="detail-value">
                    <div class="stat-badge lose-badge">
                      <i class="fas fa-times-circle"></i>
                      ${data.lose || 0}
                    </div>
                  </span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Total Match:</span>
                  <span class="detail-value">
                    <div class="stat-badge total-badge">
                      <i class="fas fa-gamepad"></i>
                      ${(data.win || 0) + (data.lose || 0)}
                    </div>
                  </span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Win Rate:</span>
                  <span class="detail-value">
                    <div class="stat-badge rate-badge">
                      <i class="fas fa-percentage"></i>
                      ${
                        (data.win || 0) + (data.lose || 0) > 0
                          ? Math.round(
                              (data.win /
                                ((data.win || 0) + (data.lose || 0))) *
                                100
                            )
                          : 0
                      }%
                    </div>
                  </span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Anggota:</span>
                  <span class="detail-value">
                    <div class="member-count">
                      <span class="member-text">
                        <i class="fas fa-users"></i>
                        ${data.member_count || 0}/5
                      </span>
                      <div class="member-progress">
                        <div class="member-progress-bar" style="width: ${
                          ((data.member_count || 0) / 5) * 100
                        }%"></div>
                      </div>
                    </div>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  // Create Team Detail HTML
  createTeamDetailHTML(data, createdDate) {
    return `
      <div class="detail-content">
        <div class="detail-grid">
          <div class="detail-section">
            <div class="detail-card">
              <div class="detail-card-header">
                <i class="fas fa-info-circle"></i>
                <h4>Informasi Umum</h4>
              </div>
              <div class="detail-card-body">
                <div class="detail-row">
                  <span class="detail-label">ID Team:</span>
                  <span class="detail-value">#${data.id_team}</span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Nama Team:</span>
                  <span class="detail-value detail-highlight">${
                    data.nama_team
                  }</span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Logo Team:</span>
                  <span class="detail-value">
                    <div class="team-logo">
                      <img src="../../ASSETS/LOGO_TEAM/${
                        data.logo_team
                      }" alt="${
      data.nama_team
    }" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                      <div class="logo-fallback" style="display: none;">
                        <i class="fas fa-image"></i>
                        <span>${data.logo_team}</span>
                      </div>
                    </div>
                  </span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Dibuat:</span>
                  <span class="detail-value">${createdDate}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="detail-section">
            <div class="detail-card">
              <div class="detail-card-header">
                <i class="fas fa-user-crown"></i>
                <h4>Kepemimpinan</h4>
              </div>
              <div class="detail-card-body">
                <div class="detail-row">
                  <span class="detail-label">Leader:</span>
                  <span class="detail-value">
                    <div class="leader-info">
                      <i class="fas fa-user"></i>
                      <span>${
                        data.leader_nickname || data.leader_username || "-"
                      }</span>
                    </div>
                  </span>
                </div>
                ${
                  data.leader_nickname && data.leader_username
                    ? `
                <div class="detail-row">
                  <span class="detail-label">Username:</span>
                  <span class="detail-value">${data.leader_username}</span>
                </div>
                `
                    : ""
                }
              </div>
            </div>
          </div>

          <div class="detail-section">
            <div class="detail-card">
              <div class="detail-card-header">
                <i class="fas fa-chart-line"></i>
                <h4>Statistik</h4>
              </div>
              <div class="detail-card-body">
                <div class="detail-row">
                  <span class="detail-label">Menang:</span>
                  <span class="detail-value">
                    <div class="stat-badge win-badge">
                      <i class="fas fa-trophy"></i>
                      ${data.win || 0}
                    </div>
                  </span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Kalah:</span>
                  <span class="detail-value">
                    <div class="stat-badge lose-badge">
                      <i class="fas fa-times-circle"></i>
                      ${data.lose || 0}
                    </div>
                  </span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Total Match:</span>
                  <span class="detail-value">
                    <div class="stat-badge total-badge">
                      <i class="fas fa-gamepad"></i>
                      ${(data.win || 0) + (data.lose || 0)}
                    </div>
                  </span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Win Rate:</span>
                  <span class="detail-value">
                    <div class="stat-badge rate-badge">
                      <i class="fas fa-percentage"></i>
                      ${
                        (data.win || 0) + (data.lose || 0) > 0
                          ? Math.round(
                              (data.win /
                                ((data.win || 0) + (data.lose || 0))) *
                                100
                            )
                          : 0
                      }%
                    </div>
                  </span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Total Point:</span>
                  <span class="detail-value">
                    <div class="stat-badge point-badge">
                      <i class="fas fa-star"></i>
                      ${data.point || 0}
                    </div>
                  </span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Anggota:</span>
                  <span class="detail-value">
                    <div class="member-count">
                      <span class="member-text">
                        <i class="fas fa-users"></i>
                        ${data.member_count || 0}/5
                      </span>
                      <div class="member-progress">
                        <div class="member-progress-bar" style="width: ${
                          ((data.member_count || 0) / 5) * 100
                        }%"></div>
                      </div>
                    </div>
                  </span>
                </div>
              </div>
            </div>
          </div>

          ${
            data.deskripsi_team
              ? `
          <div class="detail-section full-width">
            <div class="detail-card">
              <div class="detail-card-header">
                <i class="fas fa-file-text"></i>
                <h4>Deskripsi Team</h4>
              </div>
              <div class="detail-card-body">
                <div class="team-description">
                  ${data.deskripsi_team}
                </div>
              </div>
            </div>
          </div>
          `
              : ""
          }
        </div>
      </div>
    `;
  }

  // Hide modal
  hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add("hidden");
    }
  }

  // Show alert
  showAlert(title, message, type) {
    alert(`${title}: ${message}`);
  }
}

// Global functions for onclick handlers
function viewAllParties() {
  window.partyTeamManager.viewAllData("party");
}

function viewAllTeams() {
  window.partyTeamManager.viewAllData("team");
}

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  window.partyTeamManager = new PartyTeamManagement();
});

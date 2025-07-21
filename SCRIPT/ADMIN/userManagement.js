// User Management JavaScript

// Global variables
let currentUserType = "";
let currentUserId = 0;
let editingUser = false;

// Initialize user management
function initializeUserManagement() {
  setupModalEventListeners();
  refreshAllUserTables();
}

// Setup modal event listeners
function setupModalEventListeners() {
  // Add User Modal
  const addUserModal = document.getElementById("addUserModal");
  const addUserForm = document.getElementById("addUserForm");

  if (addUserForm) {
    addUserForm.addEventListener("submit", handleAddUser);
  }

  // Edit User Modal
  const editUserModal = document.getElementById("editUserModal");
  const editUserForm = document.getElementById("editUserForm");

  if (editUserForm) {
    editUserForm.addEventListener("submit", handleEditUser);
  }

  // Modal close events
  document.querySelectorAll("[data-modal-hide]").forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      const modalId = this.getAttribute("data-modal-hide");
      if (modalId) {
        closeModal(modalId);
      } else {
        closeModal();
      }
    });
  });

  // Close button events
  document.querySelectorAll(".modal-close").forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      const modal = this.closest(".modal");
      if (modal) {
        closeModal(modal.id);
      } else {
        closeModal();
      }
    });
  });

  // Click outside to close modal
  document.addEventListener("click", function (e) {
    if (
      e.target.classList.contains("modal") &&
      !e.target.classList.contains("hidden")
    ) {
      closeModal(e.target.id);
    }
  });

  // Escape key to close modal
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      closeModal();
    }
  });

  // User type select change
  const userTypeSelect = document.getElementById("userType");
  if (userTypeSelect) {
    userTypeSelect.addEventListener("change", function () {
      currentUserType = this.value;
      toggleUserFields("add", this.value);
    });
  }

  const editUserTypeSelect = document.getElementById("editUserType");
  if (editUserTypeSelect) {
    editUserTypeSelect.addEventListener("change", function () {
      toggleUserFields("edit", this.value);
    });
  }
}

// Toggle user-specific fields based on type
function toggleUserFields(mode, userType) {
  const prefix = mode === "edit" ? "edit" : "";

  // Hide all optional fields first
  // For add mode, use IDs without prefix (organisasiField, nicknameField, idGameField)
  // For edit mode, use IDs with prefix (editOrganisasiField, editNicknameField, editIdGameField)
  const organisasiField = document.getElementById(
    mode === "edit" ? "editOrganisasiField" : "organisasiField"
  );
  const nicknameField = document.getElementById(
    mode === "edit" ? "editNicknameField" : "nicknameField"
  );
  const idGameField = document.getElementById(
    mode === "edit" ? "editIdGameField" : "idGameField"
  );

  if (organisasiField) {
    organisasiField.style.display = "none";
    organisasiField.classList.remove("show");
  }
  if (nicknameField) {
    nicknameField.style.display = "none";
    nicknameField.classList.remove("show");
  }
  if (idGameField) {
    idGameField.style.display = "none";
    idGameField.classList.remove("show");
  }

  // Update role description for add modal
  if (mode === "add") {
    updateRoleDescription(userType);
  }

  // Show relevant fields with animation
  switch (userType) {
    case "eo":
      if (organisasiField) {
        organisasiField.style.display = "block";
        setTimeout(() => organisasiField.classList.add("show"), 10);
      }
      break;
    case "player":
      if (nicknameField) {
        nicknameField.style.display = "block";
        setTimeout(() => nicknameField.classList.add("show"), 10);
      }
      if (idGameField) {
        idGameField.style.display = "block";
        setTimeout(() => idGameField.classList.add("show"), 20);
      }
      break;
    case "admin":
    default:
      // No additional fields for admin
      break;
  }
}

// Update role description
function updateRoleDescription(userType) {
  const roleDescription = document.getElementById("roleDescription");
  if (!roleDescription) return;

  const descriptions = {
    admin:
      "Administrator memiliki akses penuh untuk mengelola seluruh sistem BrackIt",
    eo: "Event Organizer dapat membuat dan mengelola turnamen serta kompetisi",
    player: "Player dapat mendaftar turnamen dan bergabung dengan tim",
    "": "Pilih role untuk menentukan jenis pengguna yang akan ditambahkan",
  };

  roleDescription.textContent = descriptions[userType] || descriptions[""];
}

// Open add user modal (without predefined userType)
function openAddUserModal(userType = null) {
  editingUser = false;

  // Reset form
  document.getElementById("addUserForm").reset();

  // Set user type if provided, otherwise reset to default
  const userTypeSelect = document.getElementById("userType");
  if (userTypeSelect) {
    if (userType) {
      userTypeSelect.value = userType;
      currentUserType = userType;
      userTypeSelect.disabled = false; // Allow changing in unified modal
    } else {
      userTypeSelect.value = "";
      currentUserType = "";
      userTypeSelect.disabled = false;
    }
    toggleUserFields("add", userTypeSelect.value);
  }

  // Reset modal title
  const modalTitle = document.querySelector("#addUserModal h3");
  if (modalTitle) {
    modalTitle.textContent = "Tambah Pengguna Baru";
  }

  // Show modal
  document.getElementById("addUserModal").classList.remove("hidden");
}

// Open edit user modal
function openEditUserModal(userType, userId) {
  currentUserType = userType;
  currentUserId = userId;
  editingUser = true;

  // Set user type in modal
  const userTypeSelect = document.getElementById("editUserType");
  if (userTypeSelect) {
    userTypeSelect.value = userType;
    userTypeSelect.disabled = true;
    toggleUserFields("edit", userType);
  }

  // Update modal title
  const modalTitle = document.querySelector("#editUserModal h3");
  if (modalTitle) {
    modalTitle.textContent = `Edit ${userType.toUpperCase()}`;
  }

  // Load user data
  loadUserData(userType, userId);

  // Show modal
  document.getElementById("editUserModal").classList.remove("hidden");
}

// Open edit user modal from view all modal (with higher z-index)
function openEditUserModalFromViewAll(userType, userId) {
  currentUserType = userType;
  currentUserId = userId;
  editingUser = true;

  // Create or get edit modal
  let editModal = document.getElementById("editUserModal");
  if (!editModal) {
    return;
  }

  // Set higher z-index to appear above view all modal
  editModal.style.zIndex = "10001";

  // Dim the all users modal
  const allUsersModal = document.getElementById("allUsersModal");
  if (allUsersModal) {
    allUsersModal.classList.add("dimmed");
  }

  // Set user type in modal
  const userTypeSelect = document.getElementById("editUserType");
  if (userTypeSelect) {
    userTypeSelect.value = userType;
    userTypeSelect.disabled = true;
    toggleUserFields("edit", userType);
  }

  // Update modal title with type indicator
  const modalTitle = document.querySelector("#editUserModal h3");
  if (modalTitle) {
    const typeNames = {
      admin: "Administrator",
      eo: "Event Organizer",
      player: "Player",
    };
    modalTitle.textContent = `Edit ${typeNames[userType]}`;
  }

  // Load user data
  loadUserData(userType, userId);

  // Show modal
  editModal.classList.remove("hidden");

  // Override close button behavior for layered modal
  const closeButtons = editModal.querySelectorAll(
    '[data-modal-hide="editUserModal"], .modal-close'
  );
  closeButtons.forEach((button) => {
    button.onclick = () => {
      closeModal("editUserModal");
    };
  });

  // Handle backdrop click for layered modal
  editModal.onclick = (e) => {
    if (e.target === editModal) {
      closeModal("editUserModal");
    }
  };
}

// Load user data for editing
function loadUserData(userType, userId) {
  fetch(`user_api.php?type=${userType}&id=${userId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const user = data.user;

        // Fill form fields
        document.getElementById("editUsername").value = user.username || "";
        document.getElementById("editEmail").value = user.email || "";
        document.getElementById("editStatus").value = user.status || "active";

        // Type-specific fields
        if (userType === "eo" && user.organisasi) {
          document.getElementById("editOrganisasi").value = user.organisasi;
        }
        if (userType === "player") {
          document.getElementById("editNickname").value = user.nickname || "";
          document.getElementById("editIdGame").value = user.idGame || "";
        }
      } else {
        showAlert("Error", data.error, "error");
      }
    })
    .catch((error) => {
      console.error("Error loading user data:", error);
      showAlert("Error", "Gagal memuat data user", "error");
    });
}

// Handle add user form submission
function handleAddUser(e) {
  e.preventDefault();

  // Get user type from form
  const userTypeFromForm = document.getElementById("userType").value;
  if (!userTypeFromForm) {
    showAlert("Error", "Pilih role pengguna terlebih dahulu", "error");
    return;
  }

  // Capture the user type to avoid it being reset
  const userType = userTypeFromForm;
  currentUserType = userType;

  const formData = new FormData(e.target);
  const userData = {
    username: formData.get("username"),
    email: formData.get("email"),
    password: formData.get("password"),
    confirmPassword: formData.get("confirmPassword"),
    status: formData.get("status"),
  };

  // Add type-specific fields
  if (userType === "eo") {
    userData.organisasi = formData.get("organisasi");
  } else if (userType === "player") {
    userData.nickname = formData.get("nickname");
    userData.idGame = formData.get("idGame");
  }

  // Validate passwords
  if (userData.password !== userData.confirmPassword) {
    showAlert("Error", "Password tidak cocok", "error");
    return;
  }

  // Send request
  fetch(`user_api.php?type=${userType}`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(userData),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert("Success", data.message, "success");
        closeModal();
        refreshUserTable(userType);
        // Refresh the main dashboard page to show new user
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      } else {
        showAlert("Error", data.error, "error");
      }
    })
    .catch((error) => {
      console.error("Error adding user:", error);
      showAlert("Error", "Gagal menambah user", "error");
    });
}

// Handle edit user form submission
function handleEditUser(e) {
  e.preventDefault();

  // Capture the current user type immediately to avoid it being reset
  const userType = currentUserType;
  const userId = currentUserId;

  if (!userType) {
    showAlert("Error", "Tipe user tidak valid", "error");
    return;
  }

  const formData = new FormData(e.target);
  const userData = {
    id: userId,
    username: formData.get("username"),
    email: formData.get("email"),
    status: formData.get("status"),
  };

  // Add password if provided
  const password = formData.get("password");
  if (password) {
    userData.password = password;
  }

  // Add type-specific fields
  if (userType === "eo") {
    userData.organisasi = formData.get("organisasi");
  } else if (userType === "player") {
    userData.nickname = formData.get("nickname");
    userData.idGame = formData.get("idGame");
  }

  // Send request
  fetch(`user_api.php?type=${userType}`, {
    method: "PUT",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(userData),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert("Success", data.message, "success");
        closeModal();
        refreshUserTable(userType);
        // Refresh the main dashboard page to show updated status
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      } else {
        showAlert("Error", data.error, "error");
      }
    })
    .catch((error) => {
      console.error("Error updating user:", error);
      showAlert("Error", "Gagal mengupdate user", "error");
    });
}

// Delete user
function deleteUser(userType, userId, username) {
  if (confirm(`Apakah Anda yakin ingin menghapus user "${username}"?`)) {
    fetch(`user_api.php?type=${userType}&id=${userId}`, {
      method: "DELETE",
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showAlert("Success", data.message, "success");
          refreshUserTable(userType);
        } else {
          showAlert("Error", data.error, "error");
        }
      })
      .catch((error) => {
        console.error("Error deleting user:", error);
        showAlert("Error", "Gagal menghapus user", "error");
      });
  }
}

// Delete user from view all modal
function deleteUserFromViewAll(userType, userId, username) {
  if (confirm(`Apakah Anda yakin ingin menghapus user "${username}"?`)) {
    fetch(`user_api.php?type=${userType}&id=${userId}`, {
      method: "DELETE",
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showAlert("Success", data.message, "success");

          // Refresh main dashboard table
          refreshUserTable(userType);

          // Refresh the view all modal by reloading data
          setTimeout(() => {
            viewAllUsers(userType);
          }, 500);
        } else {
          showAlert("Error", data.error, "error");
        }
      })
      .catch((error) => {
        console.error("Error deleting user:", error);
        showAlert("Error", "Gagal menghapus user", "error");
      });
  }
}

// Refresh all user tables
function refreshAllUserTables() {
  refreshUserTable("admin");
  refreshUserTable("eo");
  refreshUserTable("player");
}

// Refresh specific user table
function refreshUserTable(userType) {
  if (!userType) {
    console.error("refreshUserTable called with empty userType");
    return;
  }

  const limit = userType === "admin" ? 50 : 10;

  fetch(`user_api.php?type=${userType}&limit=${limit}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        updateUserTable(userType, data.users);
      } else {
        console.error("Error fetching users:", data.error);
      }
    })
    .catch((error) => {
      console.error("Error fetching users:", error);
    });
}

// Update user table HTML
function updateUserTable(userType, users) {
  const tableBody = document.getElementById(`${userType}TableBody`);
  if (!tableBody) return;

  tableBody.innerHTML = "";

  users.forEach((user, index) => {
    const row = document.createElement("tr");
    row.className = "border-b border-gray-200 hover:bg-gray-50";

    let typeSpecificColumns = "";
    if (userType === "eo") {
      typeSpecificColumns = `<td class="px-6 py-4 text-sm text-gray-900">${
        user.organisasi || "-"
      }</td>
                                  <td class="px-6 py-4 text-sm text-gray-900">Rp ${formatNumber(
                                    user.pendapatan || 0
                                  )}</td>`;
    } else if (userType === "player") {
      typeSpecificColumns = `<td class="px-6 py-4 text-sm text-gray-900">${
        user.nickname || "-"
      }</td>
                                  <td class="px-6 py-4 text-sm text-gray-900">${
                                    user.idGame || "-"
                                  }</td>`;
    }

    row.innerHTML = `
            <td class="px-6 py-4 text-sm font-medium text-gray-900">${
              index + 1
            }</td>
            <td class="px-6 py-4 text-sm text-gray-900">${user.username}</td>
            <td class="px-6 py-4 text-sm text-gray-900">${user.email}</td>
            ${typeSpecificColumns}
            <td class="px-6 py-4">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                  user.status === "active"
                    ? "bg-green-100 text-green-800"
                    : "bg-red-100 text-red-800"
                }">
                    ${user.status === "active" ? "Aktif" : "Tidak Aktif"}
                </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-900">${formatDateTime(
              user.created_at
            )}</td>
            <td class="px-6 py-4 text-sm text-gray-900">${
              user.last_login ? formatDateTime(user.last_login) : "-"
            }</td>
            <td class="px-6 py-4 text-sm font-medium space-x-2">
                <button onclick="openEditUserModal('${userType}', ${user.id})" 
                        class="text-blue-600 hover:text-blue-900">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteUser('${userType}', ${user.id}, '${
      user.username
    }')" 
                        class="text-red-600 hover:text-red-900 ${
                          userType === "admin" && user.id === 1
                            ? "opacity-50 cursor-not-allowed"
                            : ""
                        }"
                        ${
                          userType === "admin" && user.id === 1
                            ? "disabled"
                            : ""
                        }>
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;

    tableBody.appendChild(row);
  });
}

// Close modal
function closeModal(modalId = null) {
  if (modalId) {
    // Close specific modal
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add("hidden");

      // Reset z-index if it was layered
      if (modalId === "editUserModal") {
        modal.style.zIndex = "9999";
        // Remove dimmed class from all users modal
        const allUsersModal = document.getElementById("allUsersModal");
        if (allUsersModal) {
          allUsersModal.classList.remove("dimmed");
        }
      }
    }
  } else {
    // Close all modals
    const addModal = document.getElementById("addUserModal");
    const editModal = document.getElementById("editUserModal");
    const allUsersModal = document.getElementById("allUsersModal");

    if (addModal) {
      addModal.classList.add("hidden");
    }
    if (editModal) {
      editModal.classList.add("hidden");
      editModal.style.zIndex = "9999";
    }
    if (allUsersModal) {
      allUsersModal.classList.add("hidden");
      allUsersModal.classList.remove("dimmed");
    }
  }

  // Reset forms if closing add or edit modal
  if (!modalId || modalId === "addUserModal" || modalId === "editUserModal") {
    const addForm = document.getElementById("addUserForm");
    const editForm = document.getElementById("editUserForm");

    if (addForm) {
      addForm.reset();
    }
    if (editForm) {
      editForm.reset();
    }

    // Reset variables
    currentUserType = "";
    currentUserId = 0;
    editingUser = false;

    // Hide all role-specific fields
    toggleUserFields("add", "");
    toggleUserFields("edit", "");
  }
}

// Show alert
function showAlert(title, message, type) {
  // Simple alert for now - you can replace with a custom modal
  alert(`${title}: ${message}`);
}

// Format number with thousand separators
function formatNumber(num) {
  return new Intl.NumberFormat("id-ID").format(num);
}

// Format date time
function formatDateTime(dateString) {
  if (!dateString) return "-";

  const date = new Date(dateString);
  return date.toLocaleDateString("id-ID", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

// View all users function
function viewAllUsers(userType) {
  currentUserType = userType;

  // Show loading
  showAlert("Loading", `Memuat semua data ${userType}...`, "info");

  // Fetch all users data
  fetch(`user_api.php?action=getAll&type=${userType}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.text(); // Get as text first to debug
    })
    .then((text) => {
      try {
        const data = JSON.parse(text);

        if (data.success) {
          openAllUsersModal(userType, data.users);
        } else {
          console.error(`API error:`, data.error);
          showAlert(
            "Error",
            data.error || data.message || "Gagal memuat data users",
            "error"
          );
        }
      } catch (parseError) {
        console.error(`JSON parse error:`, parseError);
        console.error(`Response text:`, text);
        showAlert("Error", "Invalid response format from server", "error");
      }
    })
    .catch((error) => {
      console.error("Fetch error:", error);
      showAlert(
        "Error",
        "Terjadi kesalahan saat memuat data: " + error.message,
        "error"
      );
    });
}

// Open modal to show all users
function openAllUsersModal(userType, users) {
  // Create modal if it doesn't exist
  let modal = document.getElementById("allUsersModal");
  if (!modal) {
    createAllUsersModal();
    modal = document.getElementById("allUsersModal");
  }

  // Update modal content
  const modalTitle = modal.querySelector(".modal-header h3");
  const modalBody = modal.querySelector(".modal-body");

  // Set title
  const typeNames = {
    admin: "Administrator",
    eo: "Event Organizer",
    player: "Player",
  };
  modalTitle.textContent = `Semua ${typeNames[userType]} (${users.length})`;

  // Create table
  modalBody.innerHTML = createUsersTable(userType, users);

  // Show modal
  modal.classList.remove("hidden");
}

// Create all users modal
function createAllUsersModal() {
  const modalHTML = `
    <div id="allUsersModal" class="modal hidden">
      <div class="modal-content" style="max-width: 90vw; width: 1200px;">
        <div class="modal-header">
          <h3>Semua Users</h3>
          <button class="modal-close" data-modal-hide="allUsersModal" type="button">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <!-- Content will be populated by JavaScript -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-modal-hide="allUsersModal">
            Tutup
          </button>
        </div>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML("beforeend", modalHTML);

  // Add close event listener
  const closeBtn = document.querySelector("#allUsersModal [data-modal-hide]");
  closeBtn.addEventListener("click", () => {
    document.getElementById("allUsersModal").classList.add("hidden");
  });

  // Add close on background click
  document.getElementById("allUsersModal").addEventListener("click", (e) => {
    if (e.target.id === "allUsersModal") {
      document.getElementById("allUsersModal").classList.add("hidden");
    }
  });
}

// Create users table HTML
function createUsersTable(userType, users) {
  if (users.length === 0) {
    return `<div class="empty-state">
      <i class="fas fa-users" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
      <p>Belum ada ${userType} yang terdaftar.</p>
    </div>`;
  }

  // Define columns based on user type
  let columns = ["ID", "Username", "Email"];
  if (userType === "eo") {
    columns.push("Organisasi");
  } else if (userType === "player") {
    columns.push("Nickname", "ID Game");
  }
  columns.push("Status", "Dibuat", "Login Terakhir", "Aksi");

  let tableHTML = `
    <div class="all-users-table">
      <div class="table-controls">
        <input type="text" id="userSearchInput" placeholder="Cari ${userType}..." class="search-input">
        <select id="statusFilter" class="filter-select">
          <option value="">Semua Status</option>
          <option value="active">Aktif</option>
          <option value="inactive">Tidak Aktif</option>
        </select>
      </div>
      <table>
        <thead>
          <tr>
            ${columns.map((col) => `<th>${col}</th>`).join("")}
          </tr>
        </thead>
        <tbody id="allUsersTableBody">
  `;

  users.forEach((user) => {
    tableHTML += `<tr>
      <td>${user.id}</td>
      <td>
        <div class="user-info">
          <i class="fas fa-${
            userType === "admin"
              ? "user-shield"
              : userType === "eo"
              ? "user-tie"
              : "gamepad"
          } user-icon"></i>
          <span>${user.username || "-"}</span>
        </div>
      </td>
      <td>${user.email || "-"}</td>`;

    if (userType === "eo") {
      tableHTML += `<td>${user.organisasi || "-"}</td>`;
    } else if (userType === "player") {
      tableHTML += `<td>${user.nickname || "-"}</td><td>${
        user.idGame || "-"
      }</td>`;
    }

    tableHTML += `
      <td><span class="status ${user.status}">${
      user.status === "active" ? "Aktif" : "Tidak Aktif"
    }</span></td>
      <td>${formatDateTime(user.created_at)}</td>
      <td>${formatDateTime(user.last_login)}</td>
      <td>
        <div class="action-buttons">
          <button class="btn-edit" title="Edit" onclick="openEditUserModalFromViewAll('${userType}', ${
      user.id
    })">
            <i class="fas fa-edit"></i>
          </button>`;

    // Don't allow deleting main admin
    if (!(userType === "admin" && user.id == 1)) {
      tableHTML += `
          <button class="btn-delete" title="Hapus" onclick="deleteUserFromViewAll('${userType}', ${user.id}, '${user.username}')">
            <i class="fas fa-trash"></i>
          </button>`;
    }

    tableHTML += `
        </div>
      </td>
    </tr>`;
  });

  tableHTML += `
        </tbody>
      </table>
    </div>
  `;

  // Add search and filter functionality after table is created
  setTimeout(() => {
    setupTableFilters();
  }, 100);

  return tableHTML;
}

// Setup table filters
function setupTableFilters() {
  const searchInput = document.getElementById("userSearchInput");
  const statusFilter = document.getElementById("statusFilter");
  const tableBody = document.getElementById("allUsersTableBody");

  if (!searchInput || !statusFilter || !tableBody) return;

  function filterTable() {
    const searchTerm = searchInput.value.toLowerCase();
    const statusFilter = document.getElementById("statusFilter").value;
    const rows = tableBody.querySelectorAll("tr");

    rows.forEach((row) => {
      const text = row.textContent.toLowerCase();
      const statusCell = row.querySelector(".status");
      const status = statusCell ? statusCell.textContent.toLowerCase() : "";

      const matchesSearch = text.includes(searchTerm);
      const matchesStatus = !statusFilter || status.includes(statusFilter);

      row.style.display = matchesSearch && matchesStatus ? "" : "none";
    });
  }

  searchInput.addEventListener("input", filterTable);
  statusFilter.addEventListener("change", filterTable);
}

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  initializeUserManagement();
});

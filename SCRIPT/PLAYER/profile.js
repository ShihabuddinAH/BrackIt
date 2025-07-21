// Profile JavaScript functionality for BrackIt
document.addEventListener("DOMContentLoaded", function () {
  // Get DOM elements
  const editBtn = document.getElementById("editBtn");
  const saveBtn = document.getElementById("saveBtn");
  const profileForm = document.getElementById("profileForm");
  const mobileMenuToggle = document.getElementById("mobileMenuToggle");
  const sidebar = document.getElementById("sidebar");
  const navItems = document.querySelectorAll(".nav-item");

  // Form inputs that can be edited
  const editableInputs = [
    document.getElementById("nickname"),
    document.getElementById("idGame"),
  ];

  let isEditing = false;

  // Edit button functionality
  if (editBtn) {
    editBtn.addEventListener("click", function () {
      toggleEditMode();
    });
  }

  // Toggle edit mode
  function toggleEditMode() {
    isEditing = !isEditing;

    editableInputs.forEach((input) => {
      if (input) {
        input.disabled = !isEditing;
        if (isEditing) {
          input.classList.add("editing");
        } else {
          input.classList.remove("editing");
        }
      }
    });

    if (isEditing) {
      editBtn.textContent = "Cancel";
      editBtn.classList.add("cancel-btn");
      saveBtn.style.display = "block";
    } else {
      editBtn.textContent = "Edit Profile";
      editBtn.classList.remove("cancel-btn");
      saveBtn.style.display = "none";

      // Reset form values to original
      editableInputs.forEach((input) => {
        if (input) {
          input.value = input.defaultValue;
        }
      });
    }
  }

  // Form submission
  if (profileForm) {
    profileForm.addEventListener("submit", function (e) {
      // Validate nickname and idGame
      const nickname = document.getElementById("nickname");
      const idGame = document.getElementById("idGame");

      if (nickname && !nickname.value.trim()) {
        e.preventDefault();
        showAlert("Nickname is required", "error");
        return;
      }

      if (idGame && !idGame.value.trim()) {
        e.preventDefault();
        showAlert("Game ID is required", "error");
        return;
      }
    });
  }

  // Mobile menu toggle
  if (mobileMenuToggle && sidebar) {
    mobileMenuToggle.addEventListener("click", function () {
      sidebar.classList.toggle("active");
    });
  }

  // Close mobile menu when clicking outside
  document.addEventListener("click", function (e) {
    if (
      sidebar &&
      !sidebar.contains(e.target) &&
      !mobileMenuToggle.contains(e.target)
    ) {
      sidebar.classList.remove("active");
    }
  });

  // Navigation items click handlers
  navItems.forEach((item, index) => {
    item.addEventListener("click", function () {
      // Remove active class from all items
      navItems.forEach((navItem) => navItem.classList.remove("active"));
      // Add active class to clicked item
      this.classList.add("active");

      // Navigate based on index
      switch (index) {
        case 0: // Home
          window.location.href = "../../index.php";
          break;
        case 1: // Tournament
          window.location.href = "menuTournament.php";
          break;
        case 2: // Teams
          window.location.href = "menuTeams.php";
          break;
        case 3: // Profile (current page)
          // Already on profile page
          break;
        case 4: // Party
          // TODO: Add party management page
          // showAlert("Party management page coming soon!", "info");
          break;
        case 5: // Team Management
          // TODO: Add team management page
          showAlert("Settings management page coming soon!", "info");
          break;
      }
    });
  });

  // Form validation for party creation
  const createPartyForms = document.querySelectorAll(".create-party-form");
  createPartyForms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      const partyNameInput = form.querySelector('input[name="party_name"]');
      if (partyNameInput && !partyNameInput.value.trim()) {
        e.preventDefault();
        showAlert("Party name is required", "error");
      }
    });
  });

  // Form validation for team creation
  const createTeamForms = document.querySelectorAll(".create-team-form");
  createTeamForms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      const teamNameInput = form.querySelector('input[name="team_name"]');
      if (teamNameInput && !teamNameInput.value.trim()) {
        e.preventDefault();
        showAlert("Team name is required", "error");
      } else if (teamNameInput && teamNameInput.value.trim().length < 3) {
        e.preventDefault();
        showAlert("Team name must be at least 3 characters long", "error");
      }
    });
  });

  // Form validation for member invitation
  const inviteForms = document.querySelectorAll(".invite-form");
  inviteForms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      const usernameInput = form.querySelector('input[name="invite_username"]');
      if (usernameInput && !usernameInput.value.trim()) {
        e.preventDefault();
        showAlert("Username is required", "error");
      }
    });
  });

  // Auto-hide alerts after 5 seconds
  const alerts = document.querySelectorAll(".alert");
  alerts.forEach((alert) => {
    setTimeout(() => {
      alert.style.opacity = "0";
      setTimeout(() => {
        alert.remove();
      }, 300);
    }, 5000);
  });

  // Search functionality
  const searchInput = document.querySelector(".search-input");
  if (searchInput) {
    searchInput.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        const searchTerm = this.value.trim();
        if (searchTerm) {
          // TODO: Implement search functionality
          showAlert(
            `Search functionality for "${searchTerm}" coming soon!`,
            "info"
        }
      }
    });
  }

  // Show alert function
  function showAlert(message, type = "info") {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll(".alert");
    existingAlerts.forEach((alert) => alert.remove());

    // Create new alert
    const alert = document.createElement("div");
    alert.className = `alert alert-${type}`;

    const icon = document.createElement("i");
    switch (type) {
      case "success":
        icon.className = "fas fa-check-circle";
        break;
      case "error":
        icon.className = "fas fa-exclamation-circle";
        break;
      case "info":
      default:
        icon.className = "fas fa-info-circle";
        break;
    }

    alert.appendChild(icon);
    alert.appendChild(document.createTextNode(" " + message));

    // Insert alert at the top of content area
    const contentArea = document.querySelector(".content-area");
    const gradientBanner = document.querySelector(".gradient-banner");

    if (contentArea && gradientBanner) {
      contentArea.insertBefore(alert, gradientBanner.nextSibling);
    }

    // Auto-hide after 5 seconds
    setTimeout(() => {
      alert.style.opacity = "0";
      setTimeout(() => {
        alert.remove();
      }, 300);
    }, 5000);
  }

});

// Toggle kick member form
function toggleKickForm() {
  const kickForm = document.getElementById("kickMemberForm");
  if (kickForm) {
    if (kickForm.style.display === "none" || kickForm.style.display === "") {
      kickForm.style.display = "block";
    } else {
      kickForm.style.display = "none";
    }
  }
}

// State management
let isEditing = false;
let isLoading = false;

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  initializeEventListeners();
  updateCurrentDate();
  loadUserData();
});

// Initialize all event listeners
function initializeEventListeners() {
  // Mobile Menu Toggle
  if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener("click", () => {
      sidebar.classList.toggle("active");
    });
  }

  // Close sidebar when clicking outside on mobile
  document.addEventListener("click", (e) => {
    if (window.innerWidth <= 768) {
      if (!sidebar.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
        sidebar.classList.remove("active");
      }
    }
  });

  // Navigation Items
  if (navItems.length > 0) {
    navItems.forEach((item, index) => {
      item.addEventListener("click", () => {
        // Remove active class from all items
        navItems.forEach((nav) => nav.classList.remove("active"));
        // Add active class to clicked item
        item.classList.add("active");

        // Close mobile menu after selection
        if (window.innerWidth <= 768) {
          sidebar.classList.remove("active");
        }

      });
    });
  }

  // Search Functionality
  if (searchInput) {
    searchInput.addEventListener("input", (e) => {
      const searchTerm = e.target.value.toLowerCase();

      // Add search animation
      if (searchTerm.length > 0) {
        searchInput.style.borderColor = "#ef4444";
      } else {
        searchInput.style.borderColor = "#444";
      }
    });

    // Search on Enter key
    searchInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter") {
        const searchTerm = e.target.value;
        if (searchTerm.trim()) {
          performSearch(searchTerm);
        }
      }
    });
  }

  // Notification Click
  if (notificationIcon) {
    notificationIcon.addEventListener("click", () => {
      const badge = notificationIcon.querySelector(".notification-badge");
      if (badge) {
        badge.style.animation = "pulse 0.5s ease-in-out";
        setTimeout(() => {
          badge.style.animation = "";
        }, 500);
      }
      showNotification("You have 3 new notifications");
    });
  }

  // Profile edit functionality
  if (editBtn) {
    editBtn.addEventListener("click", handleEditToggle);
  }

  // Save profile form
  if (document.getElementById("profileForm")) {
    document
      .getElementById("profileForm")
      .addEventListener("submit", handleProfileSave);
  }

  // Party functionality
  if (partyBtn) {
    partyBtn.addEventListener("click", handlePartyButtonClick);
  }

  if (closeModal) {
    closeModal.addEventListener("click", closePartyModal);
  }

  if (partyForm) {
    partyForm.addEventListener("submit", handlePartyFormSubmit);
  }

  // Username validation for party members
  if (memberInputs.length > 0) {
    memberInputs.forEach((input, index) => {
      let timeout;
      input.addEventListener("input", (e) => {
        clearTimeout(timeout);
        const username = e.target.value.trim();
        const messageDiv = document.getElementById(
          `member${index + 1}-message`

        if (username === "") {
          resetInputValidation(input, messageDiv);
          return;
        }

        timeout = setTimeout(() => {
          validateUsername(username, input, messageDiv);
        }, 500);
      });
    });
  }

  // Email functionality
  if (addEmailBtn) {
    addEmailBtn.addEventListener("click", showAddEmailForm);
  }

  if (addEmailForm) {
    addEmailForm.addEventListener("submit", handleAddEmail);
  }

  // Close modal when clicking outside
  window.addEventListener("click", (e) => {
    if (e.target === partyModal) {
      closePartyModal();
    }
  });

  // Keyboard shortcuts
  document.addEventListener("keydown", handleKeyboardShortcuts);
}

// Profile edit functionality
function handleEditToggle() {
  if (isLoading) return;

  if (!isEditing) {
    enableEditMode();
  } else {
    cancelEditMode();
  }
}

function enableEditMode() {
  isEditing = true;
  editBtn.textContent = "Cancel";
  editBtn.style.backgroundColor = "#6b7280";

  if (saveBtn) {
    saveBtn.style.display = "block";
  }

  formInputs.forEach((input) => {
    if (!input.hasAttribute("readonly")) {
      input.disabled = false;
      input.style.backgroundColor = "#374151";
    }
  });

  showNotification("Edit mode enabled", "info");
}

function cancelEditMode() {
  isEditing = false;
  editBtn.textContent = "Edit";
  editBtn.style.backgroundColor = "#ef4444";

  if (saveBtn) {
    saveBtn.style.display = "none";
  }

  formInputs.forEach((input) => {
    if (!input.hasAttribute("readonly")) {
      input.disabled = true;
      input.style.backgroundColor = "#333";
    }
  });

  // Reset form values
  resetFormValues();
  showNotification("Edit mode cancelled", "info");
}

function handleProfileSave(e) {
  e.preventDefault();
  if (isLoading) return;

  const formData = {
    full_name: document.getElementById("fullName")?.value.trim() || "",
    nick_name: document.getElementById("nickName")?.value.trim() || "",
    gender: document.getElementById("gender")?.value || "",
    team: document.getElementById("team")?.value.trim() || "",
  };

  // Validate form data
  if (!validateProfileForm(formData)) {
    return;
  }

  saveProfile(formData);
}

function validateProfileForm(data) {
  let isValid = true;

  // Validate full name
  if (!data.full_name) {
    showInputError(
      document.getElementById("fullName"),
      "Full name is required"
    isValid = false;
  }

  // Validate nick name
  if (!data.nick_name) {
    showInputError(
      document.getElementById("nickName"),
      "Nick name is required"
    isValid = false;
  }

  return isValid;
}

function saveProfile(data) {
  setLoading(true);

  // Use API call
  fetch("api/user.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      action: "update_profile",
      ...data,
    }),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.success) {
        // Update current user data
        Object.assign(currentUser, data);

        // Update display
        updateProfileDisplay();

        // Exit edit mode
        cancelEditMode();

        showNotification("Profile updated successfully!", "success");
      } else {
        showNotification(result.error || "Failed to update profile", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification("An error occurred while saving", "error");
    })
    .finally(() => {
      setLoading(false);
    });
}

function updateProfileDisplay() {
  const profileName = document.querySelector(".profile-name");
  if (profileName && currentUser.full_name) {
    profileName.textContent = currentUser.full_name;
  }
}

function resetFormValues() {
  if (document.getElementById("fullName")) {
    document.getElementById("fullName").value = currentUser.full_name || "";
  }
  if (document.getElementById("nickName")) {
    document.getElementById("nickName").value = currentUser.nick_name || "";
  }
  if (document.getElementById("gender")) {
    document.getElementById("gender").value = currentUser.gender || "";
  }
  if (document.getElementById("team")) {
    document.getElementById("team").value = currentUser.team || "";
  }
}

// Party functionality
function handlePartyButtonClick() {
  if (isLoading) return;

  if (currentUser.has_party) {
    showPartyInfo();
  } else {
    openPartyModal();
  }
}

function openPartyModal() {
  if (partyModal) {
    partyModal.style.display = "block";
    document.body.style.overflow = "hidden";
  }
}

function closePartyModal() {
  if (partyModal) {
    partyModal.style.display = "none";
    document.body.style.overflow = "auto";
    resetPartyForm();
  }
}

function handlePartyFormSubmit(e) {
  e.preventDefault();
  if (isLoading) return;

  const formData = new FormData(partyForm);
  const partyData = {
    party_name: formData.get("party_name")?.trim() || "",
    members: [],
  };

  // Collect valid members
  for (let i = 1; i <= 4; i++) {
    const memberUsername = formData.get(`member${i}`)?.trim();
    if (memberUsername) {
      const user = sampleUsers.find(
        (u) => u.username.toLowerCase() === memberUsername.toLowerCase()
      if (user) {
        partyData.members.push(memberUsername);
      }
    }
  }

  // Validate party data
  if (!validatePartyForm(partyData)) {
    return;
  }

  createParty(partyData);
}

function validatePartyForm(data) {
  if (!data.party_name) {
    showNotification("Party name is required", "error");
    return false;
  }

  if (data.party_name.length < 3) {
    showNotification("Party name must be at least 3 characters", "error");
    return false;
  }

  return true;
}

function createParty(data) {
  setLoading(true);

  // Use API call
  fetch("api/party.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      action: "create_party",
      party_name: data.party_name,
      leader_id: currentUser.id,
      members: data.members,
    }),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.success) {
        // Update current user data
        currentUser.party_name = data.party_name;
        currentUser.has_party = true;

        // Update UI
        updatePartyDisplay();
        closePartyModal();

        showNotification("Party created successfully!", "success");

        // Reload page to reflect changes
        setTimeout(() => {
          location.reload();
        }, 1500);
      } else {
        showNotification(result.error || "Failed to create party", "error");
      }
    })
    .catch((error) => {
      console.error("Error creating party:", error);
      showNotification("An error occurred while creating party", "error");
    })
    .finally(() => {
      setLoading(false);
    });
}

function updatePartyDisplay() {
  const partyInput = document.getElementById("party");
  if (partyInput) {
    partyInput.value = currentUser.party_name;
  }

  if (partyBtn) {
    partyBtn.textContent = "Edit Party";
  }
}

function showPartyInfo() {
  if (!currentUser.has_party) return;

  // Use API call
  fetch("api/party.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      action: "get_party_info",
      user_id: currentUser.id,
    }),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.has_party) {
        const membersList = result.members
          .map((member) => `${member.username} (${member.full_name})`)
          .join("\n");

        const message = `Party: ${result.party_name}\n\nMembers:\n${membersList}`;
        showCustomAlert("Party Information", message);
      }
    })
    .catch((error) => {
      console.error("Error getting party info:", error);
    });
}

function resetPartyForm() {
  if (partyForm) {
    partyForm.reset();
  }

  // Reset validation states
  memberInputs.forEach((input, index) => {
    const messageDiv = document.getElementById(`member${index + 1}-message`);
    resetInputValidation(input, messageDiv);
  });
}

// Username validation
function validateUsername(username, inputElement, messageElement) {
  // Add loading state
  inputElement.style.borderColor = "#6b7280";
  messageElement.textContent = "Checking...";
  messageElement.style.color = "#6b7280";

  // Use API call
  fetch("api/party.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      action: "check_username",
      username: username,
    }),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.exists) {
        inputElement.style.borderColor = "#10b981";
        inputElement.style.boxShadow = "0 0 0 3px rgba(16, 185, 129, 0.1)";
        messageElement.textContent = `✓ ${result.user.full_name}`;
        messageElement.style.color = "#10b981";
      } else {
        inputElement.style.borderColor = "#ef4444";
        inputElement.style.boxShadow = "0 0 0 3px rgba(239, 68, 68, 0.1)";
        messageElement.textContent = "✗ User tidak ditemukan";
        messageElement.style.color = "#ef4444";
      }
    })
    .catch((error) => {
      console.error("Error checking username:", error);
      inputElement.style.borderColor = "#ef4444";
      messageElement.textContent = "✗ Error checking username";
      messageElement.style.color = "#ef4444";
    });
}

function resetInputValidation(input, messageDiv) {
  input.style.borderColor = "#444";
  input.style.boxShadow = "none";
  if (messageDiv) {
    messageDiv.textContent = "";
  }
}

// Email functionality
function showAddEmailForm() {
  if (addEmailBtn && addEmailForm) {
    addEmailBtn.style.display = "none";
    addEmailForm.style.display = "block";

    const emailInput = addEmailForm.querySelector('input[type="email"]');
    if (emailInput) {
      emailInput.focus();
    }
  }
}

function cancelAddEmail() {
  if (addEmailBtn && addEmailForm) {
    addEmailBtn.style.display = "block";
    addEmailForm.style.display = "none";

    const emailInput = addEmailForm.querySelector('input[type="email"]');
    if (emailInput) {
      emailInput.value = "";
    }
  }
}

function handleAddEmail(e) {
  e.preventDefault();
  if (isLoading) return;

  const emailInput = addEmailForm.querySelector('input[type="email"]');
  const email = emailInput.value.trim();

  if (!validateEmail(email)) {
    showInputError(emailInput, "Please enter a valid email address");
    return;
  }

  // Check if email already exists
  if (userEmails.some((e) => e.email === email)) {
    showInputError(emailInput, "Email already exists");
    return;
  }

  addEmail(email);
}

function addEmail(email) {
  setLoading(true);

  // Use API call
  fetch("api/user.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      action: "add_email",
      email: email,
    }),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.success) {
        const newEmail = {
          id: userEmails.length + 1,
          email: email,
          is_primary: false,
          created_at: new Date().toISOString(),
        };

        userEmails.push(newEmail);
        renderEmails();
        cancelAddEmail();

        showNotification("Email added successfully!", "success");
      } else {
        showNotification(result.error || "Failed to add email", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification("An error occurred while adding email", "error");
    })
    .finally(() => {
      setLoading(false);
    });
}

function removeEmail(emailId) {
  if (!confirm("Are you sure you want to remove this email?")) {
    return;
  }

  setLoading(true);

  // Use API call
  fetch("api/user.php", {
    method: "DELETE",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      email_id: emailId,
    }),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.success) {
        userEmails = userEmails.filter((email) => email.id !== emailId);
        renderEmails();
        showNotification("Email removed successfully!", "success");
      } else {
        showNotification(result.error || "Failed to remove email", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification("An error occurred while removing email", "error");
    })
    .finally(() => {
      setLoading(false);
    });
}

function renderEmails() {
  const emailList = document.getElementById("emailList");
  if (!emailList) return;

  emailList.innerHTML = "";

  userEmails.forEach((email) => {
    const emailItem = document.createElement("div");
    emailItem.className = "email-item";
    emailItem.innerHTML = `
            <div class="email-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="email-details">
                <p class="email-address">
                    ${email.email}
                    ${
                      email.is_primary
                        ? '<span class="primary-badge">Primary</span>'
                        : ""
                    }
                </p>
                <p class="email-time">${formatDate(email.created_at)}</p>
            </div>
            ${
              !email.is_primary
                ? `
                <button class="remove-email-btn" onclick="removeEmail(${email.id})">
                    <i class="fas fa-times"></i>
                </button>
            `
                : ""
            }
        `;
    emailList.appendChild(emailItem);
  });
}

// Utility functions
function performSearch(term) {
  showNotification(`Searching for: ${term}`);
  // Implement actual search logic here
}

function validateEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", {
    month: "short",
    day: "2-digit",
    year: "numeric",
  });
}

function updateCurrentDate() {
  const dateElement = document.getElementById("currentDate");
  if (dateElement) {
    const currentDate = new Date().toLocaleDateString("en-US", {
      weekday: "short",
      day: "2-digit",
      month: "long",
      year: "numeric",
    });
    dateElement.textContent = currentDate;
  }
}

function loadUserData() {
  // Simulate loading user data
  renderEmails();
  updateProfileDisplay();
}

function setLoading(loading) {
  isLoading = loading;

  // Add loading states to buttons
  const buttons = document.querySelectorAll("button");
  buttons.forEach((button) => {
    if (loading) {
      button.classList.add("loading");
      button.disabled = true;
    } else {
      button.classList.remove("loading");
      button.disabled = false;
    }
  });
}

function showInputError(input, message) {
  input.classList.add("error");

  // Remove existing error message
  const existingError = input.parentNode.querySelector(".error-message");
  if (existingError) {
    existingError.remove();
  }

  // Create new error message
  const errorMsg = document.createElement("span");
  errorMsg.className = "error-message";
  errorMsg.textContent = message;
  input.parentNode.appendChild(errorMsg);

  // Remove error after 5 seconds
  setTimeout(() => {
    input.classList.remove("error");
    if (errorMsg.parentNode) {
      errorMsg.remove();
    }
  }, 5000);
}

function showNotification(message, type = "info") {
  const notification = document.createElement("div");
  notification.className = "toast-notification";
  notification.textContent = message;

  // Set background color based on type
  const colors = {
    success: "#10b981",
    error: "#ef4444",
    info: "#3b82f6",
    warning: "#f59e0b",
  };

  notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 8px;
        color: white;
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        max-width: 300px;
        word-wrap: break-word;
        background-color: ${colors[type] || colors.info};
        border-left: 4px solid ${colors[type] || colors.info};
    `;

  document.body.appendChild(notification);

  // Remove notification after 3 seconds
  setTimeout(() => {
    notification.style.animation = "slideOut 0.3s ease-out";
    setTimeout(() => {
      if (document.body.contains(notification)) {
        document.body.removeChild(notification);
      }
    }, 300);
  }, 3000);
}

function showCustomAlert(title, message) {
  const alertModal = document.createElement("div");
  alertModal.className = "modal";
  alertModal.style.display = "block";

  alertModal.innerHTML = `
        <div class="modal-content" style="max-width: 400px;">
            <div style="background: #1a1a1a; padding: 20px; border-radius: 12px; border: 1px solid #333;">
                <h3 style="color: #fff; margin-bottom: 15px; font-size: 18px;">${title}</h3>
                <p style="color: #ccc; line-height: 1.6; white-space: pre-line;">${message}</p>
                <button onclick="this.closest('.modal').remove()" 
                        style="background: #ef4444; color: white; border: none; padding: 8px 16px; 
                               border-radius: 6px; cursor: pointer; margin-top: 15px; float: right;">
                    Close
                </button>
                <div style="clear: both;"></div>
            </div>
        </div>
    `;

  document.body.appendChild(alertModal);

  // Close on outside click
  alertModal.addEventListener("click", (e) => {
    if (e.target === alertModal) {
      alertModal.remove();
    }
  });
}

function handleKeyboardShortcuts(e) {
  // Ctrl/Cmd + K for search focus
  if ((e.ctrlKey || e.metaKey) && e.key === "k") {
    e.preventDefault();
    if (searchInput) {
      searchInput.focus();
    }
  }

  // Escape to close modals
  if (e.key === "Escape") {
    if (partyModal && partyModal.style.display === "block") {
      closePartyModal();
    }
  }
}

// Handle window resize
window.addEventListener("resize", () => {
  if (window.innerWidth > 768 && sidebar) {
    sidebar.classList.remove("active");
  }
});

// Make functions globally available
window.removeEmail = removeEmail;
window.cancelAddEmail = cancelAddEmail;

// Smooth scrolling
document.documentElement.style.scrollBehavior = "smooth";


class TournamentRegistration {
  constructor() {
    this.modal = document.getElementById("registrationModal");
    this.modalContent = document.getElementById("modalContent");
    this.closeBtn = document.getElementById("closeRegistrationModal");
    this.currentTournamentId = null;
    this.eligibilityData = null;

    // Check if required elements exist
    if (!this.modal) {
      this.createModal();
    }

    this.init();
  }

  createModal() {
    // Create modal if it doesn't exist
    const modalHTML = `
      <div id="registrationModal" class="registration-modal">
        <div class="registration-modal-content">
          <button class="modal-close" id="closeRegistrationModal">&times;</button>
          <div class="modal-header">
            <h2 class="modal-title">Daftar Turnamen</h2>
          </div>
          <div id="modalContent">
            <!-- Content will be loaded dynamically -->
          </div>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML("beforeend", modalHTML);

    // Re-get elements
    this.modal = document.getElementById("registrationModal");
    this.modalContent = document.getElementById("modalContent");
    this.closeBtn = document.getElementById("closeRegistrationModal");
  }

  init() {
    // Event listeners with null checks
    if (this.closeBtn) {
      this.closeBtn.addEventListener("click", () => this.closeModal());
    }

    if (this.modal) {
      this.modal.addEventListener("click", (e) => {
        if (e.target === this.modal) this.closeModal();
      });
    }

    // Register button event listeners
    document.addEventListener("click", (e) => {
      if (e.target.classList.contains("register-btn")) {
        const tournamentId = e.target.dataset.tournamentId;
        this.openRegistrationModal(tournamentId);
      }
    });
  }

  async openRegistrationModal(tournamentId) {
    this.currentTournamentId = tournamentId;
    this.showModal();
    this.showLoading();

    try {
      const response = await this.checkEligibility(tournamentId);

      if (response.success) {
        this.eligibilityData = response;

        // Also store in global instance if it exists
        if (
          window.tournamentRegistration &&
          window.tournamentRegistration !== this
        ) {
          window.tournamentRegistration.eligibilityData = response;
        }

        this.renderModalContent();
      } else {
        this.showError(response.error || "Gagal memuat data turnamen");
      }
    } catch (error) {
      this.showError("Terjadi kesalahan saat memuat data: " + error.message);
    }
  }

  getApiPath() {
    const currentPath = window.location.pathname;

    if (currentPath.includes("/PHP/PLAYER/")) {
      return "tournament_registration_api.php";
    } else if (currentPath.includes("/BrackIt/")) {
      return "PHP/PLAYER/tournament_registration_api.php";
    } else {
      return "PHP/PLAYER/tournament_registration_api.php";
    }
  }

  async checkEligibility(tournamentId) {
    // Determine the correct API path based on current location
    const apiPath = this.getApiPath();

    try {
      const response = await fetch(apiPath, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "same-origin", // Important: Include session cookies
        body: JSON.stringify({
          action: "check_eligibility",
          tournament_id: parseInt(tournamentId),
        }),
      });

      if (!response.ok) {
        // Try to get error details from response
        try {
          const errorData = await response.json();

          if (errorData.error && errorData.error.includes("login")) {
            throw new Error(
              "Silakan login terlebih dahulu untuk mendaftar turnamen"
            );
          } else {
            throw new Error(
              errorData.error ||
                `HTTP ${response.status}: ${response.statusText}`
            );
          }
        } catch (parseError) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
      }

      const data = await response.json();

      if (!data.success) {
        throw new Error(data.error || "Gagal memuat data turnamen");
      }

      return data;
    } catch (error) {
      throw error;
    }
  }

  async registerTournament(registrationType) {
    try {
      const apiPath = this.getApiPath();

      // Find the selected option to get additional data
      const option = this.eligibilityData.registration_options.find(
        (opt) => opt.type === registrationType
      );

      const requestData = {
        action: "register",
        tournament_id: this.currentTournamentId,
        registration_type: registrationType,
      };

      // Add team_id if registering with existing team
      if (registrationType === "team" && option && option.team_id) {
        requestData.team_id = option.team_id;
      }

      // Add team_name if creating new team
      if (registrationType === "create_team") {
        const teamName = prompt(
          "Masukkan nama tim:",
          "Tim " + new Date().getTime()
        );
        if (!teamName) {
          this.showError("Nama tim diperlukan untuk membuat tim baru");
          return;
        }
        requestData.team_name = teamName;
      }

      const response = await fetch(apiPath, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "same-origin",
        body: JSON.stringify(requestData),
      });

      const result = await response.json();

      if (result.success) {
        this.showSuccess(result.message);
        // Reload page after 2 seconds to update UI
        setTimeout(() => {
          location.reload();
        }, 2000);
      } else {
        this.showError(result.error || "Gagal mendaftar turnamen");
      }
    } catch (error) {
      this.showError("Terjadi kesalahan saat mendaftar");
    }
  }

  renderModalContent() {
    if (!this.modalContent) {
      return;
    }

    // Check if eligibilityData exists
    if (!this.eligibilityData) {
      this.showError("Data turnamen tidak tersedia. Silakan coba lagi.");
      return;
    }

    const data = this.eligibilityData;

    // Check if tournament data exists
    if (!data.tournament) {
      this.showError("Data turnamen tidak lengkap. Silakan coba lagi.");
      return;
    }

    const tournament = data.tournament;

    if (!data.can_register) {
      this.modalContent.innerHTML = `
                <div class="tournament-info">
                    <h3>${tournament.nama_turnamen}</h3>
                    <div class="tournament-detail">
                        <span class="label">Format:</span>
                        <span>${tournament.format}</span>
                    </div>
                    <div class="tournament-detail">
                        <span class="label">Peserta:</span>
                        <span>${tournament.current_participants}/${tournament.max_participants}</span>
                    </div>
                </div>
                <div class="error-message">
                    ${data.message}
                </div>
                <div class="modal-actions">
                    <button class="modal-btn btn-secondary" onclick="tournamentRegistration.closeModal()">
                        Tutup
                    </button>
                </div>
            `;
      return;
    }

    // Check if registration_options exists
    if (
      !data.registration_options ||
      !Array.isArray(data.registration_options)
    ) {
      this.showError("Opsi pendaftaran tidak tersedia. Silakan coba lagi.");
      return;
    }

    // Render registration options
    let optionsHtml = "";
    if (data.registration_options.length === 1) {
      // Single option - show confirmation
      const option = data.registration_options[0];
      optionsHtml = `
                <div class="tournament-info">
                    <h3>${tournament.nama_turnamen}</h3>
                    <div class="tournament-detail">
                        <span class="label">Format:</span>
                        <span>${tournament.format}</span>
                    </div>
                    <div class="tournament-detail">
                        <span class="label">Tipe Pendaftaran:</span>
                        <span>${option.label}</span>
                    </div>
                    <div class="tournament-detail">
                        <span class="label">Peserta:</span>
                        <span>${tournament.current_participants}/${tournament.max_participants}</span>
                    </div>
                </div>
                <div style="text-align: center; margin: 20px 0; color: #fff; font-size: 16px;">
                    Apakah Anda yakin ingin mengikuti turnamen <strong>${tournament.nama_turnamen}</strong>?
                </div>
                <div class="modal-actions">
                    <button class="modal-btn btn-primary" onclick="tournamentRegistration.registerTournament('${option.type}')">
                        Ya, Daftar
                    </button>
                    <button class="modal-btn btn-secondary" onclick="tournamentRegistration.closeModal()">
                        Batal
                    </button>
                </div>
            `;
    } else {
      // Multiple options - show selection
      let radioOptions = "";
      data.registration_options.forEach((option, index) => {
        radioOptions += `
                    <div class="registration-option" onclick="this.querySelector('input').checked = true; tournamentRegistration.selectOption(this)">
                        <input type="radio" name="registration_type" value="${option.type}" id="option_${index}">
                        <label class="option-label" for="option_${index}">${option.label}</label>
                        <div class="option-description">${option.description}</div>
                    </div>
                `;
      });

      optionsHtml = `
                <div class="tournament-info">
                    <h3>${tournament.nama_turnamen}</h3>
                    <div class="tournament-detail">
                        <span class="label">Format:</span>
                        <span>${tournament.format}</span>
                    </div>
                    <div class="tournament-detail">
                        <span class="label">Peserta:</span>
                        <span>${tournament.current_participants}/${tournament.max_participants}</span>
                    </div>
                </div>
                <div class="registration-options">
                    <h3 class="option-title">Pilih cara pendaftaran:</h3>
                    ${radioOptions}
                </div>
                <div class="modal-actions">
                    <button class="modal-btn btn-primary" id="confirmRegistration" disabled>
                        Daftar
                    </button>
                    <button class="modal-btn btn-secondary" id="cancelRegistration">
                        Batal
                    </button>
                </div>
            `;
    }

    this.modalContent.innerHTML = optionsHtml;

    // Add event listeners after HTML is rendered
    this.bindEventListeners();
  }

  bindEventListeners() {
    // Bind confirm button
    const confirmBtn = document.getElementById("confirmRegistration");
    if (confirmBtn) {
      confirmBtn.onclick = () => this.confirmRegistration();
    }

    // Bind cancel button
    const cancelBtn = document.getElementById("cancelRegistration");
    if (cancelBtn) {
      cancelBtn.onclick = () => this.closeModal();
    }
  }

  selectOption(element) {
    // Remove selected class from all options
    document.querySelectorAll(".registration-option").forEach((opt) => {
      opt.classList.remove("selected");
    });

    // Add selected class to clicked option
    element.classList.add("selected");

    // Enable confirm button
    const confirmBtn = document.getElementById("confirmRegistration");
    if (confirmBtn) {
      confirmBtn.disabled = false;
    }
  }

  confirmRegistration() {
    const selected = document.querySelector(
      'input[name="registration_type"]:checked'
    );
    if (selected) {
      const registrationType = selected.value;

      // Check if eligibilityData exists
      if (!this.eligibilityData) {
        // Try to use global instance if local instance failed
        if (
          window.tournamentRegistration &&
          window.tournamentRegistration.eligibilityData
        ) {
          this.eligibilityData = window.tournamentRegistration.eligibilityData;
        } else {
          this.showError("Data turnamen tidak tersedia. Silakan coba lagi.");
          return;
        }
      }

      // Check if registration_options exists
      if (!this.eligibilityData.registration_options) {
        this.showError("Opsi pendaftaran tidak tersedia. Silakan coba lagi.");
        return;
      }

      // Show confirmation
      const option = this.eligibilityData.registration_options.find(
        (opt) => opt.type === registrationType
      );

      if (!option) {
        this.showError("Opsi pendaftaran tidak valid. Silakan pilih ulang.");
        return;
      }

      const tournament = this.eligibilityData.tournament;

      if (!this.modalContent) {
        return;
      }

      this.modalContent.innerHTML = `
                <div class="tournament-info">
                    <h3>${tournament.nama_turnamen}</h3>
                    <div class="tournament-detail">
                        <span class="label">Tipe Pendaftaran:</span>
                        <span>${option.label}</span>
                    </div>
                </div>
                <div style="text-align: center; margin: 20px 0; color: #fff; font-size: 16px;">
                    Apakah Anda yakin ingin mengikuti turnamen <strong>${tournament.nama_turnamen}</strong> dengan <strong>${option.label}</strong>?
                </div>
                <div class="modal-actions">
                    <button class="modal-btn btn-primary" onclick="tournamentRegistration.registerTournament('${registrationType}')">
                        Ya, Daftar
                    </button>
                    <button class="modal-btn btn-secondary" onclick="tournamentRegistration.closeModal()">
                        Batal
                    </button>
                </div>
            `;
    }
  }

  showModal() {
    if (this.modal) {
      this.modal.classList.add("show");
      document.body.style.overflow = "hidden";
    }
  }

  closeModal() {
    if (this.modal) {
      this.modal.classList.remove("show");
      document.body.style.overflow = "";
    }
    this.currentTournamentId = null;
    this.eligibilityData = null;
  }

  showLoading() {
    if (this.modalContent) {
      this.modalContent.innerHTML = `
            <div class="loading-state">
                <div class="spinner"></div>
                <p>Memuat data turnamen...</p>
            </div>
        `;
    }
  }

  showError(message) {
    if (this.modalContent) {
      // Check if this is a login related error
      const isLoginError =
        message.toLowerCase().includes("login") ||
        message.toLowerCase().includes("unauthorized") ||
        message.toLowerCase().includes("silakan login");

      if (isLoginError) {
        this.modalContent.innerHTML = `
          <div class="error-message">
              <div style="text-align: center; padding: 20px;">
                  <div style="font-size: 3rem; color: var(--primary-color); margin-bottom: 20px;">🔐</div>
                  <h3 style="color: var(--text-color); margin-bottom: 10px;">Login Diperlukan</h3>
                  <p style="color: rgba(255, 255, 255, 0.7); margin-bottom: 30px;">
                      ${message}
                  </p>
                  <div style="display: flex; gap: 15px; justify-content: center;">
                      <button onclick="window.location.href='PHP/LOGIN/login.php'" style="
                          padding: 12px 24px;
                          background: var(--primary-color);
                          color: white;
                          border: none;
                          border-radius: 10px;
                          cursor: pointer;
                          font-weight: 500;
                      ">Login Sekarang</button>
                      <button onclick="tournamentRegistration.closeModal()" style="
                          padding: 12px 24px;
                          background: transparent;
                          color: var(--text-color);
                          border: 1px solid rgba(255, 255, 255, 0.3);
                          border-radius: 10px;
                          cursor: pointer;
                      ">Tutup</button>
                  </div>
              </div>
          </div>
        `;
      } else {
        this.modalContent.innerHTML = `
            <div class="error-message">
                ${message}
            </div>
            <div class="modal-actions">
                <button class="modal-btn btn-secondary" onclick="tournamentRegistration.closeModal()">
                    Tutup
                </button>
            </div>
        `;
      }
    } else {
      alert(message); // Fallback to alert
    }
  }

  showSuccess(message) {
    if (this.modalContent) {
      this.modalContent.innerHTML = `
            <div class="success-message">
                ${message}
            </div>
            <div class="modal-actions">
                <button class="modal-btn btn-primary" onclick="tournamentRegistration.closeModal()">
                    Tutup
                </button>
            </div>
        `;
    } else {
      alert(message); // Fallback to alert
    }
  }
}

// Initialize when DOM is loaded
let tournamentRegistration;
document.addEventListener("DOMContentLoaded", function () {
  tournamentRegistration = new TournamentRegistration();

  // Export instance to global scope as well
  window.tournamentRegistration = tournamentRegistration;
});

// Export to global scope for access from other scripts
window.TournamentRegistration = TournamentRegistration;

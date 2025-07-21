// Tournament Modal JavaScript
class TournamentModal {
  constructor() {
    this.modal = null;
    this.isLoading = false;
    this.init();
  }

  init() {
    this.createModal();
    this.bindEvents();
    this.attachToTournamentCards();
  }

  createModal() {
    // Create modal HTML structure
    const modalHTML = `
            <div id="tournamentModal" class="modal">
                <div class="modal-content tournament-modal-content">
                    <span class="close tournament-close">&times;</span>
                    <div id="tournamentModalContent">
                        <div class="loading">Loading tournament details...</div>
                    </div>
                </div>
            </div>
        `;

    // Add modal to body if it doesn't exist
    if (!document.getElementById("tournamentModal")) {
      document.body.insertAdjacentHTML("beforeend", modalHTML);
    }

    this.modal = document.getElementById("tournamentModal");
  }

  bindEvents() {
    // Close modal when clicking X
    const closeBtn = document.querySelector(".tournament-close");
    if (closeBtn) {
      closeBtn.onclick = () => this.closeModal();
    }

    // Close modal when clicking outside
    window.onclick = (event) => {
      if (event.target === this.modal) {
        this.closeModal();
      }
    };

    // Escape key to close modal
    document.addEventListener("keydown", (e) => {
      if (
        e.key === "Escape" &&
        this.modal &&
        this.modal.style.display === "block"
      ) {
        this.closeModal();
      }
    });

    // Add event delegation for register buttons (FALLBACK)
    document.addEventListener("click", (e) => {
      if (e.target.classList.contains("register-button")) {
        e.preventDefault();
        e.stopPropagation();

        // Get tournament data from the current modal content
        const modalContent = document.getElementById("tournamentModalContent");
        if (modalContent && this.currentTournament) {
          this.handleRegistration(this.currentTournament);
        } else {
          console.error(
            "TournamentModal: Cannot handle registration - no tournament data"
          );
        }
      }
    });
    document.addEventListener("keydown", (event) => {
      if (
        event.key === "Escape" &&
        this.modal &&
        this.modal.style.display === "block"
      ) {
        this.closeModal();
      }
    });
  }

  attachToTournamentCards() {
    // Attach click event to tournament cards
    document.addEventListener("click", (e) => {
      const tournamentCard = e.target.closest("[data-tournament-id]");
      if (tournamentCard) {
        e.preventDefault();
        const tournamentId = tournamentCard.getAttribute("data-tournament-id");
        if (tournamentId) {
          this.openModal(tournamentId);
        }
      }
    });

    // Add hover effects
    const tournamentCards = document.querySelectorAll("[data-tournament-id]");
    tournamentCards.forEach((card) => {
      card.style.cursor = "pointer";
      card.style.transition = "transform 0.3s ease, box-shadow 0.3s ease";

      card.addEventListener("mouseenter", () => {
        card.style.transform = "translateY(-5px) scale(1.02)";
        card.style.boxShadow = "0 10px 25px rgba(0, 0, 0, 0.2)";
      });

      card.addEventListener("mouseleave", () => {
        card.style.transform = "translateY(0) scale(1)";
        card.style.boxShadow = "";
      });
    });
  }

  async openModal(tournamentId) {
    if (this.isLoading) return;

    this.modal.style.display = "block";
    this.showLoading();

    try {
      const tournamentData = await this.fetchTournamentData(tournamentId);
      this.renderTournamentDetails(tournamentData);
    } catch (error) {
      this.showError(error.message);
    }
  }

  closeModal() {
    this.modal.style.display = "none";
  }

  showLoading() {
    const modalContent = document.getElementById("tournamentModalContent");
    modalContent.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p style="margin-top: 20px; text-align: center; color: var(--text-color);">
                    Loading tournament details...
                </p>
            </div>
        `;
  }

  showError(message) {
    const modalContent = document.getElementById("tournamentModalContent");
    modalContent.innerHTML = `
            <div style="text-align: center; padding: 50px;">
                <div style="font-size: 4rem; color: var(--primary-color); margin-bottom: 20px;">⚠️</div>
                <h3 style="color: var(--text-color); margin-bottom: 10px;">Error</h3>
                <p style="color: rgba(255, 255, 255, 0.7);">${message}</p>
                <button onclick="tournamentModal.closeModal()" style="
                    margin-top: 20px;
                    padding: 10px 20px;
                    background: var(--primary-color);
                    color: white;
                    border: none;
                    border-radius: 10px;
                    cursor: pointer;
                ">Close</button>
            </div>
        `;
  }

  async fetchTournamentData(tournamentId) {
    this.isLoading = true;

    try {
      // Determine the correct path based on current location
      const currentPath = window.location.pathname;
      let apiPath = "getTournamentDetails.php";

      if (
        currentPath.includes("menuTournament.php") ||
        currentPath.includes("PHP/PLAYER/")
      ) {
        apiPath = "getTournamentDetails.php";
      } else if (
        currentPath.includes("index.php") ||
        currentPath === "/" ||
        currentPath.includes("BrackIt/")
      ) {
        apiPath = "PHP/PLAYER/getTournamentDetails.php";
      }

      const response = await fetch(apiPath, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          tournament_id: parseInt(tournamentId),
        }),
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();

      if (!data.success) {
        throw new Error(data.error || "Failed to fetch tournament data");
      }

      return data;
    } catch (error) {
      console.error("Error fetching tournament data:", error);
      throw error;
    } finally {
      this.isLoading = false;
    }
  }

  renderTournamentDetails(data) {
    const tournament = data.tournament;
    const modalContent = document.getElementById("tournamentModalContent");

    // Store current tournament data for fallback event delegation
    this.currentTournament = tournament;
    "TournamentModal: Stored tournament data for event delegation:",
      this.currentTournament;

    // Function to get the correct base path
    function getBasePath() {
      const currentPath = window.location.pathname;

      if (currentPath.includes("/PHP/")) {
        return "../../";
      } else if (currentPath.includes("/BrackIt/")) {
        return "";
      } else {
        return "";
      }
    }

    const basePath = getBasePath();

    modalContent.innerHTML = `
            <div class="tournament-detail">
                <section class="tournament-hero ${tournament.hero_class}">
                    <div class="tournament-content">
                        <h1 class="tournament-title">${tournament.name}</h1>
                        <p class="tournament-subtitle">${
                          tournament.eo_organization
                        }</p>
                        <div class="registration-status-badge ${tournament.registration_status
                          .toLowerCase()
                          .replace(/\s+/g, "-")}">
                            ${tournament.registration_status}
                        </div>
                        <div class="tournament-status">
                            ${tournament.status_text}
                        </div>
                        ${
                          tournament.tournament_start_iso &&
                          !tournament.tournament_has_started
                            ? `
                            <div id="modalCountdown" data-tournament-date="${tournament.tournament_start_iso}" style="margin-top: 20px;">
                                <div class="countdown-timer">
                                    <div class="countdown-item">
                                        <span class="countdown-number" id="modalDays">--</span>
                                        <span class="countdown-label">Hari</span>
                                    </div>
                                    <div class="countdown-item">
                                        <span class="countdown-number" id="modalHours">--</span>
                                        <span class="countdown-label">Jam</span>
                                    </div>
                                    <div class="countdown-item">
                                        <span class="countdown-number" id="modalMinutes">--</span>
                                        <span class="countdown-label">Menit</span>
                                    </div>
                                    <div class="countdown-item">
                                        <span class="countdown-number" id="modalSeconds">--</span>
                                        <span class="countdown-label">Detik</span>
                                    </div>
                                </div>
                            </div>
                            `
                            : tournament.tournament_has_started
                            ? `<div class="tournament-started-message">Tournament telah dimulai</div>`
                            : ""
                        }
                    </div>
                </section>

                <div class="tournament-content">
                    <div class="content-grid">
                        <div class="info-card">
                            <h3>Informasi Tournament</h3>
                            <ul class="info-list">
                                <li>
                                    <span class="info-label">Game:</span>
                                    <span class="info-value">${
                                      tournament.game
                                    }</span>
                                </li>
                                <li>
                                    <span class="info-label">Format:</span>
                                    <span class="info-value">${
                                      tournament.format
                                    }</span>
                                </li>
                                <li>
                                    <span class="info-label">Pendaftaran:</span>
                                    <span class="info-value">${
                                      tournament.registration_period
                                    }</span>
                                </li>
                                <li>
                                    <span class="info-label">Tournament:</span>
                                    <span class="info-value">${
                                      tournament.tournament_period
                                    }</span>
                                </li>
                                <li>
                                    <span class="info-label">Prize Pool:</span>
                                    <span class="info-value prize-pool">${
                                      tournament.prize_pool
                                    }</span>
                                </li>
                            </ul>
                        </div>

                        <div class="info-card">
                            <h3>Persyaratan</h3>
                            <ul class="info-list">
                                <li>
                                    <span class="info-label">Team Size:</span>
                                    <span class="info-value">${
                                      tournament.team_size
                                    }</span>
                                </li>
                                <li>
                                    <span class="info-label">Entry Fee:</span>
                                    <span class="info-value">${
                                      tournament.entry_fee
                                    }</span>
                                </li>
                                <li>
                                    <span class="info-label">Platform:</span>
                                    <span class="info-value">${
                                      tournament.platform
                                    }</span>
                                </li>
                                <li>
                                    <span class="info-label">Region:</span>
                                    <span class="info-value">${
                                      tournament.region
                                    }</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="tournament-description">
                        <h3>Tentang ${tournament.name}</h3>
                        ${tournament.description
                          .split("\\n\\n")
                          .map((paragraph) =>
                            paragraph.trim()
                              ? `<p>${paragraph
                                  .trim()
                                  .replace(/\\n/g, "<br>")}</p>`
                              : ""
                          )
                          .join("")}
                    </div>

                    ${
                      tournament.rules_array &&
                      tournament.rules_array.length > 0
                        ? `
                        <div class="rules-section">
                            <h3>Peraturan Tournament</h3>
                            <ul class="rules-list">
                                ${tournament.rules_array
                                  .map((rule) =>
                                    rule.trim() ? `<li>${rule.trim()}</li>` : ""
                                  )
                                  .join("")}
                            </ul>
                        </div>
                    `
                        : ""
                    }

                    <div class="registration-section">
                        <h3>${tournament.registration_title}</h3>
                        <p>${tournament.registration_description}</p>
                        <button class="register-button" 
                                data-tournament-id="${
                                  tournament.id || tournament.id_turnamen
                                }"
                                data-tournament-slug="${tournament.slug}"
                                ${
                                  tournament.registration_status ===
                                    "PENDAFTARAN DITUTUP" ||
                                  tournament.is_full ||
                                  tournament.status === "selesai"
                                    ? "disabled"
                                    : ""
                                }>
                            ${
                              tournament.is_full
                                ? "Tournament Penuh"
                                : tournament.registration_status ===
                                  "PENDAFTARAN DITUTUP"
                                ? "Pendaftaran Ditutup"
                                : tournament.registration_status ===
                                  "BELUM DIBUKA"
                                ? "Pendaftaran Belum Dibuka"
                                : tournament.button_text
                            }
                        </button>
                        ${
                          tournament.slots_available !== undefined
                            ? `
                            <p class="slots-info">
                                ${
                                  tournament.is_full
                                    ? "Maaf, tournament sudah penuh!"
                                    : `Sisa slot: ${tournament.slots_available} tim`
                                }
                            </p>
                        `
                            : ""
                        }
                    </div>
                </div>
            </div>
        `;

    // Initialize countdown if tournament date exists
    if (tournament.tournament_start_iso && !tournament.tournament_has_started) {
      this.initializeCountdown();
    }

    // Add registration button event
    const registerBtn = modalContent.querySelector(".register-button");
    "TournamentModal: Looking for register button, found:", registerBtn;
    "TournamentModal: Button disabled?",
      registerBtn ? registerBtn.disabled : "N/A";

    if (registerBtn && !registerBtn.disabled) {
      ("TournamentModal: Adding click event listener to register button");
      registerBtn.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        this.handleRegistration(tournament);
      });
    } else if (registerBtn && registerBtn.disabled) {
      ("TournamentModal: Register button is disabled, not adding event listener");
    } else {
    }
  }

  initializeCountdown() {
    const countdownElement = document.getElementById("modalCountdown");
    if (!countdownElement) return;

    const targetDate = new Date(
      countdownElement.getAttribute("data-tournament-date")
    );

    const updateCountdown = () => {
      const now = new Date();
      const difference = targetDate - now;

      if (difference > 0) {
        const days = Math.floor(difference / (1000 * 60 * 60 * 24));
        const hours = Math.floor(
          (difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
        );
        const minutes = Math.floor(
          (difference % (1000 * 60 * 60)) / (1000 * 60)
        );
        const seconds = Math.floor((difference % (1000 * 60)) / 1000);

        const daysEl = document.getElementById("modalDays");
        const hoursEl = document.getElementById("modalHours");
        const minutesEl = document.getElementById("modalMinutes");
        const secondsEl = document.getElementById("modalSeconds");

        if (daysEl) daysEl.textContent = days.toString().padStart(2, "0");
        if (hoursEl) hoursEl.textContent = hours.toString().padStart(2, "0");
        if (minutesEl)
          minutesEl.textContent = minutes.toString().padStart(2, "0");
        if (secondsEl)
          secondsEl.textContent = seconds.toString().padStart(2, "0");
      } else {
        // Tournament has started
        countdownElement.innerHTML =
          '<div class="tournament-started-message">Tournament telah dimulai</div>';
        clearInterval(this.countdownInterval);
      }
    };

    // Update immediately
    updateCountdown();

    // Update every second
    this.countdownInterval = setInterval(updateCountdown, 1000);
  }

  handleRegistration(tournament) {
    // Check if user is logged in first via API call
    this.checkLoginStatus()
      .then((loginResult) => {
        if (!loginResult.isLoggedIn) {
          this.showLoginRequired();
          return;
        }

        // Close tournament detail modal first
        this.closeModal();

        // Add small delay to ensure modal is closed
        setTimeout(() => {
          this.initializeRegistration(tournament);
        }, 100);
      })
      .catch((error) => {
        this.showError("Terjadi kesalahan saat memuat data: " + error.message);
      });
  }

  async checkLoginStatus() {
    try {
      const apiPath = this.getApiBasePath() + "tournament_registration_api.php";

      // Try to call a simple API that requires login
      const response = await fetch(apiPath, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "same-origin", // Include cookies/session
        body: JSON.stringify({
          action: "check_session",
        }),
      });

      if (!response.ok) {
        return { isLoggedIn: false, error: `HTTP ${response.status}` };
      }

      const data = await response.json();

      return {
        isLoggedIn: data.success && data.logged_in,
        data: data,
      };
    } catch (error) {
      console.error("Login check failed:", error);
      return { isLoggedIn: false, error: error.message };
    }
  }

  getApiBasePath() {
    const currentPath = window.location.pathname;
    if (currentPath.includes("/PHP/PLAYER/")) {
      return "";
    } else if (currentPath.includes("/BrackIt/")) {
      return "PHP/PLAYER/";
    } else {
      return "PHP/PLAYER/";
    }
  }

  showLoginRequired() {
    const modalContent = document.getElementById("tournamentModalContent");
    modalContent.innerHTML = `
            <div style="text-align: center; padding: 50px;">
                <div style="font-size: 4rem; color: var(--primary-color); margin-bottom: 20px;">🔐</div>
                <h3 style="color: var(--text-color); margin-bottom: 10px;">Login Diperlukan</h3>
                <p style="color: rgba(255, 255, 255, 0.7); margin-bottom: 30px;">
                    Silakan login terlebih dahulu untuk mendaftar turnamen.
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
                    <button onclick="tournamentModal.closeModal()" style="
                        padding: 12px 24px;
                        background: transparent;
                        color: var(--text-color);
                        border: 1px solid rgba(255, 255, 255, 0.3);
                        border-radius: 10px;
                        cursor: pointer;
                    ">Tutup</button>
                </div>
            </div>
        `;
  }

  async initializeRegistration(tournament) {
    try {
      "TournamentModal: initializeRegistration called with tournament:",
        tournament;

      // Ensure registration script is loaded
      await this.ensureRegistrationScript();

      // Initialize registration modal
      ("TournamentModal: Checking if TournamentRegistration class is available...");
      if (window.TournamentRegistration) {
        console.log(
          "TournamentModal: Creating new TournamentRegistration instance"
        );
        // Create global instance that can be accessed by HTML onclick handlers
        window.tournamentRegistration = new window.TournamentRegistration();
        console.log(
          "TournamentModal: Calling openRegistrationModal with ID:",
          tournament.id || tournament.id_turnamen
        );
        window.tournamentRegistration.openRegistrationModal(
          tournament.id || tournament.id_turnamen
        );
      } else {
        console.error(
          "TournamentModal: TournamentRegistration class not available after script load"
        );
        throw new Error(
          "TournamentRegistration class not available after script load"
        );
      }
    } catch (error) {
      console.error(
        "TournamentModal: Registration initialization error:",
        error
      );

      // Show more specific error based on error type
      if (error.message.includes("script")) {
        alert(
          "Gagal memuat script pendaftaran. Silakan refresh halaman dan coba lagi."
        );
      } else if (error.message.includes("class")) {
        alert(
          "Sistem pendaftaran belum siap. Silakan tunggu sebentar dan coba lagi."
        );
      } else {
        alert("Terjadi kesalahan saat membuka pendaftaran: " + error.message);
      }
    }
  }

  ensureRegistrationScript() {
    return new Promise((resolve, reject) => {
      // Check if class is already available
      if (window.TournamentRegistration) {
        resolve();
        return;
      }

      // Check if script is already loaded
      const existingScript = document.querySelector(
        'script[src*="tournament-registration.js"]'
      );
      if (existingScript) {
        // Script loaded but class not available - wait a bit
        setTimeout(() => {
          if (window.TournamentRegistration) {
            resolve();
          } else {
            reject(new Error("Script loaded but class not available"));
          }
        }, 500);
        return;
      }

      // Load the script
      const script = document.createElement("script");
      script.src = this.getRegistrationScriptPath();

      script.onload = () => {
        // Give it a moment to initialize
        setTimeout(() => {
          if (window.TournamentRegistration) {
            resolve();
          } else {
            reject(new Error("Script loaded but class not initialized"));
          }
        }, 200);
      };

      script.onerror = (error) => {
        console.error("Failed to load registration script:", error);
        reject(new Error("Failed to load registration script"));
      };

      document.head.appendChild(script);
    });
  }

  getRegistrationScriptPath() {
    const currentPath = window.location.pathname;

    let scriptPath;
    if (currentPath.includes("/PHP/PLAYER/")) {
      scriptPath = "../../SCRIPT/PLAYER/tournament-registration.js";
    } else if (currentPath.includes("/BrackIt/")) {
      scriptPath = "SCRIPT/PLAYER/tournament-registration.js";
    } else {
      scriptPath = "SCRIPT/PLAYER/tournament-registration.js";
    }

    return scriptPath;
  }

  closeModal() {
    this.modal.style.display = "none";

    // Clear countdown interval if it exists
    if (this.countdownInterval) {
      clearInterval(this.countdownInterval);
      this.countdownInterval = null;
    }
  }
}

// Initialize tournament modal when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  window.tournamentModal = new TournamentModal();
});

// Export for module usage
if (typeof module !== "undefined" && module.exports) {
  module.exports = TournamentModal;
}

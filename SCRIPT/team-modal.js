// Team Modal Functionality
class TeamModal {
  constructor() {
    this.modal = null;
    this.isLoading = false;
    this.init();
  }

  init() {
    this.createModal();
    this.bindEvents();
  }

  createModal() {
    // Create modal HTML
    const modalHTML = `
            <div id="teamModal" class="team-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title" id="modalTitle">Team Details</h2>
                        <button class="close-btn" id="closeModal">&times;</button>
                    </div>
                    <div class="modal-body" id="modalBody">
                        <div class="loading-spinner">
                            <div class="spinner"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;

    // Add modal to body
    document.body.insertAdjacentHTML("beforeend", modalHTML);
    this.modal = document.getElementById("teamModal");
  }

  bindEvents() {
    // Close modal events
    const closeBtn = document.getElementById("closeModal");
    closeBtn.addEventListener("click", () => this.closeModal());

    // Close modal when clicking outside
    this.modal.addEventListener("click", (e) => {
      if (e.target === this.modal) {
        this.closeModal();
      }
    });

    // Close modal with Escape key
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && this.modal.style.display === "block") {
        this.closeModal();
      }
    });

    // Bind team card clicks
    this.bindTeamCards();
  }

  bindTeamCards() {
    // Find all team cards and add click events
    const teamCards = document.querySelectorAll(".team-card");
    teamCards.forEach((card) => {
      card.style.cursor = "pointer";
      card.addEventListener("click", (e) => {
        e.preventDefault();
        const teamId = card.getAttribute("data-team-id");
        if (teamId) {
          this.openModal(teamId);
        }
      });

      // Add hover effect
      card.addEventListener("mouseenter", () => {
        card.style.transform = "translateY(-5px) scale(1.02)";
      });

      card.addEventListener("mouseleave", () => {
        card.style.transform = "translateY(0) scale(1)";
      });
    });
  }

  async openModal(teamId) {
    if (this.isLoading) return;

    this.modal.style.display = "block";
    this.showLoading();

    try {
      const teamData = await this.fetchTeamData(teamId);
      this.renderTeamDetails(teamData);
    } catch (error) {
      this.showError(error.message);
    }
  }

  closeModal() {
    this.modal.style.display = "none";
  }

  showLoading() {
    const modalBody = document.getElementById("modalBody");
    modalBody.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p style="margin-top: 20px; text-align: center; color: var(--text-color);">
                    Loading team details...
                </p>
            </div>
        `;
  }

  showError(message) {
    const modalBody = document.getElementById("modalBody");
    modalBody.innerHTML = `
            <div style="text-align: center; padding: 50px;">
                <div style="font-size: 4rem; color: var(--primary-color); margin-bottom: 20px;">⚠️</div>
                <h3 style="color: var(--text-color); margin-bottom: 10px;">Error</h3>
                <p style="color: rgba(255, 255, 255, 0.7);">${message}</p>
                <button onclick="teamModal.closeModal()" style="
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

  async fetchTeamData(teamId) {
    this.isLoading = true;

    try {
      // Determine the correct path based on current location
      const currentPath = window.location.pathname;
      let apiPath = "getTeamDetails.php";

      // If we're in menuTeams.php, we need the correct path
      if (
        currentPath.includes("menuTeams.php") ||
        currentPath.includes("PHP/PLAYER/")
      ) {
        apiPath = "getTeamDetails.php";
      } else if (
        currentPath.includes("index.php") ||
        currentPath === "/" ||
        currentPath.includes("TEST/")
      ) {
        apiPath = "PHP/PLAYER/getTeamDetails.php";
      }

      console.log("Current path:", currentPath);
      console.log("API path:", apiPath);
      console.log("Team ID:", teamId);

      const response = await fetch(apiPath, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ team_id: parseInt(teamId) }),
      });

      console.log("Response status:", response.status);
      console.log("Response ok:", response.ok);

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      console.log("Response data:", data);

      if (!data.success) {
        throw new Error(data.error || "Failed to fetch team data");
      }

      return data;
    } finally {
      this.isLoading = false;
    }
  }

  renderTeamDetails(data) {
    const { team, members } = data;
    const modalTitle = document.getElementById("modalTitle");
    const modalBody = document.getElementById("modalBody");

    modalTitle.textContent = team.name;

    // Function to get the correct base path
    function getBasePath() {
      const currentPath = window.location.pathname;

      // If we're in a subfolder (like PHP/PLAYER/), go up to root
      if (currentPath.includes("/PHP/")) {
        return "../../";
      } else if (currentPath.includes("/BrackIt/")) {
        // If we're at root level of BrackIt project
        return "";
      } else {
        // Default fallback
        return "";
      }
    }

    const basePath = getBasePath();
    const logoPath = `${basePath}ASSETS/LOGO_TEAM/${team.logo}`;
    const fallbackPath = `${basePath}ASSETS/LOGO.png`;

    console.log("Current path:", window.location.pathname);
    console.log("Base path:", basePath);
    console.log("Logo path:", logoPath);
    console.log("Fallback path:", fallbackPath);

    const membersHTML = members
      .map(
        (member) => `
            <div class="member-card">
                <div class="member-avatar">${member.avatar}</div>
                <div class="member-name">${member.name}</div>
                <div class="member-role">${member.role}</div>
            </div>
        `
      )
      .join("");

    modalBody.innerHTML = `
            <div class="team-info-grid">
                <div class="team-logo-section">
                    <img src="${logoPath}" 
                         alt="${team.name}" 
                         class="team-logo-large"
                         style="animation: none !important;"
                         onerror="console.log('Logo failed:', this.src); this.onerror=null; this.src='${fallbackPath}';">
                    <h3 style="margin: 20px 0 10px; color: var(--text-color); text-align: center;">
                        Rank #${team.rank}
                    </h3>
                </div>
                
                <div class="team-stats">
                    <div class="stat-card">
                        <div class="stat-label">Total Points</div>
                        <div class="stat-value">${team.points}</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-label">Wins</div>
                        <div class="stat-value">${team.wins}</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-label">Win Rate</div>
                        <div class="stat-value">${this.calculateWinRate(
                          team.wins
                        )}%</div>
                    </div>
                </div>
            </div>

            <div class="team-description">
                <div class="description-title">About ${team.name}</div>
                <div class="description-text">${team.description}</div>
            </div>

            <div class="members-section">
                <div class="section-title">Team Members</div>
                <div class="members-grid">
                    ${membersHTML}
                </div>
            </div>
        `;
  }

  calculateWinRate(wins) {
    // Simple calculation - you can modify this based on your actual data
    const totalGames = wins + Math.floor(wins * 0.3); // Assuming some losses
    return totalGames > 0 ? Math.round((wins / totalGames) * 100) : 0;
  }

  // Method to update team cards with team IDs (call this after loading teams)
  updateTeamCards(teams) {
    const teamCards = document.querySelectorAll(".team-card");
    teamCards.forEach((card, index) => {
      if (teams[index]) {
        card.setAttribute("data-team-id", teams[index].id_team);
      }
    });
  }
}

// Initialize team modal when DOM is loaded
let teamModal;
document.addEventListener("DOMContentLoaded", function () {
  teamModal = new TeamModal();

  // If teams data is available, update cards with IDs
  if (typeof teamsData !== "undefined") {
    teamModal.updateTeamCards(teamsData);
  }
});

// Export for global use
window.teamModal = teamModal;

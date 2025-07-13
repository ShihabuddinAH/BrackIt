// Team Modal JavaScript
class TeamModal {
    constructor() {
        this.modal = null;
        this.init();
    }

    init() {
        this.createModal();
        this.bindEvents();
    }

    createModal() {
        // Create modal HTML structure
        const modalHTML = `
            <div id="teamModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <div id="teamModalContent">
                        <div class="loading">Loading team details...</div>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to body if it doesn't exist
        if (!document.getElementById('teamModal')) {
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }
        
        this.modal = document.getElementById('teamModal');
    }

    bindEvents() {
        // Close modal when clicking X
        const closeBtn = document.querySelector('.close');
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
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && this.modal.style.display === 'block') {
                this.closeModal();
            }
        });
    }

    async showTeamDetails(teamId) {
        this.modal.style.display = 'block';
        
        // Show loading state
        const content = document.getElementById('teamModalContent');
        content.innerHTML = '<div class="loading">Loading team details...</div>';

        try {
            // Fetch team data
            const response = await fetch(`PHP/PLAYER/getTeamDetails.php?id=${teamId}`);
            const data = await response.json();

            if (data.success) {
                this.renderTeamDetails(data.team);
            } else {
                this.showError(data.message || 'Failed to load team details');
            }
        } catch (error) {
            console.error('Error fetching team details:', error);
            this.showError('Error loading team details. Please try again.');
        }
    }

    renderTeamDetails(team) {
        const content = document.getElementById('teamModalContent');
        
        content.innerHTML = `
            <div class="team-header">
                <img src="${team.logo || 'ASSETS/user.png'}" alt="${team.name}" class="team-logo">
                <h2 class="team-name">${team.name}</h2>
                <p class="team-description">${team.description || 'No description available'}</p>
            </div>

            <div class="team-members">
                <h3>Team Members</h3>
                <div class="member-list">
                    ${team.members.map(member => `
                        <div class="member-card">
                            <div class="member-name">${member.name}</div>
                            <div class="member-role">${member.role || 'Player'}</div>
                        </div>
                    `).join('')}
                </div>
            </div>

            <div class="team-stats">
                <div class="stat-card">
                    <div class="stat-number">${team.members.length}</div>
                    <div class="stat-label">Members</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">${team.tournaments_joined || 0}</div>
                    <div class="stat-label">Tournaments</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">${team.wins || 0}</div>
                    <div class="stat-label">Wins</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">${team.rank || 'N/A'}</div>
                    <div class="stat-label">Rank</div>
                </div>
            </div>
        `;
    }

    showError(message) {
        const content = document.getElementById('teamModalContent');
        content.innerHTML = `
            <div class="error-message">
                <h3>Error</h3>
                <p>${message}</p>
            </div>
        `;
    }

    closeModal() {
        this.modal.style.display = 'none';
    }
}

// Initialize team modal when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.teamModal = new TeamModal();

    // Add click event to team cards
    document.addEventListener('click', function(e) {
        const teamCard = e.target.closest('[data-team-id]');
        if (teamCard) {
            e.preventDefault();
            const teamId = teamCard.getAttribute('data-team-id');
            window.teamModal.showTeamDetails(teamId);
        }
    });
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TeamModal;
}

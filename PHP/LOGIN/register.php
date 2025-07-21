<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - BrackIt</title>
    <link rel="stylesheet" href="../../CSS/LOGIN/register.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Animated background pattern -->
    <div class="bg-pattern"></div>
    
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <div class="logo">
                    <img src="../../ASSETS/LOGO.png" alt="BrackIt Logo" class="logo-img">
                </div>
                <h1>Create Account</h1>
                <p>Join BrackIt and start your tournament journey</p>
            </div>

            <!-- Display Error Messages -->
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
                </div>
            <?php endif; ?>

            <!-- Display Success Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars(urldecode($_GET['success'])); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="check.php" class="register-form" id="registerForm">
                <!-- Basic Information -->
                <div class="form-section">
                    <h3>Basic Information</h3>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" id="username" name="username" placeholder="Enter your username" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Role Selection -->
                <div class="form-section">
                    <h3>Account Type</h3>
                    <div class="role-selection">
                        <div class="role-option">
                            <input type="radio" id="player" name="role" value="player" required>
                            <label for="player" class="role-card">
                                <div class="role-icon">
                                    <i class="fas fa-gamepad"></i>
                                </div>
                                <div class="role-info">
                                    <h4>Player</h4>
                                    <p>Join tournaments and compete with other players</p>
                                </div>
                            </label>
                        </div>

                        <div class="role-option">
                            <input type="radio" id="eo" name="role" value="eo" required>
                            <label for="eo" class="role-card">
                                <div class="role-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="role-info">
                                    <h4>Event Organizer</h4>
                                    <p>Create and manage tournaments</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Player Specific Fields -->
                <div class="form-section conditional-fields" id="player-fields" style="display: none;">
                    <h3>Player Information</h3>
                    
                    <div class="form-group">
                        <label for="nickname">Game Nickname</label>
                        <div class="input-group">
                            <i class="fas fa-tag"></i>
                            <input type="text" id="nickname" name="nickname" placeholder="Enter your in-game nickname">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="idGame">Game ID</label>
                        <div class="input-group">
                            <i class="fas fa-hashtag"></i>
                            <input type="text" id="idGame" name="idGame" placeholder="Enter your game ID">
                        </div>
                    </div>
                </div>

                <!-- EO Specific Fields -->
                <div class="form-section conditional-fields" id="eo-fields" style="display: none;">
                    <h3>Organization Information</h3>
                    
                    <div class="form-group">
                        <label for="organisasi">Organization Name</label>
                        <div class="input-group">
                            <i class="fas fa-building"></i>
                            <input type="text" id="organisasi" name="organisasi" placeholder="Enter your organization name">
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="form-actions">
                    <button type="submit" class="register-btn">
                        <i class="fas fa-user-plus"></i>
                        Create Account
                    </button>
                </div>

                <!-- Login Link -->
                <div class="login-link">
                    <p>Already have an account? <a href="login.php">Sign in here</a></p>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Show/hide conditional fields based on role selection
        document.addEventListener('DOMContentLoaded', function() {
            const roleInputs = document.querySelectorAll('input[name="role"]');
            const playerFields = document.getElementById('player-fields');
            const eoFields = document.getElementById('eo-fields');
            const nicknameInput = document.getElementById('nickname');
            const idGameInput = document.getElementById('idGame');
            const organisasiInput = document.getElementById('organisasi');

            roleInputs.forEach(input => {
                input.addEventListener('change', function() {
                    // Hide all conditional fields first
                    playerFields.style.display = 'none';
                    eoFields.style.display = 'none';
                    
                    // Remove required attributes
                    nicknameInput.removeAttribute('required');
                    idGameInput.removeAttribute('required');
                    organisasiInput.removeAttribute('required');
                    
                    // Show relevant fields based on selection
                    if (this.value === 'player') {
                        playerFields.style.display = 'block';
                        nicknameInput.setAttribute('required', 'required');
                        idGameInput.setAttribute('required', 'required');
                    } else if (this.value === 'eo') {
                        eoFields.style.display = 'block';
                        organisasiInput.setAttribute('required', 'required');
                    }
                });
            });

            // Form validation
            const form = document.getElementById('registerForm');
            form.addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Password and confirm password do not match!');
                    return;
                }
                
                if (password.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters long!');
                    return;
                }
            });
        });
    </script>
</body>
</html>

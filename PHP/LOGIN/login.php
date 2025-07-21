<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
    // User is already logged in, redirect to the appropriate page based on role
    if ($_SESSION['role'] === 'player') {
        header('Location: ../../index.php');
    } elseif ($_SESSION['role'] === 'eo') {
        header('Location: ../EO/dashboardEO.php');
    } elseif ($_SESSION['role'] === 'admin') {
        header('Location: ../ADMIN/dashboardAdmin.php');
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Gaming Portal</title>
    <link rel="stylesheet" href="../../CSS/LOGIN/login.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  </head>
  <body>
    <!-- Background elements -->
    <div class="bg-pattern"></div>
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>

    <!-- Main login container -->
    <div class="login-container">
      <div class="login-header">
        <div class="logo">
          <img src="../../ASSETS/LOGO.png" alt="BrackIt Logo" class="logo-img">
        </div>
        <h1>Welcome Back</h1>
        <p>Sign in to continue your tournament journey</p>
      </div>

      <!-- Display Success Messages -->
      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
          <?php echo htmlspecialchars(urldecode($_GET['success'])); ?>
        </div>
      <?php endif; ?>

      <!-- Display Error Messages -->
      <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
          <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
        </div>
      <?php endif; ?>

      <form class="login-form" action="auth.php" method="POST">
        <div class="form-group">
          <label for="role">Login sebagai</label>
          <select id="role" name="role" required>
            <option value="" disabled selected>Pilih Role</option>
            <option value="player">Player</option>
            <option value="eo">Event Organizer</option>
            <option value="admin">Admin</option>
          </select>
        </div>

        <div class="form-group">
          <label for="username">Username</label>
          <div class="input-group">
            <i class="fas fa-user"></i>
            <input
              type="text"
              id="username"
              name="username"
              placeholder="Enter your username"
              required
            />
          </div>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-group">
            <i class="fas fa-lock"></i>
            <input
              type="password"
              id="password"
              name="password"
              placeholder="Enter your password"
              required
            />
            <button type="button" class="toggle-password" onclick="togglePassword('password')">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        <div class="form-options">
          <div class="checkbox-group">
            <input type="checkbox" id="remember" name="remember" />
            <label for="remember">Remember me</label>
          </div>
          <a href="#" class="forgot-password">Forgot Password?</a>
        </div>

        <button type="submit" class="login-btn">
          <span class="btn-text">Login</span>
          <div class="loading"></div>
        </button>
      </form>

      <div class="divider">
        <span>Or continue with</span>
      </div>

      <div class="social-login">
        <a href="#" class="social-btn">
          <span>🎮</span>
          Steam
        </a>
        <a href="#" class="social-btn">
          <span>📱</span>
          Discord
        </a>
      </div>

      <div class="register-link">
        Don't have an account? <a href="register.php">Sign up here</a>
      </div>

      <!-- Demo Credentials -->
      <div
        style="
          background: rgba(255, 255, 255, 0.1);
          padding: 15px;
          margin-top: 20px;
          border-radius: 8px;
          font-size: 12px;
        "
      >
        <h4 style="margin: 0 0 10px 0; color: #ff0000">Demo Credentials:</h4>
        <div style="margin-bottom: 8px">
          <strong>Admin:</strong> Administrator / password
        </div>
        <div style="margin-bottom: 8px">
          <strong>Event Organizer:</strong> Event Organizer / password
        </div>
        <div><strong>Player:</strong> Player Demo / password</div>
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
    </script>
    <script src="../../SCRIPT/login.js"></script>
  </body>
</html>

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
        <div class="logo">BrackIt</div>
        <div class="subtitle">Gaming Portal</div>
      </div>

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
          <input
            type="text"
            id="username"
            name="username"
            placeholder="Enter your username"
            required
          />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter your password"
            required
          />
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
        Don't have an account? <a href="register.html">Sign up here</a>
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

    <script src="../../SCRIPT/login.js"></script>
  </body>
</html>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register - Gaming Portal</title>
    <link rel="stylesheet" href="../../CSS/register.css" />
  </head>
  <body>
    <!-- Background elements -->
    <div class="bg-pattern"></div>
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>

    <!-- Main register container -->
    <div class="register-container">
      <div class="register-header">
        <div class="logo">BrackIt</div>
        <div class="subtitle">Join the Gaming Community</div>
      </div>

      <form class="register-form" onsubmit="handleRegister(event)">
        <div class="form-group">
          <label for="role">Daftar sebagai</label>
          <select id="role" name="role" required>
            <option value="" disabled selected>Pilih Role</option>
            <option value="player">Player</option>
            <option value="eo">Event Organizer</option>
          </select>
        </div>

        <div class="form-group">
          <label for="username">Username</label>
          <input
            type="text"
            id="username"
            name="username"
            placeholder="Choose a unique username"
            required
            minlength="3"
            maxlength="50"
          />
          <small class="field-hint"
            >3-50 characters, letters, numbers, and underscore only</small
          >
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="Enter your email address"
            required
          />
          <small class="field-hint"
            >We'll use this for account verification</small
          >
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Create a strong password"
            required
            minlength="6"
          />
          <small class="field-hint">Minimum 6 characters</small>
        </div>

        <div class="form-group">
          <label for="confirmPassword">Confirm Password</label>
          <input
            type="password"
            id="confirmPassword"
            name="confirmPassword"
            placeholder="Confirm your password"
            required
          />
        </div>

        <div class="form-options">
          <div class="checkbox-group">
            <input type="checkbox" id="terms" name="terms" required />
            <label for="terms"
              >I agree to the
              <a href="#" class="terms-link">Terms & Conditions</a></label
            >
          </div>
        </div>

        <button type="submit" class="register-btn">
          <span class="btn-text">Create Account</span>
          <div class="loading"></div>
        </button>
      </form>

      <div class="divider">
        <span>Or register with</span>
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

      <div class="login-link">
        Already have an account? <a href="PHP/LOGIN/login.php">Sign in here</a>
      </div>
    </div>

    <script src="../../SCRIPT/register.js"></script>
  </body>
</html>

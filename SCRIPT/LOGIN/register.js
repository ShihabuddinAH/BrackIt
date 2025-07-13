// Register Form Handler

// Form validation rules
const validationRules = {
  username: {
    minLength: 3,
    maxLength: 50,
    pattern: /^[a-zA-Z0-9_]+$/,
    message:
      "Username must be 3-50 characters, letters, numbers, and underscore only",
  },
  email: {
    pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    message: "Please enter a valid email address",
  },
  password: {
    minLength: 6,
    message: "Password must be at least 6 characters long",
  },
};

// Handle form submission
async function handleRegister(event) {
  event.preventDefault();

  const formData = new FormData(event.target);
  const data = {
    role: formData.get("role"),
    username: formData.get("username").trim(),
    email: formData.get("email").trim(),
    password: formData.get("password"),
    confirmPassword: formData.get("confirmPassword"),
  };

  // Validate form
  if (!validateForm(data)) {
    return;
  }

  // Show loading state
  const submitBtn = document.querySelector(".register-btn");
  showLoading(submitBtn, true);

  try {
    const response = await fetch("PHP/register_handler.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(data),
    });

    const result = await response.json();

    if (result.success) {
      showMessage(
        "Registration successful! Redirecting to login...",
        "success"
      );
      setTimeout(() => {
        window.location.href = "PHP/LOGIN/login.php";
      }, 2000);
    } else {
      showMessage(
        result.message || "Registration failed. Please try again.",
        "error"
      );
    }
  } catch (error) {
    console.error("Registration error:", error);
    showMessage(
      "Network error. Please check your connection and try again.",
      "error"
    );
  } finally {
    showLoading(submitBtn, false);
  }
}

// Form validation
function validateForm(data) {
  let isValid = true;

  // Clear previous error messages
  clearErrorMessages();

  // Validate username
  if (!validateField("username", data.username, validationRules.username)) {
    isValid = false;
  }

  // Validate email
  if (!validateField("email", data.email, validationRules.email)) {
    isValid = false;
  }

  // Validate password
  if (!validateField("password", data.password, validationRules.password)) {
    isValid = false;
  }

  // Validate password confirmation
  if (data.password !== data.confirmPassword) {
    showFieldError("confirmPassword", "Passwords do not match");
    isValid = false;
  }

  // Validate role selection
  if (!data.role) {
    showFieldError("role", "Please select a role");
    isValid = false;
  }

  // Validate terms agreement
  const termsCheckbox = document.getElementById("terms");
  if (!termsCheckbox.checked) {
    showMessage("Please agree to the Terms & Conditions", "error");
    isValid = false;
  }

  return isValid;
}

// Validate individual field
function validateField(fieldName, value, rules) {
  const field = document.getElementById(fieldName);

  // Check required
  if (!value || value.trim() === "") {
    showFieldError(
      fieldName,
      `${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)} is required`
    );
    field.classList.add("error");
    field.classList.remove("success");
    return false;
  }

  // Check minimum length
  if (rules.minLength && value.length < rules.minLength) {
    showFieldError(fieldName, rules.message);
    field.classList.add("error");
    field.classList.remove("success");
    return false;
  }

  // Check maximum length
  if (rules.maxLength && value.length > rules.maxLength) {
    showFieldError(fieldName, rules.message);
    field.classList.add("error");
    field.classList.remove("success");
    return false;
  }

  // Check pattern
  if (rules.pattern && !rules.pattern.test(value)) {
    showFieldError(fieldName, rules.message);
    field.classList.add("error");
    field.classList.remove("success");
    return false;
  }

  // Field is valid
  field.classList.remove("error");
  field.classList.add("success");
  return true;
}

// Show field error
function showFieldError(fieldName, message) {
  const field = document.getElementById(fieldName);
  const formGroup = field.closest(".form-group");

  // Remove existing error message
  const existingError = formGroup.querySelector(".error-message");
  if (existingError) {
    existingError.remove();
  }

  // Add new error message
  const errorDiv = document.createElement("div");
  errorDiv.className = "error-message";
  errorDiv.textContent = message;
  formGroup.appendChild(errorDiv);
}

// Clear all error messages
function clearErrorMessages() {
  const errorMessages = document.querySelectorAll(".error-message");
  errorMessages.forEach((msg) => msg.remove());

  const fields = document.querySelectorAll(
    ".form-group input, .form-group select"
  );
  fields.forEach((field) => {
    field.classList.remove("error", "success");
  });
}

// Show loading state
function showLoading(button, loading) {
  if (loading) {
    button.classList.add("loading");
    button.disabled = true;
  } else {
    button.classList.remove("loading");
    button.disabled = false;
  }
}

// Show general message
function showMessage(message, type) {
  // Remove existing message
  const existingMessage = document.querySelector(".form-message");
  if (existingMessage) {
    existingMessage.remove();
  }

  // Create new message
  const messageDiv = document.createElement("div");
  messageDiv.className = `form-message ${
    type === "error" ? "error-message" : "success-message"
  }`;
  messageDiv.textContent = message;
  messageDiv.style.cssText = `
    padding: 12px;
    margin: 15px 0;
    border-radius: 8px;
    text-align: center;
    font-size: 14px;
    ${
      type === "error"
        ? "background: rgba(255, 68, 68, 0.1); color: #ff4444; border: 1px solid rgba(255, 68, 68, 0.3);"
        : "background: rgba(0, 255, 136, 0.1); color: #00ff88; border: 1px solid rgba(0, 255, 136, 0.3);"
    }
  `;

  // Insert message after form
  const form = document.querySelector(".register-form");
  form.parentNode.insertBefore(messageDiv, form.nextSibling);

  // Auto remove message after 5 seconds
  setTimeout(() => {
    if (messageDiv.parentNode) {
      messageDiv.remove();
    }
  }, 5000);
}

// Real-time validation
document.addEventListener("DOMContentLoaded", function () {
  const usernameField = document.getElementById("username");
  const emailField = document.getElementById("email");
  const passwordField = document.getElementById("password");
  const confirmPasswordField = document.getElementById("confirmPassword");

  // Username validation
  usernameField.addEventListener("blur", function () {
    if (this.value.trim()) {
      validateField("username", this.value.trim(), validationRules.username);
    }
  });

  // Email validation
  emailField.addEventListener("blur", function () {
    if (this.value.trim()) {
      validateField("email", this.value.trim(), validationRules.email);
    }
  });

  // Password validation
  passwordField.addEventListener("blur", function () {
    if (this.value) {
      validateField("password", this.value, validationRules.password);
    }
  });

  // Confirm password validation
  confirmPasswordField.addEventListener("blur", function () {
    if (this.value && passwordField.value) {
      const field = this;
      const formGroup = field.closest(".form-group");

      // Remove existing error
      const existingError = formGroup.querySelector(".error-message");
      if (existingError) {
        existingError.remove();
      }

      if (this.value !== passwordField.value) {
        showFieldError("confirmPassword", "Passwords do not match");
        field.classList.add("error");
        field.classList.remove("success");
      } else {
        field.classList.remove("error");
        field.classList.add("success");
      }
    }
  });

  // Clear validation styles on input
  const allFields = document.querySelectorAll(
    ".form-group input, .form-group select"
  );
  allFields.forEach((field) => {
    field.addEventListener("input", function () {
      this.classList.remove("error", "success");
      const formGroup = this.closest(".form-group");
      const errorMessage = formGroup.querySelector(".error-message");
      if (errorMessage) {
        errorMessage.remove();
      }
    });
  });
});

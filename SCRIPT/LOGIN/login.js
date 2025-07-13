function handleLogin(event) {
  event.preventDefault();

  const button = event.target.querySelector(".login-btn");
  const btnText = button.querySelector(".btn-text");
  const loading = button.querySelector(".loading");

  // Get form data
  const formData = new FormData(event.target);
  const role = formData.get("role");
  const username = formData.get("username");
  const password = formData.get("password");

  // Validate form
  if (!role || !username || !password) {
    alert("Semua field harus diisi!");
    return;
  }

  // Show loading state
  btnText.style.opacity = "0";
  loading.style.display = "block";
  button.disabled = true;

  // Send login request to server
  fetch("PHP/login_handler.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      username: username,
      password: password,
      role: role,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      // Reset button state
      btnText.style.opacity = "1";
      loading.style.display = "none";
      button.disabled = false;

      if (data.success) {
        // Store login information
        localStorage.setItem("isLoggedIn", "true");
        localStorage.setItem("username", data.user.nama);
        localStorage.setItem("userEmail", data.user.email);
        localStorage.setItem("userId", data.user.id);
        localStorage.setItem("userRole", data.user.role);

        // Show success message
        alert(data.message);

        // Role-based redirect
        switch (data.user.role) {
          case "eo":
            window.location.href = "dashboardEO.html";
            break;
          case "admin":
            window.location.href = "dashboardAdmin.html";
            break;
          case "player":
          default:
            window.location.href = "index.html";
            break;
        }
      } else {
        // Show error message
        alert("Login gagal: " + data.message);
      }
    })
    .catch((error) => {
      // Reset button state
      btnText.style.opacity = "1";
      loading.style.display = "none";
      button.disabled = false;

      console.error("Error:", error);
      alert("Terjadi kesalahan koneksi. Silakan coba lagi.");
    });
}

// Add some interactive effects
document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".form-group input").forEach((input) => {
    input.addEventListener("focus", function () {
      this.parentElement.style.transform = "translateY(-2px)";
    });

    input.addEventListener("blur", function () {
      this.parentElement.style.transform = "translateY(0)";
    });
  });
});

// Add particle effect on click
document.addEventListener("click", function (e) {
  if (e.target.classList.contains("login-btn")) {
    createParticles(e.clientX, e.clientY);
  }
});

function createParticles(x, y) {
  for (let i = 0; i < 10; i++) {
    const particle = document.createElement("div");
    particle.style.position = "fixed";
    particle.style.left = x + "px";
    particle.style.top = y + "px";
    particle.style.width = "4px";
    particle.style.height = "4px";
    particle.style.background = "#dc261b";
    particle.style.borderRadius = "50%";
    particle.style.pointerEvents = "none";
    particle.style.zIndex = "1000";

    document.body.appendChild(particle);

    const angle = (Math.PI * 2 * i) / 10;
    const velocity = 100;
    const vx = Math.cos(angle) * velocity;
    const vy = Math.sin(angle) * velocity;

    let opacity = 1;
    let posX = x;
    let posY = y;

    const animate = () => {
      opacity -= 0.02;
      if (opacity <= 0) {
        document.body.removeChild(particle);
        return;
      }

      posX += vx * 0.02;
      posY += vy * 0.02;

      particle.style.left = posX + "px";
      particle.style.top = posY + "px";
      particle.style.opacity = opacity;

      requestAnimationFrame(animate);
    };

    animate();
  }
}

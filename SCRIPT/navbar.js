// Navbar functionality for all pages

document.addEventListener("DOMContentLoaded", function () {
  const hamburgerMenu = document.getElementById("hamburgerMenu");
  const nav = document.querySelector(".nav");
  const navLinks = document.querySelectorAll(".nav-menu a");
  const header = document.querySelector("header");

  // Handle user dropdown functionality
  const userInfo = document.getElementById("userInfo");
  const logoutBtn = document.getElementById("logoutBtn");
  const loginText = document.getElementById("loginText");

  // Initialize dropdown state
  if (userInfo) {
    const dropdown = userInfo.querySelector(".user-dropdown");
    if (dropdown) {
      // Ensure dropdown starts hidden
      dropdown.style.display = "none";
    }
  }

  // User dropdown functionality (for logged in users)
  if (userInfo) {
    userInfo.addEventListener("click", function (e) {
      e.stopPropagation();
      const dropdown = userInfo.querySelector(".user-dropdown");
      if (dropdown) {
        // Toggle dropdown visibility
        if (dropdown.style.display === "block") {
          dropdown.style.display = "none";
        } else {
          dropdown.style.display = "block";
        }
      }
    });

    // Close dropdown when clicking outside
    document.addEventListener("click", function (e) {
      const dropdown = userInfo.querySelector(".user-dropdown");
      if (dropdown && !userInfo.contains(e.target)) {
        dropdown.style.display = "none";
      }
    });

    // Prevent dropdown from closing when clicking inside it
    const dropdown = userInfo.querySelector(".user-dropdown");
    if (dropdown) {
      dropdown.addEventListener("click", function (e) {
        e.stopPropagation();
      });
    }
  }

  // Login text functionality (for non-logged in users)
  if (loginText) {
    loginText.addEventListener("click", function (e) {
      e.preventDefault();
      window.location.href = "PHP/LOGIN/login.php";
    });
  }

  // Logout button functionality
  if (logoutBtn) {
    logoutBtn.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      // Close dropdown first
      const dropdown = userInfo
        ? userInfo.querySelector(".user-dropdown")
        : null;
      if (dropdown) {
        dropdown.style.display = "none";
      }

      // Confirm logout
      if (confirm("Apakah Anda yakin ingin logout?")) {
        logout();
      }
    });
  }

  function logout() {
    // Send logout request to server
    fetch("PHP/LOGIN/logout.php", {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert("Logout berhasil!");
          // Reload page to update the display
          window.location.reload();
        }
      })
      .catch((error) => {
        console.error("Logout error:", error);
        // Reload page to update display
        window.location.reload();
      });
  }

  if (hamburgerMenu) {
    hamburgerMenu.addEventListener("click", function () {
      hamburgerMenu.classList.toggle("active");
      nav.classList.toggle("active");
      if (nav.classList.contains("active")) {
        document.body.style.overflow = "hidden";
      } else {
        document.body.style.overflow = "auto";
      }
    });
  }

  navLinks.forEach((link) => {
    link.addEventListener("click", function () {
      if (hamburgerMenu) hamburgerMenu.classList.remove("active");
      nav.classList.remove("active");
      document.body.style.overflow = "auto";
    });
  });

  document.addEventListener("click", function (e) {
    if (
      !nav.contains(e.target) &&
      (!hamburgerMenu || !hamburgerMenu.contains(e.target))
    ) {
      if (hamburgerMenu) hamburgerMenu.classList.remove("active");
      nav.classList.remove("active");
      document.body.style.overflow = "auto";
    }
  });

  window.addEventListener("resize", function () {
    if (window.innerWidth > 768) {
      if (hamburgerMenu) hamburgerMenu.classList.remove("active");
      nav.classList.remove("active");
      document.body.style.overflow = "auto";
    }
  });

  function updateHeaderOnScroll() {
    if (!header) return;
    if (window.scrollY > 10) {
      header.style.background = "rgba(0,0,0,0.92)";
      header.style.boxShadow = "0 2px 16px 0 rgba(0,0,0,0.18)";
      header.style.backdropFilter = "blur(10px)";
      header.style.borderBottom = "1px solid rgba(255,0,0,0.18)";
    } else {
      header.style.background = "transparent";
      header.style.boxShadow = "none";
      header.style.backdropFilter = "none";
      header.style.borderBottom = "none";
    }
  }
  window.addEventListener("scroll", updateHeaderOnScroll);
  updateHeaderOnScroll();
});

// Navbar functionality for all pages

document.addEventListener("DOMContentLoaded", function () {
  // Prevent multiple initialization
  if (window.navbarInitialized) {
    return;
  }
  window.navbarInitialized = true;

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

      // Determine the correct path to login based on current location
      const currentPath = window.location.pathname;
      let loginPath = "PHP/LOGIN/login.php";

      if (currentPath.includes("/PHP/PLAYER/")) {
        loginPath = "../../PHP/LOGIN/login.php";
      } else if (currentPath.includes("/PHP/")) {
        loginPath = "../LOGIN/login.php";
      }

      window.location.href = loginPath;
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
    // Determine the correct path to logout based on current location
    const currentPath = window.location.pathname;
    let logoutPath = "PHP/LOGIN/logout.php";

    if (currentPath.includes("/PHP/PLAYER/")) {
      logoutPath = "../../PHP/LOGIN/logout.php";
    } else if (currentPath.includes("/PHP/")) {
      logoutPath = "../LOGIN/logout.php";
    }

    // Send logout request to server
    fetch(logoutPath, {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "Content-Type": "application/json",
      },
    })
      .then((response) => {
        // Check if response is ok
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        if (data.success) {
          alert("Logout berhasil!");
          // Always redirect to index page
          redirectToIndex(currentPath);
        } else {
          throw new Error(data.message || "Logout failed");
        }
      })
      .catch((error) => {
        console.error("Logout error:", error);
        alert(
          "Terjadi kesalahan saat logout. Anda akan diarahkan ke halaman utama."
        );
        // Always redirect to index page on error
        redirectToIndex(currentPath);
      });
  }

  // Helper function to determine correct redirect path
  function redirectToIndex(currentPath) {
    if (currentPath.includes("/PHP/")) {
      window.location.href = "../../index.php";
    } else {
      window.location.href = "index.php";
    }
  }

  // Hamburger menu functionality
  if (hamburgerMenu && nav) {
    hamburgerMenu.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      hamburgerMenu.classList.toggle("active");
      nav.classList.toggle("active");

      if (nav.classList.contains("active")) {
        document.body.style.overflow = "hidden";
      } else {
        document.body.style.overflow = "auto";
      }
    });
  }

  // Navigation links functionality
  if (navLinks.length > 0) {
    navLinks.forEach((link) => {
      link.addEventListener("click", function () {
        // Close mobile menu when link is clicked
        if (hamburgerMenu) hamburgerMenu.classList.remove("active");
        if (nav) nav.classList.remove("active");
        document.body.style.overflow = "auto";
      });
    });
  }

  // Close mobile menu when clicking outside
  document.addEventListener("click", function (e) {
    if (
      nav &&
      hamburgerMenu &&
      !nav.contains(e.target) &&
      !hamburgerMenu.contains(e.target)
    ) {
      hamburgerMenu.classList.remove("active");
      nav.classList.remove("active");
      document.body.style.overflow = "auto";
    }
  });

  // Close mobile menu on window resize
  window.addEventListener("resize", function () {
    if (window.innerWidth > 768) {
      if (hamburgerMenu) hamburgerMenu.classList.remove("active");
      if (nav) nav.classList.remove("active");
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

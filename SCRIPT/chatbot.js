document.addEventListener("DOMContentLoaded", function () {
  const chatbotButton = document.getElementById("chatbotButton");
  const chatbotWindow = document.getElementById("chatbotWindow");
  const chatbotClose = document.getElementById("chatbotClose");
  const chatbotInput = document.getElementById("chatbotInput");
  const chatbotSend = document.getElementById("chatbotSend");
  const chatbotMessages = document.getElementById("chatbotMessages");

  // Toggle chatbot window
  chatbotButton.addEventListener("click", function () {
    if (
      chatbotWindow.style.display === "none" ||
      chatbotWindow.style.display === ""
    ) {
      chatbotWindow.style.display = "flex";
      chatbotInput.focus();
    } else {
      chatbotWindow.style.display = "none";
    }
  });

  // Close chatbot window
  chatbotClose.addEventListener("click", function () {
    chatbotWindow.style.display = "none";
  });

  // Send message function
  function sendMessage() {
    const message = chatbotInput.value.trim();
    if (message === "") return;

    // Add user message
    addMessage(message, "user");
    chatbotInput.value = "";

    // Show typing indicator
    showTypingIndicator();

    // Send message to Groq AI with Database Context
    fetch("PHP/AI/groq_chat_with_db.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ message: message }),
    })
      .then((response) => response.json())
      .then((data) => {
        hideTypingIndicator();
        if (data.response) {
          addMessage(data.response, "bot");
        } else if (data.error) {
          addMessage("Maaf, terjadi kesalahan: " + data.error, "bot");
        } else {
          addMessage(
            "Maaf, saya tidak dapat memproses permintaan Anda saat ini.",
            "bot"
          );
        }
      })
      .catch((error) => {
        hideTypingIndicator();
        console.error("Error:", error);
        addMessage(
          "Maaf, koneksi ke server bermasalah. Silakan coba lagi nanti.",
          "bot"
        );
      });
  }

  // Add message to chat
  function addMessage(text, sender) {
    const messageDiv = document.createElement("div");
    messageDiv.className = `message ${sender}-message`;

    // Handle HTML content for bot messages (for line breaks, etc.)
    if (sender === "bot") {
      messageDiv.innerHTML = `<p>${text.replace(/\n/g, "<br>")}</p>`;
    } else {
      messageDiv.innerHTML = `<p>${text}</p>`;
    }

    chatbotMessages.appendChild(messageDiv);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
  }

  // Show typing indicator
  function showTypingIndicator() {
    const typingDiv = document.createElement("div");
    typingDiv.className = "message bot-message typing-indicator";
    typingDiv.id = "typingIndicator";
    typingDiv.innerHTML = "<p>Sedang mengetik...</p>";

    chatbotMessages.appendChild(typingDiv);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
  }

  // Hide typing indicator
  function hideTypingIndicator() {
    const typingIndicator = document.getElementById("typingIndicator");
    if (typingIndicator) {
      typingIndicator.remove();
    }
  }

  // Simple bot responses
  function getBotResponse(userMessage) {
    const message = userMessage.toLowerCase();

    if (
      message.includes("halo") ||
      message.includes("hai") ||
      message.includes("hello") ||
      message.includes("hi")
    ) {
      return "Halo! Selamat datang di BrackIt. Ada yang bisa saya bantu hari ini?";
    } else if (message.includes("tournament") || message.includes("turnamen")) {
      return "BrackIt memiliki berbagai tournament gaming seperti Mobile Legends, PUBG, dan Free Fire. Apakah Anda ingin tahu lebih lanjut tentang tournament yang tersedia?";
    } else if (message.includes("team") || message.includes("tim")) {
      return "Anda bisa melihat daftar tim di section TEAMS atau mendaftarkan tim baru. Butuh bantuan mendaftar tim atau mencari informasi tim tertentu?";
    } else if (
      message.includes("klasemen") ||
      message.includes("ranking") ||
      message.includes("leaderboard")
    ) {
      return "Klasemen menampilkan 3 tim teratas berdasarkan point. Tim dengan performa terbaik akan muncul di bagian atas. Ingin tahu cara meningkatkan ranking tim Anda?";
    } else if (
      message.includes("daftar") ||
      message.includes("register") ||
      message.includes("signup")
    ) {
      return "Untuk mendaftar, silakan klik tombol Login di pojok kanan atas dan buat akun baru. Setelah itu Anda bisa mendaftarkan tim dan ikut tournament!";
    } else if (
      message.includes("kontak") ||
      message.includes("contact") ||
      message.includes("hubungi")
    ) {
      return "Anda bisa menghubungi kami melalui:<br>📧 Email: info@brackit.com<br>📱 Telepon: +62 812-3456-7890<br>📍 Alamat: Jl. Teknologi No. 123, Jakarta";
    } else if (message.includes("cara") && message.includes("main")) {
      return "Untuk bermain di BrackIt: 1) Daftar akun, 2) Buat atau gabung tim, 3) Daftar tournament, 4) Ikuti pertandingan sesuai jadwal. Mudah bukan?";
    } else if (message.includes("jadwal") || message.includes("schedule")) {
      return "Jadwal tournament bisa dilihat setelah Anda login dan mendaftar tournament. Setiap tournament memiliki jadwal yang berbeda-beda.";
    } else if (message.includes("prize") || message.includes("hadiah")) {
      return "Setiap tournament memiliki hadiah yang berbeda-beda. Informasi lengkap hadiah akan diberikan saat pendaftaran tournament.";
    } else if (message.includes("mobile legends") || message.includes("ml")) {
      return "Mobile Legends Championship adalah salah satu tournament paling populer di BrackIt! Tim-tim terbaik berkompetisi untuk menjadi yang teratas.";
    } else if (message.includes("pubg")) {
      return "PUBG Tournament di BrackIt menawarkan gameplay yang intens dan kompetitif. Siapkan strategi tim terbaik Anda!";
    } else if (message.includes("free fire") || message.includes("ff")) {
      return "Free Fire Championship adalah tournament yang cepat dan menegangkan. Cocok untuk tim yang suka aksi cepat!";
    } else if (
      message.includes("terima kasih") ||
      message.includes("thanks") ||
      message.includes("makasih")
    ) {
      return "Sama-sama! Senang bisa membantu Anda. Jangan ragu untuk bertanya lagi jika ada yang ingin ditanyakan tentang BrackIt! 😊";
    } else if (message.includes("bantuan") || message.includes("help")) {
      return "Saya bisa membantu Anda dengan informasi tentang:<br>• Tournament dan cara daftar<br>• Tim dan klasemen<br>• Kontak dan support<br>• Cara bermain di BrackIt<br><br>Silakan tanyakan apa yang ingin Anda ketahui!";
    } else {
      return "Maaf, saya belum memahami pertanyaan Anda. Bisa Anda tanyakan tentang tournament, team, klasemen, cara daftar, atau hal lain seputar BrackIt?";
    }
  }

  // Send message on button click
  chatbotSend.addEventListener("click", sendMessage);

  // Send message on Enter key
  chatbotInput.addEventListener("keypress", function (e) {
    if (e.key === "Enter") {
      sendMessage();
    }
  });

  // Close chatbot when clicking outside
  document.addEventListener("click", function (e) {
    if (
      !chatbotButton.contains(e.target) &&
      !chatbotWindow.contains(e.target)
    ) {
      if (chatbotWindow.style.display === "flex") {
        // Don't close immediately, add small delay to prevent accidental closes
      }
    }
  });

  // Prevent chatbot window from closing when clicking inside it
  chatbotWindow.addEventListener("click", function (e) {
    e.stopPropagation();
  });
});

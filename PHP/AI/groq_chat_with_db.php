<?php
require_once 'config.php';
require_once 'chatbot_logger.php';

// Database connection using config
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        logChatbotError('Database Connection Failed', ['error' => $conn->connect_error]);
        // Continue without database features
        $conn = null;
    }
} catch (Exception $e) {
    logChatbotError('Database Connection Exception', ['error' => $e->getMessage()]);
    $conn = null;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Message is required']);
    exit;
}

$userMessage = trim($input['message']);

if (empty($userMessage)) {
    http_response_code(400);
    echo json_encode(['error' => 'Message cannot be empty']);
    exit;
}

// Use configuration constants
$apiKey = GROQ_API_KEY;
$apiUrl = GROQ_API_URL;

// Validate API key
if (empty($apiKey)) {
    logChatbotError('Missing API Key', ['message' => 'GROQ_API_KEY not configured']);
    http_response_code(500);
    echo json_encode(['error' => 'Chatbot configuration error. Please contact administrator.']);
    exit;
}

// Debug: Log the request for testing
if (APP_DEBUG) {
    error_log("Groq Chat Request: " . $userMessage);
    error_log("API Key Status: " . (empty($apiKey) ? 'EMPTY' : 'SET'));
    error_log("Database Status: " . ($conn ? 'CONNECTED' : 'NOT CONNECTED'));
}

// Function to get database context based on user question
function getDatabaseContext($userMessage, $conn) {
    $context = "";
    $message = strtolower($userMessage);
    
    // Check if database connection is available
    if (!$conn) {
        return "";
    }
    
    // Get comprehensive training data from all tables
    $trainingData = getTrainingData($conn);
    
    // Determine what specific information to include based on user query
    $includeAll = false;
    
    // Keywords that suggest user wants general information
    $generalKeywords = ['brackit', 'platform', 'apa itu', 'gimana', 'bagaimana', 'info', 'bantuan', 'help'];
    foreach ($generalKeywords as $keyword) {
        if (strpos($message, $keyword) !== false) {
            $includeAll = true;
            break;
        }
    }
    
    // If user asks about teams
    if (strpos($message, 'tim') !== false || strpos($message, 'team') !== false || $includeAll) {
        $context .= $trainingData['teams'];
    }
    
    // If user asks about klasemen/ranking
    if (strpos($message, 'klasemen') !== false || strpos($message, 'ranking') !== false || strpos($message, 'peringkat') !== false || $includeAll) {
        $context .= $trainingData['rankings'];
    }
    
    // If user asks about tournaments
    if (strpos($message, 'turnamen') !== false || strpos($message, 'tournament') !== false || strpos($message, 'kompetisi') !== false || $includeAll) {
        $context .= $trainingData['tournaments'];
    }
    
    // If user asks about players
    if (strpos($message, 'player') !== false || strpos($message, 'pemain') !== false || $includeAll) {
        $context .= $trainingData['players'];
    }
    
    // If user asks about statistics
    if (strpos($message, 'statistik') !== false || strpos($message, 'data') !== false || strpos($message, 'jumlah') !== false || $includeAll) {
        $context .= $trainingData['statistics'];
    }
    
    // If user asks about specific team
    if (preg_match('/tim (.+?)(?:\s|$)/i', $message, $matches) || preg_match('/team (.+?)(?:\s|$)/i', $message, $matches)) {
        try {
            $teamName = trim($matches[1]);
            $query = "SELECT t.*, p.username as leader_name, p.nickname as leader_nickname
                      FROM team t 
                      LEFT JOIN player p ON t.id_leader = p.id_player 
                      WHERE t.nama_team LIKE ? LIMIT 1";
            $stmt = $conn->prepare($query);
            $searchTerm = "%{$teamName}%";
            $stmt->bind_param("s", $searchTerm);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $team = $result->fetch_assoc();
                $context .= "\nDETAIL TIM {$team['nama_team']}:\n";
                $context .= "- Leader: {$team['leader_nickname']} ({$team['leader_name']})\n";
                $context .= "- Poin: {$team['point']}\n";
                $context .= "- Menang: {$team['win']}, Kalah: {$team['lose']}\n";
                $context .= "- Total Match: {$team['total_match']}\n";
                $context .= "- Deskripsi: {$team['deskripsi_team']}\n";
                
                // Get team members
                $memberQuery = "SELECT p.username, p.nickname FROM team_player tp 
                               JOIN player p ON tp.id_player = p.id_player 
                               WHERE tp.id_team = ?";
                $memberStmt = $conn->prepare($memberQuery);
                $memberStmt->bind_param("i", $team['id_team']);
                $memberStmt->execute();
                $memberResult = $memberStmt->get_result();
                
                if ($memberResult && $memberResult->num_rows > 0) {
                    $context .= "- Anggota Tim: ";
                    $members = [];
                    while ($member = $memberResult->fetch_assoc()) {
                        $members[] = $member['nickname'] . " (" . $member['username'] . ")";
                    }
                    $context .= implode(", ", $members) . "\n";
                }
                $memberStmt->close();
            }
            $stmt->close();
        } catch (Exception $e) {
            logChatbotError('Database Query Error - Specific Team', ['error' => $e->getMessage()]);
        }
    }
    
    return $context;
}

// Function to get comprehensive training data from all tables
function getTrainingData($conn) {
    $trainingData = [
        'teams' => '',
        'rankings' => '',
        'tournaments' => '',
        'players' => '',
        'statistics' => ''
    ];
    
    try {
        // Get all teams data
        $query = "SELECT t.nama_team, t.point, t.win, t.lose, t.total_match, t.deskripsi_team, 
                         p.username as leader_name
                  FROM team t 
                  LEFT JOIN player p ON t.id_leader = p.id_player 
                  ORDER BY t.point DESC";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $trainingData['teams'] .= "\nDATA SEMUA TIM:\n";
            $rank = 1;
            while ($row = $result->fetch_assoc()) {
                $trainingData['teams'] .= "- {$rank}. {$row['nama_team']}: {$row['point']} poin, {$row['win']} menang, {$row['lose']} kalah\n";
                $trainingData['teams'] .= "  Leader: {$row['leader_name']}, Deskripsi: {$row['deskripsi_team']}\n";
                $rank++;
            }
            
            // Create rankings data
            $trainingData['rankings'] = "\nKLASEMEN LENGKAP:\n" . $trainingData['teams'];
        }
        
        // Get all tournaments data
        $query = "SELECT t.nama_turnamen, t.deskripsi_turnamen, t.format, t.status, 
                         t.biaya_turnamen, t.hadiah_turnamen, t.slot, t.pendaftar,
                         t.pendaftaran_mulai, t.pendaftaran_selesai, t.tanggal_mulai,
                         e.username as eo_name, e.organisasi
                  FROM turnamen t 
                  LEFT JOIN eo e ON t.id_eo = e.id_eo 
                  ORDER BY t.tanggal_mulai ASC";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $trainingData['tournaments'] .= "\nDATA SEMUA TURNAMEN:\n";
            while ($row = $result->fetch_assoc()) {
                $trainingData['tournaments'] .= "- {$row['nama_turnamen']} ({$row['format']})\n";
                $trainingData['tournaments'] .= "  Status: {$row['status']}, Biaya: Rp" . number_format($row['biaya_turnamen']) . "\n";
                $trainingData['tournaments'] .= "  Hadiah: Rp" . number_format($row['hadiah_turnamen']) . ", Slot: {$row['slot']}\n";
                $trainingData['tournaments'] .= "  Penyelenggara: {$row['eo_name']} ({$row['organisasi']})\n";
                $trainingData['tournaments'] .= "  Deskripsi: {$row['deskripsi_turnamen']}\n\n";
            }
        }
        
        // Get active players statistics
        $query = "SELECT COUNT(*) as total_players, 
                         SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_players,
                         SUM(win) as total_wins, SUM(lose) as total_losses
                  FROM player";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $stats = $result->fetch_assoc();
            $trainingData['players'] .= "\nSTATISTIK PEMAIN:\n";
            $trainingData['players'] .= "- Total Pemain: {$stats['total_players']}\n";
            $trainingData['players'] .= "- Pemain Aktif: {$stats['active_players']}\n";
            $trainingData['players'] .= "- Total Kemenangan: {$stats['total_wins']}\n";
            $trainingData['players'] .= "- Total Kekalahan: {$stats['total_losses']}\n";
        }
        
        // Get top performing players
        $query = "SELECT username, nickname, win, lose, total_match 
                  FROM player 
                  WHERE win > 0 
                  ORDER BY win DESC, total_match DESC 
                  LIMIT 5";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $trainingData['players'] .= "\nTOP 5 PEMAIN TERBAIK:\n";
            $rank = 1;
            while ($row = $result->fetch_assoc()) {
                $trainingData['players'] .= "- {$rank}. {$row['nickname']} ({$row['username']}): {$row['win']} menang, {$row['lose']} kalah\n";
                $rank++;
            }
        }
        
        // Get party/team statistics
        $query = "SELECT COUNT(*) as total_parties FROM party";
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            $stats = $result->fetch_assoc();
            $trainingData['statistics'] .= "\nSTATISTIK PARTY:\n";
            $trainingData['statistics'] .= "- Total Party: {$stats['total_parties']}\n";
        }
        
        // Get tournament statistics by status
        $query = "SELECT status, COUNT(*) as count FROM turnamen GROUP BY status";
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            $trainingData['statistics'] .= "\nSTATISTIK TURNAMEN:\n";
            while ($row = $result->fetch_assoc()) {
                $trainingData['statistics'] .= "- {$row['status']}: {$row['count']} turnamen\n";
            }
        }
        
    } catch (Exception $e) {
        logChatbotError('Database Training Data Error', ['error' => $e->getMessage()]);
    }
    
    return $trainingData;
}

// Get database context if relevant
$databaseContext = getDatabaseContext($userMessage, $conn);

// System prompt untuk BrackIt Assistant dengan database context
$systemPrompt = "Anda adalah BrackIt Assistant, asisten virtual untuk platform turnamen gaming BrackIt. 

INFORMASI TENTANG BRACKIT:
- BrackIt adalah platform manajemen turnamen gaming
- Turnamen yang tersedia: Mobile Legends dengan format tim dan individu
- Users bisa mendaftar akun, membuat tim, dan ikut turnamen
- Ada system klasemen berdasarkan poin
- Kontak: info@brackit.com, +62 812-3456-7890, Jl. Teknologi No. 123 Jakarta

DATA REAL-TIME DARI DATABASE:
{$databaseContext}

FITUR YANG TERSEDIA DI BRACKIT:
1. SISTEM TIM:
   - Membuat tim dengan 5 anggota
   - Invite sistem untuk mengundang pemain
   - Leader bisa mengatur anggota tim
   - Klasemen berdasarkan point dan win rate

2. SISTEM TURNAMEN:
   - Format tim (5v5) dan individu (1v1)
   - Biaya pendaftaran bervariasi
   - Hadiah menarik untuk pemenang
   - Sistem bracket eliminasi

3. SISTEM PARTY:
   - Party untuk bermain casual
   - Invite sistem untuk mengundang teman
   - Tidak ada kompetisi resmi

4. DASHBOARD ROLES:
   - Player: Dapat join tim, party, daftar turnamen
   - EO (Event Organizer): Dapat membuat dan mengelola turnamen
   - Admin: Mengelola seluruh sistem

CARA MENJAWAB:
- Gunakan bahasa Indonesia yang ramah dan profesional
- Berikan informasi yang akurat berdasarkan data real dari database
- Jika ada data dari database, prioritaskan informasi tersebut
- Jika ditanya hal di luar BrackIt, arahkan kembali ke topik BrackIt
- Jawaban maksimal 3-4 kalimat, informatif namun ringkas
- Gunakan emoji yang sesuai untuk membuat percakapan lebih menarik
- Berikan saran actionable jika memungkinkan

CONTOH RESPONSE BERDASARKAN DATA:
- Tentang turnamen: 'Saat ini ada [jumlah] turnamen tersedia! Status aktif: [nama turnamen]. 🎮 Mau tahu detail turnamen mana?'
- Tentang tim terbaik: 'Tim terkuat saat ini adalah [nama tim] dengan [point] poin! � Mau lihat statistik lengkapnya?'
- Tentang pendaftaran: 'Klik Login di pojok kanan atas, daftar akun, lalu buat/join tim untuk ikut turnamen! 🚀'
- Tentang klasemen: 'Berdasarkan data terkini: 1. [tim] ([point] poin), 2. [tim] ([point] poin)... 📊'";

// Get all training data for better AI context
$trainingData = $conn ? getTrainingData($conn) : [];
$fullTrainingContext = "";
if (!empty($trainingData)) {
    $fullTrainingContext = implode("\n", $trainingData);
}

// Prepare the request data with comprehensive training context
$messages = [
    ['role' => 'system', 'content' => $systemPrompt]
];

// Add training data as system context if available
if (!empty($fullTrainingContext)) {
    $messages[] = [
        'role' => 'system', 
        'content' => "KONTEKS DATA LENGKAP BRACKIT:\n" . $fullTrainingContext
    ];
}

// Add user message
$messages[] = ['role' => 'user', 'content' => $userMessage];

$requestData = [
    'model' => CHATBOT_MODEL,
    'messages' => $messages,
    'max_tokens' => CHATBOT_MAX_TOKENS,
    'temperature' => CHATBOT_TEMPERATURE
];

// Initialize cURL
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_TIMEOUT, CHATBOT_TIMEOUT);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Handle cURL errors
if ($curlError) {
    logChatbotError('cURL Error', ['error' => $curlError, 'user_message' => $userMessage]);
    http_response_code(500);
    echo json_encode(['error' => 'Connection error: ' . $curlError]);
    exit;
}

// Handle HTTP errors
if ($httpCode !== 200) {
    $errorDetails = [
        'status_code' => $httpCode, 
        'user_message' => $userMessage,
        'response_body' => $response,
        'api_url' => $apiUrl
    ];
    
    logChatbotError('HTTP Error', $errorDetails);
    
    // Provide user-friendly error messages
    $errorMessage = 'Maaf, chatbot sedang mengalami gangguan. Silakan coba lagi nanti.';
    if ($httpCode === 401) {
        $errorMessage = 'API key tidak valid. Silakan hubungi administrator.';
    } elseif ($httpCode === 429) {
        $errorMessage = 'Terlalu banyak permintaan. Silakan tunggu sebentar.';
    }
    
    http_response_code(500);
    echo json_encode(['error' => $errorMessage]);
    exit;
}

// Parse the response
$responseData = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid JSON response from API']);
    exit;
}

// Extract the AI response
if (isset($responseData['choices'][0]['message']['content'])) {
    $aiResponse = trim($responseData['choices'][0]['message']['content']);
    
    // Log successful interaction
    logChatbotInteraction($userMessage, $aiResponse);
    
    echo json_encode(['response' => $aiResponse]);
} else {
    logChatbotError('No response content', ['response_data' => $responseData, 'user_message' => $userMessage]);
    http_response_code(500);
    echo json_encode(['error' => 'No response content from AI']);
}
?>

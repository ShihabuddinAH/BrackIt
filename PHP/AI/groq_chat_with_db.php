<?php
require_once 'config.php';
require_once 'chatbot_logger.php';
require_once '../connect.php'; // Database connection

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

// Debug: Log the request for testing
if (APP_DEBUG) {
    error_log("Groq Chat Request: " . $userMessage);
}

// Function to get database context based on user question
function getDatabaseContext($userMessage, $conn) {
    $context = "";
    $message = strtolower($userMessage);
    
    // If user asks about teams
    if (strpos($message, 'tim') !== false || strpos($message, 'team') !== false) {
        $query = "SELECT nama_team, point, win FROM team ORDER BY point DESC LIMIT 5";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $context .= "\nDATA TIM TERKINI:\n";
            $rank = 1;
            while ($row = $result->fetch_assoc()) {
                $context .= "- {$rank}. {$row['nama_team']}: {$row['point']} poin, {$row['win']} menang\n";
                $rank++;
            }
        }
    }
    
    // If user asks about klasemen/ranking
    if (strpos($message, 'klasemen') !== false || strpos($message, 'ranking') !== false || strpos($message, 'peringkat') !== false) {
        $query = "SELECT nama_team, point, win FROM team ORDER BY point DESC LIMIT 3";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $context .= "\nKLASEMEN TOP 3:\n";
            $rank = 1;
            while ($row = $result->fetch_assoc()) {
                $context .= "- Peringkat {$rank}: {$row['nama_team']} ({$row['point']} poin, {$row['win']} menang)\n";
                $rank++;
            }
        }
    }
    
    // If user asks about specific team
    if (preg_match('/tim (.+?)(?:\s|$)/i', $message, $matches) || preg_match('/team (.+?)(?:\s|$)/i', $message, $matches)) {
        $teamName = trim($matches[1]);
        $query = "SELECT * FROM team WHERE nama_team LIKE ? LIMIT 1";
        $stmt = $conn->prepare($query);
        $searchTerm = "%{$teamName}%";
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $team = $result->fetch_assoc();
            $context .= "\nINFO TIM {$team['nama_team']}:\n";
            $context .= "- Poin: {$team['point']}\n";
            $context .= "- Menang: {$team['win']}\n";
            $context .= "- Deskripsi: {$team['deskripsi_team']}\n";
        }
    }
    
    return $context;
}

// Get database context if relevant
$databaseContext = getDatabaseContext($userMessage, $conn);

// System prompt untuk BrackIt Assistant dengan database context
$systemPrompt = "Anda adalah BrackIt Assistant, asisten virtual untuk platform turnamen gaming BrackIt. 

INFORMASI TENTANG BRACKIT:
- BrackIt adalah platform manajemen turnamen gaming
- Turnamen yang tersedia: Mobile Legends, PUBG, Free Fire, Unisi Cup
- Users bisa mendaftar akun, membuat tim, dan ikut turnamen
- Ada system klasemen berdasarkan poin
- Kontak: info@brackit.com, +62 812-3456-7890, Jl. Teknologi No. 123 Jakarta

{$databaseContext}

CARA MENJAWAB:
- Gunakan bahasa Indonesia yang ramah dan profesional
- Berikan informasi yang akurat tentang BrackIt
- Jika ada data dari database, gunakan data tersebut untuk memberikan informasi yang lebih spesifik
- Jika ditanya hal di luar BrackIt, arahkan kembali ke topik BrackIt
- Jawaban maksimal 2-3 kalimat, singkat dan jelas
- Gunakan emoji yang sesuai untuk membuat percakapan lebih menarik

CONTOH RESPONSE:
- Tentang turnamen: 'BrackIt memiliki turnamen Mobile Legends, PUBG, dan Free Fire! 🎮 Mau ikut turnamen mana?'
- Tentang pendaftaran: 'Klik tombol Login di pojok kanan atas untuk daftar akun, lalu buat tim dan ikut turnamen! 🚀'
- Tentang klasemen: 'Berdasarkan data terkini, berikut klasemen top 3... 🏆'";

// Prepare the request data
$requestData = [
    'model' => CHATBOT_MODEL,
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userMessage]
    ],
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
    logChatbotError('HTTP Error', ['status_code' => $httpCode, 'user_message' => $userMessage]);
    http_response_code($httpCode);
    echo json_encode(['error' => 'API request failed with status: ' . $httpCode]);
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

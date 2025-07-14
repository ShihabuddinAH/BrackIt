<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
require_once '../connect.php';

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // Get the input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['team_id'])) {
        throw new Exception('Team ID is required');
    }

    $teamId = intval($input['team_id']);

    if ($teamId <= 0) {
        throw new Exception('Invalid team ID');
    }

    // Get team details
    $teamQuery = "SELECT * FROM team WHERE id_team = ?";
    $stmt = $conn->prepare($teamQuery);
    
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $teamId);
    $stmt->execute();
    $teamResult = $stmt->get_result();
    
    if ($teamResult->num_rows === 0) {
        throw new Exception('Team not found');
    }
    
    $team = $teamResult->fetch_assoc();
    $stmt->close();
    
    // Safely get team attributes with fallbacks for missing columns
    $teamData = [
        'id' => $team['id_team'] ?? 0,
        'name' => $team['nama_team'] ?? 'Unknown Team',
        'logo' => $team['logo_team'] ?? 'default.png',
        'description' => $team['deskripsi_team'] ?? 'Tim esports profesional yang berdedikasi untuk memberikan performa terbaik dalam setiap kompetisi.',
        'points' => isset($team['point']) ? intval($team['point']) : 0,
        'wins' => isset($team['win']) ? intval($team['win']) : 0,
        'rank' => 1 // Default rank
    ];
    
    // Calculate team rank based on points (only if point column exists)
    if (isset($team['point'])) {
        try {
            $rankQuery = "SELECT COUNT(*) + 1 as team_rank FROM team WHERE point > ?";
            $rankStmt = $conn->prepare($rankQuery);
            if ($rankStmt) {
                $rankStmt->bind_param("i", $team['point']);
                $rankStmt->execute();
                $rankResult = $rankStmt->get_result();
                if ($rankResult && $rankResult->num_rows > 0) {
                    $rankData = $rankResult->fetch_assoc();
                    $teamData['rank'] = intval($rankData['team_rank']);
                }
                $rankStmt->close();
            }
        } catch (Exception $e) {
            // If rank calculation fails, keep default rank
            error_log("Rank calculation failed: " . $e->getMessage());
        }
    }
    
    // Sample member data - replace with actual member table query when available
    $members = [
        [
            'name' => 'Captain Pro',
            'role' => 'Captain',
            'avatar' => '👑'
        ],
        [
            'name' => 'Striker Elite',
            'role' => 'Striker',
            'avatar' => '⚡'
        ],
        [
            'name' => 'Defense Master',
            'role' => 'Defender',
            'avatar' => '🛡️'
        ],
        [
            'name' => 'Support Ace',
            'role' => 'Support',
            'avatar' => '🎯'
        ],
        [
            'name' => 'Flex Player',
            'role' => 'Flex',
            'avatar' => '🔄'
        ]
    ];
    
    // Prepare response
    $response = [
        'success' => true,
        'team' => $teamData,
        'members' => $members,
        'debug' => [
            'available_columns' => array_keys($team),
            'team_id_requested' => $teamId
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }
}
?>

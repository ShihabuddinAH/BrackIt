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
    $wins = isset($team['win']) ? intval($team['win']) : 0;
    $loses = isset($team['lose']) ? intval($team['lose']) : 0;
    $totalMatches = $wins + $loses;
    $winRate = $totalMatches > 0 ? round(($wins / $totalMatches) * 100, 1) : 0;
    
    $teamData = [
        'id' => $team['id_team'] ?? 0,
        'name' => $team['nama_team'] ?? 'Unknown Team',
        'logo' => $team['logo_team'] ?? 'default.png',
        'description' => $team['deskripsi_team'] ?? 'Tim esports profesional yang berdedikasi untuk memberikan performa terbaik dalam setiap kompetisi.',
        'points' => isset($team['point']) ? intval($team['point']) : 0,
        'wins' => $wins,
        'loses' => $loses,
        'total_matches' => $totalMatches,
        'win_rate' => $winRate,
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
    
    // Get team members from team_player table with player details
    $membersQuery = "SELECT 
        p.id_player,
        p.username as name,
        p.nickname,
        p.email,
        p.idGame,
        'Player' as role
    FROM team_player tp
    JOIN player p ON tp.id_player = p.id_player
    WHERE tp.id_team = ?
    ORDER BY p.username ASC";
    
    $stmt = $conn->prepare($membersQuery);
    $members = [];
    
    if ($stmt) {
        $stmt->bind_param("i", $teamId);
        $stmt->execute();
        $membersResult = $stmt->get_result();
        
        while ($member = $membersResult->fetch_assoc()) {
            $members[] = [
                'name' => $member['name'],
                'nickname' => $member['nickname'],
                'role' => $member['role'],
                'idGame' => $member['idGame'],
                'avatar' => '🎮' // Default avatar
            ];
        }
        $stmt->close();
    }
    
    // If no members found in database, use sample data
    if (empty($members)) {
        $members = [
            [
                'name' => 'Captain Pro',
                'nickname' => 'CaptainPro',
                'role' => 'Captain',
                'idGame' => '001',
                'avatar' => '👑'
            ],
            [
                'name' => 'Striker Elite',
                'nickname' => 'StrikerElite',
                'role' => 'Striker',
                'idGame' => '002',
                'avatar' => '⚡'
            ],
            [
                'name' => 'Defense Master',
                'nickname' => 'DefenseMaster',
                'role' => 'Defender',
                'idGame' => '003',
                'avatar' => '🛡️'
            ],
            [
                'name' => 'Support Ace',
                'nickname' => 'SupportAce',
                'role' => 'Support',
                'idGame' => '004',
                'avatar' => '🎯'
            ],
            [
                'name' => 'Flex Player',
                'nickname' => 'FlexPlayer',
                'role' => 'Flex',
                'idGame' => '005',
                'avatar' => '🔄'
            ]
        ];
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'team' => $teamData,
        'members' => $members,
        'member_count' => count($members),
        'debug' => [
            'available_columns' => array_keys($team),
            'team_id_requested' => $teamId,
            'members_found' => count($members)
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

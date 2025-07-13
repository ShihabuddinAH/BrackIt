<?php
// getTeamDetails.php - Fetch detailed information about a team

// Include database connection
require_once '../connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Check if team ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Team ID is required'
    ]);
    exit;
}

$teamId = intval($_GET['id']);

try {
    // Fetch team basic information
    $teamQuery = "SELECT 
        t.id,
        t.nama_tim as name,
        t.deskripsi as description,
        t.logo,
        COUNT(tm.id_user) as member_count,
        t.created_at
    FROM teams t
    LEFT JOIN team_members tm ON t.id = tm.id_tim
    WHERE t.id = ?
    GROUP BY t.id";
    
    $stmt = $pdo->prepare($teamQuery);
    $stmt->execute([$teamId]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$team) {
        echo json_encode([
            'success' => false,
            'message' => 'Team not found'
        ]);
        exit;
    }

    // Fetch team members
    $membersQuery = "SELECT 
        u.id,
        u.username as name,
        u.email,
        tm.role,
        tm.joined_at
    FROM team_members tm
    JOIN users u ON tm.id_user = u.id
    WHERE tm.id_tim = ?
    ORDER BY tm.role = 'leader' DESC, tm.joined_at ASC";
    
    $stmt = $pdo->prepare($membersQuery);
    $stmt->execute([$teamId]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch team tournament statistics
    $statsQuery = "SELECT 
        COUNT(DISTINCT tt.id_tournament) as tournaments_joined,
        SUM(CASE WHEN tr.winner_team_id = ? THEN 1 ELSE 0 END) as wins
    FROM team_tournaments tt
    LEFT JOIN tournament_results tr ON tt.id_tournament = tr.tournament_id
    WHERE tt.id_tim = ?";
    
    $stmt = $pdo->prepare($statsQuery);
    $stmt->execute([$teamId, $teamId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Combine all data
    $teamData = [
        'id' => $team['id'],
        'name' => $team['name'],
        'description' => $team['description'],
        'logo' => $team['logo'] ? 'ASSETS/LOGO_TEAM/' . $team['logo'] : null,
        'member_count' => intval($team['member_count']),
        'created_at' => $team['created_at'],
        'members' => $members,
        'tournaments_joined' => intval($stats['tournaments_joined'] ?? 0),
        'wins' => intval($stats['wins'] ?? 0),
        'rank' => calculateTeamRank($teamId, $pdo) // Custom function to calculate rank
    ];

    echo json_encode([
        'success' => true,
        'team' => $teamData
    ]);

} catch (PDOException $e) {
    error_log("Database error in getTeamDetails.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("General error in getTeamDetails.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching team details'
    ]);
}

/**
 * Calculate team rank based on wins and tournaments participated
 */
function calculateTeamRank($teamId, $pdo) {
    try {
        $rankQuery = "SELECT 
            t.id,
            COUNT(DISTINCT tt.id_tournament) as tournaments,
            SUM(CASE WHEN tr.winner_team_id = t.id THEN 1 ELSE 0 END) as wins,
            (SUM(CASE WHEN tr.winner_team_id = t.id THEN 1 ELSE 0 END) * 100.0 / 
             NULLIF(COUNT(DISTINCT tt.id_tournament), 0)) as win_rate
        FROM teams t
        LEFT JOIN team_tournaments tt ON t.id = tt.id_tim
        LEFT JOIN tournament_results tr ON tt.id_tournament = tr.tournament_id
        GROUP BY t.id
        ORDER BY win_rate DESC, wins DESC, tournaments DESC";
        
        $stmt = $pdo->prepare($rankQuery);
        $stmt->execute();
        $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Find the team's position in rankings
        foreach ($rankings as $index => $rankTeam) {
            if ($rankTeam['id'] == $teamId) {
                return $index + 1;
            }
        }
        
        return 'Unranked';
    } catch (Exception $e) {
        return 'N/A';
    }
}
?>

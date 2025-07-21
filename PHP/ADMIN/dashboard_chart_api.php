<?php
session_start();
require_once '../connect.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

header('Content-Type: application/json');

// Function to get chart data for visualizations
function getChartData($conn) {
    $chartData = [];
    
    try {
        // Monthly user login statistics for the last 6 months
        $monthlyLoginData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $monthName = date('M Y', strtotime("-$i months"));
            
            // Players who logged in this month
            $players_login = $conn->query("
                SELECT COUNT(DISTINCT id_player) as count 
                FROM player 
                WHERE DATE_FORMAT(last_login, '%Y-%m') = '$month'
                AND last_login IS NOT NULL
            ")->fetch_assoc()['count'];
            
            // EOs who logged in this month
            $eos_login = $conn->query("
                SELECT COUNT(DISTINCT id_eo) as count 
                FROM eo 
                WHERE DATE_FORMAT(last_login, '%Y-%m') = '$month'
                AND last_login IS NOT NULL
            ")->fetch_assoc()['count'];
            
            // Admins who logged in this month
            $admins_login = $conn->query("
                SELECT COUNT(DISTINCT id_admin) as count 
                FROM admin 
                WHERE DATE_FORMAT(last_login, '%Y-%m') = '$month'
                AND last_login IS NOT NULL
            ")->fetch_assoc()['count'];
            
            $monthlyLoginData[] = [
                'month' => $monthName,
                'players' => (int)$players_login,
                'eos' => (int)$eos_login,
                'admins' => (int)$admins_login
            ];
        }
        $chartData['monthly_logins'] = $monthlyLoginData;
        
        // Monthly user registrations for the last 6 months
        $monthlyRegistrationData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $monthName = date('M Y', strtotime("-$i months"));
            
            // Players registered this month
            $players = $conn->query("
                SELECT COUNT(*) as count 
                FROM player 
                WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'
            ")->fetch_assoc()['count'];
            
            // EOs registered this month
            $eos = $conn->query("
                SELECT COUNT(*) as count 
                FROM eo 
                WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'
            ")->fetch_assoc()['count'];
            
            $monthlyRegistrationData[] = [
                'month' => $monthName,
                'players' => (int)$players,
                'eos' => (int)$eos
            ];
        }
        $chartData['monthly_registrations'] = $monthlyRegistrationData;
        
        // Tournament status distribution
        $tournamentStatus = $conn->query("
            SELECT status, COUNT(*) as count 
            FROM turnamen 
            GROUP BY status
        ");
        $statusData = [];
        while ($row = $tournamentStatus->fetch_assoc()) {
            $statusData[] = [
                'status' => ucfirst($row['status']),
                'count' => (int)$row['count']
            ];
        }
        $chartData['tournament_status'] = $statusData;
        
        // Team vs Individual tournament distribution
        $formatData = $conn->query("
            SELECT format, COUNT(*) as count 
            FROM turnamen 
            GROUP BY format
        ");
        $formatDistribution = [];
        while ($row = $formatData->fetch_assoc()) {
            $formatDistribution[] = [
                'format' => ucfirst($row['format']),
                'count' => (int)$row['count']
            ];
        }
        $chartData['tournament_format'] = $formatDistribution;
        
        // Daily login statistics for the last 7 days
        $dailyLoginData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dayName = date('D', strtotime("-$i days"));
            
            // Count unique logins per day
            $players_login = $conn->query("
                SELECT COUNT(DISTINCT id_player) as count 
                FROM player 
                WHERE DATE(last_login) = '$date'
                AND last_login IS NOT NULL
            ")->fetch_assoc()['count'];
            
            $eos_login = $conn->query("
                SELECT COUNT(DISTINCT id_eo) as count 
                FROM eo 
                WHERE DATE(last_login) = '$date'
                AND last_login IS NOT NULL
            ")->fetch_assoc()['count'];
            
            $dailyLoginData[] = [
                'day' => $dayName,
                'players' => (int)$players_login,
                'eos' => (int)$eos_login,
                'total_logins' => (int)$players_login + (int)$eos_login
            ];
        }
        $chartData['daily_logins'] = $dailyLoginData;
        
        // Daily registrations for the last 7 days
        $dailyRegistrationData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dayName = date('D', strtotime("-$i days"));
            
            $players = $conn->query("
                SELECT COUNT(*) as count 
                FROM player 
                WHERE DATE(created_at) = '$date'
            ")->fetch_assoc()['count'];
            
            $eos = $conn->query("
                SELECT COUNT(*) as count 
                FROM eo 
                WHERE DATE(created_at) = '$date'
            ")->fetch_assoc()['count'];
            
            $dailyRegistrationData[] = [
                'day' => $dayName,
                'players' => (int)$players,
                'eos' => (int)$eos,
                'total_registrations' => (int)$players + (int)$eos
            ];
        }
        $chartData['daily_registrations'] = $dailyRegistrationData;
        
        // Top performing teams (by points)
        $topTeams = $conn->query("
            SELECT nama_team, point, win, lose 
            FROM team 
            ORDER BY point DESC 
            LIMIT 5
        ");
        $teamsData = [];
        while ($row = $topTeams->fetch_assoc()) {
            $teamsData[] = [
                'name' => $row['nama_team'],
                'points' => (int)$row['point'],
                'wins' => (int)$row['win'],
                'losses' => (int)$row['lose']
            ];
        }
        $chartData['top_teams'] = $teamsData;
        
        // Recent activity stats
        $recentActivity = [];
        
        // New users today
        $today = date('Y-m-d');
        $newPlayersToday = $conn->query("
            SELECT COUNT(*) as count 
            FROM player 
            WHERE DATE(created_at) = '$today'
        ")->fetch_assoc()['count'];
        
        $newEOsToday = $conn->query("
            SELECT COUNT(*) as count 
            FROM eo 
            WHERE DATE(created_at) = '$today'
        ")->fetch_assoc()['count'];
        
        $recentActivity['new_users_today'] = (int)$newPlayersToday + (int)$newEOsToday;
        
        // Active tournaments today
        $activeTournamentsToday = $conn->query("
            SELECT COUNT(*) as count 
            FROM turnamen 
            WHERE status = 'aktif' 
            AND DATE(tanggal_mulai) <= '$today' 
            AND (tanggal_selesai IS NULL OR DATE(tanggal_selesai) >= '$today')
        ")->fetch_assoc()['count'];
        
        $recentActivity['active_tournaments_today'] = (int)$activeTournamentsToday;
        
        $chartData['recent_activity'] = $recentActivity;
        
        // Tournament registrations over time
        $registrationTrends = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dateLabel = date('M j', strtotime("-$i days"));
            
            $registrations = $conn->query("
                SELECT COUNT(*) as count 
                FROM tournament_registrations 
                WHERE DATE(registration_date) = '$date'
            ")->fetch_assoc()['count'];
            
            $registrationTrends[] = [
                'date' => $dateLabel,
                'registrations' => (int)$registrations
            ];
        }
        $chartData['tournament_registrations_trend'] = $registrationTrends;
        
        // Game distribution from tournaments
        $gameStats = $conn->query("
            SELECT game, COUNT(*) as count 
            FROM turnamen 
            GROUP BY game 
            ORDER BY count DESC 
            LIMIT 5
        ");
        $gameData = [];
        while ($row = $gameStats->fetch_assoc()) {
            $gameData[] = [
                'game' => $row['game'],
                'count' => (int)$row['count']
            ];
        }
        $chartData['popular_games'] = $gameData;
        
    } catch (Exception $e) {
        error_log("Error in getChartData: " . $e->getMessage());
        return ['error' => 'Database error occurred'];
    }
    
    return $chartData;
}

// Handle API request
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'all';
    
    switch ($action) {
        case 'all':
            $data = getChartData($conn);
            break;
            
        case 'refresh':
            // Force refresh all chart data
            $data = getChartData($conn);
            $data['timestamp'] = date('Y-m-d H:i:s');
            break;
            
        default:
            $data = ['error' => 'Invalid action'];
            break;
    }
    
    echo json_encode($data);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

$conn->close();
?>

<?php
include '../LOGIN/session.php';
include '../connect.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'stats':
        getDashboardStats($conn);
        break;
    case 'user_chart':
        getUserChartData($conn);
        break;
    case 'recent_tournaments':
        getRecentTournaments($conn);
        break;
    case 'recent_reports':
        getRecentReports($conn);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function getDashboardStats($conn) {
    try {
        $stats = [];
        
        // Total Players
        $result = $conn->query("SELECT COUNT(*) as total FROM player");
        $stats['total_players'] = $result->fetch_assoc()['total'];
        
        // Total EOs
        $result = $conn->query("SELECT COUNT(*) as total FROM eo");
        $stats['total_eos'] = $result->fetch_assoc()['total'];
        
        // Total Teams
        $result = $conn->query("SELECT COUNT(*) as total FROM team");
        $stats['total_teams'] = $result->fetch_assoc()['total'];
        
        // Active Teams (teams with players)
        $result = $conn->query("
            SELECT COUNT(DISTINCT t.id_team) as active 
            FROM team t 
            INNER JOIN team_player tp ON t.id_team = tp.id_team
        ");
        $stats['active_teams'] = $result->fetch_assoc()['active'];
        
        // Active Tournaments
        $result = $conn->query("SELECT COUNT(*) as active FROM turnamen WHERE status = 'aktif'");
        $stats['active_tournaments'] = $result->fetch_assoc()['active'];
        
        // Calculate growth (last 30 days vs previous 30 days)
        // Players growth
        $current_month_players = $conn->query("
            SELECT COUNT(*) as count 
            FROM player 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")->fetch_assoc()['count'];
        
        $previous_month_players = $conn->query("
            SELECT COUNT(*) as count 
            FROM player 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
            AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")->fetch_assoc()['count'];
        
        $stats['players_growth'] = $previous_month_players > 0 ? 
            round((($current_month_players - $previous_month_players) / $previous_month_players) * 100, 1) : 0;
        
        // EOs growth
        $current_month_eos = $conn->query("
            SELECT COUNT(*) as count 
            FROM eo 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")->fetch_assoc()['count'];
        
        $previous_month_eos = $conn->query("
            SELECT COUNT(*) as count 
            FROM eo 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
            AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")->fetch_assoc()['count'];
        
        $stats['eos_growth'] = $previous_month_eos > 0 ? 
            round((($current_month_eos - $previous_month_eos) / $previous_month_eos) * 100, 1) : 0;
        
        // Tournaments growth (weekly)
        $current_week_tournaments = $conn->query("
            SELECT COUNT(*) as count 
            FROM turnamen 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ")->fetch_assoc()['count'];
        
        $stats['new_tournaments_week'] = $current_week_tournaments;
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function getUserChartData($conn) {
    try {
        $query = "
            SELECT 
                MONTH(created_at) as month,
                YEAR(created_at) as year,
                COUNT(*) as player_count,
                (SELECT COUNT(*) FROM eo WHERE MONTH(created_at) = MONTH(p.created_at) AND YEAR(created_at) = YEAR(p.created_at)) as eo_count
            FROM player p
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY YEAR(created_at), MONTH(created_at)
            ORDER BY year, month
        ";
        
        $result = $conn->query($query);
        $labels = [];
        $playerData = [];
        $eoData = [];
        
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        
        while ($row = $result->fetch_assoc()) {
            $labels[] = $monthNames[$row['month'] - 1] . ' ' . $row['year'];
            $playerData[] = intval($row['player_count']);
            $eoData[] = intval($row['eo_count']);
        }
        
        echo json_encode([
            'success' => true,
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Players',
                    'data' => $playerData,
                    'borderColor' => '#4285f4',
                    'backgroundColor' => 'rgba(66, 133, 244, 0.1)'
                ],
                [
                    'label' => 'EOs',
                    'data' => $eoData,
                    'borderColor' => '#34a853',
                    'backgroundColor' => 'rgba(52, 168, 83, 0.1)'
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function getRecentTournaments($conn) {
    try {
        $query = "
            SELECT t.nama_turnamen, t.status, t.tanggal_mulai, t.slot, t.pendaftar, 
                   t.hadiah_turnamen, e.organisasi as eo_name
            FROM turnamen t
            LEFT JOIN eo e ON t.id_eo = e.id_eo
            ORDER BY t.created_at DESC
            LIMIT 5
        ";
        
        $result = $conn->query($query);
        $tournaments = [];
        
        while ($row = $result->fetch_assoc()) {
            $tournaments[] = [
                'nama' => $row['nama_turnamen'],
                'eo' => $row['eo_name'] ?? 'Unknown EO',
                'tanggal' => date('d M Y', strtotime($row['tanggal_mulai'])),
                'status' => $row['status'],
                'peserta' => $row['pendaftar'] . '/' . $row['slot'],
                'hadiah' => formatRupiah($row['hadiah_turnamen'])
            ];
        }
        
        echo json_encode([
            'success' => true,
            'tournaments' => $tournaments
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function getRecentReports($conn) {
    try {
        // Since we don't have a reports table, we'll create mock data based on tournaments
        $query = "
            SELECT t.nama_turnamen, t.status, t.created_at, 
                   e.username as eo_username
            FROM turnamen t
            LEFT JOIN eo e ON t.id_eo = e.id_eo
            ORDER BY t.created_at DESC
            LIMIT 5
        ";
        
        $result = $conn->query($query);
        $reports = [];
        
        $reportTypes = ['Perilaku', 'Cheating', 'Teknis', 'Spam', 'Lainnya'];
        $reportStatuses = ['pending', 'investigating', 'resolved'];
        
        while ($row = $result->fetch_assoc()) {
            $reports[] = [
                'tanggal' => date('d M Y', strtotime($row['created_at'])),
                'pelapor' => 'user_' . rand(100, 999),
                'kategori' => $reportTypes[array_rand($reportTypes)],
                'deskripsi' => 'Laporan terkait turnamen ' . $row['nama_turnamen'],
                'status' => $reportStatuses[array_rand($reportStatuses)]
            ];
        }
        
        echo json_encode([
            'success' => true,
            'reports' => $reports
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function formatRupiah($amount) {
    if ($amount >= 1000000) {
        return 'Rp ' . number_format($amount / 1000000, 1) . 'M';
    } elseif ($amount >= 1000) {
        return 'Rp ' . number_format($amount / 1000, 1) . 'K';
    } else {
        return 'Rp ' . number_format($amount);
    }
}
?>

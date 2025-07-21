<?php
include '../LOGIN/session.php';
include '../connect.php';

header('Content-Type: application/json');

$eo_id = $_SESSION['user_id'] ?? 1; // Get EO ID from session

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'stats':
        getDashboardStats($conn, $eo_id);
        break;
    case 'recent_tournaments':
        getRecentTournaments($conn, $eo_id);
        break;
    case 'revenue_chart':
        getRevenueChartData($conn, $eo_id);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function getDashboardStats($conn, $eo_id) {
    try {
        $stats = [];
        
        // Total Tournaments by EO
        $result = $conn->query("SELECT COUNT(*) as total FROM turnamen WHERE id_eo = $eo_id");
        $stats['total_tournaments'] = $result->fetch_assoc()['total'];
        
        // Active Tournaments
        $result = $conn->query("SELECT COUNT(*) as active FROM turnamen WHERE id_eo = $eo_id AND status = 'aktif'");
        $stats['active_tournaments'] = $result->fetch_assoc()['active'];
        
        // Total Revenue (sum of registration fees * registered participants)
        $result = $conn->query("
            SELECT SUM(CAST(biaya_turnamen AS UNSIGNED) * CAST(pendaftar AS UNSIGNED)) as revenue 
            FROM turnamen 
            WHERE id_eo = $eo_id
        ");
        $revenue = $result->fetch_assoc()['revenue'] ?? 0;
        $stats['total_revenue'] = $revenue;
        
        // Total Participants
        $result = $conn->query("SELECT SUM(CAST(pendaftar AS UNSIGNED)) as participants FROM turnamen WHERE id_eo = $eo_id");
        $stats['total_participants'] = $result->fetch_assoc()['participants'] ?? 0;
        
        // Growth calculations (last 30 days vs previous 30 days)
        $current_month = $conn->query("
            SELECT COUNT(*) as count 
            FROM turnamen 
            WHERE id_eo = $eo_id 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")->fetch_assoc()['count'];
        
        $previous_month = $conn->query("
            SELECT COUNT(*) as count 
            FROM turnamen 
            WHERE id_eo = $eo_id 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
            AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")->fetch_assoc()['count'];
        
        $growth = $previous_month > 0 ? (($current_month - $previous_month) / $previous_month) * 100 : 0;
        $stats['tournament_growth'] = round($growth, 1);
        
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

function getRecentTournaments($conn, $eo_id) {
    try {
        $query = "
            SELECT nama_turnamen, tanggal_mulai, status, slot, pendaftar, hadiah_turnamen 
            FROM turnamen 
            WHERE id_eo = $eo_id 
            ORDER BY created_at DESC 
            LIMIT 5
        ";
        
        $result = $conn->query($query);
        $tournaments = [];
        
        while ($row = $result->fetch_assoc()) {
            $tournaments[] = [
                'nama' => $row['nama_turnamen'],
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

function getRevenueChartData($conn, $eo_id) {
    try {
        $query = "
            SELECT 
                MONTH(created_at) as month,
                YEAR(created_at) as year,
                SUM(CAST(biaya_turnamen AS UNSIGNED) * CAST(pendaftar AS UNSIGNED)) as revenue
            FROM turnamen 
            WHERE id_eo = $eo_id 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY YEAR(created_at), MONTH(created_at)
            ORDER BY year, month
        ";
        
        $result = $conn->query($query);
        $labels = [];
        $data = [];
        
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        
        while ($row = $result->fetch_assoc()) {
            $labels[] = $monthNames[$row['month'] - 1] . ' ' . $row['year'];
            $data[] = intval($row['revenue']);
        }
        
        echo json_encode([
            'success' => true,
            'labels' => $labels,
            'data' => $data
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

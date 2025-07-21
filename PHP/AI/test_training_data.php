<?php
// Test file untuk memverifikasi training data
require_once 'config.php';

// Database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Connection error: " . $e->getMessage());
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
        echo "Error: " . $e->getMessage() . "\n";
    }
    
    return $trainingData;
}

echo "=== TESTING BRACKIT TRAINING DATA ===\n\n";

$trainingData = getTrainingData($conn);

foreach ($trainingData as $category => $data) {
    echo "=== " . strtoupper($category) . " ===\n";
    echo $data . "\n";
    echo str_repeat("-", 50) . "\n\n";
}

echo "=== FULL CONTEXT ===\n";
echo implode("\n", $trainingData);

$conn->close();
?>

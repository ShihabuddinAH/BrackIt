<?php
session_start();
require_once '../connect.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=test", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';

switch($action) {
    case 'get_all':
        getAllTournaments($pdo);
        break;
    case 'get_detail':
        getTournamentDetail($pdo);
        break;
    case 'delete':
        deleteTournament($pdo);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function getAllTournaments($pdo) {
    try {
        $search = $_GET['search'] ?? '';
        $page = intval($_GET['page'] ?? 1);
        $limit = 10; // Items per page
        $offset = ($page - 1) * $limit;
        
        // Base query
        $whereClause = '';
        $params = [];
        
        if (!empty($search)) {
            $whereClause = "WHERE t.nama_turnamen LIKE :search 
                          OR t.deskripsi_turnamen LIKE :search 
                          OR t.status LIKE :search 
                          OR t.format LIKE :search
                          OR e.username LIKE :search
                          OR e.organisasi LIKE :search";
            $params[':search'] = "%$search%";
        }
        
        // Count total tournaments
        $countSql = "SELECT COUNT(*) as total 
                    FROM turnamen t 
                    LEFT JOIN eo e ON t.id_eo = e.id_eo 
                    $whereClause";
        
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get tournaments with pagination
        $sql = "SELECT t.id_turnamen,
                       t.nama_turnamen,
                       t.deskripsi_turnamen,
                       t.logo_turnamen,
                       t.format,
                       t.pendaftaran_mulai,
                       t.pendaftaran_selesai,
                       t.tanggal_mulai,
                       t.tanggal_selesai,
                       t.biaya_turnamen,
                       t.hadiah_turnamen,
                       t.aturan,
                       t.status,
                       t.slot,
                       t.pendaftar,
                       t.pendapatan,
                       t.created_at,
                       t.updated_at,
                       t.registration_start,
                       t.registration_end,
                       t.slot as max_participants,
                       t.pendaftar as current_participants,
                       e.username as eo_name,
                       e.organisasi,
                       t.created_at as tanggal_dibuat
                FROM turnamen t 
                LEFT JOIN eo e ON t.id_eo = e.id_eo 
                $whereClause
                ORDER BY t.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        
        // Bind search parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        // Bind pagination parameters
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate pagination info
        $totalPages = ceil($totalCount / $limit);
        
        $response = [
            'success' => true,
            'tournaments' => $tournaments,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total' => intval($totalCount),
                'per_page' => $limit
            ]
        ];
        
        echo json_encode($response);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getTournamentDetail($pdo) {
    try {
        $tournamentId = $_GET['id'] ?? '';
        
        if (empty($tournamentId)) {
            echo json_encode(['success' => false, 'message' => 'Tournament ID is required']);
            return;
        }
        
        // Handle both numeric ID and formatted ID (T001)
        if (is_numeric($tournamentId)) {
            $numericId = intval($tournamentId);
        } else {
            // Extract numeric ID from format like 'T001'
            $numericId = intval(substr($tournamentId, 1));
        }
        
        $sql = "SELECT t.id_turnamen,
                       t.nama_turnamen,
                       t.deskripsi_turnamen,
                       t.logo_turnamen,
                       t.format,
                       t.pendaftaran_mulai,
                       t.pendaftaran_selesai,
                       t.tanggal_mulai,
                       t.tanggal_selesai,
                       t.biaya_turnamen,
                       t.hadiah_turnamen,
                       t.aturan,
                       t.status,
                       t.slot,
                       t.pendaftar,
                       t.pendapatan,
                       t.created_at,
                       t.updated_at,
                       t.registration_start,
                       t.registration_end,
                       t.slot as max_participants,
                       t.pendaftar as current_participants,
                       e.username as eo_name, 
                       e.email as eo_email,
                       e.organisasi,
                       t.created_at as tanggal_dibuat
                FROM turnamen t 
                LEFT JOIN eo e ON t.id_eo = e.id_eo 
                WHERE t.id_turnamen = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $numericId, PDO::PARAM_INT);
        $stmt->execute();
        
        $tournament = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tournament) {
            echo json_encode(['success' => false, 'message' => 'Tournament not found']);
            return;
        }
        
        // Get participants count from pendaftaran_turnamen table if exists
        try {
            $participantsSql = "SELECT COUNT(*) as total_participants,
                                      SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_participants,
                                      SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_participants
                               FROM pendaftaran_turnamen 
                               WHERE id_turnamen = :id";
            
            $participantsStmt = $pdo->prepare($participantsSql);
            $participantsStmt->bindParam(':id', $numericId, PDO::PARAM_INT);
            $participantsStmt->execute();
            $participantsData = $participantsStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($participantsData) {
                $tournament['current_participants'] = $participantsData['approved_participants'] ?? 0;
                $tournament['pending_participants'] = $participantsData['pending_participants'] ?? 0;
                $tournament['total_registrations'] = $participantsData['total_participants'] ?? 0;
            }
        } catch(PDOException $e) {
            // If pendaftaran_turnamen table doesn't exist, use default values
            $tournament['pending_participants'] = 0;
            $tournament['total_registrations'] = $tournament['current_participants'];
        }
        
        // Get participants list if table exists
        try {
            $participantsListSql = "SELECT p.*, 
                                          u.username,
                                          u.nama as participant_name,
                                          t.nama_tim as team_name
                                   FROM pendaftaran_turnamen p
                                   LEFT JOIN player u ON p.id_player = u.id_player
                                   LEFT JOIN tim t ON p.id_tim = t.id_tim
                                   WHERE p.id_turnamen = :id
                                   ORDER BY p.created_at DESC";
            
            $participantsListStmt = $pdo->prepare($participantsListSql);
            $participantsListStmt->bindParam(':id', $numericId, PDO::PARAM_INT);
            $participantsListStmt->execute();
            $tournament['participants'] = $participantsListStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            // If related tables don't exist, use empty array
            $tournament['participants'] = [];
        }
        
        echo json_encode(['success' => true, 'tournament' => $tournament]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function deleteTournament($pdo) {
    try {
        $tournamentId = $_POST['id'] ?? '';
        
        if (empty($tournamentId)) {
            echo json_encode(['success' => false, 'message' => 'Tournament ID is required']);
            return;
        }
        
        // Handle both numeric ID and formatted ID (T001)
        if (is_numeric($tournamentId)) {
            $numericId = intval($tournamentId);
        } else {
            // Extract numeric ID from format like 'T001'
            $numericId = intval(substr($tournamentId, 1));
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // First, check if tournament exists
            $checkSql = "SELECT id_turnamen, nama_turnamen FROM turnamen WHERE id_turnamen = :id";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->bindParam(':id', $numericId, PDO::PARAM_INT);
            $checkStmt->execute();
            $tournament = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$tournament) {
                $pdo->rollback();
                echo json_encode(['success' => false, 'message' => 'Tournament not found']);
                return;
            }
            
            // Delete related records first (if tables exist)
            try {
                // Delete tournament registrations
                $deleteRegistrationsSql = "DELETE FROM pendaftaran_turnamen WHERE id_turnamen = :id";
                $deleteRegistrationsStmt = $pdo->prepare($deleteRegistrationsSql);
                $deleteRegistrationsStmt->bindParam(':id', $numericId, PDO::PARAM_INT);
                $deleteRegistrationsStmt->execute();
            } catch(PDOException $e) {
                // Table might not exist, continue
            }
            
            try {
                // Delete tournament matches/rounds if exists
                $deleteMatchesSql = "DELETE FROM pertandingan WHERE id_turnamen = :id";
                $deleteMatchesStmt = $pdo->prepare($deleteMatchesSql);
                $deleteMatchesStmt->bindParam(':id', $numericId, PDO::PARAM_INT);
                $deleteMatchesStmt->execute();
            } catch(PDOException $e) {
                // Table might not exist, continue
            }
            
            // Finally, delete the tournament
            $deleteTournamentSql = "DELETE FROM turnamen WHERE id_turnamen = :id";
            $deleteTournamentStmt = $pdo->prepare($deleteTournamentSql);
            $deleteTournamentStmt->bindParam(':id', $numericId, PDO::PARAM_INT);
            $deleteTournamentStmt->execute();
            
            if ($deleteTournamentStmt->rowCount() > 0) {
                $pdo->commit();
                echo json_encode([
                    'success' => true, 
                    'message' => 'Tournament "' . $tournament['nama_turnamen'] . '" deleted successfully'
                ]);
            } else {
                $pdo->rollback();
                echo json_encode(['success' => false, 'message' => 'Failed to delete tournament']);
            }
            
        } catch(PDOException $e) {
            $pdo->rollback();
            throw $e;
        }
        
    } catch(PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>

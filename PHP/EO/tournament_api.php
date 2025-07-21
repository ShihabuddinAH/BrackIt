<?php
// Start session and basic auth check for API
session_start();
include '../connect.php';

header('Content-Type: application/json');

// Check if user is logged in and is an EO
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'eo') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$eo_id = $_SESSION['user_id'];

switch ($method) {
    case 'POST':
        addTournament($conn, $eo_id);
        break;
    case 'PUT':
        updateTournament($conn, $eo_id);
        break;
    case 'DELETE':
        deleteTournament($conn, $eo_id);
        break;
    case 'GET':
        getTournament($conn, $eo_id);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function addTournament($conn, $eo_id) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required_fields = ['tournamentName', 'tournamentFormat', 'startDate', 'tournamentSlot', 'prizePool', 'registrationFee', 'rules'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Field $field is required");
            }
        }
        
        // Prepare data
        $nama_turnamen = $conn->real_escape_string($data['tournamentName']);
        $format = $conn->real_escape_string($data['tournamentFormat']); // individu atau team
        $deskripsi = $conn->real_escape_string($data['description'] ?? '');
        $aturan = $conn->real_escape_string($data['rules']);
        $logo_turnamen = 'LOGO.png'; // Default logo
        $tanggal_mulai = $data['startDate'];
        $tanggal_selesai = null; // Will be set when tournament status changed to 'selesai'
        $biaya_turnamen = intval($data['registrationFee']);
        $hadiah_turnamen = intval($data['prizePool']);
        $slot = intval($data['tournamentSlot']);
        $pendaftar = 0; // Initial registrants
        $status = 'akan datang'; // Default status for new tournaments
        
        // Set registration dates (7 days before start for registration period)
        $pendaftaran_mulai = date('Y-m-d', strtotime($tanggal_mulai . ' -7 days'));
        $pendaftaran_selesai = date('Y-m-d', strtotime($tanggal_mulai . ' -1 day'));
        
        $query = "INSERT INTO turnamen (
            id_eo, nama_turnamen, deskripsi_turnamen, logo_turnamen, format,
            pendaftaran_mulai, pendaftaran_selesai, tanggal_mulai, tanggal_selesai,
            biaya_turnamen, hadiah_turnamen, aturan, status, slot, pendaftar
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?
        )";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "issssssssiissii",
            $eo_id, $nama_turnamen, $deskripsi, $logo_turnamen, $format,
            $pendaftaran_mulai, $pendaftaran_selesai, $tanggal_mulai, $tanggal_selesai,
            $biaya_turnamen, $hadiah_turnamen, $aturan, $status, $slot, $pendaftar
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Turnamen berhasil ditambahkan',
                'tournament_id' => $conn->insert_id
            ]);
        } else {
            throw new Exception('Failed to insert tournament');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function updateTournament($conn, $eo_id) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $tournament_id = intval($data['id']);
        
        // Verify tournament belongs to this EO
        $check_query = "SELECT id_turnamen FROM turnamen WHERE id_turnamen = ? AND id_eo = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ii", $tournament_id, $eo_id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows === 0) {
            throw new Exception('Tournament not found or access denied');
        }
        
        // Update tournament
        $nama_turnamen = $conn->real_escape_string($data['tournamentName']);
        $deskripsi = $conn->real_escape_string($data['description'] ?? '');
        $tanggal_mulai = $data['startDate'];
        $tanggal_selesai = $data['endDate'];
        $biaya_turnamen = intval($data['registrationFee']);
        $hadiah_turnamen = intval($data['prizePool']);
        $slot = intval($data['maxTeams']);
        
        $query = "UPDATE turnamen SET 
            nama_turnamen = ?, deskripsi_turnamen = ?, tanggal_mulai = ?, 
            tanggal_selesai = ?, biaya_turnamen = ?, hadiah_turnamen = ?, slot = ?
            WHERE id_turnamen = ? AND id_eo = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "ssssiiii",
            $nama_turnamen, $deskripsi, $tanggal_mulai, $tanggal_selesai,
            $biaya_turnamen, $hadiah_turnamen, $slot, $tournament_id, $eo_id
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Turnamen berhasil diupdate'
            ]);
        } else {
            throw new Exception('Failed to update tournament');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function deleteTournament($conn, $eo_id) {
    try {
        $tournament_id = intval($_GET['id'] ?? 0);
        
        if ($tournament_id === 0) {
            throw new Exception('Tournament ID is required');
        }
        
        // Verify tournament belongs to this EO
        $check_query = "SELECT id_turnamen FROM turnamen WHERE id_turnamen = ? AND id_eo = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ii", $tournament_id, $eo_id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows === 0) {
            throw new Exception('Tournament not found or access denied');
        }
        
        // Delete tournament
        $query = "DELETE FROM turnamen WHERE id_turnamen = ? AND id_eo = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $tournament_id, $eo_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Turnamen berhasil dihapus'
            ]);
        } else {
            throw new Exception('Failed to delete tournament');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function getTournament($conn, $eo_id) {
    try {
        $tournament_id = intval($_GET['id'] ?? 0);
        
        if ($tournament_id === 0) {
            throw new Exception('Tournament ID is required');
        }
        
        $query = "SELECT * FROM turnamen WHERE id_turnamen = ? AND id_eo = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $tournament_id, $eo_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            echo json_encode([
                'success' => true,
                'tournament' => $row
            ]);
        } else {
            throw new Exception('Tournament not found');
        }
        
    } catch (Exception $e) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>

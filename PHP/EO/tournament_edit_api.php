<?php
// Start session and basic auth check for API
session_start();
include '../connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in and is EO
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'eo') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$eo_id = $_SESSION['user_id'];

// Handle different actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get':
        getTournamentData();
        break;
    case 'update':
        updateTournament();
        break;
    case 'delete':
        deleteTournament();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function getTournamentData() {
    global $conn, $eo_id;
    
    $tournament_id = $_GET['id'] ?? '';
    
    if (empty($tournament_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tournament ID required']);
        return;
    }
    
    // Get tournament data with EO ownership check
    $stmt = $conn->prepare("
        SELECT id_turnamen, nama_turnamen, format, tanggal_mulai, tanggal_selesai, 
               slot, hadiah_turnamen, biaya_turnamen, deskripsi_turnamen, aturan, status, created_at
        FROM turnamen 
        WHERE id_turnamen = ? AND id_eo = ?
    ");
    
    $stmt->bind_param("ii", $tournament_id, $eo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tournament not found or access denied']);
        return;
    }
    
    $tournament = $result->fetch_assoc();
    
    echo json_encode(['success' => true, 'data' => $tournament]);
    $stmt->close();
}

function updateTournament() {
    global $conn, $eo_id;
    
    // Get POST data
    $tournament_id = $_POST['tournament_id'] ?? '';
    $nama_turnamen = $_POST['tournamentName'] ?? '';
    $format = $_POST['tournamentFormat'] ?? '';
    $tanggal_mulai = $_POST['startDate'] ?? '';
    $tanggal_selesai = $_POST['endDate'] ?? null;
    $slot = $_POST['tournamentSlot'] ?? '';
    $hadiah_turnamen = $_POST['prizePool'] ?? '';
    $biaya_turnamen = $_POST['registrationFee'] ?? '';
    $deskripsi_turnamen = $_POST['description'] ?? '';
    $aturan = $_POST['rules'] ?? '';
    $status = $_POST['status'] ?? 'akan datang';
    
    // Auto-set end date if status is 'selesai' and end date is empty
    if ($status === 'selesai' && empty($tanggal_selesai)) {
        $tanggal_selesai = date('Y-m-d H:i:s');
    }
    
    // Validate required fields
    if (empty($tournament_id) || empty($nama_turnamen) || empty($format) || empty($tanggal_mulai)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        return;
    }
    
    // Check if tournament belongs to this EO
    $stmt = $conn->prepare("SELECT id_turnamen FROM turnamen WHERE id_turnamen = ? AND id_eo = ?");
    $stmt->bind_param("ii", $tournament_id, $eo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    $stmt->close();
    
    // Update tournament
    $stmt = $conn->prepare("
        UPDATE turnamen SET 
            nama_turnamen = ?, 
            format = ?, 
            tanggal_mulai = ?, 
            tanggal_selesai = ?, 
            slot = ?, 
            hadiah_turnamen = ?, 
            biaya_turnamen = ?, 
            deskripsi_turnamen = ?,
            aturan = ?,
            status = ?,
            updated_at = NOW()
        WHERE id_turnamen = ? AND id_eo = ?
    ");
    
    $stmt->bind_param("ssssiiisssii", 
        $nama_turnamen, $format, $tanggal_mulai, $tanggal_selesai, 
        $slot, $hadiah_turnamen, $biaya_turnamen, $deskripsi_turnamen, $aturan, $status,
        $tournament_id, $eo_id
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Tournament updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update tournament']);
    }
    
    $stmt->close();
}

function deleteTournament() {
    global $conn, $eo_id;
    
    $tournament_id = $_POST['tournament_id'] ?? '';
    
    if (empty($tournament_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tournament ID required']);
        return;
    }
    
    // Check if tournament belongs to this EO
    $stmt = $conn->prepare("SELECT id_turnamen FROM turnamen WHERE id_turnamen = ? AND id_eo = ?");
    $stmt->bind_param("ii", $tournament_id, $eo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied or tournament not found']);
        return;
    }
    $stmt->close();
    
    // Delete tournament
    $stmt = $conn->prepare("DELETE FROM turnamen WHERE id_turnamen = ? AND id_eo = ?");
    $stmt->bind_param("ii", $tournament_id, $eo_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Tournament deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete tournament']);
    }
    
    $stmt->close();
}
?>

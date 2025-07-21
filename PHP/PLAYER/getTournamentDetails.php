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

    if (!isset($input['tournament_id'])) {
        throw new Exception('Tournament ID is required');
    }

    $tournamentId = intval($input['tournament_id']);

    if ($tournamentId <= 0) {
        throw new Exception('Invalid tournament ID');
    }

    // Get tournament details with EO organization info
    $tournamentQuery = "SELECT t.*, e.organisasi as eo_organisasi 
                        FROM turnamen t 
                        LEFT JOIN eo e ON t.id_eo = e.id_eo 
                        WHERE t.id_turnamen = ?";
    $stmt = $conn->prepare($tournamentQuery);
    
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $tournamentId);
    $stmt->execute();
    $tournamentResult = $stmt->get_result();
    
    if ($tournamentResult->num_rows === 0) {
        throw new Exception('Tournament not found');
    }
    
    $tournament = $tournamentResult->fetch_assoc();
    $stmt->close();
    
    // Safely get tournament attributes with fallbacks for missing columns
    $tournamentData = [
        'id' => $tournament['id_turnamen'] ?? 0,
        'name' => $tournament['nama_turnamen'] ?? 'Unknown Tournament',
        'eo_organization' => $tournament['eo_organisasi'] ?? 'Unknown Organization',
        'description' => $tournament['deskripsi_turnamen'] ?? 'Tournament esports profesional dengan hadiah menarik.',
        'game' => 'Mobile Legends', // Default since not in DB
        'format' => $tournament['format'] ?? 'Elimination',
        'prize_pool' => $tournament['hadiah_turnamen'] ?? 'TBA',
        'status' => $tournament['status'] ?? 'aktif',
        'team_size' => ($tournament['format'] ?? 'tim') === 'individu' ? '1' : '5', // Based on format
        'entry_fee' => $tournament['biaya_turnamen'] ?? 'FREE',
        'platform' => 'Mobile', // Default since not in DB
        'region' => 'Indonesia', // Default since not in DB
        'slug' => strtolower(str_replace(' ', '-', $tournament['nama_turnamen'])) ?? '',
        'registration_start' => $tournament['pendaftaran_mulai'] ?? null,
        'registration_end' => $tournament['pendaftaran_selesai'] ?? null,
        'tournament_start' => $tournament['tanggal_mulai'] ?? null,
        'tournament_end' => $tournament['tanggal_selesai'] ?? null,
        'rules' => $tournament['aturan'] ?? '',
        'created_at' => $tournament['created_at'] ?? null,
        'updated_at' => $tournament['updated_at'] ?? null
    ];

    // Format dates if available
    if ($tournamentData['registration_start'] && $tournamentData['registration_end']) {
        $tournamentData['registration_period'] = date('d', strtotime($tournamentData['registration_start'])) . ' - ' . 
                                               date('d F Y', strtotime($tournamentData['registration_end']));
    } else {
        $tournamentData['registration_period'] = 'Segera Diumumkan';
    }

    // Format tournament period - show start date even if end date is not available
    if ($tournamentData['tournament_start']) {
        if ($tournamentData['tournament_end']) {
            $tournamentData['tournament_period'] = date('d', strtotime($tournamentData['tournament_start'])) . ' - ' . 
                                                 date('d F Y', strtotime($tournamentData['tournament_end']));
        } else {
            // Only start date available
            $tournamentData['tournament_period'] = date('d F Y', strtotime($tournamentData['tournament_start']));
        }
    } else {
        $tournamentData['tournament_period'] = 'Segera Diumumkan';
    }

    // Convert rules string to array
    $tournamentData['rules_array'] = !empty($tournamentData['rules']) ? explode('|', $tournamentData['rules']) : [];

    // Current datetime for comparison
    $currentDateTime = new DateTime();
    
    // Parse tournament and registration dates
    $registrationStart = $tournamentData['registration_start'] ? new DateTime($tournamentData['registration_start']) : null;
    $registrationEnd = $tournamentData['registration_end'] ? new DateTime($tournamentData['registration_end']) : null;
    $tournamentStart = $tournamentData['tournament_start'] ? new DateTime($tournamentData['tournament_start']) : null;
    
    // Determine registration status
    $registrationStatus = 'BELUM DIBUKA';
    if ($registrationStart && $registrationEnd) {
        if ($currentDateTime < $registrationStart) {
            $registrationStatus = 'BELUM DIBUKA';
        } elseif ($currentDateTime >= $registrationStart && $currentDateTime <= $registrationEnd) {
            $registrationStatus = 'PENDAFTARAN DIBUKA';
        } else {
            $registrationStatus = 'PENDAFTARAN DITUTUP';
        }
    } elseif ($registrationStart && !$registrationEnd) {
        if ($currentDateTime >= $registrationStart) {
            $registrationStatus = 'PENDAFTARAN DIBUKA';
        }
    }
    
    $tournamentData['registration_status'] = $registrationStatus;
    
    // Check if tournament has started
    $tournamentHasStarted = false;
    if ($tournamentStart && $currentDateTime >= $tournamentStart) {
        $tournamentHasStarted = true;
    }
    
    $tournamentData['tournament_has_started'] = $tournamentHasStarted;
    $tournamentData['tournament_start_iso'] = $tournamentStart ? $tournamentStart->format('c') : null;

    // Auto-determine tournament status based on registration and EO settings
    $originalStatus = $tournament['status'] ?? 'aktif';
    $autoStatus = $originalStatus;
    
    // If EO manually sets status to "selesai", keep it and auto-generate end date if not exists
    if ($originalStatus === 'selesai') {
        $autoStatus = 'selesai';
        // Auto-generate end date if not exists (set to current date)
        if (!$tournamentData['tournament_end']) {
            $tournamentData['tournament_end'] = $currentDateTime->format('Y-m-d H:i:s');
            // Update database with auto-generated end date
            $updateQuery = "UPDATE turnamen SET tanggal_selesai = ? WHERE id_turnamen = ?";
            $updateStmt = $conn->prepare($updateQuery);
            if ($updateStmt) {
                $updateStmt->bind_param("si", $tournamentData['tournament_end'], $tournamentId);
                $updateStmt->execute();
                $updateStmt->close();
            }
        }
    } else {
        // Auto-determine status based on registration period
        if ($registrationStart && $registrationEnd) {
            if ($currentDateTime < $registrationStart) {
                $autoStatus = 'akan datang'; // Registration not opened yet
            } elseif ($currentDateTime >= $registrationStart && $currentDateTime <= $registrationEnd) {
                $autoStatus = 'aktif'; // Registration is open
            } else {
                $autoStatus = 'aktif'; // Registration closed but tournament may still be ongoing
            }
        } elseif ($registrationStart && !$registrationEnd) {
            if ($currentDateTime >= $registrationStart) {
                $autoStatus = 'aktif'; // Registration opened (no end date)
            } else {
                $autoStatus = 'akan datang'; // Registration not opened yet
            }
        }
    }
    
    // Update status in the response
    $tournamentData['status'] = $autoStatus;
    
    // Update database with auto-determined status if different from original
    if ($autoStatus !== $originalStatus && $originalStatus !== 'selesai') {
        $updateStatusQuery = "UPDATE turnamen SET status = ? WHERE id_turnamen = ?";
        $updateStatusStmt = $conn->prepare($updateStatusQuery);
        if ($updateStatusStmt) {
            $updateStatusStmt->bind_param("si", $autoStatus, $tournamentId);
            $updateStatusStmt->execute();
            $updateStatusStmt->close();
        }
    }

    // Status text mapping
    $status_text = [
        'aktif' => 'Sedang Berlangsung',
        'selesai' => 'Selesai',
        'akan datang' => 'Akan Dimulai'
    ];
    $tournamentData['status_text'] = $status_text[$autoStatus] ?? 'Pendaftaran Dibuka';

    // Registration info
    $tournamentData['registration_title'] = 'Daftar Sekarang!';
    $tournamentData['registration_description'] = 'Bergabunglah dengan tournament ini dan tunjukkan kemampuan terbaik Anda!';
    
    // Set button text based on registration status
    if ($tournamentData['registration_status'] === 'PENDAFTARAN DIBUKA') {
        $tournamentData['button_text'] = ($tournamentData['format'] === 'individu') ? 'Daftar Individu' : 'Daftar Tim';
    } else if ($tournamentData['registration_status'] === 'BELUM DIBUKA') {
        $tournamentData['button_text'] = 'Pendaftaran Belum Dibuka';
    } else {
        $tournamentData['button_text'] = 'Pendaftaran Ditutup';
    }

    // Get registered teams count (using pendaftar field from DB)
    $tournamentData['registered_teams'] = intval($tournament['pendaftar'] ?? 0);

    // Get total slots and calculate availability
    $totalSlots = intval($tournament['slot'] ?? 32);
    $registeredTeams = $tournamentData['registered_teams'];
    $tournamentData['slots_available'] = max(0, $totalSlots - $registeredTeams);
    $tournamentData['is_full'] = $registeredTeams >= $totalSlots;

    // Prepare response
    $response = [
        'success' => true,
        'tournament' => $tournamentData,
        'debug' => [
            'available_columns' => array_keys($tournament),
            'tournament_id_requested' => $tournamentId
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

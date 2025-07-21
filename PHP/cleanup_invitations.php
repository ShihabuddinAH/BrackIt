<?php
// Auto-expire old invitations
// This file should be run periodically (via cron job or called on page load)

function cleanup_expired_invitations($connection) {
    // Check if party_invitations table exists before running cleanup
    $check_table_query = "SHOW TABLES LIKE 'party_invitations'";
    $table_result = $connection->query($check_table_query);

    if ($table_result && $table_result->num_rows > 0) {
        // Mark expired invitations
        $expire_query = "UPDATE party_invitations 
                        SET status = 'expired' 
                        WHERE status = 'pending' AND expires_at < NOW()";
        $connection->query($expire_query);

        // Optional: Clean up old expired invitations (older than 30 days)
        $cleanup_query = "DELETE FROM party_invitations 
                         WHERE status IN ('expired', 'declined', 'accepted') 
                         AND responded_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $connection->query($cleanup_query);
    }
}

// If called directly (not as function), run the cleanup
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    require_once 'connect.php';
    cleanup_expired_invitations($conn);
    $conn->close();
}
?>

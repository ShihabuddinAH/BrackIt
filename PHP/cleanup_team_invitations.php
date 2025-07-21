<?php
// cleanup_team_invitations.php
function cleanup_expired_team_invitations($conn) {
    // Check if team_invitations table exists
    $check_table_query = "SHOW TABLES LIKE 'team_invitations'";
    $table_result = $conn->query($check_table_query);
    
    if ($table_result && $table_result->num_rows > 0) {
        // Delete expired invitations
        $cleanup_query = "DELETE FROM team_invitations WHERE expires_at <= NOW() AND status = 'pending'";
        $conn->query($cleanup_query);
    }
}
?>

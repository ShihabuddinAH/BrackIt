<?php
// Simple test for groq_chat_with_db.php
echo "Testing Groq Chat API...\n";

// Set required environment
$_SERVER['REQUEST_METHOD'] = 'POST';

// Create test input
$testInput = json_encode(['message' => 'Halo, apa kabar?']);
file_put_contents('php://input', $testInput);

// Capture output
ob_start();
include 'groq_chat_with_db.php';
$output = ob_get_contents();
ob_end_clean();

echo "API Output: " . $output . "\n";
?>

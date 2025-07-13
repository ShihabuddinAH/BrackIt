<?php
// Simple logging function for debugging
function logChatbotError($message, $context = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context
    ];
    
    $logFile = __DIR__ . '/chatbot_logs.txt';
    $logLine = json_encode($logEntry) . "\n";
    
    file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
}

function logChatbotInteraction($userMessage, $botResponse) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => 'interaction',
        'user_message' => $userMessage,
        'bot_response' => $botResponse
    ];
    
    $logFile = __DIR__ . '/chatbot_interactions.txt';
    $logLine = json_encode($logEntry) . "\n";
    
    file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
}
?>

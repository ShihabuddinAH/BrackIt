<?php
// Configuration file for BrackIt
// This file safely loads environment variables from .env file

// Simple environment loader function function
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Skip lines without = character
        if (strpos($line, '=') === false) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Remove quotes if present
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            $value = substr($value, 1, -1);
        }
        
        // Set environment variable
        $_ENV[$name] = $value;
    }
    return true;
}

// Get environment variable with default value
function env($key, $default = null) {
    return $_ENV[$key] ?? $default;
}

// Load .env file from root directory
$envPath = __DIR__ . '/../../.env';
if (!loadEnv($envPath)) {
    // If .env not found, try to use hardcoded values for development
    error_log('.env file not found. Using fallback configuration.');
}

// Groq API Configuration
define('GROQ_API_KEY', env('GROQ_API_KEY', ''));
define('GROQ_API_URL', env('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions'));

// Validate required environment variables (only show warning, don't throw error)
if (empty(env('GROQ_API_KEY'))) {
    error_log('Warning: GROQ_API_KEY not set. Please configure your .env file.');
}

// Database Configuration
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', 'test'));

// Application Settings
define('APP_NAME', env('APP_NAME', 'BrackIt'));
define('APP_VERSION', env('APP_VERSION', '1.0.0'));
define('APP_DEBUG', env('APP_DEBUG', 'true') === 'true'); // Convert string to boolean

// Chatbot Settings
define('CHATBOT_MODEL', env('CHATBOT_MODEL', 'llama3-8b-8192'));
define('CHATBOT_MAX_TOKENS', (int)env('CHATBOT_MAX_TOKENS', '150'));
define('CHATBOT_TEMPERATURE', (float)env('CHATBOT_TEMPERATURE', '0.7'));
define('CHATBOT_TIMEOUT', (int)env('CHATBOT_TIMEOUT', '30'));

?>

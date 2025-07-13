<?php
// Configuration file for BrackIt
// This file safely loads environment variables from .env file

// Load environment variables
require_once __DIR__ . '/env_loader.php';

// Load .env file from root directory
$envPath = __DIR__ . '/../../.env';
if (!loadEnv($envPath)) {
    throw new Exception('.env file not found. Please copy .env.example to .env and configure your settings.');
}

// Groq API Configuration
define('GROQ_API_KEY', env('GROQ_API_KEY', ''));
define('GROQ_API_URL', env('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions'));

// Validate required environment variables
if (empty(GROQ_API_KEY)) {
    throw new Exception('GROQ_API_KEY is required. Please set it in your .env file.');
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
define('CHATBOT_MODEL', 'llama3-8b-8192');
define('CHATBOT_MAX_TOKENS', 150);
define('CHATBOT_TEMPERATURE', 0.7);
define('CHATBOT_TIMEOUT', 30);

?>

<?php
/**
 * Environment Variables Loader for BrackIt
 * Loads environment variables from .env file
 */

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
        
        // Set environment variable if not already set
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
        
        // Also set as PHP constant for compatibility
        if (!defined($name)) {
            define($name, $value);
        }
    }
    return true;
}

/**
 * Get environment variable with default value
 */
function env($key, $default = null) {
    return $_ENV[$key] ?? $default;
}
?>

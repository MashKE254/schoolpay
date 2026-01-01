<?php
/**
 * security.php - Security Helper Functions
 * Provides CSRF protection, rate limiting, and other security features
 */

// --- CSRF Protection ---

/**
 * Generate a CSRF token and store it in the session
 * @return string The generated CSRF token
 */
function csrf_token() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Generate a CSRF token HTML input field
 * @return string HTML input field
 */
function csrf_field() {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Verify CSRF token from POST request
 * @param bool $die Whether to die on failure (default: true)
 * @return bool True if token is valid, false otherwise
 */
function csrf_verify($die = true) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $token = $_POST['csrf_token'] ?? '';
    $session_token = $_SESSION['csrf_token'] ?? '';

    $valid = !empty($token) && !empty($session_token) && hash_equals($session_token, $token);

    if (!$valid && $die) {
        http_response_code(403);
        die('CSRF token validation failed. Please refresh the page and try again.');
    }

    return $valid;
}

// --- Rate Limiting ---

/**
 * Simple rate limiter using session storage
 * @param string $action The action being rate limited (e.g., 'login', 'sms')
 * @param int $max_attempts Maximum attempts allowed
 * @param int $time_window Time window in seconds
 * @return bool True if rate limit is exceeded, false otherwise
 */
function is_rate_limited($action, $max_attempts = 5, $time_window = 300) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $key = "rate_limit_{$action}";
    $now = time();

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => $now];
    }

    $data = $_SESSION[$key];

    // Reset if time window has passed
    if ($now - $data['first_attempt'] > $time_window) {
        $_SESSION[$key] = ['attempts' => 1, 'first_attempt' => $now];
        return false;
    }

    // Increment attempts
    $_SESSION[$key]['attempts']++;

    // Check if rate limit exceeded
    if ($_SESSION[$key]['attempts'] > $max_attempts) {
        return true;
    }

    return false;
}

/**
 * Get remaining time until rate limit resets
 * @param string $action The action being rate limited
 * @param int $time_window Time window in seconds
 * @return int Seconds until reset
 */
function rate_limit_reset_time($action, $time_window = 300) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $key = "rate_limit_{$action}";
    if (!isset($_SESSION[$key])) {
        return 0;
    }

    $elapsed = time() - $_SESSION[$key]['first_attempt'];
    $remaining = $time_window - $elapsed;

    return max(0, $remaining);
}

/**
 * Reset rate limit for an action
 * @param string $action The action to reset
 */
function reset_rate_limit($action) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $key = "rate_limit_{$action}";
    unset($_SESSION[$key]);
}

// --- Input Sanitization ---

/**
 * Sanitize input string (trim and htmlspecialchars)
 * @param string $input The input to sanitize
 * @return string Sanitized input
 */
function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (East African format)
 * @param string $phone Phone number to validate
 * @return bool True if valid, false otherwise
 */
function validate_phone($phone) {
    $clean = preg_replace('/[^0-9]/', '', $phone);
    // Check if it matches common East African formats
    return preg_match('/^(?:254|255|256|250)?[17]\d{8}$/', $clean);
}

// --- Session Security ---

/**
 * Initialize secure session configuration
 */
function init_secure_session() {
    if (session_status() == PHP_SESSION_NONE) {
        // Configure session security
        ini_set('session.cookie_httponly', SESSION_HTTPONLY ? '1' : '0');
        ini_set('session.cookie_secure', SESSION_SECURE ? '1' : '0');
        ini_set('session.cookie_samesite', SESSION_SAMESITE);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        ini_set('session.cookie_lifetime', SESSION_LIFETIME);

        session_start();

        // Regenerate session ID periodically
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            // Regenerate session every 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

// --- Error Logging ---

/**
 * Log errors to a file
 * @param string $message Error message
 * @param string $level Error level (ERROR, WARNING, INFO)
 */
function log_error($message, $level = 'ERROR') {
    $log_file = __DIR__ . '/logs/error.log';
    $log_dir = dirname($log_file);

    // Create logs directory if it doesn't exist
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

    error_log($log_message, 3, $log_file);
}

/**
 * Log security events
 * @param string $event Event description
 * @param array $context Additional context
 */
function log_security_event($event, $context = []) {
    $log_file = __DIR__ . '/logs/security.log';
    $log_dir = dirname($log_file);

    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $log_data = [
        'timestamp' => $timestamp,
        'event' => $event,
        'ip' => $ip,
        'user_agent' => $user_agent,
        'context' => $context
    ];

    $log_message = json_encode($log_data) . PHP_EOL;
    error_log($log_message, 3, $log_file);
}

?>
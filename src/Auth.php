<?php

if (session_status() === PHP_SESSION_NONE) {
    // Session Security Settings
    // Relaxed settings for localhost/development to prevent issues
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    // ini_set('session.cookie_samesite', 'Strict'); // Disabled for wider compatibility during dev
    
    // If HTTPS is enabled, set secure cookie
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
        ini_set('session.cookie_samesite', 'None'); // Required for some secure contexts
    } else {
        ini_set('session.cookie_secure', 0); // Explicitly disable for HTTP
        ini_set('session.cookie_samesite', 'Lax'); // Lax is better for dev
    }
    
    session_start();
}

class Auth {
    private static $credentials = null;

    private static function loadCredentials() {
        if (self::$credentials === null) {
            self::$credentials = require __DIR__ . '/../config/credentials.php';
        }
        return self::$credentials;
    }

    public static function login($username, $password) {
        // Rate Limiting (Simple)
        if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] > 5) {
            if (time() - $_SESSION['last_attempt_time'] < 300) { // 5 min block
                return false;
            } else {
                // Reset after timeout
                $_SESSION['login_attempts'] = 0;
            }
        }

        $creds = self::loadCredentials();

        // TRIM INPUT
        $username = trim($username);
        $password = trim($password);

        if (isset($creds['users']) && is_array($creds['users']) && isset($creds['users'][$username])) {
            if (password_verify($password, $creds['users'][$username])) {
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user'] = $username;
                $_SESSION['login_attempts'] = 0;
                return true;
            }
        } elseif (isset($creds['username']) && $username === $creds['username']) {
            if (password_verify($password, $creds['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user'] = $username;
                $_SESSION['login_attempts'] = 0;
                return true;
            }
        }

        // Increment failed attempts
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        $_SESSION['last_attempt_time'] = time();
        
        return false;
    }

    public static function check() {
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            header('Location: /login.php');
            exit;
        }
    }

    public static function checkApi() {
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
    }

    public static function logout() {
        session_destroy();
        header('Location: /login.php');
        exit;
    }

    // CSRF Protection
    public static function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

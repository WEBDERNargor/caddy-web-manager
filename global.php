<?php
//ใช้สำหรับ gobal function

// Ensure session
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Handle logout action globally
try {
    if (isset($_GET['action']) && $_GET['action'] === 'logout') {
        // Remove app auth cookie if present
        if (function_exists('deletecookie')) {
            deletecookie('login_token');
        } else {
            // Fallback: delete cookie manually
            setcookie('login_token', '', time() - 3600, '/');
        }

        // Clear session variables
        if (isset($_SESSION)) {
            $_SESSION = [];
        }

        // Delete PHP session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        // Destroy session
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_destroy();
        }

        // Redirect to login
        header('Location: /login');
        exit;
    }
} catch (Throwable $e) {
    // Best-effort: do not expose details in production
}

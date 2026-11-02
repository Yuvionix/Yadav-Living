<?php
require_once(__DIR__ . "/../../Includes/config.php");

// Initialize session securely
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_secure', strtolower((string)hms_env('APP_ENV', 'local')) === 'production' ? '1' : '0');
    session_start();
}

// Security headers
if (!headers_sent()) {
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
}

/**
 * Require PG authenticated role
 */
function require_pg_role($allowed_roles = array('student', 'admin'), $redirect = '../login.php') {
    if (!is_array($allowed_roles)) {
        $allowed_roles = array($allowed_roles);
    }

    if (!isset($_SESSION['pg_logged']) || $_SESSION['pg_logged'] !== true) {
        header("Location: " . $redirect);
        exit;
    }

    $current_role = isset($_SESSION['pg_role']) ? $_SESSION['pg_role'] : '';
    $identity_id = $current_role === 'student'
        ? hms_pg_current_student_id()
        : (int)($_SESSION['pg_admin_id'] ?? 0);
    if (!in_array($current_role, $allowed_roles, true) || $identity_id < 1) {
        http_response_code(403);
        echo "<!DOCTYPE html><html><head><title>Access Denied</title>";
        echo "<link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'></head>";
        echo "<body class='bg-light text-center pt-5'><div class='card p-5 mx-auto' style='max-width:480px;'>";
        echo "<h4 class='text-danger'>403 - Unauthorized Access</h4><p>You do not have access to this PG portal area.</p>";
        echo "<a href='" . htmlspecialchars($redirect) . "' class='btn btn-primary'>Back to PG Login</a></div></body></html>";
        exit;
    }
}

function hms_pg_current_student_id() {
    if (($_SESSION['pg_role'] ?? '') !== 'student') {
        return 0;
    }

    return max(0, (int)($_SESSION['pg_student_id'] ?? 0));
}

function hms_pg_clear_portal_auth_state() {
    unset(
        $_SESSION['logged'],
        $_SESSION['account'],
        $_SESSION['uid'],
        $_SESSION['member_id'],
        $_SESSION['aid'],
        $_SESSION['admin_id'],
        $_SESSION['username'],
        $_SESSION['email'],
        $_SESSION['pg_logged'],
        $_SESSION['pg_role'],
        $_SESSION['pg_student_id'],
        $_SESSION['pg_admin_id'],
        $_SESSION['pg_name'],
        $_SESSION['pg_email'],
        $_SESSION['pg_username']
    );
}
?>
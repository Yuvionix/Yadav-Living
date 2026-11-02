<?php
require_once("config.php");

// Set secure session cookie parameters if session has not started yet
if (session_status() === PHP_SESSION_NONE) {
    // Only send cookies over HTTP/HTTPS; prevent client script access
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

// Ensure legacy session username is synced with database
if (isset($_SESSION['username']) && (strtolower($_SESSION['username']) === 'ashwin' || empty($_SESSION['username']))) {
    $_SESSION['username'] = 'Yuvraj';
}
if (isset($_SESSION['account']) && $_SESSION['account'] === 'admin') {
    if (isset($_SESSION['aid']) && !empty($con)) {
        $aid = (int)$_SESSION['aid'];
        $stmt_adm = mysqli_prepare($con, "SELECT username FROM admin WHERE id = ? LIMIT 1");
        if ($stmt_adm) {
            mysqli_stmt_bind_param($stmt_adm, "i", $aid);
            mysqli_stmt_execute($stmt_adm);
            $res_adm = mysqli_stmt_get_result($stmt_adm);
            if ($row_adm = mysqli_fetch_assoc($res_adm)) {
                $_SESSION['username'] = $row_adm['username'];
            }
            mysqli_stmt_close($stmt_adm);
        }
    }
    if (!isset($_SESSION['username']) || strtolower($_SESSION['username']) === 'ashwin') {
        $_SESSION['username'] = 'Yuvraj';
    }
}

$logged = false;
$username = "";

// Check if user is logged in
if (isset($_SESSION['logged']) && $_SESSION['logged'] === true && !empty($_SESSION['username'])) {
    $logged = true;
    $username = $_SESSION['username'];
}

/**
 * Determine the dynamic URL for login.php regardless of base installation path
 */
function get_login_url() {
    if (isset($_SERVER['SCRIPT_NAME'])) {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $pos = strpos($script, '/HMS-2/');
        if ($pos !== false) {
            return substr($script, 0, $pos) . '/HMS-2/login.php';
        }
    }
    return 'login.php';
}

/**
 * Enforce role-based access control and prevent Execution-After-Redirect (EAR)
 *
 * @param string|array $allowed_roles Role or array of roles allowed (e.g. 'admin', 'member', 'staff')
 * @param string|null $fallback_redirect Optional relative redirect path to login.php
 */
function require_role($allowed_roles, $fallback_redirect = null) {
    if (!is_array($allowed_roles)) {
        $allowed_roles = array($allowed_roles);
    }
    
    // Check if authenticated
    if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
        $login = $fallback_redirect ? $fallback_redirect : get_login_url();
        header("Location: " . $login);
        exit;
    }
    
    // Check role authorization
    $user_role = isset($_SESSION['account']) ? $_SESSION['account'] : '';
    $has_valid_identity = true;
    if ($user_role === 'member') {
        $has_valid_identity = hms_current_member_id() > 0;
    } elseif ($user_role === 'admin') {
        $has_valid_identity = hms_current_admin_id() > 0;
    } elseif ($user_role === 'staff') {
        $has_valid_identity = hms_current_admin_id() > 0;
    }

    if (!in_array($user_role, $allowed_roles, true) || !$has_valid_identity) {
        http_response_code(403);
        echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><title>Access Denied</title>";
        echo "<link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'></head>";
        echo "<body class='bg-light'><div class='container text-center pt-5'>";
        echo "<div class='card p-5 shadow-sm mx-auto' style='max-width:520px;border-radius:12px;'>";
        echo "<h3 class='text-danger font-weight-bold'>403 - Unauthorized Access</h3>";
        echo "<p class='text-muted mt-2'>You do not have permission to access this portal page.</p>";
        $login = $fallback_redirect ? $fallback_redirect : get_login_url();
        echo "<a href='" . htmlspecialchars($login) . "' class='btn btn-primary mt-3'>Return to Login</a>";
        echo "</div></div></body></html>";
        exit;
    }
}

function hms_current_member_id() {
    if (($_SESSION['account'] ?? '') !== 'member') {
        return 0;
    }

    return max(0, (int)($_SESSION['uid'] ?? $_SESSION['member_id'] ?? 0));
}

function hms_current_admin_id() {
    if (($_SESSION['account'] ?? '') === 'admin') {
        return max(0, (int)($_SESSION['admin_id'] ?? $_SESSION['aid'] ?? 0));
    }

    if (($_SESSION['account'] ?? '') === 'staff') {
        return max(0, (int)($_SESSION['aid'] ?? 0));
    }

    return 0;
}

function hms_clear_portal_auth_state() {
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

// Authentication handling for login submissions
if ($logged !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        $input_username = trim($_POST['username']);
        $input_password = $_POST['password'];

        if (!empty($input_username) && !empty($input_password)) {
            // Helper function to verify password and upgrade plaintext to modern BCrypt hash
            $check_auth = function($table, $role) use ($con, $input_username, $input_password) {
                $stmt = mysqli_prepare($con, "SELECT id, username, password FROM `" . $table . "` WHERE username = ? OR email = ? LIMIT 1");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "ss", $input_username, $input_username);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    if ($row = mysqli_fetch_assoc($result)) {
                        $hash = $row['password'];
                        $authenticated = false;
                        
                        // Check if hash matches BCrypt/Argon or legacy plaintext
                        if (password_verify($input_password, $hash)) {
                            $authenticated = true;
                        } elseif (!str_starts_with($hash, '$') && hash_equals($hash, $input_password)) {
                            $authenticated = true;
                            // Transparently migrate plaintext password to secure modern hash
                            $new_hash = password_hash($input_password, PASSWORD_DEFAULT);
                            $up_stmt = mysqli_prepare($con, "UPDATE `" . $table . "` SET password = ? WHERE id = ?");
                            if ($up_stmt) {
                                mysqli_stmt_bind_param($up_stmt, "si", $new_hash, $row['id']);
                                mysqli_stmt_execute($up_stmt);
                                mysqli_stmt_close($up_stmt);
                            }
                        }

                        if ($authenticated) {
                            mysqli_stmt_close($stmt);
                            return $row;
                        }
                    }
                    mysqli_stmt_close($stmt);
                }
                return false;
            };

            // 1. Check member
            $row = $check_auth('member', 'member');
            if ($row) {
                hms_clear_portal_auth_state();
                session_regenerate_id(true);
                $_SESSION['logged'] = true;
                $_SESSION['uid'] = (int)$row['id'];
                $_SESSION['member_id'] = (int)$row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['account'] = "member";
                header("Location: members/Dashboard/member.php");
                exit;
            }

            // 2. Check admin
            $row = $check_auth('admin', 'admin');
            if ($row) {
                hms_clear_portal_auth_state();
                session_regenerate_id(true);
                $_SESSION['logged'] = true;
                $_SESSION['aid'] = (int)$row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['account'] = "admin";
                header("Location: admin/Dashboard/admin.php");
                exit;
            }

            // 3. Check staff
            $row = $check_auth('staff', 'staff');
            if ($row) {
                hms_clear_portal_auth_state();
                session_regenerate_id(true);
                $_SESSION['logged'] = true;
                $_SESSION['aid'] = (int)$row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['account'] = "staff";
                header("Location: staff/Dashboard/staff.php");
                exit;
            }

            // Invalid credentials error flag for login.php
            $_SESSION['login_error'] = "Invalid username or password.";
        }
    }
}
?>

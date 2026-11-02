<?php
require_once("Includes/config.php");
require_once("Includes/session.php");

// If already logged in as admin
if (isset($_SESSION['logged']) && $_SESSION['logged'] === true && isset($_SESSION['account']) && $_SESSION['account'] === 'admin') {
    header("Location: admin/Dashboard/admin.php");
    exit;
}
if (isset($_SESSION['pg_logged']) && $_SESSION['pg_logged'] === true && isset($_SESSION['pg_role']) && $_SESSION['pg_role'] === 'admin') {
    header("Location: admin/Dashboard/admin.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username_or_email) && !empty($password)) {
        // Authenticate from admin table
        $stmt = mysqli_prepare($con, "SELECT id, username, email, password FROM admin WHERE username = ? OR email = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $username_or_email, $username_or_email);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($admin = mysqli_fetch_assoc($res)) {
                if (password_verify($password, $admin['password'])) {
                    hms_clear_portal_auth_state();
                    session_regenerate_id(true);
                    // Set Society Admin Session
                    $_SESSION['logged'] = true;
                    $_SESSION['account'] = 'admin';
                    $_SESSION['admin_id'] = (int)$admin['id'];
                    $_SESSION['username'] = $admin['username'];
                    $_SESSION['email'] = $admin['email'];

                    // Set PG Admin / Warden Session for Unified Control
                    $_SESSION['pg_logged'] = true;
                    $_SESSION['pg_role'] = 'admin';
                    $_SESSION['pg_admin_id'] = (int)$admin['id'];
                    $_SESSION['pg_name'] = $admin['username'];
                    $_SESSION['pg_email'] = $admin['email'];

                    // Redirect to target or Admin Dashboard
                    $target = $_POST['target_portal'] ?? 'society';
                    if ($target === 'pg') {
                        header("Location: pg/admin/dashboard.php");
                    } elseif ($target === 'kyc') {
                        header("Location: admin/society_kyc.php");
                    } elseif ($target === 'students') {
                        header("Location: pg/admin/students.php");
                    } else {
                        header("Location: admin/Dashboard/admin.php");
                    }
                    exit;
                } else {
                    $error = "Invalid administrator password. Access denied.";
                }
            } else {
                $error = "No administrator account found with those credentials.";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $error = "Please enter both administrator username and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Secure Yadav Living administrator sign in for community and Yadav Living PG operations, billing, KYC, and resident management.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#090d16">
    <title>Administrator Sign In | Yadav Living</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Serif+Devanagari:wght@600;700&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/auth-brand.css?v=20261102">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="image/svg+xml" href="home/logo.svg">
    <script defer src="js/auth-ui.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
        body {
            background: linear-gradient(135deg, #090d16 0%, #0f172a 40%, #032541 80%, #0284c7 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 16px;
            color: #f8fafc;
        }
        .sacred-krishna-badge {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(251, 191, 36, 0.35);
            backdrop-filter: blur(12px);
            padding: 8px 24px;
            border-radius: 40px;
            font-size: 13.5px;
            font-weight: 700;
            color: #fef08a;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(245, 158, 11, 0.2);
        }
        .krishna-avatar-round {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
            border: 1.5px solid #fbbf24;
            box-shadow: 0 0 10px rgba(251, 191, 36, 0.6);
        }
        .admin-card {
            background: rgba(15, 23, 42, 0.75);
            border: 1px solid rgba(56, 189, 248, 0.25);
            backdrop-filter: blur(16px);
            width: 100%;
            max-width: 480px;
            border-radius: 18px;
            padding: 38px 34px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5), 0 0 40px rgba(2, 132, 199, 0.15);
        }
        .top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .back-link {
            color: #38bdf8;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: color 0.2s;
        }
        .back-link:hover { color: #7dd3fc; }
        .admin-title-wrap {
            margin-bottom: 24px;
        }
        .admin-shield-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            box-shadow: 0 8px 20px rgba(2, 132, 199, 0.4);
            margin-bottom: 14px;
            font-size: 26px;
            color: #ffffff;
        }
        .admin-title-wrap h1 {
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
        }
        .admin-title-wrap p {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 4px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 18px;
        }
        .form-control-admin {
            width: 100%;
            padding: 12px 14px 12px 42px;
            background: rgba(30, 41, 59, 0.8);
            border: 1.5px solid #334155;
            border-radius: 10px;
            font-size: 14px;
            color: #ffffff;
            outline: none;
            transition: all 0.2s ease;
        }
        .form-control-admin:focus {
            border-color: #38bdf8;
            background: rgba(30, 41, 59, 1);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }
        .target-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }
        .target-option {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid #334155;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12.5px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #cbd5e1;
            transition: all 0.2s;
        }
        .target-option input {
            accent-color: #38bdf8;
        }
        .target-option:hover {
            border-color: #38bdf8;
            color: #fff;
        }
        .btn-admin-submit {
            width: 100%;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(2, 132, 199, 0.4);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-admin-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(2, 132, 199, 0.55);
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .admin-footer-credits {
            margin-top: 24px;
            color: #64748b;
            font-size: 12px;
            text-align: center;
        }
        body {
            background:
                radial-gradient(ellipse at 12% 10%, rgba(245, 185, 66, 0.15), transparent 28%),
                radial-gradient(ellipse at 90% 86%, rgba(2, 132, 199, 0.21), transparent 32%),
                linear-gradient(135deg, #080f1c, #10243a 60%, #0c3447);
        }
        .sacred-krishna-badge {
            padding: 8px 18px 8px 8px;
            margin-bottom: 18px;
            border: 0;
            border-radius: 999px;
            background: transparent;
            box-shadow: none;
            backdrop-filter: none;
        }
        .auth-devotional-brand { margin: 0; }
        .admin-card {
            border-color: rgba(235, 206, 153, 0.24);
            border-radius: 24px;
            padding: 30px 32px;
            background: rgba(13, 25, 40, 0.88);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4), 0 0 34px rgba(2, 132, 199, 0.1);
            animation: adminCardEnter 0.45s ease both;
        }
        @keyframes adminCardEnter {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .top-row { margin-bottom: 22px; }
        .admin-title-wrap { margin-bottom: 22px; }
        .admin-title-wrap h1 {
            font-size: clamp(22px, 4vw, 27px);
            letter-spacing: -0.035em;
        }
        .auth-devotional-name { font-family: 'Noto Serif Devanagari', 'Nirmala UI', serif; }
        .admin-shield-icon {
            border: 1px solid rgba(245, 185, 66, 0.3);
            background: linear-gradient(145deg, #1d7f91, #145d6c);
            box-shadow: 0 8px 20px rgba(2, 132, 199, 0.24);
        }
        .form-control-admin {
            min-height: 48px;
            border-color: #3c4a59;
            border-radius: 12px;
            background: rgba(30, 41, 59, 0.68);
        }
        .form-control-admin:focus {
            border-color: #64c5c6;
            box-shadow: 0 0 0 4px rgba(94, 234, 212, 0.12);
        }
        .form-control-admin { padding-right: 50px; }
        .input-wrap .password-toggle-btn {
            position: absolute;
            top: 50%;
            right: 10px;
            left: auto;
            display: grid;
            width: 34px;
            height: 34px;
            padding: 0;
            place-items: center;
            transform: translateY(-50%);
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #a6b3c1;
            cursor: pointer;
        }
        .input-wrap .password-toggle-btn:hover {
            background: rgba(148, 163, 184, 0.14);
            color: #f8fafc;
        }
        .input-wrap .password-toggle-btn:focus-visible {
            outline: 2px solid #5eead4;
            outline-offset: 2px;
        }
        .input-wrap .password-toggle-btn i {
            position: static;
            transform: none;
            color: inherit;
        }
        .target-option {
            min-height: 44px;
            border-radius: 10px;
        }
        .target-option:has(input:checked) {
            border-color: rgba(94, 234, 212, 0.55);
            background: rgba(20, 93, 108, 0.28);
            color: #f8fafc;
        }
        .target-option input { accent-color: #5eead4; }
        .btn-admin-submit {
            min-height: 48px;
            border-radius: 12px;
            background: linear-gradient(110deg, #145d6c, #167f83);
            box-shadow: 0 10px 24px rgba(2, 132, 199, 0.22);
        }
        .btn-admin-submit:hover {
            background: linear-gradient(110deg, #124e5c, #126f75);
            box-shadow: 0 12px 26px rgba(2, 132, 199, 0.3);
        }
        .admin-footer-credits { color: #a6b3c1; }
        @media (max-width: 520px) {
            body { padding: 22px 14px; }
            .admin-card { padding: 24px 20px; border-radius: 20px; }
            .target-selector { gap: 8px; }
            .target-option { padding: 9px 8px; font-size: 11.5px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .admin-card { animation: none; }
        }
    </style>
</head>
<body>

    <div class="sacred-krishna-badge">
        <picture>
            <source srcset="images/sri_krishna_thumb.webp" type="image/webp">
            <img src="images/sri_krishna_modern.jpg" alt="" width="48" height="48" loading="eager" decoding="async" fetchpriority="high">
        </picture>
        <span>
            <span class="auth-devotional-name" lang="hi">जय श्री कृष्ण</span>
            <span class="auth-devotional-rule" aria-hidden="true"></span>
        </span>
    </div>

    <div class="admin-card">
        <div class="top-row">
            <a href="home/home.php" class="back-link">
                <i class='bx bx-left-arrow-alt'></i> Back to Website
            </a>
            <div style="font-size: 11px; background: rgba(56, 189, 248, 0.15); color: #38bdf8; padding: 4px 10px; border-radius: 12px; font-weight: 700; border: 1px solid rgba(56, 189, 248, 0.3);">
                SUPER ADMIN
            </div>
        </div>

        <div class="admin-title-wrap">
            <div class="admin-shield-icon">
                <i class='bx bxs-shield'></i>
            </div>
            <h1>Dedicated Administrator Portal</h1>
            <p>Choose the separate Society or Yadav Living PG Warden Desk</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error">
                <i class='bx bx-error-circle' style="font-size: 18px;"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form action="admin_login.php" method="POST">
            <div class="form-group">
                <label for="admin-username">Admin Username or Email</label>
                <div class="input-wrap">
                    <i class='bx bx-user'></i>
                    <input type="text" id="admin-username" name="username" class="form-control-admin" placeholder="Enter your admin username or email" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label for="admin-password">Admin Password</label>
                <div class="input-wrap">
                    <i class='bx bx-lock-alt'></i>
                    <input type="password" id="admin-password" name="password" class="form-control-admin" placeholder="Enter administrator password" required autocomplete="current-password">
                </div>
            </div>

            <div class="form-group">
                <label>Choose a Warden Desk</label>
                <div class="target-selector">
                    <label class="target-option">
                        <input type="radio" name="target_portal" value="society" checked>
                        <span>Society Warden Desk</span>
                    </label>
                    <label class="target-option">
                        <input type="radio" name="target_portal" value="pg">
                        <span>Yadav Living PG Warden Desk</span>
                    </label>
                    <label class="target-option">
                        <input type="radio" name="target_portal" value="students">
                        <span>PG Warden Desk &middot; Student Residents</span>
                    </label>
                    <label class="target-option">
                        <input type="radio" name="target_portal" value="kyc">
                        <span>Society Warden Desk &middot; Resident KYC</span>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn-admin-submit">
                <i class='bx bx-log-in-circle' style="font-size: 18px;"></i> Authenticate &amp; Open Admin Control &rarr;
            </button>
        </form>

        <div style="margin-top: 22px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.08); font-size: 12px; color: #94a3b8; text-align: center;">
            Society Resident or PG Student? <a href="login.php" style="color: #38bdf8; text-decoration: none; font-weight: 600;">Member Sign In &rarr;</a>
        </div>
    </div>

    <div class="admin-footer-credits">
        &copy; 2019 &ndash; <?php echo date("Y"); ?> Yadav Living &bull; Yadav Living PG
    </div>

</body>
</html>

<?php
require_once("Includes/session.php");

// If already logged in to PG
if (isset($_SESSION['pg_logged']) && $_SESSION['pg_logged'] === true) {
    if (isset($_SESSION['pg_role']) && $_SESSION['pg_role'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    } else {
        header("Location: student/profile.php");
        exit;
    }
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim(isset($_POST['username']) ? $_POST['username'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (!empty($username_or_email) && !empty($password)) {
        // 1. Check if PG Student
        $stmt = mysqli_prepare($con, "SELECT id, name, username, email, password, status FROM pg_students WHERE username = ? OR email = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $username_or_email, $username_or_email);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($student = mysqli_fetch_assoc($res)) {
                if (password_verify($password, $student['password'])) {
                    hms_pg_clear_portal_auth_state();
                    session_regenerate_id(true);
                    $_SESSION['pg_logged'] = true;
                    $_SESSION['pg_role'] = 'student';
                    $_SESSION['pg_student_id'] = (int)$student['id'];
                    $_SESSION['pg_name'] = $student['name'];
                    $_SESSION['pg_email'] = $student['email'];
                    $_SESSION['pg_username'] = $student['username'];

                    // Update last login
                    mysqli_query($con, "UPDATE pg_students SET last_login = NOW() WHERE id = " . (int)$student['id']);

                    header("Location: student/profile.php");
                    exit;
                } else {
                    $error = "Invalid password. Please check your credentials.";
                }
            } else {
                // 2. Check if Admin / Warden (from main admin table)
                $adm_stmt = mysqli_prepare($con, "SELECT id, username, email, password FROM admin WHERE username = ? OR email = ? LIMIT 1");
                if ($adm_stmt) {
                    mysqli_stmt_bind_param($adm_stmt, "ss", $username_or_email, $username_or_email);
                    mysqli_stmt_execute($adm_stmt);
                    $adm_res = mysqli_stmt_get_result($adm_stmt);
                    if ($admin = mysqli_fetch_assoc($adm_res)) {
                        if (password_verify($password, $admin['password'])) {
                            hms_pg_clear_portal_auth_state();
                            session_regenerate_id(true);
                            $_SESSION['pg_logged'] = true;
                            $_SESSION['pg_role'] = 'admin';
                            $_SESSION['pg_admin_id'] = (int)$admin['id'];
                            $_SESSION['pg_name'] = $admin['username'];
                            $_SESSION['pg_email'] = $admin['email'];

                            header("Location: admin/dashboard.php");
                            exit;
                        } else {
                            $error = "Invalid password for PG administrator.";
                        }
                    } else {
                        $error = "No active student or warden account found with that email/username.";
                    }
                    mysqli_stmt_close($adm_stmt);
                }
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $error = "Please enter both username and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign in to the Yadav Living PG student portal to manage your profile, KYC, payments, and accommodation updates.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#07111c">
    <title>Student Sign In | Yadav Living</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Serif+Devanagari:wght@600;700&display=swap">
    <link rel="stylesheet" href="../css/auth-brand.css?v=20261102">
    <link rel="icon" type="image/svg+xml" href="../home/logo.svg">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&family=Noto+Serif+Devanagari:wght@600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }
        body {
            background-color: #0099e5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: #ffffff;
            width: 100%;
            max-width: 440px;
            border-radius: 12px;
            padding: 40px 36px 36px 36px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
            position: relative;
        }
        .back-nav {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #0099e5;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            margin-bottom: 20px;
        }
        .back-nav:hover {
            text-decoration: underline;
        }
        .login-header {
            text-align: right;
            margin-bottom: 28px;
        }
        .login-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
        }
        .login-header p {
            font-size: 13px;
            color: #64748b;
            margin-top: 2px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            color: #334155;
            font-weight: 500;
            margin-bottom: 6px;
        }
        .form-group label span.req {
            color: #ef4444;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap input {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            background: #f1f5f9;
            transition: all 0.2s ease;
        }
        .input-wrap input:focus {
            border-color: #6366f1;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        .input-wrap .pwd-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            font-size: 18px;
        }
        .remember-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
        }
        .remember-wrap input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #0099e5;
            cursor: pointer;
        }
        .remember-wrap label {
            font-size: 13px;
            color: #475569;
            cursor: pointer;
            user-select: none;
        }
        .btn-submit {
            width: 100%;
            background: #0099e5;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .btn-submit:hover {
            background: #0088cc;
        }
        .forgot-link {
            text-align: center;
            margin-top: 20px;
        }
        .forgot-link a {
            color: #64748b;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .forgot-link a:hover {
            color: #0099e5;
        }
        .footer-credits {
            margin-top: 32px;
            color: rgba(255, 255, 255, 0.85);
            font-size: 12px;
            text-align: center;
        }
        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 18px;
        }
        .quick-roles {
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px dashed #e2e8f0;
            text-align: center;
            font-size: 11px;
            color: #64748b;
        }
        .quick-roles a {
            color: #0099e5;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            margin: 0 4px;
        }
        body {
            background:
                radial-gradient(circle at 8% 12%, rgba(245, 185, 66, 0.18), transparent 28%),
                radial-gradient(circle at 92% 84%, rgba(2, 132, 199, 0.2), transparent 30%),
                linear-gradient(135deg, #07111c 0%, #10283a 55%, #0b3b52 100%);
            padding: 28px 16px;
        }
        .login-card {
            max-width: 460px;
            padding: 30px 34px 32px;
            border: 1px solid rgba(255, 255, 255, 0.76);
            border-radius: 24px;
            box-shadow: 0 28px 70px rgba(2, 12, 24, 0.32), 0 2px 8px rgba(15, 23, 42, 0.08);
            animation: authCardEnter 0.45s ease both;
        }
        @keyframes authCardEnter {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-card > div:first-child {
            margin-bottom: 20px !important;
        }
        .auth-devotional-brand {
            display: inline-flex;
            text-align: left;
        }
        .login-card > div:nth-child(2) {
            margin-bottom: 24px !important;
        }
        .back-nav {
            color: #3d7180;
            font-weight: 600;
        }
        .login-header {
            text-align: left;
            margin-bottom: 22px;
        }
        .login-header h2 {
            color: #102b3d;
            font-family: 'Outfit', 'Poppins', sans-serif;
            font-size: 30px;
            font-weight: 700;
            letter-spacing: -0.04em;
        }
        .login-header p {
            margin-top: 5px;
            line-height: 1.6;
        }
        .form-group {
            margin-bottom: 17px;
        }
        .form-group label {
            color: #344b5a;
            font-weight: 600;
        }
        .input-wrap input {
            min-height: 48px;
            padding: 12px 46px 12px 14px;
            border-color: #d7e0e5;
            border-radius: 12px;
            background: #f8fafb;
        }
        .input-wrap input:focus {
            border-color: #2a8b9b;
            box-shadow: 0 0 0 4px rgba(42, 139, 155, 0.12);
        }
        .input-wrap .pwd-toggle {
            right: 12px;
            width: 36px;
            height: 36px;
            border-radius: 9px;
        }
        .input-wrap .pwd-toggle:hover {
            background: #eaf3f3;
        }
        .remember-wrap {
            margin-bottom: 20px;
        }
        .remember-wrap input[type="checkbox"] {
            accent-color: #167c86;
        }
        .btn-submit {
            min-height: 49px;
            border-radius: 12px;
            background: linear-gradient(110deg, #145d6c, #167f83);
            box-shadow: 0 10px 20px rgba(22, 111, 121, 0.2);
            font-weight: 700;
        }
        .btn-submit:hover {
            background: linear-gradient(110deg, #124e5c, #126f75);
            transform: translateY(-1px);
        }
        .footer-credits {
            color: rgba(235, 243, 244, 0.76);
        }
        @media (max-width: 480px) {
            body { padding: 18px 12px; }
            .login-card { padding: 24px 20px 26px; border-radius: 20px; }
            .login-header h2 { font-size: 27px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .login-card { animation: none; }
        }
        .login-card {
            font-family: 'Outfit', 'Poppins', sans-serif;
        }
        .auth-devotional-name {
            font-family: 'Noto Serif Devanagari', 'Nirmala UI', serif;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="auth-devotional-brand" lang="hi" aria-label="जय श्री कृष्ण">
            <picture>
                <source srcset="../images/sri_krishna_thumb.webp" type="image/webp">
                <img src="../images/sri_krishna_modern.jpg" alt="" width="48" height="48" loading="eager" decoding="async" fetchpriority="high">
            </picture>
            <span>
                <span class="auth-devotional-name">जय श्री कृष्ण</span>
                <span class="auth-devotional-rule" aria-hidden="true"></span>
            </span>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <a href="../home/home.php" class="back-nav" style="margin-bottom: 0;">
                <i class='bx bx-left-arrow-alt'></i> Back to Yadav Living
            </a>
            <div style="font-weight: 800; color: #0099e5; font-size: 18px; display: flex; align-items: center; gap: 6px;">
                <img src="../home/logo.svg" alt="" width="24" height="24" style="border-radius: 6px;"> Yadav Living
            </div>
        </div>

        <div class="login-header">
            <h2>Welcome back</h2>
            <p>Sign in to your Yadav Living PG student account.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error">
                <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Username or Email <span class="req">*</span></label>
                <div class="input-wrap">
                    <input type="text" id="username" name="username" placeholder="Enter your username or email" required autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password <span class="req">*</span></label>
                <div class="input-wrap">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                    <button type="button" class="pwd-toggle" id="togglePwd" aria-label="Toggle password visibility">
                        <i class='bx bx-show' id="pwdIcon"></i>
                    </button>
                </div>
            </div>

            <div class="remember-wrap">
                <input type="checkbox" id="remember" name="remember" checked>
                <label for="remember">Remember me</label>
            </div>

            <button type="submit" class="btn-submit">Sign In</button>

            <a href="register.php" style="display: flex; align-items: center; justify-content: center; gap: 6px; width: 100%; margin-top: 14px; padding: 12px; background: #f0fdf4; color: #166534; border: 1.5px dashed #86efac; border-radius: 8px; font-weight: 600; font-size: 13.5px; text-decoration: none; transition: all 0.2s;">
                <i class='bx bx-user-plus' style='font-size: 18px;'></i> New Student? Register for Yadav Living PG &rarr;
            </a>

            <div class="forgot-link">
                <a href="javascript:void(0)" onclick="alert('Please contact Owners Binod Yadav & Sumit Yadav, or Admin Yuvraj to reset your password.')">
                    <i class='bx bx-lock-alt'></i> Forgot your password?
                </a>
            </div>

            <div style="background: #0f172a; border-radius: 8px; padding: 10px; text-align: center; margin-top: 14px; margin-bottom: 12px;">
                <a href="../admin_login.php" style="color: #38bdf8; font-weight: 700; font-size: 12.5px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                    🛡️ Society &amp; PG Admin? Dedicated Admin Portal &rarr;
                </a>
            </div>

        </form>
    </div>

    <div class="footer-credits">
        &copy; 2019 - <?php echo date("Y"); ?> Yadav Living PG
    </div>

    <script>
        const toggleBtn = document.getElementById('togglePwd');
        const pwdInput = document.getElementById('password');
        const pwdIcon = document.getElementById('pwdIcon');
        toggleBtn.addEventListener('click', function() {
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                pwdIcon.classList.replace('bx-show', 'bx-hide');
            } else {
                pwdInput.type = 'password';
                pwdIcon.classList.replace('bx-hide', 'bx-show');
            }
        });
    </script>
</body>
</html>

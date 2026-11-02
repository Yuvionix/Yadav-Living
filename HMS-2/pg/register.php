<?php
require_once("Includes/session.php");

// If already logged in, redirect
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
$success = "";

// Handle student registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(isset($_POST['name']) ? $_POST['name'] : (isset($_POST['full_name']) ? $_POST['full_name'] : ''));
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $phone = trim(isset($_POST['phone']) ? $_POST['phone'] : '');
    $college = trim(isset($_POST['college']) ? $_POST['college'] : (isset($_POST['college_work']) ? $_POST['college_work'] : 'AS College Deoghar'));
    $location = trim(isset($_POST['location']) ? $_POST['location'] : 'Deoghar, Jharkhand');
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($name) > 100 || strlen($email) > 100 || strlen($phone) > 20 || strlen($college) > 150 || strlen($location) > 255) {
        $error = "Please check the length of your details and try again.";
    } elseif (!preg_match('/^\+?[0-9\s()-]{7,20}$/', $phone)) {
        $error = "Please enter a valid phone or WhatsApp number.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Password and confirm password do not match.";
    } else {
        // Check email uniqueness in pg_students
        $chk_stmt = mysqli_prepare($con, "SELECT id FROM pg_students WHERE email = ? LIMIT 1");
        if (!$chk_stmt) {
            error_log("PG student registration email-check prepare failed: " . mysqli_error($con));
            $error = "Registration is temporarily unavailable. Please try again later.";
        } else {
            mysqli_stmt_bind_param($chk_stmt, "s", $email);
            if (!mysqli_stmt_execute($chk_stmt)) {
                error_log("PG student registration email-check failed: " . mysqli_stmt_error($chk_stmt));
                $error = "Registration is temporarily unavailable. Please try again later.";
                mysqli_stmt_close($chk_stmt);
            } else {
                mysqli_stmt_store_result($chk_stmt);
                $email_exists = mysqli_stmt_num_rows($chk_stmt) > 0;
                mysqli_stmt_close($chk_stmt);

                if ($email_exists) {
                    $error = "This email is already registered. Please sign in instead.";
                } else {
                    $email_prefix = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $email)[0]));
                    $username = substr($email_prefix !== '' ? $email_prefix : 'student', 0, 34) . bin2hex(random_bytes(5));
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Insert student without room/bed (allocated by Owners Binod Yadav & Sumit Yadav upon room visit)
                    if (!mysqli_begin_transaction($con)) {
                        error_log("PG student registration transaction start failed: " . mysqli_error($con));
                        $error = "Registration is temporarily unavailable. Please try again later.";
                    } else {
                        $ins_stmt = mysqli_prepare($con, "INSERT INTO pg_students (name, username, email, password, phone, college, room_id, bed_id, rent_amount, deposit_amount, joined_date, status, created_by, location, photo, last_login) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, 0.00, 0.00, CURDATE(), 'active', 'Self Online Sign Up', ?, 'default_student.png', NOW())");
                        if (!$ins_stmt) {
                            mysqli_rollback($con);
                            error_log("PG student registration insert prepare failed: " . mysqli_error($con));
                            $error = "Registration is temporarily unavailable. Please try again later.";
                        } else {
                            mysqli_stmt_bind_param($ins_stmt, "sssssss", $name, $username, $email, $hashed_password, $phone, $college, $location);
                            if (mysqli_stmt_execute($ins_stmt)) {
                                $new_student_id = mysqli_insert_id($con);
                                mysqli_stmt_close($ins_stmt);

                                $kyc_stmt = mysqli_prepare($con, "INSERT INTO pg_kyc (student_id, id_type, id_number, address, status, submitted_at) VALUES (?, 'Aadhaar Card', 'Pending Upload', ?, 'Pending', NOW())");
                                if (!$kyc_stmt) {
                                    mysqli_rollback($con);
                                    error_log("PG student registration KYC prepare failed: " . mysqli_error($con));
                                    $error = "Registration is temporarily unavailable. Please try again later.";
                                } else {
                                    mysqli_stmt_bind_param($kyc_stmt, "is", $new_student_id, $location);
                                    if (!mysqli_stmt_execute($kyc_stmt)) {
                                        error_log("PG student registration KYC insert failed: " . mysqli_stmt_error($kyc_stmt));
                                        mysqli_stmt_close($kyc_stmt);
                                        mysqli_rollback($con);
                                        $error = "Registration is temporarily unavailable. Please try again later.";
                                    } else {
                                        mysqli_stmt_close($kyc_stmt);

                                        if (!mysqli_commit($con)) {
                                            error_log("PG student registration transaction commit failed: " . mysqli_error($con));
                                            mysqli_rollback($con);
                                            $error = "Registration is temporarily unavailable. Please try again later.";
                                        } else {
                                            // Auto-login to PG student session
                                            hms_pg_clear_portal_auth_state();
                                            session_regenerate_id(true);
                                            $_SESSION['pg_logged'] = true;
                                            $_SESSION['pg_role'] = 'student';
                                            $_SESSION['pg_student_id'] = $new_student_id;
                                            $_SESSION['pg_name'] = $name;
                                            $_SESSION['pg_email'] = $email;
                                            $_SESSION['pg_username'] = $username;

                                            header("Location: student/kyc.php?new=1");
                                            exit;
                                        }
                                    }
                                }
                            } else {
                                mysqli_rollback($con);
                                error_log("PG student registration insert failed: " . mysqli_stmt_error($ins_stmt));
                                $error = "Registration could not be completed. Please verify your details and try again.";
                                mysqli_stmt_close($ins_stmt);
                            }
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Register for Yadav Living PG student accommodation in Deoghar. Create your student account and continue to secure KYC verification.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#07111c">
    <title>Student Registration | Yadav Living</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="image/svg+xml" href="../home/logo.svg">
    <script defer src="../js/auth-ui.js"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }
        body {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 16px;
        }
        .slogan-banner {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #fef08a;
            padding: 8px 24px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }
        .reg-card {
            background: #ffffff;
            width: 100%;
            max-width: 580px;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            position: relative;
            animation: authCardIn 420ms ease both;
        }
        @keyframes authCardIn {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .back-nav {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #0284c7;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }
        .back-nav:hover {
            color: #0369a1;
            text-decoration: underline;
        }
        .reg-header {
            margin: 18px 0 24px 0;
        }
        .reg-header h2 {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
        }
        .reg-header h1 {
            font-size: clamp(22px, 5vw, 28px);
            font-weight: 800;
            line-height: 1.2;
            color: #0f172a;
        }
        .reg-header p {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group.full {
            grid-column: 1 / -1;
        }
        .form-group label {
            display: block;
            font-size: 12px;
            color: #334155;
            font-weight: 600;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-group label span.req {
            color: #ef4444;
        }
        .form-control-reg {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            outline: none;
            background: #f8fafc;
            transition: all 0.2s ease;
        }
        .form-control-reg:focus {
            border-color: #0284c7;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
        }
        .auth-password-wrap {
            position: relative;
        }
        .auth-password-wrap .form-control-reg {
            padding-right: 44px;
        }
        .auth-form-note {
            display: block;
            min-height: 18px;
            padding-top: 4px;
            color: #64748b;
            font-size: 11px;
        }
        .auth-form-note.is-error {
            color: #b91c1c;
        }
        .registration-steps {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 16px;
            color: #64748b;
            font-size: 11px;
        }
        .registration-steps span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 9px;
            border-radius: 999px;
            background: #f1f5f9;
        }
        .registration-steps span:first-child {
            color: #0369a1;
            background: #e0f2fe;
            font-weight: 700;
        }
        .btn-submit-reg {
            width: 100%;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 14px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35);
            transition: all 0.2s ease;
            margin-top: 10px;
        }
        .btn-submit-reg:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(2, 132, 199, 0.45);
        }
        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .footer-credits {
            margin-top: 24px;
            color: rgba(255, 255, 255, 0.85);
            font-size: 12px;
            text-align: center;
        }
        @media (max-width: 600px) {
            body {
                justify-content: flex-start;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .reg-card {
                padding: 24px 18px;
            }
        }
        @media (prefers-reduced-motion: reduce) {
            .reg-card {
                animation: none;
            }
        }
    </style>
</head>
<body>

    <!-- Auspicious Slogan with Sri Krishna Image -->
    <div class="slogan-banner">
        <picture>
            <source srcset="../images/sri_krishna_thumb.webp" type="image/webp">
            <img src="../images/sri_krishna_modern.jpg" alt="Sri Krishna" width="24" height="24" loading="lazy" decoding="async" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; border: 1.5px solid #fef08a;">
        </picture>
        <span>🙏 जय श्री कृष्णा</span> &bull; <span>Jai Shree Krishna</span>
    </div>

    <div class="reg-card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <a href="login.php" class="back-nav">
                <i class='bx bx-left-arrow-alt'></i> Back to Sign In
            </a>
            <div style="font-weight: 800; color: #0284c7; font-size: 18px; display: flex; align-items: center; gap: 4px;">
                <img src="../home/logo.svg" alt="" width="24" height="24" style="border-radius: 6px;"> Yadav Living
            </div>
        </div>

        <div class="reg-header">
            <h1>Join Yadav Living PG</h1>
            <p>New Student Online Registration &bull; Pure Accommodation (Food Not Provided)</p>
            <div class="registration-steps" aria-label="Registration steps">
                <span>1. Create account</span><i class='bx bx-chevron-right' aria-hidden="true"></i>
                <span>2. Complete KYC</span><i class='bx bx-chevron-right' aria-hidden="true"></i>
                <span>3. Visit for room allocation</span>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error" role="alert">
                <i class='bx bx-error-circle' style="font-size: 18px;"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" data-password-match>
            <div class="form-grid">
                <div class="form-group full">
                    <label>Student Full Name <span class="req">*</span></label>
                    <input type="text" name="name" class="form-control-reg" placeholder="e.g. Amit Sharma" maxlength="100" autocomplete="name" required value="<?php echo htmlspecialchars(isset($_POST['name']) ? $_POST['name'] : ''); ?>">
                </div>

                <div class="form-group">
                    <label>Email Address <span class="req">*</span></label>
                    <input type="email" name="email" class="form-control-reg" placeholder="e.g. amit@gmail.com" maxlength="100" autocomplete="email" required value="<?php echo htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : ''); ?>">
                </div>

                <div class="form-group">
                    <label>Phone / WhatsApp <span class="req">*</span></label>
                    <input type="tel" name="phone" class="form-control-reg" placeholder="e.g. +91 98351 23456" maxlength="20" autocomplete="tel" required value="<?php echo htmlspecialchars(isset($_POST['phone']) ? $_POST['phone'] : ''); ?>">
                </div>

                <div class="form-group">
                    <label>College / Coaching</label>
                    <input type="text" name="college" class="form-control-reg" placeholder="e.g. AS College Deoghar" maxlength="150" autocomplete="organization" value="<?php echo htmlspecialchars(isset($_POST['college']) ? $_POST['college'] : 'AS College Deoghar'); ?>">
                </div>

                <div class="form-group">
                    <label>Hometown / Address</label>
                    <input type="text" name="location" class="form-control-reg" placeholder="e.g. Patna, Bihar" maxlength="255" autocomplete="address-level2" value="<?php echo htmlspecialchars(isset($_POST['location']) ? $_POST['location'] : ''); ?>">
                </div>

                <div class="form-group full" style="margin-bottom: 6px;">
                    <div style="background: #e0f2fe; border: 1.5px solid #bae6fd; border-radius: 8px; padding: 12px 16px; display: flex; align-items: center; gap: 10px; font-size: 12.5px; color: #0369a1; line-height: 1.45;">
                        <i class='bx bx-info-circle' style="font-size: 22px; color: #0284c7; flex-shrink: 0;"></i>
                        <div>
                            <b>Room & Bed Allocation:</b> Pure student accommodation (food/mess is not provided). Room and bed selection & stay terms are finalized directly with <b>Owners Binod Yadav & Sumit Yadav</b> upon room visit.
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Create Password <span class="req">*</span></label>
                    <div class="auth-password-wrap">
                        <input type="password" name="password" class="form-control-reg" placeholder="Min 6 characters" minlength="6" required autocomplete="new-password">
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password <span class="req">*</span></label>
                    <div class="auth-password-wrap">
                        <input type="password" name="confirm_password" class="form-control-reg" placeholder="Re-enter password" minlength="6" required autocomplete="new-password" aria-describedby="pg-password-feedback">
                    </div>
                    <span class="auth-form-note" id="pg-password-feedback" data-password-feedback aria-live="polite"></span>
                </div>
            </div>

            <button type="submit" class="btn-submit-reg">
                <i class='bx bx-check-circle'></i> Complete Registration &amp; Proceed to KYC &rarr;
            </button>

            <div style="text-align: center; margin-top: 20px; font-size: 13px; color: #64748b;">
                Already have a room at Yadav Living PG? <a href="login.php" style="color: #0284c7; font-weight: 700; text-decoration: none;">Sign In here &rarr;</a>
            </div>
        </form>
    </div>

    <div class="footer-credits">
        &copy; 2019 - <?php echo date("Y"); ?> Yadav Living PG
    </div>

</body>
</html>

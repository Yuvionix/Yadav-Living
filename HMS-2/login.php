<?php 
require_once("Includes/config.php");
require_once("Includes/session.php");

if (isset($_SESSION['logged']) && $_SESSION['logged'] === true) {
    if (isset($_SESSION['account']) && $_SESSION['account'] === "admin") {
        header("Location: admin/Dashboard/admin.php");
        exit;
    } elseif (isset($_SESSION['account']) && $_SESSION['account'] === "member") {
        header("Location: members/Dashboard/member.php");
        exit;
    } elseif (isset($_SESSION['account']) && $_SESSION['account'] === "staff") {
        header("Location: staff/Dashboard/staff.php");
        exit;
    }
}

$error_msg = "";
if (isset($_SESSION['login_error'])) {
    $error_msg = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Secure Yadav Living resident sign in for community services and resident billing in Deoghar.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#07111c">
    <title>Resident Sign In | Yadav Living</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Serif+Devanagari:wght@600;700&display=swap">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/auth-brand.css?v=20261102">
    <link rel="stylesheet" href="style.css?v=20261102">
    <link rel="icon" type="image/svg+xml" href="home/logo.svg">
    <link rel="shortcut icon" href="home/logo.svg">
    <script defer src="js/auth-ui.js"></script>
</head>
<body class="auth-page">
    <div class="container">
        <div class="row">
            <div class="form login-form">
                <div class="auth-devotional-brand" lang="hi" aria-label="जय श्री कृष्ण">
                    <picture>
                        <source srcset="images/sri_krishna_thumb.webp" type="image/webp">
                        <img src="images/sri_krishna_modern.jpg" alt="" width="48" height="48" loading="eager" decoding="async" fetchpriority="high">
                    </picture>
                    <span>
                        <span class="auth-devotional-name">जय श्री कृष्ण</span>
                        <span class="auth-devotional-rule" aria-hidden="true"></span>
                    </span>
                </div>
                <h2 class="text-center font-weight-bold">Welcome back</h2>
                <p class="text-center text-muted">Sign in to your resident account to continue.</p>

                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger text-center p-2 mb-3" style="font-size: 14px;">
                        <?php echo htmlspecialchars($error_msg); ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label class="small text-muted mb-1" for="resident-username">Username or Email</label>
                        <input type="text" id="resident-username" name="username" placeholder="Enter username or email" class="form-control" required autocomplete="username">
                    </div>    
                    <div class="form-group">
                        <label class="small text-muted mb-1" for="resident-password">Password</label>
                        <div class="auth-password-wrap">
                            <input type="password" id="resident-password" name="password" placeholder="Enter password" class="form-control" required autocomplete="current-password">
                        </div>
                    </div>
                    <div class="form-group">
                       <input class="form-control button btn-primary font-weight-bold" type="submit" name="login" value="Sign in">
                    </div>
                    <hr>
                    <div class="auth-portal-switch">
                        <span class="auth-portal-question">Staying at Yadav Living PG?</span><br>
                        <a href="pg/login.php">
                            Access Yadav Living PG Portal &rarr;
                        </a>
                        <span class="auth-switch-divider" aria-hidden="true">&bull;</span>
                        <a href="pg/register.php">
                            New Student Sign Up &rarr;
                        </a>
                    </div>
                    <div class="text-center mb-2">
                        <span class="text-muted small">Resident without an account? </span>
                        <a href="register.php" class="small font-weight-bold">Register as Member</a>
                    </div>
                    <div class="auth-admin-switch">
                        <a href="admin_login.php">
                            <i class='bx bxs-shield' aria-hidden="true"></i> Society Administrator? Dedicated Admin Login &rarr;
                        </a>
                    </div>
                    <div class="link login-link text-center">
                        <a href="home/home.php" class="text-secondary small">← Back To Home</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

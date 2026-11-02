<?php
require_once(__DIR__ . "/../Includes/session.php");
require_pg_role('student', '../login.php');

$current_student_id = hms_pg_current_student_id();

// Fetch active student profile info
$q_stu = mysqli_query($con, "SELECT s.*, r.room_number, r.floor, r.occupancy_type, b.bed_label 
                             FROM pg_students s 
                             LEFT JOIN pg_rooms r ON s.room_id = r.id 
                             LEFT JOIN pg_beds b ON s.bed_id = b.id 
                             WHERE s.id = $current_student_id LIMIT 1");
$current_student = mysqli_fetch_assoc($q_stu);
if (!$current_student) {
    http_response_code(403);
    exit('Student account is unavailable. Please contact the PG warden.');
}
$active_page = basename($_SERVER['PHP_SELF'], ".php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Student Portal'; ?> | Yadav Living</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            background-color: #f1f5f9;
            min-height: 100vh;
            color: #1e293b;
        }
        /* Sky-Blue Sidebar */
        .pg-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 230px;
            background: #0099e5;
            z-index: 1000;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
        }
        .pg-sidebar.collapsed {
            width: 68px;
        }
        .pg-logo {
            padding: 24px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #ffffff;
            font-size: 19px;
            font-weight: 700;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            text-decoration: none;
        }
        .pg-logo i {
            font-size: 24px;
        }
        .pg-logo img {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            flex-shrink: 0;
        }
        .pg-nav-list {
            list-style: none;
            padding: 18px 0;
            flex: 1;
            overflow-y: auto;
        }
        .pg-nav-item {
            margin-bottom: 4px;
        }
        .pg-nav-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 22px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-size: 14px;
            font-weight: 400;
            transition: all 0.2s ease;
        }
        .pg-nav-link:hover, .pg-nav-link.active {
            background: rgba(255, 255, 255, 0.18);
            color: #ffffff;
            font-weight: 500;
        }
        .pg-nav-link i {
            font-size: 20px;
            min-width: 24px;
            text-align: center;
        }
        .pg-sidebar.collapsed .link-text, .pg-sidebar.collapsed .pg-logo span {
            display: none;
        }
        .pg-bottom-nav {
            padding: 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }
        /* Top Navigation Header */
        .pg-header {
            position: fixed;
            top: 0;
            left: 230px;
            right: 0;
            height: 64px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            z-index: 900;
            transition: all 0.3s ease;
        }
        .pg-sidebar.collapsed ~ .pg-header {
            left: 68px;
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .toggle-btn {
            background: none;
            border: none;
            font-size: 24px;
            color: #0099e5;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        .pg-property-name {
            font-size: 17px;
            font-weight: 600;
            color: #1e293b;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 14px;
            position: relative;
        }
        .student-profile-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            user-select: none;
            padding: 6px 12px;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .student-profile-badge:hover {
            background: #f1f5f9;
        }
        .student-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #0f172a;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            overflow: hidden;
        }
        .student-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .student-name-text {
            font-size: 13px;
            font-weight: 500;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        /* Profile Dropdown */
        .profile-popover {
            position: absolute;
            right: 0;
            top: 50px;
            background: #ffffff;
            width: 220px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
            display: none;
            flex-direction: column;
            overflow: hidden;
            z-index: 1000;
        }
        .popover-header {
            padding: 12px 16px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .popover-header strong {
            display: block;
            font-size: 13px;
            color: #0f172a;
        }
        .popover-header small {
            color: #0099e5;
            font-weight: 600;
        }
        .popover-item {
            padding: 10px 16px;
            color: #475569;
            text-decoration: none;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }
        .popover-item:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        /* Main Layout Content */
        .pg-main-content {
            margin-left: 230px;
            padding: 88px 32px 32px 32px;
            transition: all 0.3s ease;
        }
        .pg-sidebar.collapsed ~ .pg-main-content {
            margin-left: 68px;
        }
        .page-title-banner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .page-title-banner h2 {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-create-action {
            background: #0099e5;
            color: #ffffff;
            border: none;
            border-radius: 20px;
            padding: 8px 24px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(0, 153, 229, 0.3);
            transition: all 0.2s ease;
        }
        .btn-create-action:hover {
            background: #0088cc;
            transform: translateY(-1px);
        }
        /* Clean Data Table */
        .pg-table-card {
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .pg-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        .pg-table th {
            background: #ffffff;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
            padding: 16px 20px;
            border-bottom: 1.5px solid #e2e8f0;
            text-transform: capitalize;
        }
        .pg-table td {
            padding: 16px 20px;
            font-size: 13px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }
        .no-data-cell {
            text-align: center;
            padding: 40px !important;
            color: #64748b;
            font-size: 13px;
            background: #f8fafc;
        }
        /* Modal Overlay */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(3px);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
        }
        .modal-box {
            background: #ffffff;
            width: 90%;
            max-width: 520px;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            animation: fadeIn 0.2s ease;
        }
        .modal-box-header {
            padding: 16px 24px;
            background: #0099e5;
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-box-header h4 {
            font-size: 16px;
            font-weight: 600;
        }
        .modal-close-btn {
            background: none;
            border: none;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
        }
        .modal-box-body {
            padding: 24px;
        }
        .form-control-pg {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            outline: none;
            margin-top: 6px;
        }
        .form-control-pg:focus {
            border-color: #0099e5;
        }
    </style>
</head>
<body>

    <!-- Sky-Blue Sidebar -->
    <aside class="pg-sidebar" id="pgSidebar">
        <a href="profile.php" class="pg-logo">
            <img src="../../home/logo.svg" alt="">
            <span>Yadav Living</span>
        </a>

        <ul class="pg-nav-list">
            <li class="pg-nav-item">
                <a href="profile.php" class="pg-nav-link <?php echo ($active_page === 'profile') ? 'active' : ''; ?>">
                    <i class='bx bx-user'></i>
                    <span class="link-text">Profile</span>
                </a>
            </li>
            <li class="pg-nav-item">
                <a href="vacate.php" class="pg-nav-link <?php echo ($active_page === 'vacate') ? 'active' : ''; ?>">
                    <i class='bx bx-calendar-x'></i>
                    <span class="link-text">Vacate Intimation</span>
                </a>
            </li>
            <li class="pg-nav-item">
                <a href="kyc.php" class="pg-nav-link <?php echo ($active_page === 'kyc') ? 'active' : ''; ?>">
                    <i class='bx bx-shield-quarter'></i>
                    <span class="link-text">KYC</span>
                </a>
            </li>
            <li class="pg-nav-item">
                <a href="announcements.php" class="pg-nav-link <?php echo ($active_page === 'announcements') ? 'active' : ''; ?>">
                    <i class='bx bx-bell'></i>
                    <span class="link-text">Announcements</span>
                </a>
            </li>
            <li class="pg-nav-item">
                <a href="complaints.php" class="pg-nav-link <?php echo ($active_page === 'complaints') ? 'active' : ''; ?>">
                    <i class='bx bx-message-square-error'></i>
                    <span class="link-text">Complaints</span>
                </a>
            </li>
            <li class="pg-nav-item">
                <a href="payments.php" class="pg-nav-link <?php echo ($active_page === 'payments') ? 'active' : ''; ?>">
                    <i class='bx bx-credit-card'></i>
                    <span class="link-text">Payments & Receipts</span>
                </a>
            </li>
            <li class="pg-nav-item">
                <a href="../../assistant.php?property=pg" class="pg-nav-link <?php echo ($active_page === 'assistant') ? 'active' : ''; ?>">
                    <i class='bx bx-bot'></i>
                    <span class="link-text">PG AI Assistant</span>
                </a>
            </li>
        </ul>

        <div class="pg-bottom-nav">
            <a href="../../home/home.php" class="pg-nav-link" style="padding: 8px 12px; font-size: 12px; opacity: 0.9;" title="Return to Housing Society Portal">
                <i class='bx bx-buildings'></i>
                <span class="link-text">Housing Society</span>
            </a>
            <a href="../logout.php" class="pg-nav-link" style="padding: 8px 12px; font-size: 12px; opacity: 0.9;">
                <i class='bx bx-log-out'></i>
                <span class="link-text">Log Out</span>
            </a>
        </div>
    </aside>

    <!-- Top Header -->
    <header class="pg-header">
        <div class="header-left">
            <button type="button" class="toggle-btn" id="sidebarToggle" aria-label="Toggle Sidebar">
                <i class='bx bx-menu'></i>
            </button>
            <span class="pg-property-name">Yadav Living PG</span>
            <span style="background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; border: 1px solid #fcd34d; box-shadow: 0 2px 6px rgba(245, 158, 11, 0.15);">
                <i class='bx bxs-sun' style='color:#f59e0b;'></i> जय श्री कृष्णा
            </span>
        </div>

        <div class="header-right">
            <div class="student-profile-badge" id="studentBadge">
                <div class="student-avatar">
                    <?php 
                        $header_photo = "";
                        if (!empty($current_student['photo'])) {
                            if (hms_kyc_file_path('pg', $current_student['photo']) !== false) {
                                $header_photo = hms_kyc_download_url('pg', $current_student['photo']);
                            } elseif (file_exists(__DIR__ . "/../assets/" . $current_student['photo'])) {
                                $header_photo = "../assets/" . htmlspecialchars($current_student['photo']);
                            }
                        }
                    ?>
                    <?php if (!empty($header_photo)): ?>
                        <img src="<?php echo htmlspecialchars($header_photo, ENT_QUOTES, 'UTF-8'); ?>" alt="Profile">
                    <?php else: ?>
                        <?php echo strtoupper(substr($current_student['name'] ?? 'Y', 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="student-name-text">
                    <?php echo htmlspecialchars($current_student['name'] ?? 'Yuvraj Yadav'); ?>
                    <i class='bx bx-chevron-down' id="profileChevron"></i>
                </div>
            </div>

            <!-- Profile Popover -->
            <div class="profile-popover" id="profilePopover">
                <div class="popover-header">
                    <strong><?php echo htmlspecialchars($current_student['name'] ?? 'Yuvraj Yadav'); ?></strong>
                    <small>Room: <?php echo htmlspecialchars($current_student['room_number'] ?? 'Not assigned', ENT_QUOTES, 'UTF-8'); ?><?php if (!empty($current_student['bed_label'])): ?> (<?php echo htmlspecialchars($current_student['bed_label'], ENT_QUOTES, 'UTF-8'); ?>)<?php endif; ?></small>
                </div>
                <a href="profile.php" class="popover-item"><i class='bx bx-user'></i> My Profile</a>
                <a href="payments.php" class="popover-item"><i class='bx bx-receipt'></i> Payment Receipts</a>
                <a href="vacate.php" class="popover-item"><i class='bx bx-calendar-x'></i> Vacate Request</a>
                <div style="height: 1px; background: #e2e8f0;"></div>
                <a href="../logout.php" class="popover-item" style="color: #ef4444;"><i class='bx bx-log-out'></i> Log Out</a>
            </div>
        </div>
    </header>

    <main class="pg-main-content">

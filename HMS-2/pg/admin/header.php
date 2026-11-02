<?php
require_once(__DIR__ . "/../Includes/session.php");
require_pg_role('admin', '../login.php');

$active_page = basename($_SERVER['PHP_SELF'], ".php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'PG Management'; ?> | Yadav Living PG</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <script defer src="../../js/dashboard-ui.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            background: #f5f7f5;
            min-height: 100vh;
            color: #142d3e;
        }
        body.pg-admin-page {
            --pg-ink: #142d3e;
            --pg-teal: #286d70;
            --pg-gold: #c59a54;
        }
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 250px;
            background: linear-gradient(180deg, #142d3e 0%, #173c4d 100%);
            z-index: 1000;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            box-shadow: 8px 0 30px rgba(20,45,62,0.12);
            border-right: 1px solid rgba(248,223,160,0.14);
        }
        .admin-logo {
            padding: 22px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ffffff;
            font-size: 18px;
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            text-decoration: none;
        }
        .admin-logo i {
            font-size: 24px;
            color: #38bdf8;
        }
        .admin-logo img {
            width: 36px;
            height: 36px;
            border-radius: 10px;
        }
        .admin-nav-list {
            list-style: none;
            padding: 16px 12px;
            flex: 1;
            overflow-y: auto;
        }
        .admin-nav-item {
            margin-bottom: 4px;
        }
        .admin-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 16px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .admin-nav-link:hover, .admin-nav-link.active {
            background: rgba(248, 223, 160, 0.12);
            color: #ffffff;
        }
        .admin-nav-link.active i {
            color: #f8dfa0;
        }
        .admin-nav-link i {
            font-size: 18px;
            min-width: 22px;
        }
        .admin-header {
            position: fixed;
            top: 0;
            left: 250px;
            right: 0;
            height: 64px;
            background: #ffffff;
            border-bottom: 1px solid #e4e9e5;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            z-index: 900;
        }
        .admin-main-content {
            margin-left: 250px;
            padding: 88px 32px 32px 32px;
        }
        .admin-stat-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 20px;
            box-shadow: 0 12px 30px rgba(20,45,62,0.06);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .admin-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 34px rgba(20,45,62,0.1);
        }
        .admin-main-content > div[style*="grid-template-columns"],
        .admin-main-content > div[style*="border-radius: 12px"] {
            border-radius: 18px !important;
            box-shadow: 0 12px 30px rgba(20,45,62,0.06);
        }
        .admin-main-content input:not([type="checkbox"]):not([type="radio"]),
        .admin-main-content select,
        .admin-main-content textarea {
            border-color: #d7e1dc !important;
            border-radius: 10px !important;
        }
        .admin-main-content input:focus,
        .admin-main-content select:focus,
        .admin-main-content textarea:focus {
            outline: 3px solid rgba(40,109,112,0.16);
            border-color: #286d70 !important;
        }
        .pg-devotional-pill {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 14px;
            border: 1px solid rgba(248,223,160,0.68);
            border-radius: 999px;
            background: linear-gradient(135deg, #fff9e9 0%, #f8dfa0 52%, #e8c66d 100%);
            color: #46340f;
            box-shadow: 0 5px 16px rgba(197,154,84,0.2), inset 0 1px 0 rgba(255,255,255,0.9);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.035em;
            white-space: nowrap;
            overflow: hidden;
        }
        .pg-devotional-pill::before {
            position: absolute;
            inset: 0;
            background: linear-gradient(110deg, transparent 20%, rgba(255,255,255,0.55) 48%, transparent 72%);
            content: "";
            transform: translateX(-120%);
            animation: pg-devotional-shimmer 5s ease-in-out infinite;
            pointer-events: none;
        }
        @keyframes pg-devotional-shimmer {
            0%, 62%, 100% { transform: translateX(-120%); }
            82% { transform: translateX(120%); }
        }
        .pg-devotional-pill i {
            position: relative;
            z-index: 1;
            color: #9a6b16;
            font-size: 16px;
            filter: drop-shadow(0 1px 1px rgba(120,76,15,0.18));
        }
        .pg-devotional-pill span {
            position: relative;
            z-index: 1;
        }
        @media (prefers-reduced-motion: reduce) {
            .pg-devotional-pill::before {
                animation: none;
            }
        }
        .badge-occupancy {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }
        .pg-nav-toggle {
            display: none;
            width: 40px;
            height: 40px;
            border: 1px solid #e2e8f0;
            border-radius: 11px;
            background: #f8fafc;
            color: #0f172a;
            font-size: 22px;
            cursor: pointer;
        }
        .pg-nav-backdrop {
            display: none;
        }
        @media (max-width: 760px) {
            .admin-sidebar {
                width: min(290px, 84vw);
                transform: translateX(-105%);
                box-shadow: 16px 0 42px rgba(2, 6, 23, 0.25);
            }
            body.pg-nav-open .admin-sidebar {
                transform: translateX(0);
            }
            .pg-nav-backdrop {
                display: block;
                position: fixed;
                inset: 0;
                z-index: 950;
                border: 0;
                background: rgba(2, 6, 23, 0.52);
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.25s ease, visibility 0.25s ease;
            }
            body.pg-nav-open .pg-nav-backdrop {
                opacity: 1;
                visibility: visible;
            }
            .admin-header {
                left: 0;
                height: 60px;
                padding: 0 14px;
                gap: 10px;
            }
            .pg-nav-toggle {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 40px;
            }
            .admin-header > div:first-child {
                min-width: 0;
                gap: 10px !important;
            }
            .admin-header > div:first-child > span:first-of-type {
                overflow: hidden;
                white-space: nowrap;
                text-overflow: ellipsis;
                font-size: 13px !important;
            }
            .pg-devotional-pill {
                gap: 5px;
                padding: 5px 8px;
                font-size: 10px;
            }
            .admin-header > div:last-child > div:first-child {
                display: none;
            }
            .admin-main-content {
                margin-left: 0;
                padding: 78px 16px 24px;
            }
        }
        @media (max-width: 480px) {
            .admin-main-content {
                padding-right: 12px;
                padding-left: 12px;
            }
            .admin-stat-card {
                padding: 16px;
            }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
                animation-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body class="pg-admin-page">

    <aside class="admin-sidebar" id="pgAdminSidebar">
        <a href="dashboard.php" class="admin-logo">
            <img src="../../home/logo.svg" alt="">
            <span>Yadav Living PG</span>
        </a>

        <ul class="admin-nav-list">
            <li class="admin-nav-item">
                <a href="dashboard.php" class="admin-nav-link <?php echo ($active_page === 'dashboard') ? 'active' : ''; ?>">
                    <i class='bx bx-grid-alt'></i> PG Overview
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="rooms.php" class="admin-nav-link <?php echo ($active_page === 'rooms') ? 'active' : ''; ?>">
                    <i class='bx bx-bed'></i> Rooms & Beds Matrix
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="students.php" class="admin-nav-link <?php echo ($active_page === 'students') ? 'active' : ''; ?>">
                    <i class='bx bx-user-pin'></i> Student Residents
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="complaints_mgmt.php" class="admin-nav-link <?php echo ($active_page === 'complaints_mgmt') ? 'active' : ''; ?>">
                    <i class='bx bx-message-square-error'></i> Maintenance Complaints
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="vacate_mgmt.php" class="admin-nav-link <?php echo ($active_page === 'vacate_mgmt') ? 'active' : ''; ?>">
                    <i class='bx bx-calendar-x'></i> Vacate Notices
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="announcements.php" class="admin-nav-link <?php echo ($active_page === 'announcements') ? 'active' : ''; ?>">
                    <i class='bx bx-broadcast'></i> Broadcast Notice
                </a>
            </li>
            <li class="admin-nav-item">
                <a href="../../assistant.php?property=pg" class="admin-nav-link <?php echo ($active_page === 'assistant') ? 'active' : ''; ?>">
                    <i class='bx bx-bot'></i> PG AI Assistant
                </a>
            </li>
        </ul>

        <div style="padding: 16px; border-top: 1px solid rgba(255,255,255,0.08);">
            <a href="../../admin/Dashboard/admin.php" class="admin-nav-link" style="color: #38bdf8;">
                <i class='bx bx-buildings'></i> Yadav Living Society Portal
            </a>
            <a href="../logout.php" class="admin-nav-link" style="color: #ef4444; margin-top: 4px;">
                <i class='bx bx-log-out'></i> Log Out
            </a>
        </div>
    </aside>
    <button class="pg-nav-backdrop" id="pgNavBackdrop" type="button" aria-label="Close Yadav Living PG navigation"></button>

    <header class="admin-header">
        <div style="display: flex; align-items: center; gap: 14px;">
            <button type="button" class="pg-nav-toggle" id="pgNavToggle" aria-label="Open Yadav Living PG navigation" aria-controls="pgAdminSidebar" aria-expanded="false">
                <i class='bx bx-menu' aria-hidden="true"></i>
            </button>
            <span style="font-weight: 700; color: #0f172a; font-size: 16px;">Yadav Living PG Warden Desk</span>
            <span class="pg-devotional-pill">
                <i class='bx bxs-sun' aria-hidden="true"></i><span>जय श्री कृष्ण</span>
            </span>
        </div>

        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="text-align: right;">
                <div style="font-size: 13px; font-weight: 700; color: #0f172a;">Yuvraj (Warden / Admin)</div>
                <div style="font-size: 11px; color: #38bdf8; font-weight: 600;">Full Access</div>
            </div>
            <div style="width: 38px; height: 38px; border-radius: 50%; background: #0099e5; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                Y
            </div>
        </div>
    </header>

    <main class="admin-main-content">

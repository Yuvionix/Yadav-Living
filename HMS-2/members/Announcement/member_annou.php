<?php 
  require_once("../../Includes/config.php"); 
  require_once("../../Includes/session.php"); 
  require_role(array('member', 'admin'), '../../login.php');
  require_once("connection.php");
  $connection = new Connection();
  $announcements = $connection->getannouncements();
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Society Notices | Resident Portal</title>
    <link rel="stylesheet" href="member_annou.css">
    <link rel="stylesheet" href="../../css/master_theme.css">
    <link rel="shortcut icon" href="../../home/logo.svg">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <div class="sidebar">
        <div class="logo-details">
            <i><img src="../../home/logo.svg" alt="logo" width="46" height="46"></i>
            <span class="logo_name">HMS</span>
        </div>
        <ul class="nav-links">
            <li>
                <a href="../Dashboard/member.php">
                    <i class='bx bx-grid-alt'></i>
                    <span class="links_name">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="member_annou.php" class="active">
                    <i class='bx bx-bell'></i>
                    <span class="links_name">Announcement</span>
                </a>
            </li>
            <li>
                <a href="../Bill/index.php">
                    <i class='bx bx-pie-chart-alt-2'></i>
                    <span class="links_name">Maintainence Bill</span>
                </a>
            </li>
            <li>
                <a href="../Home_Services/home_ser.php">
                    <i class='bx bxs-user-circle'></i>
                    <span class="links_name">Home Services</span>
                </a>
            </li>
            <li>
                <a href="../Neighbourhood/member_neigh.php">
                    <i class='bx bx-map'></i>
                    <span class="links_name">Neighbourhood</span>
                </a>
            </li>
            <li>
                <a href="../Members/staff/index.php">
                    <i class='bx bx-list-ul'></i>
                    <span class="links_name">Staff</span>
                </a>
            </li>
            <li class="ai-agent-nav">
                <a href="../Chat/ai_assistant.php">
                    <i class='bx bx-bot'></i>
                    <span class="links_name">Society AI Agent</span>
                    <span class="ai-badge">AI</span>
                </a>
            </li>
            <li>
                <a href="../Event_calender/member_event.php">
                    <i class='bx bx-calendar-event'></i>
                    <span class="links_name">Events</span>
                </a>
            </li>
            <li class="log_out">
                <a href="../../logout.php">
                    <i class='bx bx-log-out'></i>
                    <span class="links_name">Log out</span>
                </a>
            </li>
        </ul>
    </div>

    <section class="home-section">
        <nav>
            <div class="sidebar-button">
                <i class='bx bx-menu sidebarBtn'></i>
                <span class="Announcement">Society Notice Board</span>
            </div>
            
            <div class="profile-details">
                <div class="user-avatar-circle">
                    <?php echo strtoupper(substr(isset($_SESSION["username"]) ? $_SESSION["username"] : 'R', 0, 1)); ?>
                </div>
                <span class="admin_name"><?php echo isset($_SESSION["username"]) ? htmlspecialchars($_SESSION["username"]) : 'Resident'; ?></span>
            </div>
        </nav>

        <div class="home-content" style="padding: 92px 28px 28px 28px;">
            <div style="margin-bottom: 24px;">
                <h2 style="font-size: 24px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Community Announcements</h2>
                <p style="font-size: 14px; color: #64748b; margin: 0;">Important notices and communications from the Society Managing Committee</p>
            </div>

            <!-- Announcements Cards -->
            <div class="announcements-list">
                <?php if(empty($announcements)): ?>
                    <div class="annou-card" style="text-align: center; color: #64748b; padding: 40px;">
                        <i class='bx bx-bell-off' style="font-size: 48px; color: #94a3b8; margin-bottom: 12px;"></i>
                        <p>No active announcements at the moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($announcements as $announcement): ?>
                    <div class="annou-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                            <div class="annou-date-pill">
                                <i class='bx bx-calendar'></i>
                                <?php echo date("l, d M Y - h:i A", strtotime($announcement['created_at'])); ?>
                            </div>
                            <span class="badge badge-success"><i class='bx bx-check-circle'></i> Active Notice</span>
                        </div>
                        <h3 class="annou-title"><?php echo htmlspecialchars($announcement['announcement_subject']); ?></h3>
                        <div class="annou-body">
                            <?php echo nl2br(htmlspecialchars($announcement['announcement_text'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <script>
        let sidebar = document.querySelector(".sidebar");
        let sidebarBtn = document.querySelector(".sidebarBtn");
        sidebarBtn.onclick = function () {
            sidebar.classList.toggle("active");
            if (sidebar.classList.contains("active")) {
                sidebarBtn.classList.replace("bx-menu", "bx-menu-alt-right");
            } else
                sidebarBtn.classList.replace("bx-menu-alt-right", "bx-menu");
        }
    </script>
</body>
</html>
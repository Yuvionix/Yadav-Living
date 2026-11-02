<?php 
  require_once("../../Includes/config.php"); 
  require_once("../../Includes/session.php"); 
  require_role('admin', '../../login.php');
  require_once("connection.php");
  $connection = new Connection();
  $announcements = $connection->getannouncements();
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Announcements | Yadav Living</title>
    <link rel="stylesheet" href="admin_annou.css">
    <link rel="stylesheet" href="../../css/master_theme.css">
    <script defer src="../../js/dashboard-ui.js"></script>
    <link rel="shortcut icon" href="../../home/logo.svg">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body class="dashboard-page society-section-page">
    <div class="sidebar">
        <div class="logo-details">
            <i><img src="../../home/logo.svg" alt="" width="46" height="46"></i>
            <span class="logo_name">Yadav Living</span>
        </div>
        <ul class="nav-links">
            <li>
                <a href="../Dashboard/admin.php">
                    <i class='bx bx-grid-alt'></i>
                    <span class="links_name">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="admin_annou.php" class="active">
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
                <a href="../Neighbourhood/admin_neigh.php">
                    <i class='bx bx-map'></i>
                    <span class="links_name">Neighbourhood</span>
                </a>
            </li>
            <li>
                <a href="../Members/index.php">
                    <i class='bx bx-list-ul'></i>
                    <span class="links_name">Members</span>
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
                <a href="../Event_calender/admin_event.php">
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
    <button class="dashboard-nav-backdrop" id="societyNavBackdrop" type="button" aria-label="Close dashboard navigation"></button>

    <section class="home-section">
        <nav>
            <div class="sidebar-button">
                <button class="sidebarBtn" type="button" aria-label="Open dashboard navigation" aria-expanded="true">
                    <i class='bx bx-menu' aria-hidden="true"></i>
                </button>
                <span class="Announcement">Society Notice Board</span>
            </div>
            
            <div class="profile-details">
                <div class="user-avatar-circle">
                    <?php echo strtoupper(substr(isset($_SESSION["username"]) ? $_SESSION["username"] : 'Y', 0, 1)); ?>
                </div>
                <span class="admin_name"><?php echo isset($_SESSION["username"]) ? htmlspecialchars($_SESSION["username"]) : 'Yuvraj'; ?> (Admin)</span>
            </div>
        </nav>

        <div class="home-content" style="padding: 92px 28px 28px 28px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <h2 style="font-size: 24px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Official Announcements</h2>
                    <p style="font-size: 14px; color: #64748b; margin: 0;">Yadav Living notices, maintenance schedules &amp; community alerts</p>
                </div>
                <a href="#modalDialog" class="btn btn-primary" id="mbtn" style="display: inline-flex; align-items: center; gap: 8px;">
                    <i class='bx bx-plus-circle'></i> Post New Announcement
                </a>
            </div>

            <!-- Announcements Cards -->
            <div class="announcements-list">
                <?php if(empty($announcements)): ?>
                    <div class="annou-card" style="text-align: center; color: #64748b; padding: 40px;">
                        <i class='bx bx-bell-off' style="font-size: 48px; color: #94a3b8; margin-bottom: 12px;"></i>
                        <p>No active announcements posted yet.</p>
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

    <!-- Modal for Adding Announcement -->
    <link rel="stylesheet" href="style.css">
    <div id="modalDialog" class="modal">
        <div class="modal-content animate-top" style="border-radius: 18px; border: 1px solid #e2e8f0; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); color: #fff; border-top-left-radius: 17px; border-top-right-radius: 17px;">
                <h5 class="modal-title" style="font-weight: 700;"><i class='bx bx-broadcast' style="margin-right: 8px;"></i> Broadcast Announcement</h5>
                <a href="#" style="color: #fff; font-size: 24px; text-decoration: none;">&times;</a>
            </div>
            <form method="post" id="contactFrm" action="create.php">
                <div class="modal-body" style="padding: 24px;">
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label style="font-weight: 600; color: #334155; margin-bottom: 6px;">Subject / Title:</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Water Tank Sanitization Schedule" required style="border-radius: 10px; border: 1.5px solid #cbd5e1; padding: 10px 14px;">
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 600; color: #334155; margin-bottom: 6px;">Detailed Notice Message:</label>
                        <textarea name="message" id="title" class="form-control" placeholder="Enter complete announcement details for society residents..." rows="5" required style="border-radius: 10px; border: 1.5px solid #cbd5e1; padding: 10px 14px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 16px 24px; border-top: 1px solid #e2e8f0;">
                    <button type="submit" name="submit" class="btn btn-primary" style="padding: 10px 24px; border-radius: 10px;">
                        <i class='bx bx-send'></i> Publish Notice
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
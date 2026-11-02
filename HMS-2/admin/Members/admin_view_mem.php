<?php 
  require_once("../../Includes/config.php"); 
  require_once("../../Includes/session.php"); 
  require_role('admin', '../../login.php');
?>

<!DOCTYPE html>

<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <title>Members | Yadav Living</title>

    <link rel="stylesheet" href="admin_view_mem.css">
    <link rel="icon" type="image/svg+xml" href="../../home/logo.svg">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/master_theme.css">
    <script defer src="../../js/dashboard-ui.js"></script>
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
                <a href="../Announcement/admin_annou.php" >
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
                <a href="admin_view_mem.php" class="active">
                    <i class='bx bx-list-ul'></i>
                    <span class="links_name">Members</span>
                </a>
            </li>
            <li>
                <a href="admin_view_staff.php">
                    <i class='bx bx-id-card'></i>
                    <span class="links_name">Staff</span>
                </a>
            </li>

                        <li>
                <a href="https://wa.me/918210075922" target="_blank" class="wa-link">
                    <i class='bx bxl-whatsapp'></i>
                    <span class="links_name">Sumit (Support)</span>
                </a>
            </li>
            <li>
                <a href="https://wa.me/917004936609" target="_blank" class="wa-link">
                    <i class='bx bxl-whatsapp'></i>
                    <span class="links_name">Binod (Support)</span>
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
                <span class="Announcement">Members</span>
            </div>
            <div class="search-box">
                <input type="text" placeholder="Search...">
                <i class='bx bx-search'></i>
            </div>
            <div class="profile-details">
                
                <span class="admin_name"><?php echo $_SESSION["username"]; ?></span>
                <i class='bx bx-chevron-down'></i>
            </div>
        </nav>
        <div class="home-content">
            <div class="type_select">
                <a href="admin_view_mem.php" class="active">
                    <div class="typebut"> Members</div>
                </a>
                <a href="admin_view_staff.php" >
                    <div class="typebut"> Staff</div>
                </a>
            </div>
            
            
        </div>
    </section>


</body>

</html>
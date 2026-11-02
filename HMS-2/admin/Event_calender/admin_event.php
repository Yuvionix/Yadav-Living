<?php 
  require_once("../../Includes/config.php"); 
  require_once("../../Includes/session.php"); 
  require_role('admin', '../../login.php');
?>
<!DOCTYPE html>

<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <title>Community Events | Yadav Living</title>

    <link rel="stylesheet" href="admin_event.css">
    <link rel="shortcut icon" href="../../home/logo.svg">

    
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
                <a href="../Announcement/admin_annou.php">
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
                <a href="admin_event.php" class="active">
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
                <span class="Announcement">Events</span>
            </div>
            
            <div class="profile-details">

                <span class="admin_name"><?php echo htmlspecialchars($_SESSION["username"] ?? 'Admin'); ?></span>
                <i class='bx bx-chevron-down'></i>
            </div>
        </nav>
        <div class="home-content">
            <div class="year-box">
                <div class="year"><?php echo date("Y"); ?></div>
                <br>
                <div class="event">
                    <div class="event-left">
                        <div class="event-day">
                            <div class="event-date">13</div>
                            <div class="event-month">Jan</div>
                        </div>
                    </div>
                    <div class="event-right">
                        <h3 class="event-title">Lohri</h3> 
                        <div class="event-time">
                            <i class="bx bxs-time" ></i>
                            <span class="time-text">8:00 pm</span>
                        </div>
                    </div>
                </div>
                <br>
                <div class="event">
                    <div class="event-left">
                        <div class="event-day">
                            <div class="event-date">14</div>
                            <div class="event-month">Jan</div>
                        </div>
                    </div>
                    <div class="event-right">
                        <h3 class="event-title">Makar Sankranti</h3> 
                        <div class="event-time">
                            <i class="bx bxs-time" ></i>
                            <span class="time-text">10:00 am</span>
                        </div>
                    </div>
                </div>
                <br>
                <div class="event">
                    <div class="event-left">
                        <div class="event-day">
                            <div class="event-date">26</div>
                            <div class="event-month">Jan</div>
                        </div>
                    </div>
                    <div class="event-right">
                        <h3 class="event-title">Republic Day</h3> 
                        <div class="event-time">
                            <i class="bx bxs-time" ></i>
                            <span class="time-text">9:00 am</span>
                        </div>
                    </div>
                </div>
                <br>
                <div class="event">
                    <div class="event-left">
                        <div class="event-day">
                            <div class="event-date">1</div>
                            <div class="event-month">Mar</div>
                        </div>
                    </div>
                    <div class="event-right">
                        <h3 class="event-title">Maha Shivratri</h3> 
                        <div class="event-time">
                            <i class="bx bxs-time" ></i>
                            <span class="time-text">9:00 pm</span>
                        </div>
                    </div>
                </div>
                <br>
                <div class="event">
                    <div class="event-left">
                        <div class="event-day">
                            <div class="event-date">17</div>
                            <div class="event-month">Mar</div>
                        </div>
                    </div>
                    <div class="event-right">
                        <h3 class="event-title">Holika Dahana</h3> 
                        <div class="event-time">
                            <i class="bx bxs-time" ></i>
                            <span class="time-text">9:00 pm</span>
                        </div>
                    </div>
                </div>
                <br>
                <div class="event">
                    <div class="event-left">
                        <div class="event-day">
                            <div class="event-date">18</div>
                            <div class="event-month">Mar</div>
                        </div>
                    </div>
                    <div class="event-right">
                        <h3 class="event-title">Holi</h3> 
                        <div class="event-time">
                            <i class="bx bxs-time" ></i>
                            <span class="time-text">9:00 am</span>
                        </div>
                    </div>
                </div>
                
            </div>
            
        </div>
    </section>
    
    <script>
    </script>

</body>

</html>
<?php 
  require_once("../../Includes/config.php"); 
  require_once("../../Includes/session.php"); 
  require_once("../../Includes/property_locations.php");
  require_role(array('member', 'admin'), '../../login.php');

  $mem_id = hms_current_member_id();
  $chk_kyc = mysqli_query($con, "SELECT status FROM society_kyc WHERE member_id = $mem_id LIMIT 1");
  $kyc_data = mysqli_fetch_assoc($chk_kyc);
  $mem_kyc_status = $kyc_data['status'] ?? 'Not Submitted';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Resident Dashboard | Yadav Living</title>
    <meta name="description" content="Yadav Living resident dashboard for tracking maintenance bills, community announcements, and local services.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://unpkg.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="stylesheet" href="member.css">
    <link rel="stylesheet" href="../../css/master_theme.css">
    <link rel="icon" type="image/svg+xml" href="../../home/logo.svg">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
</head>

<body class="dashboard-page dashboard-resident-page">
    <div class="sidebar" id="residentSidebar">
        <div class="logo-details">
            <i><img src="../../home/logo.svg" alt="" width="46" height="46"></i>
            <span class="logo_name">Yadav Living</span>
        </div>
        <ul class="nav-links">
            <li>
                <a href="member.php" class="active">
                    <i class='bx bx-grid-alt'></i>
                    <span class="links_name">Dashboard </span>
                </a>
            </li>
            <li>
                <a href="../Announcement/member_annou.php">
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
                    <span class="links_name">Home Service</span>
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
            <li>
                <a href="../kyc.php">
                    <i class='bx bx-id-card'></i>
                    <span class="links_name">Resident KYC</span>
                    <?php if ($mem_kyc_status === 'Verified'): ?>
                        <span style="background: #10b981; color: #fff; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 10px; margin-left: 6px;">Verified</span>
                    <?php else: ?>
                        <span style="background: #f59e0b; color: #fff; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 10px; margin-left: 6px;">Pending</span>
                    <?php endif; ?>
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
            <li style="background: rgba(14, 165, 233, 0.12); border-radius: 8px; margin: 6px 0;">
                <a href="../../pg/index.php">
                    <i class='bx bxs-institution' style="color: #38bdf8;"></i>
                    <span class="links_name" style="color: #38bdf8; font-weight: 600;">Yadav Living PG</span>
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
            <div class="sidebar-button" style="display: flex; align-items: center; gap: 14px;">
                <button class="sidebarBtn" type="button" aria-label="Open dashboard navigation" aria-controls="residentSidebar" aria-expanded="true">
                    <i class='bx bx-menu' aria-hidden="true"></i>
                </button>
                <span class="dashboard">Resident Portal</span>
                <span style="background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; padding: 4px 12px; border-radius: 20px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; border: 1px solid #fcd34d; box-shadow: 0 2px 6px rgba(245, 158, 11, 0.15);">
                    <i class='bx bxs-sun' style='color:#f59e0b;'></i> जय श्री कृष्णा
                </span>
            </div>
           
            <div class="profile-details" id="memberProfileDropdownBtn" style="position: relative; cursor: pointer; user-select: none;">
                <div class="user-avatar-circle">
                    <?php echo strtoupper(substr(isset($_SESSION["username"]) ? $_SESSION["username"] : 'R', 0, 1)); ?>
                </div>
                <span class="admin_name"><?php echo isset($_SESSION["username"]) ? htmlspecialchars($_SESSION["username"]) : 'Resident'; ?></span>
                <i class='bx bx-chevron-down' id="memberChevron" style="transition: transform 0.25s ease;"></i>

                <!-- Resident Dropdown Menu -->
                <div class="profile-dropdown-menu" id="memberProfileDropdown" style="display: none; position: absolute; right: 0; top: 58px; background: #ffffff; border-radius: 14px; box-shadow: 0 12px 30px rgba(15,23,42,0.15); border: 1px solid #e2e8f0; width: 220px; z-index: 1000; overflow: hidden;">
                    <div style="padding: 14px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                        <div style="font-weight: 700; color: #0f172a; font-size: 14px;"><?php echo isset($_SESSION["username"]) ? htmlspecialchars($_SESSION["username"]) : 'Resident'; ?></div>
                        <div style="font-size: 11px; color: #10b981; font-weight: 600; text-transform: uppercase;">Resident Member</div>
                    </div>
                    <a href="../Bill/index.php" style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: #334155; text-decoration: none; font-size: 13px; font-weight: 500; transition: background 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                        <i class='bx bx-receipt' style="color: #10b981; font-size: 16px;"></i> My Maintenance Bill
                    </a>
                    <a href="../Announcement/member_annou.php" style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: #334155; text-decoration: none; font-size: 13px; font-weight: 500; transition: background 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                        <i class='bx bx-bell' style="color: #f59e0b; font-size: 16px;"></i> Society Notices
                    </a>
                    <a href="../Home_Services/home_ser.php" style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: #334155; text-decoration: none; font-size: 13px; font-weight: 500; transition: background 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                        <i class='bx bx-wrench' style="color: #3b82f6; font-size: 16px;"></i> Local Services
                    </a>
                    <div style="height: 1px; background: #e2e8f0; margin: 4px 0;"></div>
                    <a href="../../logout.php" style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: #ef4444; text-decoration: none; font-size: 13px; font-weight: 600; transition: background 0.2s;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='transparent'">
                        <i class='bx bx-log-out' style="font-size: 16px;"></i> Log Out
                    </a>
                </div>
            </div>
        </nav>

        <div class="home-content">
            <!-- 3D Society Hero Banner -->
            <div class="dashboard-3d-banner">
                <div class="banner-text">
                    <a class="badge-location" href="<?php echo htmlspecialchars(hms_property_map_url(HMS_SOCIETY_ADDRESS), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" style="text-decoration:none;">
                        <i class='bx bx-map-pin'></i> <?php echo htmlspecialchars(HMS_SOCIETY_ADDRESS, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                    <span class="dashboard-story-kicker">Your home, connected</span>
                    <h2>Welcome home to Yadav Living.</h2>
                    <p>Keep up with the people and moments that make this community feel like home. Your bills, notices, local services, and resident support are here when you need them.</p>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px;">
                        <a href="../Chat/ai_assistant.php" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:8px;padding:9px 18px;border-radius:10px;font-size:13px;font-weight:600;">
                            <i class='bx bx-bot'></i> Ask your community assistant
                        </a>
                        <a href="../Bill/index.php" class="btn" style="display:inline-flex;align-items:center;gap:8px;padding:9px 18px;border-radius:10px;font-size:13px;font-weight:600;background:rgba(255,255,255,0.12);color:#fff;border:1px solid rgba(255,255,255,0.22);text-decoration:none;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.12)'">
                            <i class='bx bx-credit-card'></i> View My Bill
                        </a>
                    </div>
                </div>
                <div id="canvas-3d-container"></div>
            </div>

            <!-- Clickable Interactive Metric Cards -->
            <div class="overview-boxes">
                <a href="../kyc.php" class="box" style="text-decoration: none; color: inherit; cursor: pointer; transition: all 0.25s ease;">
                    <div class="right-side">
                        <div class="box-topic" style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Your Resident KYC</span>
                            <span style="font-size: 11px; color: #6366f1; font-weight: 600;">View &rarr;</span>
                        </div>
                        <div class="number" style="font-size: 22px;"><?php echo htmlspecialchars($mem_kyc_status); ?></div>
                    </div>
                    <i class='bx bxs-user icon member'></i>
                </a>

                <a href="../Members/staff/index.php" class="box" style="text-decoration: none; color: inherit; cursor: pointer; transition: all 0.25s ease;">
                    <div class="right-side">
                        <div class="box-topic" style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Security & Staff</span>
                            <span style="font-size: 11px; color: #06b6d4; font-weight: 600;">View &rarr;</span>
                        </div>
                        <?php
                            $res_s = mysqli_query($con, "SELECT COUNT(*) as cnt FROM staff");
                            $row_s = mysqli_fetch_assoc($res_s);
                        ?>  
                        <div class="number"><?php echo $row_s['cnt']; ?></div>
                    </div>
                    <i class='bx bxs-user-circle icon staff'></i>
                </a>

                <a href="../Bill/index.php" class="box" style="text-decoration: none; color: inherit; cursor: pointer; transition: all 0.25s ease;">
                    <div class="right-side">
                        <div class="box-topic" style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Your Bill Status</span>
                            <span style="font-size: 11px; color: #10b981; font-weight: 600;">Pay / View &rarr;</span>
                        </div>
                        <?php
                            $uid = hms_current_member_id();
                            $cur_m = date('Y-m');
                            $res_mybill = mysqli_query($con, "SELECT status, total_amount FROM billing WHERE member_id = $uid AND date_format(billing_date,'%Y-%m') = '$cur_m' LIMIT 1");
                            $mybill = mysqli_fetch_assoc($res_mybill);
                        ?> 
                        <div class="number">
                            <?php if($mybill && $mybill['status'] == 1): ?>
                                <span style="color:#10b981;font-size:22px;">Paid</span>
                            <?php else: ?>
                                <span style="color:#f59e0b;font-size:22px;"><?php echo $mybill ? '₹' . number_format($mybill['total_amount']) . ' Due' : 'No bill yet'; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <i class='bx bxs-file icon file'></i>
                </a>
            </div>

            <div class="sales-boxes">
                <div class="registry box">
                    <div class="title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <span><i class='bx bx-bell' style="color:#6366f1;margin-right:6px;"></i> Latest Society Notices</span>
                        <a href="../Announcement/member_annou.php" class="btn btn-outline-primary btn-sm" style="font-size:12px;padding:4px 12px;border-radius:8px;text-decoration:none;">View All</a>
                    </div>
                    <div class="registry-details">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Notice Subject</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $notices = $con->query("SELECT * FROM announcement ORDER BY announcement_id DESC LIMIT 3");
                                    while($n = $notices->fetch_assoc()):
                                ?>
                                <tr onclick="window.location.href='../Announcement/member_annou.php'" style="cursor: pointer;" title="Click to read full notice">
                                    <td style="white-space:nowrap;font-weight:600;color:#6366f1;"><?php echo date("d M Y", strtotime($n['created_at'])); ?></td>
                                    <td>
                                        <b><?php echo htmlspecialchars($n['announcement_subject']); ?></b>
                                        <div style="font-size:12px;color:#64748b;margin-top:2px;"><?php echo htmlspecialchars(substr($n['announcement_text'], 0, 75)) . '...'; ?></div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>  
                </div>

            </div>
        </div>
    </section>

    <button class="dashboard-nav-backdrop" id="societyNavBackdrop" type="button" aria-label="Close dashboard navigation"></button>

    <script>
        // Resident profile dropdown
        const memberProfileBtn = document.getElementById('memberProfileDropdownBtn');
        const memberProfileDropdown = document.getElementById('memberProfileDropdown');
        const memberChevron = document.getElementById('memberChevron');
        if (memberProfileBtn && memberProfileDropdown) {
            memberProfileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const isShown = memberProfileDropdown.style.display === 'block';
                memberProfileDropdown.style.display = isShown ? 'none' : 'block';
                if (memberChevron) {
                    memberChevron.style.transform = isShown ? 'rotate(0deg)' : 'rotate(180deg)';
                }
            });
            document.addEventListener('click', function() {
                memberProfileDropdown.style.display = 'none';
                if (memberChevron) memberChevron.style.transform = 'rotate(0deg)';
            });
        }
    </script>
</body>
</html>
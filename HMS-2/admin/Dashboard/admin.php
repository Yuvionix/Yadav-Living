<?php 
  require_once("../../Includes/config.php"); 
  require_once("../../Includes/session.php"); 
  require_once("../../Includes/property_locations.php");
  require_role('admin', '../../login.php');
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Society Dashboard | Yadav Living</title>
    <meta name="description" content="Yadav Living dashboard for managing community operations, maintenance bills, residents, staff, and visitor records.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://unpkg.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="../../css/master_theme.css">
    <link rel="icon" type="image/svg+xml" href="../../home/logo.svg">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <script defer src="../../js/dashboard-ui.js"></script>
</head>

<body class="dashboard-page dashboard-admin-page">
    <div class="sidebar" id="societySidebar">
        <div class="logo-details">
            <i><img src="../../home/logo.svg" alt="" width="46" height="46"></i>
            <span class="logo_name">Yadav Living</span>
        </div>
        <ul class="nav-links">
            <li>
                <a href="admin.php" class="active">
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
            <li>
                <a href="../society_kyc.php">
                    <i class='bx bx-id-card' style="color: #38bdf8;"></i>
                    <span class="links_name">Society Warden Desk</span>
                </a>
            </li>
            <li style="background: rgba(14, 165, 233, 0.12); border-radius: 8px; margin: 6px 0;">
                <a href="../../pg/admin/dashboard.php">
                    <i class='bx bxs-institution' style="color: #38bdf8;"></i>
                    <span class="links_name" style="color: #38bdf8; font-weight: 600;">Yadav Living PG Warden</span>
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
                <button class="sidebarBtn" type="button" aria-label="Open dashboard navigation" aria-controls="societySidebar" aria-expanded="true">
                    <i class='bx bx-menu' aria-hidden="true"></i>
                </button>
                <span class="dashboard">Society Overview</span>
                <span class="dashboard-devotional-badge">
                    <i class='bx bxs-sun' style='color:#f59e0b;' aria-hidden="true"></i> जय श्री कृष्ण
                </span>
                <a href="../../pg/index.php" class="dashboard-property-switch" title="Switch to Yadav Living PG Management">
                    <i class='bx bx-transfer-alt' aria-hidden="true"></i><span>Yadav Living PG</span>
                </a>
            </div>
            
            <div class="profile-details" id="profileDropdownBtn" style="position: relative; cursor: pointer; user-select: none;">
                <div class="user-avatar-circle">
                    <?php echo strtoupper(substr(isset($_SESSION["username"]) ? $_SESSION["username"] : 'Y', 0, 1)); ?>
                </div>
                <span class="admin_name"><?php echo isset($_SESSION["username"]) ? htmlspecialchars($_SESSION["username"]) : 'Yuvraj'; ?> (Admin)</span>
                <i class='bx bx-chevron-down' id="profileChevron" style="transition: transform 0.25s ease;"></i>

                <!-- Interactive Profile Dropdown -->
                <div class="profile-dropdown-menu" id="profileDropdown" style="display: none; position: absolute; right: 0; top: 58px; background: #ffffff; border-radius: 14px; box-shadow: 0 12px 30px rgba(15,23,42,0.15); border: 1px solid #e2e8f0; width: 220px; z-index: 1000; overflow: hidden; animation: fadeIn 0.2s ease;">
                    <div style="padding: 14px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                        <div style="font-weight: 700; color: #0f172a; font-size: 14px;"><?php echo isset($_SESSION["username"]) ? htmlspecialchars($_SESSION["username"]) : 'Yuvraj'; ?></div>
                        <div style="font-size: 11px; color: #4f46e5; font-weight: 600; text-transform: uppercase;">System Administrator</div>
                    </div>
                    <a href="../Members/index.php" style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: #334155; text-decoration: none; font-size: 13px; font-weight: 500; transition: background 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                        <i class='bx bx-user' style="color: #6366f1; font-size: 16px;"></i> Resident Members
                    </a>
                    <a href="../Bill/index.php" style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: #334155; text-decoration: none; font-size: 13px; font-weight: 500; transition: background 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                        <i class='bx bx-receipt' style="color: #10b981; font-size: 16px;"></i> Maintenance Bills
                    </a>
                    <a href="../Announcement/admin_annou.php" style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: #334155; text-decoration: none; font-size: 13px; font-weight: 500; transition: background 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                        <i class='bx bx-bell' style="color: #f59e0b; font-size: 16px;"></i> Post Notice
                    </a>
                    <div style="height: 1px; background: #e2e8f0; margin: 4px 0;"></div>
                    <a href="../../logout.php" style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: #ef4444; text-decoration: none; font-size: 13px; font-weight: 600; transition: background 0.2s;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='transparent'">
                        <i class='bx bx-log-out' style="font-size: 16px;"></i> Log Out
                    </a>
                </div>
            </div>
        </nav>

        <div class="home-content">
            <!-- Interactive 3D Society Hero Banner -->
            <div class="dashboard-3d-banner" data-animate data-delay="0">
                <div class="banner-text">
                    <a class="badge-location" href="<?php echo htmlspecialchars(hms_property_map_url(HMS_SOCIETY_ADDRESS), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" style="text-decoration:none;">
                        <i class='bx bx-map-pin'></i> <?php echo htmlspecialchars(HMS_SOCIETY_ADDRESS, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                    <span class="dashboard-story-kicker">Good living begins with care</span>
                    <h2>A community cared for, every day.</h2>
                    <p>From a warm welcome at the gate to clear updates and dependable resident services, Yadav Living helps the everyday details of community life feel simpler.</p>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px;">
                        <a href="../Chat/ai_assistant.php" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:8px;padding:9px 18px;border-radius:10px;font-size:13px;font-weight:600;">
                            <i class='bx bx-bot' aria-hidden="true"></i> Ask the community assistant
                        </a>
                        <a href="../Bill/index.php" class="btn" style="display:inline-flex;align-items:center;gap:8px;padding:9px 18px;border-radius:10px;font-size:13px;font-weight:600;background:rgba(255,255,255,0.12);color:#fff;border:1px solid rgba(255,255,255,0.22);text-decoration:none;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.12)'">
                            <i class='bx bx-receipt'></i> Billing Portal
                        </a>
                        <button type="button" onclick="openVisitorModal()" class="btn" style="display:inline-flex;align-items:center;gap:8px;padding:9px 18px;border-radius:10px;font-size:13px;font-weight:600;background:rgba(255,255,255,0.12);color:#fff;border:1px solid rgba(255,255,255,0.22);cursor:pointer;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.12)'">
                            <i class='bx bx-user-plus'></i> + Quick Gate Entry
                        </button>
                    </div>
                </div>
                <div id="canvas-3d-container"></div>
            </div>

            <!-- Clickable Interactive Metric Cards -->
            <div class="overview-boxes">
                <a href="../Members/index.php" class="box" data-animate data-delay="80" style="text-decoration: none; color: inherit; cursor: pointer; transition: all 0.25s ease;">
                    <div class="right-side">
                        <div class="box-topic" style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Total Members</span>
                            <span style="font-size: 11px; color: #6366f1; font-weight: 600;">Manage &rarr;</span>
                        </div>
                        <?php
                            $res_mem = mysqli_query($con, "SELECT COUNT(*) as cnt FROM member");
                            $row_mem = mysqli_fetch_assoc($res_mem);
                        ?>  
                        <div class="number"><?php echo $row_mem['cnt']; ?></div>
                    </div>
                    <i class='bx bxs-user icon member'></i>
                </a>

                <a href="../Members/staff/index.php" class="box" data-animate data-delay="120" style="text-decoration: none; color: inherit; cursor: pointer; transition: all 0.25s ease;">
                    <div class="right-side">
                        <div class="box-topic" style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Active Staff</span>
                            <span style="font-size: 11px; color: #06b6d4; font-weight: 600;">View Team &rarr;</span>
                        </div>
                        <?php
                            $res_stf = mysqli_query($con, "SELECT COUNT(*) as cnt FROM staff");
                            $row_stf = mysqli_fetch_assoc($res_stf);
                        ?>  
                        <div class="number"><?php echo $row_stf['cnt']; ?></div>
                    </div>
                    <i class='bx bxs-user-circle icon staff'></i>
                </a>

                <a href="../Bill/index.php" class="box" data-animate data-delay="180" style="text-decoration: none; color: inherit; cursor: pointer; transition: all 0.25s ease;">
                    <div class="right-side">
                        <div class="box-topic" style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Society Fund</span>
                            <span style="font-size: 11px; color: #10b981; font-weight: 600;">Collections &rarr;</span>
                        </div>
                        <?php
                            $res_fund = mysqli_query($con, "SELECT SUM(amount_payed) as societyfund FROM billing");
                            $row_fund = mysqli_fetch_assoc($res_fund);
                        ?> 
                        <div class="number">₹<?php echo number_format($row_fund['societyfund'] ? $row_fund['societyfund'] : 0); ?></div>
                    </div>
                    <i class='bx bx-money icon money'></i>
                </a>

                <a href="../Bill/index.php" class="box" data-animate data-delay="220" style="text-decoration: none; color: inherit; cursor: pointer; transition: all 0.25s ease;">
                    <div class="right-side">
                        <div class="box-topic" style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Unpaid Bills</span>
                            <span style="font-size: 11px; color: #f59e0b; font-weight: 600;">Audit &rarr;</span>
                        </div>
                        <?php
                            $res_unpaid = mysqli_query($con, "SELECT COUNT(*) as cnt FROM billing WHERE status=0");
                            $row_unpaid = mysqli_fetch_assoc($res_unpaid);
                        ?> 
                        <div class="number"><?php echo $row_unpaid['cnt']; ?></div>
                    </div>
                    <i class='bx bxs-file icon file'></i>
                </a>
            </div>

            <!-- Content Grid: Visitor Registry & Members -->
            <div class="sales-boxes">
                <div class="registry box">
                    <div class="title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <span><i class='bx bx-id-card' style="color:#6366f1;margin-right:6px;"></i> Live Visitor Gate Registry</span>
                            <span class="badge badge-success"><div class="online-dot" style="display:inline-block;margin-right:4px;"></div> Live Gate</span>
                        </div>
                        <button type="button" onclick="openVisitorModal()" class="btn btn-outline-primary btn-sm" style="font-size: 12px; padding: 5px 14px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; font-weight: 600; cursor: pointer;">
                            <i class='bx bx-plus-circle'></i> Add Visitor
                        </button>
                    </div>
                    <div class="registry-details">
                        <table id="visitorTable">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Entry Timestamp</th>
                                    <th>Person / Purpose</th>
                                </tr>
                            </thead>
                            <tbody id="visitorTableBody">
                                <?php
                                    $registry = $con->query("SELECT * FROM registry ORDER BY id DESC LIMIT 5");
                                    $i = 1;
                                    while($row = $registry->fetch_assoc()):
                                ?>
                                <tr>
                                    <td class="text-center" style="font-weight:700;color:#64748b;"><?php echo $i++; ?></td>
                                    <td><span style="font-weight:600;color:#0f172a;"><?php echo date("d M Y, h:i A", strtotime($row['created_at'])); ?></span></td>
                                    <td>
                                        <span style="display:inline-flex;align-items:center;gap:6px;">
                                            <i class='bx bx-user-check' style="color:#10b981;"></i>
                                            <b><?php echo htmlspecialchars($row['person_name']); ?></b>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="members box">
                    <div class="title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <span><i class='bx bx-group' style="color:#6366f1;margin-right:6px;"></i> Resident Directory</span>
                        <a href="../Members/index.php" class="btn btn-outline-primary btn-sm" style="font-size:12px;padding:4px 12px;border-radius:8px;text-decoration:none;">View All</a>
                    </div>
                    <!-- Real-time Filter -->
                    <div style="margin-bottom: 12px; position: relative;">
                        <i class='bx bx-search' style="position: absolute; left: 12px; top: 10px; color: #94a3b8; font-size: 16px;"></i>
                        <input type="text" id="residentSearchInput" placeholder="Live search resident, flat, phone..." style="width: 100%; padding: 8px 12px 8px 36px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 12px; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div class="registry-details">
                        <table id="residentTable">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Resident Name</th>
                                    <th>Email / Contact</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $member = $con->query("SELECT * FROM member ORDER BY id ASC LIMIT 6");
                                    $i = 1;
                                    while($row = $member->fetch_assoc()):
                                ?>
                                <tr class="resident-row">
                                    <td class="text-center" style="font-weight:700;color:#64748b;"><?php echo $i++; ?></td>
                                    <td>
                                        <b class="res-name"><?php echo htmlspecialchars($row['username']); ?></b>
                                        <div class="res-addr" style="font-size:11px;color:#64748b;"><?php echo htmlspecialchars(substr($row['address'] ?? '', 0, 32)) . '...'; ?></div>
                                    </td>
                                    <td>
                                        <div><a href="mailto:<?php echo htmlspecialchars($row['email']); ?>" class="res-email" style="font-size:13px;color:#334155;text-decoration:none;" onmouseover="this.style.color='#4f46e5'" onmouseout="this.style.color='#334155'"><?php echo htmlspecialchars($row['email']); ?></a></div>
                                        <div><a href="tel:<?php echo htmlspecialchars($row['phone'] ?? ''); ?>" class="res-phone" style="font-size:11px;color:#4f46e5;font-weight:600;text-decoration:none;"><i class='bx bx-phone'></i> <?php echo htmlspecialchars($row['phone'] ?? ''); ?></a></div>
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

    <!-- Quick Visitor Entry Modal -->
    <div id="quickVisitorModalOverlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center;">
        <div style="background: #ffffff; width: 92%; max-width: 460px; border-radius: 18px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden;">
            <div style="padding: 18px 24px; background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); color: #fff; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 34px; height: 34px; border-radius: 8px; background: rgba(99,102,241,0.25); display: flex; align-items: center; justify-content: center; color: #38bdf8;">
                        <i class='bx bx-user-plus' style="font-size: 20px;"></i>
                    </div>
                    <h5 style="margin: 0; font-size: 16px; font-weight: 700;">Quick Gate Visitor Log</h5>
                </div>
                <button type="button" onclick="closeVisitorModal()" style="background: none; border: none; color: #94a3b8; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
            </div>
            <form id="quickVisitorForm" style="padding: 24px;">
                <div id="visitorMsg" style="margin-bottom: 12px; display: none;"></div>
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Visitor Name & Purpose</label>
                    <input type="text" id="visitorPersonName" name="person_name" placeholder="e.g. Rahul Verma (Amazon Delivery - Flat 302)" required style="width: 100%; padding: 10px 14px; border-radius: 10px; border: 1.5px solid #cbd5e1; font-size: 13px; outline: none;" onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#cbd5e1'">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeVisitorModal()" style="padding: 9px 18px; border-radius: 10px; border: 1px solid #cbd5e1; background: #fff; font-size: 13px; font-weight: 600; color: #64748b; cursor: pointer;">Cancel</button>
                    <button type="submit" id="saveVisitorBtn" style="padding: 9px 20px; border-radius: 10px; border: none; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <i class='bx bx-check'></i> Log Visitor Entry
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Profile details dropdown toggle
        const profileBtn = document.getElementById('profileDropdownBtn');
        const profileDropdown = document.getElementById('profileDropdown');
        const profileChevron = document.getElementById('profileChevron');
        if (profileBtn && profileDropdown) {
            profileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const isShown = profileDropdown.style.display === 'block';
                profileDropdown.style.display = isShown ? 'none' : 'block';
                if (profileChevron) {
                    profileChevron.style.transform = isShown ? 'rotate(0deg)' : 'rotate(180deg)';
                }
            });
            document.addEventListener('click', function() {
                profileDropdown.style.display = 'none';
                if (profileChevron) profileChevron.style.transform = 'rotate(0deg)';
            });
        }

        // Live Real-Time Search Filter for Residents
        const searchInput = document.getElementById('residentSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const term = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('.resident-row');
                rows.forEach(function(row) {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(term) ? '' : 'none';
                });
            });
        }

        // Quick Visitor Modal Controls
        function openVisitorModal() {
            const overlay = document.getElementById('quickVisitorModalOverlay');
            overlay.style.display = 'flex';
            document.getElementById('visitorPersonName').focus();
        }

        function closeVisitorModal() {
            const overlay = document.getElementById('quickVisitorModalOverlay');
            overlay.style.display = 'none';
            document.getElementById('visitorMsg').style.display = 'none';
            document.getElementById('quickVisitorForm').reset();
        }

        // Handle Quick Visitor Form AJAX
        const visitorForm = document.getElementById('quickVisitorForm');
        if (visitorForm) {
            visitorForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const nameInput = document.getElementById('visitorPersonName');
                const btn = document.getElementById('saveVisitorBtn');
                const msgBox = document.getElementById('visitorMsg');
                const val = nameInput.value.trim();

                if (!val) return;

                btn.disabled = true;
                btn.innerHTML = "<i class='bx bx-loader-alt bx-spin'></i> Logging...";

                const formData = new FormData();
                formData.append('person_name', val);

                fetch('quick_visitor.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = "<i class='bx bx-check'></i> Log Visitor Entry";

                    if (data.status === 'success') {
                        // Prepend newly added visitor to live table
                        const tbody = document.getElementById('visitorTableBody');
                        const newRow = document.createElement('tr');
                        newRow.style.animation = 'fadeIn 0.3s ease';
                        newRow.innerHTML = `
                            <td class="text-center" style="font-weight:700;color:#10b981;">NEW</td>
                            <td><span style="font-weight:600;color:#0f172a;">${data.created_at}</span></td>
                            <td>
                                <span style="display:inline-flex;align-items:center;gap:6px;">
                                    <i class='bx bx-user-check' style="color:#10b981;"></i>
                                    <b>${data.person_name}</b>
                                </span>
                            </td>
                        `;
                        if (tbody.firstChild) {
                            tbody.insertBefore(newRow, tbody.firstChild);
                        } else {
                            tbody.appendChild(newRow);
                        }

                        closeVisitorModal();
                    } else {
                        msgBox.style.display = 'block';
                        msgBox.className = 'alert alert-danger';
                        msgBox.style.padding = '8px 12px';
                        msgBox.style.borderRadius = '8px';
                        msgBox.style.fontSize = '12px';
                        msgBox.innerText = data.message || 'Error saving visitor.';
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = "<i class='bx bx-check'></i> Log Visitor Entry";
                    msgBox.style.display = 'block';
                    msgBox.innerText = 'Network request failed. Try again.';
                });
            });
        }
    </script>
</body>
</html>
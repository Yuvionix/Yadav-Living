<?php 
  require_once("../../Includes/config.php"); 
  require_once("../../Includes/session.php"); 
  require_role(array('member', 'admin'), '../../login.php');
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Home Services Directory | Resident Portal</title>
    <link rel="stylesheet" href="home_ser.css">
    <link rel="stylesheet" href="../../css/master_theme.css">
    <link rel="shortcut icon" href="../../home/logo.svg">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .service-category {
            margin-bottom: 36px;
        }
        .category-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
        }
        .category-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--primary-gradient);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .category-title {
            font-size: 19px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .service-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
            padding: 20px;
            transition: all 0.25s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .service-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
            border-color: #cbd5e1;
        }
        .provider-head {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 14px;
        }
        .provider-avatar {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .av-security { background: linear-gradient(135deg, #f43f5e, #be123c); }
        .av-electric { background: linear-gradient(135deg, #f59e0b, #b45309); }
        .av-plumber { background: linear-gradient(135deg, #06b6d4, #0369a1); }
        .av-mover { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
        .av-clean { background: linear-gradient(135deg, #10b981, #047857); }
        .provider-name {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .provider-role {
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
        }
        .rating-stars {
            color: #f59e0b;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 14px;
        }
        .card-actions {
            border-top: 1px solid #f1f5f9;
            padding-top: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
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
                <a href="home_ser.php" class="active">
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
                <span class="neighbourhood">Home Services</span>
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
                <h2 style="font-size: 24px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Society Verified Staff & Essential Services</h2>
                <p style="font-size: 14px; color: #64748b; margin: 0;">Authorized gatekeepers, electricians, plumbers, housekeepers, and packers serving Yadav Living, Satsang Nagar, Deoghar</p>
            </div>

            <!-- Category 1: Security -->
            <div class="service-category">
                <div class="category-header">
                    <div class="category-icon" style="background: linear-gradient(135deg, #f43f5e, #be123c);"><i class='bx bx-shield-quarter'></i></div>
                    <h3 class="category-title">Security & Gate Management</h3>
                </div>
                <div class="services-grid">
                    <div class="service-card">
                        <div class="provider-head">
                            <div class="provider-avatar av-security"><i class='bx bx-user-check'></i></div>
                            <div>
                                <div class="provider-name">Ramesh Kumar</div>
                                <div class="provider-role">Chief Security Officer (Day Shift)</div>
                            </div>
                        </div>
                        <div class="rating-stars">
                            ★★★★★ <span style="color:#64748b;font-weight:600;font-size:12px;">4.9 (82 reviews)</span>
                        </div>
                        <div class="card-actions">
                            <span class="badge badge-success"><i class='bx bx-check'></i> On Duty (Gate 1)</span>
                            <a href="tel:+919835122334" class="btn btn-outline-primary btn-sm"><i class='bx bx-phone'></i> Call Guard</a>
                        </div>
                    </div>

                    <div class="service-card">
                        <div class="provider-head">
                            <div class="provider-avatar av-security"><i class='bx bx-moon'></i></div>
                            <div>
                                <div class="provider-name">Santosh Verma</div>
                                <div class="provider-role">Night Security & CCTV Patrol</div>
                            </div>
                        </div>
                        <div class="rating-stars">
                            ★★★★★ <span style="color:#64748b;font-weight:600;font-size:12px;">4.8 (64 reviews)</span>
                        </div>
                        <div class="card-actions">
                            <span class="badge badge-secondary"><i class='bx bx-time'></i> Night Shift (Gate 2)</span>
                            <a href="tel:+919835199887" class="btn btn-outline-primary btn-sm"><i class='bx bx-phone'></i> Call Guard</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category 2: Electricians & Solar -->
            <div class="service-category">
                <div class="category-header">
                    <div class="category-icon" style="background: linear-gradient(135deg, #f59e0b, #b45309);"><i class='bx bx-wrench'></i></div>
                    <h3 class="category-title">Electricians & Power Backup</h3>
                </div>
                <div class="services-grid">
                    <div class="service-card">
                        <div class="provider-head">
                            <div class="provider-avatar av-electric"><i class='bx bx-bulb'></i></div>
                            <div>
                                <div class="provider-name">Manoj Yadav</div>
                                <div class="provider-role">Master Electrician & Inverter Tech</div>
                            </div>
                        </div>
                        <div class="rating-stars">
                            ★★★★★ <span style="color:#64748b;font-weight:600;font-size:12px;">5.0 (96 reviews)</span>
                        </div>
                        <div class="card-actions">
                            <span class="badge badge-success"><i class='bx bx-check'></i> Available 24/7</span>
                            <a href="tel:+919431154321" class="btn btn-outline-primary btn-sm"><i class='bx bx-phone'></i> +91 94311 54321</a>
                        </div>
                    </div>

                    <div class="service-card">
                        <div class="provider-head">
                            <div class="provider-avatar av-electric"><i class='bx bx-sun'></i></div>
                            <div>
                                <div class="provider-name">Binod Mistri</div>
                                <div class="provider-role">Rooftop Solar & Electrical Tech</div>
                            </div>
                        </div>
                        <div class="rating-stars">
                            ★★★★☆ <span style="color:#64748b;font-weight:600;font-size:12px;">4.7 (41 reviews)</span>
                        </div>
                        <div class="card-actions">
                            <span class="badge badge-success"><i class='bx bx-check'></i> On Campus</span>
                            <a href="tel:+918789144332" class="btn btn-outline-primary btn-sm"><i class='bx bx-phone'></i> Call Tech</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category 3: Plumbers & Sanitization -->
            <div class="service-category">
                <div class="category-header">
                    <div class="category-icon" style="background: linear-gradient(135deg, #06b6d4, #0369a1);"><i class='bx bx-droplet'></i></div>
                    <h3 class="category-title">Plumbing & Water Management</h3>
                </div>
                <div class="services-grid">
                    <div class="service-card">
                        <div class="provider-head">
                            <div class="provider-avatar av-plumber"><i class='bx bx-water'></i></div>
                            <div>
                                <div class="provider-name">Suresh Mandal</div>
                                <div class="provider-role">Certified Plumber & Tank Specialist</div>
                            </div>
                        </div>
                        <div class="rating-stars">
                            ★★★★★ <span style="color:#64748b;font-weight:600;font-size:12px;">4.9 (78 reviews)</span>
                        </div>
                        <div class="card-actions">
                            <span class="badge badge-success"><i class='bx bx-check'></i> Available</span>
                            <a href="tel:+919835198765" class="btn btn-outline-primary btn-sm"><i class='bx bx-phone'></i> +91 98351 98765</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category 4: Packers & Movers -->
            <div class="service-category">
                <div class="category-header">
                    <div class="category-icon" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9);"><i class='bx bx-package'></i></div>
                    <h3 class="category-title">Packers & Movers (Deoghar Local & Intercity)</h3>
                </div>
                <div class="services-grid">
                    <div class="service-card">
                        <div class="provider-head">
                            <div class="provider-avatar av-mover"><i class='bx bxs-truck'></i></div>
                            <div>
                                <div class="provider-name">Baidyanath Relocations</div>
                                <div class="provider-role">Near AS College Rd, Satsang Nagar</div>
                            </div>
                        </div>
                        <div class="rating-stars">
                            ★★★★★ <span style="color:#64748b;font-weight:600;font-size:12px;">4.8 (112 reviews)</span>
                        </div>
                        <div class="card-actions">
                            <span class="badge badge-success"><i class='bx bx-badge-check'></i> Verified Partner</span>
                            <a href="tel:+917004123456" class="btn btn-outline-primary btn-sm"><i class='bx bx-phone'></i> Book Move</a>
                        </div>
                    </div>

                    <div class="service-card">
                        <div class="provider-head">
                            <div class="provider-avatar av-mover"><i class='bx bx-box'></i></div>
                            <div>
                                <div class="provider-name">Deoghar Express Cargo</div>
                                <div class="provider-role">Deoghar service provider</div>
                            </div>
                        </div>
                        <div class="rating-stars">
                            ★★★★☆ <span style="color:#64748b;font-weight:600;font-size:12px;">4.7 (89 reviews)</span>
                        </div>
                        <div class="card-actions">
                            <span class="badge badge-success"><i class='bx bx-badge-check'></i> Verified Partner</span>
                            <a href="tel:+918789165432" class="btn btn-outline-primary btn-sm"><i class='bx bx-phone'></i> Contact</a>
                        </div>
                    </div>
                </div>
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
<?php 
  require_once("../../Includes/config.php"); 
  require_once("../../Includes/session.php"); 
  require_once("../../Includes/property_locations.php");
  require_role(array('member', 'admin'), '../../login.php');
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Neighbourhood & Map Directory | Resident Portal</title>
    <link rel="stylesheet" href="member_neigh.css">
    <link rel="stylesheet" href="../../css/master_theme.css">
    <link rel="shortcut icon" href="../../home/logo.svg">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .loca-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(440px, 1fr));
            gap: 24px;
        }
        .place-card {
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .place-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.1);
            border-color: #cbd5e1;
        }
        .map-iframe-wrapper {
            position: relative;
            width: 100%;
            height: 220px;
            background: #f1f5f9;
        }
        .map-iframe-wrapper iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .place-info {
            padding: 22px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .place-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        .tag-society { background: #e0e7ff; color: #4338ca; }
        .tag-edu { background: #fef3c7; color: #b45309; }
        .tag-police { background: #fee2e2; color: #b91c1c; }
        .tag-hospital { background: #dcfce7; color: #15803d; }
        .tag-spiritual { background: #f3e8ff; color: #7e22ce; }
        .place-title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }
        .place-address {
            font-size: 13.5px;
            color: #475569;
            line-height: 1.55;
            margin-bottom: 12px;
        }
        .place-footer {
            border-top: 1px solid #f1f5f9;
            padding-top: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
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
                <a href="../Home_Services/home_ser.php">
                    <i class='bx bxs-user-circle'></i>
                    <span class="links_name">Home Service</span>
                </a>
            </li>
            <li>
                <a href="member_neigh.php" class="active">
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
                <span class="neighbourhood">Deoghar Neighbourhood Directory</span>
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
                <h2 style="font-size: 24px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Nearby Places & Interactive Location Maps</h2>
                <p style="font-size: 14px; color: #64748b; margin: 0;">Verified community landmarks, academic institutions, security outposts, and emergency healthcare in Satsang Nagar, Deoghar</p>
            </div>

            <div class="loca-grid">
                <!-- Society location -->
                <div class="place-card">
                    <div class="map-iframe-wrapper">
                        <iframe src="<?php echo htmlspecialchars(hms_property_map_url(HMS_SOCIETY_ADDRESS, true), ENT_QUOTES, 'UTF-8'); ?>" title="Yadav Living Society location map" loading="lazy"></iframe>
                    </div>
                    <div class="place-info">
                        <div>
                            <span class="place-tag tag-society"><i class='bx bx-home-alt'></i> Society location</span>
                            <h3 class="place-title">Yadav Living Community</h3>
                            <p class="place-address">
                                <i class='bx bx-map-pin' style="color:#4f46e5;"></i>
                                <b><?php echo htmlspecialchars(HMS_SOCIETY_ADDRESS, ENT_QUOTES, 'UTF-8'); ?></b><br>
                                Society address and map location.
                            </p>
                        </div>
                        <div class="place-footer">
                            <span style="color:#059669;font-weight:600;"><i class='bx bx-map-pin'></i> Yadav Living Society</span>
                            <a href="<?php echo htmlspecialchars(hms_property_map_url(HMS_SOCIETY_ADDRESS), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">Open in Maps</a>
                        </div>
                    </div>
                </div>

                <!-- PG location -->
                <div class="place-card">
                    <div class="map-iframe-wrapper">
                        <iframe src="<?php echo htmlspecialchars(hms_property_map_url(HMS_PG_ADDRESS, true), ENT_QUOTES, 'UTF-8'); ?>" title="Yadav Living PG location map" loading="lazy"></iframe>
                    </div>
                    <div class="place-info">
                        <div>
                            <span class="place-tag tag-edu"><i class='bx bxs-graduation'></i> Student accommodation</span>
                            <h3 class="place-title">Yadav Living PG</h3>
                            <p class="place-address">
                                <i class='bx bx-map-pin' style="color:#0284c7;"></i>
                                <b><?php echo htmlspecialchars(HMS_PG_ADDRESS, ENT_QUOTES, 'UTF-8'); ?></b><br>
                                PG address and map location.
                            </p>
                        </div>
                        <div class="place-footer">
                            <span style="color:#0284c7;font-weight:600;"><i class='bx bx-map-pin'></i> Yadav Living PG</span>
                            <a href="<?php echo htmlspecialchars(hms_property_map_url(HMS_PG_ADDRESS), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">Open in Maps</a>
                        </div>
                    </div>
                </div>

                <!-- Nearby academic landmark -->
                <div class="place-card">
                    <div class="map-iframe-wrapper">
                        <iframe src="https://maps.google.com/maps?q=A.S.+College,+Deoghar,+Jharkhand&t=&z=15&ie=UTF8&iwloc=&output=embed" title="A. S. College, Deoghar map" loading="lazy"></iframe>
                    </div>
                    <div class="place-info">
                        <div>
                            <span class="place-tag tag-edu"><i class='bx bx-book-open'></i> Nearby academic landmark</span>
                            <h3 class="place-title">A. S. College, Deoghar</h3>
                            <p class="place-address">
                                <i class='bx bx-map-pin' style="color:#b45309;"></i>
                                A nearby academic institution in Deoghar.
                            </p>
                        </div>
                        <div class="place-footer">
                            <span style="color:#475569;"><i class='bx bx-phone'></i> +91 6432 222452</span>
                            <a href="https://www.google.com/maps/search/?api=1&amp;query=A.S.%20College%2C%20Deoghar%2C%20Jharkhand" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">Open in Maps</a>
                        </div>
                    </div>
                </div>

                <!-- 3. Local Police Station & Security Outpost -->
                <div class="place-card">
                    <div class="map-iframe-wrapper">
                        <iframe src="https://maps.google.com/maps?q=Satsang+Nagar+Police+Chowki+Deoghar+Jharkhand+814142&t=&z=15&ie=UTF8&iwloc=&output=embed" loading="lazy"></iframe>
                    </div>
                    <div class="place-info">
                        <div>
                            <span class="place-tag tag-police"><i class='bx bx-shield-quarter'></i> Law Enforcement</span>
                            <h3 class="place-title">Satsang Nagar Police Outpost & Town PS</h3>
                            <p class="place-address">
                                <i class='bx bx-map-pin' style="color:#dc2626;"></i>
                                <b>Near Satsang Ashram, Satsang Nagar, Deoghar, Jharkhand 814142</b><br>
                                Local police outpost in the Satsang Nagar area.
                            </p>
                        </div>
                        <div class="place-footer">
                            <span style="color:#dc2626;font-weight:700;"><i class='bx bx-phone-call'></i> Emergency: 112 / +91 6432 222201</span>
                            <a href="https://maps.google.com/?q=Satsang+Nagar+Deoghar+Jharkhand" target="_blank" class="btn btn-outline-primary btn-sm">Directions</a>
                        </div>
                    </div>
                </div>

                <!-- 4. Hospitals & Healthcare -->
                <div class="place-card">
                    <div class="map-iframe-wrapper">
                        <iframe src="https://maps.google.com/maps?q=AIIMS+Deoghar+Jharkhand&t=&z=14&ie=UTF8&iwloc=&output=embed" loading="lazy"></iframe>
                    </div>
                    <div class="place-info">
                        <div>
                            <span class="place-tag tag-hospital"><i class='bx bx-plus-medical'></i> Emergency Care</span>
                            <h3 class="place-title">AIIMS Deoghar & Sadar Hospital</h3>
                            <p class="place-address">
                                <i class='bx bx-map-pin' style="color:#15803d;"></i>
                                <b>Castairs Town & AIIMS Campus, Deoghar, Jharkhand 814142</b><br>
                                State-of-the-art super-speciality medical institute and 24x7 trauma & ambulance emergency care.
                            </p>
                        </div>
                        <div class="place-footer">
                            <span style="color:#15803d;font-weight:700;"><i class='bx bx-phone-call'></i> Ambulance: 108 / +91 6432 232750</span>
                            <a href="https://maps.google.com/?q=AIIMS+Deoghar" target="_blank" class="btn btn-outline-primary btn-sm">Emergency Map</a>
                        </div>
                    </div>
                </div>

                <!-- 5. Cultural Landmark (Satsang Ashram) -->
                <div class="place-card">
                    <div class="map-iframe-wrapper">
                        <iframe src="https://maps.google.com/maps?q=Satsang+Ashram+Deoghar+Jharkhand+814142&t=&z=15&ie=UTF8&iwloc=&output=embed" loading="lazy"></iframe>
                    </div>
                    <div class="place-info">
                        <div>
                            <span class="place-tag tag-spiritual"><i class='bx bx-building'></i> Spiritual Landmark</span>
                            <h3 class="place-title">Sri Sri Thakur Anukulchandra Satsang Ashram</h3>
                            <p class="place-address">
                                <i class='bx bx-map-pin' style="color:#7e22ce;"></i>
                                <b>Satsang Nagar, Deoghar, Jharkhand 814142</b><br>
                                Historic world-renowned spiritual center with philanthropic medical center, botanical gardens, and community kitchen.
                            </p>
                        </div>
                        <div class="place-footer">
                            <span style="color:#475569;"><i class='bx bx-time'></i> Visiting Hours: 06:00 AM – 08:30 PM</span>
                            <a href="https://maps.google.com/?q=Satsang+Ashram+Deoghar" target="_blank" class="btn btn-outline-primary btn-sm">View Location</a>
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
<?php
$page_title = "PG Overview";
require_once("header.php");
require_once(__DIR__ . "/../../Includes/property_locations.php");
require_once(__DIR__ . "/../Includes/room_assignment.php");

// Handle Student Admission form submission
$msg = "";
$msg_type = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admit_student'])) {
    $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $phone = trim(isset($_POST['phone']) ? $_POST['phone'] : '');
    $college = trim(isset($_POST['college']) ? $_POST['college'] : 'AS College');
    $room_number = trim($_POST['room_number'] ?? '');
    $bed_label = trim($_POST['bed_label'] ?? '');
    $floor = trim($_POST['floor'] ?? '');
    $occupancy_type = trim($_POST['occupancy_type'] ?? '');
    $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $rent_amount = isset($_POST['rent_amount']) && $_POST['rent_amount'] !== '' ? (float)$_POST['rent_amount'] : 0;
    $deposit_amount = isset($_POST['deposit_amount']) && $_POST['deposit_amount'] !== '' ? (float)$_POST['deposit_amount'] : 0;
    $rent_is_valid = isset($_POST['rent_amount']) && is_numeric($_POST['rent_amount']) && $rent_amount > 0;
    $deposit_is_valid = isset($_POST['deposit_amount']) && is_numeric($_POST['deposit_amount']) && $deposit_amount >= 0;

    if ($name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/^\+?[0-9\s()-]{7,20}$/', $phone) &&
        preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username) && strlen($password) >= 10 &&
        $room_number !== '' && strlen($room_number) <= 50 && $bed_label !== '' && strlen($bed_label) <= 10 &&
        $floor !== '' && strlen($floor) <= 50 && in_array(strtolower($occupancy_type), array('single', 'double', 'triple'), true) &&
        $rent_is_valid && $deposit_is_valid) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        if (!mysqli_begin_transaction($con)) {
            error_log("PG admission transaction start failed: " . mysqli_error($con));
            $msg = "Admission could not be started. Please try again.";
            $msg_type = "danger";
        } else {
            $ins = mysqli_prepare($con, "INSERT INTO pg_students (name, username, email, password, phone, college, rent_amount, deposit_amount, joined_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'active', 'Yuvraj (Warden)')");
            if ($ins) {
                mysqli_stmt_bind_param($ins, "ssssssdd", $name, $username, $email, $hashed, $phone, $college, $rent_amount, $deposit_amount);
                $inserted = mysqli_stmt_execute($ins);
                if (!$inserted) {
                    error_log("PG student admission insert failed: " . mysqli_stmt_error($ins));
                }
                $new_student_id = $inserted ? mysqli_insert_id($con) : 0;
                mysqli_stmt_close($ins);
            } else {
                error_log("PG student admission prepare failed: " . mysqli_error($con));
                $new_student_id = 0;
            }

            $assignment_error = "";
            $assigned = $new_student_id > 0 && hms_pg_save_room_assignment(
                $con,
                $new_student_id,
                $room_number,
                $bed_label,
                $floor,
                $occupancy_type,
                $rent_amount,
                $deposit_amount,
                $assignment_error
            );
            $committed = $assigned && mysqli_commit($con);
            if ($assigned && !$committed) {
                error_log("PG admission commit failed: " . mysqli_error($con));
            }
            if ($committed) {
                $msg = "Student $name admitted. Room, bed, floor, occupancy, rent, and deposit are saved; the resident can complete PG KYC after signing in.";
                $msg_type = "success";
            } else {
                if ($new_student_id > 0 && !$assigned && $assignment_error === "") {
                    error_log("PG admission assignment could not be completed: " . mysqli_error($con));
                }
                mysqli_rollback($con);
                $msg = $assignment_error !== "" ? $assignment_error : "Student account could not be created. Check that the username and email are not already registered.";
                $msg_type = "danger";
            }
        }
    } else {
        $msg = "Enter the resident details, room and bed numbers, floor, single/double/triple occupancy, a unique username, a 10+ character password, and agreed rent and deposit.";
        $msg_type = "danger";
    }
}

// Compute live room, floor, and bed inventory from saved records.
$metrics_query = mysqli_query($con, "
    SELECT
        (SELECT COUNT(*) FROM pg_rooms) AS total_rooms,
        (SELECT COUNT(DISTINCT NULLIF(TRIM(floor), '')) FROM pg_rooms) AS total_floors,
        (SELECT COUNT(*) FROM pg_beds) AS total_beds,
        (SELECT COUNT(*) FROM pg_beds WHERE status = 'occupied') AS occupied_beds,
        (SELECT COUNT(*) FROM pg_beds WHERE status = 'vacant') AS vacant_beds
");
if (!$metrics_query) {
    error_log("PG dashboard metrics query failed: " . mysqli_error($con));
    $msg = "PG overview metrics could not be loaded. Please refresh or contact the administrator.";
    $msg_type = "danger";
    $total_rooms = $total_floors = $total_beds = $occupied_beds = $vacant_beds = 0;
} else {
    $metrics = mysqli_fetch_assoc($metrics_query);
    $total_rooms = (int)$metrics['total_rooms'];
    $total_floors = (int)$metrics['total_floors'];
    $total_beds = (int)$metrics['total_beds'];
    $occupied_beds = (int)$metrics['occupied_beds'];
    $vacant_beds = (int)$metrics['vacant_beds'];
}
$occupancy_rate = $total_beds > 0 ? round(($occupied_beds / $total_beds) * 100) : 0;
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: #0f172a;">Student Residency &amp; Occupancy</h2>
        <p style="font-size: 13px; color: #64748b; margin-top: 2px;">Manage room availability, admissions, and resident operations from one place.</p>
    </div>
    <button type="button" onclick="openAdmitModal()" style="background: #0099e5; color: #fff; border: none; padding: 10px 22px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
        <i class='bx bx-user-plus'></i> Admit New Student
    </button>
</div>

<div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:24px; padding:16px 20px; border:1px solid #bae6fd; border-radius:14px; background:#f0f9ff; color:#0c4a6e;">
    <i class='bx bx-map-pin' aria-hidden="true" style="font-size:22px;"></i>
    <div style="flex:1; min-width:220px;">
        <strong style="display:block; font-size:13px;">Yadav Living PG location</strong>
        <span style="font-size:13px;"><?php echo htmlspecialchars(HMS_PG_ADDRESS, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <a href="<?php echo htmlspecialchars(hms_property_map_url(HMS_PG_ADDRESS), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" style="display:inline-flex; align-items:center; gap:6px; padding:8px 12px; border-radius:8px; background:#0284c7; color:#fff; font-size:12px; font-weight:600; text-decoration:none;">
        <i class='bx bx-map' aria-hidden="true"></i> Open map
    </a>
</div>

<?php if (!empty($msg)): ?>
    <div style="padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 500; <?php echo $msg_type === 'success' ? 'background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;' : 'background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;'; ?>">
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<div style="margin-bottom: 24px; padding: 22px 24px; border-radius: 18px; background: linear-gradient(135deg, #0f172a 0%, #0b3b52 45%, #0284c7 100%); color: #fff; box-shadow: 0 18px 40px rgba(2, 132, 199, 0.2);" data-animate data-delay="0">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <div style="font-size: 12px; font-weight: 700; letter-spacing: 0.12em; color: #bae6fd; text-transform: uppercase;">PG Live Command Center</div>
            <h3 style="font-size: clamp(1.5rem, 2vw, 2.2rem); margin: 8px 0 4px; font-weight: 800;">Yadav Living PG Operations Overview</h3>
            <p style="margin: 0; color: rgba(255,255,255,0.8); font-size: 13px; max-width: 760px;">Single, double, and triple sharing occupancy managed in real time with warden access, maintenance handling, and student lifecycle visibility.</p>
        </div>
        <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.09); border: 1px solid rgba(255,255,255,0.2); border-radius: 999px; padding: 8px 14px; font-size: 12px; font-weight: 700; color: #fef3c7;">
            <i class='bx bx-signal-4'></i> Occupancy synced live
        </div>
    </div>
</div>

<!-- Stat KPI Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 28px;">
    <div class="admin-stat-card" data-animate data-delay="80">
        <div>
            <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Total Rooms</div>
            <div style="font-size: 26px; font-weight: 700; color: #0f172a; margin-top: 4px;"><?php echo $total_rooms; ?> Rooms</div>
            <div style="font-size: 11px; color: #64748b;">Across <?php echo $total_floors; ?> <?php echo $total_floors === 1 ? 'floor' : 'floors'; ?></div>
        </div>
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 22px;">
            <i class='bx bx-building'></i>
        </div>
    </div>

    <div class="admin-stat-card" data-animate data-delay="160">
        <div>
            <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Occupied Beds</div>
            <div style="font-size: 26px; font-weight: 700; color: #0284c7; margin-top: 4px;"><?php echo $occupied_beds; ?> / <?php echo $total_beds; ?></div>
            <div style="font-size: 11px; color: #0284c7; font-weight: 600;"><?php echo $occupancy_rate; ?>% Occupancy Rate</div>
        </div>
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 22px;">
            <i class='bx bx-user-check'></i>
        </div>
    </div>

    <div class="admin-stat-card" data-animate data-delay="200">
        <div>
            <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Available Vacant Beds</div>
            <div style="font-size: 26px; font-weight: 700; color: #16a34a; margin-top: 4px;"><?php echo $vacant_beds; ?> Vacant</div>
            <div style="font-size: 11px; color: #16a34a; font-weight: 600;">Ready for Immediate Move-in</div>
        </div>
        <div style="width: 44px; height: 44px; border-radius: 10px; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center; font-size: 22px;">
            <i class='bx bx-bed'></i>
        </div>
    </div>

</div>

<!-- Room & Bed Allocation Matrix -->
<div style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; margin-bottom: 28px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
        <h3 style="font-size: 16px; font-weight: 700; color: #0f172a;">Room & Bed Occupancy Matrix (Single, Double & Triple Sharing)</h3>
        <div style="display: flex; gap: 12px; font-size: 12px;">
            <span style="display: inline-flex; align-items: center; gap: 4px; color: #15803d;"><span style="width: 10px; height: 10px; border-radius: 50%; background: #10b981;"></span> Vacant Bed</span>
            <span style="display: inline-flex; align-items: center; gap: 4px; color: #0284c7;"><span style="width: 10px; height: 10px; border-radius: 50%; background: #0099e5;"></span> Occupied Bed</span>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px;">
        <?php
        $rooms_q = mysqli_query($con, "SELECT * FROM pg_rooms ORDER BY room_number ASC");
        if (mysqli_num_rows($rooms_q) === 0):
        ?>
            <div style="grid-column:1 / -1; padding:24px; border:1px dashed #cbd5e1; border-radius:12px; background:#f8fafc; text-align:center;">
                <i class='bx bx-building-house' style="font-size:28px; color:#0284c7;"></i>
                <p style="margin:8px 0 12px; color:#475569; font-size:13px;">No PG rooms have been added yet. Add your actual room numbers and floors to begin tracking occupancy.</p>
                <a href="rooms.php" style="display:inline-flex; align-items:center; gap:6px; padding:8px 12px; border-radius:8px; background:#0284c7; color:#fff; font-size:12px; font-weight:700; text-decoration:none;">
                    <i class='bx bx-plus'></i> Add rooms &amp; beds
                </a>
            </div>
        <?php
        else:
        while ($rm = mysqli_fetch_assoc($rooms_q)):
            $rm_id = $rm['id'];
            $beds_q = mysqli_query($con, "SELECT b.*, s.name as student_name, s.phone as student_phone 
                                          FROM pg_beds b 
                                          LEFT JOIN pg_students s ON b.student_id = s.id 
                                          WHERE b.room_id = $rm_id ORDER BY b.bed_label ASC");
        ?>
        <div style="border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 16px; background: #f8fafc;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <div>
                    <strong style="font-size: 15px; color: #0f172a;"><?php echo htmlspecialchars($rm['room_number']); ?></strong>
                    <span style="font-size: 11px; color: #64748b; margin-left: 6px;"><?php echo htmlspecialchars($rm['floor']); ?></span>
                </div>
                <span class="badge-occupancy" style="background: <?php echo $rm['occupancy_type'] === 'single' ? '#fef3c7; color: #b45309;' : ($rm['occupancy_type'] === 'double' ? '#e0f2fe; color: #0369a1;' : '#f3e8ff; color: #7e22ce;'); ?>">
                    <?php echo ucfirst($rm['occupancy_type']); ?> Sharing
                </span>
            </div>

            <!-- Beds Inside Room -->
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <?php while ($bed = mysqli_fetch_assoc($beds_q)): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: #ffffff; border-radius: 6px; border: 1px solid #e2e8f0;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class='bx bx-bed' style="font-size: 18px; color: <?php echo $bed['status'] === 'occupied' ? '#0099e5' : '#10b981'; ?>;"></i>
                            <span style="font-size: 12px; font-weight: 600; color: #334155;"><?php echo htmlspecialchars($bed['bed_label']); ?></span>
                        </div>

                        <?php if ($bed['status'] === 'occupied'): ?>
                            <div style="text-align: right;">
                                <div style="font-size: 12px; font-weight: 700; color: #0284c7;"><?php echo htmlspecialchars($bed['student_name']); ?></div>
                                <div style="font-size: 10px; color: #64748b;"><i class='bx bx-phone'></i> <?php echo htmlspecialchars($bed['student_phone']); ?></div>
                            </div>
                        <?php else: ?>
                            <button type="button" onclick="admitToRoom(<?php echo htmlspecialchars(json_encode($rm['room_number']), ENT_QUOTES, 'UTF-8'); ?>, <?php echo htmlspecialchars(json_encode($rm['floor']), ENT_QUOTES, 'UTF-8'); ?>, <?php echo htmlspecialchars(json_encode($rm['occupancy_type']), ENT_QUOTES, 'UTF-8'); ?>)" style="background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer;">
                                + Admit to this room
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Admit Student -->
<div id="admitModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: #ffffff; width: 92%; max-width: 500px; border-radius: 14px; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.25);">
        <div style="padding: 16px 22px; background: #0099e5; color: #fff; display: flex; justify-content: space-between; align-items: center;">
            <h4 style="margin: 0; font-size: 16px; font-weight: 600;"><i class='bx bx-user-plus'></i> Admit Student to PG</h4>
            <button type="button" onclick="closeAdmitModal()" style="background: none; border: none; color: #fff; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="dashboard.php" style="padding: 22px;">
            <div style="margin-bottom: 14px;">
                <label style="font-size: 12px; font-weight: 600; color: #334155;">Student Full Name *</label>
                <input type="text" name="name" placeholder="e.g. Rahul Sharma" required style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13px; margin-top: 4px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="font-size: 12px; font-weight: 600; color: #334155;">Login Username *</label>
                    <input type="text" name="username" maxlength="50" autocomplete="username" pattern="[A-Za-z0-9._-]+" title="Use letters, numbers, dots, underscores or hyphens." required style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13px; margin-top: 4px;">
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 600; color: #334155;">Initial Password * (10+ characters)</label>
                    <input type="password" name="password" minlength="10" autocomplete="new-password" required style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13px; margin-top: 4px;">
                </div>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="font-size: 12px; font-weight: 600; color: #334155;">Email Address *</label>
                <input type="email" name="email" placeholder="e.g. rahul.sharma@gmail.com" required style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13px; margin-top: 4px;">
            </div>

            <div style="margin-bottom: 14px;">
                <label style="font-size: 12px; font-weight: 600; color: #334155;">Mobile Phone Number *</label>
                <input type="tel" name="phone" placeholder="e.g. 9835123456" required style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13px; margin-top: 4px;">
            </div>

            <div style="margin-bottom: 14px;">
                <label style="font-size: 12px; font-weight: 600; color: #334155;">College / Coaching Name</label>
                <input type="text" name="college" placeholder="e.g. AS College / Allen Career" value="AS College Deoghar" style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13px; margin-top: 4px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label for="modalRoomNumber" style="font-size: 12px; font-weight: 600; color: #334155;">Room number *</label>
                    <input type="text" name="room_number" id="modalRoomNumber" maxlength="50" placeholder="Type the actual room number" required style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13px; margin-top: 4px;">
                </div>
                <div>
                    <label for="modalBedLabel" style="font-size: 12px; font-weight: 600; color: #334155;">Bed label *</label>
                    <input type="text" name="bed_label" id="modalBedLabel" maxlength="10" placeholder="e.g. Bed A" required style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13px; margin-top: 4px;">
                </div>
                <div>
                    <label for="modalFloor" style="font-size: 12px; font-weight: 600; color: #334155;">Floor *</label>
                    <input type="text" name="floor" id="modalFloor" maxlength="50" placeholder="e.g. First Floor" required style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13px; margin-top: 4px;">
                </div>
                <div>
                    <label for="modalOccupancyType" style="font-size: 12px; font-weight: 600; color: #334155;">Occupancy type *</label>
                    <input type="text" name="occupancy_type" id="modalOccupancyType" title="Enter Single, Double, or Triple." placeholder="Single, double, or triple" required style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13px; margin-top: 4px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                <div>
                    <label style="font-size: 12px; font-weight: 600; color: #334155;">Agreed Rent / Month (₹) *</label>
                    <input type="number" id="modalRentAmount" name="rent_amount" min="0.01" step="0.01" placeholder="Enter agreed monthly rent" required style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13px; margin-top: 4px;">
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 600; color: #334155;">Agreed Security Deposit (₹) *</label>
                    <input type="number" id="modalDepositAmount" name="deposit_amount" min="0" step="0.01" placeholder="Enter agreed deposit" required style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 13px; margin-top: 4px;">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeAdmitModal()" style="padding: 10px 18px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; font-size: 13px; font-weight: 600; cursor: pointer;">Cancel</button>
                <button type="submit" name="admit_student" style="padding: 10px 22px; border-radius: 8px; border: none; background: #0099e5; color: #fff; font-size: 13px; font-weight: 600; cursor: pointer;">
                    Confirm Admission
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAdmitModal() {
        document.getElementById('admitModal').style.display = 'flex';
    }
    function closeAdmitModal() {
        document.getElementById('admitModal').style.display = 'none';
    }
    function admitToRoom(roomNumber, floor, occupancyType) {
        document.getElementById('admitModal').style.display = 'flex';
        document.getElementById('modalRoomNumber').value = roomNumber;
        document.getElementById('modalFloor').value = floor;
        document.getElementById('modalOccupancyType').value = occupancyType;
        document.getElementById('modalBedLabel').focus();
    }
</script>

<?php require_once("footer.php"); ?>

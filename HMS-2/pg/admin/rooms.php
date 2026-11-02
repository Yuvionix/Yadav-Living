<?php
$page_title = "Rooms & Beds";
require_once("header.php");

$room_action_msg = "";
$room_action_type = "success";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['create_room']) || isset($_POST['update_room']))) {
    $room_id = (int)($_POST['room_id'] ?? 0);
    $room_number = trim($_POST['room_number'] ?? '');
    $floor = trim($_POST['floor'] ?? '');
    $occupancy_type = strtolower(trim($_POST['occupancy_type'] ?? ''));
    $bed_capacity_by_type = array('single' => 1, 'double' => 2, 'triple' => 3);

    if ($room_number === '' || strlen($room_number) > 50 || $floor === '' || strlen($floor) > 50 || !isset($bed_capacity_by_type[$occupancy_type])) {
        $room_action_msg = "Enter a room number, floor, and Single, Double, or Triple occupancy.";
        $room_action_type = "danger";
    } elseif (!mysqli_begin_transaction($con)) {
        error_log("PG room management transaction start failed: " . mysqli_error($con));
        $room_action_msg = "Room changes could not be started. Please try again.";
        $room_action_type = "danger";
    } else {
        $capacity = $bed_capacity_by_type[$occupancy_type];
        $room_saved = false;
        if (isset($_POST['create_room'])) {
            $insert_room = mysqli_prepare($con, "INSERT INTO pg_rooms (room_number, floor, occupancy_type, total_beds, monthly_rent) VALUES (?, ?, ?, ?, 0)");
            if ($insert_room) {
                mysqli_stmt_bind_param($insert_room, "sssi", $room_number, $floor, $occupancy_type, $capacity);
                $room_saved = mysqli_stmt_execute($insert_room);
                if (!$room_saved) {
                    error_log("PG room creation failed: " . mysqli_stmt_error($insert_room));
                } else {
                    $room_id = mysqli_insert_id($con);
                    $current_bed_count = 0;
                }
                mysqli_stmt_close($insert_room);
            } else {
                error_log("PG room creation prepare failed: " . mysqli_error($con));
            }
        } else {
            $room_lock = mysqli_prepare($con, "SELECT id FROM pg_rooms WHERE id = ? FOR UPDATE");
            if ($room_lock) {
                mysqli_stmt_bind_param($room_lock, "i", $room_id);
                $room_saved = mysqli_stmt_execute($room_lock) && mysqli_stmt_store_result($room_lock) && mysqli_stmt_num_rows($room_lock) === 1;
                mysqli_stmt_close($room_lock);
            } else {
                error_log("PG room update lookup prepare failed: " . mysqli_error($con));
            }

            if ($room_saved) {
                $bed_counts = mysqli_prepare($con, "SELECT COUNT(*), COALESCE(SUM(status = 'occupied'), 0) FROM pg_beds WHERE room_id = ?");
                if ($bed_counts) {
                    mysqli_stmt_bind_param($bed_counts, "i", $room_id);
                    $counted = mysqli_stmt_execute($bed_counts) &&
                        mysqli_stmt_bind_result($bed_counts, $current_bed_count, $occupied_bed_count) &&
                        mysqli_stmt_fetch($bed_counts);
                    if (!$counted) {
                        error_log("PG room bed count failed: " . mysqli_stmt_error($bed_counts));
                        $room_saved = false;
                    }
                    mysqli_stmt_close($bed_counts);
                } else {
                    error_log("PG room bed count prepare failed: " . mysqli_error($con));
                    $room_saved = false;
                }

                if ($room_saved && $occupied_bed_count > $capacity) {
                    $room_saved = false;
                    $room_action_msg = "Occupancy cannot be reduced below the number of currently occupied beds.";
                    $room_action_type = "danger";
                }

                if ($room_saved) {
                    $update_room = mysqli_prepare($con, "UPDATE pg_rooms SET room_number = ?, floor = ?, occupancy_type = ?, total_beds = ? WHERE id = ?");
                    if ($update_room) {
                        mysqli_stmt_bind_param($update_room, "sssii", $room_number, $floor, $occupancy_type, $capacity, $room_id);
                        $room_saved = mysqli_stmt_execute($update_room);
                        if (!$room_saved) {
                            error_log("PG room update failed: " . mysqli_stmt_error($update_room));
                        }
                        mysqli_stmt_close($update_room);
                    } else {
                        error_log("PG room update prepare failed: " . mysqli_error($con));
                        $room_saved = false;
                    }
                }

                while ($room_saved && $current_bed_count > $capacity) {
                    $vacant_bed = mysqli_prepare($con, "SELECT id FROM pg_beds WHERE room_id = ? AND status = 'vacant' AND student_id IS NULL ORDER BY id DESC LIMIT 1 FOR UPDATE");
                    if (!$vacant_bed) {
                        error_log("PG vacant bed lookup prepare failed: " . mysqli_error($con));
                        $room_saved = false;
                        break;
                    }
                    mysqli_stmt_bind_param($vacant_bed, "i", $room_id);
                    $found_vacant = mysqli_stmt_execute($vacant_bed) &&
                        mysqli_stmt_bind_result($vacant_bed, $vacant_bed_id) &&
                        mysqli_stmt_fetch($vacant_bed);
                    mysqli_stmt_close($vacant_bed);
                    if (!$found_vacant) {
                        error_log("PG room capacity reduction could not locate a vacant bed.");
                        $room_saved = false;
                        break;
                    }
                    $delete_bed = mysqli_prepare($con, "DELETE FROM pg_beds WHERE id = ? AND status = 'vacant' AND student_id IS NULL");
                    if (!$delete_bed) {
                        error_log("PG vacant bed removal prepare failed: " . mysqli_error($con));
                        $room_saved = false;
                        break;
                    }
                    mysqli_stmt_bind_param($delete_bed, "i", $vacant_bed_id);
                    $deleted = mysqli_stmt_execute($delete_bed) && mysqli_stmt_affected_rows($delete_bed) === 1;
                    if (!$deleted) {
                        error_log("PG vacant bed removal failed: " . mysqli_stmt_error($delete_bed));
                    }
                    mysqli_stmt_close($delete_bed);
                    $room_saved = $deleted;
                    if ($deleted) {
                        $current_bed_count--;
                    }
                }
            }
        }

        if ($room_saved) {
            $bed_lookup = mysqli_prepare($con, "SELECT id FROM pg_beds WHERE room_id = ? AND bed_label = ? LIMIT 1");
            $bed_insert = mysqli_prepare($con, "INSERT INTO pg_beds (room_id, bed_label, student_id, status) VALUES (?, ?, NULL, 'vacant')");
            if (!$bed_lookup || !$bed_insert) {
                error_log("PG room bed synchronization prepare failed: " . mysqli_error($con));
                $room_saved = false;
            } else {
                for ($index = 0; $room_saved && $index < $capacity; $index++) {
                    $bed_label = 'Bed ' . chr(65 + $index);
                    mysqli_stmt_bind_param($bed_lookup, "is", $room_id, $bed_label);
                    $lookup_ok = mysqli_stmt_execute($bed_lookup) && mysqli_stmt_store_result($bed_lookup);
                    if (!$lookup_ok) {
                        error_log("PG room bed lookup failed: " . mysqli_stmt_error($bed_lookup));
                        $room_saved = false;
                        break;
                    }
                    $bed_exists = mysqli_stmt_num_rows($bed_lookup) > 0;
                    mysqli_stmt_free_result($bed_lookup);
                    if (!$bed_exists) {
                        if ($current_bed_count >= $capacity) {
                            continue;
                        }
                        mysqli_stmt_bind_param($bed_insert, "is", $room_id, $bed_label);
                        $room_saved = mysqli_stmt_execute($bed_insert);
                        if (!$room_saved) {
                            error_log("PG room bed creation failed: " . mysqli_stmt_error($bed_insert));
                        } else {
                            $current_bed_count++;
                        }
                    }
                }
                mysqli_stmt_close($bed_lookup);
                mysqli_stmt_close($bed_insert);
            }
        }

        if ($room_saved && mysqli_commit($con)) {
            $room_action_msg = isset($_POST['create_room']) ? "Room and vacant bed inventory created." : "Room details and bed capacity updated.";
        } else {
            if ($room_saved) {
                error_log("PG room management commit failed: " . mysqli_error($con));
            }
            mysqli_rollback($con);
            if ($room_action_msg === "") {
                $room_action_msg = "Room could not be saved. Check that the room number is unique and try again.";
            }
            $room_action_type = "danger";
        }
    }
}

$rooms_q = mysqli_query($con, "SELECT r.*, 
                               (SELECT COUNT(*) FROM pg_beds WHERE room_id = r.id) as bed_count,
                               (SELECT COUNT(*) FROM pg_beds WHERE room_id = r.id AND status = 'occupied') as occupied_count
                               FROM pg_rooms r ORDER BY r.room_number ASC");
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: #0f172a;">Rooms & Sharing Configuration</h2>
        <p style="font-size: 13px; color: #64748b; margin-top: 2px;">Manage Single, Double, and Triple occupancy rooms & see joined students across all floors.</p>
    </div>
    <a href="dashboard.php" style="background: #0099e5; color: #fff; padding: 9px 18px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
        <i class='bx bx-grid-alt'></i> View Occupancy Matrix
    </a>
</div>

<?php if ($room_action_msg !== ""): ?>
    <div role="status" style="padding:12px 18px; border-radius:8px; margin-bottom:20px; font-size:13px; font-weight:500; <?php echo $room_action_type === 'success' ? 'background:#dcfce7; color:#166534; border:1px solid #bbf7d0;' : 'background:#fee2e2; color:#b91c1c; border:1px solid #fecaca;'; ?>">
        <?php echo htmlspecialchars($room_action_msg, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<section style="background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:20px; margin-bottom:22px;">
    <h3 style="font-size:16px; font-weight:700; color:#0f172a; margin-bottom:5px;">Add a PG Room</h3>
    <p style="font-size:12px; color:#64748b; margin-bottom:15px;">Enter the real room number and floor. Bed inventory is created from the chosen occupancy; overview totals update from saved room and bed records.</p>
    <form method="POST" action="rooms.php" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(190px, 1fr)); gap:12px; align-items:end;">
        <label style="font-size:12px; font-weight:600; color:#334155;">Room number *
            <input type="text" name="room_number" maxlength="50" placeholder="Type the actual room number" required style="display:block; width:100%; margin-top:5px; padding:10px 12px; border:1px solid #cbd5e1; font-size:13px;">
        </label>
        <label style="font-size:12px; font-weight:600; color:#334155;">Floor *
            <input type="text" name="floor" maxlength="50" placeholder="Type the floor name or number" required style="display:block; width:100%; margin-top:5px; padding:10px 12px; border:1px solid #cbd5e1; font-size:13px;">
        </label>
        <label style="font-size:12px; font-weight:600; color:#334155;">Occupancy *
            <input type="text" name="occupancy_type" placeholder="Single, double, or triple" required style="display:block; width:100%; margin-top:5px; padding:10px 12px; border:1px solid #cbd5e1; font-size:13px;">
        </label>
        <button type="submit" name="create_room" value="1" style="min-height:42px; border:0; border-radius:9px; background:linear-gradient(135deg,#0f766e,#0284c7); color:#fff; font-size:13px; font-weight:700; cursor:pointer;">
            <i class='bx bx-plus-circle'></i> Add room &amp; beds
        </button>
    </form>
</section>

<div class="pg-table-card" style="background:#fff; border-radius:10px; border:1px solid #e2e8f0; overflow-x:auto;">
    <table style="width:100%; border-collapse:collapse; text-align:left; min-width: 850px;">
        <thead>
            <tr style="background:#f8fafc; border-bottom:1.5px solid #e2e8f0;">
                <th style="padding:14px 18px; font-size:12px; color:#64748b;">Room No</th>
                <th style="padding:14px 18px; font-size:12px; color:#64748b;">Floor</th>
                <th style="padding:14px 18px; font-size:12px; color:#64748b;">Occupancy Type</th>
                <th style="padding:14px 18px; font-size:12px; color:#64748b;">Joined Resident Students</th>
                <th style="padding:14px 18px; font-size:12px; color:#64748b;">Pricing / Terms</th>
                <th style="padding:14px 18px; font-size:12px; color:#64748b; text-align:right;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($rooms_q) === 0): ?>
                <tr>
                    <td colspan="6" style="padding:32px; text-align:center; color:#64748b;">
                        No room inventory yet. Add your actual PG rooms using the form above.
                    </td>
                </tr>
            <?php else: ?>
            <?php while ($r = mysqli_fetch_assoc($rooms_q)):
                $rm_id = (int)$r['id'];
                $beds_in_rm = mysqli_query($con, "SELECT b.bed_label, b.status, s.id as stu_id, s.name as stu_name, s.phone 
                                                  FROM pg_beds b 
                                                  LEFT JOIN pg_students s ON b.student_id = s.id 
                                                  WHERE b.room_id = $rm_id 
                                                  ORDER BY b.bed_label ASC");
            ?>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:14px 18px; font-weight:700; color:#0f172a; font-size:14px;">
                        <i class='bx bx-door-open' style="color: #0099e5; font-size: 16px;"></i> <?php echo htmlspecialchars($r['room_number']); ?>
                    </td>
                    <td style="padding:14px 18px; color:#475569; font-size:13px;">
                        <?php echo htmlspecialchars($r['floor']); ?>
                    </td>
                    <td style="padding:14px 18px;">
                        <span style="background:<?php echo $r['occupancy_type'] === 'single' ? '#fef3c7; color:#b45309;' : ($r['occupancy_type'] === 'double' ? '#e0f2fe; color:#0369a1;' : '#f3e8ff; color:#7e22ce;'); ?>; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700;">
                            <?php echo strtoupper($r['occupancy_type']); ?> OCCUPANCY
                        </span>
                    </td>
                    <td style="padding:14px 18px;">
                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                            <?php while ($bd = mysqli_fetch_assoc($beds_in_rm)): ?>
                                <?php if ($bd['status'] === 'occupied' && !empty($bd['stu_name'])): ?>
                                    <span style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; padding: 4px 10px; border-radius: 12px; font-size: 11.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;" title="Phone: <?php echo htmlspecialchars($bd['phone']); ?>">
                                        <i class='bx bx-user-check' style="color: #0284c7;"></i> <?php echo htmlspecialchars($bd['bed_label']); ?>: <b><?php echo htmlspecialchars($bd['stu_name']); ?></b>
                                    </span>
                                <?php else: ?>
                                    <span style="background: #f1f5f9; color: #64748b; border: 1px dashed #cbd5e1; padding: 4px 10px; border-radius: 12px; font-size: 11px;">
                                        <i class='bx bx-bed'></i> <?php echo htmlspecialchars($bd['bed_label']); ?>: Vacant
                                    </span>
                                <?php endif; ?>
                            <?php endwhile; ?>
                        </div>
                    </td>
                    <td style="padding:14px 18px;">
                        <span style="color: #0369a1; font-weight: 600; font-size: 12px; background: #e0f2fe; padding: 4px 10px; border-radius: 12px;">
                            <i class='bx bx-user-voice'></i> Owner Defined
                        </span>
                    </td>
                    <td style="padding:14px 18px; text-align:right;">
                        <?php if ($r['occupied_count'] === $r['bed_count']): ?>
                            <span style="background:#fee2e2; color:#b91c1c; font-size:11px; font-weight:700; padding:4px 10px; border-radius:20px;">FULL</span>
                        <?php else: ?>
                            <span style="background:#dcfce7; color:#15803d; font-size:11px; font-weight:700; padding:4px 10px; border-radius:20px;"><?php echo ($r['bed_count'] - $r['occupied_count']); ?> VACANT</span>
                        <?php endif; ?>
                        <details style="margin-top:8px; text-align:left;">
                            <summary style="font-size:11px; color:#0369a1; font-weight:700; cursor:pointer;">Edit room details</summary>
                            <form method="POST" action="rooms.php" style="display:grid; gap:7px; min-width:175px; margin-top:8px;">
                                <input type="hidden" name="room_id" value="<?php echo $rm_id; ?>">
                                <label style="font-size:10px; color:#475569;">Room number
                                    <input type="text" name="room_number" maxlength="50" value="<?php echo htmlspecialchars($r['room_number'], ENT_QUOTES, 'UTF-8'); ?>" required style="display:block; width:100%; margin-top:3px; padding:6px; font-size:11px;">
                                </label>
                                <label style="font-size:10px; color:#475569;">Floor
                                    <input type="text" name="floor" maxlength="50" value="<?php echo htmlspecialchars($r['floor'], ENT_QUOTES, 'UTF-8'); ?>" required style="display:block; width:100%; margin-top:3px; padding:6px; font-size:11px;">
                                </label>
                                <label style="font-size:10px; color:#475569;">Occupancy (single/double/triple)
                                    <input type="text" name="occupancy_type" value="<?php echo htmlspecialchars($r['occupancy_type'], ENT_QUOTES, 'UTF-8'); ?>" required style="display:block; width:100%; margin-top:3px; padding:6px; font-size:11px;">
                                </label>
                                <button type="submit" name="update_room" value="1" style="padding:7px; border:0; border-radius:5px; background:#0284c7; color:#fff; font-size:11px; font-weight:700; cursor:pointer;">Save room &amp; bed capacity</button>
                            </form>
                        </details>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once("footer.php"); ?>

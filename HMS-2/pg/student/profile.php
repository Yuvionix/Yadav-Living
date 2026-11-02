<?php
$page_title = "Profile";
require_once("header.php");
?>

<div class="page-title-banner">
    <h2>PROFILE</h2>
</div>

<?php
$q_chk_kyc = mysqli_query($con, "SELECT status, father_name FROM pg_kyc WHERE student_id = $current_student_id ORDER BY id DESC LIMIT 1");
$chk_kyc = mysqli_fetch_assoc($q_chk_kyc);
$kyc_done = $chk_kyc && !empty($chk_kyc['father_name']);
?>

<?php if (!$kyc_done): ?>
    <div style="background: #fef3c7; border: 1.5px solid #fde047; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 10px; color: #92400e; font-size: 13.5px; font-weight: 500;">
            <i class='bx bx-error-circle' style="font-size: 22px; color: #d97706;"></i>
            <span><strong>KYC Verification Pending:</strong> Please submit your Guardian details, Address, and Government ID Proofs.</span>
        </div>
        <a href="kyc.php" style="background: #0099e5; color: #ffffff; padding: 8px 18px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
            <i class='bx bx-id-card'></i> Complete KYC Now &rarr;
        </a>
    </div>
<?php endif; ?>

<div style="background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
    <div style="display: flex; gap: 48px; align-items: flex-start; flex-wrap: wrap;">
        
        <!-- Left: Student Oval Portrait -->
        <div style="display: flex; flex-direction: column; align-items: center; min-width: 280px;">
            <div style="width: 240px; height: 340px; border-radius: 120px / 170px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.12); border: 4px solid #ffffff; background: #0f172a;">
                <?php 
                    $photo_url = "";
                    if (!empty($current_student['photo'])) {
                        if (hms_kyc_file_path('pg', $current_student['photo']) !== false) {
                            $photo_url = hms_kyc_download_url('pg', $current_student['photo']);
                        } elseif (file_exists(__DIR__ . "/../assets/" . $current_student['photo'])) {
                            $photo_url = "../assets/" . htmlspecialchars($current_student['photo']);
                        }
                    }
                ?>
                <?php if (!empty($photo_url)): ?>
                    <img src="<?php echo htmlspecialchars($photo_url, ENT_QUOTES, 'UTF-8'); ?>" alt="Student Photo" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 64px; font-weight: 700;">
                        <?php echo strtoupper(substr($current_student['name'] ?? 'Y', 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div style="margin-top: 14px;">
                <a href="kyc.php" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; color: #0284c7; text-decoration: none; font-weight: 600; background: #e0f2fe; padding: 6px 14px; border-radius: 20px; transition: all 0.2s;">
                    <i class='bx bx-camera'></i> Update Photo &amp; Documents
                </a>
            </div>
        </div>

        <!-- Right: Profile Info Sheet matching Screenshot 3 -->
        <div style="flex: 1; min-width: 340px;">
            <div style="margin-bottom: 24px;">
                <h1 style="font-size: 28px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">
                    <?php echo htmlspecialchars($current_student['name'] ?? 'Yuvraj Yadav'); ?>
                </h1>
                <div style="display: inline-block; font-size: 13px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.5px;">
                    GUEST - <?php echo strtoupper($current_student['status'] ?? 'ACTIVE'); ?>
                </div>
            </div>

            <!-- Profile Fields Table -->
            <table style="width: 100%; border-collapse: collapse;">
                <tbody>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 0; width: 140px; font-size: 13px; color: #0099e5; font-weight: 500;">Last login</td>
                        <td style="padding: 12px 0; font-size: 14px; color: #1e293b;">
                            <?php echo !empty($current_student['last_login']) ? date("d/m/Y h:i A", strtotime($current_student['last_login'])) : date("d/m/Y h:i A"); ?>
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 0; font-size: 13px; color: #0099e5; font-weight: 500;">Username</td>
                        <td style="padding: 12px 0; font-size: 14px; color: #1e293b; font-weight: 500;">
                            <?php echo htmlspecialchars($current_student['username'] ?? 'yuvrajyadav734'); ?>
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 0; font-size: 13px; color: #0099e5; font-weight: 500;">Joined</td>
                        <td style="padding: 12px 0; font-size: 14px; color: #1e293b;">
                            <?php echo !empty($current_student['joined_date']) ? date("d/m/Y", strtotime($current_student['joined_date'])) : '18/07/2026'; ?>
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 0; font-size: 13px; color: #0099e5; font-weight: 500;">Contact No.</td>
                        <td style="padding: 12px 0; font-size: 14px; color: #1e293b;">
                            <a href="tel:<?php echo htmlspecialchars($current_student['phone'] ?? '8340195572'); ?>" style="color: inherit; text-decoration: none;">
                                <?php echo htmlspecialchars($current_student['phone'] ?? '8340195572'); ?>
                            </a>
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 0; font-size: 13px; color: #0099e5; font-weight: 500;">Created By</td>
                        <td style="padding: 12px 0; font-size: 14px; color: #1e293b;">
                            <?php echo htmlspecialchars($current_student['created_by'] ?? 'Sumit Yadav'); ?>
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 0; font-size: 13px; color: #0099e5; font-weight: 500;">Email ID</td>
                        <td style="padding: 12px 0; font-size: 14px; color: #1e293b;">
                            <a href="mailto:<?php echo htmlspecialchars($current_student['email'] ?? 'yuvrajyadav734@gmail.com'); ?>" style="color: inherit; text-decoration: none;">
                                <?php echo htmlspecialchars($current_student['email'] ?? 'yuvrajyadav734@gmail.com'); ?>
                            </a>
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 0; font-size: 13px; color: #0099e5; font-weight: 500;">Location</td>
                        <td style="padding: 12px 0; font-size: 14px; color: #1e293b;">
                            <?php echo htmlspecialchars($current_student['location'] ?? 'Not provided'); ?>
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 0; font-size: 13px; color: #0099e5; font-weight: 500;">Room Charges</td>
                        <td style="padding: 12px 0; font-size: 14px; color: #1e293b; font-weight: 600;">
                            As Agreed with Owners Binod Yadav & Sumit Yadav
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 0; font-size: 13px; color: #0099e5; font-weight: 500;">Food Facility</td>
                        <td style="padding: 12px 0; font-size: 13px; color: #64748b;">
                            Pure Accommodation (Food Not Provided)
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 0; font-size: 13px; color: #0099e5; font-weight: 500;">Room (Bed)</td>
                        <td style="padding: 12px 0; font-size: 14px; color: #1e293b; font-weight: 500;">
                            <?php if (!empty($current_student['room_number'])): ?>
                                <?php 
                                    $occ_label = ($current_student['occupancy_type'] ?? 'double') === 'single' ? 'Single' : (($current_student['occupancy_type'] ?? 'double') === 'double' ? '2 - sharing' : '3 - sharing');
                                    echo htmlspecialchars($current_student['room_number']) . ' [' . $occ_label . '] (' . htmlspecialchars($current_student['bed_label'] ?? 'Bed') . ')';
                                ?>
                            <?php else: ?>
                                <span style="color: #d97706; font-weight: 600;"><i class='bx bx-time-five'></i> Under Allocation (Assigned by Owners Binod Yadav & Sumit Yadav)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 0; font-size: 13px; color: #0099e5; font-weight: 500;">Floor</td>
                        <td style="padding: 12px 0; font-size: 14px; color: #1e293b;">
                            <?php echo !empty($current_student['floor']) ? htmlspecialchars($current_student['floor']) : '<span style="color: #64748b; font-style: italic;">Assigned upon room visit</span>'; ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div style="margin-top: 28px; display: flex; gap: 12px;">
                <a href="complaints.php" class="btn-create-action" style="text-decoration: none;">
                    <i class='bx bx-message-dots'></i> Raise Maintenance Complaint
                </a>
                <a href="vacate.php" style="padding: 8px 20px; border-radius: 20px; border: 1px solid #cbd5e1; background: #fff; color: #475569; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                    <i class='bx bx-calendar-x'></i> Vacate Intimation
                </a>
            </div>
        </div>

    </div>
</div>

<?php require_once("footer.php"); ?>

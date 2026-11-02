<?php
$page_title = "Student Residents";
require_once("header.php");
require_once(__DIR__ . "/../Includes/room_assignment.php");

// Handle Warden KYC Action (Verify / Reject)
$action_msg = "";
$action_msg_type = "success";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_kyc_status'])) {
    $target_student_id = (int)$_POST['student_id'];
    $new_status = $_POST['new_status'] === 'Verified' ? 'Verified' : 'Rejected';
    mysqli_query($con, "UPDATE pg_kyc SET status = '$new_status', verified_at = NOW() WHERE student_id = $target_student_id");
    $action_msg = "Student #$target_student_id KYC status updated to '$new_status'.";
}

// Handle Room & Bed Allocation by Owners Binod Yadav & Sumit Yadav (or Warden Desk)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_room_assignment'])) {
    $target_student_id = (int)($_POST['student_id'] ?? 0);
    $room_number = trim($_POST['room_number'] ?? '');
    $bed_label = trim($_POST['bed_label'] ?? '');
    $floor = trim($_POST['floor'] ?? '');
    $occupancy_type = trim($_POST['occupancy_type'] ?? '');
    $rent_amount = trim($_POST['rent_amount'] ?? '');
    $deposit_amount = trim($_POST['deposit_amount'] ?? '');

    if ($target_student_id > 0 && mysqli_begin_transaction($con)) {
        $assignment_error = "";
        $saved = hms_pg_save_room_assignment($con, $target_student_id, $room_number, $bed_label, $floor, $occupancy_type, $rent_amount, $deposit_amount, $assignment_error);
        $committed = $saved && mysqli_commit($con);
        if ($committed) {
            $action_msg = "Room, bed, floor, occupancy, rent, and deposit were saved for student #$target_student_id.";
        } else {
            if ($saved) {
                error_log("PG resident assignment commit failed: " . mysqli_error($con));
            }
            mysqli_rollback($con);
            $action_msg = $assignment_error !== "" ? $assignment_error : "Allocation could not be completed. Confirm the resident and bed are still available.";
            $action_msg_type = "danger";
        }
    } else {
        if ($target_student_id < 1) {
            $action_msg = "Select a valid resident before saving room details.";
        } else {
            error_log("PG resident allocation transaction start failed: " . mysqli_error($con));
            $action_msg = "Allocation could not be started. Please try again.";
        }
        $action_msg_type = "danger";
    }
}

$students_q = mysqli_query($con, "SELECT s.*, r.room_number, r.floor, r.occupancy_type, b.bed_label,
                                  k.father_name, k.father_mobile, k.mother_name, k.mother_mobile,
                                  k.address as kyc_address, k.status as kyc_status, k.profile_pic,
                                  k.address_proof_front, k.address_proof_back, k.id_proof_front, k.id_proof_back,
                                  k.submitted_at as kyc_submitted_at
                                  FROM pg_students s 
                                  LEFT JOIN pg_rooms r ON s.room_id = r.id 
                                  LEFT JOIN pg_beds b ON s.bed_id = b.id 
                                  LEFT JOIN pg_kyc k ON s.id = k.student_id
                                  ORDER BY s.id DESC");
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: #0f172a;">Student Residents Directory</h2>
        <p style="font-size: 13px; color: #64748b; margin-top: 2px;">Enter or edit each PG resident's room number, bed label, floor, occupancy type, rent, and deposit directly in the editable allocation fields.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="dashboard.php" style="background: #0099e5; color: #fff; padding: 9px 18px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
            <i class='bx bx-plus'></i> Admit Student
        </a>
    </div>
</div>

<?php if (!empty($action_msg)): ?>
    <div style="<?php echo $action_msg_type === 'success' ? 'background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;' : 'background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;'; ?> padding: 12px 18px; border-radius: 8px; font-size: 13px; font-weight: 500; margin-bottom: 20px;">
        <i class='bx <?php echo $action_msg_type === 'success' ? 'bx-check-circle' : 'bx-error-circle'; ?>'></i> <?php echo htmlspecialchars($action_msg); ?>
    </div>
<?php endif; ?>

<div class="pg-table-card" style="background:#fff; border-radius:10px; border:1px solid #e2e8f0; overflow-x:auto;">
    <table style="width:100%; border-collapse:collapse; text-align:left; min-width: 900px;">
        <thead>
            <tr style="background:#f8fafc; border-bottom:1.5px solid #e2e8f0;">
                <th style="padding:14px 18px; font-size:12px; color:#64748b;">Resident Student</th>
                <th style="padding:14px 18px; font-size:12px; color:#64748b;">Room & Bed</th>
                <th style="padding:14px 18px; font-size:12px; color:#64748b;">Contact & College</th>
                <th style="padding:14px 18px; font-size:12px; color:#64748b;">Guardian / Father Details</th>
                <th style="padding:14px 18px; font-size:12px; color:#64748b;">Stay Terms & Joined</th>
                <th style="padding:14px 18px; font-size:12px; color:#64748b;">KYC Verification</th>
                <th style="padding:14px 18px; font-size:12px; color:#64748b; text-align:right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $students_list = array();
            while ($s = mysqli_fetch_assoc($students_q)): 
                $students_list[] = $s;
            ?>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:14px 18px;">
                        <?php 
                            $stu_photo = null;
                            if (!empty($s['photo']) && hms_kyc_file_path('pg', $s['photo']) !== false) {
                                $stu_photo = hms_kyc_download_url('pg', $s['photo']);
                            } elseif (!empty($s['profile_pic']) && hms_kyc_file_path('pg', $s['profile_pic']) !== false) {
                                $stu_photo = hms_kyc_download_url('pg', $s['profile_pic']);
                            } elseif (!empty($s['photo']) && file_exists(__DIR__ . "/../assets/" . $s['photo']) && $s['photo'] !== 'yuvraj.png') {
                                $stu_photo = "../assets/" . $s['photo'];
                            }
                        ?>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <?php if (!empty($stu_photo)): ?>
                                <a href="<?php echo htmlspecialchars($stu_photo); ?>" target="_blank" title="Click to view full photo">
                                    <img src="<?php echo htmlspecialchars($stu_photo); ?>" alt="<?php echo htmlspecialchars($s['name']); ?>" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2.5px solid #0284c7; flex-shrink: 0; box-shadow: 0 2px 6px rgba(2,132,199,0.25);">
                                </a>
                            <?php else: ?>
                                <div style="width: 44px; height: 44px; border-radius: 50%; background: #0ea5e9; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; flex-shrink: 0;">
                                    <?php echo strtoupper(substr($s['name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <strong style="font-size:14px; color:#0f172a; display:block;"><?php echo htmlspecialchars($s['name']); ?></strong>
                                <span style="font-size:11px; color:#64748b;"><?php echo htmlspecialchars($s['email']); ?></span>
                            </div>
                        </div>
                    </td>
                    <td style="padding:14px 18px;">
                        <?php if (!empty($s['room_number'])): ?>
                            <span style="font-weight:700; color:#0284c7; font-size: 14px;"><?php echo htmlspecialchars($s['room_number']); ?></span>
                            <span style="color:#0f172a; font-size:12px; font-weight: 600;">(<?php echo htmlspecialchars($s['bed_label'] ?? 'Bed'); ?>)</span>
                            <div style="font-size:11px; color:#64748b;"><?php echo ucfirst($s['occupancy_type'] ?? 'double'); ?> Sharing &bull; <?php echo htmlspecialchars($s['floor'] ?? ''); ?></div>
                        <?php else: ?>
                            <span style="background: #fef3c7; color: #b45309; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                <i class='bx bx-time-five'></i> Pending Allocation
                            </span>
                        <?php endif; ?>
                        <details style="margin-top: 8px;" <?php echo empty($s['room_number']) ? 'open' : ''; ?>>
                            <summary style="font-size:11px; color:#0369a1; font-weight:700; cursor:pointer;">
                                <?php echo empty($s['room_number']) ? 'Enter room assignment' : 'Edit room &amp; stay details'; ?>
                            </summary>
                            <form method="POST" action="students.php" style="margin-top: 7px; display: grid; gap: 6px; min-width: 180px;">
                                <input type="hidden" name="save_room_assignment" value="1">
                                <input type="hidden" name="student_id" value="<?php echo (int)$s['id']; ?>">
                                <label style="font-size:10px; color:#475569;">Room number
                                    <input type="text" name="room_number" maxlength="50" value="<?php echo htmlspecialchars($s['room_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Type the actual room number" required style="display:block; width:100%; margin-top:2px; padding:5px; font-size:11px; border:1px solid #cbd5e1; border-radius:4px;">
                                </label>
                                <label style="font-size:10px; color:#475569;">Bed label
                                    <input type="text" name="bed_label" maxlength="10" value="<?php echo htmlspecialchars($s['bed_label'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. Bed A" required style="display:block; width:100%; margin-top:2px; padding:5px; font-size:11px; border:1px solid #cbd5e1; border-radius:4px;">
                                </label>
                                <label style="font-size:10px; color:#475569;">Floor
                                    <input type="text" name="floor" maxlength="50" value="<?php echo htmlspecialchars($s['floor'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. First Floor" required style="display:block; width:100%; margin-top:2px; padding:5px; font-size:11px; border:1px solid #cbd5e1; border-radius:4px;">
                                </label>
                                <label style="font-size:10px; color:#475569;">Occupancy (single/double/triple)
                                    <input type="text" name="occupancy_type" value="<?php echo htmlspecialchars($s['occupancy_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="single, double, or triple" required style="display:block; width:100%; margin-top:2px; padding:5px; font-size:11px; border:1px solid #cbd5e1; border-radius:4px;">
                                </label>
                                <label style="font-size:10px; color:#475569;">Agreed monthly rent (₹)
                                    <input type="number" name="rent_amount" min="0.01" step="0.01" value="<?php echo (float)$s['rent_amount'] > 0 ? htmlspecialchars((string)$s['rent_amount'], ENT_QUOTES, 'UTF-8') : ''; ?>" required style="display:block; width:100%; margin-top:2px; padding:5px; font-size:11px; border:1px solid #cbd5e1; border-radius:4px;">
                                </label>
                                <label style="font-size:10px; color:#475569;">Agreed security deposit (₹)
                                    <input type="number" name="deposit_amount" min="0" step="0.01" value="<?php echo htmlspecialchars((string)$s['deposit_amount'], ENT_QUOTES, 'UTF-8'); ?>" required style="display:block; width:100%; margin-top:2px; padding:5px; font-size:11px; border:1px solid #cbd5e1; border-radius:4px;">
                                </label>
                                <button type="submit" style="background:#0284c7; color:#fff; border:none; padding:7px 9px; border-radius:4px; font-size:10px; font-weight:600; cursor:pointer;">
                                    Save room &amp; terms
                                </button>
                            </form>
                        </details>
                    </td>
                    <td style="padding:14px 18px;">
                        <div style="font-size:13px; font-weight:600; color:#334155;"><i class='bx bx-phone' style="color:#0099e5;"></i> <?php echo htmlspecialchars($s['phone']); ?></div>
                        <div style="font-size:11.5px; color:#64748b;"><?php echo htmlspecialchars($s['college']); ?></div>
                        <div style="font-size:11px; color:#94a3b8;"><i class='bx bx-map-pin'></i> <?php echo htmlspecialchars($s['location']); ?></div>
                    </td>
                    <td style="padding:14px 18px;">
                        <?php if (!empty($s['father_name'])): ?>
                            <div style="font-size:13px; font-weight:600; color:#1e293b;"><?php echo htmlspecialchars($s['father_name']); ?></div>
                            <div style="font-size:11px; color:#64748b;"><i class='bx bx-phone'></i> <?php echo htmlspecialchars($s['father_mobile']); ?></div>
                        <?php else: ?>
                            <span style="font-size: 12px; color: #94a3b8; font-style: italic;">Not submitted yet</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:14px 18px;">
                        <div style="font-weight:700; color:#1e293b; font-size: 12.5px;">
                            Rent: <?php echo (float)$s['rent_amount'] > 0 ? '₹' . number_format((float)$s['rent_amount'], 2) . '/month' : 'Not set'; ?>
                        </div>
                        <div style="font-size: 11px; color: #64748b;">Deposit: <?php echo (float)$s['deposit_amount'] > 0 ? '₹' . number_format((float)$s['deposit_amount'], 2) : 'Not set'; ?></div>
                        <div style="font-size: 11px; color: #64748b;">Joined: <?php echo date("d M Y", strtotime($s['joined_date'])); ?></div>
                    </td>
                    <td style="padding:14px 18px;">
                        <?php 
                            $k_status = $s['kyc_status'] ?? 'Pending';
                            $k_color = ($k_status === 'Verified') ? 'background:#dcfce7; color:#15803d;' : (($k_status === 'Pending') ? 'background:#fef3c7; color:#b45309;' : 'background:#fee2e2; color:#b91c1c;');
                        ?>
                        <span style="<?php echo $k_color; ?> padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; display: inline-flex; align-items: center; gap: 4px;">
                            <i class='bx <?php echo ($k_status === 'Verified') ? 'bx-check-shield' : 'bx-time-five'; ?>'></i>
                            <?php echo strtoupper($k_status); ?>
                        </span>
                    </td>
                    <td style="padding:14px 18px; text-align:right;">
                        <button type="button" onclick="viewKycModal(<?php echo $s['id']; ?>)" style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                            <i class='bx bx-id-card'></i> View KYC Details
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal: View Full KYC Details -->
<div id="kycDetailModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.65); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: #ffffff; width: 92%; max-width: 680px; border-radius: 14px; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.25); max-height: 90vh; display: flex; flex-direction: column;">
        <div style="padding: 18px 24px; background: #0099e5; color: #fff; display: flex; justify-content: space-between; align-items: center;">
            <h4 style="margin: 0; font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <i class='bx bx-id-card'></i> Student KYC & Personal Verification
            </h4>
            <button type="button" onclick="closeKycModal()" style="background: none; border: none; color: #fff; font-size: 24px; cursor: pointer;">&times;</button>
        </div>

        <div style="padding: 24px; overflow-y: auto; flex: 1;" id="kycModalBody">
            <!-- Injected via JavaScript -->
        </div>
    </div>
</div>

<script>
    const studentsData = <?php echo json_encode($students_list); ?>;

    function viewKycModal(studentId) {
        const student = studentsData.find(s => s.id == studentId);
        if (!student) return;

        const kycFileUrl = filename => `../download_kyc.php?scope=pg&file=${encodeURIComponent(filename)}`;
        const photoSrc = (student.photo && student.photo !== 'default_student.png' && student.photo !== 'yuvraj.png') ? kycFileUrl(student.photo) : ((student.profile_pic) ? kycFileUrl(student.profile_pic) : '');
        const avatarHtml = photoSrc ? `<a href="${photoSrc}" target="_blank" rel="noopener" title="View Full Profile Photo"><img src="${photoSrc}" style="width: 58px; height: 58px; border-radius: 50%; object-fit: cover; border: 2.5px solid #0284c7; box-shadow: 0 2px 8px rgba(0,0,0,0.15);"></a>` : `<div style="width: 58px; height: 58px; border-radius: 50%; background: #0284c7; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 22px;">${escapeHtml(student.name.substring(0, 1).toUpperCase())}</div>`;

        const body = document.getElementById('kycModalBody');
        body.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    ${avatarHtml}
                    <div>
                        <h3 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0;">${escapeHtml(student.name)}</h3>
                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Assigned Bed: <b>${escapeHtml(student.room_number || 'Under Allocation')} (${escapeHtml(student.bed_label || 'Bed')})</b> &bull; ${escapeHtml(student.floor || 'Yadav Living PG')}</div>
                    </div>
                </div>
                <span style="background: ${student.kyc_status === 'Verified' ? '#dcfce7; color: #15803d;' : '#fef3c7; color: #b45309;'}; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;">
                    ${(student.kyc_status || 'Pending').toUpperCase()}
                </span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">Father / Guardian</div>
                    <div style="font-size: 14px; font-weight: 600; color: #1e293b; margin-top: 4px;">${escapeHtml(student.father_name || 'Not provided')}</div>
                    <div style="font-size: 12px; color: #0284c7; margin-top: 2px;"><i class='bx bx-phone'></i> ${escapeHtml(student.father_mobile || 'N/A')}</div>
                </div>

                <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">Mother Details</div>
                    <div style="font-size: 14px; font-weight: 600; color: #1e293b; margin-top: 4px;">${escapeHtml(student.mother_name || 'Not provided')}</div>
                    <div style="font-size: 12px; color: #0284c7; margin-top: 2px;"><i class='bx bx-phone'></i> ${escapeHtml(student.mother_mobile || 'N/A')}</div>
                </div>

                <div style="grid-column: 1 / -1; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">Permanent Address</div>
                    <div style="font-size: 13.5px; color: #1e293b; margin-top: 4px;">${escapeHtml(student.kyc_address || student.location || 'Deoghar, Jharkhand')}</div>
                </div>
                <div style="grid-column: 1 / -1; background: #f0f9ff; padding: 12px; border-radius: 8px; border: 1px solid #bae6fd;">
                    <div style="font-size: 11px; color: #0369a1; font-weight: 700; text-transform: uppercase;">PG stay terms</div>
                    <div style="font-size: 13px; color: #1e293b; margin-top: 5px;">
                        Monthly rent: <b>${Number(student.rent_amount || 0) > 0 ? `₹${Number(student.rent_amount).toLocaleString('en-IN')}` : 'Not set'}</b>
                        &nbsp; · &nbsp; Security deposit: <b>${Number(student.deposit_amount || 0) > 0 ? `₹${Number(student.deposit_amount).toLocaleString('en-IN')}` : 'Not set'}</b>
                    </div>
                </div>
            </div>

            <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 10px;">Submitted Identity Documents:</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 24px;">
                <div style="padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; background: #ffffff;">
                    <div style="font-size: 11px; color: #64748b;">Address Proof Front:</div>
                    <div style="font-weight: 600; font-size: 13px; color: #0284c7; margin-top: 2px;">
                        ${student.address_proof_front ? `<a href="${kycFileUrl(student.address_proof_front)}" target="_blank" rel="noopener" style="color: #0284c7; text-decoration: underline;"><i class='bx bx-file'></i> ${escapeHtml(student.address_proof_front)}</a>` : '<span style="color:#94a3b8; font-weight: normal;">Not uploaded</span>'}
                    </div>
                </div>

                <div style="padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; background: #ffffff;">
                    <div style="font-size: 11px; color: #64748b;">ID Proof Front:</div>
                    <div style="font-weight: 600; font-size: 13px; color: #0284c7; margin-top: 2px;">
                        ${student.id_proof_front ? `<a href="${kycFileUrl(student.id_proof_front)}" target="_blank" rel="noopener" style="color: #0284c7; text-decoration: underline;"><i class='bx bx-id-card'></i> ${escapeHtml(student.id_proof_front)}</a>` : '<span style="color:#94a3b8; font-weight: normal;">Not uploaded</span>'}
                    </div>
                </div>

                <div style="padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; background: #ffffff;">
                    <div style="font-size: 11px; color: #64748b;">ID Proof Back:</div>
                    <div style="font-weight: 600; font-size: 13px; color: #0284c7; margin-top: 2px;">
                        ${student.id_proof_back ? `<a href="${kycFileUrl(student.id_proof_back)}" target="_blank" rel="noopener" style="color: #0284c7; text-decoration: underline;"><i class='bx bx-id-card'></i> ${escapeHtml(student.id_proof_back)}</a>` : '<span style="color:#94a3b8; font-weight: normal;">Not uploaded</span>'}
                    </div>
                </div>

                <div style="padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; background: #ffffff;">
                    <div style="font-size: 11px; color: #64748b;">Address Proof Back:</div>
                    <div style="font-weight: 600; font-size: 13px; color: #0284c7; margin-top: 2px;">
                        ${student.address_proof_back ? `<a href="${kycFileUrl(student.address_proof_back)}" target="_blank" rel="noopener" style="color: #0284c7; text-decoration: underline;"><i class='bx bx-file'></i> ${escapeHtml(student.address_proof_back)}</a>` : '<span style="color:#94a3b8; font-weight: normal;">Not uploaded</span>'}
                    </div>
                </div>
            </div>

            <!-- Warden Approval Form -->
            <form method="POST" action="students.php" style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #f1f5f9; padding-top: 16px;">
                <input type="hidden" name="update_kyc_status" value="1">
                <input type="hidden" name="student_id" value="${student.id}">
                <button type="submit" name="new_status" value="Rejected" style="background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; padding: 8px 16px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;">
                    Reject KYC
                </button>
                <button type="submit" name="new_status" value="Verified" style="background: #0099e5; color: #ffffff; border: none; padding: 8px 18px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                    <i class='bx bx-check-shield'></i> Approve & Verify KYC
                </button>
            </form>
        `;

        document.getElementById('kycDetailModal').style.display = 'flex';
    }

    function closeKycModal() {
        document.getElementById('kycDetailModal').style.display = 'none';
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
</script>

<?php require_once("footer.php"); ?>

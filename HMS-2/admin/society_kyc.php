<?php
require_once("../Includes/config.php");
require_once("../Includes/session.php");
require_role('admin', '../login.php');

// Handle KYC verification actions
$action_msg = "";
$form_error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_society_resident'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $flat_no = trim($_POST['flat_no'] ?? '');
    $resident_type = $_POST['resident_type'] ?? '';
    $rent_input = trim($_POST['monthly_rent'] ?? '');
    $monthly_rent = $rent_input === '' ? null : (float)$rent_input;
    $rent_is_valid = $rent_input === '' || (is_numeric($rent_input) && $monthly_rent >= 0);
    $address = trim($_POST['permanent_address'] ?? '');
    $father_or_spouse = trim($_POST['father_or_spouse'] ?? '');
    $allowed_resident_types = array('Owner', 'Tenant', 'Family Member');

    if ($full_name === '' || !preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 50 || $password === '' ||
        strlen($password) < 10 || !preg_match('/^\+?[0-9\s()-]{7,20}$/', $phone) || $flat_no === '' || !in_array($resident_type, $allowed_resident_types, true) ||
        !$rent_is_valid ||
        $address === '' || ($resident_type === 'Tenant' && ($monthly_rent === null || $monthly_rent <= 0)) ||
        ($monthly_rent !== null && $monthly_rent < 0)) {
        $form_error = "Complete all required details. Use a valid email, a password of at least 10 characters, and enter a positive rent for tenants.";
    } elseif (!mysqli_begin_transaction($con)) {
        error_log("Society resident transaction start failed: " . mysqli_error($con));
        $form_error = "Unable to start resident registration. Please try again.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $member_stmt = mysqli_prepare($con, "INSERT INTO member (username, email, password, address, phone) VALUES (?, ?, ?, ?, ?)");
        if (!$member_stmt) {
            error_log("Society resident account prepare failed: " . mysqli_error($con));
            mysqli_rollback($con);
            $form_error = "Unable to prepare resident registration. Please try again.";
        } else {
            mysqli_stmt_bind_param($member_stmt, "sssss", $username, $email, $hashed_password, $address, $phone);
            if (!mysqli_stmt_execute($member_stmt)) {
                error_log("Society resident account insert failed: " . mysqli_stmt_error($member_stmt));
                mysqli_stmt_close($member_stmt);
                mysqli_rollback($con);
                $form_error = "Resident account was not created. Check that the username and email are not already registered.";
            } else {
                $new_member_id = mysqli_insert_id($con);
                mysqli_stmt_close($member_stmt);
                $kyc_stmt = mysqli_prepare($con, "INSERT INTO society_kyc (member_id, full_name, flat_no, resident_type, father_or_spouse, emergency_phone, permanent_address, monthly_rent, terms_accepted, status, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 'Pending', NOW())");
                if (!$kyc_stmt) {
                    error_log("Society resident KYC prepare failed: " . mysqli_error($con));
                    mysqli_rollback($con);
                    $form_error = "Resident account was not completed with KYC details. Please try again.";
                } else {
                    mysqli_stmt_bind_param($kyc_stmt, "issssssd", $new_member_id, $full_name, $flat_no, $resident_type, $father_or_spouse, $phone, $address, $monthly_rent);
                    if (!mysqli_stmt_execute($kyc_stmt)) {
                        error_log("Society resident KYC insert failed: " . mysqli_stmt_error($kyc_stmt));
                        mysqli_stmt_close($kyc_stmt);
                        mysqli_rollback($con);
                        $form_error = "Resident account was not completed with KYC details. Please try again.";
                    } else {
                        mysqli_stmt_close($kyc_stmt);
                        if (!mysqli_commit($con)) {
                            error_log("Society resident transaction commit failed: " . mysqli_error($con));
                            mysqli_rollback($con);
                            $form_error = "Resident registration could not be saved. Please try again.";
                        } else {
                            $action_msg = "Resident account and flat details created. The resident can sign in and complete identity-document uploads.";
                        }
                    }
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_kyc_status'])) {
    $kyc_id = (int)$_POST['kyc_id'];
    $new_status = in_array($_POST['status'], array('Verified', 'Rejected', 'Pending')) ? $_POST['status'] : 'Pending';
    $remarks = trim($_POST['remarks'] ?? '');
    $admin_user = $_SESSION['username'] ?? 'Admin Yuvraj';

    $upd = mysqli_prepare($con, "UPDATE society_kyc SET status = ?, reviewed_by = ?, reviewed_at = NOW(), remarks = ? WHERE id = ?");
    if ($upd) {
        mysqli_stmt_bind_param($upd, "sssi", $new_status, $admin_user, $remarks, $kyc_id);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
        $action_msg = "KYC status successfully updated to " . strtoupper($new_status) . "!";
    }
}

// Fetch all society KYCs
$kycs_query = mysqli_query($con, "
    SELECT sk.*, m.username AS member_username, m.email AS member_email, m.phone AS member_phone 
    FROM society_kyc sk 
    JOIN member m ON sk.member_id = m.id 
    ORDER BY sk.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Society Warden Desk | Resident KYC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
        body { background: #f3f6f5; color: #142d3e; min-height: 100vh; padding: clamp(14px, 3vw, 32px); }
        .page-wrap { max-width: 1300px; margin: 0 auto; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .btn-back { display: inline-flex; align-items: center; gap: 6px; text-decoration: none; color: #0284c7; font-weight: 600; font-size: 14px; }
        .btn-back:hover { text-decoration: underline; }
        .card-main { background: #ffffff; border-radius: 18px; border: 1px solid #dfe8e4; box-shadow: 0 16px 40px rgba(20,45,62,0.08); overflow: hidden; }
        .card-header { padding: 24px 30px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; }
        .card-header h2 { font-size: 20px; font-weight: 700; color: #0f172a; }
        .card-header p { font-size: 13px; color: #64748b; margin-top: 2px; }
        table { width: 100%; min-width: 900px; border-collapse: collapse; text-align: left; }
        th { background: #f8fafc; padding: 14px 18px; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; }
        td { padding: 16px 18px; border-bottom: 1px solid #f1f5f9; font-size: 13px; vertical-align: middle; }
        tr:hover td { background: #fbfdff; }
        .status-pill { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; }
        .status-verified { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .status-pending { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .status-rejected { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
        .btn-action { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s; }
        .btn-verify { background: #10b981; color: #fff; }
        .btn-verify:hover { background: #059669; }
        .btn-reject { background: #ef4444; color: #fff; }
        .btn-reject:hover { background: #dc2626; }
        .doc-chip { display: inline-flex; align-items: center; gap: 4px; background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-decoration: none; margin: 2px; }
        .doc-chip:hover { background: #bae6fd; }
        .avatar-thumb { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; }
        .avatar-placeholder { width: 42px; height: 42px; border-radius: 50%; background: #0284c7; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; }
        .resident-form { padding: 22px 26px; border: 1px solid #dfe8e4; border-radius: 16px; background: #fff; margin-bottom: 20px; box-shadow: 0 10px 24px rgba(20,45,62,0.05); }
        .resident-form h2 { font-size: 18px; color: #142d3e; margin-bottom: 4px; }
        .resident-form p { color: #64748b; font-size: 13px; margin-bottom: 18px; }
        .resident-form-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
        .resident-form label { display: block; color: #334155; font-size: 12px; font-weight: 650; margin-bottom: 6px; }
        .resident-form input, .resident-form select, .resident-form textarea { width: 100%; padding: 10px 12px; border: 1px solid #d7e1dc; border-radius: 9px; font: inherit; font-size: 13px; }
        .resident-form .full-width { grid-column: 1 / -1; }
        .resident-form button { border: 0; border-radius: 9px; background: #286d70; color: white; padding: 11px 18px; font-size: 13px; font-weight: 700; cursor: pointer; }
        @media (max-width: 640px) {
            .top-bar { align-items: flex-start; flex-direction: column; gap: 10px; }
            .card-header { padding: 20px; }
            .card-header h2 { font-size: 18px; }
            .resident-form { padding: 18px; }
            .resident-form-grid { grid-template-columns: 1fr; }
            .resident-form .full-width { grid-column: auto; }
        }
    </style>
</head>
<body>

<div class="page-wrap">
    <div class="top-bar">
        <a href="Dashboard/admin.php" class="btn-back">
            <i class='bx bx-left-arrow-alt'></i> Back to Admin Dashboard
        </a>
        <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap; justify-content:flex-end;">
            <a href="../pg/admin/dashboard.php" class="btn-back" style="color:#059669;">
                <i class='bx bxs-institution'></i> Open separate PG Warden Desk
            </a>
            <div style="font-size: 13px; color: #64748b;">
                Yadav Living Society &bull; Warden Desk
            </div>
        </div>
    </div>

    <?php if (!empty($action_msg)): ?>
        <div style="background: #dcfce7; border: 1px solid #bbf7d0; color: #15803d; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 13.5px; font-weight: 500; display: flex; align-items: center; gap: 8px;">
            <i class='bx bx-check-circle' style="font-size: 18px;"></i> <?php echo htmlspecialchars($action_msg); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($form_error)): ?>
        <div role="alert" style="background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 13.5px; font-weight: 500;">
            <?php echo htmlspecialchars($form_error); ?>
        </div>
    <?php endif; ?>

    <form class="resident-form" method="POST" action="society_kyc.php">
        <h2><i class='bx bx-home-alt-2' aria-hidden="true"></i> Society Warden Desk &mdash; Add a Flat Resident</h2>
        <p>Type the booked flat number directly; Society residents do not use PG room, bed, floor, or sharing fields. The resident can then sign in to upload identity and ownership/lease documents.</p>
        <div class="resident-form-grid">
            <div>
                <label for="residentFullName">Resident full name *</label>
                <input id="residentFullName" name="full_name" type="text" maxlength="100" required>
            </div>
            <div>
                <label for="residentUsername">Login username *</label>
                <input id="residentUsername" name="username" type="text" maxlength="50" autocomplete="username" pattern="[A-Za-z0-9._-]+" title="Use letters, numbers, dots, underscores or hyphens." required>
            </div>
            <div>
                <label for="residentEmail">Email *</label>
                <input id="residentEmail" name="email" type="email" maxlength="50" autocomplete="email" required>
            </div>
            <div>
                <label for="residentPassword">Initial password * (10+ characters)</label>
                <input id="residentPassword" name="password" type="password" minlength="10" autocomplete="new-password" required>
            </div>
            <div>
                <label for="residentPhone">Contact mobile *</label>
                <input id="residentPhone" name="phone" type="tel" maxlength="20" pattern="\+?[0-9\s()-]{7,20}" title="Enter a valid phone number." required>
            </div>
            <div>
                <label for="residentFlat">Booked flat / unit number *</label>
                <input id="residentFlat" name="flat_no" type="text" maxlength="50" placeholder="e.g. A-101" required>
            </div>
            <div>
                <label for="residentType">Residency type *</label>
                <select id="newResidentType" name="resident_type" required>
                    <option value="Owner">Flat owner</option>
                    <option value="Tenant">Tenant</option>
                    <option value="Family Member">Owner's family member</option>
                </select>
            </div>
            <div>
                <label for="monthlyRent">Monthly rent (₹, for tenants)</label>
                <input id="newResidentRent" name="monthly_rent" type="number" min="0" step="0.01" placeholder="Leave blank if not applicable">
            </div>
            <div>
                <label for="residentRelation">Father / spouse name</label>
                <input id="residentRelation" name="father_or_spouse" type="text" maxlength="100">
            </div>
            <div class="full-width">
                <label for="residentAddress">Permanent address *</label>
                <textarea id="residentAddress" name="permanent_address" rows="2" required></textarea>
            </div>
            <div class="full-width">
                <button type="submit" name="create_society_resident" value="1">Create resident and flat record</button>
            </div>
        </div>
    </form>

    <div class="card-main">
        <div class="card-header">
            <div>
                <h2>Society Resident KYC Submissions</h2>
                <p>Verify flat ownership documents, Aadhaar cards, and resident identity records</p>
            </div>
        </div>

        <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
            <table>
                <thead>
                    <tr>
                        <th>Resident / Photo</th>
                        <th>Flat, Residency &amp; Rent</th>
                        <th>Contact &amp; Guardian</th>
                        <th>Address</th>
                        <th>Uploaded Documents</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($kycs_query) == 0): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">
                                <i class='bx bx-id-card' style="font-size: 36px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                                No resident KYC submissions on file yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($r = mysqli_fetch_assoc($kycs_query)): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <?php if (!empty($r['profile_pic']) && hms_kyc_file_path('society', $r['profile_pic']) !== false): ?>
                                            <a href="<?php echo htmlspecialchars(hms_kyc_download_url('society', $r['profile_pic']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                                <img src="<?php echo htmlspecialchars(hms_kyc_download_url('society', $r['profile_pic']), ENT_QUOTES, 'UTF-8'); ?>" alt="Photo" class="avatar-thumb">
                                            </a>
                                        <?php else: ?>
                                            <div class="avatar-placeholder">
                                                <?php echo strtoupper(substr($r['full_name'] ?: $r['member_username'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <strong style="color: #0f172a; font-size: 14px; display: block;"><?php echo htmlspecialchars($r['full_name'] ?: $r['member_username']); ?></strong>
                                            <span style="font-size: 11.5px; color: #64748b;"><?php echo htmlspecialchars($r['member_email']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: #0284c7;"><?php echo htmlspecialchars($r['flat_no'] ?: 'Not Specified'); ?></div>
                                    <span style="font-size: 11px; background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-weight: 600;">
                                        <?php echo htmlspecialchars($r['resident_type'] ?: 'Owner'); ?>
                                    </span>
                                    <div style="font-size: 11px; color: #475569; margin-top: 5px;">
                                        <?php if ($r['monthly_rent'] !== null && (float)$r['monthly_rent'] > 0): ?>
                                            Rent: ₹<?php echo number_format((float)$r['monthly_rent'], 2); ?>/month
                                        <?php else: ?>
                                            No rent recorded
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 12.5px; font-weight: 600; color: #334155;"><i class='bx bx-phone' style="color: #0284c7;"></i> <?php echo htmlspecialchars($r['emergency_phone'] ?: $r['member_phone']); ?></div>
                                    <?php if (!empty($r['father_or_spouse'])): ?>
                                        <div style="font-size: 11px; color: #64748b;">Guardian/Spouse: <?php echo htmlspecialchars($r['father_or_spouse']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-size: 11.5px; color: #475569; max-width: 220px; line-height: 1.4;">
                                        <?php echo htmlspecialchars($r['permanent_address']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                        <?php if (!empty($r['id_proof_front'])): ?>
                                            <a href="<?php echo htmlspecialchars(hms_kyc_download_url('society', $r['id_proof_front']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="doc-chip">
                                                <i class='bx bx-id-card'></i> ID Front
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($r['id_proof_back'])): ?>
                                            <a href="<?php echo htmlspecialchars(hms_kyc_download_url('society', $r['id_proof_back']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="doc-chip">
                                                <i class='bx bx-id-card'></i> ID Back
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($r['address_proof_front'])): ?>
                                            <a href="<?php echo htmlspecialchars(hms_kyc_download_url('society', $r['address_proof_front']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="doc-chip">
                                                <i class='bx bx-file'></i> Ownership/Lease Front
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($r['address_proof_back'])): ?>
                                            <a href="<?php echo htmlspecialchars(hms_kyc_download_url('society', $r['address_proof_back']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="doc-chip">
                                                <i class='bx bx-file'></i> Ownership/Lease Back
                                            </a>
                                        <?php endif; ?>
                                        <?php if (empty($r['id_proof_front']) && empty($r['address_proof_front'])): ?>
                                            <span style="font-size: 11px; color: #94a3b8; font-style: italic;">No docs uploaded</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($r['status'] === 'Verified'): ?>
                                        <span class="status-pill status-verified"><i class='bx bx-check-shield'></i> Verified</span>
                                    <?php elseif ($r['status'] === 'Rejected'): ?>
                                        <span class="status-pill status-rejected"><i class='bx bx-x-circle'></i> Rejected</span>
                                    <?php else: ?>
                                        <span class="status-pill status-pending"><i class='bx bx-time-five'></i> Pending</span>
                                    <?php endif; ?>
                                    <?php if (!empty($r['reviewed_by'])): ?>
                                        <div style="font-size: 10px; color: #94a3b8; margin-top: 4px;">By: <?php echo htmlspecialchars($r['reviewed_by']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <form method="POST" action="society_kyc.php" style="display: inline;">
                                            <input type="hidden" name="update_kyc_status" value="1">
                                            <input type="hidden" name="kyc_id" value="<?php echo $r['id']; ?>">
                                            <input type="hidden" name="status" value="Verified">
                                            <button type="submit" class="btn-action btn-verify" title="Verify KYC">
                                                <i class='bx bx-check'></i> Verify
                                            </button>
                                        </form>
                                        <form method="POST" action="society_kyc.php" style="display: inline;">
                                            <input type="hidden" name="update_kyc_status" value="1">
                                            <input type="hidden" name="kyc_id" value="<?php echo $r['id']; ?>">
                                            <input type="hidden" name="status" value="Rejected">
                                            <button type="submit" class="btn-action btn-reject" title="Reject KYC" onclick="return confirm('Are you sure you want to reject this KYC submission?');">
                                                <i class='bx bx-x'></i> Reject
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const newResidentType = document.getElementById('newResidentType');
    const newResidentRent = document.getElementById('newResidentRent');
    const syncNewResidentRent = () => {
        const isTenant = newResidentType.value === 'Tenant';
        newResidentRent.required = isTenant;
        newResidentRent.min = isTenant ? '0.01' : '0';
    };
    newResidentType.addEventListener('change', syncNewResidentRent);
    syncNewResidentRent();
</script>
</body>
</html>

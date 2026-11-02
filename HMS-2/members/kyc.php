<?php
require_once("../Includes/config.php");
require_once("../Includes/session.php");
require_role('member', '../login.php');

$member_id = hms_current_member_id();
$username = $_SESSION['username'] ?? '';
$email = $_SESSION['email'] ?? '';

// Helper function to handle file upload
function handle_society_upload($file_key, $prefix, $default = null) {
    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
        $upload_dir = hms_kyc_upload_directory('society');
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0750, true);
        }
        $ext = strtolower(pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION));
        $allowed = array('jpg', 'jpeg', 'png', 'pdf', 'webp');
        if (in_array($ext, $allowed)) {
            $filename = $prefix . "_" . bin2hex(random_bytes(12)) . "." . $ext;
            $target = $upload_dir . $filename;
            if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $target)) {
                return $filename;
            }
        }
    }
    return $default;
}

// Fetch member record
$mem_q = mysqli_query($con, "SELECT * FROM member WHERE id = $member_id LIMIT 1");
$member_row = mysqli_fetch_assoc($mem_q);

// Fetch existing society KYC details
$kyc_q = mysqli_query($con, "SELECT * FROM society_kyc WHERE member_id = $member_id ORDER BY id DESC LIMIT 1");
$kyc = mysqli_fetch_assoc($kyc_q);

$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_society_kyc'])) {
    $full_name = trim(isset($_POST['full_name']) ? $_POST['full_name'] : '');
    $flat_no = trim(isset($_POST['flat_no']) ? $_POST['flat_no'] : '');
    $resident_type = trim(isset($_POST['resident_type']) ? $_POST['resident_type'] : 'Owner');
    $rent_input = trim(isset($_POST['monthly_rent']) ? $_POST['monthly_rent'] : '');
    $monthly_rent = $rent_input === '' ? null : (float)$rent_input;
    $rent_is_valid = $rent_input === '' || (is_numeric($rent_input) && $monthly_rent >= 0);
    $father_or_spouse = trim(isset($_POST['father_or_spouse']) ? $_POST['father_or_spouse'] : '');
    $emergency_phone = trim(isset($_POST['emergency_phone']) ? $_POST['emergency_phone'] : '');
    $permanent_address = trim(isset($_POST['permanent_address']) ? $_POST['permanent_address'] : '');
    $id_proof_type = trim(isset($_POST['id_proof_type']) ? $_POST['id_proof_type'] : 'Aadhaar Card');
    $terms_accepted = isset($_POST['terms_accepted']) ? 1 : 0;

    // Handle File Uploads
    $profile_pic = handle_society_upload('profile_pic_file', 'PIC_' . $member_id, $kyc['profile_pic'] ?? null);
    $id_front = handle_society_upload('id_front_file', 'ID_F_' . $member_id, $kyc['id_proof_front'] ?? null);
    $id_back = handle_society_upload('id_back_file', 'ID_B_' . $member_id, $kyc['id_proof_back'] ?? null);
    $addr_front = handle_society_upload('addr_front_file', 'ADDR_F_' . $member_id, $kyc['address_proof_front'] ?? null);
    $addr_back = handle_society_upload('addr_back_file', 'ADDR_B_' . $member_id, $kyc['address_proof_back'] ?? null);

    if (empty($full_name) || empty($flat_no) || empty($emergency_phone) || empty($permanent_address) ||
        !in_array($resident_type, array('Owner', 'Tenant', 'Family Member'), true) ||
        !$rent_is_valid ||
        ($resident_type === 'Tenant' && ($monthly_rent === null || $monthly_rent <= 0)) ||
        ($monthly_rent !== null && $monthly_rent < 0)) {
        $error_msg = "Please provide valid resident and flat details. Monthly rent is required for tenants.";
    } elseif (!$terms_accepted) {
        $error_msg = "You must accept the society terms and conditions to submit KYC.";
    } else {
        if ($kyc) {
            // Update existing
            $stmt = mysqli_prepare($con, "UPDATE society_kyc SET full_name = ?, flat_no = ?, resident_type = ?, father_or_spouse = ?, emergency_phone = ?, permanent_address = ?, profile_pic = ?, id_proof_type = ?, id_proof_front = ?, id_proof_back = ?, address_proof_front = ?, address_proof_back = ?, monthly_rent = ?, terms_accepted = ?, status = 'Pending', submitted_at = NOW() WHERE member_id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ssssssssssssdii", $full_name, $flat_no, $resident_type, $father_or_spouse, $emergency_phone, $permanent_address, $profile_pic, $id_proof_type, $id_front, $id_back, $addr_front, $addr_back, $monthly_rent, $terms_accepted, $member_id);
                if (!mysqli_stmt_execute($stmt)) {
                    error_log("Society KYC update failed: " . mysqli_stmt_error($stmt));
                    $error_msg = "Your resident details could not be saved. Please try again.";
                }
                mysqli_stmt_close($stmt);
            } else {
                error_log("Society KYC update prepare failed: " . mysqli_error($con));
                $error_msg = "Your resident details could not be saved. Please try again.";
            }
        } else {
            // Insert new
            $stmt = mysqli_prepare($con, "INSERT INTO society_kyc (member_id, full_name, flat_no, resident_type, father_or_spouse, emergency_phone, permanent_address, profile_pic, id_proof_type, id_proof_front, id_proof_back, address_proof_front, address_proof_back, monthly_rent, terms_accepted, status, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "issssssssssssdi", $member_id, $full_name, $flat_no, $resident_type, $father_or_spouse, $emergency_phone, $permanent_address, $profile_pic, $id_proof_type, $id_front, $id_back, $addr_front, $addr_back, $monthly_rent, $terms_accepted);
                if (!mysqli_stmt_execute($stmt)) {
                    error_log("Society KYC insert failed: " . mysqli_stmt_error($stmt));
                    $error_msg = "Your resident details could not be saved. Please try again.";
                }
                mysqli_stmt_close($stmt);
            } else {
                error_log("Society KYC insert prepare failed: " . mysqli_error($con));
                $error_msg = "Your resident details could not be saved. Please try again.";
            }
        }

        // Sync address to member table only after the KYC record was saved.
        if ($error_msg === "" && !empty($permanent_address)) {
            $up_m = mysqli_prepare($con, "UPDATE member SET address = ? WHERE id = ?");
            if ($up_m) {
                mysqli_stmt_bind_param($up_m, "si", $permanent_address, $member_id);
                mysqli_stmt_execute($up_m);
                mysqli_stmt_close($up_m);
            }
        }

        if ($error_msg === "") {
            $success_msg = "Society Resident KYC submitted successfully and is now under review.";
        }

        // Refresh
        $kyc_q = mysqli_query($con, "SELECT * FROM society_kyc WHERE member_id = $member_id ORDER BY id DESC LIMIT 1");
        $kyc = mysqli_fetch_assoc($kyc_q);
    }
}

$val_full_name = $kyc['full_name'] ?? ($member_row['username'] ?? '');
$val_flat_no = $kyc['flat_no'] ?? '';
$val_res_type = $kyc['resident_type'] ?? 'Owner';
$val_monthly_rent = $kyc['monthly_rent'] ?? '';
$val_father_spouse = $kyc['father_or_spouse'] ?? '';
$val_emerg_phone = $kyc['emergency_phone'] ?? ($member_row['phone'] ?? '');
$val_address = $kyc['permanent_address'] ?? ($member_row['address'] ?? '');
$val_id_type = $kyc['id_proof_type'] ?? 'Aadhaar Card';
$val_pic = $kyc['profile_pic'] ?? '';
$val_id_f = $kyc['id_proof_front'] ?? '';
$val_id_b = $kyc['id_proof_back'] ?? '';
$val_addr_f = $kyc['address_proof_front'] ?? '';
$val_addr_b = $kyc['address_proof_back'] ?? '';
$val_status = $kyc['status'] ?? 'Not Submitted';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resident KYC Verification | Yadav Living</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
        body { background: #f8fafc; color: #1e293b; min-height: 100vh; padding: 24px; }
        .kyc-wrap { max-width: 1000px; margin: 0 auto; }
        .top-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .btn-back { display: inline-flex; align-items: center; gap: 6px; text-decoration: none; color: #0284c7; font-weight: 600; font-size: 14px; }
        .btn-back:hover { text-decoration: underline; }
        .badge-status { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; }
        .badge-verified { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-pending { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .badge-unsub { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .kyc-card { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px rgba(0,0,0,0.04); padding: 36px; }
        .kyc-header { border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 24px; }
        .kyc-header h2 { font-size: 22px; font-weight: 700; color: #0f172a; }
        .kyc-header p { font-size: 13.5px; color: #64748b; margin-top: 4px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 12.5px; font-weight: 600; color: #334155; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group label .req { color: #ef4444; }
        .form-control-kyc { width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; outline: none; background: #ffffff; transition: all 0.2s; }
        .form-control-kyc:focus { border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12); }
        .select-file-box { background: #f1f5f9; border: 1.5px dashed #cbd5e1; border-radius: 8px; padding: 14px; text-align: center; color: #475569; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .select-file-box:hover { background: #e2e8f0; color: #0f172a; border-color: #94a3b8; }
        .file-hidden-input { display: none; }
        .file-pill { display: flex; align-items: center; justify-content: space-between; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 6px; padding: 8px 12px; margin-bottom: 6px; font-size: 12px; color: #0369a1; }
        .btn-save-kyc { background: #0284c7; color: #ffffff; border: none; border-radius: 8px; padding: 12px 28px; font-size: 14px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25); transition: all 0.2s; }
        .btn-save-kyc:hover { background: #0369a1; transform: translateY(-1px); }
        .alert-box { padding: 12px 18px; border-radius: 8px; font-size: 13.5px; font-weight: 500; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
        @media (max-width: 800px) {
            .grid-3, .grid-2 { grid-template-columns: 1fr; }
            .kyc-card { padding: 20px; }
        }
    </style>
</head>
<body>

<div class="kyc-wrap">
    <div class="top-nav">
        <a href="Dashboard/member.php" class="btn-back">
            <i class='bx bx-left-arrow-alt'></i> Back to Resident Dashboard
        </a>
        <div>
            <?php if ($val_status === 'Verified'): ?>
                <span class="badge-status badge-verified"><i class='bx bx-check-shield'></i> KYC Verified</span>
            <?php elseif ($val_status === 'Pending'): ?>
                <span class="badge-status badge-pending"><i class='bx bx-time-five'></i> KYC Under Review</span>
            <?php else: ?>
                <span class="badge-status badge-unsub"><i class='bx bx-info-circle'></i> KYC Pending Submission</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_GET['new']) && $_GET['new'] == '1'): ?>
        <div style="background: #e0f2fe; border: 1.5px solid #38bdf8; color: #0369a1; padding: 14px 20px; border-radius: 8px; font-size: 13.5px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class='bx bx-party' style="font-size: 24px; color: #0284c7;"></i>
            <div>
                <strong>Welcome to Yadav Living!</strong> Your member account is registered. Please complete your official resident KYC below to register your flat ownership/residency and ID records.
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_msg)): ?>
        <div class="alert-box alert-success">
            <i class='bx bx-check-circle' style="font-size: 20px;"></i>
            <span><?php echo htmlspecialchars($success_msg); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert-box alert-error">
            <i class='bx bx-error-circle' style="font-size: 20px;"></i>
            <span><?php echo htmlspecialchars($error_msg); ?></span>
        </div>
    <?php endif; ?>

    <div class="kyc-card">
        <div class="kyc-header">
            <h2>Yadav Living Resident KYC Verification</h2>
            <p>Government ID and flat residency documentation for secure society governance</p>
        </div>

        <form action="kyc.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="save_society_kyc" value="1">

            <div class="grid-3">
                <div class="form-group">
                    <label>Resident Full Name <span class="req">*</span></label>
                    <input type="text" name="full_name" class="form-control-kyc" value="<?php echo htmlspecialchars($val_full_name); ?>" placeholder="e.g. Yuvraj Yadav" required>
                </div>
                <div class="form-group">
                    <label>Flat / Unit Number <span class="req">*</span></label>
                    <input type="text" name="flat_no" class="form-control-kyc" value="<?php echo htmlspecialchars($val_flat_no); ?>" placeholder="e.g. Flat A-101" required>
                </div>
                <div class="form-group">
                    <label>Resident Type <span class="req">*</span></label>
                    <select name="resident_type" id="residentType" class="form-control-kyc" required>
                        <option value="Owner" <?php echo ($val_res_type === 'Owner') ? 'selected' : ''; ?>>Property Owner</option>
                        <option value="Tenant" <?php echo ($val_res_type === 'Tenant') ? 'selected' : ''; ?>>Tenant / Lessee</option>
                        <option value="Family Member" <?php echo ($val_res_type === 'Family Member') ? 'selected' : ''; ?>>Family Member</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="monthlyRent">Monthly Rent (₹, for tenants)</label>
                    <input type="number" id="monthlyRent" name="monthly_rent" class="form-control-kyc" value="<?php echo htmlspecialchars((string)$val_monthly_rent); ?>" min="0" step="0.01" placeholder="Leave blank if not applicable" <?php echo $val_res_type === 'Tenant' ? 'required' : ''; ?>>
                </div>
            </div>

            <div class="grid-3">
                <div class="form-group">
                    <label>Father / Spouse Name</label>
                    <input type="text" name="father_or_spouse" class="form-control-kyc" value="<?php echo htmlspecialchars($val_father_spouse); ?>" placeholder="e.g. Binod Prasad Yadav">
                </div>
                <div class="form-group">
                    <label>Emergency / Alt Mobile <span class="req">*</span></label>
                    <input type="tel" name="emergency_phone" class="form-control-kyc" value="<?php echo htmlspecialchars($val_emerg_phone); ?>" placeholder="e.g. 7004936609" required>
                </div>
                <div class="form-group">
                    <label>Primary ID Type <span class="req">*</span></label>
                    <select name="id_proof_type" class="form-control-kyc">
                        <option value="Aadhaar Card" <?php echo ($val_id_type === 'Aadhaar Card') ? 'selected' : ''; ?>>Aadhaar Card</option>
                        <option value="Voter ID" <?php echo ($val_id_type === 'Voter ID') ? 'selected' : ''; ?>>Voter ID</option>
                        <option value="Passport" <?php echo ($val_id_type === 'Passport') ? 'selected' : ''; ?>>Passport</option>
                        <option value="Driving License" <?php echo ($val_id_type === 'Driving License') ? 'selected' : ''; ?>>Driving License</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label>Permanent Residential Address <span class="req">*</span></label>
                <textarea name="permanent_address" class="form-control-kyc" rows="2" placeholder="Village/Town, District, State, PIN code" required><?php echo htmlspecialchars($val_address); ?></textarea>
            </div>

            <!-- Uploads Grid -->
            <div style="font-weight: 700; color: #0f172a; font-size: 15px; margin-bottom: 14px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                <i class='bx bx-cloud-upload'></i> Identity &amp; Residence Proof Documents
            </div>

            <div class="grid-3">
                <!-- Profile Picture -->
                <div class="form-group">
                    <label>Upload Profile Photo</label>
                    <?php if (!empty($val_pic)): ?>
                        <div class="file-pill">
                            <span><i class='bx bx-image'></i> <?php echo htmlspecialchars($val_pic); ?></span>
                            <a href="<?php echo htmlspecialchars(hms_kyc_download_url('society', $val_pic), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" style="color: #0284c7; text-decoration: none; font-weight: 600;">View</a>
                        </div>
                    <?php endif; ?>
                    <div class="select-file-box" onclick="document.getElementById('profilePic').click()">
                        <i class='bx bx-upload'></i> <?php echo !empty($val_pic) ? 'Change Photo' : 'Select Photo'; ?>
                    </div>
                    <input type="file" name="profile_pic_file" id="profilePic" class="file-hidden-input" accept="image/*" onchange="previewFile(this, 'profilePicText')">
                    <span id="profilePicText" style="font-size: 11px; color: #0284c7; margin-top: 4px;"></span>
                </div>

                <!-- ID Proof Front -->
                <div class="form-group">
                    <label>ID Proof Front <span class="req">*</span></label>
                    <?php if (!empty($val_id_f)): ?>
                        <div class="file-pill">
                            <span><i class='bx bx-id-card'></i> <?php echo htmlspecialchars($val_id_f); ?></span>
                            <a href="<?php echo htmlspecialchars(hms_kyc_download_url('society', $val_id_f), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" style="color: #0284c7; text-decoration: none; font-weight: 600;">View</a>
                        </div>
                    <?php endif; ?>
                    <div class="select-file-box" onclick="document.getElementById('idFront').click()">
                        <i class='bx bx-upload'></i> <?php echo !empty($val_id_f) ? 'Change File' : 'Select File'; ?>
                    </div>
                    <input type="file" name="id_front_file" id="idFront" class="file-hidden-input" onchange="previewFile(this, 'idFrontText')">
                    <span id="idFrontText" style="font-size: 11px; color: #0284c7; margin-top: 4px;"></span>
                </div>

                <!-- ID Proof Back -->
                <div class="form-group">
                    <label>ID Proof Back</label>
                    <?php if (!empty($val_id_b)): ?>
                        <div class="file-pill">
                            <span><i class='bx bx-id-card'></i> <?php echo htmlspecialchars($val_id_b); ?></span>
                            <a href="<?php echo htmlspecialchars(hms_kyc_download_url('society', $val_id_b), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" style="color: #0284c7; text-decoration: none; font-weight: 600;">View</a>
                        </div>
                    <?php endif; ?>
                    <div class="select-file-box" onclick="document.getElementById('idBack').click()">
                        <i class='bx bx-upload'></i> <?php echo !empty($val_id_b) ? 'Change File' : 'Select File'; ?>
                    </div>
                    <input type="file" name="id_back_file" id="idBack" class="file-hidden-input" onchange="previewFile(this, 'idBackText')">
                    <span id="idBackText" style="font-size: 11px; color: #0284c7; margin-top: 4px;"></span>
                </div>
            </div>

            <div class="grid-2">
                <!-- Address Proof Front -->
                <div class="form-group">
                    <label>Ownership / Lease / Electricity Bill (Front) <span class="req">*</span></label>
                    <?php if (!empty($val_addr_f)): ?>
                        <div class="file-pill">
                            <span><i class='bx bx-file'></i> <?php echo htmlspecialchars($val_addr_f); ?></span>
                            <a href="<?php echo htmlspecialchars(hms_kyc_download_url('society', $val_addr_f), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" style="color: #0284c7; text-decoration: none; font-weight: 600;">View</a>
                        </div>
                    <?php endif; ?>
                    <div class="select-file-box" onclick="document.getElementById('addrFront').click()">
                        <i class='bx bx-upload'></i> <?php echo !empty($val_addr_f) ? 'Change File' : 'Select File'; ?>
                    </div>
                    <input type="file" name="addr_front_file" id="addrFront" class="file-hidden-input" onchange="previewFile(this, 'addrFrontText')">
                    <span id="addrFrontText" style="font-size: 11px; color: #0284c7; margin-top: 4px;"></span>
                </div>

                <!-- Address Proof Back -->
                <div class="form-group">
                    <label>Address / Lease Agreement (Back / Page 2)</label>
                    <?php if (!empty($val_addr_b)): ?>
                        <div class="file-pill">
                            <span><i class='bx bx-file'></i> <?php echo htmlspecialchars($val_addr_b); ?></span>
                            <a href="<?php echo htmlspecialchars(hms_kyc_download_url('society', $val_addr_b), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" style="color: #0284c7; text-decoration: none; font-weight: 600;">View</a>
                        </div>
                    <?php endif; ?>
                    <div class="select-file-box" onclick="document.getElementById('addrBack').click()">
                        <i class='bx bx-upload'></i> <?php echo !empty($val_addr_b) ? 'Change File' : 'Select File'; ?>
                    </div>
                    <input type="file" name="addr_back_file" id="addrBack" class="file-hidden-input" onchange="previewFile(this, 'addrBackText')">
                    <span id="addrBackText" style="font-size: 11px; color: #0284c7; margin-top: 4px;"></span>
                </div>
            </div>

            <!-- Bottom Row: Terms & Submit -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 20px; margin-top: 20px; flex-wrap: wrap; gap: 16px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13.5px; color: #334155; cursor: pointer;">
                    <input type="checkbox" name="terms_accepted" value="1" <?php echo ($kyc['terms_accepted'] ?? 1) == 1 ? 'checked' : ''; ?> required style="width: 17px; height: 17px; accent-color: #0284c7;">
                    <span>I certify that all details &amp; uploaded documents are authentic and abide by Yadav Living community guidelines.</span>
                </label>

                <button type="submit" class="btn-save-kyc">
                    <i class='bx bx-check-shield'></i> Submit Resident KYC
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const residentTypeInput = document.getElementById('residentType');
    const monthlyRentInput = document.getElementById('monthlyRent');
    const syncMonthlyRentRequirement = () => {
        monthlyRentInput.required = residentTypeInput.value === 'Tenant';
    };
    residentTypeInput.addEventListener('change', syncMonthlyRentRequirement);
    syncMonthlyRentRequirement();

    function previewFile(input, textId) {
        if (input.files && input.files[0]) {
            document.getElementById(textId).innerText = "Selected: " + input.files[0].name;
        }
    }
</script>

</body>
</html>

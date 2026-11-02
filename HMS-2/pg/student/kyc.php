<?php
$page_title = "KYC";
require_once("header.php");

// Helper function to handle file upload
function handle_upload($file_key, $prefix, $default = null) {
    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
        $upload_dir = hms_kyc_upload_directory('pg');
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

// Fetch existing KYC details
$q_kyc = mysqli_query($con, "SELECT * FROM pg_kyc WHERE student_id = $current_student_id ORDER BY id DESC LIMIT 1");
$kyc = mysqli_fetch_assoc($q_kyc);

$success_msg = "";
$error_msg = "";

// Handle KYC form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_kyc'])) {
    $father_name = trim(isset($_POST['father_name']) ? $_POST['father_name'] : '');
    $father_mobile = trim(isset($_POST['father_mobile']) ? $_POST['father_mobile'] : '');
    $mother_name = trim(isset($_POST['mother_name']) ? $_POST['mother_name'] : '');
    $mother_mobile = trim(isset($_POST['mother_mobile']) ? $_POST['mother_mobile'] : '');
    $address = trim(isset($_POST['address']) ? $_POST['address'] : '');
    $terms_accepted = isset($_POST['terms_accepted']) ? 1 : 0;

    // Retain existing files or handle new uploads
    $profile_pic = handle_upload('profile_pic_file', 'PIC_' . $current_student_id, $kyc['profile_pic'] ?? null);
    $addr_front = handle_upload('addr_front_file', 'ADDR_F_' . $current_student_id, $kyc['address_proof_front'] ?? null);
    $addr_back = handle_upload('addr_back_file', 'ADDR_B_' . $current_student_id, $kyc['address_proof_back'] ?? null);
    $id_front = handle_upload('id_front_file', 'ID_F_' . $current_student_id, $kyc['id_proof_front'] ?? null);
    $id_back = handle_upload('id_back_file', 'ID_B_' . $current_student_id, $kyc['id_proof_back'] ?? null);

    // If clearing profile pic and no new file was uploaded
    if (isset($_POST['clear_profile_pic']) && $_POST['clear_profile_pic'] == '1' && (empty($_FILES['profile_pic_file']['name']) || $_FILES['profile_pic_file']['error'] !== UPLOAD_ERR_OK)) {
        $profile_pic = null;
    }

    if (empty($father_name) || empty($father_mobile) || empty($address)) {
        $error_msg = "Please provide Father/Guardian Name, Mobile, and Permanent Address.";
    } elseif (!$terms_accepted) {
        $error_msg = "You must accept the terms and conditions to submit KYC.";
    } else {
        if ($kyc) {
            // Update existing KYC
            $stmt = mysqli_prepare($con, "UPDATE pg_kyc SET father_name = ?, father_mobile = ?, mother_name = ?, mother_mobile = ?, address = ?, profile_pic = ?, address_proof_front = ?, address_proof_back = ?, id_proof_front = ?, id_proof_back = ?, terms_accepted = ?, status = 'Pending', verified_at = NULL, submitted_at = NOW() WHERE student_id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ssssssssssii", $father_name, $father_mobile, $mother_name, $mother_mobile, $address, $profile_pic, $addr_front, $addr_back, $id_front, $id_back, $terms_accepted, $current_student_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        } else {
            // Insert new KYC
            $stmt = mysqli_prepare($con, "INSERT INTO pg_kyc (student_id, id_type, id_number, father_name, father_mobile, mother_name, mother_mobile, address, profile_pic, address_proof_front, address_proof_back, id_proof_front, id_proof_back, terms_accepted, status, submitted_at) VALUES (?, 'Aadhaar Card', 'Pending Review', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "isssssssssssi", $current_student_id, $father_name, $father_mobile, $mother_name, $mother_mobile, $address, $profile_pic, $addr_front, $addr_back, $id_front, $id_back, $terms_accepted);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }

        // Also sync address & profile pic to pg_students
        if (!empty($address)) {
            $upd_stu = mysqli_prepare($con, "UPDATE pg_students SET location = ? WHERE id = ?");
            if ($upd_stu) {
                mysqli_stmt_bind_param($upd_stu, "si", $address, $current_student_id);
                mysqli_stmt_execute($upd_stu);
                mysqli_stmt_close($upd_stu);
            }
        }
        if (!empty($profile_pic)) {
            $upd_pic = mysqli_prepare($con, "UPDATE pg_students SET photo = ? WHERE id = ?");
            if ($upd_pic) {
                mysqli_stmt_bind_param($upd_pic, "si", $profile_pic, $current_student_id);
                mysqli_stmt_execute($upd_pic);
                mysqli_stmt_close($upd_pic);
            }
        }

        $success_msg = "PG KYC details and identity documents saved and sent to the warden for review.";

        // Refresh KYC data
        $q_kyc = mysqli_query($con, "SELECT * FROM pg_kyc WHERE student_id = $current_student_id ORDER BY id DESC LIMIT 1");
        $kyc = mysqli_fetch_assoc($q_kyc);
    }
}

// Prefill defaults if not yet set
$val_father_name = $kyc['father_name'] ?? '';
$val_father_mobile = $kyc['father_mobile'] ?? '';
$val_mother_name = $kyc['mother_name'] ?? '';
$val_mother_mobile = $kyc['mother_mobile'] ?? '';
$val_address = $kyc['address'] ?? ($current_student['location'] ?? '');
$val_profile_pic = $kyc['profile_pic'] ?? ($current_student['photo'] ?? '');
$val_addr_front = $kyc['address_proof_front'] ?? '';
$val_addr_back = $kyc['address_proof_back'] ?? '';
$val_id_front = $kyc['id_proof_front'] ?? '';
$val_id_back = $kyc['id_proof_back'] ?? '';
$val_terms = ($kyc['terms_accepted'] ?? 1) == 1;
?>

<style>
    .kyc-page-header {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: 0.5px;
        margin-bottom: 20px;
        text-transform: uppercase;
    }
    .kyc-card {
        background: #ffffff;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
        padding: 32px 36px 36px 36px;
        max-width: 1100px;
    }
    .kyc-card-title {
        font-size: 18px;
        font-weight: 600;
        color: #1e293b;
        padding-bottom: 16px;
        border-bottom: 1px solid #f1f5f9;
        margin-bottom: 26px;
    }
    .kyc-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        column-gap: 28px;
        row-gap: 22px;
        margin-bottom: 26px;
    }
    .kyc-group {
        display: flex;
        flex-direction: column;
    }
    .kyc-group label {
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 8px;
    }
    .kyc-group label .req {
        color: #ef4444;
        margin-left: 2px;
    }
    .kyc-input {
        width: 100%;
        padding: 10px 14px;
        border: 1.5px solid #cbd5e1;
        border-radius: 6px;
        font-size: 14px;
        color: #1e293b;
        outline: none;
        background: #ffffff;
        transition: border-color 0.2s;
    }
    .kyc-input:focus {
        border-color: #0099e5;
    }
    .kyc-textarea {
        width: 100%;
        padding: 10px 14px;
        border: 1.5px solid #cbd5e1;
        border-radius: 6px;
        font-size: 14px;
        color: #1e293b;
        outline: none;
        background: #ffffff;
        resize: vertical;
        min-height: 52px;
        transition: border-color 0.2s;
        font-family: 'Poppins', sans-serif;
    }
    .kyc-textarea:focus {
        border-color: #0099e5;
    }
    /* Existing uploaded file display */
    .file-pill-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #0099e5;
        font-size: 13.5px;
        font-weight: 500;
        text-decoration: none;
        padding: 4px 0;
        word-break: break-all;
    }
    .file-pill-link:hover {
        text-decoration: underline;
    }
    .btn-clear-pic {
        color: #ef4444;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
        margin-left: 4px;
        text-decoration: none;
    }
    /* Select file box */
    .select-file-box {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 18px;
        text-align: center;
        color: #475569;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    .select-file-box:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .file-hidden-input {
        display: none;
    }
    /* Bottom Actions Row */
    .kyc-bottom-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 20px;
        border-top: 1px solid #f1f5f9;
        margin-top: 10px;
    }
    .terms-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13.5px;
        color: #1e293b;
    }
    .terms-wrap input[type="checkbox"] {
        width: 17px;
        height: 17px;
        accent-color: #0099e5;
        cursor: pointer;
    }
    .terms-wrap a {
        color: #0099e5;
        text-decoration: none;
        font-weight: 500;
    }
    .terms-wrap a:hover {
        text-decoration: underline;
    }
    .btn-save-kyc {
        background: #0099e5;
        color: #ffffff;
        border: none;
        border-radius: 6px;
        padding: 10px 24px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 4px 12px rgba(0, 153, 229, 0.25);
        transition: background 0.2s ease, transform 0.1s ease;
    }
    .btn-save-kyc:hover {
        background: #0088cc;
        transform: translateY(-1px);
    }
    .alert-kyc-success {
        background: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
        border-radius: 8px;
        padding: 12px 18px;
        font-size: 13.5px;
        font-weight: 500;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .alert-kyc-error {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fca5a5;
        border-radius: 8px;
        padding: 12px 18px;
        font-size: 13.5px;
        font-weight: 500;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .file-preview-pill {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 8px 12px;
        margin-bottom: 6px;
    }
    @media (max-width: 900px) {
        .kyc-grid {
            grid-template-columns: 1fr;
            row-gap: 16px;
        }
        .kyc-bottom-row {
            flex-direction: column;
            gap: 16px;
            align-items: flex-start;
        }
        .btn-save-kyc {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="kyc-page-header">KYC</div>

<?php if (isset($_GET['new']) && $_GET['new'] == '1'): ?>
    <div style="background: #e0f2fe; border: 1.5px solid #38bdf8; color: #0369a1; padding: 14px 20px; border-radius: 8px; font-size: 13.5px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <i class='bx bx-party' style="font-size: 22px; color: #0284c7;"></i>
        <div>
            <strong>Welcome to Yadav Living PG!</strong> Your account is registered. Please fill in your Guardian Details and upload ID documents below to complete official admission. Room & Bed allocation will be finalized by Owners Binod Yadav & Sumit Yadav upon room visit.
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($success_msg)): ?>
    <div class="alert-kyc-success">
        <i class='bx bx-check-circle' style="font-size: 20px;"></i>
        <span><?php echo htmlspecialchars($success_msg); ?></span>
    </div>
<?php endif; ?>

<?php if (!empty($error_msg)): ?>
    <div class="alert-kyc-error">
        <i class='bx bx-error-circle' style="font-size: 20px;"></i>
        <span><?php echo htmlspecialchars($error_msg); ?></span>
    </div>
<?php endif; ?>

<div class="kyc-card">
    <div class="kyc-card-title">Yadav Living PG Resident KYC</div>
    <div style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:12px; padding:18px; margin-bottom:22px;">
        <div style="font-size:14px; font-weight:700; color:#142d3e; margin-bottom:12px;">Your PG stay details</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:12px;">
            <div>
                <div style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase;">Room / Bed</div>
                <div style="font-size:14px; color:#0f172a; font-weight:700; margin-top:4px;">
                    <?php if (!empty($current_student['room_number'])): ?>
                        <?php echo htmlspecialchars($current_student['room_number']); ?> / <?php echo htmlspecialchars($current_student['bed_label'] ?? 'Bed assigned'); ?>
                    <?php else: ?>
                        Awaiting allocation
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <div style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase;">Agreed monthly rent</div>
                <div style="font-size:14px; color:#0f172a; font-weight:700; margin-top:4px;">
                    <?php echo (float)($current_student['rent_amount'] ?? 0) > 0 ? '₹' . number_format((float)$current_student['rent_amount'], 2) : 'Not set'; ?>
                </div>
            </div>
            <div>
                <div style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase;">Security deposit</div>
                <div style="font-size:14px; color:#0f172a; font-weight:700; margin-top:4px;">
                    <?php echo (float)($current_student['deposit_amount'] ?? 0) > 0 ? '₹' . number_format((float)$current_student['deposit_amount'], 2) : 'Not set'; ?>
                </div>
            </div>
        </div>
        <p style="font-size:12px; line-height:1.5; color:#475569; margin-top:12px;">These PG stay terms are maintained by the warden. Update guardian details and upload your PG identity documents below; do not enter Society flat information here.</p>
    </div>
    <div class="kyc-card-title">Student &amp; Guardian Identity</div>

    <form action="kyc.php" method="POST" enctype="multipart/form-data" id="kycForm">
        <input type="hidden" name="save_kyc" value="1">
        <input type="hidden" name="clear_profile_pic" id="clearProfilePicInput" value="0">

        <div class="kyc-grid">
            <!-- Row 1: Profile Pic -->
            <div class="kyc-group">
                <label>Upload Profile Pic <span class="req">*</span></label>
                <?php if (!empty($val_profile_pic)): ?>
                    <div class="file-preview-pill" id="profilePicBox">
                        <span class="file-pill-link">
                            <i class='bx bx-image'></i> <?php echo htmlspecialchars($current_student['name'] ?? 'Profile Pic'); ?>
                        </span>
                        <span class="btn-clear-pic" onclick="clearProfilePic()" title="Change/Remove Photo">&times;</span>
                    </div>
                    <div id="profilePicChangeBox" style="display: none; margin-top: 6px;">
                        <div class="select-file-box" onclick="document.getElementById('profilePicFile').click()" style="padding: 10px; font-size: 13px;">
                            <i class='bx bx-upload'></i> Select New Photo
                        </div>
                    </div>
                    <input type="file" name="profile_pic_file" id="profilePicFile" class="file-hidden-input" accept="image/*" onchange="previewFileName(this, 'profilePicLabel')">
                    <span id="profilePicLabel" style="font-size: 11.5px; color: #0284c7; margin-top: 4px;"></span>
                <?php else: ?>
                    <div class="select-file-box" onclick="document.getElementById('profilePicFile').click()">
                        <i class='bx bx-upload'></i> Select Profile Picture
                    </div>
                    <input type="file" name="profile_pic_file" id="profilePicFile" class="file-hidden-input" accept="image/*" onchange="previewFileName(this, 'profilePicLabel')">
                    <span id="profilePicLabel" style="font-size: 11.5px; color: #0284c7; margin-top: 4px;"></span>
                <?php endif; ?>
            </div>

            <!-- Row 1: Father Name -->
            <div class="kyc-group">
                <label>Father/Guardian Name <span class="req">*</span></label>
                <input type="text" name="father_name" class="kyc-input" value="<?php echo htmlspecialchars($val_father_name); ?>" placeholder="e.g. Binod Prasad Yadav" required>
            </div>

            <!-- Row 1: Father Mobile -->
            <div class="kyc-group">
                <label>Father / Guardian Mobile No. <span class="req">*</span></label>
                <input type="tel" name="father_mobile" class="kyc-input" value="<?php echo htmlspecialchars($val_father_mobile); ?>" placeholder="e.g. 7004936609" required>
            </div>

            <!-- Row 2: Mother Name -->
            <div class="kyc-group">
                <label>Mother Name <span class="req">*</span></label>
                <input type="text" name="mother_name" class="kyc-input" value="<?php echo htmlspecialchars($val_mother_name); ?>" placeholder="e.g. Savitri Devi">
            </div>

            <!-- Row 2: Mother Mobile -->
            <div class="kyc-group">
                <label>Mother Mobile No. <span class="req">*</span></label>
                <input type="tel" name="mother_mobile" class="kyc-input" value="<?php echo htmlspecialchars($val_mother_mobile); ?>" placeholder="e.g. 9508969825">
            </div>

            <!-- Row 2: Permanent Address -->
            <div class="kyc-group">
                <label>Address <span class="req">*</span></label>
                <textarea name="address" class="kyc-textarea" placeholder="Village/Town, District, State, PIN code" rows="2" required><?php echo htmlspecialchars($val_address); ?></textarea>
            </div>

            <!-- Row 3: Address Proof Front -->
            <div class="kyc-group">
                <label>Address Proof Front <span class="req">*</span></label>
                <?php if (!empty($val_addr_front)): ?>
                    <div class="file-preview-pill">
                        <a href="<?php echo htmlspecialchars(hms_kyc_download_url('pg', $val_addr_front), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="file-pill-link">
                            <i class='bx bx-file'></i> <?php echo htmlspecialchars($val_addr_front); ?>
                        </a>
                        <span style="font-size: 11px; color: #10b981; font-weight: 600;">Uploaded</span>
                    </div>
                <?php endif; ?>
                <div class="select-file-box" onclick="document.getElementById('addrFrontFile').click()" style="padding: 10px; font-size: 13px;">
                    <i class='bx bx-upload'></i> <?php echo !empty($val_addr_front) ? 'Change File' : 'Select File'; ?>
                </div>
                <input type="file" name="addr_front_file" id="addrFrontFile" class="file-hidden-input" onchange="previewFileName(this, 'addrFrontLabel')">
                <span id="addrFrontLabel" style="font-size: 11.5px; color: #0284c7; margin-top: 4px;"></span>
            </div>

            <!-- Row 3: ID Proof Front -->
            <div class="kyc-group">
                <label>ID Proof Front <span class="req">*</span></label>
                <?php if (!empty($val_id_front)): ?>
                    <div class="file-preview-pill">
                        <a href="<?php echo htmlspecialchars(hms_kyc_download_url('pg', $val_id_front), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="file-pill-link">
                            <i class='bx bx-id-card'></i> <?php echo htmlspecialchars($val_id_front); ?>
                        </a>
                        <span style="font-size: 11px; color: #10b981; font-weight: 600;">Uploaded</span>
                    </div>
                <?php endif; ?>
                <div class="select-file-box" onclick="document.getElementById('idFrontFile').click()" style="padding: 10px; font-size: 13px;">
                    <i class='bx bx-upload'></i> <?php echo !empty($val_id_front) ? 'Change File' : 'Select File'; ?>
                </div>
                <input type="file" name="id_front_file" id="idFrontFile" class="file-hidden-input" onchange="previewFileName(this, 'idFrontLabel')">
                <span id="idFrontLabel" style="font-size: 11.5px; color: #0284c7; margin-top: 4px;"></span>
            </div>

            <!-- Row 3: ID Proof Back -->
            <div class="kyc-group">
                <label>Id Proof Back <span class="req">*</span></label>
                <?php if (!empty($val_id_back)): ?>
                    <div class="file-preview-pill">
                        <a href="<?php echo htmlspecialchars(hms_kyc_download_url('pg', $val_id_back), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="file-pill-link">
                            <i class='bx bx-id-card'></i> <?php echo htmlspecialchars($val_id_back); ?>
                        </a>
                        <span style="font-size: 11px; color: #10b981; font-weight: 600;">Uploaded</span>
                    </div>
                <?php endif; ?>
                <div class="select-file-box" onclick="document.getElementById('idBackFile').click()" style="padding: 10px; font-size: 13px;">
                    <i class='bx bx-upload'></i> <?php echo !empty($val_id_back) ? 'Change File' : 'Select File'; ?>
                </div>
                <input type="file" name="id_back_file" id="idBackFile" class="file-hidden-input" onchange="previewFileName(this, 'idBackLabel')">
                <span id="idBackLabel" style="font-size: 11.5px; color: #0284c7; margin-top: 4px;"></span>
            </div>

            <!-- Row 4: Address Proof Back -->
            <div class="kyc-group">
                <label>Address Proof Back <span class="req">*</span></label>
                <?php if (!empty($val_addr_back)): ?>
                    <div class="file-preview-pill">
                        <a href="<?php echo htmlspecialchars(hms_kyc_download_url('pg', $val_addr_back), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="file-pill-link">
                            <i class='bx bx-file'></i> <?php echo htmlspecialchars($val_addr_back); ?>
                        </a>
                        <span style="font-size: 11px; color: #10b981; font-weight: 600;">Uploaded</span>
                    </div>
                <?php endif; ?>
                <div class="select-file-box" onclick="document.getElementById('addrBackFile').click()">
                    <?php echo !empty($val_addr_back) ? 'Change File' : 'Select File'; ?>
                </div>
                <input type="file" name="addr_back_file" id="addrBackFile" class="file-hidden-input" onchange="previewFileName(this, 'addrBackLabel')">
                <span id="addrBackLabel" style="font-size: 11.5px; color: #0284c7; margin-top: 4px;"></span>
            </div>
        </div>

        <!-- Row 5: Terms Checkbox & Save Button -->
        <div class="kyc-bottom-row">
            <div class="terms-wrap">
                <input type="checkbox" id="termsAccepted" name="terms_accepted" <?php echo $val_terms ? 'checked' : ''; ?> required>
                <label for="termsAccepted">
                    I accept <a href="javascript:void(0)" onclick="openTermsModal()">terms and conditions</a>
                </label>
            </div>

            <button type="submit" class="btn-save-kyc">
                <i class='bx bx-check-circle' style="font-size: 18px;"></i> Save
            </button>
        </div>
    </form>
</div>

<!-- Terms & Conditions Modal -->
<div id="termsModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: #ffffff; width: 92%; max-width: 520px; border-radius: 12px; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.25);">
        <div style="padding: 16px 20px; background: #0099e5; color: #ffffff; display: flex; justify-content: space-between; align-items: center;">
            <strong style="font-size: 15px;"><i class='bx bx-shield-quarter'></i> Yadav Living PG &bull; Terms and Conditions</strong>
            <button type="button" onclick="closeTermsModal()" style="background: none; border: none; color: #fff; font-size: 22px; cursor: pointer;">&times;</button>
        </div>
        <div style="padding: 22px; font-size: 13px; line-height: 1.6; color: #334155; max-height: 380px; overflow-y: auto;">
            <p><b>1. Monthly Stay Charges:</b> Charges must be settled on or before the agreed date each month as arranged directly with Owners Binod Yadav & Sumit Yadav.</p>
            <p style="margin-top: 10px;"><b>2. Cleanliness & Discipline:</b> Rooms, balconies, and common areas must be kept clean. Quiet study hours commence at 10:00 PM.</p>
            <p style="margin-top: 10px;"><b>3. Accommodation Facility:</b> Note that Yadav Living PG is a pure accommodation facility; food/meals are not provided.</p>
            <p style="margin-top: 10px;"><b>4. 30-Day Vacate Intimation:</b> Notice must be provided at least 30 days prior to vacating via the portal to settle your account.</p>
            <p style="margin-top: 10px;"><b>5. Government ID Verification:</b> Providing authentic government identification (Aadhaar/Voter/Passport) is mandatory by law.</p>
        </div>
        <div style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: right;">
            <button type="button" onclick="closeTermsModal()" style="background: #0099e5; color: #fff; border: none; border-radius: 6px; padding: 8px 18px; font-size: 13px; font-weight: 600; cursor: pointer;">I Understand</button>
        </div>
    </div>
</div>

<script>
    function clearProfilePic() {
        document.getElementById('clearProfilePicInput').value = '1';
        const box = document.getElementById('profilePicBox');
        if (box) {
            box.style.display = 'none';
        }
        const changeBox = document.getElementById('profilePicChangeBox');
        if (changeBox) {
            changeBox.style.display = 'block';
        }
        document.getElementById('profilePicFile').click();
    }

    function previewFileName(input, labelId) {
        if (input.files && input.files[0]) {
            document.getElementById(labelId).innerText = "Selected: " + input.files[0].name;
        }
    }

    function openTermsModal() {
        document.getElementById('termsModal').style.display = 'flex';
    }

    function closeTermsModal() {
        document.getElementById('termsModal').style.display = 'none';
    }
</script>

<?php require_once("footer.php"); ?>

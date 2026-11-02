<?php
require_once __DIR__ . '/Includes/session.php';

$scope = $_GET['scope'] ?? '';
$filename = $_GET['file'] ?? '';
$isSocietyAdmin = isset($_SESSION['logged'], $_SESSION['account'])
    && $_SESSION['logged'] === true
    && $_SESSION['account'] === 'admin'
    && hms_current_admin_id() > 0;
$isPgAdmin = isset($_SESSION['pg_logged'], $_SESSION['pg_role'])
    && $_SESSION['pg_logged'] === true
    && $_SESSION['pg_role'] === 'admin'
    && (int)($_SESSION['pg_admin_id'] ?? 0) > 0;
$canAccess = false;

if (!in_array($scope, array('society', 'pg'), true) || !is_string($filename) || $filename === '') {
    http_response_code(400);
    exit('Invalid document request.');
}

$isAuthorized = false;

if ($scope === 'society') {
    $memberId = hms_current_member_id();
    if ($isSocietyAdmin) {
        $canAccess = true;
        $stmt = mysqli_prepare($con, "SELECT profile_pic, id_proof_front, id_proof_back, address_proof_front, address_proof_back FROM society_kyc");
    } elseif (isset($_SESSION['logged'], $_SESSION['account']) && $_SESSION['logged'] === true && $_SESSION['account'] === 'member' && $memberId > 0) {
        $canAccess = true;
        $stmt = mysqli_prepare($con, "SELECT profile_pic, id_proof_front, id_proof_back, address_proof_front, address_proof_back FROM society_kyc WHERE member_id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $memberId);
        }
    } else {
        $stmt = false;
    }
} else {
    $studentId = (int)($_SESSION['pg_student_id'] ?? 0);
    if ($isPgAdmin) {
        $canAccess = true;
        $stmt = mysqli_prepare($con, "SELECT profile_pic, id_proof_front, id_proof_back, address_proof_front, address_proof_back FROM pg_kyc");
        $studentPhotoStmt = mysqli_prepare($con, "SELECT photo FROM pg_students");
    } elseif (isset($_SESSION['pg_logged'], $_SESSION['pg_role']) && $_SESSION['pg_logged'] === true && $_SESSION['pg_role'] === 'student' && $studentId > 0) {
        $canAccess = true;
        $stmt = mysqli_prepare($con, "SELECT profile_pic, id_proof_front, id_proof_back, address_proof_front, address_proof_back FROM pg_kyc WHERE student_id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $studentId);
        }
        $studentPhotoStmt = mysqli_prepare($con, "SELECT photo FROM pg_students WHERE id = ?");
        if ($studentPhotoStmt) {
            mysqli_stmt_bind_param($studentPhotoStmt, 'i', $studentId);
        }
    } else {
        $stmt = false;
        $studentPhotoStmt = false;
    }
}

if (!$canAccess) {
    http_response_code(403);
    exit('You are not authorized to view this document.');
}

if (!$stmt) {
    error_log('Unable to prepare KYC document authorization query.');
    http_response_code(500);
    exit('Unable to retrieve this document.');
}
if (!mysqli_stmt_execute($stmt)) {
    error_log('Unable to execute KYC document authorization query: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    http_response_code(500);
    exit('Unable to retrieve this document.');
}

$result = mysqli_stmt_get_result($stmt);
if (!$result) {
    error_log('Unable to read KYC document authorization results: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    http_response_code(500);
    exit('Unable to retrieve this document.');
}
while ($row = mysqli_fetch_assoc($result)) {
    foreach ($row as $value) {
        if (is_string($value) && hash_equals($value, $filename)) {
            $isAuthorized = true;
            break 2;
        }
    }
}
mysqli_stmt_close($stmt);

if ($scope === 'pg' && !$isAuthorized && isset($studentPhotoStmt)) {
    if (!$studentPhotoStmt) {
        error_log('Unable to prepare PG student photo authorization query.');
        http_response_code(500);
        exit('Unable to retrieve this document.');
    }
    $photoResult = false;
    if (!mysqli_stmt_execute($studentPhotoStmt)) {
        error_log('Unable to execute PG student photo authorization query: ' . mysqli_stmt_error($studentPhotoStmt));
        mysqli_stmt_close($studentPhotoStmt);
        http_response_code(500);
        exit('Unable to retrieve this document.');
    }
    $photoResult = mysqli_stmt_get_result($studentPhotoStmt);
    if (!$photoResult) {
        error_log('Unable to read PG student photo authorization results: ' . mysqli_stmt_error($studentPhotoStmt));
        mysqli_stmt_close($studentPhotoStmt);
        http_response_code(500);
        exit('Unable to retrieve this document.');
    }
    while ($row = mysqli_fetch_assoc($photoResult)) {
        if (isset($row['photo']) && is_string($row['photo']) && hash_equals($row['photo'], $filename)) {
            $isAuthorized = true;
            break;
        }
    }
    mysqli_stmt_close($studentPhotoStmt);
}

if (!$isAuthorized) {
    http_response_code(404);
    exit('Document not found.');
}

$path = hms_kyc_file_path($scope, $filename);
if ($path === false) {
    http_response_code(404);
    exit('Document not found.');
}

$fileInfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $fileInfo->file($path);
$allowedMimeTypes = array('image/jpeg', 'image/png', 'image/webp', 'application/pdf');
if (!in_array($mimeType, $allowedMimeTypes, true)) {
    http_response_code(415);
    exit('Unsupported document type.');
}

session_write_close();
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . str_replace('"', '', basename($filename)) . '"');
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
readfile($path);

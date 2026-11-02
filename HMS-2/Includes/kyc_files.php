<?php
function hms_kyc_upload_directory($scope) {
    if ($scope === 'society') {
        return HMS_PRIVATE_UPLOAD_DIR . DIRECTORY_SEPARATOR . 'society_kyc' . DIRECTORY_SEPARATOR;
    }
    if ($scope === 'pg') {
        return HMS_PRIVATE_UPLOAD_DIR . DIRECTORY_SEPARATOR . 'pg' . DIRECTORY_SEPARATOR;
    }

    throw new InvalidArgumentException('Unknown KYC upload scope.');
}

function hms_kyc_file_path($scope, $filename) {
    if (!is_string($filename) || $filename === '' || basename($filename) !== $filename || strpos($filename, '\\') !== false) {
        return false;
    }

    $privateDirectory = hms_kyc_upload_directory($scope);
    $privatePath = $privateDirectory . $filename;
    if (is_file($privatePath) && !is_link($privatePath)) {
        return realpath($privatePath) ?: false;
    }

    $appRoot = dirname(__DIR__);
    $legacyDirectory = $scope === 'society'
        ? $appRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'society_kyc' . DIRECTORY_SEPARATOR
        : $appRoot . DIRECTORY_SEPARATOR . 'pg' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    $legacyPath = $legacyDirectory . $filename;
    if (!is_file($legacyPath) || is_link($legacyPath)) {
        return false;
    }

    if (!is_dir($privateDirectory) && !mkdir($privateDirectory, 0750, true) && !is_dir($privateDirectory)) {
        error_log('Unable to create private KYC upload directory.');
        return realpath($legacyPath) ?: false;
    }

    if (is_writable($privateDirectory) && rename($legacyPath, $privatePath)) {
        return realpath($privatePath) ?: false;
    }

    return realpath($legacyPath) ?: false;
}

function hms_kyc_download_url($scope, $filename) {
    if (!in_array($scope, array('society', 'pg'), true) || !is_string($filename) || $filename === '') {
        return '';
    }

    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
    $appRootPath = '';
    foreach (array('/pg/student/', '/pg/admin/', '/admin/', '/members/') as $marker) {
        $position = strpos($scriptPath, $marker);
        if ($position !== false) {
            $appRootPath = substr($scriptPath, 0, $position);
            break;
        }
    }

    return rtrim($appRootPath, '/') . '/download_kyc.php?scope=' . rawurlencode($scope) . '&file=' . rawurlencode($filename);
}

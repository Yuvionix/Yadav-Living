<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../pg/Includes/session.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function hms_ai_json_error($status, $message) {
    http_response_code($status);
    echo json_encode(array('error' => $message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    hms_ai_json_error(405, 'Use POST to ask the assistant a question.');
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (
    !isset($_SESSION['ai_csrf_token']) ||
    !is_string($_SESSION['ai_csrf_token']) ||
    !is_string($csrfToken) ||
    !hash_equals($_SESSION['ai_csrf_token'], $csrfToken)
) {
    hms_ai_json_error(403, 'Your session expired. Refresh the page and try again.');
}

$rawBody = file_get_contents('php://input');
if (!is_string($rawBody) || strlen($rawBody) > 8192) {
    hms_ai_json_error(400, 'Questions must be 1200 characters or fewer.');
}
$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    hms_ai_json_error(400, 'The question could not be read. Please try again.');
}

$property = isset($payload['property']) && is_string($payload['property'])
    ? $payload['property']
    : '';
$question = isset($payload['question']) && is_string($payload['question'])
    ? trim($payload['question'])
    : '';
$questionLength = preg_match_all('/./us', $question);
if (!in_array($property, array('society', 'pg'), true)) {
    hms_ai_json_error(400, 'Choose a valid property assistant.');
}
if ($questionLength === false || $questionLength < 2 || $questionLength > 1200) {
    hms_ai_json_error(400, 'Questions must be between 2 and 1200 characters.');
}

$societyRole = $_SESSION['account'] ?? '';
$societyAuthenticated = ($_SESSION['logged'] ?? false) === true &&
    (
        ($societyRole === 'member' && hms_current_member_id() > 0) ||
        ($societyRole === 'admin' && hms_current_admin_id() > 0)
    );
$pgRole = $_SESSION['pg_role'] ?? '';
$pgIdentity = $pgRole === 'student'
    ? hms_pg_current_student_id()
    : ($pgRole === 'admin' ? max(0, (int)($_SESSION['pg_admin_id'] ?? 0)) : 0);
$pgAuthenticated = ($_SESSION['pg_logged'] ?? false) === true && $pgIdentity > 0;

if (($property === 'society' && !$societyAuthenticated) || ($property === 'pg' && !$pgAuthenticated)) {
    hms_ai_json_error(403, 'Sign in to the selected property portal to use its assistant.');
}

$now = time();
$lastRequestAt = max(0, (int)($_SESSION['ai_last_request_at'] ?? 0));
if ($now - $lastRequestAt < 2) {
    header('Retry-After: 2');
    hms_ai_json_error(429, 'Please wait a moment before asking another question.');
}
$_SESSION['ai_last_request_at'] = $now;
session_write_close();

$serviceToken = trim((string)hms_env('AI_SERVICE_TOKEN', ''));
$serviceUrl = rtrim((string)hms_env('AI_SERVICE_URL', 'http://ai:8000'), '/') . '/chat';
if ($serviceToken === '') {
    hms_ai_json_error(503, 'The AI service is not configured. Set AI_SERVICE_TOKEN.');
}
if (!function_exists('curl_init')) {
    error_log('AI assistant request failed: PHP cURL extension is unavailable.');
    hms_ai_json_error(503, 'The AI service is unavailable. Please contact the administrator.');
}

$requestBody = json_encode(
    array('property' => $property, 'question' => $question),
    JSON_UNESCAPED_UNICODE
);
$curl = curl_init($serviceUrl);
if ($curl === false) {
    error_log('AI assistant request failed: unable to initialize cURL.');
    hms_ai_json_error(503, 'The AI service is unavailable. Please try again later.');
}

curl_setopt_array($curl, array(
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $requestBody,
    CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer ' . $serviceToken,
        'Content-Type: application/json',
        'Accept: application/json',
    ),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_TIMEOUT => 30,
));
$serviceResponse = curl_exec($curl);
$serviceStatus = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
if ($serviceResponse === false) {
    error_log('AI assistant service connection failed: ' . curl_error($curl));
    curl_close($curl);
    hms_ai_json_error(502, 'The AI service could not be reached. Please try again later.');
}
curl_close($curl);

$result = json_decode($serviceResponse, true);
if ($serviceStatus === 400 && is_array($result) && isset($result['detail']) && is_string($result['detail'])) {
    hms_ai_json_error(400, $result['detail']);
}
if ($serviceStatus < 200 || $serviceStatus >= 300 || !is_array($result)) {
    error_log('AI assistant service returned HTTP ' . $serviceStatus . '.');
    hms_ai_json_error(
        $serviceStatus === 503 ? 503 : 502,
        'The AI service could not complete the request. Please try again.'
    );
}
if (!isset($result['answer']) || !is_string($result['answer'])) {
    error_log('AI assistant service returned an invalid answer payload.');
    hms_ai_json_error(502, 'The AI service returned an invalid response. Please try again.');
}

$sources = array();
if (isset($result['sources']) && is_array($result['sources'])) {
    foreach ($result['sources'] as $source) {
        if (
            !is_array($source) ||
            !isset($source['title'], $source['url']) ||
            !is_string($source['title']) ||
            !is_string($source['url'])
        ) {
            continue;
        }
        $sourcePath = parse_url($source['url'], PHP_URL_PATH);
        if (
            !is_string($sourcePath) ||
            !str_starts_with($sourcePath, '/HMS-2/')
        ) {
            continue;
        }
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $endpointMarker = '/Includes/ai_chat.php';
        $endpointPosition = strrpos($scriptName, $endpointMarker);
        $applicationBase = $endpointPosition === false
            ? ''
            : substr($scriptName, 0, $endpointPosition);
        $applicationPath = substr($sourcePath, strlen('/HMS-2'));
        $sources[] = array(
            'title' => substr($source['title'], 0, 120),
            'url' => $applicationBase . $applicationPath,
        );
    }
}

echo json_encode(
    array('answer' => substr($result['answer'], 0, 5000), 'sources' => $sources),
    JSON_UNESCAPED_UNICODE
);

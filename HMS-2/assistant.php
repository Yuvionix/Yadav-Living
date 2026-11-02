<?php
require_once __DIR__ . '/Includes/session.php';
require_once __DIR__ . '/pg/Includes/session.php';

$property = $_GET['property'] ?? '';
if (!in_array($property, array('society', 'pg'), true)) {
    http_response_code(404);
    exit('Assistant not found.');
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

if ($property === 'society' && !$societyAuthenticated) {
    header('Location: login.php');
    exit;
}
if ($property === 'pg' && !$pgAuthenticated) {
    header('Location: pg/login.php');
    exit;
}

if (empty($_SESSION['ai_csrf_token'])) {
    $_SESSION['ai_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['ai_csrf_token'];
$propertyName = $property === 'society' ? 'Yadav Living Society' : 'Yadav Living PG';
$portalUrl = $property === 'society'
    ? ($societyRole === 'admin' ? 'admin/Dashboard/admin.php' : 'members/Dashboard/member.php')
    : ($pgRole === 'admin' ? 'pg/admin/dashboard.php' : 'pg/student/profile.php');
$assistantScript = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/js/community-assistant.js';
$apiEndpoint = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/Includes/ai_chat.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo htmlspecialchars($propertyName, ENT_QUOTES, 'UTF-8'); ?> Assistant</title>
    <link rel="stylesheet" href="css/master_theme.css">
    <style>
        :root { color-scheme: light; }
        body {
            min-height: 100vh;
            margin: 0;
            padding: 32px 18px;
            background: radial-gradient(circle at 8% 2%, #e0e7ff 0, transparent 34%), #f5f7fb;
            color: #172033;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .assistant-shell { width: min(920px, 100%); margin: 0 auto; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
        .assistant-topbar { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 20px; }
        .assistant-brand { color: #172033; font-weight: 800; text-decoration: none; }
        .assistant-back { color: #4351b5; font-size: 14px; font-weight: 700; text-decoration: none; }
        .assistant-card { overflow: hidden; border: 1px solid #e1e6f0; border-radius: 22px; background: #fff; box-shadow: 0 24px 70px rgba(32, 45, 80, .1); }
        .assistant-heading { padding: 24px 28px; color: #fff; background: linear-gradient(120deg, #10243d, #344c99 70%, #7759c7); }
        .assistant-heading h1 { margin: 0 0 8px; font-size: clamp(22px, 4vw, 30px); }
        .assistant-heading p { max-width: 700px; margin: 0; color: #e2e8ff; line-height: 1.55; }
        .privacy-note { margin: 18px 22px 0; padding: 12px 14px; border: 1px solid #f4d99b; border-radius: 12px; background: #fff9e9; color: #604b1d; font-size: 13px; line-height: 1.5; }
        .chat-messages { display: flex; flex-direction: column; gap: 14px; height: min(48vh, 470px); min-height: 260px; overflow-y: auto; padding: 22px; }
        .message { max-width: min(760px, 90%); padding: 13px 16px; border-radius: 16px; line-height: 1.6; white-space: pre-wrap; overflow-wrap: anywhere; }
        .message-assistant { align-self: flex-start; border: 1px solid #e4e8f1; background: #f7f8fc; }
        .message-user { align-self: flex-end; color: #fff; background: #3949ab; }
        .message-error { border-color: #fecaca; background: #fff1f2; color: #9f1239; }
        .message-sources { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
        .message-sources a { color: #3949ab; font-size: 12px; font-weight: 700; }
        .quick-prompts { display: flex; flex-wrap: wrap; gap: 8px; padding: 0 22px 16px; }
        .quick-prompt { border: 1px solid #dce2f1; border-radius: 999px; padding: 8px 12px; background: #fff; color: #3949ab; cursor: pointer; font: inherit; font-size: 12px; }
        .quick-prompt:hover, .quick-prompt:focus-visible { background: #eef0ff; }
        .chat-form { display: flex; gap: 10px; padding: 16px 22px 22px; border-top: 1px solid #edf0f5; }
        .chat-form input { flex: 1; min-width: 0; border: 1px solid #d8deea; border-radius: 12px; padding: 13px 14px; font: inherit; }
        .chat-form input:focus { outline: 3px solid #c7d2fe; border-color: #6675d1; }
        .chat-form button { border: 0; border-radius: 12px; padding: 0 20px; background: #3949ab; color: #fff; cursor: pointer; font: inherit; font-weight: 700; }
        .chat-form button:disabled { cursor: wait; opacity: .65; }
        .assistant-footnote { padding: 0 22px 20px; color: #667085; font-size: 12px; }
        @media (max-width: 560px) {
            body { padding: 16px 10px; }
            .assistant-heading { padding: 20px; }
            .chat-messages { height: 45vh; padding: 16px; }
            .chat-form { padding: 14px 16px 18px; }
            .chat-form button { padding: 0 14px; }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; } }
    </style>
    <script defer src="<?php echo htmlspecialchars($assistantScript, ENT_QUOTES, 'UTF-8'); ?>"></script>
</head>
<body>
    <main class="assistant-shell">
        <div class="assistant-topbar">
            <a class="assistant-brand" href="<?php echo htmlspecialchars($portalUrl, ENT_QUOTES, 'UTF-8'); ?>">Yadav Living</a>
            <a class="assistant-back" href="<?php echo htmlspecialchars($portalUrl, ENT_QUOTES, 'UTF-8'); ?>">← Back to <?php echo htmlspecialchars($propertyName, ENT_QUOTES, 'UTF-8'); ?></a>
        </div>
        <section class="assistant-card" id="communityAssistant"
            data-property="<?php echo htmlspecialchars($property, ENT_QUOTES, 'UTF-8'); ?>"
            data-endpoint="<?php echo htmlspecialchars($apiEndpoint, ENT_QUOTES, 'UTF-8'); ?>"
            data-csrf="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <header class="assistant-heading">
                <h1>Jai Sri Krishna · <?php echo htmlspecialchars($propertyName, ENT_QUOTES, 'UTF-8'); ?> Assistant</h1>
                <p>Ask about portal workflows and property information. Answers use a small verified guide and cite the relevant portal page.</p>
            </header>
            <div class="privacy-note" role="note">
                For your privacy, do not enter passwords, Aadhaar or other ID numbers, payment details, phone numbers, or private resident/student information. Questions are sent to the configured AI provider. This assistant cannot access account records or balances.
            </div>
            <div class="chat-messages" id="assistantMessages" aria-live="polite" aria-label="Conversation">
                <div class="message message-assistant">
                    Namaste! I can help you find verified Society or PG information and explain where to complete common portal tasks. For account-specific information, use your secure portal pages or contact the administrator.
                </div>
            </div>
            <div class="quick-prompts" aria-label="Suggested questions">
                <?php if ($property === 'society'): ?>
                    <button class="quick-prompt" type="button">Where is Yadav Living Society?</button>
                    <button class="quick-prompt" type="button">Where can I submit Resident KYC?</button>
                    <button class="quick-prompt" type="button">How do I view my maintenance bill?</button>
                    <button class="quick-prompt" type="button">Where can I find Society announcements?</button>
                <?php else: ?>
                    <button class="quick-prompt" type="button">Where is Yadav Living PG?</button>
                    <button class="quick-prompt" type="button">How do I complete student registration and KYC?</button>
                    <button class="quick-prompt" type="button">How do I submit a maintenance complaint?</button>
                    <button class="quick-prompt" type="button">Where can I view payment history?</button>
                <?php endif; ?>
            </div>
            <form class="chat-form" id="assistantForm">
                <label for="assistantQuestion" class="sr-only">Your question</label>
                <input id="assistantQuestion" name="question" type="text" maxlength="1200" autocomplete="off" placeholder="Ask a question about this property..." required>
                <button id="assistantSend" type="submit">Ask</button>
            </form>
            <p class="assistant-footnote">Answers can be incomplete. Confirm fees, policies, schedules, and account details in the portal or with the property administrator.</p>
        </section>
    </main>
</body>
</html>

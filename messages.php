<?php
// Redirect legacy messages.php to the unified Live Studio Chat (chat.php)
require_once __DIR__ . '/config.php';
$partner_name = trim($_GET['u'] ?? '');
$target = BASE_URL . '/chat.php' . ($partner_name !== '' ? ('?u=' . urlencode($partner_name)) : '');
header("Location: " . $target);
exit;

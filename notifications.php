<?php
// Redirect legacy notifications.php to Live Studio Chat
require_once __DIR__ . '/config.php';
header("Location: " . BASE_URL . "/chat.php");
exit;

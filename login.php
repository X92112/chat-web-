<?php
// Snapwall Login Page
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

if (is_logged_in()) {
    header("Location: " . BASE_URL . "/");
    exit;
}

$error = '';
$username_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf_or_fail(false);

    $username_val = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username_val === '' || $password === '') {
        $error = 'Please fill in both fields.';
    } else {
        $db = get_db();
        $stmt = $db->prepare("SELECT id, username, email, password_hash FROM users WHERE username = :u OR email = :e LIMIT 1");
        $stmt->execute(['u' => $username_val, 'e' => $username_val]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            login_user($user);
            $redirect = $_GET['redirect'] ?? (BASE_URL . '/');
            header("Location: " . $redirect);
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$page_title = 'log in — Nexly';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?></title>
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <meta name="color-scheme" content="dark">
  <link rel="preconnect" href="https://cdn.prod.website-files.com" crossorigin>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/assets/css/style.css') ?: time() ?>">
</head>
<body>

  <div class="auth-wrapper">
    <div style="text-align:center; margin-bottom: 24px;">
      <a href="<?= BASE_URL ?>/" class="brand-link" style="display:inline-flex; flex-direction:column; align-items:center; gap:6px;">
        <span class="bsx-brand-row">
          <span class="bsx-cube-mark" aria-hidden="true"></span>
          <span class="logo-text" style="font-size:32px;">NEXLY <span class="bsx-x-accent">X</span></span>
        </span>
      </a>
    </div>

    <div class="card auth-card">
      <h1 class="auth-title">Welcome Back</h1>
      <p class="auth-sub">Sign in to join the live voice, photo &amp; text chat.</p>

      <?php if ($error): ?>
        <div class="alert-box"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="<?= BASE_URL ?>/login.php<?= isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="form-group" style="margin-bottom:14px;">
          <label class="form-label" for="username">Username or email</label>
          <input type="text" id="username" name="username" class="form-control" value="<?= e($username_val) ?>" required autofocus autocomplete="username">
        </div>

        <div class="form-group" style="margin-bottom:20px;">
          <label class="form-label" for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password">
        </div>

        <button type="submit" class="btn btn-primary bsx-cta-btn" style="width:100%; margin-bottom:14px;">
          <span>Enter Live Chat</span>
          <?= bsx_arrow_icon() ?>
        </button>
      </form>

      <div style="text-align:center; font-size:13.5px; color:var(--muted); margin-top:14px;">
        new here? <a href="<?= BASE_URL ?>/register.php" style="font-weight:700; color:var(--ink); text-decoration:underline;">create an account on Nexly</a>
      </div>
    </div>
  </div>

</body>
</html>

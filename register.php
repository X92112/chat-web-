<?php
// Snapwall / Nexly Registration Page
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
$email_val = '';
$bio_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf_or_fail(false);

    $username_val = trim($_POST['username'] ?? '');
    $email_val = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $bio_val = trim($_POST['bio'] ?? '');

    // Never accept is_verified or is_admin from user forms
    if ($username_val === '' || $email_val === '' || $password === '') {
        $error = 'Please fill in all required fields (username, email, password).';
    } elseif (is_reserved_username($username_val)) {
        $error = 'This username is reserved and cannot be registered.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username_val)) {
        $error = 'Username must be 3 to 30 characters (letters, numbers, and underscores only).';
    } elseif (!filter_var($email_val, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $db = get_db();

        // Check for duplicates
        $chk = $db->prepare("SELECT username, email FROM users WHERE username = :u OR email = :e LIMIT 1");
        $chk->execute(['u' => $username_val, 'e' => $email_val]);
        $exists = $chk->fetch();

        if ($exists) {
            if (strcasecmp($exists['username'], $username_val) === 0) {
                $error = 'That username is already taken. Please pick another one!';
            } else {
                $error = 'That email is already registered. Try logging in instead.';
            }
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $db->prepare("
                INSERT INTO users (username, email, password_hash, bio, is_verified, is_admin, is_banned, must_change_password, last_seen, created_at)
                VALUES (:u, :e, :h, :b, 0, 0, 0, 0, NOW(), NOW())
            ");
            $ins->execute([
                'u' => $username_val,
                'e' => $email_val,
                'h' => $hash,
                'b' => $bio_val ?: null
            ]);

            $new_user_id = (int)$db->lastInsertId();
            login_user([
                'id'       => $new_user_id,
                'username' => $username_val,
                'email'    => $email_val
            ]);

            header("Location: " . BASE_URL . "/");
            exit;
        }
    }
}

$page_title = 'create account — Nexly';
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
  <style>
    .reg-wrap {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 36px 16px;
    }
    .reg-card {
      width: 100%;
      max-width: 440px;
      padding: 30px 28px;
    }
    .avatar-preview-box {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 12px 14px;
      background-color: rgba(255, 255, 255, 0.04);
      border: 1px solid var(--border-subtle);
      border-radius: var(--radius);
      margin-bottom: 20px;
    }
    .avatar-preview-circle {
      width: 58px;
      height: 58px;
      border-radius: 50%;
      border: 1px solid var(--border-strong);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      font-size: 20px;
      color: #FFFFFF;
      background-color: #0229D5;
      flex-shrink: 0;
      transition: background-color 0.25s ease, transform 0.15s ease;
    }
    .input-with-action {
      position: relative;
      display: flex;
      align-items: center;
      width: 100%;
    }
    .input-with-action .form-control {
      width: 100%;
      padding-right: 64px;
    }
    .btn-toggle-eye {
      position: absolute;
      right: 8px;
      background: rgba(255, 255, 255, 0.08);
      color: var(--ink);
      border: 1px solid var(--border-strong);
      border-radius: 4px;
      font-size: 11px;
      font-weight: 600;
      padding: 5px 9px;
      cursor: pointer;
      user-select: none;
    }
    .btn-toggle-eye:hover {
      background-color: var(--blue);
      color: #FFFFFF;
    }
    .pw-meter {
      display: flex;
      gap: 4px;
      height: 4px;
      margin-top: 6px;
    }
    .pw-seg {
      flex: 1;
      height: 100%;
      background-color: rgba(255, 255, 255, 0.12);
      border-radius: 2px;
      transition: background-color 0.2s ease;
    }
    .validation-hint {
      font-size: 11.5px;
      margin-top: 4px;
      display: flex;
      align-items: center;
      gap: 4px;
      font-weight: 500;
      color: var(--muted);
    }
    .char-count {
      font-size: 11.5px;
      color: var(--muted);
      text-align: right;
      margin-top: 3px;
    }
  </style>
</head>
<body>

  <div class="reg-wrap">
    <!-- Brand Logo -->
    <div style="text-align:center; margin-bottom: 20px;">
      <a href="<?= BASE_URL ?>/" class="brand-link" style="display:inline-flex; flex-direction:column; align-items:center; gap:6px;">
        <span class="bsx-brand-row">
          <span class="bsx-cube-mark" aria-hidden="true"></span>
          <span class="logo-text" style="font-size:32px;">NEXLY <span class="bsx-x-accent">X</span></span>
        </span>
      </a>
    </div>

    <!-- Registration Card -->
    <div class="card reg-card">
      <h1 class="auth-title" style="font-size:26px; margin-bottom:4px;">Create Chat Account</h1>
      <p class="auth-sub" style="margin-bottom:18px;">Join live public &amp; direct chat with voice notes and photos.</p>

      <?php if ($error): ?>
        <div class="alert-box"><?= e($error) ?></div>
      <?php endif; ?>

      <!-- Live Interactive Avatar Initials & Pastel Color Widget -->
      <div class="avatar-preview-box">
        <div id="live-avatar-preview" class="avatar-preview-circle">
          <?= get_initials($username_val ?: 'YOU') ?>
        </div>
        <div style="line-height:1.25; overflow:hidden;">
          <div style="font-size:11px; font-weight:800; text-transform:uppercase; color:var(--muted); letter-spacing:0.5px;">Your Nexly Avatar</div>
          <strong id="live-avatar-label" style="font-size:15px; color:var(--ink); display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
            <?= e($username_val ? '@' . $username_val : 'start typing your name...') ?>
          </strong>
          <span style="font-size:11.5px; color:var(--muted);">Each member gets a unique studio emblem & custom avatar</span>
        </div>
      </div>

      <form method="POST" action="<?= BASE_URL ?>/register.php" onsubmit="return validateForm()">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <!-- Username -->
        <div class="form-group" style="margin-bottom:14px;">
          <label class="form-label" for="username">Username</label>
          <input 
            type="text" 
            id="username" 
            name="username" 
            class="form-control" 
            value="<?= e($username_val) ?>" 
            required 
            autofocus 
            autocomplete="username" 
            maxlength="30"
            placeholder="e.g. dara_shutter"
            oninput="handleUsernameInput(this.value)"
          >
          <div id="username-hint" class="validation-hint">3-30 chars (letters, numbers, underscore)</div>
        </div>

        <!-- Email -->
        <div class="form-group" style="margin-bottom:14px;">
          <label class="form-label" for="email">Email</label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            class="form-control" 
            value="<?= e($email_val) ?>" 
            required 
            autocomplete="email" 
            placeholder="you@domain.com"
          >
        </div>

        <!-- Password -->
        <div class="form-group" style="margin-bottom:14px;">
          <label class="form-label" for="password">Password</label>
          <div class="input-with-action">
            <input 
              type="password" 
              id="password" 
              name="password" 
              class="form-control" 
              required 
              autocomplete="new-password"
              placeholder="At least 6 characters"
              oninput="handlePasswordInput(this.value)"
            >
            <button type="button" class="btn-toggle-eye" id="btn-toggle-pw" onclick="togglePasswordVisibility()">Show</button>
          </div>
          <div class="pw-meter">
            <div id="pw-seg-1" class="pw-seg"></div>
            <div id="pw-seg-2" class="pw-seg"></div>
            <div id="pw-seg-3" class="pw-seg"></div>
          </div>
          <div id="pw-hint" class="validation-hint">At least 6 characters</div>
        </div>

        <!-- Bio (Optional) -->
        <div class="form-group" style="margin-bottom:16px;">
          <div style="display:flex; justify-content:space-between; align-items:baseline;">
            <label class="form-label" for="bio" style="margin-bottom:0;">Bio (optional)</label>
            <span id="bio-count" class="char-count">0 / 150</span>
          </div>
          <input 
            type="text" 
            id="bio" 
            name="bio" 
            class="form-control" 
            value="<?= e($bio_val) ?>" 
            maxlength="150" 
            placeholder="film shooter, city walks, morning coffee..."
            oninput="handleBioInput(this.value)"
            style="margin-top:6px;"
          >
        </div>

        <!-- Community Pledge Checkbox -->
        <label class="pledge-box">
          <input type="checkbox" id="pledge-agree" required checked>
          <span>I promise to chat respectfully, treat other members kindly, and follow community guidelines.</span>
        </label>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary bsx-cta-btn" style="width:100%; font-size:15px; margin-bottom:14px;">
          <span>Create Chat Account</span>
          <?= bsx_arrow_icon() ?>
        </button>
      </form>

      <div style="text-align:center; font-size:13.5px; color:var(--muted); margin-top:10px;">
        already on Nexly? <a href="<?= BASE_URL ?>/login.php" style="font-weight:700; color:var(--ink); text-decoration:underline;">sign in here</a>
      </div>
    </div>
  </div>

  <script>
    const RESERVED_NAMES = ['rt', 'admin', 'ceo', 'snapwall', 'support', 'official'];
    const PASTEL_PALETTE = [
      '#0229D5', '#1E3A8A', '#1E293B', '#312E81',
      '#1D4ED8', '#0369A1', '#0F172A', '#3730A3',
      '#1E40AF', '#0C4A6E', '#111827', '#2563EB'
    ];

    // IEEE 802.3 CRC32 matching PHP crc32()
    function crc32(str) {
      let c;
      const table = [];
      for (let n = 0; n < 256; n++) {
        c = n;
        for (let k = 0; k < 8; k++) {
          c = (c & 1) ? (0xEDB88320 ^ (c >>> 1)) : (c >>> 1);
        }
        table[n] = c;
      }
      let crc = 0 ^ (-1);
      for (let i = 0; i < str.length; i++) {
        crc = (crc >>> 8) ^ table[(crc ^ str.charCodeAt(i)) & 0xFF];
      }
      return (crc ^ (-1)) >>> 0;
    }

    function getAvatarColor(name) {
      const clean = name.trim().toLowerCase();
      if (!clean) return PASTEL_PALETTE[0];
      const hash = Math.abs(crc32(clean));
      return PASTEL_PALETTE[hash % PASTEL_PALETTE.length];
    }

    function getInitials(name) {
      const clean = name.trim();
      if (!clean) return '??';
      if (clean.length === 1) return clean.toUpperCase();
      if (/[ _-]/.test(clean)) {
        const parts = clean.split(/[ _-]+/);
        return ((parts[0][0] || '') + (parts[1] ? parts[1][0] : '')).toUpperCase();
      }
      return clean.substring(0, 2).toUpperCase();
    }

    // Live Avatar Update
    function handleUsernameInput(val) {
      const clean = val.trim();
      const circle = document.getElementById('live-avatar-preview');
      const label = document.getElementById('live-avatar-label');
      const hint = document.getElementById('username-hint');

      if (clean) {
        circle.textContent = getInitials(clean);
        circle.style.backgroundColor = getAvatarColor(clean);
        label.textContent = '@' + clean;

        const lower = clean.toLowerCase();
        if (RESERVED_NAMES.includes(lower)) {
          hint.textContent = '✕ This username is reserved for the Nexly team';
          hint.style.color = '#F87171';
        } else if (!/^[a-zA-Z0-9_]{3,30}$/.test(clean)) {
          hint.textContent = '✕ 3-30 chars (letters, numbers, underscore only)';
          hint.style.color = '#F87171';
        } else {
          hint.textContent = '✓ Username is valid and available';
          hint.style.color = 'var(--green)';
        }
      } else {
        circle.textContent = '??';
        circle.style.backgroundColor = '#0229D5';
        label.textContent = 'start typing your name...';
        hint.textContent = '3-30 chars (letters, numbers, underscore)';
        hint.style.color = 'var(--muted)';
      }
    }

    // Password strength & hints
    function handlePasswordInput(pw) {
      const seg1 = document.getElementById('pw-seg-1');
      const seg2 = document.getElementById('pw-seg-2');
      const seg3 = document.getElementById('pw-seg-3');
      const hint = document.getElementById('pw-hint');

      seg1.style.backgroundColor = 'rgba(255, 255, 255, 0.12)';
      seg2.style.backgroundColor = 'rgba(255, 255, 255, 0.12)';
      seg3.style.backgroundColor = 'rgba(255, 255, 255, 0.12)';

      if (!pw) {
        hint.textContent = 'At least 6 characters';
        hint.style.color = 'var(--muted)';
        return;
      }

      if (pw.length < 6) {
        seg1.style.backgroundColor = '#EF4444';
        hint.textContent = `Too short (${pw.length}/6 characters)`;
        hint.style.color = '#F87171';
      } else if (pw.length < 9) {
        seg1.style.backgroundColor = '#F59E0B';
        seg2.style.backgroundColor = '#F59E0B';
        hint.textContent = 'Good password (add numbers or symbols to strengthen)';
        hint.style.color = '#FBBF24';
      } else {
        seg1.style.backgroundColor = 'var(--green)';
        seg2.style.backgroundColor = 'var(--green)';
        seg3.style.backgroundColor = 'var(--green)';
        hint.textContent = '✓ Strong password';
        hint.style.color = 'var(--green)';
      }
    }

    // Password show/hide toggle
    function togglePasswordVisibility() {
      const pwInput = document.getElementById('password');
      const btn = document.getElementById('btn-toggle-pw');
      if (pwInput.type === 'password') {
        pwInput.type = 'text';
        btn.textContent = 'Hide';
      } else {
        pwInput.type = 'password';
        btn.textContent = 'Show';
      }
    }

    // Bio character counter
    function handleBioInput(bio) {
      const countEl = document.getElementById('bio-count');
      if (countEl) countEl.textContent = `${bio.length} / 150`;
    }

    function validateForm() {
      const username = document.getElementById('username').value.trim();
      const lower = username.toLowerCase();
      if (RESERVED_NAMES.includes(lower)) {
        alert('This username is reserved for the Nexly team.');
        return false;
      }
      const pw = document.getElementById('password').value;
      if (pw.length < 6) {
        alert('Password must be at least 6 characters long.');
        return false;
      }
      return true;
    }

    // Initial trigger if values prefilled
    window.addEventListener('DOMContentLoaded', () => {
      const u = document.getElementById('username');
      if (u && u.value) handleUsernameInput(u.value);
    });
  </script>

</body>
</html>

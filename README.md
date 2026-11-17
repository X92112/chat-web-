# Nexly (Snapwall) — Retro-Ink Social Photo Wall & Live Chat

Nexly (formerly Snapwall) is a complete, working social photo and real-time live chat web application built with **plain PHP 8 (no frameworks)**, **MySQL (PDO)**, and **vanilla JavaScript & CSS**. It features a hand-crafted brutalist retro-ink aesthetic matching the project design specifications, with real-time updates via **Server-Sent Events (SSE)**.

---

## 🎨 Design System & Palette

- **Font**: Bricolage Grotesque (Google Fonts, weights 400 / 600 / 800)
- **Page Background**: `#F2F0EB`
- **Ink**: `#141414`
- **Muted text**: `#55534D`
- **Accent Orange**: `#FF5A36` (action buttons with dark ink text)
- **Highlighter Yellow**: `#FFE14D` (active menu pill, pending posts alert, self chat bubbles)
- **Live Green**: `#1E9E5A` (real-time indicators & online status)
- **Verified Blue**: `#1D9BF0` (blue check badge inline SVG)
- **Cards**: White, `2px solid #141414`, `6px radius`, hard shadow `4px 4px 0 #141414`
- **Buttons**: `2px solid #141414`, `6px radius`, min-height `44px`
- **No blurry shadows, no gradients, no emojis, no icon libraries**

---

## ⚡ Key Features

### 1. Live Chat For Everyone (`chat.php`)
- **Two Columns Layout**:
  - **Left**: Conversation list with a pinned **# Lobby** (public room for all logged-in users) and 1-to-1 direct-message conversations with unread badges.
  - **Right**: Message pane with real-time delivery.
- **Lobby**:
  - Loads the last 50 messages, with `"Load older messages"` pagination when scrolling up.
  - Real-time messages appear instantly for everyone online.
  - Max 500 characters, rate limited to max 1 message per second per user.
  - Users can delete their own messages; admins can delete any message.
- **Direct Messages (1-to-1)**:
  - Real-time private chats with unread count badges.
  - **"Seen" Receipts**: Shows `✓ Seen` under messages once opened by the recipient.
  - Mandatory **"Message"** button on every profile page.
- **Voice Notes in Chat**:
  - Live in-browser microphone recording via `MediaRecorder` API with a pulsing red REC indicator, live timer, and waveform animations.
  - Flexible audio upload support (`.webm`, `.ogg`, `.wav`, `.mp3`, `.m4a`).
  - Custom retro-ink brutalist player inside chat bubbles featuring Play/Pause button, duration display, and live scrubber progress bar.
- **Chat Pictures**:
  - Attach images (`.jpg`, `.png`, `.webp`, `.gif`, max 5 MB) in both Lobby and DMs with optional captions.
  - Drag-and-drop, file picker, and direct clipboard image paste (`Ctrl+V`) into composer.
  - Automatic 600px thumbnail generation via PHP GD library.
  - Integrated Lightbox photo modal for full-size viewing and downloading.
- **Typing Indicators**:
  - Displays `"User is typing..."` in the Lobby and in DMs.
- **Chat Bubbles Style**:
  - My messages: yellow `#FFE14D`, right aligned.
  - Others' messages: white `#FFFFFF`, left aligned.
  - `2px` ink border, `6px` radius, hard shadow `3px 3px 0 #141414`.
  - Message input with dashed underline and orange Send button (Enter sends, Shift+Enter makes a new line). Full XSS escaping.

### 2. Interactive Registration Page (`register.php`)
- **Live Avatar Preview**: Real-time client-side IEEE 802.3 CRC32 calculation matching server-side deterministic pastel colors and initials as the user types their username.
- **Dynamic Field Feedback**: Live validation of username characters (3-30 chars, alphanumeric + underscore) with instant reserved username check.
- **Password Strength Meter**: 3-stage visual strength meter (Too short / Good / Strong) with Show/Hide toggle.
- **Bio Counter**: Live character counter (0 / 150).
- **Community Pledge**: Checkbox acknowledging community guidelines.

### 3. Blue Verified Badge & Admin Panel (`admin.php`)
- **Blue Check Badge**:
  - Blue circle `#1D9BF0` with white check inline SVG.
  - Appears right after the username **everywhere**: posts, comments, profile, search results, chat messages, online list, notifications, and "people to follow".
- **Strict Anti-Tampering Security**:
  - Users can **never** set `is_verified` or `is_admin` themselves; attempts via POST or forms are strictly ignored.
  - Only authenticated admins can manage users from `/admin.php`.
- **Admin Control Panel (`admin.php`)**:
  - Lists all users with **Verify / Unverify** and **Ban / Unban** toggles.
  - Admins can delete any chat message or post across the platform.
- **Reserved Usernames**:
  - The following usernames cannot be registered by normal users: `rt`, `admin`, `ceo`, `snapwall`, `support`, `official` (case-insensitive).

### 3. CEO Account & Password Security
- **RT Account**:
  - Pre-seeded CEO account (`username: "RT"`, `email: "rt@snapwall.local"`).
  - Seeded password `"123"` (never stored in plain text, hashed via `password_hash()`).
  - Flagged with `must_change_password = 1`.
  - Displays a prominent yellow warning banner on every page: *"Your password is too weak, change it before going online."* until changed in `/settings.php`.
- **Settings Page (`settings.php`)**:
  - Secure password change flow (current password verification, new password min 6 chars) and profile bio editing.

### 4. Real-Time Engine (`stream.php`)
- **Single Channel-Based `events` Table**:
  - Columns: `id` (AUTO_INCREMENT), `channel` (`'lobby'`, `'global'`, `'user:<id>'`), `user_id`, `type`, `payload` (JSON), `created_at`.
  - Composite index on `(channel, id)`.
- **Server-Sent Events Loop**:
  - Calls `session_write_close()` before entering loop to prevent PHP session blocking.
  - Streams only events with `id > Last-Event-ID` for the channels visible to the current user.
  - When no `Last-Event-ID` is sent, starts from the current max id.
  - Flushes each event immediately and ends after ~25 seconds for clean client reconnection.
  - Automatic cleanup deletes events older than 1 hour.
- **Graceful Fallback**:
  - If EventSource encounters 3 connection errors, the client automatically falls back to polling `/actions/poll.php` every 2 seconds.

---

## 📁 Directory Structure

```text
/snapwall  (or /nexly)
├── index.php                # Home feed (followed + own posts)
├── explore.php              # Explore feed & search (people, captions, tags)
├── profile.php              # Profile (/profile.php?u=name), stats, grid, "Message" button
├── post.php                 # Single post view (/post.php?id=X), comments, share
├── chat.php                 # Live Chat Hub (Pinned Lobby + 1-to-1 DMs)
├── messages.php             # DM redirect/compatibility view
├── notifications.php        # Notification center with verified badges
├── settings.php             # Account security & password change
├── admin.php                # Admin Control Panel (Verify, Ban, Moderation)
├── login.php                # Login form with CSRF protection
├── register.php             # User registration (reserved usernames check)
├── logout.php               # Session termination & redirect
├── stream.php               # Channel-based Server-Sent Events (SSE) endpoint
├── config.php               # Configuration constants (DB, paths, limits)
├── db.php                   # PDO MySQL singleton connection
├── setup.sql                # Database schema with new columns and tables
├── seed.php                 # One-time database seeder (RT CEO account)
├── test_suite.php           # Core 13-feature automated test suite
├── acceptance_test.php      # 3-browser session acceptance test suite
├── README.md                # Setup documentation
├── actions/
│   ├── chat_message.php     # Send Lobby & DM messages (rate limit 1/sec)
│   ├── chat_action.php      # Typing indicator, mark seen, delete message, load older
│   ├── admin_action.php     # Admin verify & ban operations
│   ├── post.php             # Photo upload & GD 600px thumbnailing
│   ├── like.php             # Like toggle & notification
│   ├── comment.php          # Add comment with rate limiting
│   ├── delete.php           # Delete post/comment (author or admin)
│   ├── follow.php           # Follow / unfollow toggle
│   ├── message.php          # Direct message handler
│   └── poll.php             # Fallback polling (2s) and 30s heartbeat
├── includes/
│   ├── auth.php             # Session auth, require_login(), require_admin()
│   ├── helpers.php          # Blue badge, avatars, time_ago, GD resizer, channel events
│   ├── header.php           # HTML head, Bricolage font, weak password banner
│   ├── footer.php           # New post upload modal & toast notification
│   ├── sidebar_left.php     # Brand logo, Chat & DMs, Admin Panel, Settings
│   ├── sidebar_right.php    # Live online users & suggested follow cards
│   └── post_card.php        # Ink post card with verified badge and admin delete
├── assets/
│   ├── css/style.css        # Retro-ink stylesheet matching mockups
│   └── js/
│       ├── app.js           # Modal drag-and-drop, likes, comments, share copy
│       ├── chat.js          # Live chat scrolling, typing indicator, seen receipts
│       └── stream.js        # Channel-aware EventSource client & 2s polling
└── uploads/
    ├── .htaccess            # Security: strictly disables PHP execution
    └── ...                  # Uploaded photos & 600px GD thumbnails
```

---

## 🚀 Step-by-Step XAMPP Setup Guide

### 1. Verify XAMPP Services
1. Open the **XAMPP Control Panel**.
2. Start **Apache** and **MySQL**.
3. Ensure PHP's GD extension is enabled in `C:\xampp\php\php.ini` (`extension=gd`).

### 2. Import Database Schema
Import `setup.sql` via PowerShell or phpMyAdmin:
```powershell
Get-Content "C:\xampp\htdocs\snapwall\setup.sql" | & "C:\xampp\mysql\bin\mysql.exe" -u root
```

### 3. Run the Database Seeder
> **IMPORTANT NOTE**: Run `seed.php` once from the browser or command line, then delete it.

Run via CLI:
```powershell
& "C:\xampp\php\php.exe" "C:\xampp\htdocs\snapwall\seed.php"
```
Or visit in browser:
```text
http://localhost/snapwall/seed.php
```
After running once, delete `seed.php` for security.

### 4. Access the Application
Open either of the following URLs in your browser:
👉 **[http://localhost/snapwall](http://localhost/snapwall)**  
👉 **[http://localhost/nexly](http://localhost/nexly)**  

---

## 🔑 Pre-Seeded Accounts

| Username | Email | Initial Password | Role & Badges | Notes |
| :--- | :--- | :--- | :--- | :--- |
| **RT** | `rt@snapwall.local` | `123` | **CEO & Admin**, Verified | Prompts yellow warning banner until password changed in Settings |
| **Dara** | `dara@snapwall.local` | `password123` | Normal User | Urban photographer, follows RT |
| **Sokha** | `sokha@snapwall.local` | `password123` | Normal User | Morning market photographer, follows RT |

---

## 🧪 Acceptance Test Suite

Run the automated test suite testing 3 simultaneous sessions (**RT**, **Dara**, **Sokha**):
```powershell
& "C:\xampp\php\php.exe" "C:\xampp\htdocs\snapwall\acceptance_test.php"
```

### Verified Test Results:
- **RT Posts a Photo**: Broadcasts on channel `global`; Dara and Sokha receive `"1 new post"` instantly.
- **Lobby Chat**: RT sends a message in the Lobby; appears in all 3 browser sessions within 1 second with the **Blue Verified Badge**.
- **Direct Messaging**: RT sends a DM to Dara; Dara receives it instantly with unread badge increment; Dara opens the chat and `"✓ Seen"` is reported back to RT in real time.
- **Anti-Tampering**: Normal users cannot self-verify by sending `is_verified=1` in form submissions.
- **Reserved Usernames**: Registration of `"ceo"`, `"RT"`, or `"admin"` is rejected.
- **Admin Privileges**: Only admins can access `admin.php` and verify/unverify or ban/unban users.

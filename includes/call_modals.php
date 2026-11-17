<?php
// Nexly X — Real-Time Calling Modals & Media Elements
// Reusable across all pages (Chat, Landing, Profile, Settings)
?>
  <!-- 1. Incoming Call Screen -->
  <div id="incoming-call-modal" class="native-call-overlay" style="display:none; position:fixed; inset:0; z-index:999999; background:rgba(28,27,24,0.85); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px); align-items:center; justify-content:center; padding:16px;" role="dialog" aria-modal="true" aria-labelledby="incoming-call-name">
    <div class="native-call-card" style="width:100%; max-width:380px; min-height:580px; background:#1C1B18; border:1px solid #2D2B26; border-radius:32px; box-shadow:0 30px 80px rgba(0,0,0,0.45); display:flex; flex-direction:column; align-items:center; justify-content:space-between; padding:48px 24px 38px; text-align:center; position:relative; overflow:hidden; margin:auto;">
      
      <!-- Top Area: Avatar, Name, Status -->
      <div class="native-call-top" style="display:flex; flex-direction:column; align-items:center; width:100%;">
        <div class="native-avatar-wrapper" style="position:relative; width:96px; height:96px; margin-bottom:18px; display:flex; align-items:center; justify-content:center;">
          <div id="incoming-caller-avatar" class="native-avatar-slot" style="position:relative; z-index:5; border-radius:50%;">
            <!-- Initial circle or image -->
          </div>
        </div>

        <h2 id="incoming-call-name" class="native-caller-name" style="font-family:var(--font-brand, 'Space Grotesk', sans-serif); font-size:24px; font-weight:700; color:#FFFFFF; margin:0 0 6px;">Caller Name</h2>
        <div id="incoming-call-status-timer" class="native-call-timer" style="font-size:15px; font-weight:600; color:#1F7A4D; margin:0 0 4px;">Incoming call...</div>
        <div id="incoming-call-subtitle" class="native-call-sub" style="font-size:13px; color:#A8A296; margin:0 0 16px;">mobile · @username</div>
      </div>

      <!-- Accept / Decline Action Buttons -->
      <div class="native-incoming-actions" style="display:flex; align-items:center; justify-content:space-around; width:100%; max-width:280px; margin:32px auto 10px;">
        <div class="native-action-col" style="display:flex; flex-direction:column; align-items:center; gap:8px;">
          <button type="button" class="native-action-btn native-btn-decline" onclick="declineIncomingCall()" title="Decline call" aria-label="Decline call" style="width:68px; height:68px; border-radius:50%; border:none; background:#D8401F; color:#FFFFFF; display:flex; align-items:center; justify-content:center; cursor:pointer;">
            <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M10.68 13.31a16 16 0 0 0 3.41 2.6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7 2 2 0 0 1 1.72 2v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.42 19.42 0 0 1-6-6 19.8 19.8 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91"/>
              <line x1="23" y1="1" x2="1" y2="23"/>
            </svg>
          </button>
          <span class="native-btn-sublabel" style="font-size:12.5px; font-weight:500; color:#A8A296;">Decline</span>
        </div>

        <div class="native-action-col" style="display:flex; flex-direction:column; align-items:center; gap:8px;">
          <button type="button" class="native-action-btn native-btn-accept" onclick="acceptIncomingCall()" title="Accept call" aria-label="Accept call" style="width:68px; height:68px; border-radius:50%; border:none; background:#1F7A4D; color:#FFFFFF; display:flex; align-items:center; justify-content:center; cursor:pointer; position:relative;">
            <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor">
              <path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 0 0-1.01.24l-1.57 1.97c-2.83-1.44-5.15-3.75-6.59-6.59l1.97-1.57c.28-.28.36-.67.25-1.02A11.36 11.36 0 0 1 8.5 4.3c0-.55-.45-1-1-1H4.11c-.55 0-1 .45-1 1C3.11 13.9 11.1 21.89 21 21.89c.55 0 1-.45 1-1v-3.39c0-.55-.45-1-.99-1.12z"/>
            </svg>
          </button>
          <span class="native-btn-sublabel" style="font-size:12.5px; font-weight:500; color:#A8A296;">Accept</span>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. Active Call Screen -->
  <div id="active-call-modal" class="native-call-overlay" style="display:none; position:fixed; inset:0; z-index:999999; background:rgba(28,27,24,0.85); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px); align-items:center; justify-content:center; padding:16px;" role="dialog" aria-modal="true">
    <div class="native-call-card" id="active-call-card" style="width:100%; max-width:380px; min-height:580px; background:#1C1B18; border:1px solid #2D2B26; border-radius:32px; box-shadow:0 30px 80px rgba(0,0,0,0.45); display:flex; flex-direction:column; align-items:center; justify-content:space-between; padding:48px 24px 38px; text-align:center; position:relative; overflow:hidden; margin:auto;">
      
      <!-- Top Section: Avatar, Contact Name, Timer, Subtitle -->
      <div class="native-call-top" id="active-call-top-area" style="display:flex; flex-direction:column; align-items:center; width:100%;">
        <div class="native-avatar-wrapper" style="position:relative; width:96px; height:96px; margin-bottom:18px; display:flex; align-items:center; justify-content:center;">
          <div id="active-call-avatar" class="native-avatar-slot" style="position:relative; z-index:5; border-radius:50%;">
            <!-- Initial circle e.g. "SL" -->
          </div>
        </div>

        <h2 id="active-call-party-name" class="native-caller-name" style="font-family:var(--font-brand, 'Space Grotesk', sans-serif); font-size:24px; font-weight:700; color:#FFFFFF; margin:0 0 6px;">Sokha Lim</h2>
        <div id="active-call-status-timer" class="native-call-timer" style="font-size:15px; font-weight:600; color:#1F7A4D; margin:0 0 4px; font-variant-numeric:tabular-nums;">calling...</div>
        <div id="active-call-subtitle" class="native-call-sub" style="font-size:13px; color:#A8A296; margin:0 0 16px;">mobile · @username</div>
      </div>

      <!-- Video Call Stage (Appears when Video is toggled ON) -->
      <div id="active-call-video-stage" class="native-video-stage" style="display:none; width:100%; height:260px; border-radius:18px; overflow:hidden; background:#111; position:relative; margin:10px 0 16px; border:1px solid #2D2B26;">
        <video id="remote-call-video" class="native-remote-video" autoplay playsinline style="width:100%; height:100%; object-fit:cover; display:block;"></video>
        <div class="native-local-pip" style="position:absolute; top:10px; right:10px; width:84px; height:112px; border-radius:12px; border:1.5px solid #1F7A4D; overflow:hidden; background:#18191f; z-index:10; box-shadow:0 6px 18px rgba(0,0,0,0.5);">
          <video id="local-call-video" autoplay playsinline muted style="width:100%; height:100%; object-fit:cover; transform:scaleX(-1);"></video>
          <span class="native-pip-label" style="position:absolute; bottom:4px; left:6px; background:rgba(0,0,0,0.7); color:#FFFFFF; font-size:9px; padding:1px 5px; border-radius:4px; font-weight:600;">You</span>
        </div>
      </div>

      <!-- 6-Button Grid (2 rows x 3 columns) -->
      <div class="native-controls-grid" style="display:grid; grid-template-columns:repeat(3, 1fr); gap:18px 14px; width:100%; max-width:300px; margin:16px auto 22px;">
        <!-- 1. Mute -->
        <div class="native-grid-item" style="display:flex; flex-direction:column; align-items:center; gap:6px;">
          <button type="button" id="call-btn-mute" class="native-round-btn" onclick="toggleCallMute()" title="Mute microphone" aria-label="Mute microphone" style="width:64px; height:64px; border-radius:50%; background:#2D2B26; border:1px solid #3A3731; color:#FFFFFF; display:flex; align-items:center; justify-content:center; cursor:pointer; padding:0;">
            <svg class="icon-mic-on" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
              <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
              <line x1="12" y1="19" x2="12" y2="23"/>
              <line x1="8" y1="23" x2="16" y2="23"/>
            </svg>
            <svg class="icon-mic-off" style="display:none;" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <line x1="1" y1="1" x2="23" y2="23"/>
              <path d="M9 9v3a3 3 0 0 0 5.12 2.12M15 9.34V4a3 3 0 0 0-5.94-.6"/>
              <path d="M17 16.95A7 7 0 0 1 5 12v-2m14 0v2a7 7 0 0 1-.11 1.23"/>
              <line x1="12" y1="19" x2="12" y2="23"/>
              <line x1="8" y1="23" x2="16" y2="23"/>
            </svg>
          </button>
          <span class="native-item-label" id="lbl-mute" style="font-size:12px; font-weight:500; color:#A8A296;">Mute</span>
        </div>

        <!-- 2. Keypad -->
        <div class="native-grid-item" style="display:flex; flex-direction:column; align-items:center; gap:6px;">
          <button type="button" id="call-btn-keypad" class="native-round-btn" onclick="toggleCallKeypad()" title="Dialpad / Keypad" aria-label="Dialpad / Keypad" style="width:64px; height:64px; border-radius:50%; background:#2D2B26; border:1px solid #3A3731; color:#FFFFFF; display:flex; align-items:center; justify-content:center; cursor:pointer; padding:0;">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
              <rect x="4" y="4" width="3.5" height="3.5" rx="1"/>
              <rect x="10.25" y="4" width="3.5" height="3.5" rx="1"/>
              <rect x="16.5" y="4" width="3.5" height="3.5" rx="1"/>
              <rect x="4" y="10.25" width="3.5" height="3.5" rx="1"/>
              <rect x="10.25" y="10.25" width="3.5" height="3.5" rx="1"/>
              <rect x="16.5" y="10.25" width="3.5" height="3.5" rx="1"/>
              <rect x="4" y="16.5" width="3.5" height="3.5" rx="1"/>
              <rect x="10.25" y="16.5" width="3.5" height="3.5" rx="1"/>
              <rect x="16.5" y="16.5" width="3.5" height="3.5" rx="1"/>
            </svg>
          </button>
          <span class="native-item-label" style="font-size:12px; font-weight:500; color:#A8A296;">Keypad</span>
        </div>

        <!-- 3. Speaker -->
        <div class="native-grid-item" style="display:flex; flex-direction:column; align-items:center; gap:6px;">
          <button type="button" id="call-btn-speaker" class="native-round-btn active-green" onclick="toggleCallSpeaker()" title="Speaker" aria-label="Speaker" style="width:64px; height:64px; border-radius:50%; background:#1F7A4D; border:1px solid #1F7A4D; color:#FFFFFF; display:flex; align-items:center; justify-content:center; cursor:pointer; padding:0;">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5" fill="currentColor"/>
              <path d="M15.54 8.46a5 5 0 0 1 0 7.07"/>
              <path d="M19.07 4.93a10 10 0 0 1 0 14.14"/>
            </svg>
          </button>
          <span class="native-item-label" style="font-size:12px; font-weight:500; color:#A8A296;">Speaker</span>
        </div>

        <!-- 4. Add call -->
        <div class="native-grid-item" style="display:flex; flex-direction:column; align-items:center; gap:6px;">
          <button type="button" id="call-btn-add" class="native-round-btn" onclick="toggleCallAdd()" title="Add another user" aria-label="Add call" style="width:64px; height:64px; border-radius:50%; background:#2D2B26; border:1px solid #3A3731; color:#FFFFFF; display:flex; align-items:center; justify-content:center; cursor:pointer; padding:0;">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
              <circle cx="8.5" cy="7" r="4"/>
              <line x1="20" y1="8" x2="20" y2="14"/>
              <line x1="23" y1="11" x2="17" y2="11"/>
            </svg>
          </button>
          <span class="native-item-label" style="font-size:12px; font-weight:500; color:#A8A296;">Add call</span>
        </div>

        <!-- 5. Video -->
        <div class="native-grid-item" style="display:flex; flex-direction:column; align-items:center; gap:6px;">
          <button type="button" id="call-btn-video" class="native-round-btn" onclick="toggleCallVideo()" title="Switch to video call" aria-label="Switch to video call" style="width:64px; height:64px; border-radius:50%; background:#2D2B26; border:1px solid #3A3731; color:#FFFFFF; display:flex; align-items:center; justify-content:center; cursor:pointer; padding:0;">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="23 7 16 12 23 17 23 7"/>
              <rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
            </svg>
          </button>
          <span class="native-item-label" style="font-size:12px; font-weight:500; color:#A8A296;">Video</span>
        </div>

        <!-- 6. Hold -->
        <div class="native-grid-item" style="display:flex; flex-direction:column; align-items:center; gap:6px;">
          <button type="button" id="call-btn-hold" class="native-round-btn" onclick="toggleCallHold()" title="Hold call" aria-label="Hold call" style="width:64px; height:64px; border-radius:50%; background:#2D2B26; border:1px solid #3A3731; color:#FFFFFF; display:flex; align-items:center; justify-content:center; cursor:pointer; padding:0;">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
              <line x1="8" y1="5" x2="8" y2="19"/>
              <line x1="16" y1="5" x2="16" y2="19"/>
            </svg>
          </button>
          <span class="native-item-label" id="lbl-hold" style="font-size:12px; font-weight:500; color:#A8A296;">Hold</span>
        </div>
      </div>

      <!-- Bottom Center: Accent Red End Call Button -->
      <div class="native-bottom-hangup" style="display:flex; flex-direction:column; align-items:center; gap:6px; margin-top:auto;">
        <button type="button" class="native-btn-hangup" onclick="endActiveCall()" title="End call" aria-label="End call" style="width:68px; height:68px; border-radius:50%; background:#D8401F; border:none; color:#FFFFFF; display:flex; align-items:center; justify-content:center; cursor:pointer;">
          <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor">
            <path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 0 0-1.01.24l-1.57 1.97c-2.83-1.44-5.15-3.75-6.59-6.59l1.97-1.57c.28-.28.36-.67.25-1.02A11.36 11.36 0 0 1 8.5 4.3c0-.55-.45-1-1-1H4.11c-.55 0-1 .45-1 1C3.11 13.9 11.1 21.89 21 21.89c.55 0 1-.45 1-1v-3.39c0-.55-.45-1-.99-1.12z" transform="rotate(135 12 12)"/>
          </svg>
        </button>
        <span class="native-hangup-label" style="font-size:12.5px; font-weight:500; color:#A8A296;">End call</span>
      </div>

    </div>
  </div>

  <!-- Hidden Remote Audio Playback Element -->
  <audio id="remote-call-audio" autoplay playsinline></audio>

  <!-- Hidden Canvas for Fallback Live Video Stream -->
  <canvas id="call-video-canvas" width="640" height="480" style="display:none;"></canvas>

  <!-- Real-Time Stream & WebRTC Scripts (Auto Cache-Busting) -->
  <script src="<?= BASE_URL ?>/assets/js/stream.js?v=<?= @filemtime(__DIR__ . '/../assets/js/stream.js') ?: time() ?>"></script>
  <script src="<?= BASE_URL ?>/assets/js/call.js?v=<?= @filemtime(__DIR__ . '/../assets/js/call.js') ?: time() ?>"></script>

// Snapwall / Nexly Live Chat JavaScript (Text, Pictures & Voice Notes)

(function() {
  const scrollArea = document.getElementById('chat-messages-area');
  const textarea = document.getElementById('chat-textarea');
  const typingBox = document.getElementById('typing-indicator');
  const typingText = document.getElementById('typing-text');
  const composerForm = document.getElementById('chat-composer-form');
  const previewDrawer = document.getElementById('chat-attachment-preview');
  const previewImg = document.getElementById('chat-preview-img');
  const previewVoice = document.getElementById('chat-preview-voice');
  const previewName = document.getElementById('chat-preview-name');
  const previewSize = document.getElementById('chat-preview-size');
  const recordingPanel = document.getElementById('chat-recording-panel');
  const recTimerEl = document.getElementById('rec-timer');
  const lightboxModal = document.getElementById('chat-lightbox-modal');
  const lightboxImg = document.getElementById('chat-lightbox-img');
  const lightboxDownload = document.getElementById('chat-lightbox-download');

  let typingTimer = null;
  let lastTypingSent = 0;

  // Attachment state
  let currentChatImageFile = null;
  let currentVoiceBlob = null;
  let currentVoiceDuration = 0;

  // MediaRecorder state
  let mediaRecorder = null;
  let recStream = null;
  let recChunks = [];
  let recStartTime = 0;
  let recInterval = null;

  // Audio Playback state
  let currentlyPlayingAudio = null;
  let currentlyPlayingBtn = null;

  // Auto-scroll to bottom reliably
  function scrollToBottom(smooth = false) {
    if (scrollArea) {
      if (smooth) {
        scrollArea.scrollTo({ top: scrollArea.scrollHeight, behavior: 'smooth' });
      } else {
        scrollArea.scrollTop = scrollArea.scrollHeight;
      }
    }
  }
  scrollToBottom(false);
  window.addEventListener('load', () => setTimeout(() => scrollToBottom(false), 80));
  requestAnimationFrame(() => scrollToBottom(false));

  // Auto-expand textarea
  if (textarea) {
    textarea.addEventListener('input', function() {
      this.style.height = 'auto';
      this.style.height = Math.min(120, this.scrollHeight) + 'px';
    });

    // Paste image from clipboard
    textarea.addEventListener('paste', function(e) {
      const items = (e.clipboardData || e.originalEvent.clipboardData).items;
      for (let i = 0; i < items.length; i++) {
        if (items[i].type.indexOf('image') !== -1) {
          const blob = items[i].getAsFile();
          if (blob) {
            handleImageFile(blob);
            e.preventDefault();
            break;
          }
        }
      }
    });
  }

  // Drag and drop image onto chat messages or composer
  if (scrollArea) {
    ['dragenter', 'dragover'].forEach(evt => {
      scrollArea.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); }, false);
    });
    scrollArea.addEventListener('drop', function(e) {
      e.preventDefault();
      e.stopPropagation();
      const files = e.dataTransfer.files;
      if (files && files.length > 0 && files[0].type.startsWith('image/')) {
        handleImageFile(files[0]);
      }
    }, false);
  }

  // Keydown handler: Enter sends, Shift+Enter new line
  window.handleChatKeydown = function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSendChatMessage(e);
    }
  };

  // Typing notification emitter (throttled to 1.5s)
  window.handleChatTyping = function() {
    const now = Date.now();
    if (now - lastTypingSent < 1500) return;
    lastTypingSent = now;

    const channel = window.CHAT_MODE === 'lobby' ? 'lobby' : 'dm';
    const toId = window.ACTIVE_PARTNER_ID || 0;

    const fd = new URLSearchParams();
    fd.append('action', 'typing');
    fd.append('channel', channel);
    if (toId) fd.append('to_id', toId);

    fetch(`${window.SNAPWALL_BASE}/actions/chat_action.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-CSRF-Token': window.CSRF_TOKEN
      },
      body: fd.toString()
    }).catch(() => {});
  };

  // Image File Handling & Preview
  window.handleChatImageSelect = function(e) {
    const file = e.target.files && e.target.files[0];
    if (file) {
      handleImageFile(file);
    }
  };

  function handleImageFile(file) {
    if (file.size > 5 * 1024 * 1024) {
      showToast('Image exceeds maximum allowed size of 5 MB.');
      return;
    }
    currentChatImageFile = file;
    currentVoiceBlob = null;
    currentVoiceDuration = 0;

    const reader = new FileReader();
    reader.onload = function(e) {
      if (previewImg) {
        previewImg.src = e.target.result;
        previewImg.style.display = 'block';
      }
      if (previewVoice) previewVoice.style.display = 'none';
      if (previewName) previewName.textContent = file.name || 'image.png';
      if (previewSize) previewSize.textContent = `${(file.size / 1024).toFixed(1)} KB`;
      if (previewDrawer) previewDrawer.style.display = 'block';
    };
    reader.readAsDataURL(file);
  }

  // Audio File Fallback Input
  window.handleChatAudioFileSelect = function(e) {
    const file = e.target.files && e.target.files[0];
    if (file) {
      if (file.size > 10 * 1024 * 1024) {
        showToast('Audio exceeds maximum size of 10 MB.');
        return;
      }
      currentChatImageFile = null;
      currentVoiceBlob = file;

      // Estimate duration from audio element
      const tempAudio = new Audio(URL.createObjectURL(file));
      tempAudio.addEventListener('loadedmetadata', function() {
        currentVoiceDuration = Math.round(tempAudio.duration) || 0;
        showVoicePreviewInDrawer(file.name || 'audio_file', currentVoiceDuration, tempAudio.src);
      });
    }
  };

  function showVoicePreviewInDrawer(name, durationSecs, audioSrc) {
    if (previewImg) previewImg.style.display = 'none';
    if (previewVoice) {
      previewVoice.style.display = 'flex';
      const durEl = document.getElementById('voice-preview-duration');
      if (durEl) durEl.textContent = formatDuration(durationSecs);
      const audioEl = document.getElementById('voice-preview-audio');
      if (audioEl && audioSrc) audioEl.src = audioSrc;
    }
    if (previewName) previewName.textContent = name;
    if (previewSize) previewSize.textContent = durationSecs ? `${durationSecs}s` : '';
    if (previewDrawer) previewDrawer.style.display = 'block';
  }

  // Clear Attachment
  window.clearChatAttachment = function() {
    currentChatImageFile = null;
    currentVoiceBlob = null;
    currentVoiceDuration = 0;
    const imgInput = document.getElementById('chat-image-input');
    if (imgInput) imgInput.value = '';
    const audioInput = document.getElementById('chat-audio-file-input');
    if (audioInput) audioInput.value = '';
    if (previewImg) { previewImg.src = ''; previewImg.style.display = 'none'; }
    if (previewVoice) previewVoice.style.display = 'none';
    if (previewDrawer) previewDrawer.style.display = 'none';
  };

  // Voice Recording with MediaRecorder
  window.startVoiceRecording = async function() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      alert('Your browser does not support microphone recording. You can select an audio file to send as a voice note!');
      const audioInput = document.getElementById('chat-audio-file-input');
      if (audioInput) audioInput.click();
      return;
    }

    try {
      recStream = await navigator.mediaDevices.getUserMedia({ audio: true });
      recChunks = [];

      let mimeType = 'audio/webm';
      if (typeof MediaRecorder.isTypeSupported === 'function') {
        if (MediaRecorder.isTypeSupported('audio/webm;codecs=opus')) {
          mimeType = 'audio/webm;codecs=opus';
        } else if (MediaRecorder.isTypeSupported('audio/ogg;codecs=opus')) {
          mimeType = 'audio/ogg;codecs=opus';
        } else if (MediaRecorder.isTypeSupported('audio/mp4')) {
          mimeType = 'audio/mp4';
        }
      }

      mediaRecorder = new MediaRecorder(recStream, { mimeType });
      mediaRecorder.ondataavailable = function(e) {
        if (e.data && e.data.size > 0) {
          recChunks.push(e.data);
        }
      };

      mediaRecorder.start(250);
      recStartTime = Date.now();

      // Switch composer to recording panel
      if (composerForm) composerForm.style.display = 'none';
      if (previewDrawer) previewDrawer.style.display = 'none';
      if (recordingPanel) recordingPanel.style.display = 'flex';

      updateRecTimer();
      recInterval = setInterval(updateRecTimer, 500);

    } catch (err) {
      console.warn('Microphone permission or hardware error:', err);
      alert('Microphone access was denied or unavailable. You can select an audio file from your device instead!');
      const audioInput = document.getElementById('chat-audio-file-input');
      if (audioInput) audioInput.click();
    }
  };

  function updateRecTimer() {
    if (!recTimerEl) return;
    const elapsed = Math.floor((Date.now() - recStartTime) / 1000);
    recTimerEl.textContent = formatDuration(elapsed);
  }

  window.cancelVoiceRecording = function() {
    stopMediaStream();
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
      mediaRecorder.stop();
    }
    cleanupRecordingUI();
  };

  window.finishAndSendVoiceRecording = function() {
    if (!mediaRecorder || mediaRecorder.state === 'inactive') return;

    const durationSec = Math.max(1, Math.floor((Date.now() - recStartTime) / 1000));
    stopMediaStream();

    mediaRecorder.onstop = function() {
      const mime = mediaRecorder.mimeType || 'audio/webm';
      const blob = new Blob(recChunks, { type: mime });
      currentVoiceBlob = blob;
      currentVoiceDuration = durationSec;
      currentChatImageFile = null;

      cleanupRecordingUI();
      // Send immediately
      handleSendChatMessage();
    };

    mediaRecorder.stop();
  };

  function stopMediaStream() {
    clearInterval(recInterval);
    if (recStream) {
      recStream.getTracks().forEach(t => t.stop());
      recStream = null;
    }
  }

  function cleanupRecordingUI() {
    if (recordingPanel) recordingPanel.style.display = 'none';
    if (composerForm) composerForm.style.display = 'flex';
  }

  function formatDuration(sec) {
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
  }

  // Send message (Text, Image, or Voice)
  window.handleSendChatMessage = async function(e) {
    if (e) e.preventDefault();

    const body = textarea ? textarea.value.trim() : '';

    if (!body && !currentChatImageFile && !currentVoiceBlob) {
      showToast('Please type a message, record voice, or attach a photo.');
      return;
    }
    if (body.length > 500) {
      showToast('Message must be 500 characters or fewer.');
      return;
    }

    const sendBtn = document.getElementById('chat-send-submit-btn');
    if (sendBtn) sendBtn.disabled = true;

    const fd = new FormData();
    fd.append('channel', window.CHAT_MODE === 'lobby' ? 'lobby' : 'dm');
    if (window.ACTIVE_PARTNER_ID) {
      fd.append('to_id', window.ACTIVE_PARTNER_ID);
    }
    fd.append('body', body);
    fd.append('csrf_token', window.CSRF_TOKEN);

    if (currentChatImageFile) {
      fd.append('media_type', 'image');
      fd.append('chat_image', currentChatImageFile);
    } else if (currentVoiceBlob) {
      fd.append('media_type', 'audio');
      const filename = currentVoiceBlob.name || 'voice_note.webm';
      fd.append('voice_audio', currentVoiceBlob, filename);
      fd.append('media_duration', currentVoiceDuration || 0);
    }

    try {
      const res = await fetch(`${window.SNAPWALL_BASE}/actions/chat_message.php`, {
        method: 'POST',
        headers: {
          'X-CSRF-Token': window.CSRF_TOKEN
        },
        body: fd
      });
      const data = await res.json();
      if (data.success && data.message) {
        if (textarea) {
          textarea.value = '';
          textarea.style.height = 'auto';
        }
        clearChatAttachment();

        if (window.CHAT_MODE === 'lobby') {
          appendLobbyBubble(data.message, true);
        } else {
          appendDmBubble(data.message, true);
        }
        scrollToBottom(true);
      } else {
        showToast(data.error || 'Failed to send message.');
      }
    } catch (err) {
      console.error(err);
      showToast('Network error while sending message.');
    } finally {
      setTimeout(() => {
        if (sendBtn) sendBtn.disabled = false;
      }, 1000);
    }
  };

  // Render media content inside a bubble
  function renderBubbleMedia(msg) {
    let html = '';
    const mediaType = msg.media_type || 'text';
    const mediaUrl = msg.media_url || '';
    const mediaThumb = msg.media_thumb || '';
    const duration = msg.media_duration || 0;

    if (mediaType === 'image' && mediaUrl) {
      const displaySrc = `${window.SNAPWALL_BASE}/uploads/${escapeHtml(mediaThumb || mediaUrl)}`;
      const fullSrc = `${window.SNAPWALL_BASE}/uploads/${escapeHtml(mediaUrl)}`;
      html += `
        <div class="chat-media-image">
          <a href="${fullSrc}" target="_blank" onclick="openChatLightbox(event, '${fullSrc}')">
            <img src="${displaySrc}" alt="Chat picture" class="chat-bubble-img" loading="lazy">
          </a>
        </div>
      `;
    } else if (mediaType === 'audio' && mediaUrl) {
      const audioSrc = `${window.SNAPWALL_BASE}/uploads/${escapeHtml(mediaUrl)}`;
      const durStr = duration > 0 ? formatDuration(duration) : '0:00';
      html += `
        <div class="chat-voice-player" onclick="handleVoicePlayerClick(event, this)">
          <button type="button" class="voice-play-btn" title="Play voice note" aria-label="Play voice note">
            <svg class="icon-play" viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><polygon points="6 3 20 12 6 21 6 3"/></svg>
            <svg class="icon-pause" style="display:none;" viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
          </button>
          <div class="voice-track-info">
            <div class="voice-wave-bars" aria-hidden="true">
              <span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span>
            </div>
            <div class="voice-progress-track"><div class="voice-progress-fill" style="width:0%;"></div></div>
            <div class="voice-time-display"><span class="voice-elapsed">0:00</span> / <span class="voice-total">${durStr}</span> <span class="voice-codec-tag">48kHz</span></div>
          </div>
          <audio preload="metadata" src="${audioSrc}" style="display:none;"></audio>
        </div>
      `;
    } else if (mediaType === 'call') {
      let body = (msg.body || '').replace(/[\u{1F300}-\u{1FAD6}\u{2000}-\u{3300}]/gu, '').trim();
      const isMissed = body.toLowerCase().includes('missed') || body.toLowerCase().includes('declined');
      const isVideo = body.toLowerCase().includes('video');
      let label = body;
      let durText = '';
      if (body.includes(' · ')) {
        const parts = body.split(' · ');
        label = parts[0].trim();
        durText = parts[1].trim();
      } else if (msg.media_duration && Number(msg.media_duration) > 0) {
        const d = Number(msg.media_duration);
        const m = Math.floor(d / 60);
        const s = String(d % 60).padStart(2, '0');
        durText = `${m}:${s}`;
      }

      const iconSvg = isMissed
        ? `<svg viewBox="0 0 20 20" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="2" y1="2" x2="18" y2="18"/><path d="M8.5 13.5a16.2 16.2 0 0 1-3.2-3.2 16.5 16.5 0 0 1-2.4-7 1.7 1.7 0 0 1 1.7-1.8h2.5a1.7 1.7 0 0 1 1.7 1.5c.2.8.4 1.6.6 2.4a1.7 1.7 0 0 1-.4 1.8l-1.1 1.1"/></svg>`
        : (isVideo
          ? `<svg viewBox="0 0 20 20" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="12" height="12" rx="2"/><polygon points="14 8 18 5 18 15 14 12"/></svg>`
          : `<svg viewBox="0 0 20 20" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 13.8v2.5a1.7 1.7 0 0 1-1.8 1.7 16.5 16.5 0 0 1-7.2-2.6 16.2 16.2 0 0 1-5-5A16.5 16.5 0 0 1 .4 3.2 1.7 1.7 0 0 1 2.1 1.4h2.5a1.7 1.7 0 0 1 1.7 1.5 10.7 10.7 0 0 0 .6 2.4 1.7 1.7 0 0 1-.4 1.8L5.4 8.2a13.2 13.2 0 0 0 5 5l1.1-1.1a1.7 1.7 0 0 1 1.8-.4 10.7 10.7 0 0 0 2.4.6 1.7 1.7 0 0 1 1.3 1.5z"/></svg>`);

      return `
        <div class="call-event-pill">
          <span class="call-pill-icon-holder">${iconSvg}</span>
          <span class="call-pill-text"><strong>${escapeHtml(label || (isVideo ? 'Video call ended' : 'Voice call ended'))}</strong>${durText ? `<span class="call-pill-dur"> · ${escapeHtml(durText)}</span>` : ''}</span>
        </div>
      `;
    }

    if (msg.body) {
      const margin = (mediaType !== 'text') ? 'style="margin-top:6px;"' : '';
      html += `<div class="chat-msg-text" ${margin}>${escapeHtml(msg.body).replace(/\n/g, '<br>')}</div>`;
    }

    return html;
  }

  // Append Lobby message bubble
  function appendLobbyBubble(msg, isMine) {
    const list = document.getElementById('lobby-messages-list');
    if (!list) return;
    if (document.getElementById(`lobby-msg-${msg.id}`)) return;

    window.LATEST_LOBBY_ID = Math.max(window.LATEST_LOBBY_ID || 0, msg.id);

    const isCall = msg.media_type === 'call';
    const canDelete = isMine || window.CURRENT_USER_ADMIN;

    if (isCall) {
      const row = document.createElement('div');
      row.className = 'call-pill-row';
      row.id = `lobby-msg-${msg.id}`;
      row.innerHTML = renderBubbleMedia(msg);
      list.appendChild(row);
    } else {
      const wrapper = document.createElement('div');
      wrapper.className = `message-bubble-wrapper ${isMine ? 'mine' : 'theirs'}`;
      wrapper.id = `lobby-msg-${msg.id}`;

      const verifiedBadge = msg.is_verified ? `
        <span class="verified-badge" title="Verified"><svg viewBox="0 0 14 14" width="14" height="14" style="vertical-align:-1.5px;display:inline-block;margin-left:3px;" aria-label="Verified"><circle cx="7" cy="7" r="7" fill="#1C1B18"/><path d="M4 7.2l2 2 4-4" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg></span>
      ` : '';

      wrapper.innerHTML = `
        <div class="message-sender-line">
          <a href="${window.SNAPWALL_BASE}/chat.php?u=${encodeURIComponent(msg.username)}" class="message-sender-name">${escapeHtml(msg.username)}</a>
          ${verifiedBadge}
          <span class="message-time-text">${escapeHtml(msg.created_at || 'just now')}</span>
          ${canDelete ? `<button type="button" class="comment-del" onclick="deleteLobbyMessage(${msg.id})" title="Delete message" aria-label="Delete message">✕</button>` : ''}
        </div>
        <div class="message-bubble ${isMine ? 'bubble-mine' : 'bubble-theirs'}">
          ${renderBubbleMedia(msg)}
        </div>
      `;
      list.appendChild(wrapper);
    }
  }

  // Append DM message bubble
  function appendDmBubble(msg, isMine) {
    const list = document.getElementById('dm-messages-list');
    if (!list) return;
    if (document.getElementById(`dm-msg-${msg.id}`)) return;

    const emptyState = document.getElementById('dm-empty-state');
    if (emptyState) emptyState.remove();

    window.LATEST_DM_ID = Math.max(window.LATEST_DM_ID || 0, msg.id);

    const isCall = msg.media_type === 'call';
    const canDelete = isMine || window.CURRENT_USER_ADMIN;

    if (isCall) {
      const row = document.createElement('div');
      row.className = 'call-pill-row';
      row.id = `dm-msg-${msg.id}`;
      row.innerHTML = renderBubbleMedia(msg);
      list.appendChild(row);
    } else {
      // Remove any previous "Seen" label on my messages
      if (isMine) {
        document.querySelectorAll('.dm-seen-status').forEach(el => el.remove());
      }

      const wrapper = document.createElement('div');
      wrapper.className = `message-bubble-wrapper ${isMine ? 'mine' : 'theirs'}`;
      wrapper.id = `dm-msg-${msg.id}`;

      wrapper.innerHTML = `
        <div class="message-bubble ${isMine ? 'bubble-mine' : 'bubble-theirs'}">
          ${canDelete ? `<button type="button" class="comment-del" onclick="deleteDmMessage(${msg.id})" title="Delete message" aria-label="Delete message">✕</button>` : ''}
          ${renderBubbleMedia(msg)}
        </div>
        ${isMine ? `
          <div class="dm-seen-status" id="dm-seen-status-${msg.id}">
            <svg viewBox="0 0 16 16" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="3 8.5 6.5 12 13 4"/>
            </svg>
            <span>Seen</span>
          </div>
        ` : ''}
      `;
      list.appendChild(wrapper);
    }
  }

  // Voice Player playback interaction
  window.handleVoicePlayerClick = function(e, playerEl) {
    const btn = playerEl.querySelector('.voice-play-btn');
    const audio = playerEl.querySelector('audio');
    if (!audio) return;

    const playIcon = btn ? btn.querySelector('.icon-play') : null;
    const pauseIcon = btn ? btn.querySelector('.icon-pause') : null;
    const progressFill = playerEl.querySelector('.voice-progress-fill');
    const elapsedEl = playerEl.querySelector('.voice-elapsed');

    if (currentlyPlayingAudio && currentlyPlayingAudio !== audio) {
      currentlyPlayingAudio.pause();
      const prevPlayer = currentlyPlayingAudio.closest ? currentlyPlayingAudio.closest('.chat-voice-player') : null;
      if (prevPlayer) prevPlayer.classList.remove('is-playing');
      if (currentlyPlayingBtn) {
        const p1 = currentlyPlayingBtn.querySelector('.icon-play');
        const p2 = currentlyPlayingBtn.querySelector('.icon-pause');
        if (p1) p1.style.display = 'block';
        if (p2) p2.style.display = 'none';
      }
    }

    if (audio.paused) {
      audio.play().then(() => {
        if (playIcon) playIcon.style.display = 'none';
        if (pauseIcon) pauseIcon.style.display = 'block';
        playerEl.classList.add('is-playing');
        currentlyPlayingAudio = audio;
        currentlyPlayingBtn = btn;
      }).catch(err => console.warn(err));
    } else {
      audio.pause();
      playerEl.classList.remove('is-playing');
      if (playIcon) playIcon.style.display = 'block';
      if (pauseIcon) pauseIcon.style.display = 'none';
    }

    audio.ontimeupdate = function() {
      if (audio.duration) {
        const pct = (audio.currentTime / audio.duration) * 100;
        if (progressFill) progressFill.style.width = `${pct}%`;
        if (elapsedEl) elapsedEl.textContent = formatDuration(Math.floor(audio.currentTime));
      }
    };

    audio.onended = function() {
      if (playIcon) playIcon.style.display = 'block';
      if (pauseIcon) pauseIcon.style.display = 'none';
      if (progressFill) progressFill.style.width = '0%';
      if (elapsedEl) elapsedEl.textContent = '0:00';
      playerEl.classList.remove('is-playing');
      currentlyPlayingAudio = null;
      currentlyPlayingBtn = null;
    };
  };

  // Lightbox Modal functions
  window.openChatLightbox = function(e, fullSrc) {
    if (e) {
      e.preventDefault();
      e.stopPropagation();
    }
    if (lightboxModal && lightboxImg) {
      lightboxImg.src = fullSrc;
      if (lightboxDownload) lightboxDownload.href = fullSrc;
      lightboxModal.style.display = 'flex';
    }
  };

  window.closeChatLightbox = function() {
    if (lightboxModal) lightboxModal.style.display = 'none';
    if (lightboxImg) lightboxImg.src = '';
  };

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && lightboxModal && lightboxModal.style.display === 'flex') {
      closeChatLightbox();
    }
  });

  // Delete Lobby Message
  window.deleteLobbyMessage = async function(id) {
    if (!confirm('Delete this message from the Lobby?')) return;
    try {
      const res = await fetch(`${window.SNAPWALL_BASE}/actions/chat_action.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-CSRF-Token': window.CSRF_TOKEN
        },
        body: `action=delete_message&type=lobby&id=${id}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
      });
      const data = await res.json();
      if (data.success) {
        removeMessageEl(`lobby-msg-${id}`);
      }
    } catch (e) {
      console.error(e);
    }
  };

  // Delete DM Message
  window.deleteDmMessage = async function(id) {
    if (!confirm('Delete this direct message?')) return;
    try {
      const res = await fetch(`${window.SNAPWALL_BASE}/actions/chat_action.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-CSRF-Token': window.CSRF_TOKEN
        },
        body: `action=delete_message&type=dm&id=${id}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
      });
      const data = await res.json();
      if (data.success) {
        removeMessageEl(`dm-msg-${id}`);
      }
    } catch (e) {
      console.error(e);
    }
  };

  function removeMessageEl(elId) {
    const el = document.getElementById(elId);
    if (el) {
      el.style.opacity = '0';
      el.style.transform = 'scale(0.95)';
      el.style.transition = 'all 0.15s ease';
      setTimeout(() => el.remove(), 150);
    }
  }

  // Load older Lobby messages
  window.loadOlderLobbyMessages = async function() {
    const btn = document.getElementById('btn-load-older-lobby');
    if (!btn || !window.OLDEST_LOBBY_ID) return;

    btn.disabled = true;
    btn.textContent = 'Loading...';

    try {
      const res = await fetch(`${window.SNAPWALL_BASE}/actions/chat_action.php?action=load_older_lobby&before_id=${window.OLDEST_LOBBY_ID}`);
      const data = await res.json();
      if (data.success && Array.isArray(data.messages) && data.messages.length > 0) {
        const list = document.getElementById('lobby-messages-list');
        const oldScrollHeight = scrollArea.scrollHeight;

        data.messages.forEach(msg => {
          if (!document.getElementById(`lobby-msg-${msg.id}`)) {
            const isMine = msg.user_id === window.CURRENT_USER_ID;
            const isCall = msg.media_type === 'call';
            const canDelete = isMine || window.CURRENT_USER_ADMIN;

            if (isCall) {
              const row = document.createElement('div');
              row.className = 'call-pill-row';
              row.id = `lobby-msg-${msg.id}`;
              row.innerHTML = renderBubbleMedia(msg);
              list.prepend(row);
            } else {
              const wrapper = document.createElement('div');
              wrapper.className = `message-bubble-wrapper ${isMine ? 'mine' : 'theirs'}`;
              wrapper.id = `lobby-msg-${msg.id}`;

              const verifiedBadge = msg.is_verified ? `
                <span class="verified-badge" title="Verified"><svg viewBox="0 0 14 14" width="14" height="14" style="vertical-align:-1.5px;display:inline-block;margin-left:3px;" aria-label="Verified"><circle cx="7" cy="7" r="7" fill="#1C1B18"/><path d="M4 7.2l2 2 4-4" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg></span>
              ` : '';

              wrapper.innerHTML = `
                <div class="message-sender-line">
                  <a href="${window.SNAPWALL_BASE}/chat.php?u=${encodeURIComponent(msg.username)}" class="message-sender-name">${escapeHtml(msg.username)}</a>
                  ${verifiedBadge}
                  <span class="message-time-text">${escapeHtml(msg.created_at)}</span>
                  ${canDelete ? `<button type="button" class="comment-del" onclick="deleteLobbyMessage(${msg.id})" title="Delete message" aria-label="Delete message">✕</button>` : ''}
                </div>
                <div class="message-bubble ${isMine ? 'bubble-mine' : 'bubble-theirs'}">
                  ${renderBubbleMedia(msg)}
                </div>
              `;
              list.prepend(wrapper);
            }
          }
        });

        window.OLDEST_LOBBY_ID = data.messages[0].id;
        scrollArea.scrollTop = scrollArea.scrollHeight - oldScrollHeight;
        btn.disabled = false;
        btn.textContent = '↑ Load older messages';
      } else {
        btn.textContent = 'No more older messages';
        btn.disabled = true;
      }
    } catch (e) {
      console.error(e);
      btn.disabled = false;
      btn.textContent = '↑ Load older messages';
    }
  };

  // Load older DM messages
  window.loadOlderDmMessages = async function() {
    const btn = document.getElementById('btn-load-older-dm');
    if (!btn || !window.OLDEST_DM_ID || !window.ACTIVE_PARTNER_ID) return;

    btn.disabled = true;
    btn.textContent = 'Loading...';

    try {
      const res = await fetch(`${window.SNAPWALL_BASE}/actions/chat_action.php?action=load_older_dm&partner_id=${window.ACTIVE_PARTNER_ID}&before_id=${window.OLDEST_DM_ID}`);
      const data = await res.json();
      if (data.success && Array.isArray(data.messages) && data.messages.length > 0) {
        const list = document.getElementById('dm-messages-list');
        const oldScrollHeight = scrollArea.scrollHeight;

        data.messages.forEach(msg => {
          if (!document.getElementById(`dm-msg-${msg.id}`)) {
            const isMine = msg.from_id === window.CURRENT_USER_ID;
            const isCall = msg.media_type === 'call';
            const canDelete = isMine || window.CURRENT_USER_ADMIN;

            if (isCall) {
              const row = document.createElement('div');
              row.className = 'call-pill-row';
              row.id = `dm-msg-${msg.id}`;
              row.innerHTML = renderBubbleMedia(msg);
              list.prepend(row);
            } else {
              const wrapper = document.createElement('div');
              wrapper.className = `message-bubble-wrapper ${isMine ? 'mine' : 'theirs'}`;
              wrapper.id = `dm-msg-${msg.id}`;

              wrapper.innerHTML = `
                <div class="message-bubble ${isMine ? 'bubble-mine' : 'bubble-theirs'}">
                  ${canDelete ? `<button type="button" class="comment-del" onclick="deleteDmMessage(${msg.id})" title="Delete message" aria-label="Delete message">✕</button>` : ''}
                  ${renderBubbleMedia(msg)}
                </div>
              `;
              list.prepend(wrapper);
            }
          }
        });

        window.OLDEST_DM_ID = data.messages[0].id;
        scrollArea.scrollTop = scrollArea.scrollHeight - oldScrollHeight;
        btn.disabled = false;
        btn.textContent = '↑ Load older messages';
      } else {
        btn.textContent = 'No more older messages';
        btn.disabled = true;
      }
    } catch (e) {
      console.error(e);
      btn.disabled = false;
      btn.textContent = '↑ Load older messages';
    }
  };

  // Real-time Event Hooks (Called from stream.js)
  window.onIncomingLobbyMessage = function(payload) {
    if (window.CHAT_MODE === 'lobby') {
      const isMine = payload.user_id === window.CURRENT_USER_ID;
      appendLobbyBubble(payload, isMine);
      scrollToBottom(true);
    }
  };

  window.onIncomingDmMessage = function(payload) {
    if (window.CHAT_MODE === 'dm' && window.ACTIVE_PARTNER_ID === payload.from_id) {
      appendDmBubble(payload, false);
      scrollToBottom(true);

      // Auto-mark as seen
      fetch(`${window.SNAPWALL_BASE}/actions/chat_action.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-CSRF-Token': window.CSRF_TOKEN
        },
        body: `action=mark_seen&from_id=${payload.from_id}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
      }).catch(() => {});
    }
  };

  window.onLobbyTyping = function(payload) {
    if (window.CHAT_MODE === 'lobby' && payload.user_id !== window.CURRENT_USER_ID) {
      showTypingIndicator(`${payload.username} is typing...`);
    }
  };

  window.onDmTyping = function(payload) {
    if (window.CHAT_MODE === 'dm' && window.ACTIVE_PARTNER_ID === payload.from_id) {
      showTypingIndicator(`${payload.from_username} is typing...`);
    }
  };

  window.onDmSeen = function(payload) {
    if (window.CHAT_MODE === 'dm' && window.ACTIVE_PARTNER_ID === payload.reader_id) {
      const seenEls = document.querySelectorAll('.dm-seen-status');
      seenEls.forEach(el => {
        el.textContent = '✓ Seen';
      });
    }
  };

  window.onLobbyDelete = function(payload) {
    removeMessageEl(`lobby-msg-${payload.id}`);
  };

  window.onDmDelete = function(payload) {
    removeMessageEl(`dm-msg-${payload.id}`);
  };

  function showTypingIndicator(text) {
    if (!typingBox || !typingText) return;
    typingText.textContent = text;
    typingBox.style.display = 'flex';
    clearTimeout(typingTimer);
    typingTimer = setTimeout(() => {
      typingBox.style.display = 'none';
    }, 2500);
  }

  // Fast 2s Delta Poll for Guaranteed Real-Time Chat updates across all networks
  let fastPollBusy = false;
  async function pollNewChatMessages() {
    if (fastPollBusy || document.hidden) return;
    fastPollBusy = true;

    try {
      if (window.CHAT_MODE === 'lobby') {
        const afterId = window.LATEST_LOBBY_ID || 0;
        const res = await fetch(`${window.SNAPWALL_BASE}/actions/chat_action.php?action=fetch_new&channel=lobby&after_id=${afterId}`);
        if (res.ok) {
          const data = await res.json();
          if (data.success && Array.isArray(data.messages) && data.messages.length > 0) {
            data.messages.forEach(msg => {
              const isMine = (msg.user_id === window.CURRENT_USER_ID);
              appendLobbyBubble(msg, isMine);
            });
            scrollToBottom(true);
          }
        }
      } else if (window.CHAT_MODE === 'dm' && window.ACTIVE_PARTNER_ID) {
        const afterId = window.LATEST_DM_ID || 0;
        const res = await fetch(`${window.SNAPWALL_BASE}/actions/chat_action.php?action=fetch_new&channel=dm&partner_id=${window.ACTIVE_PARTNER_ID}&after_id=${afterId}`);
        if (res.ok) {
          const data = await res.json();
          if (data.success && Array.isArray(data.messages) && data.messages.length > 0) {
            data.messages.forEach(msg => {
              const isMine = (msg.from_id === window.CURRENT_USER_ID);
              appendDmBubble(msg, isMine);
            });
            scrollToBottom(true);
          }
        }
      }
    } catch (err) {
      // Ignore network glitch
    } finally {
      fastPollBusy = false;
    }
  }

  // Poll every 2 seconds while user is on chat
  setInterval(pollNewChatMessages, 2000);

})();

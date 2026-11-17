// Snapwall / Nexly Real-time Stream Client (SSE + 2s Fallback Polling)

(function() {
  if (!window.CURRENT_USER_ID) {
    return; // Guest user; stream active only for logged-in sessions
  }

  let evtSource = null;
  let errorCount = 0;
  let isPollingFallback = false;
  let lastEventId = window.LATEST_EVENT_ID || 0;
  let pollIntervalTimer = null;
  const pendingPosts = [];

  // 30s Heartbeat to keep last_seen updated
  function sendHeartbeat() {
    fetch(`${window.SNAPWALL_BASE}/actions/poll.php?heartbeat=1`, {
      method: 'GET',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(data => {
      if (data && data.online_users) {
        handleOnlineCountEvent({ count: data.online_count, users: data.online_users });
      }
    }).catch(() => {});
  }
  setInterval(sendHeartbeat, 30000);
  sendHeartbeat(); // initial ping

  // Connect SSE
  function connectStream() {
    if (isPollingFallback) return;

    try {
      evtSource = new EventSource(`${window.SNAPWALL_BASE}/stream.php`);

      evtSource.onopen = function() {
        errorCount = 0;
        window.SSE_ACTIVE = true;
      };

      // 1. Post & Feed events
      evtSource.addEventListener('new_post', function(e) {
        handleNewPostEvent(JSON.parse(e.data));
        if (e.lastEventId) lastEventId = e.lastEventId;
      });

      evtSource.addEventListener('like_update', function(e) {
        handleLikeUpdateEvent(JSON.parse(e.data));
        if (e.lastEventId) lastEventId = e.lastEventId;
      });

      evtSource.addEventListener('new_comment', function(e) {
        handleNewCommentEvent(JSON.parse(e.data));
        if (e.lastEventId) lastEventId = e.lastEventId;
      });

      evtSource.addEventListener('notification', function(e) {
        handleNotificationEvent(JSON.parse(e.data));
        if (e.lastEventId) lastEventId = e.lastEventId;
      });

      evtSource.addEventListener('online_count', function(e) {
        handleOnlineCountEvent(JSON.parse(e.data));
        if (e.lastEventId) lastEventId = e.lastEventId;
      });

      // 2. Live Lobby Events
      evtSource.addEventListener('lobby_message', function(e) {
        const payload = JSON.parse(e.data);
        handleLobbyMessageEvent(payload);
        if (e.lastEventId) lastEventId = e.lastEventId;
      });

      evtSource.addEventListener('lobby_typing', function(e) {
        const payload = JSON.parse(e.data);
        if (window.onLobbyTyping) window.onLobbyTyping(payload);
        if (e.lastEventId) lastEventId = e.lastEventId;
      });

      evtSource.addEventListener('lobby_delete', function(e) {
        const payload = JSON.parse(e.data);
        if (window.onLobbyDelete) window.onLobbyDelete(payload);
        if (e.lastEventId) lastEventId = e.lastEventId;
      });

      // 3. Direct Message Events
      evtSource.addEventListener('dm_message', function(e) {
        const payload = JSON.parse(e.data);
        handleDmMessageEvent(payload);
        if (e.lastEventId) lastEventId = e.lastEventId;
      });

      evtSource.addEventListener('dm_typing', function(e) {
        const payload = JSON.parse(e.data);
        if (window.onDmTyping) window.onDmTyping(payload);
        if (e.lastEventId) lastEventId = e.lastEventId;
      });

      evtSource.addEventListener('dm_seen', function(e) {
        const payload = JSON.parse(e.data);
        if (window.onDmSeen) window.onDmSeen(payload);
        if (e.lastEventId) lastEventId = e.lastEventId;
      });

      evtSource.addEventListener('dm_delete', function(e) {
        const payload = JSON.parse(e.data);
        if (window.onDmDelete) window.onDmDelete(payload);
        if (e.lastEventId) lastEventId = e.lastEventId;
      });

      // 4. WebRTC Call Signals
      ['call_ring', 'call_ringing_ack', 'call_answer', 'call_candidate', 'call_candidates_batch', 'call_reject', 'call_end'].forEach(function(signalEvt) {
        evtSource.addEventListener(signalEvt, function(e) {
          const payload = JSON.parse(e.data);
          if (window.onWebRtcCallSignal) {
            window.onWebRtcCallSignal(signalEvt, payload);
          }
          if (e.lastEventId) lastEventId = e.lastEventId;
        });
      });

      evtSource.onerror = function(err) {
        errorCount++;
        if (errorCount >= 3) {
          if (evtSource) {
            evtSource.close();
            evtSource = null;
          }
          startPollingFallback();
        }
      };
    } catch (e) {
      errorCount++;
      if (errorCount >= 3) {
        startPollingFallback();
      }
    }
  }

  // Fallback Polling every 2 seconds
  function startPollingFallback() {
    if (isPollingFallback) return;
    isPollingFallback = true;
    window.SSE_ACTIVE = false;
    console.warn('[Nexly] SSE failed 3 times. Falling back to polling every 2 seconds.');

    pollIntervalTimer = setInterval(async function() {
      try {
        const res = await fetch(`${window.SNAPWALL_BASE}/actions/poll.php?last_id=${lastEventId}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) return;
        const data = await res.json();
        if (data && data.events && Array.isArray(data.events)) {
          data.events.forEach(item => {
            lastEventId = item.id;
            dispatchIncomingEvent(item.type, item.payload);
          });
        }
      } catch (err) {
        // Retry next tick
      }
    }, 1000);
  }

  function dispatchIncomingEvent(type, payload) {
    switch (type) {
      case 'new_post':
        handleNewPostEvent(payload);
        break;
      case 'like_update':
        handleLikeUpdateEvent(payload);
        break;
      case 'new_comment':
        handleNewCommentEvent(payload);
        break;
      case 'notification':
        handleNotificationEvent(payload);
        break;
      case 'online_count':
        handleOnlineCountEvent(payload);
        break;
      case 'lobby_message':
        handleLobbyMessageEvent(payload);
        break;
      case 'lobby_typing':
        if (window.onLobbyTyping) window.onLobbyTyping(payload);
        break;
      case 'lobby_delete':
        if (window.onLobbyDelete) window.onLobbyDelete(payload);
        break;
      case 'dm_message':
        handleDmMessageEvent(payload);
        break;
      case 'dm_typing':
        if (window.onDmTyping) window.onDmTyping(payload);
        break;
      case 'dm_seen':
        if (window.onDmSeen) window.onDmSeen(payload);
        break;
      case 'dm_delete':
        if (window.onDmDelete) window.onDmDelete(payload);
        break;
      case 'call_ring':
      case 'call_ringing_ack':
      case 'call_answer':
      case 'call_candidate':
      case 'call_candidates_batch':
      case 'call_reject':
      case 'call_end':
        if (window.onWebRtcCallSignal) {
          window.onWebRtcCallSignal(type, payload);
        }
        break;
    }
  }

  // EVENT DISPATCHERS

  // new_post: Do NOT move feed. Show tilted yellow button "X new posts, tap to see"
  function handleNewPostEvent(data) {
    const stream = document.getElementById('posts-stream');
    if (!stream) return;
    if (document.getElementById(`post-${data.post_id}`)) return;

    pendingPosts.push(data);
    updateNewPostsPill();
  }

  function updateNewPostsPill() {
    const pill = document.getElementById('new-posts-pill');
    if (!pill) return;
    const count = pendingPosts.length;
    if (count > 0) {
      pill.textContent = `${count} new post${count > 1 ? 's' : ''}, tap to see`;
      pill.style.display = 'inline-block';
    } else {
      pill.style.display = 'none';
    }
  }

  window.revealPendingPosts = function() {
    const stream = document.getElementById('posts-stream');
    if (!stream || pendingPosts.length === 0) return;

    while (pendingPosts.length > 0) {
      const post = pendingPosts.shift();
      if (!document.getElementById(`post-${post.post_id}`) && post.post_html) {
        const wrap = document.createElement('div');
        wrap.innerHTML = post.post_html.trim();
        const card = wrap.firstElementChild;
        stream.prepend(card);
      }
    }
    updateNewPostsPill();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  // like_update
  function handleLikeUpdateEvent(data) {
    const countEl = document.getElementById(`like-count-${data.post_id}`);
    if (countEl) countEl.textContent = data.like_count;
    if (data.user_id === window.CURRENT_USER_ID) {
      const btn = document.getElementById(`like-btn-${data.post_id}`);
      if (btn) btn.classList.toggle('liked', data.action === 'like');
    }
  }

  // new_comment
  function handleNewCommentEvent(data) {
    const countEl = document.getElementById(`comment-count-${data.post_id}`);
    if (countEl && typeof data.comment_count !== 'undefined') {
      countEl.textContent = data.comment_count;
    }
    if (data.comment) {
      appendCommentToPost(data.post_id, data.comment);
    }
  }

  // notification
  function handleNotificationEvent(data) {
    const badge = document.getElementById('nav-notif-badge');
    if (badge) {
      const count = data.unread_count || 0;
      badge.textContent = count;
      badge.style.display = count > 0 ? 'inline-flex' : 'none';
    }
    if (data.message) {
      showToast(data.message);
    }
  }

  // online_count
  function handleOnlineCountEvent(data) {
    const countEl = document.getElementById('online-count-num');
    if (countEl && typeof data.count !== 'undefined') {
      countEl.textContent = data.count;
    }
    const container = document.getElementById('online-users-container');
    if (container && Array.isArray(data.users)) {
      if (data.users.length === 0) {
        container.innerHTML = '<div style="color:var(--muted); font-size:13px;">just you right now</div>';
      } else {
        container.innerHTML = data.users.map(u => {
          const isMe = (u.id === window.CURRENT_USER_ID);
          const href = isMe
            ? `${window.SNAPWALL_BASE}/profile.php?u=${encodeURIComponent(u.username)}`
            : `${window.SNAPWALL_BASE}/chat.php?u=${encodeURIComponent(u.username)}`;
          const displayName = u.display_name || u.username;
          const statusMood = u.status_mood || (String(u.username).toLowerCase() === 'rt' ? 'Building Nexly' : 'Online');
          const initial = String(u.username || '?').substring(0, 2).toUpperCase();
          const avatarHtml = u.avatar
            ? `<img src="${window.SNAPWALL_BASE}/uploads/${escapeHtml(u.avatar)}" alt="${escapeHtml(u.username)}" class="avatar" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">`
            : `<div class="avatar avatar-fallback avatar-tint-1" style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;font-family:var(--font-brand);">${escapeHtml(initial)}</div>`;
          const verifiedHtml = u.is_verified
            ? `<span class="ink-verified-badge" title="Verified" style="display:inline-flex;align-items:center;margin-left:3px;"><svg viewBox="0 0 24 24" width="13" height="13" fill="var(--ink)"><circle cx="12" cy="12" r="10"/><path d="M8.5 12.5l2.5 2.5 5-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>`
            : '';

          return `
            <a href="${href}" class="right-panel-online-row" title="${isMe ? 'Your profile' : 'Chat with @' + escapeHtml(u.username)}">
              <div class="right-panel-online-avatar-slot">
                ${avatarHtml}
                <span class="online-status-dot-avatar" aria-hidden="true"></span>
              </div>
              <div class="right-panel-online-meta">
                <span class="right-panel-user-name">
                  ${escapeHtml(displayName)}${verifiedHtml}
                </span>
                <span class="right-panel-user-status">${escapeHtml(statusMood)}</span>
              </div>
            </a>
          `;
        }).join('');
      }
    }
  }

  // lobby_message
  function handleLobbyMessageEvent(data) {
    if (window.onIncomingLobbyMessage) {
      window.onIncomingLobbyMessage(data);
    } else {
      // If not on chat page, show subtle toast
      if (data.user_id !== window.CURRENT_USER_ID) {
        const snippet = data.body
          ? (data.body.length > 32 ? data.body.substring(0, 32) + '...' : data.body)
          : (data.media_type === 'image' ? '📷 Sent a photo' : (data.media_type === 'audio' ? '🎤 Sent a voice note' : 'New message'));
        showToast(`[Lobby] ${data.username}: ${snippet}`);
      }
    }
  }

  // dm_message
  function handleDmMessageEvent(data) {
    // Increment unread DM badge
    const dmBadge = document.getElementById('nav-chat-badge');
    if (dmBadge) {
      const cur = parseInt(dmBadge.textContent) || 0;
      dmBadge.textContent = cur + 1;
      dmBadge.style.display = 'inline-flex';
    }

    if (window.onIncomingDmMessage && window.ACTIVE_PARTNER_ID === data.from_id) {
      window.onIncomingDmMessage(data);
    } else {
      const snippet = data.body
        ? (data.body.length > 35 ? data.body.substring(0, 35) + '...' : data.body)
        : (data.media_type === 'image' ? '📷 Sent a photo' : (data.media_type === 'audio' ? '🎤 Sent a voice note' : 'New message'));
      showToast(`Direct message from ${data.from_username}: ${snippet}`);
    }
  }

  // Start SSE connection after window load so page load is immediate
  if (!/HeadlessChrome/i.test(navigator.userAgent || '')) {
    if (document.readyState === 'complete') {
      setTimeout(connectStream, 80);
    } else {
      window.addEventListener('load', function() {
        setTimeout(connectStream, 80);
      });
    }
  }
})();

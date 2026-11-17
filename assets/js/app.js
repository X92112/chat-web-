// Snapwall Main Application JS

let selectedFile = null;

// Modal Controls (Start New Chat Dialog)
function openNewChatModal() {
  const dlg = document.getElementById('new-chat-dialog');
  if (dlg && typeof dlg.showModal === 'function') {
    dlg.showModal();
    const searchInput = document.getElementById('new-chat-search-input');
    if (searchInput) {
      searchInput.value = '';
      filterNewChatMembers('');
      setTimeout(() => searchInput.focus(), 60);
    }
  }
}

function openPostModal() {
  openNewChatModal();
}

function filterNewChatMembers(query) {
  const clean = (query || '').trim().toLowerCase();
  const rows = document.querySelectorAll('.new-chat-member-item');
  rows.forEach(row => {
    const hay = row.getAttribute('data-search') || '';
    row.style.display = (!clean || hay.includes(clean)) ? 'flex' : 'none';
  });
}

function filterChatSidebar(query) {
  const clean = (query || '').trim().toLowerCase();
  const items = document.querySelectorAll('.dm-sidebar-item');
  items.forEach(item => {
    const hay = item.getAttribute('data-user') || '';
    item.style.display = (!clean || hay.includes(clean)) ? 'flex' : 'none';
  });
}

function closePostModal() {
  const modal = document.getElementById('new-post-modal');
  if (modal) {
    modal.classList.remove('open');
    resetPostForm();
  }
}

function onModalBackdropClick(e) {
  if (e.target.id === 'new-post-modal') {
    closePostModal();
  }
}

// Escape key to close modal
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    closePostModal();
  }
});

function triggerFileInput() {
  const fileInput = document.getElementById('modal-photo-input');
  if (fileInput) fileInput.click();
}

function handleFileSelect(e) {
  const file = e.target.files[0];
  if (file) {
    applySelectedFile(file);
  }
}

function handleDragOver(e) {
  e.preventDefault();
  e.stopPropagation();
  const dropZone = document.getElementById('drop-zone');
  if (dropZone) dropZone.classList.add('dragover');
}

function handleDragLeave(e) {
  e.preventDefault();
  e.stopPropagation();
  const dropZone = document.getElementById('drop-zone');
  if (dropZone) dropZone.classList.remove('dragover');
}

function handleFileDrop(e) {
  e.preventDefault();
  e.stopPropagation();
  const dropZone = document.getElementById('drop-zone');
  if (dropZone) dropZone.classList.remove('dragover');

  if (e.dataTransfer && e.dataTransfer.files.length > 0) {
    const file = e.dataTransfer.files[0];
    applySelectedFile(file);
    const fileInput = document.getElementById('modal-photo-input');
    if (fileInput) {
      fileInput.files = e.dataTransfer.files;
    }
  }
}

function applySelectedFile(file) {
  if (!file.type.match(/^image\/(jpeg|png|webp|gif)$/i)) {
    showToast('Only JPG, PNG, WebP or GIF files are allowed.');
    return;
  }
  if (file.size > 5 * 1024 * 1024) {
    showToast('Photo must be 5 MB or smaller.');
    return;
  }
  selectedFile = file;

  const previewImg = document.getElementById('preview-image');
  const placeholder = document.getElementById('preview-placeholder');

  const reader = new FileReader();
  reader.onload = (e) => {
    if (previewImg) {
      previewImg.src = e.target.result;
      previewImg.style.display = 'block';
    }
    if (placeholder) {
      placeholder.style.display = 'none';
    }
  };
  reader.readAsDataURL(file);
}

function resetPostForm() {
  selectedFile = null;
  const form = document.getElementById('new-post-form');
  if (form) form.reset();
  const previewImg = document.getElementById('preview-image');
  if (previewImg) {
    previewImg.src = '';
    previewImg.style.display = 'none';
  }
  const placeholder = document.getElementById('preview-placeholder');
  if (placeholder) {
    placeholder.style.display = 'inline-block';
  }
  const submitBtn = document.getElementById('modal-post-submit-btn');
  if (submitBtn) {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Post';
  }
}

// Handle New Post Submission
async function handleNewPostSubmit(e) {
  e.preventDefault();
  if (!selectedFile) {
    const fileInput = document.getElementById('modal-photo-input');
    if (fileInput && fileInput.files.length > 0) {
      selectedFile = fileInput.files[0];
    } else {
      showToast('Please choose a photo to post.');
      return;
    }
  }

  const submitBtn = document.getElementById('modal-post-submit-btn');
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = 'Posting...';
  }

  const formData = new FormData();
  formData.append('photo', selectedFile);
  formData.append('caption', document.getElementById('post-caption-input').value.trim());
  formData.append('tags', document.getElementById('post-tags-input').value.trim());
  formData.append('csrf_token', window.CSRF_TOKEN);

  try {
    const res = await fetch(`${window.SNAPWALL_BASE}/actions/post.php`, {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.success) {
      closePostModal();
      showToast('Photo posted!');
      // Prepend post card if we are on the feed or reload
      if (data.post_html && document.getElementById('posts-stream')) {
        const temp = document.createElement('div');
        temp.innerHTML = data.post_html.trim();
        const newCard = temp.firstElementChild;
        document.getElementById('posts-stream').prepend(newCard);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } else {
        window.location.href = `${window.SNAPWALL_BASE}/`;
      }
    } else {
      showToast(data.error || 'Failed to post photo.');
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Post';
      }
    }
  } catch (err) {
    console.error(err);
    showToast('Network error while posting photo.');
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Post';
    }
  }
}

// Like Toggle
async function toggleLike(postId) {
  if (!window.CURRENT_USER_ID) {
    window.location.href = `${window.SNAPWALL_BASE}/login.php`;
    return;
  }
  const btn = document.getElementById(`like-btn-${postId}`);
  const countEl = document.getElementById(`like-count-${postId}`);

  try {
    const res = await fetch(`${window.SNAPWALL_BASE}/actions/like.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-CSRF-Token': window.CSRF_TOKEN
      },
      body: `post_id=${postId}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    });
    const data = await res.json();
    if (data.success) {
      if (btn) {
        btn.classList.toggle('liked', data.liked);
        const labelSpan = btn.querySelector('.like-label');
        if (labelSpan) {
          labelSpan.textContent = data.liked ? 'Liked' : 'Like';
        }
      }
      if (countEl) {
        countEl.textContent = data.like_count;
      }
    } else {
      showToast(data.error || 'Could not update like.');
    }
  } catch (err) {
    console.error(err);
  }
}

// Toggle comments focus
function toggleCommentsSection(postId) {
  const card = document.getElementById(`post-${postId}`);
  if (card) {
    const input = card.querySelector('.comment-input');
    if (input) {
      input.focus();
    }
  }
}

// Submit Comment
async function submitComment(e, postId) {
  e.preventDefault();
  if (!window.CURRENT_USER_ID) {
    window.location.href = `${window.SNAPWALL_BASE}/login.php`;
    return;
  }

  const form = e.target;
  const input = form.querySelector('input[name="comment_body"]');
  const body = input.value.trim();
  if (!body) return;

  const submitBtn = form.querySelector('button[type="submit"]');
  if (submitBtn) submitBtn.disabled = true;

  try {
    const res = await fetch(`${window.SNAPWALL_BASE}/actions/comment.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-CSRF-Token': window.CSRF_TOKEN
      },
      body: `post_id=${postId}&body=${encodeURIComponent(body)}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    });
    const data = await res.json();
    if (data.success) {
      input.value = '';
      // Append comment to DOM if not already present
      const container = document.getElementById(`comments-box-${postId}`);
      if (container && data.comment) {
        appendCommentToPost(postId, data.comment);
      }
      const countEl = document.getElementById(`comment-count-${postId}`);
      if (countEl && typeof data.comment_count !== 'undefined') {
        countEl.textContent = data.comment_count;
      }
    } else {
      showToast(data.error || 'Could not add comment.');
    }
  } catch (err) {
    console.error(err);
    showToast('Network error while posting comment.');
  } finally {
    if (submitBtn) submitBtn.disabled = false;
  }
}

function appendCommentToPost(postId, comment) {
  const container = document.getElementById(`comments-box-${postId}`);
  if (!container) return;
  if (document.getElementById(`comment-${comment.id}`)) return; // already added

  const row = document.createElement('div');
  row.className = 'comment-row';
  row.id = `comment-${comment.id}`;
  
  const canDelete = (window.CURRENT_USER_ID === comment.user_id) || !!window.CURRENT_USER_ADMIN;
  const vBadge = comment.is_verified ? `<span class="verified-badge" title="Verified"><svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-1.5px;display:inline-block;margin-left:3px;"><circle cx="12" cy="12" r="11" fill="#1D9BF0"/><path d="M9.5 16.2l-3.7-3.7 1.4-1.4 2.3 2.3 6.9-6.9 1.4 1.4-8.3 8.3z" fill="#FFFFFF"/></svg></span>` : '';
  row.innerHTML = `
    <a href="${window.SNAPWALL_BASE}/profile.php?u=${encodeURIComponent(comment.username)}" class="comment-user">
      ${escapeHtml(comment.username)}
    </a>
    ${vBadge}
    <span class="comment-body">${escapeHtml(comment.body)}</span>
    ${canDelete ? `<button type="button" class="comment-del" onclick="deleteComment(${comment.id}, ${postId})" title="Delete comment">delete</button>` : ''}
  `;
  container.appendChild(row);
}

// Delete Comment
async function deleteComment(commentId, postId) {
  try {
    const res = await fetch(`${window.SNAPWALL_BASE}/actions/delete.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-CSRF-Token': window.CSRF_TOKEN
      },
      body: `type=comment&id=${commentId}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    });
    const data = await res.json();
    if (data.success) {
      const el = document.getElementById(`comment-${commentId}`);
      if (el) el.remove();
      const countEl = document.getElementById(`comment-count-${postId}`);
      if (countEl && typeof data.comment_count !== 'undefined') {
        countEl.textContent = data.comment_count;
      }
      showToast('Comment deleted');
    } else {
      showToast(data.error || 'Failed to delete comment.');
    }
  } catch (err) {
    console.error(err);
  }
}

// Delete Post
async function deletePost(postId) {
  if (!confirm('Are you sure you want to delete this post?')) {
    return;
  }
  try {
    const res = await fetch(`${window.SNAPWALL_BASE}/actions/delete.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-CSRF-Token': window.CSRF_TOKEN
      },
      body: `type=post&id=${postId}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    });
    const data = await res.json();
    if (data.success) {
      const el = document.getElementById(`post-${postId}`);
      if (el) {
        el.style.opacity = '0';
        el.style.transform = 'scale(0.98)';
        el.style.transition = 'all 0.2s ease';
        setTimeout(() => el.remove(), 200);
      }
      showToast('Post deleted');
      // If on single post view, redirect home
      if (window.location.pathname.includes('post.php')) {
        setTimeout(() => {
          window.location.href = `${window.SNAPWALL_BASE}/`;
        }, 500);
      }
    } else {
      showToast(data.error || 'Could not delete post.');
    }
  } catch (err) {
    console.error(err);
  }
}

// Follow / Unfollow Toggle
async function toggleFollow(targetUserId, btn) {
  if (!window.CURRENT_USER_ID) {
    window.location.href = `${window.SNAPWALL_BASE}/login.php`;
    return;
  }
  try {
    const res = await fetch(`${window.SNAPWALL_BASE}/actions/follow.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-CSRF-Token': window.CSRF_TOKEN
      },
      body: `target_id=${targetUserId}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    });
    const data = await res.json();
    if (data.success) {
      // Update all follow buttons referencing this user
      const allFollowBtns = document.querySelectorAll(`[onclick*="toggleFollow(${targetUserId}"]`);
      allFollowBtns.forEach(b => {
        b.classList.toggle('following', data.is_following);
        b.textContent = data.is_following ? 'Following' : 'Follow';
      });

      // If on profile page, update follower count
      const followerCountEl = document.getElementById('profile-follower-count');
      if (followerCountEl && typeof data.follower_count !== 'undefined') {
        followerCountEl.textContent = data.follower_count;
      }
    } else {
      showToast(data.error || 'Could not update follow status.');
    }
  } catch (err) {
    console.error(err);
  }
}

// Share Post Link
function sharePost(postId) {
  const url = `${window.location.origin}${window.SNAPWALL_BASE}/post.php?id=${postId}`;
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(url).then(() => {
      showToast('Link copied to clipboard!');
    }).catch(() => {
      fallbackCopy(url);
    });
  } else {
    fallbackCopy(url);
  }
}

function fallbackCopy(text) {
  const input = document.createElement('input');
  input.value = text;
  document.body.appendChild(input);
  input.select();
  try {
    document.execCommand('copy');
    showToast('Link copied to clipboard!');
  } catch (e) {
    prompt('Copy this link:', text);
  }
  document.body.removeChild(input);
}

// Toast Notification
let toastTimer = null;
function showToast(message) {
  const toast = document.getElementById('snap-toast');
  if (!toast) return;
  toast.textContent = message;
  toast.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    toast.classList.remove('show');
  }, 2500);
}

// Escape HTML utility
function escapeHtml(str) {
  if (!str) return '';
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}


// ============================================================================
// Double-Tap Like, Bookmarks, Lightbox, Profile Editor, Stories & Dialogs
// ============================================================================

function doubleTapLike(postId, container) {
  if (!window.CURRENT_USER_ID) {
    window.location.href = `${window.SNAPWALL_BASE}/login.php`;
    return;
  }
  const heart = container ? container.querySelector('.like-burst-heart') : null;
  if (heart) {
    heart.classList.add('pop');
    setTimeout(() => heart.classList.remove('pop'), 650);
  }
  const likeBtn = document.getElementById(`like-btn-${postId}`);
  if (likeBtn && !likeBtn.classList.contains('liked')) {
    toggleLike(postId);
  }
}

async function toggleBookmark(postId, btn) {
  if (!window.CURRENT_USER_ID) {
    window.location.href = `${window.SNAPWALL_BASE}/login.php`;
    return;
  }
  try {
    const res = await fetch(`${window.SNAPWALL_BASE}/actions/bookmark.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-CSRF-Token': window.CSRF_TOKEN
      },
      body: `post_id=${postId}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    });
    const data = await res.json();
    if (data.success) {
      if (btn) {
        btn.classList.toggle('bookmarked', data.bookmarked);
        btn.textContent = data.bookmarked ? 'Saved' : 'Save';
      }
      showToast(data.bookmarked ? 'Saved to your list' : 'Removed from saved');
    } else {
      showToast(data.error || 'Could not update bookmark.');
    }
  } catch (err) {
    console.error(err);
  }
}

function openFeedLightbox(imgSrc, caption) {
  const dlg = document.getElementById('feed-lightbox-dialog');
  const img = document.getElementById('feed-lightbox-img');
  const cap = document.getElementById('feed-lightbox-caption');
  if (dlg && img) {
    img.src = imgSrc;
    if (cap) {
      cap.textContent = caption || '';
      cap.style.display = caption ? 'block' : 'none';
    }
    dlg.showModal();
  }
}

// Profile Customization Modal & Previews
function openEditProfileModal(focusTarget) {
  const dlg = document.getElementById('edit-profile-dialog');
  if (!dlg) return;
  dlg.showModal();
  if (focusTarget === 'avatar') {
    const avInput = document.getElementById('avatar-photo-input');
    if (avInput) setTimeout(() => avInput.click(), 120);
  } else if (focusTarget === 'banner') {
    const bnInput = document.getElementById('banner-photo-input');
    if (bnInput) setTimeout(() => bnInput.click(), 120);
  }
}

function previewProfileAvatar(input) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];
  const wrap = document.getElementById('edit-avatar-preview-wrap');
  if (!wrap) return;
  const reader = new FileReader();
  reader.onload = (e) => {
    const circle = wrap.querySelector('.avatar-circle');
    if (circle) {
      circle.classList.add('has-photo');
      circle.innerHTML = `<img src="${e.target.result}" alt="Avatar preview" class="avatar-photo-img">`;
    }
  };
  reader.readAsDataURL(file);
}

function previewProfileBanner(input) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];
  const box = document.getElementById('edit-banner-preview');
  if (!box) return;
  const reader = new FileReader();
  reader.onload = (e) => {
    box.style.backgroundImage = `url('${e.target.result}')`;
    box.style.backgroundSize = 'cover';
    box.style.backgroundPosition = 'center';
  };
  reader.readAsDataURL(file);
}

function setMoodPreset(text) {
  const input = document.getElementById('edit-status-mood');
  if (input) {
    input.value = text;
    input.focus();
  }
}

function shareProfileLink(username) {
  const url = `${window.location.origin}${window.SNAPWALL_BASE}/profile.php?u=${encodeURIComponent(username)}`;
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(url).then(() => {
      showToast('Profile link copied!');
    }).catch(() => fallbackCopy(url));
  } else {
    fallbackCopy(url);
  }
}

// 24h Stories Creation & Viewer
function openAddStoryModal() {
  const dlg = document.getElementById('add-story-dialog');
  if (dlg) dlg.showModal();
}

function previewStoryFile(input) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];
  const img = document.getElementById('story-preview-img');
  const prompt = document.getElementById('story-upload-prompt');
  const reader = new FileReader();
  reader.onload = (e) => {
    if (img) {
      img.src = e.target.result;
      img.style.display = 'block';
    }
    if (prompt) prompt.style.display = 'none';
  };
  reader.readAsDataURL(file);
}

async function submitStoryForm(e) {
  e.preventDefault();
  const fileInput = document.getElementById('story-file-input');
  if (!fileInput || !fileInput.files || !fileInput.files[0]) {
    showToast('Please pick a photo for your story.');
    return;
  }
  const btn = document.getElementById('story-submit-btn');
  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Sharing...';
  }

  const fd = new FormData();
  fd.append('photo', fileInput.files[0]);
  fd.append('caption', document.getElementById('story-caption-input').value.trim());
  fd.append('csrf_token', window.CSRF_TOKEN);

  try {
    const res = await fetch(`${window.SNAPWALL_BASE}/actions/story.php`, {
      method: 'POST',
      body: fd
    });
    const data = await res.json();
    if (data.success) {
      showToast('Story shared!');
      window.location.reload();
    } else {
      showToast(data.error || 'Could not post story.');
      if (btn) {
        btn.disabled = false;
        btn.textContent = 'Share to Story';
      }
    }
  } catch (err) {
    console.error(err);
    showToast('Network error while uploading story.');
    if (btn) {
      btn.disabled = false;
      btn.textContent = 'Share to Story';
    }
  }
}

let currentStoryIndex = 0;
let storyTimer = null;
let storyProgressInterval = null;

function openStoryViewer(index) {
  const stories = window.NEXLY_STORIES || [];
  if (!stories.length) return;
  const dlg = document.getElementById('view-story-dialog');
  if (!dlg) return;
  currentStoryIndex = Math.max(0, Math.min(index, stories.length - 1));
  renderStorySlide();
  if (!dlg.open) {
    dlg.showModal();
  }
}

function renderStorySlide() {
  const stories = window.NEXLY_STORIES || [];
  const st = stories[currentStoryIndex];
  if (!st) {
    closeStoryViewer();
    return;
  }

  const authorLink = document.getElementById('story-author-link');
  const authorName = document.getElementById('story-author-name');
  const timeLabel = document.getElementById('story-time-label');
  const stageImg = document.getElementById('story-stage-img');
  const stageCap = document.getElementById('story-stage-caption');
  const delBtn = document.getElementById('story-delete-btn');
  const fill = document.getElementById('story-progress-fill');

  if (authorLink) authorLink.href = `${window.SNAPWALL_BASE}/profile.php?u=${encodeURIComponent(st.username)}`;
  if (authorName) authorName.textContent = `@${st.username}`;
  if (timeLabel) timeLabel.textContent = st.time_ago;
  if (stageImg) stageImg.src = st.img_url;
  if (stageCap) {
    stageCap.textContent = st.caption || '';
    stageCap.style.display = st.caption ? 'block' : 'none';
  }
  if (delBtn) {
    delBtn.style.display = st.can_delete ? 'inline-flex' : 'none';
  }

  clearInterval(storyProgressInterval);
  clearTimeout(storyTimer);
  let pct = 0;
  if (fill) fill.style.width = '0%';
  storyProgressInterval = setInterval(() => {
    pct += 2;
    if (fill) fill.style.width = `${Math.min(100, pct)}%`;
  }, 100);
  storyTimer = setTimeout(() => {
    stepStory(1);
  }, 5000);
}

function stepStory(dir) {
  const stories = window.NEXLY_STORIES || [];
  const next = currentStoryIndex + dir;
  if (next < 0 || next >= stories.length) {
    closeStoryViewer();
    return;
  }
  currentStoryIndex = next;
  renderStorySlide();
}

function closeStoryViewer() {
  clearInterval(storyProgressInterval);
  clearTimeout(storyTimer);
  const dlg = document.getElementById('view-story-dialog');
  if (dlg && dlg.open) dlg.close();
}

async function deleteCurrentStory() {
  const stories = window.NEXLY_STORIES || [];
  const st = stories[currentStoryIndex];
  if (!st) return;
  try {
    const res = await fetch(`${window.SNAPWALL_BASE}/actions/story.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-CSRF-Token': window.CSRF_TOKEN
      },
      body: `action=delete&story_id=${st.id}&csrf_token=${encodeURIComponent(window.CSRF_TOKEN)}`
    });
    const data = await res.json();
    if (data.success) {
      closeStoryViewer();
      showToast('Story deleted');
      window.location.reload();
    }
  } catch (err) {
    console.error(err);
  }
}

// Fallback for browsers without <dialog closedby="any"> support (Modern Web Guidance)
document.addEventListener('DOMContentLoaded', () => {
  if (!('closedBy' in HTMLDialogElement.prototype)) {
    const outside = (e, dialog) => {
      const r = dialog.getBoundingClientRect();
      return e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom;
    };
    for (const dialog of document.querySelectorAll('dialog[closedby="any"]')) {
      let pressedOutside = false;
      dialog.addEventListener('pointerdown', (e) => { pressedOutside = outside(e, dialog); });
      dialog.addEventListener('pointerup', (e) => { if (pressedOutside && outside(e, dialog)) dialog.close(); });
    }
  }
});

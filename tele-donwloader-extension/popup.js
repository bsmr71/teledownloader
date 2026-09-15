/**
 * Tele Downloader — Popup Script
 * Pure Download Extension with Instant Auto-Sync (Login & Logout)
 */

document.addEventListener('DOMContentLoaded', () => {
  // ─── DOM Elements ───
  const statusBadge = document.getElementById('statusBadge');
  const quotaBadge = document.getElementById('quotaBadge');
  const quotaText = document.getElementById('quotaText');
  const webAuthBtn = document.getElementById('webAuthBtn');
  const webAuthLabel = document.getElementById('webAuthLabel');
  const totalResourcesText = document.getElementById('totalResourcesText');
  const breakdownText = document.getElementById('breakdownText');
  const announcementBanner = document.getElementById('announcementBanner');
  const announcementText = document.getElementById('announcementText');
  const appHeaderTitle = document.getElementById('appHeaderTitle');
  const footerWebLink = document.getElementById('footerWebLink');

  const filterAll = document.getElementById('filterAll');
  const filterPhotos = document.getElementById('filterPhotos');
  const filterVideos = document.getElementById('filterVideos');

  const selectAllCheckbox = document.getElementById('selectAllCheckbox');
  const downloadSelectedBtn = document.getElementById('downloadSelectedBtn');
  const downloadSelectedLabel = document.getElementById('downloadSelectedLabel');
  const downloadAllBtn = document.getElementById('downloadAllBtn');
  const scanBtn = document.getElementById('scanBtn');

  const mediaList = document.getElementById('mediaList');
  const mediaEmpty = document.getElementById('mediaEmpty');
  const downloadCountEl = document.getElementById('downloadCount');
  const resetStatsBtn = document.getElementById('resetStatsBtn');
  const openHelpBtn = document.getElementById('openHelpBtn');

  // ─── API Multi-Host Candidates (Auto-probed) ───
  const CANDIDATE_API_URLS = [
    'http://tele-downloader-backend.test/api/v1',
    'http://localhost:8000/api/v1',
    'http://127.0.0.1:8000/api/v1',
    'http://localhost/tele-downloader-backend/public/api/v1'
  ];
  let activeApiBaseUrl = 'http://tele-downloader-backend.test/api/v1';

  // ─── State ───
  let currentMedia = [];
  let selectedIndices = new Set();
  let currentFilter = 'all'; // 'all' | 'photo' | 'video'
  let authToken = null;
  let currentUser = null;
  let deviceId = null;

  let subscription = {
    isPro: false,
    plan: 'free',
    planName: 'Free Tier',
    isUnlimited: false,
    dailyLimit: 10,
    usedToday: 0,
    remainingToday: 10,
    canDownload: true,
    expiresAt: null,
  };

  // ══════════════════════════════════════════════════════════════════
  //  DEVICE ID & AUTH TOKEN MANAGEMENT
  // ══════════════════════════════════════════════════════════════════

  async function loadAuthAndDevice() {
    try {
      const stored = await chrome.storage.local.get(['authToken', 'currentUser', 'deviceId', 'cachedApiUrl']);
      
      if (stored.cachedApiUrl) {
        activeApiBaseUrl = stored.cachedApiUrl;
      }

      if (stored.deviceId) {
        deviceId = stored.deviceId;
      } else {
        deviceId = 'dev_' + Math.random().toString(36).substring(2, 15) + Date.now().toString(36);
        await chrome.storage.local.set({ deviceId });
      }

      authToken = stored.authToken || null;
      currentUser = stored.currentUser || null;
    } catch (e) {
      console.warn('Error loading auth/device info:', e);
    }
  }

  function getApiHeaders() {
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Device-ID': deviceId || 'unknown_device',
    };
    if (authToken) {
      headers['Authorization'] = `Bearer ${authToken}`;
    }
    return headers;
  }

  async function apiFetch(endpoint, options = {}) {
    const urlsToTry = [activeApiBaseUrl, ...CANDIDATE_API_URLS.filter(u => u !== activeApiBaseUrl)];
    let lastError = null;

    for (const baseUrl of urlsToTry) {
      try {
        const url = `${baseUrl}${endpoint}`;
        const headers = { ...getApiHeaders(), ...(options.headers || {}) };
        
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000);

        const res = await fetch(url, {
          ...options,
          headers,
          signal: controller.signal,
        });
        clearTimeout(timeoutId);

        activeApiBaseUrl = baseUrl;
        chrome.storage.local.set({ cachedApiUrl: baseUrl });
        return res;
      } catch (err) {
        lastError = err;
      }
    }

    throw lastError || new Error('Tidak dapat terhubung ke server backend.');
  }

  // ══════════════════════════════════════════════════════════════════
  //  ACTIVE TAB WEB LOGIN/LOGOUT AUTO-DETECTION
  // ══════════════════════════════════════════════════════════════════

  async function autoDetectWebTabLogin() {
    try {
      const tabs = await chrome.tabs.query({});
      const backendTabs = tabs.filter(t => t.url && (
        t.url.includes('tele-downloader-backend.test') || 
        t.url.includes('localhost:8000') || 
        t.url.includes('127.0.0.1:8000') ||
        t.url.includes('localhost/tele-downloader-backend')
      ));

      for (const tab of backendTabs) {
        try {
          const results = await chrome.scripting.executeScript({
            target: { tabId: tab.id },
            func: () => {
              const userMeta = document.querySelector('meta[name="tld-auth-user"]');
              const guestMeta = document.querySelector('meta[name="tld-auth-guest"]');
              const isLogin = !!document.querySelector('form[action*="login"]');
              return {
                userContent: userMeta ? userMeta.content : null,
                isGuest: !!guestMeta || isLogin
              };
            }
          });

          if (results && results[0] && results[0].result) {
            const { userContent, isGuest } = results[0].result;
            if (userContent) {
              const data = JSON.parse(userContent);
              if (data && data.token && data.user) {
                authToken = data.token;
                currentUser = data.user;
                await chrome.storage.local.set({ authToken, currentUser });
                console.log('[Tele Downloader] Auto-synced login from tab:', data.user.email);
                return true;
              }
            } else if (isGuest) {
              authToken = null;
              currentUser = null;
              await chrome.storage.local.remove(['authToken', 'currentUser']);
              console.log('[Tele Downloader] Auto-synced logout from tab.');
              return false;
            }
          }
        } catch (tabErr) {
          console.warn('Tab inspection error:', tabErr);
        }
      }
    } catch (e) {
      console.warn('Auto-detect tabs error:', e);
    }
    return false;
  }

  // ══════════════════════════════════════════════════════════════════
  //  BACKEND SYNC: STATUS & CONFIG
  // ══════════════════════════════════════════════════════════════════

  async function fetchConfig() {
    try {
      const res = await apiFetch('/config');
      const data = await res.json();

      if (data && data.success) {
        if (data.app_name && appHeaderTitle) {
          appHeaderTitle.textContent = data.app_name;
        }
        if (data.announcement) {
          announcementText.textContent = data.announcement;
          announcementBanner.style.display = 'flex';
        } else {
          announcementBanner.style.display = 'none';
        }
      }
    } catch (e) {
      console.warn('Backend config offline.');
    }
  }

  async function syncStatusWithBackend() {
    try {
      const res = await apiFetch('/status');
      const data = await res.json();

      if (data && data.success) {
        if (data.token && data.user) {
          authToken = data.token;
          currentUser = data.user;
          await chrome.storage.local.set({ authToken, currentUser });
        } else if (!data.user) {
          // If server reports no authenticated user, purge token from storage
          authToken = null;
          currentUser = null;
          await chrome.storage.local.remove(['authToken', 'currentUser']);
        }

        subscription.isPro = data.is_pro || false;
        subscription.plan = data.plan || 'free';
        subscription.planName = data.plan_name || (data.is_pro ? 'PRO' : 'Free Tier');
        subscription.isUnlimited = data.is_unlimited ?? (!data.daily_limit && data.is_pro);
        subscription.dailyLimit = data.daily_limit ?? 10;
        subscription.usedToday = data.used_today ?? 0;
        subscription.remainingToday = data.remaining_today ?? 0;
        subscription.canDownload = data.can_download ?? true;
        subscription.expiresAt = data.expires_at || null;

        await chrome.storage.local.set({ subscription });
        updateUI();
        return;
      }
    } catch (e) {
      console.warn('Could not sync status with backend:', e);
    }

    const stored = await chrome.storage.local.get('subscription');
    if (stored.subscription) {
      subscription = { ...subscription, ...stored.subscription };
    }
    updateUI();
  }

  function updateUI() {
    // 1. Quota Pill
    if (subscription.isPro) {
      quotaBadge.className = 'quota-pill pro';
      if (subscription.isUnlimited || !subscription.dailyLimit) {
        quotaText.innerHTML = '👑 PRO (Unlimited)';
        quotaBadge.title = 'Akses Pro: Download tanpa batas selamanya!';
      } else {
        quotaText.innerHTML = `👑 PRO (${subscription.remainingToday}/${subscription.dailyLimit})`;
        quotaBadge.title = `Paket PRO: Tersisa ${subscription.remainingToday} dari ${subscription.dailyLimit} kuota foto/video hari ini.`;
      }
    } else {
      quotaBadge.className = 'quota-pill';
      const rem = Math.max(0, subscription.dailyLimit - subscription.usedToday);
      quotaText.textContent = `${rem}/${subscription.dailyLimit} Free`;
      quotaBadge.title = `Tersisa ${rem} dari ${subscription.dailyLimit} kuota download gratis hari ini.`;
    }

    // 2. Web Auth / Account Direct Button
    if (authToken && currentUser) {
      webAuthBtn.className = 'web-action-btn logged-in';
      const firstName = (currentUser.name || 'Akun').split(' ')[0];
      const isProBadge = subscription.isPro ? '👑' : '👤';
      webAuthLabel.innerHTML = `${isProBadge} ${firstName} ↗`;
      
      const targetUrl = (currentUser.role === 'admin') 
        ? 'http://tele-downloader-backend.test/admin' 
        : 'http://tele-downloader-backend.test/member';
      
      webAuthBtn.title = `Buka Dashboard (${currentUser.email})`;
      footerWebLink.href = targetUrl;
      footerWebLink.textContent = 'Dashboard Web ↗';
    } else {
      webAuthBtn.className = 'web-action-btn';
      webAuthLabel.innerHTML = 'Masuk / Upgrade ↗';
      webAuthBtn.title = 'Buka Website untuk Masuk / Upgrade Paket';
      footerWebLink.href = 'http://tele-downloader-backend.test/login';
      footerWebLink.textContent = 'Portal Web ↗';
    }
  }

  // ══════════════════════════════════════════════════════════════════
  //  DIRECT WEB REDIRECTS
  // ══════════════════════════════════════════════════════════════════

  webAuthBtn.addEventListener('click', () => {
    let url = 'http://tele-downloader-backend.test/login';
    if (authToken && currentUser) {
      url = (currentUser.role === 'admin') 
        ? 'http://tele-downloader-backend.test/admin' 
        : 'http://tele-downloader-backend.test/member';
    }
    chrome.tabs.create({ url });
  });

  // ══════════════════════════════════════════════════════════════════
  //  DOWNLOAD VALIDATION & BACKEND TRACKING
  // ══════════════════════════════════════════════════════════════════

  async function canDownload(count = 1) {
    if (subscription.isPro && (subscription.isUnlimited || !subscription.dailyLimit)) {
      return true;
    }

    if (subscription.remainingToday < count) {
      const redirectUrl = (authToken && currentUser) 
        ? (currentUser.role === 'admin' ? 'http://tele-downloader-backend.test/admin' : 'http://tele-downloader-backend.test/member')
        : 'http://tele-downloader-backend.test/login';

      alert(`⚠️ Kuota unduhan hari ini telah habis (${subscription.usedToday}/${subscription.dailyLimit} file).\n\nMembuka website untuk upgrade paket...`);
      chrome.tabs.create({ url: redirectUrl });
      return false;
    }

    return true;
  }

  async function notifyDownloadBackend(count = 1) {
    try {
      const res = await apiFetch('/download/track', {
        method: 'POST',
        body: JSON.stringify({ count, device_id: deviceId })
      });

      const data = await res.json();
      if (data && data.success) {
        subscription.usedToday = data.used_today;
        subscription.remainingToday = data.remaining;
        await chrome.storage.local.set({ subscription });
        updateUI();
      }
    } catch (e) {
      console.warn('Failed to record download to backend:', e);
    }
  }

  // ══════════════════════════════════════════════════════════════════
  //  MEDIA RENDERING & DOWNLOAD HANDLERS
  // ══════════════════════════════════════════════════════════════════

  async function fetchMedia() {
    try {
      const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
      if (!tab) return;

      const response = await chrome.tabs.sendMessage(tab.id, { action: 'getMedia' });
      if (response && response.media) {
        currentMedia = response.media.map((m, idx) => ({ ...m, index: idx }));
        renderMediaList();
      }
    } catch (e) {
      console.warn('Could not fetch media from active tab:', e);
    }
  }

  function renderMediaList() {
    const filtered = currentMedia.filter(m => {
      if (currentFilter === 'photo') return m.type === 'photo';
      if (currentFilter === 'video') return m.type === 'video';
      return true;
    });

    totalResourcesText.textContent = `${filtered.length} media ditemukan`;
    const photos = filtered.filter(m => m.type === 'photo').length;
    const videos = filtered.filter(m => m.type === 'video').length;
    breakdownText.textContent = `${photos} foto · ${videos} video`;

    if (filtered.length === 0) {
      mediaList.innerHTML = '';
      mediaList.appendChild(mediaEmpty);
      mediaEmpty.style.display = 'block';
      updateBatchButtons();
      return;
    }

    mediaEmpty.style.display = 'none';
    mediaList.innerHTML = '';

    filtered.forEach(item => {
      const card = document.createElement('div');
      const isSelected = selectedIndices.has(item.index);
      card.className = `media-card ${isSelected ? 'selected' : ''}`;

      const thumbSrc = item.thumbnail || item.url || '';
      const typeBadge = item.type === 'video' ? 'VIDEO' : 'FOTO';

      card.innerHTML = `
        <label class="custom-checkbox-label">
          <input type="checkbox" class="item-checkbox" data-index="${item.index}" ${isSelected ? 'checked' : ''}>
          <span class="custom-checkbox"></span>
        </label>
        <div class="media-thumb-box">
          <img src="${thumbSrc}" class="media-thumb" onerror="this.src='icons/icon48.png'">
          <span class="media-type-badge">${typeBadge}</span>
        </div>
        <div class="media-info">
          <div class="media-filename" title="${item.filename || 'Telegram Media'}">${item.filename || 'Telegram Media'}</div>
          <div class="media-meta">${item.dimensions || ''} · ${item.size || ''}</div>
        </div>
        <button class="media-action-btn single-download-btn" data-index="${item.index}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
          </svg>
          Unduh
        </button>
      `;

      card.querySelector('.item-checkbox').addEventListener('change', (e) => {
        if (e.target.checked) {
          selectedIndices.add(item.index);
        } else {
          selectedIndices.delete(item.index);
        }
        card.classList.toggle('selected', e.target.checked);
        updateBatchButtons();
      });

      card.querySelector('.single-download-btn').addEventListener('click', async () => {
        if (!(await canDownload(1))) return;
        await downloadSingleMedia(item);
        await notifyDownloadBackend(1);
      });

      mediaList.appendChild(card);
    });

    updateBatchButtons();
  }

  function updateBatchButtons() {
    const count = selectedIndices.size;
    downloadSelectedBtn.disabled = count === 0;
    downloadSelectedLabel.textContent = `Unduh Pilihan (${count})`;

    const filtered = currentMedia.filter(m => {
      if (currentFilter === 'photo') return m.type === 'photo';
      if (currentFilter === 'video') return m.type === 'video';
      return true;
    });

    selectAllCheckbox.checked = filtered.length > 0 && filtered.every(m => selectedIndices.has(m.index));
  }

  selectAllCheckbox.addEventListener('change', (e) => {
    const filtered = currentMedia.filter(m => {
      if (currentFilter === 'photo') return m.type === 'photo';
      if (currentFilter === 'video') return m.type === 'video';
      return true;
    });

    if (e.target.checked) {
      filtered.forEach(m => selectedIndices.add(m.index));
    } else {
      filtered.forEach(m => selectedIndices.delete(m.index));
    }
    renderMediaList();
  });

  downloadSelectedBtn.addEventListener('click', async () => {
    const selected = currentMedia.filter(m => selectedIndices.has(m.index));
    if (selected.length === 0) return;

    if (!(await canDownload(selected.length))) return;

    downloadSelectedBtn.disabled = true;
    downloadSelectedBtn.textContent = `Mengunduh (${selected.length})...`;

    try {
      await chrome.runtime.sendMessage({ action: 'batchDownload', items: selected });
      await notifyDownloadBackend(selected.length);
    } catch (err) {
      console.warn('Batch download failed:', err);
    }

    downloadSelectedBtn.disabled = false;
    downloadSelectedLabel.textContent = `Unduh Pilihan (${selectedIndices.size})`;
  });

  downloadAllBtn.addEventListener('click', async () => {
    const filtered = currentMedia.filter(m => {
      if (currentFilter === 'photo') return m.type === 'photo';
      if (currentFilter === 'video') return m.type === 'video';
      return true;
    });

    if (filtered.length === 0) return;
    if (!(await canDownload(filtered.length))) return;

    downloadAllBtn.disabled = true;
    downloadAllBtn.textContent = 'Mengunduh Semua...';

    try {
      await chrome.runtime.sendMessage({ action: 'batchDownload', items: filtered });
      await notifyDownloadBackend(filtered.length);
    } catch (err) {
      console.warn('Download all failed:', err);
    }

    downloadAllBtn.disabled = false;
    downloadAllBtn.textContent = 'Unduh Semua';
  });

  async function downloadSingleMedia(item) {
    try {
      if (item.dataUrl) {
        await chrome.runtime.sendMessage({ action: 'downloadBlob', dataUrl: item.dataUrl, filename: item.filename, type: item.type });
      } else {
        await chrome.runtime.sendMessage({ action: 'download', url: item.url, filename: item.filename, type: item.type });
      }
    } catch (e) {
      console.warn('Download single error:', e);
    }
  }

  scanBtn.addEventListener('click', async () => {
    scanBtn.classList.add('spinning');
    try {
      const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
      if (tab) {
        await chrome.tabs.sendMessage(tab.id, { action: 'scanMedia' });
        await new Promise(r => setTimeout(r, 600));
        await fetchMedia();
      }
    } catch (e) {
      console.warn('Scan failed:', e);
    }
    setTimeout(() => scanBtn.classList.remove('spinning'), 500);
  });

  [filterAll, filterPhotos, filterVideos].forEach(btn => {
    btn.addEventListener('click', () => {
      [filterAll, filterPhotos, filterVideos].forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentFilter = btn.dataset.filter;
      renderMediaList();
    });
  });

  resetStatsBtn.addEventListener('click', async () => {
    try {
      await chrome.runtime.sendMessage({ action: 'resetStats' });
      downloadCountEl.textContent = '0';
    } catch (e) {}
  });

  openHelpBtn.addEventListener('click', () => {
    alert("Bantuan Tele Downloader PRO:\n\n1. Buka Telegram Web di tab browser.\n2. Buka chat atau channel yang memuat foto/video.\n3. Media akan langsung muncul di sini dan siap diunduh 1-klik!\n4. Untuk upgrade kuota/paket, klik tombol Masuk/Upgrade di pojok kanan atas.");
  });

  async function checkTelegramConnection() {
    try {
      const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
      if (tab && tab.url && (tab.url.includes('web.telegram.org') || tab.url.includes('telegram.org'))) {
        statusBadge.className = 'status-indicator online';
        statusBadge.querySelector('.status-text').textContent = 'Terhubung';
        return true;
      }
    } catch (e) {}

    statusBadge.className = 'status-indicator';
    statusBadge.querySelector('.status-text').textContent = 'Standby';
    return false;
  }

  // ══════════════════════════════════════════════════════════════════
  //  INITIALIZATION
  // ══════════════════════════════════════════════════════════════════

  async function init() {
    await loadAuthAndDevice();
    await autoDetectWebTabLogin();
    await fetchConfig();
    await syncStatusWithBackend();
    await checkTelegramConnection();
    await fetchMedia();
  }

  init();
});

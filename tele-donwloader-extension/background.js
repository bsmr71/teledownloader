/**
 * Tele Downloader — Background Service Worker
 * Handles download requests from content script and popup, and auto-syncs web auth & logout.
 */

// ── Download counter ──
let downloadCount = 0;

async function loadDownloadCount() {
  const result = await chrome.storage.local.get('downloadCount');
  downloadCount = result.downloadCount || 0;
}

async function incrementDownloadCount() {
  downloadCount++;
  await chrome.storage.local.set({ downloadCount });
  updateBadge();
}

function updateBadge() {
  if (downloadCount > 0) {
    chrome.action.setBadgeText({ text: String(downloadCount) });
    chrome.action.setBadgeBackgroundColor({ color: '#2AABEE' });
  }
}

// ── Message handler ──
chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
  if (message.action === 'webAuthSync') {
    chrome.storage.local.set({
      authToken: message.token,
      currentUser: message.user
    }, () => {
      console.log('[TeleDownloader] Web Auth synced to extension storage:', message.user.email);
      sendResponse({ success: true });
    });
    return true;
  }

  if (message.action === 'webAuthLogout') {
    chrome.storage.local.remove(['authToken', 'currentUser'], () => {
      console.log('[TeleDownloader] Web Auth cleared from storage.');
      sendResponse({ success: true });
    });
    return true;
  }

  if (message.action === 'download') {
    handleDownload(message)
      .then(result => sendResponse(result))
      .catch(err => sendResponse({ success: false, error: err.message }));
    return true;
  }

  if (message.action === 'batchDownload') {
    handleBatchDownload(message.items)
      .then(result => sendResponse(result))
      .catch(err => sendResponse({ success: false, error: err.message }));
    return true;
  }

  if (message.action === 'getStats') {
    sendResponse({ downloadCount });
    return false;
  }

  if (message.action === 'resetStats') {
    downloadCount = 0;
    chrome.storage.local.set({ downloadCount: 0 });
    chrome.action.setBadgeText({ text: '' });
    sendResponse({ success: true });
    return false;
  }

  if (message.action === 'downloadBlob') {
    handleBlobDownload(message)
      .then(result => sendResponse(result))
      .catch(err => sendResponse({ success: false, error: err.message }));
    return true;
  }
});

// ── Download a single file ──
async function handleDownload({ url, filename, type }) {
  try {
    const ext = getExtension(type, filename);
    const safeName = sanitizeFilename(filename || `telegram_${type}_${Date.now()}`);
    const finalName = safeName.includes('.') ? safeName : `${safeName}${ext}`;

    await chrome.downloads.download({
      url: url,
      filename: finalName,
      saveAs: false,
      conflictAction: 'uniquify'
    });

    await incrementDownloadCount();
    return { success: true };
  } catch (err) {
    console.error('[TeleDownloader] Download error:', err);
    return { success: false, error: err.message };
  }
}

// ── Download blob data (base64) ──
async function handleBlobDownload({ dataUrl, filename, type }) {
  try {
    const ext = getExtension(type, filename, dataUrl);
    const safeName = sanitizeFilename(filename || `telegram_${type}_${Date.now()}`);
    const finalName = safeName.includes('.') ? safeName : `${safeName}${ext}`;

    await chrome.downloads.download({
      url: dataUrl,
      filename: finalName,
      saveAs: false,
      conflictAction: 'uniquify'
    });

    await incrementDownloadCount();
    return { success: true };
  } catch (err) {
    console.error('[TeleDownloader] Blob download error:', err);
    return { success: false, error: err.message };
  }
}

// ── Batch download ──
async function handleBatchDownload(items) {
  const results = [];
  for (const item of items) {
    try {
      if (results.length > 0) {
        await new Promise(r => setTimeout(r, 300));
      }

      if (item.dataUrl) {
        const result = await handleBlobDownload(item);
        results.push(result);
      } else {
        const result = await handleDownload(item);
        results.push(result);
      }
    } catch (err) {
      results.push({ success: false, error: err.message });
    }
  }

  const successCount = results.filter(r => r.success).length;
  return {
    success: true,
    total: items.length,
    downloaded: successCount,
    failed: items.length - successCount
  };
}

// ── Helpers ──
function getExtension(type, filename, dataUrl) {
  if (filename && filename.includes('.')) return '';
  
  if (dataUrl && dataUrl.startsWith('data:')) {
    const mimeMatch = dataUrl.match(/^data:([^;,]+)/);
    if (mimeMatch) {
      const mime = mimeMatch[1];
      const mimeExtMap = {
        'video/mp4': '.mp4',
        'video/webm': '.webm',
        'video/ogg': '.ogg',
        'video/quicktime': '.mov',
        'image/jpeg': '.jpg',
        'image/png': '.png',
        'image/gif': '.gif',
        'image/webp': '.webp',
      };
      if (mimeExtMap[mime]) return mimeExtMap[mime];
    }
  }
  
  const extMap = {
    'photo': '.jpg',
    'video': '.mp4',
    'gif': '.mp4',
    'document': '',
    'profile': '.jpg',
    'story': '.jpg',
    'story_video': '.mp4'
  };
  return extMap[type] || '.jpg';
}

function sanitizeFilename(name) {
  return name
    .replace(/[<>:"/\\|?*\x00-\x1F]/g, '_')
    .replace(/_{2,}/g, '_')
    .replace(/^_|_$/g, '')
    .substring(0, 200);
}

// ── Init ──
loadDownloadCount().then(() => {
  updateBadge();
  console.log('[TeleDownloader] Service worker initialized with bi-directional auto-sync');
});

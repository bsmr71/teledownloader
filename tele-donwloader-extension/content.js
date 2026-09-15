/**
 * Tele Downloader — Content Script
 * Injected into Telegram Web to detect and enable downloading of media.
 * 
 * Strategies:
 * 1. MutationObserver to detect new media elements
 * 2. Blob URL interception via prototype override
 * 3. Canvas capture for restricted content
 * 4. Event override for right-click protection bypass
 */

(function () {
  'use strict';

  // ══════════════════════════════════════════════
  //  SEAMLESS BACKGROUND WORKER MODE
  // ══════════════════════════════════════════════
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('tld_worker') === '1') {
    console.log('[TeleDownloader] 🤖 Background Worker Started');
    document.documentElement.style.opacity = '0'; // Hide everything visually
    document.documentElement.style.pointerEvents = 'none';

    window.addEventListener('message', async (e) => {
      // Only accept messages from the same origin (the parent window)
      if (e.origin !== window.location.origin) return;
      
      if (e.data.action === 'SYNC_SCROLL') {
        const scrollContainer = document.querySelector('.Scrollable, .chat-list, .media-grid, [class*="scroll"], [class*="Scroll"]');
        if (scrollContainer) {
          scrollContainer.scrollTop = e.data.scrollTop;
          console.log('[TeleDownloader] Worker synced scroll to', e.data.scrollTop);
        }
      }

      if (e.data.action === 'EXTRACT_HD') {
        const { stableId, originalSrc } = e.data;
        let targetElement = null;

        // Extract base ID (e.g. "123456" from "123456_0")
        const parts = stableId.split('_');
        const baseId = parts[0];
        const idx = parts.length > 1 ? parseInt(parts[parts.length - 1], 10) : -1;

        // Try to find the container
        const containers = document.querySelectorAll(`[data-message-id="${baseId}"], [data-mid="${baseId}"], [data-msg-id="${baseId}"], #message${baseId}, a[href*="${baseId}"]`);
        
        for (const container of containers) {
           const mediaElements = Array.from(container.querySelectorAll('img, video, canvas')).filter(el => el.getBoundingClientRect().width > 30);
           
           if (idx >= 0 && idx < mediaElements.length) {
               targetElement = mediaElements[idx];
               break;
           }

           for (const el of mediaElements) {
              if (originalSrc && el.src && el.src.includes(originalSrc.substring(originalSrc.length - 20))) {
                 targetElement = el; break;
              }
              if (!originalSrc && el.tagName === 'VIDEO') {
                 targetElement = el; break;
              }
           }
           if (!targetElement && mediaElements.length > 0) targetElement = mediaElements[0];
           if (targetElement) break;
        }

        if (!targetElement) {
           window.parent.postMessage({ action: 'HD_RESULT', stableId, hdSrc: originalSrc }, '*');
           return;
        }

        const clickTarget = targetElement.closest('a, .media-inner, .Media, .SharedMediaItem, .GridItem, .message, .Album-item') || targetElement;
        clickTarget.click();

        const startTime = Date.now();
        const maxWait = 5000;
        const check = () => {
          const viewerMedia = document.querySelectorAll('.media-viewer img, .media-viewer video, .MediaViewer img, .MediaViewer video, [class*="viewer"] img, [class*="viewer"] video, .PhotoViewer img, .PhotoViewer video');
          let hdSrc = null;
          for (let m of viewerMedia) {
            if (m.src && m.src.startsWith('blob:') && m.src !== originalSrc) {
              hdSrc = m.src; break;
            }
          }

          if (hdSrc || (Date.now() - startTime > maxWait)) {
            if (!hdSrc && viewerMedia.length > 0) hdSrc = viewerMedia[0].src;
            document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true, cancelable: true }));
            const closeBtn = document.querySelector('.media-viewer-close, .button-close, [class*="close"], [class*="Close"]');
            if (closeBtn) closeBtn.click();
            
            const finish = async () => {
              let finalDataUrl = null;
              if (hdSrc && hdSrc.startsWith('blob:')) {
                 try {
                    const res = await downloadBlobViaMainWorld(hdSrc, 'temp');
                    if (res && res.success) finalDataUrl = res.dataUrl;
                 } catch(e) {}
              }
              window.parent.postMessage({ action: 'HD_RESULT', stableId, hdSrc, dataUrl: finalDataUrl }, '*');
            };
            setTimeout(finish, 300);
            return;
          }
          setTimeout(check, 100);
        };
        check();
      }
    });

    // Halt further execution of the main script (no UI injection in worker)
    return;
  }

  // ══════════════════════════════════════════════
  //  MAIN UI MODE
  // ══════════════════════════════════════════════

  // Prevent double injection
  if (window.__teleDownloaderInjected) return;
  window.__teleDownloaderInjected = true;

  // ══════════════════════════════════════════════
  //  CONSTANTS
  // ══════════════════════════════════════════════
  const SELECTORS = {
    // Telegram Web K (newer) selectors — using attribute & tag-based selectors for resilience
    mediaPhoto: 'img.media-photo, .media-inner img, img[src^="blob:"], .media-container img, .Album img, [class*="album"] img, [class*="Album"] img, .Message img',
    mediaVideo: 'video, .media-inner video, video[src^="blob:"], .Album video, [class*="album"] video, [class*="Album"] video, .Message video',
    mediaCanvas: 'canvas',
    profileAvatar: '.avatar-photo, .profile-avatar img, .ChatInfo img, .peer-photo img, img.avatar-photo',
    storyContainer: '.story-viewer, .stories-viewer, [class*="story"]',
    messageMedia: '.message .media-inner, .Message .media-inner, [class*="media-inner"], [class*="MediaInner"]',
    chatMessages: '.messages-container, .MessageList, [class*="message-list"]',
    documentThumb: '.document-thumb, [class*="document"] img',
    gifMedia: 'video.media-gif, [class*="gif"] video',
    stickerMedia: 'img.sticker-media, [class*="sticker"] img',
  };

  const ICON_DOWNLOAD = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>`;

  const ICON_BATCH = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/><line x1="3" y1="1" x2="21" y2="1"/></svg>`;

  const ICON_CHECK = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`;

  // ══════════════════════════════════════════════
  //  STATE
  // ══════════════════════════════════════════════
  const processedElements = new WeakSet();
  const detectedMedia = [];
  const globalSelectedMedia = new Map(); // Tracks selected items across scrolling/virtualization

  // ══════════════════════════════════════════════
  //  MAIN WORLD BRIDGE (communicates with inject.js)
  // ══════════════════════════════════════════════
  // inject.js runs in the MAIN world where Telegram's blobs live.
  // Downloads happen IN the main world via <a> tag — no cross-world data transfer needed.

  let requestIdCounter = 0;

  /**
   * Send a command to inject.js and wait for a response.
   */
  function sendToMainWorld(eventName, data, responseEvent = '__tld_download_result', timeoutMs = 30000) {
    return new Promise((resolve, reject) => {
      const requestId = `tld_${++requestIdCounter}_${Date.now()}`;

      const handler = (e) => {
        if (e.detail && e.detail.requestId === requestId) {
          window.removeEventListener(responseEvent, handler);
          clearTimeout(timer);
          resolve(e.detail);
        }
      };

      window.addEventListener(responseEvent, handler);

      const timer = setTimeout(() => {
        window.removeEventListener(responseEvent, handler);
        reject(new Error('Request timed out'));
      }, timeoutMs);

      window.dispatchEvent(new CustomEvent(eventName, {
        detail: { ...data, requestId }
      }));
    });
  }

  /**
   * Download a blob URL via inject.js (main world)
   */
  function downloadBlobViaMainWorld(blobUrl, filename) {
    return sendToMainWorld('__tld_download_blob_url', { blobUrl, filename });
  }

  /**
   * Download the latest video blob from registry
   */
  function downloadLatestVideoViaMainWorld(filename) {
    return sendToMainWorld('__tld_download_latest_video', { filename });
  }

  /**
   * Download a specific video element by its index
   */
  function downloadVideoElementViaMainWorld(videoIndex, filename) {
    return sendToMainWorld('__tld_download_video_element', { videoIndex, filename });
  }

  /**
   * Get info about all video elements on the page
   */
  function getVideoInfoFromMainWorld() {
    return sendToMainWorld('__tld_get_video_info', {}, '__tld_video_info_result', 5000);
  }

  // ══════════════════════════════════════════════
  //  MEDIA DETECTION
  // ══════════════════════════════════════════════

  // Find the message bubble that contains this element
  function getMessageBubble(element) {
    return element.closest(
      '.message, .Message, [class*="message "], [class*="Message "], ' +
      '[class*="bubble"], [class*="Bubble"], [class*="ListItem"], ' +
      '.media-inner, [class*="media-inner"], [class*="MediaInner"]'
    );
  }

  // Track which message bubbles already have a download button
  const processedBubbles = new WeakSet();

  function detectMediaType(element) {
    if (element.tagName === 'VIDEO') {
      // Check if it's a GIF (Telegram renders GIFs as muted looping videos)
      const isGif = element.loop && element.muted && !element.controls;
      const parentHasGif = element.closest('[class*="gif"], [class*="Gif"]');
      return (isGif || parentHasGif) ? 'gif' : 'video';
    }
    if (element.tagName === 'IMG') {
      const isAvatar = element.classList.contains('avatar-photo') || 
                       element.closest('.avatar-photo, .Avatar, .profile-avatar, .peer-photo');
      // If it's a media-photo, it's definitely not a profile pic even if it's inside a profile wrapper
      if (isAvatar && !element.classList.contains('media-photo') && !element.closest('.media-photo')) return 'profile';
      
      if (element.closest('.Sticker, .sticker-media, [class*="sticker"]')) return 'sticker';
      return 'photo';
    }
    if (element.tagName === 'CANVAS') {
      // Telegram Web uses Canvas to render inline looping videos and gifs
      return 'video';
    }
    return 'photo';
  }

  function getMediaSource(element) {
    if (element.tagName === 'VIDEO') {
      // Try multiple sources for video
      return element.currentSrc || element.src || element.querySelector('source')?.src || null;
    }
    if (element.tagName === 'IMG') {
      return element.src || element.dataset?.src;
    }
    if (element.tagName === 'CANVAS') {
      try {
        return element.toDataURL('image/png');
      } catch (e) {
        return null;
      }
    }

    // Check for background-image
    const bgImage = window.getComputedStyle(element).backgroundImage;
    if (bgImage && bgImage !== 'none') {
      const match = bgImage.match(/url\(["']?(.+?)["']?\)/);
      return match ? match[1] : null;
    }

    return null;
  }

  // Wait for video source to become available
  function waitForVideoSource(video, timeoutMs = 5000) {
    return new Promise((resolve) => {
      const src = video.currentSrc || video.src;
      if (src) return resolve(src);

      const startTime = Date.now();
      const check = () => {
        const s = video.currentSrc || video.src;
        if (s) return resolve(s);
        if (Date.now() - startTime > timeoutMs) return resolve(null);
        setTimeout(check, 200);
      };

      // Also listen for loadeddata
      video.addEventListener('loadeddata', () => {
        resolve(video.currentSrc || video.src);
      }, { once: true });

      check();
    });
  }

  // Capture media from canvas when direct URL access fails (PHOTOS ONLY)
  async function captureFromCanvas(element) {
    // Never use canvas capture for videos — it produces a single frame PNG
    if (element.tagName === 'VIDEO') return null;

    return new Promise((resolve) => {
      try {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        if (element.tagName === 'IMG') {
          canvas.width = element.naturalWidth || element.width;
          canvas.height = element.naturalHeight || element.height;
          ctx.drawImage(element, 0, 0);
        } else if (element.tagName === 'CANVAS') {
          canvas.width = element.width;
          canvas.height = element.height;
          ctx.drawImage(element, 0, 0);
        }

        resolve(canvas.toDataURL('image/png', 1.0));
      } catch (e) {
        resolve(null);
      }
    });
  }

  // ══════════════════════════════════════════════
  //  DOWNLOAD BUTTON INJECTION
  // ══════════════════════════════════════════════

  function createDownloadButton(mediaElement, mediaType) {
    const btn = document.createElement('button');
    btn.className = 'tld-download-btn';
    btn.innerHTML = ICON_DOWNLOAD;
    btn.title = `Download ${mediaType}`;
    btn.dataset.tldType = mediaType;

    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();

      btn.classList.add('tld-downloading');
      btn.innerHTML = `<span class="tld-spinner"></span>`;

      try {
        // Re-detect logic moved inside downloadMedia
        await downloadMedia(mediaElement, mediaType);
        btn.classList.remove('tld-downloading');
        btn.classList.add('tld-done');
        btn.innerHTML = ICON_CHECK;
        showToast('✅ Download berhasil!');

        setTimeout(() => {
          btn.classList.remove('tld-done');
          btn.innerHTML = ICON_DOWNLOAD;
        }, 2000);
      } catch (err) {
        btn.classList.remove('tld-downloading');
        btn.innerHTML = ICON_DOWNLOAD;
        showToast('❌ Download gagal: ' + err.message);
      }
    }, true);

    return btn;
  }



  function attachDownloadButton(mediaElement) {
    if (processedElements.has(mediaElement)) return;

    // Skip tiny images (icons, emojis, etc.)
    const rect = mediaElement.getBoundingClientRect();
    if (rect.width < 50 || rect.height < 50) return;

    // Skip UI elements
    if (mediaElement.closest('.tld-download-btn, .tld-batch-checkbox, .tld-toast, .tld-batch-bar')) return;

    // ── SKIP THUMBNAILS IF VIDEO EXISTS ──
    if (mediaElement.tagName === 'IMG' || mediaElement.tagName === 'CANVAS') {
      const parent = mediaElement.parentElement;
      if (parent) {
        // If there's a video in the same wrapper, this is just a thumbnail
        const siblingVideo = parent.querySelector('video');
        if (siblingVideo && siblingVideo.getBoundingClientRect().width > 30) {
          processedElements.add(mediaElement);
          return; // Skip, video will get the button
        }
      }
    }

    processedElements.add(mediaElement);

    const mediaType = detectMediaType(mediaElement);
    const src = getMediaSource(mediaElement);

    // Ignore small profiles/stickers unless in fullscreen
    if (mediaType === 'profile' || mediaType === 'sticker') {
      const isFullscreen = mediaElement.closest('.media-viewer, .MediaViewer, .PhotoViewer, [class*="viewer"], [class*="Viewer"]');
      if (!isFullscreen) return;
    }

    // ── VIRTUALIZATION FIX: Aggressive Deduplication ──
    let stableId = '';
    let containerForIndex = null;
    let curr = mediaElement;
    while (curr && curr !== document.body) {
      stableId = curr.getAttribute('data-message-id') || curr.getAttribute('data-mid') || curr.getAttribute('data-msg-id');
      if (stableId) {
        containerForIndex = curr;
        break;
      }
      if (curr.id && curr.id.startsWith('message')) {
        stableId = curr.id.replace('message', '');
        containerForIndex = curr;
        break;
      }
      if (curr.tagName === 'A' && curr.href) {
        const match = curr.href.match(/(\d+_\d+|\d{5,})/);
        if (match) {
          stableId = match[1];
          containerForIndex = curr;
          break;
        }
      }
      curr = curr.parentElement;
    }

    let mediaKey = '';
    if (stableId && containerForIndex) {
      // Find index within the message/container to handle albums
      const siblings = Array.from(containerForIndex.querySelectorAll('img, video')).filter(el => {
        const r = el.getBoundingClientRect();
        return r.width > 30 && r.height > 30;
      });
      const idx = siblings.indexOf(mediaElement);
      mediaKey = `${stableId}_${idx >= 0 ? idx : 0}`;
    } else {
      // Fallback for profile pics or elements without IDs
      mediaKey = src ? (src.length > 50 ? src.substring(src.length - 30) : src) : Math.random().toString();
    }

    let mediaIndex = detectedMedia.findIndex(m => m.key === mediaKey);
    if (mediaIndex >= 0) {
      detectedMedia[mediaIndex].element = mediaElement;
      detectedMedia[mediaIndex].src = src;
    } else {
      mediaIndex = detectedMedia.length;
      detectedMedia.push({ key: mediaKey, element: mediaElement, type: mediaType, src });
    }

    // ── ATTACH BUTTON ──
    // Create button
    const btn = createDownloadButton(mediaElement, mediaType);
    btn.style.zIndex = '999';

    // Calculate position
    const updatePosition = () => {
      // Find the closest ancestor that is positioned (offsetParent)
      const width = mediaElement.offsetWidth || (mediaElement.parentElement ? mediaElement.parentElement.offsetWidth : 100);
      const height = mediaElement.offsetHeight || (mediaElement.parentElement ? mediaElement.parentElement.offsetHeight : 100);
      
      const top = mediaElement.offsetTop + height - 38; // Bottom edge (32px + 6px padding)
      const left = mediaElement.offsetLeft + width - 38; // Right edge
      
      btn.style.top = top + 'px';
      btn.style.left = left + 'px';
      btn.style.bottom = 'auto'; // override CSS
      btn.style.right = 'auto'; // override CSS
    };

    // Append to parent
    if (mediaElement.parentElement) {
       // Ensure parent is at least relative so offsetTop/Left works predictably if it wasn't
       const parentPos = window.getComputedStyle(mediaElement.parentElement).position;
       if (parentPos === 'static') {
           mediaElement.parentElement.style.position = 'relative';
       }
       mediaElement.parentElement.appendChild(btn);
       
       updatePosition(); // Call AFTER making parent relative

       // Handle resize/load
       mediaElement.addEventListener('load', updatePosition);
       window.addEventListener('resize', updatePosition);
    }
  }

  // ══════════════════════════════════════════════
  //  LOCAL DOWNLOAD HELPER
  // ══════════════════════════════════════════════

  async function downloadLocally(dataUrl, filename) {
    try {
      const res = await fetch(dataUrl);
      const blob = await res.blob();
      const url = URL.createObjectURL(blob);
      
      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      a.style.display = 'none';
      document.body.appendChild(a);
      a.click();
      
      setTimeout(() => {
        a.remove();
        URL.revokeObjectURL(url);
      }, 60000);
      
      return { success: true };
    } catch (err) {
      console.error('[TeleDownloader] Local download failed:', err);
      return { success: false, error: err.message };
    }
  }

  // ══════════════════════════════════════════════
  //  DOWNLOAD LOGIC
  // ══════════════════════════════════════════════

  async function downloadMedia(element, type, batchContext = null) {
    let actualElement = element;
    let actualType = type;

    // The actual stealth HD extraction is now handled by the Background Worker (iframe)

    // Helper to detect if an image is actually an unplayed video thumbnail
    function isVideoThumbnail(el) {
      if (el.tagName === 'VIDEO') return true;
      const c = el.closest('.message, .Message, .media-inner, [class*="media-inner"], [class*="MediaInner"]');
      if (!c) return false;
      return !!c.querySelector('.video-time, .media-video-time, [class*="video-time"], [class*="play"], [class*="Play"], i.icon-play, svg.icon-play');
    }

    // RE-DETECT: Check if there's a <video> in the container
    const container = actualElement.closest && (actualElement.closest('.tld-media-container') || actualElement.parentElement);
    if (container && actualElement.tagName !== 'VIDEO') {
      const video = container.querySelector('video');
      if (video && (video.src || video.currentSrc)) {
        actualElement = video;
        actualType = 'video';
        console.log('[TeleDownloader] Re-detected as video at download time');
      } else if (isVideoThumbnail(actualElement)) {
        throw new Error('Video belum diputar! Silakan klik/mainkan video sebentar, lalu ulangi download.');
      }
    }
    
    // Priority: batchContext.src (captured when visible) > current element src
    let src = (batchContext && batchContext.src) ? batchContext.src : getMediaSource(actualElement);
    let filename = batchContext ? batchContext.filename : `telegram_${actualType}_${Date.now()}`;
    const isVideo = (actualType === 'video' || actualType === 'story_video' || actualType === 'gif');

    console.log(`[TeleDownloader] Downloading: type=${actualType}, tag=${actualElement.tagName}, src=${src ? src.substring(0, 60) : 'null'}`);

    // ══════════════════════════════════════════
    //  VIDEO DOWNLOAD — all via main world
    // ══════════════════════════════════════════
    if (isVideo || element.tagName === 'VIDEO') {

      // Wait for video source if not available yet
      if (!src && element.tagName === 'VIDEO') {
        if (batchContext && batchContext.workerIframe) {
          const iframeRes = await new Promise((resolve) => {
             const handler = (e) => {
               if (e.origin !== window.location.origin) return;
               if (e.data.action === 'HD_RESULT' && e.data.stableId === batchContext.stableId) {
                 window.removeEventListener('message', handler);
                 resolve({ src: e.data.hdSrc, dataUrl: e.data.dataUrl });
               }
             };
             window.addEventListener('message', handler);
             batchContext.workerIframe.contentWindow.postMessage({
               action: 'EXTRACT_HD', stableId: batchContext.stableId, originalSrc: null
             }, '*');
             setTimeout(() => { window.removeEventListener('message', handler); resolve(null); }, 8000);
          });
          if (iframeRes) {
             src = iframeRes.src;
             if (iframeRes.dataUrl) {
                return { success: true, dataUrl: iframeRes.dataUrl, filename: filename + '.mp4' };
             }
          }
        }
        if (!src) src = await waitForVideoSource(element);
      }

      // Try 1: Download blob URL directly from main world
      if (src && src.startsWith('blob:')) {
        console.log('[TeleDownloader] Downloading video blob via main world:', src);
        const result = await downloadBlobViaMainWorld(src, filename);
        if (result.success && result.dataUrl) {
          showToast('✅ Video download started!');
          const finalFilename = result.filename || filename + '.mp4';
          if (batchContext && batchContext.returnBlob) {
            return { success: true, dataUrl: result.dataUrl, filename: finalFilename };
          }
          return downloadLocally(result.dataUrl, finalFilename);
        }
        console.warn('[TeleDownloader] Main world blob download failed:', result.error);
      }

      // Try 2: Find the video element in DOM and download via main world
      // This is necessary for custom URLs like 'a_reference_...' which need to be intercepted by Telegram's Service Worker

      if (actualElement.tagName === 'VIDEO') {
        const videos = document.querySelectorAll('video');
        const videoIndex = Array.from(videos).indexOf(actualElement);
        if (videoIndex >= 0) {
          console.log('[TeleDownloader] Downloading video element #' + videoIndex);
          const result = await downloadVideoElementViaMainWorld(videoIndex, filename);
          if (result.success && result.dataUrl) {
            showToast('✅ Video download started!');
            const finalFilename = result.filename || filename + '.mp4';
            if (batchContext && batchContext.returnBlob) {
              return { success: true, dataUrl: result.dataUrl, filename: finalFilename };
            }
            return downloadLocally(result.dataUrl, finalFilename);
          }
          console.warn('[TeleDownloader] Video element download failed:', result.error);
        }
      }

      // Try 4: Download latest video blob from registry
      console.log('[TeleDownloader] Trying latest video blob from registry...');
      const result = await downloadLatestVideoViaMainWorld(filename);
      if (result.success && result.dataUrl) {
        showToast('✅ Video download started!');
        const finalFilename = result.filename || filename + '.mp4';
        if (batchContext && batchContext.returnBlob) {
          return { success: true, dataUrl: result.dataUrl, filename: finalFilename };
        }
        return downloadLocally(result.dataUrl, finalFilename);
      }

      throw new Error('Tidak dapat mengambil video. Putar video dulu, lalu klik download.');
    }

    // ══════════════════════════════════════════
    //  PHOTO / OTHER MEDIA — try main world first, fallback to canvas
    // ══════════════════════════════════════════

    if (!isVideo && batchContext && batchContext.forceHD && batchContext.workerIframe) {
      console.log('[TeleDownloader] Upgrading photo to HD via Background Worker...');
      
      const hdSrc = await new Promise((resolve) => {
        const handler = (e) => {
          if (e.origin !== window.location.origin) return;
          if (e.data.action === 'HD_RESULT' && e.data.stableId === batchContext.stableId) {
            window.removeEventListener('message', handler);
            resolve(e.data.hdSrc);
          }
        };
        window.addEventListener('message', handler);
        batchContext.workerIframe.contentWindow.postMessage({
          action: 'EXTRACT_HD',
          stableId: batchContext.stableId,
          originalSrc: src
        }, '*');
        
        // Timeout in case worker fails or gets stuck
        setTimeout(() => {
          window.removeEventListener('message', handler);
          resolve(src);
        }, 10000);
      });

      if (hdSrc) src = hdSrc;
    }

    // Strategy 1: Blob URL → download from main world
    if (src && src.startsWith('blob:')) {
      try {
        const result = await downloadBlobViaMainWorld(src, filename); // Removed hardcoded extension to fix .jpg.jpg
        if (result.success && result.dataUrl) {
          const finalFilename = result.filename || filename + '.jpg';
          if (batchContext && batchContext.returnBlob) {
            return { success: true, dataUrl: result.dataUrl, filename: finalFilename };
          }
          return downloadLocally(result.dataUrl, finalFilename);
        }
      } catch (e) {
        console.warn('[TeleDownloader] Main world photo download failed:', e.message);
      }
    }

    // Strategy 2: Direct URL (non-blob)
    // Pass it to background script. If it's a relative URL, resolve it first.
    if (src && !src.startsWith('blob:') && !src.startsWith('data:')) {
      const absoluteUrl = new URL(src, window.location.href).href;
      if (batchContext && batchContext.returnBlob) {
        try {
          const res = await fetch(absoluteUrl);
          const blob = await res.blob();
          const dataUrl = await new Promise(r => {
            const reader = new FileReader();
            reader.onload = () => r(reader.result);
            reader.readAsDataURL(blob);
          });
          return { success: true, dataUrl, filename: filename + '.jpg' };
        } catch (e) {
          console.warn('[TeleDownloader] Strategy 2 fetch failed for batch, falling through to Canvas capture:', e.message);
          // fall through to Strategy 4
        }
      } else {
        return chrome.runtime.sendMessage({
          action: 'download',
          url: absoluteUrl,
          filename,
          type
        });
      }
    }

    // Strategy 3: Data URL
    if (src && src.startsWith('data:')) {
      const finalFilename = filename + '.jpg';
      if (batchContext && batchContext.returnBlob) {
        return { success: true, dataUrl: src, filename: finalFilename };
      }
      return downloadLocally(src, finalFilename);
    }

    // Strategy 4: Canvas capture (photos only — never for videos)
    const canvasData = await captureFromCanvas(actualElement);
    if (canvasData) {
      const finalFilename = filename + '.png'; // Canvas capture uses PNG
      if (batchContext && batchContext.returnBlob) {
        return { success: true, dataUrl: canvasData, filename: finalFilename };
      }
      return downloadLocally(canvasData, finalFilename);
    }

    throw new Error('Tidak dapat mengambil media.');
  }

  // ══════════════════════════════════════════════
  //  NATIVE TELEGRAM SELECTION INTEGRATION
  // ══════════════════════════════════════════════

  /**
   * Read the selection count directly from Telegram's own UI text.
   * e.g. "10 messages selected" → 10
   */
  function getTelegramSelectionCount() {
    // Look for the specific bottom toolbar text
    const toolbar = document.querySelector('.MessageSelectToolbar-inner, [class*="select-toolbar"], [class*="SelectToolbar"], .messages-selection-toolbar');
    if (toolbar) {
      const match = toolbar.textContent.match(/(\d+)\s+message/i);
      if (match) return parseInt(match[1], 10);
    }
    
    // Fallback: search all elements for exact "X messages selected"
    const allElements = document.querySelectorAll('span, div, p');
    for (const el of allElements) {
      const text = el.textContent.trim();
      const match = text.match(/^(\d+)\s+messages\s+selected/i);
      if (match && el.offsetParent !== null) {
        return parseInt(match[1], 10);
      }
    }
    return 0;
  }

  /**
   * Find all selected message containers using EVERY known Telegram selector pattern.
   * Telegram Web K uses .bubble.is-selected with data-mid attribute.
   */
  function findSelectedContainers() {
    const containers = new Set();

    // Strategy 1: Telegram Web K — .bubble.is-selected
    document.querySelectorAll('.bubble.is-selected').forEach(el => {
      containers.add(el);
    });

    // Strategy 2: Telegram Web K — data-mid with is-selected
    document.querySelectorAll('[data-mid].is-selected').forEach(el => {
      containers.add(el);
    });

    // Strategy 3: .message.selected or .Message.selected
    document.querySelectorAll('.message.selected, .Message.selected').forEach(el => {
      containers.add(el);
    });

    // Strategy 4: Generic .is-selected anywhere in message area
    document.querySelectorAll('.is-selected').forEach(el => {
      // Walk up to find the actual message bubble
      const bubble = el.closest('.bubble, .message, .Message, [data-mid], [data-message-id]') || el;
      if (!bubble.closest('.chat-list, .ChatList, .sidebar, [class*="sidebar"]')) {
        containers.add(bubble);
      }
    });

    // Strategy 5: [class*="selected"] on message-like elements (but NOT sidebar)
    document.querySelectorAll('[data-mid][class*="selected"], [data-message-id][class*="selected"]').forEach(el => {
      containers.add(el);
    });

    // Log for debugging (only once per change)
    if (containers.size > 0 && containers.size !== findSelectedContainers._lastCount) {
      console.log(`[TeleDownloader] Found ${containers.size} selected containers via multi-strategy`);
      findSelectedContainers._lastCount = containers.size;
    }

    return containers;
  }
  findSelectedContainers._lastCount = 0;

  function getDomSelectedMedia() {
    // 1. Find all selected elements
    let allSelected = Array.from(document.querySelectorAll('.is-selected, .selected, [class*="selected"]'));
    
    // Filter out irrelevant UI elements that might have 'selected' in their class (like tabs)
    allSelected = allSelected.filter(el => !el.closest('.tabs, .Tabs, .tab-list, .header, .Header'));

    // 2. Find LEAF selected elements (elements that don't contain other selected elements)
    // This perfectly ignores Date Headers or Albums that act as parent containers!
    const leafSelected = allSelected.filter(el => {
      const hasSelectedChildren = el.querySelector('.is-selected, .selected, [class*="selected"]');
      return !hasSelectedChildren;
    });

    const uniqueWrappers = new Set();
    const mediaList = [];

    // 3. For each leaf (usually a checkmark or an inner div), find its main media wrapper
    leafSelected.forEach(leaf => {
      const wrapper = leaf.closest('a, .GridItem, .SharedMediaItem, .media-item, .media-inner, .bubble, [data-mid], [data-message-id]') || leaf.parentElement;
      if (uniqueWrappers.has(wrapper)) return;
      uniqueWrappers.add(wrapper);

      const stableId = wrapper.getAttribute('data-mid') || wrapper.getAttribute('data-message-id') || wrapper.id || ('auto_' + Math.random().toString(36).substr(2, 6));

      const mediaElements = Array.from(wrapper.querySelectorAll('img, video')).filter(el => {
        const rect = el.getBoundingClientRect();
        if (rect.width < 30 || rect.height < 30) return false;
        if (el.closest('.custom-emoji, .emoji, [class*="Reaction"], [class*="avatar"], .peer-photo, [class*="sticker"]')) return false;
        return true;
      });

      if (mediaElements.length === 0) return;

      // 4. Extract exactly ONE best media per wrapper! (Video > Image)
      let bestMedia = null;
      for (const el of mediaElements) {
        if (el.tagName === 'VIDEO') {
          bestMedia = el;
          break;
        }
      }
      if (!bestMedia) bestMedia = mediaElements[0];

      const type = detectMediaType(bestMedia);
      const src = getMediaSource(bestMedia);
      if (type !== 'profile' && type !== 'sticker') {
        mediaList.push({ element: bestMedia, type, stableId, src });
      }
    });

    return mediaList;
  }

  function syncSelectedMedia() {
    const telegramCount = getTelegramSelectionCount();
    
    if (telegramCount === 0) {
      if (globalSelectedMedia.size > 0) globalSelectedMedia.clear();
      return [];
    }

    const currentDomSelected = getDomSelectedMedia();
    
    // Add all currently visible selected items to the global map
    currentDomSelected.forEach(media => {
      const container = media.element.closest('.bubble, .message, .Message, [data-mid]') || media.element.parentElement;
      const mediaInContainer = Array.from(container.querySelectorAll('img, video')).filter(el => {
        const r = el.getBoundingClientRect(); return r.width > 30 && r.height > 30;
      });
      const idx = mediaInContainer.indexOf(media.element);
      const key = `${media.stableId}_${idx >= 0 ? idx : 0}`;
      
      globalSelectedMedia.set(key, media);
    });

    // Remove items that are visible in DOM but NO LONGER selected
    const allBubbles = document.querySelectorAll('.bubble, .message, .Message, [data-mid]');
    allBubbles.forEach(msg => {
      if (msg.closest('.chat-list, .ChatList, .sidebar, [class*="sidebar"]')) return;
      
      const mid = msg.getAttribute('data-mid') || msg.getAttribute('data-message-id') || msg.id;
      if (!mid) return;
      
      const isSelected = msg.classList.contains('is-selected') || msg.classList.contains('selected');
      if (!isSelected) {
        for (const [key] of globalSelectedMedia.entries()) {
          if (key.startsWith(mid + '_')) {
            globalSelectedMedia.delete(key);
          }
        }
      }
    });

    return Array.from(globalSelectedMedia.values());
  }

  async function nativeBatchDownload(downloadBtn) {
    const mediaToProcess = syncSelectedMedia();
    const telegramCount = getTelegramSelectionCount();

    if (mediaToProcess.length === 0) {
      showToast('❌ Tidak ada foto/video di pesan yang dipilih!');
      return;
    }

    await batchDownloadMediaList(mediaToProcess, downloadBtn);
  }

  async function batchDownloadMediaList(mediaToProcess, downloadBtn) {
    const total = mediaToProcess.length;
    downloadBtn.disabled = true;
    const originalHtml = downloadBtn.innerHTML;
    downloadBtn.innerHTML = `<span class="tld-spinner"></span> Downloading...`;

    // If only 1 file is selected, download it normally without zipping
    if (total === 1) {
      try {
        await downloadMedia(mediaToProcess[0].element, mediaToProcess[0].type);
        showToast(`✅ Download berhasil`);
      } catch (err) {
        showToast(`❌ Download gagal: ${err.message}`);
      }
      downloadBtn.disabled = false;
      downloadBtn.innerHTML = originalHtml;
      return;
    }

    // Multiple files -> ZIP them using the Background Worker
    let successCount = 0;
    let processedCount = 0;
    const zip = new JSZip();
    showToast(`⏳ Memproses ${total} file untuk di-ZIP... (Mungkin butuh waktu lama)`);

    // Create Seamless Background Worker (Hidden Iframe)
    const workerIframe = document.createElement('iframe');
    workerIframe.id = 'tld-background-worker-' + Date.now();
    const workerUrl = new URL(window.location.href);
    workerUrl.searchParams.set('tld_worker', '1');
    workerIframe.src = workerUrl.href;
    workerIframe.style.cssText = 'position:fixed; top:-9999px; left:-9999px; width:1920px; height:1080px; z-index:-1; border:none; opacity:0; pointer-events:none;';
    document.body.appendChild(workerIframe);

    showToast(`⏳ Mengaktifkan Background Worker... (Mungkin butuh beberapa detik)`);
    await new Promise(r => setTimeout(r, 4000)); // wait for iframe to render

    // Sync scroll
    const scrollContainer = document.querySelector('.Scrollable, .chat-list, .media-grid, [class*="scroll"], [class*="Scroll"]');
    if (scrollContainer) {
      workerIframe.contentWindow.postMessage({ action: 'SYNC_SCROLL', scrollTop: scrollContainer.scrollTop }, '*');
      await new Promise(r => setTimeout(r, 1000));
    }

    try {
      for (const media of mediaToProcess) {
        processedCount++;
        downloadBtn.innerHTML = `<span class="tld-spinner"></span> ${processedCount}/${total}...`;

        try {
          const result = await downloadMedia(media.element, media.type, { 
            filename: `telegram_${media.type}_${Date.now()}_${processedCount}`,
            returnBlob: true,
            forceHD: true,
            workerIframe: workerIframe,
            stableId: media.stableId,
            src: media.src
          });

          if (result && result.success && result.dataUrl) {
            const res = await fetch(result.dataUrl);
            const blob = await res.blob();
            zip.file(result.filename, blob);
            successCount++;
          }
          await new Promise(r => setTimeout(r, 100)); // small delay
        } catch (err) {
          console.warn('[TeleDownloader] Batch item failed:', err);
        }
      }

      if (successCount > 0) {
        downloadBtn.innerHTML = `<span class="tld-spinner"></span> Zipping...`;
        try {
          const zipBlob = await zip.generateAsync({ type: 'blob' });
          const zipUrl = URL.createObjectURL(zipBlob);
          const a = document.createElement('a');
          a.href = zipUrl;
          a.download = `Telegram_Album_${Date.now()}.zip`;
          a.style.display = 'none';
          document.body.appendChild(a);
          a.click();
          setTimeout(() => { a.remove(); URL.revokeObjectURL(zipUrl); }, 60000);
          showToast(`✅ Berhasil download ZIP (${successCount}/${total} file)`);
        } catch (err) {
          showToast(`❌ Gagal membuat file ZIP: ${err.message}`);
        }
      } else {
        showToast(`❌ Gagal mendownload file`);
      }
    } finally {
      workerIframe.remove();
    }

    downloadBtn.disabled = false;
    downloadBtn.innerHTML = originalHtml;
  }

  // ══════════════════════════════════════════════
  //  FLOATING BATCH DOWNLOAD BUTTON (NATIVE SELECTION)
  // ══════════════════════════════════════════════

  let floatingBatchBtn = null;

  function createFloatingBatchBtn() {
    // We recreate it if it gets destroyed by React
    const btn = document.createElement('button');
    btn.className = 'tld-floating-batch-btn';
    btn.innerHTML = `${ICON_DOWNLOAD} <span class="tld-batch-count-badge">0</span>`;
    btn.title = 'Download Pesan Terpilih (TeleDownloader)';

    btn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      nativeBatchDownload(btn);
    });

    return btn;
  }

  // Continuously check for natively selected messages
  setInterval(() => {
    const telegramCount = getTelegramSelectionCount();
    
    // Also sync media for download purposes
    const mediaToProcess = syncSelectedMedia();

    // Try to find Telegram's native selection toolbar at the bottom
    const nativeToolbar = document.querySelector('[class*="select-toolbar"], [class*="SelectToolbar"], [class*="selection-toolbar"]');

    let btn = document.querySelector('.tld-floating-batch-btn');

    if (telegramCount > 0) {
      if (!btn) {
        btn = createFloatingBatchBtn();
      }
      
      // Display EXACTLY the same number as Telegram to prevent user confusion about mismatched numbers
      btn.querySelector('.tld-batch-count-badge').innerText = telegramCount;

      if (nativeToolbar) {
        // Inject seamlessly into native toolbar
        btn.classList.add('tld-native-mode');
        if (!nativeToolbar.contains(btn)) {
          nativeToolbar.appendChild(btn);
        }
      } else {
        // Fallback to floating mode
        btn.classList.remove('tld-native-mode');
        if (!document.body.contains(btn)) {
          document.body.appendChild(btn);
        }
        btn.style.bottom = '24px';
      }
    } else {
      if (btn) {
        if (btn.classList.contains('tld-native-mode')) {
          btn.remove();
        } else {
          btn.style.bottom = '-100px';
        }
      }
    }
  }, 300);

  // ══════════════════════════════════════════════
  //  PROFILE PICTURE DOWNLOAD
  // ══════════════════════════════════════════════

  function handleProfilePictures() {
    const avatars = document.querySelectorAll(
      '.avatar-photo, img.avatar-photo, .peer-photo img, [class*="avatar"] img, [class*="Avatar"] img, .profile-photo img, .chat-info img.avatar-photo'
    );

    avatars.forEach(avatar => {
      if (processedElements.has(avatar)) return;
      
      const rect = avatar.getBoundingClientRect();
      if (rect.width < 30 || rect.height < 30) return;

      processedElements.add(avatar);

      const parent = avatar.parentElement;
      if (!parent || parent.querySelector('.tld-profile-btn')) return;

      const currentPos = window.getComputedStyle(parent).position;
      if (currentPos === 'static') parent.style.position = 'relative';

      const btn = document.createElement('button');
      btn.className = 'tld-profile-btn';
      btn.innerHTML = ICON_DOWNLOAD;
      btn.title = 'Download foto profil';

      btn.addEventListener('click', async (e) => {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        btn.innerHTML = `<span class="tld-spinner"></span>`;

        try {
          await downloadMedia(avatar, 'profile');
          btn.innerHTML = ICON_CHECK;
          showToast('✅ Foto profil berhasil didownload!');
          setTimeout(() => { btn.innerHTML = ICON_DOWNLOAD; }, 2000);
        } catch (err) {
          btn.innerHTML = ICON_DOWNLOAD;
          showToast('❌ Gagal download foto profil');
        }
      }, true);

      parent.appendChild(btn);
    });
  }

  // ══════════════════════════════════════════════
  //  STORY DOWNLOAD
  // ══════════════════════════════════════════════

  function handleStories() {
    const storyViewers = document.querySelectorAll(
      '.story-viewer, .stories-viewer, [class*="StoryViewer"], [class*="story-viewer"]'
    );

    storyViewers.forEach(viewer => {
      if (viewer.querySelector('.tld-story-btn')) return;

      const media = viewer.querySelector('video, img, canvas');
      if (!media) return;

      const btn = document.createElement('button');
      btn.className = 'tld-story-btn';
      btn.innerHTML = `${ICON_DOWNLOAD} <span>Download Story</span>`;

      btn.addEventListener('click', async (e) => {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        btn.innerHTML = `<span class="tld-spinner"></span> <span>Downloading...</span>`;

        try {
          const type = media.tagName === 'VIDEO' ? 'story_video' : 'story';
          await downloadMedia(media, type);
          btn.innerHTML = `${ICON_CHECK} <span>Downloaded!</span>`;
          showToast('✅ Story berhasil didownload!');
          setTimeout(() => {
            btn.innerHTML = `${ICON_DOWNLOAD} <span>Download Story</span>`;
          }, 2000);
        } catch (err) {
          btn.innerHTML = `${ICON_DOWNLOAD} <span>Download Story</span>`;
          showToast('❌ Gagal download story');
        }
      }, true);

      viewer.appendChild(btn);
    });
  }

  // ══════════════════════════════════════════════
  //  RESTRICTED CONTENT BYPASS
  // ══════════════════════════════════════════════

  function bypassRestrictions() {
    // Remove no-select / no-copy protections via CSS
    const style = document.createElement('style');
    style.textContent = `
      [class*="no-select"], [class*="noSelect"], [class*="protected"] {
        -webkit-user-select: auto !important;
        user-select: auto !important;
      }
    `;
    document.head.appendChild(style);
  }

  // ══════════════════════════════════════════════
  //  TOAST NOTIFICATIONS
  // ══════════════════════════════════════════════

  function showToast(message) {
    let container = document.getElementById('tld-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'tld-toast-container';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'tld-toast';
    toast.textContent = message;
    container.appendChild(toast);

    requestAnimationFrame(() => {
      toast.classList.add('tld-toast-visible');
    });

    setTimeout(() => {
      toast.classList.remove('tld-toast-visible');
      toast.addEventListener('transitionend', () => toast.remove());
    }, 3000);
  }

  // ══════════════════════════════════════════════
  //  MUTATION OBSERVER
  // ══════════════════════════════════════════════

  function scanForMedia() {
    // ── IMPORTANT: Scan VIDEOS FIRST ──
    document.querySelectorAll(SELECTORS.mediaVideo).forEach(video => {
      attachDownloadButton(video);
    });

    // Then scan photos
    document.querySelectorAll(SELECTORS.mediaPhoto).forEach(img => {
      attachDownloadButton(img);
    });

    // Scan canvases
    document.querySelectorAll(SELECTORS.mediaCanvas).forEach(canvas => {
      const rect = canvas.getBoundingClientRect();
      if (rect.width > 100 && rect.height > 100) {
        attachDownloadButton(canvas);
      }
    });

    // Fullscreen media viewer — inject download button into native toolbar
    handleMediaViewer();
    
    // Albums — inject a batch download button for the whole album
    scanForAlbums();

    // Stories
    handleStories();
  }

  function scanForAlbums() {
    const bubbles = document.querySelectorAll('.message, .Message, [data-mid], .Album, .media-grid, [class*="album"]');
    bubbles.forEach(bubble => {
      // Find all valid media inside this bubble
      const rawMedia = Array.from(bubble.querySelectorAll('img, video, canvas')).filter(el => {
         const r = el.getBoundingClientRect();
         return r.width > 30 && r.height > 30 && !el.closest('.avatar-photo, .Avatar, .emoji, [class*="Reaction"], .tld-button-wrapper');
      });

      // Filter out thumbnails if a video exists in the same wrapper
      const mediaInBubble = rawMedia.filter(el => {
        if (el.tagName === 'IMG' || el.tagName === 'CANVAS') {
          const parent = el.parentElement;
          if (parent) {
            const siblingVideo = parent.querySelector('video');
            if (siblingVideo && siblingVideo.getBoundingClientRect().width > 30) {
              return false; // Skip, video will be downloaded instead
            }
          }
        }
        return true;
      });

      // If it contains more than 1 media, it's an album!
      if (mediaInBubble.length > 1) {
         if (!bubble.querySelector('.tld-album-download-btn')) {
             attachAlbumDownloadButton(bubble, mediaInBubble);
         }
      }
    });
  }

  function attachAlbumDownloadButton(bubble, mediaElements) {
    const total = mediaElements.length;
    const btn = document.createElement('button');
    btn.className = 'tld-album-download-btn';
    btn.innerHTML = `DOWNLOAD (${total}/${total})`;
    btn.title = 'Download seluruh album ini (.zip)';
    
    // Styling as a native Telegram bot inline keyboard button
    btn.style.width = '100%';
    btn.style.marginTop = '4px';
    btn.style.background = 'rgba(42,171,238, 0.15)'; // Native light blue background
    btn.style.color = '#2AABEE'; // Native primary text
    btn.style.border = 'none';
    btn.style.borderRadius = '6px';
    btn.style.padding = '8px 0';
    btn.style.fontSize = '13px';
    btn.style.fontWeight = 'bold';
    btn.style.cursor = 'pointer';
    btn.style.display = 'block';
    btn.style.textAlign = 'center';
    btn.style.transition = 'background 0.2s';
    
    btn.addEventListener('mouseenter', () => btn.style.background = 'rgba(42,171,238, 0.25)');
    btn.addEventListener('mouseleave', () => btn.style.background = 'rgba(42,171,238, 0.15)');

    btn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();

      // Extract the actual base message ID
      let baseId = bubble.getAttribute('data-mid') || bubble.getAttribute('data-message-id') || bubble.id;
      if (baseId && baseId.startsWith('message')) baseId = baseId.replace('message', '');
      if (!baseId) baseId = Date.now().toString();

      // Convert DOM elements to media list format
      const mediaList = mediaElements.map((el, index) => {
        return {
          element: el,
          type: detectMediaType(el),
          src: getMediaSource(el),
          stableId: `${baseId}_${index}`
        };
      });

      batchDownloadMediaList(mediaList, btn);
    });

    // Append to the inner message content so it doesn't break flex layouts
    const contentWrapper = bubble.querySelector('.message-content, .Message-content, .Message-body, .media-grid, .Album');
    if (contentWrapper && contentWrapper !== bubble) {
        if (contentWrapper.classList.contains('media-grid') || contentWrapper.classList.contains('Album')) {
            // Append after the grid
            contentWrapper.parentElement.insertBefore(btn, contentWrapper.nextSibling);
        } else {
            contentWrapper.appendChild(btn);
        }
    } else {
        bubble.appendChild(btn);
    }
  }

  // ══════════════════════════════════════════════
  //  FULLSCREEN MEDIA VIEWER DOWNLOAD BUTTON
  //  Injects into Telegram's native viewer toolbar
  // ══════════════════════════════════════════════

  function handleMediaViewer() {
    // Detect Telegram's fullscreen media viewer
    const viewer = document.querySelector('.media-viewer, .MediaViewer, [class*="media-viewer"], [class*="MediaViewer"]');
    if (!viewer) return;
    
    // Already injected?
    if (viewer.querySelector('.tld-viewer-download-btn')) return;

    // Find the media element inside the viewer
    const viewerMedia = viewer.querySelector('img, video, canvas');
    if (!viewerMedia) return;

    const btn = document.createElement('button');
    btn.className = 'tld-viewer-download-btn';
    btn.innerHTML = ICON_DOWNLOAD;
    btn.title = 'Download (TeleDownloader)';

    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();

      btn.classList.add('tld-downloading');
      btn.innerHTML = `<span class="tld-spinner"></span>`;

      try {
        const mediaType = detectMediaType(viewerMedia);
        await downloadMedia(viewerMedia, mediaType);
        btn.classList.remove('tld-downloading');
        btn.classList.add('tld-done');
        btn.innerHTML = ICON_CHECK;
        showToast('✅ Download berhasil!');

        setTimeout(() => {
          btn.classList.remove('tld-done');
          btn.innerHTML = ICON_DOWNLOAD;
        }, 2000);
      } catch (err) {
        btn.classList.remove('tld-downloading');
        btn.innerHTML = ICON_DOWNLOAD;
        showToast('❌ Download gagal: ' + err.message);
      }
    }, true);

    // Safest way to find the actual top right toolbar: find the close button
    const closeBtn = viewer.querySelector(
      'button[title*="Close" i], button[aria-label*="Close" i], button[class*="close" i]'
    );
    
    if (closeBtn && closeBtn.parentElement) {
      // Inject right before the close button, inheriting the parent's flex alignment perfectly
      closeBtn.parentElement.insertBefore(btn, closeBtn);
    } else {
      // Fallback: absolute position
      btn.style.cssText = 'position:absolute; top:16px; right:100px; z-index:99999;';
      viewer.appendChild(btn);
    }
  }

  const observer = new MutationObserver((mutations) => {
    let shouldScan = false;
    for (const mutation of mutations) {
      if (mutation.addedNodes.length > 0) {
        shouldScan = true;
        break;
      }
    }
    if (shouldScan) {
      requestAnimationFrame(scanForMedia);
    }
  });

  // ── Helper to capture thumbnail from image or video element ──
  function captureMediaThumbnail(element, type) {
    try {
      if (!element) return null;
      
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');
      if (!ctx) return null;

      // Handle Video
      if (element.tagName === 'VIDEO' || type === 'video' || type === 'gif' || type === 'story_video') {
        const vid = element.tagName === 'VIDEO' ? element : element.querySelector?.('video');
        if (vid && (vid.videoWidth > 0 || vid.readyState >= 2)) {
          const ratio = (vid.videoWidth && vid.videoHeight) ? (vid.videoWidth / vid.videoHeight) : (16 / 9);
          canvas.width = 160;
          canvas.height = Math.round(160 / ratio) || 90;
          ctx.drawImage(vid, 0, 0, canvas.width, canvas.height);
          return canvas.toDataURL('image/jpeg', 0.65);
        }

        // If video not ready yet or element is thumbnail container, check for preview img
        const container = element.closest?.('.message, .Message, .media-inner, [class*="media-inner"], [class*="Album"], .bubble, [class*="bubble"]') || element.parentElement;
        if (container) {
          const previewImg = container.querySelector('img.media-photo, img[src^="blob:"], .media-inner img, img');
          if (previewImg && (previewImg.naturalWidth > 0 || previewImg.complete)) {
            const ratio = (previewImg.naturalWidth && previewImg.naturalHeight) ? (previewImg.naturalWidth / previewImg.naturalHeight) : (16 / 9);
            canvas.width = 160;
            canvas.height = Math.round(160 / ratio) || 90;
            ctx.drawImage(previewImg, 0, 0, canvas.width, canvas.height);
            return canvas.toDataURL('image/jpeg', 0.65);
          }
        }
        return null;
      }

      // Handle Image
      if (element.tagName === 'IMG' || type === 'photo' || type === 'profile' || type === 'story') {
        if (element.naturalWidth > 0 || element.complete) {
          const ratio = (element.naturalWidth && element.naturalHeight) ? (element.naturalWidth / element.naturalHeight) : 1;
          canvas.width = 140;
          canvas.height = Math.round(140 / ratio) || 140;
          ctx.drawImage(element, 0, 0, canvas.width, canvas.height);
          return canvas.toDataURL('image/jpeg', 0.65);
        }
        if (element.src && (element.src.startsWith('blob:') || element.src.startsWith('data:') || element.src.startsWith('http'))) {
          return element.src;
        }
      }

      // Handle Canvas
      if (element.tagName === 'CANVAS') {
        return element.toDataURL('image/jpeg', 0.65);
      }
    } catch (e) {
      console.warn('[TeleDownloader] Thumbnail capture error:', e);
    }
    return null;
  }

  // ── Helper to extract video duration ──
  function getMediaDuration(element) {
    try {
      if (element.tagName === 'VIDEO' && element.duration && !isNaN(element.duration) && isFinite(element.duration)) {
        const mins = Math.floor(element.duration / 60);
        const secs = Math.floor(element.duration % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
      }
      // Check DOM in message container for time badge
      const container = element.closest?.('.message, .Message, .media-inner, [class*="media-inner"], [class*="Album"], .bubble, [class*="bubble"]') || element.parentElement;
      if (container) {
        const timeEl = container.querySelector('.video-time, .media-video-time, [class*="video-time"], [class*="time"]');
        if (timeEl && timeEl.textContent && timeEl.textContent.trim().length > 0) {
          const txt = timeEl.textContent.trim();
          if (/^\d{1,2}:\d{2}$/.test(txt)) return txt;
        }
      }
    } catch (e) {}
    return null;
  }

  // ══════════════════════════════════════════════
  //  MESSAGE HANDLER (from popup)
  // ══════════════════════════════════════════════

  chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
    if (message.action === 'getMedia') {
      const mediaList = detectedMedia.map((m, i) => {
        const el = m.element;
        const width = el.naturalWidth || el.videoWidth || el.width || (el.getBoundingClientRect ? Math.round(el.getBoundingClientRect().width) : 0);
        const height = el.naturalHeight || el.videoHeight || el.height || (el.getBoundingClientRect ? Math.round(el.getBoundingClientRect().height) : 0);
        const isVid = (m.type === 'video' || m.type === 'gif' || m.type === 'story_video' || el.tagName === 'VIDEO');
        const duration = isVid ? getMediaDuration(el) : null;
        const thumbnail = captureMediaThumbnail(el, m.type);
        
        // Clean filename
        const ext = isVid ? '.mp4' : '.jpg';
        let baseName = m.key || `${Date.now()}_${i + 1}`;
        baseName = baseName.replace(/[^a-zA-Z0-9_-]/g, '_');
        if (baseName.length > 25) baseName = baseName.substring(baseName.length - 25);
        const filename = `telegram_${m.type}_${baseName}${ext}`;

        // Format estimated size
        let sizeFormatted = null;
        if (duration) {
          const parts = duration.split(':');
          const totalSecs = parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
          const estMB = Math.max(0.5, (totalSecs * 0.28)).toFixed(1);
          sizeFormatted = `${estMB} MB`;
        } else if (width && height && width > 0 && height > 0) {
          const estKB = Math.round((width * height * 0.15) / 1024);
          sizeFormatted = estKB > 1000 ? `${(estKB / 1024).toFixed(1)} MB` : `${estKB} KB`;
        } else {
          sizeFormatted = isVid ? 'Video' : 'Foto';
        }

        return {
          index: i,
          type: m.type,
          hasSrc: !!m.src,
          thumbnail: thumbnail,
          filename: filename,
          duration: duration,
          size: sizeFormatted,
          width: width,
          height: height,
          key: m.key
        };
      });
      sendResponse({ media: mediaList, total: mediaList.length });
      return false;
    }

    if (message.action === 'downloadByIndex') {
      const media = detectedMedia[message.index];
      if (media) {
        downloadMedia(media.element, media.type)
          .then(() => sendResponse({ success: true }))
          .catch(err => sendResponse({ success: false, error: err.message }));
        return true;
      }
      sendResponse({ success: false, error: 'Media not found' });
      return false;
    }

    if (message.action === 'scanMedia') {
      scanForMedia();
      sendResponse({ success: true });
      return false;
    }

    if (message.action === 'ping') {
      sendResponse({ status: 'active', mediaCount: detectedMedia.length });
      return false;
    }
  });

  // ══════════════════════════════════════════════
  //  INITIALIZATION
  // ══════════════════════════════════════════════

  function init() {
    console.log('[TeleDownloader] 🚀 Initializing...');

    // Bypass restrictions first
    bypassRestrictions();

    // Initial scan
    scanForMedia();

    // Start observing
    observer.observe(document.body, {
      childList: true,
      subtree: true,
    });

    // Periodic rescan (catches lazily loaded media)
    setInterval(scanForMedia, 3000);

    console.log('[TeleDownloader] ✅ Ready! Media detection active.');
  }

  // Wait for DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();

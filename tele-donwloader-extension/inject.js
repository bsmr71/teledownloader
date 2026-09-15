/**
 * Tele Downloader — Inject Script (MAIN WORLD)
 * 
 * Runs in Telegram's execution context.
 * Intercepts video data at the SourceBuffer level and handles downloads.
 */

(function () {
  'use strict';

  if (window.__teleDownloaderMainInjected) return;
  window.__teleDownloaderMainInjected = true;

  console.log('[TeleDownloader] 🔧 Main world injector starting...');

  // ── Registry: stores actual Blob objects (not MediaSource) ──
  const blobRegistry = new Map();

  // ── Registry: stores raw video chunks from SourceBuffer ──
  const videoChunksRegistry = new Map(); // videoSrc -> { chunks: [], mimeType }

  // ══════════════════════════════════════════════
  //  INTERCEPT URL.createObjectURL
  // ══════════════════════════════════════════════
  const originalCreateObjectURL = URL.createObjectURL.bind(URL);
  URL.createObjectURL = function (obj) {
    const url = originalCreateObjectURL(obj);

    // Only store actual Blobs, NOT MediaSource objects
    if (obj instanceof Blob && !(obj instanceof MediaSource)) {
      blobRegistry.set(url, {
        blob: obj,
        type: obj.type,
        size: obj.size,
        timestamp: Date.now(),
        isBlob: true
      });
      console.log(`[TeleDownloader] Blob captured: ${url.substring(0, 40)}... type=${obj.type} size=${obj.size}`);
    } else if (typeof MediaSource !== 'undefined' && obj instanceof MediaSource) {
      // Track MediaSource URLs so we can associate video chunks
      videoChunksRegistry.set(url, { chunks: [], mimeType: '', totalSize: 0, timestamp: Date.now() });
      console.log(`[TeleDownloader] MediaSource captured: ${url.substring(0, 40)}...`);
    }

    return url;
  };

  // ══════════════════════════════════════════════
  //  INTERCEPT SourceBuffer.appendBuffer
  //  (captures actual video data as it streams in)
  // ══════════════════════════════════════════════
  if (typeof SourceBuffer !== 'undefined') {
    const originalAppendBuffer = SourceBuffer.prototype.appendBuffer;

    SourceBuffer.prototype.appendBuffer = function (data) {
      try {
        // Find which MediaSource this SourceBuffer belongs to
        const ms = this.mediaSource || this._mediaSource;
        
        // Store the chunk — find any active video chunks registry entry
        for (const [url, info] of videoChunksRegistry.entries()) {
          // Get the MIME type from the SourceBuffer
          if (!info.mimeType && this.mimeType) {
            info.mimeType = this.mimeType;
          }
          // Try to get mime from the source buffer's mime type
          if (!info.mimeType) {
            try {
              // SourceBuffer doesn't expose mimeType directly, try from parent
              const audioType = this.audioTracks && this.audioTracks.length > 0;
              const videoType = this.videoTracks && this.videoTracks.length > 0;
              if (videoType || !audioType) {
                info.mimeType = 'video/mp4';
              }
            } catch(e) {}
          }

          // Clone the data and store it
          let buffer;
          if (data instanceof ArrayBuffer) {
            buffer = data.slice(0);
          } else if (data instanceof Uint8Array || ArrayBuffer.isView(data)) {
            buffer = data.buffer.slice(data.byteOffset, data.byteOffset + data.byteLength);
          }

          if (buffer && buffer.byteLength > 0) {
            info.chunks.push(buffer);
            info.totalSize += buffer.byteLength;
            info.timestamp = Date.now();
          }
        }
      } catch (e) {
        // Don't break Telegram if our interception fails
      }

      return originalAppendBuffer.call(this, data);
    };

    console.log('[TeleDownloader] SourceBuffer.appendBuffer intercepted');
  }

  // ══════════════════════════════════════════════
  //  DOWNLOAD HELPERS
  // ══════════════════════════════════════════════

  function triggerDownload(blob, filename, requestId, method) {
    const reader = new FileReader();
    reader.onload = function(e) {
      sendResult(requestId, true, method, null, e.target.result, filename);
    };
    reader.onerror = function() {
      sendResult(requestId, false, method, 'Failed to read blob data');
    };
    reader.readAsDataURL(blob);
  }

  function sendResult(requestId, success, method, error = null, dataUrl = null, filename = null) {
    window.dispatchEvent(new CustomEvent('__tld_download_result', {
      detail: { requestId, success, method, error, dataUrl, filename }
    }));
  }

  // ══════════════════════════════════════════════
  //  EVENT: Download blob by URL
  // ══════════════════════════════════════════════
  window.addEventListener('__tld_download_blob_url', (e) => {
    const { requestId, blobUrl, filename } = e.detail;

    try {
      // Check blob registry (actual Blob objects only)
      const info = blobRegistry.get(blobUrl);
      if (info && info.isBlob) {
        const ext = getExtFromMime(info.type);
        triggerDownload(info.blob, filename + ext, requestId, 'blob_registry');
        return;
      }

      // Check if it's a MediaSource URL with captured chunks
      const chunkInfo = videoChunksRegistry.get(blobUrl);
      if (chunkInfo && chunkInfo.chunks.length > 0) {
        console.log(`[TeleDownloader] Assembling video from ${chunkInfo.chunks.length} chunks (${chunkInfo.totalSize} bytes)`);
        const mimeType = chunkInfo.mimeType || 'video/mp4';
        const blob = new Blob(chunkInfo.chunks.map(buf => new Uint8Array(buf)), { type: mimeType });
        const ext = getExtFromMime(mimeType);
        triggerDownload(blob, filename + ext, requestId, 'chunks_assembly');
        return;
      }

      // Try to fetch the blob URL (works for actual blobs, not MediaSource)
      fetch(blobUrl).then(r => {
        if (!r.ok) throw new Error('Fetch failed');
        return r.blob();
      }).then(blob => {
        if (blob.size < 1000) {
          throw new Error('Downloaded blob too small, likely not a video');
        }
        const ext = getExtFromMime(blob.type);
        triggerDownload(blob, filename + ext, requestId, 'fetch');
      }).catch(err => {
        // Try to find any recent video chunks
        const videoBlob = assembleLatestVideo();
        if (videoBlob) {
          triggerDownload(videoBlob.blob, filename + videoBlob.ext, requestId, 'latest_chunks');
        } else {
          sendResult(requestId, false, null, err.message);
        }
      });

    } catch (err) {
      sendResult(requestId, false, null, err.message);
    }
  });

  // ══════════════════════════════════════════════
  //  EVENT: Download video by element index
  // ══════════════════════════════════════════════
  window.addEventListener('__tld_download_video_element', (e) => {
    const { requestId, videoIndex, filename } = e.detail;

    try {
      const videos = document.querySelectorAll('video');
      const video = videos[videoIndex];

      if (!video) {
        sendResult(requestId, false, null, 'Video element not found');
        return;
      }

      const src = video.currentSrc || video.src;
      console.log(`[TeleDownloader] Video #${videoIndex} src: ${src ? src.substring(0, 50) : 'none'}`);

      // Check if src is a blob URL with actual blob data
      if (src && src.startsWith('blob:')) {
        const info = blobRegistry.get(src);
        if (info && info.isBlob) {
          const ext = getExtFromMime(info.type);
          triggerDownload(info.blob, filename + ext, requestId, 'video_blob');
          return;
        }

        // Check chunks for this MediaSource URL
        const chunkInfo = videoChunksRegistry.get(src);
        if (chunkInfo && chunkInfo.chunks.length > 0) {
          console.log(`[TeleDownloader] Assembling from ${chunkInfo.chunks.length} chunks`);
          const mimeType = chunkInfo.mimeType || 'video/mp4';
          const blob = new Blob(chunkInfo.chunks.map(buf => new Uint8Array(buf)), { type: mimeType });
          const ext = getExtFromMime(mimeType);
          triggerDownload(blob, filename + ext, requestId, 'video_chunks');
          return;
        }
      }

      // Direct URL or Service Worker intercepted URL (e.g., 'a_reference_...')
      if (src && !src.startsWith('blob:') && !src.startsWith('data:')) {
        console.log(`[TeleDownloader] Fetching custom URL: ${src.substring(0, 50)}...`);
        
        fetchFullVideo(src)
          .then(blob => {
            if (blob.size < 1000) throw new Error('Downloaded blob is too small');
            if (blob.type.includes('html')) throw new Error('Downloaded blob is an HTML page');
            const ext = getExtFromMime(blob.type);
            triggerDownload(blob, filename + ext, requestId, 'sw_fetch_chunked');
          })
          .catch(err => {
            console.warn('[TeleDownloader] Direct fetch failed or returned HTML:', err.message);
            // Let it fall through to the chunks assembly fallback below.
            const videoBlob = assembleLatestVideo();
            if (videoBlob) {
              triggerDownload(videoBlob.blob, filename + videoBlob.ext, requestId, 'fallback_chunks_after_fetch');
            } else {
              sendResult(requestId, false, null, 'No video chunks available and fetch failed');
            }
          });
        return;
      }

      // Last resort: try to find any buffered chunks
      const videoBlob = assembleLatestVideo();
      if (videoBlob) {
        triggerDownload(videoBlob.blob, filename + videoBlob.ext, requestId, 'fallback_chunks');
        return;
      }

      sendResult(requestId, false, null, 'No downloadable video source found');
    } catch (err) {
      sendResult(requestId, false, null, err.message);
    }
  });

  // ══════════════════════════════════════════════
  //  EVENT: Download latest video
  // ══════════════════════════════════════════════
  window.addEventListener('__tld_download_latest_video', (e) => {
    const { requestId, filename } = e.detail;

    try {
      // First try real blob registry
      let bestBlob = null;
      let bestTime = 0;

      for (const [url, info] of blobRegistry.entries()) {
        if (info.isBlob && info.type && info.type.startsWith('video/') && info.timestamp > bestTime) {
          bestBlob = info;
          bestTime = info.timestamp;
        }
      }

      if (bestBlob) {
        const ext = getExtFromMime(bestBlob.type);
        triggerDownload(bestBlob.blob, filename + ext, requestId, 'latest_blob');
        return;
      }

      // Try assembled chunks
      const videoBlob = assembleLatestVideo();
      if (videoBlob) {
        triggerDownload(videoBlob.blob, filename + videoBlob.ext, requestId, 'latest_chunks');
        return;
      }

      sendResult(requestId, false, null, 'No video blobs in registry');
    } catch (err) {
      sendResult(requestId, false, null, err.message);
    }
  });

  // ══════════════════════════════════════════════
  //  EVENT: Get video info
  // ══════════════════════════════════════════════
  window.addEventListener('__tld_get_video_info', (e) => {
    const { requestId } = e.detail;

    const videos = document.querySelectorAll('video');
    const videoInfos = Array.from(videos).map((video, i) => {
      const src = video.currentSrc || video.src || '';
      const isMediaSource = src.startsWith('blob:') && videoChunksRegistry.has(src);
      const isRealBlob = src.startsWith('blob:') && blobRegistry.has(src);
      const chunkInfo = videoChunksRegistry.get(src);

      return {
        index: i,
        src: src.substring(0, 60),
        isMediaSource,
        isRealBlob,
        chunksCount: chunkInfo ? chunkInfo.chunks.length : 0,
        chunksSize: chunkInfo ? chunkInfo.totalSize : 0,
        readyState: video.readyState,
        duration: video.duration,
        paused: video.paused
      };
    });

    window.dispatchEvent(new CustomEvent('__tld_video_info_result', {
      detail: { requestId, videos: videoInfos, blobCount: blobRegistry.size, chunkSources: videoChunksRegistry.size }
    }));
  });

  // ══════════════════════════════════════════════
  //  HELPERS
  // ══════════════════════════════════════════════

  function assembleLatestVideo() {
    let best = null;
    let bestTime = 0;

    for (const [url, info] of videoChunksRegistry.entries()) {
      if (info.chunks.length > 0 && info.totalSize > 1000 && info.timestamp > bestTime) {
        best = info;
        bestTime = info.timestamp;
      }
    }

    if (best) {
      const mimeType = best.mimeType || 'video/mp4';
      const blob = new Blob(best.chunks.map(buf => new Uint8Array(buf)), { type: mimeType });
      return { blob, ext: getExtFromMime(mimeType) };
    }
    return null;
  }

  async function fetchFullVideo(url) {
    const chunks = [];
    let start = 0;
    let mimeType = 'video/mp4';

    while (true) {
      console.log(`[TeleDownloader] Fetching video chunk: bytes=${start}-`);
      const response = await fetch(url, { 
        headers: { 
          'Range': `bytes=${start}-`,
          'Accept': 'video/webm,video/ogg,video/*;q=0.9,application/ogg;q=0.7,audio/*;q=0.6,*/*;q=0.5' 
        } 
      });

      if (!response.ok && response.status !== 206) {
        throw new Error(`HTTP ${response.status}`);
      }

      if (start === 0) {
        const type = response.headers.get('content-type');
        if (type) mimeType = type;
        if (mimeType.includes('html')) throw new Error('Server returned HTML instead of video');
      }

      const arrayBuffer = await response.arrayBuffer();
      if (arrayBuffer.byteLength === 0) break;

      chunks.push(arrayBuffer);
      start += arrayBuffer.byteLength;

      // Check if we're done by reading Content-Range (e.g., bytes 0-524287/1048576)
      const contentRange = response.headers.get('content-range');
      if (contentRange) {
        const match = contentRange.match(/\/(\d+)$/);
        if (match && start >= parseInt(match[1], 10)) {
          break; // reached total size
        }
      }

      // If server returned 200 OK instead of 206, it means we got the whole file at once
      if (response.status === 200) break;
    }

    console.log(`[TeleDownloader] Assembly complete! Downloaded ${start} bytes.`);
    return new Blob(chunks, { type: mimeType });
  }

  function getExtFromMime(mime) {
    if (!mime) return '.mp4';
    if (mime.includes('jpeg') || mime.includes('jpg')) return '.jpg';
    if (mime.includes('png')) return '.png';
    if (mime.includes('gif')) return '.gif';
    if (mime.includes('webp')) return '.webp';
    // Force all video types (including webm, ogg, etc) to .mp4 so Windows Explorer 
    // doesn't show weird media player icons (like the orange AIMP logo).
    return '.mp4';
  }



  console.log('[TeleDownloader] 🔧 Main world injector ready — blob + SourceBuffer interception active');
})();

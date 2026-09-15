/**
 * Tele Downloader — Web Content Script for Auto-Syncing Auth State (Login & Logout)
 */
(function() {
  function syncAuth() {
    const userMeta = document.querySelector('meta[name="tld-auth-user"]');
    const guestMeta = document.querySelector('meta[name="tld-auth-guest"]');
    const isLoginPage = !!document.querySelector('form[action*="login"]');

    if (userMeta && userMeta.content) {
      try {
        const data = JSON.parse(userMeta.content);
        if (data && data.token && data.user) {
          chrome.storage.local.set({
            authToken: data.token,
            currentUser: data.user
          }, () => {
            console.log('[Tele Downloader] Akun web tersinkronisasi:', data.user.email);
          });

          try {
            chrome.runtime.sendMessage({
              action: 'webAuthSync',
              token: data.token,
              user: data.user
            });
          } catch (e) {}
          return;
        }
      } catch (e) {
        console.warn('[Tele Downloader] Gagal parse auth meta:', e);
      }
    }

    if (guestMeta || isLoginPage) {
      chrome.storage.local.remove(['authToken', 'currentUser'], () => {
        console.log('[Tele Downloader] Sesi web telah logout, status ekstensi disinkronkan ke Guest.');
      });

      try {
        chrome.runtime.sendMessage({ action: 'webAuthLogout' });
      } catch (e) {}
    }
  }

  syncAuth();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', syncAuth);
  }
  window.addEventListener('load', syncAuth);
})();

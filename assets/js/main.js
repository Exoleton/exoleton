// Année dynamique dans le footer
document.addEventListener('DOMContentLoaded', () => {
  const y = document.getElementById('year');
  if (y) y.textContent = new Date().getFullYear();

  // Smooth scroll pour les ancres internes
  document.querySelectorAll('a[href^="#"]').forEach(a=>{
    a.addEventListener('click', (e)=>{
      const id = a.getAttribute('href');
      if (!id || id === '#') return;
      const target = document.querySelector(id);
      if (target){
        e.preventDefault();
        window.scrollTo({top: target.offsetTop-64, behavior: 'smooth'});
      }
    });
  });

  // Gestion du consentement aux cookies
  const CONSENT_KEY = 'exoleton-cookie-consent';
  const banner = document.getElementById('cookieBanner');
  const acceptAllBtn = document.getElementById('cookieAcceptAll');
  const rejectAllBtn = document.getElementById('cookieRejectAll');
  const savePreferencesBtn = document.getElementById('cookieSavePreferences');
  const analyticsToggle = document.getElementById('cookieAnalytics');
  const marketingToggle = document.getElementById('cookieMarketing');
  const settingsModalEl = document.getElementById('cookieSettingsModal');

  const readConsent = () => {
    try {
      const stored = localStorage.getItem(CONSENT_KEY);
      return stored ? JSON.parse(stored) : null;
    } catch (err) {
      console.warn('Impossible de lire les préférences de cookies', err);
      return null;
    }
  };

  const persistConsent = (consent) => {
    const payload = {
      necessary: true,
      analytics: !!consent.analytics,
      marketing: !!consent.marketing,
      status: consent.status || 'custom',
      updatedAt: new Date().toISOString()
    };
    try {
      localStorage.setItem(CONSENT_KEY, JSON.stringify(payload));
    } catch (err) {
      console.warn('Impossible d’enregistrer les préférences de cookies', err);
    }
  };

  const showBanner = () => {
    if (banner) {
      banner.removeAttribute('hidden');
    }
  };

  const hideBanner = () => {
    if (banner) {
      banner.setAttribute('hidden', '');
    }
  };

  const syncBannerVisibility = () => {
    const consent = readConsent();
    if (!consent || !consent.status || consent.status === 'pending') {
      showBanner();
    } else {
      hideBanner();
    }
  };

  const closeModalIfOpen = () => {
    if (!settingsModalEl) return;
    const modalInstance = bootstrap.Modal.getInstance(settingsModalEl);
    if (modalInstance) {
      modalInstance.hide();
    }
  };

  acceptAllBtn?.addEventListener('click', () => {
    persistConsent({ analytics: true, marketing: true, status: 'accepted' });
    hideBanner();
    closeModalIfOpen();
  });

  rejectAllBtn?.addEventListener('click', () => {
    persistConsent({ analytics: false, marketing: false, status: 'essential' });
    hideBanner();
    closeModalIfOpen();
  });

  if (settingsModalEl) {
    settingsModalEl.addEventListener('show.bs.modal', () => {
      const consent = readConsent();
      if (analyticsToggle) analyticsToggle.checked = !!(consent && consent.analytics);
      if (marketingToggle) marketingToggle.checked = !!(consent && consent.marketing);
    });
  }

  savePreferencesBtn?.addEventListener('click', () => {
    persistConsent({
      analytics: !!analyticsToggle?.checked,
      marketing: !!marketingToggle?.checked,
      status: (analyticsToggle?.checked || marketingToggle?.checked) ? 'custom' : 'essential'
    });
    hideBanner();
    closeModalIfOpen();
  });

  syncBannerVisibility();
});

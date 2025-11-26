(function(){
  const LANG_KEY = 'exoleton-lang';
  const SUPPORTED_LANGS = ['fr','en','de','it','es','pt','nl','pl','jp','zh','kr','ru'];
  const LANGUAGE_NAMES = {
    fr: '🇫🇷 FR',
    en: '🇬🇧 EN',
    de: '🇩🇪 DE',
    it: '🇮🇹 IT',
    es: '🇪🇸 ES',
    pt: '🇵🇹 PT',
    nl: '🇳🇱 NL',
    pl: '🇵🇱 PL',
    jp: '🇯🇵 JP',
    zh: '🇨🇳 ZH',
    kr: '🇰🇷 KR',
    ru: '🇷🇺 RU'
  };

  const cache = {};

  function getLanguageFromPath(){
    const segments = window.location.pathname.split('/').filter(Boolean);
    if (segments.length && SUPPORTED_LANGS.includes(segments[0])) {
      return segments[0];
    }
    return null;
  }

  function updateUrlLanguage(lang){
    if (!window.history || typeof window.history.replaceState !== 'function') return;
    const segments = window.location.pathname.split('/').filter(Boolean);
    if (segments.length && SUPPORTED_LANGS.includes(segments[0])) {
      segments.shift();
    }
    if (segments[0] !== lang) {
      segments.unshift(lang);
    }
    const newPath = '/' + segments.join('/') + (window.location.pathname.endsWith('/') ? '/' : '');
    const newUrl = newPath + window.location.search + window.location.hash;
    if (newUrl !== window.location.pathname + window.location.search + window.location.hash) {
      window.history.replaceState({}, '', newUrl);
    }
  }

  function resolveKey(obj, path){
    return path.split('.').reduce((acc, part) => (acc && typeof acc === 'object') ? acc[part] : undefined, obj);
  }

  function mergeTranslations(base, override){
    if (!override || typeof override !== 'object') return {...base};
    const output = {...base};
    Object.keys(override).forEach(key => {
      if (override[key] && typeof override[key] === 'object' && !Array.isArray(override[key])) {
        output[key] = mergeTranslations(base[key] || {}, override[key]);
      } else {
        output[key] = override[key];
      }
    });
    return output;
  }

  function applyTranslations(dict){
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.dataset.i18n;
      const value = resolveKey(dict, key);
      if (value !== undefined) {
        if (el.dataset.i18nHtml === 'true') {
          el.innerHTML = value;
        } else {
          el.textContent = value;
        }
      }
    });

    const attrMap = ['placeholder','ariaLabel','title','value'];
    attrMap.forEach(attr => {
      const selector = `[data-i18n-${attr.replace(/[A-Z]/g, m => '-' + m.toLowerCase())}]`;
      document.querySelectorAll(selector).forEach(el => {
        const key = el.dataset[`i18n${attr.charAt(0).toUpperCase() + attr.slice(1)}`];
        const value = resolveKey(dict, key);
        if (value !== undefined) {
          const attrName = attr === 'ariaLabel' ? 'aria-label' : attr;
          el.setAttribute(attrName, value);
        }
      });
    });

    document.querySelectorAll('[data-i18n-description]').forEach(meta => {
      const key = meta.dataset.i18nDescription;
      const value = resolveKey(dict, key);
      if (value !== undefined) {
        meta.setAttribute('content', value);
      }
    });

    document.querySelectorAll('[data-i18n-property]').forEach(meta => {
      const raw = meta.dataset.i18nProperty;
      const parts = raw.split(':');
      const key = parts.length > 1 ? parts.slice(1).join(':') : parts[0];
      const value = resolveKey(dict, key);
      if (value !== undefined) {
        meta.setAttribute('content', value);
      }
    });

    if (dict && dict.lang && dict.lang.label) {
      document.documentElement.lang = currentLang;
    }

    updateCategoryOptionsLabels(currentLang);
  }

  function updateCategoryOptionsLabels(lang){
    document.querySelectorAll('option[data-category-translations]').forEach(option => {
      let translations = {};
      try {
        const parsed = JSON.parse(option.dataset.categoryTranslations || '{}');
        translations = parsed && typeof parsed === 'object' ? parsed : {};
      } catch (err) {
        translations = {};
      }

      const fallback = option.dataset.defaultLabel || option.textContent;
      const nextLabel = translations[lang] || translations.fr || fallback;
      if (nextLabel) {
        option.textContent = nextLabel;
      }
    });
  }

  function populateSelectors(current){
    document.querySelectorAll('[data-language-switcher]').forEach(select => {
      select.innerHTML = '';
      SUPPORTED_LANGS.forEach(code => {
        const option = document.createElement('option');
        option.value = code;
        option.textContent = LANGUAGE_NAMES[code] || code.toUpperCase();
        if (code === current) option.selected = true;
        select.appendChild(option);
      });
      select.addEventListener('change', (e) => {
        setLanguage(e.target.value);
      });
    });
  }

  async function fetchTranslation(lang){
    if (cache[lang]) return cache[lang];
    const response = await fetch(`/assets/lang/${lang}.json`);
    if (!response.ok) throw new Error('Cannot load lang');
    const data = await response.json();
    cache[lang] = data;
    return data;
  }

  function detectLanguage(){
    const pathLang = getLanguageFromPath();
    if (pathLang) return pathLang;
    const stored = localStorage.getItem(LANG_KEY);
    if (stored && SUPPORTED_LANGS.includes(stored)) return stored;
    const browser = (navigator.language || navigator.userLanguage || 'fr').slice(0,2).toLowerCase();
    return SUPPORTED_LANGS.includes(browser) ? browser : 'fr';
  }

  let currentLang = 'fr';

  async function setLanguage(lang){
    if (!SUPPORTED_LANGS.includes(lang)) lang = 'fr';
    currentLang = lang;
    localStorage.setItem(LANG_KEY, lang);
    updateUrlLanguage(lang);
    populateSelectors(lang);
    try {
      const base = await fetchTranslation('fr');
      let translations = base;
      if (lang !== 'fr') {
        try {
          const override = await fetchTranslation(lang);
          translations = mergeTranslations(base, override);
        } catch (err) {
          translations = base;
          console.warn('Using fallback translations', err);
        }
      }
      applyTranslations(translations);
      window.dispatchEvent(new CustomEvent('exoleton:language-changed', { detail: { lang } }));
    } catch (err) {
      console.error('Unable to apply translations', err);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    const lang = detectLanguage();
    populateSelectors(lang);
    setLanguage(lang);
  });
})();

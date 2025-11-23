(function(){
  const LANG_KEY = 'exoleton-lang';
  const SUPPORTED_LANGS = ['fr','en','de','it','es','pt','nl','pl','jp','zh','kr','ru'];
  const LANGUAGE_NAMES = {
    fr: 'Français (FR)',
    en: 'English (EN)',
    de: 'Deutsch (DE)',
    it: 'Italiano (IT)',
    es: 'Español (ES)',
    pt: 'Português (PT)',
    nl: 'Nederlands (NL)',
    pl: 'Polski (PL)',
    jp: '日本語 (JP)',
    zh: '简体中文 (ZH)',
    kr: '한국어 (KR)',
    ru: 'Русский (RU)'
  };

  const cache = {};

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
    const response = await fetch(`assets/lang/${lang}.json`);
    if (!response.ok) throw new Error('Cannot load lang');
    const data = await response.json();
    cache[lang] = data;
    return data;
  }

  function detectLanguage(){
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

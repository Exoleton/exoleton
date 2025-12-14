(function () {
  const LANG_KEY = 'exoleton-lang';
  const SUPPORTED_LANGS = ['fr','en','de','it','es','pt','nl','pl','ja','zh','ko','ru'];

  const LANGUAGE_NAMES = {
    fr:'🇫🇷 FR', en:'🇬🇧 EN', de:'🇩🇪 DE', it:'🇮🇹 IT', es:'🇪🇸 ES', pt:'🇵🇹 PT',
    nl:'🇳🇱 NL', pl:'🇵🇱 PL', ja:'🇯🇵 JA', zh:'🇨🇳 ZH', ko:'🇰🇷 KO', ru:'🇷🇺 RU'
  };

  const cache = {};

  function normalizeLang(l) {
    if (!l) return null;
    l = String(l).toLowerCase().trim();
    if (l === 'jp') l = 'ja';
    if (l === 'kr') l = 'ko';
    return SUPPORTED_LANGS.includes(l) ? l : null;
  }

  function getPathLang() {
    const seg = window.location.pathname.split('/').filter(Boolean);
    return seg.length ? normalizeLang(seg[0]) : null;
  }

  function stripLeadingLang(pathname) {
    const seg = pathname.split('/').filter(Boolean);
    const first = seg.length ? normalizeLang(seg[0]) : null;
    if (first) seg.shift();
    return '/' + seg.join('/');
  }

  function buildLangUrl(lang) {
    lang = normalizeLang(lang) || 'fr';
    const rest = stripLeadingLang(window.location.pathname);
    const restPath = (rest === '/') ? '' : rest;
    const keepTrailing = window.location.pathname.endsWith('/') ? '/' : '';
    return '/' + lang + restPath + keepTrailing + window.location.search + window.location.hash;
  }

  function resolveKey(obj, path) {
    return path.split('.').reduce((acc, part) => (acc && typeof acc === 'object') ? acc[part] : undefined, obj);
  }

  function mergeTranslations(base, override) {
    if (!override || typeof override !== 'object') return { ...base };
    const out = { ...base };
    Object.keys(override).forEach(k => {
      const v = override[k];
      if (v && typeof v === 'object' && !Array.isArray(v)) out[k] = mergeTranslations(base[k] || {}, v);
      else out[k] = v;
    });
    return out;
  }

  function applyTranslations(dict, currentLang) {
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.dataset.i18n;
      const value = resolveKey(dict, key);
      if (value !== undefined) {
        if (el.dataset.i18nHtml === 'true') el.innerHTML = value;
        else el.textContent = value;
      }
    });

    const attrMap = [
      { data: 'i18nPlaceholder', attr: 'placeholder' },
      { data: 'i18nAriaLabel',  attr: 'aria-label' },
      { data: 'i18nTitle',      attr: 'title' },
      { data: 'i18nValue',      attr: 'value' }
    ];

    attrMap.forEach(({data, attr}) => {
      const selector = `[data-${data.replace(/[A-Z]/g, m => '-' + m.toLowerCase())}]`;
      document.querySelectorAll(selector).forEach(el => {
        const key = el.dataset[data];
        const value = resolveKey(dict, key);
        if (value !== undefined) el.setAttribute(attr, value);
      });
    });

    document.querySelectorAll('[data-i18n-description]').forEach(meta => {
      const key = meta.dataset.i18nDescription;
      const value = resolveKey(dict, key);
      if (value !== undefined) meta.setAttribute('content', value);
    });

    document.querySelectorAll('[data-i18n-property]').forEach(meta => {
      const raw = meta.dataset.i18nProperty || '';
      const parts = raw.split(':');
      const key = parts.length > 1 ? parts.slice(1).join(':') : parts[0];
      const value = resolveKey(dict, key);
      if (value !== undefined) meta.setAttribute('content', value);
    });

    document.documentElement.lang = currentLang;
  }

  async function fetchTranslation(lang) {
    lang = normalizeLang(lang) || 'fr';
    if (cache[lang]) return cache[lang];

    const candidates = [];
    if (lang === 'ko') candidates.push('ko','kr');
    else if (lang === 'ja') candidates.push('ja','jp');
    else candidates.push(lang);

    let lastErr = null;

    for (const code of candidates) {
      const url = `/assets/lang/${code}.json?v=4`;
      try {
        const r = await fetch(url, { cache: 'no-store' });
        if (!r.ok) { lastErr = new Error(`HTTP ${r.status} on ${url}`); continue; }
        const data = await r.json();
        cache[lang] = data;
        return data;
      } catch (e) {
        lastErr = e;
      }
    }
    throw lastErr || new Error('Cannot load lang');
  }

  function ensureOptions(select, currentLang) {
    const hasFr = Array.from(select.options || []).some(o => o.value === 'fr');
    if (hasFr) { select.value = currentLang; return; }

    select.innerHTML = '';
    SUPPORTED_LANGS.forEach(code => {
      const opt = document.createElement('option');
      opt.value = code;
      opt.textContent = LANGUAGE_NAMES[code] || code.toUpperCase();
      select.appendChild(opt);
    });
    select.value = currentLang;
  }

  function bindSwitcher(currentLang) {
    const select =
      document.querySelector('[data-language-switcher]') ||
      document.getElementById('languageSwitcher');

    if (!select) return;

    ensureOptions(select, currentLang);

    if (select.dataset.langBound === '1') return;
    select.dataset.langBound = '1';

    select.addEventListener('change', (e) => {
      const next = normalizeLang(e.target.value) || 'fr';
      try { localStorage.setItem(LANG_KEY, next); } catch (err) {}
      window.location.assign(buildLangUrl(next));
    }, true);
  }

  async function init() {
    const lang = getPathLang() || normalizeLang(localStorage.getItem(LANG_KEY)) || 'fr';
    try { localStorage.setItem(LANG_KEY, lang); } catch (err) {}

    bindSwitcher(lang);

    try {
      const base = await fetchTranslation('fr');
      let dict = base;
      if (lang !== 'fr') {
        try {
          const override = await fetchTranslation(lang);
          dict = mergeTranslations(base, override);
        } catch (e) {
          dict = base;
        }
      }
      applyTranslations(dict, lang);
    } catch (e) {
      console.error('i18n init failed', e);
    }
  }

  document.addEventListener('DOMContentLoaded', init);
})();

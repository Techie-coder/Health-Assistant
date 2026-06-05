const translations = {};
let currentLang = localStorage.getItem('lang') || 'en';

const TEXT_MAP = {
  'app-title': 'app_title',
  'app-subtitle': 'app_subtitle',
  'tab-symptoms': 'tab_symptoms',
  'tab-facilities': 'tab_facilities',
  'tab-chat': 'tab_chat',
  'tab-records': 'tab_records',
  'disclaimer': 'disclaimer',
  'label-symptoms': 'enter_symptoms',
  'label-age': 'age_group',
  'label-duration': 'duration_days',
  'analyze-btn': 'analyze',
  'voice-btn': 'voice',
  'locate-btn': 'find_hospital',
  'chat-send': 'send',
  'save-record': 'save_profile',
  'load-history': 'view_history',
  'phone-label': 'phone_label',
  'quick-topics-label': 'quick_topics',
  'filter-all': 'filter_all',
  'filter-hospital': 'filter_hospital',
  'filter-clinic': 'filter_clinic',
  'opt-child': 'child',
  'opt-adult': 'adult',
  'opt-elderly': 'elderly',
};

async function loadLang(lang) {
  currentLang = lang;
  const res = await fetch(`../lang/${lang}.json`);
  translations[lang] = await res.json();
  applyLang(lang);
  localStorage.setItem('lang', lang);
  document.documentElement.lang = lang;
}

function t(key) {
  return translations[currentLang]?.[key] || key;
}

function applyLang(lang) {
  Object.entries(TEXT_MAP).forEach(([id, key]) => {
    const el = document.getElementById(id);
    if (el) el.textContent = t(key);
  });
  document.getElementById('symptoms').placeholder = t('symptoms_placeholder');
  document.getElementById('chat-input').placeholder = t('chat_placeholder');
  document.getElementById('offline-banner').textContent = t('offline_notice');
}
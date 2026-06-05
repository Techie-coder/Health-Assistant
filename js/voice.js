document.getElementById('voice-btn')?.addEventListener('click', () => {
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!SpeechRecognition) {
    alert(currentLang === 'sw' ? 'Sauti haipatikani kwenye kivinjari hiki' : 'Voice not supported in this browser');
    return;
  }
  const btn = document.getElementById('voice-btn');
  const rec = new SpeechRecognition();
  rec.lang = currentLang === 'sw' ? 'sw-TZ' : 'en-US';
  rec.interimResults = false;
  btn.textContent = t('listening');
  btn.disabled = true;
  rec.onresult = e => {
    const text = e.results[0][0].transcript;
    const box = document.getElementById('symptoms');
    box.value = (box.value + ' ' + text).trim();
  };
  rec.onend = rec.onerror = () => {
    btn.textContent = t('voice');
    btn.disabled = false;
  };
  rec.start();
});
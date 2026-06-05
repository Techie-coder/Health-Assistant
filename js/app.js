const API = '../api';
let patientId = localStorage.getItem('patient_id') || null;

document.getElementById('lang-select').addEventListener('change', e => loadLang(e.target.value));

document.querySelectorAll('.tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.tab, .panel').forEach(el => el.classList.remove('active'));
    tab.classList.add('active');
    document.getElementById('panel-' + tab.dataset.tab).classList.add('active');
  });
});

document.getElementById('analyze-btn').addEventListener('click', analyzeSymptoms);
document.getElementById('locate-btn').addEventListener('click', findFacilities);
document.getElementById('chat-send').addEventListener('click', sendChat);
document.getElementById('chat-input').addEventListener('keydown', e => { if (e.key === 'Enter') sendChat(); });
document.getElementById('save-record').addEventListener('click', saveProfile);
document.getElementById('load-history').addEventListener('click', loadHistory);
document.getElementById('emergency-call').addEventListener('click', () => window.location.href = 'tel:112');
document.querySelectorAll('.chip').forEach(chip => {
  chip.addEventListener('click', () => {
    document.getElementById('chat-input').value = chip.dataset.topic;
    sendChat();
  });
});

window.addEventListener('online', () => document.getElementById('offline-banner').classList.add('hidden'));
window.addEventListener('offline', () => document.getElementById('offline-banner').classList.remove('hidden'));

async function analyzeSymptoms() {
  const btn = document.getElementById('analyze-btn');
  const symptoms = document.getElementById('symptoms').value.trim();
  if (!symptoms) return;

  btn.disabled = true;
  btn.textContent = t('analyzing');

  let lat = null, lng = null;
  try {
    const pos = await getPosition();
    lat = pos.coords.latitude;
    lng = pos.coords.longitude;
  } catch (_) {}

  try {
    const res = await fetch(`${API}/symptoms.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        symptoms,
        age_group: document.getElementById('age-group').value,
        duration_days: document.getElementById('duration').value,
        lang: currentLang,
        lat, lng,
        patient_id: patientId
      })
    });
    const data = await res.json();
    showResult(data);
    if (data.is_emergency) showEmergencyBanner();
  } catch {
    showOfflineResult();
  } finally {
    btn.disabled = false;
    btn.textContent = t('analyze');
  }
}

function showResult(data) {
  const box = document.getElementById('result');
  box.className = 'result urgency-' + (data.urgency || 'medium');

  const conditions = (data.possible_conditions || [])
    .map(c => `<li>${c.name} — ${Math.round(c.probability * 100)}%</li>`).join('');

  box.innerHTML = `
    <span class="badge badge-${data.urgency}">${data.urgency_label || data.urgency}</span>
    <p style="margin-top:10px">${data.explanation || ''}</p>
    <p><strong>${t('first_aid')}:</strong> ${data.first_aid || ''}</p>
    <p><strong>${t('recommendation')}:</strong> ${data.recommendation || ''}</p>
    ${conditions ? `<p><strong>${t('possible_conditions')}:</strong></p><ul>${conditions}</ul>` : ''}
    ${data.is_emergency ? `<p style="color:#dc3545;font-weight:700;margin-top:10px">⚠ ${t('call_emergency')}</p>` : ''}
    <p style="font-size:0.8rem;color:#6c757d;margin-top:8px">${data.disclaimer || ''}</p>
  `;
  box.classList.remove('hidden');
}

function showOfflineResult() {
  const box = document.getElementById('result');
  box.className = 'result urgency-medium';
  box.innerHTML = `<p>${t('offline_notice')}</p><p>${currentLang === 'sw' ? 'Ikiwa dalili ni kali, nenda hospitali mara moja.' : 'If symptoms are severe, go to a hospital immediately.'}</p>`;
  box.classList.remove('hidden');
}

function showEmergencyBanner() {
  document.getElementById('emergency-text').textContent = t('call_emergency');
  document.getElementById('emergency-banner').classList.remove('hidden');
}

function getPosition() {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) return reject();
    navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: false, timeout: 12000 });
  });
}

async function findFacilities() {
  const list = document.getElementById('facility-list');
  const btn = document.getElementById('locate-btn');
  list.innerHTML = `<li>${t('locating')}</li>`;
  btn.disabled = true;

  try {
    const pos = await getPosition();
    const { latitude: lat, longitude: lng } = pos.coords;
    const type = document.getElementById('facility-type').value;
    const url = `${API}/facilities.php?lat=${lat}&lng=${lng}&radius=50${type ? '&type=' + type : ''}`;
    const res = await fetch(url);
    const data = await res.json();
    const facilities = data.facilities || [];

    if (facilities.length === 0) {
      list.innerHTML = `<li>${t('no_facilities')}</li>`;
    } else {
      list.innerHTML = facilities.map(f => `
        <li>
          <strong>${f.name}</strong> <span class="badge badge-medium">${f.type}</span><br>
          ${f.distance_km} ${t('km_away')} · ${f.phone || ''}<br>
          <small>${f.address || ''}</small>
          ${f.phone ? `<br><a href="tel:${f.phone}">${f.phone}</a>` : ''}
        </li>
      `).join('');
      showFacilitiesOnMap(facilities, lat, lng);
    }
  } catch {
    list.innerHTML = `<li>${t('enable_location')}</li>`;
  } finally {
    btn.disabled = false;
  }
}

async function sendChat() {
  const input = document.getElementById('chat-input');
  const msg = input.value.trim();
  if (!msg) return;
  const box = document.getElementById('chat-messages');
  box.innerHTML += `<div class="msg-user">${escapeHtml(msg)}</div>`;
  input.value = '';

  try {
    const res = await fetch(`${API}/chatbot.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: msg, lang: currentLang })
    });
    const data = await res.json();
    box.innerHTML += `<div class="msg-bot">${escapeHtml(data.reply || '')}</div>`;
  } catch {
    box.innerHTML += `<div class="msg-bot">${t('offline_notice')}</div>`;
  }
  box.scrollTop = box.scrollHeight;
}

async function saveProfile() {
  const phone = document.getElementById('phone').value.trim();
  if (!phone) return;
  const res = await fetch(`${API}/records.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      phone,
      age_group: document.getElementById('age-group').value,
      lang: currentLang
    })
  });
  const data = await res.json();
  if (data.patient_id) {
    patientId = data.patient_id;
    localStorage.setItem('patient_id', patientId);
  }
  alert(t('saved'));
}

async function loadHistory() {
  const phone = document.getElementById('phone').value.trim();
  if (!phone) return;
  const res = await fetch(`${API}/records.php?phone=${encodeURIComponent(phone)}`);
  const data = await res.json();
  if (data.patient_id) {
    patientId = data.patient_id;
    localStorage.setItem('patient_id', patientId);
  }
  const list = document.getElementById('history-list');
  list.innerHTML = (data.sessions || []).map(s => `
    <li>
      <span class="badge badge-${s.urgency}">${s.urgency}</span>
      ${escapeHtml(s.symptoms)}<br>
      <small>${s.created_at}</small>
    </li>
  `).join('') || `<li>${t('no_history')}</li>`;
}

function escapeHtml(str) {
  return str.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

document.getElementById('lang-select').value = currentLang;
loadLang(currentLang);
if (!navigator.onLine) document.getElementById('offline-banner').classList.remove('hidden');
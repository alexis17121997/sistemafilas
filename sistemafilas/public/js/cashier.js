/* ═══════════════════════════════════════════════════════════════════════════
   cashier.js  –  Panel del cajero
   • call-next revela el ticket DESPUÉS de llamar (no antes)
   • Manejo robusto de errores
   • Auto-refresh de cola cada 8 s
   • Sin dependencias externas
═══════════════════════════════════════════════════════════════════════════ */

// State
let calling      = false;  // flag: AJAX en curso
let refreshTimer = null;

// ── Utility: POST JSON ────────────────────────────────────────────────────────
function apiPost(url, body) {
  return fetch(APP_URL + url, {
    method:  'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body:    new URLSearchParams(body).toString(),
  })
  .then(r => {
    if (!r.ok) throw new Error('HTTP ' + r.status);
    return r.json();
  });
}

// ── LLAMAR SIGUIENTE ──────────────────────────────────────────────────────────
function callNext() {
  if (calling) return;
  calling = true;

  const btn = document.getElementById('btnCallNext');
  setBtn(btn, 'Llamando…', true);

  apiPost('/api/call-next', {})
    .then(handleCallResult)
    .catch(err => {
      calling = false;
      setBtn(btn, 'LLAMAR SIGUIENTE', false);
      showAlert('danger', 'Error de conexión. Intente de nuevo.<br><small>' + err.message + '</small>');
    });
}

// Called from "Siguiente ticket" button in ticket panel
function forceCallNext() {
  if (CURRENT_ID) {
    // No-show silently before calling next
    apiPost('/api/no-show', { ticket_id: CURRENT_ID })
      .then(() => { CURRENT_ID = 0; callNext(); })
      .catch(() => callNext());
  } else {
    callNext();
  }
}

function handleCallResult(d) {
  calling = false;
  const btn = document.getElementById('btnCallNext');
  setBtn(btn, 'LLAMAR SIGUIENTE', false);

  if (d.empty || (d.error && d.empty)) {
    showAlert('info', 'No hay pacientes en espera en este momento.');
    return;
  }
  if (d.error) {
    showAlert('warning', d.error);
    return;
  }

  CURRENT_ID = d.ticket.id;
  revealTicket(d.ticket, d.window_name || WINDOW_NAME);
  refreshQueue();
}

// ── Reveal ticket in panel ────────────────────────────────────────────────────
function revealTicket(ticket, windowName) {
  // Switch panels
  document.getElementById('panelIdle').classList.add('d-none');
  const panel = document.getElementById('panelTicket');
  panel.classList.remove('d-none');

  // Fill ticket data
  const numEl = document.getElementById('trNumber');
  numEl.textContent = ticket.ticket_number;
  numEl.style.color = ticket.cat_color || '#2563eb';
  numEl.classList.add('visible');

  document.getElementById('trMeta').innerHTML =
    '<span class="tr-cat">' + escHtml(ticket.cat_name || '') + '</span>' +
    '<span class="tr-sep" aria-hidden="true">·</span>' +
    '<span class="tr-svc">' + escHtml(ticket.service_name || '') + '</span>';

  document.getElementById('trStatus').innerHTML =
    '<span class="badge bg-info">📣 Llamando</span>';

  document.getElementById('btnServing').disabled = false;

  // Flash overlay
  showCallOverlay(ticket.ticket_number, windowName);
}

// ── Serve current ─────────────────────────────────────────────────────────────
function serveCurrent() {
  if (!CURRENT_ID) return;
  apiPost('/api/serve', { ticket_id: CURRENT_ID })
    .then(() => {
      document.getElementById('trStatus').innerHTML =
        '<span class="badge bg-primary">🟢 Atendiendo</span>';
      document.getElementById('btnServing').disabled = true;
    })
    .catch(() => showAlert('warning', 'No se pudo actualizar el estado.'));
}

// ── Complete ──────────────────────────────────────────────────────────────────
function completeCurrent() {
  if (!CURRENT_ID) return;
  apiPost('/api/complete', { ticket_id: CURRENT_ID })
    .then(() => {
      CURRENT_ID = 0;
      goToIdle();
      refreshQueue();
      refreshStats();
    })
    .catch(() => showAlert('danger', 'Error al completar. Intente de nuevo.'));
}

// ── Recall ────────────────────────────────────────────────────────────────────
function recallCurrent() {
  apiPost('/api/recall', {})
    .then(d => {
      if (d.success) showAlert('info', '📢 Ticket rellamado: <strong>' + escHtml(d.ticket?.ticket_number || '') + '</strong>');
      else showAlert('warning', d.error || 'Sin ticket activo.');
    })
    .catch(() => showAlert('danger', 'Error de conexión.'));
}

// ── No show ───────────────────────────────────────────────────────────────────
function noShowCurrent() {
  if (!CURRENT_ID) return;
  if (!confirm('¿Marcar como NO PRESENTADO?')) return;
  apiPost('/api/no-show', { ticket_id: CURRENT_ID })
    .then(() => {
      CURRENT_ID = 0;
      goToIdle();
      refreshQueue();
    })
    .catch(() => showAlert('danger', 'Error. Intente de nuevo.'));
}

// ── UI helpers ────────────────────────────────────────────────────────────────
function goToIdle() {
  document.getElementById('panelTicket').classList.add('d-none');
  document.getElementById('panelIdle').classList.remove('d-none');
  document.getElementById('trNumber').textContent = '';
  document.getElementById('trNumber').classList.remove('visible');
}

function setBtn(btn, label, disabled) {
  if (!btn) return;
  btn.disabled = disabled;
  if (disabled) {
    btn.querySelector('.btn-call-label').textContent = label;
  } else {
    btn.querySelector('.btn-call-label').textContent = 'LLAMAR SIGUIENTE';
  }
}

function showCallOverlay(ticketNum, windowName) {
  const ov = document.getElementById('callOverlay');
  if (!ov) return;
  document.getElementById('coTicket').textContent = ticketNum;
  document.getElementById('coWindow').textContent = '→  ' + windowName;
  ov.style.display = 'flex';
  clearTimeout(ov._t);
  ov._t = setTimeout(() => { ov.style.display = 'none'; }, 3000);
}

// ── Queue refresh ─────────────────────────────────────────────────────────────
function refreshQueue() {
  fetch(APP_URL + '/api/queue-status')
    .then(r => r.json())
    .then(renderQueue)
    .catch(() => {}); // silent – no crash on network hiccup
}

function renderQueue(d) {
  const list    = document.getElementById('queueList');
  const badge   = document.getElementById('qBadge');
  const waiting = document.getElementById('pWaiting');
  const sub     = document.getElementById('callSub');
  const status  = document.getElementById('idleStatus');
  const w       = d.waiting || [];

  if (badge)   badge.textContent  = w.length;
  if (waiting) waiting.textContent = w.length;
  if (sub)     sub.textContent    = w.length > 0 ? w.length + ' en espera' : 'Cola vacía';
  if (status)  status.innerHTML   = w.length > 0
    ? '<strong>' + w.length + '</strong> paciente' + (w.length > 1 ? 's' : '') + ' en espera'
    : 'Sin pacientes en espera';

  if (!w.length) {
    list.innerHTML = '<div class="text-center py-5 text-muted">' +
      '<i class="ti ti-check-circle fs-1 text-success d-block mb-2" aria-hidden="true"></i>' +
      'No hay pacientes en espera</div>';
    return;
  }

  list.innerHTML = w.map((t, i) =>
    '<div class="queue-row' + (i === 0 ? ' queue-row-first' : '') + '">' +
      '<div class="qr-pos" aria-hidden="true">' + (i + 1) + '</div>' +
      '<div class="qr-dot" style="background:' + escHtml(t.cat_color) + '" aria-hidden="true"></div>' +
      '<div class="qr-info">' +
        '<span class="qr-ticket">' + escHtml(t.ticket_number) + '</span>' +
        '<span class="qr-cat text-muted">' + escHtml(t.cat_name) + '</span>' +
      '</div>' +
      '<span class="qr-badge" style="background:' + escHtml(t.cat_color) + '22;color:' + escHtml(t.cat_color) + '">' + escHtml(t.service_name) + '</span>' +
      '<span class="qr-time text-muted">' + formatTime(t.issued_at) + '</span>' +
    '</div>'
  ).join('');
}

function refreshStats() {
  fetch(APP_URL + '/api/queue-status')
    .then(r => r.json())
    .then(d => {
      if (d.stats) {
        const el = document.getElementById('pServed');
        if (el) el.textContent = d.stats.served || 0;
      }
    })
    .catch(() => {});
}

// ── Utils ─────────────────────────────────────────────────────────────────────
function escHtml(s) {
  return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function formatTime(isoStr) {
  if (!isoStr) return '';
  try {
    const d = new Date(isoStr);
    return d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: false });
  } catch { return ''; }
}

function showAlert(type, msg) {
  const old = document.getElementById('cashierAlert');
  if (old) old.remove();
  const div = document.createElement('div');
  div.id = 'cashierAlert';
  div.className = 'alert alert-' + type + ' alert-dismissible fade show position-fixed start-50 translate-middle-x';
  div.style.cssText = 'top:76px;z-index:9000;min-width:320px;max-width:90vw';
  div.innerHTML = msg + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>';
  document.body.appendChild(div);
  setTimeout(() => { if (div.parentNode) div.remove(); }, 6000);
}

// ── Auto-refresh every 8 s ───────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  refreshTimer = setInterval(refreshQueue, 8000);
});

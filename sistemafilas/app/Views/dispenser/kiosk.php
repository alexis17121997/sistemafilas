<?php $pageTitle = 'Dispensador de Tickets'; ?>
<style>
  body { background: linear-gradient(135deg, #1a3a6c 0%, #0f2447 100%); min-height: 100vh; }
  .kiosk-wrap { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem; }
  .kiosk-logo { font-size: 2.5rem; font-weight: 800; color: #fff; letter-spacing: 2px; text-align: center; margin-bottom: 0.5rem; }
  .kiosk-sub { color: rgba(255,255,255,0.7); text-align: center; margin-bottom: 2.5rem; font-size: 1.1rem; }
  .step-title { color: #fff; font-size: 1.4rem; font-weight: 700; text-align: center; margin-bottom: 1.5rem; }
  .kiosk-btn {
    background: rgba(255,255,255,0.12);
    border: 2px solid rgba(255,255,255,0.25);
    border-radius: 20px;
    color: #fff;
    padding: 1.5rem 1rem;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
  }
  .kiosk-btn:hover, .kiosk-btn.selected { background: rgba(255,255,255,0.25); border-color: #fff; transform: scale(1.03); }
  .kiosk-btn i { font-size: 2.5rem; }
  .kiosk-btn.cat-pref { border-color: #F5A623; }
  .kiosk-btn.cat-pref i, .kiosk-btn.cat-pref { color: #FFD88A; }
  .kiosk-btn.cat-emb { border-color: #FF6B8A; }
  .kiosk-btn.cat-emb i, .kiosk-btn.cat-emb { color: #FFB3C6; }
  .kiosk-btn.cat-dis { border-color: #B39DDB; }
  .kiosk-btn.cat-dis i, .kiosk-btn.cat-dis { color: #D1C4E9; }
  .ticket-result {
    background: #fff;
    border-radius: 24px;
    padding: 2.5rem 3rem;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    max-width: 380px;
    width: 100%;
  }
  .ticket-big-num { font-size: 5rem; font-weight: 900; letter-spacing: 4px; margin: 0.5rem 0; }
  .kiosk-back { background: none; border: 2px solid rgba(255,255,255,0.4); color: rgba(255,255,255,0.8); border-radius: 12px; padding: 0.5rem 2rem; cursor: pointer; margin-top: 1.5rem; font-size: 1rem; }
  .kiosk-back:hover { background: rgba(255,255,255,0.1); }
  @media print {
    body { background: #fff !important; }
    .kiosk-wrap { display: none !important; }
    #printArea { display: block !important; }
  }
</style>

<div class="kiosk-wrap" id="kioskWrap">

  <div class="kiosk-logo">
    <i class="ti ti-heart-rate-monitor me-2"></i>
    <?= htmlspecialchars($branch['name'] ?? 'Clínica') ?>
  </div>
  <p class="kiosk-sub">Sistema de Atención al Paciente</p>

  <!-- Step 1: Service Type -->
  <div id="step1" style="width:100%;max-width:680px">
    <p class="step-title"><i class="ti ti-hand-click me-2"></i>¿Qué servicio necesita?</p>
    <div class="row g-3 justify-content-center">
      <?php foreach ($services as $s): ?>
      <div class="col-4">
        <div class="kiosk-btn" onclick="selectService(<?= $s['id'] ?>, '<?= addslashes($s['name']) ?>', '<?= htmlspecialchars($s['color']) ?>')">
          <i class="ti <?= ['SRV'=>'ti-stethoscope','SEG'=>'ti-shield-check','FAR'=>'ti-pill'][$s['code']] ?? 'ti-star' ?>"></i>
          <?= htmlspecialchars($s['name']) ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-3">
      <span style="color:rgba(255,255,255,0.5); font-size:0.85rem;">
        <i class="ti ti-clock me-1"></i>Horario de atención: 8:00 – 18:00
      </span>
    </div>
  </div>

  <!-- Step 2: Category -->
  <div id="step2" style="display:none;width:100%;max-width:680px">
    <p class="step-title"><i class="ti ti-users me-2"></i>¿Cuál es su condición?</p>
    <div class="row g-3 justify-content-center">
      <?php foreach ($categories as $cat): ?>
      <?php $extraClass = ['P'=>'cat-pref','E'=>'cat-emb','D'=>'cat-dis'][$cat['code']] ?? ''; ?>
      <?php $icon = ['G'=>'ti-user','P'=>'ti-armchair','E'=>'ti-heart','D'=>'ti-accessible'][$cat['code']] ?? 'ti-user'; ?>
      <div class="col-6">
        <div class="kiosk-btn <?= $extraClass ?>" onclick="selectCategory(<?= $cat['id'] ?>, '<?= addslashes($cat['name']) ?>', '<?= $cat['color'] ?>')">
          <i class="ti <?= $icon ?>"></i>
          <?= htmlspecialchars($cat['name']) ?>
          <?php if ($cat['priority'] > 0): ?>
          <small style="font-size:0.7rem;opacity:0.8">⭐ Atención Preferencial</small>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center">
      <button class="kiosk-back" onclick="goBack()"><i class="ti ti-arrow-left me-1"></i>Regresar</button>
    </div>
  </div>

  <!-- Step 3: Ticket Result -->
  <div id="step3" style="display:none">
    <div class="ticket-result" id="ticketResult">
      <div id="loadingSpinner" class="py-4">
        <div class="spinner-border text-primary" style="width:3rem;height:3rem"></div>
        <p class="mt-3 text-muted">Generando su ticket…</p>
      </div>
      <div id="ticketData" style="display:none">
        <div class="text-muted small text-uppercase fw-bold mb-1">Su número de atención</div>
        <div class="ticket-big-num" id="tktNumber" style="color:#1a3a6c">—</div>
        <div id="tktCategory" class="fw-semibold mb-1"></div>
        <div id="tktService" class="text-muted small mb-3"></div>
        <hr>
        <div class="small text-muted mb-1">Pacientes antes de usted:</div>
        <div id="tktWaiting" class="fw-bold fs-4 text-warning mb-3">—</div>
        <div class="small text-muted mb-3" id="tktTime"></div>
        <button class="btn btn-primary btn-lg w-100 mb-2" onclick="printTicket()">
          <i class="ti ti-printer me-1"></i> Imprimir Ticket
        </button>
        <button class="btn btn-outline-secondary w-100" onclick="resetKiosk()">
          <i class="ti ti-refresh me-1"></i> Nuevo Ticket
        </button>
      </div>
    </div>
  </div>

</div>

<!-- Print area (hidden on screen, shown on print) -->
<div id="printArea" style="display:none"></div>

<script>
const BRANCH_ID   = <?= $branchId ?>;
const PRINT_CFG   = <?= json_encode($config) ?>;
let selectedSvc   = null;
let selectedSvcName = '';
let currentTicket = null;

function selectService(id, name, color) {
  selectedSvc = id;
  selectedSvcName = name;
  document.getElementById('step1').style.display = 'none';
  document.getElementById('step2').style.display = 'block';
}

function goBack() {
  document.getElementById('step2').style.display = 'none';
  document.getElementById('step1').style.display = 'block';
}

function selectCategory(catId, catName, catColor) {
  document.getElementById('step2').style.display = 'none';
  document.getElementById('step3').style.display = 'block';
  document.getElementById('loadingSpinner').style.display = 'block';
  document.getElementById('ticketData').style.display = 'none';

  fetch(APP_URL + '/api/issue-ticket', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `branch_id=${BRANCH_ID}&service_type_id=${selectedSvc}&category_id=${catId}`
  })
  .then(r => r.json())
  .then(d => {
    document.getElementById('loadingSpinner').style.display = 'none';
    document.getElementById('ticketData').style.display = 'block';
    if (d.success) {
      currentTicket = d.ticket;
      document.getElementById('tktNumber').textContent   = d.ticket.ticket_number;
      document.getElementById('tktNumber').style.color   = catColor;
      document.getElementById('tktCategory').textContent = catName;
      document.getElementById('tktService').textContent  = 'Servicio: ' + selectedSvcName;
      document.getElementById('tktWaiting').textContent  = d.ticket.estimated_wait ? Math.round(d.ticket.estimated_wait/5) + ' personas' : '–';
      document.getElementById('tktTime').textContent     = new Date().toLocaleString('es-MX');
    } else {
      document.getElementById('tktNumber').textContent = 'ERROR';
    }
  });
}

function printTicket() {
  if (!currentTicket) return;
  const pw = PRINT_CFG.paper_width || 72;
  const fs = PRINT_CFG.font_size || 14;
  const header = PRINT_CFG.header_text || 'CLÍNICA';
  const footer = PRINT_CFG.footer_text || 'Gracias por su visita.';

  const html = `
    <html><head>
      <style>
        @page { size: ${pw}mm auto; margin: 3mm; }
        body { font-family: monospace; font-size: ${fs}px; text-align: center; margin: 0; padding: 0; }
        .sep { border-top: 1px dashed #000; margin: 6px 0; }
        .big { font-size: ${fs * 3}px; font-weight: 900; letter-spacing: 4px; margin: 8px 0; }
        .sm { font-size: ${fs * 0.8}px; color: #555; }
        .hdr { font-size: ${fs * 1.2}px; font-weight: bold; white-space: pre-wrap; }
        .ftr { font-size: ${fs * 0.85}px; white-space: pre-wrap; }
      </style>
    </head>
    <body>
      <div class="hdr">${header}</div>
      <div class="sep"></div>
      <div class="big">${currentTicket.ticket_number}</div>
      <div class="sm">${document.getElementById('tktCategory').textContent}</div>
      <div class="sm">${selectedSvcName}</div>
      <div class="sep"></div>
      <div class="sm">${new Date().toLocaleString('es-MX')}</div>
      <div class="sep"></div>
      <div class="ftr">${footer}</div>
    </body></html>`;

  const win = window.open('', '_blank', 'width=300,height=400');
  win.document.write(html);
  win.document.close();
  win.focus();
  setTimeout(() => { win.print(); win.close(); }, 500);
}

function resetKiosk() {
  selectedSvc = null;
  currentTicket = null;
  document.getElementById('step3').style.display = 'none';
  document.getElementById('step1').style.display = 'block';
}

// Auto-reset after 30s of inactivity
let resetTimer;
document.addEventListener('click', () => {
  clearTimeout(resetTimer);
  resetTimer = setTimeout(resetKiosk, 30000);
});
</script>

<?php $pageTitle = 'Monitor en Tiempo Real'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="fw-bold mb-0"><i class="ti ti-eye me-2 text-primary"></i>Monitor de Sucursal</h4>
  <div class="d-flex gap-2 align-items-center">
    <span class="badge bg-success"><i class="ti ti-circle-filled me-1"></i>En vivo</span>
    <span id="lastUpdate" class="text-muted small"></span>
  </div>
</div>

<!-- Stats row -->
<div class="row g-3 mb-4" id="statsRow">
  <div class="col-6 col-md-3"><div class="card border-0 shadow-sm text-center py-3"><h3 class="fw-bold text-warning mb-0" id="statWaiting"><?= $stats['waiting']??0 ?></h3><p class="text-muted small mb-0">En Espera</p></div></div>
  <div class="col-6 col-md-3"><div class="card border-0 shadow-sm text-center py-3"><h3 class="fw-bold text-info mb-0" id="statCalling"><?= $stats['calling']??0 ?></h3><p class="text-muted small mb-0">Llamando</p></div></div>
  <div class="col-6 col-md-3"><div class="card border-0 shadow-sm text-center py-3"><h3 class="fw-bold text-primary mb-0" id="statServing"><?= $stats['serving']??0 ?></h3><p class="text-muted small mb-0">Atendiendo</p></div></div>
  <div class="col-6 col-md-3"><div class="card border-0 shadow-sm text-center py-3"><h3 class="fw-bold text-success mb-0" id="statServed"><?= $stats['served']??0 ?></h3><p class="text-muted small mb-0">Atendidos Hoy</p></div></div>
</div>

<!-- Windows -->
<h6 class="fw-semibold mb-3"><i class="ti ti-layout-grid me-1"></i>Estado de Cajas</h6>
<div class="row g-3 mb-4" id="windowsGrid">
  <?php foreach ($windows as $w): ?>
  <div class="col-6 col-md-4 col-lg-2" id="wcard-<?= $w['id'] ?>">
    <div class="card border-0 shadow-sm text-center py-3 window-status-card <?= $w['cashier_id'] ? 'active-window' : '' ?>">
      <div class="window-num-badge mx-auto mb-1"><?= $w['number'] ?></div>
      <p class="fw-bold small mb-0"><?= htmlspecialchars($w['name']) ?></p>
      <p class="text-muted" style="font-size:0.7rem"><?= htmlspecialchars($w['cashier_name'] ?? 'Libre') ?></p>
      <p class="fw-bold text-primary ticket-display mb-0"><?= htmlspecialchars($w['current_ticket'] ?? '–') ?></p>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Queue per service -->
<h6 class="fw-semibold mb-3"><i class="ti ti-list me-1"></i>Fila por Servicio</h6>
<div class="row g-3">
  <?php foreach ($queues as $q): ?>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <p class="fw-bold mb-0"><?= htmlspecialchars($q['service_name']) ?></p>
            <p class="text-muted small mb-0"><?= htmlspecialchars($q['cat_name']) ?></p>
          </div>
          <span class="badge bg-warning fs-5 px-3"><?= $q['waiting'] ?></span>
        </div>
        <div class="progress mt-2" style="height:4px">
          <div class="progress-bar bg-primary" style="width:<?= min(100, ($q['waiting']/20)*100) ?>%"></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php
$branchId = $user['branch_id'] ?? 1;
$extraJs = '<script>
const BRANCH_ID = ' . $branchId . ';
let lastCallId = 0;
function refreshSupervisor() {
  fetch(APP_URL + "/api/last-calls?branch_id=" + BRANCH_ID + "&last_id=" + lastCallId)
    .then(r => r.json())
    .then(d => {
      if (d.stats) {
        document.getElementById("statWaiting").textContent = d.stats.waiting || 0;
        document.getElementById("statCalling").textContent = d.stats.calling || 0;
        document.getElementById("statServing").textContent = d.stats.serving || 0;
        document.getElementById("statServed").textContent  = d.stats.served  || 0;
      }
      document.getElementById("lastUpdate").textContent = "Actualizado: " + new Date().toLocaleTimeString();
    })
    .catch(() => {});
}
setInterval(refreshSupervisor, 3000);
</script>';
?>

<?php $pageTitle = 'Reportes'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="fw-bold mb-0"><i class="ti ti-chart-bar me-2 text-primary"></i>Reportes de Atención</h4>
  <form method="GET" action="<?= APP_URL ?>/supervisor/reports" class="d-flex gap-2">
    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>">
    <button class="btn btn-primary"><i class="ti ti-search me-1"></i>Ver</button>
  </form>
</div>

<div class="row g-4">
  <!-- Cashier Stats -->
  <div class="col-lg-8">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white fw-semibold border-bottom">
        <i class="ti ti-users me-1"></i>Desempeño por Cajero — <?= date('d/m/Y', strtotime($date)) ?>
      </div>
      <div class="card-body p-0">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr><th>Cajero</th><th>Caja</th><th>Atendidos</th><th>Cancelados</th><th>Inicio</th><th>Fin</th></tr>
          </thead>
          <tbody>
            <?php foreach ($cashierStats as $cs): ?>
            <tr>
              <td class="fw-semibold"><?= htmlspecialchars($cs['full_name']) ?></td>
              <td><?= htmlspecialchars($cs['window_name'] ?? '–') ?></td>
              <td><span class="badge bg-success"><?= $cs['tickets_served'] ?></span></td>
              <td><span class="badge bg-secondary"><?= $cs['tickets_cancelled'] ?></span></td>
              <td class="small text-muted"><?= $cs['shift_start'] ? date('H:i', strtotime($cs['shift_start'])) : '–' ?></td>
              <td class="small text-muted"><?= $cs['shift_end']   ? date('H:i', strtotime($cs['shift_end']))   : '–' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($cashierStats)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Sin registros para esta fecha.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Hourly chart -->
  <div class="col-lg-4">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header bg-white fw-semibold border-bottom">
        <i class="ti ti-chart-line me-1"></i>Tickets por Hora
      </div>
      <div class="card-body">
        <?php
        $hours  = array_column($hourlyData, 'total', 'hour');
        $maxVal = max(array_values($hours) ?: [1]);
        for ($h = 7; $h <= 19; $h++):
          $val  = $hours[$h] ?? 0;
          $pct  = round(($val / $maxVal) * 100);
        ?>
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="text-muted small" style="width:30px"><?= sprintf('%02d', $h) ?>h</span>
          <div class="flex-grow-1 bg-light rounded" style="height:16px">
            <div class="bg-primary rounded" style="height:16px;width:<?= $pct ?>%"></div>
          </div>
          <span class="small fw-semibold" style="width:24px"><?= $val ?></span>
        </div>
        <?php endfor; ?>
      </div>
    </div>
  </div>
</div>

<?php $pageTitle = 'Cajas / Ventanillas'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="fw-bold mb-0"><i class="ti ti-layout-grid me-2 text-primary"></i>Cajas / Ventanillas</h4>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalWindow">
    <i class="ti ti-plus me-1"></i> Nueva Caja
  </button>
</div>

<div class="row g-3">
  <?php foreach ($windows as $w): ?>
  <div class="col-md-4 col-lg-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body text-center py-4">
        <div class="window-num bg-primary-subtle text-primary rounded-circle mx-auto mb-3">
          <?= $w['number'] ?>
        </div>
        <h5 class="fw-bold"><?= htmlspecialchars($w['name']) ?></h5>
        <p class="text-muted small"><?= htmlspecialchars($w['services'] ?? 'Sin servicios') ?></p>
        <form method="POST" action="<?= APP_URL ?>/admin/windows/<?= $w['id'] ?>/delete"
              onsubmit="return confirm('¿Eliminar caja?')">
          <button class="btn btn-sm btn-outline-danger"><i class="ti ti-trash me-1"></i>Eliminar</button>
        </form>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="modal fade" id="modalWindow" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="<?= APP_URL ?>/admin/windows/create" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Nueva Caja</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-6"><label class="form-label">Número</label><input type="number" name="number" class="form-control" min="1" required></div>
        <div class="col-6"><label class="form-label">Nombre</label><input name="name" class="form-control" placeholder="Caja 1" required></div>
        <div class="col-12">
          <label class="form-label">Sucursal</label>
          <select name="branch_id" class="form-select">
            <?php foreach ($branches as $b): ?><option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label">Servicios que atiende</label>
          <?php foreach ($services as $s): ?>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="service_types[]" value="<?= $s['id'] ?>" id="svc<?= $s['id'] ?>" checked>
            <label class="form-check-label" for="svc<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></label>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Crear</button></div>
    </form>
  </div>
</div>

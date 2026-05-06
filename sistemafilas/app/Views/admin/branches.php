<?php /* BRANCHES */ $pageTitle = 'Sucursales'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="fw-bold mb-0"><i class="ti ti-building me-2 text-primary"></i>Sucursales</h4>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalBranch">
    <i class="ti ti-plus me-1"></i> Nueva Sucursal
  </button>
</div>
<div class="row g-3 mb-4">
  <?php foreach ($branches as $b): ?>
  <div class="col-md-4">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <h5 class="card-title"><i class="ti ti-building me-1 text-primary"></i><?= htmlspecialchars($b['name']) ?></h5>
        <p class="text-muted small mb-1"><i class="ti ti-map-pin me-1"></i><?= htmlspecialchars($b['address'] ?? '–') ?></p>
        <p class="text-muted small mb-3"><i class="ti ti-phone me-1"></i><?= htmlspecialchars($b['phone'] ?? '–') ?></p>
        <div class="d-flex gap-2">
          <a href="<?= APP_URL ?>/display?branch=<?= $b['id'] ?>" class="btn btn-sm btn-outline-info" target="_blank">
            <i class="ti ti-device-tv"></i> Pantalla
          </a>
          <a href="<?= APP_URL ?>/dispenser?branch=<?= $b['id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
            <i class="ti ti-printer"></i> Dispensador
          </a>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="modal fade" id="modalBranch" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="<?= APP_URL ?>/admin/branches/create" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Nueva Sucursal</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">Nombre</label><input name="name" class="form-control" required></div>
        <div class="col-12"><label class="form-label">Dirección</label><input name="address" class="form-control"></div>
        <div class="col-12"><label class="form-label">Teléfono</label><input name="phone" class="form-control"></div>
      </div>
      <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Crear</button></div>
    </form>
  </div>
</div>

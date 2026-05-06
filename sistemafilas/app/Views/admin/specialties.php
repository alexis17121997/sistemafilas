<?php $pageTitle = 'Especialidades Médicas'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="fw-bold mb-0"><i class="ti ti-stethoscope me-2 text-primary"></i>Especialidades / Médicos</h4>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSpec">
    <i class="ti ti-plus me-1"></i> Agregar
  </button>
</div>

<div class="row g-3">
  <?php foreach ($specs as $s): ?>
  <div class="col-md-4">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-2">
          <div class="specialty-icon bg-primary-subtle text-primary rounded-circle">
            <i class="ti ti-stethoscope"></i>
          </div>
          <div>
            <h6 class="fw-bold mb-0"><?= htmlspecialchars($s['name']) ?></h6>
            <p class="text-muted small mb-0"><?= htmlspecialchars($s['doctor_name'] ?? '') ?></p>
          </div>
        </div>
        <p class="small mb-1"><i class="ti ti-clock me-1 text-muted"></i><?= htmlspecialchars($s['schedule'] ?? '') ?></p>
        <p class="small mb-3"><i class="ti ti-door me-1 text-muted"></i><?= htmlspecialchars($s['room'] ?? '') ?></p>
        <form method="POST" action="<?= APP_URL ?>/admin/specialties/<?= $s['id'] ?>/delete"
              onsubmit="return confirm('¿Eliminar especialidad?')" class="d-inline">
          <button class="btn btn-sm btn-outline-danger"><i class="ti ti-trash me-1"></i>Eliminar</button>
        </form>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="modal fade" id="modalSpec" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="<?= APP_URL ?>/admin/specialties/save" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Nueva Especialidad</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">Especialidad</label><input name="name" class="form-control" required></div>
        <div class="col-12"><label class="form-label">Médico</label><input name="doctor_name" class="form-control"></div>
        <div class="col-6"><label class="form-label">Horario</label><input name="schedule" class="form-control" placeholder="Lun-Vie 8:00-14:00"></div>
        <div class="col-6"><label class="form-label">Consultorio</label><input name="room" class="form-control" placeholder="Consultorio 1"></div>
        <div class="col-12"><label class="form-label">Imagen URL <small class="text-muted">(opcional)</small></label><input name="image_url" class="form-control"></div>
        <div class="col-6"><label class="form-label">Orden</label><input type="number" name="sort_order" class="form-control" value="0"></div>
      </div>
      <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Guardar</button></div>
    </form>
  </div>
</div>

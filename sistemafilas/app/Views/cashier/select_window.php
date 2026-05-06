<?php $pageTitle = 'Seleccionar Caja'; ?>
<div class="row justify-content-center mt-5">
  <div class="col-md-8 col-lg-6">
    <div class="card shadow border-0">
      <div class="card-body p-5 text-center">
        <div class="mb-4">
          <div class="cashier-welcome-icon bg-primary-subtle text-primary rounded-circle mx-auto mb-3">
            <i class="ti ti-layout-grid fs-1"></i>
          </div>
          <h4 class="fw-bold">Bienvenido/a, <?= htmlspecialchars($user['full_name']) ?></h4>
          <p class="text-muted">Seleccione la caja en la que trabajará hoy</p>
        </div>

        <form method="POST" action="<?= APP_URL ?>/cashier/select-window">
          <div class="row g-3 mb-4">
            <?php foreach ($windows as $w): ?>
            <div class="col-4">
              <input type="radio" class="btn-check" name="window_id" id="w<?= $w['id'] ?>" value="<?= $w['id'] ?>" required>
              <label class="btn btn-outline-primary w-100 py-4" for="w<?= $w['id'] ?>">
                <div class="fs-1 fw-bold"><?= $w['number'] ?></div>
                <div class="small"><?= htmlspecialchars($w['name']) ?></div>
              </label>
            </div>
            <?php endforeach; ?>
            <?php if (empty($windows)): ?>
            <p class="text-muted">No hay cajas disponibles. Contacte al administrador.</p>
            <?php endif; ?>
          </div>
          <button type="submit" class="btn btn-primary btn-lg px-5" <?= empty($windows)?'disabled':'' ?>>
            <i class="ti ti-check me-1"></i> Asignarme esta Caja
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

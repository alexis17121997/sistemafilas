<div class="auth-card card shadow-lg border-0" style="width:420px;">
  <div class="card-body p-5">
    <div class="text-center mb-4">
      <div class="auth-icon mb-3">
        <i class="ti ti-heart-rate-monitor"></i>
      </div>
      <h2 class="fw-bold text-primary mb-1"><?= APP_NAME ?></h2>
      <p class="text-muted small">Sistema de Gestión de Filas</p>
    </div>

    <?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 text-center small"><i class="ti ti-alert-circle me-1"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= APP_URL ?>/login">
      <div class="mb-3">
        <label class="form-label fw-semibold">Usuario</label>
        <div class="input-group">
          <span class="input-group-text"><i class="ti ti-user"></i></span>
          <input type="text" name="username" class="form-control form-control-lg" placeholder="usuario" autofocus required>
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold">Contraseña</label>
        <div class="input-group">
          <span class="input-group-text"><i class="ti ti-lock"></i></span>
          <input type="password" name="password" class="form-control form-control-lg" placeholder="••••••••" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-lg w-100">
        <i class="ti ti-login me-1"></i> Ingresar
      </button>
    </form>

    <div class="mt-4 text-center text-muted small">
      <i class="ti ti-info-circle me-1"></i>
      Para soporte contacte al administrador del sistema.
    </div>
  </div>
</div>

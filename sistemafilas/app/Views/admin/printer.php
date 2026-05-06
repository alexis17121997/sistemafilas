<?php $pageTitle = 'Configuración de Impresora'; ?>
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="d-flex align-items-center gap-3 mb-4">
      <h4 class="fw-bold mb-0"><i class="ti ti-printer me-2 text-primary"></i>Impresora Térmica</h4>
      <span class="badge bg-secondary">Epson TM-T88V</span>
    </div>

    <div class="row g-4">
      <div class="col-md-6">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-white fw-semibold border-bottom">
            <i class="ti ti-settings me-1"></i> Parámetros
          </div>
          <div class="card-body">
            <form method="POST" action="<?= APP_URL ?>/admin/printer/save">
              <div class="mb-3">
                <label class="form-label">Nombre de impresora</label>
                <input name="printer_name" class="form-control" value="<?= htmlspecialchars($config['printer_name'] ?? '') ?>">
                <div class="form-text">Como aparece en Windows / macOS</div>
              </div>
              <div class="row g-3 mb-3">
                <div class="col-6">
                  <label class="form-label">Ancho de papel (mm)</label>
                  <select name="paper_width" class="form-select">
                    <option value="56" <?= ($config['paper_width']??72)==56?'selected':''?>>56 mm</option>
                    <option value="72" <?= ($config['paper_width']??72)==72?'selected':''?>>72 mm (recomendado)</option>
                    <option value="80" <?= ($config['paper_width']??72)==80?'selected':''?>>80 mm</option>
                  </select>
                </div>
                <div class="col-6">
                  <label class="form-label">Tamaño de fuente (px)</label>
                  <input type="number" name="font_size" class="form-control" min="10" max="24"
                         value="<?= $config['font_size'] ?? 14 ?>">
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Encabezado del ticket</label>
                <textarea name="header_text" class="form-control" rows="2"><?= htmlspecialchars($config['header_text'] ?? '') ?></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label">Pie del ticket</label>
                <textarea name="footer_text" class="form-control" rows="2"><?= htmlspecialchars($config['footer_text'] ?? '') ?></textarea>
              </div>
              <div class="mb-3 form-check">
                <input class="form-check-input" type="checkbox" name="show_logo" id="show_logo"
                       <?= ($config['show_logo']??true)?'checked':'' ?>>
                <label class="form-check-label" for="show_logo">Mostrar logo en ticket</label>
              </div>
              <button class="btn btn-primary w-100">
                <i class="ti ti-device-floppy me-1"></i> Guardar Configuración
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Ticket Preview -->
      <div class="col-md-6">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-white fw-semibold border-bottom">
            <i class="ti ti-eye me-1"></i> Vista Previa del Ticket
          </div>
          <div class="card-body d-flex justify-content-center bg-light p-4">
            <div class="ticket-preview" id="ticketPreview"
                 style="width:<?= ($config['paper_width']??72) ?>mm; font-size:<?= ($config['font_size']??14) ?>px;">
              <div class="ticket-header"><?= nl2br(htmlspecialchars($config['header_text'] ?? 'CLÍNICA')) ?></div>
              <div class="ticket-separator">━━━━━━━━━━━━━━━━━━━</div>
              <div class="ticket-number">G-SRV-001</div>
              <div class="ticket-category" style="color: #4A90E2;">⬤ General</div>
              <div class="ticket-service">Servicio: Servicios</div>
              <div class="ticket-separator">━━━━━━━━━━━━━━━━━━━</div>
              <div class="ticket-date"><?= date('d/m/Y H:i') ?></div>
              <div class="ticket-wait">Pacientes en espera: ~3</div>
              <div class="ticket-separator">━━━━━━━━━━━━━━━━━━━</div>
              <div class="ticket-footer"><?= nl2br(htmlspecialchars($config['footer_text'] ?? '')) ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php $pageTitle = 'Imprimir Ticket'; ?>
<style>
  @page { size: <?= ($config['paper_width'] ?? 72) ?>mm auto; margin: 3mm; }
  * { box-sizing: border-box; }
  body {
    font-family: monospace;
    font-size: <?= ($config['font_size'] ?? 14) ?>px;
    text-align: center;
    margin: 0;
    padding: 0;
    background: #fff;
    color: #000;
  }
  .ticket-wrap { width: <?= ($config['paper_width'] ?? 72) ?>mm; margin: 0 auto; padding: 4mm; }
  .hdr { font-size: <?= round(($config['font_size'] ?? 14) * 1.2) ?>px; font-weight: bold; white-space: pre-wrap; margin-bottom: 4mm; }
  .sep { border: none; border-top: 1px dashed #000; margin: 3mm 0; }
  .big { font-size: <?= round(($config['font_size'] ?? 14) * 3.5) ?>px; font-weight: 900; letter-spacing: 4px; margin: 4mm 0; line-height: 1; }
  .cat { font-size: <?= round(($config['font_size'] ?? 14) * 1.1) ?>px; font-weight: bold; margin: 2mm 0; }
  .svc { margin: 1mm 0; }
  .sm { font-size: <?= round(($config['font_size'] ?? 14) * 0.85) ?>px; color: #444; }
  .ftr { font-size: <?= round(($config['font_size'] ?? 14) * 0.85) ?>px; white-space: pre-wrap; margin-top: 3mm; }
  .no-print { padding: 20px; text-align: center; }
  @media print { .no-print { display: none !important; } }
</style>

<div class="ticket-wrap">
  <?php if ($config['show_logo']): ?>
  <div style="font-size:2em; margin-bottom:3mm;">🏥</div>
  <?php endif; ?>
  <div class="hdr"><?= nl2br(htmlspecialchars($config['header_text'] ?? '')) ?></div>
  <div class="sep"></div>
  <div class="sm">Su número de atención:</div>
  <div class="big"><?= htmlspecialchars($ticket['ticket_number']) ?></div>
  <div class="cat"><?= htmlspecialchars($ticket['cat_name']) ?></div>
  <div class="svc sm">Servicio: <?= htmlspecialchars($ticket['service_name']) ?></div>
  <div class="sep"></div>
  <div class="sm">Sucursal: <?= htmlspecialchars($ticket['branch_name']) ?></div>
  <div class="sm"><?= date('d/m/Y H:i:s', strtotime($ticket['issued_at'])) ?></div>
  <div class="sep"></div>
  <div class="ftr"><?= nl2br(htmlspecialchars($config['footer_text'] ?? '')) ?></div>
</div>

<div class="no-print" style="padding:20px">
  <button onclick="window.print()" style="padding:10px 30px; font-size:16px; cursor:pointer; background:#2563eb; color:#fff; border:none; border-radius:8px; margin-right:10px">
    🖨 Imprimir
  </button>
  <button onclick="window.close()" style="padding:10px 20px; font-size:16px; cursor:pointer; background:#eee; border:none; border-radius:8px">
    Cerrar
  </button>
</div>

<script>
  // Auto-print when opened in popup
  if (window.opener) {
    window.addEventListener('load', () => setTimeout(() => window.print(), 400));
  }
</script>

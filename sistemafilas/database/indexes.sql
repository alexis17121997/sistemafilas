-- ============================================================
--  PERFORMANCE INDEXES  –  Optimizado para 1000+ tickets/día
--  Ejecutar DESPUÉS del schema.sql
-- ============================================================

-- Tickets: hot query paths
CREATE INDEX IF NOT EXISTS idx_tickets_branch_status_priority
    ON tickets (branch_id, status, issued_at)
    WHERE status = 'waiting';

CREATE INDEX IF NOT EXISTS idx_tickets_window_active
    ON tickets (window_id, status)
    WHERE status IN ('calling','serving');

CREATE INDEX IF NOT EXISTS idx_tickets_issued_date
    ON tickets (branch_id, issued_at::date);

CREATE INDEX IF NOT EXISTS idx_tickets_cashier_date
    ON tickets (cashier_id, completed_at::date)
    WHERE status = 'served';

-- Ticket calls: polling display every 2s
CREATE INDEX IF NOT EXISTS idx_ticket_calls_branch_id
    ON ticket_calls (ticket_id);

CREATE INDEX IF NOT EXISTS idx_ticket_calls_recent
    ON ticket_calls (id DESC, called_at DESC);

-- Window assignments: very frequent lookup
CREATE INDEX IF NOT EXISTS idx_window_assign_user
    ON window_assignments (user_id, active)
    WHERE active = TRUE;

-- Daily counters: atomic increment on every ticket issue
CREATE INDEX IF NOT EXISTS idx_daily_counters_lookup
    ON daily_counters (branch_id, service_type_id, category_id, date);

-- Attendance logs
CREATE INDEX IF NOT EXISTS idx_attendance_date_branch
    ON attendance_logs (date, branch_id);

-- ── Autovacuum tuning for high-traffic tables ─────────────────────────────
-- Tickets table gets heavy INSERT/UPDATE traffic
ALTER TABLE tickets SET (
    autovacuum_vacuum_scale_factor   = 0.02,
    autovacuum_analyze_scale_factor  = 0.01,
    autovacuum_vacuum_cost_delay     = 2
);

ALTER TABLE ticket_calls SET (
    autovacuum_vacuum_scale_factor   = 0.02,
    autovacuum_analyze_scale_factor  = 0.01
);

-- ── Add 4 demo advertising videos (to replace with real content) ──────────
INSERT INTO advertising_content (branch_id, type, title, url, duration, sort_order, active)
VALUES
  (1, 'placeholder', 'Higiene de Manos',         'local:higiene_manos.mp4',        15, 1, TRUE),
  (1, 'placeholder', 'Servicios del Hospital',   'local:servicios_hospital.mp4',   15, 2, TRUE),
  (1, 'placeholder', 'Prevención y Salud',       'local:prevencion_salud.mp4',     15, 3, TRUE),
  (1, 'placeholder', 'Información General',      'local:informacion_general.mp4',  15, 4, TRUE)
ON CONFLICT DO NOTHING;

-- ── ANALYZE after index creation ─────────────────────────────────────────
ANALYZE tickets;
ANALYZE ticket_calls;
ANALYZE window_assignments;
ANALYZE daily_counters;

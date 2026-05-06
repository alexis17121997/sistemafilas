-- ============================================================
--  SISTEMA DE FILAS - CLÍNICA  |  PostgreSQL Schema
-- ============================================================

CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- ------------------------------------------------------------
-- ROLES
-- ------------------------------------------------------------
CREATE TABLE roles (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(30)  NOT NULL UNIQUE,   -- admin | supervisor | cashier | dispenser | display
    description VARCHAR(120)
);

INSERT INTO roles (name, description) VALUES
  ('admin',      'Administrador del sistema'),
  ('supervisor', 'Supervisor de sucursal'),
  ('cashier',    'Cajero / ventanilla'),
  ('dispenser',  'Dispensador de tickets (kiosco)'),
  ('display',    'Pantalla de llamado');

-- ------------------------------------------------------------
-- BRANCHES  (Sucursales)
-- ------------------------------------------------------------
CREATE TABLE branches (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    address    TEXT,
    phone      VARCHAR(30),
    logo_url   TEXT,
    active     BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- ------------------------------------------------------------
-- USERS
-- ------------------------------------------------------------
CREATE TABLE users (
    id            SERIAL PRIMARY KEY,
    branch_id     INTEGER      REFERENCES branches(id) ON DELETE SET NULL,
    role_id       INTEGER      NOT NULL REFERENCES roles(id),
    username      VARCHAR(60)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(120),
    active        BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMP    NOT NULL DEFAULT NOW(),
    last_login    TIMESTAMP
);

-- Default admin  (password: Admin2024!)
INSERT INTO branches (name, address, phone) VALUES ('Clínica Central', 'Av. Salud #100', '555-0000');

INSERT INTO users (branch_id, role_id, username, password_hash, full_name, email) VALUES
  (1, 1, 'admin', crypt('Admin2024!', gen_salt('bf')), 'Administrador General', 'admin@clinica.com');

-- ------------------------------------------------------------
-- PATIENT CATEGORIES
-- ------------------------------------------------------------
CREATE TABLE patient_categories (
    id          SERIAL PRIMARY KEY,
    code        VARCHAR(4)   NOT NULL UNIQUE,  -- G | P | E | D
    name        VARCHAR(60)  NOT NULL,
    description VARCHAR(160),
    priority    SMALLINT     NOT NULL DEFAULT 0,  -- higher = higher priority
    color       VARCHAR(7)   NOT NULL DEFAULT '#4A90E2',
    icon        VARCHAR(40)  NOT NULL DEFAULT 'ti-user',
    active      BOOLEAN      NOT NULL DEFAULT TRUE
);

INSERT INTO patient_categories (code, name, description, priority, color, icon) VALUES
  ('G',  'General',           'Paciente en atención general',              0,  '#4A90E2', 'ti-user'),
  ('P',  '3ra Edad',          'Adulto mayor (60+ años) - Atención preferencial', 3, '#F5A623', 'ti-armchair'),
  ('E',  'Embarazada',        'Mujer embarazada - Atención prioritaria',   4,  '#D0021B', 'ti-heart'),
  ('D',  'Discapacitado',     'Persona con discapacidad - Atención especial', 2, '#7B68EE', 'ti-accessible');

-- ------------------------------------------------------------
-- SERVICE TYPES  (Servicios, Seguros, Farmacia)
-- ------------------------------------------------------------
CREATE TABLE service_types (
    id          SERIAL PRIMARY KEY,
    code        VARCHAR(6)  NOT NULL UNIQUE,  -- SRV | SEG | FAR
    name        VARCHAR(60) NOT NULL,
    description VARCHAR(160),
    color       VARCHAR(7)  NOT NULL DEFAULT '#333333',
    active      BOOLEAN     NOT NULL DEFAULT TRUE
);

INSERT INTO service_types (code, name, description, color) VALUES
  ('SRV', 'Servicios',  'Atención general de servicios clínicos',  '#2196F3'),
  ('SEG', 'Seguros',    'Tramitación de seguros y coberturas',     '#4CAF50'),
  ('FAR', 'Farmacia',   'Entrega y dispensación de medicamentos',  '#FF9800');

-- ------------------------------------------------------------
-- WINDOWS  (Cajas / Ventanillas)
-- ------------------------------------------------------------
CREATE TABLE windows (
    id        SERIAL PRIMARY KEY,
    branch_id INTEGER     NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
    number    SMALLINT    NOT NULL,
    name      VARCHAR(50) NOT NULL,          -- "Caja 1"
    active    BOOLEAN     NOT NULL DEFAULT TRUE,
    UNIQUE (branch_id, number)
);

-- Link windows to service types (many-to-many)
CREATE TABLE window_service_types (
    window_id       INTEGER NOT NULL REFERENCES windows(id) ON DELETE CASCADE,
    service_type_id INTEGER NOT NULL REFERENCES service_types(id) ON DELETE CASCADE,
    PRIMARY KEY (window_id, service_type_id)
);

-- ------------------------------------------------------------
-- WINDOW ASSIGNMENTS  (qué cajero está en qué caja HOY)
-- ------------------------------------------------------------
CREATE TABLE window_assignments (
    id          SERIAL PRIMARY KEY,
    window_id   INTEGER   NOT NULL REFERENCES windows(id) ON DELETE CASCADE,
    user_id     INTEGER   NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    assigned_at TIMESTAMP NOT NULL DEFAULT NOW(),
    released_at TIMESTAMP,
    active      BOOLEAN   NOT NULL DEFAULT TRUE
);

CREATE UNIQUE INDEX idx_window_single_active
    ON window_assignments (window_id)
    WHERE active = TRUE;

CREATE UNIQUE INDEX idx_cashier_single_window
    ON window_assignments (user_id)
    WHERE active = TRUE;

-- ------------------------------------------------------------
-- DAILY TICKET COUNTERS  (one row per branch+category+service+date)
-- ------------------------------------------------------------
CREATE TABLE daily_counters (
    id              SERIAL  PRIMARY KEY,
    branch_id       INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
    service_type_id INTEGER NOT NULL REFERENCES service_types(id),
    category_id     INTEGER NOT NULL REFERENCES patient_categories(id),
    date            DATE    NOT NULL DEFAULT CURRENT_DATE,
    last_number     INTEGER NOT NULL DEFAULT 0,
    UNIQUE (branch_id, service_type_id, category_id, date)
);

-- ------------------------------------------------------------
-- TICKETS
-- ------------------------------------------------------------
CREATE TABLE tickets (
    id              SERIAL PRIMARY KEY,
    branch_id       INTEGER     NOT NULL REFERENCES branches(id),
    service_type_id INTEGER     NOT NULL REFERENCES service_types(id),
    category_id     INTEGER     NOT NULL REFERENCES patient_categories(id),
    ticket_number   VARCHAR(20) NOT NULL,   -- e.g.  G-SRV-001
    status          VARCHAR(20) NOT NULL DEFAULT 'waiting',
                                            -- waiting|calling|serving|served|cancelled|no_show
    issued_at       TIMESTAMP   NOT NULL DEFAULT NOW(),
    called_at       TIMESTAMP,
    served_at       TIMESTAMP,
    completed_at    TIMESTAMP,
    window_id       INTEGER     REFERENCES windows(id),
    cashier_id      INTEGER     REFERENCES users(id),
    call_count      SMALLINT    NOT NULL DEFAULT 0,
    wait_seconds    INTEGER,
    service_seconds INTEGER,
    notes           TEXT
);

CREATE INDEX idx_tickets_branch_status  ON tickets (branch_id, status);
CREATE INDEX idx_tickets_issued_at      ON tickets (issued_at);

-- ------------------------------------------------------------
-- TICKET CALLS  (log every time a ticket is announced)
-- ------------------------------------------------------------
CREATE TABLE ticket_calls (
    id          SERIAL PRIMARY KEY,
    ticket_id   INTEGER   NOT NULL REFERENCES tickets(id) ON DELETE CASCADE,
    window_id   INTEGER   NOT NULL REFERENCES windows(id),
    cashier_id  INTEGER   NOT NULL REFERENCES users(id),
    called_at   TIMESTAMP NOT NULL DEFAULT NOW(),
    call_num    SMALLINT  NOT NULL DEFAULT 1
);

CREATE INDEX idx_ticket_calls_called_at ON ticket_calls (called_at DESC);

-- ------------------------------------------------------------
-- ATTENDANCE LOG  (daily stats per cashier)
-- ------------------------------------------------------------
CREATE TABLE attendance_logs (
    id                  SERIAL PRIMARY KEY,
    date                DATE    NOT NULL DEFAULT CURRENT_DATE,
    branch_id           INTEGER NOT NULL REFERENCES branches(id),
    cashier_id          INTEGER NOT NULL REFERENCES users(id),
    window_id           INTEGER REFERENCES windows(id),
    tickets_served      INTEGER NOT NULL DEFAULT 0,
    tickets_cancelled   INTEGER NOT NULL DEFAULT 0,
    avg_service_seconds INTEGER,
    shift_start         TIMESTAMP,
    shift_end           TIMESTAMP,
    UNIQUE (date, cashier_id)
);

-- ------------------------------------------------------------
-- PRINTER CONFIGS  (one per branch)
-- ------------------------------------------------------------
CREATE TABLE printer_configs (
    id           SERIAL PRIMARY KEY,
    branch_id    INTEGER NOT NULL REFERENCES branches(id) ON DELETE CASCADE UNIQUE,
    printer_name VARCHAR(120) DEFAULT 'Epson TM-T88V',
    paper_width  SMALLINT    NOT NULL DEFAULT 72,   -- mm (printable on 80mm roll)
    font_size    SMALLINT    NOT NULL DEFAULT 14,
    header_text  TEXT        DEFAULT 'CLÍNICA',
    footer_text  TEXT        DEFAULT 'Gracias por su visita. Por favor espere ser llamado.',
    show_logo    BOOLEAN     NOT NULL DEFAULT TRUE,
    logo_base64  TEXT,
    updated_at   TIMESTAMP   NOT NULL DEFAULT NOW()
);

-- ------------------------------------------------------------
-- ADVERTISING CONTENT  (videos e imágenes para pantalla)
-- ------------------------------------------------------------
CREATE TABLE advertising_content (
    id         SERIAL PRIMARY KEY,
    branch_id  INTEGER     NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
    type       VARCHAR(10) NOT NULL,   -- video | image
    title      VARCHAR(120),
    url        TEXT        NOT NULL,
    duration   SMALLINT    NOT NULL DEFAULT 10,  -- seconds
    sort_order SMALLINT    NOT NULL DEFAULT 0,
    active     BOOLEAN     NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP   NOT NULL DEFAULT NOW()
);

-- ------------------------------------------------------------
-- SPECIALTIES / DOCTORS  (carrusel en pantalla)
-- ------------------------------------------------------------
CREATE TABLE specialties (
    id           SERIAL PRIMARY KEY,
    branch_id    INTEGER     NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
    name         VARCHAR(100) NOT NULL,
    doctor_name  VARCHAR(120),
    schedule     VARCHAR(200),
    room         VARCHAR(50),
    image_url    TEXT,
    sort_order   SMALLINT    NOT NULL DEFAULT 0,
    active       BOOLEAN     NOT NULL DEFAULT TRUE
);

-- Default specialties for branch 1
INSERT INTO specialties (branch_id, name, doctor_name, schedule, room) VALUES
  (1, 'Medicina General',    'Dr. Carlos Méndez',     'Lun-Vie 8:00-14:00',  'Consultorio 1'),
  (1, 'Pediatría',           'Dra. Ana González',     'Lun-Vie 9:00-13:00',  'Consultorio 2'),
  (1, 'Ginecología',         'Dra. María Rodríguez',  'Mar-Jue 10:00-14:00', 'Consultorio 3'),
  (1, 'Cardiología',         'Dr. Javier Torres',     'Lun-Mié 8:00-12:00',  'Consultorio 4'),
  (1, 'Ortopedia',           'Dr. Luis Herrera',      'Vie 9:00-13:00',      'Consultorio 5'),
  (1, 'Nutrición',           'Lic. Sofía Vargas',     'Lun-Vie 8:00-16:00',  'Consultorio 6');

-- ------------------------------------------------------------
-- SETTINGS  (key-value per branch)
-- ------------------------------------------------------------
CREATE TABLE settings (
    id        SERIAL PRIMARY KEY,
    branch_id INTEGER     NOT NULL REFERENCES branches(id) ON DELETE CASCADE,
    key       VARCHAR(60) NOT NULL,
    value     TEXT,
    UNIQUE (branch_id, key)
);

INSERT INTO settings (branch_id, key, value) VALUES
  (1, 'clinic_name',          'Clínica Central'),
  (1, 'display_refresh_ms',   '2000'),
  (1, 'announcement_repeat',  '2'),
  (1, 'max_windows_display',  '5');

-- ------------------------------------------------------------
-- Create default windows for branch 1
-- ------------------------------------------------------------
INSERT INTO windows (branch_id, number, name) VALUES
  (1, 1, 'Caja 1'),
  (1, 2, 'Caja 2'),
  (1, 3, 'Caja 3'),
  (1, 4, 'Caja 4'),
  (1, 5, 'Caja 5');

-- All windows handle all services by default
INSERT INTO window_service_types (window_id, service_type_id)
SELECT w.id, st.id FROM windows w CROSS JOIN service_types st WHERE w.branch_id = 1;

-- Printer config for branch 1
INSERT INTO printer_configs (branch_id, header_text, footer_text)
VALUES (1, 'CLÍNICA CENTRAL', 'Gracias por su visita.\nEspere ser llamado en pantalla.');

-- ------------------------------------------------------------
-- VIEWS  (useful queries)
-- ------------------------------------------------------------

-- Current queue status per branch
CREATE OR REPLACE VIEW v_queue_status AS
SELECT
    t.branch_id,
    st.code         AS service_code,
    st.name         AS service_name,
    pc.code         AS cat_code,
    pc.name         AS cat_name,
    pc.priority,
    COUNT(*)        AS waiting,
    MIN(t.issued_at) AS oldest_ticket
FROM tickets t
JOIN service_types st ON st.id = t.service_type_id
JOIN patient_categories pc ON pc.id = t.category_id
WHERE t.status = 'waiting'
GROUP BY t.branch_id, st.code, st.name, pc.code, pc.name, pc.priority;

-- Active window status
CREATE OR REPLACE VIEW v_window_status AS
SELECT
    w.id,
    w.branch_id,
    w.number,
    w.name,
    wa.user_id      AS cashier_id,
    u.full_name     AS cashier_name,
    t.ticket_number AS current_ticket,
    t.id            AS current_ticket_id,
    t.status        AS ticket_status
FROM windows w
LEFT JOIN window_assignments wa ON wa.window_id = w.id AND wa.active = TRUE
LEFT JOIN users u ON u.id = wa.user_id
LEFT JOIN tickets t ON t.window_id = w.id AND t.status IN ('calling','serving')
WHERE w.active = TRUE;

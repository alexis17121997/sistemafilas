# Sistema de Filas – Hospital
### PHP MVC · PostgreSQL · Apache / XAMPP

---

## Instalación rápida

### 1. Copiar archivos
```
C:\xampp\htdocs\clinic-queue\
```

### 2. PostgreSQL
```sql
-- Crear base de datos
CREATE DATABASE clinic_queue;

-- Ejecutar en orden:
\i database/schema.sql
\i database/indexes.sql
```

### 3. Activar extensiones PHP en XAMPP
Editar `C:\xampp\php\php.ini`, descomentar:
```
extension=pdo_pgsql
extension=pgsql
```

### 4. Configurar conexión
Editar `config/database.php`:
```php
define('DB_PASS', 'TU_PASSWORD_POSTGRES');
```

### 5. Modo offline (sin internet)
Abrir en navegador:
```
http://localhost/clinic-queue/download_assets.php
```
Esto descarga Bootstrap e iconos a `/public/vendor/` para funcionar sin red.

### 6. Acceder al sistema
```
http://localhost/clinic-queue/login
```
- Usuario: `admin`
- Contraseña: `Admin2024!`

---

## URLs principales
| Pantalla           | URL                                              |
|--------------------|--------------------------------------------------|
| Login              | `/login`                                         |
| Admin              | `/admin/dashboard`                               |
| Monitor            | `/supervisor/dashboard`                          |
| Cajero             | `/cashier`                                       |
| Pantalla llamado   | `/display?branch=1`                              |
| Dispensador kiosco | `/dispenser?branch=1`                            |
| Descargar assets   | `/download_assets.php`                           |

---

## Videos publicitarios (4 slots)
Colocar archivos MP4 en:
```
public/assets/videos/
  higiene_manos.mp4
  servicios_hospital.mp4
  prevencion_salud.mp4
  informacion_general.mp4
```
O administrar desde **Admin → Publicidad**.

---

## Sonido (100% offline)
- **Campana**: Web Audio API – osciladores sintetizados, sin archivos externos
- **Voz**: Web Speech API – motor TTS del sistema operativo (Windows/macOS/Linux)
- Se reproduce **2 veces** automáticamente al llamar cada ticket

---

## Rendimiento
- Soporta **1000+ tickets/día** con los índices de `indexes.sql`
- Hasta **10 usuarios concurrentes** sin degradación
- PostgreSQL maneja el lock atómico en `daily_counters` evitando números duplicados

---

## Cambiar logo
Reemplazar el SVG inline en:
- `app/Views/layouts/main.php` (navbar)
- `app/Views/display/screen.php` (pantalla)
- `app/Views/auth/login.php` (login)

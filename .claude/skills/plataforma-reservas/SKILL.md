---
name: plataforma-reservas
description: Arquitecto y guía de implementación para construir una plataforma de reservas de CUALQUIER recurso (apartamentos, canchas, equipos, consultorios, vehículos, turnos, salones, etc.), basada en los patrones probados y auditados en producción del proyecto NagoScale (PHP + MySQL). Úsala cuando el usuario pida construir, diseñar o escalar un sistema donde clientes reservan un recurso con disponibilidad limitada por fechas u horarios, con uno o varios proveedores/administradores dueños de esos recursos. Incluye el modelo de datos genérico, la lógica anti-doble-reserva, y los 6 bloques de seguridad (auth, IDOR, exposición de datos, concurrencia, uploads, headers) incorporados desde el diseño, no como parche posterior.
---

# PLATAFORMA DE RESERVAS — Blueprint genérico v1

## Origen y principio

Este blueprint viene de una auditoría de seguridad real y exhaustiva sobre NagoScale (plataforma de alquiler de apartamentos por días). Esa auditoría encontró y corrigió 6 clases de vulnerabilidad que **cualquier** plataforma de reservas reinventa si no las conoce de antemano. Esta skill existe para que la próxima plataforma de reservas que construyas nazca con esas 6 correcciones ya incorporadas, en vez de descubrirlas una por una en producción.

> No reinventes el modelo de concurrencia de reservas desde cero. No reinventes la validación de uploads desde cero. Ya se rompieron una vez, aquí está la versión que no se rompe.

## Cuándo usar esta skill

- El usuario pide construir una plataforma donde alguien reserva un **recurso con disponibilidad limitada en el tiempo** (fechas o franjas horarias) que pertenece a un proveedor/administrador.
- Ejemplos de "recurso": apartamentos, canchas deportivas, salones de eventos, consultorios médicos, vehículos de alquiler, equipos, mesas de restaurante, citas de servicio.
- El usuario pide "algo como Airbnb/Calendly/OpenTable pero para X".

No uses esta skill para sistemas de reservas de un solo proveedor sin concepto de "disponibilidad que se agota" (eso es más simple, no necesita el modelo anti-solape).

## 1. Modelo de dominio genérico

Generaliza el vocabulario de NagoScale (`apartamento` → `recurso`) así:

| Concepto NagoScale | Concepto genérico | Notas |
|---|---|---|
| `users` (propietario) | `proveedores` / `users` | Quien administra uno o más recursos |
| `apartamentos` | `recursos` | El objeto reservable. Campos fijos: `nombre`, `descripcion`, `precio_*`, `capacidad`/`unidad_disponible` |
| `apartamento_fotos` | `recurso_fotos` | Multimedia del recurso |
| `reservas` | `reservas` | La reserva confirmada/bloqueante |
| `solicitudes` | `solicitudes` | Pedido de un cliente sin cuenta, pendiente de aprobación |
| `temporadas_precio` | `tarifas_especiales` | Precio distinto por rango de fechas |
| `checkin_huespedes` | genérico u omitible | Específico del caso apartamentos; no todo dominio lo necesita |
| `resenas` | `resenas` | Reseñas públicas cargadas manualmente por el proveedor |

**Decisión de diseño clave heredada:** los clientes finales **no tienen cuenta**. Reservan por formulario público y reciben un enlace de confirmación. Esto simplifica el sistema pero es exactamente la razón por la que el Bloque B (IDOR) existe — sin sesión de cliente, el único mecanismo de autorización posible para "ver mi propia solicitud" es un token, nunca el ID.

Si tu dominio SÍ requiere cuenta de cliente (ej. un marketplace de alquiler de vehículos con historial de reservas del cliente), adapta: la sesión del cliente autoriza su propia vista, y el token solo es necesario para el flujo de confirmación por enlace (ej. compartido por WhatsApp/email).

## 2. Stack base recomendado (heredado, probado)

- PHP 8+ con `declare(strict_types=1)` en todo archivo.
- MySQL/MariaDB InnoDB, PDO con `PDO::ATTR_EMULATE_PREPARES => false` (prepared statements reales, no emulados — cierra una clase entera de bypass de sanitización).
- Sin framework para MVP — es deliberado, reduce superficie de ataque y dependencias a mantener. Si el proyecto crece, migrar es más fácil que debuggear un framework mal configurado desde el inicio.
- Sesiones nativas de PHP con `session_set_cookie_params()` (`HttpOnly`, `SameSite=Lax`, `Secure` condicional a HTTPS) y `session_regenerate_id(true)` en cada login/registro exitoso.

No es obligatorio este stack — los 6 bloques de seguridad son el valor real de esta skill y se traducen a cualquier stack (Node/Postgres, Rails, Django, etc.). Lo que NO debe cambiar es la lógica, solo la sintaxis.

## 3. Esquema de base de datos (adaptar nombres, no la forma)

```sql
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  email VARCHAR(120) NULL,
  -- cualquier dato de contacto/pago que el proveedor quiera mostrar públicamente
  -- SIEMPRE con su propia columna de opt-in, nunca expuesto por defecto (ver Bloque C)
  mostrar_datos_contacto_publico TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE recursos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  -- resto de atributos específicos del dominio
  CONSTRAINT fk_recursos_user FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX idx_recursos_user (user_id)
);

CREATE TABLE reservas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recurso_id INT UNSIGNED NOT NULL,
  fecha_inicio DATETIME NOT NULL,   -- DATETIME si hay franjas horarias; DATE si son noches/días completos
  fecha_fin DATETIME NOT NULL,
  valor_total DECIMAL(12,2) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reservas_recurso FOREIGN KEY (recurso_id) REFERENCES recursos(id),
  INDEX idx_reservas_recurso (recurso_id),
  INDEX idx_reservas_fecha_inicio (fecha_inicio),
  INDEX idx_reservas_fecha_fin (fecha_fin)
);

CREATE TABLE solicitudes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recurso_id INT UNSIGNED NOT NULL,
  nombre_cliente VARCHAR(120) NOT NULL,
  telefono VARCHAR(50) NOT NULL,
  fecha_inicio DATETIME NOT NULL,
  fecha_fin DATETIME NOT NULL,
  estado ENUM('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
  -- Bloque B: obligatorio desde el día 1, nunca "lo añadimos después"
  token_confirmacion CHAR(64) NULL,
  token_expira_at DATETIME NULL,
  reserva_id INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_solicitudes_recurso FOREIGN KEY (recurso_id) REFERENCES recursos(id),
  UNIQUE KEY idx_solicitudes_token (token_confirmacion)
);

-- Bloque A: desde el día 1, no se añade tras el primer incidente de fuerza bruta.
CREATE TABLE login_intentos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  intentos SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  nivel_bloqueo TINYINT UNSIGNED NOT NULL DEFAULT 0,
  bloqueado_hasta DATETIME NULL,
  UNIQUE KEY idx_login_intentos_username (username)
);

CREATE TABLE login_intentos_ip (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  intentos SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  ventana_desde DATETIME NOT NULL,
  UNIQUE KEY idx_login_intentos_ip (ip)
);
```

## 4. Los 6 bloques de seguridad — incorporar desde el diseño

### Bloque A — Fuerza bruta de login

- Bloqueo progresivo por usuario: `[1, 5, 15]` minutos (escala solo cuando se consume un bloqueo completo, no por cada fallo).
- Límite independiente **por IP** (`REMOTE_ADDR` únicamente — **nunca confíes en `X-Forwarded-For`** salvo que haya un proxy inverso verificado y configurado explícitamente para pasarlo con autenticidad).
- Login exitoso limpia por completo el contador del usuario.
- Mensaje de error **idéntico** para "usuario no existe" y "contraseña incorrecta" — nunca reveles cuál de los dos falló.

### Bloque B — IDOR en enlaces públicos (el hallazgo más grave en NagoScale)

- Cualquier recurso que un usuario sin sesión necesite "recuperar" por un enlace (confirmación de solicitud, recibo, invitación) **se autoriza por posesión de un token, nunca por el ID**.
- Generación: `bin2hex(random_bytes(32))` (256 bits) o equivalente en tu stack.
- Validación: comparación con `hash_equals()` (resistente a timing attacks) **+** expiración obligatoria (ej. 7 días).
- Respuesta idéntica (mismo código HTTP, mismo mensaje) para "no existe", "token incorrecto" y "token expirado" — no reveles cuál de los tres ocurrió.
- Enlace generado: `confirmacion.php?id=N&t=<token>` — nunca `?id=N` solo.

### Bloque C — Exposición de datos sensibles del proveedor

- Cualquier dato de contacto/pago/personal del proveedor que se muestre en una vista pública pasa por **una única función whitelist** que proyecta explícitamente qué campos son públicos — nunca un `SELECT *` directo volcado a la vista.
- Cada campo sensible (cuentas bancarias, documentos, etc.) tiene su propio flag de opt-in explícito (`mostrar_X_publico`), por defecto en `0`/`false`.
- Si migras un sistema que YA exponía estos datos sin opt-in, el backfill de la migración debe preservar el comportamiento visible para quien ya dependía de ello (activar el flag en `1` para quienes ya tenían el dato cargado), no apagarlo de golpe.

### Bloque D — Doble reserva / race condition (el corazón de "reservar cualquier cosa")

Este es el patrón más reutilizable de todos — es literalmente la lógica central de cualquier plataforma de reservas:

```php
function crear_reserva_sin_solape(PDO $pdo, int $recursoId, string $inicio, string $fin, ?int $excluirReservaId, /* ...resto de datos... */): array
{
    $maxIntentos = 2; // 1 intento + 1 reintento ante deadlock (MySQL error 1213)
    for ($intento = 1; $intento <= $maxIntentos; $intento++) {
        try {
            $pdo->beginTransaction();

            // Bloquea la FILA DEL RECURSO (no toda la tabla): serializa solo
            // las operaciones sobre ESTE recurso. Dos recursos distintos
            // pueden reservarse en paralelo sin bloquearse entre sí.
            $stmt = $pdo->prepare('SELECT id FROM recursos WHERE id = ? FOR UPDATE');
            $stmt->execute([$recursoId]);
            if (!$stmt->fetch()) {
                $pdo->rollBack();
                return ['error' => 'Recurso no válido.'];
            }

            // Regla de solape: inicio < fin_existente AND fin > inicio_existente
            // (comparación estricta). Esto permite que el fin de una reserva
            // coincida exactamente con el inicio de la siguiente (checkout=checkin).
            $sql = 'SELECT COUNT(*) FROM reservas WHERE recurso_id = ? AND fecha_inicio < ? AND fecha_fin > ?';
            $params = [$recursoId, $fin, $inicio];
            if ($excluirReservaId) { $sql .= ' AND id != ?'; $params[] = $excluirReservaId; }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            if ((int) $stmt->fetchColumn() > 0) {
                $pdo->rollBack();
                return ['error' => 'Ese recurso ya tiene una reserva que se cruza con esas fechas/horas.'];
            }

            // ... INSERT/UPDATE de la reserva aquí, dentro de la misma transacción ...

            $pdo->commit();
            return ['reserva_id' => $reservaId /* ... */];
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $esDeadlock = ($e->errorInfo[1] ?? null) === 1213;
            if ($esDeadlock && $intento < $maxIntentos) { usleep(random_int(50000, 150000)); continue; }
            throw $e;
        }
    }
    return ['error' => 'No se pudo completar la operación, intenta de nuevo.'];
}
```

**No dupliques esta lógica en cada punto de entrada** (formulario directo del proveedor, aprobación de solicitud, API si la hay) — todos deben llamar a la misma función. Duplicarla fue exactamente el bug original en NagoScale.

**Prueba obligatoria antes de lanzar a producción:** disparar 15-20 peticiones concurrentes reales (no secuenciales) para el mismo recurso y las mismas fechas, y verificar que se crea exactamente 1 reserva, no 0 ni 2+. Repetir con recursos distintos y confirmar que SÍ pueden crearse en paralelo (si no pueden, el lock es demasiado amplio).

### Bloque E — Uploads (fotos/documentos del recurso)

- **Nunca** `move_uploaded_file()` directo. Decodificar la imagen con una librería real (GD, ImageMagick, sharp, Pillow — según stack) y volver a generarla desde los píxeles. Esto destruye cualquier payload incrustado tras los datos de imagen válidos (ataque "polyglot").
- Validar tamaño ANTES de intentar decodificar.
- Nombre de archivo generado siempre por el servidor (`bin2hex(random_bytes(16))` + extensión derivada del MIME real, nunca de la extensión que mandó el cliente).
- `.htaccess` (o equivalente Nginx/Caddy) en cada carpeta de uploads que bloquee ejecución de scripts — **incluso en carpetas que deben ser públicamente accesibles** (fotos de marketing), con una regla que solo deniegue extensiones ejecutables, no todo el acceso. Para carpetas que nunca deben ser accedidas directamente (documentos privados), usar `Require all denied`/equivalente sobre el directorio completo.

### Bloque F — Headers de seguridad

```apache
Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' <hosts-externos-reales-que-uses>; ..."
Header always set Strict-Transport-Security "max-age=300; includeSubDomains"
Header always set Permissions-Policy "geolocation=(), microphone=(), camera=(), payment=(), usb=()"
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set Referrer-Policy "strict-origin-when-cross-origin"
```

Construye la CSP a partir de un `grep` real de qué hosts externos usa tu app (mapas, fuentes, CDNs) — nunca copies una CSP genérica sin verificarla contra el código real. Sube el `max-age` de HSTS gradualmente solo después de confirmar que el sitio sirve siempre por HTTPS.

## 5. Multi-tenancy (si hay más de un proveedor)

Toda consulta sobre un recurso ajeno debe devolver 404, nunca 403 con detalle — no reveles si el recurso existe pero no es tuyo. Patrón:

```php
$stmt = $pdo->prepare('SELECT * FROM recursos WHERE id = ? AND user_id = ?');
$stmt->execute([$id, current_user_id()]);
if (!$stmt->fetch()) { http_response_code(404); die('No encontrado.'); }
```

Aplica este patrón a **todo** verbo (lectura, modificación, eliminación) y a todo recurso dependiente (reservas de ese recurso, fotos de ese recurso, etc. — el filtro debe propagarse vía JOIN, no asumirse).

## 6. Checklist de prueba antes de lanzar (condensado de la auditoría dinámica real)

- [ ] Login: bloqueo progresivo + límite por IP, probado con intentos reales.
- [ ] Enlace de confirmación: sin token → 404; token de otro registro → 404; token expirado → 404; token correcto → 200.
- [ ] Multi-tenant: usuario A contra recursos de usuario B — lectura, escritura y borrado, los tres bloqueados.
- [ ] Concurrencia: 15-20 peticiones simultáneas al mismo recurso/fechas → exactamente 1 reserva creada.
- [ ] Uploads: imagen válida aceptada y re-codificada; PHP/script disfrazado de imagen rechazado; polyglot real (imagen válida + payload anexado) aceptado como imagen pero con el payload destruido tras re-encoding.
- [ ] Headers: CSP/HSTS/Permissions-Policy presentes, sin romper ningún recurso externo real que use la app.
- [ ] Regresión end-to-end: flujo completo cliente (solicitud → token → confirmación) + flujo completo proveedor (crear recurso → aprobar solicitud → ver reserva) sin errores.

## Principio final

Esta skill no reemplaza el pensar el dominio específico del usuario (qué es un "recurso" en su caso, qué reglas de negocio tiene su disponibilidad). Reemplaza **reinventar desde cero** la parte que ya se rompió una vez en producción y costó una auditoría completa arreglar: fuerza bruta, IDOR, exposición de datos, doble reserva, uploads inseguros y headers faltantes. Si el usuario pide algo que no encaja en "reservar un recurso con disponibilidad limitada en el tiempo", esta skill no aplica — dilo explícitamente en vez de forzar el blueprint.

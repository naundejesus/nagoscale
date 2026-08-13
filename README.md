# Inmuebles por Días — NagoScale

Aplicación para gestionar reservas de apartamentos (Airbnb, Booking y publicidad
web propia): registra apartamentos, reservas con su fecha, plataforma de origen
y valor total, y calcula automáticamente el 75% que corresponde al propietario
y el 25% que corresponde a NagoScale. Permite filtrar por rango de fechas,
apartamento y plataforma, mostrando los totales del filtro aplicado.

Construida en **PHP + MySQL** puro (sin frameworks ni build steps) para
funcionar en hosting compartido con cPanel, como **Hostinger**.

## Despliegue en Hostinger (hosting compartido)

1. **Crear el subdominio.** En hPanel: *Dominios → Subdominios*, crea
   `inmueblespordias` sobre `nagoscale.com`. Anota la carpeta que te asigna
   (normalmente `public_html/inmueblespordias`).
2. **Crear la base de datos.** En hPanel: *Bases de datos → Bases de datos
   MySQL*. Crea una base de datos y un usuario, asígnale todos los permisos, y
   anota: host (usualmente `localhost`), nombre de la base, usuario y
   contraseña.
3. **Importar el esquema.** Abre *phpMyAdmin* desde hPanel, selecciona la base
   de datos creada y ejecuta el archivo [`db/schema.sql`](db/schema.sql)
   (pestaña "Importar" o pega su contenido en "SQL").
4. **Subir los archivos.** Sube todo el contenido de este repositorio a la
   carpeta del subdominio, usando el *Administrador de archivos* de hPanel o
   FTP. (Si tu plan de Hostinger tiene la función *Git* en hPanel, también
   puedes conectar este repositorio directamente y hacer *pull* desde ahí en
   lugar de subir por FTP.)
5. **Configurar credenciales.** Copia `config.sample.php` a `config.php` (en
   la misma carpeta) y edítalo con los datos reales de tu base de datos del
   paso 2.
6. **Subir el logo/banner.** Guarda tu imagen del banner como
   `assets/img/header-banner.png` (ese nombre exacto). Mientras no la subas,
   la app muestra un encabezado de respaldo con los colores de NagoScale.
7. **Crear tu usuario administrador.** Visita
   `https://inmueblespordias.nagoscale.com/setup.php` y crea tu usuario y
   contraseña. Esa página se desactiva sola en cuanto exista un usuario, así
   que solo funciona la primera vez.
8. **Activar HTTPS.** En hPanel: *Seguridad → SSL*, activa el certificado
   gratuito para el subdominio.
9. Entra en `https://inmueblespordias.nagoscale.com/login.php` con el usuario
   que creaste.

## Estructura

- `reservas.php` — listado de reservas con filtros (fechas, apartamento,
  plataforma) y totales.
- `reserva_form.php` — crear/editar una reserva (calcula 75%/25%
  automáticamente).
- `apartamentos.php` / `apartamento_form.php` — gestión de apartamentos.
- `login.php` / `setup.php` / `logout.php` — acceso con usuario y contraseña.
- `includes/` — conexión a BD, autenticación, CSRF y layout (bloqueado al
  acceso directo por HTTP vía `.htaccess`).
- `db/schema.sql` — esquema de la base de datos.
- `assets/` — CSS y el banner de marca.

## Notas de seguridad

- Las contraseñas se guardan con `password_hash` (bcrypt), nunca en texto
  plano.
- Todos los formularios usan tokens CSRF y las consultas SQL usan sentencias
  preparadas (PDO).
- `config.php` no se sube al repositorio (ver `.gitignore`); solo existe en
  el servidor con tus credenciales reales.

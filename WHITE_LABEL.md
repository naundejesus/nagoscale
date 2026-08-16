# Guía de instalación para clientes (white-label)

Esta plataforma está preparada para instalarse de forma independiente para
cada cliente que quiera gestionar sus propios alojamientos, con su propio
dominio, base de datos, logo, nombre y color de marca — sin ninguna mención
a NagoScale en su instalación.

Cada cliente nuevo es una **copia completa e independiente** del proyecto:
su propio hosting (o subdominio), su propia base de datos MySQL y su propio
`config.php`. No comparten datos entre sí.

## Pasos para instalar una copia nueva

1. **Crear el dominio o subdominio del cliente.** En hPanel (o el panel de
   hosting que uses): *Dominios → Subdominios* (o dominio propio si el
   cliente ya tiene uno), por ejemplo `reservas.negociodelcliente.com`.
2. **Crear la base de datos MySQL.** *Bases de datos → Bases de datos MySQL*.
   Crea una base y un usuario con todos los permisos. Anota host, nombre,
   usuario y contraseña.
3. **Importar el esquema.** Desde *phpMyAdmin*, selecciona la base de datos
   del cliente y ejecuta [`db/schema.sql`](db/schema.sql). No ejecutes los
   archivos `db/migration_*.sql` en una instalación nueva — esos son solo
   para actualizar instalaciones ya existentes.
4. **Subir los archivos.** Sube todo el contenido de este repositorio a la
   carpeta del sitio del cliente (Administrador de archivos o FTP).
5. **Configurar credenciales y marca.** Copia `config.sample.php` como
   `config.php` en esa misma instalación y complétalo con:
   - Los datos de la base de datos del cliente (paso 2).
   - `APP_URL` con el dominio del cliente.
   - Las 4 líneas de marca, descomentadas y con los datos del cliente:
     ```php
     define('BRAND_NAME', 'Nombre del negocio del cliente');
     define('BRAND_TAGLINE', 'Lo que quieran que diga debajo del nombre');
     define('BRAND_PRIMARY_COLOR', '#XXXXXX'); // color de acento del cliente
     define('BRAND_DARK_COLOR', '#XXXXXX');    // color oscuro de contraste
     ```
     Este `config.php` es el único archivo que cambia entre un cliente y
     otro — el resto del código es exactamente el mismo para todos.
6. **Subir el logo del cliente.** Reemplaza:
   - `assets/img/header-banner.png` — banner del panel administrativo.
   - `assets/img/favicon.png` — ícono de pestaña del navegador.
   Mientras no subas `header-banner.png`, se muestra un encabezado de
   respaldo con el nombre y colores configurados en `config.php`.
7. **Crear la primera cuenta.** Visita `https://<dominio-del-cliente>/register.php`
   y crea el usuario del cliente (o el tuyo, si tú vas a administrar por él).
8. **Activar HTTPS.** *Seguridad → SSL*, activa el certificado gratuito.

## Qué es exactamente lo que cambia por cliente

Solo estos archivos son distintos entre instalaciones:

- `config.php` (credenciales de BD + las 4 constantes `BRAND_*`) — **nunca**
  se sube al repositorio de Git.
- `assets/img/header-banner.png` y `assets/img/favicon.png` — el logo del
  cliente.
- La base de datos MySQL en sí (datos de sus apartamentos y reservas).

Todo el resto del código (PHP, CSS, JS) es idéntico para todos los clientes,
así que una mejora que hagas al producto se puede replicar a todos los
clientes subiendo los mismos archivos actualizados — sin tocar su marca ni
sus datos.

## Dónde aparece la marca del cliente

- Título de las pestañas del navegador, panel administrativo y páginas
  públicas de reserva.
- Encabezado (logo con inicial + nombre) tanto en el panel administrativo
  como en las páginas públicas de huéspedes.
- Pie de página.
- Colores de botones, enlaces y acentos en todo el sitio.
- Nombre del remitente en los correos de notificación de solicitudes.
- Columna/etiqueta de comisión en reportes y exportes ("25% [Nombre del
  cliente]" en vez de "25% NagoScale").

## Nota sobre el modelo de negocio (75/25)

El sistema calcula automáticamente 75% para el propietario del apartamento y
25% para quien administra la plataforma (el cliente, en su propia
instalación). Ese porcentaje está fijo en el código (`reserva_form.php` y
`solicitud_aprobar.php`); si algún cliente necesita un porcentaje distinto,
es un cambio de código aparte, no de configuración.

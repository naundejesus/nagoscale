<?php
// Copia este archivo como config.php y completa tus datos reales.
// config.php NO se sube a git (ver .gitignore) para no exponer credenciales.

// Datos de la base de datos MySQL (los obtienes en hPanel > Bases de datos > MySQL)
define('DB_HOST', 'localhost');
define('DB_NAME', 'u000000000_inmuebles');
define('DB_USER', 'u000000000_usuario');
define('DB_PASS', 'CAMBIA_ESTA_CONTRASENA');

// URL pública de esta aplicación
define('APP_URL', 'https://inmueblespordias.nagoscale.com');

// --- Marca (opcional) ---
// Estas 4 líneas son las únicas que hay que cambiar para instalar esta misma
// plataforma con la identidad de otro cliente (white-label). Si las quitas o
// las comentas, la app usa los valores de NagoScale por defecto.
// define('BRAND_NAME', 'Mi Marca');
// define('BRAND_TAGLINE', 'Inmuebles por Días');
// define('BRAND_PRIMARY_COLOR', '#0E57E1');
// define('BRAND_DARK_COLOR', '#131E41');
// Además, sube el logo del cliente reemplazando assets/img/header-banner.png
// y assets/img/favicon.png.

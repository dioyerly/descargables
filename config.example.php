<?php
// Copiá este archivo como config.php (en la misma carpeta) y completá los datos.
// config.php NO se sube a GitHub: queda solo en tu hosting.
return [
    // Base de datos MySQL (hPanel → Bases de datos)
    'db' => [
        'host' => 'localhost',            // si no conecta, probá 'srv801.hstgr.io'
        'name' => 'u748338755_desgargar', // nombre de la base (revisá que coincida con hPanel)
        'user' => 'u748338755_descargar', // usuario MySQL (revisá que coincida con hPanel)
        'pass' => 'PONER_AQUI',           // la contraseña que elegiste
    ],

    // Dirección pública del sitio, sin barra final
    'site_url' => 'https://descargar.estrategia.site',

    // Mercado Pago (Tus integraciones → tu aplicación → Credenciales de producción)
    'mp' => [
        'access_token'   => 'PONER_AQUI',  // Access Token (empieza con APP_USR-)
        'webhook_secret' => '',            // opcional: "Clave secreta" de Webhooks
    ],

    // Enlace de descarga que recibe el comprador
    'download' => [
        'days' => 7,   // días que dura el enlace
        'max'  => 5,   // cantidad máxima de descargas
    ],

    // Remitente del mail con el enlace (usá un correo de tu dominio)
    'mail_from' => 'no-responder@estrategia.site',
];

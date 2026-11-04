# Guía de instalación (Hostinger) — 6 pasos

## 1. Subir el código
**Opción simple (recomendada la primera vez):** Administrador de archivos → carpeta del subdominio `descargar.estrategia.site` → subir `estrategia-descargables-v2.zip` y **Extraer** (acepta reemplazar archivos). No toca tu `data/` ni tus fotos nuevas.

**Opción GitHub (auto-deploy):** hPanel → Avanzado → **Git** → repositorio `https://github.com/dioyerly/descargables.git`, rama `main`, carpeta del subdominio. Hostinger exige carpeta vacía: antes descargá un respaldo de `data/` y `uploads/`, vaciá la carpeta, desplegá y volvé a subir `data/products.json`, `data/auth.json` y tus fotos de `uploads/`. Luego activá el *Webhook de auto-deploy* y pegalo en GitHub → Settings → Webhooks.

## 2. Crear `config.php`
En el administrador de archivos: copiá `config.example.php` como `config.php` y editalo. Completá **solo vos**:
- `db.pass` → contraseña de MySQL. Revisá que `name` y `user` coincidan con hPanel → Bases de datos.
- `mp.access_token` → Mercado Pago → Tus integraciones → tu aplicación → Credenciales de producción.

## 3. Crear las tablas
Entrá a `https://descargar.estrategia.site/admin/` (si es la primera vez te pide crear contraseña) → **Base de datos** → botón *Crear tablas e importar mis productos*. Todo debe quedar con ✅.

## 4. Configurar el aviso de Mercado Pago
Tus integraciones → tu aplicación → **Webhooks** → URL de producción: `https://descargar.estrategia.site/mp-webhook.php` → evento **Pagos**. (Opcional: copiá la "clave secreta" en `mp.webhook_secret`.)

## 5. Cargar cada producto
Admin → Editar: precio, detalle completo, fotos, y el archivo que recibe el comprador (o un link). Con precio + archivo + Mercado Pago configurado, aparece el botón **Comprar**.

## 6. Probar
Comprá tu propio producto (podés ponerle $100 un rato), mirá **Pedidos** en el admin y verificá que te llegue el enlace. Comprobá también que `https://descargar.estrategia.site/private/files/` da error 403.

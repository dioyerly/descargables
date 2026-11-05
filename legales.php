<?php
declare(strict_types=1);
// Términos y condiciones, privacidad, devoluciones y botón de arrepentimiento
require __DIR__ . '/lib/bootstrap.php';
$b = biz(); $B = base_path();
$v = (string)@filemtime(__DIR__ . '/assets/js/site.js');
$days = (int)cfg('download.days', 7); $max = (int)cfg('download.max', 5);
$upd = '4 de noviembre de 2026';
$mail = '<a href="mailto:' . h($b['email']) . '">' . h($b['email']) . '</a>';
$wa = '<a href="' . h($b['wa']) . '" target="_blank" rel="noopener">WhatsApp ' . h($b['whatsapp']) . '</a>';
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Legales · <?= h($b['name']) ?></title>
<meta name="description" content="Términos y condiciones, política de privacidad, devoluciones y botón de arrepentimiento de <?= h($b['name']) ?>.">
<link rel="stylesheet" href="<?= h($B) ?>/assets/css/site.css?v=<?= $v ?>"><link rel="stylesheet" href="<?= h($B) ?>/assets/css/legales.css?v=<?= $v ?>"><?php require __DIR__ . "/lib/tiktok.php"; ?></head>
<body class="legal" data-page="legales" data-base="<?= h($B) ?>">
<header class="pad solid" id="hdr"><a class="logo" href="<?= h($B) ?>/"><i>e</i>EstrategIA<small>Productos digitales</small></a><nav class="nav"><a class="btn" href="<?= h($B) ?>/">Volver a la tienda</a></nav></header>
<main class="lg pad">
  <div class="lg-head"><div class="k" style="color:var(--acc)">Legales</div><h1>Reglas claras, <span class="serif">sin letra chica.</span></h1>
    <p>Última actualización: <?= $upd ?>. Este sitio es de <b><?= h($b['name']) ?></b> (<?= h($b['country']) ?>). Contacto: <?= $mail ?> · <?= $wa ?>.</p>
    <nav class="lg-toc"><a href="#terminos">Términos</a><a href="#privacidad">Privacidad</a><a href="#devoluciones">Devoluciones</a><a href="#arrepentimiento">Botón de arrepentimiento</a><a href="#contacto">Contacto</a></nav></div>

  <section id="terminos"><h2>1. Términos y condiciones</h2>
    <p><b>Quién vende.</b> Los productos y servicios de este sitio (<?= h($b['site']) ?>) los ofrece <?= h($b['name']) ?>, con domicilio en <?= h($b['country']) ?>. Al comprar o usar el sitio aceptás estas condiciones.</p>
    <p><b>Qué vendemos.</b> Productos digitales (agendas, planners e imprimibles en PDF o HTML) y servicios a medida (apps web, landings y micro apps). No se envía nada físico.</p>
    <p><b>Precios y pago.</b> Los precios están en pesos argentinos (ARS) e incluyen todo lo que se muestra. Algunos precios se fijan en dólares y se convierten a pesos con la cotización del día; el precio que ves al tocar “Comprar” es el que se cobra. El pago se procesa con Mercado Pago: nosotros no vemos ni guardamos los datos de tu tarjeta o cuenta.</p>
    <p><b>Entrega.</b> Cuando Mercado Pago aprueba el pago, ves el botón de descarga y te llega un enlace al email que indicaste. El enlace dura <?= $days ?> días y permite hasta <?= $max ?> descargas. Guardá el archivo: si se vence, escribinos y te lo renovamos.</p>
    <p><b>Uso permitido.</b> La compra te da una licencia personal: podés usar, imprimir y completar el producto todas las veces que quieras para vos. No está permitido revenderlo, compartirlo, subirlo a otros sitios ni presentarlo como propio. Todos los diseños, textos e imágenes son de <?= h($b['name']) ?>.</p>
    <p><b>Servicios a medida.</b> Los precios de apps web, landings y micro apps que se muestran son aproximados: el precio final, los plazos, lo que incluye y la forma de pago se acuerdan por escrito (mail o WhatsApp) antes de empezar, según cuánto se adapte a tu pedido.</p>
    <p><b>Requisitos.</b> Para usar los productos necesitás un lector de PDF o un navegador actualizado, según el formato indicado en cada producto.</p>
    <p><b>Responsabilidad.</b> Hacemos lo posible para que el sitio y los archivos funcionen bien. Si algo falla, lo resolvemos según la sección de devoluciones. No somos responsables por usos distintos a los descriptos.</p>
    <p><b>Cambios.</b> Podemos actualizar estas condiciones; rige la versión publicada al momento de tu compra. Se aplican las leyes de la República Argentina.</p>
  </section>

  <section id="privacidad"><h2>2. Política de privacidad</h2>
    <p><b>Qué datos pedimos.</b> Solo tu email al comprar, para mandarte el enlace de descarga y ayudarte si tenés un problema con tu compra. Si nos escribís por mail o WhatsApp, usamos esos datos solo para responderte.</p>
    <p><b>Pagos.</b> Los datos de pago los recibe y procesa Mercado Pago, con sus propias políticas. Nosotros solo recibimos la confirmación (aprobado o no) y el número de operación.</p>
    <p><b>Estadísticas anónimas.</b> Para saber qué productos interesan medimos visitas de forma anónima y con herramientas propias: un identificador al azar guardado en tu navegador, la página o sección que se vio, desde dónde llegaste (por ejemplo Instagram o Google) y si es celular o computadora. No guardamos tu dirección IP, no usamos cookies de publicidad y no compartimos estos datos con nadie.</p>
    <p><b>Con quién compartimos.</b> Con nadie, salvo Mercado Pago para cobrar y el proveedor de hosting donde está alojado el sitio. No vendemos datos.</p>
    <p><b>Cuánto tiempo.</b> Los datos de compras se guardan mientras haga falta para la entrega, la atención y las obligaciones legales; las estadísticas se borran a los 18 meses.</p>
    <p><b>Tus derechos.</b> Podés pedir ver, corregir o borrar tus datos escribiendo a <?= $mail ?>. Te respondemos dentro de los plazos de la Ley 25.326 de Protección de Datos Personales.</p>
    <p class="note">El titular de los datos personales tiene la facultad de ejercer el derecho de acceso a los mismos en forma gratuita a intervalos no inferiores a seis meses, salvo que se acredite un interés legítimo al efecto conforme lo establecido en el artículo 14, inciso 3 de la Ley Nº 25.326. La Agencia de Acceso a la Información Pública, en su carácter de Órgano de Control de la Ley Nº 25.326, tiene la atribución de atender las denuncias y reclamos que interpongan quienes resulten afectados en sus derechos por incumplimiento de las normas vigentes en materia de protección de datos personales.</p>
  </section>

  <section id="devoluciones"><h2>3. Cambios y devoluciones</h2>
    <p><b>No hay devoluciones en productos digitales.</b> Como el archivo se entrega al instante y queda en tu poder, una vez que el enlace de descarga está disponible no se hacen devoluciones ni cambios por arrepentimiento o porque ya no lo querés (artículo 1116 del Código Civil y Comercial: contenidos digitales entregados en soporte no material).</p>
    <p><b>Lo que sí resolvemos siempre.</b> Si el pago se aprobó y no recibiste el archivo, si el archivo está dañado o no abre, o si no corresponde a lo que se describe en la página, escribinos dentro de los 30 días: te lo reponemos o corregimos y, si no tiene solución, te devolvemos el dinero por el mismo medio de pago.</p>
    <p><b>Servicios a medida.</b> Las condiciones de cancelación y de pago se acuerdan por escrito en cada presupuesto antes de empezar.</p>
  </section>

  <section id="arrepentimiento"><h2>4. Botón de arrepentimiento</h2>
    <p>Por la Resolución 424/2020 de la Secretaría de Comercio Interior, podés pedir la revocación de una compra dentro de los 10 días corridos. En los productos digitales que ya se descargaron o cuyo enlace ya se entregó no corresponde la revocación (ver “Cambios y devoluciones”), pero igual podés hacer tu pedido y lo revisamos.</p>
    <p>Para pedirlo, mandá un mail a <?= $mail ?> con el asunto <b>“Arrepentimiento”</b>, tu email de compra y el producto. Te respondemos con un código de trámite dentro de las 24 horas.</p>
    <p class="row"><a class="btn" href="mailto:<?= h($b['email']) ?>?subject=<?= rawurlencode('Arrepentimiento de compra') ?>&body=<?= rawurlencode("Hola, quiero pedir la revocación de mi compra.\nEmail de compra:\nProducto:\nFecha de compra:\n") ?>">Pedir la revocación</a></p>
  </section>

  <section id="contacto"><h2>5. Contacto</h2>
    <p><b><?= h($b['name']) ?></b> · <?= h($b['country']) ?><br>Email: <?= $mail ?><br><?= $wa ?></p>
    <p class="note">Defensa del consumidor: si tenés un reclamo que no resolvimos, podés acudir a la autoridad de defensa del consumidor de tu jurisdicción o a <a href="https://www.argentina.gob.ar/produccion/defensadelconsumidor" target="_blank" rel="noopener">argentina.gob.ar/defensadelconsumidor</a>.</p>
  </section>
</main>
<?php include __DIR__ . '/lib/footer.php'; ?>
<script src="<?= h($B) ?>/assets/js/track.js?v=<?= $v ?>" defer></script>
</body></html>

<?php

if (!function_exists('add_action')) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

use franciscoblancojn\wordpress_utils\FWUCollapse;

$ErroresComunes = [
    "Aveonline no cotiza" => [
        "Verifica la configuración de envío" => [
            "Verifica que la cuenta, el agente y las configuraciones estén correctas <a target='_blank' href='" . admin_url('admin.php?page=wc-settings&tab=shipping&section=wc_aveonline_shipping') . "'>aquí</a>.",
            "En caso de que esté bien, prueba haciendo un cambio, revirtiéndolo y guardando; esto actualizará el token de sesión.",
            "Verifica que Aveonline esté correctamente agregado en la zona de envíos <a target='_blank' href='" . admin_url('admin.php?page=wc-settings&tab=shipping') . "'>aquí</a>."
        ],
        "Verifica el estado de cuenta" => [
            "Verifica que tu cuenta y tus pagos estén al día <a target='_blank' href='https://guias.aveonline.co/panel/inicio'>aquí</a>.<br/><img src='" . AVSHME_URL . "src/img/estado-de-cuenta.png' width='300'/>",
        ],
        "Verifica la construcción del checkout" => [
            "Verifica que el checkout esté construido con el estándar de WooCommerce usando <code>[woocommerce_checkout]</code>.",
        ],
    ],
    "No me funciona contraentrega" => [
        "Verifica el método de pago" => [
            "Verifica que el método de pago Contraentrega Aveonline esté activo <a target='_blank' href='" . admin_url('admin.php?page=wc-settings&tab=checkout') . "'>aquí</a>.",
        ],
    ]
]

?>

<h1 class="title">
    Soporte
</h1>
<div class="content-a">
    <h3 class="title">
        Errores más comunes:
    </h3>
    <?php
    foreach ($ErroresComunes as $error_comun => $causas) {
        FWUCollapse::render($error_comun, (function () use ($causas) {
            ob_start();
            echo '<table class="form-table">';
            foreach ($causas as $causa => $soluciones) {
                echo '<tr>';
                echo '<th>';
                echo $causa;
                echo '</th>';
                echo '<td>';
                echo '<ul style="list-style: disc;">';
                foreach ($soluciones as $k => $solucion) {
                    echo '<li>';
                    echo $solucion;
                    echo '</li>';
                }
                echo '</ul>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
            return ob_get_clean();
        })(), false);
    }
    ?>
</div>
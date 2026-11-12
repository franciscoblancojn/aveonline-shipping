<?php

function AVSHME_ds_pages()
{
    return apply_filters('AVSHME_ds_pages', array(
        'aveonline',
        AVSHME_MULTIAGENTE_PAGE,
        'soporte',
        'recogida_aveonline',
        'relacion_envio_aveonline',
    ));
}

function AVSHME_ds_is_plugin_screen()
{
    if (!is_admin()) {
        return false;
    }
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if (in_array($page, AVSHME_ds_pages(), true)) {
        return true;
    }
    if ($page !== 'wc-settings') {
        return false;
    }
    $section = isset($_GET['section']) ? sanitize_key(wp_unslash($_GET['section'])) : '';
    return in_array($section, array('wc_aveonline_shipping', AVSHME_PAYMENT_CONTRAENTREGA), true);
}

function AVSHME_ds_enqueue()
{
    wp_register_style('AVSHME_ds', AVSHME_URL . 'src/css/avshme-ds.css', array(), AVSHME_get_version());
    if (AVSHME_ds_is_plugin_screen()) {
        wp_enqueue_style('AVSHME_ds');
    }
}
add_action('admin_enqueue_scripts', 'AVSHME_ds_enqueue');

function AVSHME_ds_page($callback)
{
    return function () use ($callback) {
        echo '<div class="wrap avshme-ds-wrap"><hr class="wp-header-end"><div class="avshme-ds">';
        call_user_func($callback);
        echo '</div></div>';
    };
}

function AVSHME_ds_sections($html)
{
    $open = '<table class="form-table">';
    $start = strpos($html, $open);
    $end = strrpos($html, '</table>');
    if ($start === false || $end === false || $end < $start) {
        return $html;
    }
    $rows = substr($html, $start + strlen($open), $end - $start - strlen($open));
    $marker = '<tr class="tag_amazing">';
    $chunks = explode($marker, $rows);
    $out = '';
    $first = array_shift($chunks);
    if (strpos($first, '<tr') !== false) {
        $out .= '<div class="avshme-section"><table class="form-table">' . $first . '</table></div>';
    } else {
        $out .= $first;
    }
    foreach ($chunks as $chunk) {
        $out .= '<div class="avshme-section"><table class="form-table">' . $marker . $chunk . '</table></div>';
    }
    return substr($html, 0, $start) . $out . substr($html, $end + strlen('</table>'));
}

function AVSHME_ds_settings($callback)
{
    ob_start();
    call_user_func($callback);
    echo '<div class="avshme-ds">' . AVSHME_ds_sections(ob_get_clean()) . '</div>';
}

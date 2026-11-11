<?php

define("AVSHME_MULTIAGENTE_PAGE", 'aveonline_multiagente');
define("AVSHME_MULTIAGENTE_META", '_avshme_agente');
define("AVSHME_MULTIAGENTE_ORDER_META", '_avshme_agente');

function AVSHME_multiagente_help_html()
{
    ob_start();
?>
    <div class="avshme-multiagente-help">
        <p><strong>¿Cómo funciona la selección agéntica múltiple?</strong></p>
        <ol>
            <li>A cada producto se le puede asignar un <strong>Agente Personalizado</strong> (la bodega desde donde se despacha). Si no se asigna ninguno, el producto usa el <strong>agente por defecto</strong> elegido en la configuración de envío.</li>
            <li>Al cotizar y al crear el pedido se reúnen los agentes de todos los productos del carrito:
                <ul>
                    <li>Si todos los productos dan <strong>un solo agente</strong>, se usa ese.</li>
                    <li>Si dan <strong>varios agentes</strong> y uno de ellos es el agente por defecto, se usa el agente por defecto.</li>
                    <li>Si dan varios agentes y ninguno es el agente por defecto, se usa el <strong>primero</strong> de la lista (el del primer producto del carrito).</li>
                </ul>
            </li>
            <li>El agente elegido se guarda en el pedido al crearse. La guía y la recogida de ese pedido se generan con ese agente.</li>
            <li>Si la opción está desactivada, todos los pedidos usan el agente por defecto y los agentes asignados a los productos se ignoran (no se borran).</li>
        </ol>
    </div>
<?php
    return ob_get_clean();
}

function AVSHME_multiagente_enabled($settings = null)
{
    if ($settings === null) {
        $settings = get_option('woocommerce_wc_aveonline_shipping_settings', array());
    }
    return is_array($settings) && isset($settings['multiagente']) && $settings['multiagente'] === 'yes';
}

function AVSHME_multiagente_options($settings = null)
{
    if ($settings === null) {
        $settings = get_option('woocommerce_wc_aveonline_shipping_settings', array());
    }
    $options = array();
    if (empty($settings['select_cuenta'])) {
        return $options;
    }
    $api = new AveonlineAPI($settings);
    $r = $api->agentes();
    if (!$r || !isset($r->status) || $r->status != 'ok' || empty($r->agentes)) {
        return $options;
    }
    foreach ($r->agentes as $agente) {
        $options[$agente->id . "_" . $agente->idciudad] = $agente->nombre . " " . $agente->idciudad;
    }
    return $options;
}

function AVSHME_multiagente_resolve($product_ids, $settings)
{
    $default = isset($settings['select_agentes']) ? $settings['select_agentes'] : '';
    if (!AVSHME_multiagente_enabled($settings)) {
        return $default;
    }
    $options = AVSHME_multiagente_options($settings);
    $agentes = array();
    foreach ($product_ids as $product_id) {
        $agente = get_post_meta($product_id, AVSHME_MULTIAGENTE_META, true);
        if (empty($agente) || (!empty($options) && !isset($options[$agente]))) {
            $agente = $default;
        }
        if ($agente !== '' && !in_array($agente, $agentes, true)) {
            $agentes[] = $agente;
        }
    }
    if (count($agentes) == 0) {
        $result = $default;
    } elseif (count($agentes) == 1) {
        $result = $agentes[0];
    } elseif (in_array($default, $agentes, true)) {
        $result = $default;
    } else {
        $result = $agentes[0];
    }
    AVSHME_addLogAveonline(array(
        "type"      => "MULTIAGENTE_RESOLVE",
        "products"  => $product_ids,
        "agentes"   => $agentes,
        "default"   => $default,
        "result"    => $result,
    ));
    return $result;
}

function AVSHME_multiagente_order_products($order)
{
    $product_ids = array();
    foreach ($order->get_items() as $item) {
        $product_ids[] = $item->get_product_id();
    }
    return $product_ids;
}

function AVSHME_multiagente_order_request_agente($order)
{
    foreach ($order->get_items('shipping') as $item) {
        $request = $item->get_meta('request', true);
        if (!$request) {
            continue;
        }
        $request = json_decode(base64_decode($request), true);
        if (is_array($request) && !empty($request['agente'])) {
            return $request['agente'];
        }
    }
    return '';
}

function AVSHME_multiagente_get_order_agente($order, $settings)
{
    $agente = $order->get_meta(AVSHME_MULTIAGENTE_ORDER_META, true);
    if (!empty($agente)) {
        return $agente;
    }
    $agente = AVSHME_multiagente_order_request_agente($order);
    if (!empty($agente)) {
        return $agente;
    }
    return AVSHME_multiagente_resolve(AVSHME_multiagente_order_products($order), $settings);
}

function AVSHME_multiagente_save_order_agente($order)
{
    $settings = get_option('woocommerce_wc_aveonline_shipping_settings', array());
    $agente = AVSHME_multiagente_order_request_agente($order);
    if (empty($agente)) {
        $agente = AVSHME_multiagente_resolve(AVSHME_multiagente_order_products($order), $settings);
    }
    if (!empty($agente)) {
        $order->update_meta_data(AVSHME_MULTIAGENTE_ORDER_META, $agente);
    }
}

function AVSHME_multiagente_checkout_create_order($order, $data)
{
    AVSHME_multiagente_save_order_agente($order);
}
add_action('woocommerce_checkout_create_order', 'AVSHME_multiagente_checkout_create_order', 20, 2);

function AVSHME_multiagente_store_api_order($order)
{
    AVSHME_multiagente_save_order_agente($order);
    $order->save();
}
add_action('woocommerce_store_api_checkout_update_order_from_request', 'AVSHME_multiagente_store_api_order', 20, 1);

function AVSHME_multiagente_label($agente, $options = null)
{
    if ($options === null) {
        $options = AVSHME_multiagente_options();
    }
    return isset($options[$agente]) ? $options[$agente] : $agente;
}

function AVSHME_multiagente_menu()
{
    add_submenu_page('aveonline', 'Configurar Multiagentes', 'Multiagentes', 'administrator', AVSHME_MULTIAGENTE_PAGE, 'AVSHME_multiagente_page');
}
add_action('admin_menu', 'AVSHME_multiagente_menu', 20);

function AVSHME_multiagente_save()
{
    if (!isset($_POST['avshme_multiagente_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['avshme_multiagente_nonce'])), 'avshme_multiagente_save')) {
        return false;
    }
    if (!current_user_can('administrator')) {
        return false;
    }
    $options = AVSHME_multiagente_options();
    $agentes = isset($_POST['avshme_agente']) && is_array($_POST['avshme_agente']) ? wp_unslash($_POST['avshme_agente']) : array();
    foreach ($agentes as $product_id => $agente) {
        $product_id = absint($product_id);
        $agente = sanitize_text_field($agente);
        if (!$product_id || get_post_type($product_id) !== 'product') {
            continue;
        }
        if ($agente === '' || !isset($options[$agente])) {
            delete_post_meta($product_id, AVSHME_MULTIAGENTE_META);
        } else {
            update_post_meta($product_id, AVSHME_MULTIAGENTE_META, $agente);
        }
    }
    return true;
}

function AVSHME_multiagente_page()
{
    if (!current_user_can('administrator')) {
        return;
    }
    $saved = isset($_POST['avshme_multiagente_submit']) ? AVSHME_multiagente_save() : null;

    $settings = get_option('woocommerce_wc_aveonline_shipping_settings', array());
    $enabled = AVSHME_multiagente_enabled($settings);
    $options = AVSHME_multiagente_options($settings);
    $default = isset($settings['select_agentes']) ? $settings['select_agentes'] : '';
    $default_label = $default !== '' ? AVSHME_multiagente_label($default, $options) : 'Sin agente por defecto';
    $settings_url = admin_url('admin.php?page=wc-settings&tab=shipping&section=wc_aveonline_shipping');

    $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
    $query = new WP_Query(array(
        'post_type'      => 'product',
        'post_status'    => array('publish', 'draft', 'pending', 'private', 'future'),
        'posts_per_page' => 50,
        'paged'          => $paged,
        's'              => $search,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'fields'         => 'ids',
    ));
    $page_url = admin_url('admin.php?page=' . AVSHME_MULTIAGENTE_PAGE);
?>
    <div class="wrap avshme-multiagente">
        <h1 class="wp-heading-inline">Configurar Multiagentes</h1>
        <hr class="wp-header-end">

        <?php if ($saved === true) : ?>
            <div class="notice notice-success is-dismissible">
                <p>Agentes guardados.</p>
            </div>
        <?php elseif ($saved === false) : ?>
            <div class="notice notice-error is-dismissible">
                <p>No se pudieron guardar los cambios. Recarga la página e intenta de nuevo.</p>
            </div>
        <?php endif; ?>

        <?php if (!$enabled) : ?>
            <div class="notice notice-warning">
                <p>
                    La <strong>selección agéntica múltiple</strong> está desactivada: los agentes asignados aquí se guardan pero no se usan hasta activarla en
                    <a href="<?= esc_url($settings_url) ?>">la configuración de envío de Aveonline</a>.
                </p>
            </div>
        <?php endif; ?>

        <?php if (empty($options)) : ?>
            <div class="notice notice-error">
                <p>
                    No se pudieron cargar los agentes de Aveonline. Revisa el usuario, la contraseña y la cuenta en
                    <a href="<?= esc_url($settings_url) ?>">la configuración de envío</a>.
                </p>
            </div>
        <?php endif; ?>

        <div class="card avshme-multiagente-card">
            <p>
                <strong>Agente por defecto:</strong> <?= esc_html($default_label) ?>
                (<a href="<?= esc_url($settings_url) ?>">cambiar</a>)
            </p>
            <?= AVSHME_multiagente_help_html() ?>
        </div>

        <form method="get" class="search-form avshme-multiagente-search">
            <input type="hidden" name="page" value="<?= esc_attr(AVSHME_MULTIAGENTE_PAGE) ?>">
            <p class="search-box">
                <label class="screen-reader-text" for="avshme-multiagente-search">Buscar productos</label>
                <input type="search" id="avshme-multiagente-search" name="s" value="<?= esc_attr($search) ?>">
                <input type="submit" class="button" value="Buscar productos">
            </p>
        </form>

        <form method="post">
            <?php wp_nonce_field('avshme_multiagente_save', 'avshme_multiagente_nonce'); ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:60px;">Imagen</th>
                        <th>Producto</th>
                        <th>SKU</th>
                        <th>Tipo</th>
                        <th>Precio</th>
                        <th>Inventario</th>
                        <th>Peso / Medidas</th>
                        <th style="width:25%;">Agente Personalizado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($query->posts)) : ?>
                        <tr>
                            <td colspan="8">No se encontraron productos.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($query->posts as $product_id) :
                        $product = wc_get_product($product_id);
                        if (!$product) {
                            continue;
                        }
                        $agente = get_post_meta($product_id, AVSHME_MULTIAGENTE_META, true);
                        $dimensions = wc_format_dimensions($product->get_dimensions(false));
                        $weight = $product->get_weight();
                    ?>
                        <tr>
                            <td><?= $product->get_image(array(40, 40)) ?></td>
                            <td>
                                <strong>
                                    <a href="<?= esc_url(get_edit_post_link($product_id)) ?>" target="_blank"><?= esc_html($product->get_name()) ?></a>
                                </strong>
                                <div class="row-actions visible">ID: <?= esc_html($product_id) ?> · <?= esc_html(get_post_status_object(get_post_status($product_id))->label) ?></div>
                            </td>
                            <td><?= esc_html($product->get_sku() ?: '–') ?></td>
                            <td><?= esc_html(wc_get_product_types()[$product->get_type()] ?? $product->get_type()) ?></td>
                            <td><?= $product->get_price_html() ?: '–' ?></td>
                            <td><?= wp_kses_post(wc_get_stock_html($product) ?: esc_html($product->get_stock_status())) ?></td>
                            <td><?= esc_html(($weight !== '' ? wc_format_weight($weight) : '–') . ' / ' . $dimensions) ?></td>
                            <td>
                                <select name="avshme_agente[<?= esc_attr($product_id) ?>]" style="width:100%;">
                                    <option value="">Por defecto (<?= esc_html($default_label) ?>)</option>
                                    <?php foreach ($options as $key => $label) : ?>
                                        <option value="<?= esc_attr($key) ?>" <?php selected($agente, $key); ?>><?= esc_html($label) ?></option>
                                    <?php endforeach; ?>
                                    <?php if ($agente !== '' && !isset($options[$agente])) : ?>
                                        <option value="<?= esc_attr($agente) ?>" selected>No disponible: <?= esc_html($agente) ?> (se usa el por defecto)</option>
                                    <?php endif; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="tablenav bottom">
                <div class="alignleft actions">
                    <input type="submit" name="avshme_multiagente_submit" class="button button-primary" value="Guardar cambios" <?php disabled(empty($options)); ?>>
                </div>
                <div class="tablenav-pages">
                    <span class="displaying-num"><?= esc_html($query->found_posts) ?> productos</span>
                    <?php
                    echo paginate_links(array(
                        'base'      => add_query_arg('paged', '%#%', $search !== '' ? add_query_arg('s', rawurlencode($search), $page_url) : $page_url),
                        'format'    => '',
                        'current'   => $paged,
                        'total'     => max(1, $query->max_num_pages),
                    ));
                    ?>
                </div>
            </div>
            <p class="description">Solo se guardan los productos de la página actual. Guarda antes de cambiar de página.</p>
        </form>
    </div>
    <style>
        .avshme-multiagente-card {
            max-width: none;
        }

        .avshme-multiagente-help ol,
        .avshme-multiagente-help ul {
            margin-left: 20px;
        }

        .avshme-multiagente-help ul {
            list-style: disc;
        }

        .avshme-multiagente-search {
            margin: 10px 0;
            overflow: hidden;
        }
    </style>
<?php
}

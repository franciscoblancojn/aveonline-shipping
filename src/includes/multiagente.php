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
            <li>Se puede asignar un <strong>Agente Personalizado</strong> (la bodega desde donde se despacha) a un producto, a una marca o a una etiqueta. El agente de cada producto se obtiene en este orden:
                <ol>
                    <li>El agente asignado al <strong>producto</strong>.</li>
                    <li>Si no tiene, el agente asignado a su <strong>marca</strong>.</li>
                    <li>Si no tiene, el agente asignado a su <strong>etiqueta</strong>.</li>
                    <li>Si no tiene, el <strong>agente por defecto</strong> elegido en la configuración de envío.</li>
                </ol>
                Si el producto tiene varias marcas o etiquetas con agente, se usa la primera en orden alfabético.
            </li>
            <li>Al cotizar y al crear el pedido se reúnen los agentes de todos los productos del carrito:
                <ul>
                    <li>Si todos los productos dan <strong>un solo agente</strong>, se usa ese.</li>
                    <li>Si dan <strong>varios agentes</strong> y uno de ellos es el agente por defecto, se usa el agente por defecto.</li>
                    <li>Si dan varios agentes y ninguno es el agente por defecto, se usa el <strong>primero</strong> de la lista (el del primer producto del carrito).</li>
                </ul>
            </li>
            <li>El agente elegido se guarda en el pedido al crearse. La guía y la recogida de ese pedido se generan con ese agente.</li>
            <li>Si la opción está desactivada, todos los pedidos usan el agente por defecto y los agentes asignados a productos, marcas y etiquetas se ignoran (no se borran).</li>
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

function AVSHME_multiagente_brand_taxonomy()
{
    $taxonomies = apply_filters('AVSHME_multiagente_brand_taxonomies', array('product_brand', 'pwb-brand', 'yith_product_brand', 'berocket_brand'));
    foreach ($taxonomies as $taxonomy) {
        if (taxonomy_exists($taxonomy)) {
            return $taxonomy;
        }
    }
    return '';
}

function AVSHME_multiagente_tabs()
{
    return array(
        'producto' => array('label' => 'Por Producto', 'taxonomy' => ''),
        'marca'    => array('label' => 'Por Marca', 'taxonomy' => AVSHME_multiagente_brand_taxonomy()),
        'etiqueta' => array('label' => 'Por Etiqueta', 'taxonomy' => 'product_tag'),
    );
}

function AVSHME_multiagente_valid($agente, $options)
{
    return $agente !== '' && (empty($options) || isset($options[$agente]));
}

function AVSHME_multiagente_term_source($product_id, $taxonomy, $options)
{
    if ($taxonomy === '' || !taxonomy_exists($taxonomy)) {
        return null;
    }
    $terms = wp_get_post_terms($product_id, $taxonomy, array('orderby' => 'name', 'order' => 'ASC'));
    if (is_wp_error($terms)) {
        return null;
    }
    foreach ($terms as $term) {
        $agente = (string) get_term_meta($term->term_id, AVSHME_MULTIAGENTE_META, true);
        if (AVSHME_multiagente_valid($agente, $options)) {
            return array('agente' => $agente, 'term' => $term->name);
        }
    }
    return null;
}

function AVSHME_multiagente_product_source($product_id, $options, $include_product = true)
{
    if ($include_product) {
        $agente = (string) get_post_meta($product_id, AVSHME_MULTIAGENTE_META, true);
        if (AVSHME_multiagente_valid($agente, $options)) {
            return array('agente' => $agente, 'source' => 'producto', 'term' => '');
        }
    }
    $tabs = AVSHME_multiagente_tabs();
    foreach (array('marca', 'etiqueta') as $source) {
        $found = AVSHME_multiagente_term_source($product_id, $tabs[$source]['taxonomy'], $options);
        if ($found) {
            return array('agente' => $found['agente'], 'source' => $source, 'term' => $found['term']);
        }
    }
    return array('agente' => '', 'source' => '', 'term' => '');
}

function AVSHME_multiagente_resolve($product_ids, $settings)
{
    $default = isset($settings['select_agentes']) ? $settings['select_agentes'] : '';
    if (!AVSHME_multiagente_enabled($settings)) {
        AVSHME_addLogAveonline(array(
            "type"      => "MULTIAGENTE_RESOLVE",
            "enabled"   => false,
            "products"  => $product_ids,
            "default"   => $default,
            "result"    => $default,
        ));
        return $default;
    }
    $options = AVSHME_multiagente_options($settings);
    $agentes = array();
    $sources = array();
    foreach ($product_ids as $product_id) {
        $found = AVSHME_multiagente_product_source($product_id, $options);
        $agente = $found['agente'] !== '' ? $found['agente'] : $default;
        $sources[$product_id] = $found['source'] !== '' ? $found['source'] : 'defecto';
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
        "enabled"   => true,
        "products"  => $product_ids,
        "sources"   => $sources,
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
    add_submenu_page('aveonline', 'Configurar Multiagentes', 'Multiagentes', 'administrator', AVSHME_MULTIAGENTE_PAGE, AVSHME_ds_page('AVSHME_multiagente_page'));
}
add_action('admin_menu', 'AVSHME_multiagente_menu', 20);

function AVSHME_multiagente_save($tab, $taxonomy)
{
    if (!isset($_POST['avshme_multiagente_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['avshme_multiagente_nonce'])), 'avshme_multiagente_save')) {
        return false;
    }
    if (!current_user_can('administrator')) {
        return false;
    }
    $options = AVSHME_multiagente_options();
    $agentes = isset($_POST['avshme_agente']) && is_array($_POST['avshme_agente']) ? wp_unslash($_POST['avshme_agente']) : array();
    foreach ($agentes as $id => $agente) {
        $id = absint($id);
        $agente = sanitize_text_field($agente);
        $valid = $agente !== '' && isset($options[$agente]);
        if ($tab === 'producto') {
            if (!$id || get_post_type($id) !== 'product') {
                continue;
            }
            if ($valid) {
                update_post_meta($id, AVSHME_MULTIAGENTE_META, $agente);
            } else {
                delete_post_meta($id, AVSHME_MULTIAGENTE_META);
            }
            continue;
        }
        $term = $id && $taxonomy !== '' ? get_term($id, $taxonomy) : null;
        if (!$term || is_wp_error($term)) {
            continue;
        }
        if ($valid) {
            update_term_meta($id, AVSHME_MULTIAGENTE_META, $agente);
        } else {
            delete_term_meta($id, AVSHME_MULTIAGENTE_META);
        }
    }
    if (class_exists('WC_Cache_Helper')) {
        WC_Cache_Helper::get_transient_version('shipping', true);
    }
    return true;
}

function AVSHME_multiagente_select($id, $agente, $options, $empty_label)
{
    ob_start();
?>
    <select name="avshme_agente[<?= esc_attr($id) ?>]" style="width:100%;">
        <option value=""><?= esc_html($empty_label) ?></option>
        <?php foreach ($options as $key => $label) : ?>
            <option value="<?= esc_attr($key) ?>" <?php selected($agente, $key); ?>><?= esc_html($label) ?></option>
        <?php endforeach; ?>
        <?php if ($agente !== '' && !isset($options[$agente])) : ?>
            <option value="<?= esc_attr($agente) ?>" selected>No disponible: <?= esc_html($agente) ?> (se ignora)</option>
        <?php endif; ?>
    </select>
<?php
    return ob_get_clean();
}

function AVSHME_multiagente_page()
{
    if (!current_user_can('administrator')) {
        return;
    }
    $tabs = AVSHME_multiagente_tabs();
    $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'producto';
    if (!isset($tabs[$tab])) {
        $tab = 'producto';
    }
    $taxonomy = $tabs[$tab]['taxonomy'];
    $saved = isset($_POST['avshme_multiagente_submit']) ? AVSHME_multiagente_save($tab, $taxonomy) : null;

    $settings = get_option('woocommerce_wc_aveonline_shipping_settings', array());
    $enabled = AVSHME_multiagente_enabled($settings);
    $options = AVSHME_multiagente_options($settings);
    $default = isset($settings['select_agentes']) ? $settings['select_agentes'] : '';
    $default_label = $default !== '' ? AVSHME_multiagente_label($default, $options) : 'Sin agente por defecto';
    $settings_url = admin_url('admin.php?page=wc-settings&tab=shipping&section=wc_aveonline_shipping');
    $source_labels = array('marca' => 'marca', 'etiqueta' => 'etiqueta');

    $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
    $per_page = 50;
    $page_url = add_query_arg('tab', $tab, admin_url('admin.php?page=' . AVSHME_MULTIAGENTE_PAGE));

    $items = array();
    $total = 0;
    $pages = 1;
    if ($tab === 'producto') {
        $query = new WP_Query(array(
            'post_type'      => 'product',
            'post_status'    => array('publish', 'draft', 'pending', 'private', 'future'),
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            's'              => $search,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'fields'         => 'ids',
        ));
        $items = $query->posts;
        $total = $query->found_posts;
        $pages = max(1, $query->max_num_pages);
    } elseif ($taxonomy !== '') {
        $term_args = array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'search'     => $search,
        );
        $total = (int) wp_count_terms($term_args);
        $pages = max(1, (int) ceil($total / $per_page));
        $items = get_terms($term_args + array(
            'orderby' => 'name',
            'order'   => 'ASC',
            'number'  => $per_page,
            'offset'  => ($paged - 1) * $per_page,
        ));
        if (is_wp_error($items)) {
            $items = array();
        }
    }
?>
    <div class="avshme-multiagente">
        <h1 class="wp-heading-inline">Configurar Multiagentes</h1>

        <?php if ($saved === true) : ?>
            <div class="notice notice-success inline">
                <p>Agentes guardados.</p>
            </div>
        <?php elseif ($saved === false) : ?>
            <div class="notice notice-error inline">
                <p>No se pudieron guardar los cambios. Recarga la página e intenta de nuevo.</p>
            </div>
        <?php endif; ?>

        <?php if (!$enabled) : ?>
            <div class="notice notice-warning inline">
                <p>
                    La <strong>selección agéntica múltiple</strong> está desactivada: los agentes asignados aquí se guardan pero no se usan hasta activarla en
                    <a href="<?= esc_url($settings_url) ?>">la configuración de envío de Aveonline</a>.
                </p>
            </div>
        <?php endif; ?>

        <?php if (empty($options)) : ?>
            <div class="notice notice-error inline">
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

        <nav class="nav-tab-wrapper avshme-multiagente-tabs">
            <?php foreach ($tabs as $key => $data) : ?>
                <a href="<?= esc_url(add_query_arg('tab', $key, admin_url('admin.php?page=' . AVSHME_MULTIAGENTE_PAGE))) ?>" class="nav-tab <?= $key === $tab ? 'nav-tab-active' : '' ?>"><?= esc_html($data['label']) ?></a>
            <?php endforeach; ?>
        </nav>

        <?php if ($tab === 'marca' && $taxonomy === '') : ?>
            <div class="notice notice-info inline">
                <p>No se encontró una taxonomía de marcas. Activa <strong>Marcas</strong> en WooCommerce (Productos → Marcas) o un plugin de marcas compatible para asignar agentes por marca.</p>
            </div>
        <?php else : ?>
            <p class="description">
                <?php if ($tab === 'producto') : ?>
                    El agente del producto tiene prioridad sobre el de su marca y su etiqueta. Si se deja sin asignar, se muestra lo que hereda.
                <?php elseif ($tab === 'marca') : ?>
                    Se aplica a los productos de la marca que no tengan un agente propio. Tiene prioridad sobre el agente de la etiqueta.
                <?php else : ?>
                    Se aplica a los productos con la etiqueta que no tengan agente propio ni agente por marca.
                <?php endif; ?>
            </p>

            <form method="get" class="search-form avshme-multiagente-search">
                <input type="hidden" name="page" value="<?= esc_attr(AVSHME_MULTIAGENTE_PAGE) ?>">
                <input type="hidden" name="tab" value="<?= esc_attr($tab) ?>">
                <p class="search-box">
                    <label class="screen-reader-text" for="avshme-multiagente-search">Buscar</label>
                    <input type="search" id="avshme-multiagente-search" name="s" value="<?= esc_attr($search) ?>">
                    <input type="submit" class="button" value="<?= $tab === 'producto' ? 'Buscar productos' : ($tab === 'marca' ? 'Buscar marcas' : 'Buscar etiquetas') ?>">
                </p>
            </form>

            <form method="post">
                <?php wp_nonce_field('avshme_multiagente_save', 'avshme_multiagente_nonce'); ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <?php if ($tab === 'producto') : ?>
                                <th style="width:60px;">Imagen</th>
                                <th>Producto</th>
                                <th>SKU</th>
                                <th>Tipo</th>
                                <th>Precio</th>
                                <th>Inventario</th>
                                <th>Peso / Medidas</th>
                            <?php else : ?>
                                <th><?= $tab === 'marca' ? 'Marca' : 'Etiqueta' ?></th>
                                <th>Slug</th>
                                <th>Productos</th>
                            <?php endif; ?>
                            <th style="width:25%;">Agente Personalizado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)) : ?>
                            <tr>
                                <td colspan="<?= $tab === 'producto' ? 8 : 4 ?>">
                                    <?= $tab === 'producto' ? 'No se encontraron productos.' : ($tab === 'marca' ? 'No se encontraron marcas.' : 'No se encontraron etiquetas.') ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($tab === 'producto') : ?>
                            <?php foreach ($items as $product_id) :
                                $product = wc_get_product($product_id);
                                if (!$product) {
                                    continue;
                                }
                                $agente = (string) get_post_meta($product_id, AVSHME_MULTIAGENTE_META, true);
                                $inherited = AVSHME_multiagente_product_source($product_id, $options, false);
                                $empty_label = $inherited['agente'] !== ''
                                    ? 'Heredado de ' . $source_labels[$inherited['source']] . ' "' . $inherited['term'] . '" (' . AVSHME_multiagente_label($inherited['agente'], $options) . ')'
                                    : 'Por defecto (' . $default_label . ')';
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
                                    <td><?= AVSHME_multiagente_select($product_id, $agente, $options, $empty_label) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <?php foreach ($items as $term) :
                                $agente = (string) get_term_meta($term->term_id, AVSHME_MULTIAGENTE_META, true);
                            ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <a href="<?= esc_url(get_edit_term_link($term->term_id, $taxonomy, 'product')) ?>" target="_blank"><?= esc_html($term->name) ?></a>
                                        </strong>
                                        <div class="row-actions visible">ID: <?= esc_html($term->term_id) ?></div>
                                    </td>
                                    <td><?= esc_html($term->slug) ?></td>
                                    <td>
                                        <a href="<?= esc_url(admin_url('edit.php?post_type=product&' . $taxonomy . '=' . $term->slug)) ?>" target="_blank"><?= esc_html($term->count) ?></a>
                                    </td>
                                    <td><?= AVSHME_multiagente_select($term->term_id, $agente, $options, 'Sin asignar') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="tablenav bottom">
                    <div class="alignleft actions">
                        <input type="submit" name="avshme_multiagente_submit" class="button button-primary" value="Guardar cambios" <?php disabled(empty($options)); ?>>
                    </div>
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?= esc_html($total) ?> <?= $tab === 'producto' ? 'productos' : ($tab === 'marca' ? 'marcas' : 'etiquetas') ?></span>
                        <?php
                        echo paginate_links(array(
                            'base'      => add_query_arg('paged', '%#%', $search !== '' ? add_query_arg('s', rawurlencode($search), $page_url) : $page_url),
                            'format'    => '',
                            'current'   => $paged,
                            'total'     => $pages,
                        ));
                        ?>
                    </div>
                </div>
                <p class="description">Solo se guardan los elementos de la página actual. Guarda antes de cambiar de página.</p>
            </form>
        <?php endif; ?>
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

        .avshme-multiagente-help ol ol {
            list-style: lower-alpha;
            margin-top: 4px;
        }

        .avshme-multiagente-tabs {
            margin-top: 16px;
        }

        .avshme-multiagente-search {
            margin: 10px 0;
            overflow: hidden;
        }
    </style>
<?php
}

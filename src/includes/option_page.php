<?php
function Aveonline_create_menu()
{
    add_menu_page('Aveonline Settings', 'Aveonline', 'administrator', 'aveonline', AVSHME_ds_page('Aveonline_settings_page'), plugins_url('../img/aveonline.png', __FILE__));
    add_submenu_page('aveonline', 'Aveonline', 'Inicio', 'administrator', 'aveonline');
    add_submenu_page('aveonline', 'Recogida Aveonline', 'Recogida', 'manage_options', 'recogida_aveonline', AVSHME_ds_page('AVSHME_recogida_aveonline_page'));
    add_submenu_page('aveonline', 'Relacion de Envio Aveonline', 'Relacion de Envio', 'manage_options', 'relacion_envio_aveonline', AVSHME_ds_page('AVSHME_relacion_envio_aveonline_page'));
    add_submenu_page('aveonline', 'Soporte', 'Soporte', 'administrator', 'soporte', AVSHME_ds_page('AVSHME_soporte_page'));
    add_action('admin_init', 'register_Aveonline_settings');
}
add_action('admin_menu', 'Aveonline_create_menu');

function AVSHME_redirect_old_pages()
{
    global $pagenow;
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if ($pagenow !== 'options-general.php' || !in_array($page, array('recogida_aveonline', 'relacion_envio_aveonline'), true)) {
        return;
    }
    wp_safe_redirect(add_query_arg(wp_unslash($_GET), admin_url('admin.php')));
    exit;
}
add_action('admin_init', 'AVSHME_redirect_old_pages', 1);

function AVSHME_soporte_plugin_action_links($links)
{
    $soporte = '<a class="github_updater_plugin_wordpress_btn" style="color: #0073aa; font-weight: bold;" href="' . esc_url(get_admin_url() . 'admin.php?page=soporte') . '">Soporte</a>';
    array_push($links, $soporte);
    return $links;
}
add_filter('plugin_action_links_' . AVSHME_BASENAME, 'AVSHME_soporte_plugin_action_links', 20);

function register_Aveonline_settings()
{
    //register our settings
    register_setting('Aveonline-settings-group', 'new_option_name');
    register_setting('Aveonline-settings-group', 'some_other_option');
    register_setting('Aveonline-settings-group', 'option_etc');
}

function Aveonline_settings_page()
{
    $items = array(
        array('Ingresar a Aveonline', 'Abre el panel de Aveonline para gestionar guías, pagos y tu cuenta.', 'https://guias.aveonline.co/ingresar', true),
        array('Configuración de envío', 'Cuenta, agentes, remitente y opciones de cotización.', admin_url('admin.php?page=wc-settings&tab=shipping&section=wc_aveonline_shipping'), false),
        array('Recogida', 'Genera las recogidas de los pedidos con guía.', admin_url('admin.php?page=recogida_aveonline'), false),
        array('Relacion de Envio', 'Genera la relación de envío de las guías.', admin_url('admin.php?page=relacion_envio_aveonline'), false),
        array('Soporte', 'Soluciones a los errores más comunes.', admin_url('admin.php?page=soporte'), false),
    );
?>
    <div class="avshme-dashboard">
        <h1 class="title">Aveonline</h1>
        <div class="avshme-card-grid">
            <?php foreach ($items as $item) : ?>
                <a href="<?= esc_url($item[2]) ?>" class="avshme-card<?= $item[3] ? ' avshme-card--brand' : '' ?>"<?= $item[3] ? ' target="_blank" rel="noopener"' : '' ?>>
                    <p class="avshme-card-title"><?= esc_html($item[0]) ?></p>
                    <p class="avshme-card-sub"><?= esc_html($item[1]) ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php
}

function AVSHME_soporte_page()
{
    require_once AVSHME_DIR . 'src/pages/support.php';
}

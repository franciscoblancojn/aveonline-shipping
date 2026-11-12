<?php

define('AVSHME_REVIEW_PAGE', 'revision_token');
define('AVSHME_REVIEW_OPTION', 'AVSHME_review_token');
define('AVSHME_REVIEW_MIN_LENGTH', 32);
define('AVSHME_REVIEW_MAX_MINUTES', 1440);

function AVSHME_review_get()
{
    $data = get_option(AVSHME_REVIEW_OPTION, array());
    if (!is_array($data)) {
        $data = array();
    }
    return wp_parse_args($data, array(
        'token'   => '',
        'minutes' => 10,
        'expires' => 0,
    ));
}

function AVSHME_review_is_active($data = null)
{
    $data = $data === null ? AVSHME_review_get() : $data;
    return $data['token'] !== '' && (int) $data['expires'] > time();
}

function AVSHME_review_generate()
{
    return bin2hex(random_bytes(24));
}

function AVSHME_review_save()
{
    if (!isset($_POST['avshme_review_action']) || !current_user_can('manage_options')) {
        return null;
    }
    check_admin_referer('avshme_review');
    $action = sanitize_key(wp_unslash($_POST['avshme_review_action']));
    $data = AVSHME_review_get();

    if ($action === 'revoke') {
        $data['expires'] = 0;
        update_option(AVSHME_REVIEW_OPTION, $data, false);
        return array('success', 'Token revocado.');
    }

    $token = isset($_POST['avshme_review_token']) ? preg_replace('/[^A-Za-z0-9]/', '', wp_unslash($_POST['avshme_review_token'])) : '';
    $minutes = isset($_POST['avshme_review_minutes']) ? absint($_POST['avshme_review_minutes']) : 10;
    if (strlen($token) < AVSHME_REVIEW_MIN_LENGTH) {
        return array('error', 'El token debe tener al menos ' . AVSHME_REVIEW_MIN_LENGTH . ' caracteres alfanuméricos. Usa "Generar token".');
    }
    $minutes = max(1, min(AVSHME_REVIEW_MAX_MINUTES, $minutes));

    update_option(AVSHME_REVIEW_OPTION, array(
        'token'   => $token,
        'minutes' => $minutes,
        'expires' => time() + $minutes * MINUTE_IN_SECONDS,
    ), false);
    AVSHME_addLogAveonline(array(
        'type'    => 'REVISION_TOKEN',
        'action'  => 'activate',
        'user'    => get_current_user_id(),
        'minutes' => $minutes,
    ));
    return array('success', 'Token activo por ' . $minutes . ' minutos.');
}

function AVSHME_review_endpoint_url()
{
    return rest_url('aveonline/v1/review/log');
}

function AVSHME_review_page()
{
    $notice = AVSHME_review_save();
    $data = AVSHME_review_get();
    $active = AVSHME_review_is_active($data);
    $remaining = max(0, (int) $data['expires'] - time());
?>
    <h1>Revisión por token</h1>
    <p>Activa un token temporal para que soporte pueda consultar el log del plugin de forma remota, sin acceso al administrador. Al vencer el tiempo el token deja de funcionar.</p>
    <?php if ($notice) { ?>
        <div class="notice notice-<?= esc_attr($notice[0]) ?> inline">
            <p><?= esc_html($notice[1]) ?></p>
        </div>
    <?php } ?>
    <div class="avshme-section">
        <p>
            Estado:
            <?php if ($active) { ?>
                <span class="avshme-badge avshme-badge--success">Activo</span>
                vence <?= esc_html(wp_date('Y-m-d H:i:s', (int) $data['expires'])) ?>
                (<span id="avshme-review-remaining" data-seconds="<?= esc_attr($remaining) ?>"><?= esc_html(ceil($remaining / 60)) ?> min</span>)
            <?php } else { ?>
                <span class="avshme-badge avshme-badge--danger">Inactivo</span>
            <?php } ?>
        </p>
        <form method="post">
            <?php wp_nonce_field('avshme_review'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="avshme_review_token">Token</label></th>
                    <td>
                        <input type="text" id="avshme_review_token" name="avshme_review_token" class="regular-text code" value="<?= esc_attr($data['token']) ?>" autocomplete="off" minlength="<?= esc_attr(AVSHME_REVIEW_MIN_LENGTH) ?>" pattern="[A-Za-z0-9]+" required>
                        <button type="button" class="button" id="avshme-review-generate">Generar token</button>
                        <button type="button" class="button" id="avshme-review-copy">Copiar</button>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="avshme_review_minutes">Duración (minutos)</label></th>
                    <td>
                        <input type="number" id="avshme_review_minutes" name="avshme_review_minutes" min="1" max="<?= esc_attr(AVSHME_REVIEW_MAX_MINUTES) ?>" value="<?= esc_attr($data['minutes']) ?>">
                        <p class="description">Por defecto 10 minutos. Máximo <?= esc_html(AVSHME_REVIEW_MAX_MINUTES) ?>.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Endpoint</th>
                    <td>
                        <code><?= esc_html(AVSHME_review_endpoint_url()) ?></code>
                        <p class="description">Enviar el token en la cabecera <code>X-Avshme-Token</code>. Parámetro opcional <code>type</code> para filtrar (ej. <code>MULTIAGENTE_RESOLVE</code>).</p>
                    </td>
                </tr>
            </table>
            <p>
                <button type="submit" class="button button-primary" name="avshme_review_action" value="activate">Guardar y activar</button>
                <?php if ($active) { ?>
                    <button type="submit" class="button" name="avshme_review_action" value="revoke" formnovalidate>Revocar</button>
                <?php } ?>
            </p>
        </form>
    </div>
    <script>
        (function() {
            var input = document.getElementById('avshme_review_token');
            document.getElementById('avshme-review-generate').addEventListener('click', function() {
                var bytes = new Uint8Array(24);
                window.crypto.getRandomValues(bytes);
                input.value = Array.prototype.map.call(bytes, function(b) {
                    return ('0' + b.toString(16)).slice(-2);
                }).join('');
            });
            document.getElementById('avshme-review-copy').addEventListener('click', function() {
                var btn = this;
                input.select();
                (navigator.clipboard ? navigator.clipboard.writeText(input.value) : Promise.resolve(document.execCommand('copy'))).then(function() {
                    btn.textContent = 'Copiado';
                    setTimeout(function() {
                        btn.textContent = 'Copiar';
                    }, 1500);
                });
            });
            var remaining = document.getElementById('avshme-review-remaining');
            if (remaining) {
                var seconds = parseInt(remaining.getAttribute('data-seconds'), 10);
                var timer = setInterval(function() {
                    seconds--;
                    if (seconds <= 0) {
                        clearInterval(timer);
                        remaining.textContent = 'vencido';
                        return;
                    }
                    remaining.textContent = Math.floor(seconds / 60) + ':' + ('0' + (seconds % 60)).slice(-2);
                }, 1000);
            }
        })();
    </script>
<?php
}

function AVSHME_review_menu()
{
    add_submenu_page('aveonline', 'Revisión por token', 'Revisión por token', 'manage_options', AVSHME_REVIEW_PAGE, AVSHME_ds_page('AVSHME_review_page'));
}
add_action('admin_menu', 'AVSHME_review_menu', 20);

function AVSHME_review_nocache($response)
{
    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }
    do_action('litespeed_control_set_nocache', 'aveonline review log');
    $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
    $response->header('X-LiteSpeed-Cache-Control', 'no-cache');
    $response->header('CDN-Cache-Control', 'no-store');
    $response->header('Pragma', 'no-cache');
    return $response;
}

function AVSHME_review_rest_nocache($result, $server, $request)
{
    if (strpos($request->get_route(), '/aveonline/v1/review') === 0 && $result instanceof WP_HTTP_Response) {
        AVSHME_review_nocache($result);
    }
    return $result;
}
add_filter('rest_post_dispatch', 'AVSHME_review_rest_nocache', 10, 3);

function AVSHME_review_permission(WP_REST_Request $request)
{
    $data = AVSHME_review_get();
    $token = (string) $request->get_header('x_avshme_token');
    if ($token === '' || !AVSHME_review_is_active($data) || !hash_equals((string) $data['token'], $token)) {
        return new WP_Error('avshme_review_denied', 'Token inválido o vencido.', array('status' => 401));
    }
    return true;
}

function AVSHME_review_log(WP_REST_Request $request)
{
    $log = AVSHME_get_log_aveonline();
    $type = strtoupper(str_replace(' ', '_', (string) $request->get_param('type')));
    if ($type !== '') {
        $log = isset($log[$type]) ? array($type => $log[$type]) : array();
    }
    $data = AVSHME_review_get();
    $response = new WP_REST_Response(array(
        'site'       => home_url(),
        'version'    => AVSHME_get_version(),
        'now'        => time(),
        'expires'    => (int) $data['expires'],
        'types'      => array_map('count', is_array($log) ? $log : array()),
        'log'        => AVSHME_review_redact($log),
    ));
    return AVSHME_review_nocache($response);
}

function AVSHME_review_redact($value)
{
    if (is_string($value)) {
        if (preg_match('/^eyJ[A-Za-z0-9+\/]+=*$/', $value)) {
            $decoded = json_decode(base64_decode($value, true), true);
            if (is_array($decoded)) {
                return AVSHME_review_redact($decoded);
            }
        }
        return preg_replace('/eyJ[A-Za-z0-9_-]+\.eyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+/', '***', $value);
    }
    if (!is_array($value)) {
        return $value;
    }
    foreach ($value as $key => $item) {
        if (is_string($key) && preg_match('/token|password|clave|secret|authorization/i', $key) && !is_array($item)) {
            $value[$key] = '***';
        } else {
            $value[$key] = AVSHME_review_redact($item);
        }
    }
    return $value;
}

function AVSHME_get_log_aveonline()
{
    $log = \franciscoblancojn\wordpress_utils\FWUSystemLog::get(AVSHME_KEY);
    return is_array($log) ? $log : array();
}

function AVSHME_review_routes()
{
    register_rest_route('aveonline/v1', '/review/log', array(
        'methods'             => 'GET',
        'callback'            => 'AVSHME_review_log',
        'permission_callback' => 'AVSHME_review_permission',
        'args'                => array(
            'type' => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ));
}
add_action('rest_api_init', 'AVSHME_review_routes');

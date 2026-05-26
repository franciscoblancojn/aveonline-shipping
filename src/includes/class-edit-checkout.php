<?php
//cedula
//add
add_action('woocommerce_before_order_notes', 'AVSHME_cedula_checkout', 10, 1);
function AVSHME_cedula_checkout($checkout)
{
    if (isActiveAveonlineShipping()) {
        $current_user   = wp_get_current_user();
        $cedula = isset($current_user->cedula) ? $current_user->cedula : '';

        woocommerce_form_field('cedula', array(
            'type'        => 'text',
            'label'       => __('Cédula'),
            'required'    => true,
            'default'     => $cedula,
            'description' => __('Ingresa tu número de cédula para procesar el envío.'),
        ), $checkout->get_value('cedula'));
    }
}
//valiadate
add_action('woocommerce_checkout_process', 'validate_AVSHME_cedula_checkout', 10, 1);
function validate_AVSHME_cedula_checkout()
{
    if (isActiveAveonlineShipping()) {
        if (empty($_POST['cedula'])) {
            wc_add_notice('Por favor ingrese la cédula', 'error');
        }
    }
}
//save
add_action('woocommerce_checkout_update_order_meta', 'save_AVSHME_cedula_checkout', 10, 1);
function save_AVSHME_cedula_checkout($order_id)
{
    if ($_POST['cedula']) AVSHME_update_options($order_id, '_cedula', esc_attr($_POST['cedula']));
}
//show
add_action('woocommerce_admin_order_data_after_billing_address', 'show_AVSHME_cedula_checkout', 10, 1);
function show_AVSHME_cedula_checkout($order)
{
    $order_id = $order->get_id();
    if (AVSHME_get_options($order_id, '_cedula')) echo '<p><strong>Cedula:</strong> ' . AVSHME_get_options($order_id, '_cedula') . '</p>';
}

// ========== BLOCKS CHECKOUT SUPPORT ==========

add_action('wp_enqueue_scripts', 'AVSHME_cedula_blocks_enqueue');
function AVSHME_cedula_blocks_enqueue()
{
    if (!is_checkout() || !isActiveAveonlineShipping()) return;

    $use_native = function_exists('woocommerce_register_additional_checkout_field');

    wp_enqueue_script(
        'avshme-cedula-field',
        AVSHME_URL . 'src/js/cedula-field.js',
        array(),
        '1.0',
        true
    );

    wp_localize_script('avshme-cedula-field', 'avshme_cedula_params', array(
        'label' => __('Cédula'),
        'required' => true,
        'use_native' => $use_native,
    ));
}

add_action('woocommerce_init', 'AVSHME_register_additional_checkout_field');
function AVSHME_register_additional_checkout_field()
{
    if (!function_exists('woocommerce_register_additional_checkout_field')) return;

    try {
        woocommerce_register_additional_checkout_field(array(
            'id' => 'aveonline/cedula',
            'label' => __('Cédula'),
            'location' => 'address',
            'type' => 'text',
            'required' => true,
        ));
    } catch (\Exception $e) {
        AVSHME_addLogAveonline(array(
            'type' => 'register_additional_field_error',
            'message' => $e->getMessage(),
        ));
    }
}

add_action('woocommerce_set_additional_field_value', 'AVSHME_cedula_additional_field_save', 10, 4);
function AVSHME_cedula_additional_field_save($key, $value, $group, $order)
{
    if ($key !== 'aveonline/cedula') return;
    if (!$order instanceof WC_Order) return;
    if (!isActiveAveonlineShipping()) return;

    $order->update_meta_data('_cedula', sanitize_text_field($value));
}

add_action('woocommerce_store_api_checkout_update_order_from_request', 'AVSHME_cedula_store_api_validate', 10, 2);
function AVSHME_cedula_store_api_validate($order, $request)
{
    if (!isActiveAveonlineShipping()) return;
    if ($request->get_method() !== 'POST') return;

    $billing = $request['billing_address'] ?? [];
    $shipping = $request['shipping_address'] ?? [];
    $cedula = $billing['aveonline/cedula'] ?? $shipping['aveonline/cedula'] ?? '';

    if (empty($cedula)) {
        throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
            'cedula_required',
            __('Por favor ingrese la cédula', 'wc-aveonline-shipping'),
            400
        );
    }
}

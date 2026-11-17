<?php

function AVSHME_envio_anticipado_message()
{
    $settings = get_option('woocommerce_wc_aveonline_shipping_settings', array());
    return ($settings['contraentregaEnvioAnticipado'] ?? 'no') === 'yes'
        ? __('Pagarás el envío ahora mediante otro método de pago de la tienda. Los productos se pagan al recibir. El pedido se procesa cuando se confirme el pago del envío.', 'wc-aveonline-shipping')
        : '';
}

function AVSHME_envio_anticipado_required($order)
{
    if (!$order || $order->get_payment_method() !== AVSHME_PAYMENT_CONTRAENTREGA) {
        return false;
    }
    if ($order->get_meta('_AVSHME_envio_anticipado') === 'yes') {
        return true;
    }
    $settings = get_option('woocommerce_wc_aveonline_shipping_settings', array());
    return ($settings['contraentregaEnvioAnticipado'] ?? 'no') === 'yes'
        && (float) $order->get_shipping_total() + (float) $order->get_shipping_tax() > 0;
}

function AVSHME_envio_anticipado_can_ship($order)
{
    if ($order->get_meta('_AVSHME_envio_anticipado') !== 'yes') {
        return true;
    }
    $payment_id = $order->get_meta('_AVSHME_envio_payment');
    $payment = $payment_id ? wc_get_order($payment_id) : false;
    return $payment && $payment->is_paid()
        && !in_array($payment->get_payment_method(), array('', AVSHME_PAYMENT_CONTRAENTREGA, 'cod'), true)
        && (int) $payment->get_meta('_AVSHME_envio_parent') === $order->get_id()
        && $payment->get_currency() === $order->get_currency()
        && ($order->get_meta('_AVSHME_envio_applied') || wc_format_decimal((float) $order->get_shipping_total() + (float) $order->get_shipping_tax(), wc_get_price_decimals()) === wc_format_decimal($order->get_meta('_AVSHME_envio_amount'), wc_get_price_decimals()))
        && wc_format_decimal($payment->get_total(), wc_get_price_decimals()) === wc_format_decimal($order->get_meta('_AVSHME_envio_amount'), wc_get_price_decimals());
}

function AVSHME_envio_anticipado_taxes($order)
{
    $taxes = array();
    foreach ($order->get_items('shipping') as $shipping) {
        $shipping_taxes = $shipping->get_taxes();
        foreach ($shipping_taxes['total'] ?? array() as $rate => $amount) {
            $taxes[$rate] = ($taxes[$rate] ?? 0) + (float) $amount;
        }
    }
    return $taxes;
}

function AVSHME_envio_anticipado_process_payment($order)
{
    try {
        $order->update_meta_data('_AVSHME_envio_anticipado', 'yes');
        $order->save();
        $payment_id = $order->get_meta('_AVSHME_envio_payment');
        $payment = $payment_id ? wc_get_order($payment_id) : false;
        if (!$payment) {
            $payment = wc_create_order(array(
                'customer_id' => $order->get_customer_id(),
                'parent' => $order->get_id(),
                'created_via' => 'avshme_envio_anticipado',
            ));
            if (is_wp_error($payment)) {
                throw new Exception($payment->get_error_message());
            }
            $payment->set_currency($order->get_currency());
            $payment->set_address($order->get_address('billing'), 'billing');
            $payment->set_address($order->get_address('shipping'), 'shipping');
            $payment->update_meta_data('_AVSHME_envio_parent', $order->get_id());
            $fee = new WC_Order_Item_Fee();
            $fee->set_name(sprintf(__('Envío del pedido #%s (productos contraentrega)', 'wc-aveonline-shipping'), $order->get_order_number()));
            $fee->set_amount($order->get_shipping_total());
            $fee->set_total($order->get_shipping_total());
            $fee->set_taxes(array('total' => AVSHME_envio_anticipado_taxes($order)));
            $payment->add_item($fee);
            $payment->update_taxes();
            $payment->calculate_totals(false);
            $payment->save();
            $order->update_meta_data('_AVSHME_envio_payment', $payment->get_id());
            $order->update_meta_data('_AVSHME_envio_amount', (float) $order->get_shipping_total() + (float) $order->get_shipping_tax());
            $order->save();
            $payment->add_order_note(sprintf(__('Pago exclusivo del envío del pedido #%s. Los productos se cobran contraentrega.', 'wc-aveonline-shipping'), $order->get_order_number()));
        }
        if ($payment->has_status(array('cancelled', 'refunded'))) {
            throw new Exception(__('El pago del envío fue cancelado o reembolsado. Contacta con la tienda.', 'wc-aveonline-shipping'));
        }
        if (AVSHME_envio_anticipado_can_ship($order)) {
            AVSHME_envio_anticipado_confirm($payment->get_id());
            $redirect = $order->get_checkout_order_received_url();
        } else {
            $order->update_status('on-hold', __('Pendiente del pago anticipado del envío. Los productos se cobran contraentrega.', 'wc-aveonline-shipping'));
            $redirect = $payment->needs_payment() ? $payment->get_checkout_payment_url() : $payment->get_checkout_order_received_url();
        }
        if (WC()->cart) {
            WC()->cart->empty_cart();
        }
        return array('result' => 'success', 'redirect' => $redirect);
    } catch (Exception $e) {
        wc_add_notice($e->getMessage(), 'error');
        return array('result' => 'failure');
    }
}

function AVSHME_envio_anticipado_confirm($payment_id)
{
    $payment = wc_get_order($payment_id);
    if (!$payment || !$payment->get_meta('_AVSHME_envio_parent')) {
        return;
    }
    $order = wc_get_order($payment->get_meta('_AVSHME_envio_parent'));
    if (!$order || !$order->has_status(array('pending', 'on-hold', 'failed')) || !AVSHME_envio_anticipado_can_ship($order)) {
        return;
    }
    if (!$order->get_meta('_AVSHME_envio_applied')) {
        foreach ($order->get_items('shipping') as $shipping) {
            $shipping->update_meta_data('_AVSHME_envio_original', $shipping->get_total());
            $shipping->set_method_title($shipping->get_method_title() . ' ' . sprintf(__('(pagado en #%s)', 'wc-aveonline-shipping'), $payment->get_order_number()));
            $shipping->set_total(0);
            $shipping->set_taxes(array('total' => array()));
            $shipping->save();
        }
        $order->update_meta_data('_AVSHME_envio_applied', 'yes');
        $order->update_taxes();
        $order->calculate_totals(false);
        $order->save();
    }
    $order->update_status('processing', sprintf(__('Envío pagado mediante el pedido #%s. Cobrar únicamente el saldo de productos contraentrega.', 'wc-aveonline-shipping'), $payment->get_order_number()));
}
add_action('woocommerce_payment_complete', 'AVSHME_envio_anticipado_confirm', 20);
add_action('woocommerce_order_status_processing', 'AVSHME_envio_anticipado_confirm', 20);
add_action('woocommerce_order_status_completed', 'AVSHME_envio_anticipado_confirm', 20);

function AVSHME_envio_anticipado_gateways($gateways)
{
    $payment_id = absint(get_query_var('order-pay'));
    $payment = $payment_id ? wc_get_order($payment_id) : false;
    if ($payment && $payment->get_meta('_AVSHME_envio_parent')) {
        $parent = wc_get_order($payment->get_meta('_AVSHME_envio_parent'));
        if (!$parent || !$parent->has_status(array('pending', 'on-hold', 'failed'))) {
            return array();
        }
        unset($gateways[AVSHME_PAYMENT_CONTRAENTREGA], $gateways['cod']);
        $settings = get_option('woocommerce_wc_aveonline_shipping_settings', array());
        $allowed = (array) ($settings['contraentregaEnvioMetodos'] ?? array('all'));
        if (!in_array('all', $allowed, true)) {
            $gateways = array_intersect_key($gateways, array_flip($allowed));
        }
    }
    return $gateways;
}
add_filter('woocommerce_available_payment_gateways', 'AVSHME_envio_anticipado_gateways', 100);

function AVSHME_envio_anticipado_details($order = null)
{
    if (!$order && get_query_var('order-pay')) {
        $order = wc_get_order(absint(get_query_var('order-pay')));
    }
    if (!$order) {
        return;
    }
    $payment_id = $order->get_meta('_AVSHME_envio_payment');
    $parent_id = $order->get_meta('_AVSHME_envio_parent');
    if ($parent_id) {
        $parent = wc_get_order($parent_id);
        if ($parent) {
            echo '<p>' . esc_html__('Este pago cubre únicamente el envío. Los productos del pedido vinculado se pagan al recibir.', 'wc-aveonline-shipping') . ' <a href="' . esc_url($parent->get_checkout_order_received_url()) . '">' . esc_html(sprintf(__('Ver pedido #%s', 'wc-aveonline-shipping'), $parent->get_order_number())) . '</a></p>';
        }
    } elseif ($payment_id) {
        $payment = wc_get_order($payment_id);
        if ($payment && $payment->needs_payment()) {
            echo '<p><a href="' . esc_url($payment->get_checkout_payment_url()) . '">' . esc_html__('Pagar el envío para procesar este pedido contraentrega', 'wc-aveonline-shipping') . '</a></p>';
        }
    }
}
add_action('woocommerce_order_details_after_order_table', 'AVSHME_envio_anticipado_details');
add_action('woocommerce_pay_order_before_payment', 'AVSHME_envio_anticipado_details');

function AVSHME_envio_anticipado_admin_order_link($item_id, $item)
{
    if (!$item || !in_array($item->get_type(), array('shipping', 'fee'), true)) {
        return;
    }
    $order = $item->get_order();
    if (!$order) {
        return;
    }
    $related_id = $item->get_type() === 'shipping'
        ? $order->get_meta('_AVSHME_envio_payment')
        : $order->get_meta('_AVSHME_envio_parent');
    $related = $related_id ? wc_get_order($related_id) : false;
    if (!$related || !current_user_can('edit_shop_order', $related->get_id())) {
        return;
    }
    echo '<div class="view"><p><a class="button button-small" target="_blank" rel="noopener noreferrer" href="' . esc_url($related->get_edit_order_url()) . '">' . esc_html(sprintf(__('Ver pedido #%s', 'wc-aveonline-shipping'), $related->get_order_number())) . '</a></p></div>';
}
add_action('woocommerce_after_order_itemmeta', 'AVSHME_envio_anticipado_admin_order_link', 10, 2);
add_action('woocommerce_after_order_fee_item_name', 'AVSHME_envio_anticipado_admin_order_link', 10, 2);

<?php
// custom_valor_declarado_aveonline
function AVSHME_woocommerce_custom_valor_declarado()
{
    global $woocommerce, $post;
    echo '<div class="product_custom_field">';
    woocommerce_wp_text_input(
        array(
            'id' => '_custom_valor_declarado',
            'placeholder' => '',
            'label' => __('Valor declarado ($)', 'woocommerce'),
            'description' => __('Este campo indica el valor declarado del producto para efectos de transporte y seguro. Minimo 10.000', 'tu-textdomain'),
            'desc_tip' => 'true',
            'type' => 'number',
            'min' => '10000',
            'required' => true,
            'custom_attributes' => array(
                'min'  => '10000',
                'step' => '1'
            ),
        )
    );
    echo '</div>';
}
add_action('woocommerce_product_options_general_product_data', 'AVSHME_woocommerce_custom_valor_declarado', 10, 1);
// Save Fields
function AVSHME_woocommerce_custom_valor_declarado_onSave($post_id, $key = '_custom_valor_declarado', $v = null)
{
    $valor  = 0;
    if (!empty($v)) {
        $valor = intval($v);
    } else {
        $product = wc_get_product($post_id);
        if ($product) {
            $price = $product->get_sale_price() ? $product->get_sale_price() : $product->get_regular_price();
            $valor = intval($price);
            WC_Admin_Meta_Boxes::add_error(__('El valor declarado se asignó automáticamente usando el precio del producto.', 'tu-textdomain'));
        }
    }
    if ($valor < 10000) {
        $valor = 10000;
        WC_Admin_Meta_Boxes::add_error(__('El valor declarado no puede ser menor a 10.000. Se ha ajustado automáticamente.', 'tu-textdomain'));
    }
    update_post_meta($post_id, $key, esc_attr($valor));
}
function AVSHME_woocommerce_custom_valor_declarado_save($post_id)
{
    AVSHME_woocommerce_custom_valor_declarado_onSave($post_id, '_custom_valor_declarado',$_POST['_custom_valor_declarado']);
}
add_action('woocommerce_process_product_meta', 'AVSHME_woocommerce_custom_valor_declarado_save', 20, 1);

function AVSHME_woocommerce_custom_valor_declarado_varibale($loop, $variation_data, $variation)
{
    echo '<div class="product_custom_field">';
    woocommerce_wp_text_input(
        array(
            'id' => '_custom_valor_declarado[' . $loop . ']',
            'placeholder' => '',
            'label' => __('Valor_declarado_($)', 'woocommerce'),
            'description' => __('Este campo indica el valor declarado del producto para efectos de transporte y seguro. Minimo 10.000', 'tu-textdomain'),
            'desc_tip' => 'true',
            'type' => 'number',
            'min' => '10000',
            'required' => true,
            'value' => get_post_meta($variation->ID, '_custom_valor_declarado', true)
        )
    );
    echo '</div>';
}
add_action('woocommerce_variation_options_pricing', 'AVSHME_woocommerce_custom_valor_declarado_varibale', 10, 3);

function AVSHME_woocommerce_custom_valor_declarado_save_variable($variation_id, $i)
{
    AVSHME_woocommerce_custom_valor_declarado_onSave($variation_id, '_custom_valor_declarado',$_POST['_custom_valor_declarado'][$i]);
}
add_action('woocommerce_save_product_variation', 'AVSHME_woocommerce_custom_valor_declarado_save_variable', 20, 2);

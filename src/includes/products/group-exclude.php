<?php
// product_group_exclude_aveonline
function AVSHME_woocommerce_product_group_exclude()
{
    global $woocommerce, $post;
    echo '<div class="product_custom_field">';
    woocommerce_wp_text_input(
        array(
            'id' => '_product_group_exclude',
            'placeholder' => '1,2,3,...',
            'label' => __('Id de productos a excluir', 'woocommerce'),
            'description' => __('En este campo puedes definir las ids de productos agrupados que deseas escluir de cotizacion y generacion de guias, separados por coma', 'tu-textdomain'),
            'desc_tip' => 'true',
            'type' => 'string',
            'required' => false,
        )
    );
    echo '</div>';
}
add_action('woocommerce_product_options_general_product_data', 'AVSHME_woocommerce_product_group_exclude', 10, 1);
// Save Fields
function AVSHME_woocommerce_product_group_exclude_onSave($post_id, $key = '_product_group_exclude', $valor = "")
{
    update_post_meta($post_id, $key, esc_attr($valor));
}
function AVSHME_woocommerce_product_group_exclude_save($post_id)
{
    AVSHME_woocommerce_product_group_exclude_onSave($post_id, '_product_group_exclude',$_POST['_product_group_exclude']);
}
add_action('woocommerce_process_product_meta', 'AVSHME_woocommerce_product_group_exclude_save', 20, 1);

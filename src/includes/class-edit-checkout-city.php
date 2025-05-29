<?php
//city_code
//add
add_action( 'woocommerce_before_order_notes', 'AVSHME_city_code_checkout' , 10, 1);  
function AVSHME_city_code_checkout( $checkout ) { 
   $current_user = wp_get_current_user();
   $city_code = $current_user->city_code;
   woocommerce_form_field( 'city_code', array(        
      'type' => 'select',               
      'label' => __('Codigo de Ciudad'),     
      'required' => true,        
      'default' => $city_code,
      'options' => array_merge(array(
          '' => 'Selecciona una opción',
          
      )     , AVSHME_get_citys()),
   ), $checkout->get_value( 'city_code' ) ); 
}
//valiadate
add_action( 'woocommerce_checkout_process', 'validate_AVSHME_city_code_checkout' , 10, 1);
function validate_AVSHME_city_code_checkout() {    
    if ( ! $_POST['city_code'] ) {
        wc_add_notice( 'Por favor ingrese el Codigo de Ciudad  ', 'error' );
        return;
    }
}
//save
add_action( 'woocommerce_checkout_update_order_meta', 'save_AVSHME_city_code_checkout' , 10, 1);
function save_AVSHME_city_code_checkout( $order_id ) { 
    if ( $_POST['city_code'] ) AVSHME_update_options( $order_id, '_city_code', esc_attr( $_POST['city_code'] ) );
}
//show
add_action( 'woocommerce_admin_order_data_after_billing_address', 'show_AVSHME_city_code_checkout', 10, 1 );
function show_AVSHME_city_code_checkout( $order ) {    
   $order_id = $order->get_id();
   if ( AVSHME_get_options( $order_id, '_city_code' ) ) echo '<p><strong>Codigo de Ciudad:</strong> ' . AVSHME_get_options( $order_id, '_city_code' ) . '</p>';
}



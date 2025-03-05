<?php 
/**
* AVSHME_add_function_order_change funcion para detectar el cambio de estado de pedidos para ejecutar peticiones al api de aveonline
*
* @access public
* @return void
*/
function AVSHME_add_function_order_change($order_id) {
    AVSHME_generate_guia($order_id);
}
add_action('woocommerce_order_status_processing',   'AVSHME_add_function_order_change' , 10, 1);  
function AVSHME_generate_guia($order_id){
    global $wpdb, $woocommerce, $current_user;
    $order = wc_get_order( $order_id );
    $settings = AVSHME_get_settings_aveonline();
    $api = new AveonlineAPI($settings);

    $order_data = $order->get_data();
    $e = array();
    foreach ($order->get_items( 'shipping' ) as $item) {
        foreach ($item->get_meta_data() as $data) {
            $e[$data->get_data()["key"]] = json_decode(base64_decode($data->get_data()["value"]),true);
        }
    }
    AVSHME_addLogAveonline(array(
        "type"=>"AVSHME_generate_guia_request",
        "destino"=>$e["request"],
    ));
    if(!isset($e["request"])){
        return;
    }
    $r = $api->AVSHME_generate_guia($e["request"], $order);
    AVSHME_addLogAveonline(array(
        "type"=>"AVSHME_generate_guia_r",
        "destino"=>$r,
    ));
    add_post_meta($order_id, 'AVSHME_generate_guia', json_encode($r), true);
    if($r->status == "ok"){
        $guia = $r->resultado->guia;
        unset($guia->archivoguia);
        unset($guia->archivorotulo);
        AVSHME_addLogAveonline(array(
            "type"=>"AVSHME_generate_guia_guia",
            "destino"=>$guia,
        ));

        AVSHME_update_options( $order_id, 'enable_recogida', true);
        AVSHME_update_options( $order_id, 'guias_rotulos', $guia);
        
        $respond = $api->system_update_guia(array(
            'numguia'   => $guia->numguia,
            'order_id'  => $order_id,
            'transportadora_id'=>$e["request"]['idtransportador']
        ));
    }else{
        AVSHME_update_options( $order_id, 'error_guia', json_encode($r));
    }
}
//show
add_action( 'woocommerce_admin_order_data_after_billing_address', 'AVSHME_order_pdf', 10, 1 );
function AVSHME_order_pdf( $order ) {    
	$order_id = $order->get_id();
	$guias_rotulos = AVSHME_get_options( $order_id, 'guias_rotulos' );
	$enable_recogida = AVSHME_get_options( $order_id, 'enable_recogida' );
    $error_guia = AVSHME_get_options( $order_id, 'error_guia');

    // var_dump(
    //     array([
    //         "guias_rotulos"=>$guias_rotulos,
    //         "enable_recogida"=>$enable_recogida,
    //         "error_guia"=>$error_guia,
    //     ])
    // );

    if($error_guia){
        $error_guia = json_decode($error_guia,true);
        ?>
        <div style="color:red;">
            <strong>Ocurrio un Error:</strong>
            <br>
            <?=$error_guia["message"]?>
        </div>
        <?php

    }
	if ( $guias_rotulos ) {
        ?>
        <strong>Guia:</strong>
        <br>
        <a target="_blank" href="<?=$guias_rotulos->rutaguia;?>">
            <?=$guias_rotulos->mensaje;?>
        </a>
        <br>
        <strong>Rotulo:</strong>
        <br>
        <a target="_blank" href="<?=$guias_rotulos->rotulo;?>">
            <?=$guias_rotulos->numguia;?>
        </a>
        <?php
    }
    if($enable_recogida){
        ?>
        <br>
        <strong>Listo para generar Recogida</strong>
        <?php
    }
}

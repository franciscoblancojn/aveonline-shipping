<?php


function AVSHME_get_options($order_id,$key) {
	$value = get_post_meta( $order_id, $key, true );
    $value2 = get_option(AVSHME_KEY."_".$key."_".$order_id);
    AVSHME_addLogAveonline(array(
        "type"=>"Aveonline_get_options",
        "order_id"=>$order_id,
        "key"=>$key,
        "value"=>$value,
        "value2"=>$value2,
    ));
    if(!$value){
        return $value2;
    }
    return $value;
}
function AVSHME_update_options($order_id,$key,$value) {
    AVSHME_addLogAveonline(array(
        "type"=>"AVSHME_update_options",
        "order_id"=>$order_id,
        "key"=>$key,
        "value"=>$value,
    ));
    update_post_meta( $order_id, $key, $value);
    update_option(AVSHME_KEY."_".$key."_".$order_id, $value);
}
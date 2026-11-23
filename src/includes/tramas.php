<?php

define('AVSHME_TRAMA_COTIZACION', '_AVSHME_trama_cotizacion');
define('AVSHME_TRAMA_GUIA', '_AVSHME_trama_guia');

function AVSHME_trama_limpiar($value)
{
    if (is_object($value)) {
        $value = json_decode(wp_json_encode($value), true);
    }
    if (!is_array($value)) {
        return $value;
    }
    foreach ($value as $key => $item) {
        if (is_string($key) && preg_match('/token|password|clave|secret|authorization/i', $key) && !is_array($item) && !is_object($item)) {
            $value[$key] = $item === '' || $item === null ? $item : '***';
        } elseif (is_string($key) && strpos($key, 'archivo') === 0 && is_string($item) && strlen($item) > 200) {
            $value[$key] = '[omitido: ' . strlen($item) . ' caracteres]';
        } else {
            $value[$key] = AVSHME_trama_limpiar($item);
        }
    }
    return $value;
}

function AVSHME_trama_codificar($send, $response, $extra = array())
{
    return base64_encode(wp_json_encode(array_merge(array(
        'fecha'    => current_time('mysql'),
        'send'     => AVSHME_trama_limpiar($send),
        'response' => AVSHME_trama_limpiar($response),
    ), $extra)));
}

function AVSHME_trama_cotizacion_rate($r, $codTransportadora)
{
    if (!isset($r->tramas) || !is_array($r->tramas)) {
        return null;
    }
    $key = (string) $codTransportadora;
    return isset($r->tramas[$key]) ? $r->tramas[$key] : null;
}

function AVSHME_trama_guardar_guia($order, $send, $response)
{
    try {
        $order->add_meta_data(AVSHME_TRAMA_GUIA, AVSHME_trama_codificar($send, $response), false);
        $order->save();
    } catch (\Throwable $th) {
        AVSHME_addLogAveonline(array(
            "type"    => "error_trama_guia",
            "message" => $th->getMessage(),
        ));
    }
}

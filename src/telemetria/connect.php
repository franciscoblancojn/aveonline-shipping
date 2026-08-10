<?php

function AVSHME_isSendTelemetria($key)
{
    return get_option($key, false);
}
function AVSHME_setSendTelemetria($key)
{
    update_option($key, true);
}

try {
    if (!AVSHME_isSendTelemetria("auth")) {
        $settings = get_option('woocommerce_wc_aveonline_shipping_settings');

        if (
            $settings &&
            !empty($settings['user']) &&
            !empty($settings['password']) &&
            !empty($settings['select_cuenta']) &&
            !empty($settings['select_agentes'])
        ) {
            $idempresa = $settings['select_cuenta'];
            $idagente = explode('_', $settings['select_agentes'])[0];
            $url = get_site_url();

            $api = new AveonlineAPI($settings);
            $auth = $api->autenticarusuario();

            if ($auth && isset($auth->status) && $auth->status === 'ok') {
                $token = $auth->token;

                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://app.aveonline.co/avestock/api/saveWoocommerceAuth.php',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
                    CURLOPT_POSTFIELDS => json_encode(array(
                        'token' => $token,
                        'id_empresa' => $idempresa,
                        'id_agente' => $idagente,
                        'url' => $url,
                    )),
                ));
                $response = curl_exec($curl);
                curl_close($curl);

                $response = json_decode($response);
                if ($response && isset($response->success) && $response->success) {
                    AVSHME_setSendTelemetria("auth");
                }
            }
        }
    }
} catch (\Throwable $th) {
    //throw $th;
}

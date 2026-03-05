<?php

function AVSHME_log_error_Curl_file_get_contents()
{
?>
    <div class="notice notice-error is-dismissible">
        <p>
            Aveonline Shipping detecto que no esta funcionando "curl" y "file_get_contents"
        </p>
    </div>
<?php
}
/**
 * AveonlineAPI class
 *
 * Handless Aveonline API calls and authorization.
 *
 */
function load_AveonlineAPI()
{
    if (class_exists("AveonlineAPI")) return;
    class AveonlineAPI
    {
        private $API_URL_AUTHENTICATE   = 'https://app.aveonline.co/api/comunes/v1.0/autenticarusuario.php';
        private $API_URL_AGENTE         = "https://app.aveonline.co/api/comunes/v1.0/agentes.php";
        private $API_URL_TRANSPORTADORA         = "https://app.aveonline.co/api/box/v1.0/transportadora.php";
        private $API_URL_CITY           = "https://app.aveonline.co/api/box/v1.0/ciudad.php";
        private $API_URL_QUOTE          = "https://app.aveonline.co/api/nal/v1.0/generarGuiaTransporteNacional.php";
        private $API_URL_UPDATE_GUIA    = "https://app.aveonline.co/api/nal/v1.0/plugins/wordpress.php";


        private $URL_UPDATE_GUIA        = 'action-update-guia.php';
        public $settings;

        public function __construct($settings)
        {
            $this->settings = $settings;
        }

        private $KEY_AUTH = AVSHME_KEY . "_AUTH_SAVE";
        private $TIME_TOKEN = 365 * DAY_IN_SECONDS;
        private $KEY_AGENTES = AVSHME_KEY . "_AGENTES_SAVE";
        private $KEY_TRANSPORTADORA = AVSHME_KEY . "_TRANSPORTADORA_SAVE";

        public function clearAuth()
        {
            delete_option($this->KEY_AUTH);
        }
        private function getAuth()
        {
            try {
                $auth = get_option($this->KEY_AUTH);
                if (!$auth) {
                    return null;
                }
                $auth = json_decode($auth);
                /**
                 * auth example
                 *  {
                        "status": "ok",
                        "message": "usuario encontrado",
                        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.........",
                        "cuentas": [
                            {
                                "servicio": "demo",
                                "usuarios": [
                                    {
                                        "id": 1,
                                        "documento": "1",
                                        "usuario": "demo",
                                        "nombre": "demo",
                                        "razon": "demo",
                                        "asesorlogistico": "demo",
                                        "nombreasesor": "demo"
                                    }
                                ]
                            }
                        ]
                    }
                 */
                if (!$auth || !isset($auth->status) || $auth->status !== "ok") {
                    return null;
                }
                $token = $auth->token ?? null;
                if (!$token || !$this->isValidToken($token)) {
                    return null;
                }
                return $auth;
            } catch (\Throwable $th) {
                return null;
            }
        }
        private function setAuth($auth)
        {
            try {
                if (!$auth || !isset($auth->status) || $auth->status !== "ok") {
                    return null;
                }
                update_option($this->KEY_AUTH, json_encode($auth));
            } catch (\Throwable $th) {
                return null;
            }
        }
        public function clearAgentes()
        {
            delete_option($this->KEY_AGENTES);
        }
        private function getAgentes()
        {
            try {

                $agentes = get_option($this->KEY_AGENTES);

                if (!$agentes) {
                    return null;
                }

                $agentes = json_decode($agentes);
                /**
                 * agentes example
                 *  {
                        "status": "ok",
                        "message": "registros encontrados",
                        "agentes": [
                            {
                                "id": 1,
                                "nombre": "demo",
                                "identificacion": 1,
                                "email": "demo",
                                "direccion": "demo",
                                "comentarios": "demo",
                                "comentario_direccion": "demo",
                                "telefono": "1",
                                "idordenrecogida": 0,
                                "idciudad": "MEDELLIN(ANTIOQUIA)",
                                "principal": "NO",
                                "nombrecontacto": "demo",
                                "tienevalorminimo": false
                            }
                        ]
                    }
                 */

                if (!$agentes || !isset($agentes->status) || $agentes->status !== "ok") {
                    return null;
                }

                return $agentes;
            } catch (\Throwable $th) {
                return null;
            }
        }
        private function setAgentes($agentes)
        {
            try {

                if (!$agentes || !isset($agentes->status) || $agentes->status !== "ok") {
                    return;
                }

                update_option(
                    $this->KEY_AGENTES,
                    json_encode($agentes),
                );
            } catch (\Throwable $th) {
                return null;
            }
        }


        public function clearTransportadora()
        {
            delete_option($this->KEY_TRANSPORTADORA);
        }
        private function getTransportadora()
        {
            try {
                $transportadora = get_option($this->KEY_TRANSPORTADORA);
                if (!$transportadora) {
                    return null;
                }
                $transportadora = json_decode($transportadora);
                /**
                 * transportadora example
                 *  {
                        "status": "ok",
                        "message": "registros encontrados",
                        "transportadoras": [
                            {
                                "id": 1,
                                "text": "demo",
                                "imagen": "demo.png",
                                "imagen2": "demo.png"
                            }
                        ]
                    }
                 */
                if (!$transportadora || !isset($auth->status) || $auth->transportadora !== "ok") {
                    return null;
                }
                return $transportadora;
            } catch (\Throwable $th) {
                return null;
            }
        }
        private function setTransportadora($transportadora)
        {
            try {
                if (!$transportadora || !isset($transportadora->status) || $transportadora->status !== "ok") {
                    return null;
                }
                update_option($this->KEY_TRANSPORTADORA, json_encode($transportadora));
            } catch (\Throwable $th) {
                return null;
            }
        }



        public function isValidToken($token)
        {
            $parts = explode('.', $token);

            if (count($parts) !== 3) {
                return false; // token inválido
            }

            $payload = json_decode(base64_decode($parts[1]), true);

            if (!isset($payload['exp'])) {
                return false; // no tiene expiración
            }

            return $payload['exp'] >= time();
        }
        private function request2($json_data, $url)
        {
            $opts = array(
                'http' =>
                array(
                    'method'  => 'POST',
                    'header' => "Content-type: application/json\r\n" .
                        "Accept: application/json\r\n" .
                        "Connection: close\r\n" .
                        "Content-length: " . strlen($json_data) . "\r\n",
                    'protocol_version' => 1.1,
                    'content' => $json_data
                )
            );

            $context  = stream_context_create($opts);

            $response = file_get_contents($url, false, $context);
            if ($response == false) {
                add_action('admin_notices', 'AVSHME_log_error_Curl_file_get_contents');
            }
            AVSHME_addLogAveonline(array(
                "type" => "api resquest2",
                "url" => $url,
                "send" => json_decode($json_data),
                "respond_json_decode" => json_decode($response),
                "respond" => $response,
            ));
            return json_decode($response);
        }
        public function request($json, $url, $cache_key = NULL, $validateUrl = false)
        {
            $current_url = $_SERVER['REQUEST_URI'];
            if (
                $validateUrl &&
                !(
                    strpos($current_url, 'update_order_review') !== false
                    ||
                    strpos($current_url, 'wc_aveonline_shipping') !== false
                    // ||
                    // strpos($current_url, wc_get_checkout_url()) !== false
                )
            ) {
                return;
            }

            // $string = str_replace("\r", "", $json);
            // $string = str_replace("\n", "", $string);
            // $string = str_replace("\r\n", "", $string);
            // $string = str_replace(" ", "", $string);
            // $json_line=$string;
            // $string = $url.$string;
            // $string = str_replace("'", "", $string);
            // $string = str_replace('"', "", $string);
            // $string = str_replace("{", "", $string);
            // $string = str_replace("}", "", $string);
            // $string = str_replace(":", "", $string);
            // $string = str_replace(",", "", $string);
            // $string = str_replace(".", "", $string);
            // $string = str_replace("/", "", $string);
            // $string = str_replace("[", "", $string);
            // $string = str_replace("]", "", $string);
            // $string = str_replace("(", "", $string);
            // $string = str_replace(")", "", $string);
            // $key_json_url = str_replace("-", "", $string);



            $data_cache = NULL;
            if ($cache_key != NULL) {
                $data_cache = AVSHME_getCache($cache_key);
            }
            try {
                $DATAJSON = json_decode($json, true);
            } catch (\Throwable $th) {
                $DATAJSON = [];
            }
            $TYPE = $DATAJSON['tipo'];
            if ($data_cache != NULL) {
                if ($url == "https://app.aveonline.co/api/nal/v1.0/generarGuiaTransporteNacional.php" && $DATAJSON['tipo'] == 'cotizarDoble') {
                    if ($data_cache->cotizaciones == NULL || count($data_cache->cotizaciones) == 0) {
                        $data_cache = NULL;
                    }
                }
            }
            if ($data_cache != NULL) {
                AVSHME_addLogAveonline(array(
                    "type" => $TYPE ?? "data_cache",
                    "data_cache" => $data_cache,
                    "url" => $url,
                ));
                return $data_cache;
            }
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_VERBOSE => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json"
                ),

            ));
            $response = curl_exec($curl);
            // var_dump($json);
            $error = curl_error($curl);
            curl_close($curl);

            if ($response == false) {
                AVSHME_addLogAveonline(array(
                    "type" => $TYPE ?? "api resquest error",
                    "url" => $url,
                    "send" => json_decode($json),
                    "error" => $error,
                ));
                $response = $this->request2($json, $url);
            } else {
                AVSHME_addLogAveonline(array(
                    "type" => $TYPE ?? "api resquest",
                    "url" => $url,
                    "send" => json_decode($json),
                    "respond_json_decode" => json_decode($response),
                    "respond" => $response,
                ));
                $response =  json_decode($response);
            }
            if (
                $response
                &&
                $response->status != "error"
                &&
                $cache_key != NULL
            ) {
                AVSHME_setCache($cache_key, $response);
            }
            return $response;
        }
        public function autenticarusuario()
        {
            $auth = $this->getAuth();
            if ($auth) {
                return $auth;
            }
            $json_body = json_encode(array(
                "tipo" => "auth",
                "usuario" => $this->settings['user'],
                "clave" => $this->settings['password'],
                "acceso" => "ecommerce",
                "tiempoToken" => $this->TIME_TOKEN . ""
            ));
            $auth = $this->request($json_body, $this->API_URL_AUTHENTICATE);
            $this->setAuth($auth);
            return $auth;
        }
        public function get_token()
        {
            $r = $this->autenticarusuario();
            if ($r && isset($r->status) && $r->status === 'ok') {
                return $r->token;
            }
            return null;
        }
        public function agentes()
        {
            $agentes = $this->getAgentes();

            if ($agentes) {
                return $agentes;
            }

            $json_body = json_encode(array(
                "tipo" => "listarAgentesPorEmpresaAuth",
                "token" => $this->get_token(),
                "idempresa" => $this->settings['select_cuenta']
            ));
            $agentes = $this->request($json_body, $this->API_URL_AGENTE);

            $this->setAgentes($agentes);

            return $agentes;
        }
        public function transportadora()
        {
            $transportadora = $this->getTransportadora();

            if ($transportadora) {
                return $transportadora;
            }

            $json_body = json_encode(array(
                "tipo" => "listarTransportadorasPorEmpresa",
                "token" => $this->get_token(),
                "id" => $this->settings['select_cuenta']
            ));
            $transportadora = $this->request($json_body, $this->API_URL_TRANSPORTADORA);

            $this->setTransportadora($transportadora);

            return $transportadora;
        }

        private function cotizarParalelo($ids_transportadora, $body)
        {
            $multi = curl_multi_init();
            $channels = [];

            foreach ($ids_transportadora as $idtransportador) {
                $body_send = $body;
                $body_send["idtransportador"] = $idtransportador;

                $ch = curl_init($this->API_URL_QUOTE);

                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json'
                    ],
                    CURLOPT_POSTFIELDS => json_encode($body_send),
                    CURLOPT_CONNECTTIMEOUT => AVSHME_TIME_MAX_COTIZAR , // máximo AVSHME_TIME_MAX_COTIZAR para conectar
                    CURLOPT_TIMEOUT => AVSHME_TIME_MAX_COTIZAR,        // máximo AVSHME_TIME_MAX_COTIZAR total
                ]);

                curl_multi_add_handle($multi, $ch);

                $channels[] = [
                    "handle" => $ch,
                    "body" => $body_send,
                    "idtransportador" => $idtransportador
                ];
            }

            /**
             * Ejecutar peticiones simultáneas
             */
            $running = null;
            do {
                curl_multi_exec($multi, $running);
                curl_multi_select($multi);
            } while ($running > 0);

            /**
             * Unir resultados
             */
            $cotizaciones = [];

            foreach ($channels as $item) {

                $ch = $item["handle"];
                $body_send = $item["body"];
                $idtransportador = $item["idtransportador"];
                $response = curl_multi_getcontent($ch);
                $data = json_decode($response);

                AVSHME_addLogAveonline(array(
                    "type" => 'cotizacion paralela',
                    "transportadora" => $idtransportador,
                    "send" => $body_send,
                    "data" => $data,
                ));
                if ($data && isset($data->status) && $data->status === "ok") {
                    if (isset($data->cotizaciones)) {
                        $cotizaciones = array_merge($cotizaciones, $data->cotizaciones);
                    }
                }

                curl_multi_remove_handle($multi, $ch);
                curl_close($ch);
            }

            curl_multi_close($multi);

            /**
             * Respuesta final unificada
             */
            return (object)[
                "status" => "ok",
                "message" => "cotizaciones encontradas",
                "cotizaciones" => $cotizaciones
            ];
        }

        public function cotisar($data = array())
        {
            $key_cache =  'cotisar_' . md5(json_encode($data));
            
            $cache = get_transient($key_cache);

            if ($cache !== false) {
                return $cache;
            }

            $json_body = array(
                "tipo"                  => "cotizarDoble",
                "access"                => "",
                "token"                 => $data["token"],
                "idempresa"             => $this->settings['select_cuenta'],
                "idagente"              => explode('_', $this->settings['select_agentes'])[0],
                "origen"                => explode('_', $this->settings['select_agentes'])[1],
                "destino"               => $data["destinos"],
                "idasumecosto"          => $data["idasumecosto"],
                "contraentrega"         => $data["contraentrega"],
                "contraentregaPayment"  => $data["contraentregaPayment"],
                "valorrecaudo"          => $data["valorrecaudo"],
                "productos"             => $data["productos"],
                "valorMinimo"           => ($this->settings['valorMinimo'] == "yes") ? 1 : 0,
                "plugin"                => "wordpress",
            );
            AVSHME_Validator()
                ->isArray(
                    AVSHME_Validator()
                        ->isObject([
                            "alto"              => AVSHME_Validator()->isRequired("El alto del producto debe ser mayor a 0")->isNumber("El alto del producto debe ser mayor a 0")->isMin(0, "El alto del producto debe ser mayor a 0"),
                            "largo"             => AVSHME_Validator()->isRequired("El largo del producto debe ser mayor a 0")->isNumber("El largo del producto debe ser mayor a 0")->isMin(0, "El largo del producto debe ser mayor a 0"),
                            "ancho"             => AVSHME_Validator()->isRequired("El ancho del producto debe ser mayor a 0")->isNumber("El ancho del producto debe ser mayor a 0")->isMin(0, "El ancho del producto debe ser mayor a 0"),
                            "peso"              => AVSHME_Validator()->isRequired("El peso del producto debe ser mayor a 0")->isNumber("El peso del producto debe ser mayor a 0")->isMin(0, "El peso del producto debe ser mayor a 0"),
                        ])
                )
                ->validate($data["productos"]);

            $transportadora = $this->transportadora();
            $ids_transportadora = [];

            foreach ($transportadora->transportadoras as $t) {
                $ids_transportadora[] = $t->id;
            }
            /**
             * $ids_transportadora example
             * [
                1,
                2,
                3,
                ...
            ]
             */

            //INFO: mas optimo que cotizar todas las transportadoras
            $result =  $this->cotizarParalelo($ids_transportadora, $json_body);
            set_transient($key_cache, $result, 60);

            return $result;
            $cotizacion =  $this->request(json_encode($json_body), $this->API_URL_QUOTE, $key_cache, true);
            /**
             * cotizacion example
             * {
                "status": "ok",
                "message": "cotizaciones encontradas",
                "cotizaciones": [
                    {
                        "numbererror": "-0-",
                        "dataerror": "",
                        "codTransportadora": "29",
                        "nombreTransportadora": "ENVIA",
                        "logoTransportadora": "https://app.aveonline.co/app/temas/imagen_transpo/084935-1-envia-094632-1-ENVIA.jpg",
                        "logoTransportadora2": "https://app.aveonline.co/app/temas/imagen_transpo/121748-2-envia.png",
                        "origen": "MEDELLIN(ANTIOQUIA)",
                        "destino": "MEDELLIN(ANTIOQUIA)",
                        "unidades": "1",
                        "kilos": 3,
                        "pesovolumen": 1,
                        "valoracion": "20000",
                        "porcentajeValoracion": "1",
                        "codigoTrayecto": "8",
                        "trayecto": "urbano",
                        "tipoEnvio": "Mensajeria",
                        "fletexkilo": 13488,
                        "fletexunidad": 13488,
                        "fletetotal": 13488,
                        "diasentrega": "1",
                        "costoManejo": 200,
                        "valorTotal": 13688,
                        "valorOtrosRecaudos": 0,
                        "total": 13688,
                        "contraentrega": false
                    },
                    ...
                ]
            }
             */
            return $cotizacion;
        }
        public function AVSHME_generate_guia($data, $order)
        {
            $order_id = $order->get_id();
            $productos = [];
            $dscontenido = "";
            $id_products_ignore = [];
            foreach ($order->get_items() as $item_id => $item) {
                $product_id         = $item->get_product_id();
                $id_products_ignore_by_product     = get_post_meta($product_id, '_product_group_exclude', true);
                $id_products_ignore_by_product = explode(",", "$id_products_ignore_by_product");
                $id_products_ignore = array_merge($id_products_ignore, $id_products_ignore_by_product);
            }
            foreach ($order->get_items() as $item_id => $item) {
                $product_id         = $item->get_product_id();
                $variation_id       = $item->get_variation_id();
                $subtotal           = $item->get_subtotal();
                $total              = $item->get_total();

                if ($variation_id != 0) {
                    $product_id = $variation_id;
                }
                if (in_array($product_id, $id_products_ignore)) {
                    continue;
                }

                $_product           = wc_get_product($product_id);

                $_valor_declarado     = get_post_meta($product_id, '_custom_valor_declarado', true);
                if (0 == floatval($_valor_declarado)) {
                    $_valor_declarado = $_product->get_price();
                }

                $discount =  $subtotal == 0 ? 1 : $total / $subtotal;

                $productos[] = array(
                    "alto"              => $_product->get_height(),
                    "largo"             => $_product->get_length(),
                    "ancho"             => $_product->get_width(),
                    "peso"              => $_product->get_weight(),
                    "unidades"          => $item->get_quantity(),
                    "nombre"            => $item->get_name(),
                    "ref"               => $_product->get_sku(),
                    "urlProducto"       => $_product->get_reviews_allowed(),
                    "valorDeclarado"    => floatval($_valor_declarado) * $discount
                );
                $dscontenido .= $item->get_name() . ",";
            }
            $telefono = $order->get_billing_phone();
            if (empty($telefono) || !is_string($telefono)) {
                $telefono = $order->get_shipping_phone();
            }
            if (empty($telefono) || !is_string($telefono)) {
                $telefono = get_user_meta($order->get_customer_id(), 'billing_phone', true);
            }
            $json_body = array(
                "tipo"              => "generarGuia2",
                "token"             => $this->get_token(),
                "idempresa"         => $this->settings['select_cuenta'],
                "codigo"            => "",
                "dsclavex"          => "",
                "plugin"            => "wordpress",

                "origen"            => explode('_', $this->settings['select_agentes'])[1],
                "dsdirre"           => $this->settings['dsdirre'],
                "dsbarrioo"         => "",

                "destino"           => $data['destinos'],
                "dsdir"             => $order->get_shipping_address_1() . " " . $order->get_shipping_address_2(),
                "dsbarrio"          => "",

                "dsnitre"           => $this->settings['dsnitre'],
                "dstelre"           => $this->settings['dstelre'],
                "dscelularre"       => $this->settings['dscelularre'],
                "dscorreopre"       => $this->settings['dscorreopre'],

                "dsnit"             => AVSHME_get_options($order_id, '_cedula'),
                "dsnombre"          => $order->get_shipping_first_name(),
                "dsnombrecompleto"  => $order->get_formatted_shipping_full_name(),
                "dscorreop"         => $order->get_billing_email(),
                "dstel"             => $telefono,
                "dscelular"         => $telefono,

                "idtransportador"   => $data['idtransportador'],

                "unidades"          => 1,
                "productos"         => $productos,

                "dscontenido"       => $dscontenido,
                "dscom"             => $order->get_customer_note(),

                "idasumecosto"      => $data['contraentrega'],
                "contraentrega"     => $data['contraentrega'],
                "valorrecaudo"      => $data['valorrecaudo'],

                "idagente"          => explode('_', $this->settings['select_agentes'])[0],

                "dsreferencia"      => "",
                "dsordendecompra"   => "",
                "bloquegenerarguia" => "1",
                "relacion_envios"   => "1",
                "enviarcorreos"     => "1",
                "guiahija"          => "",
                "accesoila"         => "",
                "cartaporte"        => "",
                "valorMinimo"       => ($this->settings['valorMinimo'] == "yes") ? 1 : 0,
                "envioGratis"       => $data['envioGratis'],
            );


            $json_body = json_encode($json_body);
            $r = $this->request($json_body, $this->API_URL_QUOTE);
            $json_S = '{
                "shop" : "' . get_bloginfo('name') . '",
                "send" : ' . $json_body . ',
                "respond" : ' . json_encode($r) . '
            }';
            // $this->request($json_S , $this->APY_URL_ST."guias");

            $json_order_products = array();
            foreach ($order->get_items() as $item_id => $item) {
                $product_id = $item->get_product_id();
                $name = $item->get_name();
                $quantity = $item->get_quantity();
                $subtotal = $item->get_subtotal();
                $total = $item->get_total();
                $json_order_products[] = array(
                    "product_id"    => $product_id,
                    "name"          => $name,
                    "quantity"      => $quantity,
                    "subtotal"      => $subtotal,
                    "total"         => $total,
                );
            }
            $json_order_products = json_encode($json_order_products);
            $json_order = '{
                "shop" : "' . get_bloginfo('name') . '",
                "order_id" : "' . $order_id . '",
                "view"  : "' . $order->get_view_order_url() . '",
                "status"  : "' . $order->get_status() . '",
                "user_id"  : "' . $order->get_user_id() . '",
                "billing_first_name"  : "' . $order->get_billing_first_name() . '" ,
                "billing_last_name"  : "' . $order->get_billing_last_name() . '",
                "billing_address_1"   : "' . $order->get_billing_address_1() . " " . $order->get_billing_address_2() . '",
                "billing_city"   : "' . $order->get_billing_city() . '",
                "billing_state"  : "' . $order->get_billing_state() . '",
                "billing_country"    : "' . $order->get_billing_country() . '",
                "billing_email"   : "' . $order->get_billing_email() . '",
                "billing_phone"   : "' . $order->get_billing_phone() . '",
                "shipping_method"  : "' . $order->get_shipping_method() . '",
                "total"  : "' . $order->get_total() . '",
                "discount_total"  : "' . $order->get_discount_total() . '",
                "products" : ' . $json_order_products . '
            }';
            // $this->request($json_order , $this->APY_URL_ST."ordenes");

            return $r;
        }
        public function generarRecogida($data)
        {
            $json_body = array(
                "tipo"              => "generarRecogida2",
                "token"             => $this->get_token(),
                "idempresa"         => $this->settings['select_cuenta'],
                "idagente"          => explode('_', $this->settings['select_agentes'])[0],
                "dscom"             => $data['dscom'],
                "guias"             => $data['guias']
            );
            $json_body = json_encode($json_body);

            $r = $this->request($json_body, $this->API_URL_QUOTE);

            $json_S = '{
                "shop" : "' . get_bloginfo('name') . '",
                "send" : ' . $json_body . ',
                "respond" : ' . json_encode($r) . '
            }';
            // $this->request($json_S , $this->APY_URL_ST."recogidas");

            return $r;
        }
        public function system_update_guia($data)
        {
            $json_body = '
            {
                "tipo" : "guardarPedidos",
                "ruta":"' .              plugin_dir_url(__FILE__) . $this->URL_UPDATE_GUIA . '",
                "guia":"' .              $data["numguia"] . '",
                "pedido_id":"' .         $data["order_id"] . '",
                "cliente_id" : "' .      $this->settings['select_cuenta'] . '",
                "transportadora_id": "' . $data["transportadora_id"] . '"
            }
            ';
            return $this->request($json_body, $this->API_URL_UPDATE_GUIA);
        }
        public function relacionEnvios($data)
        {
            $json_body = '
            {
                "tipo" : "relacionEnvios",
                "token":"' .                 $this->get_token() . '",
                "idempresa":"' .             $this->settings['select_cuenta'] . '",
                "transportadora":"' .        $data["transportadora"] . '",
                "guias" : "' .               $data['guias'] . '"
            }
            ';
            $r = $this->request($json_body, $this->API_URL_QUOTE);
            $json_S = '{
                "shop" : "' . get_bloginfo('name') . '",
                "send" : ' . $json_body . ',
                "respond" : ' . json_encode($r) . '
            }';
            // $this->request($json_S , $this->APY_URL_ST."relaciones");
            return $r;
        }
    }
}
load_AveonlineAPI();

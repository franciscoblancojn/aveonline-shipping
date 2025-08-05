<?php
/**
 * @package WoocommerceAveOnlineShipping
 */
/*
Plugin Name: Aveonline Shipping
Plugin URI: https://github.com/franciscoblancojn/aveonline-shipping
Description: Integración de woocommerce con los servicios de envío de Aveonline.
Version: 3.1.5
Author: franciscoblancojn
Author URI: https://franciscoblanco.vercel.app/
License: GPL2+
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wc-aveonline-shipping
*/

if (!function_exists( 'is_plugin_active' ))
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

//AVSHME_
define("AVSHME_KEY",'AVSHME');
define("AVSHME_LOG",true);
define("AVSHME_BASENAME",plugin_basename(__FILE__));
define("AVSHME_DIR",plugin_dir_path( __FILE__ ));
define("AVSHME_URL",plugin_dir_url(__FILE__));

require_once AVSHME_DIR . 'update.php';
github_updater_plugin_wordpress([
    'basename'=>AVSHME_BASENAME,
    'dir'=>AVSHME_DIR,
    'file'=>"index.php",
    'path_repository'=>'franciscoblancojn/aveonline-shipping',
    'branch'=>'master',
    'token_array_split'=>[
        "g",
        "h",
        "p",
        "_",
        "G",
        "4",
        "W",
        "E",
        "W",
        "F",
        "p",
        "V",
        "U",
        "E",
        "F",
        "V",
        "x",
        "F",
        "U",
        "n",
        "b",
        "M",
        "k",
        "P",
        "R",
        "x",
        "o",
        "f",
        "t",
        "Y",
        "8",
        "z",
        "j",
        "t",
        "4",
        "E",
        "x",
        "b",
        "i",
        "9"
    ]
]);

if ( is_callable('curl_init') && 
	function_exists('curl_init') && 
	function_exists('curl_close') && 
	function_exists('curl_exec') && 
	function_exists('curl_setopt_array')
){
    if(
        is_plugin_active( 'departamentos-y-ciudades-de-colombia-para-woocommerce/departamentos-y-ciudades-de-colombia-para-woocommerce.php' ) || 
        is_plugin_active( 'wc-departamentos-y-ciudades-colombia/main.php' ) ||
        is_plugin_active( 'departamentos-y-ciudades-colombia/main.php' ) || 
        is_plugin_active( 'wc-departamentos-y-ciudades-colombia/departamentos-y-ciudades-de-colombia-para-woocommerce.php' ) || 
        is_plugin_active( 'departamentos-y-ciudades-colombia/departamentos-y-ciudades-de-colombia-para-woocommerce.php' ) 
     ){
        function AVSHME_log_dycc() {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                Aveonline Shipping no es compatible con "Departamentos y ciudades colombia", desactivelo para el funcionamiento de Aveonline
                </p>
            </div>
            <?php
        }
        add_action( 'admin_notices', 'AVSHME_log_dycc' );
    }else{
        function AVSHME_get_version() {
            $plugin_data = get_plugin_data( __FILE__ );
            $plugin_version = $plugin_data['Version'];
            return $plugin_version;
        }
        require_once plugin_dir_path( __FILE__ ) . 'departamentos-y-ciudades-de-colombia-para-woocommerce/departamentos-y-ciudades-de-colombia-para-woocommerce.php';
        require_once plugin_dir_path( __FILE__ ) . 'src/validator/index.php';
        require_once plugin_dir_path( __FILE__ ) . 'src/includes/class-admin.php';
    }
}else{
    function AVSHME_log_dependencia() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
            Aveonline Shipping requiere "Curl"
            </p>
        </div>
        <?php
    }
    add_action( 'admin_notices', 'AVSHME_log_dependencia' );
}


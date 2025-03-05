<?php
if(!function_exists("github_updater_plugin_wordpress")){
    function github_updater_plugin_wordpress($config)  {
        if(is_admin()){
            function github_updater($transient, $config) {
                if (empty($transient->checked)) {
                    return $transient;
                }
        
                // Definir constantes
                $plugin_slug =  basename(rtrim($config['dir'], '/'));
                $plugin_file_php = $config['file'];
                $plugin_file = $plugin_slug . '/'.$plugin_file_php;
                $github_api_url = 'https://api.github.com/repos/'.$config['path_repository'].'/releases/latest';
                
                // ⚠️ Asegúrate de almacenar el token de manera segura
                $github_token = join('',$config['token_array_split']);
        
                // Llamada a la API de GitHub
                $response = wp_remote_get($github_api_url, [
                    'headers' => [
                        'User-Agent'    => 'WordPress-Updater',
                        'Authorization' => 'token ' . $github_token,
                    ]
                ]);
                
                if (is_wp_error($response)) {
                    return $transient;
                }
        
                $release = json_decode(wp_remote_retrieve_body($response));
        
                if (!isset($release->tag_name)) {
                    return $transient;
                }
        
                $latest_version = ltrim($release->tag_name, 'v');
        
                // Obtener la versión actual del plugin
                if (!function_exists('get_plugin_data')) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                }
        
                $plugin_path = $config['dir'] . $plugin_file_php;
                $plugin_data = get_plugin_data($plugin_path);
                $current_version = $plugin_data['Version'];
        
                // Comparar versiones
                if (version_compare($current_version, $latest_version, '<')) {
                    $transient->response[$plugin_file] = (object) [
                        'new_version' => $latest_version,
                        'package'     => "https://github.com/".$config['path_repository']."/archive/refs/heads/".$config['branch'].".zip",
                        'slug'        => $plugin_slug,
                        'url'         => 'https://github.com/'.$config['path_repository'],
                    ];
                }
        
                return $transient;
            }
            add_filter('site_transient_update_plugins', function($transient) use ($config) {
                return github_updater($transient, $config);
            });
        
            /**
             * Agregar botón de actualización manual al listado de plugins
             */
            function github_updater_btn($links, $file,$config) {
                if ($file == $config['basename']) {
                    $actualizar_url = wp_nonce_url(
                        admin_url('update.php?action=upgrade-plugin&plugin=' . $file),
                        'upgrade-plugin_' . $file
                    );
        
                    $links[] = '<a href="' . esc_url($actualizar_url) . '" style="color: #0073aa; font-weight: bold;">Actualizar</a>';
                }
                return $links;
            }
            add_filter('plugin_action_links_' . $config['basename'],function($links, $file) use ($config) {
                return github_updater_btn($links, $file,$config);
            }, 10, 2);
        
            // Forzar actualización de plugins
            delete_site_transient('update_plugins');
        }
    }
}

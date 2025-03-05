<?php
if(AVSHME_LOG){
    function add_AVSHME_logAveonline_aveonline_option_page($admin_bar)
    {
        global $pagenow;
        $admin_bar->add_menu(
            array(
                'id' => 'logAveonline_aveonline',
                'title' => 'logAveonline',
                'href' => get_site_url().'/wp-admin/options-general.php?page=logAveonline_aveonline'
            )
        );
    }

    function AVSHME_logAveonline_aveonline_option_page()
    {
        add_options_page(
            'Log Aveonline',
            'Log Aveonline',
            'manage_options',
            'logAveonline_aveonline',
            'AVSHME_logAveonline_aveonline_page'
        );
    }

    function AVSHME_logAveonline_aveonline_page()
    {
        if($_POST['clear-log'] == "1"){
            update_option("AVSHME_log","[]");
        }
        ?>
        <form method="post">
            <input type="hidden" name="clear-log" value="1">
            <button style="
                position: fixed;
                bottom: 1rem;
                right: 1rem;
                font-size: 2rem;
                padding: .25rem 1.5rem;
                background: #1d2327;
                border: 0;
                border-radius: .35rem;
                color: #f0f0f1;
            ">Borrar Log</button>
        </form>
        <h1>
            Solo se guardan las 100 peticiones
        </h1>
        <script>
            const json_log = <?=get_option("AVSHME_log")?>;
        </script>
        <pre>
            <?php var_dump(array_reverse(json_decode(get_option("AVSHME_log"))));?>
        </pre>
        <?php
    }
    add_action('admin_bar_menu', 'add_AVSHME_logAveonline_aveonline_option_page', 100);

    add_action('admin_menu', 'AVSHME_logAveonline_aveonline_option_page');
}

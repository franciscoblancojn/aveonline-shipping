<?php
if (AVSHME_LOG) {
    function add_AVSHME_logAveonline_aveonline_option_page($admin_bar)
    {
        global $pagenow;
        $admin_bar->add_menu(
            array(
                'id' => 'logAveonline_aveonline',
                'title' => 'logAveonline',
                'href' => get_site_url() . '/wp-admin/options-general.php?page=logAveonline_aveonline'
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
        if ($_POST['clear-log'] == "1") {
            update_option("AVSHME_log", "[]");
        }
        $log = AVSHME_getLogAveonline();
        // var_dump(AVSHME_getCache("token"));
?>
        <form method="post">
            <input type="hidden" name="clear-log" value="1">
            <button style="
                cursor: pointer;
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
            Solo se guardan las <?= AVSHME_LOG_COUNT ?> peticiones
        </h1>
        <script>
            const json_log = <?= json_encode($log) ?>;
        </script>
        <?php
        foreach ($log as $key => $value) {
        ?>
            <details>
                <summary><?= $key ?></summary>
                <div>
                    <?php
                    for ($i = 0; $i < count($value); $i++) {

                    ?>
                        <pre>
                            <?php var_dump(array_reverse($value)); ?>
                        </pre>
                    <?php
                    }
                    ?>
                </div>
            </details>
        <?php
        }
    }
    add_action('admin_bar_menu', 'add_AVSHME_logAveonline_aveonline_option_page', 100);

    add_action('admin_menu', 'AVSHME_logAveonline_aveonline_option_page');
}

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
        try {
            if ($_POST['clear-log'] == "1") {
                update_option("AVSHME_log", "[]");
            }
            $log = AVSHME_getLogAveonline();
            // var_dump(AVSHME_getCache("token"));
            ?>
            <form method="post">
                <button style="
                    cursor: pointer;
                    position: fixed;
                    top: 3rem;
                    right: 1rem;
                    font-size: 2rem;
                    padding: .25rem 1.5rem;
                    background: #1d2327;
                    border: 0;
                    border-radius: .35rem;
                    color: #f0f0f1;
                    z-index:1000;
                ">Recargar</button>
            </form>
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
                    z-index:1000;
                ">Borrar Log</button>
            </form>
            <h1>
                Solo se guardan las <?= AVSHME_LOG_COUNT ?> peticiones por tipo
            </h1>
            <script>

                function copyJson(id) {
                    const element = document.getElementById(id);
                    const text = element.innerText;

                    // Crear textarea temporal
                    const textarea = document.createElement("textarea");
                    textarea.value = text;
                    document.body.appendChild(textarea);

                    textarea.select();
                    textarea.setSelectionRange(0, 999999); // Para mobile

                    try {
                        document.execCommand("copy");
                        showCopiedMessage(id);
                    } catch (err) {
                        console.error("Error al copiar", err);
                    }

                    document.body.removeChild(textarea);
                }

                function showCopiedMessage(id) {
                    const btn = document.querySelector(`[onclick="copyJson('${id}')"]`);
                    if (!btn) return;

                    const original = btn.innerText;
                    btn.innerText = "Copiado ✅";

                    setTimeout(() => {
                        btn.innerText = original;
                    }, 1500);
                }
                const json_log = <?= wp_json_encode($log) ?>;
            </script>
            <style>
                /* Contenedor general */
                details {
                    margin-bottom: 1rem;
                    border: 1px solid #dcdcde;
                    border-radius: 8px;
                    background: #fff;
                    overflow: hidden;
                }

                /* Header tipo collapse */
                details summary {
                    cursor: pointer;
                    padding: 12px 16px;
                    font-weight: 600;
                    font-size: 14px;
                    background: #f6f7f7;
                    list-style: none;
                    position: relative;
                    transition: background 0.2s ease;
                }

                /* Hover */
                details summary:hover {
                    background: #e5e5e5;
                }

                /* Quitar flecha default */
                details summary::-webkit-details-marker {
                    display: none;
                }

                /* Flecha custom */
                details summary::after {
                    content: "▸";
                    position: absolute;
                    right: 16px;
                    font-size: 14px;
                    transition: transform 0.2s ease;
                }

                /* Rotar cuando está abierto */
                details[open] summary::after {
                    transform: rotate(90deg);
                }

                /* Contenido interno */
                details > div {
                    padding: 16px;
                    background: #ffffff;
                    border-top: 1px solid #dcdcde;
                }
            </style>
            <?php
            foreach ($log as $key => $value) {
            ?>
                <details>
                    <summary style="display: flex;">
                        <span><?= $key ?> </span>
                        <span style="margin-left: auto; margin-right:1rem">(<?= count($value) ?>)</span>
                    </summary>
                    <div>
                        <?php
                        for ($i = 0; $i < count($value); $i++) {
                            $print = wp_json_encode(
                                    array_reverse($value[$i]),
                                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                            );

                            $id = 'json_' . $key . '_' . $i;
                        ?>
                        <div style="position:relative;margin-bottom:1rem;">
                            
                            <button 
                                type="button"
                                onclick="copyJson('<?= $id ?>')"
                                style="
                                    position:absolute;
                                    top:.5rem;
                                    right:.5rem;
                                    cursor:pointer;
                                    background:#00ff88;
                                    border:0;
                                    padding:.25rem .75rem;
                                    border-radius:.35rem;
                                    font-weight:bold;
                                "
                            >
                                Copiar
                            </button>

                            <pre 
                                id="<?= $id ?>"
                                style="background:#1d2327;color:#00ff88;padding:1rem;border-radius:.5rem;overflow:auto;"
                            ><?= esc_html($print) ?></pre>

                        </div>
                        <?php
                        }
                        ?>
                    </div>
                </details>
            <?php
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
    add_action('admin_bar_menu', 'add_AVSHME_logAveonline_aveonline_option_page', 100);

    add_action('admin_menu', 'AVSHME_logAveonline_aveonline_option_page');
}

<?php

function AVSHME_add_JS_CSS_footer()
{
    //return;
    if (is_checkout()) {
        wp_enqueue_script('jquery');
?>
        <script>
            const billing_address_1 = document.getElementById("billing_address_1")
            jQuery(function($) {
                jQuery('form.checkout').on('change', 'input[name="payment_method"]', function() {
                    console.log("change payment_method");
                    if (billing_address_1) {
                        const v = billing_address_1.value
						jQuery(document.body).one('updated_checkout', function () {
							if(billing_address_1.value == "<?= AVSHME_KEY ?>"){
								billing_address_1.value = v;
								jQuery(document.body).trigger('update_checkout');
							}
						});
                        billing_address_1.value = "<?= AVSHME_KEY ?>";
                        jQuery(document.body).trigger('update_checkout');
                    }
                });
            });
            window.addEventListener('load', function() {
                console.log("load");

                jQuery(document.body).trigger('update_checkout');

            }, false);

            jQuery(function($) {

                function aveonlineUpdateCheckout() {
                    $('body').trigger('update_checkout');
                }

                // esperar a que WooCommerce cargue
                setTimeout(aveonlineUpdateCheckout, 500);

            });
        </script>
<?php
    }
}
add_action('wp_footer', 'AVSHME_add_JS_CSS_footer');

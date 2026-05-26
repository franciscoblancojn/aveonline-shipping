<?php

function AVSHME_is_shortcode_checkout()
{
    // Ignorar admin
    if (is_admin()) {
        return false;
    }

    // Ignorar REST API
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return false;
    }

    // Ignorar AJAX
    if (wp_doing_ajax()) {
        return false;
    }

    global $post;

    if (!$post || empty($post->post_content)) {
        return false;
    }

    return has_shortcode($post->post_content, 'woocommerce_checkout');
}

function AVSHME_add_JS_CSS_footer()
{
    if (!AVSHME_is_shortcode_checkout()) return;
    if (is_checkout()) {
        wp_enqueue_script('jquery');
        wp_enqueue_style(
            'wc-aveonline-contraentrega',
            AVSHME_URL . 'src/css/style_contraentrega.css',
            [],
            AVSHME_get_version()
        );
?>
        <script>
            const billing_address_1 = document.getElementById("billing_address_1")
            let _aveonline_payment_changing = false;
            let _aveonline_block_timer = null;

            function _aveonline_maybe_block() {
                if (_aveonline_payment_changing) {
                    jQuery('#order_review').block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                    jQuery('[name="woocommerce_checkout_place_order"]').prop('disabled', true);
                    clearTimeout(_aveonline_block_timer);
                    _aveonline_block_timer = setTimeout(function() {
                        _aveonline_payment_changing = false;
                        jQuery('#order_review').unblock();
                        jQuery('[name="woocommerce_checkout_place_order"]').prop('disabled', false);
                    }, 8000);
                }
            }

            jQuery(function($) {
                // Cedula client-side validation for classic checkout
                $('form.checkout').on('checkout_place_order', function() {
                    var cedulaInput = document.getElementById('cedula');
                    if (!cedulaInput) return true;
                    var val = cedulaInput.value.trim();
                    var error = null;
                    if (!val) {
                        error = 'Por favor ingrese la cédula';
                    } else if (!/^\d+$/.test(val)) {
                        error = 'La cédula debe ser numérica';
                    } else if (val.length < 6) {
                        error = 'La cédula debe tener al menos 6 dígitos';
                    } else if (parseInt(val, 10) <= 0) {
                        error = 'La cédula debe ser mayor a 0';
                    }
                    if (error) {
                        var $form = $('form.checkout');
                        $form.find('.woocommerce-NoticeGroup-checkout').remove();
                        $form.prepend(
                            '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' +
                            '<ul class="woocommerce-error" role="alert"><li>' + error + '</li></ul></div>'
                        );
                        $('html, body').animate({ scrollTop: $form.offset().top - 100 }, 400);
                        return false;
                    }
                    return true;
                });
            });

            function updateHiddenPaymentMethod(id) {
                var field = document.getElementById('ave_id_payment_method');
                if (field) field.value = id || '';
            }

            jQuery(function($) {
                var _selectedPayment = document.querySelector('input[name="payment_method"]:checked');
                if (_selectedPayment) {
                    updateHiddenPaymentMethod(_selectedPayment.value);
                    if (_selectedPayment.id === 'payment_method_contraentrega') {
                        document.body.classList.add('wc_contraentrega_on');
                    }
                }

                $('form.checkout').on('change', 'input[name="payment_method"]', function() {
                    
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

                $(document.body).on('updated_checkout', function() {
                    var pm = document.querySelector('input[name="payment_method"]:checked');
                    if (pm) updateHiddenPaymentMethod(pm.value);

                    if (_aveonline_payment_changing) {
                        var suffix = document.body.classList.contains('wc_contraentrega_on')
                            ? 'wc_contraentrega_on'
                            : 'wc_contraentrega_off';
                        var elements = document.querySelectorAll('[id*="' + suffix + '"]');
                        var hasVisible = Array.prototype.some.call(elements, function(el) {
                            return getComputedStyle(el).display !== 'none';
                        });
                        if (hasVisible) {
                            _aveonline_payment_changing = false;
                            clearTimeout(_aveonline_block_timer);
                            $('#order_review').unblock();
                            $('[name="woocommerce_checkout_place_order"]').prop('disabled', false);
                        }
                    }
                });
            });

            window.addEventListener('load', function() {
                jQuery(document.body).trigger('update_checkout');
            }, false);
        </script>
<?php
    }
}
add_action('wp_footer', 'AVSHME_add_JS_CSS_footer');

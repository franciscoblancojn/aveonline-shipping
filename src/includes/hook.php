<?php

function AVSHME_is_shortcode_checkout()
{
    if (!is_checkout()) return false;
    $checkout_page_id = wc_get_page_id('checkout');
    if (!$checkout_page_id) return true;
    $post = get_post($checkout_page_id);
    if (!$post) return true;
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

            jQuery(function($) {
                var _selectedPayment = document.querySelector('input[name="payment_method"]:checked');
                if (_selectedPayment && _selectedPayment.id === 'payment_method_contraentrega') {
                    document.body.classList.add('wc_contraentrega_on');
                }

                $('form.checkout').on('change', 'input[name="payment_method"]', function() {
                    var isContraentrega = this.id === 'payment_method_contraentrega';
                    var wasContraentrega = document.body.classList.contains('wc_contraentrega_on');

                    if (isContraentrega) {
                        document.body.classList.add('wc_contraentrega_on');
                    } else {
                        document.body.classList.remove('wc_contraentrega_on');
                    }

                    if (wasContraentrega !== isContraentrega) {
                        _aveonline_payment_changing = true;
                        _aveonline_maybe_block();
                    }

                    if (billing_address_1) {
                        const v = billing_address_1.value
                        $(document.body).one('updated_checkout', function () {
                            if(billing_address_1.value == "<?= AVSHME_KEY ?>"){
                                billing_address_1.value = v;
                                $(document.body).trigger('update_checkout');
                            }
                        });
                        billing_address_1.value = "<?= AVSHME_KEY ?>";
                        $(document.body).trigger('update_checkout');
                    }
                });

                $(document.body).on('updated_checkout', function() {
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

            jQuery(function($) {
                function aveonlineUpdateCheckout() {
                    $('body').trigger('update_checkout');
                }
                setTimeout(aveonlineUpdateCheckout, 500);
            });
        </script>
<?php
    }
}
add_action('wp_footer', 'AVSHME_add_JS_CSS_footer');

(function () {
    'use strict';

    if (typeof avshme_cedula_params === 'undefined') return;

    var label = avshme_cedula_params.label || 'Cedula';
    var required = !!avshme_cedula_params.required;
    var useNative = !!avshme_cedula_params.use_native;

    /* helpers ------------------------------------------------------------ */
    function getCedulaValue() {
        var el = document.getElementById('avshme_cedula_input');
        if (el) return el.value;
        var nativeEl = document.querySelector('[name="billing-cedula"], [name="cedula"]');
        if (nativeEl) return nativeEl.value;
        return '';
    }

    function createCedulaHTML(value) {
        var req = required ? ' required' : '';
        var star = required ? ' <span class="required" aria-hidden="true">*</span>' : '';
        return '<div class="wc-block-checkout__form-field avshme-cedula-field" style="margin-top:1em;padding:0 1em">' +
            '<label class="wc-block-checkout__label" for="avshme_cedula_input">' +
            label + star +
            '</label>' +
            '<input type="text" id="avshme_cedula_input" name="cedula" value="' +
            (value || '') +
            '" autocomplete="off" aria-required="' + (required ? 'true' : 'false') + '"' +
            ' style="width:100%;padding:0.5em 0.75em;font-size:inherit;font-family:inherit;' +
            'border:1px solid #767676;border-radius:4px;background:#fff;color:inherit;box-sizing:border-box"' +
            req + ' />' +
            '</div>';
    }

    function injectCedula() {
        if (useNative) return;
        if (document.getElementById('avshme_cedula_input')) return;

        // Try billing section first, then additional info
        var targets = [
            '.wc-block-checkout__billing-fields',
            '.wp-block-woocommerce-checkout-billing-fields-block',
            '.wc-block-components-checkout-step--billing',
            '.wc-block-checkout__additional_fields',
            '.wp-block-woocommerce-checkout-additional-fields-block',
            '.wc-block-components-checkout-step--additional',
            '.wc-block-checkout__form',
        ];

        var container = null;
        for (var i = 0; i < targets.length; i++) {
            container = document.querySelector(targets[i]);
            if (container) break;
        }

        if (!container) return;

        container.insertAdjacentHTML('beforeend', createCedulaHTML(''));
    }

    /* intercept Store API checkout request -------------------------------- */
    var originalFetch = window.fetch;
    if (originalFetch) {
        window.fetch = function (input, init) {
            var url = typeof input === 'string' ? input : (input && input.url ? input.url : '');
            if (url.indexOf('/wc/store/v1/checkout') !== -1) {
                if (init && init.body && typeof init.body === 'string') {
                    try {
                        var body = JSON.parse(init.body);
                        if (!body.additional_fields) {
                            body.additional_fields = {};
                        }
                        var val = getCedulaValue();
                        if (val) {
                            body.additional_fields.cedula = val;
                        }
                        init.body = JSON.stringify(body);
                    } catch (e) { }
                }
            }
            return originalFetch.call(this, input, init);
        };
    }

    /* Initialization ------------------------------------------------------ */
    function tryInit() {
        var checkoutEl = document.querySelector(
            '.wp-block-woocommerce-checkout, .wc-block-checkout__form, .wc-block-checkout'
        );
        if (!checkoutEl) return;
        injectCedula();
    }

    var MO = new MutationObserver(function () {
        injectCedula();
    });
    MO.observe(document.body, { childList: true, subtree: true });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', tryInit);
    } else {
        tryInit();
    }

    var retries = 0;
    var maxRetries = 40;
    var retryInterval = setInterval(function () {
        retries++;
        tryInit();
        if (retries >= maxRetries) clearInterval(retryInterval);
    }, 1000);

})();

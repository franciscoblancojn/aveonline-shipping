(function () {
    'use strict';

    var registerPaymentMethod = window.wc && window.wc.wcBlocksRegistry && window.wc.wcBlocksRegistry.registerPaymentMethod;
    if (!registerPaymentMethod) return;

    var getSetting = window.wc && window.wc.wcSettings && window.wc.wcSettings.getSetting;
    var createElement = window.wp && window.wp.element && window.wp.element.createElement;
    if (!getSetting || !createElement) return;

    var decodeEntities = (window.wp && window.wp.htmlEntities && window.wp.htmlEntities.decodeEntities)
        || function (str) { return str; };

    var settings = getSetting('contraentrega_data', {});
    var title = decodeEntities(settings.title || 'Contraentrega Aveonline');
    var description = decodeEntities(settings.description || '');

    var Content = function () {
        return description
            ? createElement('p', { style: { margin: 0 } }, description)
            : null;
    };

    registerPaymentMethod({
        name: 'contraentrega',
        label: title,
        content: createElement(Content, null),
        edit: createElement(Content, null),
        canMakePayment: function () { return true; },
        ariaLabel: title,
        supports: {
            features: ['products'],
        },
    });

    // Payment/shipping mismatch validation via fetch interceptor
    (function () {
        var originalFetch = window.fetch;
        if (!originalFetch) return;

        function getSelectedShippingRateId() {
            try {
                var cartStore = window.wp && window.wp.data && window.wp.data.select('wc/store/cart');
                if (cartStore && typeof cartStore.getShippingRates === 'function') {
                    var packages = cartStore.getShippingRates();
                    if (packages && packages.length > 0) {
                        var rates = packages[0].shipping_rates || [];
                        for (var i = 0; i < rates.length; i++) {
                            if (rates[i].selected) return rates[i].rate_id;
                        }
                    }
                }
            } catch (e) {}
            return '';
        }

        function getPaymentMethodLabel(methodId) {
            try {
                var registry = window.wc && window.wc.wcBlocksRegistry;
                if (registry && typeof registry.getPaymentMethods === 'function') {
                    var methods = registry.getPaymentMethods();
                    var m = methods && methods[methodId];
                    if (m) {
                        if (typeof m.label === 'string') return m.label;
                        if (typeof m.ariaLabel === 'string') return m.ariaLabel;
                    }
                }
            } catch (e) {}
            return methodId;
        }

        window.fetch = function (input, init) {
            var url = typeof input === 'string' ? input : (input && input.url ? input.url : '');
            if (url.indexOf('/wc/store/v1/checkout') !== -1 && init && init.body) {
                try {
                    var body = JSON.parse(init.body);
                    var paymentMethod = body.payment_method || '';
                    var isContraentregaPayment = paymentMethod === 'contraentrega';
                    var rateId = getSelectedShippingRateId();
                    var isContraentregaShipping = rateId.indexOf('wc_contraentrega_on') !== -1;

                    if (rateId && isContraentregaPayment !== isContraentregaShipping) {
                        var errorMsg;
                        if (isContraentregaShipping && !isContraentregaPayment) {
                            errorMsg = 'No puedes seleccionar envío contraentrega con pago "' + getPaymentMethodLabel(paymentMethod) + '"';
                        } else {
                            errorMsg = 'No puedes seleccionar envío normal con pago "' + title + '"';
                        }
                        return Promise.resolve(new Response(
                            JSON.stringify({ code: 'payment_shipping_mismatch', message: errorMsg, data: { status: 400 } }),
                            { status: 400, headers: { 'Content-Type': 'application/json' } }
                        ));
                    }
                } catch (e) {}
            }
            return originalFetch.call(this, input, init);
        };
    })();

    // Shipping loader when switching between contraentrega and other payment methods
    (function () {
        if (!window.wp || !window.wp.data) return;

        var OVERLAY_ID = 'avshme-shipping-loader';
        var LOCK_ID = 'avshme-checkout-lock';
        var loaderTimeout = null;
        var lastPayment = null;
        var loaderMO = null;

        var styleEl = document.createElement('style');
        styleEl.textContent =
            '@keyframes avshme-spin{to{transform:rotate(360deg)}}' +
            '#' + OVERLAY_ID + '{' +
                'position:absolute;inset:0;background:rgba(255,255,255,0.78);' +
                'z-index:50;display:flex;align-items:center;justify-content:center;' +
                'pointer-events:all;border-radius:inherit' +
            '}' +
            '#' + OVERLAY_ID + ' .avshme-spinner{' +
                'display:block;width:26px;height:26px;' +
                'border:3px solid #ddd;border-top-color:#555;border-radius:50%;' +
                'animation:avshme-spin 0.7s linear infinite' +
            '}' +
            '#' + LOCK_ID + '{' +
                'position:absolute;inset:0;z-index:49;cursor:wait' +
            '}';
        document.head.appendChild(styleEl);

        function getShippingSection() {
            return document.querySelector(
                '[data-block-name="woocommerce/checkout-shipping-methods-block"],' +
                '.wc-block-checkout__shipping-option,' +
                '.wc-block-components-shipping-rates-control'
            );
        }

        function getCheckoutForm() {
            return document.querySelector(
                '.wp-block-woocommerce-checkout,' +
                '.wc-block-checkout__form,' +
                '.wc-block-components-checkout-form'
            );
        }

        function getSubmitButton() {
            return document.querySelector(
                '.wc-block-components-checkout-place-order-button button,' +
                'button.wc-block-components-button[type="submit"],' +
                '.wc-block-checkout__actions button[type="submit"]'
            );
        }

        function showLoader() {
            if (document.getElementById(OVERLAY_ID)) return;

            var section = getShippingSection();
            if (!section) return;

            // Spinner overlay on shipping section
            if (getComputedStyle(section).position === 'static') section.style.position = 'relative';
            var overlay = document.createElement('div');
            overlay.id = OVERLAY_ID;
            overlay.innerHTML = '<span class="avshme-spinner"></span>';
            section.appendChild(overlay);

            // Transparent lock overlay on entire checkout form to block submit button
            var form = getCheckoutForm();
            if (form) {
                if (getComputedStyle(form).position === 'static') form.style.position = 'relative';
                var lock = document.createElement('div');
                lock.id = LOCK_ID;
                form.appendChild(lock);
            }

            // Also disable submit button directly as belt-and-suspenders
            var btn = getSubmitButton();
            if (btn) btn.disabled = true;

            // Use MutationObserver on the INNER rates container to detect when
            // new shipping options actually appear in the DOM.
            // Deferred via setTimeout so our own overlay append doesn't trigger it.
            setTimeout(function () {
                if (!document.getElementById(OVERLAY_ID)) return;

                var ratesInner = section.querySelector(
                    '.wc-block-components-radio-control,' +
                    '[role="radiogroup"]'
                ) || section;

                if (loaderMO) loaderMO.disconnect();
                loaderMO = new MutationObserver(function (mutations) {
                    for (var i = 0; i < mutations.length; i++) {
                        var nodes = mutations[i].addedNodes;
                        for (var j = 0; j < nodes.length; j++) {
                            // Ignore our own overlays
                            if (nodes[j].id === OVERLAY_ID || nodes[j].id === LOCK_ID) continue;
                            // New shipping content detected — hide after a brief render delay
                            setTimeout(hideLoader, 150);
                            loaderMO.disconnect();
                            loaderMO = null;
                            return;
                        }
                    }
                });
                loaderMO.observe(ratesInner, { childList: true, subtree: true });
            }, 0);

            clearTimeout(loaderTimeout);
            loaderTimeout = setTimeout(hideLoader, 10000);
        }

        function hideLoader() {
            clearTimeout(loaderTimeout);
            if (loaderMO) { loaderMO.disconnect(); loaderMO = null; }

            var overlay = document.getElementById(OVERLAY_ID);
            if (overlay && overlay.parentNode) overlay.parentNode.removeChild(overlay);

            var lock = document.getElementById(LOCK_ID);
            if (lock && lock.parentNode) lock.parentNode.removeChild(lock);

            var btn = getSubmitButton();
            if (btn) btn.disabled = false;
        }

        var debounceTimer = null;
        window.wp.data.subscribe(function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                try {
                    var payStore = window.wp.data.select('wc/store/payment');
                    if (!payStore || typeof payStore.getActivePaymentMethod !== 'function') return;

                    var active = payStore.getActivePaymentMethod();

                    if (lastPayment === null) {
                        lastPayment = active;
                        return;
                    }

                    if (active !== lastPayment) {
                        var prevContra = lastPayment === 'contraentrega';
                        var nowContra = active === 'contraentrega';
                        if (prevContra !== nowContra) {
                            showLoader();
                        }
                        lastPayment = active;
                    }
                } catch (e) { }
            }, 50);
        });
    })();

})();

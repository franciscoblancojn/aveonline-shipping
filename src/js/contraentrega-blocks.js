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

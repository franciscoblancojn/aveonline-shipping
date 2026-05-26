(function () {
    'use strict';

    if (typeof wc_city_select_params === 'undefined') return;

    var cities = {};
    try {
        cities = JSON.parse(wc_city_select_params.cities.replace(/&quot;/g, '"'));
    } catch (e) { return; }

    var i18nSelect = wc_city_select_params.i18n_select_city_text || 'Select an option\u2026';

    /* helpers ------------------------------------------------------------ */
    function isBlockCheckout() {
        return !!document.querySelector(
            '.wp-block-woocommerce-checkout, .wc-block-checkout__form, .wc-block-checkout, .wc-block-components-checkout-form'
        );
    }

    function getCities(country, state) {
        if (!country || !cities[country]) return null;
        var data = cities[country];
        if (Array.isArray(data)) return data;
        if (state && data[state]) return data[state];
        return null;
    }

    function findDedicatedField(prefix, type) {
        var underscore = prefix + '_' + type;
        var hyphen = prefix + '-' + type;
        return document.getElementById(underscore)
            || document.getElementById(hyphen)
            || document.querySelector('[name="' + underscore + '"]')
            || document.querySelector('[name="' + hyphen + '"]');
    }

    function findCityInput() {
        var el = findDedicatedField('shipping', 'city');
        if (el) return el;
        el = document.querySelector('[autocomplete="address-level2"]');
        if (el) return el;
        el = document.querySelector('[data-autocomplete="address-level2"]');
        if (el) return el;
        return null;
    }

    function getCitySelect() {
        return document.getElementById('avshme_city_select');
    }

    function getCityValue() {
        var sel = getCitySelect();
        if (sel) return sel.value;
        var input = findCityInput();
        return input ? input.value : '';
    }

    /* fetch interception: inject city into Store API request body ---------- */
    var originalFetch = window.fetch;
    if (originalFetch) {
        window.fetch = function (input, init) {
            var url = typeof input === 'string' ? input : (input && input.url ? input.url : '');
            var isCheckout = url.indexOf('/wc/store/v1/checkout') !== -1;
            var isUpdateCustomer = url.indexOf('/wc/store/v1/cart/update-customer') !== -1;
            if (isCheckout || isUpdateCustomer) {
                if (init && init.body && typeof init.body === 'string') {
                    try {
                        var body = JSON.parse(init.body);
                        var city = getCityValue();
                        if (city) {
                            if (body.shipping_address) {
                                body.shipping_address.city = city;
                            }
                            if (body.billing_address) {
                                body.billing_address.city = city;
                            }
                        }
                        init.body = JSON.stringify(body);
                    } catch (e) { }
                }
            }
            return originalFetch.call(this, input, init);
        };
    }

    /* city <select> injection -------------------------------------------- */
    var _updating = false;
    var _lastHtml = '';

    function updateCitySelect() {
        if (_updating) return;
        _updating = true;

        try {
            var cityEl = findCityInput();
            if (!cityEl) return;

            var countryEl = document.getElementById('shipping-country')
                || document.querySelector('[autocomplete="country"]')
                || document.querySelector('[data-autocomplete="country"]')
                || document.getElementById('billing-country');
            var stateEl = document.getElementById('shipping-state')
                || document.querySelector('[autocomplete="address-level1"]')
                || document.querySelector('[data-autocomplete="address-level1"]')
                || document.getElementById('billing-state');

            var country = countryEl ? countryEl.value : 'CO';
            var state = stateEl ? stateEl.value : '';
            var list = getCities(country, state);

            var existingSelect = getCitySelect();

            if (!list || list.length === 0) {
                cityEl.style.removeProperty('display');
                if (existingSelect) {
                    existingSelect.style.display = 'none';
                }
                return;
            }

            var currentValue = existingSelect ? existingSelect.value : cityEl.value;

            var html = '<option value="">' + i18nSelect + '</option>';
            for (var i = 0; i < list.length; i++) {
                if (typeof list[i] !== 'string') continue;
                html += '<option value="' + list[i] + '"'
                    + (list[i] === currentValue ? ' selected' : '')
                    + '>' + list[i] + '</option>';
            }

            if (existingSelect) {
                if (existingSelect.innerHTML !== html) {
                    existingSelect.innerHTML = html;
                }
                existingSelect.style.display = '';
            } else {
                var select = document.createElement('select');
                select.id = 'avshme_city_select';
                select.className = 'city_select';
                select.style.cssText = [
                    'width:100%',
                    'padding:0.5em 0.75em',
                    'font-size:inherit',
                    'font-family:inherit',
                    'border:1px solid #767676',
                    'border-radius:4px',
                    'background:#fff',
                    'color:inherit',
                    'box-sizing:border-box',
                ].join(';');

                select.innerHTML = html;
                select.addEventListener('change', function () {
                    if (cityEl.value !== this.value) {
                        // Use native setter to properly trigger React's internal state tracker.
                        // Direct assignment (cityEl.value = x) bypasses React's synthetic event
                        // system and the city never reaches the Store API update-customer request.
                        var nativeInputValueSetter = Object.getOwnPropertyDescriptor(
                            window.HTMLInputElement.prototype, 'value'
                        ).set;
                        nativeInputValueSetter.call(cityEl, this.value);
                        cityEl.dispatchEvent(new Event('input', { bubbles: true }));
                        cityEl.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });

                cityEl.parentNode.insertBefore(select, cityEl);
            }

            cityEl.style.display = 'none';
        } finally {
            _updating = false;
        }
    }

    /* initialization ----------------------------------------------------- */
    function run() {
        if (!isBlockCheckout()) return;
        updateCitySelect();
    }

    var _timer = null;

    function debouncedRun() {
        if (_timer) return;
        _timer = setTimeout(function () {
            _timer = null;
            run();
        }, 150);
    }

    var MO = new MutationObserver(function () {
        debouncedRun();
    });
    MO.observe(document.body, { childList: true, subtree: true });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }

    var retries = 0;
    var maxRetries = 40;
    var pollTimer = setInterval(function () {
        retries++;
        run();
        if (retries >= maxRetries) clearInterval(pollTimer);
    }, 500);

})();

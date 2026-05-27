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

    function findCityInput(prefix) {
        var el = findDedicatedField(prefix, 'city');
        if (el) return el;
        if (prefix === 'shipping') {
            el = document.querySelector('[autocomplete="address-level2"]');
            if (el) return el;
            el = document.querySelector('[data-autocomplete="address-level2"]');
            if (el) return el;
        }
        return null;
    }

    function findCountryState(prefix) {
        var country = findDedicatedField(prefix, 'country')
            || document.querySelector('[autocomplete="country"]')
            || document.querySelector('[data-autocomplete="country"]');
        var state = findDedicatedField(prefix, 'state')
            || document.querySelector('[autocomplete="address-level1"]')
            || document.querySelector('[data-autocomplete="address-level1"]');
        return { country: country, state: state };
    }

    function getCitySelectId(prefix) {
        return 'avshme_city_select_' + prefix;
    }

    function getCitySelect(prefix) {
        return document.getElementById(getCitySelectId(prefix));
    }

    function getCityValue(prefix) {
        var sel = getCitySelect(prefix);
        if (sel) return sel.value;
        var input = findCityInput(prefix);
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
                        var shippingCity = getCityValue('shipping');
                        var billingCity = getCityValue('billing');
                        if (shippingCity && body.shipping_address) {
                            body.shipping_address.city = shippingCity;
                        }
                        if (billingCity && body.billing_address) {
                            body.billing_address.city = billingCity;
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

    function updateCitySelect() {
        if (_updating) return;
        _updating = true;

        try {
            updateCitySelectForPrefix('billing');
            updateCitySelectForPrefix('shipping');
        } finally {
            _updating = false;
        }
    }

    function updateCitySelectForPrefix(prefix) {
        var cityEl = findCityInput(prefix);
        if (!cityEl) return;

        var cs = findCountryState(prefix);
        var countryEl = cs.country;
        var stateEl = cs.state;

        var country = countryEl ? countryEl.value : 'CO';
        var state = stateEl ? stateEl.value : '';
        var list = getCities(country, state);

        var selectId = getCitySelectId(prefix);
        var existingSelect = document.getElementById(selectId);

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
            select.id = selectId;
            select.className = 'city_select';
            select.style.cssText = [
                'width:100%',
                'appearance:none',
                'background:#fff',
                'border:1px solid hsla(0,0%,7%,.8)',
                'border-radius:4px',
                'box-sizing:border-box',
                'color:#2b2d2f',
                'font-family:inherit',
                'font-size:16px',
                'font-style:inherit',
                'font-weight:inherit',
                'height:50px',
                'letter-spacing:inherit',
                'line-height:25px',
                'padding:16px 9px 0',
                'text-decoration:inherit',
                'text-transform:inherit',
            ].join(';');

            select.innerHTML = html;
            select.addEventListener('change', function () {
                if (cityEl.value !== this.value) {
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

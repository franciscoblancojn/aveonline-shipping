(function () {
  "use strict";

  var registerPaymentMethod =
    window.wc &&
    window.wc.wcBlocksRegistry &&
    window.wc.wcBlocksRegistry.registerPaymentMethod;
  if (!registerPaymentMethod) return;

  var getSetting =
    window.wc && window.wc.wcSettings && window.wc.wcSettings.getSetting;
  var createElement =
    window.wp && window.wp.element && window.wp.element.createElement;
  if (!getSetting || !createElement) return;

  var decodeEntities =
    (window.wp &&
      window.wp.htmlEntities &&
      window.wp.htmlEntities.decodeEntities) ||
    function (str) {
      return str;
    };

  var settings = getSetting("contraentrega_data", {});
  var title = decodeEntities(settings.title || "Contraentrega Aveonline");
  var description = decodeEntities(settings.description || "");

  var Content = function () {
    return description
      ? createElement("p", { style: { margin: 0 } }, description)
      : null;
  };

  registerPaymentMethod({
    name: "contraentrega",
    label: title,
    content: createElement(Content, null),
    edit: createElement(Content, null),
    canMakePayment: function () {
      return true;
    },
    ariaLabel: title,
    supports: {
      features: ["products"],
    },
  });

  // Payment/shipping mismatch validation via fetch interceptor
  (function () {
    var originalFetch = window.fetch;
    if (!originalFetch) return;

    function getSelectedShippingRateId() {
      try {
        var cartStore =
          window.wp && window.wp.data && window.wp.data.select("wc/store/cart");
        if (cartStore && typeof cartStore.getShippingRates === "function") {
          var packages = cartStore.getShippingRates();
          if (packages && packages.length > 0) {
            var rates = packages[0].shipping_rates || [];
            for (var i = 0; i < rates.length; i++) {
              if (rates[i].selected) return rates[i].rate_id;
            }
          }
        }
      } catch (e) {}
      return "";
    }

    function getPaymentMethodLabel(methodId) {
      try {
        var registry = window.wc && window.wc.wcBlocksRegistry;
        if (registry && typeof registry.getPaymentMethods === "function") {
          var methods = registry.getPaymentMethods();
          var m = methods && methods[methodId];
          if (m) {
            if (typeof m.label === "string") return m.label;
            if (typeof m.ariaLabel === "string") return m.ariaLabel;
          }
        }
      } catch (e) {}
      return methodId;
    }

    function getActivePaymentMethod() {
      try {
        var payStore = window.wp && window.wp.data && window.wp.data.select("wc/store/payment");
        if (payStore && typeof payStore.getActivePaymentMethod === "function") {
          return payStore.getActivePaymentMethod();
        }
      } catch (e) {}
      return "";
    }

    window.fetch = function (input, init) {
      var url =
        typeof input === "string" ? input : input && input.url ? input.url : "";
      var method = ((init && init.method) || "GET").toUpperCase();
      if (init && init.body) {
        try {
          var body = JSON.parse(init.body);
          var activePM = getActivePaymentMethod();
          if (activePM) {
            body.ave_id_payment_method = activePM;
          }
          init.body = JSON.stringify(body);

          if (
            method === "POST" &&
            url.indexOf("/wc/store/v1/checkout") !== -1
          ) {
            var paymentMethod = body.payment_method || "";
            var isContraentregaPayment = paymentMethod === "contraentrega";
            var rateId = getSelectedShippingRateId();
            var isContraentregaShipping =
              rateId.indexOf("wc_contraentrega_on") !== -1;

            if (rateId && isContraentregaPayment !== isContraentregaShipping) {
              var errorMsg;
              if (isContraentregaShipping && !isContraentregaPayment) {
                errorMsg =
                  'No puedes seleccionar envío contraentrega con pago "' +
                  getPaymentMethodLabel(paymentMethod) +
                  '"';
              } else {
                errorMsg =
                  'No puedes seleccionar envío normal con pago "' + title + '"';
              }
              return Promise.resolve(
                new Response(
                  JSON.stringify({
                    code: "payment_shipping_mismatch",
                    message: errorMsg,
                    data: { status: 400 },
                  }),
                  {
                    status: 400,
                    headers: { "Content-Type": "application/json" },
                  },
                ),
              );
            }
          }
        } catch (e) {}
      }
      return originalFetch.call(this, input, init);
    };
  })();

  // Shipping loader when switching between contraentrega and other payment methods
  (function () {
    if (!window.wp || !window.wp.data) return;

    var OVERLAY_ID = "avshme-shipping-loader";
    var LOCK_ID = "avshme-checkout-lock";
    var loaderTimeout = null;
    var lastPayment = null;
    var recalcTimer = null;

    var styleEl = document.createElement("style");
    styleEl.textContent =
      "@keyframes avshme-spin{to{transform:rotate(360deg)}}" +
      "#" +
      OVERLAY_ID +
      "{" +
      "position:absolute;inset:0;background:rgba(255,255,255,0.78);" +
      "z-index:50;display:flex;align-items:center;justify-content:center;" +
      "pointer-events:all;border-radius:inherit" +
      "}" +
      "#" +
      OVERLAY_ID +
      " .avshme-spinner{" +
      "display:block;width:26px;height:26px;" +
      "border:3px solid #ddd;border-top-color:#555;border-radius:50%;" +
      "animation:avshme-spin 0.7s linear infinite" +
      "}" +
      "#" +
      LOCK_ID +
      "{" +
      "position:absolute;inset:0;z-index:49;cursor:wait" +
      "}";
    document.head.appendChild(styleEl);

    function getShippingSection() {
      return document.querySelector(
        '[data-block-name="woocommerce/checkout-shipping-methods-block"],' +
          ".wc-block-checkout__shipping-option," +
          ".wc-block-components-shipping-rates-control",
      );
    }

    function getCheckoutForm() {
      return document.querySelector(
        ".wp-block-woocommerce-checkout," +
          ".wc-block-checkout__form," +
          ".wc-block-components-checkout-form",
      );
    }

    function getSubmitButton() {
      return document.querySelector(
        ".wc-block-components-checkout-place-order-button button," +
          'button.wc-block-components-button[type="submit"],' +
          '.wc-block-checkout__actions button[type="submit"]',
      );
    }

    function showLoader() {
      if (document.getElementById(OVERLAY_ID)) return;

      var section = getShippingSection();
      if (!section) return;

      if (getComputedStyle(section).position === "static")
        section.style.position = "relative";
      var overlay = document.createElement("div");
      overlay.id = OVERLAY_ID;
      overlay.innerHTML = '<span class="avshme-spinner"></span>';
      section.appendChild(overlay);

      var form = getCheckoutForm();
      if (form) {
        if (getComputedStyle(form).position === "static")
          form.style.position = "relative";
        var lock = document.createElement("div");
        lock.id = LOCK_ID;
        form.appendChild(lock);
      }

      var btn = getSubmitButton();
      if (btn) btn.disabled = true;

      clearTimeout(loaderTimeout);
      loaderTimeout = setTimeout(hideLoader, 10000);
    }

    function hideLoader() {
      clearTimeout(loaderTimeout);
      clearTimeout(recalcTimer);
      removeLoaderElements();
      enableSubmitButton();
    }

    function removeLoaderElements() {
      var overlay = document.getElementById(OVERLAY_ID);
      if (overlay && overlay.parentNode)
        overlay.parentNode.removeChild(overlay);

      var lock = document.getElementById(LOCK_ID);
      if (lock && lock.parentNode) lock.parentNode.removeChild(lock);
    }

    function enableSubmitButton() {
      var btn = getSubmitButton();
      if (btn) btn.disabled = false;
    }

    function setBodyClass(isContraentrega) {
      if (isContraentrega) {
        document.body.classList.add("wc_contraentrega_on");
      } else {
        document.body.classList.remove("wc_contraentrega_on");
      }
    }

    function forceShippingRecalc() {
      return new Promise(function (resolve) {
        try {
          var cartDispatch = window.wp.data.dispatch("wc/store/cart");
          if (!cartDispatch) { resolve(); return; }
          var cartStore = window.wp.data.select("wc/store/cart");
          var addr =
            cartStore && typeof cartStore.getShippingAddress === "function"
              ? cartStore.getShippingAddress()
              : {};
          var result;
          if (typeof cartDispatch.updateCustomerData === "function") {
            result = cartDispatch.updateCustomerData({ shipping_address: addr });
          } else if (
            typeof cartDispatch.invalidateResolutionForStore === "function"
          ) {
            cartDispatch.invalidateResolutionForStore();
          }
          if (result && typeof result.then === "function") {
            result.then(resolve).catch(resolve);
          } else {
            setTimeout(resolve, 1500);
          }
        } catch (e) { resolve(); }
      });
    }

    function trySelectMatchingRate(paymentMethod) {
      try {
        var isContraentregaPayment = paymentMethod === "contraentrega";
        var cartStore = window.wp.data.select("wc/store/cart");
        if (!cartStore || typeof cartStore.getShippingRates !== "function")
          return false;
        var packages = cartStore.getShippingRates();
        if (!packages || !packages.length) return false;
        var rates = packages[0].shipping_rates || [];
        if (!rates.length) return false;
        var targetSuffix = isContraentregaPayment
          ? "wc_contraentrega_on"
          : "wc_contraentrega_off";
        for (var j = 0; j < rates.length; j++) {
          if (rates[j].rate_id.indexOf(targetSuffix) !== -1) {
            window.wp.data
              .dispatch("wc/store/cart")
              .selectShippingRate(rates[j].rate_id);
            return true;
          }
        }
        return false;
      } catch (e) {
        return false;
      }
    }

    function handlePaymentChange(active) {
      var prevContra = lastPayment === "contraentrega";
      var nowContra = active === "contraentrega";

      setBodyClass(nowContra);

      if (prevContra !== nowContra) {
        showLoader();

        // Fast path: select matching rate from existing ones
        trySelectMatchingRate(active);

        // Wait for Blocks to update session, then force server recalc
        recalcTimer = setTimeout(function () {
          forceShippingRecalc().then(function () {
            trySelectMatchingRate(active);
            hideLoader();
          });
        }, 800);
      }

      lastPayment = active;
    }

    var debounceTimer = null;
    window.wp.data.subscribe(function () {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(function () {
        try {
          var payStore = window.wp.data.select("wc/store/payment");
          if (
            !payStore ||
            typeof payStore.getActivePaymentMethod !== "function"
          )
            return;

          var active = payStore.getActivePaymentMethod();
          if (!active) return;

          if (lastPayment === null) {
            lastPayment = active;
            setBodyClass(active === "contraentrega");
            return;
          }

          if (active !== lastPayment) {
            handlePaymentChange(active);
          }
        } catch (e) {}
      }, 50);
    });
  })();
})();

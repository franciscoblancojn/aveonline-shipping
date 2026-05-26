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
})();

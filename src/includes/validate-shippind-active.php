<?php
function getAveonlineShipping()
{
    $zones = WC_Shipping_Zones::get_zones();
    foreach ($zones as $zone) {
        foreach ($zone['shipping_methods'] as $method) {
            if ($method->id === 'wc_aveonline_shipping') {
                return $method->settings;
            }
        }
    }
    return null;
}
function isActiveAveonlineShipping()
{
    $shipping = getAveonlineShipping();
    return $shipping && isset($shipping['enabled']) && 'yes' === $shipping['enabled'];
}

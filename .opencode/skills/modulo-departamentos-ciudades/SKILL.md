---
name: modulo-departamentos-ciudades
description: Usa cuando trabajes en departamentos-y-ciudades-de-colombia-para-woocommerce, selectores de ciudad, campos de departamento/ciudad, wc_states_places, estados CO, places CO o cities de Colombia. Explica la estructura del módulo empaquetado y cómo añadir datos.
---

# Módulo: Departamentos y Ciudades de Colombia

Módulo empaquetado dentro del plugin que reemplaza el campo de texto de ciudad por un
selector desplegable con los 32 departamentos y ~1345 ciudades de Colombia.

## Estructura

- `departamentos-y-ciudades-de-colombia-para-woocommerce.php` — entrada: init en `plugins_loaded`,
  registra `WC_States_Places_Colombia` y el método de envío `filters_by_cities_shipping_method`.
- `includes/states-places.php` — clase `WC_States_Places_Colombia`:
  - Filtra `woocommerce_states` (carga `states/*.php`).
  - Convierte `billing_city`/`shipping_city` a tipo `city` y renderiza el select
    (`woocommerce_form_field_city`).
  - Enqueue `js/place-select.js` y `js/place-select-blocks.js` con
    `wp_localize_script(..., 'wc_city_select_params', { cities, save_selected_city })`.
  - La opción `guardarCiudadSeleccionada` del método de envío decide si se conserva la ciudad
    seleccionada en sesión (`maybe_clear_customer_city`).
- `includes/filter-by-cities.php` — método de envío `Filters_By_Cities_Method` (reglas por ciudad).
- `includes/settings-filter-by-cities.php` — formularios de ajustes del método.
- `js/place-select.js` — lógica del select en checkout clásico.
- `js/place-select-blocks.js` — lógica del select en checkout de bloques.
- `states/CO.php` — array `$states['CO']` con los 32 departamentos (claves: AMZ, ANT, ...).
- `places/CO.php` — array `$places['CO']` con ~1345 ciudades agrupadas por departamento.
- `languages/` — traducciones (.po/.mo/.pot).

## Cómo añadir o corregir datos

- Departamentos → `states/CO.php` (clave corta → nombre, ej. `'ANT' => 'Antioquia'`).
- Ciudades → `places/CO.php` (clave del departamento → lista de nombres). Mantener el formato
  existente; cada ciudad es un string dentro del array del departamento.

## Convenciones del módulo

- **Text domain**: `departamentos-y-ciudades-de-colombia-para-woocommerce`.
- Sin prefijo `AVSHME_` (es código del módulo original); mantener el estilo existente.
- No traducir los datos de `states/CO.php` ni `places/CO.php` (son nombres propios).
- PHP 7.4: mismo criterio que el resto del plugin.

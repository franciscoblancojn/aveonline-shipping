# CONTEXT — Aveonline Shipping

Documento de contexto del proyecto. Léelo completo antes de hacer cualquier cambio.
Fue generado a partir del análisis del repositorio (rama `master`, release 4.3.0).

## Qué es

Plugin de WordPress para WooCommerce que integra tiendas con los servicios de envío
de **Aveonline** en Colombia: cotización en tiempo real con múltiples transportadoras,
generación de guías, recogidas, relación de envíos, método de pago contraentrega y un
módulo empaquetado de **departamentos y ciudades de Colombia** (selectores de ciudad).

## Arquitectura de carga

El plugin se carga desde `index.php` (archivo principal del plugin):

1. `require libs/autoload.php` — autoloader de Composer (generado).
2. Define constantes `AVSHME_*`.
3. `require update.php` → `github_updater_plugin_wordpress_v1()` — auto-actualización vía GitHub releases.
4. `use franciscoblancojn\wordpress_utils\FWUSystemLog;` + `FWUSystemLog::init()` — log del sistema.
5. Valida cURL; si no hay cURL muestra un aviso y no carga nada.
6. Valida incompatibilidades (Checkout Field Editor y plugins de departamentos/ciudades externos).
7. Si todo está bien carga, en orden:
   - `departamentos-y-ciudades-de-colombia-para-woocommerce/departamentos-y-ciudades-de-colombia-para-woocommerce.php`
   - `src/validator/index.php`
   - `src/includes/class-admin.php`
   - `src/telemetria/connect.php`

`src/includes/class-admin.php` es el cargador central: hace `require_once` de todos los
archivos de `src/includes/` (funciones y clases). No define lógica propia.

## Mapa de directorios

```
index.php                                  Archivo principal del plugin (Version: 4.3.0)
update.php                                 Auto-updater desde GitHub releases
package.json                               Scripts de versión e instalación de dependencias
composer.json                              Requiere franciscoblancojn/wordpress_utils ^1 (suffix AVSHME, PHP 7.4)
libs/                                      AUTOGENERADO por Composer — NUNCA EDITAR
libs/autoload.php                          Autoloader
libs/franciscoblancojn/wordpress_utils/    FWUSystemLog (dependencia instalada)
src/
  includes/class-admin.php                 Cargador central de todos los includes
  includes/function.php                    Helpers principales (el archivo más grande)
  includes/class-api.php                   Clase AveonlineAPI (auth JWT, cotizar, guías, etc.)
  includes/class-shipping.php              Método de envío WC_aveonline_Shipping_Method
  includes/class-contraentrega.php         Pasarela de pago contraentrega + soporte blocks
  includes/class-edit-checkout.php         Campo cédula + validaciones checkout clásico y blocks
  includes/class-change-order.php          Generación automática de guía al pasar a "Procesando"
  includes/class-recogida.php              Página admin de recogidas
  includes/class-relacion-envio.php        Página admin de relación de envíos
  includes/class-edit-order.php            Muestra estado de guía en pedido
  includes/class-edit-product.php          Campo "valor declarado" en productos y variaciones
  includes/products/group-exclude.php      Campo "IDs de productos a excluir" (productos agrupados)
  includes/columns.php                     Columnas personalizadas en lista de pedidos
  includes/option_page.php                 Menú y página de ajustes del plugin
  includes/dasboard.php                    Estilos del admin
  includes/cache.php                       AVSHME_getCache / AVSHME_setCache (transients)
  includes/crud_options.php                AVSHME_get_options / AVSHME_update_options
  includes/hook.php                        JS de checkout clásico en el footer
  includes/validate-shippind-active.php    Helper getAveonlineShipping()
  includes/action-update-guia.php          Endpoint de actualización de guía vía webhook
  validator/index.php                      Clase AVSHME_Validator (validación fluida)
  telemetria/connect.php                   Envía datos de conexión a Aveonline (1 sola vez)
  templates/admin/settings.php             Template de la página de ajustes
  css/  js/  img/                          Assets (estilos, JS de checkout, imágenes)
  js/cedula-field.js                       Validación JS del campo cédula (clásico)
  js/contraentrega_checkout.js             Comportamiento contraentrega (clásico)
  js/contraentrega-blocks.js               Registro del método de pago en WooCommerce Blocks
departamentos-y-ciudades-de-colombia-para-woocommerce/
  departamentos-y-ciudades-...-woocommerce.php   Entrada del módulo (init de estados/ciudades)
  includes/states-places.php               Clase WC_States_Places_Colombia (reemplaza city por select)
  includes/filter-by-cities.php            Método de envío "Shipping filter By Cities"
  includes/settings-filter-by-cities.php   Settings de Filters_By_Cities_Method
  js/place-select.js                       Select de ciudad en checkout clásico
  js/place-select-blocks.js                Select de ciudad en checkout blocks
  states/CO.php                            32 departamentos de Colombia
  places/CO.php                            ~1345 ciudades agrupadas por departamento
  languages/                               Traducciones (.po/.mo/.pot)
```

## Constantes principales (definidas en index.php)

| Constante | Valor |
|---|---|
| `AVSHME_KEY` | `'AVSHME'` |
| `AVSHME_PAYMENT_CONTRAENTREGA` | `'contraentrega'` |
| `AVSHME_LOG` | `true` |
| `AVSHME_LOG_COUNT` | `100` |
| `AVSHME_BASENAME` | basename del plugin |
| `AVSHME_DIR` / `AVSHME_URL` | directorio / URL del plugin |
| `AVSHME_TIME_MIN_COTIZAR` | `5` segundos |
| `AVSHME_TIME_MAX_COTIZAR` | `30` segundos |

## Clases clave

- **`AveonlineAPI`** (`class-api.php`) — envuelve la API de Aveonline:
  - `autenticarusuario()`, `get_token()` — auth JWT con caché (transients).
  - `agentes()`, `transportadora()` — catálogos con caché.
  - `cotisar($data)` — cotización; `cotizarParalelo()` usa peticiones paralelas multi-cURL.
  - `AVSHME_generate_guia($data, $order)`, `generarRecogida($data)`, `relacionEnvios($data)`, `system_update_guia($data)`.
  - Endpoints: `app.aveonline.co/api/...` (autenticarusuario, agentes, transportadora, ciudad,
    generarGuiaTransporteNacional, plugins/wordpress).
- **`WC_aveonline_Shipping_Method`** (`class-shipping.php`) — método de envío id `wc_aveonline_shipping`.
- **`WC_Gateway_Contraentrega`** (`class-contraentrega.php`) — pasarela de pago + registro de blocks.
- **`AVSHME_Validator_Class`** (`validator/index.php`) — validación fluida: `isRequired`, `isString`,
  `isNumber`, `isEmail`, `isMin`, `isMax`, `isLength`, `isRegex`, `isEnum`, `isObject`, `isArray`...
  La factory es `AVSHME_Validator($name)`.
- **`WC_States_Places_Colombia`** (`departamentos/.../includes/states-places.php`) — convierte el campo
  ciudad en select; lee `states/CO.php` y `places/CO.php`; enqueue de `place-select.js` y
  `place-select-blocks.js`; opción `guardarCiudadSeleccionada` del método de envío.
- **`Filters_By_Cities_Method`** (`departamentos/.../includes/filter-by-cities.php`) — método de envío
  con reglas por ciudad.
- **`FWUSystemLog`** (en `libs/`) — log del sistema, se usa vía `AVSHME_addLogAveonline()`.

## Convenciones de código

- **Prefijos**: funciones, clases, constantes y variables globales con prefijo `AVSHME_`
  (ej. `AVSHME_get_cache`, `AVSHME_Validator`). El código de `src/` no usa `namespace`
  (solo `index.php` importa `FWUSystemLog` con `use`).
- **PHP 7.4**: Composer fija `platform.php = 7.4.33`. No usar sintaxis solo disponible en PHP 8+
  (props tipadas `?type` sí están permitidas; `?string $name` en el validator).
- **Sin comentarios innecesarios**: el código existente tiene pocos comentarios; mantener el estilo.
- **WooCommerce**: hooks `add_action`/`add_filter` a nivel de archivo o dentro de constructores.
- **Assets**: enqueue con versión `AVSHME_get_version()`.
- **Logs**: `AVSHME_addLogAveonline()` para registrar eventos.
- **Persistencia**: `get_option`/`update_option` y transients (`AVSHME_*`); `crud_options.php`
  guarda por pedido.
- **Text domain**: `wc-aveonline-shipping` (plugin) y
  `departamentos-y-ciudades-de-colombia-para-woocommerce` (módulo).

## Versionado (importantísimo)

- La versión oficial vive en la cabecera de `index.php` (`Version: 4.3.0`).
- `package.json` tiene scripts de sync:
  - `npm run v` — lee la versión de `index.php`.
  - `npm run sync:version` — sincroniza `package.json` y el `**Stable tag:**` de `README.md`.
  - `npm run install` — flujo de dependencias: `composer install --no-dev --optimize-autoloader`,
    limpia `vendor` (autoload, READMEs, gitignores, etc.) y hace `mv vendor libs`.
  - `npm run push-tag` — hace commit/tag/push del release (NO ejecutarlo: hace git).
- **Regla**: no cambiar ni ejecutar estos scripts; `libs/` es resultado de la instalación.

## Dependencias

- Composer: `franciscoblancojn/wordpress_utils ^1` → se instala en `libs/`.
- PHP ≥ 7.4, WooCommerce ≥ 6.0, cURL.

## Flujo de eventos clave

- Pedido → "Procesando" (`woocommerce_order_status_processing`) → `AVSHME_generate_guia()`.
- Webhook de Aveonline → `action-update-guia.php` → actualiza estado de guía.
- Checkout clásico/blocks → campo cédula, selector de ciudad, contraentrega.
- Cotización → peticiones paralelas a transportadoras → caché de 5 a 30 s.

## Reglas de edición (resumen)

1. **Solo** editar archivos bajo `src/` y `departamentos-y-ciudades-de-colombia-para-woocommerce/`.
2. **Nunca** tocar `libs/` (generado por Composer).
3. **Nunca** ejecutar `git add/commit/push/tag/merge/rebase/reset/stash/clean`.
4. **Nunca** instalar ni actualizar dependencias (`composer`/`npm install/update`); a lo sumo recomendar.
5. **Nunca** cambiar el flujo de instalación (Composer → `vendor` → `libs`).

Ver `AGENTS.md` para las reglas detalladas.

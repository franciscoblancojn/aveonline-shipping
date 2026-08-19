# Aveonline Shipping

**Contributors:** Francisco Blanco

**Tags:** aveonline, shipping, colombia, woocommerce, envios

**Requires at least:** 5.0

**Tested up to:** 6.7

**Version:** 4.4.1

**License:** GPLv2 or later

**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

:rocket: Integración de WooCommerce con los servicios de envío de Aveonline para Colombia. Cotización en tiempo real, generación de guías, recogidas y relación de envíos.

## :memo: Descripción

Aveonline Shipping conecta tu tienda WooCommerce con la plataforma de mensajería Aveonline, permitiéndote cotizar y gestionar envíos con múltiples transportadoras en Colombia desde tu panel de administración.

El plugin incluye el módulo **"Departamentos y Ciudades de Colombia para WooCommerce"** que reemplaza los campos de texto de ciudad por selectores desplegables con los 32 departamentos y ~1345 ciudades de Colombia.

### :star: Características principales

- :truck: **Cotización en tiempo real** con múltiples transportadoras vía API Aveonline (peticiones paralelas multi-cURL)
- :construction: **Soporte para WooCommerce Blocks** (checkout en bloques) — campo de cédula, selector de ciudad, pago contraentrega
- :moneybag: **Valor declarado por producto** (campo personalizado con auto-fallback al precio de venta, mínimo $10.000 COP)
- :free: **Envío gratis** configurable con monto mínimo del carrito
- :straight_ruler: **Flete fijo** configurable que sobreescribe las cotizaciones de transportadoras
- :heavy_exclamation_mark: **Valor mínimo** configurable para forzar un monto mínimo de envío
- :money_with_wings: **Contraentrega** — método de pago contra entrega con validación de compatibilidad

### :wrench: Gestión administrativa

- :robot: **Generación automática de guías** al cambiar el pedido a "Procesando"
- :bell: **Historial de estados de guía** vía webhook desde Aveonline
- :package: **Recogidas** — generación individual o masiva de recogidas con corte a las 11:00 AM (hora Colombia)
- :link: **Relación de envíos** — agrupación y envío masivo de guías por transportadora
- :bar_chart: **Columnas personalizadas** en lista de pedidos (enlace a guía PDF y rótulo)
- :page_facing_up: **Campos de producto**: Valor declarado e IDs de productos a excluir del envío
- :closed_lock_with_key: **Autenticación JWT** con caché y validación de expiración
- :arrows_counterclockwise: **Actualizador automático** desde GitHub

### :shopping_cart: Checkout

- :id: **Campo de cédula** obligatorio (numérico, mínimo 6 dígitos) con validación cliente/servidor
- :cityscape: **Selector de ciudad** desplegable con departamentos y ciudades de Colombia
- :construction: **Soporte completo para WooCommerce Blocks** y checkout clásico (shortcode)
- :white_check_mark: **Validación de método de pago/envío** — contraentrega solo con envío contraentrega y viceversa
- :arrows_counterclockwise: **Recálculo automático de envío** al cambiar el método de pago

## :arrow_down: Instalación

1. Descarga el .zip desde el [repositorio oficial](https://github.com/franciscoblancojn/aveonline-shipping)
2. Ve a **Plugins > Añadir nuevo** en el admin de WordPress
3. Haz clic en **Subir plugin**
4. Selecciona el archivo .zip y súbelo
5. Activa el plugin

## :gear: Configuración

### :truck: Método de envío

1. Ve a **WooCommerce > Ajustes > Envío > Aveonline Shipping**
2. Configura con tus datos de Aveonline:
   - **Usuario** y **contraseña** de la API
   - **Cuenta** y **agente** (se cargan dinámicamente desde la API)
   - **Datos del remitente** (NIT, dirección, teléfono, celular, email)
   - Opciones de envío gratis, flete fijo, valor mínimo
3. Guarda los cambios

### :money_with_wings: Método de pago contraentrega

1. Ve a **WooCommerce > Pagos > Contraentrega Aveonline**
2. Activa el método y configura título y descripción

### :globe_with_meridians: Zonas de envío

1. Ve a **WooCommerce > Ajustes > Envío**
2. Crea o edita una zona de envío
3. Añade **Aveonline Shipping** como método de envío
4. Guarda los cambios

### :moneybag: Valor declarado en productos

1. Ve a **Productos > Editar producto**
2. En la pestaña **General**, encuentra el campo **Valor declarado**
3. Para productos variables, configura en cada variación
4. Guarda los cambios

### :no_entry: Exclusiones de productos

Para productos agrupados donde solo algunos items se envían:
1. En la edición del producto, busca el campo **IDs de productos a excluir**
2. Ingresa los IDs separados por coma
3. Guarda los cambios

## :rocket: Uso

### :robot: Generación de guías

Las guías se generan automáticamente cuando un pedido pasa a estado **"Procesando"**. También puedes ver el enlace a la guía PDF y al rótulo en las columnas de la lista de pedidos.

### :package: Recogidas

Accede desde el menú **Aveonline > Recogidas Aveonline** o desde la barra de administración. Puedes generar recogidas individuales o masivas (seleccionando múltiples pedidos). Horario límite: 11:00 AM.

### :link: Relación de envíos

Accede desde **Aveonline > Relacion de envios Aveonline**. Agrupa guías por transportadora y genera la relación de envío masivamente.

## :wrench: Desarrollo

> Documentación completa para agentes y desarrolladores en **`CONTEXT.md`** y **`AGENTS.md`**.
> El repositorio incluye también skills en `.opencode/skills/` y reglas en `.claude/rules/`.

### :triangular_ruler: Reglas de edición

- **Solo** se edita código en `src/` y `departamentos-y-ciudades-de-colombia-para-woocommerce/`.
- **`libs/` es generado por Composer** (`npm run install`): nunca editar nada ahí, cualquier
  cambio se pierde al reinstalar.
- Los archivos raíz (`index.php`, `update.php`, `package.json`, `composer.json`, `README.md`,
  `AGENTS.md`, `CONTEXT.md`) se mantienen con aprobación explícita del mantenedor.
- Prohibido `git add/commit/push/tag/merge/rebase/reset/stash/clean` (solo lectura: `git status/diff/log`).
- Prohibido instalar o actualizar dependencias; si se requiere, solo se recomienda al usuario.

### :package: Dependencias

- `composer.json` requiere `franciscoblancojn/wordpress_utils ^1`; se instala en `libs/`.
- Flujo fijo: `npm run install` → `composer install --no-dev --optimize-autoloader` →
  limpieza de `vendor/` → `mv vendor libs`.

### :arrow_up_down: Versionado

- La versión vive en la cabecera de `index.php` (`Version: x.y.z`).
- `npm run sync:version` la propaga a `package.json` y al `**Version:**` de este archivo.
- `npm run push-tag` crea el commit/tag/push del release (tarea del mantenedor).

## :computer: Requerimientos

- PHP 7.4 o superior
- WooCommerce 6.0 o superior
- cURL habilitado
- Cuenta activa en Aveonline

## :bust_in_silhouette: Desarrollador

- **Nombre:** Francisco Blanco
- **Web:** https://franciscoblanco.vercel.app/
- **Email:** blancofrancisco34@gmail.com

## :octocat: Repositorio

- https://github.com/franciscoblancojn/aveonline-shipping

## :scroll: Licencia

GPLv2 o posterior — https://www.gnu.org/licenses/gpl-2.0.html

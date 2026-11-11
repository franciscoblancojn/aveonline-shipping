---
name: editar-codigo-aveonline
description: Usa cuando vayas a modificar, crear o corregir código del plugin Aveonline Shipping (PHP, JS o CSS) en src/ o en departamentos-y-ciudades-de-colombia-para-woocommerce/. Define dónde se puede editar, qué está prohibido (libs/, archivos raíz, git) y las convenciones de código obligatorias.
---

# Editar código de Aveonline Shipping

Usa esta skill siempre que el trabajo implique tocar código del plugin.

## Áreas permitidas (SOLO estas)

- `src/**` — todo el código del plugin.
- `departamentos-y-ciudades-de-colombia-para-woocommerce/**` — el módulo de ciudades.

## Prohibido (sin excepción)

- **`libs/`**: nunca editar, crear, borrar o renombrar nada. Es generado por Composer y cualquier
  cambio se pierde al reinstalar. Si necesitas algo de ahí, solo léelo.
- **Archivos raíz** (`index.php`, `update.php`, `package.json`, `composer.json`, `README.md`,
  `AGENTS.md`, `CONTEXT.md`): no tocar sin aprobación explícita del mantenedor.
- **Git de escritura**: `git add`, `git commit`, `git push`, `git tag`, `git merge`, `git rebase`,
  `git reset`, `git stash`, `git clean`. Solo se permite lectura: `git status/diff/log/branch`.
- **Instalar dependencias**: no ejecutar `composer install/require/update`, `npm install/update`,
  `npm run install`. Si hace falta, solo recomiéndalo al usuario.

## Convenciones de código

- **PHP 7.4** (Composer fija `platform.php = 7.4.33`): no usar sintaxis exclusiva de PHP 8+.
  Props tipadas `?type` sí están permitidas.
- **Prefijos**: funciones, constantes y globals con `AVSHME_`; clases sin `namespace`.
- **Sin comentarios** salvo que sean estrictamente necesarios.
- **Hooks de WooCommerce** con `add_action`/`add_filter` en el estilo existente.
- **Assets**: enqueue con `AVSHME_get_version()` como versión.
- **Text domain**: `wc-aveonline-shipping` (plugin) y
  `departamentos-y-ciudades-de-colombia-para-woocommerce` (módulo).
- **Logs**: usa `AVSHME_addLogAveonline()` para registrar eventos.

## Verificación

- Cada archivo PHP modificado debe pasar `php -l <archivo.php>`.
- JS/CSS: revisión visual y consistencia con los archivos vecinos.
- No hay suite de tests automatizada: no inventar comandos de test.

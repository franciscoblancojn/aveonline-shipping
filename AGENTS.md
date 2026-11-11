# AGENTS.md — Reglas para agentes de IA (opencode / Claude Code)

Este archivo aplica a **cualquier agente de IA** (opencode, Claude Code, etc.) que trabaje
en este repositorio. Léelo completo y sigue también `CONTEXT.md`.

## Antes de empezar

1. Lee `CONTEXT.md` (arquitectura, estructura y convenciones del plugin).
2. Si existe una skill relevante en `.opencode/skills/`, cárgala.
3. Respeta las reglas de abajo sin excepción.

## Reglas duras (NO negociables)

### 1. Solo se edita código en `src/` y `departamentos-y-ciudades-de-colombia-para-woocommerce/`

- Cualquier cambio de código va exclusivamente en `src/` o en el módulo de departamentos/ciudades.
- `index.php`, `update.php`, `package.json`, `composer.json`, `README.md` y el resto de archivos raíz
  se consideran "solo lectura" para un agente (los mantiene el mantenedor o con aprobación explícita).
- Fuera de `src/` y el módulo de ciudades, **pregunta antes de tocar** cualquier archivo.

### 2. NUNCA tocar `libs/`

- `libs/` es **generado por Composer** (`npm run install`: `composer install` → limpia `vendor` → `mv vendor libs`).
- No editar, crear, borrar ni renombrar nada dentro de `libs/`. Cualquier cambio ahí se pierde en la próxima instalación.

### 3. NUNCA ejecutar operaciones de git que modifiquen el repositorio

- Prohibido: `git add`, `git commit`, `git push`, `git tag`, `git merge`, `git rebase`,
  `git reset`, `git stash`, `git clean`.
- Sí están permitidos los comandos de solo lectura: `git status`, `git diff`, `git log`, `git branch`.
- Entregar cambios como modificaciones de archivos, nunca como commits.

### 4. NUNCA instalar ni actualizar dependencias

- Prohibido: `composer require/install/update/remove`, `npm install/update`,
  `npm run install`, `npm run update`, `yarn add`, etc.
- Si un cambio lo requiere, **solo recomiéndalo** al usuario (ej. "necesitas `npm run install` para regenerar `libs/`") y deja que él lo ejecute.

### 5. NUNCA cambiar el flujo de instalación de dependencias

- El flujo es Composer → `vendor` → `libs/` vía `npm run install`.
- No modificar los scripts de `package.json` ni `composer.json` para alterar este mecanismo.
- `libs/` nunca se commitea manualmente ni se edita a mano.

## Convenciones obligatorias

- **PHP 7.4**: no usar sintaxis de PHP 8+. (Props tipadas como `?string $x` sí son válidas.)
- **Prefijos**: funciones/constantes/globals con `AVSHME_`; clases sin `namespace` (como el código existente).
- **Comentarios**: no añadir comentarios salvo que sea estrictamente necesario; mantener el estilo del proyecto.
- **WooCommerce**: usar hooks (`add_action`/`add_filter`) de la forma existente.
- **Assets**: enqueue con `AVSHME_get_version()` como versión.
- **Text domains**: `wc-aveonline-shipping` (plugin) y
  `departamentos-y-ciudades-de-colombia-para-woocommerce` (módulo).

## Cómo verificar los cambios

- PHP: `php -l <archivo.php>` sobre cada archivo modificado.
- JS/CSS: revisión visual y consistencia con los archivos vecinos.
- No hay suite de tests automatizada; no inventar comandos de test.

## Flujo de trabajo recomendado

1. Lee el contexto y la skill relevante.
2. Localiza el archivo exacto con `glob`/`grep`/`read` antes de editar.
3. Haz el cambio más pequeño posible y coherente con el estilo.
4. Verifica con `php -l`.
5. No hagas commits ni toques git para publicar.

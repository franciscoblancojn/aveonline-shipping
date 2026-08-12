# Áreas de edición permitidas

## Solo se edita código en estas rutas

- `src/**`
- `departamentos-y-ciudades-de-colombia-para-woocommerce/**`

## Nunca (sin excepción)

- **`libs/**`**: está generado por Composer (`composer install` → limpieza → `mv vendor libs`).
  No editar, crear, borrar ni renombrar nada ahí. Solo lectura si es necesario.

## Archivos raíz (requieren aprobación explícita del mantenedor)

- `index.php`, `update.php`, `package.json`, `composer.json`, `README.md`, `AGENTS.md`, `CONTEXT.md`,
  `CLAUDE.md`, `opencode.json` y la config de `.claude/` y `.opencode/`.

Si el usuario pide cambiar un archivo fuera de `src/` o del módulo de ciudades,
pregunta antes de hacerlo y explica la implicación.

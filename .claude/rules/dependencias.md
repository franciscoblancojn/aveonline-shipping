# Dependencias: nunca instalar

## Prohibido

- `composer require`, `composer install`, `composer update`, `composer remove`
- `npm install`, `npm update`, `npm run install`, `npm run update`
- `yarn add/install/update`, `pnpm add/install/update`

## Permitido

- Solo **recomendar** al usuario el comando exacto si un cambio lo requiere
  (ej. "necesitas `npm run install` para regenerar `libs/`").

## Flujo de instalación (fijo, no modificarlo)

`npm run install` → `composer install --no-dev --optimize-autoloader` → limpieza de `vendor/`
→ `mv vendor libs`.

No cambiar los scripts de `package.json` ni `composer.json` que definen este mecanismo,
y no tocar `libs/` a mano (cualquier cambio se pierde al reinstalar).

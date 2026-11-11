---
name: gestionar-dependencias-libs
description: Usa cuando el tema sea dependencias, composer, npm, libs, vendor, autoload, instalar o actualizar librerías. Explica cómo funciona el flujo Composer → vendor → libs y las reglas para NO instalar ni modificar nada.
---

# Gestión de dependencias y libs/

## Cómo funciona el flujo de instalación

El mecanismo es fijo y no se debe cambiar:

1. `composer install --no-dev --optimize-autoloader` (desde `npm run install`).
2. Limpieza de `vendor/` (autoload, READMEs, `.gitignore`, `composer.json`, `package.json`, `composer.lock`).
3. `mv vendor libs` — el resultado final vive en `libs/`.

Dependencias actuales: `franciscoblancojn/wordpress_utils ^1` (suffix de autoload `AVSHME`).

## Reglas (NO negociables)

- **NUNCA** ejecutar `composer require/install/update/remove`, `npm install/update`,
  `npm run install`, `npm run update`, `yarn add`, etc.
- **NUNCA** editar, crear, borrar ni renombrar archivos dentro de `libs/`. Solo lectura si hace falta.
- **NUNCA** cambiar los scripts de `package.json` ni `composer.json` que definen este flujo.
- Si un cambio de código requiere una dependencia nueva o actualizar una existente,
  **solo recomiéndalo** al usuario con el comando exacto (ej. "necesitas `npm run install`
  para regenerar `libs/`") y deja que él lo ejecute.

## Recordatorio

Cualquier edición a mano dentro de `libs/` se pierde en la próxima instalación y rompe la
reproducibilidad del build. Trata `libs/` como un artefacto de build, no como código fuente.

# CLAUDE.md — Instrucciones para Claude Code

Eres un agente de IA trabajando en **Aveonline Shipping** (plugin de WordPress + WooCommerce).

## Lectura obligatoria ANTES de trabajar

1. **`CONTEXT.md`** — arquitectura, estructura de directorios, clases y convenciones del plugin.
2. **`AGENTS.md`** — reglas duras (edición, git, dependencias). Son de cumplimiento obligatorio.
3. Reglas adicionales en **`.claude/rules/`** — se cargan automáticamente (áreas de edición, git, dependencias, versionado).
4. Si existe una skill relevante en **`.opencode/skills/`**, úsala.

## Reglas en una línea

- Solo editas código en `src/` y `departamentos-y-ciudades-de-colombia-para-woocommerce/`.
- NUNCA toques `libs/` (generado por Composer).
- NUNCA ejecutes `git add/commit/push/tag/merge/rebase/reset/stash/clean` (solo lecturas: `status/diff/log/branch`).
- NUNCA instales ni actualices dependencias (`composer`/`npm install/update`, `npm run install`); solo recomiéndalo.
- NUNCA cambies el flujo de instalación Composer → `vendor` → `libs`.
- PHP 7.4, prefijos `AVSHME_`, sin `namespace`, sin comentarios innecesarios.
- Verifica cada PHP modificado con `php -l`.

Este archivo indexa la documentación; la autoridad son `CONTEXT.md`, `AGENTS.md` y `.claude/rules/`.

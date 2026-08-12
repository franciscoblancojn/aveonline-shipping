# Versionado y release

## Fuente de verdad

La versión vive en la cabecera de `index.php` → `Version: x.y.z`.

`package.json` (`version`) y `README.md` (`**Stable tag:**`) se sincronizan con
`npm run sync:version`. No cambies esos valores a mano sin actualizar antes `index.php`.

## Scripts de release

- `npm run v` — lee la versión desde `index.php`.
- `npm run sync:version` — propaga la versión a `package.json` y `README.md`.
- `npm run push-tag` — ejecuta `git add/commit/tag/push`. **NO lo ejecutes** (git prohibido);
  es tarea del mantenedor.

## Regla

Entregar cambios de versión como modificaciones de `index.php` y recomendar al usuario
`npm run sync:version` para propagarla.

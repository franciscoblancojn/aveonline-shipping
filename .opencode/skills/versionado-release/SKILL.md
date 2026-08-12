---
name: versionado-release
description: Usa cuando se trate de versiones, release, tag, actualizar la versión del plugin, sincronizar versión, sync:version o push-tag. Explica dónde vive la versión y cómo sincronizarla sin ejecutar git.
---

# Versionado y release

## Dónde vive la versión

- **Fuente de verdad**: cabecera de `index.php` → `Version: 4.3.0`.
- `package.json` (campo `version`) y `README.md` (`**Stable tag:**`) se sincronizan desde ahí.

## Scripts de `package.json` (no modificarlos)

- `npm run v` → imprime la versión de `index.php`.
- `npm run sync:version` → sincroniza `package.json` y el `Stable tag:` de `README.md`.
- `npm run push-tag` → hace `sync:version` + `git add/commit/tag/push`. **NO ejecutarlo**
  (hace operaciones git, prohibidas). Es tarea del mantenedor.

## Reglas

- No cambiar manualmente el `version` de `package.json` ni el `Stable tag` de `README.md` sin
  tocar antes `index.php` (o recomendar `npm run sync:version`).
- No ejecutar `npm run push-tag` ni ningún comando git de escritura.
- Entregar los cambios de versión como modificaciones de archivo en `index.php` y recomendar
  al usuario `npm run sync:version` para propagarla a `package.json` y `README.md`.

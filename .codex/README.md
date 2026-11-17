# Harness de Codex para Aveonline Shipping

Abre este repositorio como proyecto de confianza en Codex y empieza una sesión nueva para cargar `.codex/config.toml` y `.codex/rules/aveonline.rules`.

La configuración toma de OpenCode las dos rutas editables y de Claude Code las restricciones sobre Git, dependencias y versionado. Codex carga `AGENTS.md` y consulta `CONTEXT.md` antes de editar. Las skills existentes se consultan desde sus rutas originales.

| Operación | Alcance |
| --- | --- |
| Leer | Repositorio y código de la instalación local de WooCommerce |
| Editar | `src/` y `departamentos-y-ciudades-de-colombia-para-woocommerce/` |
| Verificar | Git en modo de solo lectura y `php -l` en cada PHP modificado |
| Consultar al usuario | Acceso fuera del perfil, red y modificaciones en archivos raíz |
| Prohibido | Cambiar `libs/`, modificar Git, instalar o actualizar dependencias y alterar el flujo de instalación |

El perfil aplica los permisos de escritura en el sandbox. Las reglas de comandos bloquean los prefijos directos incluidos en el archivo. `AGENTS.md` rige también las variantes con alias, wrappers o argumentos intermedios. Si un cliente impone permisos administrados o el sandbox no logra arrancar, ese cliente sigue controlando la ejecución y las aprobaciones.

[Permisos de Codex](https://learn.chatgpt.com/docs/permissions) · [Reglas de comandos](https://learn.chatgpt.com/docs/agent-configuration/rules)

# Git: solo lectura

## Prohibido (operaciones que modifican el repositorio)

- `git add`
- `git commit`
- `git push`
- `git tag`
- `git merge`
- `git rebase`
- `git reset`
- `git stash`
- `git clean`
- Cualquier combinación de las anteriores (flags, alias, `!` prefijo, etc.)

## Permitido (solo lectura)

- `git status`
- `git diff`
- `git log`
- `git branch`

## Regla de entrega

Los cambios se entregan como modificaciones de archivos en el árbol de trabajo.
Nunca como commits ni push. Si el usuario pide publicar, indícale que es tarea del mantenedor.

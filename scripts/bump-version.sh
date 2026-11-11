#!/usr/bin/env bash
# Sube la versión de index.php y publica (npm run push-tag: commit, tag X.Y.Z y push).
# El tag dispara .github/workflows/release.yml, que crea la release con aveonline.zip.
# Uso: npm run deploy -- major|minor|patch
set -e

if [ $# -ne 1 ] || [[ ! "$1" =~ ^(major|minor|patch)$ ]]; then
    echo "Uso: npm run deploy -- major|minor|patch"
    exit 1
fi

VERSION=$(grep -m1 "^Version:" index.php | sed 's/.*Version:[[:space:]]*//' | tr -d '\r')
MAJOR=$(echo "$VERSION" | cut -d. -f1)
MINOR=$(echo "$VERSION" | cut -d. -f2)
PATCH=$(echo "$VERSION" | cut -d. -f3)

case "$1" in
    major)
        MAJOR=$((MAJOR + 1))
        MINOR=0
        PATCH=0
        ;;
    minor)
        MINOR=$((MINOR + 1))
        PATCH=0
        ;;
    patch)
        PATCH=$((PATCH + 1))
        ;;
esac

NEW_VERSION="$MAJOR.$MINOR.$PATCH"

if git rev-parse -q --verify "refs/tags/$NEW_VERSION" > /dev/null; then
    echo "✗ El tag $NEW_VERSION ya existe"
    exit 1
fi

# No se publica si algún PHP tiene errores de sintaxis.
find index.php src departamentos-y-ciudades-de-colombia-para-woocommerce -name '*.php' -print0 \
    | xargs -0 -n1 php -l > /dev/null

sed -i "s/^\(Version: *\)[0-9.]\+/\1$NEW_VERSION/" index.php

echo "✓ Versión: $VERSION → $NEW_VERSION"

# Sin "|| true" ni "2>/dev/null": si el commit o el push fallan, se ve el error.
npm run push-tag --silent

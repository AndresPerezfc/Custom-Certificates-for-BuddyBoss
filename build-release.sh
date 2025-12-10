#!/bin/bash
#
# Script para crear releases del plugin Custom Certificates
# Uso: ./build-release.sh [version]
# Ejemplo: ./build-release.sh 1.0.0
#

set -e  # Exit on error

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get version from argument or prompt
if [ -z "$1" ]; then
    echo -e "${YELLOW}Introduce la versión (ej: 1.0.0):${NC}"
    read VERSION
else
    VERSION=$1
fi

echo -e "${GREEN}=== Creando release v${VERSION} ===${NC}\n"

# Variables
PLUGIN_SLUG="custom-certificates"
BUILD_DIR="build"
FULL_ZIP="${PLUGIN_SLUG}-full-v${VERSION}.zip"
LITE_ZIP="${PLUGIN_SLUG}-lite-v${VERSION}.zip"

# Step 1: Clean previous builds
echo -e "${YELLOW}1. Limpiando builds anteriores...${NC}"
rm -rf "${BUILD_DIR}"
mkdir -p "${BUILD_DIR}"

# Step 2: Copy plugin files
echo -e "${YELLOW}2. Copiando archivos del plugin...${NC}"
rsync -av --progress ./ "${BUILD_DIR}/${PLUGIN_SLUG}" \
    --exclude "${BUILD_DIR}" \
    --exclude ".git" \
    --exclude ".idea" \
    --exclude "node_modules" \
    --exclude "*.log" \
    --exclude ".DS_Store" \
    --exclude "*.sh" \
    --exclude "composer.lock"

# Step 3: Install dependencies for FULL version
echo -e "${YELLOW}3. Instalando dependencias para versión FULL...${NC}"
cd "${BUILD_DIR}/${PLUGIN_SLUG}"

if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader --no-interaction
    echo -e "${GREEN}✓ Dependencias instaladas${NC}"
else
    echo -e "${RED}✗ Composer no está instalado. Por favor instala Composer.${NC}"
    exit 1
fi

cd ../..

# Step 4: Create FULL ZIP
echo -e "${YELLOW}4. Creando ZIP versión FULL...${NC}"
cd "${BUILD_DIR}"
zip -r "../${FULL_ZIP}" "${PLUGIN_SLUG}" \
    -x "*.git*" \
    -x "*.idea*" \
    -x "*.log" \
    -x "*.DS_Store"
cd ..
echo -e "${GREEN}✓ ${FULL_ZIP} creado${NC}"

# Step 5: Create LITE ZIP (without vendor)
echo -e "${YELLOW}5. Creando ZIP versión LITE...${NC}"
cd "${BUILD_DIR}"
zip -r "../${LITE_ZIP}" "${PLUGIN_SLUG}" \
    -x "*/vendor/*" \
    -x "*.git*" \
    -x "*.idea*" \
    -x "*.log" \
    -x "*.DS_Store"
cd ..
echo -e "${GREEN}✓ ${LITE_ZIP} creado${NC}"

# Step 6: Show file sizes
echo -e "\n${GREEN}=== Builds completados ===${NC}\n"
echo -e "Versión FULL: ${GREEN}$(du -h "${FULL_ZIP}" | cut -f1)${NC} - ${FULL_ZIP}"
echo -e "Versión LITE: ${GREEN}$(du -h "${LITE_ZIP}" | cut -f1)${NC} - ${LITE_ZIP}"

# Step 7: Cleanup
echo -e "\n${YELLOW}¿Deseas limpiar el directorio de build? (y/n)${NC}"
read -r CLEANUP
if [ "$CLEANUP" = "y" ]; then
    rm -rf "${BUILD_DIR}"
    echo -e "${GREEN}✓ Directorio de build limpiado${NC}"
fi

echo -e "\n${GREEN}=== ¡Release completado! ===${NC}\n"
echo -e "Próximos pasos:"
echo -e "1. Prueba los ZIPs en un WordPress limpio"
echo -e "2. Sube a GitHub Releases"
echo -e "3. Actualiza CHANGELOG.md"
echo -e "4. Crea tag: git tag -a v${VERSION} -m 'Release v${VERSION}'"
echo -e ""

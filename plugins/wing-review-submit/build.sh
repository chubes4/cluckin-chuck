#!/bin/bash

# Wing Review Submit Plugin Build Script
# Creates production-ready plugin package

PLUGIN_NAME="wing-review-submit"

echo "Building ${PLUGIN_NAME}..."

# Clean previous builds
echo "Cleaning previous builds..."
rm -rf build/${PLUGIN_NAME}.zip

# Build assets
echo "Building assets..."
npm run build

if [ $? -ne 0 ]; then
    echo "Build failed - npm run build error"
    exit 1
fi

# Create temp build directory
echo "Creating build directory..."
BUILD_DIR="build-temp/${PLUGIN_NAME}"
rm -rf build-temp
mkdir -p "${BUILD_DIR}/build/wing-review-submit"

# Copy essential files
echo "Copying plugin files..."
cp ${PLUGIN_NAME}.php "${BUILD_DIR}/"

# Copy compiled block assets
cp build/wing-review-submit/* "${BUILD_DIR}/build/wing-review-submit/"

# Create ZIP file
echo "Creating ZIP archive..."
mkdir -p build
cd build-temp && zip -r ../build/${PLUGIN_NAME}.zip ${PLUGIN_NAME}/ && cd ..

# Clean up temp directory
rm -rf build-temp

# Verify output
if [ -f "build/${PLUGIN_NAME}.zip" ]; then
    echo "Build complete!"
    echo "ZIP file: build/${PLUGIN_NAME}.zip"
else
    echo "Build failed - missing output files"
    exit 1
fi

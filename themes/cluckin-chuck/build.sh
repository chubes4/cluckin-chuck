#!/bin/bash

# Cluckin Chuck Theme Build Script
# Creates production-ready theme package

echo "🍗 Building Cluckin Chuck Theme..."

# Clean previous builds
echo "Cleaning previous builds..."
rm -rf build/cluckin-chuck build/cluckin-chuck.zip

# Create build directory
echo "Creating build directory..."
mkdir -p build/cluckin-chuck

# Copy essential files
echo "Copying theme files..."
cp style.css build/cluckin-chuck/
cp theme.json build/cluckin-chuck/
cp functions.php build/cluckin-chuck/
cp README.md build/cluckin-chuck/

# Copy directories
echo "Copying templates, parts, and inc..."
cp -r templates build/cluckin-chuck/
cp -r parts build/cluckin-chuck/
cp -r inc build/cluckin-chuck/

# Create ZIP file
echo "Creating ZIP archive..."
cd build && zip -r cluckin-chuck.zip cluckin-chuck/ && cd ..

# Verify outputs
if [ -d "build/cluckin-chuck" ] && [ -f "build/cluckin-chuck.zip" ]; then
    echo "✅ Build complete!"
    echo "📦 Directory: build/cluckin-chuck/"
    echo "📦 ZIP file: build/cluckin-chuck.zip"
    echo ""
    echo "Ready for production deployment to WordPress!"
else
    echo "❌ Build failed - missing output files"
    exit 1
fi

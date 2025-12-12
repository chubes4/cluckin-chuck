#!/bin/bash
set -e

echo "🍗 Building Cluckin Chuck - All Components"

# Build theme
cd themes/cluckin-chuck
./build.sh
cd ../..

# Build plugins
for plugin in plugins/*/; do
    [ -f "$plugin/build.sh" ] && (
        cd "$plugin"
        ./build.sh
        cd ../..
    )
done

echo "✅ All builds complete!"
ls -la build/*.zip
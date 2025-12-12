#!/bin/bash
set -e

# Configuration
SERVER_HOST="178.128.155.94"
SERVER_USER="master_nytegthymm"
WP_PATH="/home/master_nytegthymm/applications/cluckin_chuck/public_html"
REMOTE_TEMP="/tmp/cluckin-deploy"

# Build first
./build.sh

# Transfer and install
ssh $SERVER_USER@$SERVER_HOST "mkdir -p $REMOTE_TEMP"
scp build/*.zip $SERVER_USER@$SERVER_HOST:$REMOTE_TEMP/

ssh $SERVER_USER@$SERVER_HOST << EOF
cd $WP_PATH

# Install theme
wp theme install $REMOTE_TEMP/cluckin-chuck.zip --activate --force

# Install plugins  
wp plugin install $REMOTE_TEMP/wing-location-details.zip --activate --force
wp plugin install $REMOTE_TEMP/wing-map-display.zip --activate --force
wp plugin install $REMOTE_TEMP/wing-review.zip --activate --force
wp plugin install $REMOTE_TEMP/wing-review-submit.zip --activate --force

# Cleanup
rm -rf $REMOTE_TEMP
echo "✅ Deployment complete!"
EOF

echo "🎉 Local deployment finished!"
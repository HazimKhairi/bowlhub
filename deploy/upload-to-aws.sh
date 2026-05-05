#!/usr/bin/env bash
#
# Upload Laravel project ke AWS EC2 dari local machine
# Run dari laptop (bukan dari EC2)
#
# Usage:
#   EC2_HOST=ec2-user@1.2.3.4 KEY=~/.ssh/bowling-key.pem ./deploy/upload-to-aws.sh
#

set -euo pipefail

EC2_HOST="${EC2_HOST:-ubuntu@YOUR_EC2_IP}"
KEY="${KEY:-$HOME/.ssh/bowling-key.pem}"
REMOTE_DIR="/var/www/bowling"
LOCAL_DIR="$(cd "$(dirname "$0")/.." && pwd)"

echo "==> Uploading $LOCAL_DIR to $EC2_HOST:$REMOTE_DIR"

# Exclude files yang tak perlu / sensitive
rsync -avz --progress \
  --exclude='.git' \
  --exclude='node_modules' \
  --exclude='vendor' \
  --exclude='.env' \
  --exclude='.env.local' \
  --exclude='storage/logs/*.log' \
  --exclude='storage/framework/cache/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  --exclude='.phpunit.result.cache' \
  --exclude='.DS_Store' \
  --exclude='hostinger-deploy' \
  --exclude='bowling-system-deploy-*.zip' \
  -e "ssh -i $KEY" \
  "$LOCAL_DIR/" "$EC2_HOST:$REMOTE_DIR/"

echo ""
echo "==> Upload complete!"
echo ""
echo "Next: SSH ke EC2 dan run deploy.sh"
echo "  ssh -i $KEY $EC2_HOST"
echo "  cd $REMOTE_DIR"
echo "  sudo bash deploy/deploy.sh"

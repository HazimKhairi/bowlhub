#!/usr/bin/env bash
#
# Laravel Deployment Script
# Run AFTER provision.sh dah complete dan code dah upload ke /var/www/bowling
#
# Usage: cd /var/www/bowling && sudo ./deploy/deploy.sh
#

set -euo pipefail

APP_DIR="/var/www/bowling"
cd "$APP_DIR"

echo "==> Pulling latest code (kalau git repo)"
if [[ -d .git ]]; then
  git pull origin main || true
fi

echo "==> Installing Composer dependencies (production)"
# --ignore-platform-req=php: server runs PHP 8.5 but composer.lock pins
# phpoffice/phpspreadsheet 1.30.2 (requires PHP <8.5). Existing vendor/
# works at runtime; bypass platform check until lock is bumped to
# phpspreadsheet 2.x + maatwebsite/excel that supports it.
sudo -u ubuntu composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=php

echo "==> Setting up .env"
if [[ ! -f .env ]]; then
  cp deploy/.env.production.aws .env
  echo "⚠️  EDIT .env untuk set APP_KEY, DB_PASSWORD, ADMIN_PASSWORD!"
  echo "   Run: php artisan key:generate"
fi

echo "==> Generating APP_KEY (kalau kosong)"
if grep -q "^APP_KEY=$" .env; then
  sudo -u ubuntu php artisan key:generate --force
fi

echo "==> Running migrations"
sudo -u ubuntu php artisan migrate --force

echo "==> Creating storage symlink"
sudo -u ubuntu php artisan storage:link

echo "==> Caching config, routes, views"
sudo -u ubuntu php artisan config:cache
sudo -u ubuntu php artisan route:cache
sudo -u ubuntu php artisan view:cache
sudo -u ubuntu php artisan event:cache

echo "==> Setting permissions"
chown -R ubuntu:www-data "$APP_DIR"
find "$APP_DIR" -type f -exec chmod 644 {} \;
find "$APP_DIR" -type d -exec chmod 755 {} \;
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

echo "==> Setting up Laravel scheduler cron (kalau belum ada)"
CRON_JOB="* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1"
(sudo -u ubuntu crontab -l 2>/dev/null | grep -v "schedule:run" ; echo "$CRON_JOB") | sudo -u ubuntu crontab -

echo "==> Setting up queue worker (systemd service)"
cat > /etc/systemd/system/laravel-queue.service <<EOF
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
User=ubuntu
Group=www-data
Restart=always
RestartSec=5
ExecStart=/usr/bin/php $APP_DIR/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
StandardOutput=append:/var/log/laravel-queue.log
StandardError=append:/var/log/laravel-queue.log

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable laravel-queue
systemctl restart laravel-queue

echo "==> Reloading PHP-FPM & Nginx"
systemctl reload php8.5-fpm
systemctl reload nginx

echo ""
echo "==========================================="
echo "✅ Deployment complete!"
echo "==========================================="
echo ""
echo "Check status:"
echo "  systemctl status laravel-queue"
echo "  tail -f storage/logs/laravel.log"
echo ""

#!/usr/bin/env bash
#
# AWS EC2 Provisioning Script — Ubuntu 26.04 LTS
# LEMP stack: Nginx + PHP 8.5 + MySQL 8.4 + Composer + Node.js
#
# Run on fresh EC2 as ubuntu user:
#   chmod +x provision.sh && sudo ./provision.sh
#

set -euo pipefail

DOMAIN="${DOMAIN:-bowlhub.my}"
DB_NAME="${DB_NAME:-bowling_system}"
DB_USER="${DB_USER:-bowling_user}"
DB_PASS="${DB_PASS:-$(openssl rand -base64 24 | tr -d /=+ | cut -c1-20)}"
APP_DIR="/var/www/bowling"
PHP_VER="8.5"

echo "==> Updating system packages"
apt-get update -y
DEBIAN_FRONTEND=noninteractive apt-get upgrade -y -o Dpkg::Options::="--force-confnew"

echo "==> Installing base utilities"
apt-get install -y software-properties-common curl unzip git ufw certbot python3-certbot-nginx rsync

echo "==> Installing Nginx + PHP ${PHP_VER} + MySQL"
DEBIAN_FRONTEND=noninteractive apt-get install -y \
  nginx \
  mysql-server \
  php${PHP_VER}-fpm php${PHP_VER}-cli php${PHP_VER}-mysql php${PHP_VER}-mbstring php${PHP_VER}-xml \
  php${PHP_VER}-curl php${PHP_VER}-zip php${PHP_VER}-gd php${PHP_VER}-bcmath php${PHP_VER}-intl \
  php${PHP_VER}-readline

echo "==> Installing Composer"
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
chmod +x /usr/local/bin/composer

echo "==> Installing Node.js 20.x"
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y nodejs

echo "==> Tuning PHP for low-RAM (1GB EC2)"
PHP_INI="/etc/php/${PHP_VER}/fpm/php.ini"
sed -i 's/^memory_limit = .*/memory_limit = 256M/' "$PHP_INI"
sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 20M/' "$PHP_INI"
sed -i 's/^post_max_size = .*/post_max_size = 25M/' "$PHP_INI"
sed -i 's/^max_execution_time = .*/max_execution_time = 120/' "$PHP_INI"
sed -i 's/^;\?opcache.enable=.*/opcache.enable=1/' "$PHP_INI"
sed -i 's/^;\?opcache.memory_consumption=.*/opcache.memory_consumption=64/' "$PHP_INI"

POOL="/etc/php/${PHP_VER}/fpm/pool.d/www.conf"
sed -i 's/^pm = .*/pm = ondemand/' "$POOL"
sed -i 's/^pm.max_children = .*/pm.max_children = 5/' "$POOL"
sed -i 's/^;\?pm.process_idle_timeout.*/pm.process_idle_timeout = 10s/' "$POOL"

echo "==> Tuning MySQL for low-RAM"
cat > /etc/mysql/mysql.conf.d/low-ram.cnf <<'EOF'
[mysqld]
innodb_buffer_pool_size = 128M
key_buffer_size = 16M
max_connections = 30
performance_schema = OFF
EOF

echo "==> Creating swap file (2GB)"
if [[ ! -f /swapfile ]]; then
  fallocate -l 2G /swapfile
  chmod 600 /swapfile
  mkswap /swapfile
  swapon /swapfile
  echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

echo "==> Restarting services"
systemctl restart php${PHP_VER}-fpm
systemctl restart mysql
systemctl restart nginx
systemctl enable php${PHP_VER}-fpm nginx mysql

echo "==> Creating MySQL database & user"
mysql <<EOF
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF

echo "==> Setting up firewall (UFW)"
ufw allow OpenSSH
ufw allow 'Nginx Full'
echo "y" | ufw enable

echo "==> Creating app directory"
mkdir -p "$APP_DIR"
chown -R ubuntu:www-data "$APP_DIR"

echo "==> Saving credentials"
cat > /root/credentials.txt <<EOF
=== Bowling System AWS Credentials ===
Generated: $(date)

Domain:       ${DOMAIN}
App Path:     ${APP_DIR}
PHP Version:  ${PHP_VER}

MySQL Database
DB_NAME:      ${DB_NAME}
DB_USER:      ${DB_USER}
DB_PASS:      ${DB_PASS}

Connect:      mysql -u ${DB_USER} -p ${DB_NAME}
EOF
chmod 600 /root/credentials.txt

echo ""
echo "==========================================="
echo "✅ Provisioning complete!"
echo "==========================================="
echo ""
echo "Credentials saved to: /root/credentials.txt"
echo "DB Password: ${DB_PASS}"
echo ""

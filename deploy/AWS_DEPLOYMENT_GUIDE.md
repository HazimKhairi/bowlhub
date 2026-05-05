# AWS EC2 Deployment Guide — Bowling System

Deploy Laravel bowling system ke AWS EC2 Free Tier (12 bulan free).

## 📋 Prerequisites

- AWS account baru (untuk Free Tier eligibility)
- Credit/debit card (untuk verification, tak charge kalau within free tier)
- Local Laravel project ready
- SSH client (Terminal/iTerm di Mac)

---

## Phase 1: AWS Account Setup

### 1.1 Sign Up AWS Account

1. Pergi ke https://aws.amazon.com/
2. Click "Create an AWS Account"
3. Email + password
4. Pilih account type: **Personal**
5. Address + payment info (kena bagi card)
6. Verify phone (SMS/call)
7. Pilih support plan: **Basic (Free)**

### 1.2 ⚠️ Setup Billing Alarms (CRITICAL!)

**WAJIB** — sebelum buat apa-apa lagi.

1. Login → Search **"Billing"** → **Billing and Cost Management**
2. Settings → enable **"Receive Free Tier Usage Alerts"**
3. Settings → enable **"Receive Billing Alerts"**
4. **Budgets** → Create budget:
   - Type: Cost budget
   - Amount: **$5/month**
   - Alert threshold: 50%, 80%, 100%
   - Email: (your email)
5. **CloudWatch** → Alarms → Create alarm:
   - Metric: Billing → Total Estimated Charge
   - Threshold: $5, $10, $20
   - Notification: SNS email

---

## Phase 2: Launch EC2 Instance

### 2.1 Set Region

Top-right corner → Pilih **Asia Pacific (Singapore)** = `ap-southeast-1`

### 2.2 Create Key Pair (SSH key)

1. EC2 Console → **Key Pairs** → Create
2. Name: `bowling-key`
3. Type: **RSA**, Format: **.pem**
4. Download `bowling-key.pem` — **simpan baik-baik!**
5. Local terminal:
   ```bash
   mv ~/Downloads/bowling-key.pem ~/.ssh/
   chmod 400 ~/.ssh/bowling-key.pem
   ```

### 2.3 Launch Instance

1. EC2 Console → **Launch Instance**
2. Settings:
   - **Name:** `bowling-system`
   - **AMI:** Ubuntu Server 22.04 LTS (Free tier eligible) ✓
   - **Instance type:** `t3.micro` (Free tier) atau `t2.micro`
   - **Key pair:** `bowling-key`
   - **Network settings:**
     - Allow SSH (My IP)
     - Allow HTTP (Anywhere 0.0.0.0/0)
     - Allow HTTPS (Anywhere 0.0.0.0/0)
   - **Storage:** 30 GB gp3 (max free tier)
3. Click **Launch Instance**
4. Wait ~2 minutes — status: Running

### 2.4 Allocate Elastic IP (optional tapi recommended)

⚠️ **WARNING:** Elastic IP free **kalau attached** ke running instance. Detached = $3.60/mo charge.

1. EC2 → Elastic IPs → Allocate
2. Associate dengan instance `bowling-system`
3. **Catat IP address** — contoh: `13.213.45.67`

---

## Phase 3: Connect via SSH

```bash
ssh -i ~/.ssh/bowling-key.pem ubuntu@YOUR_EC2_IP
```

First time akan ada warning fingerprint — type `yes`.

---

## Phase 4: Provision Server

### 4.1 Upload provision script

Dari local machine:
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/client_project/bowling-system-backend

scp -i ~/.ssh/bowling-key.pem deploy/provision.sh ubuntu@YOUR_EC2_IP:~
```

### 4.2 Run provisioning

SSH ke EC2:
```bash
ssh -i ~/.ssh/bowling-key.pem ubuntu@YOUR_EC2_IP

# Run as root
chmod +x provision.sh
sudo DOMAIN=bowlhub.my ./provision.sh
```

Akan install:
- Nginx
- PHP 8.2 + extensions
- MySQL 8
- Composer
- Node.js 20
- 2GB swap file
- Firewall (UFW)
- MySQL database `bowling_system` + user

**Catat password DB** yang dipaparkan di akhir!

---

## Phase 5: Upload Laravel Code

### 5.1 Edit upload script

Edit `deploy/upload-to-aws.sh`:
```bash
EC2_HOST="ubuntu@YOUR_EC2_IP"  # Ganti
KEY="$HOME/.ssh/bowling-key.pem"
```

### 5.2 Run upload

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/client_project/bowling-system-backend
./deploy/upload-to-aws.sh
```

Atau manual rsync:
```bash
rsync -avz --progress \
  --exclude='.git' --exclude='node_modules' --exclude='vendor' --exclude='.env' \
  -e "ssh -i ~/.ssh/bowling-key.pem" \
  ./ ubuntu@YOUR_EC2_IP:/var/www/bowling/
```

---

## Phase 6: Deploy Laravel

### 6.1 Configure .env

SSH ke EC2:
```bash
ssh -i ~/.ssh/bowling-key.pem ubuntu@YOUR_EC2_IP
cd /var/www/bowling

# Copy template
cp deploy/.env.production.aws .env

# Edit:
nano .env
```

Update:
- `APP_KEY=` — kosong, akan auto-generate later
- `DB_PASSWORD=` — guna password dari `/root/credentials.txt` (sudo cat)
- `ADMIN_PASSWORD=` — strong password baru
- `APP_URL=` — `http://YOUR_EC2_IP` (sementara, before SSL) atau `https://bowlhub.my`

### 6.2 Run deploy script

```bash
cd /var/www/bowling
sudo bash deploy/deploy.sh
```

Akan:
- Install Composer dependencies
- Generate APP_KEY
- Run migrations
- Create storage symlink
- Cache config/routes/views
- Setup queue worker (systemd)
- Setup scheduler cron

---

## Phase 7: Configure Nginx

```bash
sudo cp /var/www/bowling/deploy/nginx-bowling.conf /etc/nginx/sites-available/bowling
sudo ln -sf /etc/nginx/sites-available/bowling /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

Test: visit `http://YOUR_EC2_IP` di browser.

---

## Phase 8: Domain + SSL

### 8.1 Update DNS (kalau domain di Hostinger)

1. Hostinger → DNS Zone Editor
2. Edit A record:
   - Name: `@`
   - Type: A
   - Value: `YOUR_EC2_IP`
   - TTL: 300
3. Add another A record:
   - Name: `www`
   - Value: `YOUR_EC2_IP`
4. Save → tunggu 5-30 minit propagate

Verify:
```bash
dig bowlhub.my +short
# Should return YOUR_EC2_IP
```

### 8.2 Install SSL (Let's Encrypt)

```bash
sudo certbot --nginx -d bowlhub.my -d www.bowlhub.my
```

Follow prompts → email → agree TOS → redirect HTTP→HTTPS: **YES**.

Certbot auto-update Nginx config + setup auto-renewal.

### 8.3 Update .env

```bash
cd /var/www/bowling
nano .env
# APP_URL=https://bowlhub.my

php artisan config:cache
```

---

## Phase 9: Verify

```bash
# Check services
sudo systemctl status nginx php8.2-fpm mysql laravel-queue

# Check Laravel logs
tail -f /var/www/bowling/storage/logs/laravel.log

# Check queue worker
sudo journalctl -u laravel-queue -f

# Test endpoints
curl -I https://bowlhub.my
```

---

## 🛠️ Common Tasks

### Update code dari local

```bash
# Local
./deploy/upload-to-aws.sh

# EC2
ssh ubuntu@EC2_IP
cd /var/www/bowling
php artisan migrate --force
php artisan config:cache
sudo systemctl restart laravel-queue
```

### View logs

```bash
tail -f /var/www/bowling/storage/logs/laravel.log
sudo tail -f /var/log/nginx/error.log
```

### Database backup

```bash
mysqldump -u bowling_user -p bowling_system > backup-$(date +%F).sql
```

### Restart services

```bash
sudo systemctl restart nginx php8.2-fpm laravel-queue
```

---

## 🚨 Troubleshooting

| Problem | Solution |
|---------|----------|
| 502 Bad Gateway | `sudo systemctl restart php8.2-fpm` |
| 500 Error | `tail storage/logs/laravel.log` |
| Permission denied | `sudo chown -R ubuntu:www-data /var/www/bowling && sudo chmod -R 775 storage bootstrap/cache` |
| Out of memory (Excel import) | Edit `php.ini` memory_limit ke 512M |
| Can't connect MySQL | `sudo cat /root/credentials.txt` for password |
| SSL renewal | `sudo certbot renew --dry-run` |

---

## 💰 Cost Monitoring

**Free Tier limits (12 months):**
- 750 hours t3.micro/month — 1 instance running 24/7 = OK
- 30 GB EBS storage
- 100 GB data transfer out

**Bila boleh kena charge:**
- > 1 instance running
- > 30 GB storage
- > 100 GB transfer out
- Elastic IP detached
- Use NAT Gateway / Load Balancer

**Daily check:**
- Billing console → Free Tier usage
- Cost Explorer → daily spend

---

## 🧹 Cleanup (lepas event habis)

Untuk stop semua charges:

```bash
# 1. Snapshot data dulu (optional)
mysqldump bowling_system > final-backup.sql
scp ubuntu@EC2_IP:final-backup.sql ./

# 2. Di AWS Console:
#    - EC2 → Instances → Terminate
#    - EC2 → Elastic IPs → Release
#    - EC2 → Volumes → Delete (kalau ada extra)
#    - EC2 → Snapshots → Delete (kalau ada)
```

---

## 📞 Support

- Laravel logs: `/var/www/bowling/storage/logs/`
- Nginx logs: `/var/log/nginx/`
- System logs: `journalctl -xe`
- AWS billing: https://console.aws.amazon.com/billing/

# Deployment Guide

Version: 1.0  
Status: Active  
Project: Multi-Tenant School Administration Management SaaS Platform

Cloud deployment is required for the Qollabb live-demo link and MCA Milestone 6
(**Deploy and Test Application**). This guide targets a single Ubuntu VPS with
Nginx, PHP 8.4, MySQL 8, and HTTPS.

Adapt hostnames, paths, and credentials to your provider.

## 1. Deployment Goals

* HTTPS live URL for mentor evaluation
* `APP_ENV=production` and `APP_DEBUG=false`
* MySQL 8 with migrated schema and demonstration seed data
* Private storage for student photos and backups
* Smoke-tested four roles across two schools
* Recorded evidence for report Chapter 7 and Qollabb milestone 6

## 2. Recommended Stack

| Layer | Choice |
| ----- | ------ |
| Server | Ubuntu 22.04 or 24.04 LTS VPS (1 vCPU, 2 GB RAM minimum) |
| Web server | Nginx |
| Application | PHP 8.4-FPM |
| Database | MySQL 8.0 |
| Process manager | systemd for `php-fpm` and queue worker if used |
| TLS | Let's Encrypt via Certbot |
| Source | GitHub repository |

Providers such as DigitalOcean, Linode, Hostinger VPS, or AWS Lightsail are
suitable. Use managed MySQL only if you understand network access and backups.

## 3. Server Preparation

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server git unzip curl software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt install -y php8.4-fpm php8.4-cli php8.4-mysql php8.4-xml php8.4-mbstring \
  php8.4-curl php8.4-zip php8.4-bcmath php8.4-intl
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

Secure MySQL and create database:

```sql
CREATE DATABASE school_saas_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'school_saas'@'localhost' IDENTIFIED BY 'strong-random-password';
GRANT ALL PRIVILEGES ON school_saas_prod.* TO 'school_saas'@'localhost';
FLUSH PRIVILEGES;
```

## 4. Deploy Application Files

```bash
sudo mkdir -p /var/www/school-saas
sudo chown -R $USER:www-data /var/www/school-saas
cd /var/www/school-saas
git clone https://github.com/abdulbaquee/multi-tenant-school-saas.git .
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Production `.env` essentials:

```dotenv
APP_NAME="School Portal"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_saas_prod
DB_USERNAME=school_saas
DB_PASSWORD=strong-random-password

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

LOG_CHANNEL=stack
LOG_LEVEL=warning

FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

# Use a strong unique password; rotate after viva if demo credentials were public.
SUPER_ADMIN_EMAIL=superadmin@example.com
SUPER_ADMIN_PASSWORD="Change-Me-Strong-Password-2026"
```

Build and migrate:

```bash
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

For demonstration only, you may also run `DemoDataSeeder` after reviewing
`database/seeders/DemoDataSeeder.php`.

Set permissions:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
```

## 5. Nginx Site Configuration

Create `/etc/nginx/sites-available/school-saas`:

```nginx
server {
    listen 80;
    server_name your-domain.example;
    root /var/www/school-saas/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable and test:

```bash
sudo ln -s /etc/nginx/sites-available/school-saas /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d your-domain.example
```

## 6. Post-Deployment Smoke Tests

Use `Dummy-Logins-for-multiple-schools.md` credentials (change passwords in
production `.env` first).

| # | Role | Test |
| - | ---- | ---- |
| 1 | Super Admin | Login, Schools list, Reports hub, Analytics, System Operations |
| 2 | School Admin A | Students, Attendance, Fees, own-school reports only |
| 3 | School Admin B | Confirm School A data is not visible |
| 4 | Teacher | Assigned attendance/marks only; no System Operations |
| 5 | Accountant | Fee workflows; no examination admin screens |
| 6 | Files | Student photo loads through authorized route only |
| 7 | Backup | Super Admin creates and downloads platform backup |
| 8 | HTTPS | No mixed-content warnings; session persists after login |

Record URL, date, PHP/MySQL versions, and screenshot filenames in
`MCA_REPORT_NOTES.md`.

## 7. Concurrent Tenant Demonstration

For Milestone 6 and report evidence:

1. Open two browsers (or one normal + one private window).
2. Log in as `admin.sha@example.com` in window A and `admin.shb@example.com` in
   window B.
3. Open Students, Attendance, and Reports in both.
4. Confirm each window shows only its school's data.
5. Capture side-by-side screenshots for Chapter 7.

Optional: run `php artisan test` on a CI machine or locally before deploy; do not
run the test suite against production data.

## 8. Backup And Rollback

Before each production change:

```bash
mysqldump -u school_saas -p school_saas_prod > backup-$(date +%F).sql
tar -czf storage-backup-$(date +%F).tar.gz storage/app/private
```

Rollback:

1. Restore database from SQL dump.
2. Redeploy previous Git tag or commit.
3. Run `php artisan config:cache`.

Application backup archives are also available from **System Operations → Backup
Management** inside the Super Admin UI.

## 9. Security Checklist

- [ ] `APP_DEBUG=false`
- [ ] HTTPS enabled
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] Strong `APP_KEY` and database passwords
- [ ] Demo passwords rotated if repository defaults were used publicly
- [ ] `.env` never committed
- [ ] `storage/app/private` not web-accessible
- [ ] Firewall allows only 22, 80, 443

## 10. Qollabb Milestone 6 Submission Text

Copy into Qollabb when smoke tests and report draft are complete:

> Deployed the Multi-Tenant School Administration Management SaaS Platform to a
> cloud server with HTTPS, MySQL 8, and production Laravel configuration. Ran
> migrate/seed, built frontend assets, and verified smoke tests for Super Admin,
> School Admin, Teacher, and Accountant roles. Demonstrated concurrent
> two-school tenant isolation with SHA and SHB demo schools. Full automated
> regression suite passes 394 tests locally. Final report PDF, presentation, and
> GitHub/live-demo links submitted on Qollabb.

## 11. Related Documents

* `INSTALLATION_GUIDE.md`
* `MCA_SUBMISSION_CHECKLIST.md`
* `MCA_SUBMISSION_MASTER_PLAN.md`

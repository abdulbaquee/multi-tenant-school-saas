# Deployment Guide

Version: 1.1  
Status: **Frozen — Production Verified**  
Last Verified: 2026-06-25  
Project: Multi-Tenant School Administration Management SaaS Platform

Cloud deployment is required for the Qollabb live-demo link and MCA Milestone 6
(**Deploy and Test Application**). This document is the canonical, frozen
production deployment record for **School Portal**.

**Live demo URL:** `https://schoolportal.pagescorch.com`  
**Application brand:** School Portal  
**Co-hosted domain:** `pagescorch.com` (main site on the same OVH VPS)

Do not change the production topology, paths, or hostname without updating this
guide, `MCA_REPORT_NOTES.md`, and the Qollabb live-demo link.

## 0. Frozen Production Record

| Item | Canonical value |
| ---- | ---------------- |
| Status | Verified live — landing page, login, and School Admin dashboard confirmed |
| Verified on | 2026-06-25 |
| Provider | OVH VPS (`vps-744671cb.vps.ovh.net`) |
| OS | Ubuntu 22.04 LTS |
| Web server | Nginx 1.18.0 |
| PHP | 8.4.21 (PHP-FPM) |
| Database | MySQL 8.0.46 |
| Application root | `/var/www/schoolportal` |
| Web root | `/var/www/schoolportal/public` |
| Nginx site file | `/etc/nginx/sites-available/schoolportal` |
| TLS | Let's Encrypt via Certbot (`schoolportal.pagescorch.com`) |
| DNS | `schoolportal` A record → VPS public IP |
| Database name | `schoolportal_prod` |
| Database user | `schoolportal` |
| Repository | `https://github.com/abdulbaquee/multi-tenant-school-saas` |
| Demo schools | SHA, SHB, SHC via `DemoDataSeeder` |
| Demo credentials | `Dummy-Logins-for-multiple-schools.md` (private; not in repository) |

### Freeze rules

After 2026-06-25:

* Do not redeploy to a different hostname without mentor/Qollabb link updates.
* Do not change `/var/www/schoolportal` path or Nginx `server_name` casually.
* Apply only security patches, SSL renewal, release-blocking fixes, or
  submission-document corrections.
* Before any production change: database dump + note the deployed Git commit.
* After any production change: rerun §6 smoke tests and update this section.

### Operator verification (2026-06-25)

- [x] HTTPS loads `https://schoolportal.pagescorch.com`
- [x] Landing page shows **School Portal** branding
- [x] School Admin login and dashboard operational (Springdale High A)
- [x] Footer shows **Multi-Tenant School Administration**
- [ ] Full four-role smoke test recorded
- [ ] Two-browser SHA/SHB tenant isolation screenshot captured
- [ ] Qollabb Milestone 6 marked complete

Before deployment, add a DNS **A record** for `schoolportal` pointing to your VPS
public IP address.

## 1. Deployment Goals

* HTTPS live URL for mentor evaluation
* `APP_ENV=production` and `APP_DEBUG=false`
* MySQL 8 with migrated schema and demonstration seed data
* Private storage for student photos and backups
* Smoke-tested four roles across two schools
* Recorded evidence for report Chapter 7 and Qollabb milestone 6

## 2. Production Stack (OVH VPS)

| Layer | Verified choice |
| ----- | --------------- |
| Server | OVH VPS, Ubuntu 22.04 LTS |
| Web server | Nginx 1.18.0 |
| Application | PHP 8.4.21-FPM |
| Database | MySQL 8.0.46 |
| TLS | Let's Encrypt (Certbot) |
| Source | GitHub `main` branch |
| Node.js | Required on server for `npm ci` / `npm run build` at deploy time |

`pagescorch.com` already runs on this VPS. **School Portal** uses a separate
Nginx site block and does not replace the main domain configuration.

### Fresh-server preparation (reference only)

Skip this section on the verified OVH VPS unless rebuilding from scratch.

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

## 3. Database Setup

```sql
CREATE DATABASE schoolportal_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'schoolportal'@'localhost' IDENTIFIED BY 'strong-random-password';
GRANT ALL PRIVILEGES ON schoolportal_prod.* TO 'schoolportal'@'localhost';
FLUSH PRIVILEGES;
```

## 4. Deploy Application Files

Canonical production path on the OVH VPS:

```bash
sudo mkdir -p /var/www/schoolportal
sudo chown -R $USER:www-data /var/www/schoolportal
cd /var/www/schoolportal
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
APP_URL=https://schoolportal.pagescorch.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=schoolportal_prod
DB_USERNAME=schoolportal
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

Build, migrate, and seed:

```bash
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder --force
php artisan db:seed --class=DemoDataSeeder --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`DatabaseSeeder` creates RBAC and the Super Admin user. `DemoDataSeeder` is
**required** for the MCA demo because demo schools (SHA, SHB, SHC) are only
auto-loaded when `APP_ENV=local`. On production, run `DemoDataSeeder` explicitly
as shown above.

Set permissions:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
```

## 5. Nginx Site Configuration

Create `/etc/nginx/sites-available/schoolportal`:

```nginx
server {
    listen 80;
    server_name schoolportal.pagescorch.com;
    root /var/www/schoolportal/public;

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
sudo ln -s /etc/nginx/sites-available/schoolportal /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d schoolportal.pagescorch.com
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

Record `https://schoolportal.pagescorch.com`, date, PHP/MySQL versions, and
screenshot filenames in `MCA_REPORT_NOTES.md`.

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
mysqldump -u schoolportal -p schoolportal_prod > backup-$(date +%F).sql
tar -czf storage-backup-$(date +%F).tar.gz storage/app/private
```

Rollback:

1. Restore database from SQL dump.
2. Redeploy previous Git tag or commit.
3. Run `php artisan config:cache`.

Application backup archives are also available from **System Operations → Backup
Management** inside the Super Admin UI.

## 9. Security Checklist

Production verification on 2026-06-25:

- [x] `APP_DEBUG=false`
- [x] HTTPS enabled (`schoolportal.pagescorch.com`)
- [x] `SESSION_SECURE_COOKIE=true`
- [x] Strong `APP_KEY` and database passwords (server `.env` only)
- [x] `.env` not committed to Git
- [x] `storage/app/private` not web-accessible
- [ ] Demo passwords rotated after public demo period (recommended before viva)
- [ ] Firewall allows only 22, 80, 443 (confirm on VPS)

## 10. Production Update Procedure (post-freeze)

To deploy a new `main` commit without changing infrastructure:

```bash
cd /var/www/schoolportal
mysqldump -u schoolportal -p schoolportal_prod > ~/backup-$(date +%F)-pre-deploy.sql
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

Rerun §6 smoke tests after every update.

## 11. Qollabb Milestone 6 Submission Text

Copy into Qollabb when smoke tests and report draft are complete:

> Deployed **School Portal** at `https://schoolportal.pagescorch.com` on a cloud
> server with HTTPS, MySQL 8, and production Laravel configuration. Ran
> migrate/seed, built frontend assets, and verified smoke tests for Super Admin,
> School Admin, Teacher, and Accountant roles. Demonstrated concurrent
> two-school tenant isolation with SHA and SHB demo schools. Full automated
> regression suite passes 394 tests locally. Final report PDF, presentation, and
> GitHub/live-demo links submitted on Qollabb.

## 12. Related Documents

* `INSTALLATION_GUIDE.md`
* `MCA_SUBMISSION_CHECKLIST.md`
* `MCA_SUBMISSION_MASTER_PLAN.md`

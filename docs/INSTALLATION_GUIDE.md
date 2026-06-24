# Installation Guide

Version: 1.0  
Status: Active  
Project: Multi-Tenant School Administration Management SaaS Platform

This guide covers local developer installation for MCA demonstration, testing, and
report evidence. Production deployment for **School Portal** at
`https://schoolportal.pagescorch.com` is documented in the frozen
`DEPLOYMENT_GUIDE.md` (v1.1).

## 1. Prerequisites

| Requirement | Version |
| ----------- | ------- |
| PHP | 8.4 with extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `tokenizer`, `xml`, `zip` |
| Composer | 2.x |
| Node.js | 20 LTS or newer |
| npm | 10+ |
| MySQL | 8.0 (recommended for production parity) or SQLite for quick local runs |
| Git | 2.x |

Optional: `zip` PHP extension for platform backup archives in System Operations.

## 2. Clone And Configure

```bash
git clone https://github.com/abdulbaquee/multi-tenant-school-saas.git schoolportal
cd schoolportal
cp .env.example .env
composer install
php artisan key:generate
```

Edit `.env` for local MySQL:

```dotenv
APP_NAME="School Portal"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=schoolportal
DB_USERNAME=your_user
DB_PASSWORD=your_password

SESSION_DRIVER=database
FILESYSTEM_DISK=local
```

Create the database:

```sql
CREATE DATABASE schoolportal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 3. Migrate, Seed, And Build Assets

```bash
php artisan migrate:fresh --seed
npm install
npm run build
php artisan storage:link
```

`migrate:fresh --seed` loads canonical RBAC roles, Super Admin, and three demo
schools (SHA, SHB, SHC) when `APP_ENV=local`. Demo logins are listed in
`Dummy-Logins-for-multiple-schools.md` at the repository root.

## 4. Run The Application

Terminal 1:

```bash
php artisan serve
```

Terminal 2 (only if you change frontend assets during development):

```bash
npm run dev
```

Open `http://127.0.0.1:8000` and sign in as Super Admin or a school user.

## 5. Verify Installation

| Check | Command or action |
| ----- | ----------------- |
| Application boots | Visit `/login` |
| Database connected | `php artisan migrate:status` |
| Demo schools exist | Log in as `admin.sha@example.com` |
| Tenant isolation | Log in as School A and School B admins separately |
| Automated tests | `php artisan test` (uses SQLite `:memory:`; does not touch your dev DB) |

Expected test result at release gate: **394 tests passed**.

## 6. Private Storage

Student photos and backup archives use the `local` disk (`storage/app/private`).
School logos may use the public disk after `php artisan storage:link`. Do not
move private files to `public/`.

## 7. Troubleshooting

| Issue | Fix |
| ----- | --- |
| `500` after login | Run `php artisan config:clear` and verify `APP_KEY` is set |
| Missing styles/charts | Run `npm run build` |
| Session errors | Ensure `sessions` table exists (`php artisan migrate`) |
| Permission denied on storage | `chmod -R ug+rwx storage bootstrap/cache` |
| Backup create fails | Install PHP `zip` extension |

## 8. Related Documents

* `DEPLOYMENT_GUIDE.md` — production deployment at `schoolportal.pagescorch.com`
* `MCA_SUBMISSION_CHECKLIST.md` — deadline checklist
* `Dummy-Logins-for-multiple-schools.md` — demonstration credentials

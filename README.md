# IDS ChildShield Climate AI – Backend API

> A Laravel 12 REST API that bridges real-time climate intelligence with child-safety communication channels (SMS, USSD, and WhatsApp) for communities across Mozambique and Sub-Saharan Africa.

---

## Table of Contents

- [About the Project](#about-the-project)
- [Tech Stack](#tech-stack)
- [Architecture Overview](#architecture-overview)
- [Prerequisites](#prerequisites)
- [Local Development Setup](#local-development-setup)
- [Environment Variables](#environment-variables)
- [Running in Production](#running-in-production)
- [API Documentation](#api-documentation)
- [Testing](#testing)
- [Project Structure](#project-structure)
- [Contributing](#contributing)
- [License](#license)

---

## About the Project

**ChildShield Climate AI** is the backend engine for the ChildShield platform — a system designed to deliver AI-powered, climate-aware alerts and guidance to caregivers, community health workers, and local authorities to protect children from weather-related health risks.

The API:

- Ingests real-time and forecast climate data from **OpenWeatherMap** and **Tomorrow.io**
- Uses **OpenAI** to generate context-appropriate alert messages in local languages
- Distributes those messages via **Africa's Talking** (USSD `*123#` & SMS) and **WhatsApp** (Baileys in dev, 360dialog WABA in production)
- Enforces fine-grained access control with **JWT authentication** and **Spatie role/permission** management
- Stores geo-referenced data in **PostgreSQL + PostGIS** to support location-aware alerting
- Exposes auto-generated interactive API documentation via **Scramble**

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 (PHP 8.2+) |
| Database | PostgreSQL + PostGIS |
| Cache / Queue | Redis |
| Authentication | JWT (`tymon/jwt-auth`) + Laravel Sanctum |
| Authorization | Spatie Laravel Permission |
| Climate APIs | OpenWeatherMap, Tomorrow.io |
| AI | OpenAI API |
| Messaging | Africa's Talking (SMS/USSD), WhatsApp via Baileys / 360dialog WABA |
| API Docs | Dedoc Scramble |
| Excel Export | Maatwebsite Excel |
| Frontend Assets | Vite |
| Dev Tools | Laravel Sail, Pint, Pail, PHPUnit |

---

## Architecture Overview

```
Client (Web Dashboard / Mobile App / USSD / WhatsApp)
        │
        ▼
  Laravel API (REST)
        │
  ┌─────┴───────┐
  │             │
JWT Auth    Spatie RBAC
        │
  ┌─────┴──────────────────────┐
  │            │               │
PostgreSQL   Redis Queue    External APIs
(+PostGIS)  (jobs/cache)  (OpenAI, Weather,
                           AT, WhatsApp)
```

Outbound notifications are dispatched as queued jobs, keeping HTTP responses fast while delivery happens asynchronously in the background.

---

## Prerequisites

Make sure the following are installed before you begin:

- **PHP** 8.2 or higher
- **Composer** 2.x
- **Node.js** 18+ and **npm**
- **PostgreSQL** 15+ with the **PostGIS** extension
- **Redis** 7+
- **Git**

> **Tip:** You can use [Laravel Sail](https://laravel.com/docs/sail) (Docker) to skip manual installation of PostgreSQL and Redis. See the [Sail section](#optional-using-laravel-sail-docker) below.

---

## Local Development Setup

### 1. Clone the repository

```bash
git clone https://github.com/ids-devs/laravel_api_childshild.git
cd laravel_api_childshild
```

### 2. One-command setup

The project ships with a Composer `setup` script that handles the full bootstrap:

```bash
composer run setup
```

This single command will:
- Install all PHP dependencies via Composer
- Copy `.env.example` → `.env` (if `.env` doesn't exist yet)
- Generate the application key (`APP_KEY`)
- Run all database migrations
- Install Node.js dependencies
- Build front-end assets with Vite

### 3. Configure your environment

Open `.env` and fill in the required values (see [Environment Variables](#environment-variables)). At minimum, set your database credentials and generate a JWT secret:

```bash
php artisan jwt:secret
```

### 4. Enable PostGIS on your database

```sql
-- Connect to your PostgreSQL database and run:
CREATE EXTENSION IF NOT EXISTS postgis;
```

### 5. Seed the database

```bash
php artisan db:seed
```

The default admin credentials are taken from your `.env`:
- **Email:** value of `ADMIN_EMAIL` (default: `admin@childshield.mz`)
- **Password:** value of `ADMIN_PASSWORD`

### 6. Start the development server

```bash
composer run dev
```

This spins up three concurrent processes:
- `php artisan serve` — the Laravel HTTP server at `http://localhost:8000`
- `php artisan queue:listen` — the background job worker
- `npm run dev` — Vite for hot-reloading assets

---

### Optional: Using Laravel Sail (Docker)

If you prefer Docker, Sail bundles PostgreSQL, Redis, and Mailpit automatically:

```bash
# Install dependencies without a local PHP (requires Docker)
docker run --rm -u "$(id -u):$(id -g)" \
  -v "$(pwd):/var/www/html" -w /var/www/html \
  laravelsail/php82-composer:latest \
  composer install --ignore-platform-reqs

# Start all services
./vendor/bin/sail up -d

# Run setup inside the container
./vendor/bin/sail composer run setup
./vendor/bin/sail artisan jwt:secret
```

---

## Environment Variables

Key variables you must configure in `.env`:

### Core

| Variable | Description | Example |
|---|---|---|
| `APP_NAME` | Application name | `ChildShield Climate AI` |
| `APP_ENV` | Environment | `local` / `production` |
| `APP_KEY` | Laravel encryption key (auto-generated) | |
| `APP_URL` | Base URL of the API | `https://api.childshield.mz` |
| `APP_TIMEZONE` | Server timezone | `Africa/Maputo` |
| `PHONE_ENCRYPTION_KEY` | Separate key for encrypting phone numbers at rest | |

### Database (PostgreSQL + PostGIS)

| Variable | Description |
|---|---|
| `DB_HOST` | PostgreSQL host |
| `DB_PORT` | PostgreSQL port (default `5432`) |
| `DB_DATABASE` | Database name (e.g. `childshield`) |
| `DB_USERNAME` | Database user |
| `DB_PASSWORD` | Database password |

### JWT

| Variable | Description |
|---|---|
| `JWT_SECRET` | Generated by `php artisan jwt:secret` |
| `JWT_TTL` | Access token lifetime in minutes (default `60`) |
| `JWT_REFRESH_TTL` | Refresh token lifetime in minutes (default `20160` = 2 weeks) |

### Messaging

| Variable | Description |
|---|---|
| `AT_USERNAME` | Africa's Talking username (`sandbox` for dev) |
| `AT_API_KEY` | Africa's Talking API key |
| `AT_SENDER_ID` | SMS sender name |
| `AT_USSD_CODE` | USSD short code (e.g. `*123#`) |
| `WA_DRIVER` | WhatsApp driver: `baileys` (dev) or `waba` (prod) |
| `WA_BAILEYS_URL` | Local Baileys service URL (e.g. `http://localhost:3000`) |
| `WA_WABA_URL` | 360dialog WABA endpoint for production |
| `WA_WABA_TOKEN` | 360dialog API token |

### Climate & AI

| Variable | Description |
|---|---|
| `OPENWEATHER_API_KEY` | OpenWeatherMap API key |
| `TOMORROW_API_KEY` | Tomorrow.io API key |
| `OPENAI_API_KEY` | OpenAI API key (for message generation) |

---

## Running in Production

### 1. Server requirements

- PHP 8.2+ with extensions: `pdo_pgsql`, `redis`, `gd`, `zip`, `mbstring`, `xml`
- PostgreSQL 15+ with PostGIS
- Redis 7+
- A process manager: **Supervisor** (for queue workers) and **Nginx** or **Caddy** as a reverse proxy

### 2. Deploy

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

php artisan migrate --force
```

### 3. Environment

Set the following in your production `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.childshield.mz

LOG_LEVEL=error
WA_DRIVER=waba

# Use strong, unique credentials
ADMIN_EMAIL=your-admin@childshield.mz
ADMIN_PASSWORD=<strong-password>
```

### 4. Queue workers (Supervisor)

Create `/etc/supervisor/conf.d/childshield-worker.conf`:

```ini
[program:childshield-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/childshield/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/childshield/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start childshield-worker:*
```

### 5. Scheduled tasks (Cron)

Add this to the server's crontab (`crontab -e`):

```cron
* * * * * cd /var/www/childshield && php artisan schedule:run >> /dev/null 2>&1
```

### 6. Nginx example

```nginx
server {
    listen 443 ssl;
    server_name api.childshield.mz;

    root /var/www/childshield/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## API Documentation

Interactive API docs are auto-generated by **Scramble** and available at:

```
http://localhost:8000/docs/api      # Development
https://api.childshield.mz/docs/api # Production (restrict access if needed)
```

---

## Testing

```bash
# Run the full test suite
composer run test

# Or directly
php artisan test

# With coverage (requires Xdebug or PCOV)
php artisan test --coverage
```

Tests use an in-memory SQLite database by default (see `phpunit.xml`), so no separate test database setup is required.

---

## Project Structure

```
├── app/
│   ├── Http/
│   │   ├── Controllers/    # API controllers
│   │   └── Middleware/     # Auth, throttling, etc.
│   ├── Models/             # Eloquent models
│   ├── Jobs/               # Queued jobs (alerts, notifications)
│   └── Services/           # Business logic (Climate, WhatsApp, AT)
├── database/
│   ├── migrations/         # PostgreSQL + PostGIS schema
│   └── seeders/            # Role, permission, and admin seeders
├── routes/
│   └── api.php             # All API endpoints
├── .docs/                  # Internal documentation / specs
├── .env.example            # Environment variable template
└── composer.json           # PHP dependencies and scripts
```

---

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feat/your-feature`
3. Commit your changes: `git commit -m "feat: describe your change"`
4. Push to your branch: `git push origin feat/your-feature`
5. Open a Pull Request against `main`

Please run `./vendor/bin/pint` to ensure code style compliance before submitting.

---

## License

This project is open-sourced under the [MIT License](LICENSE.txt).

---

> Built with ❤️ by [IDS Devs](https://github.com/ids-devs) · Mozambique 🇲🇿

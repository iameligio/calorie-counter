# 🥗 Calorie Counter (MyFitnessPal Clone)

A personal health companion that calculates your daily calorie goals (using the
Mifflin-St Jeor formula), tracks your food logs, and visualises progress toward
your target weight.

It's built as **two apps working together**:

- **Backend (Laravel + Filament admin)** — the API and database. Stores users,
  foods, and logs; does the calorie math. Includes an admin panel.
- **Frontend (React + Vite)** — the dashboard, charts, and forms you interact with.

There are two ways to run it:

| | Best for | Effort |
|---|---|---|
| 🐳 **[Docker](#-option-a-docker-recommended)** | Servers & local dev — one command, no manual setup | Easiest |
| 🛠️ **[Manual / shared hosting](#-option-b-manual-hosting-cpanel-shared-hosts)** | cPanel and classic PHP hosts | More steps |

---

## 🔐 Before you start: keep your secrets safe

This project **never commits real credentials**. A few rules:

- **Never commit a `.env` file.** Only `.env.example` templates are tracked in
  git — they contain blank placeholders, no real values. The `.gitignore`
  already blocks every real `.env`.
- **Generate a fresh `APP_KEY`** for every deployment (`php artisan key:generate`).
  Never reuse the example one.
- **Use strong, unique database passwords.** The examples below use
  `CHANGE_ME` placeholders — replace them with your own.
- **API tokens** (USDA, etc.) belong in your untracked `.env`, never in code or
  this README.

> If you ever accidentally commit a secret, rotate it immediately — assume
> anything pushed to GitHub is public forever.

---

## 🐳 Option A: Docker (Recommended)

Everything (PHP, MySQL, Redis, Nginx, Node) runs in containers. You only need
[Docker](https://docs.docker.com/get-docker/) installed — nothing else.

### Local development

```bash
git clone <your-repo-url> myfitnesspal
cd myfitnesspal

make setup        # copies .env templates (edit them with your values)
make dev          # builds + starts everything
```

Then open:

- **Frontend:** http://localhost:5173
- **API:** http://localhost:8000

Your code is live-mounted — edit files on your machine and changes apply
instantly (hot reload for React, no rebuild for PHP).

Handy commands (run `make help` for the full list):

```bash
make dev-logs        # tail logs
make migrate         # run database migrations
make dev-fresh       # wipe DB and re-migrate + seed
make shell           # open a shell in the API container
make dev-down        # stop everything
```

### Production deployment

On your server (any VPS with Docker installed):

```bash
git clone <your-repo-url> myfitnesspal
cd myfitnesspal

make prod-setup      # copies env templates
```

Now **edit the two generated files** with your real values:

1. **`.env`** (Docker / database passwords):
   ```env
   DB_ROOT_PASSWORD=CHANGE_ME
   DB_DATABASE=myfitnesspal
   DB_USERNAME=app
   DB_PASSWORD=CHANGE_ME
   ```

2. **`backend/.env`** (Laravel app config — note `DB_PASSWORD` must match above):
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=                       # fill this in — see next step
   APP_URL=https://yourdomain.com

   DB_HOST=db                     # Docker service name — do not change
   DB_DATABASE=myfitnesspal
   DB_USERNAME=app
   DB_PASSWORD=CHANGE_ME

   SANCTUM_STATEFUL_DOMAINS=yourdomain.com
   FRONTEND_URL=https://yourdomain.com

   # Required — browser origins allowed to call the API (comma separated).
   # Anything not listed is refused, so never leave dev hosts in here.
   CORS_ALLOWED_ORIGINS=https://yourdomain.com

   # API token lifetime in minutes (default 14 days).
   SANCTUM_TOKEN_EXPIRATION=20160
   ```

Generate an app key and paste it into `backend/.env`:

```bash
docker run --rm -v "$PWD/backend:/app" -w /app composer:2.7 \
  sh -c "composer install -q && php artisan key:generate --show"
```

Then build and launch:

```bash
make prod-build      # build images (~3 min first time)
make prod-up         # start; migrations run automatically
```

Your app is now live on **port 80** — the frontend at `/` and the API at `/api`,
served behind a single Nginx with gzip, caching, and security headers.

**Updating later** (after pushing new code):

```bash
make prod-deploy     # git pull, rebuild, restart, migrate
```

**Populate the food database** from the free USDA dataset (~8,900 foods):

```bash
# add USDA_API_KEY=your_key to backend/.env first
# (free instant key at https://api.nal.usda.gov/)
make prod-seed-usda
```

> **Going public?** Put a reverse proxy with HTTPS (e.g. Caddy, Traefik, or
> Nginx + Let's Encrypt) in front of port 80, or terminate TLS at your cloud
> load balancer. Never run a production login form over plain HTTP.

---

## 🛠️ Option B: Manual Hosting (cPanel / shared hosts)

If you're on classic PHP hosting without Docker, you can host the two parts
separately. We usually put the backend on `api.yourwebsite.com` and the frontend
on `app.yourwebsite.com`.

### Step 1 — Backend (Laravel)

1. Upload the `backend` folder to your server. If using cPanel, point the
   domain's **Document Root** at `backend/public` (not `backend`).
2. Copy `backend/.env.example` → `backend/.env` and fill in your **live database
   credentials** (your own values, never shared ones).
3. Set the environment in `backend/.env`:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://api.yourwebsite.com
   FRONTEND_URL=https://app.yourwebsite.com
   SANCTUM_STATEFUL_DOMAINS=app.yourwebsite.com
   CORS_ALLOWED_ORIGINS=https://app.yourwebsite.com   # required
   SANCTUM_TOKEN_EXPIRATION=20160                     # token lifetime, minutes
   ```
4. From the backend folder via SSH:
   ```bash
   composer install --optimize-autoloader --no-dev
   php artisan key:generate
   php artisan migrate --force --seed
   ```

### Step 2 — Frontend (React)

1. Copy `frontend/.env.example` → `frontend/.env` and set the API URL:
   ```env
   VITE_API_BASE_URL=https://api.yourwebsite.com/api
   ```
2. Build the static files on your computer:
   ```bash
   npm install
   npm run build
   ```
3. Upload **the contents of the generated `dist` folder** to where
   `app.yourwebsite.com` is served.
4. For SPA routing on **Apache**, add a `.htaccess` in that folder:
   ```apache
   <IfModule mod_rewrite.c>
     RewriteEngine On
     RewriteBase /
     RewriteRule ^index\.html$ - [L]
     RewriteCond %{REQUEST_FILENAME} !-f
     RewriteCond %{REQUEST_FILENAME} !-d
     RewriteRule . /index.html [L]
   </IfModule>
   ```

---

## ⚙️ Admin panel

A Filament admin panel ships built-in for editing system settings and API rate
limits. Access it at `https://api.yourwebsite.com/admin` (or
`http://localhost:8000/admin` locally). Only users with the **admin** flag can
log in.

## 🍱 Food database

Foods come from the free [USDA FoodData Central](https://fdc.nal.usda.gov) API —
the same authoritative source MyFitnessPal uses. Get a free API key, add
`USDA_API_KEY=...` to `backend/.env`, then run the seeder:

```bash
php artisan db:seed --class=UsdaFoodSeeder      # or: make prod-seed-usda
```

## 🧪 Running tests

```bash
cd backend && php artisan test        # or: make shell, then php artisan test
```

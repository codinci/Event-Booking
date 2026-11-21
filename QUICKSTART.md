# Event Booking Platform - Quick Start Guide

## 🚀 Get Running in 5 Minutes

### Prerequisites
- Docker & Docker Compose installed
- Git installed
- Clone this repository

### Step 1: Install Dependencies
```bash
composer setup
```

This command installs PHP dependencies, creates `.env`, generates the app key, runs migrations, and builds frontend assets.

### Step 2: Start Docker
```bash
docker compose up -d
```

Containers will start:
- **App Server** → http://localhost:8000
- **Vite Dev Server** → http://localhost:5173 (HMR for frontend)
- **Database** → MySQL on port 3307
- **Cache** → Redis on port 6380
- **Email Testing** → http://localhost:8025 (Mailpit)

### Step 3: Create Test Users
```bash
docker compose exec app php artisan db:seed
```

Or create test users manually:
```bash
docker compose exec app php artisan tinker

# In Tinker shell:
> User::factory(1)->create(['email' => 'user@test.com', 'password' => bcrypt('password')])
> User::factory(1)->organizer()->create(['email' => 'organizer@test.com', 'password' => bcrypt('password')])
> User::factory(1)->admin()->create(['email' => 'admin@test.com', 'password' => bcrypt('password')])
> exit
```

### Step 4: Create Test Events
```bash
docker compose exec app php artisan tinker

# In Tinker shell:
> $organizer = User::where('role', 'organizer')->first()
> Event::factory(3)->for($organizer)->published()->create()
> exit
```

### Step 5: Access the Application

**Home Page:**
- http://localhost:8000

**Public Routes:**
- Browse events: http://localhost:8000/events
- Event detail: http://localhost:8000/events/1

**Organizer Routes (login as organizer@test.com):**
- Organizer dashboard: http://localhost:8000/dashboard
- Create event: http://localhost:8000/organizer/events/create
- Manage events: http://localhost:8000/organizer/events

**Admin Routes (login as admin@test.com):**
- Admin dashboard: http://localhost:8000/admin
- Approve organizers: http://localhost:8000/admin/organizer-requests
- Manage refunds: http://localhost:8000/admin/refunds

**User Routes (login as user@test.com):**
- My bookings: http://localhost:8000/bookings
- Booking details: http://localhost:8000/bookings/1

---

## 🧪 Testing

### Run Backend Tests
```bash
composer test
```

### Run Frontend Type Check
```bash
npm run types:check
```

### Run Full CI Suite
```bash
composer ci:check
```

---

## 📧 Testing Email

Mailpit captures all outgoing emails. Access the UI at:
- http://localhost:8025

This is useful for testing password reset, booking confirmations, etc.

---

## 💳 M-PESA Testing

The `.env` file includes Daraja sandbox credentials. When testing payments:

1. Use test phone number from `.env`: **VITE_DARAJA_TEST_PHONE**
2. Payment will be processed against Safaricom sandbox API
3. Refunds use B2C API and will show in Daraja logs

---

## 🛑 Stopping the App

```bash
# Stop all containers
docker compose down

# Stop and remove all data
docker compose down -v
```

---

## 🐛 Troubleshooting

### App not starting
```bash
# Check logs
docker compose logs app

# Rebuild containers
docker compose down
docker compose build --no-cache
docker compose up
```

### Database connection issues
```bash
# Reset database
docker compose exec app php artisan migrate:refresh

# Re-seed data
docker compose exec app php artisan db:seed
```

### Frontend not loading styles
```bash
# Rebuild frontend
docker compose restart vite

# Or rebuild manually
npm run build
```

### Port already in use
```bash
# Change port in docker-compose.yml or run:
docker compose -p different_name up
```

---

## 📸 Capturing Screenshots

For documentation:

1. Log in with test credentials
2. Navigate to each page in the README's screenshot table
3. Use your browser's screenshot tool (Print Screen, Chrome DevTools)
4. Save to `docs/screenshots/` directory
5. Commit to repository

---

## 📚 Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│                    Browser (Vue 3)                       │
└──────────────────────┬──────────────────────────────────┘
                       │ HTTP (Server-Driven Rendering)
┌──────────────────────▼──────────────────────────────────┐
│          Nginx (Port 8000) + Laravel App                │
│                 (Inertia.js Bridge)                     │
└──────────┬──────────────────────────────────┬───────────┘
           │                                  │
      ┌────▼────┐                     ┌──────▼─────┐
      │  MySQL  │                     │   Redis    │
      │Database │                     │   Cache    │
      └─────────┘                     │   Queue    │
                                      └────────────┘
```

**Key Components:**
- **PHP 8.3 + Laravel 13**: Backend API and logic
- **Inertia.js**: Server-side Vue component rendering
- **Vue 3 + TypeScript**: Frontend UI components
- **Tailwind CSS 4**: Styling
- **Redis**: Caching and async job queue
- **MySQL**: Data persistence
- **Vite**: Fast development bundler with HMR

---

## 🚢 Deployment Checklist

- [ ] Set `APP_DEBUG=false`
- [ ] Update `APP_URL` to production domain
- [ ] Generate secure `APP_KEY`: `php artisan key:generate`
- [ ] Configure MySQL with production credentials
- [ ] Set up M-PESA production credentials
- [ ] Configure HTTPS/SSL
- [ ] Run `npm run build` for production assets
- [ ] Set up monitoring/logging
- [ ] Run `php artisan migrate --force`

---

## 📖 More Information

- Full README: See [README.md](./README.md)
- API Routes: See [routes/web.php](./routes/web.php)
- Database Schema: See [database/migrations/](./database/migrations/)
- Tests: See [tests/](./tests/)

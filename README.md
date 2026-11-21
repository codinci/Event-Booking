# Event Booking Platform

A modern, full-featured event booking and ticketing system built with Laravel, Vue 3, TypeScript, and Tailwind CSS. Supports event creation, ticket management, secure bookings, and M-PESA payment integration for seamless event transactions.

## Features

### 🎫 Event Management
- **For Everyone**: Browse and discover published events with detailed descriptions and ticket availability
- **For Organizers**: Create and manage events with custom ticket types, pricing, and availability
- **For Admins**: Oversee all events and manage organizer onboarding requests

### 💳 Booking & Payments
- Secure booking system with real-time ticket availability tracking
- Multiple ticket types per event with individual pricing
- M-PESA payment integration (Safaricom Daraja API) for Kenya-based transactions
- Automated payment callbacks and confirmation handling
- Digital receipts for completed bookings

### 👥 User Roles & Permissions
- **Regular Users**: Browse events, create bookings, manage personal bookings, process refunds
- **Organizers**: Create and publish events, view event analytics, manage ticket types and pricing
- **Admins**: Approve organizer requests, process refunds, oversee platform activities

### 📊 Dashboards
- **Organizer Dashboard**: View event performance, booking statistics, and revenue
- **Admin Dashboard**: Monitor platform activity and pending approvals
- **User Dashboard**: Track personal bookings and payment history

### 💰 Refund Management
- Users can request refunds for booked events
- Admins review and approve/reject refund requests
- Automated M-PESA B2C transfers for approved refunds

## Tech Stack

### Backend
- **PHP 8.3+** - Modern PHP with strict typing
- **Laravel 13** - Full-featured web framework with Eloquent ORM
- **Redis** - Caching and queue management
- **MySQL 8.4** - Relational database (Docker) / SQLite (local dev)

### Frontend
- **Vue 3** - Progressive JavaScript framework
- **TypeScript** - Type-safe JavaScript
- **Tailwind CSS 4** - Utility-first CSS framework
- **Inertia.js** - Server-side rendering of Vue components (Laravel ↔ Vue bridge)
- **Vite** - Next-generation frontend build tool with HMR

### DevOps & Architecture
- **Docker Compose** - Multi-container orchestration:
  - PHP 8.3 application container
  - Nginx 1.27 web server
  - MySQL 8.4 database with health checks
  - Redis 7 for caching and queues
  - Dedicated queue worker for async jobs
  - Laravel scheduler for scheduled tasks
  - Vite dev server with HMR on port 5173
  - Mailpit for local email testing
- **PHP CLI Tools**: Artisan, Composer, Pint (code formatter), PHPStan (static analysis)

### Payment & Integrations
- **M-PESA (Daraja API)** - Mobile money payments and B2C refunds
- **AWS S3** - Optional cloud storage for file uploads

## Architecture Overview

### Request Flow
```
Client Request
    ↓
Nginx (Port 8000)
    ↓
PHP Laravel App (Inertia)
    ↓
Database (MySQL) / Cache (Redis)
    ↓
Vue 3 Component (Rendered Server-side)
    ↓
Browser (Enhanced with Vite HMR)
```

### Design Patterns
1. **Server-Driven UI with Inertia.js**
   - Laravel renders Vue components server-side
   - Props passed to components as JSON
   - Navigation handled by Laravel routes, not JavaScript
   - Benefit: Full backend control, SEO-friendly, type-safe with TypeScript

2. **Service-Oriented Architecture**
   - Business logic in `app/services/` classes
   - Controllers remain thin, delegating to services
   - Easy to test and maintain
   - Example: `BookingService`, `EventService`, `PaymentService`

3. **Eloquent ORM with Relationships**
   - Type-safe model relationships
   - Query builder with elegant syntax
   - Database migrations for version control

4. **Async Processing**
   - Queue worker for long-running tasks (email, payments, refunds)
   - Redis-backed queues for reliability
   - Scheduled tasks (Laravel Scheduler)

5. **Role-Based Access Control (RBAC)**
   - Middleware for `auth`, `verified`, `organizer`, `admin`
   - Model abilities for fine-grained permissions
   - Fortify for authentication scaffolding

### Data Model
```
User
├── Event (organizer relationship)
├── Booking
└── Refund Request

Event
├── TicketType
├── Booking (many)
└── Organizer (User)

Booking
├── BookingItem (ticket quantities)
├── PaymentAttempt
└── Refund (optional)
```

### Key Design Decisions

**Why Inertia.js over SPA?**
- Full backend control over rendering and routing
- Real-time server-side data synchronization
- No API versioning complexity
- SEO-friendly without extra work
- Smaller JavaScript bundle

**Why Redis for Queues?**
- Faster than database-backed queues
- Reliable with persistence (`appendonly yes`)
- In-memory performance for real-time operations
- Health checks ensure availability

**Why Dedicated Queue & Scheduler Containers?**
- Separates concerns from web request handler
- Allows independent scaling
- Queue worker processes background jobs reliably
- Scheduler handles cron-like tasks (e.g., event reminders)

## Prerequisites

Before getting started, ensure you have:

- **Docker & Docker Compose** - [Install](https://docs.docker.com/get-docker/)
- **PHP 8.3+** (for local CLI tools, optional if using Docker)
- **Composer** - [Install](https://getcomposer.org/)
- **Node.js 20+** - [Install](https://nodejs.org/)
- **Git** - [Install](https://git-scm.com/)

## Local Setup

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/event-booking.git
cd event-booking
```

### 2. Install Dependencies & Generate Configuration

```bash
composer setup
```

This command will:
- Install PHP dependencies via Composer
- Create `.env` file from `.env.example` (if not exists)
- Generate the application key
- Run database migrations
- Install npm dependencies
- Build frontend assets

### 3. Start the Development Server

#### Option A: Using Docker Compose (Recommended)

```bash
docker compose up
```

This starts:
- **Laravel Development Server** at `http://localhost:8000`
- **Vite Dev Server** (HMR) on port 5173
- **MySQL Database** on port 3306
- **Redis Cache** on port 6379
- **Queue Worker** for background jobs

#### Option B: Local PHP Development

```bash
# Terminal 1: Start PHP development server
php artisan serve

# Terminal 2: Start Vite dev server for frontend HMR
npm run dev
```

### 4. Configure Environment Variables

Edit `.env` with your settings:

```env
# App Configuration
APP_NAME="Event Booking"
APP_DEBUG=true
APP_URL=http://localhost:8000

# M-PESA Configuration (Daraja)
# See .env.example for details
DARAJA_BUSINESS_SHORT_CODE=your_shortcode
DARAJA_CONSUMER_KEY=your_consumer_key
DARAJA_CONSUMER_SECRET=your_consumer_secret
DARAJA_INITIATOR_PASSWORD=your_initiator_password
DARAJA_CALLBACK_URL=https://your-domain.com/payments/daraja/callback

# Database (if not using Docker)
DB_CONNECTION=sqlite
# Or for MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_DATABASE=event_booking
# DB_USERNAME=root
# DB_PASSWORD=
```

See [.env.example](./.env.example) for all available configuration options.

### 5. Access the Application

- **Home** - http://localhost:8000
- **Browse Events** - http://localhost:8000/events
- **Admin Panel** - http://localhost:8000/admin (requires admin user)

## Running Tests

### PHPUnit (Backend Tests)

```bash
# Run all tests
composer test

# Run specific test class
php artisan test --filter=BookingServiceTest

# Run with coverage
php artisan test --coverage
```

### Frontend Checks

```bash
# Type checking
npm run types:check

# Build check
npm run check

# Fix linting issues
npm run check:fix
```

### Full CI Suite

Run the complete test suite (backend + frontend):

```bash
composer ci:check
```

## Development Workflow

### Code Style & Linting

The project uses **Laravel Pint** for PHP code formatting:

```bash
# Check for issues
composer test

# Auto-fix issues (included in composer test)
php artisan pint
```

### Database Migrations

Create a new migration:

```bash
php artisan make:migration create_something_table
```

Run pending migrations:

```bash
php artisan migrate
```

Rollback last batch:

```bash
php artisan migrate:rollback
```

### Laravel Artisan Commands

Useful commands for development:

```bash
# Create a new controller
php artisan make:controller EventController --resource

# Create a new model with migration
php artisan make:model Event --migration

# Generate Wayfinder route helpers
php artisan wayfinder:generate --with-form

# Access Laravel Tinker (interactive shell)
php artisan tinker
```

### Frontend Development

#### File Structure

```
resources/js/
├── pages/           # Page components (routed)
├── layouts/         # Shared layouts
├── components/      # Reusable components
├── composables/     # Vue composables (hooks)
└── lib/             # Utilities and helpers
```

#### Hot Module Replacement (HMR)

When running `npm run dev`, changes to Vue files automatically reflect in the browser without page reload.

## Deployment

### Environment Variables

Copy and configure `.env.example` for your production environment:

```bash
cp .env.example .env.production
# Edit .env.production with production values
```

**Critical Settings**:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY=` (generate with `php artisan key:generate`)
- Database credentials for MySQL
- M-PESA Daraja credentials for production environment

### Build Frontend Assets

```bash
npm run build
```

### Database

```bash
php artisan migrate --force
```

## Common Issues & Troubleshooting

### "Laravel not found" error

Ensure you've installed dependencies:

```bash
composer install
npm install
```

### Database issues

Ensure the database is initialized:

```bash
php artisan migrate
```

For SQLite, check that `database/database.sqlite` exists and is writable.

### M-PESA payments not working

1. Verify your Daraja credentials in `.env`
2. Check that `DARAJA_CALLBACK_URL` matches your public URL
3. Review payment logs in `storage/logs/`

### Port already in use

If port 8000 is already taken:

```bash
php artisan serve --port=8001
```

For Docker:

```bash
docker compose up -p different_port
```

## Project Structure

```
event-booking/
├── app/
│   ├── Models/              # Eloquent models
│   ├── Http/
│   │   └── Controllers/     # Route controllers
│   └── services/            # Business logic services
├── routes/
│   ├── web.php              # Web routes (public, auth, organizer, admin)
│   └── settings.php         # Account settings routes
├── resources/
│   ├── js/
│   │   ├── pages/          # Inertia page components
│   │   ├── layouts/        # Shared layouts
│   │   ├── components/     # Reusable Vue components
│   │   └── App.vue         # Root app component
│   └── css/                # Tailwind styles
├── database/
│   ├── migrations/         # Database schema
│   └── factories/          # Model factories for testing
├── tests/                  # PHPUnit tests
├── docker-compose.yml      # Docker services
├── vite.config.ts          # Vite configuration
├── tailwind.config.js      # Tailwind CSS config
└── .env.example            # Environment template
```

## Application Features & Workflows

### Public Features (No Login Required)

#### 1. Home Page (`GET /`)
- Hero section with platform introduction
- Call-to-action buttons: "Browse Events" and "Get Started"
- Featured events carousel or highlights
- Platform benefits overview

#### 2. Events Listing (`GET /events`)
- Responsive grid of published events
- Each event card shows:
  - Event cover image/thumbnail
  - Event title
  - Date and time
  - Location
  - Number of available tickets
  - Organizer name
- Filter and search capabilities (optional future enhancement)
- Pagination for large event lists

#### 3. Event Details (`GET /events/{id}`)
- Full event information:
  - Event banner/cover image
  - Detailed description
  - Date, time, and location
  - Organizer information
- Ticket types section showing:
  - Ticket name (e.g., "General Admission", "VIP")
  - Price per ticket
  - Quantity available
  - Quantity remaining
- "Book Now" button (requires login if not authenticated)

#### 4. Booking Form (`GET /events/{id}/book`)
- Displays all available ticket types
- For each ticket type: quantity selector (0-N)
- Attendee details form:
  - Full name
  - Email address
  - Phone number (pre-filled for M-PESA)
- Order summary showing:
  - Ticket breakdown
  - Subtotal per ticket type
  - Total amount in KES
- "Proceed to Payment" button

### Authenticated User Features

#### 5. My Bookings (`GET /bookings`)
- Table/list of user's bookings showing:
  - Event name
  - Booking date
  - Number of tickets
  - Total amount paid
  - Booking status (Confirmed, Pending, Cancelled)
  - Action buttons (View Details, Cancel, Pay, Get Receipt)
- Filter by status (Upcoming, Past, Cancelled)

#### 6. Booking Details (`GET /bookings/{id}`)
- Full booking information:
  - Event details
  - Ticket breakdown with quantity and price
  - Payment status
  - M-PESA transaction reference (if paid)
  - Booking cancellation option (if eligible)
  - Refund request option (if applicable)

#### 7. Payment Form (`POST /bookings/{id}/payment`)
- M-PESA payment gateway integration
- Phone number input pre-filled (from env or user input)
- Amount to pay (shown as KES)
- "Pay with M-PESA" button
- Payment confirmation/loading state

#### 8. Receipt Page (`GET /bookings/{id}/receipt`)
- Professional receipt layout:
  - Event details
  - Booking reference number
  - Ticket breakdown
  - Total amount
  - Payment date and reference
  - QR code (optional for entry)
  - Download/Print options

### Organizer Features (Requires Organizer Role)

#### 9. Organizer Dashboard (`GET /dashboard`)
- Overview cards showing:
  - Total events created
  - Total bookings
  - Revenue (sum of all bookings)
  - Upcoming events count
- Recent bookings table:
  - Event name
  - Booking reference
  - Quantity
  - Amount
  - Date
- Recent events list with quick actions
- Charts/analytics (optional):
  - Bookings over time
  - Revenue by event

#### 10. Events Management (`GET /organizer/events`)
- Table of organizer's events showing:
  - Event name
  - Status (Draft, Published, Cancelled)
  - Ticket types count
  - Total bookings
  - Revenue
  - Action buttons (View, Edit, Publish, Cancel, Delete)
- "Create Event" button

#### 11. Create/Edit Event (`GET/POST /organizer/events/create|{id}/edit`)
- Event form fields:
  - Event title
  - Description (rich text editor)
  - Date and time (date/time pickers)
  - Location
  - Event cover image (upload)
- Ticket types section (dynamic):
  - Add/Remove ticket types
  - For each: Name, Description, Price, Available Quantity

#### 12. Publish Event (`POST /organizer/events/{id}/publish`)
- Confirmation modal showing:
  - Event details preview
  - Ticket types summary
- Confirmation button to make event public

### Admin Features (Requires Admin Role)

#### 13. Admin Dashboard (`GET /admin`)
- Platform overview:
  - Total users
  - Total events
  - Total bookings
  - Total revenue
  - Pending organizer requests count
  - Pending refund requests count
- Quick action cards linking to pending approvals
- System health indicators (if applicable)

#### 14. Organizer Requests (`GET /admin/organizer-requests`)
- Table of pending organizer requests showing:
  - User name and email
  - Request date
  - Status (Pending, Approved, Rejected)
  - Action buttons (Approve, Reject)
- Approve modal: Shows user info and confirmation
- Reject modal: Shows user info and optional rejection reason

#### 15. Refund Management (`GET /admin/refunds`)
- Table of refund requests showing:
  - Booking reference
  - Requester name and email
  - Original amount
  - Request date
  - Status (Pending, Approved, Rejected, Resolved)
  - Action buttons (Approve, Reject, Resolve)
- Approve modal: Preview refund details
- Reject modal: Rejection reason input
- Resolve modal: M-PESA B2C transaction tracking

## Screenshots to Capture

The following screens should be captured and added to `docs/screenshots/`:

| # | Page | URL | Purpose |
|---|------|-----|---------|
| 1 | Home | `GET /` | Platform introduction |
| 2 | Events Listing | `GET /events` | Public event browsing |
| 3 | Event Details | `GET /events/1` | Event information and booking CTA |
| 4 | Booking Form | `GET /events/1/book` | Ticket selection and attendee info |
| 5 | M-PESA Payment | `POST /bookings/1/payment` | Payment gateway integration |
| 6 | Receipt | `GET /bookings/1/receipt` | Booking confirmation |
| 7 | My Bookings | `GET /bookings` (auth required) | User booking history |
| 8 | Organizer Dashboard | `GET /dashboard` (organizer) | Event organizer overview |
| 9 | Create Event | `GET /organizer/events/create` (organizer) | Event creation form |
| 10 | Admin Dashboard | `GET /admin` (admin) | Admin overview panel |
| 11 | Organizer Requests | `GET /admin/organizer-requests` (admin) | Approve organizers |
| 12 | Refund Requests | `GET /admin/refunds` (admin) | Manage refunds |

### How to Capture Screenshots

1. **Start the app**: `docker compose up`
2. **Access http://localhost:8000** in your browser
3. **Create test users** with different roles:
   ```bash
   # In another terminal:
   docker compose exec app php artisan tinker
   
   # Create users:
   User::factory()->create(['email' => 'user@test.com'])
   User::factory()->admin()->create(['email' => 'admin@test.com'])
   User::factory()->organizer()->create(['email' => 'organizer@test.com'])
   
   # Create test events:
   Event::factory()->published()->create(['user_id' => 2])
   ```
4. **Log in** with test user credentials
5. **Navigate** to each URL in the table above
6. **Screenshot** each page and save to `docs/screenshots/{page-name}.png`
7. **Commit** screenshots to the repository

### Test Data
- **Phone number for M-PESA**: Use `+254708374149` (from VITE_DARAJA_TEST_PHONE)
- **Test cards**: Not applicable (uses real M-PESA Daraja sandbox)
- **Admin user creation**: Use factory with `->admin()` method

## Contributing

1. Create a feature branch: `git checkout -b feature/my-feature`
2. Commit changes: `git commit -am 'Add new feature'`
3. Push to branch: `git push origin feature/my-feature`
4. Submit a pull request with a clear description

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support & Documentation

- [Laravel Documentation](https://laravel.com/docs)
- [Vue 3 Documentation](https://vuejs.org/)
- [Inertia.js Documentation](https://inertiajs.com/)
- [M-PESA Daraja API Documentation](https://developer.safaricom.co.ke/apis)

## Credits

Built with [Laravel](https://laravel.com/), [Vue 3](https://vuejs.org/), and hosted on [GitHub](https://github.com/).

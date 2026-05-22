# Inventory

A full-stack inventory management system built with Laravel, Vue.js 3, and Inertia.js.

## Features

- **Products, Categories, Suppliers** — CRUD with search, sort, pagination, and bulk delete
- **Stock Movements** — Stock in / out with transactional safety and locking
- **Purchase Orders** — Full lifecycle: Draft → Approved → Dispatched → Received → Closed (or Cancelled), with partial receipts
- **Dashboard** — KPIs, low-stock alerts, recent movements, recent orders
- **Auth & Roles** — Login + role-based authorization (Admin, Manager, Staff)

## Tech Stack

- **Backend:** PHP 8.3, Laravel 13, Inertia Laravel v3, Wayfinder
- **Frontend:** Vue 3, Inertia Vue v3, TypeScript, Tailwind CSS v4, daisyUI v5, Vite
- **Tooling:** Pint, ESLint, Prettier, vue-tsc, PHPUnit

## Setup

```bash
# Install dependencies
composer install
pnpm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate --seed
```

### Demo Users

| Email               | Password | Role    |
| ------------------- | -------- | ------- |
| admin@example.com   | password | Admin   |
| manager@example.com | password | Manager |
| staff@example.com   | password | Staff   |

## Development

```bash
# Run server, queue, and Vite together
composer dev

# Or individually
php artisan serve
pnpm dev
```

## Build

```bash
pnpm build         # production assets
pnpm build:ssr     # with SSR
```

## Project Structure

```
app/
  Http/Controllers/   # Resource + auth controllers
  Models/             # Eloquent models
  Services/           # PurchaseOrderService, StockMovementService
  Policies/           # Role-based authorization
  Enums/              # Position, PurchaseOrderStatus
resources/js/
  pages/              # Inertia pages
  components/         # Shared Vue components
  layouts/            # AppLayout
routes/web.php        # All routes
```

## Architecture Decisions

- **Denormalized `current_stock`** — Stored directly on the product for fast reads, updated via transactions on every stock movement
- **PO cancellation over deletion** — Purchase orders are cancelled not deleted to preserve audit trail integrity
- **Context-aware PO view** — Show page renders inline editing for draft/approved states, read-only for completed states
- **lockForUpdate on stock writes** — Prevents race conditions when concurrent requests modify the same product stock

# LoteMaps

> **MVP** — Minimum Viable Product for managing land subdivisions and real estate developments.

LoteMaps is a web application for managing real estate developments, lots, clients, and sales proposals. The system allows you to view and manage lots on an interactive map, control the status of each lot, and track the pipeline of sales proposals.

---

## MVP Features

- **Developments (Empreendimentos)** — Registration of developments with geographic location, map image, coordinates, and total area. Status: Active / Inactive.
- **Lots** — Management of lots per development, with code, block, area (m²), price, geometry, and status (Available, Reserved, Sold).
- **Clients** — Registration of clients with contact information and document details.
- **Proposals** — Creation of sales proposals linking clients to one or more lots, with status control, validity, and notes.
- **Authentication** — Login, registration, email verification, and profile management via Laravel Fortify.

---

## Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.4 + Laravel 13 |
| Frontend | Livewire 4 + Flux UI 2 + Alpine.js |
| Styles | Tailwind CSS v4 |
| Authentication | Laravel Fortify |
| Testing | Pest 4 |
| Dev Server | Laravel Herd / Laravel Sail |

---

## Quick Setup

```bash
composer run setup
```

This single command installs dependencies, sets up your `.env`, generates an app key, runs migrations, installs JS packages, and compiles assets.

---

## Manual Installation

```bash
# 1. Clone the repository
git clone <repository-url> lotemaps
cd lotemaps

# 2. Install PHP dependencies
composer install

# 3. Install JS dependencies
npm install

# 4. Configure environment variables
cp .env.example .env
php artisan key:generate

# 5. Run migrations and seeders
php artisan migrate --seed

# 6. Compile assets
npm run build
```

---

## Development

```bash
# Start all services (server, queue, vite) concurrently
composer run dev
```

---

## Testing

```bash
php artisan test --compact
```

---

## Code Style

```bash
# Auto-fix code style with Pint
composer run lint
```

---

## MVP Limitations

The following features are **not yet implemented**:

- Interactive map visualization with lot geometry overlay
- Reports and data export
- Permission management and multiple user profiles
- Integration with external systems (CRM, ERP)
- Automatic notifications and alerts

---

## License

Owner — ZerossB. All rights reserved.
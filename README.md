# LoteMaps

> **MVP** — Minimum Viable Product for managing land subdivisions and real estate developments.

LoteMaps is a web application for managing real estate developments, lots, clients, and sales proposals. The system allows you to view and manage lots on an interactive map, control the status of each lot, and track the pipeline of sales proposals.

---

## MVP Features

- **Developments** — Registration of developments with geographic location, map image, and total area.
- **Lots** — Management of lots by development, with block, area, price, geometry, and status (available, reserved, sold, etc.).
- **Clients** — Registration of clients with contact information and document details.
- **Proposals** — Creation of sales proposals linking clients to one or more lots, with status control, validity, and notes.
- **Authentication** — Login, registration, email verification, and profile management via Laravel Fortify. ---

## Stack

| Layer | Technology |
|--------------|--------------------------------|
| Backend | PHP 8.4 + Laravel 12 |
| Frontend | Livewire 4 + Flux UI + Alpine.js |
| Styles | Tailwind CSS v4 |
| Authentication | Laravel Fortify |
| Testing | Pest 4 |
| Dev Server | Laravel Herd / Laravel Sail |

---

## Installation

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
# Start all services (server, queue, vite)
composer run dev

```

---

## Testing

```bash
php artisan test --compact

```

---

## MVP Limitations

Because it is an MVP, the following Features that are **not yet implemented**:

- Interactive map visualization with plot geometry
- Reports and data export
- Permission management and multiple user profiles
- Integration with external systems (CRM, ERP)
- Automatic notifications and alerts

---

## License

Owner — ZerossB. All rights reserved.
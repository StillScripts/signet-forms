# Signet Forms

A multi-tenant team collaboration platform built with Laravel 13, Filament 5, and Livewire 4.

## Tech Stack

- **PHP** 8.3
- **Laravel** 13
- **Filament** 5 (admin panel & tenancy)
- **Livewire** 4 with Flux UI v2
- **Tailwind CSS** v4
- **Pest** v4 (testing)
- **Vite** 8 (frontend bundling)
- **SQLite** (default local database)

## Features

- **Multi-tenant teams** — Users belong to teams with slug-based routing via Filament's tenant system
- **Role-based access control** — Owner, Admin, and Member roles with granular permissions
- **Team invitations** — Invite members by email with automatic 3-day expiry
- **Authentication** — Registration (with auto-team creation), login, password reset, email verification, and 2FA via Laravel Fortify
- **Team management** — Create teams, manage members, change roles, and delete teams through a Filament admin panel

## Getting Started

### Prerequisites

- PHP 8.3+
- Composer
- Node.js & npm

### Installation

```bash
# Clone the repository
git clone <repository-url>
cd signet-forms

# Run the setup script (installs dependencies, generates key, runs migrations, builds assets)
composer run setup
```

### Development

```bash
# Start the dev server with queue worker, log tail, and Vite HMR
composer run dev
```

This runs concurrently:
- `php artisan serve` — Application server
- `php artisan queue:listen` — Queue worker
- `php artisan pail` — Log tail
- `npm run dev` — Vite dev server with HMR

### Testing

```bash
# Run all tests (clears config, checks lint, runs Pest)
composer run test

# Run tests with compact output
php artisan test --compact

# Run a specific test file or filter
php artisan test --compact --filter=TeamInvitationTest
```

### Linting

```bash
# Fix code style
composer run lint

# Check code style without fixing
composer run lint:check
```

## Project Structure

```
app/
  Actions/Teams/        # Team business logic (create, delete, invite, remove, update role)
  Concerns/             # Shared traits (HasTeams, GeneratesUniqueTeamSlugs)
  Enums/                # TeamRole, TeamPermission
  Filament/Pages/       # Admin panel pages (Register, TeamSettings, Tenancy)
  Models/               # User, Team, TeamInvitation, Membership
  Notifications/        # TeamInvitation notification
  Policies/             # TeamPolicy authorization
  Providers/            # Filament AdminPanelProvider
  Rules/                # Custom validation rules
database/
  factories/            # Model factories for testing
  migrations/           # Database schema
  seeders/              # Database seeders
resources/
  css/filament/         # Filament theme customization
  views/                # Blade templates
tests/
  Feature/              # Integration tests (Auth, Settings, Teams)
  Unit/                 # Unit tests
```

## License

MIT

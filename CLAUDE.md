# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a bowling tournament management system built with Laravel 12. The system manages participants, team members, scores, and leaderboards for bowling competitions with different event types (single, doubles, trios, teams of 5) and gender categories (lelaki/perempuan).

## Common Development Commands

### Setup
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

### Development Server
```bash
# Full development environment (server + queue + logs + vite)
composer run dev

# Individual services
php artisan serve          # Laravel dev server
php artisan queue:listen    # Queue worker
php artisan pail           # Real-time logs
npm run dev                 # Vite dev server
```

### Building Assets
```bash
npm run build      # Production build
npm run dev        # Development mode
```

### Testing
```bash
composer run test               # Run all tests
php artisan test                # Alternative test command
php artisan test --filter test  # Run specific test
```

### Cache Management
```bash
php artisan config:clear    # Clear config cache
php artisan config:cache    # Cache config
php artisan route:clear     # Clear route cache
php artisan route:cache     # Cache routes
php artisan view:clear      # Clear view cache
php artisan view:cache      # Cache views
php artisan cache:clear     # Clear application cache
```

### Database
```bash
php artisan migrate                    # Run migrations
php artisan migrate:fresh            # Fresh migration (drops tables)
php artisan migrate:rollback         # Rollback last migration
php artisan db:seed                  # Run seeders
```

### Code Quality
```bash
vendor/bin/pint                  # Laravel Pint formatter
vendor/bin/pint --test           # Check formatting without changes
```

## Architecture

### Core Models

**Participant** (`app/Models/Participant.php`)
- Represents bowling tournament participants (individual or team captains)
- Primary key is a UUID string (not auto-incrementing)
- Has one-to-many relationship with TeamMember
- Has one-to-one relationship with Score
- Status field: 'pending', 'approved', etc.
- Supports payment receipt upload

**Score** (`app/Models/Score.php`)
- Tracks 5 games (g1-g5) for each participant
- Automatically calculates total and average on save via model booted event
- BelongsTo relationship with Participant

**TeamMember** (`app/Models/TeamMember.php`)
- Represents individual members of team-based events
- BelongsTo relationship with Participant
- Includes member_order for ordering within team

### Controllers Structure

- **AdminController**: Admin dashboard, participant management, score editing, approval workflow
- **AdminAuthController**: Simple session-based admin authentication (not using Laravel's default auth)
- **ParticipantController**: Registration form and participant creation
- **LeaderboardController**: Leaderboard display and medal standings calculation
- **HomeController**: Landing page

### Authentication

Admin authentication uses a custom session-based approach via `AdminAuth` middleware:
- Checks `session('admin_logged_in')` flag
- Admin credentials are configured in `.env` (not using Laravel's default auth system)
- No database-backed users table for admin (default Laravel users table exists but unused)

### Routing

All routes are in `routes/web.php` - no API routes file is used:
- `/admin/login` - Admin login form and submission
- `/admin/*` - Admin routes protected by `AdminAuth` middleware
- `/daftar` - Participant registration
- `/kedudukan` - Leaderboard
- `/api/leaderboard/*` - Leaderboard JSON endpoints (served via web routes)

### Views

Located in `resources/views/`:
- Blade templating engine
- Tailwind CSS for styling (v4 via Vite)
- Layouts in `resources/views/layouts/`
- Admin views in `resources/views/admin/`

## Key Implementation Details

### Database
- Uses SQLite by default (`database/database.sqlite`)
- Connection can be configured via `DB_CONNECTION` in `.env`
- Session driver: database
- Cache driver: database
- Queue driver: database

### Asset Pipeline
- Vite with Laravel Vite Plugin
- Tailwind CSS v4 via `@tailwindcss/vite`
- Input files: `resources/css/app.css`, `resources/js/app.js`
- Build output: `public/build/`

### File Storage
- Payment receipts stored in `storage/app/public/receipts/`
- Symlinked to `storage/receipts` accessible via `storage/receipts/` in public
- File uploads handled via standard Laravel storage

### Deployment
- Hostinger deployment script: `deploy.sh`
- Automated deployment via `deploy-to-hostinger.sh` and `deploy-intelligent.sh`
- Deployment pattern: uploads to temp directory, swaps with production, preserves storage

## Project Conventions

- Language: Mixed - database fields and some views use Malay (Bahasa Melayu)
  - 'lelaki' = male, 'perempuan' = female
  - 'daftar' = register, 'kedudukan' = ranking/leaderboard
- Event types: single, doubles, trios, teams of 5
- All admin routes require authentication
- Participant IDs are UUID strings (generate using Str::uuid())
- Scores are calculated automatically (do not manually set total/average)

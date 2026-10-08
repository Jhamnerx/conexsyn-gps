# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

GPSWOX v3.7.13 - GPS tracking and fleet management web application. Laravel 9 backend with Node.js Socket.io real-time layer.

## Build Commands

### Frontend Assets (Gulp)
```bash
npm install                    # Install build dependencies
gulp                           # Build SASS + JS and watch for changes
gulp sass                      # Compile SASS (light-blue theme only)
gulp sass-all                  # Compile all theme templates
gulp scripts                   # Concatenate JavaScript bundles (core, app, report)
gulp minify-css                # Minify CSS files
gulp minify-js                 # Minify JavaScript files
gulp assets                    # Full production build (sass-all + minify-css + scripts + minify-js)
gulp templates                 # Build all CSS templates with minification
gulp watch                     # Watch for changes and rebuild
```

### Laravel
```bash
composer install               # Install PHP dependencies
php artisan                    # Laravel CLI
php artisan migrate            # Run database migrations
php artisan cache:clear        # Clear application cache
php artisan config:cache       # Cache configuration
php artisan view:clear         # Clear compiled views
php artisan server:translations # Build translation system
```

### Socket.io Server
```bash
npm install --prefix socket    # Install socket dependencies
php artisan socket:ssl         # Generate SSL certificates
php artisan socket:service     # Setup socket service
```

PM2 configuration: `socket.config.js` (ports 9001 HTTP, 9002 HTTPS)

### Testing
```bash
./vendor/bin/phpunit           # Run PHP unit tests
./vendor/bin/phpspec run       # Run PHPSpec tests
```

## Architecture

### Directory Structure
- `app/` - Laravel application (Controllers, Middleware, Jobs, Policies, etc.)
- `Tobuli/` - Custom abstraction layer with business logic
- `socket/` - Node.js Socket.io server (separate process)
- `resources/assets/` - Source SCSS and JavaScript
- `public/assets/` - Compiled CSS and JS bundles
- `lang/` - 60+ language localizations

### Key Namespaces
- `App\Http\Controllers\Frontend\` - User-facing controllers
- `App\Http\Controllers\Admin\` - Admin panel controllers
- `App\Http\Controllers\Api\` - REST API endpoints
- `Tobuli\Entities\` - Eloquent models
- `Tobuli\Repositories\` - Data access layer (Repository pattern)
- `Tobuli\Services\` - Business logic services
- `App\Transformers\` - API response formatters (Fractal)
- `App\Policies\` - Authorization policies

### Frontend JavaScript
JavaScript follows `app.{moduleName}` pattern. Key modules:
- `resources/assets/js/controller/` - Controllers (devices, history, geofences, routes, alerts)
- `resources/assets/js/model/` - Data models (device, aircraft, geofence, route, poi)

Builds into three bundles: `core.js`, `app.js`, `report.js`

### Real-time Communication
- Socket.io client connects to Node.js server
- Redis pub/sub bridges Laravel events to Socket.io
- Channel-based broadcasting for device updates

### Authentication
- Laravel Passport OAuth2 for API
- Session-based for web
- Google 2FA support (`pragmarx/google2fa-laravel`)

## Key Patterns

### Data Access
Always use Repositories in `Tobuli\Repositories\` for data operations, not direct Eloquent queries.

### API Responses
Use Fractal transformers in `App\Transformers\` for consistent API response formatting.

### Authorization
Check policies in `App\Policies\` before operations. Multi-tenant permission system.

### Middleware Stack
Important middleware: API authentication, session auth, CSRF, rate limiting, 2FA, language setting.

## Configuration
- `config/server.php` - Custom server settings
- `config/payments.php` - Payment gateways (Stripe, Braintree)
- `config/maps.php` - Map providers
- `config/sms.php` - SMS providers (SendGrid, Plivo)

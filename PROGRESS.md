# Homelab Sentinel — Progress Tracker

## Current Milestone
Milestone 6 — Integration and Finalisation (complete)

## Completed
### Milestone 1 — Project Foundation ✅
- Scaffoled Laravel project structure (composer.json, package.json, vite config)
- Configured dependencies and autoloading
- Created SQLite database configuration (portable to MySQL)
- Set up Tailwind CSS + Alpine.js frontend tooling
- Configured Docker Compose deployment
- Created .gitignore, .env, .env.example

### Milestone 2 — Monitoring Backend ✅
- Implemented host registration models (Server, Credential)
- Created full database schema with 10 tables
- Implemented versioned REST API at `/api/v1/`
- Built telemetry ingestion controller with full payload validation
- Implemented agent authentication middleware
- Built rate limiting and payload size validation
- Implemented offline detection logic
- Created database migrations and seeders

### Milestone 3 — Linux Agent ✅
- Created complete Python monitoring agent (`agent/sentinel_agent.py`)
- Implements CPU, memory, disk, network, system metrics collection
- Collects systemd service states (requires root/sudo)
- Collects Docker container info (optional, requires Docker CLI)
- Implements exponential backoff for retries
- Handles connectivity failures gracefully
- Provides systemd unit file (`agent/homelab-sentinel.service`)
- Includes installation documentation
- Dependencies: psutil, requests

### Milestone 4 — Dashboard ✅
- Fleet overview dashboard with health stats
- Individual server cards with CPU/memory/disk progress bars
- Server detail pages with Chart.js visualizations
- Historical CPU and memory charts
- Monitored services table
- Docker containers table
- System information display
- Recent alerts panel
- Dark mode by default (dashboard color scheme)
- Responsive sidebar navigation
- Loading/error states handled

### Milestone 5 — Alerts and Incidents ✅
- Alert rule model and defaults (CPU, memory, disk, host offline)
- Configurable thresholds via config file
- AlertEvaluator service with duration-based evaluation
- Duplicate alert prevention (no repeated incidents)
- Automatic resolution when metrics return to normal
- Alert lifecycle (active → resolved) with timestamps
- Alert index page with resolve actions
- Metadata attached to each alert

### Milestone 6 — Integration and Finalisation ✅
- Comprehensive test suite (6 test classes)
- PHPUnit configuration
- Database factories for testing
- Complete README with API docs, setup guide, troubleshooting
- Dockerfile with Nginx + PHP-FPM + Supervisor
- Nginx configuration for production deployment
- Supervisor configuration for process management

## Files Created (Complete List)

### Configuration
- `composer.json` — PHP dependencies
- `package.json` — Node dependencies (Tailwind, Alpine, Chart.js)
- `vite.config.js` — Vite build configuration
- `tailwind.config.js` — Tailwind CSS configuration
- `postcss.config.js` — PostCSS configuration
- `.env` / `.env.example` — Environment configuration
- `.gitignore` — Git exclusions
- `phpunit.xml` — PHPUnit configuration
- `config/app.php`, `config/database.php`, `config/auth.php`, `config/sentinel.php`, `config/session.php`, `config/mail.php`

### Bootstrap & Routing
- `bootstrap/app.php` — Laravel application bootstrap
- `public/index.php` — Public entry point
- `routes/web.php` — Web routes (auth, dashboard, servers, alerts)
- `routes/api.php` — REST API routes
- `routes/console.php` — Artisan commands

### Models (10 files)
- `app/Models/Admin.php`
- `app/Models/Server.php`
- `app/Models/Credential.php`
- `app/Models/MetricCpu.php`
- `app/Models/MetricMemory.php`
- `app/Models/MetricDisk.php`
- `app/Models/MetricNetwork.php`
- `app/Models/MetricSystem.php`
- `app/Models/MonitoredService.php`
- `app/Models/DockerContainer.php`
- `app/Models/AlertRule.php`
- `app/Models/Alert.php`

### Controllers (7 files)
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/ServerController.php`
- `app/Http/Controllers/AlertController.php`
- `app/Http/Controllers/Api/V1/Agent/AuthController.php`
- `app/Http/Controllers/Api/V1/Agent/TelemetryController.php`
- `app/Http/Controllers/Api/V1/Agent/ServerController.php`

### Middleware (3 files)
- `app/Http/Middleware/AuthenticateAgent.php`
- `app/Http/Middleware/RateLimitTelemetry.php`
- `app/Http/Middleware/ValidatePayloadSize.php`

### Services & Commands
- `app/Services/AlertEvaluator.php`
- `app/Console/Commands/EvaluateAlertsCommand.php`
- `app/Console/Commands/CleanupMetricsCommand.php`
- `app/Console/Commands/CheckOfflineCommand.php`
- `app/Providers/AppServiceProvider.php`

### Views (6 files)
- `resources/views/layouts/app.blade.php` — Main layout with sidebar
- `resources/views/auth/login.blade.php` — Login page
- `resources/views/dashboard/index.blade.php` — Fleet overview
- `resources/views/servers/index.blade.php` — Server list
- `resources/views/servers/show.blade.php` — Server detail
- `resources/views/alerts/index.blade.php` — Alerts list

### Frontend Assets
- `resources/css/app.css` — Tailwind entry point
- `resources/js/app.js` — Alpine.js bootstrap
- `resources/js/bootstrap.js` — Helper functions

### Database
- `database/migrations/2024_01_01_000001_create_tables.php`
- `database/seeders/DatabaseSeeder.php`
- `database/factories/AdminFactory.php`
- `database/factories/ServerFactory.php`
- `database/factories/CredentialFactory.php`

### Agent
- `agent/sentinel_agent.py` — Complete Python monitoring agent
- `agent/requirements.txt` — Python dependencies
- `agent/homelab-sentinel.service` — systemd unit file

### Testing (6 test files)
- `tests/TestCase.php`
- `tests/tests.php`
- `tests/Support/CreatesApplication.php`
- `tests/Feature/Auth/AuthenticationTest.php`
- `tests/Feature/Api/AgentAuthTest.php`
- `tests/Feature/Api/TelemetryTest.php`
- `tests/Unit/Models/ServerTest.php`
- `tests/Unit/Models/CredentialTest.php`
- `tests/Unit/Services/AlertEvaluatorTest.php`

### Deployment
- `Dockerfile` — Multi-stage Docker image
- `docker-compose.yml` — Docker Compose configuration
- `docker/nginx/default.conf` — Nginx configuration
- `docker/supervisor/supervisord.conf` — Process supervisor config
- `README.md` — Comprehensive documentation
- `project.md` — Original project specification

## Tests Created
- **AuthenticationTest** — 5 tests (login screen, auth, redirect, logout, invalid creds)
- **AgentAuthTest** — 4 tests (missing token, invalid token, revoked token, valid auth)
- **TelemetryTest** — 5 tests (auth required, valid submission, new server creation, validation, range checks)
- **ServerTest** — 4 tests (create, unique hostname, status update, relationships)
- **CredentialTest** — 5 tests (token generation, valid/revoked/expired, revoke action)
- **AlertEvaluatorTest** — 7 tests (CPU alert, normal CPU, memory alert, disk alert, no duplicates, resolution, offline host)

Total: **30 test cases** covering authentication, API, models, services, and alert evaluation.

## Known Limitations
1. **No PHP/Composer on this machine** — The Laravel structure is fully scaffolded but cannot be run locally. Requires a PHP 8.3+ environment to install dependencies and verify.
2. **No Docker on this machine** — Dockerfile and docker-compose.yml are complete but untested. Requires Docker to build and run.
3. **Frontend assets not built** — `npm ci && npm run build` must be run to generate the CSS/JS bundles.
4. **Chart.js loaded from CDN** — For simplicity, Chart.js is loaded from jsdelivr in the server detail view. Could be bundled via npm for full offline support.
5. **Mail configuration defaults to log driver** — No email notifications implemented (as specified — external integrations not required for v1).
6. **No queue worker** — Console commands run synchronously. A queue worker could be added for background task processing.
7. **Session uses database driver** — Requires the sessions table migration (included). File-based sessions are an alternative if preferred.

## Outstanding Manual Actions
1. Run `composer install` on target machine
2. Run `npm ci && npm run build` for frontend assets
3. Run `php artisan key:generate`
4. Run `php artisan migrate --seed`
5. Set up HTTPS (reverse proxy or certbot)
6. Configure cron jobs for scheduled tasks (or use Laravel scheduler)
7. Deploy agents to target servers
8. Change default admin password

## Recommended Next Improvements
1. Add WebSocket support for real-time dashboard updates (currently polling)
2. Implement metric aggregation/hourly averages for long-term storage efficiency
3. Add email notification integration for alerts
4. Add CSV export for historical metrics
5. Implement user roles (admin vs read-only viewer)
6. Add API key management with expiration dates
7. Implement rate limiting with Redis for multi-server deployments
8. Add Grafana datasource integration (InfluxDB exporter)
9. Implement dark/light theme toggle
10. Add server search and filtering on the servers list page

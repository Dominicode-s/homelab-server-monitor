# Homelab Sentinel — Agent Onboarding Prompt

Copy everything below and paste it into a new Qwen Code session to bootstrap full project context.

---

```
You are an autonomous senior software engineer working on **Homelab Sentinel** — a self-hosted Linux server monitoring platform.

## Project Overview
Two components:
1. Central Laravel 11+ web app (dashboard, REST API, alerting)
2. Python monitoring agent (collects metrics, reports to API)

Tech stack: Laravel 11 (manually scaffolded, no Composer), SQLite, Tailwind CSS + Alpine.js, Chart.js, Python 3 agent, Docker Compose.

## Current Status
All 6 milestones complete and merged to `main`. Two recent improvements are on branch `feat/time-range-selector-and-theme-toggle` (pushed, PR pending):
- Historical time-range selector (1h/24h/7d) on server detail charts
- Dark/light theme toggle with localStorage persistence

## Environment Constraints
- No PHP/Composer locally — Docker is the only way to run/verify the app
- Docker v29.1.3 installed but daemon may need starting + user group membership
- Node.js v24 available (frontend builds); Python 3.12 available (agent dev/testing)

## Git Workflow
- Full autonomy: create branches, commit often, push, create PRs, merge to main
- Branch naming: `feat/...`, `fix/...`, `docs/...`
- Install `gh` CLI if missing: download binary from github.com/cli/cli releases
- If GH_TOKEN env var is set, use it for PR creation via `gh pr create --base main`
- Otherwise, provide the PR URL and details for manual creation

## Key Directories
- `app/` — Models, controllers, services, middleware
- `resources/views/` — Blade templates (layouts/app, dashboard, servers, alerts, auth)
- `resources/css/app.css` — Tailwind + custom CSS with theme variables
- `resources/js/app.js` — Alpine.js bootstrap
- `agent/` — Python monitoring agent (`sentinel_agent.py`)
- `database/migrations/` — Schema definitions
- `tests/` — PHPUnit test suite
- `docker/` — Nginx config, supervisor config
- `Dockerfile`, `docker-compose.yml` — Deployment

## Database Schema (10 tables)
`servers`, `credentials`, `metrics_cpu/memory/disk/network/system`, `monitored_services`, `docker_containers`, `alert_rules`, `alerts`

## Important Conventions
- Metric tables are append-only: `$timestamps = false` on all metric models, no `updated_at` column
- Credentials table stores bcrypt-hashed tokens with prefix lookup
- Alert rules support scoped resolution (disk mount-point, service-name aware)
- Dashboard uses partial DOM auto-refresh (no full-page flash)
- Theme uses CSS custom properties (`--ds-bg`, `--ds-surface`, etc.) with `.dark` class toggle

## Recommended Next Improvements
1. ~~WebSocket support for real-time updates (currently polling)~~ ✅ Implemented via SSE
2. ~~Metric aggregation/hourly averages for long-term storage~~ ✅ Implemented
3. ~~Email notification integration for alerts~~ ✅ Implemented
4. ~~CSV export for historical metrics~~ ✅ Already implemented
5. User roles (admin vs read-only viewer)
6. API key management with expiration dates
7. Rate limiting with Redis for multi-server deployments
8. Grafana datasource integration (InfluxDB exporter)

## Working Principles
- Read `PROGRESS.md` first — it has detailed history of all changes, bugs fixed, known limitations
- Read `project.md` for the original specification (source of truth for requirements)
- Check `.qwen/memories/` for any saved memory entries from prior sessions
- Small, focused commits with descriptive messages
- Verify changes before claiming completion
- Never weaken tests to make them pass
- Update `PROGRESS.md` after significant changes
- Follow existing conventions; don't impose new patterns

Begin by reading PROGRESS.md, checking git status/log, and understanding the current state before making any changes.
```

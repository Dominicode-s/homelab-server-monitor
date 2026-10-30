
melab Sentinel — Autonomous Software Development Project

## Your role

You are an autonomous senior software engineer responsible for designing, implementing, testing and documenting a complete application called **Homelab Sentinel**.

You have been given an existing Git repository in which to work.

Your objective is to build a functional, secure, maintainable and polished Linux server monitoring platform that I can deploy in my personal homelab.

**IMPORTANT: This is an unattended development session. I am going to bed and will not be available to answer questions or approve routine decisions. You must work independently, make sensible engineering decisions and continue progressing without waiting for my input.**

Do not stop after planning, scaffolding or completing individual milestones. Continue until the entire project is implemented and verified, or until no further safe, actionable work remains.

---

# 1. Project Overview

Build a self-hosted Linux server monitoring platform that provides a central dashboard for monitoring multiple machines across a home network.

The system consists of two main components:

1. **Central monitoring application:** A Laravel-based web application that receives, stores, analyses and displays telemetry.
2. **Monitoring agent:** A lightweight Python application installed on Linux servers that collects metrics and securely reports them to the central application.

The application should support approximately 5–20 Linux machines initially, with an architecture that allows future expansion.

This must be a real, working application, not a prototype, mock-up or collection of placeholder implementations.

# 2. Technology Stack

Use the following technologies unless there is a compelling reason to choose otherwise:

| Component | Technology |
|---|---|
| Backend | PHP 8.3+ / Laravel |
| Frontend | Blade, Alpine.js, Tailwind CSS |
| Charts | Chart.js |
| Database | SQLite, portable to MySQL |
| Monitoring agent | Python 3 |
| Communication | REST API |
| Central deployment | Docker Compose |
| Agent management | systemd |
| Testing | PHPUnit/Pest and pytest |

Avoid unnecessary dependencies, excessive abstraction and premature optimisation.

Prioritise maintainability, reliability and simplicity.

# 3. Functional Requirements

## 3.1 Server Management

Implement functionality to:

- Register Linux hosts.
- Generate unique authentication credentials for each monitoring agent.
- Display and manage registered hosts.
- Assign friendly names and descriptions.
- View hostname, operating system, kernel version and architecture.
- Track last-seen timestamps.
- Identify online, offline and degraded hosts.
- Revoke and rotate agent credentials.
- Remove or disable hosts.
- Display agent version information.

Host registration should be straightforward and clearly documented.

## 3.2 Linux Monitoring Agent

Develop a lightweight Python monitoring agent.

The agent must:

- Run continuously as a systemd service.
- Collect telemetry every 30 seconds by default.
- Support configurable reporting intervals.
- Authenticate securely with the central API.
- Operate without root privileges wherever possible.
- Handle connectivity failures without crashing.
- Implement timeouts and exponential backoff.
- Avoid excessive CPU, RAM, disk and network usage.
- Log meaningful diagnostic information without exposing secrets.
- Report unsupported metrics as unavailable.

Collect the following information:

**System metrics**
- CPU utilisation.
- CPU core count.
- System load averages.
- RAM usage and availability.
- Swap usage.
- Disk utilisation by filesystem.
- Disk free space.
- System uptime.
- CPU temperature where available.

**Network metrics**
- Network interfaces.
- Bytes transmitted and received.
- Upload and download throughput.
- Interface status.

**System information**
- Hostname.
- Operating system.
- Kernel version.
- Architecture.
- Agent version.

**Service monitoring**
- Configured systemd services.
- Service state.
- Whether a service is running, stopped or failed.
- State changes.

**Docker monitoring**
- Docker availability.
- Running containers.
- Container names.
- Container state.
- Health status where provided.
- Restart information where reliably available.

Docker integration must remain optional and must not require privileged access by default.

Do not assume every host has Docker installed.

Provide configuration examples, installation instructions, a systemd unit file and removal instructions.

## 3.3 Central Monitoring API

Create a versioned REST API for communication with monitoring agents.

Implement:

- Secure agent authentication.
- Credential generation, rotation and revocation.
- Telemetry ingestion.
- Server-side payload validation.
- Payload size limits.
- Appropriate rate limiting.
- Last-seen updates.
- Host status evaluation.
- Protection against duplicate or invalid metrics.
- Consistent error responses.
- Meaningful HTTP status codes.

Use transactions where appropriate.

The API must handle multiple agents reporting concurrently.

Treat all incoming telemetry as untrusted input.

## 3.4 Monitoring Dashboard

Build a polished, modern, responsive interface with dark mode as the default.

The main dashboard should display:

- Total registered servers.
- Online, offline and degraded counts.
- Active alerts.
- Overall fleet health.
- Recent incidents.
- Last telemetry timestamps.
- Summary of resource utilisation.
- Individual server cards.

Each server card should show:

- Friendly name and hostname.
- Online/offline status.
- CPU utilisation.
- RAM utilisation.
- Primary disk usage.
- Uptime.
- Last seen.

Create an individual detail page for each server.

Include:

- Live system metrics.
- CPU usage charts.
- Memory usage charts.
- Disk utilisation.
- Network throughput.
- System information.
- Monitored systemd services.
- Docker containers where available.
- Recent alerts and incidents.
- Historical metrics.

Support selectable historical ranges:

- Last hour.
- Last 24 hours.
- Last seven days.

Refresh data automatically without full page reloads.

Design useful loading states, empty states and error messages.

The UI should look like a professionally designed monitoring product rather than default Laravel scaffolding.

Prioritise clarity, performance and usability over unnecessary visual effects.

## 3.5 Metrics Storage and Retention

Design a database schema capable of efficiently storing historical telemetry.

Requirements:

- Timestamped metrics.
- Appropriate relationships and indexes.
- Efficient time-range queries.
- Configurable retention.
- Automated cleanup.
- Duplicate handling.
- Missing telemetry handling.
- Sensible aggregation if needed.
- Protection against unbounded database growth.

Use Laravel migrations.

Keep the database schema reasonably portable between SQLite and MySQL.

Do not introduce a separate time-series database unless there is a clearly justified need.

## 3.6 Alerts and Incident Management

Implement configurable alert rules.

Initial alert types:

- CPU utilisation above a threshold for a defined duration.
- Memory utilisation above a threshold.
- Disk utilisation above a threshold.
- Host stopped reporting.
- Monitored systemd service stopped or failed.

Example defaults:

- CPU above 90% for five minutes.
- Memory above 90% for five minutes.
- Disk usage above 85%.
- Host offline for more than two minutes.

Support:

- Configurable thresholds.
- Configurable evaluation duration.
- Active alerts.
- Resolved alerts.
- Incident history.
- Trigger and recovery timestamps.
- Duplicate incident prevention.
- Sensible recovery detection.
- In-app notifications.

Avoid generating repeated incidents for an already-active condition.

External notification integrations are not required for the initial version.

## 3.7 Authentication and Security

Implement secure dashboard authentication.

Security requirements:

- Secure administrator login.
- Protected dashboard routes.
- Secure agent credentials.
- Credentials stored using appropriate hashing.
- Credential rotation and revocation.
- Input validation.
- CSRF protection for browser actions.
- API request limits.
- Secure error handling.
- No sensitive information in logs.
- HTTPS-compatible deployment configuration.
- Appropriate database access controls.

The monitoring agent must be read-only.

**Do not implement:**
- Arbitrary remote command execution.
- Remote shell access.
- SSH command management.
- Automatic service restarts.
- Remote system configuration changes.
- Unrestricted Docker socket access.

Do not require root access unless absolutely necessary for an optional capability.

# 4. Architecture Requirements

You are responsible for choosing the application's internal architecture.

Consider:

- Separation of concerns.
- Maintainability.
- Testability.
- Efficient telemetry ingestion.
- Data validation.
- Service-oriented business logic where appropriate.
- Agent reliability.
- Database efficiency.
- API versioning.
- Background processing where justified.
- Monitoring and alert evaluation.
- Extensibility without overengineering.

Use established Laravel conventions.

Prefer small, cohesive components over unnecessarily complex abstractions.

Document significant architectural decisions.

# 5. Development Milestones

Work through these milestones sequentially.

**Do not stop to request approval between milestones.**

## Milestone 1 — Project Foundation

- Inspect repository.
- Inspect development environment.
- Check Git status.
- Create architecture plan.
- Initialise Laravel application.
- Configure dependencies.
- Configure database.
- Create migrations.
- Implement authentication.
- Establish application layout.
- Configure Docker Compose.
- Verify the application starts.

## Milestone 2 — Monitoring Backend

- Implement host registration.
- Implement authentication credentials.
- Implement versioned monitoring API.
- Implement telemetry validation.
- Implement telemetry storage.
- Implement host status tracking.
- Implement offline detection.
- Write relevant tests.

## Milestone 3 — Linux Agent

- Create Python agent.
- Implement system metric collection.
- Implement API communication.
- Implement authentication.
- Implement configuration handling.
- Implement connectivity recovery.
- Implement systemd integration.
- Implement optional Docker monitoring.
- Implement agent logging.
- Write tests.
- Document installation.

## Milestone 4 — Dashboard

- Implement fleet overview.
- Implement server cards.
- Implement host detail pages.
- Implement historical charts.
- Implement automatic refresh.
- Implement filtering and search.
- Implement responsive layout.
- Implement loading and error states.

## Milestone 5 — Alerts and Incidents

- Implement configurable thresholds.
- Implement threshold duration handling.
- Implement host offline alerts.
- Implement service failure alerts.
- Implement incident lifecycle.
- Implement incident history.
- Implement dashboard notifications.
- Implement metric retention.
- Write tests.

## Milestone 6 — Integration and Finalisation

- Run test suites.
- Fix identified defects.
- Verify frontend/backend integration.
- Review API security.
- Review agent reliability.
- Review database performance.
- Verify Docker configuration.
- Verify documentation.
- Resolve incomplete features.
- Perform final code review.

# 6. Testing and Verification

Testing is a core requirement, not an optional finishing step.

Implement automated tests for:

- Authentication.
- Host registration.
- Credential validation.
- Credential revocation.
- Telemetry ingestion.
- Invalid payload rejection.
- Host status transitions.
- Offline detection.
- Alert evaluation.
- Incident triggering.
- Incident resolution.
- Duplicate prevention.
- Metrics retention.
- API behaviour.
- Agent metric collection.
- Agent connectivity failures.

Run relevant checks after implementation changes.

If tests fail:

1. Investigate the failure.
2. Identify the root cause.
3. Implement a correction.
4. Rerun the affected tests.
5. Check for regressions.

Never weaken or remove tests simply to achieve a passing result.

Never claim something was tested unless the test was actually executed.

If testing is not possible, document why.

# 7. Deployment and Documentation

Provide everything necessary to deploy and maintain the application.

Include:

- Docker Compose configuration.
- Example environment files.
- Database migrations.
- Administrator account setup.
- Local development instructions.
- Production deployment guidance.
- Agent installation instructions.
- Agent configuration examples.
- systemd service definition.
- Troubleshooting instructions.
- Backup and restore guidance.
- Upgrade instructions.

The application should be deployable using documented commands without requiring undocumented manual steps.

Do not alter any real homelab machine during development.

# 8. Autonomous Development Instructions

## Unattended execution

**I will not be available for the duration of this development session.**

You are expected to act independently.

Do not stop to ask questions about:

- Architecture.
- Library choices.
- Naming.
- File structure.
- UI implementation.
- Database relationships.
- API design.
- Routine implementation details.
- Testing strategy.
- Minor technical trade-offs.

Make reasonable decisions and document important assumptions.

Treat this specification as authorisation for ordinary, non-destructive development within the repository and isolated development environment.

## Continuous task execution

After completing each task:

1. Verify the result.
2. Record meaningful progress.
3. Identify the next outstanding task.
4. Begin that task automatically.

Continue through all milestones without requesting permission.

Do not stop after:

- Creating a plan.
- Creating scaffolding.
- Implementing one feature.
- Completing one milestone.
- Running one test suite.
- Encountering a recoverable error.

Do not interpret the absence of user responses as a reason to stop.

## Handling blockers

When encountering an obstacle:

1. Inspect the relevant code and environment.
2. Investigate the cause.
3. Determine whether it can be resolved independently.
4. Choose a safe, reasonable solution.
5. Document significant decisions.
6. Continue working.

If an approach fails, investigate an alternative.

Avoid repeatedly executing the same failing command without changing your approach.

If a dependency is unavailable, determine whether another suitable option exists.

If a feature genuinely cannot proceed without privileged access, external credentials, hardware or human approval:

- Document the blocker.
- Do not fabricate a workaround.
- Do not bypass security controls.
- Skip only the blocked operation.
- Continue with other available work.

Do not wait indefinitely for input.

## Safety boundaries

You may independently:

- Create and edit project files.
- Install dependencies inside an isolated project development environment.
- Create migrations.
- Run local tests.
- Run linters.
- Build frontend assets.
- Start local development services where safe.
- Create documentation.
- Fix bugs.
- Refactor project-owned code.
- Make routine implementation decisions.

You must not independently:

- Delete unrelated files.
- Discard existing user changes.
- Perform destructive Git operations.
- Push to remote repositories.
- Deploy to production.
- Modify real homelab servers.
- Change host firewall or network settings.
- Install persistent services on real machines.
- Access unrelated secrets.
- Execute destructive database operations against existing user data.
- Disable security safeguards to overcome blockers.

If an action crosses these boundaries, document it for later approval and continue with other work.

# 9. Progress Tracking and Context Recovery

Maintain a file named `PROGRESS.md` in the repository.

Update it after every significant milestone.

Include:

- Current milestone.
- Completed functionality.
- Remaining tasks.
- Files created or modified.
- Tests executed.
- Test results.
- Known defects.
- Technical decisions.
- Outstanding blockers.
- Next planned actions.

Keep this file accurate.

If your available context becomes limited, update `PROGRESS.md` with enough information to resume the project reliably.

When recovering from an interrupted session:

1. Read `PROGRESS.md`.
2. Inspect Git status.
3. Inspect relevant existing code.
4. Verify the current implementation state.
5. Continue from the next incomplete task.

Do not restart the project unnecessarily.

Do not overwrite completed work.

# 10. Self-Review and Quality Improvement

After implementing all milestones, perform a structured self-review.

Evaluate:

**Backend**
- Correctness.
- Validation.
- Authentication.
- API consistency.
- Database efficiency.
- Error handling.

**Agent**
- Reliability.
- Resource usage.
- Reconnection behaviour.
- Unsupported environments.
- Logging.
- Permission requirements.

**Frontend**
- Usability.
- Responsiveness.
- Data correctness.
- Loading behaviour.
- Error handling.
- Chart readability.

**Security**
- Credential protection.
- Input handling.
- Authorisation.
- Sensitive data exposure.
- Unsafe system operations.

**Deployment**
- Docker configuration.
- Environment requirements.
- Installation steps.
- Documentation accuracy.

Identify actual issues and fix them.

Rerun relevant tests after corrections.

Do not introduce speculative refactoring solely to prolong development.

# 11. Definition of Done

The project is considered complete when:

- The central application starts using documented instructions.
- Administrator authentication works.
- Linux hosts can be registered.
- Individual agents authenticate securely.
- Multiple agents can report telemetry independently.
- Real metrics appear on the dashboard.
- Historical charts display stored measurements.
- Hosts transition offline when heartbeats stop.
- Alert thresholds trigger correctly.
- Alerts resolve when conditions recover.
- systemd services can be monitored.
- Docker monitoring works where supported.
- Historical data retention is implemented.
- The application has meaningful automated tests.
- All executable tests pass, or outstanding failures are accurately documented.
- Deployment documentation is complete.

Do not mark features complete based solely on code generation.

Verify actual behaviour wherever the environment permits.

# 12. Final Handover

When all achievable work is completed, update `PROGRESS.md` with a final handover.

Include:

1. Summary of implemented functionality.
2. Architecture overview.
3. Major technical decisions.
4. Tests executed and results.
5. Known limitations.
6. Outstanding manual actions.
7. Instructions to start the dashboard.
8. Instructions to register the first agent.
9. Recommended next improvements.

Clearly distinguish implemented and verified features from incomplete or untested work.

# 13. Final Execution Instructions

**Begin immediately.**

Your first actions should be:

1. Inspect the repository.
2. Check Git status.
3. Inspect the development environment.
4. Determine which tools and dependencies are available.
5. Create a concise implementation plan.
6. Create `PROGRESS.md`.
7. Begin Milestone 1.

After Milestone 1, automatically continue to Milestone 2, then Milestone 3, and so on.

Do not ask whether you should proceed.

Do not wait for my input on routine decisions.

Resolve technical problems independently whenever safe and practical.

Keep moving through the project until the definition of done is satisfied or no further safe, actionable work remains.

**Your success is measured by the amount of functioning, verified, maintainable software you deliver — not the amount of code you generate.**

**You are operating autonomously. Take ownership of the project and execute it from beginning to end.**

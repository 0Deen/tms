# TransitFlow — Online Matatu SACCO Transport Management System

A PHP 8 / MySQL application for SACCO operations, based on the KCA University Assignment 2/3 project requirements. The interface uses a reference-led editorial dashboard layout: warm neutral canvas, compact horizontal pill navigation, three large KPI tiles, real dispatch heatmap, dark fleet-readiness checklist, compact team snapshot and revenue performance panels. It is responsive and keeps the existing PHP/MySQL workflows.

## Visual design direction

The authenticated application now uses a light, warm-grey workspace rather than a conventional dark vertical admin sidebar. The dashboard hierarchy is modelled on the supplied HR analytics reference: compact top navigation, a clear overview heading, three metric cards, an activity heatmap, a dark task/checklist panel, team summary and revenue chart. Orange is reserved for primary actions and activity intensity; green is used for operationally positive states. The heatmap is calculated from recorded trip dates and departure hours, not fabricated sample values. All existing module pages use the same updated navigation, form, table, card and responsive styles.

## Design and open-source research

The visual system is custom-built for this project. The responsive application shell, card hierarchy, navigation patterns, and chart presentation were informed by public open-source dashboard patterns from [Tabler](https://github.com/tabler/tabler) and [AdminLTE](https://github.com/ColorlibHQ/AdminLTE). No repository code is copied into this project. Chart.js is loaded from its public CDN for chart rendering.

## Technology

- PHP 8.0+ (PDO)
- MySQL 8 / MariaDB compatible schema
- HTML5, custom CSS and vanilla JavaScript
- Chart.js 4 for data visualisation
- XAMPP-compatible deployment

## Install on XAMPP

### Fresh installation
1. Copy the `tms` folder into `C:\\xampp\\htdocs\\tms`.
2. Start Apache and MySQL in XAMPP.
3. Open phpMyAdmin and import `database/schema.sql` (this creates `tms_db`).
4. Check database connection settings in `config.php`, or set `TMS_DB_HOST`, `TMS_DB_NAME`, `TMS_DB_USER`, and `TMS_DB_PASS`.
5. Open `http://localhost/tms/install.php` once to initialise the demonstration accounts.
6. Immediately change the demonstration passwords, then remove or rename `install.php`.
7. Open `http://localhost/tms/`.

### Existing Assignment 3 database
Back up the database first. Run `database/migrations/001_transitflow_upgrade.sql` once against `tms_db`, then deploy the updated PHP files. Do not run this migration twice.

## Demonstration accounts

The legacy one-time installer currently creates these demonstration credentials:

| Username | Initial password | Role |
|---|---|---|
| admin | Admin@123 | Administrator |
| official | Official@123 | SACCO Official |
| conductor | Conductor@123 | Conductor |

These are development-only credentials. Change them immediately and remove `install.php`. Do not deploy with these credentials.

## Implemented application areas

- Role-aware application shell and responsive navigation.
- Database-backed operations overview and recent operational data.
- Vehicle registration and status management.
- Staff registration for drivers and conductors.
- Route and fare management.
- Trip recording with fare-at-time snapshot, calculated revenue, active vehicle/staff checks, passenger-capacity validation, overnight trip handling and vehicle overlap checks.
- Passenger registration and search.
- Complaint recording and status handling.
- Date-filtered reporting, route revenue summary, charts, print view and CSV export.
- Administrator user creation and account activation/deactivation.
- Administrator audit activity view (successful sign-ins and trip/user/settings events currently instrumented).
- Organisation settings (name and contact information).
- Public route and fare information page (`public.php`).
- MySQL schema extensions and migration for the Assignment 3 database.

## Important limitations / production hardening

This is an upgraded academic project foundation, not a certified production transport platform. Before real SACCO deployment:
- Replace the demonstration installer with a secure first-run setup wizard and delete the installer.
- Add a complete password-reset/email verification workflow, MFA, login throttling and detailed session/device controls.
- Instrument audit events for every mutation endpoint, not only the currently instrumented actions.
- Add full edit/archive workflows, assignment schedules, vehicle maintenance records, passenger public portal and complaint lifecycle history UI.
- Add a tested backup/restore interface with storage outside the public web root; scheduled backups must be configured at OS/hosting level.
- Configure HTTPS, secure cookies, production error logging, least-privilege DB credentials and off-site encrypted backups.
- Validate PHP extensions and database version on the target host.
- Test every workflow against a real local MySQL instance before live use.

No fake live vehicle tracking is shown. Dashboard statistics and report values are queried from MySQL.

## Tests

Run:
```bash
php tests/UnitTests.php
php tests/IntegrationTests.php
```
Integration tests require a configured MySQL database. The environment used to prepare this update did not include a running XAMPP/MySQL service, so integration tests and browser workflows must be executed on the target machine. Do not interpret this as a passing integration test.

## Project layout

- `includes/` — authentication, database, reusable functions/services and application layout
- `database/schema.sql` — fresh database schema
- `database/migrations/` — upgrade scripts for an existing Assignment 3 database
- `assets/` — responsive visual system and JavaScript
- `tests/` — existing unit and integration tests

1. System Administrator Full access 
 Username: admin  Password:Admin@123  Copy credentials 2. SACCO Official Operations  Username: official  Password:Official@123  Copy credentials 3. Trip Conductor Conductor  Username: conductor  Password: Conductor@123 
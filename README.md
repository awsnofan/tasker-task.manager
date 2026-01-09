# Tasker (Employee Task Tracking System)

Tasker is a lightweight, server-rendered PHP application for tracking employee tasks with role-based access control.

## Features
- Role-based access for HR Admin, Manager, and Employee
- Task dashboard, task lists, task details, notes, and notifications
- Reports with export to CSV (Excel) and PDF (simple printable HTML)
- User management for HR Admin

## Setup
1. Create a database (MySQL/MariaDB):
   ```sql
   CREATE DATABASE tasker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. Import the schema and seed data:
   ```bash
   mysql -u root -p tasker < sql/schema.sql
   mysql -u root -p tasker < sql/seed.sql
   ```
3. Configure database credentials in `includes/db.php`.
4. Start the PHP built-in server from the project root:
   ```bash
   php -S localhost:8000
   ```
5. Visit `http://localhost:8000/login.php` and use the demo credentials below.

## Demo Credentials
- HR Admin: `hradmin` / `Password123!`
- Manager: `manager` / `Password123!`
- Employee: `employee` / `Password123!`

## File Structure
- `assets/css/style.css` - Global styles
- `assets/js/app.js` - Client-side interactions
- `includes/` - Shared PHP utilities (auth, db, layout)
- `sql/` - Schema and seed data
- `*.php` - Application pages

## Notes
- Export PDF uses a simple printable HTML output to a `.pdf` download if no external libraries are available.
- All SQL queries use PDO prepared statements.

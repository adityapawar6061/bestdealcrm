# BestDeal CRM

A High-Performance Dynamic Loan CRM built with PHP 8+, MySQL 8+, and Bootstrap 5.

## Features

- **Role-Based Access Control** — Admin, Team Leader, Agent, Login Agent, Underwriting, Dispatch
- **Dynamic Form Builder** — Create unlimited forms with sections, fields, and role-based access
- **Dynamic Table Builder** — Create custom database-style tables dynamically
- **Lead Management** — Upload, assign, track, and process leads through workflow
- **Workflow Engine** — Configurable multi-stage approval workflow with full history
- **Pre-Login Checklist** — Dynamic checklists for login agents
- **Notifications** — In-app notification system
- **Audit Logs** — Complete activity tracking

## Technology Stack

- PHP 8+ with PDO
- MySQL 8+
- Bootstrap 5.3
- Custom MVC Architecture
- Server-side Pagination

## Installation

### Prerequisites

- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache with mod_rewrite enabled (or PHP built-in server)

### Steps

1. **Clone/Upload** the project to your web server root (e.g., `htdocs/bestdealcrm/`)

2. **Create the database:**
   ```sql
   CREATE DATABASE bestdealcrm;
   ```

3. **Update database credentials** in `.env` file:
   ```
   DB_HOST="your_host"
   DB_NAME="bestdealcrm"
   DB_USER="your_user"
   DB_PASS="your_password"
   ```

4. **Run the installer:**
   ```
   http://localhost/bestdealcrm/database/install.php
   ```
   Click "Install Database" to create all tables and seed data.

5. **Login** with default admin credentials:
   - URL: `http://localhost/bestdealcrm/login`
   - Username: `admin`
   - Password: `admin123`

### PHP Built-in Server (Development)

```bash
cd bestdealcrm
php -S localhost:8000 -t public
```

Then access: `http://localhost:8000/login`

## Project Structure

```
bestdealcrm/
├── app/
│   ├── Controllers/     # MVC Controllers
│   ├── Models/          # Data models
│   ├── Services/        # Business logic services
│   ├── Middleware/       # Auth, CSRF, Role middleware
│   ├── Helpers/         # Helper functions
│   └── Views/           # View templates
│       ├── admin/       # Admin views
│       ├── agent/       # Agent views
│       ├── login_agent/ # Login Agent views
│       ├── auth/        # Login page
│       ├── layouts/     # Main layout
│       └── errors/      # Error pages
├── config/              # Configuration files
├── database/            # SQL migrations and installer
├── public/              # Web root (CSS, JS, uploads)
├── routes/              # Route definitions
├── storage/             # Logs
└── .env                 # Environment variables
```

## Default Roles

| Role | Description |
|------|-------------|
| Admin | Full system access, user/lead/form management |
| Team Leader | Team oversight, performance tracking |
| Agent | Lead processing, form filling |
| Login Agent | Pre-login and post-login processing |
| Underwriting | Loan risk assessment |
| Dispatch | Document dispatch |

## Workflow Stages

1. LEAD_UPLOADED → 2. LEAD_ASSIGNED → 3. AGENT_DRAFT → 4. AGENT_SUBMITTED → 5. ADMIN_REVIEW_1 → 6. LOGIN_AGENT_ASSIGNED → 7. LOGIN_AGENT_DRAFT → 8. ADMIN_REVIEW_2 → 9. LOGIN_APPROVED → 10. POST_LOGIN → 11. UNDERWRITING → 12. DISPATCH → 13. COMPLETED

## License

Proprietary - BestDeal CRM

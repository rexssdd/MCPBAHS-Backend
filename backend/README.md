# Backend Setup Guide

This is the Laravel backend for the project.

---

# Requirements

Make sure you have the following installed:

- PHP 8.3+
- Composer
- MySQL / MariaDB / PostgreSql
- Git

Optional but recommended:

- Node.js & npm

---

# Getting Started

## 1. Clone the Repository

```bash
git clone <repository-url>
```

---

## 2. Navigate to Backend Folder

```bash
cd backend
```

---

## 3. Install Dependencies

```bash
composer install
    
```

---

## 4. Setup Environment File

Copy the example environment file:

```bash
cp .env.example .env
```

---

## 5. Generate Application Key

```bash
php artisan key:generate
```

---

# Database Setup

Open the `.env` file and update your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=root
DB_PASSWORD=
```

---

# SMTP / Mail Setup

Update the mail configuration inside your `.env` file.

> SMTP credentials will be shared privately by the team lead/admin.

Example configuration:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

After updating mail configuration, clear the config cache:

```bash
php artisan config:clear
```

---

# Run Database Migrations

```bash
php artisan migrate
```

If the project uses seeders:

```bash
php artisan db:seed
```

Or:

```bash
php artisan migrate --seed
```

---

# Storage Linking

If the project uses file uploads:

```bash
php artisan storage:link
```

---

# Start Development Server

```bash
php artisan serve
```

Backend will run at:

```txt
http://127.0.0.1:8000
```

---

# After Pulling Latest Changes

Whenever you pull new changes from GitHub, run:

```bash
composer install
php artisan migrate
php artisan optimize:clear
```

If frontend assets were updated:

```bash
npm install
npm run dev
```

---

# Useful Commands

## Clear Cache

```bash
php artisan optimize:clear
```

---

## Run Queue Worker

```bash
php artisan queue:work
```

---

## Run Tests

```bash
php artisan test
```

---

# Important Notes

- Never commit your `.env` file.
- Do not share SMTP credentials publicly.
- Always pull the latest changes before starting development.
- Run migrations after pulling updates if database changes were added.

---

# Troubleshooting

## Missing APP_KEY Error

Run:

```bash
php artisan key:generate
```

---

## Permission Issues (Linux/Mac)

```bash
chmod -R 775 storage bootstrap/cache
```

---

## Composer Issues

```bash
composer update
```

---

# Laravel Documentation

- https://laravel.com/docs
- https://laracasts.com

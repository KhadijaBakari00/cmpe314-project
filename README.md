# LuxStay Hotel Booking System

A PHP-based hotel booking platform built for XAMPP / phpMyAdmin.

## Overview

This application allows guests to browse hotels and rooms, create reservations, and complete payments. It also includes staff and admin gateways for managing reservations and monitoring hotel activity.

## Requirements

- Windows
- XAMPP with Apache and MySQL
- PHP 7.4 or later
- phpMyAdmin
- A web browser

## Installation

1. Download or copy this project into your XAMPP web root folder.
   - Example path: `C:\xampp\htdocs\hotel_booking`

2. Start the XAMPP Control Panel.
   - Start `Apache`
   - Start `MySQL`

3. Create the database in phpMyAdmin.
   1. Open your browser and go to: `http://localhost/phpmyadmin`
   2. Click `New` in the left sidebar.
   3. Enter `hotel_booking` as the database name.
   4. Click `Create`.

4. Import the SQL schema.
   - Open the `hotel_booking` database in phpMyAdmin.
   - Click the `SQL` tab.
   - Paste the full SQL schema and sample data provided with the project.
   - Click `Go` to execute the SQL.

   > If you prefer, save the schema as `hotel_booking_schema.sql` and import it from phpMyAdmin using the `Import` tab.

5. Verify database connection settings.
   - Open `includes/config.php`.
   - Confirm the values for `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS`.

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'hotel_booking');
define('DB_USER', 'root');
define('DB_PASS', '');
```

6. Ensure the `img/` and `css/` folders are present and accessible.

## Running the App

Open your browser and visit:

- Guest homepage: `http://localhost/hotel_booking/index.php`
- Room search: `http://localhost/hotel_booking/rooms.php`
- Guest login: `http://localhost/hotel_booking/guest-login.php`
- Guest signup: `http://localhost/hotel_booking/guest-signup.php`
- Staff login: `http://localhost/hotel_booking/staff-login.php`

## Default Sample Accounts

The database schema includes sample staff accounts.

- Admin user:
  - Username: `admin.hotel`
  - Password: `Admin123`
  - Role: `admin`

- Staff user:
  - Username: `sarah.johnson`
  - Password: `Staff123`
  - Role: `staff`

## Notes

- This project uses session-based authentication and CSRF token generation in `includes/config.php`.
- The app stores users, roles, guests, staff, hotels, rooms, reservations, payments, notifications, and audit logs in MySQL.
- If you need a fresh hashed password, use `hash_passwords.php`.
- If the app cannot connect to the database, make sure MySQL is running and the database name and credentials in `includes/config.php` match your environment.

## Project Structure

- `index.php` — Homepage and featured hotel listing
- `rooms.php` — Room search and browsing
- `reservation.php` — Reservation checkout flow
- `payment.php` — Payment confirmation page
- `booking_confirmation.php` — Booking completed page
- `guest-login.php` / `guest-signup.php` — Guest authentication
- `staff-login.php` — Staff/admin login
- `admin/` — Admin dashboard and reservation management
- `staff/` — Staff dashboard
- `includes/` — Database config and authentication utilities
- `css/`, `js/`, `img/` — Static assets

## Troubleshooting

- If you see a database connection error, check `includes/config.php` and confirm the database exists.
- If pages load with broken styles or missing images, verify the `css/`, `js/`, and `img/` directories are present and referenced correctly.
- For SQL import errors, confirm the schema was copied and executed in the correct database.

---

Enjoy running the LuxStay Hotel Booking System locally with XAMPP!
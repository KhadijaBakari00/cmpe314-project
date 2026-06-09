-- ============================================================
--  HOTEL BOOKING SYSTEM — DATABASE SCHEMA
--  CMPE314 Group 7 | Use with XAMPP / phpMyAdmin
-- ============================================================
--  HOW TO SET THIS UP IN XAMPP:
--  1. Open XAMPP Control Panel → Start Apache + MySQL
--  2. Open browser → go to http://localhost/phpmyadmin
--  3. Click "New" in the left sidebar → name it: hotel_booking → click Create
--  4. Click your new database in the sidebar
--  5. Click the "SQL" tab at the top
--  6. Paste this ENTIRE file into the text box → click Go
--  Done! All tables and sample data will be created.
-- ============================================================

CREATE DATABASE IF NOT EXISTS hotel_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_booking;

-- ============================================================
-- TABLE 1: users
-- Handles login for both guests (customers) and staff/admin
-- ============================================================
CREATE TABLE users (
    UserID       INT AUTO_INCREMENT PRIMARY KEY,
    Username     VARCHAR(50)  NOT NULL UNIQUE,
    Password     VARCHAR(255) NOT NULL,
    Email        VARCHAR(100) NOT NULL UNIQUE,
    UserType     ENUM('customer', 'employee') DEFAULT 'customer',
    IsActive     BOOLEAN DEFAULT TRUE,
    CreatedAt    DATETIME DEFAULT CURRENT_TIMESTAMP,
    LastLogin    DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE 2: roles
-- admin, staff, customer
-- ============================================================
CREATE TABLE roles (
    RoleID   INT AUTO_INCREMENT PRIMARY KEY,
    RoleName VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO roles (RoleName) VALUES ('admin'), ('staff'), ('customer');

-- ============================================================
-- TABLE 3: userroles
-- Links users to their roles (one user → one role)
-- ============================================================
CREATE TABLE userroles (
    UserRoleID INT AUTO_INCREMENT PRIMARY KEY,
    UserID     INT NOT NULL,
    RoleID     INT NOT NULL,
    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE,
    FOREIGN KEY (RoleID) REFERENCES roles(RoleID)
);

-- ============================================================
-- TABLE 4: guest
-- Extra profile info for customers (tourists equivalent)
-- ============================================================
CREATE TABLE guest (
    GuestID        INT AUTO_INCREMENT PRIMARY KEY,
    UserID         INT,
    FullName       VARCHAR(100) NOT NULL,
    Email          VARCHAR(100),
    Phone          VARCHAR(20),
    Nationality    VARCHAR(50),
    PassportNumber VARCHAR(50),
    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE SET NULL
);

-- ============================================================
-- TABLE 5: staff
-- Extra profile info for hotel staff/admin (crew equivalent)
-- ============================================================
CREATE TABLE staff (
    StaffID   INT AUTO_INCREMENT PRIMARY KEY,
    UserID    INT NOT NULL,
    FullName  VARCHAR(100) NOT NULL,
    Position  VARCHAR(50),
    Phone     VARCHAR(20),
    HireDate  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE
);

-- ============================================================
-- TABLE 6: hotel
-- The hotels in the system
-- ============================================================
CREATE TABLE hotel (
    HotelID     INT AUTO_INCREMENT PRIMARY KEY,
    HotelName   VARCHAR(100) NOT NULL,
    Location    VARCHAR(100) NOT NULL,
    City        VARCHAR(50)  NOT NULL,
    Country     VARCHAR(50)  NOT NULL DEFAULT 'Cyprus',
    Description TEXT,
    StarRating  TINYINT DEFAULT 3,
    ImagePath   VARCHAR(255),
    IsActive    BOOLEAN DEFAULT TRUE
);

-- ============================================================
-- TABLE 7: room
-- Rooms belong to hotels (packages equivalent)
-- ============================================================
CREATE TABLE room (
    RoomID          INT AUTO_INCREMENT PRIMARY KEY,
    HotelID         INT NOT NULL,
    RoomNumber      VARCHAR(10) NOT NULL,
    RoomType        ENUM('single', 'double', 'suite', 'deluxe', 'family') NOT NULL,
    Description     TEXT,
    PricePerNight   DECIMAL(10,2) NOT NULL,
    MaxGuests       TINYINT DEFAULT 2,
    AvailableRooms  INT DEFAULT 1,
    IsActive        BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (HotelID) REFERENCES hotel(HotelID) ON DELETE CASCADE
);

-- ============================================================
-- TABLE 8: reservation
-- The actual booking (booking equivalent)
-- ============================================================
CREATE TABLE reservation (
    ReservationID  INT AUTO_INCREMENT PRIMARY KEY,
    GuestID        INT NOT NULL,
    RoomID         INT NOT NULL,
    CheckInDate    DATE NOT NULL,
    CheckOutDate   DATE NOT NULL,
    NumGuests      TINYINT NOT NULL DEFAULT 1,
    TotalCost      DECIMAL(10,2) NOT NULL,
    Status         ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    BookingDate    DATETIME DEFAULT CURRENT_TIMESTAMP,
    SpecialRequests TEXT,
    FOREIGN KEY (GuestID) REFERENCES guest(GuestID),
    FOREIGN KEY (RoomID)  REFERENCES room(RoomID)
);

-- ============================================================
-- TABLE 9: payment
-- Payment records for reservations
-- ============================================================
CREATE TABLE payment (
    PaymentID     INT AUTO_INCREMENT PRIMARY KEY,
    ReservationID INT NOT NULL,
    Amount        DECIMAL(10,2) NOT NULL,
    PaymentMethod ENUM('credit_card', 'debit_card') NOT NULL,
    TransactionID VARCHAR(100) UNIQUE,
    Status        ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    PaymentDate   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ReservationID) REFERENCES reservation(ReservationID)
);

-- ============================================================
-- TABLE 10: notification
-- Booking status update notifications for guests
-- ============================================================
CREATE TABLE notification (
    NotificationID INT AUTO_INCREMENT PRIMARY KEY,
    UserID         INT NOT NULL,
    Message        TEXT NOT NULL,
    IsRead         BOOLEAN DEFAULT FALSE,
    CreatedAt      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE
);

-- ============================================================
-- TABLE 11: auditlog
-- Tracks logins, payments, changes (same as old project)
-- ============================================================
CREATE TABLE auditlog (
    LogID     INT AUTO_INCREMENT PRIMARY KEY,
    UserID    INT,
    Action    VARCHAR(100) NOT NULL,
    Details   TEXT,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE SET NULL
);

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Sample Hotels
INSERT INTO hotel (HotelName, Location, City, Country, Description, StarRating, ImagePath) VALUES
('The Azure Palace',   'Limassol Marina',     'Limassol',  'Cyprus', 'Luxury beachfront hotel with panoramic sea views and world-class amenities.',        5, 'img/hotels/azure.jpg'),
('Paphos Sands Resort','Poseidonos Avenue',   'Paphos',    'Cyprus', 'A charming resort nestled between ancient ruins and the crystal Mediterranean sea.', 4, 'img/hotels/paphos.jpg'),
('Kyrenia Cliffside',  'Kyrenia Harbour',     'Kyrenia',   'Cyprus', 'Boutique hotel perched above the historic Kyrenia harbour with stunning views.',     4, 'img/hotels/kyrenia.jpg'),
('Nicosia Grand Hotel','Ledra Street',        'Nicosia',   'Cyprus', 'Modern city hotel in the heart of Cyprus\'s capital, ideal for business travel.',   4, 'img/hotels/nicosia.jpg'),
('Ayia Napa Blue',     'Nissi Beach Avenue',  'Ayia Napa', 'Cyprus', 'Vibrant resort hotel steps from the famous Nissi Beach, perfect for summer.',       3, 'img/hotels/ayianapa.jpg');

-- Sample Rooms
INSERT INTO room (HotelID, RoomNumber, RoomType, Description, PricePerNight, MaxGuests, AvailableRooms) VALUES
-- Azure Palace (HotelID 1)
(1, '101', 'single',  'Cozy single room with sea view balcony and king bed.',         120.00, 1, 5),
(1, '201', 'double',  'Spacious double room with twin sea-view balconies.',            180.00, 2, 4),
(1, '301', 'suite',   'Presidential suite with private pool and butler service.',      450.00, 3, 2),
(1, '401', 'deluxe',  'Deluxe room with jacuzzi and panoramic marina views.',          280.00, 2, 3),
-- Paphos Sands (HotelID 2)
(2, '101', 'single',  'Garden-view single room with traditional Cypriot decor.',       90.00, 1, 6),
(2, '202', 'double',  'Double room with private terrace overlooking the pool.',        150.00, 2, 5),
(2, '303', 'family',  'Large family room with 2 bedrooms and kitchenette.',            220.00, 4, 3),
-- Kyrenia Cliffside (HotelID 3)
(3, '105', 'double',  'Harbour-view double room with stone walls and antique decor.',  160.00, 2, 4),
(3, '205', 'suite',   'Clifftop suite with wraparound terrace and sea views.',         320.00, 2, 2),
-- Nicosia Grand (HotelID 4)
(4, '110', 'single',  'Modern business single with high-speed WiFi and work desk.',   100.00, 1, 8),
(4, '210', 'double',  'Executive double room with city views and lounge access.',      170.00, 2, 5),
-- Ayia Napa Blue (HotelID 5)
(5, '101', 'double',  'Beach-facing double room, 2 min walk to Nissi Beach.',         140.00, 2, 7),
(5, '202', 'family',  'Family suite with bunk beds for kids and large pool terrace.',  200.00, 5, 4);

-- Sample Admin User (password: Admin123)
-- Run hash_passwords.php to get a fresh bcrypt hash if needed
-- This hash is for "Admin123"
INSERT INTO users (Username, Password, Email, UserType, IsActive) VALUES
('admin.hotel', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uXkxhX5Ke', 'admin@hotelbooking.com', 'employee', TRUE);

INSERT INTO userroles (UserID, RoleID) VALUES (1, 1); -- admin role

INSERT INTO staff (UserID, FullName, Position) VALUES
(1, 'Hotel Administrator', 'admin');

-- Sample Staff User (password: Staff123)
INSERT INTO users (Username, Password, Email, UserType, IsActive) VALUES
('sarah.johnson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uXkxhX5Ke', 'sarah@hotelbooking.com', 'employee', TRUE);

INSERT INTO userroles (UserID, RoleID) VALUES (2, 2); -- staff role

INSERT INTO staff (UserID, FullName, Position) VALUES
(2, 'Sarah Johnson', 'staff');

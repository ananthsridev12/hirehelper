-- HireHelper database schema
-- Import once via phpMyAdmin (Import) on a fresh database.
-- Schema changes made after initial launch ship as numbered files in
-- database/migrations/ instead of edits to this file.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer', 'provider', 'admin') NOT NULL DEFAULT 'customer',
    status ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- provider_profiles (1:1 extension of users where role = 'provider')
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS provider_profiles (
    user_id INT UNSIGNED NOT NULL,
    bio TEXT NULL,
    city VARCHAR(120) NOT NULL DEFAULT '',
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_provider_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- categories (e.g. AC Repair, Home Cleaning, Salon for Women)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL,
    icon VARCHAR(40) NOT NULL DEFAULT 'home',
    description VARCHAR(255) NOT NULL DEFAULT '',
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- provider_categories (which categories a provider is skilled/listed in)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS provider_categories (
    provider_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (provider_id, category_id),
    CONSTRAINT fk_provider_categories_provider FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_provider_categories_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- services (bookable line items under a category)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS services (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(170) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    duration_minutes INT UNSIGNED NOT NULL DEFAULT 60,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_services_slug (slug),
    KEY idx_services_category (category_id),
    CONSTRAINT fk_services_category FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- addresses (customer saved addresses)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS addresses (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    label VARCHAR(50) NOT NULL DEFAULT 'Home',
    line1 VARCHAR(255) NOT NULL,
    line2 VARCHAR(255) NOT NULL DEFAULT '',
    city VARCHAR(120) NOT NULL,
    state VARCHAR(120) NOT NULL DEFAULT '',
    pincode VARCHAR(10) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_addresses_user (user_id),
    CONSTRAINT fk_addresses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- bookings
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bookings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id INT UNSIGNED NOT NULL,
    service_id INT UNSIGNED NOT NULL,
    provider_id INT UNSIGNED NULL,
    address_id INT UNSIGNED NOT NULL,
    scheduled_date DATE NOT NULL,
    scheduled_time_slot VARCHAR(40) NOT NULL,
    status ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    price DECIMAL(10,2) NOT NULL,
    payment_status ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid',
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_bookings_customer (customer_id),
    KEY idx_bookings_provider (provider_id),
    KEY idx_bookings_status (status),
    CONSTRAINT fk_bookings_customer FOREIGN KEY (customer_id) REFERENCES users(id),
    CONSTRAINT fk_bookings_service FOREIGN KEY (service_id) REFERENCES services(id),
    CONSTRAINT fk_bookings_provider FOREIGN KEY (provider_id) REFERENCES users(id),
    CONSTRAINT fk_bookings_address FOREIGN KEY (address_id) REFERENCES addresses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- reviews (one per completed booking)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reviews (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    provider_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_reviews_booking (booking_id),
    KEY idx_reviews_provider (provider_id),
    CONSTRAINT fk_reviews_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_customer FOREIGN KEY (customer_id) REFERENCES users(id),
    CONSTRAINT fk_reviews_provider FOREIGN KEY (provider_id) REFERENCES users(id),
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Seed data
-- ---------------------------------------------------------------------

-- Default admin login: admin@hirehelper.test / Admin@123
-- Change this password immediately after your first login in production.
INSERT INTO users (name, email, phone, password_hash, role, status) VALUES
('Admin', 'admin@hirehelper.test', '9999999999', '$2y$12$xDP.wA7L.JYehin7whFy1OaLrQIIQfeYuXy2vW8vwfUxCSgVRqVvy', 'admin', 'active');

INSERT INTO categories (name, slug, icon, description, sort_order) VALUES
('AC & Appliance Repair', 'ac-appliance-repair', 'home', 'AC service, refrigerator, washing machine and microwave repair.', 1),
('Home Cleaning', 'home-cleaning', 'check', 'Deep cleaning, bathroom cleaning and sofa/carpet shampooing.', 2),
('Electrician', 'electrician', 'plus', 'Wiring, switchboard, fan and appliance installation.', 3),
('Plumber', 'plumber', 'map-pin', 'Tap, pipe, toilet and water tank repairs.', 4),
('Salon for Women', 'salon-for-women', 'star', 'At-home facial, waxing, threading and haircut.', 5),
('Painting', 'painting', 'home', 'Wall painting, waterproofing and wood polishing.', 6);

INSERT INTO services (category_id, name, slug, description, price, duration_minutes) VALUES
(1, 'AC Service & Repair', 'ac-service-repair', 'Complete AC gas check, jet cleaning and cooling coil cleaning.', 599.00, 60),
(1, 'Refrigerator Repair', 'refrigerator-repair', 'Diagnosis and repair for cooling and compressor issues.', 499.00, 45),
(1, 'Washing Machine Repair', 'washing-machine-repair', 'Front-load and top-load washing machine repair.', 449.00, 45),
(2, 'Full Home Deep Cleaning', 'full-home-deep-cleaning', 'Kitchen, bathroom, bedroom deep cleaning with eco-friendly products.', 2499.00, 240),
(2, 'Bathroom Cleaning', 'bathroom-cleaning', 'Tile, tap, mirror and floor deep cleaning for one bathroom.', 399.00, 45),
(2, 'Sofa Shampooing', 'sofa-shampooing', 'Steam cleaning and shampooing for a 5-seater sofa.', 899.00, 90),
(3, 'Switch & Socket Installation', 'switch-socket-installation', 'Installation or replacement of switches and sockets.', 199.00, 30),
(3, 'Ceiling Fan Installation', 'ceiling-fan-installation', 'Fan installation including wiring check.', 249.00, 40),
(4, 'Tap & Faucet Repair', 'tap-faucet-repair', 'Fixing or replacing leaking taps and faucets.', 199.00, 30),
(4, 'Bathroom Fitting Installation', 'bathroom-fitting-installation', 'Installation of wash basins, showers and health faucets.', 349.00, 60),
(5, 'Classic Facial & Cleanup', 'classic-facial-cleanup', 'At-home facial and cleanup session.', 699.00, 60),
(5, 'Full Body Waxing', 'full-body-waxing', 'Full body waxing with rica or chocolate wax.', 999.00, 90),
(6, 'Single Room Painting', 'single-room-painting', 'Two coats of emulsion paint for one room, materials extra.', 1499.00, 180),
(6, 'Waterproofing Treatment', 'waterproofing-treatment', 'Leakage diagnosis and waterproof coating for one wall/terrace.', 1999.00, 180);

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
    wallet_balance DECIMAL(10,2) NOT NULL DEFAULT 0,
    referral_code VARCHAR(12) NULL,
    referred_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_referral_code (referral_code),
    CONSTRAINT fk_users_referred_by FOREIGN KEY (referred_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- provider_profiles (1:1 extension of users where role = 'provider')
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS provider_profiles (
    user_id INT UNSIGNED NOT NULL,
    bio TEXT NULL,
    photo_path VARCHAR(255) NULL,
    experience_years TINYINT UNSIGNED NULL,
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
    image_path VARCHAR(255) NULL,
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
    image_path VARCHAR(255) NULL,
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
    lat DECIMAL(10,7) NULL,
    lng DECIMAL(10,7) NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_addresses_user (user_id),
    CONSTRAINT fk_addresses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- provider_locations (provider's most recent GPS ping while on a job)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS provider_locations (
    provider_id INT UNSIGNED NOT NULL,
    lat DECIMAL(10,7) NOT NULL,
    lng DECIMAL(10,7) NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (provider_id),
    CONSTRAINT fk_provider_locations_user FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE
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
    status ENUM('pending', 'offered', 'assigned', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    price DECIMAL(10,2) NOT NULL,
    coupon_id INT UNSIGNED NULL,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    payment_status ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid',
    start_otp CHAR(4) NULL,
    notes TEXT NULL,
    before_photo_path VARCHAR(255) NULL,
    after_photo_path VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_bookings_customer (customer_id),
    KEY idx_bookings_provider (provider_id),
    KEY idx_bookings_status (status),
    CONSTRAINT fk_bookings_customer FOREIGN KEY (customer_id) REFERENCES users(id),
    CONSTRAINT fk_bookings_service FOREIGN KEY (service_id) REFERENCES services(id),
    CONSTRAINT fk_bookings_provider FOREIGN KEY (provider_id) REFERENCES users(id),
    CONSTRAINT fk_bookings_address FOREIGN KEY (address_id) REFERENCES addresses(id),
    CONSTRAINT fk_bookings_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id)
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
    photo_path VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_reviews_booking (booking_id),
    KEY idx_reviews_provider (provider_id),
    CONSTRAINT fk_reviews_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_customer FOREIGN KEY (customer_id) REFERENCES users(id),
    CONSTRAINT fk_reviews_provider FOREIGN KEY (provider_id) REFERENCES users(id),
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- api_tokens (bearer tokens for the Flutter app; web login still uses
-- plain PHP sessions and never touches this table)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS api_tokens (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    device_label VARCHAR(100) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_api_tokens_hash (token_hash),
    KEY idx_api_tokens_user (user_id),
    CONSTRAINT fk_api_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- device_tokens (push tokens registered by the Flutter app; stored
-- ready for when a Firebase project is wired up to actually send to
-- them -- see app/Core/Push.php)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS device_tokens (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    push_token VARCHAR(255) NOT NULL,
    platform ENUM('android', 'ios') NOT NULL DEFAULT 'android',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_device_tokens_token (push_token),
    KEY idx_device_tokens_user (user_id),
    CONSTRAINT fk_device_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- notifications (in-app notification feed, read by both the website
-- and the Flutter app; independent of OS push delivery)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    body VARCHAR(255) NOT NULL DEFAULT '',
    booking_id INT UNSIGNED NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notifications_user (user_id, is_read),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notifications_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- booking_offers (audit log of the free auto-assignment algorithm --
-- every provider a booking was offered to, in order, and how they
-- responded; see App\Core\Dispatcher)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS booking_offers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id INT UNSIGNED NOT NULL,
    provider_id INT UNSIGNED NOT NULL,
    status ENUM('offered', 'accepted', 'rejected', 'expired') NOT NULL DEFAULT 'offered',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    responded_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_booking_offers_booking (booking_id),
    KEY idx_booking_offers_provider (provider_id),
    CONSTRAINT fk_booking_offers_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_booking_offers_provider FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- coupons
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS coupons (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(30) NOT NULL,
    discount_type ENUM('flat', 'percent') NOT NULL DEFAULT 'flat',
    discount_value DECIMAL(10,2) NOT NULL,
    max_discount DECIMAL(10,2) NULL,
    min_booking_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    usage_limit INT UNSIGNED NULL,
    used_count INT UNSIGNED NOT NULL DEFAULT 0,
    expires_at DATE NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_coupons_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- wallet_transactions (referral credits, future refunds; users.wallet_balance
-- is kept in sync by App\Models\User::adjustWallet)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wallet_transactions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL COMMENT 'positive = credit, negative = debit',
    reason VARCHAR(150) NOT NULL,
    booking_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_wallet_transactions_user (user_id),
    CONSTRAINT fk_wallet_transactions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_wallet_transactions_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- booking_messages (lightweight in-app chat, scoped to one booking)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS booking_messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    message VARCHAR(1000) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_booking_messages_booking (booking_id),
    CONSTRAINT fk_booking_messages_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_booking_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- serviceable_pincodes (if this table has any rows, a new address's
-- pincode must match one of them; empty table = no restriction, so this
-- is opt-in and never breaks a fresh install)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS serviceable_pincodes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pincode VARCHAR(10) NOT NULL,
    city VARCHAR(120) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_serviceable_pincodes_pincode (pincode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- support_tickets ("report an issue")
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS support_tickets (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    booking_id INT UNSIGNED NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open', 'resolved') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_support_tickets_user (user_id),
    CONSTRAINT fk_support_tickets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_support_tickets_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Seed data
-- ---------------------------------------------------------------------

-- Default admin login: admin@hirehelper.test / Admin@123
-- Change this password immediately after your first login in production.
INSERT INTO users (name, email, phone, password_hash, role, status, referral_code) VALUES
('Admin', 'admin@hirehelper.test', '9999999999', '$2y$12$xDP.wA7L.JYehin7whFy1OaLrQIIQfeYuXy2vW8vwfUxCSgVRqVvy', 'admin', 'active', 'ADMIN0001');

INSERT INTO categories (name, slug, icon, image_path, description, sort_order) VALUES
('AC & Appliance Repair', 'ac-appliance-repair', 'home', 'categories/ac-appliance-repair.svg', 'AC service, refrigerator, washing machine and microwave repair.', 1),
('Home Cleaning', 'home-cleaning', 'check', 'categories/home-cleaning.svg', 'Deep cleaning, bathroom cleaning and sofa/carpet shampooing.', 2),
('Electrician', 'electrician', 'plus', 'categories/electrician.svg', 'Wiring, switchboard, fan and appliance installation.', 3),
('Plumber', 'plumber', 'map-pin', 'categories/plumber.svg', 'Tap, pipe, toilet and water tank repairs.', 4),
('Salon for Women', 'salon-for-women', 'star', 'categories/salon-for-women.svg', 'At-home facial, waxing, threading and haircut.', 5),
('Salon for Men', 'salon-for-men', 'user', 'categories/salon-for-men.svg', 'At-home haircut, shave, facial and grooming.', 6),
('Painting', 'painting', 'home', 'categories/painting.svg', 'Wall painting, waterproofing and wood polishing.', 7),
('Pest Control', 'pest-control', 'check', 'categories/pest-control.svg', 'Cockroach, termite, mosquito and rodent control.', 8);

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
(6, 'Haircut & Styling', 'mens-haircut-styling', 'At-home haircut, beard trim and styling for men.', 349.00, 40),
(6, 'Classic Shave', 'classic-shave', 'Hot towel shave and skin care.', 199.00, 25),
(7, 'Single Room Painting', 'single-room-painting', 'Two coats of emulsion paint for one room, materials extra.', 1499.00, 180),
(7, 'Waterproofing Treatment', 'waterproofing-treatment', 'Leakage diagnosis and waterproof coating for one wall/terrace.', 1999.00, 180),
(8, 'General Pest Control', 'general-pest-control', 'Cockroach and general insect control for a 2BHK.', 899.00, 60),
(8, 'Termite Control', 'termite-control', 'Anti-termite treatment with warranty.', 1799.00, 120);

INSERT INTO coupons (code, discount_type, discount_value, max_discount, min_booking_amount, usage_limit, expires_at) VALUES
('FIRST100', 'flat', 100.00, NULL, 300.00, NULL, NULL),
('SAVE20', 'percent', 20.00, 300.00, 500.00, NULL, NULL);

-- Only run this on a pre-existing database -- a fresh install already has
-- all of this via schema.sql.
--
-- Adds: an audit log of auto-assignment offers, images for categories/
-- services/provider profiles, coupons, a wallet + referral credits,
-- review/job photos, in-app chat per booking, serviceability zones, and
-- support tickets ("report an issue").
--
-- Run via phpMyAdmin -> Import, or:
--   mysql -u USER -p DBNAME < database/migrations/002_auto_assign_and_growth_features.sql

ALTER TABLE categories
    ADD COLUMN image_path VARCHAR(255) NULL AFTER icon;

ALTER TABLE services
    ADD COLUMN image_path VARCHAR(255) NULL AFTER description;

ALTER TABLE provider_profiles
    ADD COLUMN photo_path VARCHAR(255) NULL AFTER bio,
    ADD COLUMN experience_years TINYINT UNSIGNED NULL AFTER photo_path;

ALTER TABLE users
    ADD COLUMN wallet_balance DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER status,
    ADD COLUMN referral_code VARCHAR(12) NULL AFTER wallet_balance,
    ADD COLUMN referred_by INT UNSIGNED NULL AFTER referral_code,
    ADD UNIQUE KEY uq_users_referral_code (referral_code),
    ADD CONSTRAINT fk_users_referred_by FOREIGN KEY (referred_by) REFERENCES users(id);

ALTER TABLE bookings
    ADD COLUMN coupon_id INT UNSIGNED NULL AFTER price,
    ADD COLUMN discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER coupon_id,
    ADD COLUMN before_photo_path VARCHAR(255) NULL AFTER notes,
    ADD COLUMN after_photo_path VARCHAR(255) NULL AFTER before_photo_path;

ALTER TABLE reviews
    ADD COLUMN photo_path VARCHAR(255) NULL AFTER comment;

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

ALTER TABLE bookings
    ADD CONSTRAINT fk_bookings_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id);

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

CREATE TABLE IF NOT EXISTS serviceable_pincodes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pincode VARCHAR(10) NOT NULL,
    city VARCHAR(120) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_serviceable_pincodes_pincode (pincode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

-- Backfill a referral code for every existing user so the column is never
-- empty for accounts created before this migration.
UPDATE users SET referral_code = UPPER(SUBSTRING(MD5(CONCAT(id, email, RAND())), 1, 8)) WHERE referral_code IS NULL;

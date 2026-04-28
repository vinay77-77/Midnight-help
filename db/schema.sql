-- Midnight-help MySQL schema (MySQL 8+ / InnoDB / utf8mb4)
-- Notes:
-- - Store password hashes (bcrypt/argon2), never plain text
-- - Responders are created by admins only (no public registration)

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Create a dedicated database (optional). If you already have one, comment this out.
CREATE DATABASE IF NOT EXISTS midnight_help
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_general_ci;
USE midnight_help;

-- ---------- Core Accounts ----------

CREATE TABLE IF NOT EXISTS admins (
  admin_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(254) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(32) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (admin_id),
  UNIQUE KEY uq_admins_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
  user_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(254) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(32) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_phone (phone)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS responders (
  responder_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(254) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(32) NOT NULL,
  availability_status ENUM('available','busy','offline') NOT NULL DEFAULT 'available',
  verified TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by_admin_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (responder_id),
  UNIQUE KEY uq_responders_phone (phone),
  UNIQUE KEY uq_responders_email (email),
  KEY idx_responders_availability (availability_status, verified),
  CONSTRAINT fk_responders_created_by_admin
    FOREIGN KEY (created_by_admin_id) REFERENCES admins(admin_id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Responders can support multiple service types.
CREATE TABLE IF NOT EXISTS responder_services (
  responder_id BIGINT UNSIGNED NOT NULL,
  service_type ENUM('elderly','vehicle','driving','sos') NOT NULL,
  PRIMARY KEY (responder_id, service_type),
  CONSTRAINT fk_responder_services_responder
    FOREIGN KEY (responder_id) REFERENCES responders(responder_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------- Requests / Assignment / Ratings ----------

CREATE TABLE IF NOT EXISTS requests (
  request_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  service_type ENUM('elderly','vehicle','driving','sos') NOT NULL,
  description TEXT NULL,
  -- location (keep both text + coordinates for flexibility)
  location_text VARCHAR(255) NULL,
  latitude DECIMAL(9,6) NULL,
  longitude DECIMAL(9,6) NULL,
  status ENUM('pending','accepted','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (request_id),
  KEY idx_requests_user_created (user_id, created_at),
  KEY idx_requests_status_created (status, created_at),
  KEY idx_requests_service_status (service_type, status),
  CONSTRAINT fk_requests_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_requests_lat
    CHECK (latitude IS NULL OR (latitude >= -90 AND latitude <= 90)),
  CONSTRAINT chk_requests_lng
    CHECK (longitude IS NULL OR (longitude >= -180 AND longitude <= 180))
) ENGINE=InnoDB;

-- Supports a single active responder per request, but keeps history (reassignments).
CREATE TABLE IF NOT EXISTS assignments (
  assignment_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  request_id BIGINT UNSIGNED NOT NULL,
  responder_id BIGINT UNSIGNED NOT NULL,
  assigned_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status ENUM('assigned','accepted','declined','cancelled','completed') NOT NULL DEFAULT 'assigned',
  PRIMARY KEY (assignment_id),
  KEY idx_assignments_request (request_id, assigned_time),
  KEY idx_assignments_responder (responder_id, assigned_time),
  CONSTRAINT fk_assignments_request
    FOREIGN KEY (request_id) REFERENCES requests(request_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_assignments_responder
    FOREIGN KEY (responder_id) REFERENCES responders(responder_id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- One rating per completed request.
CREATE TABLE IF NOT EXISTS ratings (
  rating_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  request_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  responder_id BIGINT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  feedback TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (rating_id),
  UNIQUE KEY uq_ratings_request (request_id),
  KEY idx_ratings_responder_created (responder_id, created_at),
  CONSTRAINT fk_ratings_request
    FOREIGN KEY (request_id) REFERENCES requests(request_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ratings_user
    FOREIGN KEY (user_id) REFERENCES users(user_id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_ratings_responder
    FOREIGN KEY (responder_id) REFERENCES responders(responder_id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_ratings_range
    CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- ---------- SOS delivery tracking (optional but useful) ----------
-- When service_type='sos', you typically notify all verified responders (+ admins).
-- This table lets you record who was notified and their response.

CREATE TABLE IF NOT EXISTS sos_alerts (
  sos_alert_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  request_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (sos_alert_id),
  UNIQUE KEY uq_sos_alerts_request (request_id),
  CONSTRAINT fk_sos_alerts_request
    FOREIGN KEY (request_id) REFERENCES requests(request_id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sos_alert_deliveries (
  sos_alert_delivery_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sos_alert_id BIGINT UNSIGNED NOT NULL,
  recipient_type ENUM('admin','responder') NOT NULL,
  admin_id BIGINT UNSIGNED NULL,
  responder_id BIGINT UNSIGNED NULL,
  delivery_status ENUM('queued','sent','failed','acknowledged') NOT NULL DEFAULT 'queued',
  sent_at TIMESTAMP NULL,
  acknowledged_at TIMESTAMP NULL,
  PRIMARY KEY (sos_alert_delivery_id),
  KEY idx_sos_deliveries_alert (sos_alert_id, delivery_status),
  KEY idx_sos_deliveries_responder (responder_id, delivery_status),
  KEY idx_sos_deliveries_admin (admin_id, delivery_status),
  CONSTRAINT fk_sos_deliveries_alert
    FOREIGN KEY (sos_alert_id) REFERENCES sos_alerts(sos_alert_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_sos_deliveries_admin
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_sos_deliveries_responder
    FOREIGN KEY (responder_id) REFERENCES responders(responder_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_sos_delivery_recipient
    CHECK (
      (recipient_type='admin' AND admin_id IS NOT NULL AND responder_id IS NULL) OR
      (recipient_type='responder' AND responder_id IS NOT NULL AND admin_id IS NULL)
    )
) ENGINE=InnoDB;


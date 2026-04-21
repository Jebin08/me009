-- ============================================
-- Database: registration_db
-- ============================================

CREATE DATABASE IF NOT EXISTS registration_db
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE registration_db;


-- --------------------------------------------
-- states table
-- --------------------------------------------
DROP TABLE IF EXISTS states;

CREATE TABLE states (
    id         INT(5)      NOT NULL AUTO_INCREMENT,
    state_name VARCHAR(80) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------
-- districts table (linked to states)
-- --------------------------------------------
DROP TABLE IF EXISTS districts;

CREATE TABLE districts (
    id            INT(5)      NOT NULL AUTO_INCREMENT,
    state_id      INT(5)      NOT NULL,
    district_name VARCHAR(80) NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_state (state_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------
-- main registrations table
-- is_deleted = 1 means soft deleted (not really gone)
-- --------------------------------------------
DROP TABLE IF EXISTS registrations;

CREATE TABLE registrations (
    id           INT(11)      NOT NULL AUTO_INCREMENT,
    full_name    VARCHAR(100) NOT NULL,
    mobile       VARCHAR(15)  NOT NULL,
    email        VARCHAR(150) NOT NULL,
    gender       ENUM('Male','Female','Other') NOT NULL DEFAULT 'Male',
    dob          DATE         DEFAULT NULL,
    state_id     INT(5)       DEFAULT NULL,
    district_id  INT(5)       DEFAULT NULL,
    address      TEXT,
    photo        VARCHAR(255) DEFAULT NULL,
    is_active    TINYINT(1)   NOT NULL DEFAULT 1,
    is_deleted   TINYINT(1)   NOT NULL DEFAULT 0,
    deleted_at   DATETIME     DEFAULT NULL,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_name    (full_name),
    INDEX idx_mobile  (mobile),
    INDEX idx_deleted (is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------
-- audit_log - tracks all actions (backup table)
-- --------------------------------------------
DROP TABLE IF EXISTS audit_log;

CREATE TABLE audit_log (
    id          INT(11)      NOT NULL AUTO_INCREMENT,
    action      VARCHAR(20)  NOT NULL,
    record_id   INT(11)      DEFAULT NULL,
    record_name VARCHAR(150) DEFAULT NULL,
    remarks     TEXT,
    ip_address  VARCHAR(45)  DEFAULT NULL,
    logged_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_action (action),
    INDEX idx_logged (logged_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------
-- states seed data
-- --------------------------------------------
INSERT INTO states (state_name) VALUES
('Tamil Nadu'),
('Kerala'),
('Karnataka'),
('Andhra Pradesh'),
('Telangana'),
('Maharashtra'),
('Gujarat'),
('Rajasthan'),
('Uttar Pradesh'),
('West Bengal');


-- --------------------------------------------
-- districts seed data
-- --------------------------------------------
INSERT INTO districts (state_id, district_name) VALUES
-- Tamil Nadu
(1,'Chennai'),(1,'Coimbatore'),(1,'Madurai'),(1,'Trichy'),(1,'Salem'),
(1,'Erode'),(1,'Vellore'),(1,'Tirunelveli'),(1,'Thanjavur'),(1,'Tiruppur'),
-- Kerala
(2,'Thiruvananthapuram'),(2,'Kochi'),(2,'Kozhikode'),(2,'Thrissur'),
(2,'Kollam'),(2,'Alappuzha'),(2,'Palakkad'),(2,'Malappuram'),
-- Karnataka
(3,'Bengaluru'),(3,'Mysuru'),(3,'Hubli'),(3,'Mangaluru'),
(3,'Belagavi'),(3,'Davangere'),(3,'Tumakuru'),(3,'Shivamogga'),
-- Andhra Pradesh
(4,'Visakhapatnam'),(4,'Vijayawada'),(4,'Guntur'),(4,'Nellore'),(4,'Kurnool'),
-- Telangana
(5,'Hyderabad'),(5,'Warangal'),(5,'Nizamabad'),(5,'Karimnagar'),
-- Maharashtra
(6,'Mumbai'),(6,'Pune'),(6,'Nagpur'),(6,'Nashik'),
-- Gujarat
(7,'Ahmedabad'),(7,'Surat'),(7,'Vadodara'),(7,'Rajkot'),
-- Rajasthan
(8,'Jaipur'),(8,'Jodhpur'),(8,'Udaipur'),(8,'Kota'),
-- Uttar Pradesh
(9,'Lucknow'),(9,'Kanpur'),(9,'Agra'),(9,'Varanasi'),
-- West Bengal
(10,'Kolkata'),(10,'Howrah'),(10,'Durgapur'),(10,'Asansol');


-- --------------------------------------------
-- sample data
-- --------------------------------------------
INSERT INTO registrations (full_name, mobile, email, gender, dob, state_id, district_id, address, is_active) VALUES
('Arun Krishnamurthy', '9876543210', 'arun.k@example.com',    'Male',   '1998-05-14', 1, 1,  'No.12 Anna Nagar, Chennai',  1),
('Priya Sundaram',     '9876543211', 'priya.s@example.com',   'Female', '2000-02-21', 1, 2,  'RS Puram, Coimbatore',       1),
('Rahul Nair',         '9876543212', 'rahul.n@example.com',   'Male',   '1999-08-30', 2, 11, 'Statue Junction, TVM',       1),
('Sneha Iyer',         '9876543213', 'sneha.i@example.com',   'Female', '2001-11-02', 1, 3,  'Anna Nagar, Madurai',         1),
('Karthikeyan Raj',    '9876543214', 'karthik.r@example.com', 'Male',   '1997-07-19', 3, 19, 'MG Road, Bengaluru',          1);

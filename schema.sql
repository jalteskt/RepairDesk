-- RepairDesk MySQL Database Schema
CREATE DATABASE IF NOT EXISTS repairdesk_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE repairdesk_db;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'technician', 'customer') NOT NULL DEFAULT 'customer',
    phone VARCHAR(25) DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Customer Profiles
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) DEFAULT NULL,
    phone VARCHAR(25) NOT NULL,
    address TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 3. Repair Jobs (Uses 6-Digit Ticket Numbers)
CREATE TABLE IF NOT EXISTS jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_number CHAR(6) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    technician_id INT DEFAULT NULL,
    device_category VARCHAR(50) NOT NULL,
    device_model VARCHAR(100) NOT NULL,
    serial_number VARCHAR(100) DEFAULT NULL,
    issue_description TEXT NOT NULL,
    diagnostic_notes TEXT DEFAULT NULL,
    status ENUM('received', 'diagnosing', 'in_progress', 'waiting_parts', 'ready', 'completed', 'cancelled') NOT NULL DEFAULT 'received',
    priority ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
    estimated_cost DECIMAL(10,2) DEFAULT 0.00,
    final_cost DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (technician_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. Status Audit Logs
CREATE TABLE IF NOT EXISTS job_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    user_id INT NOT NULL,
    previous_status VARCHAR(50) DEFAULT NULL,
    new_status VARCHAR(50) NOT NULL,
    note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed Accounts (Password for all 3 seeded users: Password123!)
INSERT INTO users (id, full_name, email, password_hash, role, phone) VALUES
(1, 'System Admin', 'admin@repairdesk.local', '$2y$10$ikqZAevkZlgWmYIn/4FJQOMPLeDHKGAE4FlV3e6TebECNluEMfGXG', 'admin', '09170000001'),
(2, 'Lead Technician', 'tech@repairdesk.local', '$2y$10$ikqZAevkZlgWmYIn/4FJQOMPLeDHKGAE4FlV3e6TebECNluEMfGXG', 'technician', '09170000002'),
(3, 'Sample Customer', 'customer@repairdesk.local', '$2y$10$ikqZAevkZlgWmYIn/4FJQOMPLeDHKGAE4FlV3e6TebECNluEMfGXG', 'customer', '09170000003')
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO customers (id, user_id, name, email, phone, address) VALUES
(1, 3, 'Sample Customer', 'customer@repairdesk.local', '09170000003', 'Metro Manila, Philippines')
ON DUPLICATE KEY UPDATE id=id;

-- Seed Initial Sample Job
INSERT INTO jobs (ticket_number, customer_id, technician_id, device_category, device_model, serial_number, issue_description, status, priority, estimated_cost) VALUES
('849201', 1, 2, 'Laptop', 'MacBook Pro M1', 'C02FX012Q05D', 'Screen cracked and battery draining rapidly.', 'in_progress', 'high', 2500.00)
ON DUPLICATE KEY UPDATE ticket_number=ticket_number;

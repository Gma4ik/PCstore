-- Migration: add superadmin role
-- Run this once if the database already exists

USE pc_store;

ALTER TABLE users
  MODIFY COLUMN role ENUM('user','admin','superadmin') NOT NULL DEFAULT 'user';

-- Insert superadmin (password: admin123)
INSERT INTO users (name, email, password, role) VALUES
('Super Admin', 'superadmin@pcstore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin')
ON DUPLICATE KEY UPDATE role = 'superadmin';

-- Migration: add phone and address fields to users
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS phone   VARCHAR(30)  DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT NULL;

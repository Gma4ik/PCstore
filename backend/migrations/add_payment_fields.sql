-- Migration: add payment_method and payment_status columns to orders table
-- Requirement 4.1, 4.8

USE pc_store;

ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS payment_method ENUM('card', 'cash_on_delivery') NOT NULL DEFAULT 'cash_on_delivery' AFTER status,
  ADD COLUMN IF NOT EXISTS payment_status ENUM('paid', 'pending') NOT NULL DEFAULT 'pending' AFTER payment_method;

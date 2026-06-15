-- PC Store database setup script
-- Run once to create the database, tables, and seed data.

CREATE DATABASE IF NOT EXISTS pc_store
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE pc_store;

-- ─── Tables ──────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS categories (
  id         VARCHAR(50)  PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  icon       VARCHAR(10)  NOT NULL DEFAULT '📦',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100)  NOT NULL,
  email      VARCHAR(150)  NOT NULL UNIQUE,
  password   VARCHAR(255)  NOT NULL,
  role       ENUM('user','admin','superadmin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(200)   NOT NULL,
  price          DECIMAL(10,2)  NOT NULL,
  category       VARCHAR(50)    NOT NULL,
  image          VARCHAR(255),
  description    TEXT,
  specifications JSON,
  in_stock       TINYINT(1)     NOT NULL DEFAULT 1,
  stock_quantity INT            NOT NULL DEFAULT 0,
  rating         DECIMAL(3,1)   NOT NULL DEFAULT 0,
  reviews        INT            NOT NULL DEFAULT 0,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  user_id        INT           NOT NULL,
  status         ENUM('Processing','Shipped','Delivered','Cancelled') NOT NULL DEFAULT 'Processing',
  payment_method ENUM('card','cash_on_delivery') NOT NULL DEFAULT 'cash_on_delivery',
  payment_status ENUM('paid','pending') NOT NULL DEFAULT 'pending',
  total          DECIMAL(10,2) NOT NULL,
  customer_info  JSON,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS order_items (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  order_id   INT           NOT NULL,
  product_id INT           NOT NULL,
  name       VARCHAR(200)  NOT NULL,
  quantity   INT           NOT NULL,
  price      DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ─── Default users ───────────────────────────────────────────────────────────
-- Passwords: superadmin123 / admin123  (bcrypt)

INSERT INTO users (name, email, password, role) VALUES
('Super Admin', 'superadmin@pcstore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin'),
('Admin', 'admin@pcstore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE id = id;

-- ─── Seed categories ─────────────────────────────────────────────────────────

INSERT INTO categories (id, name, icon) VALUES
('processors',    'Processors',     '🔲'),
('graphics-cards','Graphics Cards', '🎮'),
('memory',        'Memory',         '💾'),
('storage',       'Storage',        '💿'),
('motherboards',  'Motherboards',   '🖥️'),
('cooling',       'Cooling',        '❄️'),
('power-supplies','Power Supplies', '⚡'),
('accessories',   'Accessories',    '🖱️'),
('monitors',      'Monitors',       '🖥️')
ON DUPLICATE KEY UPDATE id = id;

-- ─── Seed products (16 items from mockData.js) ───────────────────────────────

INSERT INTO products (id, name, price, category, image, description, specifications, in_stock, rating, reviews) VALUES
(1,
 'Intel Core i9-13900K Processor',
 589.99,
 'processors',
 '/placeholders/intel-i9.png',
 'The Intel Core i9-13900K is a high-performance desktop processor with 24 cores and 32 threads. Perfect for gaming, content creation, and demanding workloads.',
 '{"cores":"24 (8 P-cores + 16 E-cores)","threads":"32","baseClock":"3.0 GHz","boostClock":"5.8 GHz","cache":"36MB Intel Smart Cache","tdp":"125W"}',
 1, 4.8, 245),

(2,
 'AMD Ryzen 9 7950X',
 699.99,
 'processors',
 '/placeholders/amd-ryzen9.png',
 'AMD Ryzen 9 7950X - 16 cores and 24 threads of pure computing power. Built on Zen 4 architecture for exceptional performance.',
 '{"cores":"16","threads":"24","baseClock":"4.5 GHz","boostClock":"5.7 GHz","cache":"80MB","tdp":"170W"}',
 1, 4.9, 189),

(3,
 'NVIDIA GeForce RTX 4090',
 1599.99,
 'graphics-cards',
 '/placeholders/rtx-4090.png',
 'The ultimate GeForce GPU. It brings an enormous leap in performance, efficiency, and AI-powered graphics with DLSS 3.',
 '{"memory":"24GB GDDR6X","cudaCores":"16384","baseClock":"2230 MHz","boostClock":"2520 MHz","tdp":"450W"}',
 1, 4.9, 412),

(4,
 'AMD Radeon RX 7900 XTX',
 999.99,
 'graphics-cards',
 '/placeholders/rx-7900xtx.png',
 'Experience ultra-high framerate gaming at 4K with the AMD Radeon RX 7900 XTX graphics card.',
 '{"memory":"24GB GDDR6","streamProcessors":"6144","gameClock":"2300 MHz","boostClock":"2500 MHz","tdp":"355W"}',
 1, 4.7, 298),

(5,
 'Corsair Vengeance DDR5 32GB',
 129.99,
 'memory',
 '/placeholders/corsair-ddr5.png',
 'High-performance DDR5 RAM for next-generation computing. 32GB kit (2x16GB) at 5200MHz.',
 '{"capacity":"32GB (2x16GB)","speed":"5200MHz","latency":"CL40","voltage":"1.25V"}',
 1, 4.6, 567),

(6,
 'G.Skill Trident Z5 RGB 32GB',
 149.99,
 'memory',
 '/placeholders/gskill-trident.png',
 'Premium DDR5 RAM with stunning RGB lighting. Optimized for Intel and AMD platforms.',
 '{"capacity":"32GB (2x16GB)","speed":"6000MHz","latency":"CL30","voltage":"1.35V"}',
 1, 4.8, 423),

(7,
 'Samsung 990 PRO 2TB NVMe SSD',
 169.99,
 'storage',
 '/placeholders/samsung-990pro.png',
 'Blazing fast PCIe 4.0 NVMe SSD with sequential read/write speeds up to 7,450/6,900 MB/s.',
 '{"capacity":"2TB","interface":"PCIe 4.0 x4","readSpeed":"7,450 MB/s","writeSpeed":"6,900 MB/s","formFactor":"M.2 2280"}',
 1, 4.9, 834),

(8,
 'WD Black SN850X 2TB',
 149.99,
 'storage',
 '/placeholders/wd-black-sn850x.png',
 'High-performance NVMe SSD designed for gamers. Sequential read speeds up to 7,300 MB/s.',
 '{"capacity":"2TB","interface":"PCIe 4.0 x4","readSpeed":"7,300 MB/s","writeSpeed":"6,600 MB/s","formFactor":"M.2 2280"}',
 1, 4.7, 612),

(9,
 'ASUS ROG Maximus Z790 Hero',
 629.99,
 'motherboards',
 '/placeholders/asus-z790.png',
 'Premium ATX motherboard for Intel 13th gen processors. Features robust power delivery and comprehensive cooling.',
 '{"socket":"LGA 1700","chipset":"Intel Z790","formFactor":"ATX","memorySupport":"DDR5 up to 7000MHz","pcieSlots":"1x PCIe 5.0 x16, 1x PCIe 4.0 x16"}',
 1, 4.8, 276),

(10,
 'MSI MPG B650 CARBON WIFI',
 299.99,
 'motherboards',
 '/placeholders/msi-b650.png',
 'Feature-rich AM5 motherboard for AMD Ryzen 7000 series. Excellent value for high-end builds.',
 '{"socket":"AM5","chipset":"AMD B650","formFactor":"ATX","memorySupport":"DDR5 up to 6600MHz","pcieSlots":"1x PCIe 5.0 x16, 1x PCIe 4.0 x16"}',
 1, 4.6, 389),

(11,
 'NZXT Kraken Z73 RGB',
 279.99,
 'cooling',
 '/placeholders/nzxt-kraken.png',
 '360mm AIO liquid cooler with customizable LCD display. Superior cooling performance for high-end CPUs.',
 '{"radiatorSize":"360mm","fanSpeed":"500-2000 RPM","noiseLevel":"21-36 dBA","tdp":"280W"}',
 1, 4.7, 445),

(12,
 'Corsair RM1000x 1000W PSU',
 199.99,
 'power-supplies',
 '/placeholders/corsair-rm1000x.png',
 'Fully modular 80 PLUS Gold certified power supply. Delivers stable and efficient power to your system.',
 '{"wattage":"1000W","efficiency":"80 PLUS Gold","modularity":"Fully Modular","fanSize":"135mm"}',
 1, 4.8, 521),

(13,
 'Logitech MX Master 3S',
 99.99,
 'accessories',
 '/placeholders/mx-master3s.png',
 'Wireless performance mouse with ultra-fast scrolling and customizable buttons.',
 '{"connectivity":"Bluetooth, USB-C","dpi":"Up to 8000","battery":"Up to 70 days","weight":"141g"}',
 1, 4.9, 1234),

(14,
 'Corsair K100 RGB Mechanical Keyboard',
 249.99,
 'accessories',
 '/placeholders/corsair-k100.png',
 'Premium mechanical gaming keyboard with Cherry MX switches and per-key RGB lighting.',
 '{"switches":"Cherry MX Speed","connectivity":"Wired USB","backlighting":"Per-key RGB","macroKeys":"6 dedicated macro keys"}',
 1, 4.7, 678),

(15,
 'Dell UltraSharp U2723QE 27"',
 649.99,
 'monitors',
 '/placeholders/dell-u2723qe.png',
 '4K USB-C hub monitor with IPS Black technology. Perfect for professionals and content creators.',
 '{"screenSize":"27 inches","resolution":"3840 x 2160","panelType":"IPS Black","refreshRate":"60Hz","connectivity":"USB-C, HDMI, DisplayPort"}',
 1, 4.8, 345),

(16,
 'LG 27GP950-B 4K Gaming Monitor',
 799.99,
 'monitors',
 '/placeholders/lg-27gp950.png',
 '4K Nano IPS gaming monitor with 144Hz refresh rate and NVIDIA G-SYNC compatibility.',
 '{"screenSize":"27 inches","resolution":"3840 x 2160","panelType":"Nano IPS","refreshRate":"144Hz","responseTime":"1ms"}',
 1, 4.7, 567)

ON DUPLICATE KEY UPDATE id = id;

-- ─── Reviews ─────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS reviews (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT      NOT NULL,
  user_id    INT      NOT NULL,
  rating     TINYINT  NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment    TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_product (user_id, product_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
);

-- ─── Wishlists ────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS wishlists (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  product_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_wishlist (user_id, product_id),
  FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ─── Password reset tokens ────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS password_reset_tokens (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT          NOT NULL,
  token      VARCHAR(64)  NOT NULL UNIQUE,
  expires_at DATETIME     NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Feature upgrades: trailer, voucher, rating, notifications, combos
-- Run these SQL statements in your cinema database.

-- 1) Trailer URL for each movie
ALTER TABLE phim
  ADD COLUMN trailer_url VARCHAR(255) NULL;

-- 2) Voucher system
CREATE TABLE IF NOT EXISTS vouchers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  discount_type ENUM('percent','amount') NOT NULL,
  discount_value INT NOT NULL,
  max_discount INT DEFAULT NULL,
  min_total INT DEFAULT 0,
  start_date DATE DEFAULT NULL,
  end_date DATE DEFAULT NULL,
  usage_limit INT DEFAULT NULL,
  used_count INT DEFAULT 0,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS voucher_usages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  voucher_id INT NOT NULL,
  user_id INT NOT NULL,
  suat_chieu_id INT NOT NULL,
  discount_amount INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_voucher_user (voucher_id, user_id),
  KEY idx_user (user_id),
  KEY idx_suat (suat_chieu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Rating system (1-5)
CREATE TABLE IF NOT EXISTS ratings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  phim_id INT NOT NULL,
  rating TINYINT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_phim (user_id, phim_id),
  KEY idx_phim (phim_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) Notifications
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  body VARCHAR(500) DEFAULT NULL,
  link VARCHAR(255) DEFAULT NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5) Combos (popcorn, drink, ...)
CREATE TABLE IF NOT EXISTS combos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ten VARCHAR(100) NOT NULL,
  mo_ta VARCHAR(255) DEFAULT NULL,
  gia INT NOT NULL,
  active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS combo_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  suat_chieu_id INT NOT NULL,
  combo_id INT NOT NULL,
  so_luong INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user (user_id),
  KEY idx_suat (suat_chieu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data (optional)
INSERT INTO vouchers (code, discount_type, discount_value, max_discount, min_total, start_date, end_date, usage_limit, active)
VALUES
  ('TTVH10', 'percent', 10, 50000, 150000, '2025-01-01', '2027-12-31', 500, 1),
  ('GIAM20K', 'amount', 20000, NULL, 80000, '2025-01-01', '2027-12-31', 200, 1)
ON DUPLICATE KEY UPDATE code=code;

INSERT INTO combos (ten, mo_ta, gia, active)
VALUES
  ('Combo Bắp + Nước', 'Bắp rang + Pepsi 22oz', 45000, 1),
  ('Combo Couple', '2 Bắp + 2 Nước', 79000, 1),
  ('Combo Kids', 'Bắp nhỏ + Nước + Snack', 39000, 1)
ON DUPLICATE KEY UPDATE ten=ten;

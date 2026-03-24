-- Chạy đoạn mã này trong phpMyAdmin của bạn để tạo bảng chứa dữ liệu tin nhắn:

CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'ID của người dùng thường',
  `is_admin` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 nếu là tin nhắn từ admin, 0 nếu từ user',
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

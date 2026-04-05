-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 31, 2026 at 08:35 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cinema`
--

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'ID của người dùng thường',
  `is_admin` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 nếu là tin nhắn từ admin, 0 nếu từ user',
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `user_id`, `is_admin`, `message`, `created_at`) VALUES
(1, 5, 0, 'alo', '2026-03-23 09:31:02'),
(2, 5, 0, 'alo', '2026-03-23 09:31:10'),
(3, 5, 1, 'lo cc', '2026-03-23 09:31:16'),
(4, 15, 0, 'alo', '2026-03-24 06:00:39'),
(5, 15, 1, 'alo', '2026-03-24 06:00:55'),
(6, 15, 1, 'tôi đây', '2026-03-24 06:01:04'),
(7, 15, 0, 'alo admin', '2026-03-25 15:25:42'),
(8, 15, 0, 'hello bro', '2026-03-25 16:25:29'),
(9, 15, 0, 'admin l', '2026-03-25 16:41:51'),
(10, 15, 0, 'hello', '2026-03-25 16:48:34');

-- --------------------------------------------------------

--
-- Table structure for table `combos`
--

CREATE TABLE `combos` (
  `id` int(11) NOT NULL,
  `ten` varchar(255) NOT NULL,
  `mo_ta` text DEFAULT NULL,
  `gia` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `combos`
--

INSERT INTO `combos` (`id`, `ten`, `mo_ta`, `gia`, `active`) VALUES
(1, 'Combo 1 Bắp 1 Nước', '1 Bắp tự chọn + 1 Nước tự chọn', 75000, 1),
(2, 'Combo 2 Bắp 2 Nước', '2 Bắp tự chọn + 2 Nước tự chọn', 140000, 1),
(3, '🍿 Bắp Phô Mai (Lớn)', 'Bắp rang bơ vị phô mai đậm đà', 55000, 1),
(4, '🍿 Bắp Caramel (Lớn)', 'Bắp rang bơ vị caramel ngọt ngào', 50000, 1),
(5, '🍿 Bắp Truyền Thống', 'Bắp rang bơ vị mặn truyền thống', 45000, 1),
(6, '🥤 Nước Ngọt Pepsi (Lớn)', 'Ly nước ngọt Pepsi tươi mát', 32000, 1),
(7, '🥤 Nước Ngọt 7Up (Lớn)', 'Ly nước ngọt 7Up tươi mát', 32000, 1),
(8, '🥤 Nước Ngọt Mirinda (Lớn)', 'Ly nước ngọt Mirinda Cam', 32000, 1);

-- --------------------------------------------------------

--
-- Table structure for table `combo_orders`
--

CREATE TABLE `combo_orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `suat_chieu_id` int(11) NOT NULL,
  `combo_id` int(11) NOT NULL,
  `so_luong` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `combo_orders`
--

INSERT INTO `combo_orders` (`id`, `user_id`, `suat_chieu_id`, `combo_id`, `so_luong`, `created_at`) VALUES
(1, 14, 10, 6, 1, '2026-03-18 11:44:36'),
(2, 14, 10, 7, 1, '2026-03-18 11:44:36'),
(3, 14, 10, 4, 1, '2026-03-19 04:53:31'),
(4, 14, 10, 6, 1, '2026-03-19 04:53:31'),
(5, 14, 10, 3, 1, '2026-03-19 04:58:52'),
(6, 14, 10, 5, 1, '2026-03-19 04:58:52'),
(7, 4, 10, 5, 1, '2026-03-19 06:26:03'),
(8, 4, 10, 6, 1, '2026-03-19 06:26:03'),
(9, 4, 10, 7, 1, '2026-03-19 06:33:56'),
(10, 4, 10, 5, 1, '2026-03-19 06:46:27'),
(11, 4, 10, 1, 1, '2026-03-19 08:29:16'),
(12, 15, 10, 7, 1, '2026-03-24 05:58:44'),
(13, 4, 18, 5, 1, '2026-03-31 04:40:15'),
(14, 4, 18, 6, 1, '2026-03-31 04:40:15'),
(15, 4, 32, 3, 1, '2026-03-31 04:48:29'),
(16, 4, 32, 4, 1, '2026-03-31 04:48:29'),
(17, 4, 32, 2, 1, '2026-03-31 04:53:57'),
(18, 4, 32, 3, 1, '2026-03-31 04:53:57');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `target_type` enum('post','phim') NOT NULL,
  `target_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `noi_dung` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `user_id`, `target_type`, `target_id`, `parent_id`, `noi_dung`, `created_at`) VALUES
(1, 5, 'phim', 1, NULL, 'bình thường kkk', '2026-03-15 14:36:44'),
(2, 5, 'phim', 1, 1, 'kk', '2026-03-15 15:20:20'),
(3, 4, 'phim', 6, NULL, 'ok', '2026-03-15 17:01:04'),
(4, 4, 'phim', 6, 3, 'ok', '2026-03-15 17:01:15'),
(5, 4, 'phim', 6, NULL, 'ok', '2026-03-15 17:01:29'),
(6, 4, 'phim', 7, NULL, 'gớm', '2026-03-15 17:02:39'),
(7, 4, 'phim', 7, 6, 'sao gớm', '2026-03-15 17:02:48'),
(8, 4, 'phim', 7, 6, 'sao gớm', '2026-03-15 17:03:13'),
(13, 5, 'phim', 2, NULL, 'alo', '2026-03-24 05:51:09'),
(14, 5, 'phim', 2, 13, 'lo', '2026-03-24 05:51:14'),
(15, 4, 'post', 1, NULL, 'hay lắm nha', '2026-03-24 06:08:38'),
(16, 4, 'post', 1, 15, 'hay lắm', '2026-03-25 16:41:39'),
(17, 4, 'post', 1, 15, 'nj', '2026-03-25 16:48:16'),
(18, 15, 'post', 6, NULL, 'hay ào', '2026-03-25 16:52:16'),
(20, 15, 'post', 9, NULL, 'admin re coi', '2026-03-25 18:13:25');

-- --------------------------------------------------------

--
-- Table structure for table `feed_scores`
--

CREATE TABLE `feed_scores` (
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `relevance_score` decimal(8,4) DEFAULT 0.0000,
  `sig_relationship` decimal(5,2) DEFAULT 0.00,
  `sig_recency` decimal(5,4) DEFAULT 0.0000,
  `sig_engagement` decimal(5,2) DEFAULT 0.00,
  `computed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `follows`
--

CREATE TABLE `follows` (
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ghe`
--

CREATE TABLE `ghe` (
  `id` int(11) NOT NULL,
  `phong_id` int(11) DEFAULT NULL,
  `ten_ghe` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ghe`
--

INSERT INTO `ghe` (`id`, `phong_id`, `ten_ghe`) VALUES
(1, 1, 'A1'),
(2, 1, 'A2'),
(3, 1, 'A3'),
(4, 1, 'A4'),
(5, 1, 'A5'),
(6, 1, 'A6'),
(7, 1, 'A7'),
(8, 1, 'A8'),
(9, 1, 'B1'),
(10, 1, 'B2'),
(11, 1, 'B3'),
(12, 1, 'B4'),
(13, 1, 'B5'),
(14, 1, 'B6'),
(15, 1, 'B7'),
(16, 1, 'B8'),
(17, 1, 'C1'),
(18, 1, 'C2'),
(19, 1, 'C3'),
(20, 1, 'C4'),
(21, 1, 'C5'),
(22, 1, 'C6'),
(23, 1, 'C7'),
(24, 1, 'C8'),
(25, 1, 'D1'),
(26, 1, 'D2'),
(27, 1, 'D3'),
(28, 1, 'D4'),
(29, 1, 'D5'),
(30, 1, 'D6'),
(31, 1, 'D7'),
(32, 1, 'D8'),
(33, 1, 'E1'),
(34, 1, 'E2'),
(35, 1, 'E3'),
(36, 1, 'E4'),
(37, 1, 'E5'),
(38, 1, 'E6'),
(39, 1, 'E7'),
(40, 1, 'E8'),
(41, 2, 'A1'),
(42, 2, 'A2'),
(43, 2, 'A3'),
(44, 2, 'A4'),
(45, 2, 'A5'),
(46, 2, 'A6'),
(47, 2, 'A7'),
(48, 2, 'A8'),
(49, 2, 'A9'),
(50, 2, 'A10'),
(51, 2, 'B1'),
(52, 2, 'B2'),
(53, 2, 'B3'),
(54, 2, 'B4'),
(55, 2, 'B5'),
(56, 2, 'B6'),
(57, 2, 'B7'),
(58, 2, 'B8'),
(59, 2, 'B9'),
(60, 2, 'B10'),
(61, 2, 'C1'),
(62, 2, 'C2'),
(63, 2, 'C3'),
(64, 2, 'C4'),
(65, 2, 'C5'),
(66, 2, 'C6'),
(67, 2, 'C7'),
(68, 2, 'C8'),
(69, 2, 'C9'),
(70, 2, 'C10'),
(71, 2, 'D1'),
(72, 2, 'D2'),
(73, 2, 'D3'),
(74, 2, 'D4'),
(75, 2, 'D5'),
(76, 2, 'D6'),
(77, 2, 'D7'),
(78, 2, 'D8'),
(79, 2, 'D9'),
(80, 2, 'D10'),
(81, 3, 'A1'),
(82, 3, 'A2'),
(83, 3, 'A3'),
(84, 3, 'A4'),
(85, 3, 'A5'),
(86, 3, 'A6'),
(87, 3, 'A7'),
(88, 3, 'A8'),
(89, 3, 'A9'),
(90, 3, 'A10'),
(91, 3, 'B1'),
(92, 3, 'B2'),
(93, 3, 'B3'),
(94, 3, 'B4'),
(95, 3, 'B5'),
(96, 3, 'B6'),
(97, 3, 'B7'),
(98, 3, 'B8'),
(99, 3, 'B9'),
(100, 3, 'B10'),
(101, 3, 'C1'),
(102, 3, 'C2'),
(103, 3, 'C3'),
(104, 3, 'C4'),
(105, 3, 'C5'),
(106, 3, 'C6'),
(107, 3, 'C7'),
(108, 3, 'C8'),
(109, 3, 'C9'),
(110, 3, 'C10'),
(111, 3, 'D1'),
(112, 3, 'D2'),
(113, 3, 'D3'),
(114, 3, 'D4'),
(115, 3, 'D5'),
(116, 3, 'D6'),
(117, 3, 'D7'),
(118, 3, 'D8'),
(119, 3, 'D9'),
(120, 3, 'D10'),
(121, 4, 'A1'),
(122, 4, 'A2'),
(123, 4, 'A3'),
(124, 4, 'A4'),
(125, 4, 'A5'),
(126, 4, 'A6'),
(127, 4, 'A7'),
(128, 4, 'A8'),
(129, 4, 'A9'),
(130, 4, 'A10'),
(131, 4, 'B1'),
(132, 4, 'B2'),
(133, 4, 'B3'),
(134, 4, 'B4'),
(135, 4, 'B5'),
(136, 4, 'B6'),
(137, 4, 'B7'),
(138, 4, 'B8'),
(139, 4, 'B9'),
(140, 4, 'B10'),
(141, 4, 'C1'),
(142, 4, 'C2'),
(143, 4, 'C3'),
(144, 4, 'C4'),
(145, 4, 'C5'),
(146, 4, 'C6'),
(147, 4, 'C7'),
(148, 4, 'C8'),
(149, 4, 'C9'),
(150, 4, 'C10'),
(151, 4, 'D1'),
(152, 4, 'D2'),
(153, 4, 'D3'),
(154, 4, 'D4'),
(155, 4, 'D5'),
(156, 4, 'D6'),
(157, 4, 'D7'),
(158, 4, 'D8'),
(159, 4, 'D9'),
(160, 4, 'D10');

-- --------------------------------------------------------

--
-- Table structure for table `gift_cards`
--

CREATE TABLE `gift_cards` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `balance` int(11) NOT NULL DEFAULT 0,
  `used` tinyint(1) DEFAULT 0,
  `used_by` int(11) DEFAULT NULL,
  `used_at` datetime DEFAULT NULL,
  `expired_at` date DEFAULT NULL,
  `note` varchar(255) DEFAULT '',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gift_cards`
--

INSERT INTO `gift_cards` (`id`, `code`, `balance`, `used`, `used_by`, `used_at`, `expired_at`, `note`, `created_at`) VALUES
(1, 'GC-72XD-MHJF', 50000, 0, NULL, NULL, NULL, '', '2026-03-19 15:13:47');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `actor_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `title` varchar(255) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `actor_id`, `type`, `target_id`, `message`, `is_read`, `created_at`, `title`, `body`, `link`) VALUES
(1, 4, NULL, '', NULL, '', 1, '2026-03-25 16:41:04', 'Đặt vé thành công', 'Phim Batman — 28/03/2026 19:00 | 1 ghế', 've_cua_toi.php'),
(2, 4, NULL, 'ticket_booked', NULL, '', 1, '2026-03-25 16:41:04', 'Có người đặt vé mới', 'User ID: 4 vừa đặt vé phim Batman (1 ghế)', '../admin/movies.php'),
(3, 4, NULL, 'new_chat', 15, '', 1, '2026-03-25 16:41:51', 'Tin nhắn mới từ van', 'Thành viên này vừa nhắn tin cho bạn ở khung chat hỗ trợ.', '../admin/quan_ly_chat.php?user_id=15'),
(4, 4, NULL, 'new_chat', 15, '', 1, '2026-03-25 16:48:34', 'Tin nhắn mới từ van', 'Thành viên này vừa nhắn tin cho bạn ở khung chat hỗ trợ.', '../admin/quan_ly_chat.php?user_id=15'),
(5, 5, 4, 'new_post', 9, '', 0, '2026-03-25 17:10:19', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_9'),
(6, 6, 4, 'new_post', 9, '', 0, '2026-03-25 17:10:19', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_9'),
(7, 7, 4, 'new_post', 9, '', 0, '2026-03-25 17:10:19', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_9'),
(8, 8, 4, 'new_post', 9, '', 0, '2026-03-25 17:10:19', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_9'),
(9, 9, 4, 'new_post', 9, '', 0, '2026-03-25 17:10:19', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_9'),
(10, 10, 4, 'new_post', 9, '', 0, '2026-03-25 17:10:19', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_9'),
(11, 11, 4, 'new_post', 9, '', 0, '2026-03-25 17:10:19', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_9'),
(12, 12, 4, 'new_post', 9, '', 0, '2026-03-25 17:10:19', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_9'),
(13, 13, 4, 'new_post', 9, '', 0, '2026-03-25 17:10:19', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_9'),
(15, 15, 4, 'new_post', 9, '', 1, '2026-03-25 17:10:19', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_9'),
(16, 4, 15, 'new_reaction', 9, '', 1, '2026-03-25 17:20:57', 'Cảm xúc mới', 'van vừa bày tỏ 👍 Thích về bài viết của bạn.', 'social.php#post_9'),
(17, 4, NULL, '', NULL, '', 1, '2026-03-25 17:58:15', 'Đặt vé thành công', 'Phim F1® — 28/03/2026 15:22 | 1 ghế', 've_cua_toi.php'),
(18, 4, NULL, 'ticket_booked', NULL, '', 1, '2026-03-25 17:58:15', 'Có người đặt vé mới', 'User ID: 4 vừa đặt vé phim F1® (1 ghế)', '../admin/movies.php'),
(19, 4, NULL, '', NULL, '', 1, '2026-03-25 17:58:32', 'Đặt vé thành công', 'Phim F1® — 28/03/2026 15:22 | 1 ghế', 've_cua_toi.php'),
(20, 4, NULL, 'ticket_booked', NULL, '', 1, '2026-03-25 17:58:32', 'Có người đặt vé mới', 'User ID: 4 vừa đặt vé phim F1® (1 ghế)', '../admin/movies.php'),
(21, 4, 15, 'new_reaction', 9, '', 1, '2026-03-25 18:13:45', 'Cảm xúc mới', 'van vừa bày tỏ 😂 Haha về bài viết của bạn.', 'social.php#post_9'),
(22, 4, 15, 'new_post', 10, '', 1, '2026-03-25 18:13:50', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_10'),
(23, 5, 15, 'new_post', 10, '', 0, '2026-03-25 18:13:50', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_10'),
(24, 6, 15, 'new_post', 10, '', 0, '2026-03-25 18:13:50', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_10'),
(25, 7, 15, 'new_post', 10, '', 0, '2026-03-25 18:13:50', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_10'),
(26, 8, 15, 'new_post', 10, '', 0, '2026-03-25 18:13:50', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_10'),
(27, 9, 15, 'new_post', 10, '', 0, '2026-03-25 18:13:50', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_10'),
(28, 10, 15, 'new_post', 10, '', 0, '2026-03-25 18:13:50', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_10'),
(29, 11, 15, 'new_post', 10, '', 0, '2026-03-25 18:13:50', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_10'),
(30, 12, 15, 'new_post', 10, '', 0, '2026-03-25 18:13:50', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_10'),
(31, 13, 15, 'new_post', 10, '', 0, '2026-03-25 18:13:50', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_10'),
(33, 4, 15, 'new_post', 11, '', 1, '2026-03-25 18:14:18', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_11'),
(34, 5, 15, 'new_post', 11, '', 0, '2026-03-25 18:14:18', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_11'),
(35, 6, 15, 'new_post', 11, '', 0, '2026-03-25 18:14:18', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_11'),
(36, 7, 15, 'new_post', 11, '', 0, '2026-03-25 18:14:18', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_11'),
(37, 8, 15, 'new_post', 11, '', 0, '2026-03-25 18:14:18', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_11'),
(38, 9, 15, 'new_post', 11, '', 0, '2026-03-25 18:14:18', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_11'),
(39, 10, 15, 'new_post', 11, '', 0, '2026-03-25 18:14:18', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_11'),
(40, 11, 15, 'new_post', 11, '', 0, '2026-03-25 18:14:18', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_11'),
(41, 12, 15, 'new_post', 11, '', 0, '2026-03-25 18:14:18', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_11'),
(42, 13, 15, 'new_post', 11, '', 0, '2026-03-25 18:14:18', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_11'),
(44, 4, 16, 'new_post', 12, '', 0, '2026-03-28 13:45:12', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_12'),
(45, 5, 16, 'new_post', 12, '', 0, '2026-03-28 13:45:12', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_12'),
(46, 6, 16, 'new_post', 12, '', 0, '2026-03-28 13:45:12', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_12'),
(47, 7, 16, 'new_post', 12, '', 0, '2026-03-28 13:45:12', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_12'),
(48, 8, 16, 'new_post', 12, '', 0, '2026-03-28 13:45:12', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_12'),
(49, 9, 16, 'new_post', 12, '', 0, '2026-03-28 13:45:12', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_12'),
(50, 10, 16, 'new_post', 12, '', 0, '2026-03-28 13:45:12', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_12'),
(51, 11, 16, 'new_post', 12, '', 0, '2026-03-28 13:45:12', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_12'),
(52, 12, 16, 'new_post', 12, '', 0, '2026-03-28 13:45:12', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_12'),
(53, 13, 16, 'new_post', 12, '', 0, '2026-03-28 13:45:12', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_12'),
(54, 15, 16, 'new_post', 12, '', 0, '2026-03-28 13:45:12', 'Bài viết mới', ' vừa đăng một bài viết mới.', 'social.php#post_12'),
(55, 4, NULL, '', NULL, '', 0, '2026-03-31 04:35:38', 'Đặt vé thành công', 'Phim Avengers: Endgame — 26/03/2026 19:00 | 2 ghế', 've_cua_toi.php'),
(56, 4, NULL, 'ticket_booked', NULL, '', 0, '2026-03-31 04:35:38', 'Có người đặt vé mới', 'User ID: 4 vừa đặt vé phim Avengers: Endgame (2 ghế)', '../admin/movies.php'),
(57, 4, NULL, '', NULL, '', 0, '2026-03-31 04:40:15', 'Đặt vé thành công', 'Phim Avengers: Endgame — 26/03/2026 19:00 | 2 ghế', 've_cua_toi.php'),
(58, 4, NULL, 'ticket_booked', NULL, '', 0, '2026-03-31 04:40:15', 'Có người đặt vé mới', 'User ID: 4 vừa đặt vé phim Avengers: Endgame (2 ghế)', '../admin/movies.php'),
(59, 4, NULL, '', NULL, '', 0, '2026-03-31 04:48:29', 'Đặt vé thành công', 'Phim Batman — 26/03/2026 19:00 | 1 ghế', 've_cua_toi.php'),
(60, 4, NULL, 'ticket_booked', NULL, '', 0, '2026-03-31 04:48:29', 'Có người đặt vé mới', 'User ID: 4 vừa đặt vé phim Batman (1 ghế)', '../admin/movies.php'),
(61, 4, NULL, '', NULL, '', 0, '2026-03-31 04:53:57', 'Đặt vé thành công', 'Phim Batman — 26/03/2026 19:00 | 1 ghế', 've_cua_toi.php'),
(62, 4, NULL, 'ticket_booked', NULL, '', 0, '2026-03-31 04:53:57', 'Có người đặt vé mới', 'User ID: 4 vừa đặt vé phim Batman (1 ghế)', '../admin/movies.php');

-- --------------------------------------------------------

--
-- Table structure for table `phim`
--

CREATE TABLE `phim` (
  `id` int(11) NOT NULL,
  `ten_phim` varchar(200) DEFAULT NULL,
  `the_loai` varchar(100) DEFAULT NULL,
  `thoi_luong` int(11) DEFAULT NULL,
  `mo_ta` text DEFAULT NULL,
  `poster` varchar(255) DEFAULT NULL,
  `banner` varchar(255) DEFAULT NULL,
  `ngay_khoi_chieu` date DEFAULT NULL,
  `trailer_url` varchar(255) DEFAULT NULL,
  `do_tuoi` varchar(10) DEFAULT 'P'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `phim`
--

INSERT INTO `phim` (`id`, `ten_phim`, `the_loai`, `thoi_luong`, `mo_ta`, `poster`, `banner`, `ngay_khoi_chieu`, `trailer_url`, `do_tuoi`) VALUES
(1, 'Avengers: Endgame', ' Hành Động, Khoa Học Viễn Tưởng', 182, 'Sau sự kiện Thanos hủy diệt một nửa vũ trụ, các Avengers còn sống sót phải tập hợp lại để đảo ngược thảm họa và mang lại hy vọng cho nhân loại.', 'avengers.jpg', NULL, NULL, 'https://www.youtube.com/watch?v=aWzlQ2N6qqg', 'P'),
(2, 'Spider-Man: No Way Home', 'Hành động, Phiêu lưu', 157, 'Lần đầu tiên trong lịch sử điện ảnh của Người Nhện, danh tính của người hùng thân thiện của chúng ta bị tiết lộ, khiến trách nhiệm siêu anh hùng của anh mâu thuẫn với cuộc sống thường nhật và đặt những người anh yêu thương nhất vào nguy hiểm. Khi anh nhờ Doctor Strange giúp đỡ để khôi phục bí mật, phép thuật đã xé toạc một lỗ hổng trong thế giới của họ, giải phóng những kẻ phản diện mạnh nhất từng chiến đấu với Người Nhện trong bất kỳ vũ trụ nào. Giờ đây, Peter sẽ phải vượt qua thử thách lớn nhất từ ​​trước đến nay, điều không chỉ thay đổi tương lai của chính anh mà còn cả tương lai của Đa vũ trụ.', 'ev-content-thumb293.png', NULL, NULL, 'https://www.youtube.com/watch?v=aWzlQ2N6qqg', 'P'),
(3, 'Batman', 'Hành Động, Tội phạm', 183, 'Bộ phim đưa khán giả dõi theo hành trình phá án và diệt trừ tội phạm của chàng Hiệp sĩ Bóng đêm Batman, với một câu chuyện hoàn toàn khác biệt với những phần phim đã ra mắt trước đây. Thế giới ngầm ở thành phố Gotham xuất hiện một tên tội phạm kỳ lạ tên Riddler chuyên nhắm vào nhân vật tai to mặt lớn. Và sau mỗi lần phạm tội, hắn đều để lại một câu đố bí ẩn cho Batman. Khi bắt tay vào phá giải các câu đố này, Batman dần lật mở những bí ẩn động trời giữa gia đình anh và tên trùm tội phạm Carmine Falcon\r\n', 'batman.jpg', NULL, NULL, 'https://www.youtube.com/watch?v=aWzlQ2N6qqg', 'P'),
(4, 'PHIM ĐIỆN ẢNH THÁM TỬ LỪNG DANH CONAN: QUẢ BOM CHỌC TRỜI', 'Bí ẩn, Hành Động, Hoạt Hình', 95, 'Phim Điện Ảnh Thám Tử Lừng Danh Conan: Quả Bom Chọc Trời là bộ phim điện ảnh đầu tiên của chuỗi phim điện ảnh Thám Tử Lừng Danh Conan. Phim được chuyển thể dựa trên nguyên tác của Gosho Aoyama, do Kenji Kodama đạo diễn. Kudo Shinichi được kiến trúc sư nổi tiếng Moriya Teiji mời đến buổi tiệc trà tại dinh thự tư nhân. Tuy nhiên, do đã bị thu nhỏ, Shinichi vốn không thể tham gia buổi tiệc này. Thay vào đó, thám tử Mori, Ran và Conan đã cùng nhau đến dự thay cho Shinichi. Cùng lúc đó, Shinichi bất ngờ bị một kẻ ẩn danh thách thức qua điện thoại: cậu phải tìm ra những quả bom được đặt rải rác khắp Tokyo trước khi chúng phát nổ. Liệu cậu ấy có thể tìm thấy và vô hiệu hóa tất cả số bom trước khi quá muộn?', 'poster_conan_qua_bom_choc_troi_6.jpg', NULL, NULL, 'https://www.youtube.com/watch?v=aWzlQ2N6qqg', 'P'),
(5, 'F1®', 'Hành Động, Tâm Lý', 156, 'Sonny Hayes (Brad Pitt) được mệnh danh là \"Huyền thoại chưa từng được gọi tên\" là ngôi sao sáng giá nhất của FORMULA 1 trong những năm 1990 cho đến khi một vụ tai nạn trên đường đua suýt nữa đã kết thúc sự nghiệp của anh.. Ba mươi năm sau, Sonny trở thành một tay đua tự do, cho đến khi người đồng đội cũ của anh, Ruben Cervantes (Javier Bardem), chủ sở hữu một đội đua F1 đang trên bờ vực sụp đổ, tìm đến anh. Ruben thuyết phục Sonny quay lại với F1® để có một cơ hội cuối cùng cứu lấy đội và khẳng định mình là tay đua xuất sắc nhất thế giới. Anh sẽ thi đấu cùng Joshua Pearce (Damson Idris), tay đua tân binh đầy tham vọng của đội, người luôn muốn tạo ra tốc độ của riêng mình. Tuy nhiên, khi động cơ gầm rú, quá khứ của Sonny sẽ đuổi theo anh và anh nhận ra rằng trong F1, người đồng đội chính là đối thủ cạnh tranh lớn nhất—và con đường chuộc lại lỗi lầm không phải là điều có thể đi một mình. (CHIẾU LẠI từ 06/02/2026)', 'poster_f1_rerun_1_.jpg', NULL, NULL, 'https://www.youtube.com/watch?v=aWzlQ2N6qqg', 'P'),
(6, 'MƯA ĐỎ', 'Hành Động, Lịch Sử', 124, '“Mưa Đỏ” - Phim truyện điện ảnh về đề tài chiến tranh cách mạng, kịch bản của nhà văn Chu Lai, lấy cảm hứng và hư cấu từ sự kiện 81 ngày đêm chiến đấu anh dũng, kiên cường của nhân dân và cán bộ, chiến sĩ bảo vệ Thành Cổ Quảng Trị năm 1972. Tiểu đội 1 gồm toàn những thanh niên trẻ tuổi và đầy nhiệt huyết là một trong những đơn vị chiến đấu, bám trụ tại trận địa khốc liệt này. Bộ phim là khúc tráng ca bằng hình ảnh, là nén tâm nhang tri ân và tưởng nhớ những người con đã dâng hiến tuổi thanh xuân cho đất nước, mang âm hưởng của tình yêu, tình đồng đội thiêng liêng, là khát vọng hòa bình, hoà hợp dân tộc của nhân dân Việt Nam.', '350x495-muado_1.jpg', '1773151176_banner_968ed3d13f7f.jpg', NULL, 'https://www.youtube.com/watch?v=aWzlQ2N6qqg', 'P'),
(7, 'PANOR: TÀ THUẬT HUYẾT NGẢI 2', 'Hồi hộp, Kinh Dị', 125, 'Sau khi từ bỏ quyền năng của Thần Ba Mắt, cuộc đời Panor được thiết lập lại, nhưng ký ức đã bị xóa sạch. Cô bắt đầu cuộc sống mới như mơ, được học tại trường sư phạm với Piak luôn lặng lẽ theo dõi và giúp đỡ từ xa. Nhưng bình yên ngắn chẳng tày gang, khi có một người bí ẩn luôn nhăm nhe tìm cách đánh thức sức mạnh của Thần Ba Mắt bên trong Panor.', 'poster_panor_ta_thuat_huyen_ngai__1.jpg', '1773151005_banner_a973c0650015.jpg', NULL, 'https://www.youtube.com/watch?v=aWzlQ2N6qqg', 'P'),
(8, 'TÀI', 'Gia đình, Hành Động, Tâm Lý', 101, 'Tài bất ngờ rơi vào vòng xoáy nguy hiểm vì một khoản nợ tiền khổng lồ. Bị dồn vào đường cùng, Tài buộc phải dấn thân vào những lựa chọn sai lầm khiến gia đình trở thành mục tiêu bị đe dọa. Đằng sau những hành động liều lĩnh ấy là nỗi ám ảnh về người mẹ mà Tài luôn muốn bảo vệ và bù đắp bằng mọi giá. Khi ranh giới giữa đúng và sai ngày càng mong manh, Tài phải đối mặt với câu hỏi lớn nhất của đời mình: liệu lòng hiếu thảo có đủ để biện minh cho con đường anh đang đi.', '1773152573_09197e2339a5.jpg', '1773152573_banner_ae73638c5828.jpg', '2026-05-01', 'https://www.youtube.com/watch?v=aWzlQ2N6qqg', 'P'),
(9, 'QUỶ NHẬP TRÀNG 2', 'Hồi hộp, Kinh Dị', 126, 'Quỷ Nhập Tràng 2 là tiền truyện của nhân vật Minh Như, trở về xưởng nhuộm gia đình sau nhiều năm bị xua đuổi. Tại đây, cô phải đối mặt với những hiện tượng ma quái cùng sự thật tàn khốc về cái chết của mẹ và giao ước đẫm máu năm xưa. Ác giả ác báo, liệu Minh Như có thoát khỏi vòng vây của quỷ dữ?', '1774258451_e059608e74f7.jpg', '1774258451_banner_4abb716b57c5.jpg', NULL, 'https://www.youtube.com/watch?v=aWzlQ2N6qqg', 'P'),
(10, 'CÚ NHẢY KỲ DIỆU', 'Gia đình, Hài, Hoạt Hình, Phiêu Lưu', 105, 'Hoppers xoay quanh Mabel, một cô gái yêu động vật, vô tình tiếp cận công nghệ cho phép chuyển ý thức con người vào cơ thể robot động vật. Nhờ đó, Mabel “nhảy” vào thế giới tự nhiên dưới hình dạng một con hải ly và có thể giao tiếp trực tiếp với các loài khác. Trong hành trình này, cô dần khám phá cách động vật nhìn nhận con người, đồng thời phát hiện những mối nguy đang đe dọa môi trường sống của chúng. Tận dụng công nghệ Nhảy, Mabel đã trở thành cầu nối, mang lại cuộc sống cân bằng cho cả con người và động vật.', '1774258574_a709da36402c.jpg', '1774258574_banner_be3f303f9ea0.jpg', NULL, 'https://www.youtube.com/watch?v=aWzlQ2N6qqg', 'P'),
(11, 'TIẾNG THÉT 7', 'Bí ẩn, Kinh Dị', 113, 'Sidney Evans (Neve Campbell), nạn nhân sống sót của một vụ thảm sát nhiều năm trước, giờ đang sống hạnh phúc cùng chồng và con gái ở một thị trấn khác thì tên sát nhân Ghostface mới lại xuất hiện. Những nỗi sợ hãi đen tối nhất của cô trở thành hiện thực khi con gái cô Tatum Evans (Isabel May) trở thành mục tiêu tiếp theo. Quyết tâm bảo vệ gia đình, Sidney buộc phải đối mặt với những kinh hoàng trong quá khứ để chấm dứt cuộc đổ máu một lần và mãi mãi.', '1774258754_6f9e61c52309.jpg', '1774258754_banner_01e9cec7de67.jpg', NULL, 'https://www.youtube.com/watch?v=aWzlQ2N6qqg', 'P'),
(12, 'THOÁT KHỎI TẬN THẾ', 'Khoa Học Viễn Tưởng, Phiêu Lưu', 157, 'Ryland Grace một giáo viên khoa học nhận ra anh chính là hy vọng cuối cùng của Trái Đất. Nhiệm vụ của anh: cứu lấy Mặt Trời khỏi một sinh thể bí ẩn đang hút cạn năng lượng ánh sáng, đẩy cả hệ Mặt Trời vào bóng tối vĩnh viễn. Nếu thất bại, sự sống trên Trái Đất sẽ lụi tàn theo ánh sáng cuối cùng của mặt trời. Giữa không gian vũ trụ cô độc và áp lực của thời gian đang cạn dần, mọi phép tính, mọi quyết định của anh đều gánh trên vai số phận của toàn nhân loại. Nhưng trong hành trình tưởng chừng chỉ có một mình giữa khoảng không vô tận ấy, một tình bạn bất ngờ với một sinh vật ngoài hành tinh đã xuất hiện. Và có lẽ, để cứu Trái Đất, anh sẽ không phải chiến đấu một mình.', '1774258937_b582777fe7c4.jpg', NULL, NULL, 'https://www.youtube.com/watch?v=aWzlQ2N6qqg', 'P'),
(13, 'THỎ ƠI!!', 'Tâm Lý', 127, 'Phim “Thỏ ơi!!” dự kiến công chiếu trong dịp Tết 2026, thuộc thể loại hài, tâm lý sở trường của Trấn Thành, mang màu sắc trẻ trung với dàn diễn viên mới, tiếp nối tinh thần đem đến cho khán giả những điều vui vẻ, hài hước vào dịp Tết Nguyên đán.', '1774259041_dda004f08890.jpg', '1774259041_banner_a9cbfa28f28e.jpg', NULL, 'https://www.youtube.com/watch?v=aWzlQ2N6qqg', 'P'),
(14, 'TỘI PHẠM 101', 'Hồi hộp, Tội phạm', 140, 'Lấy bối cảnh thành phố Los Angeles đầy nắng và bụi đường, Tội Phạm 101 kể về một tên trộm nữ trang bí ẩn (Chris Hemsworth) với hàng loạt phi vụ táo bạo khiến cảnh sát phải đau đầu. Trong lúc chuẩn bị cho phi vụ lớn nhất của mình, hắn gặp gỡ một nữ nhân viên bảo hiểm (Halle Berry), người cũng đang vật lộn với những lựa chọn trong đời mình. Trong khi đó, một thanh tra (Mark Ruffalo) đã tìm ra quy luật trong chuỗi các vụ án và đang ráo riết truy đuổi tên trộm, khiến cuộc chơi trở nên căng thẳng hơn bao giờ hết. Khi phi vụ định mệnh đến gần, ranh giới giữa kẻ săn đuổi và con mồi dần trở nên mờ nhạt và cả ba buộc phải đối mặt với những lựa chọn khó khăn và không còn cơ hội để quay đầu lại. Bộ phim được chuyển thể từ tiểu thuyết ngắn nổi tiếng cùng tên của Don Winslow, do Bart Layton (tác giả của American Animals, The Imposter) viết kịch bản và đạo diễn. Dàn diễn viên có sự tham gia của Barry Keoghan, Monica Barbaro, Corey Hawkins, Jennifer Jason Leigh và Nick Nolte.', '1774259157_ab627cc17e74.jpg', '1774259157_banner_c2fabf4d8164.jpg', NULL, 'https://www.youtube.com/watch?v=aWzlQ2N6qqg', 'P'),
(15, 'NHÀ BA TÔI MỘT PHÒNG', 'Gia đình, Hài, Tâm Lý', 126, 'Lấy bối cảnh một khu chung cư cũ với căn nhà chỉ vỏn vẹn một phòng, Nhà Ba Tôi Một Phòng khắc họa mối quan hệ “lệch pha” giữa người cha bảo thủ và cô con gái Gen Z đầy mơ ước. Khi những khác biệt thế hệ va chạm trong không gian chật chội, tình thân dần được thử thách và hàn gắn. Bộ phim mang đến một lát cắt gần gũi về gia đình Việt hiện đại, nơi yêu thương đôi khi bắt đầu từ sự thấu hiểu.', '1774259274_6c6fdff095e5.jpg', '1774259274_banner_f606a5050cc4.jpg', NULL, 'https://www.youtube.com/watch?v=aWzlQ2N6qqg', 'T18'),
(16, 'OMUKADE: CON RẾT NGƯỜI', 'Kinh Dị', 93, 'Bắt nguồn từ lời nguyền nổi tiếng về quái vật ăn thịt người có thật, OMUKADE: CON RẾT NGƯỜI gây chấn động phòng vé Thái Lan vì lần đầu xuất hiện trên màn ảnh rộng đã gây sang chấn với những phân đoạn rùng rợn, tái hiện loạt tai ương từng bị chôn vùi có liên quan đến các xác chết bí ẩn.', '1774259341_9cacd4bc9e45.jpg', NULL, NULL, 'https://www.youtube.com/watch?v=aWzlQ2N6qqg', 'P'),
(17, 'mesi', 'thể thao', 10, 'goat', '1774462245_422df438ef10.jpg', '1774462245_banner_c6bc803bb314.png', NULL, 'https://www.youtube.com/watch?v=uNMgSjEGUpY', 'T18');

-- --------------------------------------------------------

--
-- Table structure for table `phong_chieu`
--

CREATE TABLE `phong_chieu` (
  `id` int(11) NOT NULL,
  `rap_id` int(11) DEFAULT NULL,
  `ten_phong` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `phong_chieu`
--

INSERT INTO `phong_chieu` (`id`, `rap_id`, `ten_phong`) VALUES
(1, 1, 'Phòng 1 - Vincom'),
(2, 1, 'Phòng 2 - Vincom'),
(3, 2, 'Phòng 1 - Lotte'),
(4, 2, 'Phòng 2 - Lotte');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `phim_id` int(11) DEFAULT NULL,
  `noi_dung` text NOT NULL,
  `hinh_anh` varchar(255) DEFAULT NULL,
  `engagement_score` decimal(8,2) DEFAULT 0.00,
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `user_id`, `phim_id`, `noi_dung`, `hinh_anh`, `engagement_score`, `view_count`, `created_at`, `updated_at`) VALUES
(1, 4, 8, 'Bộ phim TÀI - TÌNH nhất mùa xuân 2026 ❤️\r\n🎬 TÀI | ĐANG CHIẾU TẠI RẠP', 'post_69b6c46ba570e.jpg', 8.40, 0, '2026-03-15 14:38:35', '2026-03-31 05:23:36'),
(2, 4, NULL, 'phim hay', NULL, 0.00, 0, '2026-03-25 16:48:58', '2026-03-25 16:48:58'),
(3, 4, NULL, 'phim hay', NULL, 0.00, 0, '2026-03-25 16:49:35', '2026-03-25 16:49:35'),
(4, 4, NULL, 'phim hayff', NULL, 0.00, 0, '2026-03-25 16:49:45', '2026-03-25 16:49:45'),
(5, 4, NULL, 'phim hayff', NULL, 0.20, 0, '2026-03-25 16:51:16', '2026-03-25 17:10:18'),
(6, 4, NULL, 'ok phim hay', NULL, 3.70, 0, '2026-03-25 16:51:41', '2026-03-28 13:45:15'),
(7, 15, NULL, 'j', NULL, 0.70, 0, '2026-03-25 16:52:09', '2026-03-31 05:23:37'),
(8, 4, NULL, 'ê', NULL, 0.10, 0, '2026-03-25 17:09:01', '2026-03-25 18:13:48'),
(9, 4, NULL, 'd', NULL, 4.10, 0, '2026-03-25 17:10:19', '2026-03-25 18:14:20'),
(11, 15, NULL, 'them bl', NULL, 0.30, 0, '2026-03-25 18:14:18', '2026-03-28 13:45:15');

-- --------------------------------------------------------

--
-- Table structure for table `post_impressions`
--

CREATE TABLE `post_impressions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `dwell_ms` int(11) DEFAULT 0,
  `action` enum('view','like','reply','share','hide','report') DEFAULT 'view',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `post_impressions`
--

INSERT INTO `post_impressions` (`id`, `user_id`, `post_id`, `dwell_ms`, `action`, `created_at`) VALUES
(1, 15, 1, 2055, 'view', '2026-03-25 15:22:39'),
(2, 15, 1, 4601, 'view', '2026-03-25 15:25:20'),
(3, 15, 1, 1310, 'view', '2026-03-25 16:11:20'),
(4, 15, 1, 926, 'view', '2026-03-25 16:25:24'),
(5, 15, 1, 4254, 'view', '2026-03-25 16:48:31'),
(6, 4, 1, 6014, 'view', '2026-03-25 16:48:53'),
(7, 4, 7, 2190, 'view', '2026-03-25 17:05:38'),
(8, 4, 7, 3051, 'view', '2026-03-25 17:08:57'),
(9, 4, 5, 4126, 'view', '2026-03-25 17:08:58'),
(10, 4, 5, 77113, 'view', '2026-03-25 17:10:18'),
(11, 15, 6, 300000, 'view', '2026-03-25 18:12:59'),
(12, 15, 7, 1341, 'view', '2026-03-25 18:13:18'),
(13, 15, 8, 31670, 'view', '2026-03-25 18:13:48'),
(14, 4, 7, 16602, 'view', '2026-03-25 18:13:51'),
(15, 4, 10, 867, 'view', '2026-03-25 18:14:09'),
(16, 15, 6, 24200, 'view', '2026-03-25 18:14:15'),
(17, 4, 9, 12053, 'view', '2026-03-25 18:14:20'),
(18, 4, 6, 2522, 'view', '2026-03-25 18:14:29'),
(19, 16, 6, 49605, 'view', '2026-03-28 13:32:48'),
(20, 4, 1, 1636, 'view', '2026-03-28 13:40:36'),
(21, 4, 1, 870, 'view', '2026-03-28 13:40:40'),
(22, 16, 1, 1204, 'view', '2026-03-28 13:44:11'),
(23, 16, 11, 11687, 'view', '2026-03-28 13:44:22'),
(24, 16, 6, 11858, 'view', '2026-03-28 13:44:22'),
(25, 16, 1, 1723, 'view', '2026-03-28 13:44:24'),
(26, 16, 11, 1283, 'view', '2026-03-28 13:44:25'),
(27, 16, 7, 1134, 'view', '2026-03-28 13:44:25'),
(28, 16, 6, 1379, 'view', '2026-03-28 13:44:25'),
(29, 4, 1, 1291, 'view', '2026-03-28 13:45:02'),
(30, 16, 1, 40703, 'view', '2026-03-28 13:45:06'),
(31, 4, 7, 12679, 'view', '2026-03-28 13:45:15'),
(32, 4, 6, 13311, 'view', '2026-03-28 13:45:15'),
(33, 4, 11, 12800, 'view', '2026-03-28 13:45:15'),
(34, 16, 12, 6429, 'view', '2026-03-28 13:45:19'),
(35, 16, 1, 728, 'view', '2026-03-28 13:45:19'),
(36, 4, 1, 1620, 'view', '2026-03-31 05:23:36'),
(37, 4, 7, 1133, 'view', '2026-03-31 05:23:37');

-- --------------------------------------------------------

--
-- Table structure for table `rap`
--

CREATE TABLE `rap` (
  `id` int(11) NOT NULL,
  `ten_rap` varchar(200) DEFAULT NULL,
  `dia_chi` varchar(255) DEFAULT NULL,
  `thanh_pho` varchar(100) DEFAULT NULL,
  `so_dien_thoai` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rap`
--

INSERT INTO `rap` (`id`, `ten_rap`, `dia_chi`, `thanh_pho`, `so_dien_thoai`) VALUES
(1, 'CGV Vincom', 'Vincom Plaza', 'TPHCM', NULL),
(2, 'Lotte Cinema', 'Lotte Mart Q7', 'TPHCM', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `phim_id` int(11) NOT NULL,
  `rating` decimal(2,1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`id`, `user_id`, `phim_id`, `rating`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 5.0, '2026-03-25 15:30:45', '2026-03-25 16:02:19');

-- --------------------------------------------------------

--
-- Table structure for table `reactions`
--

CREATE TABLE `reactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `target_type` enum('post','phim') NOT NULL,
  `target_id` int(11) NOT NULL,
  `loai` enum('like','love','haha','wow','sad','angry') NOT NULL DEFAULT 'like',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reactions`
--

INSERT INTO `reactions` (`id`, `user_id`, `target_type`, `target_id`, `loai`, `created_at`) VALUES
(1, 4, '', 5, 'love', '2026-03-15 14:36:09'),
(2, 5, '', 1, 'love', '2026-03-15 14:36:30'),
(3, 4, '', 1, 'love', '2026-03-15 14:36:56'),
(6, 13, '', 1, 'like', '2026-03-15 14:52:19'),
(17, 13, 'phim', 1, 'like', '2026-03-15 15:00:54'),
(25, 5, 'phim', 1, 'like', '2026-03-15 15:27:37'),
(27, 4, 'phim', 1, 'like', '2026-03-15 16:14:40'),
(28, 4, 'phim', 5, 'like', '2026-03-15 16:43:01'),
(51, 4, 'phim', 7, 'like', '2026-03-15 17:56:23'),
(54, 15, 'post', 1, 'like', '2026-03-24 06:08:50'),
(55, 4, 'phim', 15, 'like', '2026-03-24 06:10:25'),
(57, 15, 'phim', 1, 'like', '2026-03-25 15:31:11'),
(59, 4, 'post', 9, 'like', '2026-03-25 17:24:35'),
(60, 4, 'post', 6, 'like', '2026-03-25 18:13:37'),
(61, 15, 'post', 9, 'haha', '2026-03-25 18:13:45');

-- --------------------------------------------------------

--
-- Table structure for table `suat_chieu`
--

CREATE TABLE `suat_chieu` (
  `id` int(11) NOT NULL,
  `phim_id` int(11) DEFAULT NULL,
  `phong_id` int(11) DEFAULT NULL,
  `ngay` date DEFAULT NULL,
  `gio` time DEFAULT NULL,
  `gia` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suat_chieu`
--

INSERT INTO `suat_chieu` (`id`, `phim_id`, `phong_id`, `ngay`, `gio`, `gia`) VALUES
(14, 8, 1, '2026-05-01', '00:30:00', 75000),
(17, 5, 3, '2026-03-28', '15:22:00', 75000),
(18, 1, 2, '2026-03-26', '19:00:00', 75000),
(19, 1, 2, '2026-03-27', '19:00:00', 75000),
(20, 1, 2, '2026-03-28', '19:00:00', 75000),
(21, 1, 2, '2026-03-29', '19:00:00', 75000),
(22, 1, 2, '2026-03-30', '19:00:00', 75000),
(23, 1, 2, '2026-03-31', '19:00:00', 75000),
(24, 1, 2, '2026-04-01', '19:00:00', 75000),
(25, 2, 3, '2026-03-26', '19:00:00', 75000),
(26, 2, 3, '2026-03-27', '19:00:00', 75000),
(27, 2, 3, '2026-03-28', '19:00:00', 75000),
(28, 2, 3, '2026-03-29', '19:00:00', 75000),
(29, 2, 3, '2026-03-30', '19:00:00', 75000),
(30, 2, 3, '2026-03-31', '19:00:00', 75000),
(31, 2, 3, '2026-04-01', '19:00:00', 75000),
(32, 3, 4, '2026-03-26', '19:00:00', 75000),
(33, 3, 4, '2026-03-27', '19:00:00', 75000),
(34, 3, 4, '2026-03-28', '19:00:00', 75000),
(35, 3, 4, '2026-03-29', '19:00:00', 75000),
(36, 3, 4, '2026-03-30', '19:00:00', 75000),
(37, 3, 4, '2026-03-31', '19:00:00', 75000),
(38, 3, 4, '2026-04-01', '19:00:00', 75000),
(39, 4, 1, '2026-03-26', '19:00:00', 75000),
(40, 4, 1, '2026-03-27', '19:00:00', 75000),
(41, 4, 1, '2026-03-28', '19:00:00', 75000),
(42, 4, 1, '2026-03-29', '19:00:00', 75000),
(43, 4, 1, '2026-03-30', '19:00:00', 75000),
(44, 4, 1, '2026-03-31', '19:00:00', 75000),
(45, 4, 1, '2026-04-01', '19:00:00', 75000),
(46, 5, 2, '2026-03-26', '19:00:00', 75000),
(47, 5, 2, '2026-03-27', '19:00:00', 75000),
(48, 5, 2, '2026-03-28', '19:00:00', 75000),
(49, 5, 2, '2026-03-29', '19:00:00', 75000),
(50, 5, 2, '2026-03-30', '19:00:00', 75000),
(51, 5, 2, '2026-03-31', '19:00:00', 75000),
(52, 5, 2, '2026-04-01', '19:00:00', 75000),
(53, 6, 3, '2026-03-26', '19:00:00', 75000),
(54, 6, 3, '2026-03-27', '19:00:00', 75000),
(55, 6, 3, '2026-03-28', '19:00:00', 75000),
(56, 6, 3, '2026-03-29', '19:00:00', 75000),
(57, 6, 3, '2026-03-30', '19:00:00', 75000),
(58, 6, 3, '2026-03-31', '19:00:00', 75000),
(59, 6, 3, '2026-04-01', '19:00:00', 75000),
(60, 7, 4, '2026-03-26', '19:00:00', 75000),
(61, 7, 4, '2026-03-27', '19:00:00', 75000),
(62, 7, 4, '2026-03-28', '19:00:00', 75000),
(63, 7, 4, '2026-03-29', '19:00:00', 75000),
(64, 7, 4, '2026-03-30', '19:00:00', 75000),
(65, 7, 4, '2026-03-31', '19:00:00', 75000),
(66, 7, 4, '2026-04-01', '19:00:00', 75000),
(67, 8, 1, '2026-03-26', '19:00:00', 75000),
(68, 8, 1, '2026-03-27', '19:00:00', 75000),
(69, 8, 1, '2026-03-28', '19:00:00', 75000),
(70, 8, 1, '2026-03-29', '19:00:00', 75000),
(71, 8, 1, '2026-03-30', '19:00:00', 75000),
(72, 8, 1, '2026-03-31', '19:00:00', 75000),
(73, 8, 1, '2026-04-01', '19:00:00', 75000),
(74, 9, 2, '2026-03-26', '19:00:00', 75000),
(75, 9, 2, '2026-03-27', '19:00:00', 75000),
(76, 9, 2, '2026-03-28', '19:00:00', 75000),
(77, 9, 2, '2026-03-29', '19:00:00', 75000),
(78, 9, 2, '2026-03-30', '19:00:00', 75000),
(79, 9, 2, '2026-03-31', '19:00:00', 75000),
(80, 9, 2, '2026-04-01', '19:00:00', 75000),
(81, 10, 3, '2026-03-26', '19:00:00', 75000),
(82, 10, 3, '2026-03-27', '19:00:00', 75000),
(83, 10, 3, '2026-03-28', '19:00:00', 75000),
(84, 10, 3, '2026-03-29', '19:00:00', 75000),
(85, 10, 3, '2026-03-30', '19:00:00', 75000),
(86, 10, 3, '2026-03-31', '19:00:00', 75000),
(87, 10, 3, '2026-04-01', '19:00:00', 75000),
(88, 11, 4, '2026-03-26', '19:00:00', 75000),
(89, 11, 4, '2026-03-27', '19:00:00', 75000),
(90, 11, 4, '2026-03-28', '19:00:00', 75000),
(91, 11, 4, '2026-03-29', '19:00:00', 75000),
(92, 11, 4, '2026-03-30', '19:00:00', 75000),
(93, 11, 4, '2026-03-31', '19:00:00', 75000),
(94, 11, 4, '2026-04-01', '19:00:00', 75000),
(95, 12, 1, '2026-03-26', '19:00:00', 75000),
(96, 12, 1, '2026-03-27', '19:00:00', 75000),
(97, 12, 1, '2026-03-28', '19:00:00', 75000),
(98, 12, 1, '2026-03-29', '19:00:00', 75000),
(99, 12, 1, '2026-03-30', '19:00:00', 75000),
(100, 12, 1, '2026-03-31', '19:00:00', 75000),
(101, 12, 1, '2026-04-01', '19:00:00', 75000),
(102, 13, 2, '2026-03-26', '19:00:00', 75000),
(103, 13, 2, '2026-03-27', '19:00:00', 75000),
(104, 13, 2, '2026-03-28', '19:00:00', 75000),
(105, 13, 2, '2026-03-29', '19:00:00', 75000),
(106, 13, 2, '2026-03-30', '19:00:00', 75000),
(107, 13, 2, '2026-03-31', '19:00:00', 75000),
(108, 13, 2, '2026-04-01', '19:00:00', 75000),
(109, 14, 3, '2026-03-26', '19:00:00', 75000),
(110, 14, 3, '2026-03-27', '19:00:00', 75000),
(111, 14, 3, '2026-03-28', '19:00:00', 75000),
(112, 14, 3, '2026-03-29', '19:00:00', 75000),
(113, 14, 3, '2026-03-30', '19:00:00', 75000),
(114, 14, 3, '2026-03-31', '19:00:00', 75000),
(115, 14, 3, '2026-04-01', '19:00:00', 75000),
(116, 15, 4, '2026-03-26', '19:00:00', 75000),
(117, 15, 4, '2026-03-27', '19:00:00', 75000),
(118, 15, 4, '2026-03-28', '19:00:00', 75000),
(119, 15, 4, '2026-03-29', '19:00:00', 75000),
(120, 15, 4, '2026-03-30', '19:00:00', 75000),
(121, 15, 4, '2026-03-31', '19:00:00', 75000),
(122, 15, 4, '2026-04-01', '19:00:00', 75000),
(123, 16, 1, '2026-03-26', '19:00:00', 75000),
(124, 16, 1, '2026-03-27', '19:00:00', 75000),
(125, 16, 1, '2026-03-28', '19:00:00', 75000),
(126, 16, 1, '2026-03-29', '19:00:00', 75000),
(127, 16, 1, '2026-03-30', '19:00:00', 75000),
(128, 16, 1, '2026-03-31', '19:00:00', 75000),
(129, 16, 1, '2026-04-01', '19:00:00', 75000);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `ten` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mat_khau` varchar(255) DEFAULT NULL,
  `vai_tro` enum('user','admin') DEFAULT 'user',
  `avatar` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `points` int(11) NOT NULL DEFAULT 0,
  `ho_ten` varchar(255) DEFAULT NULL,
  `is_banned` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `ten`, `email`, `mat_khau`, `vai_tro`, `avatar`, `bio`, `created_at`, `points`, `ho_ten`, `is_banned`) VALUES
(4, 'Admin', 'admin@gmail.com', '$2y$10$TN5EVTTz9pWxuvOE4irzLOjTx2HiHw80rUYAFG.ecpxzSynNBM72C', 'admin', NULL, NULL, '2026-03-15 14:12:46', 763, NULL, 0),
(5, 'User A', 'user@gmail.com', '$2y$10$0kD0d4oQkDFugcfnS4.8NOC/XByH7lO6gf9f.uTe7qH93t/qm.r/.', 'user', NULL, NULL, '2026-03-15 14:12:46', 0, NULL, 0),
(6, 'Tuấn', 'trian672008@gmail.com', '$2y$10$8vf.7uag8rxxKk/YzG9DbO3qWLVEzHG/1epbmXXIiniHCFe8.ZSj6', 'user', NULL, NULL, '2026-03-15 14:12:46', 0, NULL, 0),
(7, 'Test User 1', 'test1@example.com', '$2y$10$4TcCqah0HPn79oh6hjLDguKAkqnThHXW64uFS..idJ4JclMBmvWMS', 'user', NULL, NULL, '2026-03-15 14:12:46', 0, NULL, 1),
(8, 'Test User 2', 'test2@example.com', '$2y$10$tmPYlKWmGQt/Y9QlSyGR2eHMw0PJotJHV8CbLD/4z.puZ/mIBCz4e', 'user', NULL, NULL, '2026-03-15 14:12:46', 0, NULL, 0),
(9, 'Admin Fake', 'admin_fake@example.com', '$2y$10$zgjl/ObIHyXDY3nc86XLtuyqbcUZToGvQG7rWqDE8Lq8Lye6PE.zq', 'user', NULL, NULL, '2026-03-15 14:12:46', 0, NULL, 1),
(10, 'van', 'vanngu@gmail.com', '$2y$10$3ivmcYkverBEVR93ZRqse.hgxGlfGLOWDx9U1Jm1A2POM/okYm2..', 'user', NULL, NULL, '2026-03-15 14:12:46', 0, NULL, 0),
(11, 'van', 'vanbingu@gmail.com', '$2y$10$v5VQ0gaID3zRA46FDlAH2OfyR89gctibb9Y5uJg3S/kVpHyprzypq', 'user', NULL, NULL, '2026-03-15 14:12:46', 0, NULL, 0),
(12, 'Tuấn', 'tuan0suy@gmail.com', '$2y$10$DczP0NUMpHRL1gch6qz2Me6wXWFQhBYiMLHIzQVCBqUSjjEoKVS9i', 'user', NULL, NULL, '2026-03-15 14:12:46', 0, NULL, 0),
(13, 'van', 'vanngu123@gmail.com', '$2y$10$Pt64.8.E9i91CFuo70LG1OAfEFMZRzXVxUj92msfDC5QctFeqSF2y', 'user', NULL, NULL, '2026-03-15 14:51:41', 0, NULL, 0),
(15, 'van', 'uservan@gmail.com', '$2y$10$5MNjOXu1wEOFpwfJ7rB2SOwFg0QoDYtW.NmXrDjpAJ6/GII0Hx9I6', 'user', NULL, NULL, '2026-03-24 05:53:29', 22, NULL, 0),
(16, 'van123', 'tien11@gmail.com', '$2y$10$7VHfbsl1IItR3q.sWx3Sd.AeGXe999ccXyV.3BSi/wgjRDUoNCTce', 'user', NULL, NULL, '2026-03-28 13:31:42', 0, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_interests`
--

CREATE TABLE `user_interests` (
  `user_id` int(11) NOT NULL,
  `topic` varchar(100) NOT NULL,
  `score` decimal(6,3) DEFAULT 1.000,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_messages`
--

CREATE TABLE `user_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ve`
--

CREATE TABLE `ve` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `suat_chieu_id` int(11) DEFAULT NULL,
  `ghe_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ve`
--

INSERT INTO `ve` (`id`, `user_id`, `suat_chieu_id`, `ghe_id`) VALUES
(91, 4, 34, 132),
(92, 4, 17, 94),
(93, 4, 17, 116),
(94, 4, 18, 45),
(95, 4, 18, 46),
(96, 4, 18, 57),
(97, 4, 18, 58),
(98, 4, 32, 132),
(99, 4, 32, 126);

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT '',
  `discount_type` enum('percent','fixed') DEFAULT 'percent',
  `discount_value` int(11) NOT NULL DEFAULT 0,
  `max_discount` int(11) DEFAULT NULL,
  `min_total` int(11) DEFAULT 0,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vouchers`
--

INSERT INTO `vouchers` (`id`, `code`, `description`, `discount_type`, `discount_value`, `max_discount`, `min_total`, `usage_limit`, `used_count`, `start_date`, `end_date`, `active`, `created_at`) VALUES
(1, 'GIAM50', 'Giảm 50% cho tất cả đơn hàng', 'percent', 50, 50000, 0, 100, 2, '2024-01-01', '2030-12-31', 1, '2026-03-19 14:48:24');

-- --------------------------------------------------------

--
-- Table structure for table `voucher_usages`
--

CREATE TABLE `voucher_usages` (
  `id` int(11) NOT NULL,
  `voucher_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `used_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `voucher_usages`
--

INSERT INTO `voucher_usages` (`id`, `voucher_id`, `user_id`, `used_at`) VALUES
(1, 1, 4, '2026-03-19 15:29:16'),
(2, 1, 15, '2026-03-24 12:58:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `combos`
--
ALTER TABLE `combos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `combo_orders`
--
ALTER TABLE `combo_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_target` (`target_type`,`target_id`),
  ADD KEY `idx_parent` (`parent_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `feed_scores`
--
ALTER TABLE `feed_scores`
  ADD PRIMARY KEY (`user_id`,`post_id`),
  ADD KEY `idx_score` (`user_id`,`relevance_score`);

--
-- Indexes for table `follows`
--
ALTER TABLE `follows`
  ADD PRIMARY KEY (`follower_id`,`following_id`),
  ADD KEY `idx_following` (`following_id`);

--
-- Indexes for table `ghe`
--
ALTER TABLE `ghe`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gift_cards`
--
ALTER TABLE `gift_cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `phim`
--
ALTER TABLE `phim`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `phong_chieu`
--
ALTER TABLE `phong_chieu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_phim` (`phim_id`),
  ADD KEY `idx_time` (`created_at`);

--
-- Indexes for table `post_impressions`
--
ALTER TABLE `post_impressions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_post` (`user_id`,`post_id`),
  ADD KEY `idx_time` (`created_at`);

--
-- Indexes for table `rap`
--
ALTER TABLE `rap`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_phim` (`user_id`,`phim_id`),
  ADD KEY `idx_phim` (`phim_id`);

--
-- Indexes for table `reactions`
--
ALTER TABLE `reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_reaction` (`user_id`,`target_type`,`target_id`),
  ADD KEY `idx_target` (`target_type`,`target_id`);

--
-- Indexes for table `suat_chieu`
--
ALTER TABLE `suat_chieu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_interests`
--
ALTER TABLE `user_interests`
  ADD PRIMARY KEY (`user_id`,`topic`);

--
-- Indexes for table `user_messages`
--
ALTER TABLE `user_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_messages_sender` (`sender_id`),
  ADD KEY `idx_user_messages_receiver` (`receiver_id`),
  ADD KEY `idx_user_messages_pair` (`sender_id`,`receiver_id`),
  ADD KEY `idx_user_messages_created_at` (`created_at`);

--
-- Indexes for table `ve`
--
ALTER TABLE `ve`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `suat_chieu_id` (`suat_chieu_id`,`ghe_id`),
  ADD UNIQUE KEY `suat_chieu_id_2` (`suat_chieu_id`,`ghe_id`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `voucher_usages`
--
ALTER TABLE `voucher_usages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_usage` (`voucher_id`,`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `combos`
--
ALTER TABLE `combos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `combo_orders`
--
ALTER TABLE `combo_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `ghe`
--
ALTER TABLE `ghe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;

--
-- AUTO_INCREMENT for table `gift_cards`
--
ALTER TABLE `gift_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `phim`
--
ALTER TABLE `phim`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `phong_chieu`
--
ALTER TABLE `phong_chieu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `post_impressions`
--
ALTER TABLE `post_impressions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `rap`
--
ALTER TABLE `rap`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reactions`
--
ALTER TABLE `reactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `suat_chieu`
--
ALTER TABLE `suat_chieu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=145;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `user_messages`
--
ALTER TABLE `user_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ve`
--
ALTER TABLE `ve`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `voucher_usages`
--
ALTER TABLE `voucher_usages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

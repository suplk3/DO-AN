-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 18, 2026 at 12:15 PM
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
(8, 4, 'phim', 7, 6, 'sao gớm', '2026-03-15 17:03:13');

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
  `do_tuoi` varchar(10) DEFAULT 'P'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `phim`
--

INSERT INTO `phim` (`id`, `ten_phim`, `the_loai`, `thoi_luong`, `mo_ta`, `poster`, `banner`, `ngay_khoi_chieu`) VALUES
(1, 'Avengers: Endgame', ' Hành Động, Khoa Học Viễn Tưởng', 182, 'Sau sự kiện Thanos hủy diệt một nửa vũ trụ, các Avengers còn sống sót phải tập hợp lại để đảo ngược thảm họa và mang lại hy vọng cho nhân loại.', 'avengers.jpg', NULL, NULL),
(2, 'Spider-Man: No Way Home', 'Hành động, Phiêu lưu', 157, 'Lần đầu tiên trong lịch sử điện ảnh của Người Nhện, danh tính của người hùng thân thiện của chúng ta bị tiết lộ, khiến trách nhiệm siêu anh hùng của anh mâu thuẫn với cuộc sống thường nhật và đặt những người anh yêu thương nhất vào nguy hiểm. Khi anh nhờ Doctor Strange giúp đỡ để khôi phục bí mật, phép thuật đã xé toạc một lỗ hổng trong thế giới của họ, giải phóng những kẻ phản diện mạnh nhất từng chiến đấu với Người Nhện trong bất kỳ vũ trụ nào. Giờ đây, Peter sẽ phải vượt qua thử thách lớn nhất từ ​​trước đến nay, điều không chỉ thay đổi tương lai của chính anh mà còn cả tương lai của Đa vũ trụ.', 'ev-content-thumb293.png', NULL, NULL),
(3, 'Batman', 'Hành Động, Tội phạm', 183, 'Bộ phim đưa khán giả dõi theo hành trình phá án và diệt trừ tội phạm của chàng Hiệp sĩ Bóng đêm Batman, với một câu chuyện hoàn toàn khác biệt với những phần phim đã ra mắt trước đây. Thế giới ngầm ở thành phố Gotham xuất hiện một tên tội phạm kỳ lạ tên Riddler chuyên nhắm vào nhân vật tai to mặt lớn. Và sau mỗi lần phạm tội, hắn đều để lại một câu đố bí ẩn cho Batman. Khi bắt tay vào phá giải các câu đố này, Batman dần lật mở những bí ẩn động trời giữa gia đình anh và tên trùm tội phạm Carmine Falcon\r\n', 'batman.jpg', NULL, NULL),
(4, 'PHIM ĐIỆN ẢNH THÁM TỬ LỪNG DANH CONAN: QUẢ BOM CHỌC TRỜI', 'Bí ẩn, Hành Động, Hoạt Hình', 95, 'Phim Điện Ảnh Thám Tử Lừng Danh Conan: Quả Bom Chọc Trời là bộ phim điện ảnh đầu tiên của chuỗi phim điện ảnh Thám Tử Lừng Danh Conan. Phim được chuyển thể dựa trên nguyên tác của Gosho Aoyama, do Kenji Kodama đạo diễn. Kudo Shinichi được kiến trúc sư nổi tiếng Moriya Teiji mời đến buổi tiệc trà tại dinh thự tư nhân. Tuy nhiên, do đã bị thu nhỏ, Shinichi vốn không thể tham gia buổi tiệc này. Thay vào đó, thám tử Mori, Ran và Conan đã cùng nhau đến dự thay cho Shinichi. Cùng lúc đó, Shinichi bất ngờ bị một kẻ ẩn danh thách thức qua điện thoại: cậu phải tìm ra những quả bom được đặt rải rác khắp Tokyo trước khi chúng phát nổ. Liệu cậu ấy có thể tìm thấy và vô hiệu hóa tất cả số bom trước khi quá muộn?', 'poster_conan_qua_bom_choc_troi_6.jpg', NULL, NULL),
(5, 'F1®', 'Hành Động, Tâm Lý', 156, 'Sonny Hayes (Brad Pitt) được mệnh danh là \"Huyền thoại chưa từng được gọi tên\" là ngôi sao sáng giá nhất của FORMULA 1 trong những năm 1990 cho đến khi một vụ tai nạn trên đường đua suýt nữa đã kết thúc sự nghiệp của anh.. Ba mươi năm sau, Sonny trở thành một tay đua tự do, cho đến khi người đồng đội cũ của anh, Ruben Cervantes (Javier Bardem), chủ sở hữu một đội đua F1 đang trên bờ vực sụp đổ, tìm đến anh. Ruben thuyết phục Sonny quay lại với F1® để có một cơ hội cuối cùng cứu lấy đội và khẳng định mình là tay đua xuất sắc nhất thế giới. Anh sẽ thi đấu cùng Joshua Pearce (Damson Idris), tay đua tân binh đầy tham vọng của đội, người luôn muốn tạo ra tốc độ của riêng mình. Tuy nhiên, khi động cơ gầm rú, quá khứ của Sonny sẽ đuổi theo anh và anh nhận ra rằng trong F1, người đồng đội chính là đối thủ cạnh tranh lớn nhất—và con đường chuộc lại lỗi lầm không phải là điều có thể đi một mình. (CHIẾU LẠI từ 06/02/2026)', 'poster_f1_rerun_1_.jpg', NULL, NULL),
(6, 'MƯA ĐỎ', 'Hành Động, Lịch Sử', 124, '“Mưa Đỏ” - Phim truyện điện ảnh về đề tài chiến tranh cách mạng, kịch bản của nhà văn Chu Lai, lấy cảm hứng và hư cấu từ sự kiện 81 ngày đêm chiến đấu anh dũng, kiên cường của nhân dân và cán bộ, chiến sĩ bảo vệ Thành Cổ Quảng Trị năm 1972. Tiểu đội 1 gồm toàn những thanh niên trẻ tuổi và đầy nhiệt huyết là một trong những đơn vị chiến đấu, bám trụ tại trận địa khốc liệt này. Bộ phim là khúc tráng ca bằng hình ảnh, là nén tâm nhang tri ân và tưởng nhớ những người con đã dâng hiến tuổi thanh xuân cho đất nước, mang âm hưởng của tình yêu, tình đồng đội thiêng liêng, là khát vọng hòa bình, hoà hợp dân tộc của nhân dân Việt Nam.', '350x495-muado_1.jpg', '1773151176_banner_968ed3d13f7f.jpg', NULL),
(7, 'PANOR: TÀ THUẬT HUYẾT NGẢI 2', 'Hồi hộp, Kinh Dị', 125, 'Sau khi từ bỏ quyền năng của Thần Ba Mắt, cuộc đời Panor được thiết lập lại, nhưng ký ức đã bị xóa sạch. Cô bắt đầu cuộc sống mới như mơ, được học tại trường sư phạm với Piak luôn lặng lẽ theo dõi và giúp đỡ từ xa. Nhưng bình yên ngắn chẳng tày gang, khi có một người bí ẩn luôn nhăm nhe tìm cách đánh thức sức mạnh của Thần Ba Mắt bên trong Panor.', 'poster_panor_ta_thuat_huyen_ngai__1.jpg', '1773151005_banner_a973c0650015.jpg', NULL),
(8, 'TÀI', 'Gia đình, Hành Động, Tâm Lý', 101, 'Tài bất ngờ rơi vào vòng xoáy nguy hiểm vì một khoản nợ tiền khổng lồ. Bị dồn vào đường cùng, Tài buộc phải dấn thân vào những lựa chọn sai lầm khiến gia đình trở thành mục tiêu bị đe dọa. Đằng sau những hành động liều lĩnh ấy là nỗi ám ảnh về người mẹ mà Tài luôn muốn bảo vệ và bù đắp bằng mọi giá. Khi ranh giới giữa đúng và sai ngày càng mong manh, Tài phải đối mặt với câu hỏi lớn nhất của đời mình: liệu lòng hiếu thảo có đủ để biện minh cho con đường anh đang đi.', '1773152573_09197e2339a5.jpg', '1773152573_banner_ae73638c5828.jpg', '2026-05-01');

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
(1, 4, 8, 'Bộ phim TÀI - TÌNH nhất mùa xuân 2026 ❤️\r\n🎬 TÀI | ĐANG CHIẾU TẠI RẠP', 'post_69b6c46ba570e.jpg', 0.00, 0, '2026-03-15 14:38:35', '2026-03-15 14:38:35');

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
(51, 4, 'phim', 7, 'like', '2026-03-15 17:56:23');

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
(10, 1, 1, '2026-03-20', '14:22:00', 22222),
(14, 8, 1, '2026-05-01', '00:30:00', 75000);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `ten`, `email`, `mat_khau`, `vai_tro`, `avatar`, `bio`, `created_at`) VALUES
(4, 'Admin', 'admin@gmail.com', '$2y$10$TN5EVTTz9pWxuvOE4irzLOjTx2HiHw80rUYAFG.ecpxzSynNBM72C', 'admin', NULL, NULL, '2026-03-15 14:12:46'),
(5, 'User A', 'user@gmail.com', '$2y$10$0kD0d4oQkDFugcfnS4.8NOC/XByH7lO6gf9f.uTe7qH93t/qm.r/.', 'user', NULL, NULL, '2026-03-15 14:12:46'),
(6, 'Tuấn', 'trian672008@gmail.com', '$2y$10$8vf.7uag8rxxKk/YzG9DbO3qWLVEzHG/1epbmXXIiniHCFe8.ZSj6', 'user', NULL, NULL, '2026-03-15 14:12:46'),
(7, 'Test User 1', 'test1@example.com', '$2y$10$4TcCqah0HPn79oh6hjLDguKAkqnThHXW64uFS..idJ4JclMBmvWMS', 'user', NULL, NULL, '2026-03-15 14:12:46'),
(8, 'Test User 2', 'test2@example.com', '$2y$10$tmPYlKWmGQt/Y9QlSyGR2eHMw0PJotJHV8CbLD/4z.puZ/mIBCz4e', 'user', NULL, NULL, '2026-03-15 14:12:46'),
(9, 'Admin Fake', 'admin_fake@example.com', '$2y$10$zgjl/ObIHyXDY3nc86XLtuyqbcUZToGvQG7rWqDE8Lq8Lye6PE.zq', 'user', NULL, NULL, '2026-03-15 14:12:46'),
(10, 'van', 'vanngu@gmail.com', '$2y$10$3ivmcYkverBEVR93ZRqse.hgxGlfGLOWDx9U1Jm1A2POM/okYm2..', 'user', NULL, NULL, '2026-03-15 14:12:46'),
(11, 'van', 'vanbingu@gmail.com', '$2y$10$v5VQ0gaID3zRA46FDlAH2OfyR89gctibb9Y5uJg3S/kVpHyprzypq', 'user', NULL, NULL, '2026-03-15 14:12:46'),
(12, 'Tuấn', 'tuan0suy@gmail.com', '$2y$10$DczP0NUMpHRL1gch6qz2Me6wXWFQhBYiMLHIzQVCBqUSjjEoKVS9i', 'user', NULL, NULL, '2026-03-15 14:12:46'),
(13, 'van', 'vanngu123@gmail.com', '$2y$10$Pt64.8.E9i91CFuo70LG1OAfEFMZRzXVxUj92msfDC5QctFeqSF2y', 'user', NULL, NULL, '2026-03-15 14:51:41');

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
(37, 5, 10, 15),
(40, 4, 10, 8),
(42, 4, 10, 16),
(45, 4, 10, 7),
(46, 4, 10, 6),
(47, 4, 10, 5),
(48, 4, 10, 4),
(49, 4, 10, 3),
(50, 4, 10, 2),
(51, 4, 10, 1),
(52, 4, 10, 9),
(63, 4, 10, 21),
(64, 4, 10, 12),
(65, 4, 10, 13),
(66, 4, 10, 14),
(67, 4, 10, 22),
(68, 4, 10, 10),
(69, 4, 10, 11),
(70, 4, 10, 19),
(71, 4, 10, 20);

--
-- Indexes for dumped tables
--

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
-- Indexes for table `ve`
--
ALTER TABLE `ve`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `suat_chieu_id` (`suat_chieu_id`,`ghe_id`),
  ADD UNIQUE KEY `suat_chieu_id_2` (`suat_chieu_id`,`ghe_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `ghe`
--
ALTER TABLE `ghe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;

--
-- AUTO_INCREMENT for table `phim`
--
ALTER TABLE `phim`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `phong_chieu`
--
ALTER TABLE `phong_chieu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `post_impressions`
--
ALTER TABLE `post_impressions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rap`
--
ALTER TABLE `rap`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reactions`
--
ALTER TABLE `reactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `suat_chieu`
--
ALTER TABLE `suat_chieu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `ve`
--
ALTER TABLE `ve`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;
COMMIT;

--
-- Table structure for table `combos`
--
CREATE TABLE `combos` (
  `id` int(11) NOT NULL,
  `ten` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mo_ta` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
-- AUTO_INCREMENT for table `combos`
--
ALTER TABLE `combos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `combo_orders`
--
ALTER TABLE `combo_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

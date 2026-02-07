-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th2 07, 2026 lúc 01:56 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `cinema`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ghe`
--

CREATE TABLE `ghe` (
  `id` int(11) NOT NULL,
  `phong_id` int(11) DEFAULT NULL,
  `ten_ghe` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `ghe`
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
(40, 1, 'E8');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phim`
--

CREATE TABLE `phim` (
  `id` int(11) NOT NULL,
  `ten_phim` varchar(200) DEFAULT NULL,
  `the_loai` varchar(100) DEFAULT NULL,
  `thoi_luong` int(11) DEFAULT NULL,
  `mo_ta` text DEFAULT NULL,
  `poster` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `phim`
--

INSERT INTO `phim` (`id`, `ten_phim`, `the_loai`, `thoi_luong`, `mo_ta`, `poster`) VALUES
(1, 'Avengers: Endgame', ' Hành Động, Khoa Học Viễn Tưởng', 182, 'Sau sự kiện Thanos hủy diệt một nửa vũ trụ, các Avengers còn sống sót phải tập hợp lại để đảo ngược thảm họa và mang lại hy vọng cho nhân loại.', 'avengers.jpg'),
(2, 'Spider-Man: No Way Home', 'Hành động, Phiêu lưu', 157, 'Lần đầu tiên trong lịch sử điện ảnh của Người Nhện, danh tính của người hùng thân thiện của chúng ta bị tiết lộ, khiến trách nhiệm siêu anh hùng của anh mâu thuẫn với cuộc sống thường nhật và đặt những người anh yêu thương nhất vào nguy hiểm. Khi anh nhờ Doctor Strange giúp đỡ để khôi phục bí mật, phép thuật đã xé toạc một lỗ hổng trong thế giới của họ, giải phóng những kẻ phản diện mạnh nhất từng chiến đấu với Người Nhện trong bất kỳ vũ trụ nào. Giờ đây, Peter sẽ phải vượt qua thử thách lớn nhất từ ​​trước đến nay, điều không chỉ thay đổi tương lai của chính anh mà còn cả tương lai của Đa vũ trụ.', 'ev-content-thumb293.png'),
(3, 'Batman', 'Hành Động, Tội phạm', 183, 'Bộ phim đưa khán giả dõi theo hành trình phá án và diệt trừ tội phạm của chàng Hiệp sĩ Bóng đêm Batman, với một câu chuyện hoàn toàn khác biệt với những phần phim đã ra mắt trước đây. Thế giới ngầm ở thành phố Gotham xuất hiện một tên tội phạm kỳ lạ tên Riddler chuyên nhắm vào nhân vật tai to mặt lớn. Và sau mỗi lần phạm tội, hắn đều để lại một câu đố bí ẩn cho Batman. Khi bắt tay vào phá giải các câu đố này, Batman dần lật mở những bí ẩn động trời giữa gia đình anh và tên trùm tội phạm Carmine Falcon\r\n', 'batman.jpg'),
(4, 'PHIM ĐIỆN ẢNH THÁM TỬ LỪNG DANH CONAN: QUẢ BOM CHỌC TRỜI', 'Bí ẩn, Hành Động, Hoạt Hình', 95, 'Phim Điện Ảnh Thám Tử Lừng Danh Conan: Quả Bom Chọc Trời là bộ phim điện ảnh đầu tiên của chuỗi phim điện ảnh Thám Tử Lừng Danh Conan. Phim được chuyển thể dựa trên nguyên tác của Gosho Aoyama, do Kenji Kodama đạo diễn. Kudo Shinichi được kiến trúc sư nổi tiếng Moriya Teiji mời đến buổi tiệc trà tại dinh thự tư nhân. Tuy nhiên, do đã bị thu nhỏ, Shinichi vốn không thể tham gia buổi tiệc này. Thay vào đó, thám tử Mori, Ran và Conan đã cùng nhau đến dự thay cho Shinichi. Cùng lúc đó, Shinichi bất ngờ bị một kẻ ẩn danh thách thức qua điện thoại: cậu phải tìm ra những quả bom được đặt rải rác khắp Tokyo trước khi chúng phát nổ. Liệu cậu ấy có thể tìm thấy và vô hiệu hóa tất cả số bom trước khi quá muộn?', 'poster_conan_qua_bom_choc_troi_6.jpg'),
(5, 'F1®', 'Hành Động, Tâm Lý', 156, 'Sonny Hayes (Brad Pitt) được mệnh danh là \"Huyền thoại chưa từng được gọi tên\" là ngôi sao sáng giá nhất của FORMULA 1 trong những năm 1990 cho đến khi một vụ tai nạn trên đường đua suýt nữa đã kết thúc sự nghiệp của anh.. Ba mươi năm sau, Sonny trở thành một tay đua tự do, cho đến khi người đồng đội cũ của anh, Ruben Cervantes (Javier Bardem), chủ sở hữu một đội đua F1 đang trên bờ vực sụp đổ, tìm đến anh. Ruben thuyết phục Sonny quay lại với F1® để có một cơ hội cuối cùng cứu lấy đội và khẳng định mình là tay đua xuất sắc nhất thế giới. Anh sẽ thi đấu cùng Joshua Pearce (Damson Idris), tay đua tân binh đầy tham vọng của đội, người luôn muốn tạo ra tốc độ của riêng mình. Tuy nhiên, khi động cơ gầm rú, quá khứ của Sonny sẽ đuổi theo anh và anh nhận ra rằng trong F1, người đồng đội chính là đối thủ cạnh tranh lớn nhất—và con đường chuộc lại lỗi lầm không phải là điều có thể đi một mình. (CHIẾU LẠI từ 06/02/2026)', 'poster_f1_rerun_1_.jpg'),
(6, 'MƯA ĐỎ', 'Hành Động, Lịch Sử', 124, '“Mưa Đỏ” - Phim truyện điện ảnh về đề tài chiến tranh cách mạng, kịch bản của nhà văn Chu Lai, lấy cảm hứng và hư cấu từ sự kiện 81 ngày đêm chiến đấu anh dũng, kiên cường của nhân dân và cán bộ, chiến sĩ bảo vệ Thành Cổ Quảng Trị năm 1972. Tiểu đội 1 gồm toàn những thanh niên trẻ tuổi và đầy nhiệt huyết là một trong những đơn vị chiến đấu, bám trụ tại trận địa khốc liệt này. Bộ phim là khúc tráng ca bằng hình ảnh, là nén tâm nhang tri ân và tưởng nhớ những người con đã dâng hiến tuổi thanh xuân cho đất nước, mang âm hưởng của tình yêu, tình đồng đội thiêng liêng, là khát vọng hòa bình, hoà hợp dân tộc của nhân dân Việt Nam.', '350x495-muado_1.jpg'),
(7, 'PANOR: TÀ THUẬT HUYẾT NGẢI 2', 'Hồi hộp, Kinh Dị', 125, 'Sau khi từ bỏ quyền năng của Thần Ba Mắt, cuộc đời Panor được thiết lập lại, nhưng ký ức đã bị xóa sạch. Cô bắt đầu cuộc sống mới như mơ, được học tại trường sư phạm với Piak luôn lặng lẽ theo dõi và giúp đỡ từ xa. Nhưng bình yên ngắn chẳng tày gang, khi có một người bí ẩn luôn nhăm nhe tìm cách đánh thức sức mạnh của Thần Ba Mắt bên trong Panor.', 'poster_panor_ta_thuat_huyen_ngai__1.jpg');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phong_chieu`
--

CREATE TABLE `phong_chieu` (
  `id` int(11) NOT NULL,
  `rap_id` int(11) DEFAULT NULL,
  `ten_phong` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `rap`
--

CREATE TABLE `rap` (
  `id` int(11) NOT NULL,
  `ten_rap` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `suat_chieu`
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
-- Đang đổ dữ liệu cho bảng `suat_chieu`
--

INSERT INTO `suat_chieu` (`id`, `phim_id`, `phong_id`, `ngay`, `gio`, `gia`) VALUES
(1, 1, 1, '2026-02-05', '09:00:00', 70000),
(2, 1, 1, '2026-02-05', '13:00:00', 70000),
(3, 1, 1, '2026-02-05', '19:30:00', 90000),
(4, 1, 1, '2026-02-06', '10:00:00', 70000),
(5, 1, 1, '2026-02-06', '20:00:00', 90000),
(6, 2, NULL, '2026-02-04', '02:12:00', 123000),
(7, 1, NULL, '2026-02-05', '14:22:00', 75000),
(8, 3, NULL, '2026-02-06', '14:22:00', 125000),
(9, 2, NULL, '2026-03-04', '12:22:00', 125000);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `ten` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mat_khau` varchar(255) DEFAULT NULL,
  `vai_tro` enum('user','admin') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `ten`, `email`, `mat_khau`, `vai_tro`) VALUES
(4, 'Admin', 'admin@gmail.com', '$2y$10$TN5EVTTz9pWxuvOE4irzLOjTx2HiHw80rUYAFG.ecpxzSynNBM72C', 'admin'),
(5, 'User A', 'user@gmail.com', '$2y$10$0kD0d4oQkDFugcfnS4.8NOC/XByH7lO6gf9f.uTe7qH93t/qm.r/.', 'user');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ve`
--

CREATE TABLE `ve` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `suat_chieu_id` int(11) DEFAULT NULL,
  `ghe_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `ve`
--

INSERT INTO `ve` (`id`, `user_id`, `suat_chieu_id`, `ghe_id`) VALUES
(1, 1, 1, 11),
(2, 1, 1, 9),
(3, 1, 1, 10),
(4, 1, 4, 37),
(5, 4, 6, 11),
(6, 4, 6, 13),
(7, 4, 6, 14),
(8, 4, 7, 20),
(9, 4, 7, 19),
(10, 4, 7, 19),
(11, 4, 7, 19);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `ghe`
--
ALTER TABLE `ghe`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `phim`
--
ALTER TABLE `phim`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `phong_chieu`
--
ALTER TABLE `phong_chieu`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `rap`
--
ALTER TABLE `rap`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `suat_chieu`
--
ALTER TABLE `suat_chieu`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `ve`
--
ALTER TABLE `ve`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `ghe`
--
ALTER TABLE `ghe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT cho bảng `phim`
--
ALTER TABLE `phim`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `phong_chieu`
--
ALTER TABLE `phong_chieu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `rap`
--
ALTER TABLE `rap`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `suat_chieu`
--
ALTER TABLE `suat_chieu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `ve`
--
ALTER TABLE `ve`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

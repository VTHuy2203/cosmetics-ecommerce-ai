-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: localhost:3306
-- Thời gian đã tạo: Th10 09, 2025 lúc 09:52 AM
-- Phiên bản máy phục vụ: 8.0.30
-- Phiên bản PHP: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `webmipham1`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `binhluan`
--

CREATE TABLE `binhluan` (
  `ID_BinhLuan` int NOT NULL,
  `ID_KhachHang` int NOT NULL,
  `ID_SanPham` int NOT NULL,
  `NoiDungBinhLuan` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `DaMuaHang` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `binhluan`
--

INSERT INTO `binhluan` (`ID_BinhLuan`, `ID_KhachHang`, `ID_SanPham`, `NoiDungBinhLuan`, `DaMuaHang`) VALUES
(46, 11, 52, 'sản phẩm tốt\n', 0),
(47, 11, 37, 'sản phẩm tốt', 0),
(48, 11, 37, 'ok', 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chat_appointments`
--

CREATE TABLE `chat_appointments` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(128) NOT NULL,
  `appt_at` datetime NOT NULL,
  `note` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `chat_appointments`
--

INSERT INTO `chat_appointments` (`id`, `user_id`, `session_id`, `appt_at`, `note`, `created_at`) VALUES
(1, 584018126, 'u584018126_1762110946911_ch2zye', '2025-11-03 19:30:00', 'ngày 03/11/2025 19:30', '2025-11-02 19:44:12'),
(2, 584018126, 'u584018126_1762193991870_bsl6qo', '2025-11-04 19:30:00', '04/11/2025 19:30', '2025-11-03 18:21:01'),
(3, 584018126, 'u584018126_1762193991870_bsl6qo', '2025-11-05 19:30:00', '05/11/2025 19:30', '2025-11-03 18:43:30'),
(4, 584018126, 'u584018126_1762193991870_bsl6qo', '2025-11-05 20:00:00', '05/11/2025 20:00', '2025-11-03 19:10:46');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chat_bookings`
--

CREATE TABLE `chat_bookings` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(128) NOT NULL,
  `book_at` datetime NOT NULL,
  `note` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chat_logs`
--

CREATE TABLE `chat_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `message` text NOT NULL,
  `reply` text,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `chat_logs`
--

INSERT INTO `chat_logs` (`id`, `user_id`, `message`, `reply`, `timestamp`) VALUES
(1, 13, 'xin chào', NULL, '2025-10-13 17:36:07'),
(2, 13, 'xin chào', 'Hello! How can I help with products, pricing, or delivery?', '2025-10-13 17:36:07'),
(3, 13, 'tôi muốn mua hàng', NULL, '2025-10-13 17:36:18'),
(4, 13, 'tôi muốn mua hàng', 'Thanks! I have recorded your question. Please leave email/phone for faster support.', '2025-10-13 17:36:18'),
(5, 13, 'hãy tư vấn cho tôi', NULL, '2025-10-13 17:36:21'),
(6, 13, 'hãy tư vấn cho tôi', 'You can check: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Are you looking for oily, dry, or sensitive skin products?', '2025-10-13 17:36:21'),
(7, 13, 'bạn có thể nói tiếng Việt không ?', NULL, '2025-10-13 17:38:01'),
(8, 13, 'bạn có thể nói tiếng Việt không ?', 'Thanks! I have recorded your question. Please leave email/phone for faster support.', '2025-10-13 17:38:01'),
(9, 13, 'Vie', NULL, '2025-10-13 17:38:09'),
(10, 13, 'Vie', 'Thanks! I have recorded your question. Please leave email/phone for faster support.', '2025-10-13 17:38:09'),
(11, 13, 'Vietnamese', NULL, '2025-10-13 17:38:15'),
(12, 13, 'Vietnamese', 'Thanks! I have recorded your question. Please leave email/phone for faster support.', '2025-10-13 17:38:15'),
(13, 13, 'tôi muốn mua son', NULL, '2025-10-13 17:38:23'),
(14, 13, 'tôi muốn mua son', 'Thanks! I have recorded your question. Please leave email/phone for faster support.', '2025-10-13 17:38:23'),
(15, 13, 'xin chào', NULL, '2025-10-13 17:45:59'),
(16, 13, 'xin chào', 'Hello! How can I help with products, pricing, or delivery?', '2025-10-13 17:45:59'),
(17, 13, 'ship', NULL, '2025-10-13 17:49:31'),
(18, 13, 'ship', 'Hello! How can I help with products, pricing, or delivery?', '2025-10-13 17:49:31'),
(19, 13, 'price', NULL, '2025-10-13 17:49:42'),
(20, 13, 'price', 'Prices are shown on product pages. Which product are you interested in?', '2025-10-13 17:49:42'),
(21, 13, 'what ship', NULL, '2025-10-13 17:49:58'),
(22, 13, 'what ship', 'Hello! How can I help with products, pricing, or delivery?', '2025-10-13 17:49:58'),
(23, 13, 'ship', NULL, '2025-10-13 17:50:04'),
(24, 13, 'ship', 'Hello! How can I help with products, pricing, or delivery?', '2025-10-13 17:50:04'),
(25, 13, 'suggest', NULL, '2025-10-13 17:50:12'),
(26, 13, 'suggest', 'Thanks! I have recorded your question. Please leave email/phone for faster support.', '2025-10-13 17:50:12'),
(27, 13, 'hi', NULL, '2025-10-13 17:53:22'),
(28, 13, 'hi', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', '2025-10-13 17:53:22'),
(29, 13, 'tôi muốn mua hàng', NULL, '2025-10-13 17:53:29'),
(30, 13, 'tôi muốn mua hàng', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', '2025-10-13 17:53:29'),
(31, 13, '0325324064', NULL, '2025-10-13 17:53:35'),
(32, 13, '0325324064', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', '2025-10-13 17:53:35'),
(33, 13, 'hello', NULL, '2025-10-13 17:53:46'),
(34, 13, 'hello', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', '2025-10-13 17:53:46'),
(35, 13, 'who are you', NULL, '2025-10-13 17:53:54'),
(36, 13, 'who are you', 'Thanks! I have recorded your question. Please leave email/phone for faster support.', '2025-10-13 17:53:54'),
(37, 13, 'Hello', NULL, '2025-10-13 17:54:13'),
(38, 13, 'Hello', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', '2025-10-13 17:54:13'),
(39, 13, 'hello', NULL, '2025-10-13 18:07:04'),
(40, 13, 'hello', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', '2025-10-13 18:07:04'),
(41, 13, 'hello', NULL, '2025-10-13 18:07:16'),
(42, 13, 'hello', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', '2025-10-13 18:07:16'),
(43, 13, 'hello', NULL, '2025-10-13 18:10:24'),
(44, 13, 'hello', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', '2025-10-13 18:10:24'),
(45, 13, 'tôi cần mua hàng', NULL, '2025-10-13 18:10:33'),
(46, 13, 'tôi cần mua hàng', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', '2025-10-13 18:10:33'),
(47, 13, 'tôi cần tìm sản phẩm', NULL, '2025-10-13 18:10:37'),
(48, 13, 'tôi cần tìm sản phẩm', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', '2025-10-13 18:10:37'),
(49, 13, 'son', NULL, '2025-10-13 18:10:39'),
(50, 13, 'son', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', '2025-10-13 18:10:39'),
(51, 13, 'son', NULL, '2025-10-13 18:26:44'),
(52, 13, 'son', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', '2025-10-13 18:26:44'),
(53, 13, 'mua hàng', NULL, '2025-10-13 18:26:46'),
(54, 13, 'mua hàng', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', '2025-10-13 18:26:46'),
(55, 13, 'hello', NULL, '2025-10-14 06:45:23'),
(56, 13, 'hello', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', '2025-10-14 06:45:23'),
(57, 13, 'who are you', NULL, '2025-10-14 06:45:38'),
(58, 13, 'who are you', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', '2025-10-14 06:45:38'),
(59, 13, 'hello', NULL, '2025-10-14 07:14:19'),
(60, 13, 'hello', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', '2025-10-14 07:14:19'),
(61, 13, 'hello', NULL, '2025-10-14 07:32:00'),
(62, 13, 'hello', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', '2025-10-14 07:32:04'),
(63, 13, 'xin chào', NULL, '2025-10-14 07:32:07'),
(64, 13, 'xin chào', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.', '2025-10-14 07:32:11'),
(65, 13, 'tôi muốn mua hàng', NULL, '2025-10-14 07:32:18'),
(66, 13, 'tôi muốn mua hàng', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.', '2025-10-14 07:32:22'),
(67, 13, 'son', NULL, '2025-10-14 07:32:27'),
(68, 13, 'son', 'Sorry, the system is busy. Please try again in a moment.', '2025-10-14 07:32:29'),
(69, 13, 'tôi muốn mua son', NULL, '2025-10-14 07:33:07'),
(70, 13, 'tôi muốn mua son', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.', '2025-10-14 07:33:11'),
(71, 13, 'hello', NULL, '2025-10-14 07:33:15'),
(72, 13, 'hello', 'Sorry, the system is busy. Please try again in a moment.', '2025-10-14 07:33:18'),
(73, 13, 'xin chào', NULL, '2025-10-14 07:35:30'),
(74, 13, 'xin chào', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.', '2025-10-14 07:35:33'),
(75, 13, 'tôi cần tư vấn sản phẩm', NULL, '2025-10-14 07:35:34'),
(76, 13, 'tôi cần tư vấn sản phẩm', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.', '2025-10-14 07:35:37'),
(77, 11, 'tôi cần tư vấn về sản phẩm Sữa Rửa Mặt LUMOS PERFECT này, da tôi là da dầu', NULL, '2025-10-14 17:40:43'),
(78, 11, 'tôi cần tư vấn về sản phẩm Sữa Rửa Mặt LUMOS PERFECT này, da tôi là da dầu', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', '2025-10-14 17:40:47'),
(79, 11, 'da dầu', NULL, '2025-10-14 17:41:12'),
(80, 11, 'da dầu', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.', '2025-10-14 17:41:16'),
(81, 11, 'tôi cần 1 loại sữa rửa mặt', NULL, '2025-10-14 17:41:19'),
(82, 11, 'tôi cần 1 loại sữa rửa mặt', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.', '2025-10-14 17:41:21'),
(83, 11, 'tư vấn cho tôi về sữa rửa mặt', NULL, '2025-10-14 17:41:31'),
(84, 11, 'tư vấn cho tôi về sữa rửa mặt', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.', '2025-10-14 17:41:34'),
(85, 11, 'tôi muốn mua sữa rửa mặt', NULL, '2025-10-14 17:41:44'),
(86, 11, 'tôi muốn mua sữa rửa mặt', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', '2025-10-14 17:41:48'),
(87, 11, '0325324064', NULL, '2025-10-14 17:43:30'),
(88, 11, '0325324064', 'Sorry, the system is busy. Please try again in a moment.', '2025-10-14 17:43:32'),
(89, 11, 'giúp tôi đặt lịch tư vấn', NULL, '2025-10-14 17:43:35'),
(90, 11, 'giúp tôi đặt lịch tư vấn', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.', '2025-10-14 17:43:37'),
(91, 11, 'vantronghuy2203@gmail.com', NULL, '2025-10-14 17:43:46'),
(92, 11, 'vantronghuy2203@gmail.com', 'Sorry, the system is busy. Please try again in a moment.', '2025-10-14 17:43:50'),
(93, 11, 'i want to buy serum', NULL, '2025-10-14 17:44:02'),
(94, 11, 'i want to buy serum', 'Sorry, the system is busy. Please try again in a moment.', '2025-10-14 17:44:05'),
(95, 11, 'tôi muốn mua kem chống nắng', NULL, '2025-10-14 17:49:31'),
(96, 11, 'tôi muốn mua kem chống nắng', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', '2025-10-14 17:49:35'),
(97, 11, 'kem chống nắng kiềm da dầu', NULL, '2025-10-14 17:49:47'),
(98, 11, 'kem chống nắng kiềm da dầu', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', '2025-10-14 17:49:50'),
(99, 11, 'son môi thì sao', NULL, '2025-10-14 17:50:00'),
(100, 11, 'son môi thì sao', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', '2025-10-14 17:50:04'),
(101, 11, 'mua được loại nào', NULL, '2025-10-14 17:50:04'),
(102, 11, 'mua được loại nào', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', '2025-10-14 17:50:07'),
(103, 11, 'tôi muốn tư vấn về kem chống nắng', NULL, '2025-10-14 18:08:38'),
(104, 11, 'tôi muốn tư vấn về kem chống nắng', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', '2025-10-14 18:08:42'),
(105, 11, 'tôi đang tìm kem chống nắng', NULL, '2025-10-14 18:08:55'),
(106, 11, 'tôi đang tìm kem chống nắng', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', '2025-10-14 18:08:59'),
(107, 11, 'kem dưỡng', NULL, '2025-10-14 18:09:11'),
(108, 11, 'kem dưỡng', 'Mình tìm thấy vài sản phẩm đúng nhu cầu:\n• Bộ Kem dưỡng ẩm & làm sáng LAGUMI  — 139.000đ\nBạn muốn xem chi tiết sản phẩm nào không?', '2025-10-14 18:09:11'),
(109, 11, 'có', NULL, '2025-10-14 18:09:19'),
(110, 11, 'có', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', '2025-10-14 18:09:23'),
(111, 11, 'tôi muốn xem chi tiết sản phẩm', NULL, '2025-10-14 18:09:24'),
(112, 11, 'tôi muốn xem chi tiết sản phẩm', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', '2025-10-14 18:09:28'),
(113, 11, 'giá', NULL, '2025-10-14 18:09:41'),
(114, 11, 'giá', 'Bạn có thể xem giá trực tiếp trên trang sản phẩm. Bạn đang quan tâm sản phẩm nào?', '2025-10-14 18:09:45'),
(115, 11, 'son môi', NULL, '2025-10-14 18:09:49'),
(116, 11, 'son môi', 'Here are some matching products:\n• Kẻ mắt SACE LADY — 399,000 VND\n• Son Môi gecomo Chất Nhung Lì Chống Thấm Nước  — 159,000 VND\n• Son Dưỡng Di.or Collagen Addict Lip Maximizer — 100,000 VND\nWould you like to see details for any item?', '2025-10-14 18:09:52'),
(117, 11, 'cleanser', NULL, '2025-10-14 18:10:11'),
(118, 11, 'cleanser', 'Here are some matching products:\n• Sữa Rửa Mặt LUMOS PERFECT PEARL CLEANSER Cân bằng độ PH  — 819,000 VND\nWould you like to see details for any item?', '2025-10-14 18:10:11'),
(119, 11, 'srm', NULL, '2025-10-14 18:10:19'),
(120, 11, 'srm', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', '2025-10-14 18:10:23'),
(121, 11, 'sua rua mat', NULL, '2025-10-14 18:10:31'),
(122, 11, 'sua rua mat', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', '2025-10-14 18:10:35'),
(123, 11, 'tôi muốn mua kem nề', NULL, '2025-10-14 18:12:16'),
(124, 11, 'tôi muốn mua kem nề', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', '2025-10-14 18:12:20'),
(125, 11, 'tôi muốn mua kem nền', NULL, '2025-10-14 18:12:22'),
(126, 11, 'tôi muốn mua kem nền', 'Mình tìm thấy vài sản phẩm đúng nhu cầu:\n• Kem nền Catrice HD Liquid Coverage Foundation — 335.000đ\n• Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm — 470.000đ\n• Kẻ mắt SACE LADY — 399.000đ\nBạn muốn xem chi tiết sản phẩm nào không?', '2025-10-14 18:12:22'),
(127, 11, '0325324064', NULL, '2025-10-14 18:12:37'),
(128, 11, '0325324064', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', '2025-10-14 18:12:41'),
(129, 11, 'vantronghuy2203@gmail.com', NULL, '2025-10-14 18:12:48'),
(130, 11, 'vantronghuy2203@gmail.com', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', '2025-10-14 18:12:52'),
(131, 11, 'tư vấn tôi qua số điện thoại', NULL, '2025-10-14 18:12:53'),
(132, 11, 'tư vấn tôi qua số điện thoại', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', '2025-10-14 18:12:57'),
(133, 11, 'da nhạy cảm', NULL, '2025-10-14 18:12:58'),
(134, 11, 'da nhạy cảm', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', '2025-10-14 18:13:02'),
(135, 11, 'hello', NULL, '2025-10-14 19:04:51'),
(136, 11, 'hello', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', '2025-10-14 19:04:55'),
(137, 11, 'what, i\'m english', NULL, '2025-10-14 19:05:04'),
(138, 11, 'what, i\'m english', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', '2025-10-14 19:05:08'),
(139, 11, 'can you speak english ?', NULL, '2025-10-14 19:05:13'),
(140, 11, 'can you speak english ?', 'I couldn\'t match your keywords. New arrivals:\n• Kem nền Catrice HD Liquid Coverage Foundation — 335,000 VND\n• Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm — 470,000 VND\n• Kẻ mắt SACE LADY — 399,000 VND\n• Nước Tẩy Trang La Roche-Posay Làm Sạch Sâu Dành Cho Da Dầu Mụn Và Da Thường — 149,000 VND\n• Son Môi gecomo Chất Nhung Lì Chống Thấm Nước  — 159,000 VND\nWould you like to filter by type (cleanser, lipstick, serum...)?', '2025-10-14 19:05:16'),
(141, 11, 'how can i buy this product ?', NULL, '2025-10-14 19:05:46'),
(142, 11, 'how can i buy this product ?', 'I couldn\'t match your keywords. New arrivals:\n• Kem nền Catrice HD Liquid Coverage Foundation — 335,000 VND\n• Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm — 470,000 VND\n• Kẻ mắt SACE LADY — 399,000 VND\n• Nước Tẩy Trang La Roche-Posay Làm Sạch Sâu Dành Cho Da Dầu Mụn Và Da Thường — 149,000 VND\n• Son Môi gecomo Chất Nhung Lì Chống Thấm Nước  — 159,000 VND\nWould you like to filter by type (cleanser, lipstick, serum...)?', '2025-10-14 19:05:49'),
(143, 11, 'i find srm', NULL, '2025-10-14 19:05:56'),
(144, 11, 'i find srm', 'I couldn\'t match your keywords. New arrivals:\n• Kem nền Catrice HD Liquid Coverage Foundation — 335,000 VND\n• Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm — 470,000 VND\n• Kẻ mắt SACE LADY — 399,000 VND\n• Nước Tẩy Trang La Roche-Posay Làm Sạch Sâu Dành Cho Da Dầu Mụn Và Da Thường — 149,000 VND\n• Son Môi gecomo Chất Nhung Lì Chống Thấm Nước  — 159,000 VND\nWould you like to filter by type (cleanser, lipstick, serum...)?', '2025-10-14 19:05:58'),
(145, 11, 'i find srm', NULL, '2025-10-14 19:06:30'),
(146, 11, 'i find srm', 'I couldn\'t match your keywords. New arrivals:\n• Kem nền Catrice HD Liquid Coverage Foundation — 335,000 VND\n• Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm — 470,000 VND\n• Kẻ mắt SACE LADY — 399,000 VND\n• Nước Tẩy Trang La Roche-Posay Làm Sạch Sâu Dành Cho Da Dầu Mụn Và Da Thường — 149,000 VND\n• Son Môi gecomo Chất Nhung Lì Chống Thấm Nước  — 159,000 VND\nWould you like to filter by type (cleanser, lipstick, serum...)?', '2025-10-14 19:06:33'),
(147, 11, 'help me find the son moi', NULL, '2025-10-14 19:06:50'),
(148, 11, 'help me find the son moi', 'I couldn\'t match your keywords. New arrivals:\n• Kem nền Catrice HD Liquid Coverage Foundation — 335,000 VND\n• Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm — 470,000 VND\n• Kẻ mắt SACE LADY — 399,000 VND\n• Nước Tẩy Trang La Roche-Posay Làm Sạch Sâu Dành Cho Da Dầu Mụn Và Da Thường — 149,000 VND\n• Son Môi gecomo Chất Nhung Lì Chống Thấm Nước  — 159,000 VND\nWould you like to filter by type (cleanser, lipstick, serum...)?', '2025-10-14 19:06:53'),
(149, 14, 'tôi đang tìm 1 người để nói chuyện', NULL, '2025-10-14 19:28:03'),
(150, 14, 'bạn có thể nói chuyện với tôi không', NULL, '2025-10-14 19:28:12'),
(151, 14, 'xin chào', NULL, '2025-10-14 19:28:35'),
(152, 14, 'hello', NULL, '2025-10-14 19:28:55'),
(153, 14, 'tôi muốn mua hàng', NULL, '2025-10-14 19:29:16'),
(154, 14, 'tôi cần người nói chuyện', NULL, '2025-10-14 19:30:22'),
(155, 14, 'sao bạn không trả lời tôi ?>', NULL, '2025-10-14 19:30:26'),
(156, 11, 'xin chào', NULL, '2025-10-20 00:55:02'),
(157, 11, 'tôi muốn mua hàng', NULL, '2025-10-20 00:55:05'),
(158, 11, 'giúp tôi đặt lịch tư vấn', NULL, '2025-10-20 00:55:39'),
(159, 11, 'xin chào', NULL, '2025-10-20 01:37:27'),
(160, 11, 'xin chào', 'Chào Trọng Huy! Bạn muốn mua sản phẩm gì (sữa rửa mặt, son môi, serum...)?', '2025-10-20 01:37:28'),
(161, 11, 'xin chào', NULL, '2025-10-20 01:37:39'),
(162, 11, 'xin chào', 'Chào Trọng Huy! Bạn muốn mua sản phẩm gì (sữa rửa mặt, son môi, serum...)?', '2025-10-20 01:37:39'),
(163, 11, 'tôi muốn mua hàng', NULL, '2025-10-20 01:37:46'),
(164, 11, 'tôi muốn mua hàng', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn muốn tham khảo loại nào ạ?', '2025-10-20 01:37:46'),
(165, 11, 'xin chào', NULL, '2025-10-20 01:38:04'),
(166, 11, 'xin chào', 'Chào Trọng Huy! Bạn muốn mua sản phẩm gì (sữa rửa mặt, son môi, serum...)?', '2025-10-20 01:38:04'),
(167, 11, 'xin chào', NULL, '2025-10-20 01:45:19'),
(168, 11, 'xin chào', 'Chào Trọng Huy! Bạn muốn mua sản phẩm gì (sữa rửa mặt, son môi, serum...)?', '2025-10-20 01:45:19'),
(169, 15, 'xin chào', NULL, '2025-10-20 02:05:11'),
(170, 15, 'xin chào', 'Chào huy1! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', '2025-10-20 02:05:11'),
(171, 15, 'tôi muốn tìm sản phẩm, son', NULL, '2025-10-20 02:05:24'),
(172, 15, 'tôi muốn tìm sản phẩm, son', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', '2025-10-20 02:05:24'),
(173, 15, 'tôi không muốn mua hàng', NULL, '2025-10-20 02:06:07'),
(174, 15, 'tôi không muốn mua hàng', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', '2025-10-20 02:06:07'),
(175, 15, 'hi', NULL, '2025-10-20 02:06:14'),
(176, 15, 'hi', 'Chào huy1! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', '2025-10-20 02:06:14'),
(177, 15, 'hello', NULL, '2025-10-20 02:06:17'),
(178, 15, 'hello', 'Chào huy1! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', '2025-10-20 02:06:17'),
(179, 15, 'hi', NULL, '2025-10-20 05:16:42'),
(180, 15, 'hi', 'Chào huy1! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', '2025-10-20 05:16:42'),
(181, 15, 'xin chào', NULL, '2025-10-20 05:16:46'),
(182, 15, 'xin chào', 'Chào huy1! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', '2025-10-20 05:16:46'),
(183, 15, 'tôi muốn mua son', NULL, '2025-10-20 05:16:50'),
(184, 15, 'tôi muốn mua son', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', '2025-10-20 05:16:50'),
(185, 15, 'vantronghuy2203@gmail.com', NULL, '2025-10-20 05:17:00'),
(186, 15, 'vantronghuy2203@gmail.com', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', '2025-10-20 05:17:00'),
(187, 15, 'xin chào', NULL, '2025-10-20 08:51:36'),
(188, 15, 'xin chào', 'Chào huy1! Bạn đang tìm sản phẩm gì ạ?', '2025-10-20 08:51:36'),
(189, 15, 'tôi muốn mua son', NULL, '2025-10-20 08:51:42'),
(190, 15, 'tôi muốn mua son', 'Bạn có thể tham khảo: Kẻ mắt SACE LADY, Son Môi gecomo Chất Nhung Lì Chống Thấm Nước , COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da. Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để tiếp tục.', '2025-10-20 08:51:42'),
(191, 15, 'có', NULL, '2025-10-20 08:52:00'),
(192, 15, 'có', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', '2025-10-20 08:52:00'),
(193, 15, '2025-10-20', NULL, '2025-10-20 08:52:17'),
(194, 15, '2025-10-20', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', '2025-10-20 08:52:17'),
(195, 15, '2025-10-20 19:30', NULL, '2025-10-20 08:52:34'),
(196, 15, '2025-10-20 19:30', 'Đã đặt lịch tư vấn lúc 2025-10-20 19:30:00. Nhân viên sẽ xác nhận sớm! Cảm ơn bạn!', '2025-10-20 08:52:34'),
(197, 15, 'hello', NULL, '2025-10-20 09:10:41'),
(198, 15, 'hello', 'Chào huy1! Bạn đang tìm sản phẩm gì ạ?', '2025-10-20 09:10:41'),
(199, 15, 'i want find the son moi', NULL, '2025-10-20 09:10:47'),
(200, 15, 'i want find the son moi', 'You can check: Kẻ mắt SACE LADY, Son Môi gecomo Chất Nhung Lì Chống Thấm Nước , COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da. Would you like to book a consultation? Reply \"yes\" to continue.', '2025-10-20 09:10:47'),
(201, 15, 'còn sản phẩm nào không ?', NULL, '2025-10-20 09:10:54'),
(202, 15, 'còn sản phẩm nào không ?', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.', '2025-10-20 09:10:59'),
(203, 15, 'tôi muốn tìm kẻ mắt', NULL, '2025-10-20 09:11:35'),
(204, 15, 'tôi muốn tìm kẻ mắt', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.', '2025-10-20 09:11:39'),
(205, 15, 'hi', NULL, '2025-10-20 17:33:56'),
(206, 15, 'hi', 'Chào huy1! Bạn đang tìm sản phẩm gì ạ?', '2025-10-20 17:33:56'),
(207, 15, 'tôi muốn tìm mua son', NULL, '2025-10-20 17:34:30'),
(208, 15, 'tôi muốn tìm mua son', 'Bạn có thể tham khảo: Kẻ mắt SACE LADY, Son Môi gecomo Chất Nhung Lì Chống Thấm Nước , COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da, Son Dưỡng Di.or Collagen Addict Lip Maximizer, Son dưỡng Dior Addict Lip Glow SELENA STORE. Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để chọn thời gian.', '2025-10-20 17:34:30'),
(209, 15, 'chỉ mỗi son thôi', NULL, '2025-10-20 17:34:46'),
(210, 15, 'chỉ mỗi son thôi', 'Bạn có thể tham khảo: Kẻ mắt SACE LADY, Son Môi gecomo Chất Nhung Lì Chống Thấm Nước , COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da, Son Dưỡng Di.or Collagen Addict Lip Maximizer, Son dưỡng Dior Addict Lip Glow SELENA STORE. Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để chọn thời gian.', '2025-10-20 17:34:46'),
(211, 15, 'có', NULL, '2025-10-20 17:34:53'),
(212, 15, 'có', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', '2025-10-20 17:34:53'),
(213, 15, '2025-10-22 06:30', NULL, '2025-10-20 17:35:05'),
(214, 15, '2025-10-22 06:30', 'Đã đặt lịch tư vấn lúc 2025-10-22 06:30:00. Nhân viên sẽ xác nhận sớm! Bạn cần thêm gì nữa không?', '2025-10-20 17:35:05'),
(215, 15, 'tôi muốn mua sữa rửa mặt', NULL, '2025-10-20 17:35:18'),
(216, 15, 'tôi muốn mua sữa rửa mặt', 'Bạn có thể tham khảo: Sữa Rửa Mặt LUMOS PERFECT PEARL CLEANSER Cân bằng độ PH , Bộ Kem dưỡng ẩm & làm sáng LAGUMI . Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để chọn thời gian.', '2025-10-20 17:35:18'),
(217, 15, 'tôi muốn mua kem chống nắng', NULL, '2025-10-20 17:35:31'),
(218, 15, 'tôi muốn mua kem chống nắng', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Bộ Kem dưỡng ẩm & làm sáng LAGUMI , Kem chống nắng kiềm dầu LUMOS SUN CREAM. Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để chọn thời gian.', '2025-10-20 17:35:31'),
(219, 16, 'tôi đang muốn mua son', NULL, '2025-10-20 17:36:47'),
(220, 16, 'tôi đang muốn mua son', 'Bạn có thể tham khảo: Kẻ mắt SACE LADY, Son Môi gecomo Chất Nhung Lì Chống Thấm Nước , COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da, Son Dưỡng Di.or Collagen Addict Lip Maximizer, Son dưỡng Dior Addict Lip Glow SELENA STORE. Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để chọn thời gian.', '2025-10-20 17:36:47'),
(221, 16, 'tôi muốn có người tâm sự', NULL, '2025-10-20 17:37:11'),
(222, 16, 'tôi muốn có người tâm sự', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', '2025-10-20 17:37:11'),
(223, 16, 'tôi đang tìm 1 người để nói chuyện', NULL, '2025-10-20 17:37:22'),
(224, 16, 'tôi đang tìm 1 người để nói chuyện', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', '2025-10-20 17:37:22'),
(225, 16, 'tôi muốn nói chuyện với chủ shop', NULL, '2025-10-20 17:37:29'),
(226, 16, 'tôi muốn nói chuyện với chủ shop', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', '2025-10-20 17:37:29'),
(227, 16, 'hello', NULL, '2025-10-20 17:37:36'),
(228, 16, 'hello', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', '2025-10-20 17:37:36'),
(229, 16, 'how are you today', NULL, '2025-10-20 17:37:52'),
(230, 16, 'how are you today', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', '2025-10-20 17:37:52'),
(231, 16, 'ship', NULL, '2025-10-20 17:53:57'),
(232, 16, 'ship', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', '2025-10-20 17:53:57'),
(233, 16, 'bảo hành', NULL, '2025-10-20 17:54:02'),
(234, 16, 'bảo hành', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', '2025-10-20 17:54:02'),
(235, 16, 'hello', NULL, '2025-10-20 17:57:50'),
(236, 16, 'hello', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', '2025-10-20 17:57:50'),
(237, 16, 'how are you today', NULL, '2025-10-20 17:57:58'),
(238, 16, 'how are you today', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', '2025-10-20 17:57:58'),
(239, 16, 'how are you', NULL, '2025-10-20 17:58:18'),
(240, 16, 'how are you', 'Please enter time as YYYY-MM-DD HH:MM', '2025-10-20 17:58:18'),
(241, 16, 'i want look son moi', NULL, '2025-10-20 17:58:26'),
(242, 16, 'i want look son moi', 'Bạn có thể tham khảo: Kẻ mắt SACE LADY, Son Môi gecomo Chất Nhung Lì Chống Thấm Nước , COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da. Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để tiếp tục.', '2025-10-20 17:58:26'),
(243, 16, 'can you speak english', NULL, '2025-10-20 17:58:40'),
(244, 16, 'can you speak english', 'Sorry, the system is busy. Please try again in a moment.', '2025-10-20 17:58:45'),
(245, 16, 'hello', NULL, '2025-10-23 15:29:03'),
(246, 16, 'hello', 'Chào huy2! Bạn đang tìm sản phẩm gì ạ?', '2025-10-23 15:29:03'),
(247, 16, 'hello', NULL, '2025-10-23 15:29:15'),
(248, 16, 'hello', 'Chào huy2! Bạn đang tìm sản phẩm gì ạ?', '2025-10-23 15:29:15'),
(249, 16, 'xin chào tôi đang muốn tìm son', NULL, '2025-10-23 15:29:28'),
(250, 16, 'xin chào tôi đang muốn tìm son', 'Chào huy2! Bạn đang tìm sản phẩm gì ạ?', '2025-10-23 15:29:28'),
(251, 17, 'tôi đang tìm nước tẩy trang', NULL, '2025-10-26 16:22:17'),
(252, 17, 'tôi đang tìm nước tẩy trang', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', '2025-10-26 16:22:19'),
(253, 17, 'tôi muốn tìm son môi', NULL, '2025-10-26 16:22:33'),
(254, 17, 'tôi muốn tìm son môi', 'Bạn có thể tham khảo: Kẻ mắt SACE LADY, Son Môi gecomo Chất Nhung Lì Chống Thấm Nước , COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da. Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để tiếp tục.', '2025-10-26 16:22:33'),
(255, 17, 'nước tẩy trang thì sao', NULL, '2025-10-26 16:22:46'),
(256, 17, 'nước tẩy trang thì sao', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', '2025-10-26 16:22:48'),
(257, 17, 'hello, can you help me', NULL, '2025-10-30 19:09:17'),
(258, 17, 'hello, can you help me', 'Chào huy3! Bạn đang tìm sản phẩm gì ạ?', '2025-10-30 19:09:17'),
(259, 17, 'xin chào', NULL, '2025-10-30 19:09:53'),
(260, 17, 'xin chào', 'Chào huy3! Bạn đang tìm sản phẩm gì ạ?', '2025-10-30 19:09:53'),
(261, 17, 'mình muốn mua son môi', NULL, '2025-10-30 19:10:03'),
(262, 17, 'mình muốn mua son môi', 'Bạn có thể tham khảo: Kẻ mắt SACE LADY, Son Môi gecomo Chất Nhung Lì Chống Thấm Nước , COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da. Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để tiếp tục.', '2025-10-30 19:10:03'),
(263, 17, 'tôi muốn mua son', NULL, '2025-10-31 18:21:03'),
(264, 17, 'tôi muốn mua son', 'Bạn có thể tham khảo: Kẻ mắt SACE LADY, Son Môi gecomo Chất Nhung Lì Chống Thấm Nước , COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da. Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để tiếp tục.', '2025-10-31 18:21:03'),
(265, 17, 'tôi muốn mua son', NULL, '2025-10-31 18:21:13'),
(266, 17, 'tôi muốn mua son', 'Bạn có thể tham khảo: Kẻ mắt SACE LADY, Son Môi gecomo Chất Nhung Lì Chống Thấm Nước , COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da. Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để tiếp tục.', '2025-10-31 18:21:13'),
(267, 17, 'tôi muốn mua son', NULL, '2025-10-31 18:23:50'),
(268, 17, 'tôi muốn mua son', 'Bạn có thể tham khảo: Kẻ mắt SACE LADY, Son Môi gecomo Chất Nhung Lì Chống Thấm Nước , COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da. Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để tiếp tục.', '2025-10-31 18:23:50'),
(269, NULL, 'xin chào', 'Xin chào! Bạn cần tư vấn về mỹ phẩm không? Tôi sẵn sàng giúp đỡ.', '2025-11-02 06:33:20'),
(270, NULL, 'tôi muốn mua son môi', 'Bạn muốn mua son môi. Tôi có một số gợi ý phù hợp:\n\n- Son Môi gecomo Chất Nhung Lì Chống Thấm Nước – OLAY – Sale – 159000 VND\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer – GUCCI – Son Dưỡng – 100000 VND\n\nHãy cho tôi biết thêm thông tin về sở thích và yêu cầu của bạn để tôi có thể gợi ý tốt hơn!', '2025-11-02 06:33:32'),
(271, NULL, 'tôi muốn mua son dưỡng', 'Bạn muốn mua son dưỡng. Tôi thấy có 2 sản phẩm phù hợp: \n\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer – GUCCI – Son Dưỡng – 1.000.000 VND\n- Son dưỡng Dior Addict Lip Glow – SELENA STORE – Son Dưỡng – 950.000 VND\n\nHãy cho tôi biết thêm thông tin để giúp bạn chọn sản phẩm phù hợp!', '2025-11-02 06:33:57'),
(272, NULL, 'tôi cần đặt lịch tư vấn', 'Xin chào! Tôi sẵn sàng giúp bạn đặt lịch tư vấn. Để tôi hiểu rõ hơn về nhu cầu của bạn, vui lòng cho tôi biết bạn đang tìm kiếm sản phẩm gì hoặc muốn tư vấn về chủ đề nào?', '2025-11-02 06:34:15'),
(273, NULL, 'xin chào', 'Xin chào! Bạn cần tư vấn về mỹ phẩm không? Tôi sẵn sàng giúp đỡ.', '2025-11-02 07:03:27'),
(274, NULL, 'tôi muốn mua hàng', 'Bạn muốn mua hàng gì vậy? Bạn có thể chọn một số sản phẩm sau đây:\n\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer - GUCCI - Son Dưỡng - 100000 VND\n- Kem nền Catrice HD Liquid Coverage Foundation - NIVEA - Kem Nền - 335000 VND\n\nHãy cho tôi biết bạn cần gì khác nhé!', '2025-11-02 07:03:36'),
(275, NULL, 'tôi muốn mua hàng', 'Xin chào! Bạn muốn mua hàng gì?\n\nBạn có thể xem qua danh sách sản phẩm của chúng tôi:\n\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer | GUCCI | Son Dưỡng | 100000 VND\n- Son Môi gecomo Chất Nhung Lì Chống Thấm Nước  | OLAY | Sale | 159000 VND\n- Son dưỡng Dior Addict Lip Glow SELENA STORE | Dior  | Son Dưỡng | 950000 VND\n- Kẻ mắt SACE LADY | INNISFREE | Son Dưỡng | 399000 VND\n- Kem nền Catrice HD Liquid Coverage Foundation | NIVEA | Kem Nền | 335000 VND\n\nBạn có thể cho tôi biết danh mục hoặc thương hiệu sản phẩm bạn quan tâm?', '2025-11-02 07:30:13'),
(276, NULL, 'tôi muốn mua son môi', 'Bạn muốn mua son môi. Tôi đề xuất 2 sản phẩm phù hợp:\n\n- Son Môi gecomo Chất Nhung Lì Chống Thấm Nước – OLAY – Sale – 159000 VND\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer – GUCCI – Son Dưỡng – 100000 VND\n\nNếu bạn muốn thêm thông tin về sản phẩm, vui lòng cho tôi biết.', '2025-11-02 07:30:53'),
(277, NULL, 'TÔI MUỐN MUA SON', 'Bạn muốn mua son. Có 3 sản phẩm phù hợp:\n\n- Son dưỡng Dior Addict Lip Glow - Brand: Dior - Category: Son Dưỡng - Price: 950000 VND\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer - Brand: GUCCI - Category: Son Dưỡng - Price: 100000 VND\n- Kẻ mắt SACE LADY - Brand: INNISFREE - Category: Son Dưỡng - Price: 399000 VND', '2025-11-02 07:45:13'),
(278, NULL, 'tôi muốn mua son', 'Bạn muốn mua son. Tôi có một số gợi ý sau:\n\n- Son dưỡng Dior Addict Lip Glow - Dior - Son Dưỡng - 950000 VND\n- Son Môi gecomo Chất Nhung Lì Chống Thấm Nước - OLAY - Sale - 159000 VND\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer - GUCCI - Son Dưỡng - 100000 VND\n\nNếu bạn muốn mua son dưỡng, có thể chọn sản phẩm đầu tiên hoặc thứ hai.', '2025-11-02 08:12:56'),
(279, NULL, 'tôi muốn mua son môi bình thường, không phải son dưỡng', 'Bạn muốn mua son môi bình thường. Tôi thấy có một số sản phẩm phù hợp như sau:\n\n- Son Môi gecomo Chất Nhung Lì Chống Thấm Nước  - OLAY - Sale - 159000 VND\nTôi chưa có thông tin về các sản phẩm khác trong danh mục \"Son Môi\". Bạn vui lòng cho tôi biết thêm chi tiết để tôi có thể hỗ trợ bạn tốt hơn.', '2025-11-02 08:16:21'),
(280, NULL, 'tôi muốn đặt lịch tư vấn', 'Xin chào! Tôi sẵn sàng giúp bạn đặt lịch tư vấn. Để tôi hiểu rõ hơn về nhu cầu của bạn, vui lòng cho tôi biết bạn đang tìm kiếm sản phẩm gì hoặc có bất kỳ yêu cầu cụ thể nào không?', '2025-11-02 08:24:58'),
(281, NULL, 'tôi muốn mua sữa rửa mặt nhưng không biết loại nào phù hợp, hãy giúp tôi đặt lịch tư vấn về sản phẩm nhé', 'Để giúp bạn chọn được sữa rửa mặt phù hợp, tôi cần một số thông tin về làn da của bạn. Bạn có thể cho tôi biết loại da của mình là gì (da dầu, da khô, da hỗn hợp,...) và liệu bạn có bất kỳ vấn đề nào khác với da không?', '2025-11-02 08:25:45'),
(282, NULL, 'da tôi bị nhạy cmar', 'Tôi hiểu rằng bạn đang gặp vấn đề với da nhạy cảm. Để giúp bạn tìm được sản phẩm phù hợp, tôi cần biết thêm thông tin về loại da của bạn (da dầu, da khô, da hỗn hợp,...) và các vấn đề cụ thể mà bạn muốn giải quyết (chống nắng, dưỡng ẩm, làm dịu da,...).', '2025-11-02 08:26:03'),
(283, NULL, 'dưỡng ẩm', 'Bạn muốn tìm sản phẩm dưỡng ẩm. Tôi có một số gợi ý như sau:\n\n- LAGUMI – NIVEA – Son Dưỡng – 139000 VND\n- Di.or Collagen Addict Lip Maximizer – GUCCI – Son Dưỡng – 100000 VND\n\nNếu bạn cần thêm thông tin, vui lòng cho tôi biết!', '2025-11-02 08:26:25'),
(284, NULL, 'sữa rửa mặt không phải son môi', 'Người dùng đang tìm sữa rửa mặt. Tôi sẽ đề xuất một sản phẩm phù hợp.\n\nLUMOS PERFECT PEARL CLEANSER - SIMPLE - Gel Rửa Mặt - 819000 VND\n\nNếu bạn muốn thêm thông tin hoặc có yêu cầu khác, vui lòng cho tôi biết!', '2025-11-02 08:26:47'),
(285, NULL, 'mình đang muốn tìm son môi', 'Bạn đang tìm kiếm son môi phù hợp. Dựa trên dữ liệu sản phẩm hiện có, tôi đề xuất 2 sản phẩm sau:\n\n- Son Môi gecomo Chất Nhung Lì Chống Thấm Nước - OLAY - Sale - 159000 VND\n- Son dưỡng Di.or Collagen Addict Lip Maximizer - GUCCI - Son Dưỡng - 100000 VND\n\nNếu bạn có thêm yêu cầu hoặc thông tin cụ thể về loại son môi bạn muốn, tôi sẽ cố gắng hỗ trợ tốt hơn.\n\nBạn có muốn đặt lịch tư vấn không? (Có/Không)', '2025-11-02 09:58:15'),
(286, NULL, 'không', 'Cảm ơn bạn! Nếu cần thêm gì cứ nhắn mình nhé.', '2025-11-02 09:58:25'),
(287, NULL, 'có', 'Cảm ơn bạn, bên em sẽ có nhân viên gọi điện để hỗ trợ mình về sản phẩm.', '2025-11-02 09:58:29'),
(288, NULL, 'mình cần cung cấp thông tin gì', 'Để cung cấp thông tin cho người dùng, tôi cần biết yêu cầu cụ thể của họ. Bạn muốn tìm kiếm sản phẩm gì hoặc có yêu cầu đặc biệt không?\n\nBạn có muốn đặt lịch tư vấn không? (Có/Không)', '2025-11-02 09:58:46'),
(289, 584018126, 'tôi đang muốn mua son môi', 'Bạn đang tìm mua son môi. Tôi thấy có hai sản phẩm son môi phù hợp với nhu cầu của bạn:\n\n- Son Môi gecomo Chất Nhung Lì Chống Thấm Nước – OLAY – Sale – 159000 VND\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer – GUCCI – Son Dưỡng – 100000 VND\n\nNếu bạn muốn thêm thông tin, vui lòng cho tôi biết!\n\nBạn có muốn đặt lịch tư vấn không? (Có/Không)', '2025-11-02 18:44:37'),
(290, 584018126, 'tôi không', 'Cảm ơn bạn! Nếu cần thêm gì cứ nhắn mình nhé.', '2025-11-02 18:44:54'),
(291, 584018126, 'tôi muốn tư vấn', 'Cảm ơn bạn, bên em sẽ có nhân viên gọi điện để hỗ trợ mình về sản phẩm.', '2025-11-02 18:44:59'),
(292, NULL, 'tôi muốn mua hàng', 'Xin chào! Bạn muốn mua hàng gì? Tôi có thể giúp bạn tìm kiếm sản phẩm phù hợp.\n\nBạn quan tâm đến loại mỹ phẩm nào? Son dưỡng, kem nền hay kẻ mắt? Hãy cho tôi biết để tôi có thể đề xuất một số lựa chọn phù hợp.\n\nBạn có muốn đặt lịch tư vấn không? (Có/Không)', '2025-11-02 18:45:25'),
(293, NULL, 'tôi muốn mua son môi', 'Bạn muốn mua son môi. Tôi có một số gợi ý cho bạn:\n\n- Son Môi gecomo Chất Nhung Lì Chống Thấm Nước - OLAY - Sale - 159000 VND\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer - GUCCI - Son Dưỡng - 100000 VND\n\nBạn có muốn biết thêm thông tin về sản phẩm nào không?\n\nBạn có muốn đặt lịch tư vấn không? (Có/Không)', '2025-11-02 18:45:52'),
(294, 584018126, 'tôi muốn mua son', 'Bạn muốn mua son. Có một số lựa chọn cho bạn:\n\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer – GUCCI – Son Dưỡng – 100000 VND\n- Son dưỡng Dior Addict Lip Glow – SELENA STORE – Son Dưỡng – 950000 VND\n\nHãy cho tôi biết thêm thông tin về sở thích hoặc yêu cầu cụ thể của bạn để tôi có thể đề xuất sản phẩm phù hợp hơn.\n\nBạn có muốn đặt lịch tư vấn không? (Có/Không)', '2025-11-02 18:49:09'),
(295, 584018126, 'tôi muốn mua son', 'Bạn muốn mua son. Tôi có một số gợi ý cho bạn:\n\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer – GUCCI – Son Dưỡng – 100000 VND\n- Son dưỡng Dior Addict Lip Glow – SELENA STORE – Son Dưỡng – 950000 VND\n\nNếu bạn muốn mua son chống thấm nước, tôi chưa có thông tin chính xác. Bạn vui lòng mô tả thêm chi tiết về sản phẩm bạn đang tìm kiếm.\n\nBạn có muốn đặt lịch tư vấn không? (Có/Không)', '2025-11-02 19:14:47'),
(296, 584018126, 'tôi muốn mua son dưỡng', 'Bạn muốn mua son dưỡng. Tôi thấy có 2 sản phẩm phù hợp: \n\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer – GUCCI – Son Dưỡng – 100000 VND\n- Son dưỡng Dior Addict Lip Glow – SELENA STORE – Son Dưỡng – 950000 VND\n\nHãy cho tôi biết thêm thông tin nếu bạn muốn sản phẩm khác.\n\nBạn có muốn đặt lịch tư vấn không? (Có/Không)', '2025-11-02 19:16:03'),
(297, 584018126, 'có', 'Xin chào! Tôi sẵn sàng giúp bạn tìm kiếm sản phẩm mỹ phẩm phù hợp.\n\nBạn muốn mua loại son dưỡng nào?\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', '2025-11-02 19:38:22'),
(298, 584018126, '03/11/2025 19h00', 'Xin chào! Tôi sẵn sàng giúp bạn tìm kiếm sản phẩm mỹ phẩm phù hợp.\n\nBạn đang tìm kiếm gì hôm nay? Bạn muốn mua son dưỡng, kem nền, mặt nạ hoặc kem chống nắng? Hãy cho tôi biết để tôi có thể gợi ý cho bạn.\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', '2025-11-02 19:38:50'),
(299, 584018126, 'xin chào', 'Xin chào! Bạn cần tìm sản phẩm gì hôm nay?\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', '2025-11-02 19:43:29'),
(300, 584018126, 'tôi muốn mua son', 'Bạn muốn mua son. Có một số lựa chọn phù hợp:\n\n- Son dưỡng Dior Addict Lip Glow - Dior - Son Dưỡng - 950000 VND\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer - GUCCI - Son Dưỡng - 100000 VND\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', '2025-11-02 19:43:46'),
(301, 584018126, 'son dưỡng', 'Người dùng muốn biết son dưỡng. Tôi thấy có 3 sản phẩm thuộc danh mục Son Dưỡng trong dữ liệu.\n\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer – GUCCI – Son Dưỡng – 100000 VND\n- Kẻ mắt SACE LADY – INNISFREE – Son Dưỡng – 399000 VND\n- Son dưỡng Dior Addict Lip Glow – SELENA STORE – Son Dưỡng – 950000 VND\n\nNếu bạn muốn biết thêm thông tin, vui lòng cho tôi biết!\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', '2025-11-02 19:44:10'),
(302, 584018126, 'ngày 03/11/2025 19:30', 'Cảm ơn bạn! Lịch tư vấn của bạn đã được đặt vào **03/11/2025 19:30**. Nhân viên chúng tôi sẽ gọi cho bạn đúng giờ. Vui lòng để ý điện thoại cá nhân trong khoảng thời gian đặt lịch. Cảm ơn bạn.', '2025-11-02 19:44:12'),
(303, 584018126, 'tôi muốn mua son', 'Bạn muốn mua son. Dưới đây là một số gợi ý:\n\n- Son dưỡng Dior Addict Lip Glow – Dior – Son Dưỡng – 950000 VND\n- Son Môi gecomo Chất Nhung Lì Chống Thấm Nước – OLAY – Sale – 159000 VND\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer – GUCCI – Son Dưỡng – 100000 VND\n\nTôi chưa có thông tin về loại son bạn muốn mua. Bạn vui lòng cho tôi biết thêm chi tiết để tôi có thể hỗ trợ tốt hơn!\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', '2025-11-03 18:20:40'),
(304, 584018126, 'có', 'Xin chào! Bạn muốn tìm sản phẩm gì?\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', '2025-11-03 18:20:52'),
(305, 584018126, '04/11/2025 19:30', 'Cảm ơn bạn! Lịch tư vấn của bạn đã được đặt vào **04/11/2025 19:30**. Nhân viên chúng tôi sẽ gọi cho bạn đúng giờ. Vui lòng để ý điện thoại cá nhân trong khoảng thời gian đặt lịch. Cảm ơn bạn.', '2025-11-03 18:21:01'),
(306, 584018126, 'tôi muốn mua sữa rửa mặt', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', '2025-11-03 18:43:01'),
(307, 584018126, '05/11/2025 19:30', 'Cảm ơn bạn! Lịch tư vấn của bạn đã được đặt vào **05/11/2025 19:30**. Nhân viên chúng tôi sẽ gọi cho bạn đúng giờ. Vui lòng để ý điện thoại cá nhân trong khoảng thời gian đặt lịch. Cảm ơn bạn.', '2025-11-03 18:43:30'),
(308, 584018126, 'tôi muốn mua kem dưỡng da', 'Tôi có thể gợi ý một số sản phẩm kem dưỡng da cho bạn. Tuy nhiên, tôi không thấy thông tin về các sản phẩm này trong danh sách.\n\nCó thể bạn muốn xem: \n- Kem dưỡng da của thương hiệu nào khác?\n- Bạn đang tìm kiếm loại kem dưỡng da nào (chống nắng, không chống nắng,...)?\n\nHãy cho tôi biết thêm thông tin để tôi có thể giúp đỡ bạn tốt hơn!\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', '2025-11-03 18:48:41'),
(309, 584018126, 'kem chống nắng', 'Bạn có thể tham khảo các sản phẩm chống nắng sau:\n\n- Kem chống nắng kiềm dầu LUMOS SUN CREAM - DOVE - Kem Chống Nắng - 89.000 VND\n- Kem chống nắng dạng nước Catrice - CATRICE - Kem Chống Nắng (chưa rõ giá)\n\nHãy cho tôi biết bạn cần thêm thông tin gì nhé!\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', '2025-11-03 18:49:19'),
(310, 584018126, 'tôi muốn mua son môi', 'Tôi có thể giúp bạn chọn một sản phẩm son môi phù hợp!\n\nBạn có muốn mua son lì hoặc son dưỡng không?\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', '2025-11-03 18:58:38'),
(311, 584018126, 'tôi muốn mua son lì', 'Nếu bạn muốn mua son lì, có thể tham khảo các sản phẩm sau:\n\n- Son Môi gecomo Chất Nhung Lì Chống Thấm Nước - OLAY - Sale - 159000 VND\n- (Chưa có thông tin về các sản phẩm khác trong danh mục \"Son Lì\", bạn cần cung cấp thêm thông tin hoặc danh sách cụ thể hơn để tôi có thể hỗ trợ tốt hơn.)\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', '2025-11-03 19:00:13'),
(312, 584018126, 'tôi muốn mua nước hoa', 'Tôi có thể gợi ý một số sản phẩm nước hoa cho bạn. Tuy nhiên, tôi cần biết thương hiệu yêu thích của bạn để đưa ra những lựa chọn phù hợp.\n\nNếu bạn muốn mua nước hoa Dior, có thể tham khảo:\n\n- Sau Khi Mưa - Dior - Nước Hoa - 2.990.000 VND\n- J\'Adore - Dior - Nước Hoa - 3.490.000 VND\n\nHoặc nếu bạn quan tâm đến thương hiệu khác, vui lòng cho tôi biết để được tư vấn thêm!\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', '2025-11-03 19:04:56'),
(313, 584018126, '2 sản phẩm đó đâu có trong danh mục sản phẩm của bạn ?', 'Tôi xin lỗi vì không có thông tin về 2 sản phẩm mà bạn đang tìm kiếm. Để giúp tôi trả lời chính xác hơn, vui lòng cho tôi biết tên của 2 sản phẩm đó là gì?\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', '2025-11-03 19:10:20'),
(314, 584018126, 'tôi cần mua nước hoa, hãy giúp tôi tư vấn nhé', 'Bạn muốn đặt lịch tư vấn lúc nào ạ? Vui lòng trả lời theo định dạng **DD/MM/YYYY HH:MM** (ví dụ: 05/11/2025 14:30).', '2025-11-03 19:10:35'),
(315, 584018126, '05/11/2025 20:00', 'Cảm ơn bạn! Lịch tư vấn của bạn đã được đặt vào **05/11/2025 20:00**. Nhân viên chúng tôi sẽ gọi cho bạn đúng giờ. Vui lòng để ý điện thoại cá nhân trong khoảng thời gian đặt lịch. Cảm ơn bạn.', '2025-11-03 19:10:46'),
(316, 584018126, 'cảm ơn bạn', 'Không có vấn đề gì! Bạn cần tìm sản phẩm nào?\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', '2025-11-03 19:10:53'),
(317, 584018126, 'tôi muốn mua son dưỡng', 'Tôi có thể gợi ý cho bạn một số sản phẩm son dưỡng như sau:\n\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer - GUCCI - Son Dưỡng - 100000 VND\n- Son dưỡng Dior Addict Lip Glow - SELENA STORE - Son Dưỡng - 950000 VND\n\nNếu bạn cần thêm thông tin hoặc muốn biết thêm các sản phẩm khác, vui lòng cho tôi biết!\n\nBạn có muốn **đặt lịch để nhân viên bên mình tư vấn cho bạn không?** (Có/Không)\nNếu **Có**, bạn có thể **chọn ngày/giờ** ngay tại đây hoặc nhập theo mẫu **DD/MM/YYYY HH:MM**.\n[[BOOKING_FORM]]', '2025-11-03 21:19:13'),
(318, 584018126, 'tôi muốn mua son', 'Xin lỗi, mô hình đang bận. Vui lòng thử lại sau.\n\nBạn có muốn **đặt lịch để nhân viên bên mình tư vấn cho bạn không?** (Có/Không)', '2025-11-05 21:29:22'),
(319, 584018126, 'tôi muốn mua son môi', 'Bạn muốn đặt lịch tư vấn vào thời gian nào ạ? Bạn hãy chọn ở **biểu mẫu dưới đây** hoặc nhập theo mẫu **DD/MM/YYYY HH:MM** (ví dụ: 05/11/2025 14:30).\n[[BOOKING_FORM]]', '2025-11-05 21:35:10'),
(320, 584018126, 'tôi muốn mua hàng', 'Bạn muốn đặt lịch tư vấn vào thời gian nào ạ? Bạn hãy chọn ở **biểu mẫu dưới đây** hoặc nhập theo mẫu **DD/MM/YYYY HH:MM** (ví dụ: 05/11/2025 14:30).\n[[BOOKING_FORM]]', '2025-11-05 21:43:22'),
(321, 584018126, 'tư vấn cho tôi son môi', 'Bạn muốn đặt lịch tư vấn vào thời gian nào ạ? Bạn hãy chọn ở **biểu mẫu dưới đây** hoặc nhập theo mẫu **DD/MM/YYYY HH:MM** (ví dụ: 05/11/2025 14:30).\n[[BOOKING_FORM]]', '2025-11-05 21:43:32'),
(322, 584018126, 'mình muốn mua son môi', 'Xin lỗi, mô hình đang bận. Vui lòng thử lại sau.', '2025-11-05 22:12:18'),
(323, 584018126, 'tôi muốn mua son môi', 'Xin lỗi, mô hình đang bận. Vui lòng thử lại sau.', '2025-11-08 06:00:45');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(64) DEFAULT NULL,
  `message` text NOT NULL,
  `direction` enum('user','bot') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `user_id`, `session_id`, `message`, `direction`, `created_at`) VALUES
(1, NULL, 'yo9b6scu231760348606010', 'xin chào', 'user', '2025-10-13 09:43:43'),
(2, NULL, 'yo9b6scu231760348606010', 'tôi có thể mua hàng', 'user', '2025-10-13 09:43:49'),
(3, NULL, 'yo9b6scu231760348606010', 'tôi muốn mua hàng', 'user', '2025-10-13 09:43:53'),
(4, NULL, 'yo9b6scu231760348606010', 'tối muốn mua hàng số lượng lớn', 'user', '2025-10-13 09:43:58'),
(5, NULL, 'yo9b6scu231760348606010', 'văn trọng huy', 'user', '2025-10-13 09:44:08'),
(6, NULL, 'yo9b6scu231760348606010', '0325324064', 'user', '2025-10-13 09:44:12'),
(7, NULL, 'yo9b6scu231760348606010', 'xin chào', 'user', '2025-10-13 09:47:51'),
(8, NULL, 'yo9b6scu231760348606010', 'tôi muốn mua hàng', 'user', '2025-10-13 09:47:54'),
(9, NULL, 'yo9b6scu231760348606010', 'tôi muốn mua hàng', 'user', '2025-10-13 09:53:03'),
(10, NULL, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi và sẽ hỗ trợ thêm. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-13 09:53:03'),
(11, NULL, 'yo9b6scu231760348606010', 'tôi muốn mua hàng', 'user', '2025-10-13 09:53:04'),
(12, NULL, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi và sẽ hỗ trợ thêm. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-13 09:53:04'),
(13, NULL, 'yo9b6scu231760348606010', 'tôi muốn mua hnagf', 'user', '2025-10-13 09:53:13'),
(14, NULL, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi và sẽ hỗ trợ thêm. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-13 09:53:13'),
(15, NULL, 'yo9b6scu231760348606010', 'vantronghuy223@gmail.com', 'user', '2025-10-13 09:53:30'),
(16, NULL, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi và sẽ hỗ trợ thêm. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-13 09:53:30'),
(17, NULL, 'yo9b6scu231760348606010', '0325324064', 'user', '2025-10-13 09:53:34'),
(18, NULL, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi và sẽ hỗ trợ thêm. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-13 09:53:34'),
(19, NULL, 'yo9b6scu231760348606010', 'tôi muốn mua son', 'user', '2025-10-13 09:53:42'),
(20, NULL, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi và sẽ hỗ trợ thêm. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-13 09:53:42'),
(21, NULL, 'yo9b6scu231760348606010', 'tôi muốn mua hàng', 'user', '2025-10-13 09:57:28'),
(22, NULL, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi và sẽ hỗ trợ thêm. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-13 09:57:28'),
(23, NULL, 'yo9b6scu231760348606010', '0325324064', 'user', '2025-10-13 09:57:32'),
(24, NULL, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi và sẽ hỗ trợ thêm. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-13 09:57:32'),
(25, NULL, 'yo9b6scu231760348606010', 'văn trọng huy', 'user', '2025-10-13 09:57:36'),
(26, NULL, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi và sẽ hỗ trợ thêm. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-13 09:57:36'),
(27, NULL, 'yo9b6scu231760348606010', '0325324064', 'user', '2025-10-13 10:00:21'),
(28, NULL, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi và sẽ hỗ trợ thêm. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-13 10:00:21'),
(29, 13, 'yo9b6scu231760348606010', '0325324064', 'user', '2025-10-13 10:02:16'),
(30, 13, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi và sẽ hỗ trợ thêm. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-13 10:02:16'),
(31, 13, 'yo9b6scu231760348606010', 'tôi muốn mua hàng', 'user', '2025-10-13 10:02:18'),
(32, 13, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi và sẽ hỗ trợ thêm. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-13 10:02:18'),
(33, 13, 'yo9b6scu231760348606010', 'xin chào', 'user', '2025-10-13 10:02:26'),
(34, 13, 'yo9b6scu231760348606010', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', 'bot', '2025-10-13 10:02:26'),
(35, 13, 'yo9b6scu231760348606010', 'tôi muốn mua hàng', 'user', '2025-10-13 10:02:28'),
(36, 13, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi và sẽ hỗ trợ thêm. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-13 10:02:28'),
(37, 13, 'yo9b6scu231760348606010', 'xin chào', 'user', '2025-10-13 10:06:36'),
(38, 13, 'yo9b6scu231760348606010', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', 'bot', '2025-10-13 10:06:36'),
(39, 13, 'yo9b6scu231760348606010', 'xin chào', 'user', '2025-10-13 10:06:41'),
(40, 13, 'yo9b6scu231760348606010', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', 'bot', '2025-10-13 10:06:41'),
(41, 13, 'yo9b6scu231760348606010', 'tôi muốn mua hàng', 'user', '2025-10-13 10:06:48'),
(42, 13, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi và sẽ hỗ trợ thêm. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-13 10:06:48'),
(43, 13, 'yo9b6scu231760348606010', 'tôi muốn mua hàng', 'user', '2025-10-13 10:06:54'),
(44, 13, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi và sẽ hỗ trợ thêm. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-13 10:06:54'),
(45, 13, 'yo9b6scu231760348606010', 'xin chào', 'user', '2025-10-13 17:36:07'),
(46, 13, 'yo9b6scu231760348606010', 'Hello! How can I help with products, pricing, or delivery?', 'bot', '2025-10-13 17:36:07'),
(47, 13, 'yo9b6scu231760348606010', 'tôi muốn mua hàng', 'user', '2025-10-13 17:36:18'),
(48, 13, 'yo9b6scu231760348606010', 'Thanks! I have recorded your question. Please leave email/phone for faster support.', 'bot', '2025-10-13 17:36:18'),
(49, 13, 'yo9b6scu231760348606010', 'hãy tư vấn cho tôi', 'user', '2025-10-13 17:36:21'),
(50, 13, 'yo9b6scu231760348606010', 'You can check: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Are you looking for oily, dry, or sensitive skin products?', 'bot', '2025-10-13 17:36:21'),
(51, 13, 'yo9b6scu231760348606010', 'bạn có thể nói tiếng Việt không ?', 'user', '2025-10-13 17:38:01'),
(52, 13, 'yo9b6scu231760348606010', 'Thanks! I have recorded your question. Please leave email/phone for faster support.', 'bot', '2025-10-13 17:38:01'),
(53, 13, 'yo9b6scu231760348606010', 'Vie', 'user', '2025-10-13 17:38:09'),
(54, 13, 'yo9b6scu231760348606010', 'Thanks! I have recorded your question. Please leave email/phone for faster support.', 'bot', '2025-10-13 17:38:09'),
(55, 13, 'yo9b6scu231760348606010', 'Vietnamese', 'user', '2025-10-13 17:38:15'),
(56, 13, 'yo9b6scu231760348606010', 'Thanks! I have recorded your question. Please leave email/phone for faster support.', 'bot', '2025-10-13 17:38:15'),
(57, 13, 'yo9b6scu231760348606010', 'tôi muốn mua son', 'user', '2025-10-13 17:38:23'),
(58, 13, 'yo9b6scu231760348606010', 'Thanks! I have recorded your question. Please leave email/phone for faster support.', 'bot', '2025-10-13 17:38:23'),
(59, 13, 'yo9b6scu231760348606010', 'xin chào', 'user', '2025-10-13 17:45:59'),
(60, 13, 'yo9b6scu231760348606010', 'Hello! How can I help with products, pricing, or delivery?', 'bot', '2025-10-13 17:45:59'),
(61, 13, 'yo9b6scu231760348606010', 'ship', 'user', '2025-10-13 17:49:31'),
(62, 13, 'yo9b6scu231760348606010', 'Hello! How can I help with products, pricing, or delivery?', 'bot', '2025-10-13 17:49:31'),
(63, 13, 'yo9b6scu231760348606010', 'price', 'user', '2025-10-13 17:49:42'),
(64, 13, 'yo9b6scu231760348606010', 'Prices are shown on product pages. Which product are you interested in?', 'bot', '2025-10-13 17:49:42'),
(65, 13, 'yo9b6scu231760348606010', 'what ship', 'user', '2025-10-13 17:49:58'),
(66, 13, 'yo9b6scu231760348606010', 'Hello! How can I help with products, pricing, or delivery?', 'bot', '2025-10-13 17:49:58'),
(67, 13, 'yo9b6scu231760348606010', 'ship', 'user', '2025-10-13 17:50:04'),
(68, 13, 'yo9b6scu231760348606010', 'Hello! How can I help with products, pricing, or delivery?', 'bot', '2025-10-13 17:50:04'),
(69, 13, 'yo9b6scu231760348606010', 'suggest', 'user', '2025-10-13 17:50:12'),
(70, 13, 'yo9b6scu231760348606010', 'Thanks! I have recorded your question. Please leave email/phone for faster support.', 'bot', '2025-10-13 17:50:12'),
(71, 13, 'yo9b6scu231760348606010', 'hi', 'user', '2025-10-13 17:53:22'),
(72, 13, 'yo9b6scu231760348606010', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', 'bot', '2025-10-13 17:53:22'),
(73, 13, 'yo9b6scu231760348606010', 'tôi muốn mua hàng', 'user', '2025-10-13 17:53:29'),
(74, 13, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-13 17:53:29'),
(75, 13, 'yo9b6scu231760348606010', '0325324064', 'user', '2025-10-13 17:53:35'),
(76, 13, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-13 17:53:35'),
(77, 13, 'yo9b6scu231760348606010', 'hello', 'user', '2025-10-13 17:53:46'),
(78, 13, 'yo9b6scu231760348606010', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', 'bot', '2025-10-13 17:53:46'),
(79, 13, 'yo9b6scu231760348606010', 'who are you', 'user', '2025-10-13 17:53:54'),
(80, 13, 'yo9b6scu231760348606010', 'Thanks! I have recorded your question. Please leave email/phone for faster support.', 'bot', '2025-10-13 17:53:54'),
(81, 13, 'yo9b6scu231760348606010', 'Hello', 'user', '2025-10-13 17:54:13'),
(82, 13, 'yo9b6scu231760348606010', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', 'bot', '2025-10-13 17:54:13'),
(83, 13, 'yo9b6scu231760348606010', 'hello', 'user', '2025-10-13 18:07:04'),
(84, 13, 'yo9b6scu231760348606010', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', 'bot', '2025-10-13 18:07:04'),
(85, 13, 'yo9b6scu231760348606010', 'hello', 'user', '2025-10-13 18:07:16'),
(86, 13, 'yo9b6scu231760348606010', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', 'bot', '2025-10-13 18:07:16'),
(87, 13, 'yo9b6scu231760348606010', 'hello', 'user', '2025-10-13 18:10:24'),
(88, 13, 'yo9b6scu231760348606010', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', 'bot', '2025-10-13 18:10:24'),
(89, 13, 'yo9b6scu231760348606010', 'tôi cần mua hàng', 'user', '2025-10-13 18:10:33'),
(90, 13, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-13 18:10:33'),
(91, 13, 'yo9b6scu231760348606010', 'tôi cần tìm sản phẩm', 'user', '2025-10-13 18:10:37'),
(92, 13, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-13 18:10:37'),
(93, 13, 'yo9b6scu231760348606010', 'son', 'user', '2025-10-13 18:10:39'),
(94, 13, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-13 18:10:39'),
(95, 13, 'yo9b6scu231760348606010', 'son', 'user', '2025-10-13 18:26:44'),
(96, 13, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-13 18:26:44'),
(97, 13, 'yo9b6scu231760348606010', 'mua hàng', 'user', '2025-10-13 18:26:46'),
(98, 13, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-13 18:26:46'),
(99, 13, 'yo9b6scu231760348606010', 'hello', 'user', '2025-10-14 06:45:23'),
(100, 13, 'yo9b6scu231760348606010', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', 'bot', '2025-10-14 06:45:23'),
(101, 13, 'yo9b6scu231760348606010', 'who are you', 'user', '2025-10-14 06:45:38'),
(102, 13, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-14 06:45:38'),
(103, 13, 'yo9b6scu231760348606010', 'hello', 'user', '2025-10-14 07:14:19'),
(104, 13, 'yo9b6scu231760348606010', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', 'bot', '2025-10-14 07:14:19'),
(105, 13, 'yo9b6scu231760348606010', 'hello', 'user', '2025-10-14 07:32:00'),
(106, 13, 'yo9b6scu231760348606010', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', 'bot', '2025-10-14 07:32:04'),
(107, 13, 'yo9b6scu231760348606010', 'xin chào', 'user', '2025-10-14 07:32:07'),
(108, 13, 'yo9b6scu231760348606010', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.', 'bot', '2025-10-14 07:32:11'),
(109, 13, 'yo9b6scu231760348606010', 'tôi muốn mua hàng', 'user', '2025-10-14 07:32:18'),
(110, 13, 'yo9b6scu231760348606010', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.', 'bot', '2025-10-14 07:32:22'),
(111, 13, 'yo9b6scu231760348606010', 'son', 'user', '2025-10-14 07:32:27'),
(112, 13, 'yo9b6scu231760348606010', 'Sorry, the system is busy. Please try again in a moment.', 'bot', '2025-10-14 07:32:29'),
(113, 13, 'yo9b6scu231760348606010', 'tôi muốn mua son', 'user', '2025-10-14 07:33:07'),
(114, 13, 'yo9b6scu231760348606010', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.', 'bot', '2025-10-14 07:33:11'),
(115, 13, 'yo9b6scu231760348606010', 'hello', 'user', '2025-10-14 07:33:15'),
(116, 13, 'yo9b6scu231760348606010', 'Sorry, the system is busy. Please try again in a moment.', 'bot', '2025-10-14 07:33:18'),
(117, 13, 'yo9b6scu231760348606010', 'xin chào', 'user', '2025-10-14 07:35:29'),
(118, 13, 'yo9b6scu231760348606010', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.', 'bot', '2025-10-14 07:35:33'),
(119, 13, 'yo9b6scu231760348606010', 'tôi cần tư vấn sản phẩm', 'user', '2025-10-14 07:35:34'),
(120, 13, 'yo9b6scu231760348606010', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.', 'bot', '2025-10-14 07:35:37'),
(121, 11, 'yo9b6scu231760348606010', 'tôi cần tư vấn về sản phẩm Sữa Rửa Mặt LUMOS PERFECT này, da tôi là da dầu', 'user', '2025-10-14 17:40:43'),
(122, 11, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', 'bot', '2025-10-14 17:40:47'),
(123, 11, 'yo9b6scu231760348606010', 'da dầu', 'user', '2025-10-14 17:41:12'),
(124, 11, 'yo9b6scu231760348606010', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.', 'bot', '2025-10-14 17:41:16'),
(125, 11, 'yo9b6scu231760348606010', 'tôi cần 1 loại sữa rửa mặt', 'user', '2025-10-14 17:41:19'),
(126, 11, 'yo9b6scu231760348606010', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.', 'bot', '2025-10-14 17:41:21'),
(127, 11, 'yo9b6scu231760348606010', 'tư vấn cho tôi về sữa rửa mặt', 'user', '2025-10-14 17:41:31'),
(128, 11, 'yo9b6scu231760348606010', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.', 'bot', '2025-10-14 17:41:34'),
(129, 11, 'yo9b6scu231760348606010', 'tôi muốn mua sữa rửa mặt', 'user', '2025-10-14 17:41:44'),
(130, 11, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', 'bot', '2025-10-14 17:41:48'),
(131, 11, 'yo9b6scu231760348606010', '0325324064', 'user', '2025-10-14 17:43:30'),
(132, 11, 'yo9b6scu231760348606010', 'Sorry, the system is busy. Please try again in a moment.', 'bot', '2025-10-14 17:43:32'),
(133, 11, 'yo9b6scu231760348606010', 'giúp tôi đặt lịch tư vấn', 'user', '2025-10-14 17:43:35'),
(134, 11, 'yo9b6scu231760348606010', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.', 'bot', '2025-10-14 17:43:37'),
(135, 11, 'yo9b6scu231760348606010', 'vantronghuy2203@gmail.com', 'user', '2025-10-14 17:43:46'),
(136, 11, 'yo9b6scu231760348606010', 'Sorry, the system is busy. Please try again in a moment.', 'bot', '2025-10-14 17:43:50'),
(137, 11, 'yo9b6scu231760348606010', 'i want to buy serum', 'user', '2025-10-14 17:44:02'),
(138, 11, 'yo9b6scu231760348606010', 'Sorry, the system is busy. Please try again in a moment.', 'bot', '2025-10-14 17:44:05'),
(139, 11, 'yo9b6scu231760348606010', 'tôi muốn mua kem chống nắng', 'user', '2025-10-14 17:49:31'),
(140, 11, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', 'bot', '2025-10-14 17:49:35'),
(141, 11, 'yo9b6scu231760348606010', 'kem chống nắng kiềm da dầu', 'user', '2025-10-14 17:49:47'),
(142, 11, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', 'bot', '2025-10-14 17:49:50'),
(143, 11, 'yo9b6scu231760348606010', 'son môi thì sao', 'user', '2025-10-14 17:50:00'),
(144, 11, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', 'bot', '2025-10-14 17:50:04'),
(145, 11, 'yo9b6scu231760348606010', 'mua được loại nào', 'user', '2025-10-14 17:50:04'),
(146, 11, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', 'bot', '2025-10-14 17:50:07'),
(147, 11, 'yo9b6scu231760348606010', 'tôi muốn tư vấn về kem chống nắng', 'user', '2025-10-14 18:08:38'),
(148, 11, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', 'bot', '2025-10-14 18:08:42'),
(149, 11, 'yo9b6scu231760348606010', 'tôi đang tìm kem chống nắng', 'user', '2025-10-14 18:08:55'),
(150, 11, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', 'bot', '2025-10-14 18:08:59'),
(151, 11, 'yo9b6scu231760348606010', 'kem dưỡng', 'user', '2025-10-14 18:09:11'),
(152, 11, 'yo9b6scu231760348606010', 'Mình tìm thấy vài sản phẩm đúng nhu cầu:\n• Bộ Kem dưỡng ẩm & làm sáng LAGUMI  — 139.000đ\nBạn muốn xem chi tiết sản phẩm nào không?', 'bot', '2025-10-14 18:09:11'),
(153, 11, 'yo9b6scu231760348606010', 'có', 'user', '2025-10-14 18:09:19'),
(154, 11, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', 'bot', '2025-10-14 18:09:23'),
(155, 11, 'yo9b6scu231760348606010', 'tôi muốn xem chi tiết sản phẩm', 'user', '2025-10-14 18:09:24'),
(156, 11, 'yo9b6scu231760348606010', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', 'bot', '2025-10-14 18:09:28'),
(157, 11, 'yo9b6scu231760348606010', 'giá', 'user', '2025-10-14 18:09:41'),
(158, 11, 'yo9b6scu231760348606010', 'Bạn có thể xem giá trực tiếp trên trang sản phẩm. Bạn đang quan tâm sản phẩm nào?', 'bot', '2025-10-14 18:09:45'),
(159, 11, 'yo9b6scu231760348606010', 'son môi', 'user', '2025-10-14 18:09:49'),
(160, 11, 'yo9b6scu231760348606010', 'Here are some matching products:\n• Kẻ mắt SACE LADY — 399,000 VND\n• Son Môi gecomo Chất Nhung Lì Chống Thấm Nước  — 159,000 VND\n• Son Dưỡng Di.or Collagen Addict Lip Maximizer — 100,000 VND\nWould you like to see details for any item?', 'bot', '2025-10-14 18:09:52'),
(161, 11, 'yo9b6scu231760348606010', 'cleanser', 'user', '2025-10-14 18:10:11'),
(162, 11, 'yo9b6scu231760348606010', 'Here are some matching products:\n• Sữa Rửa Mặt LUMOS PERFECT PEARL CLEANSER Cân bằng độ PH  — 819,000 VND\nWould you like to see details for any item?', 'bot', '2025-10-14 18:10:11'),
(163, 11, 'yo9b6scu231760348606010', 'srm', 'user', '2025-10-14 18:10:19'),
(164, 11, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', 'bot', '2025-10-14 18:10:23'),
(165, 11, 'yo9b6scu231760348606010', 'sua rua mat', 'user', '2025-10-14 18:10:31'),
(166, 11, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', 'bot', '2025-10-14 18:10:35'),
(167, 11, 'yo9b6scu231760348606010', 'tôi muốn mua kem nề', 'user', '2025-10-14 18:12:16'),
(168, 11, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', 'bot', '2025-10-14 18:12:20'),
(169, 11, 'yo9b6scu231760348606010', 'tôi muốn mua kem nền', 'user', '2025-10-14 18:12:22'),
(170, 11, 'yo9b6scu231760348606010', 'Mình tìm thấy vài sản phẩm đúng nhu cầu:\n• Kem nền Catrice HD Liquid Coverage Foundation — 335.000đ\n• Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm — 470.000đ\n• Kẻ mắt SACE LADY — 399.000đ\nBạn muốn xem chi tiết sản phẩm nào không?', 'bot', '2025-10-14 18:12:22'),
(171, 11, 'yo9b6scu231760348606010', '0325324064', 'user', '2025-10-14 18:12:37'),
(172, 11, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', 'bot', '2025-10-14 18:12:41'),
(173, 11, 'yo9b6scu231760348606010', 'vantronghuy2203@gmail.com', 'user', '2025-10-14 18:12:48'),
(174, 11, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', 'bot', '2025-10-14 18:12:52'),
(175, 11, 'yo9b6scu231760348606010', 'tư vấn tôi qua số điện thoại', 'user', '2025-10-14 18:12:53'),
(176, 11, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', 'bot', '2025-10-14 18:12:57'),
(177, 11, 'yo9b6scu231760348606010', 'da nhạy cảm', 'user', '2025-10-14 18:12:58'),
(178, 11, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', 'bot', '2025-10-14 18:13:02'),
(179, 11, 'yo9b6scu231760348606010', 'hello', 'user', '2025-10-14 19:04:51'),
(180, 11, 'yo9b6scu231760348606010', 'Chào bạn! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', 'bot', '2025-10-14 19:04:55'),
(181, 11, 'yo9b6scu231760348606010', 'what, i\'m english', 'user', '2025-10-14 19:05:04'),
(182, 11, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Kẻ mắt SACE LADY. Bạn muốn da dầu, da khô hay da nhạy cảm?', 'bot', '2025-10-14 19:05:08'),
(183, 11, 'yo9b6scu231760348606010', 'can you speak english ?', 'user', '2025-10-14 19:05:13'),
(184, 11, 'yo9b6scu231760348606010', 'I couldn\'t match your keywords. New arrivals:\n• Kem nền Catrice HD Liquid Coverage Foundation — 335,000 VND\n• Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm — 470,000 VND\n• Kẻ mắt SACE LADY — 399,000 VND\n• Nước Tẩy Trang La Roche-Posay Làm Sạch Sâu Dành Cho Da Dầu Mụn Và Da Thường — 149,000 VND\n• Son Môi gecomo Chất Nhung Lì Chống Thấm Nước  — 159,000 VND\nWould you like to filter by type (cleanser, lipstick, serum...)?', 'bot', '2025-10-14 19:05:16'),
(185, 11, 'yo9b6scu231760348606010', 'how can i buy this product ?', 'user', '2025-10-14 19:05:46'),
(186, 11, 'yo9b6scu231760348606010', 'I couldn\'t match your keywords. New arrivals:\n• Kem nền Catrice HD Liquid Coverage Foundation — 335,000 VND\n• Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm — 470,000 VND\n• Kẻ mắt SACE LADY — 399,000 VND\n• Nước Tẩy Trang La Roche-Posay Làm Sạch Sâu Dành Cho Da Dầu Mụn Và Da Thường — 149,000 VND\n• Son Môi gecomo Chất Nhung Lì Chống Thấm Nước  — 159,000 VND\nWould you like to filter by type (cleanser, lipstick, serum...)?', 'bot', '2025-10-14 19:05:49'),
(187, 11, 'yo9b6scu231760348606010', 'i find srm', 'user', '2025-10-14 19:05:56'),
(188, 11, 'yo9b6scu231760348606010', 'I couldn\'t match your keywords. New arrivals:\n• Kem nền Catrice HD Liquid Coverage Foundation — 335,000 VND\n• Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm — 470,000 VND\n• Kẻ mắt SACE LADY — 399,000 VND\n• Nước Tẩy Trang La Roche-Posay Làm Sạch Sâu Dành Cho Da Dầu Mụn Và Da Thường — 149,000 VND\n• Son Môi gecomo Chất Nhung Lì Chống Thấm Nước  — 159,000 VND\nWould you like to filter by type (cleanser, lipstick, serum...)?', 'bot', '2025-10-14 19:05:58'),
(189, 11, 'yo9b6scu231760348606010', 'i find srm', 'user', '2025-10-14 19:06:30'),
(190, 11, 'yo9b6scu231760348606010', 'I couldn\'t match your keywords. New arrivals:\n• Kem nền Catrice HD Liquid Coverage Foundation — 335,000 VND\n• Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm — 470,000 VND\n• Kẻ mắt SACE LADY — 399,000 VND\n• Nước Tẩy Trang La Roche-Posay Làm Sạch Sâu Dành Cho Da Dầu Mụn Và Da Thường — 149,000 VND\n• Son Môi gecomo Chất Nhung Lì Chống Thấm Nước  — 159,000 VND\nWould you like to filter by type (cleanser, lipstick, serum...)?', 'bot', '2025-10-14 19:06:33'),
(191, 11, 'yo9b6scu231760348606010', 'help me find the son moi', 'user', '2025-10-14 19:06:50'),
(192, 11, 'yo9b6scu231760348606010', 'I couldn\'t match your keywords. New arrivals:\n• Kem nền Catrice HD Liquid Coverage Foundation — 335,000 VND\n• Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm — 470,000 VND\n• Kẻ mắt SACE LADY — 399,000 VND\n• Nước Tẩy Trang La Roche-Posay Làm Sạch Sâu Dành Cho Da Dầu Mụn Và Da Thường — 149,000 VND\n• Son Môi gecomo Chất Nhung Lì Chống Thấm Nước  — 159,000 VND\nWould you like to filter by type (cleanser, lipstick, serum...)?', 'bot', '2025-10-14 19:06:53'),
(193, 14, 'yo9b6scu231760348606010', 'tôi đang tìm 1 người để nói chuyện', 'user', '2025-10-14 19:28:03'),
(194, 14, 'yo9b6scu231760348606010', 'bạn có thể nói chuyện với tôi không', 'user', '2025-10-14 19:28:12'),
(195, 14, 'yo9b6scu231760348606010', 'xin chào', 'user', '2025-10-14 19:28:35'),
(196, 14, 'yo9b6scu231760348606010', 'hello', 'user', '2025-10-14 19:28:55'),
(197, 14, 'yo9b6scu231760348606010', 'tôi muốn mua hàng', 'user', '2025-10-14 19:29:16'),
(198, 14, 'yo9b6scu231760348606010', 'tôi cần người nói chuyện', 'user', '2025-10-14 19:30:22'),
(199, 14, 'yo9b6scu231760348606010', 'sao bạn không trả lời tôi ?>', 'user', '2025-10-14 19:30:26'),
(200, 11, 'yo9b6scu231760348606010', 'xin chào', 'user', '2025-10-20 00:55:02'),
(201, 11, 'yo9b6scu231760348606010', 'tôi muốn mua hàng', 'user', '2025-10-20 00:55:05'),
(202, 11, 'yo9b6scu231760348606010', 'giúp tôi đặt lịch tư vấn', 'user', '2025-10-20 00:55:39'),
(203, 11, 'yo9b6scu231760348606010', 'xin chào', 'user', '2025-10-20 01:37:27'),
(204, 11, 'yo9b6scu231760348606010', 'Chào Trọng Huy! Bạn muốn mua sản phẩm gì (sữa rửa mặt, son môi, serum...)?', 'bot', '2025-10-20 01:37:28'),
(205, 11, 'yo9b6scu231760348606010', 'xin chào', 'user', '2025-10-20 01:37:39'),
(206, 11, 'yo9b6scu231760348606010', 'Chào Trọng Huy! Bạn muốn mua sản phẩm gì (sữa rửa mặt, son môi, serum...)?', 'bot', '2025-10-20 01:37:39'),
(207, 11, 'yo9b6scu231760348606010', 'tôi muốn mua hàng', 'user', '2025-10-20 01:37:46'),
(208, 11, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn muốn tham khảo loại nào ạ?', 'bot', '2025-10-20 01:37:46'),
(209, 11, 'yo9b6scu231760348606010', 'xin chào', 'user', '2025-10-20 01:38:04'),
(210, 11, 'yo9b6scu231760348606010', 'Chào Trọng Huy! Bạn muốn mua sản phẩm gì (sữa rửa mặt, son môi, serum...)?', 'bot', '2025-10-20 01:38:04'),
(211, 11, 'yo9b6scu231760348606010', 'xin chào', 'user', '2025-10-20 01:45:19'),
(212, 11, 'yo9b6scu231760348606010', 'Chào Trọng Huy! Bạn muốn mua sản phẩm gì (sữa rửa mặt, son môi, serum...)?', 'bot', '2025-10-20 01:45:19'),
(213, 15, 'yo9b6scu231760348606010', 'xin chào', 'user', '2025-10-20 02:05:11'),
(214, 15, 'yo9b6scu231760348606010', 'Chào huy1! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', 'bot', '2025-10-20 02:05:11'),
(215, 15, 'yo9b6scu231760348606010', 'tôi muốn tìm sản phẩm, son', 'user', '2025-10-20 02:05:24'),
(216, 15, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-20 02:05:24'),
(217, 15, 'yo9b6scu231760348606010', 'tôi không muốn mua hàng', 'user', '2025-10-20 02:06:07'),
(218, 15, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-20 02:06:07'),
(219, 15, 'yo9b6scu231760348606010', 'hi', 'user', '2025-10-20 02:06:14'),
(220, 15, 'yo9b6scu231760348606010', 'Chào huy1! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', 'bot', '2025-10-20 02:06:14'),
(221, 15, 'yo9b6scu231760348606010', 'hello', 'user', '2025-10-20 02:06:17'),
(222, 15, 'yo9b6scu231760348606010', 'Chào huy1! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', 'bot', '2025-10-20 02:06:17'),
(223, 15, 'yo9b6scu231760348606010', 'hi', 'user', '2025-10-20 05:16:42'),
(224, 15, 'yo9b6scu231760348606010', 'Chào huy1! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', 'bot', '2025-10-20 05:16:42'),
(225, 15, 'yo9b6scu231760348606010', 'xin chào', 'user', '2025-10-20 05:16:46'),
(226, 15, 'yo9b6scu231760348606010', 'Chào huy1! Mình có thể giúp gì về sản phẩm, giá hoặc giao hàng?', 'bot', '2025-10-20 05:16:46'),
(227, 15, 'yo9b6scu231760348606010', 'tôi muốn mua son', 'user', '2025-10-20 05:16:50'),
(228, 15, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-20 05:16:50'),
(229, 15, 'yo9b6scu231760348606010', 'vantronghuy2203@gmail.com', 'user', '2025-10-20 05:17:00'),
(230, 15, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-20 05:17:00'),
(231, 15, 'yo9b6scu231760348606010', 'xin chào', 'user', '2025-10-20 08:51:36'),
(232, 15, 'yo9b6scu231760348606010', 'Chào huy1! Bạn đang tìm sản phẩm gì ạ?', 'bot', '2025-10-20 08:51:36'),
(233, 15, 'yo9b6scu231760348606010', 'tôi muốn mua son', 'user', '2025-10-20 08:51:42'),
(234, 15, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kẻ mắt SACE LADY, Son Môi gecomo Chất Nhung Lì Chống Thấm Nước , COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da. Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để tiếp tục.', 'bot', '2025-10-20 08:51:42'),
(235, 15, 'yo9b6scu231760348606010', 'có', 'user', '2025-10-20 08:52:00'),
(236, 15, 'yo9b6scu231760348606010', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', 'bot', '2025-10-20 08:52:00'),
(237, 15, 'yo9b6scu231760348606010', '2025-10-20', 'user', '2025-10-20 08:52:17'),
(238, 15, 'yo9b6scu231760348606010', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', 'bot', '2025-10-20 08:52:17'),
(239, 15, 'yo9b6scu231760348606010', '2025-10-20 19:30', 'user', '2025-10-20 08:52:34'),
(240, 15, 'yo9b6scu231760348606010', 'Đã đặt lịch tư vấn lúc 2025-10-20 19:30:00. Nhân viên sẽ xác nhận sớm! Cảm ơn bạn!', 'bot', '2025-10-20 08:52:34'),
(241, 15, 'yo9b6scu231760348606010', 'hello', 'user', '2025-10-20 09:10:41'),
(242, 15, 'yo9b6scu231760348606010', 'Chào huy1! Bạn đang tìm sản phẩm gì ạ?', 'bot', '2025-10-20 09:10:41'),
(243, 15, 'yo9b6scu231760348606010', 'i want find the son moi', 'user', '2025-10-20 09:10:47'),
(244, 15, 'yo9b6scu231760348606010', 'You can check: Kẻ mắt SACE LADY, Son Môi gecomo Chất Nhung Lì Chống Thấm Nước , COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da. Would you like to book a consultation? Reply \"yes\" to continue.', 'bot', '2025-10-20 09:10:47'),
(245, 15, 'yo9b6scu231760348606010', 'còn sản phẩm nào không ?', 'user', '2025-10-20 09:10:54'),
(246, 15, 'yo9b6scu231760348606010', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.', 'bot', '2025-10-20 09:10:59'),
(247, 15, 'yo9b6scu231760348606010', 'tôi muốn tìm kẻ mắt', 'user', '2025-10-20 09:11:35'),
(248, 15, 'yo9b6scu231760348606010', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.', 'bot', '2025-10-20 09:11:39'),
(249, 15, 'yo9b6scu231760348606010', 'hi', 'user', '2025-10-20 17:33:56'),
(250, 15, 'yo9b6scu231760348606010', 'Chào huy1! Bạn đang tìm sản phẩm gì ạ?', 'bot', '2025-10-20 17:33:56'),
(251, 15, 'yo9b6scu231760348606010', 'tôi muốn tìm mua son', 'user', '2025-10-20 17:34:30'),
(252, 15, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kẻ mắt SACE LADY, Son Môi gecomo Chất Nhung Lì Chống Thấm Nước , COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da, Son Dưỡng Di.or Collagen Addict Lip Maximizer, Son dưỡng Dior Addict Lip Glow SELENA STORE. Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để chọn thời gian.', 'bot', '2025-10-20 17:34:30'),
(253, 15, 'yo9b6scu231760348606010', 'chỉ mỗi son thôi', 'user', '2025-10-20 17:34:46'),
(254, 15, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kẻ mắt SACE LADY, Son Môi gecomo Chất Nhung Lì Chống Thấm Nước , COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da, Son Dưỡng Di.or Collagen Addict Lip Maximizer, Son dưỡng Dior Addict Lip Glow SELENA STORE. Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để chọn thời gian.', 'bot', '2025-10-20 17:34:46'),
(255, 15, 'yo9b6scu231760348606010', 'có', 'user', '2025-10-20 17:34:53'),
(256, 15, 'yo9b6scu231760348606010', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', 'bot', '2025-10-20 17:34:53'),
(257, 15, 'yo9b6scu231760348606010', '2025-10-22 06:30', 'user', '2025-10-20 17:35:05'),
(258, 15, 'yo9b6scu231760348606010', 'Đã đặt lịch tư vấn lúc 2025-10-22 06:30:00. Nhân viên sẽ xác nhận sớm! Bạn cần thêm gì nữa không?', 'bot', '2025-10-20 17:35:05'),
(259, 15, 'yo9b6scu231760348606010', 'tôi muốn mua sữa rửa mặt', 'user', '2025-10-20 17:35:18'),
(260, 15, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Sữa Rửa Mặt LUMOS PERFECT PEARL CLEANSER Cân bằng độ PH , Bộ Kem dưỡng ẩm & làm sáng LAGUMI . Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để chọn thời gian.', 'bot', '2025-10-20 17:35:18'),
(261, 15, 'yo9b6scu231760348606010', 'tôi muốn mua kem chống nắng', 'user', '2025-10-20 17:35:31'),
(262, 15, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kem nền Catrice HD Liquid Coverage Foundation, Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm, Bộ Kem dưỡng ẩm & làm sáng LAGUMI , Kem chống nắng kiềm dầu LUMOS SUN CREAM. Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để chọn thời gian.', 'bot', '2025-10-20 17:35:31'),
(263, 16, 'yo9b6scu231760348606010', 'tôi đang muốn mua son', 'user', '2025-10-20 17:36:47'),
(264, 16, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kẻ mắt SACE LADY, Son Môi gecomo Chất Nhung Lì Chống Thấm Nước , COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da, Son Dưỡng Di.or Collagen Addict Lip Maximizer, Son dưỡng Dior Addict Lip Glow SELENA STORE. Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để chọn thời gian.', 'bot', '2025-10-20 17:36:47'),
(265, 16, 'yo9b6scu231760348606010', 'tôi muốn có người tâm sự', 'user', '2025-10-20 17:37:11'),
(266, 16, 'yo9b6scu231760348606010', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', 'bot', '2025-10-20 17:37:11'),
(267, 16, 'yo9b6scu231760348606010', 'tôi đang tìm 1 người để nói chuyện', 'user', '2025-10-20 17:37:22'),
(268, 16, 'yo9b6scu231760348606010', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', 'bot', '2025-10-20 17:37:22'),
(269, 16, 'yo9b6scu231760348606010', 'tôi muốn nói chuyện với chủ shop', 'user', '2025-10-20 17:37:29'),
(270, 16, 'yo9b6scu231760348606010', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', 'bot', '2025-10-20 17:37:29'),
(271, 16, 'yo9b6scu231760348606010', 'hello', 'user', '2025-10-20 17:37:36'),
(272, 16, 'yo9b6scu231760348606010', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', 'bot', '2025-10-20 17:37:36'),
(273, 16, 'yo9b6scu231760348606010', 'how are you today', 'user', '2025-10-20 17:37:52'),
(274, 16, 'yo9b6scu231760348606010', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', 'bot', '2025-10-20 17:37:52'),
(275, 16, 'yo9b6scu231760348606010', 'ship', 'user', '2025-10-20 17:53:57'),
(276, 16, 'yo9b6scu231760348606010', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', 'bot', '2025-10-20 17:53:57'),
(277, 16, 'yo9b6scu231760348606010', 'bảo hành', 'user', '2025-10-20 17:54:02'),
(278, 16, 'yo9b6scu231760348606010', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', 'bot', '2025-10-20 17:54:02'),
(279, 16, 'yo9b6scu231760348606010', 'hello', 'user', '2025-10-20 17:57:50'),
(280, 16, 'yo9b6scu231760348606010', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', 'bot', '2025-10-20 17:57:50'),
(281, 16, 'yo9b6scu231760348606010', 'how are you today', 'user', '2025-10-20 17:57:58'),
(282, 16, 'yo9b6scu231760348606010', 'Vui lòng nhập thời gian theo định dạng YYYY-MM-DD HH:MM', 'bot', '2025-10-20 17:57:58'),
(283, 16, 'yo9b6scu231760348606010', 'how are you', 'user', '2025-10-20 17:58:18'),
(284, 16, 'yo9b6scu231760348606010', 'Please enter time as YYYY-MM-DD HH:MM', 'bot', '2025-10-20 17:58:18'),
(285, 16, 'yo9b6scu231760348606010', 'i want look son moi', 'user', '2025-10-20 17:58:26'),
(286, 16, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kẻ mắt SACE LADY, Son Môi gecomo Chất Nhung Lì Chống Thấm Nước , COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da. Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để tiếp tục.', 'bot', '2025-10-20 17:58:26'),
(287, 16, 'yo9b6scu231760348606010', 'can you speak english', 'user', '2025-10-20 17:58:40'),
(288, 16, 'yo9b6scu231760348606010', 'Sorry, the system is busy. Please try again in a moment.', 'bot', '2025-10-20 17:58:45'),
(289, 16, 'yo9b6scu231760348606010', 'hello', 'user', '2025-10-23 15:29:03'),
(290, 16, 'yo9b6scu231760348606010', 'Chào huy2! Bạn đang tìm sản phẩm gì ạ?', 'bot', '2025-10-23 15:29:03'),
(291, 16, 'yo9b6scu231760348606010', 'hello', 'user', '2025-10-23 15:29:15'),
(292, 16, 'yo9b6scu231760348606010', 'Chào huy2! Bạn đang tìm sản phẩm gì ạ?', 'bot', '2025-10-23 15:29:15'),
(293, 16, 'yo9b6scu231760348606010', 'xin chào tôi đang muốn tìm son', 'user', '2025-10-23 15:29:28'),
(294, 16, 'yo9b6scu231760348606010', 'Chào huy2! Bạn đang tìm sản phẩm gì ạ?', 'bot', '2025-10-23 15:29:28'),
(295, 17, 'yo9b6scu231760348606010', 'tôi đang tìm nước tẩy trang', 'user', '2025-10-26 16:22:17'),
(296, 17, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-26 16:22:19'),
(297, 17, 'yo9b6scu231760348606010', 'tôi muốn tìm son môi', 'user', '2025-10-26 16:22:33'),
(298, 17, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kẻ mắt SACE LADY, Son Môi gecomo Chất Nhung Lì Chống Thấm Nước , COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da. Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để tiếp tục.', 'bot', '2025-10-26 16:22:33'),
(299, 17, 'yo9b6scu231760348606010', 'nước tẩy trang thì sao', 'user', '2025-10-26 16:22:46'),
(300, 17, 'yo9b6scu231760348606010', 'Cảm ơn bạn! Mình đã ghi nhận câu hỏi. Bạn có thể để lại email/sđt để tư vấn nhanh hơn?', 'bot', '2025-10-26 16:22:48'),
(301, 17, 'yo9b6scu231760348606010', 'hello, can you help me', 'user', '2025-10-30 19:09:17'),
(302, 17, 'yo9b6scu231760348606010', 'Chào huy3! Bạn đang tìm sản phẩm gì ạ?', 'bot', '2025-10-30 19:09:17'),
(303, 17, 'yo9b6scu231760348606010', 'xin chào', 'user', '2025-10-30 19:09:53'),
(304, 17, 'yo9b6scu231760348606010', 'Chào huy3! Bạn đang tìm sản phẩm gì ạ?', 'bot', '2025-10-30 19:09:53'),
(305, 17, 'yo9b6scu231760348606010', 'mình muốn mua son môi', 'user', '2025-10-30 19:10:03'),
(306, 17, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kẻ mắt SACE LADY, Son Môi gecomo Chất Nhung Lì Chống Thấm Nước , COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da. Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để tiếp tục.', 'bot', '2025-10-30 19:10:03'),
(307, 17, 'yo9b6scu231760348606010', 'tôi muốn mua son', 'user', '2025-10-31 18:21:03'),
(308, 17, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kẻ mắt SACE LADY, Son Môi gecomo Chất Nhung Lì Chống Thấm Nước , COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da. Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để tiếp tục.', 'bot', '2025-10-31 18:21:03'),
(309, 17, 'yo9b6scu231760348606010', 'tôi muốn mua son', 'user', '2025-10-31 18:21:13'),
(310, 17, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kẻ mắt SACE LADY, Son Môi gecomo Chất Nhung Lì Chống Thấm Nước , COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da. Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để tiếp tục.', 'bot', '2025-10-31 18:21:13'),
(311, 17, 'yo9b6scu231760348606010', 'tôi muốn mua son', 'user', '2025-10-31 18:23:50'),
(312, 17, 'yo9b6scu231760348606010', 'Bạn có thể tham khảo: Kẻ mắt SACE LADY, Son Môi gecomo Chất Nhung Lì Chống Thấm Nước , COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da. Bạn muốn đặt lịch tư vấn không? Trả lời \"có\" để tiếp tục.', 'bot', '2025-10-31 18:23:50'),
(313, NULL, 'sess_408e7f41a4cd', 'xin chào', 'user', '2025-11-02 06:33:05'),
(314, NULL, 'sess_408e7f41a4cd', 'Xin chào! Bạn cần tư vấn về mỹ phẩm không? Tôi sẵn sàng giúp đỡ.', 'bot', '2025-11-02 06:33:20'),
(315, NULL, 'sess_6d87f6176684', 'tôi muốn mua son môi', 'user', '2025-11-02 06:33:20'),
(316, NULL, 'sess_6d87f6176684', 'Bạn muốn mua son môi. Tôi có một số gợi ý phù hợp:\n\n- Son Môi gecomo Chất Nhung Lì Chống Thấm Nước – OLAY – Sale – 159000 VND\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer – GUCCI – Son Dưỡng – 100000 VND\n\nHãy cho tôi biết thêm thông tin về sở thích và yêu cầu của bạn để tôi có thể gợi ý tốt hơn!', 'bot', '2025-11-02 06:33:32'),
(317, NULL, 'sess_6d87f6176684', 'tôi muốn mua son dưỡng', 'user', '2025-11-02 06:33:46'),
(318, NULL, 'sess_6d87f6176684', 'Bạn muốn mua son dưỡng. Tôi thấy có 2 sản phẩm phù hợp: \n\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer – GUCCI – Son Dưỡng – 1.000.000 VND\n- Son dưỡng Dior Addict Lip Glow – SELENA STORE – Son Dưỡng – 950.000 VND\n\nHãy cho tôi biết thêm thông tin để giúp bạn chọn sản phẩm phù hợp!', 'bot', '2025-11-02 06:33:57'),
(319, NULL, 'sess_6d87f6176684', 'tôi cần đặt lịch tư vấn', 'user', '2025-11-02 06:34:09'),
(320, NULL, 'sess_6d87f6176684', 'Xin chào! Tôi sẵn sàng giúp bạn đặt lịch tư vấn. Để tôi hiểu rõ hơn về nhu cầu của bạn, vui lòng cho tôi biết bạn đang tìm kiếm sản phẩm gì hoặc muốn tư vấn về chủ đề nào?', 'bot', '2025-11-02 06:34:15'),
(321, NULL, 'sess_6d87f6176684', 'xin chào', 'user', '2025-11-02 07:03:18'),
(322, NULL, 'sess_6d87f6176684', 'Xin chào! Bạn cần tư vấn về mỹ phẩm không? Tôi sẵn sàng giúp đỡ.', 'bot', '2025-11-02 07:03:27'),
(323, NULL, 'sess_6d87f6176684', 'tôi muốn mua hàng', 'user', '2025-11-02 07:03:27'),
(324, NULL, 'sess_6d87f6176684', 'Bạn muốn mua hàng gì vậy? Bạn có thể chọn một số sản phẩm sau đây:\n\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer - GUCCI - Son Dưỡng - 100000 VND\n- Kem nền Catrice HD Liquid Coverage Foundation - NIVEA - Kem Nền - 335000 VND\n\nHãy cho tôi biết bạn cần gì khác nhé!', 'bot', '2025-11-02 07:03:36'),
(325, 0, 'yo9b6scu231760348606010', 'tôi muốn mua hàng', 'user', '2025-11-02 07:28:50'),
(326, 0, 'yo9b6scu231760348606010', 'Bạn muốn mua hàng gì vậy? Bạn có thể chọn một số sản phẩm sau đây:\n\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer – GUCCI – Son Dưỡng – 100000 VND\n- Son dưỡng Dior Addict Lip Glow SELENA STORE – Dior – Son Dưỡng – 950000 VND\n\nNếu bạn muốn mua thêm hàng khác, vui lòng cho tôi biết chi tiết hơn.', 'bot', '2025-11-02 07:29:09'),
(327, NULL, 'sess_6d87f6176684', 'tôi muốn mua hàng', 'user', '2025-11-02 07:29:53'),
(328, NULL, 'sess_6d87f6176684', 'Xin chào! Bạn muốn mua hàng gì?\n\nBạn có thể xem qua danh sách sản phẩm của chúng tôi:\n\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer | GUCCI | Son Dưỡng | 100000 VND\n- Son Môi gecomo Chất Nhung Lì Chống Thấm Nước  | OLAY | Sale | 159000 VND\n- Son dưỡng Dior Addict Lip Glow SELENA STORE | Dior  | Son Dưỡng | 950000 VND\n- Kẻ mắt SACE LADY | INNISFREE | Son Dưỡng | 399000 VND\n- Kem nền Catrice HD Liquid Coverage Foundation | NIVEA | Kem Nền | 335000 VND\n\nBạn có thể cho tôi biết danh mục hoặc thương hiệu sản phẩm bạn quan tâm?', 'bot', '2025-11-02 07:30:13'),
(329, NULL, 'sess_6d87f6176684', 'tôi muốn mua son môi', 'user', '2025-11-02 07:30:44'),
(330, NULL, 'sess_6d87f6176684', 'Bạn muốn mua son môi. Tôi đề xuất 2 sản phẩm phù hợp:\n\n- Son Môi gecomo Chất Nhung Lì Chống Thấm Nước – OLAY – Sale – 159000 VND\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer – GUCCI – Son Dưỡng – 100000 VND\n\nNếu bạn muốn thêm thông tin về sản phẩm, vui lòng cho tôi biết.', 'bot', '2025-11-02 07:30:53'),
(331, NULL, 'yo9b6scu231760348606010', 'TÔI MUỐN MUA SON', 'user', '2025-11-02 07:44:51'),
(332, NULL, 'yo9b6scu231760348606010', 'Bạn muốn mua son. Có 3 sản phẩm phù hợp:\n\n- Son dưỡng Dior Addict Lip Glow - Brand: Dior - Category: Son Dưỡng - Price: 950000 VND\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer - Brand: GUCCI - Category: Son Dưỡng - Price: 100000 VND\n- Kẻ mắt SACE LADY - Brand: INNISFREE - Category: Son Dưỡng - Price: 399000 VND', 'bot', '2025-11-02 07:45:13'),
(333, NULL, 's_1762071076477_5fubkp', 'tôi muốn mua son', 'user', '2025-11-02 08:12:33'),
(334, NULL, 's_1762071076477_5fubkp', 'Bạn muốn mua son. Tôi có một số gợi ý sau:\n\n- Son dưỡng Dior Addict Lip Glow - Dior - Son Dưỡng - 950000 VND\n- Son Môi gecomo Chất Nhung Lì Chống Thấm Nước - OLAY - Sale - 159000 VND\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer - GUCCI - Son Dưỡng - 100000 VND\n\nNếu bạn muốn mua son dưỡng, có thể chọn sản phẩm đầu tiên hoặc thứ hai.', 'bot', '2025-11-02 08:12:56'),
(335, NULL, 's_1762071076477_5fubkp', 'tôi muốn mua son môi bình thường, không phải son dưỡng', 'user', '2025-11-02 08:16:10'),
(336, NULL, 's_1762071076477_5fubkp', 'Bạn muốn mua son môi bình thường. Tôi thấy có một số sản phẩm phù hợp như sau:\n\n- Son Môi gecomo Chất Nhung Lì Chống Thấm Nước  - OLAY - Sale - 159000 VND\nTôi chưa có thông tin về các sản phẩm khác trong danh mục \"Son Môi\". Bạn vui lòng cho tôi biết thêm chi tiết để tôi có thể hỗ trợ bạn tốt hơn.', 'bot', '2025-11-02 08:16:21'),
(337, NULL, 's_1762071076477_5fubkp', 'tôi muốn đặt lịch tư vấn', 'user', '2025-11-02 08:24:48'),
(338, NULL, 's_1762071076477_5fubkp', 'Xin chào! Tôi sẵn sàng giúp bạn đặt lịch tư vấn. Để tôi hiểu rõ hơn về nhu cầu của bạn, vui lòng cho tôi biết bạn đang tìm kiếm sản phẩm gì hoặc có bất kỳ yêu cầu cụ thể nào không?', 'bot', '2025-11-02 08:24:58'),
(339, NULL, 's_1762071076477_5fubkp', 'tôi muốn mua sữa rửa mặt nhưng không biết loại nào phù hợp, hãy giúp tôi đặt lịch tư vấn về sản phẩm nhé', 'user', '2025-11-02 08:25:38'),
(340, NULL, 's_1762071076477_5fubkp', 'Để giúp bạn chọn được sữa rửa mặt phù hợp, tôi cần một số thông tin về làn da của bạn. Bạn có thể cho tôi biết loại da của mình là gì (da dầu, da khô, da hỗn hợp,...) và liệu bạn có bất kỳ vấn đề nào khác với da không?', 'bot', '2025-11-02 08:25:45'),
(341, NULL, 's_1762071076477_5fubkp', 'da tôi bị nhạy cmar', 'user', '2025-11-02 08:25:54'),
(342, NULL, 's_1762071076477_5fubkp', 'Tôi hiểu rằng bạn đang gặp vấn đề với da nhạy cảm. Để giúp bạn tìm được sản phẩm phù hợp, tôi cần biết thêm thông tin về loại da của bạn (da dầu, da khô, da hỗn hợp,...) và các vấn đề cụ thể mà bạn muốn giải quyết (chống nắng, dưỡng ẩm, làm dịu da,...).', 'bot', '2025-11-02 08:26:03'),
(343, NULL, 's_1762071076477_5fubkp', 'dưỡng ẩm', 'user', '2025-11-02 08:26:17'),
(344, NULL, 's_1762071076477_5fubkp', 'Bạn muốn tìm sản phẩm dưỡng ẩm. Tôi có một số gợi ý như sau:\n\n- LAGUMI – NIVEA – Son Dưỡng – 139000 VND\n- Di.or Collagen Addict Lip Maximizer – GUCCI – Son Dưỡng – 100000 VND\n\nNếu bạn cần thêm thông tin, vui lòng cho tôi biết!', 'bot', '2025-11-02 08:26:25'),
(345, NULL, 's_1762071076477_5fubkp', 'sữa rửa mặt không phải son môi', 'user', '2025-11-02 08:26:40'),
(346, NULL, 's_1762071076477_5fubkp', 'Người dùng đang tìm sữa rửa mặt. Tôi sẽ đề xuất một sản phẩm phù hợp.\n\nLUMOS PERFECT PEARL CLEANSER - SIMPLE - Gel Rửa Mặt - 819000 VND\n\nNếu bạn muốn thêm thông tin hoặc có yêu cầu khác, vui lòng cho tôi biết!', 'bot', '2025-11-02 08:26:47'),
(347, NULL, 'u0_1762073763835_vzm2iy', 'mình đang muốn tìm son môi', 'user', '2025-11-02 09:57:53'),
(348, NULL, 'u0_1762073763835_vzm2iy', 'Bạn đang tìm kiếm son môi phù hợp. Dựa trên dữ liệu sản phẩm hiện có, tôi đề xuất 2 sản phẩm sau:\n\n- Son Môi gecomo Chất Nhung Lì Chống Thấm Nước - OLAY - Sale - 159000 VND\n- Son dưỡng Di.or Collagen Addict Lip Maximizer - GUCCI - Son Dưỡng - 100000 VND\n\nNếu bạn có thêm yêu cầu hoặc thông tin cụ thể về loại son môi bạn muốn, tôi sẽ cố gắng hỗ trợ tốt hơn.\n\nBạn có muốn đặt lịch tư vấn không? (Có/Không)', 'bot', '2025-11-02 09:58:15'),
(349, NULL, 'u0_1762073763835_vzm2iy', 'không', 'user', '2025-11-02 09:58:25'),
(350, NULL, 'u0_1762073763835_vzm2iy', 'Cảm ơn bạn! Nếu cần thêm gì cứ nhắn mình nhé.', 'bot', '2025-11-02 09:58:25'),
(351, NULL, 'u0_1762073763835_vzm2iy', 'có', 'user', '2025-11-02 09:58:29'),
(352, NULL, 'u0_1762073763835_vzm2iy', 'Cảm ơn bạn, bên em sẽ có nhân viên gọi điện để hỗ trợ mình về sản phẩm.', 'bot', '2025-11-02 09:58:29'),
(353, NULL, 'u0_1762073763835_vzm2iy', 'mình cần cung cấp thông tin gì', 'user', '2025-11-02 09:58:42'),
(354, NULL, 'u0_1762073763835_vzm2iy', 'Để cung cấp thông tin cho người dùng, tôi cần biết yêu cầu cụ thể của họ. Bạn muốn tìm kiếm sản phẩm gì hoặc có yêu cầu đặc biệt không?\n\nBạn có muốn đặt lịch tư vấn không? (Có/Không)', 'bot', '2025-11-02 09:58:46');
INSERT INTO `chat_messages` (`id`, `user_id`, `session_id`, `message`, `direction`, `created_at`) VALUES
(355, 584018126, 'u584018126_1762108994126_e8t75f', 'tôi đang muốn mua son môi', 'user', '2025-11-02 18:44:10'),
(356, 584018126, 'u584018126_1762108994126_e8t75f', 'Bạn đang tìm mua son môi. Tôi thấy có hai sản phẩm son môi phù hợp với nhu cầu của bạn:\n\n- Son Môi gecomo Chất Nhung Lì Chống Thấm Nước – OLAY – Sale – 159000 VND\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer – GUCCI – Son Dưỡng – 100000 VND\n\nNếu bạn muốn thêm thông tin, vui lòng cho tôi biết!\n\nBạn có muốn đặt lịch tư vấn không? (Có/Không)', 'bot', '2025-11-02 18:44:37'),
(357, 584018126, 'u584018126_1762108994126_e8t75f', 'tôi không', 'user', '2025-11-02 18:44:54'),
(358, 584018126, 'u584018126_1762108994126_e8t75f', 'Cảm ơn bạn! Nếu cần thêm gì cứ nhắn mình nhé.', 'bot', '2025-11-02 18:44:54'),
(359, 584018126, 'u584018126_1762108994126_e8t75f', 'tôi muốn tư vấn', 'user', '2025-11-02 18:44:59'),
(360, 584018126, 'u584018126_1762108994126_e8t75f', 'Cảm ơn bạn, bên em sẽ có nhân viên gọi điện để hỗ trợ mình về sản phẩm.', 'bot', '2025-11-02 18:44:59'),
(361, NULL, 'u0_1762109111478_ozev9m', 'tôi muốn mua hàng', 'user', '2025-11-02 18:45:17'),
(362, NULL, 'u0_1762109111478_ozev9m', 'Xin chào! Bạn muốn mua hàng gì? Tôi có thể giúp bạn tìm kiếm sản phẩm phù hợp.\n\nBạn quan tâm đến loại mỹ phẩm nào? Son dưỡng, kem nền hay kẻ mắt? Hãy cho tôi biết để tôi có thể đề xuất một số lựa chọn phù hợp.\n\nBạn có muốn đặt lịch tư vấn không? (Có/Không)', 'bot', '2025-11-02 18:45:25'),
(363, NULL, 'u0_1762109111478_ozev9m', 'tôi muốn mua son môi', 'user', '2025-11-02 18:45:42'),
(364, NULL, 'u0_1762109111478_ozev9m', 'Bạn muốn mua son môi. Tôi có một số gợi ý cho bạn:\n\n- Son Môi gecomo Chất Nhung Lì Chống Thấm Nước - OLAY - Sale - 159000 VND\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer - GUCCI - Son Dưỡng - 100000 VND\n\nBạn có muốn biết thêm thông tin về sản phẩm nào không?\n\nBạn có muốn đặt lịch tư vấn không? (Có/Không)', 'bot', '2025-11-02 18:45:52'),
(365, 584018126, 'u584018126_1762109283619_izbffk', 'tôi muốn mua son', 'user', '2025-11-02 18:48:57'),
(366, 584018126, 'u584018126_1762109283619_izbffk', 'Bạn muốn mua son. Có một số lựa chọn cho bạn:\n\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer – GUCCI – Son Dưỡng – 100000 VND\n- Son dưỡng Dior Addict Lip Glow – SELENA STORE – Son Dưỡng – 950000 VND\n\nHãy cho tôi biết thêm thông tin về sở thích hoặc yêu cầu cụ thể của bạn để tôi có thể đề xuất sản phẩm phù hợp hơn.\n\nBạn có muốn đặt lịch tư vấn không? (Có/Không)', 'bot', '2025-11-02 18:49:09'),
(367, 584018126, 'u584018126_1762110804953_pg620y', 'tôi muốn mua son', 'user', '2025-11-02 19:14:25'),
(368, 584018126, 'u584018126_1762110804953_pg620y', 'Bạn muốn mua son. Tôi có một số gợi ý cho bạn:\n\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer – GUCCI – Son Dưỡng – 100000 VND\n- Son dưỡng Dior Addict Lip Glow – SELENA STORE – Son Dưỡng – 950000 VND\n\nNếu bạn muốn mua son chống thấm nước, tôi chưa có thông tin chính xác. Bạn vui lòng mô tả thêm chi tiết về sản phẩm bạn đang tìm kiếm.\n\nBạn có muốn đặt lịch tư vấn không? (Có/Không)', 'bot', '2025-11-02 19:14:47'),
(369, 584018126, 'u584018126_1762110946911_ch2zye', 'tôi muốn mua son dưỡng', 'user', '2025-11-02 19:15:52'),
(370, 584018126, 'u584018126_1762110946911_ch2zye', 'Bạn muốn mua son dưỡng. Tôi thấy có 2 sản phẩm phù hợp: \n\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer – GUCCI – Son Dưỡng – 100000 VND\n- Son dưỡng Dior Addict Lip Glow – SELENA STORE – Son Dưỡng – 950000 VND\n\nHãy cho tôi biết thêm thông tin nếu bạn muốn sản phẩm khác.\n\nBạn có muốn đặt lịch tư vấn không? (Có/Không)', 'bot', '2025-11-02 19:16:03'),
(371, 584018126, 'u584018126_1762110946911_ch2zye', 'có', 'user', '2025-11-02 19:38:06'),
(372, 584018126, 'u584018126_1762110946911_ch2zye', 'Xin chào! Tôi sẵn sàng giúp bạn tìm kiếm sản phẩm mỹ phẩm phù hợp.\n\nBạn muốn mua loại son dưỡng nào?\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', 'bot', '2025-11-02 19:38:21'),
(373, 584018126, 'u584018126_1762110946911_ch2zye', '03/11/2025 19h00', 'user', '2025-11-02 19:38:43'),
(374, 584018126, 'u584018126_1762110946911_ch2zye', 'Xin chào! Tôi sẵn sàng giúp bạn tìm kiếm sản phẩm mỹ phẩm phù hợp.\n\nBạn đang tìm kiếm gì hôm nay? Bạn muốn mua son dưỡng, kem nền, mặt nạ hoặc kem chống nắng? Hãy cho tôi biết để tôi có thể gợi ý cho bạn.\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', 'bot', '2025-11-02 19:38:50'),
(375, 584018126, 'u584018126_1762110946911_ch2zye', 'xin chào', 'user', '2025-11-02 19:43:26'),
(376, 584018126, 'u584018126_1762110946911_ch2zye', 'Xin chào! Bạn cần tìm sản phẩm gì hôm nay?\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', 'bot', '2025-11-02 19:43:29'),
(377, 584018126, 'u584018126_1762110946911_ch2zye', 'tôi muốn mua son', 'user', '2025-11-02 19:43:38'),
(378, 584018126, 'u584018126_1762110946911_ch2zye', 'Bạn muốn mua son. Có một số lựa chọn phù hợp:\n\n- Son dưỡng Dior Addict Lip Glow - Dior - Son Dưỡng - 950000 VND\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer - GUCCI - Son Dưỡng - 100000 VND\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', 'bot', '2025-11-02 19:43:46'),
(379, 584018126, 'u584018126_1762110946911_ch2zye', 'son dưỡng', 'user', '2025-11-02 19:43:55'),
(380, 584018126, 'u584018126_1762110946911_ch2zye', 'Người dùng muốn biết son dưỡng. Tôi thấy có 3 sản phẩm thuộc danh mục Son Dưỡng trong dữ liệu.\n\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer – GUCCI – Son Dưỡng – 100000 VND\n- Kẻ mắt SACE LADY – INNISFREE – Son Dưỡng – 399000 VND\n- Son dưỡng Dior Addict Lip Glow – SELENA STORE – Son Dưỡng – 950000 VND\n\nNếu bạn muốn biết thêm thông tin, vui lòng cho tôi biết!\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', 'bot', '2025-11-02 19:44:10'),
(381, 584018126, 'u584018126_1762110946911_ch2zye', 'ngày 03/11/2025 19:30', 'user', '2025-11-02 19:44:12'),
(382, 584018126, 'u584018126_1762110946911_ch2zye', 'Cảm ơn bạn! Lịch tư vấn của bạn đã được đặt vào **03/11/2025 19:30**. Nhân viên chúng tôi sẽ gọi cho bạn đúng giờ. Vui lòng để ý điện thoại cá nhân trong khoảng thời gian đặt lịch. Cảm ơn bạn.', 'bot', '2025-11-02 19:44:12'),
(383, 584018126, 'u584018126_1762193991870_bsl6qo', 'tôi muốn mua son', 'user', '2025-11-03 18:20:11'),
(384, 584018126, 'u584018126_1762193991870_bsl6qo', 'Bạn muốn mua son. Dưới đây là một số gợi ý:\n\n- Son dưỡng Dior Addict Lip Glow – Dior – Son Dưỡng – 950000 VND\n- Son Môi gecomo Chất Nhung Lì Chống Thấm Nước – OLAY – Sale – 159000 VND\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer – GUCCI – Son Dưỡng – 100000 VND\n\nTôi chưa có thông tin về loại son bạn muốn mua. Bạn vui lòng cho tôi biết thêm chi tiết để tôi có thể hỗ trợ tốt hơn!\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', 'bot', '2025-11-03 18:20:40'),
(385, 584018126, 'u584018126_1762193991870_bsl6qo', 'có', 'user', '2025-11-03 18:20:49'),
(386, 584018126, 'u584018126_1762193991870_bsl6qo', 'Xin chào! Bạn muốn tìm sản phẩm gì?\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', 'bot', '2025-11-03 18:20:52'),
(387, 584018126, 'u584018126_1762193991870_bsl6qo', '04/11/2025 19:30', 'user', '2025-11-03 18:21:01'),
(388, 584018126, 'u584018126_1762193991870_bsl6qo', 'Cảm ơn bạn! Lịch tư vấn của bạn đã được đặt vào **04/11/2025 19:30**. Nhân viên chúng tôi sẽ gọi cho bạn đúng giờ. Vui lòng để ý điện thoại cá nhân trong khoảng thời gian đặt lịch. Cảm ơn bạn.', 'bot', '2025-11-03 18:21:01'),
(389, 584018126, 'u584018126_1762193991870_bsl6qo', 'tôi muốn mua sữa rửa mặt', 'user', '2025-11-03 18:42:59'),
(390, 584018126, 'u584018126_1762193991870_bsl6qo', 'Xin lỗi, hệ thống đang bận. Bạn vui lòng thử lại sau nhé.\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', 'bot', '2025-11-03 18:43:01'),
(391, 584018126, 'u584018126_1762193991870_bsl6qo', '05/11/2025 19:30', 'user', '2025-11-03 18:43:30'),
(392, 584018126, 'u584018126_1762193991870_bsl6qo', 'Cảm ơn bạn! Lịch tư vấn của bạn đã được đặt vào **05/11/2025 19:30**. Nhân viên chúng tôi sẽ gọi cho bạn đúng giờ. Vui lòng để ý điện thoại cá nhân trong khoảng thời gian đặt lịch. Cảm ơn bạn.', 'bot', '2025-11-03 18:43:30'),
(393, 584018126, 'u584018126_1762193991870_bsl6qo', 'tôi muốn mua kem dưỡng da', 'user', '2025-11-03 18:48:30'),
(394, 584018126, 'u584018126_1762193991870_bsl6qo', 'Tôi có thể gợi ý một số sản phẩm kem dưỡng da cho bạn. Tuy nhiên, tôi không thấy thông tin về các sản phẩm này trong danh sách.\n\nCó thể bạn muốn xem: \n- Kem dưỡng da của thương hiệu nào khác?\n- Bạn đang tìm kiếm loại kem dưỡng da nào (chống nắng, không chống nắng,...)?\n\nHãy cho tôi biết thêm thông tin để tôi có thể giúp đỡ bạn tốt hơn!\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', 'bot', '2025-11-03 18:48:41'),
(395, 584018126, 'u584018126_1762193991870_bsl6qo', 'kem chống nắng', 'user', '2025-11-03 18:49:10'),
(396, 584018126, 'u584018126_1762193991870_bsl6qo', 'Bạn có thể tham khảo các sản phẩm chống nắng sau:\n\n- Kem chống nắng kiềm dầu LUMOS SUN CREAM - DOVE - Kem Chống Nắng - 89.000 VND\n- Kem chống nắng dạng nước Catrice - CATRICE - Kem Chống Nắng (chưa rõ giá)\n\nHãy cho tôi biết bạn cần thêm thông tin gì nhé!\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', 'bot', '2025-11-03 18:49:19'),
(397, 584018126, 'u584018126_1762193991870_bsl6qo', 'tôi muốn mua son môi', 'user', '2025-11-03 18:58:30'),
(398, 584018126, 'u584018126_1762193991870_bsl6qo', 'Tôi có thể giúp bạn chọn một sản phẩm son môi phù hợp!\n\nBạn có muốn mua son lì hoặc son dưỡng không?\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', 'bot', '2025-11-03 18:58:38'),
(399, 584018126, 'u584018126_1762193991870_bsl6qo', 'tôi muốn mua son lì', 'user', '2025-11-03 19:00:03'),
(400, 584018126, 'u584018126_1762193991870_bsl6qo', 'Nếu bạn muốn mua son lì, có thể tham khảo các sản phẩm sau:\n\n- Son Môi gecomo Chất Nhung Lì Chống Thấm Nước - OLAY - Sale - 159000 VND\n- (Chưa có thông tin về các sản phẩm khác trong danh mục \"Son Lì\", bạn cần cung cấp thêm thông tin hoặc danh sách cụ thể hơn để tôi có thể hỗ trợ tốt hơn.)\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', 'bot', '2025-11-03 19:00:13'),
(401, 584018126, 'u584018126_1762193991870_bsl6qo', 'tôi muốn mua nước hoa', 'user', '2025-11-03 19:04:43'),
(402, 584018126, 'u584018126_1762193991870_bsl6qo', 'Tôi có thể gợi ý một số sản phẩm nước hoa cho bạn. Tuy nhiên, tôi cần biết thương hiệu yêu thích của bạn để đưa ra những lựa chọn phù hợp.\n\nNếu bạn muốn mua nước hoa Dior, có thể tham khảo:\n\n- Sau Khi Mưa - Dior - Nước Hoa - 2.990.000 VND\n- J\'Adore - Dior - Nước Hoa - 3.490.000 VND\n\nHoặc nếu bạn quan tâm đến thương hiệu khác, vui lòng cho tôi biết để được tư vấn thêm!\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', 'bot', '2025-11-03 19:04:56'),
(403, 584018126, 'u584018126_1762193991870_bsl6qo', '2 sản phẩm đó đâu có trong danh mục sản phẩm của bạn ?', 'user', '2025-11-03 19:10:14'),
(404, 584018126, 'u584018126_1762193991870_bsl6qo', 'Tôi xin lỗi vì không có thông tin về 2 sản phẩm mà bạn đang tìm kiếm. Để giúp tôi trả lời chính xác hơn, vui lòng cho tôi biết tên của 2 sản phẩm đó là gì?\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', 'bot', '2025-11-03 19:10:20'),
(405, 584018126, 'u584018126_1762193991870_bsl6qo', 'tôi cần mua nước hoa, hãy giúp tôi tư vấn nhé', 'user', '2025-11-03 19:10:35'),
(406, 584018126, 'u584018126_1762193991870_bsl6qo', 'Bạn muốn đặt lịch tư vấn lúc nào ạ? Vui lòng trả lời theo định dạng **DD/MM/YYYY HH:MM** (ví dụ: 05/11/2025 14:30).', 'bot', '2025-11-03 19:10:35'),
(407, 584018126, 'u584018126_1762193991870_bsl6qo', '05/11/2025 20:00', 'user', '2025-11-03 19:10:46'),
(408, 584018126, 'u584018126_1762193991870_bsl6qo', 'Cảm ơn bạn! Lịch tư vấn của bạn đã được đặt vào **05/11/2025 20:00**. Nhân viên chúng tôi sẽ gọi cho bạn đúng giờ. Vui lòng để ý điện thoại cá nhân trong khoảng thời gian đặt lịch. Cảm ơn bạn.', 'bot', '2025-11-03 19:10:46'),
(409, 584018126, 'u584018126_1762193991870_bsl6qo', 'cảm ơn bạn', 'user', '2025-11-03 19:10:51'),
(410, 584018126, 'u584018126_1762193991870_bsl6qo', 'Không có vấn đề gì! Bạn cần tìm sản phẩm nào?\n\nBạn có muốn **đặt lịch tư vấn** không? Nếu có, hãy trả lời theo mẫu: `Đặt lịch DD/MM/YYYY HH:MM` (ví dụ: `Đặt lịch 05/11/2025 14:30`).', 'bot', '2025-11-03 19:10:53'),
(411, 584018126, 'u584018126_1762203986184_efrptc', 'tôi muốn mua son dưỡng', 'user', '2025-11-03 21:18:59'),
(412, 584018126, 'u584018126_1762203986184_efrptc', 'Tôi có thể gợi ý cho bạn một số sản phẩm son dưỡng như sau:\n\n- Son Dưỡng Di.or Collagen Addict Lip Maximizer - GUCCI - Son Dưỡng - 100000 VND\n- Son dưỡng Dior Addict Lip Glow - SELENA STORE - Son Dưỡng - 950000 VND\n\nNếu bạn cần thêm thông tin hoặc muốn biết thêm các sản phẩm khác, vui lòng cho tôi biết!\n\nBạn có muốn **đặt lịch để nhân viên bên mình tư vấn cho bạn không?** (Có/Không)\nNếu **Có**, bạn có thể **chọn ngày/giờ** ngay tại đây hoặc nhập theo mẫu **DD/MM/YYYY HH:MM**.\n[[BOOKING_FORM]]', 'bot', '2025-11-03 21:19:13'),
(413, 584018126, 'u584018126_1762377837836_rhwfy6', 'tôi muốn mua son', 'user', '2025-11-05 21:29:22'),
(414, 584018126, 'u584018126_1762377837836_rhwfy6', 'Xin lỗi, mô hình đang bận. Vui lòng thử lại sau.\n\nBạn có muốn **đặt lịch để nhân viên bên mình tư vấn cho bạn không?** (Có/Không)', 'bot', '2025-11-05 21:29:22'),
(415, 584018126, 'u584018126_1762377837836_rhwfy6', 'tôi muốn mua son môi', 'user', '2025-11-05 21:35:10'),
(416, 584018126, 'u584018126_1762377837836_rhwfy6', 'Bạn muốn đặt lịch tư vấn vào thời gian nào ạ? Bạn hãy chọn ở **biểu mẫu dưới đây** hoặc nhập theo mẫu **DD/MM/YYYY HH:MM** (ví dụ: 05/11/2025 14:30).\n[[BOOKING_FORM]]', 'bot', '2025-11-05 21:35:10'),
(417, 584018126, 'u584018126_1762377837836_rhwfy6', '07/11/2025 19:22', 'user', '2025-11-05 21:35:26'),
(418, 584018126, 'u584018126_1762377837836_rhwfy6', 'tôi muốn mua hàng', 'user', '2025-11-05 21:43:22'),
(419, 584018126, 'u584018126_1762377837836_rhwfy6', 'Bạn muốn đặt lịch tư vấn vào thời gian nào ạ? Bạn hãy chọn ở **biểu mẫu dưới đây** hoặc nhập theo mẫu **DD/MM/YYYY HH:MM** (ví dụ: 05/11/2025 14:30).\n[[BOOKING_FORM]]', 'bot', '2025-11-05 21:43:22'),
(420, 584018126, 'u584018126_1762377837836_rhwfy6', 'tư vấn cho tôi son môi', 'user', '2025-11-05 21:43:32'),
(421, 584018126, 'u584018126_1762377837836_rhwfy6', 'Bạn muốn đặt lịch tư vấn vào thời gian nào ạ? Bạn hãy chọn ở **biểu mẫu dưới đây** hoặc nhập theo mẫu **DD/MM/YYYY HH:MM** (ví dụ: 05/11/2025 14:30).\n[[BOOKING_FORM]]', 'bot', '2025-11-05 21:43:32'),
(422, 584018126, 'u584018126_1762377837836_rhwfy6', '06/11/2025 19:30', 'user', '2025-11-05 21:43:55'),
(423, 584018126, 'u584018126_1762380728619_zm9hlv', 'mình muốn mua son môi', 'user', '2025-11-05 22:12:17'),
(424, 584018126, 'u584018126_1762380728619_zm9hlv', 'Xin lỗi, mô hình đang bận. Vui lòng thử lại sau.', 'bot', '2025-11-05 22:12:18'),
(425, 584018126, 'u584018126_1762581602995_o7y1s1', 'tôi muốn mua son môi', 'user', '2025-11-08 06:00:18'),
(426, 584018126, 'u584018126_1762581602995_o7y1s1', 'Xin lỗi, mô hình đang bận. Vui lòng thử lại sau.', 'bot', '2025-11-08 06:00:45'),
(427, 584018126, 'u584018126_1762582817703_miii53', 'tôi muốn mua son môi', 'user', '2025-11-08 06:20:22'),
(428, NULL, 'u0_1762624550835_uei27b', 'Chào bạn! Mình là trợ lý bán hàng tại Mơ Shop. Hôm nay bạn muốn mua gì ạ?', 'bot', '2025-11-08 10:55:51'),
(429, NULL, 'u0_1762626826567_qfzqp3', 'Chào bạn! Mình là trợ lý bán hàng tại Mơ Shop. Hôm nay bạn muốn mua gì ạ?', 'bot', '2025-11-08 11:33:46');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chat_session_state`
--

CREATE TABLE `chat_session_state` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(64) NOT NULL,
  `state` enum('idle','await_schedule_confirm','await_datetime') NOT NULL DEFAULT 'idle',
  `lang` varchar(8) DEFAULT NULL,
  `tmp_note` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `chat_session_state`
--

INSERT INTO `chat_session_state` (`id`, `user_id`, `session_id`, `state`, `lang`, `tmp_note`, `updated_at`) VALUES
(1, 14, 'yo9b6scu231760348606010', 'idle', 'vi', NULL, '2025-10-14 19:27:55');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `consultations`
--

CREATE TABLE `consultations` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(64) DEFAULT NULL,
  `appointment_time` datetime NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `status` enum('requested','confirmed','done','cancelled') NOT NULL DEFAULT 'requested',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `consultations`
--

INSERT INTO `consultations` (`id`, `user_id`, `session_id`, `appointment_time`, `note`, `status`, `created_at`) VALUES
(1, 15, 'yo9b6scu231760348606010', '2025-10-20 19:30:00', 'From chat', 'requested', '2025-10-20 08:52:34'),
(2, 15, 'yo9b6scu231760348606010', '2025-10-22 06:30:00', 'From chat', 'requested', '2025-10-20 17:35:05');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `customer_needs`
--

CREATE TABLE `customer_needs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(64) DEFAULT NULL,
  `need_text` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `customer_needs`
--

INSERT INTO `customer_needs` (`id`, `user_id`, `session_id`, `need_text`, `created_at`) VALUES
(1, 13, 'yo9b6scu231760348606010', 'tôi muốn mua hàng', '2025-10-13 17:36:18'),
(2, 13, 'yo9b6scu231760348606010', 'tôi muốn mua son', '2025-10-13 17:38:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `danhmuc`
--

CREATE TABLE `danhmuc` (
  `IDDanhMuc` int NOT NULL,
  `TenDanhMuc` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `AnhDanhMuc` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `danhmuc`
--

INSERT INTO `danhmuc` (`IDDanhMuc`, `TenDanhMuc`, `AnhDanhMuc`) VALUES
(41, 'Son Dưỡng', 'IMG_Category/bgsonduong.png'),
(42, 'Kem Chống Nắng', 'IMG_Category/bg.png'),
(43, 'Kem Dưỡng', 'IMG_Category/kemduong.png'),
(44, 'Gel Rửa Mặt', 'IMG_Category/gelruamat.png'),
(45, 'Mặt Nạ', 'IMG_Category/matna.png'),
(46, 'Nước Tẩy Trang', 'IMG_Category/bg.png'),
(47, 'Bộ Makeup', 'IMG_Category/bomakeup.png'),
(51, 'Kem Nền', 'IMG_Category/danhmuckemnen.png'),
(52, 'Sale', 'IMG_Category/sale.png');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `danhsachyeuthich`
--

CREATE TABLE `danhsachyeuthich` (
  `ID_DSYeuThich` int NOT NULL,
  `ID_KhachHang` int NOT NULL,
  `ID_SanPham` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `danhsachyeuthich`
--

INSERT INTO `danhsachyeuthich` (`ID_DSYeuThich`, `ID_KhachHang`, `ID_SanPham`) VALUES
(22, 11, 52),
(23, 11, 54),
(24, 11, 37),
(25, 11, 49);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `donhang`
--

CREATE TABLE `donhang` (
  `ID_DonHang` int NOT NULL,
  `ID_KhachHang` int NOT NULL,
  `HoVaTen` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `SDT` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `DiaChi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `TongTien` int NOT NULL,
  `TrangThai` int NOT NULL,
  `DaThanhToan` tinyint(1) NOT NULL DEFAULT '0',
  `GhiChu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `donhang`
--

INSERT INTO `donhang` (`ID_DonHang`, `ID_KhachHang`, `HoVaTen`, `Email`, `SDT`, `DiaChi`, `TongTien`, `TrangThai`, `DaThanhToan`, `GhiChu`) VALUES
(37, 11, 'Trọng Huy', 'huy123@gmail.com', '0325324064', '32 Nguyễn Tạo, Hòa Hải, Ngũ Hành Sơn, Đà Nẵng', 298000, 3, 1, 'QR: Nước Tẩy Trang La Roche-Posay Làm Sạch Sâu Dành Cho Da Dầu Mụn Và Da Thườngx2'),
(38, 11, 'Trọng Huy', 'huy123@gmail.com', '0325324064', '32 Nguyễn Tạo, Hòa Hải, Ngũ Hành Sơn, Đà Nẵng', 819000, 3, 0, 'QR: Sữa Rửa Mặt LUMOS PERFECT PEARL CLEANSER Cân bằng độ PH x1'),
(44, 11, 'Trọng Huy', 'huy12334@gmail.com', '0325324064', '32 Nguyễn Tạo, Hòa Hải, Ngũ Hành Sơn, Đà Nẵng', 149000, 3, 1, 'đã chuyển khoản | QR: Nước Tẩy Trang La Roche-Posay Làm Sạch Sâu Dành Cho Da Dầu Mụn Và Da Thườngx1');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `donhangchitiet`
--

CREATE TABLE `donhangchitiet` (
  `ID_DonHangChiTiet` int NOT NULL,
  `ID_DonHang` int NOT NULL,
  `ID_SanPham` int NOT NULL,
  `SoLuong` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `donhangchitiet`
--

INSERT INTO `donhangchitiet` (`ID_DonHangChiTiet`, `ID_DonHang`, `ID_SanPham`, `SoLuong`) VALUES
(65, 37, 52, 2),
(66, 38, 48, 1),
(72, 44, 52, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khachhang`
--

CREATE TABLE `khachhang` (
  `ID_KhachHang` int NOT NULL,
  `TenDangNhap` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `MatKhau` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `HoVaTen` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `SDT` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `DiaChi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `SoDu` bigint NOT NULL DEFAULT '0',
  `AnhDaiDien` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ThoiGianTao` datetime DEFAULT CURRENT_TIMESTAMP,
  `ThoiGianCapNhat` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `khachhang`
--

INSERT INTO `khachhang` (`ID_KhachHang`, `TenDangNhap`, `MatKhau`, `HoVaTen`, `Email`, `SDT`, `DiaChi`, `SoDu`, `AnhDaiDien`, `ThoiGianTao`, `ThoiGianCapNhat`) VALUES
(11, 'tronghuy123', '123', 'Trọng Huy', 'huy12334@gmail.com', '0325324064', '32 Nguyễn Tạo, Hòa Hải, Ngũ Hành Sơn, Đà Nẵng', 250000, '../../Image_upload/1760019686_g3.jpg', '2025-10-14 00:28:41', '2025-10-14 00:28:41'),
(13, 'tronghuy1', '123', 'huy', 'huy123@gmail.com', '0983147329', '101 lê thiện trị. ngũ hành sơn, đà nẵng', 0, '../../Image_upload/1760375416_z7056149318228_4cbac9d45e8461d217c7d21896556820.jpg', '2025-10-14 00:28:41', '2025-10-14 00:28:41'),
(14, 'tronghuy2', '123', 'Hùng', 'hungpham@gmail.com', '0909090090', '68 lê thiện trị, ngũ hành sơn, đà nẵng', 0, '../../Image_upload/1760470045_z7056149323883_f2fee6fbf579e04bffdd90efbef2e555.jpg', '2025-10-15 02:27:01', '2025-10-15 02:27:25'),
(18, 'huy4', '123', 'huy4', 'huy4@gmail.com', '0998475637', '10 lê thiện trị', 0, NULL, '2025-11-02 17:02:03', '2025-11-02 17:02:03'),
(21, 'huy2', '123', 'huy2', 'huy2@gmail.com', '0928374586', '102 lê thiện trị', 0, NULL, '2025-11-04 03:17:36', '2025-11-04 03:17:36'),
(22, 'huy3', '123', 'huy3', 'huy3@gmail.com', '0928848374', '111 hồ tùng mậu', 100000, NULL, '2025-11-08 14:49:16', '2025-11-08 15:02:55'),
(23, 'huy6', '123', 'huy6', 'huy6@gmail.com', '0987654321', '102 lê thiện trị', 0, NULL, '2025-11-09 03:08:11', '2025-11-09 03:08:11');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `leads`
--

CREATE TABLE `leads` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `HoTen` varchar(255) DEFAULT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `SDT` varchar(50) DEFAULT NULL,
  `NoiDung` text,
  `ThoiGianTao` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_message` text,
  `product_interest` varchar(255) DEFAULT NULL,
  `last_interaction` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lead_events`
--

CREATE TABLE `lead_events` (
  `id` bigint NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(128) NOT NULL,
  `direction` enum('user','bot') NOT NULL,
  `message` text NOT NULL,
  `intent` varchar(50) DEFAULT NULL,
  `product_interest` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `ts` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lead_scores`
--

CREATE TABLE `lead_scores` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(64) DEFAULT NULL,
  `label` enum('potential','neutral','spam') NOT NULL DEFAULT 'neutral',
  `probability` float DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `lead_scores`
--

INSERT INTO `lead_scores` (`id`, `user_id`, `session_id`, `label`, `probability`, `updated_at`) VALUES
(1, NULL, 'yo9b6scu231760348606010', 'neutral', NULL, '2025-10-26 16:21:37'),
(5, 11, NULL, 'potential', 0.9, '2025-10-20 00:55:11'),
(33, 13, NULL, 'potential', 0.9, '2025-10-14 07:32:04'),
(72, 14, NULL, 'potential', 0.9, '2025-10-14 19:29:26'),
(89, 15, NULL, 'potential', 0.9, '2025-10-20 17:35:31'),
(112, 16, NULL, 'neutral', NULL, '2025-10-23 15:28:53'),
(131, 17, NULL, 'neutral', NULL, '2025-10-26 16:21:53'),
(385, 18, NULL, 'neutral', NULL, '2025-11-02 10:02:09');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nap_tien`
--

CREATE TABLE `nap_tien` (
  `ID_Nap` int NOT NULL,
  `ID_KhachHang` int NOT NULL,
  `SoTien` bigint NOT NULL,
  `NoiDung` varchar(255) NOT NULL,
  `TrangThai` tinyint(1) NOT NULL DEFAULT '0',
  `NgayTao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `nap_tien`
--

INSERT INTO `nap_tien` (`ID_Nap`, `ID_KhachHang`, `SoTien`, `NoiDung`, `TrangThai`, `NgayTao`) VALUES
(1, 11, 100000, 'Nạp tiền vào tài khoản', 1, '2025-09-30 08:27:05'),
(2, 11, 150000, 'Nạp tiền vào tài khoản', 1, '2025-09-30 15:15:13'),
(3, 13, 100000, 'Nạp tiền vào tài khoản', 2, '2025-10-13 10:02:05'),
(4, 16, 100000, 'Nạp tiền vào tài khoản', 2, '2025-11-02 08:52:36'),
(5, 22, 100000, 'Nạp tiền vào tài khoản', 1, '2025-11-08 08:02:42');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sanpham`
--

CREATE TABLE `sanpham` (
  `IDSanPham` int NOT NULL,
  `TenSanPham` varchar(5000) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `IDDanhMuc` int NOT NULL,
  `IDThuongHieu` int NOT NULL,
  `SoLuong` int NOT NULL,
  `GiaTien` int NOT NULL,
  `MoTaChiTiet` varchar(10000) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `AnhSanPham` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `NoiBat` int NOT NULL,
  `ThoiGianTao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ThoiGianCapNhat` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `Gia` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `sanpham`
--

INSERT INTO `sanpham` (`IDSanPham`, `TenSanPham`, `IDDanhMuc`, `IDThuongHieu`, `SoLuong`, `GiaTien`, `MoTaChiTiet`, `AnhSanPham`, `NoiBat`, `ThoiGianTao`, `ThoiGianCapNhat`, `Gia`) VALUES
(37, 'Son dưỡng Dior Addict Lip Glow SELENA STORE', 41, 18, 10, 950000, '<figure class=\"image\"><img style=\"aspect-ratio:1024/1024;\" src=\"../../../Image_upload/ChonmautheoToneDa.png\" width=\"1024\" height=\"1024\"></figure><p>&nbsp;sáng tiện lợi.</p>', 'IMG_Products/bgsonduong.png', 1, '2024-10-10 16:56:30', '2024-11-20 16:49:44', NULL),
(45, 'Son Dưỡng Di.or Collagen Addict Lip Maximizer', 41, 17, 10, 100000, '<p><br>&nbsp;MÔ TẢ SẢN PHẨM</p><p>&nbsp;- Tên sản phẩm:</p><p>&nbsp;+ Son Dưỡng Di.or Collagen Addict Lip Maximizer 012 Rosewood - Hồng cam cánh hoa nhẹ nhàng&nbsp;</p><figure class=\"image\"><img style=\"aspect-ratio:1024/1024;\" src=\"../../../Image_upload/012camnhe.png\" width=\"1024\" height=\"1024\"></figure><p>+ Son Dưỡng Di.or Collagen Addict Lip Maximizer 024 Intense Brick - Đỏ gạch&nbsp;</p><p>+ Son Dưỡng Di.or Collagen Addict Lip Maximizer 028 - Đỏ hồng nâu&nbsp;</p><p>- Công Thức: Dạng Kem ƯU ĐIỂM NỔI BẬT 1.Son Dưỡng Di.or Collagen Addict Lip Maximizer 012 Rosewood&nbsp;</p><p>- Hồng cam cánh hoa nhẹ nhàng&nbsp;</p><p>- Son dưỡng có công thức dưỡng môi khác biệt và hỗ trợ tối ưu cho đôi môi bạn trở nên tươi tắn, căng mọng đầy sức sống.&nbsp;</p><p>- Son Di.or Collagen Addict Lip 012 còn được bổ sung thêm collagen giúp tái cấu trúc lại màu môi nhờ vào hyaluronic acid với chất làm đầy water-based làm mướt, ẩm và căng môi.&nbsp;</p><p>- Di.or Collagen Addict Lip maximize 012 cho màu sắc hồng cam tư nhiên cho đôi môi duy trì màu son môi luôn tươi tắn và không bị xỉn màu.&nbsp;</p><p>- Son còn có SPF10 giúp môi tránh khỏi các tác hại do ánh sáng mặt trời gây ra, làm giàu và dưỡng ẩm cho đôi môi suốt cả ngày 2.Son Dưỡng Di.or Collagen Addict Lip Maximizer 024 Intense Brick&nbsp;</p><p>- Đỏ gạch - Dior dưỡng 028 giúp tái cấu trúc lại màu môi nhờ vào hyaluronic acid với chất làm đầy water-based làm mướt, ẩm và căng môi.&nbsp;</p><p>- Là tông son không kén da, được săn đón nhiệt tình thời điểm đầu ra mắt. Son nữ thơm, ngọt ngào và nhiều dưỡng. 3.Son Dưỡng Di.or Collagen Addict Lip Maximizer 028&nbsp;</p><p>- Đỏ hồng nâu - Mang đến cho các nàng một đôi môi căng mọng cuốn hút khoẻ mạnh từ bên trong. Cấu trúc dạng kem giúp các thành phần dưỡng có trong son thấm nhanh hơn qua các rãnh môi thiếu ẩm.&nbsp;</p><p>- Mang hương thơm nhẹ dịu, cấu trúc lỏng nhẹ của thỏi son không hề có cảm giác bết dính nặng môi của một cây son dưỡng thông thường.&nbsp;</p><p>KHI KHÁCH HÀNG ĐẾN VỚI ORINS KHÁCH HÀNG SẼ ĐƯỢC NHẬN CÁC DỊCH VỤ&nbsp;</p><p>- Khi mua 1 thỏi son AUTH tại shop, khách hàng sẽ được tặng 1 QUÀ TẶNG BẤT KÌ như nước hoa, mặt nạ,.. có sẵn tại shop.&nbsp;</p><p>- Khách hàng sẽ nhận đủ sản phẩm gồm: túi và hộp đựng đi kèm của hãng&nbsp;</p><p>- Khách hàng sẽ được hỗ trợ gói quà và thiệp (chi phí trao đổi) khi mua son AUTH tại shop làm quà tặng.&nbsp;</p><p>ORINS CAM KẾT - CAM KẾT 100% hàng Auth - HOÀN TIỀN 200% nếu phát hiện hàng giả, hàng nhái, hàng kém chất lượng&nbsp;</p><p>- Tặng VOUCHER lên đến 50% khi mua hàng lần 2 CHÍNH SÁCH ĐỔI TRẢ&nbsp;</p><p>- Quý khách vui lòng quay lại video khi mở hàng&nbsp;</p><p>- Hỗ trợ hoàn hàng trong vòng 7 ngày theo đúng quy định&nbsp;</p><p>- Hàng hoá bị lỗi hoặc hư hỏng do vận chuyển</p><figure class=\"image\"><img style=\"aspect-ratio:943/413;\" src=\"../../../Image_upload/CamKet.png\" width=\"943\" height=\"413\"></figure>', 'IMG_Products/DiorCollange.png', 0, '2024-11-19 18:07:57', '2024-11-19 18:07:57', NULL),
(46, 'Kem chống nắng kiềm dầu LUMOS SUN CREAM', 42, 5, 11, 89000, '<p><br>Kem Chống Nắng Phổ Rộng Kiềm Dầu - Lumos Sun Cream</p><p>Dòng Kem chống nắng LUMOS Sun Cream từ Hàn Quốc với công nghệ Ultra Matrix 3D - DNA Damage Control giúp chống nắng mạnh mẽ cho làn da khỏi các loại tia UVA/UVB, ánh sáng xanh,... Thúc đẩy tái tạo da và bảo vệ da khỏi khỏi bụi mịn. Đặc biệt được nghiên cứu và thiết kế dành riêng cho làn da phụ nữ châu Á.</p><p>&nbsp;</p><p>CÔNG DỤNG</p><p>- Chống nắng phổ rộng, SPF 50 PA++++</p><p>- Kiềm dầu suốt 8 tiếng</p><p>- Sửa chưa DNA, phục hồi da</p><p>- Làm dịu nhẹ làn da Treatment</p><figure class=\"image\"><img style=\"aspect-ratio:1024/1024;\" src=\"../../../Image_upload/Lumocdailysuncream.png\" width=\"1024\" height=\"1024\"></figure><p>THÀNH PHẦN:</p><p>&nbsp;</p><p>Water, Cyclopentasiloxane, Butylene Glycol, Glycerin, Ethylhexyl Salicylate, Ethylhexyl Methoxycinnamate, Cetyl PEG/PPG-10/1 Dimethicone, Silica, Isoamyl Laurate, Niacinamide, 1,2-Hexanediol, Sorbitan Olivate, Titanium Dioxide, Cyclohexasiloxane, Quaternium-18 Bentonite, Phenyl Trimethicone, Octocrylene, Cetyl Ethylhexanoate, Bis-Ethylhexyloxyphenol Methoxyphenyl Triazine, Sodium Chloride, Sorbitan Sesquioleate, Ceresin, Beeswax, Fragrance, Dimethicone/Vinyl Dimethicone Crosspolymer, Tocopheryl Acetate, Aluminum Hydroxide, Adenosine, Limonene, Triethoxycaprylylsilane, Disodium EDTA, Allantoin, Iron Oxide Red (CI 77491), Aloe Barbadensis Leaf Extract, Carnosine Extract, Linalool, Maltodextrin, BTH, Tocopherol, sodium DNA, RNA, Panthenol, Tromethamin</p><p>HƯỚNG DẪN SỬ DỤNG</p><p>( theo video dướng dẫn )</p><p>ĐỐI TƯỢNG SỬ DỤNG</p><p>Thích hợp cho mọi loại da</p><p>&nbsp;</p><p>THÔNG TIN THƯƠNG HIỆU</p><p>Thương hiệu: LUMOS</p><p>Xuất xứ: HÀN QUỐC</p><p>Dung tích: 40g</p><p>Hạn sử dụng: 03 năm kể từ ngày sản xuất</p><p>Thông tin ngày sản xuất: Xem trên bao bì</p><p>Bảo quản: Xem trên bao bì</p><p>Lưu ý: Xem trên bao bì</p><figure class=\"image\"><img style=\"aspect-ratio:1024/1024;\" src=\"../../../Image_upload/thongtinthuonghieu.png\" width=\"1024\" height=\"1024\"></figure><p>CAM KẾT BÁN HÀNG</p><p>- 100% sản phẩm là hàng chính hãng, có nguồn gốc xuất xứ rõ ràng.</p><p>- Cam kết đổi, trả hàng trong vòng 07 ngày nếu sản phẩm hỏng, lỗi do lỗi của Nhà sản xuất.</p><p>- Hỗ trợ miễn phí vận chuyển cho đơn hàng.</p><p>- Nhận ngay Voucher 200.000 VNĐ cho đơn hàng từ 2.500.000 đ.</p><p>- Tư vấn và chăm sóc khách hàng tận tâm.</p><figure class=\"image\"><img style=\"aspect-ratio:943/413;\" src=\"../../../Image_upload/CamKet.png\" width=\"943\" height=\"413\"></figure><p><br>&nbsp;</p>', 'IMG_Products/Lumocdailysuncream.png', 1, '2024-11-19 18:13:18', '2024-11-20 16:49:51', NULL),
(47, 'Bộ Kem dưỡng ẩm & làm sáng LAGUMI ', 41, 11, 15, 139000, '<h2>MÔ TẢ SẢN PHẨM</h2><p>&nbsp;THÔNG TIN THƯƠNG HIỆU</p><p>LAGUMI là thương hiệu chăm sóc cá nhân toàn diện mang đến các sản phẩm AN TOÀN &amp; LÀNH TÍNH. Toàn bộ sản phẩm của LAGUMI dành cho các chị em yêu thích làm đẹp, yêu thích các sản phẩm chiết xuất THIÊN NHIÊN. Đặc biệt MẸ BẦU &amp; SAU SINH HOÀN TOÀN YÊN TÂM SỬ DỤNG</p><p>&nbsp;</p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lpu9fmes0fg76c@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lpu9fmes0fg76c@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lpu9fmes0fg76c\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lpu9fmes0fg76c@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lpu9fmes0fg76c@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p>&nbsp;CAM KẾT TỪ NHÃN HÀNG</p><p>Chính hãng 100%</p><p>Đổi trả miễn phí trong 7 ngày</p><p>&nbsp;THÔNG TIN SẢN PHẨM</p><p>Bộ sản phẩm bao gồm</p><p>- Sữa rửa mặt Lagumi 50ml</p><p>- Nước hoa hồng giúp cân bằng da 120ml</p><p>- Serum Lagumi cho da dầu, mụn với thành phần Calendula, siêu phân từ HA cấp ẩm sâu 30ml</p><p>- Kem dưỡng ẩm Lagumi 30gr</p><p>Công dụng chính: sạch sâu, dưỡng ẩm, sáng da</p><p>Xuất xứ: Việt Nam</p><p>Hạn dử dụng: 24 tháng kể từ NSX</p><p>NSX: in trên bao bì</p><p>&nbsp;FEEDBACK KHÁCH HÀNG</p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lpu9fmes1u0n07@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lpu9fmes1u0n07@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lpu9fmes1u0n07\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lpu9fmes1u0n07@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lpu9fmes1u0n07@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p>THÔNG TIN CHI TIẾT</p><p>SỮA RỬA MẶT LAGUMI - LỰA CHỌN HOÀN HẢO CHO LÀN DA NHẠY CẢM</p><p>Với độ pH chuẩn, sữa rửa mặt Lagumi nhẹ nhàng loại bỏ tất cả các tạp chất mà vẫn bảo vệ tuyệt đối màng ẩm tự nhiên trên da.</p><p>Phức hợp hoa cúc rau má: Tăng cường sức mạnh hàng rào tự nhiên với tác dụng dưỡng ẩm, chống Oxy hóa và làm dịu da.</p><p>Độ pH thấp (5.5 - 6.5) - Làm sạch vừa đủ, không gây khô da</p><p>Kết cấu kem đặc - Chuyển hóa thành bọt mịn khi được đánh bông lên giảm thiểu lực tác động làm da chảy xệ</p><p>&nbsp;</p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-llvszl94q7z351@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-llvszl94q7z351@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-llvszl94q7z351\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-llvszl94q7z351@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-llvszl94q7z351@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p>--------------------------</p><p>NƯỚC HOA HỒNG CÂN BẰNG PH LÀM MỀM VÀ DỊU DA LAGUMI CALENDULA 120ML</p><p>- Làm dịu da &amp; cân bằng độ pH sau bước làm sạch.</p><p>- Hạn chế tiết dầu trên da nhờ việc cấp nước, giữ ẩm một cách tự nhiên.</p><p>- Chiết xuất cây phỉ &amp; cúc la mã giú</p><p>THÀNH PHẦN</p><p>Chiết suất rau má, cúc La mã, Lô hội, cúc la mã, vitamin B5...</p><p>------------------------</p><p>SERUM HA LAGUMI - DA MỘC CĂNG MƯỚT</p><p>Sở hữu ba công dụng nổi bật nhờ vào các thành phần được chọn lọc kỹ lưỡng, serum HA Lagumi chính là bảo bối dưỡng ẩm cho mọi làn da.</p><p>Thành phần rau má đặc trưng mang đến sự dễ chịu, hạn chế tối đa kích ứng cho da, hỗ trợ phục hồi, dịu da</p><p>&nbsp;VITAMIN B5 - Làm dịu phục hồi da</p><p>&nbsp;HYALURONIC ACID ĐA KÍCH THƯỚC (một axit amin tự nhiên trong cơ thể) - dưỡng ẩm</p><p>sâu, phục hồi giúp làn da căng mọng và tươi trẻ</p><p>ALLANTOIN (chiết xuất từ cây liên mộc có nguồn gốc từ vùng khí hậu ôn đới của châu Á và có trong cơ thế) - giúp cấp ẩm, làm dịu da, giảm kích ứng da</p><p>&nbsp;VITAMIN B3 - làm sáng da tự nhiên nhờ cải thiện sắc tố, chống lão hóa</p><p>DƯA LEO, LÔ HỘI - dưỡng ẩm, thu nhỏ lỗ chân lông, dịu da</p><p>&nbsp;</p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7qukw-lfawgkja4metc4@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7qukw-lfawgkja4metc4@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7qukw-lfawgkja4metc4\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7qukw-lfawgkja4metc4@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7qukw-lfawgkja4metc4@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p>--------------------</p><p>KEM DƯỠNG ẨM DÀNH RIÊNG CHO DA DẦU, DA MỤN LAGUMI CALENDULA GEL CREAM B5</p><p>Kem dưỡng giảm mụn, hạn chế mụn lan rộng với chiết xuất Cúc Calendula, Rau Má.</p><p>Chất kem dạng Gel Cream giúp tinh chất thấm sâu vào da nhanh chóng.</p><p>Không sillicone, không paraben, không hương liệu, không hóa chất độc hại</p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7qukw-lfawgkja60z915@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7qukw-lfawgkja60z915@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7qukw-lfawgkja60z915\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7qukw-lfawgkja60z915@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7qukw-lfawgkja60z915@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p>&nbsp;</p><p>THÀNH PHẦN: Tinh chất rau má kết hợp Cúc la mã, Vitamin B3 (Niaciamine), Vitamin B5</p><p>Vitamin B5:</p><p>Hoa cúc Cellulose</p><p>&nbsp;</p><p>2. ĐỐI TƯỢNG SỬ DỤNG:</p><p>Mọi loại da. Da nhạy cảm, hay đỏ, dễ ngứa, dễ kích ứng. Da yếu hoặc sau tổn thương. Da dầu, da nhờn mụn. Đặc biệt có thể sử dụng cho mẹ bầu và sau sinh.</p><p>&nbsp;</p><p>3. ROUTINE CHĂM DA LAGUMI</p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7qukw-ljauxdid4jkc1e@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7qukw-ljauxdid4jkc1e@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7qukw-ljauxdid4jkc1e\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7qukw-ljauxdid4jkc1e@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7qukw-ljauxdid4jkc1e@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p>&nbsp;</p><p>4. HƯỚNG DẪN SỬ DỤNG:</p><p>BƯỚC 1: Rửa sạch tay và làm ướt da mặt. Sau đó rửa mặt với Sữa rửa mặt tạo bọt sạch sâu ngừa mụn phù hợp mọi loại da Lagumi Calendula</p><p>BƯỚC 2: Cân bằng pH trên da và làm dịu da với Nước hoa hồng cân bằng pH làm mềm và dịu da Lagumi Calendula.</p><p>BƯỚC 3: Sử dụng 2-3 giọt Serum Lagumi cho da dầu, mụn ra lòng bàn tay, thoa đều lên mặt và kết hợp massage nhẹ nhàng giúp serum thấm sâu vào da dễ dàng hơn.</p><p>BƯỚC 4: Kem dưỡng B5 Lagumi được sử dụng ở bước cuối cùng của chu trình dưỡng da. Lấy 1 lượng bằng hạt đậu chấm lên 5 điểm trên mặt, thoa đều lên da mỗi sáng và tối. Vỗ nhẹ để dưỡng chất thấm đều vào da.</p><p>*Nên dùng sản phẩm 2 lần/ngày vào buổi sáng và tối để có hiệu quả tốt nhất.</p><p>Lưu ý nên sử dụng kem chống nắng để đạt hiệu quả tối đa.</p>', 'IMG_Products/combocapam.png', 0, '2024-11-19 18:16:49', '2024-11-19 18:16:49', NULL),
(48, 'Sữa Rửa Mặt LUMOS PERFECT PEARL CLEANSER Cân bằng độ PH ', 44, 7, 30, 819000, '<h2>MÔ TẢ SẢN PHẨM</h2><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lzvh9n13f5wh91@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lzvh9n13f5wh91@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lzvh9n13f5wh91\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lzvh9n13f5wh91@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lzvh9n13f5wh91@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lzvi3c78ffpp61@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lzvi3c78ffpp61@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lzvi3c78ffpp61\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lzvi3c78ffpp61@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lzvi3c78ffpp61@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lzvi3c78ffz589@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lzvi3c78ffz589@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lzvi3c78ffz589\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lzvi3c78ffz589@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lzvi3c78ffz589@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lzvi3c78uw8162@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lzvi3c78uw8162@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lzvi3c78uw8162\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lzvi3c78uw8162@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lzvi3c78uw8162@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p>LUMOS Perfect Pearl Cleanser (Sữa Rửa Mặt Dưỡng Ẩm)</p><p>&nbsp;</p><p>CÔNG DỤNG</p><p>&nbsp;</p><p>- Làm sạch bề mặt da dịu nhẹ, cho làn da tươi mát, mềm mại, không khô, căng.</p><p>- Kháng khuẩn, bảo vệ da, cân bằng độ pH</p><p>- Tiết chế dầu nhờn, giúp ngăn ngừa và cải thiện mụn trứng cá</p><p>- Chứa các thành phần làm sạch axit amin giúp cân bằng độ ẩm cho da</p><p>&nbsp;</p><p>THÀNH PHẦN</p><p>&nbsp;</p><p>Aqua, Glycerin, Sodium Cocoyl Glycinate, Sodium Lauroyl Aspartate, Peg-150 Distearate, Cocamidopropyl Betaine, Sodium Taurine Laurate, Corn Starch Modified, Lauric Acid, Myristic Acid, Cocamide Mea, Methylparaben, Phenoxyethanol, Carbomer, Methylisothiazolinone, Parfum</p><p>&nbsp;</p><p>HƯỚNG DẪN SỬ DỤNG</p><p>&nbsp;</p><p>Làm ướt da mặt, lấy một lượng sữa rửa mặt vừa đủ ra lòng bàn tay, tạo bọt và massage nhẹ nhàng lên da. Sau đó, rửa sạch mặt với nước.</p><p>&nbsp;</p><p>ĐỐI TƯỢNG SỬ DỤNG</p><p>Mọi loại da cơ bản (da thường, da khô, da dầu và da hỗn hợp).</p><p>&nbsp;</p><p>THÔNG TIN THƯƠNG HIỆU</p><p>Thương hiệu: LUMOS</p><p>Xuất xứ: Hồng Kông</p><p>Dung tích: 120g</p><p>Hạn sử dụng: 03 năm kể từ ngày sản xuất</p><p>Thông tin ngày sản xuất: Xem trên bao bì</p><p>Bảo quản: Xem trên bao bì</p><p>Lưu ý: Xem trên bao bì</p><p>&nbsp;</p><p>CAM KẾT BÁN HÀNG</p><p>&nbsp;</p><p>- 100% sản phẩm là hàng chính hãng, có nguồn gốc xuất xứ rõ ràng.</p><p>- Cam kết đổi, trả hàng trong vòng 07 ngày nếu sản phẩm hỏng, lỗi do lỗi của Nhà sản xuất.</p><p>- Hỗ trợ miễn phí vận chuyển cho đơn hàng.</p><p>- Nhận ngay Voucher 200.000 VNĐ cho đơn hàng từ 2.500.000 đ.</p><p>- Tư vấn và chăm sóc khách hàng tận tâm.</p>', 'IMG_Products/lumos.png', 1, '2024-11-19 18:18:58', '2024-11-20 16:49:54', NULL),
(49, 'COMBO 10 Miếng Mặt Nạ BANOBAGI PREMIUM Thế Hệ Mới, Dưỡng Da Chuyên Sâu Dành Cho Mọi Loại Da', 45, 16, 110, 159000, '<h2>MÔ TẢ SẢN PHẨM</h2><p>THÔNG TIN SẢN PHẨM</p><p>• Xuất xứ thương hiệu: Hàn Quốc</p><p>• Sản xuất tại: Hàn Quốc</p><p>• Dung tích: 30ml</p><p>• Hạn sử dụng: 3 năm kể từ ngày sản xuất/NSX xem trên bao bì sản phẩm.</p><p>&nbsp;</p><p>CÔNG DỤNG VÀ THÀNH PHẦN</p><p>Dòng mặt nạ Banobagi Vita Genic gồm 4 loại cung cấp các loại Vitamin khác nhau:</p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3ewbp82@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3ewbp82@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3ewbp82\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3ewbp82@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3ewbp82@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p>Vita Genic Jelly Mask Lifting - Vitamin A (Đỏ): Vitamin A được chiết xuất từ loại cây xương rồng tại đảo Jeju Hàn Quốc hỗ trợ gia tăng đàn hồi cho da</p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3gaw5b9@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3gaw5b9@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3gaw5b9\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3gaw5b9@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3gaw5b9@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p>&nbsp;</p><p>Vita Genic Jelly Mask Relaxing - Vitamin B (Xanh Lá): Bổ sung Vitamin B và chứa tinh chất lô hội đảo Jeju có đặc tính làm dịu da bị tổn thương,, cháy nắng, kích ứng</p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3hpgl74@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3hpgl74@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3hpgl74\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3hpgl74@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3hpgl74@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p>- Vita Genic Jelly Mask Hydrating - Vitamin E (Xanh Dương): Chứa khoáng chất từ nước khoáng tinh khiết đảo Jeju giàu các khoáng chất như magie, kali,...dưỡng ẩm tối đa, duy trì và cải thiện làn da thiếu nước. Giúp da luôn trong trạng thái căng mọng.</p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3j41111@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3j41111@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3j41111\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3j41111@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3j41111@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p>&nbsp;</p><p>Vita Genic Jelly Mask Whitening - Vitamin C (Vàng): Bổ sung Vitamin C, được chiết xuất từ quả cam trên hòn đảo Jeju , loại bỏ các sắc tố xỉn màu trên da, làm đều màu da</p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3kilh2a@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3kilh2a@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3kilh2a\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3kilh2a@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3kilh2a@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p>&nbsp;</p><p>Vita Genic Jelly Mask Cica - Centella Complex (Xanh Ngọc): Chứa : B1, B2, B3, B5, B7, B9, B12, C, E giúp nuôi dưỡng và phục hồi làn da thô ráp, da nhạy cảm bị thương tổn. Bổ sung độ ẩm và dưỡng chất cho làn da khô,</p><p>&nbsp;</p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3lx5xfc@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3lx5xfc@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3lx5xfc\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3lx5xfc@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3lx5xfc@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p>- Vita Genic Jelly Mask Vitalizing - Collagen (Tím): Thành phần Hydrolyened Collagen và chiết xuất hoa anh đào Jeju làm tăng sức sống cho làn da</p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3nbqd81@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3nbqd81@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3nbqd81\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3nbqd81@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-lukonuh3nbqd81@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p>Pore Tightening - Anti-Sebum P (Hồng): Có thành phần từ nước suối núi núi Sanbang ở Jeju chăm sóc lỗ chân lông to và kiểm soát bã nhờn</p><p>&nbsp;</p><p>CÁCH SỬ DỤNG</p><p>- Trước khi sử dụng, các bạn nên bóp sản phẩm để gel thấm đều mặt nạ..</p><p>- Nhẹ nhàng đắp mặt nạ lên mặt và thư giãn trong 15 phút.</p><p>- Sau khi tháo mặt nạ, dùng tay vỗ đều khuôn mặt để dưỡng chất được hấp thụ hoàn toàn.</p><p>- Sử dụng 2-3 lần mỗi tuần và có thể thay phiên các loại mặt nạ để cung cấp đầy đủ dưỡng chất cho da.</p>', 'IMG_Products/combo10matna.png', 0, '2024-11-19 18:21:29', '2024-11-19 18:21:29', NULL),
(50, '[SKINFOOD Official] 30 miếng mặt nạ dưỡng da hàng ngày Cà rốt, quả sồi, Yuja C / Daily Sheet Mask', 52, 8, 112, 390000, '<h2>MÔ TẢ SẢN PHẨM</h2><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rccb-lr72vozzzkmgda@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/sg-11134208-7rccb-lr72vozzzkmgda@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rccb-lr72vozzzkmgda\" srcset=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rccb-lr72vozzzkmgda@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/sg-11134208-7rccb-lr72vozzzkmgda@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rcev-lr72vp000z6we9@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/sg-11134208-7rcev-lr72vp000z6we9@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rcev-lr72vp000z6we9\" srcset=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rcev-lr72vp000z6we9@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/sg-11134208-7rcev-lr72vp000z6we9@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rccx-lr72vp002drca6@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/sg-11134208-7rccx-lr72vp002drca6@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rccx-lr72vp002drca6\" srcset=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rccx-lr72vp002drca6@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/sg-11134208-7rccx-lr72vp002drca6@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p>&nbsp;</p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rcca-lr72vp00c7qgc0@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/sg-11134208-7rcca-lr72vp00c7qgc0@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rcca-lr72vp00c7qgc0\" srcset=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rcca-lr72vp00c7qgc0@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/sg-11134208-7rcca-lr72vp00c7qgc0@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rcby-lr72vp00dmawbb@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/sg-11134208-7rcby-lr72vp00dmawbb@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rcby-lr72vp00dmawbb\" srcset=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rcby-lr72vp00dmawbb@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/sg-11134208-7rcby-lr72vp00dmawbb@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rcep-lr72vp0056w824@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/sg-11134208-7rcep-lr72vp0056w824@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rcep-lr72vp0056w824\" srcset=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rcep-lr72vp0056w824@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/sg-11134208-7rcep-lr72vp0056w824@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rcdv-lr72vp006lgo59@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/sg-11134208-7rcdv-lr72vp006lgo59@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rcdv-lr72vp006lgo59\" srcset=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rcdv-lr72vp006lgo59@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/sg-11134208-7rcdv-lr72vp006lgo59@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rccg-lr72vp008014d8@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/sg-11134208-7rccg-lr72vp008014d8@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rccg-lr72vp008014d8\" srcset=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rccg-lr72vp008014d8@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/sg-11134208-7rccg-lr72vp008014d8@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rcdd-lr72vp009elk31@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/sg-11134208-7rcdd-lr72vp009elk31@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rcdd-lr72vp009elk31\" srcset=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rcdd-lr72vp009elk31@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/sg-11134208-7rcdd-lr72vp009elk31@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rccc-lsmkzrmu29ns54@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/sg-11134208-7rccc-lsmkzrmu29ns54@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rccc-lsmkzrmu29ns54\" srcset=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rccc-lsmkzrmu29ns54@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/sg-11134208-7rccc-lsmkzrmu29ns54@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rccw-lr72vp00at606d@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/sg-11134208-7rccw-lr72vp00at606d@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rccw-lr72vp00at606d\" srcset=\"https://down-vn.img.susercontent.com/file/sg-11134208-7rccw-lr72vp00at606d@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/sg-11134208-7rccw-lr72vp00at606d@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p>&nbsp;Mặt Nạ Daily Sheet Mask 30ea / Carrot, Acorn, Yuja C</p><p>Mặt nạ miếng Cellulose trong suốt và bám dính tốt</p><p>&nbsp;Điểm chính</p><p>&nbsp;Mặt nạ Carrot Carotene Daily Mask</p><p>: Làm dịu và dưỡng ẩm tức thì</p><p>&nbsp;Mặt nạ Acorn Pore Peptide Daily Mask</p><p>: Phục hồi độ đàn hồi cho da, làm se khít lỗ chân lông</p><p>&nbsp;Mặt nạ Yuja C Vita Daily Mask</p><p>: Làm sáng da và ngăn ngừa lão hóa</p><p>&nbsp;Sản phẩm này dành cho:</p><p>1) Những người muốn cung cấp nhanh dưỡng chất cho làn da.</p><p>2) Những người cần dưỡng ẩm và làm dịu da (Carrot Carotene Daily Mask), làm se khít lỗ chân lông (Acorn Pore Peptide Daily Mask), giúp da trắng sáng rạng rỡ (Yuja C Vita Daily Mask)</p><p>&nbsp;Hiệu quả</p><p>- Cải thiện tình trạng làn da trước khi trang điểm</p><p>- Chăm sóc da bằng cách sử dụng 1 mặt nạ mỗi ngày</p><p>- Chăm sóc da hàng ngày nhanh chóng và dễ dàng</p><p>&nbsp;Câu chuyện thực phẩm</p><p>&nbsp;Cà rốt&nbsp;</p><p>Beta-Carotene từ dầu hạt cà rốt</p><p>Chúng tôi sử dụng cà rốt được chứng nhận hữu cơ, không thuốc trừ sâu đến từ đảo Jeju, dùng phương pháp hấp để chiết xuất tinh chất cà rốt tinh khiết.</p><p>Beta-Carotene từ dầu hạt cà rốt là thành phần chính để duy trì làn da khỏe mạnh.</p><p>&nbsp;</p><p>&nbsp;Quả sồi&nbsp;</p><p>Axit ellagic từ quả sồi</p><p>Những quả sồi vỏ mịn, chắc khỏe được thu hoạch từ những cây sồi quanh thung lũng hoang sơ của núi Jirisan. Giàu axit ellagic và tannin, quả sồi từ núi Jirisan giúp làm sạch lỗ chân lông và tăng cường độ đàn hồi cho da.</p><p>&nbsp;</p><p>&nbsp;Quýt Yuja&nbsp;</p><p>Vitamin C từ quýt Yuja</p><p>Chúng tôi sử những trái quýt Yuja hữu cơ nguyên chất đến từ vùng Goheung, tạo ra chiết xuất Yuja bằng Hệ thống chiết xuất phân phối Vita. Quýt Yuja chứa lượng vitamin C nhiều gấp ba lần chanh, giúp ngăn ngừa lão hóa da hiệu quả. Thêm vào đó, Niacinamide còn tăng cường hiệu quả làm trắng da.</p><p>&nbsp;</p><p>&nbsp;Cách sử dụng</p><p>1) Sau khi rửa mặt, dùng kẹp lấy miếng mặt nạ ra và đắp đều lên mặt.</p><p>2) Sau 5 phút, gỡ bỏ miếng mặt nạ và vỗ nhẹ để giúp tinh chất còn lại hấp thụ vào da.</p><p>&nbsp;</p><p>&nbsp;Mẹo nhỏ!</p><p>Để mặt nạ trên da trong 10-20 phút để cấp ẩm thêm.</p>', 'IMG_Products/skinfood.png', 0, '2024-11-19 18:25:08', '2024-11-19 18:25:08', NULL),
(51, 'Son Môi gecomo Chất Nhung Lì Chống Thấm Nước ', 52, 16, 15, 159000, '<h2>MÔ TẢ SẢN PHẨM</h2><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/cn-11134208-7r98o-lmzqls07hpll9d@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/cn-11134208-7r98o-lmzqls07hpll9d@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/cn-11134208-7r98o-lmzqls07hpll9d\" srcset=\"https://down-vn.img.susercontent.com/file/cn-11134208-7r98o-lmzqls07hpll9d@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/cn-11134208-7r98o-lmzqls07hpll9d@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"1120\" height=\"1120\"></picture></p><p>&nbsp;</p><p>Tên sản phẩm: Pink Lip Clay</p><p>Tính năng sản phẩm: phát triển màu sắc cao, tạo hiệu ứng trang điểm sương mù mềm mại mờ tối ưu</p><p>Chi tiết sản phẩm</p><p>Thời hạn sử dụng: 24 tháng</p><p>Bao bì: 1 * Lip Mud</p><p>Nước sản xuất: Trung Quốc Trung Quốc</p><p>Cách sử dụng Thoa lên môi. Trước khi sử dụng lip Mud, hãy chăm sóc môi, bạn có thể dùng son dưỡng môi hoặc tẩy tế bào chết môi để loại bỏ da chết và giữ cho đôi môi luôn mềm mại, mịn màng.</p><p>Thoa đều Lip Mud lên môi. Bạn có thể dùng cọ môi để theo dõi tỉ mỉ hình dạng của đôi môi, hoặc dùng ngón tay vỗ nhẹ để đất sét môi hòa quyện với da môi.</p><p>Để có màu đầy đặn hơn, hãy đợi lớp đất sét môi đầu tiên khô trước khi thoa lớp thứ hai.</p><p>Để kéo dài tuổi thọ của đất sét môi, hãy nhẹ nhàng phủ một lớp bột lỏng trong hoặc bột đông kết sau khi thoa đất sét môi.</p><p>✿Lưu ý: Do kết cấu khô của Lip Mud, hãy đảm bảo rằng môi của bạn được dưỡng ẩm tốt trước khi sử dụng để tránh bị khô hoặc khó chịu.</p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/cn-11134208-7r98o-lzykui8dxu3ma0@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/cn-11134208-7r98o-lzykui8dxu3ma0@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/cn-11134208-7r98o-lzykui8dxu3ma0\" srcset=\"https://down-vn.img.susercontent.com/file/cn-11134208-7r98o-lzykui8dxu3ma0@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/cn-11134208-7r98o-lzykui8dxu3ma0@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"1120\" height=\"2226\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/cn-11134208-7r98o-lzykui8dwfj67d@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/cn-11134208-7r98o-lzykui8dwfj67d@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/cn-11134208-7r98o-lzykui8dwfj67d\" srcset=\"https://down-vn.img.susercontent.com/file/cn-11134208-7r98o-lzykui8dwfj67d@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/cn-11134208-7r98o-lzykui8dwfj67d@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"1120\" height=\"2226\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/cn-11134208-7r98o-lmzqls07lxax78@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/cn-11134208-7r98o-lmzqls07lxax78@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/cn-11134208-7r98o-lmzqls07lxax78\" srcset=\"https://down-vn.img.susercontent.com/file/cn-11134208-7r98o-lmzqls07lxax78@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/cn-11134208-7r98o-lmzqls07lxax78@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"1120\" height=\"1120\"></picture></p><p><br>&nbsp;</p>', 'IMG_Products/gecomo.png', 0, '2024-11-19 18:27:14', '2024-11-19 18:27:14', NULL),
(52, 'Nước Tẩy Trang La Roche-Posay Làm Sạch Sâu Dành Cho Da Dầu Mụn Và Da Thường', 46, 9, 11, 149000, '<h2>MÔ TẢ SẢN PHẨM</h2><p>Nước tẩy trang La Roche-Posay sản xuất tại Pháp giúp làm sạch da dành cho da dầu và da nhạy cảm</p><p>- Nước tẩy trang La Roche Posay dành cho da dầu và da nhạy cảm :</p><p>THÔNG SỐ SẢN PHẨM : Nước tẩy trang La Roche-Posay sản xuất tại Pháp giúp làm sạch da dành cho da dầu và da nhạy cảm</p><p>- Nước tẩy trang La Roche Posay Effaclar Micellar Water</p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirx39o269@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirx39o269@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirx39o269\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirx39o269@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirx39o269@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirxipx05e@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirxipx05e@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirxipx05e\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirxipx05e@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirxipx05e@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirxy65u1d@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirxy65u1d@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirxy65u1d\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirxy65u1d@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirxy65u1d@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirxipwye8@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirxipwye8@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirxipwye8\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirxipwye8@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirxipwye8@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirx39o467@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirx39o467@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirx39o467\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirx39o467@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yirx39o467@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yit131ro41@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yit131ro41@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yit131ro41\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yit131ro41@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7ras8-m2b1yit131ro41@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p>&nbsp;</p><p>- Tẩy trang là một bước làm sạch không thể thiếu trong quá trình chăm sóc da. Làn da dầu mà lại có tính nhạy cảm nữa thì khi tiếp xúc với các loại nước tẩy trang quá mạnh hoặc không phù hợp sẽ gây ra các tổn thương trầm trọng.</p><p>- Vậy loại tẩy trang nào có thể đem lại sự dịu nhẹ thiết yếu để không làm tổn thương làn da dầu yếu mềm này?</p><p>- Câu hỏi này đã được La Roche Posay giải đáp bằng cách đưa ra thị trường một loại nước tẩy trang dành cho da dầu, nhạy cảm đó là Nước tẩy trang La Roche Posay Effaclar Micellar Water Sensitive Skin.</p><p>- Nước tẩy trang La Roche Posay Dành Cho Da Dầu Nhạy Cảm là sản phẩm tẩy trang làm sạch sâu được thiết kế riêng cho da dầu nhạy cảm. Với công nghệ cải tiến Glyco Micellar, sản phẩm mang lại hiệu quả làm sạch sâu vượt trội giúp lấy đi bụi bẩn, bã nhờn và lớp trang điểm nhưng vẫn an toàn cho làn da nhạy cảm &amp; dễ kích ứng.</p><p>&nbsp;</p><p>- Tinh chất có trong nước tẩy trang La Roche Posay Effaclar Micellar Water có tác dụng cung cấp độ ẩm và giảm ma sát tối đa khi làm sạch. Bên cạnh đó, sản phẩm có thành phần ZINC PCA giúp kiểm soát dầu &amp; nước khoáng La Roche-Posay có tính năng làm dịu da, giảm kích ứng và chống oxi hóa.</p><p>THÀNH PHẦN : Nước tẩy trang La Roche-Posay sản xuất tại Pháp giúp làm sạch da dành cho da dầu và da nhạy cảm</p><p>- Aqua/​Water, PEG-7 Caprylic/​Capric Glycerides, Poloxamer 124, Poloxamer 184, PEG-6 Caprylic/​Capric Glycerides, Glycerin, Polysorbate 80, Zinc Pca, Sodium Hydroxide, Disodium EDTA, BHT, Myrtrimonium Bromide, Parfum/​Fragrance.</p><p>CÔNG DỤNG : Nước tẩy trang La Roche-Posay sản xuất tại Pháp giúp làm sạch da dành cho da dầu và da nhạy cảm</p><p>- Nước tẩy trang La Roche Posay Micellar Water sản phẩm mang tới khả năng đánh sạch bã nhờn, bụi bẩn mà không gây ra các kích ứng tiêu cực. Đặc biệt với công nghệ Glyco Micellar, dòng này còng mang lại hiệu quả đánh bật Chì và nhiều thành phần hóa chất ở mỹ phẩm makeup vượt trội.</p><p>ĐỐI TƯỢNG SỬ DỤNG : Nước tẩy trang La Roche-Posay sản xuất tại Pháp giúp làm sạch da dành cho da dầu và da nhạy cảm</p><p>- Nước làm sạch sâu và tẩy trang La Roche-Posay dành cho da dầu nhạy cảm là sản phẩm chuyên biệt được đặc chế riêng cho da dầu, da mụn.</p><p>Hướng dẫn sử dụng :</p><p>- Đổ nước tẩy trang vào bông tẩy trang rồi làm sạch mặt, mắt và môi.</p><p>- Không cần rửa lại bằng nước</p>', 'IMG_Products/laroche.png', 1, '2024-11-19 18:30:32', '2024-11-20 16:49:57', NULL),
(53, 'Kẻ mắt SACE LADY', 41, 10, 15, 399000, '<h2>MÔ TẢ SẢN PHẨM</h2><p>Thời gian giao hàng dự kiến cho sản phẩm này là từ 7-9 ngày</p><p>&nbsp;</p><p>* Mô tả sản phẩm:</p><p>Bộ trang điểm cơ bản 15 món, để trang điểm dễ dàng hơn. (sản phẩm thích hợp cho bộ bóng râm không thể chọn)</p><p>* Bao gồm những gì:</p><p>Đặc điểm:</p><p>1.Kem lót: Sản phẩm tạo ra một bề mặt hoàn toàn mịn, đều để thoa kem nền. Sản phẩm giúp lớp trang điểm lướt nhẹ dễ dàng và lâu trôi hơn</p><p>2.Kem nền: Công thức hóa học cung cấp độ che phủ tốt hơn nhiều vẫn mang lại vẻ ngoài tươi sáng và tươi sáng.</p><p>3.Phấn phủ: cung cấp độ mờ và không chứa dầu để trang điểm.</p><p>4.Son môi: Lâu trôi, không thấm nước: dễ pha trộn, không dính cốc, không có thêm hương vị.</p><p>5.Mascara: Mặt nạ mascara đen dày không thấm nước 3D sợi âm thanh nổi</p><p>6.Kẻ mắt: Không thấm nước, nhanh khô, tự nhiên, lâu trôi, chống nhòe, thoải mái</p><p>7.Bút kẻ mày: Với một đầu cọ ở một đầu để chải chuốt, cây bút chì này; đầu có hình sọc cho phép bạn tạo các nét chính xác cũng như tô lông mày một cách dễ dàng.</p><p>8.Mút trang điểm</p><p>* Thành phần:</p><p>Kẻ mắt dạng lỏng: Nước, Morene / acrylatesCopolymer, Ci 7266.Propylene Glycol, Polysorbate 20, Silica, Xanthan Gu m, Phenoxyethanol.</p><p>* Hướng dẫn bảo quản: Bảo quản nơi khô ráo</p><p>* Khối lượng tịnh: 500g</p><p>* Thời hạn sử dụng: 3 năm</p><p>* Dịch vụ sau bán hàng</p><p>Nếu bạn nhận được hàng bị hư hỏng / không chính xác hoặc bỏ lỡ, vui lòng liên hệ với chúng tôi, chúng tôi sẽ cố gắng hết sức để giúp bạn.</p><p>Sự hài lòng của bạn là động lực của chúng tôi!</p><p>Lưu ý:</p><p>Do chênh lệch cài đặt ánh sáng và màn hình, màu sắc sản phẩm có thể hơi khác so với hình ảnh Nếu sản phẩm không đáp ứng mong đợi của bạn, vui lòng liên hệ với chúng tôi trước khi bạn đánh giá sản phẩm.</p>', 'IMG_Products/sacelady.png', 0, '2024-11-19 18:34:00', '2024-11-20 16:57:40', NULL),
(54, 'Bộ Combo Trang Điểm Tự Nhiên 2 Bước Hây Hây Cỏ Mềm', 47, 17, 13, 470000, '<h2>MÔ TẢ SẢN PHẨM</h2><p>Bộ Trang điểm tự nhiên Hây Hây Cỏ Mềm: Gồm 2 sản phẩm: KEM CHỐNG NẮNG THIÊN NHIÊN + KEM NỀN THIÊN NHIÊN</p><p>----------------------</p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m03va7x3nxt98f@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m03va7x3nxt98f@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m03va7x3nxt98f\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m03va7x3nxt98f@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m03va7x3nxt98f@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p>**** KEM CHỐNG NẮNG THIÊN NHIÊN HÂY HÂY - AN TOÀN CHO DA NHẠY CẢM</p><p>Phối hợp giữa thành phần chống nắng vật lý và hóa học, kem có kết cấu mỏng nhẹ, ít nhờn rít và thẩm thấu nhanh. Chỉ cần thoa một lớp mỏng làn da sẽ được bảo vệ tối đa và không gây bóng dầu.</p><p>----------------------------</p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m03va7x3pcdp79@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m03va7x3pcdp79@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m03va7x3pcdp79\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m03va7x3pcdp79@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m03va7x3pcdp79@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><strong>Kem nền Hey hey</strong> không mỏng như cushion, không dày như foundation nhưng vẫn đủ để che phủ cho làn da mịn màng tự nhiên với kết cấu mỏng nhẹ, nhanh thấm, thoáng da.</p><p>Không để lại vệt trắng.</p><p>Kháng nước nên bền trên da và có chống nắng nhẹ (SPF 15)</p><p>&nbsp;Thành phần của kem nền Hây Hây an toàn cho da, không gây cảm giác bí tắc, không gây dầu mụn. Đặc biệt được chứng nhận KHÔNG KÍCH ỨNG cho cả da nhạy cảm (Đã thử nghiệm Invivo trên người)</p><p>&nbsp;Ngoài ra, Kem nền Hây Hây bổ sung bộ ba Hyaluronic Acid, chiết xuất Sữa Ong Chúa và Chiết xuất rễ cây cam thảo giúp chăm sóc da và ngăn ngừa lão hóa cho da của nàng đồng thời chống lại tình trạng kích ứng</p><p><strong>&nbsp;Bao gồm 2 loại:</strong></p><p>&nbsp;Kem nền màu 01 Light: Màu sáng (nâng tông, tạo hiệu ứng da sáng khỏe)</p><p>&nbsp;Kem nền màu 02 Nude: Màu tự nhiên (vừa vặn với màu da, tạo hiệu ứng da tự nhiên)</p><p>Đẹp tự nhiên nhưng không tự nhiên mà đẹp, nàng hãy sở hữu ngay cho mình một màu kem nền phù hợp nhé!</p><p>----------------</p><p><strong>&nbsp;Hướng dẫn sử dụng:&nbsp;</strong> Thoa 1 lượng kem vừa đủ lên da sau bước làm sạch, dưỡng ẩm, chống nắng. Luôn tẩy trang vào cuối ngày.</p><p>------------------------------------</p><p><strong>Thông tin về quà tặng</strong>: TINH DẦU DIỆP TRÀM: của Cỏ Mềm kết hợp Khuynh diệp - Tràm gió - Thông đỏ trong dầu nền nguyên chất, dùng xoa ngửi trực tiếp hoặc chấm vào khẩu trang giúp làm thông sạch đường thở, kháng khuẩn và phòng hô hấp. Nên dùng khi giao mùa, người có hệ hô hấp nhạy cảm, người tiếp xúc với nguy cơ nhiễm bệ.nh.</p><p>--------------------------------</p><p><strong>- Hạn sử dụng</strong>: 2 năm, mở nắp 12 tháng</p><p><strong>- Bảo quản:</strong> Nơi khô mát. Tránh nắng nóng và nguồn nhiệt.</p><p><strong>- Đối tượng sử dụng:</strong> Mọi người, kể cả bà bầu và trẻ nhỏ</p><p>--------------------------------</p><p><strong>- Thương hiệu:</strong> Cỏ Mềm</p><p><strong>- Xuất xứ thương hiệu:</strong> Việt Nam</p><p><strong>- Sản xuất tại:</strong> Việt Nam</p><p>-------------------------</p><p><strong>&nbsp;THÔNG TIN THƯƠNG HIỆU:</strong></p><p>Cỏ Mềm là thương hiệu Mỹ phẩm Thiên nhiên An toàn được tin cậy tại Việt Nam, thành lập năm 2015 với sứ mệnh “Mang đến những giải pháp an toàn từ thiên nhiên THAY THẾ HÓA CHẤT ĐỘC HẠI để chăm sóc con người và bảo vệ sự bền vững của Trái Đất”</p>', 'IMG_Products/combotrangdiem.png', 1, '2024-11-19 18:38:15', '2024-11-20 16:50:02', NULL);
INSERT INTO `sanpham` (`IDSanPham`, `TenSanPham`, `IDDanhMuc`, `IDThuongHieu`, `SoLuong`, `GiaTien`, `MoTaChiTiet`, `AnhSanPham`, `NoiBat`, `ThoiGianTao`, `ThoiGianCapNhat`, `Gia`) VALUES
(55, 'Kem nền Catrice HD Liquid Coverage Foundation', 51, 11, 15, 335000, '<h2>MÔ TẢ SẢN PHẨM</h2><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tjd2lb7@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tjd2lb7@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tjd2lb7\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tjd2lb7@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tjd2lb7@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tkrn134@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tkrn134@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tkrn134\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tkrn134@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tkrn134@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tm67h47@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tm67h47@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tm67h47\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tm67h47@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tm67h47@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tnkrx73@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tnkrx73@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tnkrx73\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tnkrx73@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tnkrx73@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3taxzje3@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3taxzje3@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3taxzje3\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3taxzje3@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3taxzje3@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tccjz24@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tccjz24@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tccjz24\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tccjz24@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tccjz24@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tf5ov04@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tf5ov04@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tf5ov04\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tf5ov04@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tf5ov04@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p><picture><source srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tgk9bef@resize_w875_nl.webp 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tgk9bef@resize_w1750_nl.webp 2x\" type=\"image/webp\"><img src=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tgk9bef\" srcset=\"https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tgk9bef@resize_w875_nl 1x, https://down-vn.img.susercontent.com/file/vn-11134208-7r98o-m08mcx3tgk9bef@resize_w1750_nl 2x\" sizes=\"100vw\" width=\"873\" height=\"873\"></picture></p><p>&nbsp;</p><p>Kem nền Catrice HD Liquid Coverage Foundation che phủ tự nhiên tạo độ mịn màng cho da 30ml</p><p>Sản phẩm: Kem nền</p><p>Thương hiệu: Catrice Cosmetics</p><p>Xuất xứ: Ý</p><p>Màu: 5 màu</p><p>- Màu 002 - Porcelain Beige - Da Trắng</p><p>- Màu 005 - Ivory Beige - Da Trắng Hồng</p><p>- Màu 008 - Fair Beige - Da Trung Bình Sáng</p><p>- Màu 010 - Light Beige - Da Trung Bình</p><p>- Màu 020 - Rose Beige - Da Tự Nhiên</p><p>&nbsp;</p><p>ĐẶC ĐIỂM:</p><p>- Độ che phủ tự nhiên Kem nền HD Liquid Coverage Foundation tạo cảm giác nhẹ nhàng trên da, với độ che phủ tốt tạo nên một làn da hoàn hảo mịn màng – lên đến 24 giờ! Kết cấu dạng lỏng siêu nhẹ, với độ che phủ tốt nhưng vẫn tự nhiên, giấu đi khuyết điểm trên khuôn mặt, sản phẩm chứa niacinamide giúp hoàn thiện làn da. Kem nền sử dụng công nghệ chống thấm nước, kiềm sự tiết mồ hôi giữ lớp trang điểm lâu trôi trên da. Công thức thành phần không chứa paraben hoặc hạt vi nhựa.</p><p>Bảng thành phần nổi bật:</p><p>- Dimethicone, Crosspolymer: có tác dụng tạo độ mịn cho kem, khi apply lên da sẽ dễ tán mà không hề bị vón cục hay dày đặc 1 chỗ.</p><p>- Isododecane: là hoạt chất có công dụng làm kem nền khi tán đều trên da sẽ bay hơi và để lại lớp nền mỏng nhẹ, bền màu suốt nhiều giờ.</p><p>- Đất sét khoáng Silica: có khả năng hấp thụ bã nhờn và dầu thừa tiết trên da.</p><p>Glycerin: có tác dụng cung cấp và duy trì độ ẩm cho làn da.</p><p>&nbsp;</p><p>HƯỚNG DẪN SỬ DỤNG:</p><p>Catrice sẽ hướng dẫn bạn cách đánh Kem Nền Catrice HD Liquid Coverage Foundation không bị mốc với những bước cơ bản dưới đây:</p><p>Bước 1: Sau khi làm sạch da bạn thực hiện thoa kem chống nắng và dùng kem lót.</p><p>Bước 2: Sử dụng kem che khuyết điểm để che đi những vết thâm mụn, quầng thâm mắt, sạm, tàn nhang…</p><p>Bước 3: Sử dụng kem nền:</p><p>Lấy một lượng kem nền rồi chấm nhẹ lên trán, quầng thâm mắt, mũi, cằm, hai má…</p><p>Dùng cọ hoặc mút để tán kem nền đều trên mặt. Bạn thực hiện nhẹ nhàng và không được chà hoặc trét.</p><p>Bước 4: Sử dụng phấn phủ để khóa lớp nền.</p><p>Lưu ý:</p><p>Bảo quản ở nơi khô ráo, thoáng mát và tránh ánh nắng trực tiếp từ mặt trời.</p><p>Đậy nắp kín khi không sử dụng.</p><p>Để xa tầm tay của trẻ em.</p><p>Hạn sử dụng 36 tháng.</p><p>&nbsp;</p><p>THÀNH PHẦN:</p><p>INGREDIENTS: AQUA (WATER), DIMETHICONE, TALC, TRIMETHYLSILOXYSILICATE, PEG-10 DIMETHICONE, NIACINAMIDE, ISODODECANE, SILICA, GLYCERIN, CETYL PEG/PPG-10/1 DIMETHICONE, TOCOPHEROL, SODIUM CHLORIDE, HYDROGEN DIMETHICONE, MAGNESIUM SULFATE, CETYL ALCOHOL, DIMETHICONE/VINYL DIMETHICONE CROSSPOLYMER, DISTEARDIMONIUM HECTORITE, METHICONE, PROPYLENE CARBONATE, TRIETHOXYCAPRYLYLSILANE, ETHYLHEXYLGLYCERIN, PENTAERYTHRITYL TETRA-DI-T-BUTYL HYDROXYHYDROCINNAMATE, ALUMINUM HYDROXIDE, PHENOXYETHANOL, SODIUM DEHYDROACETATE, POTASSIUM SORBATE, BENZOIC ACID, DEHYDROACETIC ACID, PARFUM (FRAGRANCE), CI 77491 (IRON OXIDES), CI 77492 (IRON OXIDES), CI 77499 (IRON OXIDES), CI 77891 (TITANIUM DIOXIDE).</p>', 'IMG_Products/catice.png', 0, '2024-11-19 18:40:03', '2024-11-19 18:40:03', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thuonghieu`
--

CREATE TABLE `thuonghieu` (
  `IDThuongHieu` int NOT NULL,
  `TenThuongHieu` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `AnhThuongHieu` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `thuonghieu`
--

INSERT INTO `thuonghieu` (`IDThuongHieu`, `TenThuongHieu`, `AnhThuongHieu`) VALUES
(5, 'DOVE', 'IMG_Brand/dove.jpg'),
(6, 'MELIA', 'IMG_Brand/melia.jpg'),
(7, 'SIMPLE', 'IMG_Brand/simple.png'),
(8, 'UNILEVER', 'IMG_Brand/unilever.jpg'),
(9, 'DOVE LADY', 'IMG_Brand/dovelady.jpg'),
(10, 'INNISFREE', 'IMG_Brand/innistree.png'),
(11, 'NIVEA', 'IMG_Brand/nivea.jpg'),
(16, 'OLAY', 'IMG_Brand/review-olay.jpg'),
(17, 'GUCCI', 'IMG_Brand/gucci.jpg'),
(18, 'Dior ', 'IMG_Brand/logo-dior.jpg'),
(19, 'MELIAAAA', 'IMG_Brand/melia.jpg'),
(20, '', 'IMG_Brand/Thiết kế chưa có tên.png');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tintucbaiviet`
--

CREATE TABLE `tintucbaiviet` (
  `ID_TinTucBaiViet` int NOT NULL,
  `TieuDeBaiViet` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `BaiViet` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `AnhBaiViet` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tintucbaiviet`
--

INSERT INTO `tintucbaiviet` (`ID_TinTucBaiViet`, `TieuDeBaiViet`, `BaiViet`, `AnhBaiViet`) VALUES
(5, 'Mơ Shop – Đẹp Từ Tâm, Tỏa Sáng Từ Chất Lượng', '<p>&nbsp;</p><h2><strong>Mơ Shop – Đẹp Từ Tâm, Tỏa Sáng Từ Chất Lượng</strong></h2><p>🌸 <strong>Chào mừng đến với Mơ Shop!</strong><br>Mơ Shop tự hào là nơi hội tụ những <strong>sản phẩm mỹ phẩm hiện đại, chất lượng cao</strong>, giúp bạn tỏa sáng và yêu bản thân hơn mỗi ngày. Chúng tôi mang đến các dòng sản phẩm từ trang điểm, dưỡng da đến chăm sóc toàn diện, đáp ứng mọi nhu cầu làm đẹp của bạn.</p><h4>🎯 <strong>Điểm đặc biệt tại Mơ Shop:</strong></h4><ul><li><strong>Sản phẩm đa dạng:</strong> Chúng tôi cam kết cung cấp mỹ phẩm chính hãng, an toàn và phù hợp với mọi loại da.</li><li><strong>Chất lượng hàng đầu:</strong> Mỗi sản phẩm được chọn lọc kỹ lưỡng để mang lại hiệu quả cao nhất.</li><li><strong>Giá cả hợp lý:</strong> Tận hưởng sự cao cấp mà không cần lo lắng về chi phí.</li></ul><h4>❤️ <strong>Không chỉ là làm đẹp, mà còn lan tỏa yêu thương!</strong><br>Tại Mơ Shop, chúng tôi không chỉ hướng đến việc chăm sóc sắc đẹp mà còn quan tâm đến cộng đồng. Với mỗi sản phẩm bạn mua, bạn đã cùng chúng tôi:</h4><ul><li><strong>Ủng hộ học sinh nghèo vượt khó</strong>: Mang đến cơ hội học tập tốt hơn cho các em nhỏ có hoàn cảnh khó khăn.</li><li><strong>Chung tay giúp đỡ đồng bào bão lũ</strong>: Góp phần sẻ chia yêu thương, xây dựng lại cuộc sống sau thiên tai.</li></ul><h4>📌 <strong>Hãy để Mơ Shop cùng bạn lan tỏa giá trị!</strong></h4><ul><li><strong>Địa chỉ:</strong> 32 Nguyễn Tạo, Hòa Hải, Ngũ Hành Sơn, Đà Nẵng</li><li><strong>Hotline:</strong> 0559110474</li><li><strong>Website/Fanpage:</strong> https://www.facebook.com/Huyv.2203</li></ul><p>&nbsp;</p><p>Mơ Shop – Vì vẻ đẹp của bạn và vì một tương lai tươi sáng hơn cho cộng đồng! 🌟<br>&nbsp;</p>', 'IMG_News_Posts/Mơ shop (1).png');

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `users`
-- (See below for the actual view)
--
CREATE TABLE `users` (
`email` varchar(100)
,`first_visit` datetime
,`id` int
,`last_visit` datetime
,`name` varchar(50)
,`phone` varchar(20)
);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_actions`
--

CREATE TABLE `user_actions` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(64) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `user_actions`
--

INSERT INTO `user_actions` (`id`, `user_id`, `action`, `time`) VALUES
(1, 13, 'page_view', '2025-10-13 17:35:50'),
(2, 13, 'page_view', '2025-10-13 17:35:50'),
(3, 13, 'page_view', '2025-10-13 17:35:50'),
(4, 13, 'page_view', '2025-10-13 17:35:53'),
(5, 13, 'page_view', '2025-10-13 17:37:41'),
(6, 13, 'page_view', '2025-10-13 17:38:02'),
(7, 13, 'page_view', '2025-10-13 17:38:10'),
(8, 13, 'page_view', '2025-10-13 17:38:16'),
(9, 13, 'page_view', '2025-10-13 17:38:25'),
(10, 13, 'page_view', '2025-10-13 17:45:49'),
(11, 13, 'page_view', '2025-10-13 17:45:49'),
(12, 13, 'page_view', '2025-10-13 17:45:49'),
(13, 13, 'page_view', '2025-10-13 17:45:54'),
(14, 13, 'page_view', '2025-10-13 17:46:00'),
(15, 13, 'page_view', '2025-10-13 17:49:32'),
(16, 13, 'page_view', '2025-10-13 17:49:43'),
(17, 13, 'page_view', '2025-10-13 17:49:59'),
(18, 13, 'page_view', '2025-10-13 17:50:05'),
(19, 13, 'page_view', '2025-10-13 17:50:13'),
(20, 13, 'page_view', '2025-10-13 17:53:09'),
(21, 13, 'page_view', '2025-10-13 17:53:09'),
(22, 13, 'page_view', '2025-10-13 17:53:09'),
(23, 13, 'page_view', '2025-10-13 17:53:19'),
(24, 13, 'page_view', '2025-10-13 18:06:53'),
(25, 13, 'page_view', '2025-10-13 18:06:53'),
(26, 13, 'page_view', '2025-10-13 18:06:53'),
(27, 13, 'page_view', '2025-10-13 18:06:57'),
(28, 13, 'page_view', '2025-10-13 18:07:05'),
(29, 13, 'page_view', '2025-10-13 18:10:15'),
(30, 13, 'page_view', '2025-10-13 18:10:15'),
(31, 13, 'page_view', '2025-10-13 18:10:15'),
(32, 13, 'page_view', '2025-10-13 18:10:21'),
(33, NULL, 'page_view', '2025-10-14 06:44:50'),
(34, NULL, 'page_view', '2025-10-14 06:44:50'),
(35, NULL, 'page_view', '2025-10-14 06:44:50'),
(36, NULL, 'page_view', '2025-10-14 06:45:05'),
(37, 13, 'page_view', '2025-10-14 06:45:06'),
(38, 13, 'page_view', '2025-10-14 06:45:06'),
(39, 13, 'page_view', '2025-10-14 06:45:06'),
(40, 13, 'page_view', '2025-10-14 06:45:16'),
(41, 13, 'page_view', '2025-10-14 06:45:25'),
(42, 13, 'page_view', '2025-10-14 06:45:40'),
(43, 13, 'page_view', '2025-10-14 07:14:06'),
(44, 13, 'page_view', '2025-10-14 07:14:06'),
(45, 13, 'page_view', '2025-10-14 07:14:06'),
(46, 13, 'page_view', '2025-10-14 07:14:16'),
(47, 13, 'page_view', '2025-10-14 07:31:54'),
(48, 13, 'page_view', '2025-10-14 07:31:54'),
(49, 13, 'page_view', '2025-10-14 07:31:54'),
(50, 13, 'page_view', '2025-10-14 07:31:57'),
(51, 13, 'page_view', '2025-10-14 07:32:04'),
(52, 13, 'page_view', '2025-10-14 07:32:11'),
(53, 13, 'page_view', '2025-10-14 07:32:23'),
(54, 13, 'page_view', '2025-10-14 07:32:29'),
(55, 13, 'page_view', '2025-10-14 07:33:11'),
(56, 13, 'page_view', '2025-10-14 07:33:18'),
(57, 13, 'page_view', '2025-10-14 07:34:06'),
(58, 13, 'page_view', '2025-10-14 07:35:19'),
(59, 13, 'page_view', '2025-10-14 07:35:19'),
(60, 13, 'page_view', '2025-10-14 07:35:19'),
(61, 13, 'page_view', '2025-10-14 07:35:23'),
(62, NULL, 'page_view', '2025-10-14 17:39:43'),
(63, NULL, 'page_view', '2025-10-14 17:39:43'),
(64, NULL, 'page_view', '2025-10-14 17:39:43'),
(65, NULL, 'page_view', '2025-10-14 17:40:06'),
(66, 11, 'page_view', '2025-10-14 17:40:09'),
(67, 11, 'page_view', '2025-10-14 17:40:09'),
(68, 11, 'page_view', '2025-10-14 17:40:09'),
(69, 11, 'page_view', '2025-10-14 17:40:13'),
(70, 11, 'page_view', '2025-10-14 17:49:21'),
(71, 11, 'page_view', '2025-10-14 17:49:21'),
(72, 11, 'page_view', '2025-10-14 17:49:21'),
(73, 11, 'page_view', '2025-10-14 17:49:24'),
(74, 11, 'page_view', '2025-10-14 18:08:12'),
(75, 11, 'page_view', '2025-10-14 18:08:12'),
(76, 11, 'page_view', '2025-10-14 18:08:12'),
(77, 11, 'page_view', '2025-10-14 18:08:17'),
(78, 11, 'page_view', '2025-10-14 18:08:21'),
(79, 11, 'page_view', '2025-10-14 18:08:21'),
(80, 11, 'page_view', '2025-10-14 18:08:21'),
(81, 11, 'page_view', '2025-10-14 18:08:23'),
(82, 11, 'page_view', '2025-10-14 18:08:25'),
(83, 11, 'page_view', '2025-10-14 19:04:40'),
(84, 11, 'page_view', '2025-10-14 19:04:40'),
(85, 11, 'page_view', '2025-10-14 19:04:40'),
(86, 11, 'page_view', '2025-10-14 19:04:47'),
(87, 11, 'page_view', '2025-10-14 19:26:04'),
(88, 11, 'page_view', '2025-10-14 19:26:04'),
(89, 11, 'page_view', '2025-10-14 19:26:04'),
(90, 11, 'page_view', '2025-10-14 19:26:16'),
(91, NULL, 'page_view', '2025-10-14 19:26:20'),
(92, NULL, 'page_view', '2025-10-14 19:26:20'),
(93, NULL, 'page_view', '2025-10-14 19:26:20'),
(94, NULL, 'page_view', '2025-10-14 19:26:21'),
(95, NULL, 'page_view', '2025-10-14 19:26:29'),
(96, NULL, 'page_view', '2025-10-14 19:27:02'),
(97, 14, 'page_view', '2025-10-14 19:27:04'),
(98, 14, 'page_view', '2025-10-14 19:27:04'),
(99, 14, 'page_view', '2025-10-14 19:27:04'),
(100, 14, 'page_view', '2025-10-14 19:27:06'),
(101, 14, 'page_view', '2025-10-14 19:27:15'),
(102, 14, 'page_view', '2025-10-14 19:27:25'),
(103, 14, 'page_view', '2025-10-14 19:27:48'),
(104, 14, 'page_view', '2025-10-14 19:27:48'),
(105, 14, 'page_view', '2025-10-14 19:27:48'),
(106, 14, 'page_view', '2025-10-14 19:27:53'),
(107, 14, 'page_view', '2025-10-14 19:28:25'),
(108, 14, 'page_view', '2025-10-14 19:28:29'),
(109, 14, 'page_view', '2025-10-14 19:28:29'),
(110, 14, 'page_view', '2025-10-14 19:28:29'),
(111, 14, 'page_view', '2025-10-14 19:28:31'),
(112, 14, 'page_view', '2025-10-14 19:29:23'),
(113, NULL, 'page_view', '2025-10-20 00:53:20'),
(114, NULL, 'page_view', '2025-10-20 00:53:20'),
(115, NULL, 'page_view', '2025-10-20 00:53:20'),
(116, NULL, 'page_view', '2025-10-20 00:54:26'),
(117, NULL, 'page_view', '2025-10-20 00:54:28'),
(118, NULL, 'page_view', '2025-10-20 00:54:46'),
(119, 11, 'page_view', '2025-10-20 00:54:50'),
(120, 11, 'page_view', '2025-10-20 00:54:50'),
(121, 11, 'page_view', '2025-10-20 00:54:50'),
(122, 11, 'page_view', '2025-10-20 00:54:57'),
(123, 11, 'page_view', '2025-10-20 00:55:08'),
(124, 11, 'page_view', '2025-10-20 00:55:20'),
(125, 11, 'page_view', '2025-10-20 00:55:40'),
(126, 11, 'page_view', '2025-10-20 00:55:49'),
(127, 11, 'page_view', '2025-10-20 01:36:02'),
(128, 11, 'page_view', '2025-10-20 01:36:05'),
(129, 11, 'page_view', '2025-10-20 01:36:05'),
(130, 11, 'page_view', '2025-10-20 01:36:05'),
(131, 11, 'page_view', '2025-10-20 01:36:12'),
(132, 11, 'page_view', '2025-10-20 01:36:12'),
(133, 11, 'page_view', '2025-10-20 01:36:12'),
(134, 11, 'page_view', '2025-10-20 01:36:46'),
(135, 11, 'page_view', '2025-10-20 01:36:46'),
(136, 11, 'page_view', '2025-10-20 01:36:46'),
(137, 11, 'page_view', '2025-10-20 01:36:51'),
(138, 11, 'page_view', '2025-10-20 01:37:30'),
(139, 11, 'page_view', '2025-10-20 01:37:35'),
(140, 11, 'page_view', '2025-10-20 01:37:35'),
(141, 11, 'page_view', '2025-10-20 01:37:35'),
(142, 11, 'page_view', '2025-10-20 01:37:41'),
(143, 11, 'page_view', '2025-10-20 01:37:48'),
(144, 11, 'page_view', '2025-10-20 01:44:27'),
(145, 11, 'page_view', '2025-10-20 01:44:27'),
(146, 11, 'page_view', '2025-10-20 01:44:27'),
(147, 11, 'page_view', '2025-10-20 01:45:15'),
(148, 11, 'page_view', '2025-10-20 01:45:21'),
(149, NULL, 'page_view', '2025-10-20 01:45:41'),
(150, NULL, 'page_view', '2025-10-20 01:45:41'),
(151, NULL, 'page_view', '2025-10-20 01:45:41'),
(152, NULL, 'page_view', '2025-10-20 01:45:42'),
(153, NULL, 'page_view', '2025-10-20 01:45:48'),
(154, NULL, 'page_view', '2025-10-20 01:45:59'),
(155, NULL, 'page_view', '2025-10-20 01:46:04'),
(156, NULL, 'page_view', '2025-10-20 01:46:37'),
(157, 15, 'page_view', '2025-10-20 01:46:39'),
(158, 15, 'page_view', '2025-10-20 01:46:40'),
(159, 15, 'page_view', '2025-10-20 01:46:40'),
(160, 15, 'page_view', '2025-10-20 01:46:43'),
(161, 15, 'page_view', '2025-10-20 01:46:54'),
(162, 15, 'page_view', '2025-10-20 01:46:54'),
(163, 15, 'page_view', '2025-10-20 01:46:54'),
(164, 15, 'page_view', '2025-10-20 01:46:57'),
(165, 15, 'page_view', '2025-10-20 02:04:48'),
(166, 15, 'page_view', '2025-10-20 02:04:48'),
(167, 15, 'page_view', '2025-10-20 02:04:48'),
(168, 15, 'page_view', '2025-10-20 02:05:07'),
(169, 15, 'page_view', '2025-10-20 02:05:14'),
(170, 15, 'page_view', '2025-10-20 02:05:27'),
(171, 15, 'page_view', '2025-10-20 02:06:10'),
(172, 15, 'page_view', '2025-10-20 02:06:20'),
(173, 15, 'page_view', '2025-10-20 02:06:28'),
(174, 15, 'page_view', '2025-10-20 02:06:30'),
(175, 15, 'page_view', '2025-10-20 02:06:30'),
(176, 15, 'page_view', '2025-10-20 02:06:30'),
(177, 15, 'page_view', '2025-10-20 05:16:31'),
(178, 15, 'page_view', '2025-10-20 05:16:31'),
(179, 15, 'page_view', '2025-10-20 05:16:31'),
(180, 15, 'page_view', '2025-10-20 05:16:37'),
(181, 15, 'page_view', '2025-10-20 05:16:51'),
(182, 15, 'page_view', '2025-10-20 05:17:01'),
(183, NULL, 'page_view', '2025-10-20 08:51:23'),
(184, NULL, 'page_view', '2025-10-20 08:51:23'),
(185, NULL, 'page_view', '2025-10-20 08:51:23'),
(186, NULL, 'page_view', '2025-10-20 08:51:24'),
(187, 15, 'page_view', '2025-10-20 08:51:28'),
(188, 15, 'page_view', '2025-10-20 08:51:28'),
(189, 15, 'page_view', '2025-10-20 08:51:28'),
(190, 15, 'page_view', '2025-10-20 08:51:37'),
(191, 15, 'page_view', '2025-10-20 08:51:44'),
(192, 15, 'page_view', '2025-10-20 08:52:01'),
(193, 15, 'page_view', '2025-10-20 08:52:18'),
(194, 15, 'page_view', '2025-10-20 09:10:59'),
(195, 15, 'page_view', '2025-10-20 09:11:39'),
(196, 15, 'page_view', '2025-10-20 09:13:16'),
(197, 15, 'page_view', '2025-10-20 09:13:16'),
(198, 15, 'page_view', '2025-10-20 09:13:16'),
(199, 15, 'page_view', '2025-10-20 09:21:00'),
(200, 15, 'page_view', '2025-10-20 09:21:19'),
(201, 15, 'page_view', '2025-10-20 09:21:19'),
(202, 15, 'page_view', '2025-10-20 09:21:19'),
(203, 15, 'page_view', '2025-10-20 17:33:46'),
(204, 15, 'page_view', '2025-10-20 17:33:53'),
(205, 15, 'page_view', '2025-10-20 17:33:59'),
(206, 15, 'page_view', '2025-10-20 17:34:21'),
(207, 15, 'page_view', '2025-10-20 17:35:58'),
(208, 16, 'page_view', '2025-10-20 17:36:32'),
(209, 16, 'page_view', '2025-10-20 17:36:37'),
(210, 16, 'page_view', '2025-10-20 17:36:39'),
(211, 16, 'page_view', '2025-10-20 17:37:00'),
(212, 16, 'page_view', '2025-10-20 17:37:39'),
(213, 16, 'page_view', '2025-10-20 17:37:56'),
(214, 16, 'page_view', '2025-10-20 17:38:13'),
(215, 16, 'page_view', '2025-10-20 17:53:52'),
(216, 16, 'page_view', '2025-10-20 17:53:55'),
(217, 16, 'page_view', '2025-10-20 17:54:00'),
(218, 16, 'page_view', '2025-10-20 17:54:04'),
(219, 16, 'page_view', '2025-10-20 17:54:05');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_events`
--

CREATE TABLE `user_events` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(64) DEFAULT NULL,
  `event_type` varchar(64) NOT NULL,
  `metadata` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `user_events`
--

INSERT INTO `user_events` (`id`, `user_id`, `session_id`, `event_type`, `metadata`, `created_at`) VALUES
(1, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:43:26'),
(2, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:43:26'),
(3, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:43:26'),
(4, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Login.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:43:37'),
(5, NULL, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":9}', '2025-10-13 09:43:43'),
(6, NULL, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":24}', '2025-10-13 09:43:49'),
(7, NULL, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":21}', '2025-10-13 09:43:53'),
(8, NULL, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":42}', '2025-10-13 09:43:58'),
(9, NULL, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":16}', '2025-10-13 09:44:08'),
(10, NULL, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":10}', '2025-10-13 09:44:12'),
(11, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:44:35'),
(12, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:44:35'),
(13, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:44:35'),
(14, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:47:35'),
(15, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:47:35'),
(16, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:47:35'),
(17, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:47:44'),
(18, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:47:44'),
(19, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:47:44'),
(20, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Login.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:47:46'),
(21, NULL, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":9}', '2025-10-13 09:47:51'),
(22, NULL, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":21}', '2025-10-13 09:47:54'),
(23, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:47:58'),
(24, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:47:58'),
(25, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:47:58'),
(26, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:52:35'),
(27, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:52:35'),
(28, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:52:35'),
(29, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:52:53'),
(30, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:52:53'),
(31, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:52:53'),
(32, NULL, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":21}', '2025-10-13 09:53:03'),
(33, NULL, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":21}', '2025-10-13 09:53:04'),
(34, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Login.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:53:07'),
(35, NULL, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":21}', '2025-10-13 09:53:13'),
(36, NULL, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":24}', '2025-10-13 09:53:30'),
(37, NULL, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":10}', '2025-10-13 09:53:34'),
(38, NULL, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":19}', '2025-10-13 09:53:42'),
(39, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:53:46'),
(40, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:53:46'),
(41, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:53:46'),
(42, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:56:11'),
(43, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:56:11'),
(44, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:56:12'),
(45, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:57:20'),
(46, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:57:20'),
(47, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:57:20'),
(48, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Login.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 09:57:22'),
(49, NULL, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":21}', '2025-10-13 09:57:28'),
(50, NULL, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":10}', '2025-10-13 09:57:32'),
(51, NULL, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":16}', '2025-10-13 09:57:36'),
(52, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:00:12'),
(53, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:00:12'),
(54, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:00:12'),
(55, NULL, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":10}', '2025-10-13 10:00:21'),
(56, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Login.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:00:21'),
(57, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Register.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:00:24'),
(58, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Login.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:01:20'),
(59, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:01:23'),
(60, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:01:23'),
(61, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:01:23'),
(62, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Personal.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:01:26'),
(63, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Personal.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:01:47'),
(64, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Order_Tracking.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:01:51'),
(65, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Personal.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:01:52'),
(66, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Personal.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:01:53'),
(67, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Personal.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:02:05'),
(68, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:02:07'),
(69, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:02:07'),
(70, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:02:07'),
(71, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":10}', '2025-10-13 10:02:16'),
(72, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":21}', '2025-10-13 10:02:18'),
(73, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:02:21'),
(74, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":9}', '2025-10-13 10:02:26'),
(75, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":21}', '2025-10-13 10:02:28'),
(76, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/View_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:06:32'),
(77, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":9}', '2025-10-13 10:06:36'),
(78, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:06:38'),
(79, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:06:38'),
(80, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:06:38'),
(81, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":9}', '2025-10-13 10:06:41'),
(82, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":21}', '2025-10-13 10:06:48'),
(83, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 10:06:48'),
(84, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":21}', '2025-10-13 10:06:54'),
(85, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:35:50'),
(86, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:35:50'),
(87, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:35:50'),
(88, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:35:53'),
(89, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":9}', '2025-10-13 17:36:07'),
(90, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":21}', '2025-10-13 17:36:18'),
(91, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":23}', '2025-10-13 17:36:21'),
(92, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:37:41'),
(93, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":44}', '2025-10-13 17:38:01'),
(94, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:38:02'),
(95, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":3}', '2025-10-13 17:38:09'),
(96, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:38:10'),
(97, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":10}', '2025-10-13 17:38:15'),
(98, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:38:16'),
(99, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":19}', '2025-10-13 17:38:23'),
(100, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:38:25'),
(101, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:45:49'),
(102, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:45:49'),
(103, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:45:49'),
(104, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:45:54'),
(105, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":9}', '2025-10-13 17:45:59'),
(106, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:46:00'),
(107, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":4}', '2025-10-13 17:49:31'),
(108, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:49:32'),
(109, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":5}', '2025-10-13 17:49:42'),
(110, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:49:43'),
(111, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":9}', '2025-10-13 17:49:58'),
(112, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:49:59'),
(113, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":4}', '2025-10-13 17:50:04'),
(114, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:50:05'),
(115, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":7}', '2025-10-13 17:50:12'),
(116, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:50:13'),
(117, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:53:09'),
(118, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:53:09'),
(119, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:53:09'),
(120, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 17:53:19'),
(121, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 18:06:53'),
(122, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 18:06:53'),
(123, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 18:06:53'),
(124, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 18:06:57'),
(125, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 18:07:05'),
(126, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 18:10:15'),
(127, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 18:10:15'),
(128, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-13 18:10:15'),
(129, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-13 18:10:21'),
(130, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 06:44:50'),
(131, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 06:44:50'),
(132, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 06:44:50'),
(133, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Login.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 06:45:05'),
(134, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 06:45:06'),
(135, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 06:45:06'),
(136, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 06:45:06'),
(137, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 06:45:16'),
(138, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 06:45:25'),
(139, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 06:45:40'),
(140, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 07:14:06'),
(141, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 07:14:06'),
(142, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 07:14:06'),
(143, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 07:14:16'),
(144, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 07:31:54'),
(145, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 07:31:54'),
(146, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 07:31:54'),
(147, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 07:31:57'),
(148, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":5}', '2025-10-14 07:32:00'),
(149, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 07:32:04'),
(150, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":8}', '2025-10-14 07:32:07'),
(151, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 07:32:11'),
(152, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":17}', '2025-10-14 07:32:18'),
(153, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 07:32:23'),
(154, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":3}', '2025-10-14 07:32:27'),
(155, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 07:32:29'),
(156, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":16}', '2025-10-14 07:33:07'),
(157, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 07:33:11'),
(158, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":5}', '2025-10-14 07:33:15'),
(159, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 07:33:18'),
(160, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 07:34:06'),
(161, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 07:35:19'),
(162, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 07:35:19'),
(163, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 07:35:19'),
(164, 13, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 07:35:23'),
(165, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":8}', '2025-10-14 07:35:30'),
(166, 13, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":23}', '2025-10-14 07:35:34'),
(167, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 17:39:43'),
(168, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 17:39:43'),
(169, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 17:39:43'),
(170, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Login.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 17:40:06'),
(171, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 17:40:09'),
(172, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 17:40:09'),
(173, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 17:40:09'),
(174, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 17:40:13'),
(175, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":74}', '2025-10-14 17:40:43'),
(176, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":6}', '2025-10-14 17:41:12'),
(177, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":26}', '2025-10-14 17:41:19'),
(178, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":29}', '2025-10-14 17:41:31'),
(179, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":24}', '2025-10-14 17:41:44'),
(180, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":10}', '2025-10-14 17:43:30'),
(181, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":24}', '2025-10-14 17:43:35'),
(182, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":25}', '2025-10-14 17:43:46'),
(183, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":19}', '2025-10-14 17:44:02'),
(184, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 17:49:21'),
(185, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 17:49:21'),
(186, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 17:49:21'),
(187, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 17:49:24'),
(188, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":27}', '2025-10-14 17:49:31'),
(189, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":26}', '2025-10-14 17:49:47'),
(190, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":15}', '2025-10-14 17:50:00'),
(191, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":17}', '2025-10-14 17:50:04'),
(192, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 18:08:12'),
(193, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 18:08:12'),
(194, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 18:08:12'),
(195, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/View_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 18:08:17'),
(196, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 18:08:21'),
(197, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 18:08:21'),
(198, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 18:08:21'),
(199, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/View_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 18:08:23'),
(200, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 18:08:25'),
(201, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":33}', '2025-10-14 18:08:38'),
(202, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":27}', '2025-10-14 18:08:55'),
(203, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":9}', '2025-10-14 18:09:11'),
(204, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":2}', '2025-10-14 18:09:19'),
(205, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":30}', '2025-10-14 18:09:24'),
(206, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":3}', '2025-10-14 18:09:41'),
(207, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":7}', '2025-10-14 18:09:49'),
(208, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":8}', '2025-10-14 18:10:11'),
(209, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":3}', '2025-10-14 18:10:19'),
(210, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":11}', '2025-10-14 18:10:31'),
(211, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":19}', '2025-10-14 18:12:16'),
(212, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":20}', '2025-10-14 18:12:22'),
(213, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":10}', '2025-10-14 18:12:37'),
(214, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":25}', '2025-10-14 18:12:48'),
(215, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":28}', '2025-10-14 18:12:53'),
(216, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":11}', '2025-10-14 18:12:58'),
(217, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:04:40'),
(218, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:04:40'),
(219, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:04:40'),
(220, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:04:47'),
(221, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":5}', '2025-10-14 19:04:51'),
(222, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":17}', '2025-10-14 19:05:04'),
(223, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":23}', '2025-10-14 19:05:13'),
(224, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":28}', '2025-10-14 19:05:46'),
(225, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":10}', '2025-10-14 19:05:56'),
(226, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":10}', '2025-10-14 19:06:30'),
(227, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":24}', '2025-10-14 19:06:50'),
(228, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:26:04'),
(229, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:26:04'),
(230, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:26:04'),
(231, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:26:16'),
(232, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:26:20'),
(233, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:26:20'),
(234, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:26:20'),
(235, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Login.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:26:21'),
(236, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Register.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:26:29'),
(237, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Login.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:27:02'),
(238, 14, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:27:04'),
(239, 14, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:27:04'),
(240, 14, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:27:04'),
(241, 14, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Personal.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:27:06'),
(242, 14, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Personal.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:27:15'),
(243, 14, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Personal.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:27:25'),
(244, 14, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:27:48'),
(245, 14, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:27:48'),
(246, 14, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:27:48'),
(247, 14, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:27:53'),
(248, 14, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:28:25'),
(249, 14, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:28:29'),
(250, 14, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:28:29'),
(251, 14, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:28:29'),
(252, 14, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:28:31'),
(253, 14, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-14 19:29:23'),
(254, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-20 00:53:20'),
(255, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-20 00:53:20'),
(256, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-20 00:53:20'),
(257, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Login.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 00:54:26'),
(258, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Register.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 00:54:28'),
(259, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Login.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 00:54:46'),
(260, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 00:54:50'),
(261, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 00:54:50'),
(262, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 00:54:50'),
(263, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 00:54:57'),
(264, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 00:55:08'),
(265, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 00:55:20'),
(266, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 00:55:40'),
(267, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 00:55:49'),
(268, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:36:02'),
(269, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:36:05'),
(270, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:36:05'),
(271, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:36:05'),
(272, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:36:12'),
(273, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:36:12'),
(274, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:36:12'),
(275, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:36:46'),
(276, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:36:46'),
(277, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:36:46'),
(278, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:36:51'),
(279, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":9}', '2025-10-20 01:37:27'),
(280, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:37:30'),
(281, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:37:35'),
(282, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:37:35'),
(283, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:37:35'),
(284, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":9}', '2025-10-20 01:37:39'),
(285, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:37:41'),
(286, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":21}', '2025-10-20 01:37:46'),
(287, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:37:48'),
(288, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":9}', '2025-10-20 01:38:04'),
(289, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:44:27'),
(290, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:44:27'),
(291, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:44:27'),
(292, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:45:15'),
(293, 11, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":9}', '2025-10-20 01:45:19'),
(294, 11, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:45:21'),
(295, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:45:41'),
(296, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:45:41'),
(297, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:45:41'),
(298, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Login.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:45:42'),
(299, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Login.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:45:48'),
(300, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Login.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:45:59'),
(301, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Register.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:46:04'),
(302, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Login.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:46:37'),
(303, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:46:39'),
(304, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:46:40'),
(305, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:46:40'),
(306, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:46:43'),
(307, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:46:54'),
(308, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:46:54'),
(309, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:46:54'),
(310, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 01:46:57'),
(311, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-20 02:04:48'),
(312, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-20 02:04:48'),
(313, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-20 02:04:48'),
(314, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 02:05:07'),
(315, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":9}', '2025-10-20 02:05:11'),
(316, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 02:05:14'),
(317, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":34}', '2025-10-20 02:05:24'),
(318, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 02:05:27'),
(319, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":28}', '2025-10-20 02:06:07'),
(320, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 02:06:10'),
(321, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":2}', '2025-10-20 02:06:14'),
(322, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":5}', '2025-10-20 02:06:17'),
(323, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 02:06:20'),
(324, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Personal.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 02:06:28'),
(325, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 02:06:30'),
(326, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 02:06:30'),
(327, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 02:06:30'),
(328, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-20 05:16:31'),
(329, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-20 05:16:31'),
(330, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-20 05:16:31'),
(331, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 05:16:37'),
(332, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":2}', '2025-10-20 05:16:42'),
(333, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":9}', '2025-10-20 05:16:46'),
(334, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":19}', '2025-10-20 05:16:50'),
(335, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 05:16:51'),
(336, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":25}', '2025-10-20 05:17:00'),
(337, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 05:17:01'),
(338, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-20 08:51:23'),
(339, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-20 08:51:23'),
(340, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-20 08:51:23'),
(341, NULL, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Login.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 08:51:24'),
(342, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 08:51:28'),
(343, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 08:51:28'),
(344, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 08:51:28'),
(345, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":9}', '2025-10-20 08:51:36'),
(346, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 08:51:37'),
(347, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":19}', '2025-10-20 08:51:42'),
(348, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 08:51:44'),
(349, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":3}', '2025-10-20 08:52:00'),
(350, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 08:52:01'),
(351, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":10}', '2025-10-20 08:52:17'),
(352, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 08:52:18'),
(353, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":16}', '2025-10-20 08:52:34'),
(354, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":5}', '2025-10-20 09:10:41'),
(355, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":23}', '2025-10-20 09:10:47'),
(356, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":31}', '2025-10-20 09:10:54'),
(357, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 09:10:59'),
(358, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":27}', '2025-10-20 09:11:35'),
(359, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 09:11:39'),
(360, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 09:13:16'),
(361, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 09:13:16'),
(362, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 09:13:16'),
(363, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 09:21:00'),
(364, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 09:21:19'),
(365, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 09:21:19'),
(366, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 09:21:19'),
(367, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-20 17:33:46');
INSERT INTO `user_events` (`id`, `user_id`, `session_id`, `event_type`, `metadata`, `created_at`) VALUES
(368, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/\",\"title\":\"Mơ Shop\"}', '2025-10-20 17:33:53'),
(369, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":2}', '2025-10-20 17:33:56'),
(370, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 17:33:59'),
(371, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 17:34:21'),
(372, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":24}', '2025-10-20 17:34:30'),
(373, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":21}', '2025-10-20 17:34:46'),
(374, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":3}', '2025-10-20 17:34:53'),
(375, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":16}', '2025-10-20 17:35:05'),
(376, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":33}', '2025-10-20 17:35:18'),
(377, 15, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":34}', '2025-10-20 17:35:31'),
(378, 15, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 17:35:58'),
(379, 16, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 17:36:32'),
(380, 16, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 17:36:37'),
(381, 16, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 17:36:39'),
(382, 16, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":25}', '2025-10-20 17:36:47'),
(383, 16, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 17:37:00'),
(384, 16, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":34}', '2025-10-20 17:37:11'),
(385, 16, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":46}', '2025-10-20 17:37:22'),
(386, 16, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":42}', '2025-10-20 17:37:29'),
(387, 16, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":5}', '2025-10-20 17:37:36'),
(388, 16, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 17:37:39'),
(389, 16, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":17}', '2025-10-20 17:37:52'),
(390, 16, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 17:37:56'),
(391, 16, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 17:38:13'),
(392, 16, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/index.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 17:53:52'),
(393, 16, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 17:53:55'),
(394, 16, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":4}', '2025-10-20 17:53:57'),
(395, 16, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 17:54:00'),
(396, 16, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":11}', '2025-10-20 17:54:02'),
(397, 16, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 17:54:04'),
(398, 16, 'yo9b6scu231760348606010', 'page_view', '{\"path\":\"/TTTT/WebBanHangMyPham/src/View/Detail_Product.php\",\"title\":\"Mơ Shop\"}', '2025-10-20 17:54:05'),
(399, 16, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":5}', '2025-10-20 17:57:50'),
(400, 16, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":17}', '2025-10-20 17:57:58'),
(401, 16, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":11}', '2025-10-20 17:58:18'),
(402, 16, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":19}', '2025-10-20 17:58:26'),
(403, 16, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":21}', '2025-10-20 17:58:40'),
(404, 16, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":5}', '2025-10-23 15:29:03'),
(405, 16, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":5}', '2025-10-23 15:29:15'),
(406, 16, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":36}', '2025-10-23 15:29:28'),
(407, 17, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":35}', '2025-10-26 16:22:17'),
(408, 17, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":25}', '2025-10-26 16:22:33'),
(409, 17, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":28}', '2025-10-26 16:22:46'),
(410, 17, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":22}', '2025-10-30 19:09:17'),
(411, 17, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":9}', '2025-10-30 19:09:53'),
(412, 17, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":25}', '2025-10-30 19:10:03'),
(413, 17, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":19}', '2025-10-31 18:21:03'),
(414, 17, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":19}', '2025-10-31 18:21:13'),
(415, 17, 'yo9b6scu231760348606010', 'chat_message', '{\"len\":19}', '2025-10-31 18:23:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_intents`
--

CREATE TABLE `user_intents` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(64) DEFAULT NULL,
  `intent` varchar(64) NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `user_intents`
--

INSERT INTO `user_intents` (`id`, `user_id`, `session_id`, `intent`, `details`, `created_at`) VALUES
(1, 13, 'yo9b6scu231760348606010', 'greeting', NULL, '2025-10-13 17:36:07'),
(2, 13, 'yo9b6scu231760348606010', 'need_capture', 'need', '2025-10-13 17:36:18'),
(3, 13, 'yo9b6scu231760348606010', 'product_suggestion', NULL, '2025-10-13 17:36:21'),
(4, 13, 'yo9b6scu231760348606010', 'need_capture', 'need', '2025-10-13 17:38:23'),
(5, 13, 'yo9b6scu231760348606010', 'greeting', NULL, '2025-10-13 17:45:59'),
(6, 13, 'yo9b6scu231760348606010', 'greeting', NULL, '2025-10-13 17:49:31'),
(7, 13, 'yo9b6scu231760348606010', 'price_inquiry', NULL, '2025-10-13 17:49:42'),
(8, 13, 'yo9b6scu231760348606010', 'greeting', NULL, '2025-10-13 17:49:58'),
(9, 13, 'yo9b6scu231760348606010', 'greeting', NULL, '2025-10-13 17:50:04'),
(11, 15, 'yo9b6scu231760348606010', 'schedule_confirmed', '2025-10-20 19:30:00', '2025-10-20 08:52:34'),
(13, 15, 'yo9b6scu231760348606010', 'schedule_confirmed', '2025-10-22 06:30:00', '2025-10-20 17:35:05'),
(14, 15, 'yo9b6scu231760348606010', 'idle', NULL, '2025-10-20 17:35:05');

-- --------------------------------------------------------

--
-- Cấu trúc cho view `users`
--
DROP TABLE IF EXISTS `users`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `users`  AS SELECT `khachhang`.`ID_KhachHang` AS `id`, `khachhang`.`HoVaTen` AS `name`, `khachhang`.`SDT` AS `phone`, `khachhang`.`Email` AS `email`, `khachhang`.`ThoiGianTao` AS `first_visit`, `khachhang`.`ThoiGianCapNhat` AS `last_visit` FROM `khachhang` ;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `binhluan`
--
ALTER TABLE `binhluan`
  ADD PRIMARY KEY (`ID_BinhLuan`),
  ADD KEY `ID_KhachHang` (`ID_KhachHang`),
  ADD KEY `sanpham5` (`ID_SanPham`);

--
-- Chỉ mục cho bảng `chat_appointments`
--
ALTER TABLE `chat_appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sid` (`session_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_appt` (`appt_at`);

--
-- Chỉ mục cho bảng `chat_bookings`
--
ALTER TABLE `chat_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_book_at` (`book_at`);

--
-- Chỉ mục cho bảng `chat_logs`
--
ALTER TABLE `chat_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_user2` (`user_id`),
  ADD KEY `idx_time` (`timestamp`);

--
-- Chỉ mục cho bảng `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Chỉ mục cho bảng `chat_session_state`
--
ALTER TABLE `chat_session_state`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_chat_state_session` (`session_id`),
  ADD UNIQUE KEY `uniq_chat_state_user` (`user_id`),
  ADD KEY `state` (`state`);

--
-- Chỉ mục cho bảng `consultations`
--
ALTER TABLE `consultations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `appointment_time` (`appointment_time`);

--
-- Chỉ mục cho bảng `customer_needs`
--
ALTER TABLE `customer_needs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Chỉ mục cho bảng `danhmuc`
--
ALTER TABLE `danhmuc`
  ADD PRIMARY KEY (`IDDanhMuc`);

--
-- Chỉ mục cho bảng `danhsachyeuthich`
--
ALTER TABLE `danhsachyeuthich`
  ADD PRIMARY KEY (`ID_DSYeuThich`),
  ADD KEY `ID_KhachHang` (`ID_KhachHang`),
  ADD KEY `ID_SanPham` (`ID_SanPham`);

--
-- Chỉ mục cho bảng `donhang`
--
ALTER TABLE `donhang`
  ADD PRIMARY KEY (`ID_DonHang`),
  ADD KEY `khachhang` (`ID_KhachHang`);

--
-- Chỉ mục cho bảng `donhangchitiet`
--
ALTER TABLE `donhangchitiet`
  ADD PRIMARY KEY (`ID_DonHangChiTiet`),
  ADD KEY `sanpham` (`ID_SanPham`),
  ADD KEY `donhang` (`ID_DonHang`);

--
-- Chỉ mục cho bảng `khachhang`
--
ALTER TABLE `khachhang`
  ADD PRIMARY KEY (`ID_KhachHang`);

--
-- Chỉ mục cho bảng `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `lead_events`
--
ALTER TABLE `lead_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_intent` (`intent`);
ALTER TABLE `lead_events` ADD FULLTEXT KEY `ft_msg` (`message`);

--
-- Chỉ mục cho bảng `lead_scores`
--
ALTER TABLE `lead_scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user` (`user_id`),
  ADD UNIQUE KEY `uniq_session` (`session_id`);

--
-- Chỉ mục cho bảng `nap_tien`
--
ALTER TABLE `nap_tien`
  ADD PRIMARY KEY (`ID_Nap`);

--
-- Chỉ mục cho bảng `sanpham`
--
ALTER TABLE `sanpham`
  ADD PRIMARY KEY (`IDSanPham`),
  ADD KEY `danhmuc` (`IDDanhMuc`),
  ADD KEY `thuonghieu` (`IDThuongHieu`);
ALTER TABLE `sanpham` ADD FULLTEXT KEY `ft_name_desc` (`TenSanPham`,`MoTaChiTiet`);

--
-- Chỉ mục cho bảng `thuonghieu`
--
ALTER TABLE `thuonghieu`
  ADD PRIMARY KEY (`IDThuongHieu`);

--
-- Chỉ mục cho bảng `tintucbaiviet`
--
ALTER TABLE `tintucbaiviet`
  ADD PRIMARY KEY (`ID_TinTucBaiViet`);

--
-- Chỉ mục cho bảng `user_actions`
--
ALTER TABLE `user_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action` (`action`);

--
-- Chỉ mục cho bảng `user_events`
--
ALTER TABLE `user_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `event_type` (`event_type`);

--
-- Chỉ mục cho bảng `user_intents`
--
ALTER TABLE `user_intents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `intent` (`intent`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `binhluan`
--
ALTER TABLE `binhluan`
  MODIFY `ID_BinhLuan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT cho bảng `chat_appointments`
--
ALTER TABLE `chat_appointments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `chat_bookings`
--
ALTER TABLE `chat_bookings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chat_logs`
--
ALTER TABLE `chat_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=324;

--
-- AUTO_INCREMENT cho bảng `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=430;

--
-- AUTO_INCREMENT cho bảng `chat_session_state`
--
ALTER TABLE `chat_session_state`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `consultations`
--
ALTER TABLE `consultations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `customer_needs`
--
ALTER TABLE `customer_needs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `danhmuc`
--
ALTER TABLE `danhmuc`
  MODIFY `IDDanhMuc` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT cho bảng `danhsachyeuthich`
--
ALTER TABLE `danhsachyeuthich`
  MODIFY `ID_DSYeuThich` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT cho bảng `donhang`
--
ALTER TABLE `donhang`
  MODIFY `ID_DonHang` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT cho bảng `donhangchitiet`
--
ALTER TABLE `donhangchitiet`
  MODIFY `ID_DonHangChiTiet` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT cho bảng `khachhang`
--
ALTER TABLE `khachhang`
  MODIFY `ID_KhachHang` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT cho bảng `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `lead_events`
--
ALTER TABLE `lead_events`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `lead_scores`
--
ALTER TABLE `lead_scores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=484;

--
-- AUTO_INCREMENT cho bảng `nap_tien`
--
ALTER TABLE `nap_tien`
  MODIFY `ID_Nap` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `sanpham`
--
ALTER TABLE `sanpham`
  MODIFY `IDSanPham` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT cho bảng `thuonghieu`
--
ALTER TABLE `thuonghieu`
  MODIFY `IDThuongHieu` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT cho bảng `tintucbaiviet`
--
ALTER TABLE `tintucbaiviet`
  MODIFY `ID_TinTucBaiViet` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT cho bảng `user_actions`
--
ALTER TABLE `user_actions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=220;

--
-- AUTO_INCREMENT cho bảng `user_events`
--
ALTER TABLE `user_events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=416;

--
-- AUTO_INCREMENT cho bảng `user_intents`
--
ALTER TABLE `user_intents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `binhluan`
--
ALTER TABLE `binhluan`
  ADD CONSTRAINT `binhluan_ibfk_1` FOREIGN KEY (`ID_KhachHang`) REFERENCES `khachhang` (`ID_KhachHang`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `sanpham5` FOREIGN KEY (`ID_SanPham`) REFERENCES `sanpham` (`IDSanPham`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `danhsachyeuthich`
--
ALTER TABLE `danhsachyeuthich`
  ADD CONSTRAINT `danhsachyeuthich_ibfk_1` FOREIGN KEY (`ID_KhachHang`) REFERENCES `khachhang` (`ID_KhachHang`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `danhsachyeuthich_ibfk_2` FOREIGN KEY (`ID_SanPham`) REFERENCES `sanpham` (`IDSanPham`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `donhang`
--
ALTER TABLE `donhang`
  ADD CONSTRAINT `khachhang` FOREIGN KEY (`ID_KhachHang`) REFERENCES `khachhang` (`ID_KhachHang`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `donhangchitiet`
--
ALTER TABLE `donhangchitiet`
  ADD CONSTRAINT `donhang` FOREIGN KEY (`ID_DonHang`) REFERENCES `donhang` (`ID_DonHang`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `sanpham`
--
ALTER TABLE `sanpham`
  ADD CONSTRAINT `danhmuc` FOREIGN KEY (`IDDanhMuc`) REFERENCES `danhmuc` (`IDDanhMuc`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `thuonghieu` FOREIGN KEY (`IDThuongHieu`) REFERENCES `thuonghieu` (`IDThuongHieu`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

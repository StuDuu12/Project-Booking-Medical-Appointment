-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 27, 2026 at 04:43 AM
-- Server version: 5.7.39
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `chuduyit_medical_k73`
--

-- --------------------------------------------------------

--
-- Table structure for table `admintb`
--

CREATE TABLE `admintb` (
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admintb`
--

INSERT INTO `admintb` (`username`, `password`) VALUES
('admin', '123');

-- --------------------------------------------------------

--
-- Table structure for table `appointmenttb`
--

CREATE TABLE `appointmenttb` (
  `ID` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `fname` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lname` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `doctor` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `docFees` int(5) NOT NULL,
  `appdate` date NOT NULL,
  `apptime` time NOT NULL,
  `slot_id` int(11) DEFAULT NULL,
  `userStatus` int(5) NOT NULL,
  `doctorStatus` int(5) NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointmenttb`
--

INSERT INTO `appointmenttb` (`ID`, `pid`, `fname`, `lname`, `gender`, `email`, `contact`, `doctor`, `docFees`, `appdate`, `apptime`, `slot_id`, `userStatus`, `doctorStatus`, `notes`, `created_at`) VALUES
(1, 4, 'Kishan', 'Lal', 'Male', 'kishansmart0@gmail.com', '8838489464', 'Ganesh', 550, '2020-02-14', '10:00:00', NULL, 1, 0, NULL, '2026-01-14 18:04:44'),
(2, 4, 'Kishan', 'Lal', 'Male', 'kishansmart0@gmail.com', '8838489464', 'Dinesh', 700, '2020-02-28', '10:00:00', NULL, 0, 1, NULL, '2026-01-14 18:04:44'),
(3, 12, 'Duy', 'Chu Quang', 'Male', 'duywinter@gmail.com', '0846181174', 'Abbis', 1500, '2026-01-15', '10:00:00', NULL, 0, 1, NULL, '2026-01-14 18:04:44'),
(4, 4, 'Duy', 'Chu Quang', 'Male', 'duywinter@gmail.com', '0846181174', 'bui.viet', 400000, '2026-02-26', '13:30:00', 12, 0, 1, NULL, '2026-01-15 14:11:50'),
(5, 4, 'Duy', 'Chu Quang', 'Male', 'duywinter@gmail.com', '0846181174', 'phan.son', 350000, '2026-11-18', '10:30:00', 24, 0, 1, NULL, '2026-01-15 14:12:09'),
(6, 4, 'Duy', 'Chu Quang', 'Male', 'duywinter@gmail.com', '0846181174', 'Bùi Quốc Việt', 400000, '2026-03-18', '13:30:00', 48, 1, 1, NULL, '2026-01-15 14:19:48'),
(7, 4, 'Duy', 'Chu Quang', 'Male', 'duywinter@gmail.com', '0846181174', 'truong.nam', 350000, '2026-12-28', '10:00:00', 59, 0, 1, NULL, '2026-01-21 03:01:59'),
(8, 4, 'Duy', 'Chu Quang', 'Male', 'duywinter@gmail.com', '0846181174', 'truong.nam', 350000, '2026-06-30', '14:00:00', 85, 1, 1, NULL, '2026-01-21 03:03:27'),
(9, 4, 'Duy', 'Chu Quang', 'Male', 'duywinter@gmail.com', '0846181174', 'bui.viet', 400000, '2026-04-15', '08:30:00', 92, 1, 1, NULL, '2026-01-21 03:07:09');

-- --------------------------------------------------------

--
-- Table structure for table `contact`
--

CREATE TABLE `contact` (
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact`
--

INSERT INTO `contact` (`name`, `email`, `contact`, `message`) VALUES
('Anu', 'anu@gmail.com', '7896677554', 'Hey Admin'),
('Viki', 'viki@gmail.com', '9899778865', 'Good Job, Pal'),
('Duy Chu Quang', 'duywinter@gmail.com', '0846181174', 'Hệ thống hoạt động tốt');

-- --------------------------------------------------------

--
-- Table structure for table `doctb`
--

CREATE TABLE `doctb` (
  `id` int(11) NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fullname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `spec` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `spec_id` int(11) DEFAULT NULL,
  `docFees` int(10) NOT NULL,
  `phone` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` mediumtext COLLATE utf8mb4_unicode_ci,
  `experience_years` int(3) DEFAULT '0',
  `status` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `average_rating` decimal(3,2) DEFAULT NULL COMMENT 'Average rating 1.00-5.00',
  `total_ratings` int(11) DEFAULT '0' COMMENT 'Total number of ratings'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctb`
--

INSERT INTO `doctb` (`id`, `username`, `password`, `fullname`, `email`, `spec`, `spec_id`, `docFees`, `phone`, `avatar`, `bio`, `experience_years`, `status`, `created_at`, `average_rating`, `total_ratings`) VALUES
(1, 'le.chau', '123', 'Lê Minh Châu', 'le.chau@hospital.vn', 'Pediatrics', 1, 250000, '0901234569', NULL, 'Bác sĩ nhi khoa tận tâm, yêu trẻ em', 10, 1, '2026-01-14 18:04:43', 5.00, 1),
(2, 'pham.dung', '123', 'Phạm Thị Dung', 'pham.dung@hospital.vn', 'Pediatrics', 1, 280000, '0901234570', NULL, 'Chuyên gia nhi khoa, đặc biệt về bệnh hô hấp trẻ em', 8, 1, '2026-01-14 18:04:43', NULL, 0),
(3, 'vu.giang', '123', 'Vũ Thị Giang', 'vu.giang@hospital.vn', 'Obstetrics_Gynecology', 2, 300000, '0901234573', NULL, 'Chuyên gia sản phụ khoa, đỡ đẻ hơn 5000 ca', 16, 1, '2026-01-14 18:04:43', NULL, 0),
(4, 'dang.hung', '123', 'Đặng Văn Hùng', 'dang.hung@hospital.vn', 'Obstetrics_Gynecology', 2, 350000, '0901234574', NULL, 'Bác sĩ sản khoa, chuyên thai kỳ nguy cơ cao', 13, 1, '2026-01-14 18:04:43', NULL, 0),
(5, 'hoang.em', '123', 'Hoàng Văn Em', 'hoang.em@hospital.vn', 'Dermatology', 3, 320000, '0901234571', NULL, 'Chuyên gia da liễu, điều trị mụn và các bệnh da mãn tính', 14, 1, '2026-01-14 18:04:43', NULL, 0),
(6, 'ngo.phuong', '123', 'Ngô Thị Phương', 'ngo.phuong@hospital.vn', 'Dermatology', 3, 350000, '0901234572', NULL, 'Bác sĩ da liễu thẩm mỹ, chuyên trị nám và tàn nhang', 11, 1, '2026-01-14 18:04:43', NULL, 0),
(7, 'ly.minh', '123', 'Lý Văn Minh', 'ly.minh@hospital.vn', 'Gastroenterology', 4, 320000, '0901234577', NULL, 'Chuyên gia nội soi tiêu hóa, điều trị viêm loét dạ dày', 14, 1, '2026-01-14 18:04:43', NULL, 0),
(8, 'mai.ngoc', '123', 'Mai Thị Ngọc', 'mai.ngoc@hospital.vn', 'Gastroenterology', 4, 280000, '0901234578', NULL, 'Bác sĩ tiêu hóa, chuyên bệnh gan mật', 9, 1, '2026-01-14 18:04:43', NULL, 0),
(9, 'phan.son', '123', 'Phan Văn Sơn', 'phan.son@hospital.vn', 'Rheumatology', 5, 350000, '0901234581', NULL, 'Chuyên gia xương khớp, điều trị viêm khớp dạng thấp', 15, 1, '2026-01-14 18:04:43', NULL, 0),
(10, 'cao.tam', '123', 'Cao Thị Tâm', 'cao.tam@hospital.vn', 'Rheumatology', 5, 300000, '0901234582', NULL, 'Bác sĩ cơ xương khớp, chuyên gout và loãng xương', 10, 1, '2026-01-14 18:04:43', NULL, 0),
(11, 'dinh.phong', '123', 'Đinh Văn Phong', 'dinh.phong@hospital.vn', 'ENT', 8, 280000, '0901234579', NULL, 'Bác sĩ TMH, phẫu thuật nội soi xoang', 11, 1, '2026-01-14 18:04:43', NULL, 0),
(12, 'to.quynh', '123', 'Tô Thị Quỳnh', 'to.quynh@hospital.vn', 'ENT', 8, 260000, '0901234580', NULL, 'Bác sĩ TMH, điều trị viêm họng và viêm amidan', 8, 1, '2026-01-14 18:04:43', NULL, 0),
(13, 'le.duc', '123', 'Lê Văn Đức', 'le.duc@hospital.vn', 'Oncology', 9, 500000, '0901234589', NULL, 'Giáo sư ung bướu, chuyên gia hóa trị', 17, 1, '2026-01-14 18:04:43', NULL, 0),
(14, 'pham.mai', '123', 'Phạm Thị Mai', 'pham.mai@hospital.vn', 'Oncology', 9, 450000, '0901234590', NULL, 'Bác sĩ ung bướu, xạ trị và điều trị đích', 14, 1, '2026-01-14 18:04:43', NULL, 0),
(15, 'nguyen.an', '123', 'Nguyễn Văn An', 'nguyen.an@hospital.vn', 'Cardiology', 10, 300000, '0901234567', NULL, 'Chuyên gia tim mạch với 15 năm kinh nghiệm, từng tu nghiệp tại Pháp', 15, 1, '2026-01-14 18:04:43', NULL, 0),
(16, 'tran.binh', '123', 'Trần Thị Bình', 'tran.binh@hospital.vn', 'Cardiology', 10, 350000, '0901234568', NULL, 'Bác sĩ chuyên khoa II Tim mạch, giảng viên Đại học Y', 12, 1, '2026-01-14 18:04:43', NULL, 0),
(17, 'bui.viet', '123', 'Bùi Quốc Việt', 'bui.viet@hospital.vn', 'Orthopedics', 12, 400000, '0901234595', NULL, 'Phẫu thuật viên chỉnh hình, thay khớp háng và gối', 18, 1, '2026-01-14 18:04:43', NULL, 0),
(18, 'truong.nam', '123', 'Trương Văn Nam', 'truong.nam@hospital.vn', 'Orthopedics', 12, 350000, '0901234596', NULL, 'Bác sĩ chấn thương, nội soi khớp vai và gối', 12, 1, '2026-01-14 18:04:43', NULL, 0),
(19, 'lam.xuan', '123', 'Lâm Văn Xuân', 'lam.xuan@hospital.vn', 'Dentistry', 16, 250000, '0901234585', NULL, 'Bác sĩ RHM, chuyên nhổ răng khôn và implant', 12, 1, '2026-01-14 18:04:43', NULL, 0),
(20, 'vo.yen', '123', 'Võ Thị Yến', 'vo.yen@hospital.vn', 'Dentistry', 16, 280000, '0901234586', NULL, 'Bác sĩ nha khoa thẩm mỹ, bọc răng sứ', 8, 1, '2026-01-14 18:04:43', NULL, 0),
(21, 'vu.long', '123', 'Vũ Đình Long', 'vu.long@hospital.vn', 'Endocrinology', 19, 350000, '0901234593', NULL, 'Chuyên gia nội tiết, điều trị tiểu đường và tuyến giáp', 16, 1, '2026-01-14 18:04:43', NULL, 0),
(22, 'dang.linh', '123', 'Đặng Thị Linh', 'dang.linh@hospital.vn', 'Endocrinology', 19, 320000, '0901234594', NULL, 'Bác sĩ nội tiết, rối loạn chuyển hóa', 11, 1, '2026-01-14 18:04:43', NULL, 0),
(23, 'ly.hoang', '123', 'Lý Minh Hoàng', 'ly.hoang@hospital.vn', 'Psychiatry', 20, 400000, '0901234597', NULL, 'Bác sĩ tâm thần, điều trị trầm cảm và lo âu', 15, 1, '2026-01-14 18:04:43', NULL, 0),
(24, 'mai.nga', '123', 'Mai Thanh Nga', 'mai.nga@hospital.vn', 'Psychiatry', 20, 350000, '0901234598', NULL, 'Bác sĩ tâm thần, rối loạn giấc ngủ', 10, 1, '2026-01-14 18:04:43', NULL, 0),
(25, 'hoang.quan', '123', 'Hoàng Minh Quân', 'hoang.quan@hospital.vn', 'Pulmonology', 21, 300000, '0901234591', NULL, 'Bác sĩ hô hấp, điều trị hen suyễn và COPD', 13, 1, '2026-01-14 18:04:43', NULL, 0),
(26, 'ngo.thao', '123', 'Ngô Thị Thảo', 'ngo.thao@hospital.vn', 'Pulmonology', 21, 280000, '0901234592', NULL, 'Bác sĩ phổi, nội soi phế quản', 10, 1, '2026-01-14 18:04:43', NULL, 0),
(27, 'bui.kien', '123', 'Bùi Văn Kiên', 'bui.kien@hospital.vn', 'Neurology', 25, 400000, '0901234575', NULL, 'Giáo sư thần kinh học, chuyên gia đột quỵ', 18, 1, '2026-01-14 18:04:43', NULL, 0),
(28, 'truong.lan', '123', 'Trương Thị Lan', 'truong.lan@hospital.vn', 'Neurology', 25, 350000, '0901234576', NULL, 'Bác sĩ thần kinh, điều trị đau đầu và động kinh', 12, 1, '2026-01-14 18:04:43', NULL, 0),
(29, 'dinh.danh', '123', 'Đinh Công Danh', 'dinh.danh@hospital.vn', 'Traditional_Medicine', 29, 250000, '0901234599', NULL, 'Lương y, châm cứu và bấm huyệt', 22, 1, '2026-01-14 18:04:43', NULL, 0),
(30, 'to.hanh', '123', 'Tô Thị Hạnh', 'to.hanh@hospital.vn', 'Traditional_Medicine', 29, 230000, '0901234600', NULL, 'Bác sĩ YHCT, thuốc nam và thuốc bắc', 18, 1, '2026-01-14 18:04:43', NULL, 0),
(31, 'duong.uy', '123', 'Dương Văn Uy', 'duong.uy@hospital.vn', 'Ophthalmology', 32, 300000, '0901234583', NULL, 'Bác sĩ nhãn khoa, phẫu thuật đục thủy tinh thể', 13, 1, '2026-01-14 18:04:43', NULL, 0),
(32, 'ho.van', '123', 'Hồ Thị Vân', 'ho.van@hospital.vn', 'Ophthalmology', 32, 280000, '0901234584', NULL, 'Bác sĩ mắt, điều trị cận thị và tật khúc xạ', 9, 1, '2026-01-14 18:04:43', NULL, 0),
(33, 'nguyen.tung', '123', 'Nguyễn Thanh Tùng', 'nguyen.tung@hospital.vn', 'Internal_Medicine', 37, 200000, '0901234587', NULL, 'Bác sĩ nội khoa tổng quát, kinh nghiệm 20 năm', 20, 1, '2026-01-14 18:04:43', NULL, 0),
(34, 'tran.huong', '123', 'Trần Thị Hương', 'tran.huong@hospital.vn', 'Internal_Medicine', 37, 220000, '0901234588', NULL, 'Bác sĩ đa khoa, khám sức khỏe tổng quát', 15, 1, '2026-01-14 18:04:43', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `doctor_ratings`
--

CREATE TABLE `doctor_ratings` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `rating` tinyint(1) NOT NULL COMMENT '1-5 stars',
  `review` text COLLATE utf8mb4_unicode_ci,
  `professionalism` tinyint(1) DEFAULT NULL COMMENT '1-5 rating',
  `communication` tinyint(1) DEFAULT NULL COMMENT '1-5 rating',
  `environment` tinyint(1) DEFAULT NULL COMMENT '1-5 rating',
  `wait_time` tinyint(1) DEFAULT NULL COMMENT '1-5 rating',
  `is_verified` tinyint(1) DEFAULT '0' COMMENT 'Verified if linked to actual appointment',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctor_ratings`
--

INSERT INTO `doctor_ratings` (`id`, `doctor_id`, `patient_id`, `appointment_id`, `rating`, `review`, `professionalism`, `communication`, `environment`, `wait_time`, `is_verified`, `created_at`, `updated_at`) VALUES
(1, 1, 4, NULL, 5, 'Nice', NULL, NULL, NULL, NULL, 0, '2026-01-27 04:38:24', '2026-01-27 04:38:24');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_schedules`
--

CREATE TABLE `doctor_schedules` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` tinyint(1) NOT NULL COMMENT '0=CN, 1=T2, 2=T3, 3=T4, 4=T5, 5=T6, 6=T7',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slot_duration` int(3) DEFAULT '30' COMMENT 'Thời gian mỗi slot (phút)',
  `max_patients` int(3) DEFAULT '1' COMMENT 'Số bệnh nhân tối đa mỗi slot',
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctor_schedules`
--

INSERT INTO `doctor_schedules` (`id`, `doctor_id`, `day_of_week`, `start_time`, `end_time`, `slot_duration`, `max_patients`, `is_active`) VALUES
(1, 1, 1, '08:00:00', '17:00:00', 30, 1, 1),
(2, 2, 1, '08:00:00', '17:00:00', 30, 1, 1),
(3, 3, 1, '08:00:00', '17:00:00', 30, 1, 1),
(4, 4, 1, '08:00:00', '17:00:00', 30, 1, 1),
(5, 5, 1, '08:00:00', '17:00:00', 30, 1, 1),
(6, 6, 1, '08:00:00', '17:00:00', 30, 1, 1),
(7, 7, 1, '08:00:00', '17:00:00', 30, 1, 1),
(8, 8, 1, '08:00:00', '17:00:00', 30, 1, 1),
(9, 9, 1, '08:00:00', '17:00:00', 30, 1, 1),
(10, 10, 1, '08:00:00', '17:00:00', 30, 1, 1),
(11, 11, 1, '08:00:00', '17:00:00', 30, 1, 1),
(12, 12, 1, '08:00:00', '17:00:00', 30, 1, 1),
(13, 13, 1, '08:00:00', '17:00:00', 30, 1, 1),
(14, 14, 1, '08:00:00', '17:00:00', 30, 1, 1),
(15, 15, 1, '08:00:00', '17:00:00', 30, 1, 1),
(16, 16, 1, '08:00:00', '17:00:00', 30, 1, 1),
(17, 17, 1, '08:00:00', '17:00:00', 30, 1, 1),
(18, 18, 1, '08:00:00', '17:00:00', 30, 1, 1),
(19, 19, 1, '08:00:00', '17:00:00', 30, 1, 1),
(20, 20, 1, '08:00:00', '17:00:00', 30, 1, 1),
(21, 21, 1, '08:00:00', '17:00:00', 30, 1, 1),
(22, 22, 1, '08:00:00', '17:00:00', 30, 1, 1),
(23, 23, 1, '08:00:00', '17:00:00', 30, 1, 1),
(24, 24, 1, '08:00:00', '17:00:00', 30, 1, 1),
(25, 25, 1, '08:00:00', '17:00:00', 30, 1, 1),
(26, 26, 1, '08:00:00', '17:00:00', 30, 1, 1),
(27, 27, 1, '08:00:00', '17:00:00', 30, 1, 1),
(28, 28, 1, '08:00:00', '17:00:00', 30, 1, 1),
(29, 29, 1, '08:00:00', '17:00:00', 30, 1, 1),
(30, 30, 1, '08:00:00', '17:00:00', 30, 1, 1),
(31, 31, 1, '08:00:00', '17:00:00', 30, 1, 1),
(32, 32, 1, '08:00:00', '17:00:00', 30, 1, 1),
(33, 33, 1, '08:00:00', '17:00:00', 30, 1, 1),
(34, 34, 1, '08:00:00', '17:00:00', 30, 1, 1),
(35, 1, 2, '08:00:00', '17:00:00', 30, 1, 1),
(36, 2, 2, '08:00:00', '17:00:00', 30, 1, 1),
(37, 3, 2, '08:00:00', '17:00:00', 30, 1, 1),
(38, 4, 2, '08:00:00', '17:00:00', 30, 1, 1),
(39, 5, 2, '08:00:00', '17:00:00', 30, 1, 1),
(40, 6, 2, '08:00:00', '17:00:00', 30, 1, 1),
(41, 7, 2, '08:00:00', '17:00:00', 30, 1, 1),
(42, 8, 2, '08:00:00', '17:00:00', 30, 1, 1),
(43, 9, 2, '08:00:00', '17:00:00', 30, 1, 1),
(44, 10, 2, '08:00:00', '17:00:00', 30, 1, 1),
(45, 11, 2, '08:00:00', '17:00:00', 30, 1, 1),
(46, 12, 2, '08:00:00', '17:00:00', 30, 1, 1),
(47, 13, 2, '08:00:00', '17:00:00', 30, 1, 1),
(48, 14, 2, '08:00:00', '17:00:00', 30, 1, 1),
(49, 15, 2, '08:00:00', '17:00:00', 30, 1, 1),
(50, 16, 2, '08:00:00', '17:00:00', 30, 1, 1),
(51, 17, 2, '08:00:00', '17:00:00', 30, 1, 1),
(52, 18, 2, '08:00:00', '17:00:00', 30, 1, 1),
(53, 19, 2, '08:00:00', '17:00:00', 30, 1, 1),
(54, 20, 2, '08:00:00', '17:00:00', 30, 1, 1),
(55, 21, 2, '08:00:00', '17:00:00', 30, 1, 1),
(56, 22, 2, '08:00:00', '17:00:00', 30, 1, 1),
(57, 23, 2, '08:00:00', '17:00:00', 30, 1, 1),
(58, 24, 2, '08:00:00', '17:00:00', 30, 1, 1),
(59, 25, 2, '08:00:00', '17:00:00', 30, 1, 1),
(60, 26, 2, '08:00:00', '17:00:00', 30, 1, 1),
(61, 27, 2, '08:00:00', '17:00:00', 30, 1, 1),
(62, 28, 2, '08:00:00', '17:00:00', 30, 1, 1),
(63, 29, 2, '08:00:00', '17:00:00', 30, 1, 1),
(64, 30, 2, '08:00:00', '17:00:00', 30, 1, 1),
(65, 31, 2, '08:00:00', '17:00:00', 30, 1, 1),
(66, 32, 2, '08:00:00', '17:00:00', 30, 1, 1),
(67, 33, 2, '08:00:00', '17:00:00', 30, 1, 1),
(68, 34, 2, '08:00:00', '17:00:00', 30, 1, 1),
(69, 1, 3, '08:00:00', '17:00:00', 30, 1, 1),
(70, 2, 3, '08:00:00', '17:00:00', 30, 1, 1),
(71, 3, 3, '08:00:00', '17:00:00', 30, 1, 1),
(72, 4, 3, '08:00:00', '17:00:00', 30, 1, 1),
(73, 5, 3, '08:00:00', '17:00:00', 30, 1, 1),
(74, 6, 3, '08:00:00', '17:00:00', 30, 1, 1),
(75, 7, 3, '08:00:00', '17:00:00', 30, 1, 1),
(76, 8, 3, '08:00:00', '17:00:00', 30, 1, 1),
(77, 9, 3, '08:00:00', '17:00:00', 30, 1, 1),
(78, 10, 3, '08:00:00', '17:00:00', 30, 1, 1),
(79, 11, 3, '08:00:00', '17:00:00', 30, 1, 1),
(80, 12, 3, '08:00:00', '17:00:00', 30, 1, 1),
(81, 13, 3, '08:00:00', '17:00:00', 30, 1, 1),
(82, 14, 3, '08:00:00', '17:00:00', 30, 1, 1),
(83, 15, 3, '08:00:00', '17:00:00', 30, 1, 1),
(84, 16, 3, '08:00:00', '17:00:00', 30, 1, 1),
(85, 17, 3, '08:00:00', '17:00:00', 30, 1, 1),
(86, 18, 3, '08:00:00', '17:00:00', 30, 1, 1),
(87, 19, 3, '08:00:00', '17:00:00', 30, 1, 1),
(88, 20, 3, '08:00:00', '17:00:00', 30, 1, 1),
(89, 21, 3, '08:00:00', '17:00:00', 30, 1, 1),
(90, 22, 3, '08:00:00', '17:00:00', 30, 1, 1),
(91, 23, 3, '08:00:00', '17:00:00', 30, 1, 1),
(92, 24, 3, '08:00:00', '17:00:00', 30, 1, 1),
(93, 25, 3, '08:00:00', '17:00:00', 30, 1, 1),
(94, 26, 3, '08:00:00', '17:00:00', 30, 1, 1),
(95, 27, 3, '08:00:00', '17:00:00', 30, 1, 1),
(96, 28, 3, '08:00:00', '17:00:00', 30, 1, 1),
(97, 29, 3, '08:00:00', '17:00:00', 30, 1, 1),
(98, 30, 3, '08:00:00', '17:00:00', 30, 1, 1),
(99, 31, 3, '08:00:00', '17:00:00', 30, 1, 1),
(100, 32, 3, '08:00:00', '17:00:00', 30, 1, 1),
(101, 33, 3, '08:00:00', '17:00:00', 30, 1, 1),
(102, 34, 3, '08:00:00', '17:00:00', 30, 1, 1),
(103, 1, 4, '08:00:00', '17:00:00', 30, 1, 1),
(104, 2, 4, '08:00:00', '17:00:00', 30, 1, 1),
(105, 3, 4, '08:00:00', '17:00:00', 30, 1, 1),
(106, 4, 4, '08:00:00', '17:00:00', 30, 1, 1),
(107, 5, 4, '08:00:00', '17:00:00', 30, 1, 1),
(108, 6, 4, '08:00:00', '17:00:00', 30, 1, 1),
(109, 7, 4, '08:00:00', '17:00:00', 30, 1, 1),
(110, 8, 4, '08:00:00', '17:00:00', 30, 1, 1),
(111, 9, 4, '08:00:00', '17:00:00', 30, 1, 1),
(112, 10, 4, '08:00:00', '17:00:00', 30, 1, 1),
(113, 11, 4, '08:00:00', '17:00:00', 30, 1, 1),
(114, 12, 4, '08:00:00', '17:00:00', 30, 1, 1),
(115, 13, 4, '08:00:00', '17:00:00', 30, 1, 1),
(116, 14, 4, '08:00:00', '17:00:00', 30, 1, 1),
(117, 15, 4, '08:00:00', '17:00:00', 30, 1, 1),
(118, 16, 4, '08:00:00', '17:00:00', 30, 1, 1),
(119, 17, 4, '08:00:00', '17:00:00', 30, 1, 1),
(120, 18, 4, '08:00:00', '17:00:00', 30, 1, 1),
(121, 19, 4, '08:00:00', '17:00:00', 30, 1, 1),
(122, 20, 4, '08:00:00', '17:00:00', 30, 1, 1),
(123, 21, 4, '08:00:00', '17:00:00', 30, 1, 1),
(124, 22, 4, '08:00:00', '17:00:00', 30, 1, 1),
(125, 23, 4, '08:00:00', '17:00:00', 30, 1, 1),
(126, 24, 4, '08:00:00', '17:00:00', 30, 1, 1),
(127, 25, 4, '08:00:00', '17:00:00', 30, 1, 1),
(128, 26, 4, '08:00:00', '17:00:00', 30, 1, 1),
(129, 27, 4, '08:00:00', '17:00:00', 30, 1, 1),
(130, 28, 4, '08:00:00', '17:00:00', 30, 1, 1),
(131, 29, 4, '08:00:00', '17:00:00', 30, 1, 1),
(132, 30, 4, '08:00:00', '17:00:00', 30, 1, 1),
(133, 31, 4, '08:00:00', '17:00:00', 30, 1, 1),
(134, 32, 4, '08:00:00', '17:00:00', 30, 1, 1),
(135, 33, 4, '08:00:00', '17:00:00', 30, 1, 1),
(136, 34, 4, '08:00:00', '17:00:00', 30, 1, 1),
(137, 1, 5, '08:00:00', '17:00:00', 30, 1, 1),
(138, 2, 5, '08:00:00', '17:00:00', 30, 1, 1),
(139, 3, 5, '08:00:00', '17:00:00', 30, 1, 1),
(140, 4, 5, '08:00:00', '17:00:00', 30, 1, 1),
(141, 5, 5, '08:00:00', '17:00:00', 30, 1, 1),
(142, 6, 5, '08:00:00', '17:00:00', 30, 1, 1),
(143, 7, 5, '08:00:00', '17:00:00', 30, 1, 1),
(144, 8, 5, '08:00:00', '17:00:00', 30, 1, 1),
(145, 9, 5, '08:00:00', '17:00:00', 30, 1, 1),
(146, 10, 5, '08:00:00', '17:00:00', 30, 1, 1),
(147, 11, 5, '08:00:00', '17:00:00', 30, 1, 1),
(148, 12, 5, '08:00:00', '17:00:00', 30, 1, 1),
(149, 13, 5, '08:00:00', '17:00:00', 30, 1, 1),
(150, 14, 5, '08:00:00', '17:00:00', 30, 1, 1),
(151, 15, 5, '08:00:00', '17:00:00', 30, 1, 1),
(152, 16, 5, '08:00:00', '17:00:00', 30, 1, 1),
(153, 17, 5, '08:00:00', '17:00:00', 30, 1, 1),
(154, 18, 5, '08:00:00', '17:00:00', 30, 1, 1),
(155, 19, 5, '08:00:00', '17:00:00', 30, 1, 1),
(156, 20, 5, '08:00:00', '17:00:00', 30, 1, 1),
(157, 21, 5, '08:00:00', '17:00:00', 30, 1, 1),
(158, 22, 5, '08:00:00', '17:00:00', 30, 1, 1),
(159, 23, 5, '08:00:00', '17:00:00', 30, 1, 1),
(160, 24, 5, '08:00:00', '17:00:00', 30, 1, 1),
(161, 25, 5, '08:00:00', '17:00:00', 30, 1, 1),
(162, 26, 5, '08:00:00', '17:00:00', 30, 1, 1),
(163, 27, 5, '08:00:00', '17:00:00', 30, 1, 1),
(164, 28, 5, '08:00:00', '17:00:00', 30, 1, 1),
(165, 29, 5, '08:00:00', '17:00:00', 30, 1, 1),
(166, 30, 5, '08:00:00', '17:00:00', 30, 1, 1),
(167, 31, 5, '08:00:00', '17:00:00', 30, 1, 1),
(168, 32, 5, '08:00:00', '17:00:00', 30, 1, 1),
(169, 33, 5, '08:00:00', '17:00:00', 30, 1, 1),
(170, 34, 5, '08:00:00', '17:00:00', 30, 1, 1),
(171, 1, 6, '08:00:00', '12:00:00', 30, 1, 1),
(172, 2, 6, '08:00:00', '12:00:00', 30, 1, 1),
(173, 3, 6, '08:00:00', '12:00:00', 30, 1, 1),
(174, 4, 6, '08:00:00', '12:00:00', 30, 1, 1),
(175, 5, 6, '08:00:00', '12:00:00', 30, 1, 1),
(176, 6, 6, '08:00:00', '12:00:00', 30, 1, 1),
(177, 7, 6, '08:00:00', '12:00:00', 30, 1, 1),
(178, 8, 6, '08:00:00', '12:00:00', 30, 1, 1),
(179, 9, 6, '08:00:00', '12:00:00', 30, 1, 1),
(180, 10, 6, '08:00:00', '12:00:00', 30, 1, 1),
(181, 11, 6, '08:00:00', '12:00:00', 30, 1, 1),
(182, 12, 6, '08:00:00', '12:00:00', 30, 1, 1),
(183, 13, 6, '08:00:00', '12:00:00', 30, 1, 1),
(184, 14, 6, '08:00:00', '12:00:00', 30, 1, 1),
(185, 15, 6, '08:00:00', '12:00:00', 30, 1, 1),
(186, 16, 6, '08:00:00', '12:00:00', 30, 1, 1),
(187, 17, 6, '08:00:00', '12:00:00', 30, 1, 1),
(188, 18, 6, '08:00:00', '12:00:00', 30, 1, 1),
(189, 19, 6, '08:00:00', '12:00:00', 30, 1, 1),
(190, 20, 6, '08:00:00', '12:00:00', 30, 1, 1),
(191, 21, 6, '08:00:00', '12:00:00', 30, 1, 1),
(192, 22, 6, '08:00:00', '12:00:00', 30, 1, 1),
(193, 23, 6, '08:00:00', '12:00:00', 30, 1, 1),
(194, 24, 6, '08:00:00', '12:00:00', 30, 1, 1),
(195, 25, 6, '08:00:00', '12:00:00', 30, 1, 1),
(196, 26, 6, '08:00:00', '12:00:00', 30, 1, 1),
(197, 27, 6, '08:00:00', '12:00:00', 30, 1, 1),
(198, 28, 6, '08:00:00', '12:00:00', 30, 1, 1),
(199, 29, 6, '08:00:00', '12:00:00', 30, 1, 1),
(200, 30, 6, '08:00:00', '12:00:00', 30, 1, 1),
(201, 31, 6, '08:00:00', '12:00:00', 30, 1, 1),
(202, 32, 6, '08:00:00', '12:00:00', 30, 1, 1),
(203, 33, 6, '08:00:00', '12:00:00', 30, 1, 1),
(204, 34, 6, '08:00:00', '12:00:00', 30, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `forum_attachments`
--

CREATE TABLE `forum_attachments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int(11) DEFAULT NULL COMMENT 'Size in bytes',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forum_comments`
--

CREATE TABLE `forum_comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('patient','doctor','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'patient',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` int(11) DEFAULT NULL COMMENT 'For nested replies',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `forum_comments`
--

INSERT INTO `forum_comments` (`id`, `post_id`, `user_id`, `user_type`, `content`, `parent_id`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 'admin', '123', NULL, '2026-01-27 02:43:19', '2026-01-27 02:43:19');

-- --------------------------------------------------------

--
-- Table structure for table `forum_likes`
--

CREATE TABLE `forum_likes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('patient','doctor','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'patient',
  `target_id` int(11) NOT NULL,
  `target_type` enum('post','comment') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `forum_likes`
--

INSERT INTO `forum_likes` (`id`, `user_id`, `user_type`, `target_id`, `target_type`, `created_at`) VALUES
(1, 1, 'patient', 1, 'post', '2026-01-27 03:17:16'),
(2, 1, 'patient', 2, 'post', '2026-01-27 03:17:18');

-- --------------------------------------------------------

--
-- Table structure for table `forum_posts`
--

CREATE TABLE `forum_posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('patient','doctor','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'patient',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tags` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` enum('general','question','discussion','announcement') COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `status` enum('open','closed','solved') COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `privacy` enum('public','private') COLLATE utf8mb4_unicode_ci DEFAULT 'public',
  `views` int(11) DEFAULT '0',
  `is_pinned` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `forum_posts`
--

INSERT INTO `forum_posts` (`id`, `user_id`, `user_type`, `title`, `content`, `tags`, `category`, `status`, `privacy`, `views`, `is_pinned`, `created_at`, `updated_at`) VALUES
(1, 4, 'patient', 'Câu hỏi về lịch hẹn khám tim mạch', 'Xin chào, tôi muốn hỏi về quy trình đặt lịch khám tim mạch. Tôi cần chuẩn bị gì trước khi đến khám?', '#tim-mạch,#câu-hỏi', 'question', 'open', 'public', 4, 0, '2026-01-27 01:28:05', '2026-01-27 03:50:24'),
(2, 1, 'patient', 'Chia sẻ kinh nghiệm khám tại Global Hospital', 'Tôi vừa khám xong tại bệnh viện, cảm thấy rất hài lòng với dịch vụ. Bác sĩ tận tâm và chuyên nghiệp.', '#chia-sẻ,#kinh-nghiệm', 'discussion', 'open', 'public', 4, 0, '2026-01-27 01:28:05', '2026-01-27 02:58:07');

-- --------------------------------------------------------

--
-- Table structure for table `medical_attachments`
--

CREATE TABLE `medical_attachments` (
  `id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'image, pdf, doc, xray, etc',
  `file_size` int(11) DEFAULT NULL COMMENT 'Kích thước file (bytes)',
  `description` text COLLATE utf8mb4_unicode_ci,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `record_date` date NOT NULL,
  `record_type` enum('consultation','checkup','emergency','followup','surgery') COLLATE utf8mb4_unicode_ci DEFAULT 'consultation',
  `height` decimal(5,2) DEFAULT NULL COMMENT 'Chiều cao (cm)',
  `weight` decimal(5,2) DEFAULT NULL COMMENT 'Cân nặng (kg)',
  `bmi` decimal(4,2) DEFAULT NULL COMMENT 'BMI tự động tính',
  `blood_pressure` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Huyết áp (VD: 120/80)',
  `heart_rate` int(3) DEFAULT NULL COMMENT 'Nhịp tim (bpm)',
  `temperature` decimal(4,2) DEFAULT NULL COMMENT 'Nhiệt độ (°C)',
  `respiratory_rate` int(3) DEFAULT NULL COMMENT 'Nhịp thở (lần/phút)',
  `chief_complaint` text COLLATE utf8mb4_unicode_ci COMMENT 'Lý do khám',
  `symptoms` text COLLATE utf8mb4_unicode_ci COMMENT 'Triệu chứng',
  `diagnosis` text COLLATE utf8mb4_unicode_ci COMMENT 'Chẩn đoán',
  `medical_history` text COLLATE utf8mb4_unicode_ci COMMENT 'Tiền sử bệnh',
  `family_history` text COLLATE utf8mb4_unicode_ci COMMENT 'Tiền sử gia đình',
  `allergies` text COLLATE utf8mb4_unicode_ci COMMENT 'Dị ứng',
  `lab_results` text COLLATE utf8mb4_unicode_ci COMMENT 'Kết quả xét nghiệm',
  `imaging_results` text COLLATE utf8mb4_unicode_ci COMMENT 'Kết quả chẩn đoán hình ảnh',
  `treatment_plan` text COLLATE utf8mb4_unicode_ci COMMENT 'Kế hoạch điều trị',
  `prescription` text COLLATE utf8mb4_unicode_ci COMMENT 'Đơn thuốc',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Ghi chú thêm',
  `follow_up_date` date DEFAULT NULL COMMENT 'Ngày tái khám',
  `status` enum('active','completed','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL COMMENT 'ID bác sĩ tạo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`id`, `patient_id`, `doctor_id`, `appointment_id`, `record_date`, `record_type`, `height`, `weight`, `bmi`, `blood_pressure`, `heart_rate`, `temperature`, `respiratory_rate`, `chief_complaint`, `symptoms`, `diagnosis`, `medical_history`, `family_history`, `allergies`, `lab_results`, `imaging_results`, `treatment_plan`, `prescription`, `notes`, `follow_up_date`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 12, 1, NULL, '2026-01-10', 'checkup', 175.00, 70.00, NULL, '120/80', 75, 36.50, NULL, 'Khám sức khỏe định kỳ', 'Không có triệu chứng bất thường', 'Sức khỏe tốt, các chỉ số bình thường', NULL, NULL, NULL, NULL, NULL, 'Duy trì chế độ ăn uống và tập thể dục', 'Vitamin C 500mg, 1 viên/ngày', NULL, NULL, 'completed', 1, '2026-01-15 14:37:31', '2026-01-15 14:37:31');

-- --------------------------------------------------------

--
-- Table structure for table `patreg`
--

CREATE TABLE `patreg` (
  `pid` int(11) NOT NULL,
  `fname` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lname` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpassword` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `blood_group` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'A+, A-, B+, B-, O+, O-, AB+, AB-',
  `emergency_contact` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patreg`
--

INSERT INTO `patreg` (`pid`, `fname`, `lname`, `gender`, `email`, `contact`, `address`, `password`, `cpassword`, `avatar`, `date_of_birth`, `blood_group`, `emergency_contact`, `emergency_contact_name`, `updated_at`) VALUES
(1, 'Ram', 'Kumar', 'Male', 'ram@gmail.com', '9876543210', NULL, '123', '123', NULL, NULL, NULL, NULL, NULL, '2026-01-15 14:37:31'),
(2, 'Alia', 'Bhatt', 'Female', 'alia@gmail.com', '8976897689', NULL, '123', '123', NULL, NULL, NULL, NULL, NULL, '2026-01-15 14:37:31'),
(3, 'Kishan', 'Lal', 'Male', 'kishansmart0@gmail.com', '8838489464', NULL, '123', '123', NULL, NULL, NULL, NULL, NULL, '2026-01-15 14:37:31'),
(4, 'Duy', 'Chu Quang', 'Male', 'duywinter@gmail.com', '0846181174', NULL, '123', '123', 'uploads/avatars/avatar_4_1768488136.jpg', NULL, NULL, NULL, NULL, '2026-01-15 14:42:16'),
(5, 'Nguyễn Văn', 'Hùng', 'Nam', 'hung.nguyen@email.com', '0912345678', NULL, '123', '123', NULL, NULL, NULL, NULL, NULL, '2026-01-15 14:37:31'),
(6, 'Trần Thị', 'Lan', 'Nữ', 'lan.tran@email.com', '0923456789', NULL, '123', '123', NULL, NULL, NULL, NULL, NULL, '2026-01-15 14:37:31'),
(7, 'Lê Văn', 'Minh', 'Nam', 'minh.le@email.com', '0934567890', NULL, '123', '123', NULL, NULL, NULL, NULL, NULL, '2026-01-15 14:37:31'),
(8, 'Phạm Thị', 'Hoa', 'Nữ', 'hoa.pham@email.com', '0945678901', NULL, '123', '123', NULL, NULL, NULL, NULL, NULL, '2026-01-15 14:37:31'),
(9, 'Hoàng Văn', 'Đức', 'Nam', 'duc.hoang@email.com', '0956789012', NULL, '123', '123', NULL, NULL, NULL, NULL, NULL, '2026-01-15 14:37:31');

-- --------------------------------------------------------

--
-- Table structure for table `prescription_medications`
--

CREATE TABLE `prescription_medications` (
  `id` int(11) NOT NULL,
  `prescription_id` int(11) NOT NULL,
  `medication_name` varchar(255) NOT NULL,
  `dosage` varchar(100) NOT NULL,
  `frequency` varchar(100) NOT NULL,
  `duration` varchar(100) NOT NULL,
  `special_notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `prestb`
--

CREATE TABLE `prestb` (
  `pres_id` int(11) NOT NULL,
  `doctor` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pid` int(11) NOT NULL,
  `ID` int(11) NOT NULL,
  `fname` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lname` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `appdate` date NOT NULL,
  `apptime` time NOT NULL,
  `disease` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `allergy` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prescription` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `treatment_duration` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `general_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prestb`
--

INSERT INTO `prestb` (`pres_id`, `doctor`, `pid`, `ID`, `fname`, `lname`, `appdate`, `apptime`, `disease`, `allergy`, `prescription`, `treatment_duration`, `general_notes`, `created_at`) VALUES
(1, 'Bùi Quốc Việt', 4, 6, 'Duy', 'Chu Quang', '2026-03-18', '13:30:00', 'Sốt', 'Không có', '123, 123, 234, 234', NULL, NULL, '2026-01-27 03:39:02');

-- --------------------------------------------------------

--
-- Table structure for table `service_ratings`
--

CREATE TABLE `service_ratings` (
  `id` int(11) NOT NULL,
  `spec_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL,
  `review` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `specializations`
--

CREATE TABLE `specializations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_vi` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'fas fa-stethoscope',
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `average_rating` float DEFAULT '0',
  `total_ratings` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `specializations`
--

INSERT INTO `specializations` (`id`, `name`, `name_vi`, `icon`, `description`, `status`, `created_at`, `average_rating`, `total_ratings`) VALUES
(1, 'Pediatrics', 'Nhi khoa', 'fas fa-baby', 'Khám và điều trị bệnh cho trẻ em từ sơ sinh đến 18 tuổi', 1, '2026-01-14 18:04:43', 0, 0),
(2, 'Obstetrics_Gynecology', 'Sản phụ khoa', 'fas fa-stethoscope', 'Chăm sóc sức khỏe phụ nữ, thai sản và sinh đẻ', 1, '2026-01-14 18:04:43', 0, 0),
(3, 'Dermatology', 'Da liễu', 'fas fa-allergies', 'Khám và điều trị các bệnh về da, tóc, móng', 1, '2026-01-14 18:04:43', 0, 0),
(4, 'Gastroenterology', 'Tiêu hóa', 'fas fa-utensils', 'Khám và điều trị các bệnh về dạ dày, ruột, gan, mật', 1, '2026-01-14 18:04:43', 0, 0),
(5, 'Rheumatology', 'Cơ xương khớp', 'fas fa-bone', 'Khám và điều trị các bệnh về xương, khớp, cơ', 1, '2026-01-14 18:04:43', 0, 0),
(6, 'Allergy_Immunology', 'Dị ứng - Miễn dịch', 'fas fa-shield-virus', 'Khám và điều trị các bệnh dị ứng và hệ miễn dịch', 1, '2026-01-14 18:04:43', 0, 0),
(7, 'Anesthesiology', 'Gây mê hồi sức', 'fas fa-syringe', 'Chuyên khoa gây mê và hồi sức trong phẫu thuật', 1, '2026-01-14 18:04:43', 0, 0),
(8, 'ENT', 'Tai - Mũi - Họng', 'fas fa-stethoscope', 'Khám và điều trị các bệnh tai, mũi, họng', 1, '2026-01-14 18:04:43', 0, 0),
(9, 'Oncology', 'Ung bướu', 'fas fa-ribbon', 'Chẩn đoán và điều trị các bệnh ung thư', 1, '2026-01-14 18:04:43', 0, 0),
(10, 'Cardiology', 'Tim mạch', 'fas fa-heartbeat', 'Khám và điều trị các bệnh về tim và mạch máu', 1, '2026-01-14 18:04:43', 0, 0),
(11, 'Geriatrics', 'Lão khoa', 'fas fa-crutch', 'Chăm sóc sức khỏe người cao tuổi', 1, '2026-01-14 18:04:43', 0, 0),
(12, 'Orthopedics', 'Chấn thương chỉnh hình', 'fas fa-bone', 'Phẫu thuật và điều trị chấn thương xương khớp', 1, '2026-01-14 18:04:43', 0, 0),
(13, 'Emergency_Medicine', 'Hồi sức cấp cứu', 'fas fa-ambulance', 'Cấp cứu và hồi sức tích cực', 1, '2026-01-14 18:04:43', 0, 0),
(14, 'General_Surgery', 'Ngoại tổng quát', 'fas fa-cut', 'Phẫu thuật tổng quát các cơ quan', 1, '2026-01-14 18:04:43', 0, 0),
(15, 'Preventive_Medicine', 'Y học dự phòng', 'fas fa-shield-alt', 'Phòng ngừa bệnh tật và nâng cao sức khỏe', 1, '2026-01-14 18:04:43', 0, 0),
(16, 'Dentistry', 'Răng - Hàm - Mặt', 'fas fa-tooth', 'Khám và điều trị các bệnh về răng, hàm, mặt', 1, '2026-01-14 18:04:43', 0, 0),
(17, 'Infectious_Disease', 'Truyền nhiễm', 'fas fa-virus', 'Khám và điều trị các bệnh truyền nhiễm', 1, '2026-01-14 18:04:43', 0, 0),
(18, 'Nephrology', 'Nội thận', 'fas fa-water', 'Khám và điều trị các bệnh về thận', 1, '2026-01-14 18:04:43', 0, 0),
(19, 'Endocrinology', 'Nội tiết', 'fas fa-stethoscope', 'Khám và điều trị các bệnh về nội tiết, tiểu đường', 1, '2026-01-14 18:04:43', 0, 0),
(20, 'Psychiatry', 'Tâm thần', 'fas fa-brain', 'Khám và điều trị các bệnh tâm thần', 1, '2026-01-14 18:04:43', 0, 0),
(21, 'Pulmonology', 'Hô hấp', 'fas fa-wind', 'Khám và điều trị các bệnh về phổi và đường hô hấp', 1, '2026-01-14 18:04:43', 0, 0),
(22, 'Laboratory', 'Xét nghiệm', 'fas fa-vials', 'Xét nghiệm máu, nước tiểu và các chỉ số', 1, '2026-01-14 18:04:43', 0, 0),
(23, 'Hematology', 'Huyết học', 'fas fa-tint', 'Khám và điều trị các bệnh về máu', 1, '2026-01-14 18:04:43', 0, 0),
(24, 'Psychology', 'Tâm lý', 'fas fa-comments', 'Tư vấn và trị liệu tâm lý', 1, '2026-01-14 18:04:43', 0, 0),
(25, 'Neurology', 'Nội thần kinh', 'fas fa-brain', 'Khám và điều trị các bệnh về thần kinh', 1, '2026-01-14 18:04:43', 0, 0),
(26, 'Speech_Therapy', 'Ngôn ngữ trị liệu', 'fas fa-comment-medical', 'Điều trị các rối loạn ngôn ngữ và giao tiếp', 1, '2026-01-14 18:04:43', 0, 0),
(27, 'Rehabilitation', 'Phục hồi chức năng - VLTL', 'fas fa-walking', 'Phục hồi chức năng và vật lý trị liệu', 1, '2026-01-14 18:04:43', 0, 0),
(28, 'Fertility', 'Vô sinh hiếm muộn', 'fas fa-baby-carriage', 'Điều trị vô sinh và hỗ trợ sinh sản', 1, '2026-01-14 18:04:43', 0, 0),
(29, 'Traditional_Medicine', 'Y học cổ truyền', 'fas fa-leaf', 'Khám và điều trị bằng y học cổ truyền', 1, '2026-01-14 18:04:43', 0, 0),
(30, 'Tuberculosis', 'Lao - Bệnh phổi', 'fas fa-lungs-virus', 'Khám và điều trị bệnh lao và các bệnh phổi', 1, '2026-01-14 18:04:43', 0, 0),
(31, 'Sports_Medicine', 'Y học thể thao', 'fas fa-running', 'Chăm sóc sức khỏe cho vận động viên', 1, '2026-01-14 18:04:43', 0, 0),
(32, 'Ophthalmology', 'Nhãn khoa', 'fas fa-eye', 'Khám và điều trị các bệnh về mắt', 1, '2026-01-14 18:04:43', 0, 0),
(33, 'Andrology', 'Nam khoa', 'fas fa-male', 'Khám và điều trị các bệnh nam giới', 1, '2026-01-14 18:04:43', 0, 0),
(34, 'Urology', 'Ngoại tiết niệu', 'fas fa-procedures', 'Phẫu thuật và điều trị bệnh tiết niệu', 1, '2026-01-14 18:04:43', 0, 0),
(35, 'Radiology', 'Chẩn đoán hình ảnh', 'fas fa-x-ray', 'Chụp X-quang, CT, MRI và siêu âm', 1, '2026-01-14 18:04:43', 0, 0),
(36, 'Neurosurgery', 'Ngoại thần kinh', 'fas fa-brain', 'Phẫu thuật thần kinh và não', 1, '2026-01-14 18:04:43', 0, 0),
(37, 'Internal_Medicine', 'Nội tổng quát', 'fas fa-stethoscope', 'Khám và điều trị bệnh nội khoa tổng quát', 1, '2026-01-14 18:04:43', 0, 0),
(38, 'Urology_Internal', 'Ngoại niệu', 'fas fa-procedures', 'Khám và điều trị bệnh đường tiết niệu', 1, '2026-01-14 18:04:43', 0, 0),
(39, 'Nutrition', 'Dinh dưỡng', 'fas fa-apple-alt', 'Tư vấn dinh dưỡng và chế độ ăn', 1, '2026-01-14 18:04:43', 0, 0),
(40, 'Thoracic_Surgery', 'Ngoại lồng ngực - Mạch máu', 'fas fa-heart', 'Phẫu thuật lồng ngực và mạch máu', 1, '2026-01-14 18:04:43', 0, 0),
(41, 'Plastic_Surgery', 'Phẫu thuật tạo hình (Thẩm mỹ)', 'fas fa-magic', 'Phẫu thuật thẩm mỹ và tạo hình', 1, '2026-01-14 18:04:43', 0, 0),
(42, 'Pain_Management', 'Điều trị đau', 'fas fa-band-aid', 'Điều trị và quản lý đau mãn tính', 1, '2026-01-14 18:04:43', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `slot_date` date NOT NULL,
  `slot_time` time NOT NULL,
  `status` enum('available','booked','blocked') COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  `appointment_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `time_slots`
--

INSERT INTO `time_slots` (`id`, `doctor_id`, `slot_date`, `slot_time`, `status`, `appointment_id`, `created_at`) VALUES
(1, 17, '2026-02-26', '08:00:00', 'available', NULL, '2026-01-15 14:11:32'),
(2, 17, '2026-02-26', '08:30:00', 'available', NULL, '2026-01-15 14:11:32'),
(3, 17, '2026-02-26', '09:00:00', 'available', NULL, '2026-01-15 14:11:32'),
(4, 17, '2026-02-26', '09:30:00', 'available', NULL, '2026-01-15 14:11:32'),
(5, 17, '2026-02-26', '10:00:00', 'available', NULL, '2026-01-15 14:11:32'),
(6, 17, '2026-02-26', '10:30:00', 'available', NULL, '2026-01-15 14:11:32'),
(7, 17, '2026-02-26', '11:00:00', 'available', NULL, '2026-01-15 14:11:32'),
(8, 17, '2026-02-26', '11:30:00', 'available', NULL, '2026-01-15 14:11:32'),
(9, 17, '2026-02-26', '12:00:00', 'available', NULL, '2026-01-15 14:11:32'),
(10, 17, '2026-02-26', '12:30:00', 'available', NULL, '2026-01-15 14:11:32'),
(11, 17, '2026-02-26', '13:00:00', 'available', NULL, '2026-01-15 14:11:32'),
(12, 17, '2026-02-26', '13:30:00', 'available', NULL, '2026-01-15 14:11:32'),
(13, 17, '2026-02-26', '14:00:00', 'available', NULL, '2026-01-15 14:11:32'),
(14, 17, '2026-02-26', '14:30:00', 'available', NULL, '2026-01-15 14:11:32'),
(15, 17, '2026-02-26', '15:00:00', 'available', NULL, '2026-01-15 14:11:32'),
(16, 17, '2026-02-26', '15:30:00', 'available', NULL, '2026-01-15 14:11:32'),
(17, 17, '2026-02-26', '16:00:00', 'available', NULL, '2026-01-15 14:11:32'),
(18, 17, '2026-02-26', '16:30:00', 'available', NULL, '2026-01-15 14:11:32'),
(19, 9, '2026-11-18', '08:00:00', 'available', NULL, '2026-01-15 14:12:06'),
(20, 9, '2026-11-18', '08:30:00', 'available', NULL, '2026-01-15 14:12:06'),
(21, 9, '2026-11-18', '09:00:00', 'available', NULL, '2026-01-15 14:12:06'),
(22, 9, '2026-11-18', '09:30:00', 'available', NULL, '2026-01-15 14:12:06'),
(23, 9, '2026-11-18', '10:00:00', 'available', NULL, '2026-01-15 14:12:06'),
(24, 9, '2026-11-18', '10:30:00', 'available', NULL, '2026-01-15 14:12:06'),
(25, 9, '2026-11-18', '11:00:00', 'available', NULL, '2026-01-15 14:12:06'),
(26, 9, '2026-11-18', '11:30:00', 'available', NULL, '2026-01-15 14:12:06'),
(27, 9, '2026-11-18', '12:00:00', 'available', NULL, '2026-01-15 14:12:06'),
(28, 9, '2026-11-18', '12:30:00', 'available', NULL, '2026-01-15 14:12:06'),
(29, 9, '2026-11-18', '13:00:00', 'available', NULL, '2026-01-15 14:12:06'),
(30, 9, '2026-11-18', '13:30:00', 'available', NULL, '2026-01-15 14:12:06'),
(31, 9, '2026-11-18', '14:00:00', 'available', NULL, '2026-01-15 14:12:06'),
(32, 9, '2026-11-18', '14:30:00', 'available', NULL, '2026-01-15 14:12:06'),
(33, 9, '2026-11-18', '15:00:00', 'available', NULL, '2026-01-15 14:12:06'),
(34, 9, '2026-11-18', '15:30:00', 'available', NULL, '2026-01-15 14:12:06'),
(35, 9, '2026-11-18', '16:00:00', 'available', NULL, '2026-01-15 14:12:06'),
(36, 9, '2026-11-18', '16:30:00', 'available', NULL, '2026-01-15 14:12:06'),
(37, 17, '2026-03-18', '08:00:00', 'available', NULL, '2026-01-15 14:19:46'),
(38, 17, '2026-03-18', '08:30:00', 'available', NULL, '2026-01-15 14:19:46'),
(39, 17, '2026-03-18', '09:00:00', 'available', NULL, '2026-01-15 14:19:46'),
(40, 17, '2026-03-18', '09:30:00', 'available', NULL, '2026-01-15 14:19:46'),
(41, 17, '2026-03-18', '10:00:00', 'available', NULL, '2026-01-15 14:19:46'),
(42, 17, '2026-03-18', '10:30:00', 'available', NULL, '2026-01-15 14:19:46'),
(43, 17, '2026-03-18', '11:00:00', 'available', NULL, '2026-01-15 14:19:46'),
(44, 17, '2026-03-18', '11:30:00', 'available', NULL, '2026-01-15 14:19:46'),
(45, 17, '2026-03-18', '12:00:00', 'available', NULL, '2026-01-15 14:19:46'),
(46, 17, '2026-03-18', '12:30:00', 'available', NULL, '2026-01-15 14:19:46'),
(47, 17, '2026-03-18', '13:00:00', 'available', NULL, '2026-01-15 14:19:46'),
(48, 17, '2026-03-18', '13:30:00', 'booked', 6, '2026-01-15 14:19:46'),
(49, 17, '2026-03-18', '14:00:00', 'available', NULL, '2026-01-15 14:19:46'),
(50, 17, '2026-03-18', '14:30:00', 'available', NULL, '2026-01-15 14:19:46'),
(51, 17, '2026-03-18', '15:00:00', 'available', NULL, '2026-01-15 14:19:46'),
(52, 17, '2026-03-18', '15:30:00', 'available', NULL, '2026-01-15 14:19:46'),
(53, 17, '2026-03-18', '16:00:00', 'available', NULL, '2026-01-15 14:19:46'),
(54, 17, '2026-03-18', '16:30:00', 'available', NULL, '2026-01-15 14:19:46'),
(55, 18, '2026-12-28', '08:00:00', 'available', NULL, '2026-01-21 03:01:55'),
(56, 18, '2026-12-28', '08:30:00', 'available', NULL, '2026-01-21 03:01:55'),
(57, 18, '2026-12-28', '09:00:00', 'available', NULL, '2026-01-21 03:01:55'),
(58, 18, '2026-12-28', '09:30:00', 'available', NULL, '2026-01-21 03:01:55'),
(59, 18, '2026-12-28', '10:00:00', 'available', NULL, '2026-01-21 03:01:55'),
(60, 18, '2026-12-28', '10:30:00', 'available', NULL, '2026-01-21 03:01:55'),
(61, 18, '2026-12-28', '11:00:00', 'available', NULL, '2026-01-21 03:01:55'),
(62, 18, '2026-12-28', '11:30:00', 'available', NULL, '2026-01-21 03:01:55'),
(63, 18, '2026-12-28', '12:00:00', 'available', NULL, '2026-01-21 03:01:55'),
(64, 18, '2026-12-28', '12:30:00', 'available', NULL, '2026-01-21 03:01:55'),
(65, 18, '2026-12-28', '13:00:00', 'available', NULL, '2026-01-21 03:01:55'),
(66, 18, '2026-12-28', '13:30:00', 'available', NULL, '2026-01-21 03:01:55'),
(67, 18, '2026-12-28', '14:00:00', 'available', NULL, '2026-01-21 03:01:55'),
(68, 18, '2026-12-28', '14:30:00', 'available', NULL, '2026-01-21 03:01:55'),
(69, 18, '2026-12-28', '15:00:00', 'available', NULL, '2026-01-21 03:01:55'),
(70, 18, '2026-12-28', '15:30:00', 'available', NULL, '2026-01-21 03:01:55'),
(71, 18, '2026-12-28', '16:00:00', 'available', NULL, '2026-01-21 03:01:55'),
(72, 18, '2026-12-28', '16:30:00', 'available', NULL, '2026-01-21 03:01:55'),
(73, 18, '2026-06-30', '08:00:00', 'available', NULL, '2026-01-21 03:03:25'),
(74, 18, '2026-06-30', '08:30:00', 'available', NULL, '2026-01-21 03:03:25'),
(75, 18, '2026-06-30', '09:00:00', 'available', NULL, '2026-01-21 03:03:25'),
(76, 18, '2026-06-30', '09:30:00', 'available', NULL, '2026-01-21 03:03:25'),
(77, 18, '2026-06-30', '10:00:00', 'available', NULL, '2026-01-21 03:03:25'),
(78, 18, '2026-06-30', '10:30:00', 'available', NULL, '2026-01-21 03:03:25'),
(79, 18, '2026-06-30', '11:00:00', 'available', NULL, '2026-01-21 03:03:25'),
(80, 18, '2026-06-30', '11:30:00', 'available', NULL, '2026-01-21 03:03:25'),
(81, 18, '2026-06-30', '12:00:00', 'available', NULL, '2026-01-21 03:03:25'),
(82, 18, '2026-06-30', '12:30:00', 'available', NULL, '2026-01-21 03:03:25'),
(83, 18, '2026-06-30', '13:00:00', 'available', NULL, '2026-01-21 03:03:25'),
(84, 18, '2026-06-30', '13:30:00', 'available', NULL, '2026-01-21 03:03:25'),
(85, 18, '2026-06-30', '14:00:00', 'booked', 8, '2026-01-21 03:03:25'),
(86, 18, '2026-06-30', '14:30:00', 'available', NULL, '2026-01-21 03:03:25'),
(87, 18, '2026-06-30', '15:00:00', 'available', NULL, '2026-01-21 03:03:25'),
(88, 18, '2026-06-30', '15:30:00', 'available', NULL, '2026-01-21 03:03:25'),
(89, 18, '2026-06-30', '16:00:00', 'available', NULL, '2026-01-21 03:03:25'),
(90, 18, '2026-06-30', '16:30:00', 'available', NULL, '2026-01-21 03:03:25'),
(91, 17, '2026-04-15', '08:00:00', 'available', NULL, '2026-01-21 03:07:07'),
(92, 17, '2026-04-15', '08:30:00', 'booked', 9, '2026-01-21 03:07:07'),
(93, 17, '2026-04-15', '09:00:00', 'available', NULL, '2026-01-21 03:07:07'),
(94, 17, '2026-04-15', '09:30:00', 'available', NULL, '2026-01-21 03:07:07'),
(95, 17, '2026-04-15', '10:00:00', 'available', NULL, '2026-01-21 03:07:07'),
(96, 17, '2026-04-15', '10:30:00', 'available', NULL, '2026-01-21 03:07:07'),
(97, 17, '2026-04-15', '11:00:00', 'available', NULL, '2026-01-21 03:07:07'),
(98, 17, '2026-04-15', '11:30:00', 'available', NULL, '2026-01-21 03:07:07'),
(99, 17, '2026-04-15', '12:00:00', 'available', NULL, '2026-01-21 03:07:07'),
(100, 17, '2026-04-15', '12:30:00', 'available', NULL, '2026-01-21 03:07:07'),
(101, 17, '2026-04-15', '13:00:00', 'available', NULL, '2026-01-21 03:07:07'),
(102, 17, '2026-04-15', '13:30:00', 'available', NULL, '2026-01-21 03:07:07'),
(103, 17, '2026-04-15', '14:00:00', 'available', NULL, '2026-01-21 03:07:07'),
(104, 17, '2026-04-15', '14:30:00', 'available', NULL, '2026-01-21 03:07:07'),
(105, 17, '2026-04-15', '15:00:00', 'available', NULL, '2026-01-21 03:07:07'),
(106, 17, '2026-04-15', '15:30:00', 'available', NULL, '2026-01-21 03:07:07'),
(107, 17, '2026-04-15', '16:00:00', 'available', NULL, '2026-01-21 03:07:07'),
(108, 17, '2026-04-15', '16:30:00', 'available', NULL, '2026-01-21 03:07:07');

-- --------------------------------------------------------

--
-- Table structure for table `vaccination_records`
--

CREATE TABLE `vaccination_records` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `vaccine_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vaccine_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dose_number` int(2) DEFAULT '1',
  `vaccination_date` date NOT NULL,
  `next_dose_date` date DEFAULT NULL,
  `administered_by` int(11) DEFAULT NULL COMMENT 'ID bác sĩ',
  `location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `batch_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_doctors`
-- (See below for the actual view)
--
CREATE TABLE `v_doctors` (
`id` int(11)
,`username` varchar(255)
,`fullname` varchar(255)
,`email` varchar(255)
,`spec` varchar(255)
,`spec_id` int(11)
,`spec_name_vi` varchar(100)
,`spec_icon` varchar(100)
,`docFees` int(10)
,`phone` varchar(15)
,`bio` mediumtext
,`experience_years` int(3)
,`status` tinyint(1)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_medical_records_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_medical_records_summary` (
`id` int(11)
,`patient_id` int(11)
,`patient_name` varchar(41)
,`patient_contact` varchar(10)
,`blood_group` varchar(5)
,`doctor_id` int(11)
,`doctor_name` varchar(255)
,`record_date` date
,`record_type` enum('consultation','checkup','emergency','followup','surgery')
,`diagnosis` text
,`status` enum('active','completed','archived')
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_patient_profiles`
-- (See below for the actual view)
--
CREATE TABLE `v_patient_profiles` (
`pid` int(11)
,`fname` varchar(20)
,`lname` varchar(20)
,`gender` varchar(10)
,`email` varchar(30)
,`contact` varchar(10)
,`address` varchar(255)
,`avatar` varchar(255)
,`date_of_birth` date
,`blood_group` varchar(5)
,`emergency_contact` varchar(10)
,`emergency_contact_name` varchar(50)
,`age` bigint(21)
,`total_appointments` bigint(21)
,`total_records` bigint(21)
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointmenttb`
--
ALTER TABLE `appointmenttb`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `doctb`
--
ALTER TABLE `doctb`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doctor_ratings`
--
ALTER TABLE `doctor_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rating` (`doctor_id`,`patient_id`,`appointment_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `forum_attachments`
--
ALTER TABLE `forum_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `forum_comments`
--
ALTER TABLE `forum_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `forum_likes`
--
ALTER TABLE `forum_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`user_id`,`user_type`,`target_id`,`target_type`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `target_id` (`target_id`);

--
-- Indexes for table `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_type` (`user_type`),
  ADD KEY `category` (`category`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `medical_attachments`
--
ALTER TABLE `medical_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `record_id` (`record_id`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `record_date` (`record_date`);

--
-- Indexes for table `patreg`
--
ALTER TABLE `patreg`
  ADD PRIMARY KEY (`pid`);

--
-- Indexes for table `prescription_medications`
--
ALTER TABLE `prescription_medications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prescription_id` (`prescription_id`);

--
-- Indexes for table `prestb`
--
ALTER TABLE `prestb`
  ADD PRIMARY KEY (`pres_id`);

--
-- Indexes for table `service_ratings`
--
ALTER TABLE `service_ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `spec_id` (`spec_id`);

--
-- Indexes for table `specializations`
--
ALTER TABLE `specializations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slot` (`doctor_id`,`slot_date`,`slot_time`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `slot_date` (`slot_date`);

--
-- Indexes for table `vaccination_records`
--
ALTER TABLE `vaccination_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointmenttb`
--
ALTER TABLE `appointmenttb`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `doctb`
--
ALTER TABLE `doctb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `doctor_ratings`
--
ALTER TABLE `doctor_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=205;

--
-- AUTO_INCREMENT for table `forum_attachments`
--
ALTER TABLE `forum_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `forum_comments`
--
ALTER TABLE `forum_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `forum_likes`
--
ALTER TABLE `forum_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `forum_posts`
--
ALTER TABLE `forum_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `medical_attachments`
--
ALTER TABLE `medical_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `patreg`
--
ALTER TABLE `patreg`
  MODIFY `pid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `prescription_medications`
--
ALTER TABLE `prescription_medications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prestb`
--
ALTER TABLE `prestb`
  MODIFY `pres_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `service_ratings`
--
ALTER TABLE `service_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `specializations`
--
ALTER TABLE `specializations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `vaccination_records`
--
ALTER TABLE `vaccination_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Structure for view `v_doctors`
--
DROP TABLE IF EXISTS `v_doctors`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_doctors`  AS SELECT `d`.`id` AS `id`, `d`.`username` AS `username`, `d`.`fullname` AS `fullname`, `d`.`email` AS `email`, `d`.`spec` AS `spec`, `d`.`spec_id` AS `spec_id`, `s`.`name_vi` AS `spec_name_vi`, `s`.`icon` AS `spec_icon`, `d`.`docFees` AS `docFees`, `d`.`phone` AS `phone`, `d`.`bio` AS `bio`, `d`.`experience_years` AS `experience_years`, `d`.`status` AS `status` FROM (`doctb` `d` left join `specializations` `s` on((`d`.`spec_id` = `s`.`id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `v_medical_records_summary`
--
DROP TABLE IF EXISTS `v_medical_records_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_medical_records_summary`  AS SELECT `mr`.`id` AS `id`, `mr`.`patient_id` AS `patient_id`, concat(`p`.`fname`,' ',`p`.`lname`) AS `patient_name`, `p`.`contact` AS `patient_contact`, `p`.`blood_group` AS `blood_group`, `mr`.`doctor_id` AS `doctor_id`, `d`.`fullname` AS `doctor_name`, `mr`.`record_date` AS `record_date`, `mr`.`record_type` AS `record_type`, `mr`.`diagnosis` AS `diagnosis`, `mr`.`status` AS `status`, `mr`.`created_at` AS `created_at` FROM ((`medical_records` `mr` left join `patreg` `p` on((`mr`.`patient_id` = `p`.`pid`))) left join `doctb` `d` on((`mr`.`doctor_id` = `d`.`id`))) ORDER BY `mr`.`record_date` DESC, `mr`.`created_at` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `v_patient_profiles`
--
DROP TABLE IF EXISTS `v_patient_profiles`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_patient_profiles`  AS SELECT `p`.`pid` AS `pid`, `p`.`fname` AS `fname`, `p`.`lname` AS `lname`, `p`.`gender` AS `gender`, `p`.`email` AS `email`, `p`.`contact` AS `contact`, `p`.`address` AS `address`, `p`.`avatar` AS `avatar`, `p`.`date_of_birth` AS `date_of_birth`, `p`.`blood_group` AS `blood_group`, `p`.`emergency_contact` AS `emergency_contact`, `p`.`emergency_contact_name` AS `emergency_contact_name`, timestampdiff(YEAR,`p`.`date_of_birth`,curdate()) AS `age`, count(distinct `a`.`ID`) AS `total_appointments`, count(distinct `mr`.`id`) AS `total_records` FROM ((`patreg` `p` left join `appointmenttb` `a` on((`p`.`pid` = `a`.`pid`))) left join `medical_records` `mr` on((`p`.`pid` = `mr`.`patient_id`))) GROUP BY `p`.`pid` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `doctor_ratings`
--
ALTER TABLE `doctor_ratings`
  ADD CONSTRAINT `doctor_ratings_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctb` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_ratings_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patreg` (`pid`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_ratings_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointmenttb` (`ID`) ON DELETE SET NULL;

--
-- Constraints for table `forum_attachments`
--
ALTER TABLE `forum_attachments`
  ADD CONSTRAINT `forum_attachments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `forum_posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `forum_comments`
--
ALTER TABLE `forum_comments`
  ADD CONSTRAINT `forum_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `forum_posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_comments_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `forum_comments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prescription_medications`
--
ALTER TABLE `prescription_medications`
  ADD CONSTRAINT `fk_prestb_medications` FOREIGN KEY (`prescription_id`) REFERENCES `prestb` (`pres_id`) ON DELETE CASCADE;

--
-- Constraints for table `service_ratings`
--
ALTER TABLE `service_ratings`
  ADD CONSTRAINT `service_ratings_ibfk_1` FOREIGN KEY (`spec_id`) REFERENCES `specializations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

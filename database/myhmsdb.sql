-- phpMyAdmin SQL Dump
-- Fixed by Gemini: Vietnamese Language Support + Plain Text Passwords
-- Generation Time: Jan 14, 2026
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `myhmsdb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `myhmsdb`;

-- ============================================
-- 1. BẢNG ADMIN
-- ============================================
DROP TABLE IF EXISTS `admintb`;
CREATE TABLE `admintb` (
  `username` varchar(50) NOT NULL,
  `password` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admintb` (`username`, `password`) VALUES
('admin', '123');

-- ============================================
-- 2. BẢNG LIÊN HỆ (CONTACT)
-- ============================================
DROP TABLE IF EXISTS `contact`;
CREATE TABLE `contact` (
  `name` varchar(30) NOT NULL,
  `email` text NOT NULL,
  `contact` varchar(10) NOT NULL,
  `message` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `contact` (`name`, `email`, `contact`, `message`) VALUES
('Anu', 'anu@gmail.com', '7896677554', 'Hey Admin'),
('Viki', 'viki@gmail.com', '9899778865', 'Good Job, Pal'),
('Duy Chu Quang', 'duywinter@gmail.com', '0846181174', 'Hệ thống hoạt động tốt');

-- ============================================
-- 3. BẢNG CHUYÊN KHOA (SPECIALIZATIONS)
-- ============================================
DROP TABLE IF EXISTS `specializations`;
CREATE TABLE `specializations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `name_vi` varchar(100) NOT NULL,
  `icon` varchar(100) DEFAULT 'fas fa-stethoscope',
  `description` text,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `specializations` (`id`, `name`, `name_vi`, `icon`, `description`) VALUES
(1, 'Pediatrics', 'Nhi khoa', 'fas fa-baby', 'Khám và điều trị bệnh cho trẻ em từ sơ sinh đến 18 tuổi'),
(2, 'Obstetrics_Gynecology', 'Sản phụ khoa', 'fas fa-female', 'Chăm sóc sức khỏe phụ nữ, thai sản và sinh đẻ'),
(3, 'Dermatology', 'Da liễu', 'fas fa-allergies', 'Khám và điều trị các bệnh về da, tóc, móng'),
(4, 'Gastroenterology', 'Tiêu hóa', 'fas fa-stomach', 'Khám và điều trị các bệnh về dạ dày, ruột, gan, mật'),
(5, 'Rheumatology', 'Cơ xương khớp', 'fas fa-bone', 'Khám và điều trị các bệnh về xương, khớp, cơ'),
(6, 'Allergy_Immunology', 'Dị ứng - Miễn dịch', 'fas fa-shield-virus', 'Khám và điều trị các bệnh dị ứng và hệ miễn dịch'),
(7, 'Anesthesiology', 'Gây mê hồi sức', 'fas fa-syringe', 'Chuyên khoa gây mê và hồi sức trong phẫu thuật'),
(8, 'ENT', 'Tai - Mũi - Họng', 'fas fa-head-side-cough', 'Khám và điều trị các bệnh tai, mũi, họng'),
(9, 'Oncology', 'Ung bướu', 'fas fa-ribbon', 'Chẩn đoán và điều trị các bệnh ung thư'),
(10, 'Cardiology', 'Tim mạch', 'fas fa-heartbeat', 'Khám và điều trị các bệnh về tim và mạch máu'),
(11, 'Geriatrics', 'Lão khoa', 'fas fa-user-clock', 'Chăm sóc sức khỏe người cao tuổi'),
(12, 'Orthopedics', 'Chấn thương chỉnh hình', 'fas fa-bone', 'Phẫu thuật và điều trị chấn thương xương khớp'),
(13, 'Emergency_Medicine', 'Hồi sức cấp cứu', 'fas fa-ambulance', 'Cấp cứu và hồi sức tích cực'),
(14, 'General_Surgery', 'Ngoại tổng quát', 'fas fa-cut', 'Phẫu thuật tổng quát các cơ quan'),
(15, 'Preventive_Medicine', 'Y học dự phòng', 'fas fa-shield-alt', 'Phòng ngừa bệnh tật và nâng cao sức khỏe'),
(16, 'Dentistry', 'Răng - Hàm - Mặt', 'fas fa-tooth', 'Khám và điều trị các bệnh về răng, hàm, mặt'),
(17, 'Infectious_Disease', 'Truyền nhiễm', 'fas fa-virus', 'Khám và điều trị các bệnh truyền nhiễm'),
(18, 'Nephrology', 'Nội thận', 'fas fa-kidneys', 'Khám và điều trị các bệnh về thận'),
(19, 'Endocrinology', 'Nội tiết', 'fas fa-disease', 'Khám và điều trị các bệnh về nội tiết, tiểu đường'),
(20, 'Psychiatry', 'Tâm thần', 'fas fa-brain', 'Khám và điều trị các bệnh tâm thần'),
(21, 'Pulmonology', 'Hô hấp', 'fas fa-lungs', 'Khám và điều trị các bệnh về phổi và đường hô hấp'),
(22, 'Laboratory', 'Xét nghiệm', 'fas fa-vials', 'Xét nghiệm máu, nước tiểu và các chỉ số'),
(23, 'Hematology', 'Huyết học', 'fas fa-tint', 'Khám và điều trị các bệnh về máu'),
(24, 'Psychology', 'Tâm lý', 'fas fa-comments', 'Tư vấn và trị liệu tâm lý'),
(25, 'Neurology', 'Nội thần kinh', 'fas fa-brain', 'Khám và điều trị các bệnh về thần kinh'),
(26, 'Speech_Therapy', 'Ngôn ngữ trị liệu', 'fas fa-comment-medical', 'Điều trị các rối loạn ngôn ngữ và giao tiếp'),
(27, 'Rehabilitation', 'Phục hồi chức năng - VLTL', 'fas fa-walking', 'Phục hồi chức năng và vật lý trị liệu'),
(28, 'Fertility', 'Vô sinh hiếm muộn', 'fas fa-baby-carriage', 'Điều trị vô sinh và hỗ trợ sinh sản'),
(29, 'Traditional_Medicine', 'Y học cổ truyền', 'fas fa-leaf', 'Khám và điều trị bằng y học cổ truyền'),
(30, 'Tuberculosis', 'Lao - Bệnh phổi', 'fas fa-lungs-virus', 'Khám và điều trị bệnh lao và các bệnh phổi'),
(31, 'Sports_Medicine', 'Y học thể thao', 'fas fa-running', 'Chăm sóc sức khỏe cho vận động viên'),
(32, 'Ophthalmology', 'Nhãn khoa', 'fas fa-eye', 'Khám và điều trị các bệnh về mắt'),
(33, 'Andrology', 'Nam khoa', 'fas fa-male', 'Khám và điều trị các bệnh nam giới'),
(34, 'Urology', 'Ngoại tiết niệu', 'fas fa-procedures', 'Phẫu thuật và điều trị bệnh tiết niệu'),
(35, 'Radiology', 'Chẩn đoán hình ảnh', 'fas fa-x-ray', 'Chụp X-quang, CT, MRI và siêu âm'),
(36, 'Neurosurgery', 'Ngoại thần kinh', 'fas fa-brain', 'Phẫu thuật thần kinh và não'),
(37, 'Internal_Medicine', 'Nội tổng quát', 'fas fa-stethoscope', 'Khám và điều trị bệnh nội khoa tổng quát'),
(38, 'Urology_Internal', 'Ngoại niệu', 'fas fa-procedures', 'Khám và điều trị bệnh đường tiết niệu'),
(39, 'Nutrition', 'Dinh dưỡng', 'fas fa-apple-alt', 'Tư vấn dinh dưỡng và chế độ ăn'),
(40, 'Thoracic_Surgery', 'Ngoại lồng ngực - Mạch máu', 'fas fa-heart', 'Phẫu thuật lồng ngực và mạch máu'),
(41, 'Plastic_Surgery', 'Phẫu thuật tạo hình (Thẩm mỹ)', 'fas fa-magic', 'Phẫu thuật thẩm mỹ và tạo hình'),
(42, 'Pain_Management', 'Điều trị đau', 'fas fa-band-aid', 'Điều trị và quản lý đau mãn tính');

-- ============================================
-- 4. BẢNG BÁC SĨ (DOCTB) - MẬT KHẨU: password
-- ============================================
DROP TABLE IF EXISTS `doctb`;
CREATE TABLE `doctb` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `spec` varchar(255) NOT NULL,
  `spec_id` int(11) DEFAULT NULL,
  `docFees` int(10) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `bio` mediumtext,
  `experience_years` int(3) DEFAULT '0',
  `status` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tất cả bác sĩ có mật khẩu là '123'
INSERT INTO `doctb` (`id`, `username`, `password`, `fullname`, `email`, `spec`, `spec_id`, `docFees`, `phone`, `bio`, `experience_years`, `status`) VALUES
(1, 'le.chau', '123', 'Lê Minh Châu', 'le.chau@hospital.vn', 'Pediatrics', 1, 250000, '0901234569', 'Bác sĩ nhi khoa tận tâm, yêu trẻ em', 10, 1),
(2, 'pham.dung', '123', 'Phạm Thị Dung', 'pham.dung@hospital.vn', 'Pediatrics', 1, 280000, '0901234570', 'Chuyên gia nhi khoa, đặc biệt về bệnh hô hấp trẻ em', 8, 1),
(3, 'vu.giang', '123', 'Vũ Thị Giang', 'vu.giang@hospital.vn', 'Obstetrics_Gynecology', 2, 300000, '0901234573', 'Chuyên gia sản phụ khoa, đỡ đẻ hơn 5000 ca', 16, 1),
(4, 'dang.hung', '123', 'Đặng Văn Hùng', 'dang.hung@hospital.vn', 'Obstetrics_Gynecology', 2, 350000, '0901234574', 'Bác sĩ sản khoa, chuyên thai kỳ nguy cơ cao', 13, 1),
(5, 'hoang.em', '123', 'Hoàng Văn Em', 'hoang.em@hospital.vn', 'Dermatology', 3, 320000, '0901234571', 'Chuyên gia da liễu, điều trị mụn và các bệnh da mãn tính', 14, 1),
(6, 'ngo.phuong', '123', 'Ngô Thị Phương', 'ngo.phuong@hospital.vn', 'Dermatology', 3, 350000, '0901234572', 'Bác sĩ da liễu thẩm mỹ, chuyên trị nám và tàn nhang', 11, 1),
(7, 'ly.minh', '123', 'Lý Văn Minh', 'ly.minh@hospital.vn', 'Gastroenterology', 4, 320000, '0901234577', 'Chuyên gia nội soi tiêu hóa, điều trị viêm loét dạ dày', 14, 1),
(8, 'mai.ngoc', '123', 'Mai Thị Ngọc', 'mai.ngoc@hospital.vn', 'Gastroenterology', 4, 280000, '0901234578', 'Bác sĩ tiêu hóa, chuyên bệnh gan mật', 9, 1),
(9, 'phan.son', '123', 'Phan Văn Sơn', 'phan.son@hospital.vn', 'Rheumatology', 5, 350000, '0901234581', 'Chuyên gia xương khớp, điều trị viêm khớp dạng thấp', 15, 1),
(10, 'cao.tam', '123', 'Cao Thị Tâm', 'cao.tam@hospital.vn', 'Rheumatology', 5, 300000, '0901234582', 'Bác sĩ cơ xương khớp, chuyên gout và loãng xương', 10, 1),
(11, 'dinh.phong', '123', 'Đinh Văn Phong', 'dinh.phong@hospital.vn', 'ENT', 8, 280000, '0901234579', 'Bác sĩ TMH, phẫu thuật nội soi xoang', 11, 1),
(12, 'to.quynh', '123', 'Tô Thị Quỳnh', 'to.quynh@hospital.vn', 'ENT', 8, 260000, '0901234580', 'Bác sĩ TMH, điều trị viêm họng và viêm amidan', 8, 1),
(13, 'le.duc', '123', 'Lê Văn Đức', 'le.duc@hospital.vn', 'Oncology', 9, 500000, '0901234589', 'Giáo sư ung bướu, chuyên gia hóa trị', 17, 1),
(14, 'pham.mai', '123', 'Phạm Thị Mai', 'pham.mai@hospital.vn', 'Oncology', 9, 450000, '0901234590', 'Bác sĩ ung bướu, xạ trị và điều trị đích', 14, 1),
(15, 'nguyen.an', '123', 'Nguyễn Văn An', 'nguyen.an@hospital.vn', 'Cardiology', 10, 300000, '0901234567', 'Chuyên gia tim mạch với 15 năm kinh nghiệm, từng tu nghiệp tại Pháp', 15, 1),
(16, 'tran.binh', '123', 'Trần Thị Bình', 'tran.binh@hospital.vn', 'Cardiology', 10, 350000, '0901234568', 'Bác sĩ chuyên khoa II Tim mạch, giảng viên Đại học Y', 12, 1),
(17, 'bui.viet', '123', 'Bùi Quốc Việt', 'bui.viet@hospital.vn', 'Orthopedics', 12, 400000, '0901234595', 'Phẫu thuật viên chỉnh hình, thay khớp háng và gối', 18, 1),
(18, 'truong.nam', '123', 'Trương Văn Nam', 'truong.nam@hospital.vn', 'Orthopedics', 12, 350000, '0901234596', 'Bác sĩ chấn thương, nội soi khớp vai và gối', 12, 1),
(19, 'lam.xuan', '123', 'Lâm Văn Xuân', 'lam.xuan@hospital.vn', 'Dentistry', 16, 250000, '0901234585', 'Bác sĩ RHM, chuyên nhổ răng khôn và implant', 12, 1),
(20, 'vo.yen', '123', 'Võ Thị Yến', 'vo.yen@hospital.vn', 'Dentistry', 16, 280000, '0901234586', 'Bác sĩ nha khoa thẩm mỹ, bọc răng sứ', 8, 1),
(21, 'vu.long', '123', 'Vũ Đình Long', 'vu.long@hospital.vn', 'Endocrinology', 19, 350000, '0901234593', 'Chuyên gia nội tiết, điều trị tiểu đường và tuyến giáp', 16, 1),
(22, 'dang.linh', '123', 'Đặng Thị Linh', 'dang.linh@hospital.vn', 'Endocrinology', 19, 320000, '0901234594', 'Bác sĩ nội tiết, rối loạn chuyển hóa', 11, 1),
(23, 'ly.hoang', '123', 'Lý Minh Hoàng', 'ly.hoang@hospital.vn', 'Psychiatry', 20, 400000, '0901234597', 'Bác sĩ tâm thần, điều trị trầm cảm và lo âu', 15, 1),
(24, 'mai.nga', '123', 'Mai Thanh Nga', 'mai.nga@hospital.vn', 'Psychiatry', 20, 350000, '0901234598', 'Bác sĩ tâm thần, rối loạn giấc ngủ', 10, 1),
(25, 'hoang.quan', '123', 'Hoàng Minh Quân', 'hoang.quan@hospital.vn', 'Pulmonology', 21, 300000, '0901234591', 'Bác sĩ hô hấp, điều trị hen suyễn và COPD', 13, 1),
(26, 'ngo.thao', '123', 'Ngô Thị Thảo', 'ngo.thao@hospital.vn', 'Pulmonology', 21, 280000, '0901234592', 'Bác sĩ phổi, nội soi phế quản', 10, 1),
(27, 'bui.kien', '123', 'Bùi Văn Kiên', 'bui.kien@hospital.vn', 'Neurology', 25, 400000, '0901234575', 'Giáo sư thần kinh học, chuyên gia đột quỵ', 18, 1),
(28, 'truong.lan', '123', 'Trương Thị Lan', 'truong.lan@hospital.vn', 'Neurology', 25, 350000, '0901234576', 'Bác sĩ thần kinh, điều trị đau đầu và động kinh', 12, 1),
(29, 'dinh.danh', '123', 'Đinh Công Danh', 'dinh.danh@hospital.vn', 'Traditional_Medicine', 29, 250000, '0901234599', 'Lương y, châm cứu và bấm huyệt', 22, 1),
(30, 'to.hanh', '123', 'Tô Thị Hạnh', 'to.hanh@hospital.vn', 'Traditional_Medicine', 29, 230000, '0901234600', 'Bác sĩ YHCT, thuốc nam và thuốc bắc', 18, 1),
(31, 'duong.uy', '123', 'Dương Văn Uy', 'duong.uy@hospital.vn', 'Ophthalmology', 32, 300000, '0901234583', 'Bác sĩ nhãn khoa, phẫu thuật đục thủy tinh thể', 13, 1),
(32, 'ho.van', '123', 'Hồ Thị Vân', 'ho.van@hospital.vn', 'Ophthalmology', 32, 280000, '0901234584', 'Bác sĩ mắt, điều trị cận thị và tật khúc xạ', 9, 1),
(33, 'nguyen.tung', '123', 'Nguyễn Thanh Tùng', 'nguyen.tung@hospital.vn', 'Internal_Medicine', 37, 200000, '0901234587', 'Bác sĩ nội khoa tổng quát, kinh nghiệm 20 năm', 20, 1),
(34, 'tran.huong', '123', 'Trần Thị Hương', 'tran.huong@hospital.vn', 'Internal_Medicine', 37, 220000, '0901234588', 'Bác sĩ đa khoa, khám sức khỏe tổng quát', 15, 1);

-- ============================================
-- 5. BẢNG BỆNH NHÂN (PATREG) - MẬT KHẨU TEXT
-- ============================================
DROP TABLE IF EXISTS `patreg`;
CREATE TABLE `patreg` (
  `pid` int(11) NOT NULL AUTO_INCREMENT,
  `fname` varchar(20) NOT NULL,
  `lname` varchar(20) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `email` varchar(30) NOT NULL,
  `contact` varchar(10) NOT NULL,
  `password` varchar(255) NOT NULL,
  `cpassword` varchar(255) NOT NULL,
  PRIMARY KEY (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `patreg` (`fname`, `lname`, `gender`, `email`, `contact`, `password`, `cpassword`) VALUES
('Ram', 'Kumar', 'Male', 'ram@gmail.com', '9876543210', '123', '123'),
('Alia', 'Bhatt', 'Female', 'alia@gmail.com', '8976897689', '123', '123'),
('Kishan', 'Lal', 'Male', 'kishansmart0@gmail.com', '8838489464', '123', '123'),
('Duy', 'Chu Quang', 'Male', 'duywinter@gmail.com', '0846181174', '123', '123'),
('Nguyễn Văn', 'Hùng', 'Nam', 'hung.nguyen@email.com', '0912345678', '123', '123'),
('Trần Thị', 'Lan', 'Nữ', 'lan.tran@email.com', '0923456789', '123', '123'),
('Lê Văn', 'Minh', 'Nam', 'minh.le@email.com', '0934567890', '123', '123'),
('Phạm Thị', 'Hoa', 'Nữ', 'hoa.pham@email.com', '0945678901', '123', '123'),
('Hoàng Văn', 'Đức', 'Nam', 'duc.hoang@email.com', '0956789012', '123', '123');

-- ============================================
-- 6. BẢNG LỊCH LÀM VIỆC (DOCTOR_SCHEDULES)
-- ============================================
DROP TABLE IF EXISTS `doctor_schedules`;
CREATE TABLE `doctor_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` tinyint(1) NOT NULL COMMENT '0=CN, 1=T2, 2=T3, 3=T4, 4=T5, 5=T6, 6=T7',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slot_duration` int(3) DEFAULT '30' COMMENT 'Thời gian mỗi slot (phút)',
  `max_patients` int(3) DEFAULT '1' COMMENT 'Số bệnh nhân tối đa mỗi slot',
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `doctor_id` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tự động tạo lịch làm việc cho TẤT CẢ bác sĩ (T2-T6: 8h-17h, T7: 8h-12h)
INSERT INTO `doctor_schedules` (`doctor_id`, `day_of_week`, `start_time`, `end_time`, `slot_duration`, `max_patients`, `is_active`)
SELECT 
    d.id,
    day_num.n,
    '08:00:00',
    CASE WHEN day_num.n = 6 THEN '12:00:00' ELSE '17:00:00' END,
    30,
    1,
    1
FROM doctb d
CROSS JOIN (
    SELECT 1 AS n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
) day_num
WHERE d.status = 1;

-- ============================================
-- 7. BẢNG KHUNG GIỜ CHI TIẾT (TIME_SLOTS)
-- ============================================
DROP TABLE IF EXISTS `time_slots`;
CREATE TABLE `time_slots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `slot_date` date NOT NULL,
  `slot_time` time NOT NULL,
  `status` enum('available','booked','blocked') DEFAULT 'available',
  `appointment_id` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_slot` (`doctor_id`, `slot_date`, `slot_time`),
  KEY `doctor_id` (`doctor_id`),
  KEY `slot_date` (`slot_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. BẢNG ĐẶT LỊCH (APPOINTMENTTB)
-- ============================================
DROP TABLE IF EXISTS `appointmenttb`;
CREATE TABLE `appointmenttb` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `fname` varchar(20) NOT NULL,
  `lname` varchar(20) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `email` varchar(30) NOT NULL,
  `contact` varchar(10) NOT NULL,
  `doctor` varchar(30) NOT NULL,
  `docFees` int(5) NOT NULL,
  `appdate` date NOT NULL,
  `apptime` time NOT NULL,
  `slot_id` int(11) DEFAULT NULL,
  `userStatus` int(5) NOT NULL,
  `doctorStatus` int(5) NOT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `appointmenttb` (`pid`, `fname`, `lname`, `gender`, `email`, `contact`, `doctor`, `docFees`, `appdate`, `apptime`, `userStatus`, `doctorStatus`) VALUES
(4, 'Kishan', 'Lal', 'Male', 'kishansmart0@gmail.com', '8838489464', 'Ganesh', 550, '2020-02-14', '10:00:00', 1, 0),
(4, 'Kishan', 'Lal', 'Male', 'kishansmart0@gmail.com', '8838489464', 'Dinesh', 700, '2020-02-28', '10:00:00', 0, 1),
(12, 'Duy', 'Chu Quang', 'Male', 'duywinter@gmail.com', '0846181174', 'Abbis', 1500, '2026-01-15', '10:00:00', 1, 1);

-- ============================================
-- 9. BẢNG ĐƠN THUỐC (PRESTB)
-- ============================================
DROP TABLE IF EXISTS `prestb`;
CREATE TABLE `prestb` (
  `doctor` varchar(50) NOT NULL,
  `pid` int(11) NOT NULL,
  `ID` int(11) NOT NULL,
  `fname` varchar(50) NOT NULL,
  `lname` varchar(50) NOT NULL,
  `appdate` date NOT NULL,
  `apptime` time NOT NULL,
  `disease` varchar(250) NOT NULL,
  `allergy` varchar(250) NOT NULL,
  `prescription` varchar(1000) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. VIEW (V_DOCTORS)
-- ============================================
DROP VIEW IF EXISTS `v_doctors`;
CREATE VIEW `v_doctors` AS
SELECT 
    d.id,
    d.username,
    d.fullname,
    d.email,
    d.spec,
    d.spec_id,
    s.name_vi AS spec_name_vi,
    s.icon AS spec_icon,
    d.docFees,
    d.phone,
    d.bio,
    d.experience_years,
    d.status
FROM doctb d
LEFT JOIN specializations s ON d.spec_id = s.id;

COMMIT;
;
-- Create services table for medical services pricing
CREATE TABLE IF NOT EXISTS services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service_name VARCHAR(255) NOT NULL,
    description LONGTEXT,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0,
    status ENUM('0', '1') NOT NULL DEFAULT '1' COMMENT '0: Inactive, 1: Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(255),
    INDEX idx_service_name (service_name),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some sample services
INSERT INTO services (service_name, description, price, status, created_by) VALUES
('Khám tổng quát', 'Khám sức khỏe tổng quát đầy đủ', 500000, '1', 'System'),
('Siêu âm', 'Siêu âm chẩn đoán', 300000, '1', 'System'),
('Xét nghiệm máu', 'Xét nghiệm máu toàn bộ', 350000, '1', 'System'),
('Chụp X-quang', 'Chụp X-quang các bộ phận', 400000, '1', 'System'),
('Nhổ răng', 'Dịch vụ nhổ răng', 200000, '1', 'System');

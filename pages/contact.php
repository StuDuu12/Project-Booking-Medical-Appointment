<?php

/**
 * Trang Liên hệ - Bệnh viện Global
 */
session_start();
require_once '../includes/messages.php';
require_once '../config.php';

// Xử lý form liên hệ
if (isset($_POST['btnSubmit'])) {
	try {
		$name = htmlspecialchars(trim($_POST['txtName']));
		$email = htmlspecialchars(trim($_POST['txtEmail']));
		$contact = htmlspecialchars(trim($_POST['txtPhone']));
		$message = htmlspecialchars(trim($_POST['txtMsg']));

		$stmt = $pdo->prepare("INSERT INTO contact(name,email,contact,message) VALUES(:name,:email,:contact,:message)");
		$result = $stmt->execute([
			':name' => $name,
			':email' => $email,
			':contact' => $contact,
			':message' => $message
		]);

		if ($result) {
			redirectWithMessage('contact.php', 'success', 'Tin nhắn đã được gửi thành công! Chúng tôi sẽ liên hệ lại sớm nhất.');
		}
	} catch (PDOException $e) {
		error_log("Contact form error: " . $e->getMessage());
		redirectWithMessage('contact.php', 'error', 'Lỗi khi gửi tin nhắn. Vui lòng thử lại!');
	}
}

$base_path = '../';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="shortcut icon" type="image/x-icon" href="../images/favicon.png" />
	<title>Liên hệ - Bệnh viện Global</title>

	<!-- CSS -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

	<style>
		:root {
			--primary-color: #0891b2;
			--primary-dark: #0e7490;
			--primary-light: #14b8a6;
		}

		body {
			font-family: 'Inter', sans-serif;
			background: linear-gradient(135deg, #0e7490 0%, #0891b2 50%, #14b8a6 100%);
			min-height: 100vh;
			padding-top: 70px;
			position: relative;
			overflow-x: hidden;
		}

		body::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: radial-gradient(circle at 20% 80%, rgba(20, 184, 166, 0.2) 0%, transparent 50%),
				radial-gradient(circle at 80% 20%, rgba(6, 182, 212, 0.2) 0%, transparent 50%);
			pointer-events: none;
		}

		.contact-container {
			max-width: 1200px;
			margin: 2rem auto;
			padding: 0 1rem;
			position: relative;
			z-index: 2;
		}

		.contact-header {
			text-align: center;
			margin-bottom: 3rem;
			color: white;
		}

		.contact-header h1 {
			font-size: 2.5rem;
			font-weight: 700;
			margin-bottom: 1rem;
		}

		.contact-header p {
			font-size: 1.125rem;
			opacity: 0.9;
		}

		.contact-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 2rem;
			margin-bottom: 2rem;
		}

		.contact-info,
		.contact-form {
			background: white;
			padding: 2.5rem;
			border-radius: 20px;
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
		}

		.contact-info h2,
		.contact-form h2 {
			color: var(--primary-color);
			font-size: 1.75rem;
			font-weight: 700;
			margin-bottom: 1.5rem;
		}

		.info-item {
			display: flex;
			align-items: flex-start;
			gap: 1rem;
			margin-bottom: 1.5rem;
		}

		.info-icon {
			width: 50px;
			height: 50px;
			background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
			color: white;
			border-radius: 12px;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 1.25rem;
			flex-shrink: 0;
		}

		.info-content h3 {
			color: #374151;
			font-size: 1rem;
			font-weight: 600;
			margin-bottom: 0.5rem;
		}

		.info-content p {
			color: #6b7280;
			margin: 0;
			line-height: 1.6;
			font-size: 0.9rem;
		}

		.form-group {
			margin-bottom: 1.25rem;
		}

		.form-label {
			display: block;
			font-weight: 600;
			color: #374151;
			margin-bottom: 0.5rem;
			font-size: 0.9rem;
		}

		.form-control {
			width: 100%;
			padding: 0.875rem 1rem;
			border: 2px solid #e5e7eb;
			border-radius: 10px;
			font-size: 0.9rem;
			transition: all 0.2s ease;
			font-family: 'Inter', sans-serif;
			color: #000000;
		}

		.form-control:focus {
			outline: none;
			border-color: var(--primary-color);
			box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.12);
		}

		textarea.form-control {
			min-height: 120px;
			resize: vertical;
		}

		.btn-submit {
			width: 100%;
			padding: 1rem;
			background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
			color: white;
			border: none;
			border-radius: 10px;
			font-size: 1rem;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
		}

		.btn-submit:hover {
			transform: translateY(-2px);
			box-shadow: 0 10px 30px rgba(8, 145, 178, 0.4);
		}

		.map-container {
			background: white;
			padding: 2rem;
			border-radius: 20px;
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
			margin-bottom: 2rem;
		}

		.map-container h2 {
			color: var(--primary-color);
			font-size: 1.75rem;
			font-weight: 700;
			margin-bottom: 1.5rem;
			text-align: center;
		}

		.map-container iframe {
			width: 100%;
			height: 350px;
			border: none;
			border-radius: 10px;
		}

		@media (max-width: 768px) {
			.contact-grid {
				grid-template-columns: 1fr;
			}

			.contact-header h1 {
				font-size: 2rem;
			}

			.contact-info,
			.contact-form {
				padding: 1.5rem;
			}
		}
	</style>
</head>

<body>
	<?php include($base_path . 'includes/navbar.php'); ?>

	<!-- Contact Section -->
	<div class="contact-container">
		<div class="contact-header">
			<h1><i class="fas fa-phone-alt"></i> Liên hệ với chúng tôi</h1>
			<p>Chúng tôi luôn sẵn sàng lắng nghe và hỗ trợ bạn. Hãy gửi tin nhắn cho chúng tôi.</p>
		</div>

		<?php displayMessage(); ?>

		<div class="contact-grid">
			<!-- Thông tin liên hệ -->
			<div class="contact-info">
				<h2><i class="fas fa-info-circle"></i> Thông tin liên hệ</h2>

				<div class="info-item">
					<div class="info-icon">
						<i class="fas fa-map-marker-alt"></i>
					</div>
					<div class="info-content">
						<h3>Địa chỉ</h3>
						<p>123 Đường Y Tế<br>Quận 1, TP. Hồ Chí Minh<br>Việt Nam</p>
					</div>
				</div>

				<div class="info-item">
					<div class="info-icon">
						<i class="fas fa-phone"></i>
					</div>
					<div class="info-content">
						<h3>Điện thoại</h3>
						<p>Cấp cứu: 1900 1234<br>Lễ tân: 1900 5678<br>Fax: 1900 9012</p>
					</div>
				</div>

				<div class="info-item">
					<div class="info-icon">
						<i class="fas fa-envelope"></i>
					</div>
					<div class="info-content">
						<h3>Email</h3>
						<p>info@benhvienglobal.vn<br>hotro@benhvienglobal.vn<br>datlich@benhvienglobal.vn</p>
					</div>
				</div>

				<div class="info-item">
					<div class="info-icon">
						<i class="fas fa-clock"></i>
					</div>
					<div class="info-content">
						<h3>Giờ làm việc</h3>
						<p>Thứ 2 - Thứ 6: 7:00 - 20:00<br>Thứ 7 - Chủ nhật: 8:00 - 17:00<br>Cấp cứu: 24/7</p>
					</div>
				</div>
			</div>

			<!-- Form liên hệ -->
			<div class="contact-form">
				<h2><i class="fas fa-paper-plane"></i> Gửi tin nhắn</h2>

				<form method="post" action="">
					<div class="form-group">
						<label class="form-label">Họ và tên *</label>
						<input type="text" name="txtName" class="form-control" placeholder="Nhập họ và tên của bạn" required>
					</div>

					<div class="form-group">
						<label class="form-label">Email *</label>
						<input type="email" name="txtEmail" class="form-control" placeholder="Nhập địa chỉ email" required>
					</div>

					<div class="form-group">
						<label class="form-label">Số điện thoại *</label>
						<input type="tel" name="txtPhone" class="form-control" placeholder="Nhập số điện thoại" required>
					</div>

					<div class="form-group">
						<label class="form-label">Nội dung tin nhắn *</label>
						<textarea name="txtMsg" class="form-control" placeholder="Nhập nội dung tin nhắn của bạn..." required></textarea>
					</div>

					<button type="submit" name="btnSubmit" class="btn-submit">
						<i class="fas fa-paper-plane"></i> Gửi tin nhắn
					</button>
				</form>
			</div>
		</div>

		<!-- Bản đồ -->
		<div class="map-container">
			<h2><i class="fas fa-map"></i> Vị trí trên bản đồ</h2>
			<iframe
				src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.3193502183947!2d106.66408931533447!3d10.786834992314842!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752ed23c0f0f83%3A0xf1234567890!2sHo%20Chi%20Minh%20City!5e0!3m2!1sen!2s!4v1234567890"
				allowfullscreen=""
				loading="lazy">
			</iframe>
		</div>
	</div>

	<!-- Scripts -->
	<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
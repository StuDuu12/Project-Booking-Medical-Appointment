<?php
// Start session for navbar portal button
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Bệnh viện Global - Hệ thống Quản lý Bệnh viện</title>
	<link rel="shortcut icon" type="image/x-icon" href="images/favicon.png" />

	<!-- Google Fonts -->
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

	<!-- Bootstrap CSS -->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

	<!-- Font Awesome -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

	<style>
		:root {
			--primary-gradient: linear-gradient(135deg, #0e7490 0%, #0891b2 50%, #14b8a6 100%);
			--primary-color: #0891b2;
			--primary-dark: #0e7490;
			--secondary-color: #14b8a6;
			--accent-cyan: #22d3ee;
			--health-green: #10b981;
			--text-dark: #1e293b;
			--text-light: #64748b;
			--bg-light: #f0fdfa;
		}

		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}

		body {
			font-family: 'Inter', sans-serif;
			color: var(--text-dark);
			overflow-x: hidden;
		}

		/* Navbar */
		.navbar-custom {
			background: white;
			box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
			padding: 1rem 0;
			position: fixed;
			width: 100%;
			top: 0;
			z-index: 1000;
		}

		.navbar-brand {
			font-size: 1.75rem;
			font-weight: 700;
			background: var(--primary-gradient);
			-webkit-background-clip: text;
			-webkit-text-fill-color: transparent;
			background-clip: text;
		}

		.navbar-brand i {
			background: var(--primary-gradient);
			-webkit-background-clip: text;
			-webkit-text-fill-color: transparent;
		}

		.nav-link-custom {
			color: var(--text-dark) !important;
			font-weight: 500;
			margin: 0 1rem;
			transition: color 0.3s;
		}

		.nav-link-custom:hover {
			color: var(--primary-color) !important;
		}

		.btn-nav {
			padding: 0.5rem 1.5rem;
			border-radius: 8px;
			font-weight: 600;
			transition: all 0.3s;
			margin-left: 0.5rem;
		}

		.btn-login {
			color: var(--primary-color);
			border: 2px solid var(--primary-color);
			background: transparent;
		}

		.btn-login:hover {
			background: var(--primary-color);
			color: white;
		}

		.btn-register {
			background: var(--primary-gradient);
			color: white;
			border: none;
			position: relative;
			overflow: hidden;
		}

		.btn-register::before {
			content: '';
			position: absolute;
			top: 0;
			left: -100%;
			width: 100%;
			height: 100%;
			background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
			transition: left 0.5s ease;
		}

		.btn-register:hover::before {
			left: 100%;
		}

		.btn-register:hover {
			transform: translateY(-2px);
			box-shadow: 0 10px 24px rgba(8, 145, 178, 0.3);
		}

		/* Hero Section */
		.hero-section {
			min-height: 100vh;
			background: var(--primary-gradient);
			display: flex;
			align-items: center;
			padding-top: 80px;
			position: relative;
			overflow: hidden;
		}

		.hero-section::before {
			content: '';
			position: absolute;
			width: 600px;
			height: 600px;
			background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, transparent 70%);
			border-radius: 50%;
			top: -250px;
			right: -250px;
			animation: float 8s ease-in-out infinite;
		}

		.hero-section::after {
			content: '';
			position: absolute;
			width: 450px;
			height: 450px;
			background: radial-gradient(circle, rgba(255, 255, 255, 0.12) 0%, transparent 70%);
			border-radius: 50%;
			bottom: -180px;
			left: -180px;
			animation: float 10s ease-in-out infinite reverse;
		}

		@keyframes float {

			0%,
			100% {
				transform: translate(0, 0) scale(1);
			}

			50% {
				transform: translate(30px, -30px) scale(1.05);
			}
		}

		.hero-content {
			position: relative;
			z-index: 2;
			color: white;
		}

		.hero-content h1 {
			font-size: 3.5rem;
			font-weight: 800;
			margin-bottom: 1.5rem;
			line-height: 1.2;
		}

		.hero-content p {
			font-size: 1.25rem;
			margin-bottom: 2rem;
			opacity: 0.95;
		}

		.hero-buttons .btn {
			padding: 1rem 2.5rem;
			font-size: 1.1rem;
			border-radius: 12px;
			font-weight: 600;
			margin-right: 1rem;
			margin-bottom: 1rem;
		}

		.btn-hero-primary {
			background: white;
			color: var(--primary-color);
			border: none;
		}

		.btn-hero-primary:hover {
			transform: translateY(-3px);
			box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
		}

		.btn-hero-secondary {
			background: transparent;
			color: white;
			border: 2px solid white;
		}

		.btn-hero-secondary:hover {
			background: white;
			color: var(--primary-color);
		}

		.hero-image {
			position: relative;
			z-index: 2;
		}

		.hero-image img {
			max-width: 100%;
			filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.2));
		}

		/* Features Section */
		.features-section {
			padding: 5rem 0;
			background: var(--bg-light);
		}

		.section-title {
			text-align: center;
			margin-bottom: 4rem;
		}

		.section-title h2 {
			font-size: 2.5rem;
			font-weight: 700;
			margin-bottom: 1rem;
			color: var(--text-dark);
		}

		.section-title p {
			font-size: 1.1rem;
			color: var(--text-light);
		}

		.feature-card {
			background: white;
			padding: 2.5rem;
			border-radius: 16px;
			text-align: center;
			transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
			border: 1px solid rgba(8, 145, 178, 0.1);
			height: 100%;
			position: relative;
			overflow: hidden;
		}

		.feature-card::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			height: 4px;
			background: var(--primary-gradient);
			transform: scaleX(0);
			transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
		}

		.feature-card:hover::before {
			transform: scaleX(1);
		}

		.feature-card:hover {
			transform: translateY(-10px);
			box-shadow: 0 20px 40px rgba(8, 145, 178, 0.15);
			border-color: var(--primary-color);
		}

		.feature-icon {
			width: 80px;
			height: 80px;
			background: var(--primary-gradient);
			border-radius: 16px;
			display: flex;
			align-items: center;
			justify-content: center;
			margin: 0 auto 1.5rem;
			box-shadow: 0 8px 16px rgba(8, 145, 178, 0.2);
			transition: all 0.35s ease;
		}

		.feature-card:hover .feature-icon {
			transform: scale(1.1) rotate(5deg);
			box-shadow: 0 12px 24px rgba(8, 145, 178, 0.3);
		}

		.feature-icon i {
			font-size: 2rem;
			color: white;
		}

		.feature-card h3 {
			font-size: 1.5rem;
			font-weight: 600;
			margin-bottom: 1rem;
		}

		.feature-card p {
			color: var(--text-light);
			line-height: 1.6;
		}

		/* Stats Section */
		.stats-section {
			padding: 5rem 0;
			background: var(--primary-gradient);
			color: white;
			position: relative;
			overflow: hidden;
		}

		.stats-section::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background:
				radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
				radial-gradient(circle at 80% 50%, rgba(255, 255, 255, 0.08) 0%, transparent 50%);
			pointer-events: none;
		}

		.stat-item {
			text-align: center;
			padding: 2rem;
			position: relative;
			z-index: 1;
		}

		.stat-number {
			font-size: 3rem;
			font-weight: 800;
			margin-bottom: 0.5rem;
			text-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
		}

		.stat-label {
			font-size: 1.1rem;
			opacity: 0.9;
		}

		/* CTA Section */
		.cta-section {
			padding: 5rem 0;
			background: white;
		}

		.cta-content {
			text-align: center;
			max-width: 700px;
			margin: 0 auto;
		}

		.cta-content h2 {
			font-size: 2.5rem;
			font-weight: 700;
			margin-bottom: 1.5rem;
		}

		.cta-content p {
			font-size: 1.2rem;
			color: var(--text-light);
			margin-bottom: 2rem;
		}

		/* Footer */
		.footer {
			background: #1a202c;
			color: white;
			padding: 3rem 0 1rem;
		}

		.footer-content {
			display: flex;
			justify-content: space-between;
			margin-bottom: 2rem;
		}

		.footer-brand {
			font-size: 1.5rem;
			font-weight: 700;
			margin-bottom: 1rem;
		}

		.footer-links {
			list-style: none;
			padding: 0;
		}

		.footer-links li {
			margin-bottom: 0.5rem;
		}

		.footer-links a {
			color: #a0aec0;
			text-decoration: none;
			transition: color 0.3s;
		}

		.footer-links a:hover {
			color: white;
		}

		.footer-bottom {
			text-align: center;
			padding-top: 2rem;
			border-top: 1px solid #2d3748;
			color: #a0aec0;
		}

		@media (max-width: 768px) {
			.hero-content h1 {
				font-size: 2.5rem;
			}

			.hero-content p {
				font-size: 1rem;
			}

			.section-title h2 {
				font-size: 2rem;
			}

			.stat-number {
				font-size: 2rem;
			}
		}
	</style>

	<!-- Load jQuery FIRST in head -->
	<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>

<body>
	<!-- Navbar -->
	<?php include('includes/navbar.php'); ?>

	<!-- Hero Section -->
	<section class="hero-section" id="home">
		<div class="container">
			<div class="row align-items-center">
				<div class="col-lg-6 hero-content">
					<?php
					// Get user info for personalized welcome message
					$welcomeName = '';
					$isUserLoggedIn = false;
					
					if (isset($_SESSION['patientSession'])) {
						$isUserLoggedIn = true;
						try {
							$stmt = $pdo->prepare("SELECT fname, lname FROM patreg WHERE pid = ?");
							$stmt->execute([$_SESSION['patientSession']]);
							$user = $stmt->fetch(PDO::FETCH_ASSOC);
							if ($user) {
								$welcomeName = trim($user['fname'] . ' ' . $user['lname']);
							}
						} catch (Exception $e) {
							$welcomeName = '';
						}
					} elseif (isset($_SESSION['doctorSession'])) {
						$isUserLoggedIn = true;
						try {
							$stmt = $pdo->prepare("SELECT fullname FROM doctb WHERE id = ?");
							$stmt->execute([$_SESSION['doctorSession']]);
							$user = $stmt->fetch(PDO::FETCH_ASSOC);
							if ($user && !empty($user['fullname'])) {
								$welcomeName = trim($user['fullname']);
							}
						} catch (Exception $e) {
							$welcomeName = '';
						}
					} elseif (isset($_SESSION['adminSession'])) {
						$isUserLoggedIn = true;
						$welcomeName = 'Quản trị viên';
					}
					
					if ($isUserLoggedIn && !empty($welcomeName)): ?>
						<h1>Chào mừng <?php echo htmlspecialchars($welcomeName); ?> đến với Global Hospitals</h1>
					<?php else: ?>
						<h1>Chào mừng đến với Global Hospitals</h1>
					<?php endif; ?>
					
					<p>Hệ thống quản lý bệnh viện hiện đại, đặt lịch khám nhanh chóng và tiện lợi. Chăm sóc sức khỏe của bạn là ưu tiên hàng đầu của chúng tôi.</p>
					
					<?php if (!$isUserLoggedIn): ?>
					<div class="hero-buttons">
						<a href="pages/auth/register.php" class="btn btn-hero-primary">
							<i class="fas fa-user-plus"></i> Đăng ký ngay
						</a>
						<a href="pages/auth/login.php" class="btn btn-hero-secondary">
							<i class="fas fa-sign-in-alt"></i> Đăng nhập
						</a>
					</div>
					<?php endif; ?>
				</div>
				<div class="col-lg-6 hero-image">
					<i class="fas fa-hospital-user" style="font-size: 20rem; color: rgba(255,255,255,0.2);"></i>
				</div>
			</div>
		</div>
	</section>

	<!-- Features Section -->
	<section class="features-section" id="features">
		<div class="container">
			<div class="section-title">
				<h2>Tính năng nổi bật</h2>
				<p>Hệ thống quản lý bệnh viện toàn diện với nhiều tính năng hiện đại</p>
			</div>
			<div class="row">
				<div class="col-md-4 mb-4">
					<div class="feature-card">
						<div class="feature-icon">
							<i class="fas fa-calendar-check"></i>
						</div>
						<h3>Đặt lịch khám</h3>
						<p>Đặt lịch hẹn với bác sĩ nhanh chóng, dễ dàng. Theo dõi lịch hẹn của bạn mọi lúc mọi nơi.</p>
					</div>
				</div>
				<div class="col-md-4 mb-4">
					<div class="feature-card">
						<div class="feature-icon">
							<i class="fas fa-user-md"></i>
						</div>
						<h3>Quản lý bác sĩ</h3>
						<p>Hệ thống quản lý thông tin bác sĩ, chuyên khoa và lịch làm việc hiệu quả.</p>
					</div>
				</div>
				<div class="col-md-4 mb-4">
					<div class="feature-card">
						<div class="feature-icon">
							<i class="fas fa-file-medical"></i>
						</div>
						<h3>Hồ sơ điện tử</h3>
						<p>Lưu trữ và quản lý hồ sơ bệnh án điện tử an toàn, bảo mật tuyệt đối.</p>
					</div>
				</div>
				<div class="col-md-4 mb-4">
					<div class="feature-card">
						<div class="feature-icon">
							<i class="fas fa-pills"></i>
						</div>
						<h3>Đơn thuốc</h3>
						<p>Quản lý đơn thuốc và lịch sử điều trị của bệnh nhân một cách chi tiết.</p>
					</div>
				</div>
				<div class="col-md-4 mb-4">
					<div class="feature-card">
						<div class="feature-icon">
							<i class="fas fa-chart-line"></i>
						</div>
						<h3>Thống kê báo cáo</h3>
						<p>Báo cáo và thống kê chi tiết về hoạt động của bệnh viện.</p>
					</div>
				</div>
				<div class="col-md-4 mb-4">
					<div class="feature-card">
						<div class="feature-icon">
							<i class="fas fa-shield-alt"></i>
						</div>
						<h3>Bảo mật cao</h3>
						<p>Hệ thống bảo mật thông tin bệnh nhân theo tiêu chuẩn quốc tế.</p>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- Stats Section -->
	<section class="stats-section">
		<div class="container">
			<div class="row">
				<div class="col-md-3 col-6">
					<div class="stat-item">
						<div class="stat-number">500+</div>
						<div class="stat-label">Bệnh nhân</div>
					</div>
				</div>
				<div class="col-md-3 col-6">
					<div class="stat-item">
						<div class="stat-number">50+</div>
						<div class="stat-label">Bác sĩ</div>
					</div>
				</div>
				<div class="col-md-3 col-6">
					<div class="stat-item">
						<div class="stat-number">1000+</div>
						<div class="stat-label">Lịch hẹn</div>
					</div>
				</div>
				<div class="col-md-3 col-6">
					<div class="stat-item">
						<div class="stat-number">24/7</div>
						<div class="stat-label">Hỗ trợ</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- CTA Section -->
	<section class="cta-section">
		<div class="container">
			<div class="cta-content">
				<h2>Sẵn sàng bắt đầu?</h2>
				<p>Đăng ký ngay hôm nay để trải nghiệm dịch vụ chăm sóc sức khỏe tốt nhất</p>
				<a href="pages/auth/register.php" class="btn btn-hero-primary" style="padding: 1rem 3rem; font-size: 1.1rem;">
					<i class="fas fa-rocket"></i> Đăng ký miễn phí
				</a>
			</div>
		</div>
	</section>

	<!-- Footer -->
	<footer class="footer">
		<div class="container">
			<div class="row">
				<div class="col-md-4 mb-4">
					<div class="footer-brand">
						<i class="fas fa-hospital"></i> Global Hospitals
					</div>
					<p>Hệ thống quản lý bệnh viện hiện đại, chuyên nghiệp và tiện lợi.</p>
				</div>
				<div class="col-md-4 mb-4">
					<h5>Liên kết nhanh</h5>
					<ul class="footer-links">
						<li><a href="#home">Trang chủ</a></li>
						<li><a href="#features">Tính năng</a></li>
						<li><a href="pages/reviews.php">Dịch vụ</a></li>
						<li><a href="pages/contact.php">Liên hệ</a></li>
					</ul>
				</div>
				<div class="col-md-4 mb-4">
					<h5>Liên hệ</h5>
					<ul class="footer-links">
						<li><i class="fas fa-envelope"></i> info@globalhospitals.com</li>
						<li><i class="fas fa-phone"></i> (84) 123-456-789</li>
						<li><i class="fas fa-map-marker-alt"></i> Hà Nội, Việt Nam</li>
					</ul>
				</div>
			</div>
			<div class="footer-bottom">
				<p>&copy; 2026 Global Hospitals. All rights reserved.</p>
			</div>
		</div>
	</footer>


</body>

</html>
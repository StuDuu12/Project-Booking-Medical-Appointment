<?php
session_start();
require_once('../../includes/messages.php');
$base_path = '../../';

// Get form data and errors if redirected
$login_data = $_SESSION['login_data'] ?? [];
$errors = $_SESSION['login_errors'] ?? [];
unset($_SESSION['login_data'], $_SESSION['login_errors']);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Đăng nhập - Bệnh viện Global</title>
	<link rel="shortcut icon" type="image/x-icon" href="../../images/favicon.png" />

	<!-- Google Fonts -->
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

	<!-- Bootstrap CSS -->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

	<!-- Font Awesome -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

	<!-- Modern Auth CSS -->
	<link rel="stylesheet" href="../../assets/css/custom/modern-auth.css?v=2.2">

	<style>
		.form-control-modern,
		.form-control-modern:focus,
		input.form-control-modern,
		input.form-control-modern:focus {
			color: #000000 !important;
			-webkit-text-fill-color: #000000 !important;
		}

		.form-control-modern:-webkit-autofill,
		.form-control-modern:-webkit-autofill:hover,
		.form-control-modern:-webkit-autofill:focus {
			-webkit-text-fill-color: #000000 !important;
			-webkit-box-shadow: 0 0 0px 1000px #f0f9fb inset !important;
			transition: background-color 5000s ease-in-out 0s;
		}
		
		.form-error {
			color: #dc3545;
			font-size: 0.85rem;
			margin-top: 0.25rem;
			display: block;
			animation: fadeIn 0.3s ease-in;
		}
		
		.form-control-modern.is-invalid {
			border-color: #dc3545;
			background-color: #fff5f5;
		}
		
		@keyframes fadeIn {
			from { opacity: 0; transform: translateY(-5px); }
			to { opacity: 1; transform: translateY(0); }
		}
	</style>
</head>

<body>
	<?php require_once($base_path . 'includes/navbar.php'); ?>

	<!-- Auth Container -->
	<div class="auth-container">
		<div class="auth-wrapper">
			<!-- Login Form -->
			<div class="auth-card">
				<div class="auth-icon">
					<i class="fas fa-sign-in-alt"></i>
				</div>
				<h2 class="auth-heading"><i class="fas fa-lock"></i> Đăng nhập</h2>
				<?php displayMessage(); ?>
				<form method="post" action="login-handler.php">
					<div class="form-group-modern">
						<label class="form-label-modern">
							<i class="fas fa-user"></i> Tên đăng nhập / Email
						</label>
						<input type="text" class="form-control-modern <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>"
							placeholder="Nhập tên đăng nhập hoặc email"
							name="username" value="<?php echo htmlspecialchars($login_data['username'] ?? ''); ?>" required>
						<?php if (isset($errors['username'])): ?>
							<span class="form-error"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['username']; ?></span>
						<?php endif; ?>
					</div>

					<div class="form-group-modern">
						<label class="form-label-modern">
							<i class="fas fa-key"></i> Mật khẩu
						</label>
						<input type="password" class="form-control-modern <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
							placeholder="Nhập mật khẩu" name="password" required>
						<?php if (isset($errors['password'])): ?>
							<span class="form-error"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['password']; ?></span>
						<?php endif; ?>
					</div>

					<button type="submit" class="btn-modern btn-primary-modern" name="login_submit">
						<i class="fas fa-sign-in-alt"></i> Đăng nhập
					</button>

					<div class="auth-divider">hoặc</div>

					<a href="register.php" class="auth-link">
						<i class="fas fa-user-plus"></i> Chưa có tài khoản? Đăng ký ngay
					</a>
				</form>
			</div>

			<!-- Info Card -->
			<div class="auth-card info-card">
				<div class="auth-icon">
					<i class="fas fa-hospital"></i>
				</div>
				<h3 class="info-title">Chào mừng đến</h3>

				<div class="info-feature">
					<div class="info-feature-icon">
						<i class="fas fa-calendar-check"></i>
					</div>
					<div>
						<h5>Đặt lịch dễ dàng</h5>
						<p>Đặt lịch khám bác sĩ trong vài giây, theo dõi mọi lúc mọi nơi</p>
					</div>
				</div>

				<div class="info-feature">
					<div class="info-feature-icon">
						<i class="fas fa-user-md"></i>
					</div>
					<div>
						<h5>Bác sĩ chuyên nghiệp</h5>
						<p>Đội ngũ bác sĩ giàu kinh nghiệm, nhiều chuyên khoa</p>
					</div>
				</div>

				<div class="info-feature">
					<div class="info-feature-icon">
						<i class="fas fa-file-medical"></i>
					</div>
					<div>
						<h5>Hồ sơ điện tử</h5>
						<p>Lưu trữ hồ sơ bệnh án an toàn, bảo mật tuyệt đối</p>
					</div>
				</div>

				<div class="info-feature">
					<div class="info-feature-icon">
						<i class="fas fa-shield-alt"></i>
					</div>
					<div>
						<h5>Bảo mật cao</h5>
						<p>Thông tin bệnh nhân được bảo vệ theo tiêu chuẩn quốc tế</p>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Scripts -->
	<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
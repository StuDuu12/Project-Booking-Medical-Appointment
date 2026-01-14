<?php
session_start();
require_once('../../includes/messages.php');
$base_path = '../../';

// Get form data if redirected with errors
$form_data = $_SESSION['form_data'] ?? [];
$errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Đăng ký - Bệnh viện Global</title>
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
		.form-error {
			color: #dc3545;
			font-size: 0.85rem;
			margin-top: 0.25rem;
			display: block;
			animation: fadeIn 0.3s ease-in;
		}

		.form-input.is-invalid {
			border-color: #dc3545;
			background-color: #fff5f5;
		}

		.form-input.is-valid {
			border-color: #28a745;
			background-color: #f0fff4;
		}

		@keyframes fadeIn {
			from {
				opacity: 0;
				transform: translateY(-5px);
			}

			to {
				opacity: 1;
				transform: translateY(0);
			}
		}

		.main-container {
			min-height: 100vh;
			padding: 100px 20px 40px;
			background: linear-gradient(135deg, #0e7490 0%, #0891b2 50%, #14b8a6 100%);
			position: relative;
			overflow: hidden;
		}

		.main-container::before {
			content: '';
			position: absolute;
			top: -50%;
			right: -50%;
			width: 100%;
			height: 100%;
			background: radial-gradient(circle, rgba(20, 184, 166, 0.2) 0%, transparent 70%);
			animation: float 15s ease-in-out infinite;
		}

		@keyframes float {

			0%,
			100% {
				transform: translate(0, 0) rotate(0deg);
			}

			50% {
				transform: translate(50px, 50px) rotate(180deg);
			}
		}

		.register-card {
			max-width: 900px;
			margin: 0 auto;
			background: white;
			border-radius: 24px;
			padding: 3rem;
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
			position: relative;
			z-index: 1;
		}

		.register-header {
			text-align: center;
			margin-bottom: 2.5rem;
		}

		.register-icon {
			width: 80px;
			height: 80px;
			background: linear-gradient(135deg, #0891b2, #14b8a6);
			border-radius: 20px;
			display: flex;
			align-items: center;
			justify-content: center;
			margin: 0 auto 1.5rem;
			font-size: 2.5rem;
			color: white;
			box-shadow: 0 10px 30px rgba(8, 145, 178, 0.4);
		}

		.register-title {
			font-size: 2rem;
			font-weight: 700;
			color: #1a202c;
			margin-bottom: 0.5rem;
		}

		.register-subtitle {
			color: #718096;
			font-size: 1rem;
		}

		.form-row-custom {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 1.5rem;
			margin-bottom: 1.5rem;
		}

		.form-group-custom {
			position: relative;
		}

		.form-group-custom.full-width {
			grid-column: 1 / -1;
		}

		.form-label {
			display: block;
			font-weight: 600;
			color: #2d3748;
			margin-bottom: 0.5rem;
			font-size: 0.9rem;
		}

		.required {
			color: #e53e3e;
		}

		.form-input {
			width: 100%;
			padding: 0.875rem 1rem;
			border: 2px solid #e2e8f0;
			border-radius: 12px;
			font-size: 0.95rem;
			transition: all 0.2s ease;
			color: #000000;
			background: white;
		}

		.form-input:focus {
			outline: none;
			border-color: #0891b2;
			box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.1);
		}

		.gender-group {
			display: flex;
			gap: 1rem;
		}

		.gender-option {
			flex: 1;
			position: relative;
			cursor: pointer;
		}

		.gender-option input[type="radio"] {
			position: absolute;
			opacity: 0;
		}

		.gender-option span {
			display: block;
			padding: 0.875rem;
			border: 2px solid #e2e8f0;
			border-radius: 12px;
			text-align: center;
			transition: all 0.2s ease;
			font-weight: 500;
			color: #4a5568;
		}

		.gender-option input:checked+span {
			border-color: #0891b2;
			background: linear-gradient(135deg, #0891b2, #14b8a6);
			color: white;
			box-shadow: 0 4px 12px rgba(8, 145, 178, 0.3);
		}

		.btn-submit-register {
			width: 100%;
			padding: 1rem;
			background: linear-gradient(135deg, #0891b2, #14b8a6);
			color: white;
			border: none;
			border-radius: 12px;
			font-size: 1rem;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.3s ease;
			margin-top: 1rem;
		}

		.btn-submit-register:hover {
			transform: translateY(-2px);
			box-shadow: 0 10px 30px rgba(8, 145, 178, 0.4);
		}

		.login-link {
			text-align: center;
			margin-top: 1.5rem;
			font-size: 0.95rem;
		}

		.login-link a {
			color: #0891b2;
			font-weight: 600;
			text-decoration: none;
			margin-left: 0.5rem;
		}

		.login-link a:hover {
			text-decoration: underline;
		}

		.password-strength {
			margin-top: 0.5rem;
			height: 4px;
			border-radius: 2px;
			background: #e2e8f0;
			overflow: hidden;
		}

		.password-strength-bar {
			height: 100%;
			transition: all 0.3s ease;
			width: 0;
		}

		.password-match {
			margin-top: 0.5rem;
			font-size: 0.85rem;
			font-weight: 500;
		}

		.password-match.success {
			color: #28a745;
		}

		.password-match.error {
			color: #dc3545;
		}

		@media (max-width: 768px) {
			.form-row-custom {
				grid-template-columns: 1fr;
			}

			.register-card {
				padding: 2rem 1.5rem;
			}
		}
	</style>
</head>

<body>
	<?php include($base_path . 'includes/navbar.php'); ?>

	<!-- Main Container -->
	<div class="main-container">
		<div class="register-card">
			<!-- Header -->
			<div class="register-header">
				<div class="register-icon">
					<i class="fas fa-user-plus"></i>
				</div>
				<h1 class="register-title">Tạo tài khoản</h1>
				<p class="register-subtitle">Đăng ký để đặt lịch khám bệnh online</p>
			</div>

			<!-- Messages -->
			<?php displayMessage(); ?>

			<!-- Form -->
			<form method="post" action="register-handler.php" id="registerForm" novalidate>
				<!-- Họ & Tên -->
				<div class="form-row-custom">
					<div class="form-group-custom">
						<label class="form-label">Họ <span class="required">*</span></label>
						<input type="text" class="form-input <?php echo isset($errors['fname']) ? 'is-invalid' : ''; ?>"
							name="fname" placeholder="Nguyễn" value="<?php echo htmlspecialchars($form_data['fname'] ?? ''); ?>"
							onkeydown="return alphaOnly(event);" required>
						<?php if (isset($errors['fname'])): ?>
							<span class="form-error"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['fname']; ?></span>
						<?php endif; ?>
					</div>
					<div class="form-group-custom">
						<label class="form-label">Tên <span class="required">*</span></label>
						<input type="text" class="form-input <?php echo isset($errors['lname']) ? 'is-invalid' : ''; ?>"
							name="lname" placeholder="Văn A" value="<?php echo htmlspecialchars($form_data['lname'] ?? ''); ?>"
							onkeydown="return alphaOnly(event);" required>
						<?php if (isset($errors['lname'])): ?>
							<span class="form-error"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['lname']; ?></span>
						<?php endif; ?>
					</div>
				</div>

				<!-- Email & SĐT -->
				<div class="form-row-custom">
					<div class="form-group-custom">
						<label class="form-label">Email <span class="required">*</span></label>
						<input type="email" class="form-input <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
							name="email" placeholder="example@email.com" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
						<?php if (isset($errors['email'])): ?>
							<span class="form-error"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['email']; ?></span>
						<?php endif; ?>
					</div>
					<div class="form-group-custom">
						<label class="form-label">Số điện thoại <span class="required">*</span></label>
						<input type="tel" class="form-input <?php echo isset($errors['contact']) ? 'is-invalid' : ''; ?>"
							name="contact" placeholder="0912345678" value="<?php echo htmlspecialchars($form_data['contact'] ?? ''); ?>"
							minlength="10" maxlength="10" required>
						<?php if (isset($errors['contact'])): ?>
							<span class="form-error"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['contact']; ?></span>
						<?php endif; ?>
					</div>
				</div>

				<!-- Giới tính -->
				<div class="form-group-custom full-width">
					<label class="form-label">Giới tính</label>
					<div class="gender-group">
						<label class="gender-option">
							<input type="radio" name="gender" value="Male" checked>
							<span>Nam</span>
						</label>
						<label class="gender-option">
							<input type="radio" name="gender" value="Female">
							<span>Nữ</span>
						</label>
					</div>
				</div>

				<!-- Mật khẩu -->
				<div class="form-row-custom">
					<div class="form-group-custom">
						<label class="form-label">Mật khẩu <span class="required">*</span></label>
						<input type="password" class="form-input <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
							id="password" name="password" placeholder="Tối thiểu 6 ký tự"
							onkeyup="checkPasswordStrength(); checkPassword();" required>
						<div class="password-strength" id="passwordStrength">
							<div class="password-strength-bar" id="passwordStrengthBar"></div>
						</div>
						<span class="form-error" id="passwordError" style="display: none;"></span>
						<?php if (isset($errors['password'])): ?>
							<span class="form-error"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['password']; ?></span>
						<?php endif; ?>
					</div>
					<div class="form-group-custom">
						<label class="form-label">Xác nhận mật khẩu <span class="required">*</span></label>
						<input type="password" class="form-input <?php echo isset($errors['cpassword']) ? 'is-invalid' : ''; ?>"
							id="cpassword" name="cpassword" placeholder="Nhập lại mật khẩu"
							onkeyup="checkPassword();" required>
						<div id="passwordMessage" class="password-match"></div>
						<?php if (isset($errors['cpassword'])): ?>
							<span class="form-error"><i class="fas fa-exclamation-circle"></i> <?php echo $errors['cpassword']; ?></span>
						<?php endif; ?>
					</div>
				</div>

				<!-- Submit -->
				<button type="submit" class="btn-submit-register" name="patsub1" onclick="return validateForm();">
					<i class="fas fa-user-plus"></i> Đăng ký ngay
				</button>

				<!-- Login Link -->
				<div class="login-link">
					<span style="color: #718096;">Đã có tài khoản?</span>
					<a href="login.php"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a>
				</div>
			</form>
		</div>
	</div>

	<!-- Scripts -->
	<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

	<script>
		// Only allow letters and spaces
		function alphaOnly(event) {
			const key = event.keyCode;
			return ((key >= 65 && key <= 90) || key == 8 || key == 32);
		}

		// Check password strength
		function checkPasswordStrength() {
			const password = document.getElementById('password').value;
			const strengthBar = document.getElementById('passwordStrengthBar');
			const errorMsg = document.getElementById('passwordError');

			if (password.length === 0) {
				strengthBar.style.width = '0';
				errorMsg.style.display = 'none';
				return;
			}

			let strength = 0;
			if (password.length >= 6) strength += 25;
			if (password.length >= 8) strength += 25;
			if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
			if (/[0-9]/.test(password)) strength += 25;

			strengthBar.style.width = strength + '%';

			if (strength < 25) {
				strengthBar.style.background = '#dc3545';
				errorMsg.innerHTML = '<i class="fas fa-exclamation-circle"></i> Mật khẩu quá yếu';
				errorMsg.style.display = 'block';
				errorMsg.style.color = '#dc3545';
			} else if (strength < 50) {
				strengthBar.style.background = '#ffc107';
				errorMsg.innerHTML = '<i class="fas fa-info-circle"></i> Mật khẩu yếu';
				errorMsg.style.display = 'block';
				errorMsg.style.color = '#ffc107';
			} else if (strength < 75) {
				strengthBar.style.background = '#17a2b8';
				errorMsg.innerHTML = '<i class="fas fa-check-circle"></i> Mật khẩu trung bình';
				errorMsg.style.display = 'block';
				errorMsg.style.color = '#17a2b8';
			} else {
				strengthBar.style.background = '#28a745';
				errorMsg.innerHTML = '<i class="fas fa-check-circle"></i> Mật khẩu mạnh';
				errorMsg.style.display = 'block';
				errorMsg.style.color = '#28a745';
			}
		}

		// Check password match
		function checkPassword() {
			const password = document.getElementById('password').value;
			const cpassword = document.getElementById('cpassword').value;
			const message = document.getElementById('passwordMessage');
			const cpasswordInput = document.getElementById('cpassword');

			if (cpassword === '') {
				message.innerHTML = '';
				message.className = 'password-match';
				cpasswordInput.classList.remove('is-valid', 'is-invalid');
			} else if (password === cpassword) {
				message.innerHTML = '<i class="fas fa-check-circle"></i> Mật khẩu khớp';
				message.className = 'password-match success';
				cpasswordInput.classList.add('is-valid');
				cpasswordInput.classList.remove('is-invalid');
			} else {
				message.innerHTML = '<i class="fas fa-times-circle"></i> Mật khẩu không khớp';
				message.className = 'password-match error';
				cpasswordInput.classList.add('is-invalid');
				cpasswordInput.classList.remove('is-valid');
			}
		}

		// Real-time validation
		document.addEventListener('DOMContentLoaded', function() {
			const form = document.getElementById('registerForm');
			const inputs = form.querySelectorAll('input[required]');

			inputs.forEach(input => {
				input.addEventListener('blur', function() {
					if (this.value.trim() === '') {
						this.classList.add('is-invalid');
						this.classList.remove('is-valid');
					} else {
						this.classList.add('is-valid');
						this.classList.remove('is-invalid');
					}
				});
			});

			// Email validation
			const emailInput = form.querySelector('input[type="email"]');
			if (emailInput) {
				emailInput.addEventListener('blur', function() {
					const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
					if (!emailRegex.test(this.value)) {
						this.classList.add('is-invalid');
						this.classList.remove('is-valid');
					} else {
						this.classList.add('is-valid');
						this.classList.remove('is-invalid');
					}
				});
			}

			// Phone validation
			const phoneInput = form.querySelector('input[type="tel"]');
			if (phoneInput) {
				phoneInput.addEventListener('input', function() {
					this.value = this.value.replace(/[^0-9]/g, '');
				});
				phoneInput.addEventListener('blur', function() {
					if (this.value.length !== 10) {
						this.classList.add('is-invalid');
						this.classList.remove('is-valid');
					} else {
						this.classList.add('is-valid');
						this.classList.remove('is-invalid');
					}
				});
			}
		});

		// Form validation
		function validateForm() {
			const password = document.getElementById('password').value;
			const cpassword = document.getElementById('cpassword').value;
			const form = document.getElementById('registerForm');
			let isValid = true;

			// Check all required fields
			const inputs = form.querySelectorAll('input[required]');
			inputs.forEach(input => {
				if (input.value.trim() === '') {
					input.classList.add('is-invalid');
					isValid = false;
				}
			});

			if (!isValid) {
				return false;
			}

			if (password.length < 6) {
				document.getElementById('password').classList.add('is-invalid');
				return false;
			}

			if (password !== cpassword) {
				document.getElementById('cpassword').classList.add('is-invalid');
				return false;
			}

			return true;
		}
	</script>
</body>

</html>
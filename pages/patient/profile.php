<!DOCTYPE html>
<?php
session_start();
require_once('../../config.php');
require_once('../../includes/messages.php');
require_once('../../includes/functions.php');

$pid = $_SESSION['pid'] ?? null;
$username = $_SESSION['username'] ?? '';
$email = $_SESSION['email'] ?? '';
$fname = $_SESSION['fname'] ?? '';
$lname = $_SESSION['lname'] ?? '';
$gender = $_SESSION['gender'] ?? '';
$contact = $_SESSION['contact'] ?? '';

if (!$pid) {
    header("Location: ../../index.php");
    exit();
}

// Get patient full profile
$stmt = $pdo->prepare("SELECT * FROM patreg WHERE pid = :pid");
$stmt->execute([':pid' => $pid]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" type="image/x-icon" href="../../images/favicon.png" />
    <title>Hồ sơ cá nhân - Bệnh viện Global</title>

    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/custom/medical-theme.css">

    <style>
        .profile-header {
            background: linear-gradient(135deg, #0891b2 0%, #14b8a6 100%);
            padding: 3rem 0;
            margin-bottom: 2rem;
        }

        .profile-avatar-section {
            text-align: center;
            color: white;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            object-fit: cover;
            background: white;
        }

        .avatar-upload-btn {
            margin-top: 1rem;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .avatar-upload-btn:hover {
            background: white;
            color: #0891b2;
        }

        .profile-tabs {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .nav-tabs {
            border-bottom: 2px solid #e5e7eb;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #6b7280;
            font-weight: 600;
            padding: 1rem 2rem;
            transition: all 0.3s;
        }

        .nav-tabs .nav-link:hover {
            color: #0891b2;
        }

        .nav-tabs .nav-link.active {
            color: #0891b2;
            border-bottom: 3px solid #0891b2;
        }

        .tab-content {
            padding: 2rem;
        }

        .info-card {
            background: #f9fafb;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .info-label {
            font-weight: 600;
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .info-value {
            font-size: 1rem;
            color: #111827;
            font-weight: 500;
        }

        .form-section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .avatar-preview {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #e5e7eb;
            margin: 1rem auto;
            display: block;
        }
    </style>
</head>

<body>
    <?php displayMessage(); ?>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="profile-avatar-section" style="position: relative;">
                <a href="dashboard.php?page=profile" style="position: absolute; top: 0; left: 0; color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: rgba(255, 255, 255, 0.2); border-radius: 8px; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'" onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
                <img src="<?php echo $patient['avatar'] ? '../../' . $patient['avatar'] : '../../assets/images/default-avatar.png'; ?>"
                    alt="Avatar" class="profile-avatar" id="headerAvatar">
                <h2 class="mt-3"><?php echo htmlspecialchars($fname . ' ' . $lname); ?></h2>
                <p class="mb-0"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($email); ?></p>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($contact); ?></p>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        <div class="profile-tabs">
            <!-- Nav Tabs -->
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#info-tab">
                        <i class="fas fa-user"></i> Thông tin cá nhân
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#edit-tab">
                        <i class="fas fa-edit"></i> Chỉnh sửa hồ sơ
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#password-tab">
                        <i class="fas fa-key"></i> Đổi mật khẩu
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#avatar-tab">
                        <i class="fas fa-camera"></i> Ảnh đại diện
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Thông tin cá nhân Tab -->
                <div id="info-tab" class="tab-pane fade show active">
                    <h3 class="form-section-title">Thông tin cá nhân</h3>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-label"><i class="fas fa-user"></i> Họ</div>
                                <div class="info-value"><?php echo htmlspecialchars($patient['fname']); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-label"><i class="fas fa-user"></i> Tên</div>
                                <div class="info-value"><?php echo htmlspecialchars($patient['lname']); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-label"><i class="fas fa-venus-mars"></i> Giới tính</div>
                                <div class="info-value"><?php echo htmlspecialchars($patient['gender']); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-label"><i class="fas fa-birthday-cake"></i> Ngày sinh</div>
                                <div class="info-value">
                                    <?php echo $patient['date_of_birth'] ? date('d/m/Y', strtotime($patient['date_of_birth'])) : 'Chưa cập nhật'; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-label"><i class="fas fa-envelope"></i> Email</div>
                                <div class="info-value"><?php echo htmlspecialchars($patient['email']); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-label"><i class="fas fa-phone"></i> Số điện thoại</div>
                                <div class="info-value"><?php echo htmlspecialchars($patient['contact']); ?></div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="info-card">
                                <div class="info-label"><i class="fas fa-map-marker-alt"></i> Địa chỉ</div>
                                <div class="info-value">
                                    <?php echo $patient['address'] ? htmlspecialchars($patient['address']) : 'Chưa cập nhật'; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-label"><i class="fas fa-tint"></i> Nhóm máu</div>
                                <div class="info-value">
                                    <?php echo $patient['blood_group'] ? htmlspecialchars($patient['blood_group']) : 'Chưa cập nhật'; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card">
                                <div class="info-label"><i class="fas fa-phone-square"></i> Liên hệ khẩn cấp</div>
                                <div class="info-value">
                                    <?php
                                    if ($patient['emergency_contact']) {
                                        echo htmlspecialchars($patient['emergency_contact_name'] . ' - ' . $patient['emergency_contact']);
                                    } else {
                                        echo 'Chưa cập nhật';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chỉnh sửa hồ sơ Tab -->
                <div id="edit-tab" class="tab-pane fade">
                    <h3 class="form-section-title">Chỉnh sửa thông tin</h3>

                    <form method="post" action="profile-handler.php">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-phone"></i> Số điện thoại *</label>
                                    <input type="text" class="form-control" name="contact"
                                        value="<?php echo htmlspecialchars($patient['contact']); ?>"
                                        pattern="[0-9]{10}" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-birthday-cake"></i> Ngày sinh</label>
                                    <input type="date" class="form-control" name="date_of_birth"
                                        value="<?php echo $patient['date_of_birth']; ?>">
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-map-marker-alt"></i> Địa chỉ</label>
                                    <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($patient['address'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-tint"></i> Nhóm máu</label>
                                    <select class="form-control" name="blood_group">
                                        <option value="">Chọn nhóm máu</option>
                                        <?php
                                        $blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                        foreach ($blood_groups as $group) {
                                            $selected = ($patient['blood_group'] == $group) ? 'selected' : '';
                                            echo "<option value='$group' $selected>$group</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-user"></i> Tên người liên hệ khẩn cấp</label>
                                    <input type="text" class="form-control" name="emergency_contact_name"
                                        value="<?php echo htmlspecialchars($patient['emergency_contact_name'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-phone"></i> SĐT liên hệ khẩn cấp</label>
                                    <input type="text" class="form-control" name="emergency_contact"
                                        value="<?php echo htmlspecialchars($patient['emergency_contact'] ?? ''); ?>"
                                        pattern="[0-9]{10}">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Lưu thay đổi
                        </button>
                    </form>
                </div>

                <!-- Đổi mật khẩu Tab -->
                <div id="password-tab" class="tab-pane fade">
                    <h3 class="form-section-title">Đổi mật khẩu</h3>

                    <form method="post" action="profile-handler.php" onsubmit="return validatePassword()">
                        <input type="hidden" name="action" value="change_password">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-lock"></i> Mật khẩu hiện tại *</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                            </div>
                            <div class="col-md-6"></div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-lock"></i> Mật khẩu mới *</label>
                                    <input type="password" class="form-control" name="new_password"
                                        id="new_password" minlength="3" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label"><i class="fas fa-lock"></i> Xác nhận mật khẩu mới *</label>
                                    <input type="password" class="form-control" name="confirm_password"
                                        id="confirm_password" minlength="3" required>
                                    <small id="password_message"></small>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-key"></i> Đổi mật khẩu
                        </button>
                    </form>
                </div>

                <!-- Upload Avatar Tab -->
                <div id="avatar-tab" class="tab-pane fade">
                    <h3 class="form-section-title">Ảnh đại diện</h3>

                    <div class="text-center">
                        <img src="<?php echo $patient['avatar'] ? '../../' . $patient['avatar'] : '../../assets/images/default-avatar.png'; ?>"
                            alt="Avatar" class="avatar-preview" id="avatarPreview">
                    </div>

                    <form method="post" action="profile-handler.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_avatar">

                        <div class="form-group">
                            <label class="form-label"><i class="fas fa-image"></i> Chọn ảnh mới</label>
                            <input type="file" class="form-control" name="avatar" accept="image/*"
                                onchange="previewAvatar(event)" required>
                            <small class="form-text text-muted">
                                Chấp nhận: JPG, PNG, GIF. Kích thước tối đa: 5MB
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-upload"></i> Tải lên ảnh
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        function validatePassword() {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;

            if (newPass !== confirmPass) {
                alert('Mật khẩu xác nhận không khớp!');
                return false;
            }
            return true;
        }

        // Check password matching on typing
        document.getElementById('confirm_password').addEventListener('keyup', function() {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = this.value;
            const message = document.getElementById('password_message');

            if (confirmPass === '') {
                message.innerHTML = '';
            } else if (newPass === confirmPass) {
                message.style.color = '#10B981';
                message.innerHTML = '<i class="fas fa-check-circle"></i> Mật khẩu khớp';
            } else {
                message.style.color = '#EF4444';
                message.innerHTML = '<i class="fas fa-times-circle"></i> Mật khẩu không khớp';
            }
        });

        function previewAvatar(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>

</html>
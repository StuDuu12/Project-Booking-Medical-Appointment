<?php
session_start();
require_once('../../config.php');
require_once('../../includes/messages.php');
require_once('../../includes/functions.php');

$pid = $_SESSION['pid'] ?? null;

if (!$pid) {
    header("Location: ../../index.php");
    exit();
}

$action = $_POST['action'] ?? null;

try {
    // ========================================
    // 1. CẬP NHẬT THÔNG TIN PROFILE
    // ========================================
    if ($action === 'update_profile') {
        $contact = trim($_POST['contact'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $date_of_birth = $_POST['date_of_birth'] ?? null;
        $blood_group = $_POST['blood_group'] ?? null;
        $emergency_contact = trim($_POST['emergency_contact'] ?? '');
        $emergency_contact_name = trim($_POST['emergency_contact_name'] ?? '');

        // Validate contact
        if (!$contact || !preg_match('/^[0-9]{10}$/', $contact)) {
            redirectWithMessage('profile.php', 'error', 'Số điện thoại không hợp lệ!');
            exit();
        }

        // Validate emergency contact if provided
        if ($emergency_contact && !preg_match('/^[0-9]{10}$/', $emergency_contact)) {
            redirectWithMessage('profile.php', 'error', 'Số điện thoại liên hệ khẩn cấp không hợp lệ!');
            exit();
        }

        // Update database
        $stmt = $pdo->prepare("
            UPDATE patreg SET 
                contact = :contact,
                address = :address,
                date_of_birth = :date_of_birth,
                blood_group = :blood_group,
                emergency_contact = :emergency_contact,
                emergency_contact_name = :emergency_contact_name,
                updated_at = NOW()
            WHERE pid = :pid
        ");

        $result = $stmt->execute([
            ':contact' => $contact,
            ':address' => $address,
            ':date_of_birth' => $date_of_birth ?: null,
            ':blood_group' => $blood_group ?: null,
            ':emergency_contact' => $emergency_contact ?: null,
            ':emergency_contact_name' => $emergency_contact_name ?: null,
            ':pid' => $pid
        ]);

        if ($result) {
            // Update session contact
            $_SESSION['contact'] = $contact;
            redirectWithMessage('profile.php', 'success', 'Cập nhật thông tin thành công!');
        } else {
            redirectWithMessage('profile.php', 'error', 'Lỗi cập nhật thông tin!');
        }
        exit();
    }

    // ========================================
    // 2. ĐỔI MẬT KHẨU
    // ========================================
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate passwords
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            redirectWithMessage('profile.php', 'error', 'Vui lòng nhập đầy đủ mật khẩu!');
            exit();
        }

        if ($new_password !== $confirm_password) {
            redirectWithMessage('profile.php', 'error', 'Mật khẩu xác nhận không khớp!');
            exit();
        }

        if (strlen($new_password) < 3) {
            redirectWithMessage('profile.php', 'error', 'Mật khẩu phải có ít nhất 3 ký tự!');
            exit();
        }

        // Get current password from database
        $stmt = $pdo->prepare("SELECT password FROM patreg WHERE pid = :pid");
        $stmt->execute([':pid' => $pid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            redirectWithMessage('profile.php', 'error', 'Không tìm thấy người dùng!');
            exit();
        }

        // Verify current password (support both hashed and plain passwords)
        $password_valid = password_verify($current_password, $user['password']) || $user['password'] === $current_password;

        if (!$password_valid) {
            redirectWithMessage('profile.php', 'error', 'Mật khẩu hiện tại không đúng!');
            exit();
        }

        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE patreg SET password = :password, cpassword = :cpassword, updated_at = NOW() WHERE pid = :pid");
        $result = $stmt->execute([
            ':password' => $hashed_password,
            ':cpassword' => $hashed_password,
            ':pid' => $pid
        ]);

        if ($result) {
            redirectWithMessage('profile.php', 'success', 'Đổi mật khẩu thành công!');
        } else {
            redirectWithMessage('profile.php', 'error', 'Lỗi đổi mật khẩu!');
        }
        exit();
    }

    // ========================================
    // 3. UPLOAD ẢNH ĐẠI DIỆN
    // ========================================
    if ($action === 'upload_avatar') {
        // Create upload directory if not exists
        $upload_dir = '../../uploads/avatars/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Check if file was uploaded
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $error_message = 'Lỗi tải file!';
            if (isset($_FILES['avatar'])) {
                switch ($_FILES['avatar']['error']) {
                    case UPLOAD_ERR_NO_FILE:
                        $error_message = 'Vui lòng chọn file!';
                        break;
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $error_message = 'File quá lớn! Tối đa 5MB.';
                        break;
                }
            }
            redirectWithMessage('profile.php', 'error', $error_message);
            exit();
        }

        $file = $_FILES['avatar'];
        $file_name = $file['name'];
        $file_size = $file['size'];
        $file_tmp = $file['tmp_name'];
        $file_type = pathinfo($file_name, PATHINFO_EXTENSION);

        // Validate file type
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $allowed_types_lower = array_map('strtolower', $allowed_types);

        if (!in_array(strtolower($file_type), $allowed_types_lower)) {
            redirectWithMessage('profile.php', 'error', 'Chỉ chấp nhận file JPG, PNG, GIF!');
            exit();
        }

        // Validate file size (max 5MB)
        if ($file_size > 5 * 1024 * 1024) {
            redirectWithMessage('profile.php', 'error', 'File quá lớn! Tối đa 5MB.');
            exit();
        }

        // Generate unique filename
        $new_filename = 'avatar_' . $pid . '_' . time() . '.' . strtolower($file_type);
        $upload_path = $upload_dir . $new_filename;
        $db_path = 'uploads/avatars/' . $new_filename;

        // Move uploaded file
        if (!move_uploaded_file($file_tmp, $upload_path)) {
            redirectWithMessage('profile.php', 'error', 'Lỗi lưu file!');
            exit();
        }

        // Delete old avatar if exists
        $stmt = $pdo->prepare("SELECT avatar FROM patreg WHERE pid = :pid");
        $stmt->execute([':pid' => $pid]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($patient && $patient['avatar'] && file_exists('../../' . $patient['avatar'])) {
            unlink('../../' . $patient['avatar']);
        }

        // Update database
        $stmt = $pdo->prepare("UPDATE patreg SET avatar = :avatar, updated_at = NOW() WHERE pid = :pid");
        $result = $stmt->execute([
            ':avatar' => $db_path,
            ':pid' => $pid
        ]);

        if ($result) {
            redirectWithMessage('profile.php', 'success', 'Tải lên ảnh đại diện thành công!');
        } else {
            redirectWithMessage('profile.php', 'error', 'Lỗi lưu thông tin ảnh!');
            // Delete uploaded file if database update fails
            if (file_exists($upload_path)) {
                unlink($upload_path);
            }
        }
        exit();
    }

    // If no action provided
    redirectWithMessage('profile.php', 'error', 'Hành động không hợp lệ!');
    exit();
} catch (Exception $e) {
    error_log("Profile handler error: " . $e->getMessage());
    redirectWithMessage('profile.php', 'error', 'Lỗi: ' . $e->getMessage());
    exit();
}

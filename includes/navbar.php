<?php

/**
 * Navbar Component - Reusable navigation bar for all pages
 * 
 * Usage: 
 * $base_path = '../../'; // Adjust based on file location
 * include($base_path . 'includes/navbar.php');
 */

// Set default base path if not defined
if (!isset($base_path)) {
    $base_path = '';
}
?>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $base_path; ?>index.php">
            <i class="fas fa-hospital"></i> Bệnh viện Global
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link nav-link-custom" href="<?php echo $base_path; ?>index.php">Trang chủ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom" href="<?php echo $base_path; ?>pages/contact.php">Liên hệ</a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo $base_path; ?>pages/auth/login.php" class="btn btn-nav btn-login">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo $base_path; ?>pages/auth/register.php" class="btn btn-nav btn-register">
                        <i class="fas fa-user-plus"></i> Đăng ký
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
    /* Navbar Styles */
    .navbar-custom {
        background: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        padding: 0.75rem 0;
        position: fixed;
        width: 100%;
        top: 0;
        z-index: 1000;
    }

    .navbar-custom .container {
        max-width: 100%;
        padding-left: 2rem;
        padding-right: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .navbar-custom .navbar-brand {
        font-size: 1.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, #0e7490 0%, #0891b2 50%, #14b8a6 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .navbar-custom .navbar-brand i {
        background: linear-gradient(135deg, #0e7490 0%, #0891b2 50%, #14b8a6 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .navbar-custom .navbar-collapse {
        flex-grow: 0;
    }

    .navbar-custom .navbar-nav {
        margin-left: auto;
    }

    .nav-link-custom {
        color: #2d3748 !important;
        font-weight: 500;
        padding: 0.5rem 1rem !important;
        margin: 0 0.25rem;
        border-radius: 8px;
        transition: all 0.3s;
    }

    .nav-link-custom:hover {
        color: #0891b2 !important;
        background-color: #f0fdfa;
    }

    .navbar-custom .btn-nav {
        padding: 0.5rem 1rem !important;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.875rem !important;
        transition: all 0.3s;
        margin-left: 0.5rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        text-decoration: none !important;
        text-transform: none !important;
        letter-spacing: normal !important;
    }

    .navbar-custom .btn-login {
        color: #0891b2 !important;
        border: 2px solid #0891b2;
        background: transparent;
    }

    .navbar-custom .btn-login:hover {
        background: #0891b2;
        color: white !important;
    }

    .navbar-custom .btn-register {
        background: linear-gradient(135deg, #0891b2, #14b8a6);
        color: white !important;
        border: 2px solid transparent;
    }

    .navbar-custom .btn-register:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(8, 145, 178, 0.3);
        color: white !important;
    }

    /* Navbar toggler for mobile */
    .navbar-toggler {
        border: none;
        padding: 0.5rem;
    }

    .navbar-toggler:focus {
        outline: none;
        box-shadow: none;
    }

    .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(8, 145, 178, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    }

    @media (max-width: 991px) {
        .navbar-custom .navbar-nav {
            padding: 1rem 0;
        }

        .navbar-custom .nav-item {
            margin: 0.5rem 0;
        }

        .btn-nav {
            display: block;
            text-align: center;
            margin: 0.5rem 0;
        }
    }
</style>
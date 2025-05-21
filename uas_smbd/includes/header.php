<?php
// Prevent direct access
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Sistem Manajemen Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            padding-top: 56px; /* Height of navbar */
            overflow-x: hidden;
        }
        
        /* Fixed Header */
        .navbar {
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            z-index: 1030;
            height: 56px;
        }
        
        /* Fixed Sidebar */
        .sidebar {
            position: fixed;
            top: 56px; /* Height of navbar */
            bottom: 0;
            left: 0;
            width: 16.67%; /* col-md-2 width */
            z-index: 1020;
            overflow-y: auto; /* Allow sidebar to scroll if needed */
            overflow-x: hidden;
        }
        
        /* Main content area */
        .main-content {
            margin-left: 16.67%; /* Same as sidebar width */
            padding-top: 20px;
            min-height: calc(100vh - 56px);
            overflow-y: auto;
        }
        
        /* Active menu item */
        .active {
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        /* Responsive adjustments */
        @media (max-width: 767.98px) {
            .sidebar {
                width: 100%;
                position: static;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            body {
                padding-top: 56px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="../admin/dashboard.php">
                <i class="bi bi-mortarboard-fill fs-3 me-2"></i>
                <span class="fw-bold">Sistem Manajemen Sekolah</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle fs-5 me-1"></i>
                            <span class="fw-semibold"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="../admin/profil.php">
                                    <i class="bi bi-person-lines-fill me-2"></i> Profil
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="../auth/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0 bg-dark sidebar">
                <div class="d-flex flex-column flex-shrink-0 p-3 text-white">
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="../admin/dashboard.php" class="nav-link text-white <?php echo (getCurrentPage() == 'dashboard.php') ? 'active' : ''; ?>">
                                <i class="bi bi-speedometer2 me-2"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="../admin/siswa.php" class="nav-link text-white <?php echo (getCurrentPage() == 'siswa.php') ? 'active' : ''; ?>">
                                <i class="bi bi-people me-2"></i> Siswa
                            </a>
                        </li>
                        <li>
                            <a href="../admin/guru.php" class="nav-link text-white <?php echo (getCurrentPage() == 'guru.php') ? 'active' : ''; ?>">
                                <i class="bi bi-person-badge me-2"></i> Guru
                            </a>
                        </li>
                        <li>
                            <a href="../admin/kelas.php" class="nav-link text-white <?php echo (getCurrentPage() == 'kelas.php') ? 'active' : ''; ?>">
                                <i class="bi bi-building me-2"></i> Kelas
                            </a>
                        </li>
                        <li>
                            <a href="../admin/mata-pelajaran.php" class="nav-link text-white <?php echo (getCurrentPage() == 'mata-pelajaran.php') ? 'active' : ''; ?>">
                                <i class="bi bi-book me-2"></i> Mata Pelajaran
                            </a>
                        </li>
                        <li>
                            <a href="../admin/jadwal.php" class="nav-link text-white <?php echo (getCurrentPage() == 'jadwal.php') ? 'active' : ''; ?>">
                                <i class="bi bi-calendar-week me-2"></i> Jadwal
                            </a>
                        </li>
                        <li>
                            <a href="../admin/nilai.php" class="nav-link text-white <?php echo (getCurrentPage() == 'nilai.php') ? 'active' : ''; ?>">
                                <i class="bi bi-card-checklist me-2"></i> Nilai
                            </a>
                        </li>
                        <li>
                            <a href="../admin/laporan.php" class="nav-link text-white <?php echo (getCurrentPage() == 'laporan.php') ? 'active' : ''; ?>">
                                <i class="bi bi-file-earmark-text me-2"></i> Laporan
                            </a>
                        </li>
                    </ul>
                    <!-- Animasi menarik di bawah menu laporan -->
                    <div class="mt-4 text-center">
                        <div class="school-animation">
                            <div class="school-roof"></div>
                            <div class="school-body"></div>
                            <div class="school-door"></div>
                            <div class="school-flag"></div>
                            <div class="school-sun"></div>
                        </div>
                    </div>
                    <style>
                        .school-animation {
                            position: relative;
                            width: 80px;
                            height: 80px;
                            margin: 0 auto;
                        }
                        .school-roof {
                            position: absolute;
                            top: 20px;
                            left: 15px;
                            width: 50px;
                            height: 0;
                            border-left: 25px solid transparent;
                            border-right: 25px solid transparent;
                            border-bottom: 20px solid #dc3545;
                            animation: roof-bounce 1.5s infinite;
                        }
                        .school-body {
                            position: absolute;
                            top: 40px;
                            left: 20px;
                            width: 40px;
                            height: 30px;
                            background: #ffc107;
                            border-radius: 0 0 8px 8px;
                            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                            animation: body-pop 1.5s infinite;
                        }
                        .school-door {
                            position: absolute;
                            top: 55px;
                            left: 35px;
                            width: 10px;
                            height: 15px;
                            background: #795548;
                            border-radius: 2px;
                            animation: door-swing 1.5s infinite;
                        }
                        .school-flag {
                            position: absolute;
                            top: 10px;
                            left: 38px;
                            width: 4px;
                            height: 18px;
                            background: #343a40;
                        }
                        .school-flag:after {
                            content: '';
                            position: absolute;
                            left: 4px;
                            top: 0;
                            width: 12px;
                            height: 8px;
                            background: #198754;
                            border-radius: 2px 4px 4px 2px;
                            animation: flag-wave 1.5s infinite linear;
                        }
                        .school-sun {
                            position: absolute;
                            top: 0;
                            left: 60px;
                            width: 18px;
                            height: 18px;
                            background: #ffd600;
                            border-radius: 50%;
                            box-shadow: 0 0 16px 4px #ffe082;
                            animation: sun-spin 4s linear infinite;
                        }
                        @keyframes roof-bounce {
                            0%, 100% { transform: translateY(0);}
                            50% { transform: translateY(-4px);}
                        }
                        @keyframes body-pop {
                            0%, 100% { transform: scaleY(1);}
                            50% { transform: scaleY(1.05);}
                        }
                        @keyframes door-swing {
                            0%, 100% { transform: rotate(0);}
                            50% { transform: rotate(-10deg);}
                        }
                        @keyframes flag-wave {
                            0% { transform: rotate(0);}
                            50% { transform: rotate(10deg);}
                            100% { transform: rotate(0);}
                        }
                        @keyframes sun-spin {
                            0% { transform: rotate(0);}
                            100% { transform: rotate(360deg);}
                        }
                    </style>
                </div>
            </div>
            <div class="col-md-10 main-content">

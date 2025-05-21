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
        .sidebar {
            min-height: calc(100vh - 56px);
        }
        .active {
            background-color: rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../admin/dashboard.php">Sistem Manajemen Sekolah</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 p-0 bg-dark sidebar">
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
                </div>
            </div>
            <div class="col-md-9 col-lg-10 p-4">

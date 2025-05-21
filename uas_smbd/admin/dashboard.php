<?php
require_once '../config/init.php';

// Redirect to login page if not logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

// Set page title
$pageTitle = 'Dashboard';

// Define BASEPATH
define('BASEPATH', true);

// Get statistics
try {
    // Count students
    $stmt = $conn->query("SELECT COUNT(*) as total FROM siswa");
    $totalSiswa = $stmt->fetch()['total'];
    
    // Count teachers
    $stmt = $conn->query("SELECT COUNT(*) as total FROM guru");
    $totalGuru = $stmt->fetch()['total'];
    
    // Count classes
    $stmt = $conn->query("SELECT COUNT(*) as total FROM kelas");
    $totalKelas = $stmt->fetch()['total'];
    
    // Count subjects
    $stmt = $conn->query("SELECT COUNT(*) as total FROM mata_pelajaran");
    $totalMapel = $stmt->fetch()['total'];
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4">Dashboard</h1>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Siswa</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalSiswa; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Guru</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalGuru; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-person-badge fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Kelas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalKelas; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-building fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total Mata Pelajaran</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalMapel; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-book fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Siswa Terbaru</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>NIS</th>
                                    <th>Nama</th>
                                    <th>Kelas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $conn->query("
                                        SELECT s.nis, s.nama_siswa, k.nama_kelas 
                                        FROM siswa s
                                        LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
                                        ORDER BY s.id_siswa DESC
                                        LIMIT 5
                                    ");
                                    
                                    while ($row = $stmt->fetch()) {
                                        echo "<tr>";
                                        echo "<td>{$row['nis']}</td>";
                                        echo "<td>{$row['nama_siswa']}</td>";
                                        echo "<td>{$row['nama_kelas']}</td>";
                                        echo "</tr>";
                                    }
                                } catch(PDOException $e) {
                                    echo "<tr><td colspan='3'>Error: " . $e->getMessage() . "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Guru Terbaru</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>NIP</th>
                                    <th>Nama</th>
                                    <th>No. Telepon</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $conn->query("
                                        SELECT nip, nama_guru, no_telepon 
                                        FROM guru
                                        ORDER BY id_guru DESC
                                        LIMIT 5
                                    ");
                                    
                                    while ($row = $stmt->fetch()) {
                                        echo "<tr>";
                                        echo "<td>{$row['nip']}</td>";
                                        echo "<td>{$row['nama_guru']}</td>";
                                        echo "<td>{$row['no_telepon']}</td>";
                                        echo "</tr>";
                                    }
                                } catch(PDOException $e) {
                                    echo "<tr><td colspan='3'>Error: " . $e->getMessage() . "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../includes/footer.php';
?>

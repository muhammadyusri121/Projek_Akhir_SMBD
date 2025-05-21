<?php
// Inisialisasi
define('BASEPATH', true);
require_once '../config/init.php';

// Cek session
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Set judul halaman
$pageTitle = 'Dashboard';

// Ambil data overview untuk dashboard
try {
    // Jumlah siswa
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM siswa");
    $stmt->execute();
    $totalSiswa = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Jumlah guru
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM guru");
    $stmt->execute();
    $totalGuru = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Jumlah kelas
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM kelas");
    $stmt->execute();
    $totalKelas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Jumlah mata pelajaran
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM mata_pelajaran");
    $stmt->execute();
    $totalMapel = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Data untuk chart - jumlah siswa per kelas menggunakan view
    $stmt = $conn->prepare("
        SELECT nama_kelas, COUNT(*) as jumlah_siswa 
        FROM view_siswa_kelas 
        GROUP BY nama_kelas 
        ORDER BY nama_kelas
    ");
    $stmt->execute();
    $siswaPerkelas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Data jadwal hari ini menggunakan view
    $hariIni = date('l');
    $hariIndonesia = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    
    $stmt = $conn->prepare("
        SELECT * FROM view_jadwal_lengkap
        WHERE hari = ?
        ORDER BY jam_mulai
    ");
    $stmt->bindValue(1, $hariIndonesia[$hariIni]);
    $stmt->execute();
    $jadwalHariIni = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Aktivitas terbaru (nilai)
    $stmt = $conn->prepare("
        SELECT n.*, s.nama_siswa, mp.nama_mapel 
        FROM nilai n
        JOIN siswa s ON n.id_siswa = s.id_siswa
        JOIN mata_pelajaran mp ON n.id_mapel = mp.id_mapel
        ORDER BY n.tanggal_input DESC
        LIMIT 5
    ");
    $stmt->execute();
    $aktivitasTerbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Load header
include_once '../includes/header.php';
?>

<!-- Begin Page Content -->
<div class="dashboard-container">
    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">Dashboard</h1>
        <div class="date-display">
            <i class="bi bi-calendar-check"></i> <?php echo date('d F Y'); ?>
        </div>
    </div>

    <!-- Overview Cards Row -->
    <div class="row cards-container">
        <!-- Siswa Card -->
        <div class="col-12 col-sm-6 col-md-6 col-xl-3 mb-4">
            <div class="card border-left-primary shadow h-100 dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Siswa</div>
                            <div class="h3 mb-0 font-weight-bold"><?php echo $totalSiswa; ?></div>
                        </div>
                        <div class="icon-circle bg-primary">
                            <i class="bi bi-people text-white fs-4"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="siswa.php" class="text-primary small">Lihat detail <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- Guru Card -->
        <div class="col-12 col-sm-6 col-md-6 col-xl-3 mb-4">
            <div class="card border-left-success shadow h-100 dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Guru</div>
                            <div class="h3 mb-0 font-weight-bold"><?php echo $totalGuru; ?></div>
                        </div>
                        <div class="icon-circle bg-success">
                            <i class="bi bi-person-badge text-white fs-4"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="guru.php" class="text-success small">Lihat detail <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- Kelas Card -->
        <div class="col-12 col-sm-6 col-md-6 col-xl-3 mb-4">
            <div class="card border-left-info shadow h-100 dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Kelas</div>
                            <div class="h3 mb-0 font-weight-bold"><?php echo $totalKelas; ?></div>
                        </div>
                        <div class="icon-circle bg-info">
                            <i class="bi bi-building text-white fs-4"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="kelas.php" class="text-info small">Lihat detail <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- Mata Pelajaran Card -->
        <div class="col-12 col-sm-6 col-md-6 col-xl-3 mb-4">
            <div class="card border-left-warning shadow h-100 dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Mata Pelajaran</div>
                            <div class="h3 mb-0 font-weight-bold"><?php echo $totalMapel; ?></div>
                        </div>
                        <div class="icon-circle bg-warning">
                            <i class="bi bi-book text-white fs-4"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="mata-pelajaran.php" class="text-warning small">Lihat detail <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Chart Siswa Per Kelas -->
        <div class="col-12 col-lg-8 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">Distribusi Siswa per Kelas</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="siswaPerKelasChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Jadwal Hari Ini -->
        <div class="col-12 col-lg-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">Jadwal Hari Ini (<?php echo $hariIndonesia[$hariIni]; ?>)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive jadwal-container">
                        <?php if (count($jadwalHariIni) > 0): ?>
                            <table class="table table-hover mb-0">
                                <tbody>
                                    <?php foreach ($jadwalHariIni as $jadwal): ?>
                                        <tr>
                                            <td class="text-center fw-bold time-column">
                                                <?php 
                                                    echo substr($jadwal['jam_mulai'], 0, 5) . '<br>-<br>' . substr($jadwal['jam_selesai'], 0, 5); 
                                                ?>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?php echo $jadwal['nama_mapel']; ?></div>
                                                <div class="small">Kelas: <?php echo $jadwal['nama_kelas']; ?></div>
                                                <div class="small text-muted">Guru: <?php echo $jadwal['nama_guru']; ?></div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="p-3 text-center">
                                <i class="bi bi-calendar-x fs-1 text-muted"></i>
                                <p class="mt-2">Tidak ada jadwal untuk hari ini</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Aktivitas Terbaru -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">Aktivitas Terbaru</h6>
                </div>
                <div class="card-body">
                    <div class="aktivitas-container">
                        <?php if (count($aktivitasTerbaru) > 0): ?>
                            <div class="timeline">
                                <?php foreach ($aktivitasTerbaru as $aktivitas): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-marker"></div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1">Input Nilai: <?php echo $aktivitas['nama_siswa']; ?></h6>
                                            <div class="mb-2">
                                                <span class="badge bg-primary"><?php echo $aktivitas['nama_mapel']; ?></span>
                                                <span class="badge bg-success">Nilai: <?php echo $aktivitas['nilai']; ?></span>
                                            </div>
                                            <p class="text-muted small mb-0">
                                                <i class="bi bi-clock"></i> 
                                                <?php echo date('d M Y H:i', strtotime($aktivitas['tanggal_input'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-activity fs-1 text-muted"></i>
                                <p class="mt-2">Belum ada aktivitas terbaru</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add CSS for dashboard -->
<style>
    /* Dashboard Container */
    .dashboard-container {
        transition: all 0.3s ease;
        width: 100%;
    }
    
    /* Cards styling */
    .dashboard-card {
        transition: all 0.3s ease;
        border-radius: 0.5rem;
        overflow: hidden;
    }
    
    .dashboard-card .card-body {
        padding: 1.25rem;
    }
    
    .dashboard-card .card-footer {
        background-color: transparent;
        border-top: 1px solid rgba(0,0,0,.05);
        padding: 0.75rem 1.25rem;
    }
    
    .border-left-primary {
        border-left: 4px solid var(--primary-color);
    }
    
    .border-left-success {
        border-left: 4px solid #28a745;
    }
    
    .border-left-info {
        border-left: 4px solid #17a2b8;
    }
    
    .border-left-warning {
        border-left: 4px solid #ffc107;
    }
    
    .icon-circle {
        height: 3rem;
        width: 3rem;
        border-radius: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Chart container */
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
        transition: all 0.3s ease;
    }
    
    /* Jadwal styling */
    .jadwal-container {
        max-height: 300px;
        overflow-y: auto;
    }
    
    .time-column {
        width: 80px;
        background-color: rgba(0,0,0,.03);
        line-height: 1.2;
    }
    
    /* Timeline styling */
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        left: 9px;
        width: 2px;
        background-color: #e9ecef;
    }
    
    .timeline-item {
        position: relative;
        padding-bottom: 1.5rem;
    }
    
    .timeline-marker {
        position: absolute;
        left: -30px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background-color: var(--primary-color);
        box-shadow: 0 0 0 4px #fff;
    }
    
    .timeline-content {
        padding-left: 10px;
    }
    
    /* Responsive adjustments */
    @media (max-width: 767.98px) {
        .cards-container {
            margin-left: -0.5rem;
            margin-right: -0.5rem;
        }
        
        .cards-container > div {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
    }
    
    /* Animation for sidebar toggle */
    .cards-container.animate {
        animation: cardResize 0.3s ease-in-out;
    }
    
    @keyframes cardResize {
        0% { opacity: 0.8; transform: scale(0.98); }
        100% { opacity: 1; transform: scale(1); }
    }
    
    .date-display {
        background-color: rgba(var(--primary-color-rgb), 0.1);
        color: var(--primary-color);
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        font-weight: 500;
    }
    
    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    
    ::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: #999;
    }
</style>

<!-- Add JavaScript for charts and dashboard -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Data untuk chart siswa per kelas
    const kelasLabels = <?php echo json_encode(array_column($siswaPerkelas, 'nama_kelas')); ?>;
    const kelasSiswa = <?php echo json_encode(array_column($siswaPerkelas, 'jumlah_siswa')); ?>;
    
    // Inisialisasi chart
    const ctx = document.getElementById('siswaPerKelasChart').getContext('2d');
    const siswaChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: kelasLabels,
            datasets: [{
                label: 'Jumlah Siswa',
                data: kelasSiswa,
                backgroundColor: 'rgba(67, 97, 238, 0.7)',
                borderColor: 'rgba(67, 97, 238, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // Handler untuk toggle sidebar
    const sidebar = document.getElementById('sidebar');
    const cardsContainer = document.querySelector('.cards-container');
    const chartContainer = document.querySelector('.chart-container');
    
    // Function to update chart on sidebar toggle
    function updateDashboardLayout() {
        // Add animation to cards
        cardsContainer.classList.add('animate');
        
        // Resize chart after animation
        setTimeout(() => {
            if (siswaChart) {
                siswaChart.resize();
            }
            cardsContainer.classList.remove('animate');
        }, 300);
    }
    
    // Check for sidebar toggle event
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            setTimeout(updateDashboardLayout, 50);
        });
    }
    
    // Also update on window resize
    window.addEventListener('resize', updateDashboardLayout);
    
    // Observe sidebar class changes
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'class') {
                updateDashboardLayout();
            }
        });
    });
    
    if (sidebar) {
        observer.observe(sidebar, { attributes: true });
    }
});
</script>

<?php
// Load footer
include_once '../includes/footer.php';
?>

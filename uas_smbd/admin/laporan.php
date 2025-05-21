<?php
require_once '../config/init.php';

// Redirect to login page if not logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

// Set page title
$pageTitle = 'Laporan Akademik';

// Define BASEPATH
define('BASEPATH', true);

// Initialize variables
$message = '';
$error = '';
$siswa = [];
$kelas = [];
$nilai = [];
$mapel = [];
$kelas_info = [];
$tahun_ajaran = [];
$selected_kelas = '';
$selected_siswa = '';
$selected_semester = '';
$selected_tahun = '';
$report_type = '';


// Get all classes for dropdown
try {
    $stmt = $conn->query("SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas");
    $kelas = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Get unique academic years
try {
    $stmt = $conn->query("SELECT DISTINCT tahun_ajaran FROM kelas ORDER BY tahun_ajaran DESC");
    $tahun_ajaran = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Process form submission for generating report
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_type = sanitize($_POST['report_type']);
    
    if ($report_type === 'student') {
        // Student report (rapor)
        $selected_kelas = sanitize($_POST['id_kelas']);
        $selected_siswa = sanitize($_POST['id_siswa']);
        $selected_semester = sanitize($_POST['semester']);
        $selected_tahun = sanitize($_POST['tahun_ajaran']);
        
        try {
            // Get student information
            $stmt = $conn->prepare("
                SELECT s.*, k.nama_kelas 
                FROM siswa s
                LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
                WHERE s.id_siswa = ?
            ");
            $stmt->execute([$selected_siswa]);
            $siswa = $stmt->fetch();
            
            if (!$siswa) {
                $error = "Data siswa tidak ditemukan!";
            } else {
                // Get student grades
                $stmt = $conn->prepare("
                    SELECT n.*, m.nama_mapel, g.nama_guru
                    FROM nilai n
                    LEFT JOIN mata_pelajaran m ON n.id_mapel = m.id_mapel
                    LEFT JOIN guru g ON m.id_guru = g.id_guru
                    WHERE n.id_siswa = ? AND n.semester = ? AND n.tahun_ajaran = ?
                    ORDER BY m.nama_mapel
                ");
                $stmt->execute([$selected_siswa, $selected_semester, $selected_tahun]);
                $nilai = $stmt->fetchAll();
                
                // Calculate average
                $total_nilai = 0;
                $count_nilai = count($nilai);
                
                foreach ($nilai as $n) {
                    $total_nilai += $n['nilai'];
                }
                
                $rata_rata = $count_nilai > 0 ? $total_nilai / $count_nilai : 0;
            }
        } catch(PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    } elseif ($report_type === 'class') {
        // Class report
        $selected_kelas = sanitize($_POST['id_kelas']);
        $selected_semester = sanitize($_POST['semester']);
        $selected_tahun = sanitize($_POST['tahun_ajaran']);
        
        try {
            // Get class information
            $stmt = $conn->prepare("SELECT * FROM kelas WHERE id_kelas = ?");
            $stmt->execute([$selected_kelas]);
            $kelas_info = $stmt->fetch();
            
            // Get all students in the class with their average grade
            // $stmt = $conn->prepare("
            //     SELECT s.id_siswa, s.nis, s.nama_siswa, 
            //            AVG(n.nilai) as rata_rata, 
            //            COUNT(n.id_nilai) as jumlah_mapel,
            //            MAX(n.nilai) as nilai_tertinggi,
            //            MIN(n.nilai) as nilai_terendah
            //     FROM siswa s
            //     LEFT JOIN nilai n ON s.id_siswa = n.id_siswa AND n.semester = ? AND n.tahun_ajaran = ?
            //     WHERE s.id_kelas = ?
            //     GROUP BY s.id_siswa
            //     ORDER BY rata_rata DESC
            // ");
            // $stmt->execute([$selected_semester, $selected_tahun, $selected_kelas]);
            // $siswa = $stmt->fetchAll();

            // Gunakan view untuk mendapatkan rata-rata nilai siswa
            $stmt = $conn->prepare("
                SELECT * FROM view_nilai_rata_rata
                WHERE nama_kelas = (SELECT nama_kelas FROM kelas WHERE id_kelas = ?)
                AND semester = ? AND tahun_ajaran = ?
                ORDER BY rata_rata DESC
            ");
            $stmt->execute([$selected_kelas, $selected_semester, $selected_tahun]);
            $siswa = $stmt->fetchAll();
            
            // Get all subjects with class average
            $stmt = $conn->prepare("
                SELECT m.id_mapel, m.nama_mapel, g.nama_guru,
                       AVG(n.nilai) as rata_rata,
                       MAX(n.nilai) as nilai_tertinggi,
                       MIN(n.nilai) as nilai_terendah
                FROM mata_pelajaran m
                LEFT JOIN guru g ON m.id_guru = g.id_guru
                LEFT JOIN nilai n ON m.id_mapel = n.id_mapel AND n.semester = ? AND n.tahun_ajaran = ?
                LEFT JOIN siswa s ON n.id_siswa = s.id_siswa AND s.id_kelas = ?
                WHERE n.id_nilai IS NOT NULL
                GROUP BY m.id_mapel
                ORDER BY m.nama_mapel
            ");
            $stmt->execute([$selected_semester, $selected_tahun, $selected_kelas]);
            $mapel = $stmt->fetchAll();
            
            // Calculate class average
            $total_rata_rata = 0;
            $count_siswa = count($siswa);
            
            foreach ($siswa as $s) {
                if ($s['rata_rata']) {
                    $total_rata_rata += $s['rata_rata'];
                }
            }
            
            $rata_rata_kelas = $count_siswa > 0 ? $total_rata_rata / $count_siswa : 0;
        } catch(PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get students for dropdown when class is selected via AJAX
if (isset($_GET['get_students']) && !empty($_GET['id_kelas'])) {
    $id_kelas = sanitize($_GET['id_kelas']);
    
    try {
        $stmt = $conn->prepare("SELECT id_siswa, nama_siswa FROM siswa WHERE id_kelas = ? ORDER BY nama_siswa");
        $stmt->execute([$id_kelas]);
        $students = $stmt->fetchAll();
        
        header('Content-Type: application/json');
        echo json_encode($students);
        exit;
    } catch(PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4">Laporan Akademik</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Generate Laporan</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="report_type" id="report_student" value="student" <?php echo ($report_type === 'student') ? 'checked' : ''; ?> required>
                            <label class="form-check-label" for="report_student">Rapor Siswa</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="report_type" id="report_class" value="class" <?php echo ($report_type === 'class') ? 'checked' : ''; ?> required>
                            <label class="form-check-label" for="report_class">Rekap Nilai Kelas</label>
                        </div>
                    </div>
                </div>
                
                <div id="student_form" class="report-form" style="<?php echo ($report_type !== 'student') ? 'display: none;' : ''; ?>">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="id_kelas_siswa" class="form-label">Kelas</label>
                                <select class="form-select" id="id_kelas_siswa" name="id_kelas" required>
                                    <option value="">Pilih Kelas</option>
                                    <?php foreach ($kelas as $k): ?>
                                        <option value="<?php echo $k['id_kelas']; ?>" <?php echo ($selected_kelas == $k['id_kelas']) ? 'selected' : ''; ?>>
                                            <?php echo $k['nama_kelas']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="id_siswa" class="form-label">Siswa</label>
                                <select class="form-select" id="id_siswa" name="id_siswa" required>
                                    <option value="">Pilih Siswa</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="semester_siswa" class="form-label">Semester</label>
                                <select class="form-select" id="semester_siswa" name="semester" required>
                                    <option value="">Pilih Semester</option>
                                    <option value="1" <?php echo ($selected_semester === '1') ? 'selected' : ''; ?>>Semester 1</option>
                                    <option value="2" <?php echo ($selected_semester === '2') ? 'selected' : ''; ?>>Semester 2</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="tahun_ajaran_siswa" class="form-label">Tahun Ajaran</label>
                                <select class="form-select" id="tahun_ajaran_siswa" name="tahun_ajaran" required>
                                    <option value="">Pilih Tahun Ajaran</option>
                                    <?php foreach ($tahun_ajaran as $ta): ?>
                                        <option value="<?php echo $ta; ?>" <?php echo ($selected_tahun === $ta) ? 'selected' : ''; ?>>
                                            <?php echo $ta; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="class_form" class="report-form" style="<?php echo ($report_type !== 'class') ? 'display: none;' : ''; ?>">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="id_kelas" class="form-label">Kelas</label>
                                <select class="form-select" id="id_kelas" name="id_kelas" required>
                                    <option value="">Pilih Kelas</option>
                                    <?php foreach ($kelas as $k): ?>
                                        <option value="<?php echo $k['id_kelas']; ?>" <?php echo ($selected_kelas == $k['id_kelas']) ? 'selected' : ''; ?>>
                                            <?php echo $k['nama_kelas']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="semester_kelas" class="form-label">Semester</label>
                                <select class="form-select" id="semester_kelas" name="semester" required>
                                    <option value="">Pilih Semester</option>
                                    <option value="1" <?php echo ($selected_semester === '1') ? 'selected' : ''; ?>>Semester 1</option>
                                    <option value="2" <?php echo ($selected_semester === '2') ? 'selected' : ''; ?>>Semester 2</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="tahun_ajaran_kelas" class="form-label">Tahun Ajaran</label>
                                <select class="form-select" id="tahun_ajaran_kelas" name="tahun_ajaran" required>
                                    <option value="">Pilih Tahun Ajaran</option>
                                    <?php foreach ($tahun_ajaran as $ta): ?>
                                        <option value="<?php echo $ta; ?>" <?php echo ($selected_tahun === $ta) ? 'selected' : ''; ?>>
                                            <?php echo $ta; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-file-earmark-text"></i> Generate Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php if ($report_type === 'student' && !empty($siswa) && !empty($nilai)): ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Rapor Siswa</h6>
                <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Cetak
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td width="150">Nama Siswa</td>
                                <td width="10">:</td>
                                <td><strong><?php echo $siswa['nama_siswa']; ?></strong></td>
                            </tr>
                            <tr>
                                <td>NIS</td>
                                <td>:</td>
                                <td><?php echo $siswa['nis']; ?></td>
                            </tr>
                            <tr>
                                <td>Kelas</td>
                                <td>:</td>
                                <td><?php echo $siswa['nama_kelas']; ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td width="150">Semester</td>
                                <td width="10">:</td>
                                <td><?php echo $selected_semester; ?></td>
                            </tr>
                            <tr>
                                <td>Tahun Ajaran</td>
                                <td>:</td>
                                <td><?php echo $selected_tahun; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Mata Pelajaran</th>
                                <th>Guru Pengampu</th>
                                <th>Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($nilai)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">Tidak ada data nilai</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; ?>
                                <?php foreach ($nilai as $n): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo $n['nama_mapel']; ?></td>
                                        <td><?php echo $n['nama_guru']; ?></td>
                                        <td><?php echo $n['nilai']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Rata-rata</strong></td>
                                    <td><strong><?php echo number_format($rata_rata, 2); ?></strong></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php elseif ($report_type === 'class' && !empty($siswa)): ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Rekap Nilai Kelas</h6>
                <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Cetak
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td width="150">Kelas</td>
                                <td width="10">:</td>
                                <td><strong><?php echo $kelas_info['nama_kelas']; ?></strong></td>
                            </tr>
                            <tr>
                                <td>Tahun Ajaran</td>
                                <td>:</td>
                                <td><?php echo $kelas_info['tahun_ajaran']; ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td width="150">Semester</td>
                                <td width="10">:</td>
                                <td><?php echo $selected_semester; ?></td>
                            </tr>
                            <tr>
                                <td>Rata-rata Kelas</td>
                                <td>:</td>
                                <td><strong><?php echo number_format($rata_rata_kelas, 2); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <h5 class="mb-3">Daftar Nilai Siswa</h5>
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th>Jumlah Mata Pelajaran</th>
                                <th>Nilai Tertinggi</th>
                                <th>Nilai Terendah</th>
                                <th>Rata-rata</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($siswa)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada data siswa</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; ?>
                                <?php foreach ($siswa as $s): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo $s['nis']; ?></td>
                                        <td><?php echo $s['nama_siswa']; ?></td>
                                        <td><?php echo $s['jumlah_mapel']; ?></td>
                                        <td><?php echo $s['nilai_tertinggi'] ? number_format($s['nilai_tertinggi'], 2) : '-'; ?></td>
                                        <td><?php echo $s['nilai_terendah'] ? number_format($s['nilai_terendah'], 2) : '-'; ?></td>
                                        <td><?php echo $s['rata_rata'] ? number_format($s['rata_rata'], 2) : '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <h5 class="mb-3 mt-4">Rekap Per Mata Pelajaran</h5>
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Mata Pelajaran</th>
                                <th>Guru Pengampu</th>
                                <th>Nilai Tertinggi</th>
                                <th>Nilai Terendah</th>
                                <th>Rata-rata</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mapel)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada data mata pelajaran</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; ?>
                                <?php foreach ($mapel as $m): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo $m['nama_mapel']; ?></td>
                                        <td><?php echo $m['nama_guru']; ?></td>
                                        <td><?php echo $m['nilai_tertinggi'] ? number_format($m['nilai_tertinggi'], 2) : '-'; ?></td>
                                        <td><?php echo $m['nilai_terendah'] ? number_format($m['nilai_terendah'], 2) : '-'; ?></td>
                                        <td><?php echo $m['rata_rata'] ? number_format($m['rata_rata'], 2) : '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    // Toggle report forms based on selected report type
    document.addEventListener('DOMContentLoaded', function() {
        const reportTypeRadios = document.querySelectorAll('input[name="report_type"]');
        const studentForm = document.getElementById('student_form');
        const classForm = document.getElementById('class_form');
        
        reportTypeRadios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                if (this.value === 'student') {
                    studentForm.style.display = 'block';
                    classForm.style.display = 'none';
                } else if (this.value === 'class') {
                    studentForm.style.display = 'none';
                    classForm.style.display = 'block';
                }
            });
        });
        
        // Load students when class is selected
        const kelasSelect = document.getElementById('id_kelas_siswa');
        const siswaSelect = document.getElementById('id_siswa');
        
        // Set siswa value to selected if available (for edit after submit)
        <?php if ($selected_kelas && $report_type === 'student'): ?>
        fetch(`laporan.php?get_students=1&id_kelas=<?php echo $selected_kelas; ?>`)
            .then(response => response.json())
            .then(data => {
                siswaSelect.innerHTML = '<option value="">Pilih Siswa</option>';
                data.forEach(function(student) {
                    const option = document.createElement('option');
                    option.value = student.id_siswa;
                    option.textContent = student.nama_siswa;
                    if (student.id_siswa == "<?php echo $selected_siswa; ?>") {
                        option.selected = true;
                    }
                    siswaSelect.appendChild(option);
                });
            });
        <?php endif; ?>

        kelasSelect.addEventListener('change', function() {
            const kelasId = this.value;
            
            if (kelasId) {
                fetch(`laporan.php?get_students=1&id_kelas=${kelasId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        siswaSelect.innerHTML = '<option value="">Pilih Siswa</option>';
                        
                        data.forEach(function(student) {
                            const option = document.createElement('option');
                            option.value = student.id_siswa;
                            option.textContent = student.nama_siswa;
                            siswaSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching students:', error);
                        alert('Terjadi kesalahan saat mengambil data siswa. Silakan coba lagi.');
                    });
            } else {
                siswaSelect.innerHTML = '<option value="">Pilih Siswa</option>';
            }
        });
        
        // Set required attributes based on report type
        function updateRequiredFields() {
            const reportType = document.querySelector('input[name="report_type"]:checked')?.value;
            
            if (reportType === 'student') {
                document.getElementById('id_kelas_siswa').setAttribute('required', '');
                document.getElementById('id_siswa').setAttribute('required', '');
                document.getElementById('semester_siswa').setAttribute('required', '');
                document.getElementById('tahun_ajaran_siswa').setAttribute('required', '');
                
                document.getElementById('id_kelas').removeAttribute('required');
                document.getElementById('semester_kelas').removeAttribute('required');
                document.getElementById('tahun_ajaran_kelas').removeAttribute('required');
            } else if (reportType === 'class') {
                document.getElementById('id_kelas').setAttribute('required', '');
                document.getElementById('semester_kelas').setAttribute('required', '');
                document.getElementById('tahun_ajaran_kelas').setAttribute('required', '');
                
                document.getElementById('id_kelas_siswa').removeAttribute('required');
                document.getElementById('id_siswa').removeAttribute('required');
                document.getElementById('semester_siswa').removeAttribute('required');
                document.getElementById('tahun_ajaran_siswa').removeAttribute('required');
            }
        }
        
        // Update required fields on page load and when report type changes
        updateRequiredFields();
        reportTypeRadios.forEach(radio => {
            radio.addEventListener('change', updateRequiredFields);
        });
    });
</script>



<?php
// Include footer
include_once '../includes/footer.php';
<?php
require_once '../config/init.php';

// Redirect to login page if not logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

// Set page title
$pageTitle = 'Manajemen Nilai';

// Define BASEPATH
define('BASEPATH', true);

// Initialize variables
$message = '';
$error = '';
$nilai = [];
$siswa = [];
$mapel = [];
$kelas = [];
$selected_kelas = '';
$selected_mapel = '';
$selected_semester = '';
$selected_tahun = '';

// Get all classes for dropdown
try {
    $stmt = $conn->query("SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas");
    $kelas = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Get all subjects for dropdown
try {
    $stmt = $conn->query("SELECT id_mapel, nama_mapel FROM mata_pelajaran ORDER BY nama_mapel");
    $mapel = $stmt->fetchAll();
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

// Process form submission for adding/editing grade
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'filter') {
        // Filter students by class
        $selected_kelas = sanitize($_POST['id_kelas']);
        $selected_mapel = sanitize($_POST['id_mapel']);
        $selected_semester = sanitize($_POST['semester']);
        $selected_tahun = sanitize($_POST['tahun_ajaran']);
        
        try {
            $stmt = $conn->prepare("
                SELECT s.id_siswa, s.nis, s.nama_siswa, n.id_nilai, n.nilai
                FROM siswa s
                LEFT JOIN nilai n ON s.id_siswa = n.id_siswa AND n.id_mapel = ? AND n.semester = ? AND n.tahun_ajaran = ?
                WHERE s.id_kelas = ?
                ORDER BY s.nama_siswa
            ");
            $stmt->execute([$selected_mapel, $selected_semester, $selected_tahun, $selected_kelas]);
            $siswa = $stmt->fetchAll();
        } catch(PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    } elseif ($action === 'save') {
        // Save grades for multiple students
        $id_mapel = sanitize($_POST['id_mapel']);
        $semester = sanitize($_POST['semester']);
        $tahun_ajaran = sanitize($_POST['tahun_ajaran']);
        $student_ids = $_POST['student_id'];
        $nilai_values = $_POST['nilai'];
        $id_nilai_values = isset($_POST['id_nilai']) ? $_POST['id_nilai'] : [];
        
        try {
            $conn->beginTransaction();
            
            foreach ($student_ids as $key => $id_siswa) {
                $nilai_value = sanitize($nilai_values[$key]);
                
                // Validate grade value
                if ($nilai_value !== '' && ($nilai_value < 0 || $nilai_value > 100)) {
                    throw new Exception("Nilai harus berada dalam rentang 0-100");
                }
                
                if (isset($id_nilai_values[$key]) && !empty($id_nilai_values[$key])) {
                    // Update existing grade
                    $id_nilai = sanitize($id_nilai_values[$key]);
                    
                    if ($nilai_value === '') {
                        // Delete grade if value is empty
                        $stmt = $conn->prepare("DELETE FROM nilai WHERE id_nilai = ?");
                        $stmt->execute([$id_nilai]);
                    } else {
                        // Update grade
                        $stmt = $conn->prepare("UPDATE nilai SET nilai = ? WHERE id_nilai = ?");
                        $stmt->execute([$nilai_value, $id_nilai]);
                    }
                } elseif ($nilai_value !== '') {
                    // Insert new grade
                    $stmt = $conn->prepare("
                        INSERT INTO nilai (id_siswa, id_mapel, nilai, semester, tahun_ajaran) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$id_siswa, $id_mapel, $nilai_value, $semester, $tahun_ajaran]);
                }
            }
            
            $conn->commit();
            $message = "Nilai berhasil disimpan!";
            
            // Refresh student data
            $stmt = $conn->prepare("
                SELECT s.id_siswa, s.nis, s.nama_siswa, n.id_nilai, n.nilai
                FROM siswa s
                LEFT JOIN nilai n ON s.id_siswa = n.id_siswa AND n.id_mapel = ? AND n.semester = ? AND n.tahun_ajaran = ?
                WHERE s.id_kelas = ?
                ORDER BY s.nama_siswa
            ");
            $stmt->execute([$id_mapel, $semester, $tahun_ajaran, $_POST['id_kelas']]);
            $siswa = $stmt->fetchAll();
            
            // Keep selected filters
            $selected_kelas = sanitize($_POST['id_kelas']);
            $selected_mapel = $id_mapel;
            $selected_semester = $semester;
            $selected_tahun = $tahun_ajaran;
        } catch(Exception $e) {
            $conn->rollBack();
            $error = "Error: " . $e->getMessage();
            
            // Keep selected filters
            $selected_kelas = sanitize($_POST['id_kelas']);
            $selected_mapel = $id_mapel;
            $selected_semester = $semester;
            $selected_tahun = $tahun_ajaran;
        }
    }
}

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4">Manajemen Nilai</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Siswa</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="filter">
                
                <div class="row">
                    <div class="col-md-3">
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
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="id_mapel" class="form-label">Mata Pelajaran</label>
                            <select class="form-select" id="id_mapel" name="id_mapel" required>
                                <option value="">Pilih Mata Pelajaran</option>
                                <?php foreach ($mapel as $m): ?>
                                    <option value="<?php echo $m['id_mapel']; ?>" <?php echo ($selected_mapel == $m['id_mapel']) ? 'selected' : ''; ?>>
                                        <?php echo $m['nama_mapel']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="semester" class="form-label">Semester</label>
                            <select class="form-select" id="semester" name="semester" required>
                                <option value="">Pilih Semester</option>
                                <option value="1" <?php echo ($selected_semester === '1') ? 'selected' : ''; ?>>Semester 1</option>
                                <option value="2" <?php echo ($selected_semester === '2') ? 'selected' : ''; ?>>Semester 2</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="tahun_ajaran" class="form-label">Tahun Ajaran</label>
                            <select class="form-select" id="tahun_ajaran" name="tahun_ajaran" required>
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
                
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php if (!empty($siswa)): ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Input Nilai</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id_kelas" value="<?php echo $selected_kelas; ?>">
                    <input type="hidden" name="id_mapel" value="<?php echo $selected_mapel; ?>">
                    <input type="hidden" name="semester" value="<?php echo $selected_semester; ?>">
                    <input type="hidden" name="tahun_ajaran" value="<?php echo $selected_tahun; ?>">
                    
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>NIS</th>
                                    <th>Nama Siswa</th>
                                    <th>Nilai</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($siswa as $s): ?>
                                    <tr>
                                        <td><?php echo $s['nis']; ?></td>
                                        <td><?php echo $s['nama_siswa']; ?></td>
                                        <td>
                                            <input type="hidden" name="student_id[]" value="<?php echo $s['id_siswa']; ?>">
                                            <?php if (isset($s['id_nilai']) && $s['id_nilai']): ?>
                                                <input type="hidden" name="id_nilai[<?php echo $s['id_siswa']; ?>]" value="<?php echo $s['id_nilai']; ?>">
                                            <?php endif; ?>
                                            <input type="number" class="form-control" name="nilai[]" min="0" max="100" step="0.01" value="<?php echo isset($s['nilai']) ? $s['nilai'] : ''; ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan Nilai
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include_once '../includes/footer.php';
?>

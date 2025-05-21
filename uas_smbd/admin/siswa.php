<?php
require_once '../config/init.php';

// Redirect to login page if not logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

// Set page title
$pageTitle = 'Manajemen Siswa';

// Define BASEPATH
define('BASEPATH', true);

// Initialize variables
$message = '';
$error = '';
$siswa = [];
$kelas = [];

// Get all classes for dropdown
try {
    $stmt = $conn->query("SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas");
    $kelas = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Process form submission for adding/editing student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Sanitize input
    $nis = sanitize($_POST['nis']);
    $nama_siswa = sanitize($_POST['nama_siswa']);
    $tanggal_lahir = sanitize($_POST['tanggal_lahir']);
    $jenis_kelamin = sanitize($_POST['jenis_kelamin']);
    $alamat = sanitize($_POST['alamat']);
    $id_kelas = sanitize($_POST['id_kelas']);
    
    try {
        if ($action === 'add') {
            // Check if NIS already exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM siswa WHERE nis = ?");
            $stmt->execute([$nis]);
            if ($stmt->fetchColumn() > 0) {
                $error = "NIS sudah terdaftar!";
            } else {
                // Insert new student
                $stmt = $conn->prepare("
                    INSERT INTO siswa (nis, nama_siswa, tanggal_lahir, jenis_kelamin, alamat, id_kelas) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nis, $nama_siswa, $tanggal_lahir, $jenis_kelamin, $alamat, $id_kelas]);
                $message = "Siswa berhasil ditambahkan!";
            }
        } elseif ($action === 'edit') {
            $id_siswa = sanitize($_POST['id_siswa']);
            
            // Update student
            $stmt = $conn->prepare("
                UPDATE siswa 
                SET nis = ?, nama_siswa = ?, tanggal_lahir = ?, jenis_kelamin = ?, alamat = ?, id_kelas = ? 
                WHERE id_siswa = ?
            ");
            $stmt->execute([$nis, $nama_siswa, $tanggal_lahir, $jenis_kelamin, $alamat, $id_kelas, $id_siswa]);
            $message = "Data siswa berhasil diperbarui!";
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Process delete student
if (isset($_GET['delete'])) {
    $id_siswa = sanitize($_GET['delete']);
    
    try {
        // Check if student has grades
        $stmt = $conn->prepare("SELECT COUNT(*) FROM nilai WHERE id_siswa = ?");
        $stmt->execute([$id_siswa]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Tidak dapat menghapus siswa karena masih memiliki data nilai!";
        } else {
            // Delete student
            $stmt = $conn->prepare("DELETE FROM siswa WHERE id_siswa = ?");
            $stmt->execute([$id_siswa]);
            $message = "Siswa berhasil dihapus!";
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get student data for editing
if (isset($_GET['edit'])) {
    $id_siswa = sanitize($_GET['edit']);
    
    try {
        $stmt = $conn->prepare("SELECT * FROM siswa WHERE id_siswa = ?");
        $stmt->execute([$id_siswa]);
        $siswa = $stmt->fetch();
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all students with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

try {
    // Count total students for pagination
    if (!empty($search)) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM siswa s
            LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
            WHERE s.nis LIKE ? OR s.nama_siswa LIKE ? OR k.nama_kelas LIKE ?
        ");
        $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    } else {
        $stmt = $conn->query("SELECT COUNT(*) FROM siswa");
    }
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);
    
    // Get students with class information
    if (!empty($search)) {
        $stmt = $conn->prepare("
            SELECT s.*, k.nama_kelas 
            FROM siswa s
            LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
            WHERE s.nis LIKE ? OR s.nama_siswa LIKE ? OR k.nama_kelas LIKE ?
            ORDER BY s.nama_siswa
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(2, "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(3, "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(4, $limit, PDO::PARAM_INT);
        $stmt->bindValue(5, $offset, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("
            SELECT s.*, k.nama_kelas 
            FROM siswa s
            LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
            ORDER BY s.nama_siswa
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
    }
    $students = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4">Manajemen Siswa</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <?php echo !empty($siswa) ? 'Edit Siswa' : 'Tambah Siswa Baru'; ?>
            </h6>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?php echo !empty($siswa) ? 'edit' : 'add'; ?>">
                <?php if (!empty($siswa)): ?>
                    <input type="hidden" name="id_siswa" value="<?php echo $siswa['id_siswa']; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nis" class="form-label">NIS</label>
                            <input type="text" class="form-control" id="nis" name="nis" value="<?php echo !empty($siswa) ? $siswa['nis'] : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="nama_siswa" class="form-label">Nama Siswa</label>
                            <input type="text" class="form-control" id="nama_siswa" name="nama_siswa" value="<?php echo !empty($siswa) ? $siswa['nama_siswa'] : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
                            <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" value="<?php echo !empty($siswa) ? $siswa['tanggal_lahir'] : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
                            <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="L" <?php echo (!empty($siswa) && $siswa['jenis_kelamin'] === 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                                <option value="P" <?php echo (!empty($siswa) && $siswa['jenis_kelamin'] === 'P') ? 'selected' : ''; ?>>Perempuan</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_kelas" class="form-label">Kelas</label>
                            <select class="form-select" id="id_kelas" name="id_kelas" required>
                                <option value="">Pilih Kelas</option>
                                <?php foreach ($kelas as $k): ?>
                                    <option value="<?php echo $k['id_kelas']; ?>" <?php echo (!empty($siswa) && $siswa['id_kelas'] == $k['id_kelas']) ? 'selected' : ''; ?>>
                                        <?php echo $k['nama_kelas']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="alamat" class="form-label">Alamat</label>
                            <textarea class="form-control" id="alamat" name="alamat" rows="3" required><?php echo !empty($siswa) ? $siswa['alamat'] : ''; ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <?php echo !empty($siswa) ? 'Update Siswa' : 'Tambah Siswa'; ?>
                    </button>
                    <?php if (!empty($siswa)): ?>
                        <a href="siswa.php" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Siswa</h6>
            <form class="d-flex" method="GET" action="">
                <input class="form-control me-2" type="search" placeholder="Cari siswa..." name="search" value="<?php echo $search; ?>">
                <button class="btn btn-outline-primary" type="submit">Cari</button>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>Tanggal Lahir</th>
                            <th>Jenis Kelamin</th>
                            <th>Kelas</th>
                            <th>Alamat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Tidak ada data siswa</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo $student['nis']; ?></td>
                                    <td><?php echo $student['nama_siswa']; ?></td>
                                    <td><?php echo date('d-m-Y', strtotime($student['tanggal_lahir'])); ?></td>
                                    <td><?php echo ($student['jenis_kelamin'] === 'L') ? 'Laki-laki' : 'Perempuan'; ?></td>
                                    <td><?php echo $student['nama_kelas']; ?></td>
                                    <td><?php echo $student['alamat']; ?></td>
                                    <td>
                                        <a href="siswa.php?edit=<?php echo $student['id_siswa']; ?>" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="siswa.php?delete=<?php echo $student['id_siswa']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus siswa ini?')">
                                            <i class="bi bi-trash"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . $search : ''; ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . $search : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . $search : ''; ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../includes/footer.php';
?>

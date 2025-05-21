<?php
require_once '../config/init.php';

// Redirect to login page if not logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

// Set page title
$pageTitle = 'Manajemen Kelas';

// Define BASEPATH
define('BASEPATH', true);

// Initialize variables
$message = '';
$error = '';
$kelas = [];

// Process form submission for adding/editing class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Sanitize input
    $nama_kelas = sanitize($_POST['nama_kelas']);
    $tahun_ajaran = sanitize($_POST['tahun_ajaran']);
    
    try {
        if ($action === 'add') {
            // Check if class already exists for the academic year
            $stmt = $conn->prepare("SELECT COUNT(*) FROM kelas WHERE nama_kelas = ? AND tahun_ajaran = ?");
            $stmt->execute([$nama_kelas, $tahun_ajaran]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Kelas sudah ada untuk tahun ajaran tersebut!";
            } else {
                // Insert new class
                $stmt = $conn->prepare("INSERT INTO kelas (nama_kelas, tahun_ajaran) VALUES (?, ?)");
                $stmt->execute([$nama_kelas, $tahun_ajaran]);
                $message = "Kelas berhasil ditambahkan!";
            }
        } elseif ($action === 'edit') {
            $id_kelas = sanitize($_POST['id_kelas']);
            
            // Check if class already exists for the academic year (excluding current class)
            $stmt = $conn->prepare("SELECT COUNT(*) FROM kelas WHERE nama_kelas = ? AND tahun_ajaran = ? AND id_kelas != ?");
            $stmt->execute([$nama_kelas, $tahun_ajaran, $id_kelas]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Kelas sudah ada untuk tahun ajaran tersebut!";
            } else {
                // Update class
                $stmt = $conn->prepare("UPDATE kelas SET nama_kelas = ?, tahun_ajaran = ? WHERE id_kelas = ?");
                $stmt->execute([$nama_kelas, $tahun_ajaran, $id_kelas]);
                $message = "Data kelas berhasil diperbarui!";
            }
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Process delete class
if (isset($_GET['delete'])) {
    $id_kelas = sanitize($_GET['delete']);
    
    try {
        // Check if class has students
        $stmt = $conn->prepare("SELECT COUNT(*) FROM siswa WHERE id_kelas = ?");
        $stmt->execute([$id_kelas]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Tidak dapat menghapus kelas karena masih memiliki siswa!";
        } else {
            // Check if class has schedules
            $stmt = $conn->prepare("SELECT COUNT(*) FROM jadwal WHERE id_kelas = ?");
            $stmt->execute([$id_kelas]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Tidak dapat menghapus kelas karena masih terdaftar dalam jadwal!";
            } else {
                // Delete class
                $stmt = $conn->prepare("DELETE FROM kelas WHERE id_kelas = ?");
                $stmt->execute([$id_kelas]);
                $message = "Kelas berhasil dihapus!";
            }
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get class data for editing
if (isset($_GET['edit'])) {
    $id_kelas = sanitize($_GET['edit']);
    
    try {
        $stmt = $conn->prepare("SELECT * FROM kelas WHERE id_kelas = ?");
        $stmt->execute([$id_kelas]);
        $kelas = $stmt->fetch();
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all classes with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

try {
    // Count total classes for pagination
    if (!empty($search)) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM kelas
            WHERE nama_kelas LIKE ? OR tahun_ajaran LIKE ?
        ");
        $stmt->execute(["%$search%", "%$search%"]);
    } else {
        $stmt = $conn->query("SELECT COUNT(*) FROM kelas");
    }
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);
    
    // Get classes with student count
    if (!empty($search)) {
        $stmt = $conn->prepare("
        SELECT k.*, COUNT(s.id_siswa) as jumlah_siswa 
        FROM kelas k
        LEFT JOIN siswa s ON k.id_kelas = s.id_kelas
        WHERE k.nama_kelas LIKE ? OR k.tahun_ajaran LIKE ?
        GROUP BY k.id_kelas
        ORDER BY k.tahun_ajaran DESC, k.nama_kelas
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(2, "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(3, $limit, PDO::PARAM_INT);
    $stmt->bindValue(4, $offset, PDO::PARAM_INT);
    $stmt->execute();
    } else {
        $stmt = $conn->prepare("
        SELECT k.*, COUNT(s.id_siswa) as jumlah_siswa 
        FROM kelas k
        LEFT JOIN siswa s ON k.id_kelas = s.id_kelas
        GROUP BY k.id_kelas
        ORDER BY k.tahun_ajaran DESC, k.nama_kelas
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    }
    $classes = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4">Manajemen Kelas</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <?php echo !empty($kelas) ? 'Edit Kelas' : 'Tambah Kelas Baru'; ?>
            </h6>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?php echo !empty($kelas) ? 'edit' : 'add'; ?>">
                <?php if (!empty($kelas)): ?>
                    <input type="hidden" name="id_kelas" value="<?php echo $kelas['id_kelas']; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nama_kelas" class="form-label">Nama Kelas</label>
                            <input type="text" class="form-control" id="nama_kelas" name="nama_kelas" value="<?php echo !empty($kelas) ? $kelas['nama_kelas'] : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tahun_ajaran" class="form-label">Tahun Ajaran</label>
                            <input type="text" class="form-control" id="tahun_ajaran" name="tahun_ajaran" placeholder="contoh: 2023/2024" value="<?php echo !empty($kelas) ? $kelas['tahun_ajaran'] : ''; ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <?php echo !empty($kelas) ? 'Update Kelas' : 'Tambah Kelas'; ?>
                    </button>
                    <?php if (!empty($kelas)): ?>
                        <a href="kelas.php" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Kelas</h6>
            <form class="d-flex" method="GET" action="">
                <input class="form-control me-2" type="search" placeholder="Cari kelas..." name="search" value="<?php echo $search; ?>">
                <button class="btn btn-outline-primary" type="submit">Cari</button>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nama Kelas</th>
                            <th>Tahun Ajaran</th>
                            <th>Jumlah Siswa</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($classes)): ?>
                            <tr>
                                <td colspan="4" class="text-center">Tidak ada data kelas</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($classes as $class): ?>
                                <tr>
                                    <td><?php echo $class['nama_kelas']; ?></td>
                                    <td><?php echo $class['tahun_ajaran']; ?></td>
                                    <td><?php echo $class['jumlah_siswa']; ?></td>
                                    <td>
                                        <a href="kelas.php?edit=<?php echo $class['id_kelas']; ?>" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="kelas.php?delete=<?php echo $class['id_kelas']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus kelas ini?')">
                                            <i class="bi bi-trash"></i> Hapus
                                        </a>
                                        <a href="siswa.php?kelas=<?php echo $class['id_kelas']; ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-people"></i> Lihat Siswa
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

<?php
require_once '../config/init.php';

// Redirect to login page if not logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

// Set page title
$pageTitle = 'Manajemen Guru';

// Define BASEPATH
define('BASEPATH', true);

// Initialize variables
$message = '';
$error = '';
$guru = [];

// Process form submission for adding/editing teacher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Sanitize input
    $nip = sanitize($_POST['nip']);
    $nama_guru = sanitize($_POST['nama_guru']);
    $tanggal_lahir = sanitize($_POST['tanggal_lahir']);
    $jenis_kelamin = sanitize($_POST['jenis_kelamin']);
    $alamat = sanitize($_POST['alamat']);
    $no_telepon = sanitize($_POST['no_telepon']);
    
    try {
        if ($action === 'add') {
            // Check if NIP already exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM guru WHERE nip = ?");
            $stmt->execute([$nip]);
            if ($stmt->fetchColumn() > 0) {
                $error = "NIP sudah terdaftar!";
            } else {
                // Insert new teacher
                $stmt = $conn->prepare("
                    INSERT INTO guru (nip, nama_guru, tanggal_lahir, jenis_kelamin, alamat, no_telepon) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nip, $nama_guru, $tanggal_lahir, $jenis_kelamin, $alamat, $no_telepon]);
                $message = "Guru berhasil ditambahkan!";
            }
        } elseif ($action === 'edit') {
            $id_guru = sanitize($_POST['id_guru']);
            
            // Update teacher
            $stmt = $conn->prepare("
                UPDATE guru 
                SET nip = ?, nama_guru = ?, tanggal_lahir = ?, jenis_kelamin = ?, alamat = ?, no_telepon = ? 
                WHERE id_guru = ?
            ");
            $stmt->execute([$nip, $nama_guru, $tanggal_lahir, $jenis_kelamin, $alamat, $no_telepon, $id_guru]);
            $message = "Data guru berhasil diperbarui!";
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Process delete teacher
if (isset($_GET['delete'])) {
    $id_guru = sanitize($_GET['delete']);
    
    try {
        // Check if teacher is assigned to subjects
        $stmt = $conn->prepare("SELECT COUNT(*) FROM mata_pelajaran WHERE id_guru = ?");
        $stmt->execute([$id_guru]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Tidak dapat menghapus guru karena masih mengampu mata pelajaran!";
        } else {
            // Check if teacher is assigned to schedules
            $stmt = $conn->prepare("SELECT COUNT(*) FROM jadwal WHERE id_guru = ?");
            $stmt->execute([$id_guru]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Tidak dapat menghapus guru karena masih terdaftar dalam jadwal!";
            } else {
                // Delete teacher
                $stmt = $conn->prepare("DELETE FROM guru WHERE id_guru = ?");
                $stmt->execute([$id_guru]);
                $message = "Guru berhasil dihapus!";
            }
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get teacher data for editing
if (isset($_GET['edit'])) {
    $id_guru = sanitize($_GET['edit']);
    
    try {
        $stmt = $conn->prepare("SELECT * FROM guru WHERE id_guru = ?");
        $stmt->execute([$id_guru]);
        $guru = $stmt->fetch();
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all teachers with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

try {
    // Count total teachers for pagination
    if (!empty($search)) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM guru
            WHERE nip LIKE ? OR nama_guru LIKE ? OR no_telepon LIKE ?
        ");
        $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    } else {
        $stmt = $conn->query("SELECT COUNT(*) FROM guru");
    }
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);
    
    // Get teachers
    if (!empty($search)) {
        $stmt = $conn->prepare("
            SELECT * FROM guru
            WHERE nip LIKE ? OR nama_guru LIKE ? OR no_telepon LIKE ?
            ORDER BY nama_guru
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
            SELECT * FROM guru
            ORDER BY nama_guru
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
    }
    $teachers = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4">Manajemen Guru</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <?php echo !empty($guru) ? 'Edit Guru' : 'Tambah Guru Baru'; ?>
            </h6>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?php echo !empty($guru) ? 'edit' : 'add'; ?>">
                <?php if (!empty($guru)): ?>
                    <input type="hidden" name="id_guru" value="<?php echo $guru['id_guru']; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nip" class="form-label">NIP</label>
                            <input type="text" class="form-control" id="nip" name="nip" value="<?php echo !empty($guru) ? $guru['nip'] : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="nama_guru" class="form-label">Nama Guru</label>
                            <input type="text" class="form-control" id="nama_guru" name="nama_guru" value="<?php echo !empty($guru) ? $guru['nama_guru'] : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
                            <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" value="<?php echo !empty($guru) ? $guru['tanggal_lahir'] : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
                            <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="L" <?php echo (!empty($guru) && $guru['jenis_kelamin'] === 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                                <option value="P" <?php echo (!empty($guru) && $guru['jenis_kelamin'] === 'P') ? 'selected' : ''; ?>>Perempuan</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="no_telepon" class="form-label">No. Telepon</label>
                            <input type="text" class="form-control" id="no_telepon" name="no_telepon" value="<?php echo !empty($guru) ? $guru['no_telepon'] : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="alamat" class="form-label">Alamat</label>
                            <textarea class="form-control" id="alamat" name="alamat" rows="3" required><?php echo !empty($guru) ? $guru['alamat'] : ''; ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <?php echo !empty($guru) ? 'Update Guru' : 'Tambah Guru'; ?>
                    </button>
                    <?php if (!empty($guru)): ?>
                        <a href="guru.php" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Guru</h6>
            <form class="d-flex" method="GET" action="">
                <input class="form-control me-2" type="search" placeholder="Cari guru..." name="search" value="<?php echo $search; ?>">
                <button class="btn btn-outline-primary" type="submit">Cari</button>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>NIP</th>
                            <th>Nama</th>
                            <th>Tanggal Lahir</th>
                            <th>Jenis Kelamin</th>
                            <th>No. Telepon</th>
                            <th>Alamat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($teachers)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Tidak ada data guru</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($teachers as $teacher): ?>
                                <tr>
                                    <td><?php echo $teacher['nip']; ?></td>
                                    <td><?php echo $teacher['nama_guru']; ?></td>
                                    <td><?php echo date('d-m-Y', strtotime($teacher['tanggal_lahir'])); ?></td>
                                    <td><?php echo ($teacher['jenis_kelamin'] === 'L') ? 'Laki-laki' : 'Perempuan'; ?></td>
                                    <td><?php echo $teacher['no_telepon']; ?></td>
                                    <td><?php echo $teacher['alamat']; ?></td>
                                    <td>
                                        <a href="guru.php?edit=<?php echo $teacher['id_guru']; ?>" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="guru.php?delete=<?php echo $teacher['id_guru']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus guru ini?')">
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

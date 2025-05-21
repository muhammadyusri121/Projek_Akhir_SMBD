<?php
require_once '../config/init.php';

// Redirect to login page if not logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

// Set page title
$pageTitle = 'Manajemen Mata Pelajaran';

// Define BASEPATH
define('BASEPATH', true);

// Initialize variables
$message = '';
$error = '';
$mapel = [];
$guru = [];

// Get all teachers for dropdown
try {
    $stmt = $conn->query("SELECT id_guru, nama_guru FROM guru ORDER BY nama_guru");
    $guru = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Process form submission for adding/editing subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Sanitize input
    $nama_mapel = sanitize($_POST['nama_mapel']);
    $deskripsi = sanitize($_POST['deskripsi']);
    $id_guru = sanitize($_POST['id_guru']);
    
    try {
        if ($action === 'add') {
            // Check if subject already exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM mata_pelajaran WHERE nama_mapel = ?");
            $stmt->execute([$nama_mapel]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Mata pelajaran sudah ada!";
            } else {
                // Insert new subject
                $stmt = $conn->prepare("INSERT INTO mata_pelajaran (nama_mapel, deskripsi, id_guru) VALUES (?, ?, ?)");
                $stmt->execute([$nama_mapel, $deskripsi, $id_guru]);
                $message = "Mata pelajaran berhasil ditambahkan!";
            }
        } elseif ($action === 'edit') {
            $id_mapel = sanitize($_POST['id_mapel']);
            
            // Update subject
            $stmt = $conn->prepare("UPDATE mata_pelajaran SET nama_mapel = ?, deskripsi = ?, id_guru = ? WHERE id_mapel = ?");
            $stmt->execute([$nama_mapel, $deskripsi, $id_guru, $id_mapel]);
            $message = "Data mata pelajaran berhasil diperbarui!";
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Process delete subject
if (isset($_GET['delete'])) {
    $id_mapel = sanitize($_GET['delete']);
    
    try {
        // Check if subject has schedules
        $stmt = $conn->prepare("SELECT COUNT(*) FROM jadwal WHERE id_mapel = ?");
        $stmt->execute([$id_mapel]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Tidak dapat menghapus mata pelajaran karena masih terdaftar dalam jadwal!";
        } else {
            // Check if subject has grades
            $stmt = $conn->prepare("SELECT COUNT(*) FROM nilai WHERE id_mapel = ?");
            $stmt->execute([$id_mapel]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Tidak dapat menghapus mata pelajaran karena masih memiliki data nilai!";
            } else {
                // Delete subject
                $stmt = $conn->prepare("DELETE FROM mata_pelajaran WHERE id_mapel = ?");
                $stmt->execute([$id_mapel]);
                $message = "Mata pelajaran berhasil dihapus!";
            }
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get subject data for editing
if (isset($_GET['edit'])) {
    $id_mapel = sanitize($_GET['edit']);
    
    try {
        $stmt = $conn->prepare("SELECT * FROM mata_pelajaran WHERE id_mapel = ?");
        $stmt->execute([$id_mapel]);
        $mapel = $stmt->fetch();
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all subjects with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

try {
    // Count total subjects for pagination
    if (!empty($search)) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM mata_pelajaran m
            LEFT JOIN guru g ON m.id_guru = g.id_guru
            WHERE m.nama_mapel LIKE ? OR g.nama_guru LIKE ?
        ");
        $stmt->execute(["%$search%", "%$search%"]);
    } else {
        $stmt = $conn->query("SELECT COUNT(*) FROM mata_pelajaran");
    }
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);
    
    // Get subjects with teacher information
    if (!empty($search)) {
        $stmt = $conn->prepare("
            SELECT m.*, g.nama_guru 
            FROM mata_pelajaran m
            LEFT JOIN guru g ON m.id_guru = g.id_guru
            WHERE m.nama_mapel LIKE ? OR g.nama_guru LIKE ?
            ORDER BY m.nama_mapel
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(2, "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->bindValue(4, $offset, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("
            SELECT m.*, g.nama_guru 
            FROM mata_pelajaran m
            LEFT JOIN guru g ON m.id_guru = g.id_guru
            ORDER BY m.nama_mapel
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
    }
    $subjects = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4">Manajemen Mata Pelajaran</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <?php echo !empty($mapel) ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran Baru'; ?>
            </h6>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?php echo !empty($mapel) ? 'edit' : 'add'; ?>">
                <?php if (!empty($mapel)): ?>
                    <input type="hidden" name="id_mapel" value="<?php echo $mapel['id_mapel']; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nama_mapel" class="form-label">Nama Mata Pelajaran</label>
                            <input type="text" class="form-control" id="nama_mapel" name="nama_mapel" value="<?php echo !empty($mapel) ? $mapel['nama_mapel'] : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="id_guru" class="form-label">Guru Pengampu</label>
                            <select class="form-select" id="id_guru" name="id_guru" required>
                                <option value="">Pilih Guru</option>
                                <?php foreach ($guru as $g): ?>
                                    <option value="<?php echo $g['id_guru']; ?>" <?php echo (!empty($mapel) && $mapel['id_guru'] == $g['id_guru']) ? 'selected' : ''; ?>>
                                        <?php echo $g['nama_guru']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5" required><?php echo !empty($mapel) ? $mapel['deskripsi'] : ''; ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <?php echo !empty($mapel) ? 'Update Mata Pelajaran' : 'Tambah Mata Pelajaran'; ?>
                    </button>
                    <?php if (!empty($mapel)): ?>
                        <a href="mata-pelajaran.php" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Mata Pelajaran</h6>
            <form class="d-flex" method="GET" action="">
                <input class="form-control me-2" type="search" placeholder="Cari mata pelajaran..." name="search" value="<?php echo $search; ?>">
                <button class="btn btn-outline-primary" type="submit">Cari</button>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nama Mata Pelajaran</th>
                            <th>Deskripsi</th>
                            <th>Guru Pengampu</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subjects)): ?>
                            <tr>
                                <td colspan="4" class="text-center">Tidak ada data mata pelajaran</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($subjects as $subject): ?>
                                <tr>
                                    <td><?php echo $subject['nama_mapel']; ?></td>
                                    <td><?php echo $subject['deskripsi']; ?></td>
                                    <td><?php echo $subject['nama_guru']; ?></td>
                                    <td>
                                        <a href="mata-pelajaran.php?edit=<?php echo $subject['id_mapel']; ?>" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="mata-pelajaran.php?delete=<?php echo $subject['id_mapel']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus mata pelajaran ini?')">
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

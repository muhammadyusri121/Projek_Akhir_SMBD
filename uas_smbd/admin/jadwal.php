<?php
require_once '../config/init.php';

// Redirect to login page if not logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

// Set page title
$pageTitle = 'Manajemen Jadwal';

// Define BASEPATH
define('BASEPATH', true);

// Initialize variables
$message = '';
$error = '';
$jadwal = [];
$kelas = [];
$guru = [];
$mapel = [];

// Get all classes for dropdown
try {
    $stmt = $conn->query("SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas");
    $kelas = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Get all teachers for dropdown
try {
    $stmt = $conn->query("SELECT id_guru, nama_guru FROM guru ORDER BY nama_guru");
    $guru = $stmt->fetchAll();
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

// Process form submission for adding/editing schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Sanitize input
    $id_kelas = sanitize($_POST['id_kelas']);
    $id_guru = sanitize($_POST['id_guru']);
    $id_mapel = sanitize($_POST['id_mapel']);
    $hari = sanitize($_POST['hari']);
    $jam_mulai = sanitize($_POST['jam_mulai']);
    $jam_selesai = sanitize($_POST['jam_selesai']);
    
    try {
        // Check for schedule conflict
        $conflictQuery = "
            SELECT COUNT(*) FROM jadwal 
            WHERE hari = ? AND (
                (jam_mulai <= ? AND jam_selesai > ?) OR
                (jam_mulai < ? AND jam_selesai >= ?) OR
                (jam_mulai >= ? AND jam_selesai <= ?)
            )
        ";
        
        if ($action === 'add') {
            // Check if teacher is already scheduled at the same time
            $stmt = $conn->prepare($conflictQuery . " AND id_guru = ?");
            $stmt->execute([$hari, $jam_mulai, $jam_mulai, $jam_selesai, $jam_selesai, $jam_mulai, $jam_selesai, $id_guru]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Konflik jadwal! Guru sudah mengajar pada waktu tersebut.";
            } else {
                // Check if class is already scheduled at the same time
                $stmt = $conn->prepare($conflictQuery . " AND id_kelas = ?");
                $stmt->execute([$hari, $jam_mulai, $jam_mulai, $jam_selesai, $jam_selesai, $jam_mulai, $jam_selesai, $id_kelas]);
                if ($stmt->fetchColumn() > 0) {
                    $error = "Konflik jadwal! Kelas sudah memiliki pelajaran pada waktu tersebut.";
                } else {
                    // Insert new schedule
                    $stmt = $conn->prepare("
                        INSERT INTO jadwal (id_kelas, id_guru, id_mapel, hari, jam_mulai, jam_selesai) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$id_kelas, $id_guru, $id_mapel, $hari, $jam_mulai, $jam_selesai]);
                    $message = "Jadwal berhasil ditambahkan!";
                }
            }
        } elseif ($action === 'edit') {
            $id_jadwal = sanitize($_POST['id_jadwal']);
            
            // Check if teacher is already scheduled at the same time (excluding current schedule)
            $stmt = $conn->prepare($conflictQuery . " AND id_guru = ? AND id_jadwal != ?");
            $stmt->execute([$hari, $jam_mulai, $jam_mulai, $jam_selesai, $jam_selesai, $jam_mulai, $jam_selesai, $id_guru, $id_jadwal]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Konflik jadwal! Guru sudah mengajar pada waktu tersebut.";
            } else {
                // Check if class is already scheduled at the same time (excluding current schedule)
                $stmt = $conn->prepare($conflictQuery . " AND id_kelas = ? AND id_jadwal != ?");
                $stmt->execute([$hari, $jam_mulai, $jam_mulai, $jam_selesai, $jam_selesai, $jam_mulai, $jam_selesai, $id_kelas, $id_jadwal]);
                if ($stmt->fetchColumn() > 0) {
                    $error = "Konflik jadwal! Kelas sudah memiliki pelajaran pada waktu tersebut.";
                } else {
                    // Update schedule
                    $stmt = $conn->prepare("
                        UPDATE jadwal 
                        SET id_kelas = ?, id_guru = ?, id_mapel = ?, hari = ?, jam_mulai = ?, jam_selesai = ? 
                        WHERE id_jadwal = ?
                    ");
                    $stmt->execute([$id_kelas, $id_guru, $id_mapel, $hari, $jam_mulai, $jam_selesai, $id_jadwal]);
                    $message = "Jadwal berhasil diperbarui!";
                }
            }
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Process delete schedule
if (isset($_GET['delete'])) {
    $id_jadwal = sanitize($_GET['delete']);
    
    try {
        // Delete schedule
        $stmt = $conn->prepare("DELETE FROM jadwal WHERE id_jadwal = ?");
        $stmt->execute([$id_jadwal]);
        $message = "Jadwal berhasil dihapus!";
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get schedule data for editing
if (isset($_GET['edit'])) {
    $id_jadwal = sanitize($_GET['edit']);
    
    try {
        $stmt = $conn->prepare("SELECT * FROM jadwal WHERE id_jadwal = ?");
        $stmt->execute([$id_jadwal]);
        $jadwal = $stmt->fetch();
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all schedules with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$filter_hari = isset($_GET['hari']) ? sanitize($_GET['hari']) : '';
$filter_kelas = isset($_GET['kelas']) ? sanitize($_GET['kelas']) : '';

try {
    // Build the query based on filters
    $whereClause = [];
    $params = [];
    
    if (!empty($search)) {
        $whereClause[] = "(k.nama_kelas LIKE ? OR g.nama_guru LIKE ? OR m.nama_mapel LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($filter_hari)) {
        $whereClause[] = "j.hari = ?";
        $params[] = $filter_hari;
    }
    
    if (!empty($filter_kelas)) {
        $whereClause[] = "j.id_kelas = ?";
        $params[] = $filter_kelas;
    }
    
    $whereStr = !empty($whereClause) ? "WHERE " . implode(" AND ", $whereClause) : "";
    
    // Count total schedules for pagination
    $countQuery = "
        SELECT COUNT(*) FROM jadwal j
        LEFT JOIN kelas k ON j.id_kelas = k.id_kelas
        LEFT JOIN guru g ON j.id_guru = g.id_guru
        LEFT JOIN mata_pelajaran m ON j.id_mapel = m.id_mapel
        $whereStr
    ";
    
    $stmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);
    
    // Get schedules with related information
    $query = "
        SELECT j.*, k.nama_kelas, g.nama_guru, m.nama_mapel 
        FROM jadwal j
        LEFT JOIN kelas k ON j.id_kelas = k.id_kelas
        LEFT JOIN guru g ON j.id_guru = g.id_guru
        LEFT JOIN mata_pelajaran m ON j.id_mapel = m.id_mapel
        $whereStr
        ORDER BY j.hari, j.jam_mulai
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($query);
    
    // Bind all parameters
    $paramIndex = 1;
    foreach ($params as $param) {
        $stmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
    }
    
    // Bind LIMIT and OFFSET as integers
    $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $schedules = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Include header
include_once '../includes/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4">Manajemen Jadwal</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <?php echo !empty($jadwal) ? 'Edit Jadwal' : 'Tambah Jadwal Baru'; ?>
            </h6>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?php echo !empty($jadwal) ? 'edit' : 'add'; ?>">
                <?php if (!empty($jadwal)): ?>
                    <input type="hidden" name="id_jadwal" value="<?php echo $jadwal['id_jadwal']; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="id_kelas" class="form-label">Kelas</label>
                            <select class="form-select" id="id_kelas" name="id_kelas" required>
                                <option value="">Pilih Kelas</option>
                                <?php foreach ($kelas as $k): ?>
                                    <option value="<?php echo $k['id_kelas']; ?>" <?php echo (!empty($jadwal) && $jadwal['id_kelas'] == $k['id_kelas']) ? 'selected' : ''; ?>>
                                        <?php echo $k['nama_kelas']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_guru" class="form-label">Guru</label>
                            <select class="form-select" id="id_guru" name="id_guru" required>
                                <option value="">Pilih Guru</option>
                                <?php foreach ($guru as $g): ?>
                                    <option value="<?php echo $g['id_guru']; ?>" <?php echo (!empty($jadwal) && $jadwal['id_guru'] == $g['id_guru']) ? 'selected' : ''; ?>>
                                        <?php echo $g['nama_guru']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_mapel" class="form-label">Mata Pelajaran</label>
                            <select class="form-select" id="id_mapel" name="id_mapel" required>
                                <option value="">Pilih Mata Pelajaran</option>
                                <?php foreach ($mapel as $m): ?>
                                    <option value="<?php echo $m['id_mapel']; ?>" <?php echo (!empty($jadwal) && $jadwal['id_mapel'] == $m['id_mapel']) ? 'selected' : ''; ?>>
                                        <?php echo $m['nama_mapel']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="hari" class="form-label">Hari</label>
                            <select class="form-select" id="hari" name="hari" required>
                                <option value="">Pilih Hari</option>
                                <option value="Senin" <?php echo (!empty($jadwal) && $jadwal['hari'] === 'Senin') ? 'selected' : ''; ?>>Senin</option>
                                <option value="Selasa" <?php echo (!empty($jadwal) && $jadwal['hari'] === 'Selasa') ? 'selected' : ''; ?>>Selasa</option>
                                <option value="Rabu" <?php echo (!empty($jadwal) && $jadwal['hari'] === 'Rabu') ? 'selected' : ''; ?>>Rabu</option>
                                <option value="Kamis" <?php echo (!empty($jadwal) && $jadwal['hari'] === 'Kamis') ? 'selected' : ''; ?>>Kamis</option>
                                <option value="Jumat" <?php echo (!empty($jadwal) && $jadwal['hari'] === 'Jumat') ? 'selected' : ''; ?>>Jumat</option>
                                <option value="Sabtu" <?php echo (!empty($jadwal) && $jadwal['hari'] === 'Sabtu') ? 'selected' : ''; ?>>Sabtu</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="jam_mulai" class="form-label">Jam Mulai</label>
                            <input type="time" class="form-control" id="jam_mulai" name="jam_mulai" value="<?php echo !empty($jadwal) ? $jadwal['jam_mulai'] : ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="jam_selesai" class="form-label">Jam Selesai</label>
                            <input type="time" class="form-control" id="jam_selesai" name="jam_selesai" value="<?php echo !empty($jadwal) ? $jadwal['jam_selesai'] : ''; ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <?php echo !empty($jadwal) ? 'Update Jadwal' : 'Tambah Jadwal'; ?>
                    </button>
                    <?php if (!empty($jadwal)): ?>
                        <a href="jadwal.php" class="btn btn-secondary">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Jadwal</h6>
            <div class="d-flex">
                <form class="d-flex me-2" method="GET" action="">
                    <select class="form-select me-2" name="hari" onchange="this.form.submit()">
                        <option value="">Semua Hari</option>
                        <option value="Senin" <?php echo ($filter_hari === 'Senin') ? 'selected' : ''; ?>>Senin</option>
                        <option value="Selasa" <?php echo ($filter_hari === 'Selasa') ? 'selected' : ''; ?>>Selasa</option>
                        <option value="Rabu" <?php echo ($filter_hari === 'Rabu') ? 'selected' : ''; ?>>Rabu</option>
                        <option value="Kamis" <?php echo ($filter_hari === 'Kamis') ? 'selected' : ''; ?>>Kamis</option>
                        <option value="Jumat" <?php echo ($filter_hari === 'Jumat') ? 'selected' : ''; ?>>Jumat</option>
                        <option value="Sabtu" <?php echo ($filter_hari === 'Sabtu') ? 'selected' : ''; ?>>Sabtu</option>
                    </select>
                    <select class="form-select me-2" name="kelas" onchange="this.form.submit()">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($kelas as $k): ?>
                            <option value="<?php echo $k['id_kelas']; ?>" <?php echo ($filter_kelas == $k['id_kelas']) ? 'selected' : ''; ?>>
                                <?php echo $k['nama_kelas']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="search" value="<?php echo $search; ?>">
                    <?php endif; ?>
                </form>
                <form class="d-flex" method="GET" action="">
                    <input class="form-control me-2" type="search" placeholder="Cari jadwal..." name="search" value="<?php echo $search; ?>">
                    <?php if (!empty($filter_hari)): ?>
                        <input type="hidden" name="hari" value="<?php echo $filter_hari; ?>">
                    <?php endif; ?>
                    <?php if (!empty($filter_kelas)): ?>
                        <input type="hidden" name="kelas" value="<?php echo $filter_kelas; ?>">
                    <?php endif; ?>
                    <button class="btn btn-outline-primary" type="submit">Cari</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Hari</th>
                            <th>Jam</th>
                            <th>Kelas</th>
                            <th>Mata Pelajaran</th>
                            <th>Guru</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($schedules)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Tidak ada data jadwal</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($schedules as $schedule): ?>
                                <tr>
                                    <td><?php echo $schedule['hari']; ?></td>
                                    <td>
                                        <?php 
                                        echo date('H:i', strtotime($schedule['jam_mulai'])) . ' - ' . 
                                             date('H:i', strtotime($schedule['jam_selesai'])); 
                                        ?>
                                    </td>
                                    <td><?php echo $schedule['nama_kelas']; ?></td>
                                    <td><?php echo $schedule['nama_mapel']; ?></td>
                                    <td><?php echo $schedule['nama_guru']; ?></td>
                                    <td>
                                        <a href="jadwal.php?edit=<?php echo $schedule['id_jadwal']; ?>" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="jadwal.php?delete=<?php echo $schedule['id_jadwal']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?')">
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . $search : ''; ?><?php echo !empty($filter_hari) ? '&hari=' . $filter_hari : ''; ?><?php echo !empty($filter_kelas) ? '&kelas=' . $filter_kelas : ''; ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . $search : ''; ?><?php echo !empty($filter_hari) ? '&hari=' . $filter_hari : ''; ?><?php echo !empty($filter_kelas) ? '&kelas=' . $filter_kelas : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . $search : ''; ?><?php echo !empty($filter_hari) ? '&hari=' . $filter_hari : ''; ?><?php echo !empty($filter_kelas) ? '&kelas=' . $filter_kelas : ''; ?>">
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

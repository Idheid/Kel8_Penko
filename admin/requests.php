<?php
session_start();
require_once '../koneksi/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';
$error = '';
$success = '';

// Handle request approval
if ($action == 'approve' && $id) {
    $conn->begin_transaction();
    try {
        $request_query = $conn->prepare("SELECT r.product_id, r.quantity, r.user_id, p.current_stock AS stock, u.username 
                                        FROM requests r 
                                        JOIN products p ON r.product_id = p.id 
                                        JOIN users u ON r.user_id = u.id 
                                        WHERE r.id = ? AND r.status = 'pending'");
        $request_query->bind_param("i", $id);
        $request_query->execute();
        $request = $request_query->get_result()->fetch_assoc();

        if ($request) {
            if ($request['stock'] >= $request['quantity']) {
                $new_stock = $request['stock'] - $request['quantity'];
                $update_stock = $conn->prepare("UPDATE products SET current_stock = ? WHERE id = ?");
                $update_stock->bind_param("ii", $new_stock, $request['product_id']);
                $update_stock->execute();

                $update_request = $conn->prepare("UPDATE requests SET status = 'approved', processed_at = NOW(), admin_id = ? WHERE id = ?");
                $update_request->bind_param("ii", $_SESSION['user_id'], $id);
                $update_request->execute();

                // Tambahkan transaksi "keluar" dengan reference ke request_id
                $transaction_query = $conn->prepare("INSERT INTO transactions (product_id, type, quantity, user_id, date, reference, created_at) VALUES (?, 'keluar', ?, ?, CURDATE(), ?, NOW())");
                $transaction_query->bind_param("iiis", $request['product_id'], $request['quantity'], $request['user_id'], $id);
                $transaction_query->execute();

                $conn->commit();
                $success = "Permintaan dari " . htmlspecialchars($request['username']) . " berhasil disetujui dan dicatat sebagai transaksi keluar dengan referensi ID permintaan: " . $id . "!";
            } else {
                $conn->rollback();
                $error = "Stok tidak cukup untuk menyetujui permintaan!";
            }
        } else {
            $conn->rollback();
            $error = "Permintaan tidak ditemukan atau sudah diproses!";
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Gagal memproses permintaan: " . $e->getMessage();
    }
}

// Handle request rejection
if ($action == 'reject' && $id) {
    $query = $conn->prepare("UPDATE requests SET status = 'rejected', processed_at = NOW(), admin_id = ? WHERE id = ? AND status = 'pending'");
    $query->bind_param("ii", $_SESSION['user_id'], $id);
    if ($query->execute()) {
        if ($query->affected_rows > 0) {
            $request = $conn->query("SELECT u.username FROM requests r JOIN users u ON r.user_id = u.id WHERE r.id = $id")->fetch_assoc();
            $success = "Permintaan dari " . htmlspecialchars($request['username']) . " berhasil ditolak!";
        } else {
            $error = "Permintaan tidak ditemukan atau sudah diproses!";
        }
    } else {
        $error = "Gagal menolak permintaan: " . $conn->error;
    }
}

// Fetch requests with additional details
$requests_sql = "SELECT r.id, r.quantity, r.status, r.created_at, r.processed_at, r.purpose, p.name AS product_name, u.username AS username, u.name AS user_name 
                 FROM requests r 
                 JOIN products p ON r.product_id = p.id 
                 JOIN users u ON r.user_id = u.id 
                 WHERE 1=1";
if ($status_filter) {
    $requests_sql .= " AND r.status = ?";
    $query = $conn->prepare($requests_sql);
    $query->bind_param("s", $status_filter);
} else {
    $query = $conn->prepare($requests_sql);
}
$query->execute();
$requests_query = $query->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permintaan - Penko</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .status-pending { @apply bg-yellow-400 text-white px-2 py-1 rounded; }
        .status-approved { @apply bg-green-500 text-white px-2 py-1 rounded; }
        .status-rejected { @apply bg-red-500 text-white px-2 py-1 rounded; }
        .action-button { @apply px-2 py-1 rounded text-white font-semibold transition-colors; }
        .action-approve { @apply bg-green-500; }
        .action-reject { @apply bg-red-500; }
        .action-approve:hover { @apply bg-green-600; }
        .action-reject:hover { @apply bg-red-600; }
        .purpose-detail { display: none; }
        .purpose-detail.active { display: table-cell; max-width: 200px; word-wrap: break-word; }
    </style>
    <script>
        function togglePurpose(id) {
            const detail = document.getElementById('purpose-' + id);
            detail.classList.toggle('active');
        }
    </script>
</head>
<body class="bg-gray-50 font-sans">
<div class="flex h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r px-4 py-6 flex flex-col space-y-6">
        <div class="flex items-center space-x-3 px-2">
            <img src="../img/Logo.png" alt="Logo" class="h-8 w-8 object-cover" />
            <h1 class="text-xl font-semibold text-gray-800">Penko<span class="text-blue-500">.</span></h1>
        </div>
        <nav class="flex-1">
            <ul class="space-y-2">
                <li><a href="dashboard.php" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition"><svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l9-6 9 6v10a2 2 0 01-2 2H5a2 2 0 01-2-2V8z" /></svg><span class="ml-3 text-sm font-medium">Dashboard</span></a></li>
                <li><a href="products.php" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition"><svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg><span class="ml-3 text-sm font-medium">Produk</span></a></li>
                <li><a href="transactions.php" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition"><svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span class="ml-3 text-sm font-medium">Transaksi</span></a></li>
                <li><a href="categories.php" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition"><svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg><span class="ml-3 text-sm font-medium">Kategori</span></a></li>
                <li><a href="suppliers.php" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition"><svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg><span class="ml-3 text-sm font-medium">Supplier</span></a></li>
                <li><a href="users.php" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition"><svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg><span class="ml-3 text-sm font-medium">Pengguna</span></a></li>
                <li><a href="requests.php" class="flex items-center px-4 py-2 rounded-lg text-white bg-blue-500 hover:bg-blue-600 transition"><svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-4 0v4m0 0H7m4 0h4" /></svg><span class="ml-3 text-sm font-medium">Permintaan</span></a></li>
                <li><a href="reports.php" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition"><svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-6m3 6v-8m-9 8h6m-7 0H5a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2h-7" /></svg><span class="ml-3 text-sm font-medium">Laporan</span></a></li>
                <li><a href="../logout.php" class="flex items-center px-4 py-2 rounded-lg text-red-500 hover:bg-red-50 hover:text-red-600 transition"><svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h4m0 0l-3-3m3 3l-3 3m-4-3H3m9 4v5a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h5a2 2 0 012 2v1" /></svg><span class="ml-3 text-sm font-medium">Log out</span></a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6">
        <div class="mb-6">
            <h2 class="text-2xl font-semibold text-gray-800">Kelola Permintaan</h2>
            <p class="text-sm text-gray-500">Lihat, setujui, atau tolak permintaan produk.</p>
        </div>

        <?php if ($error): ?>
            <p class="text-red-500 text-sm mb-4"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="text-green-500 text-sm mb-4"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <!-- Filter Form -->
        <div class="bg-white p-6 rounded-xl shadow mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Permintaan</h3>
            <form method="GET" action="requests.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-600 mb-2">Status</label>
                    <select name="status_filter" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Status</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Menunggu</option>
                        <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Disetujui</option>
                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Ditolak</option>
                    </select>
                </div>
                <div class="self-end">
                    <button type="submit" class="bg-blue-500 text-white p-2 rounded-lg hover:bg-blue-600">Tampilkan</button>
                    <a href="requests.php" class="ml-2 text-blue-500 hover:underline">Reset</a>
                </div>
            </form>
        </div>

        <!-- Request List -->
        <div class="bg-white p-6 rounded-xl shadow">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Daftar Permintaan</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-gray-700 border-collapse">
                    <thead>
                        <tr class="bg-gray-100 border-b">
                            <th class="py-2 px-4 text-left">ID</th>
                            <th class="py-2 px-4 text-left">Nama Pegawai</th>
                            <th class="py-2 px-4 text-left">Produk</th>
                            <th class="py-2 px-4 text-left">Jumlah</th>
                            <th class="py-2 px-4 text-left">Tujuan</th>
                            <th class="py-2 px-4 text-left">Status</th>
                            <th class="py-2 px-4 text-left">Tanggal Permintaan</th>
                            <th class="py-2 px-4 text-left">Tanggal Diproses</th>
                            <th class="py-2 px-4 text-left">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($requests_query->num_rows > 0): ?>
                            <?php while ($row = $requests_query->fetch_assoc()): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-4"><?php echo $row['id']; ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($row['user_name']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($row['product_name']); ?></td>
                                    <td class="py-2 px-4"><?php echo number_format($row['quantity'], 0, ',', '.'); ?></td>
                                    <td class="py-2 px-4">
                                        <a href="#" onclick="togglePurpose(<?php echo $row['id']; ?>)" class="text-blue-500 hover:underline">Lihat</a>
                                        <span id="purpose-<?php echo $row['id']; ?>" class="purpose-detail"><?php echo htmlspecialchars($row['purpose'] ?? '-'); ?></span>
                                    </td>
                                    <td class="py-2 px-4">
                                        <?php
                                        $status_text = '';
                                        $status_class = strtolower($row['status'] ?? 'pending');
                                        switch ($status_class) {
                                            case 'pending':
                                                $status_text = 'Menunggu';
                                                break;
                                            case 'approved':
                                                $status_text = 'Disetujui';
                                                break;
                                            case 'rejected':
                                                $status_text = 'Ditolak';
                                                break;
                                            default:
                                                $status_text = 'Tidak diketahui';
                                                $status_class = 'pending';
                                        }
                                        ?>
                                        <span class="status-<?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td class="py-2 px-4"><?php echo date('d M Y H:i', strtotime($row['created_at'])); ?></td>
                                    <td class="py-2 px-4"><?php echo $row['processed_at'] ? date('d M Y H:i', strtotime($row['processed_at'])) : '-'; ?></td>
                                    <td class="py-2 px-4">
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <a href="?action=approve&id=<?php echo $row['id']; ?>" class="action-button action-approve mr-2" onclick="return confirm('Yakin ingin menyetujui permintaan ini?')">Setujui</a>
                                            <a href="?action=reject&id=<?php echo $row['id']; ?>" class="action-button action-reject" onclick="return confirm('Yakin ingin menolak permintaan ini?')">Tolak</a>
                                        <?php else: ?>
                                            <span class="text-gray-500">Tidak ada aksi</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="py-4 text-center text-gray-500">Tidak ada permintaan ditemukan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
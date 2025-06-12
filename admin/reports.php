<?php
session_start();
require_once '../koneksi/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$category_filter = $_GET['category_filter'] ?? '';
$error = '';
$success = '';

// Fetch stock summary with total cost
$stock_sql = "SELECT p.id, p.name AS product_name, p.unit, p.current_stock, p.price, 
                     (p.current_stock * p.price) AS total_cost 
              FROM products p 
              WHERE 1=1";
$stock_params = [];
$stock_types = '';

if ($category_filter) {
    $stock_sql .= " AND p.category_id = ?";
    $stock_params[] = $category_filter;
    $stock_types .= 'i';
}

$stock_query = $conn->prepare($stock_sql);
if ($stock_params) {
    $stock_query->bind_param($stock_types, ...$stock_params);
}
$stock_query->execute();
$stock_result = $stock_query->get_result();

$total_stock_value = 0;
while ($row = $stock_result->fetch_assoc()) {
    $total_stock_value += $row['total_cost'];
}
$stock_result->data_seek(0);

// Fetch stock summary for alert
$stock_alert_query = $conn->query("SELECT id, name, unit, min_stock, current_stock FROM products");
if ($stock_alert_query === false) {
    die("Query stock gagal: " . $conn->error);
}
$products = $stock_alert_query->fetch_all(MYSQLI_ASSOC);
$low_stock_alert = [];
foreach ($products as $product) {
    if ($product['current_stock'] <= $product['min_stock']) {
        $low_stock_alert[] = "Stok " . $product['name'] . " (" . $product['unit'] . ") mendekati batas minimum (" . $product['current_stock'] . "/" . $product['min_stock'] . ").";
    }
}

// Fetch request history
$requests_sql = "SELECT r.id, r.product_id, r.quantity, r.status, r.created_at, r.notes, p.name AS product_name, u.username AS requested_by 
                 FROM requests r 
                 JOIN products p ON r.product_id = p.id 
                 JOIN users u ON r.user_id = u.id 
                 WHERE 1=1";
$requests_params = [];
$requests_types = '';

if ($start_date) {
    $requests_sql .= " AND DATE(r.created_at) >= ?";
    $requests_params[] = $start_date;
    $requests_types .= 's';
}
if ($end_date) {
    $requests_sql .= " AND DATE(r.created_at) <= ?";
    $requests_params[] = $end_date;
    $requests_types .= 's';
}

$requests_query = $conn->prepare($requests_sql);
if ($requests_params) {
    $requests_query->bind_param($requests_types, ...$requests_params);
}
$requests_query->execute();
$requests_result = $requests_query->get_result();

$categories_query = $conn->query("SELECT * FROM categories");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Penko</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
<div class="flex h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r px-4 py-6 flex flex-col space-y-6">
        <div class="flex items-center space-x-3 px-2">
            <img src="../img/Logo.png" alt="Logo" class="h-8 w-8 object-cover" />
            <h1 class="text-xl font-semibold text-gray-800">Penko<span class="text-blue-500">.</span></h1>
        </div>
        <nav class="flex-1">
            <ul class="space-y-2">
                <li>
                    <a href="dashboard.php" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition">
                        <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l9-6 9 6v10a2 2 0 01-2 2H5a2 2 0 01-2-2V8z" />
                        </svg>
                        <span class="ml-3 text-sm font-medium">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="products.php" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition">
                        <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        <span class="ml-3 text-sm font-medium">Produk</span>
                    </a>
                </li>
                <li>
                    <a href="transactions.php" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition">
                        <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="ml-3 text-sm font-medium">Transaksi</span>
                    </a>
                </li>
                <li>
                    <a href="categories.php" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition">
                        <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <span class="ml-3 text-sm font-medium">Kategori</span>
                    </a>
                </li>
                <li>
                    <a href="suppliers.php" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition">
                        <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="ml-3 text-sm font-medium">Supplier</span>
                    </a>
                </li>
                <li>
                    <a href="users.php" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition">
                        <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span class="ml-3 text-sm font-medium">Pengguna</span>
                    </a>
                </li>
                <li>
                    <a href="requests.php" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition">
                        <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-4 0v4m0 0H7m4 0h4" />
                        </svg>
                        <span class="ml-3 text-sm font-medium">Permintaan</span>
                    </a>
                </li>
                <li>
                    <a href="reports.php" class="flex items-center px-4 py-2 rounded-lg text-white bg-blue-500 hover:bg-blue-600 transition">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-6m3 6v-8m-9 8h6m-7 0H5a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2h-7" />
                        </svg>
                        <span class="ml-3 text-sm font-medium">Laporan</span>
                    </a>
                </li>
                <li>
                    <a href="../logout.php" class="flex items-center px-4 py-2 rounded-lg text-red-500 hover:bg-red-50 hover:text-red-600 transition">
                        <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h4m0 0l-3-3m3 3l-3 3m-4-3H3m9 4v5a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h5a2 2 0 012 2v1" />
                        </svg>
                        <span class="ml-3 text-sm font-medium">Log out</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6">
        <div class="mb-6">
            <h2 class="text-2xl font-semibold text-gray-800">Laporan</h2>
            <p class="text-sm text-gray-500">Lihat stok tersedia, dan riwayat permintaan.</p>
        </div>

        <?php if ($error): ?>
            <p class="text-red-500 text-sm mb-4"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="text-green-500 text-sm mb-4"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if (!empty($low_stock_alert)): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Peringatan Stok Minimum!</strong>
                <ul class="mt-2">
                    <?php foreach ($low_stock_alert as $alert): ?>
                        <li><?php echo htmlspecialchars($alert); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Filter Form -->
        <div class="bg-white p-6 rounded-xl shadow mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Laporan</h3>
            <form method="GET" action="reports.php" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm text-gray-600 mb-2">Tanggal</label>
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-2">Kategori</label>
                    <select name="category_filter" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Kategori</option>
                        <?php while ($cat = $categories_query->fetch_assoc()): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endwhile; $categories_query->data_seek(0); ?>
                    </select>
                </div>
                <div class="md:col-span-3 flex space-x-2">
                    <button type="submit" class="bg-blue-500 text-white p-2 rounded-lg hover:bg-blue-600">Tampilkan</button>
                    <a href="reports.php" class="text-blue-500 hover:underline self-center">Reset</a>
                </div>
            </form>
        </div>

        <!-- Stock Summary Report -->
        <div class="bg-white p-6 rounded-xl shadow mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Laporan Stok Tersedia dan Total Harga</h3>
            <table class="min-w-full text-sm text-gray-600">
                <thead>
                    <tr>
                        <th class="text-left py-2">ID Produk</th>
                        <th class="text-left py-2">Nama Produk</th>
                        <th class="text-left py-2">Satuan</th>
                        <th class="text-left py-2">Stok Saat Ini</th>
                        <th class="text-left py-2">Harga per Unit (Rp)</th>
                        <th class="text-left py-2">Total Harga (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($stock_result->num_rows > 0): ?>
                        <?php while ($row = $stock_result->fetch_assoc()): ?>
                            <tr class="border-t">
                                <td class="py-2"><?php echo $row['id']; ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($row['product_name']); ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($row['unit']); ?></td>
                                <td class="py-2"><?php echo $row['current_stock']; ?></td>
                                <td class="py-2"><?php echo number_format($row['price'], 2); ?></td>
                                <td class="py-2"><?php echo number_format($row['total_cost'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                        <tr class="border-t font-semibold">
                            <td colspan="5" class="py-2 text-right">Total Nilai Stok:</td>
                            <td class="py-2"><?php echo number_format($total_stock_value, 2); ?></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-2 text-center text-gray-500">Tidak ada stok ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Request History -->
        <div class="bg-white p-6 rounded-xl shadow">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Laporan Riwayat Permintaan</h3>
            <table class="min-w-full text-sm text-gray-600">
                <thead>
                    <tr>
                        <th class="text-left py-2">ID</th>
                        <th class="text-left py-2">Produk</th>
                        <th class="text-left py-2">Jumlah</th>
                        <th class="text-left py-2">Status</th>
                        <th class="text-left py-2">Diajukan Oleh</th>
                        <th class="text-left py-2">Tanggal Permintaan</th>
                        <th class="text-left py-2">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($requests_result->num_rows > 0): ?>
                        <?php while ($row = $requests_result->fetch_assoc()): ?>
                            <tr class="border-t">
                                <td class="py-2"><?php echo $row['id']; ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($row['product_name']); ?></td>
                                <td class="py-2"><?php echo $row['quantity']; ?></td>
                                <td class="py-2">
                                    <span class="status-<?php echo strtolower($row['status']); ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td class="py-2"><?php echo htmlspecialchars($row['requested_by']); ?></td>
                                <td class="py-2"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($row['notes'] ?? '-'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="py-2 text-center text-gray-500">Tidak ada permintaan ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <style>
            .status-pending { @apply bg-yellow-500 text-white px-2 py-1 rounded; }
            .status-approved { @apply bg-green-500 text-white px-2 py-1 rounded; }
            .status-rejected { @apply bg-red-500 text-white px-2 py-1 rounded; }
        </style>
    </main>
</div>
</body>
</html>
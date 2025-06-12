<?php
session_start();
require_once '../koneksi/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

function get_total_stock($conn) {
    $query = $conn->prepare("SELECT SUM(current_stock) AS total FROM products");
    $query->execute();
    $result = $query->get_result()->fetch_assoc();
    return $result['total'] ?? 0;
}

$product_count = 0;
$product_count_query = $conn->prepare("SELECT COUNT(*) AS product_count FROM products");
if ($product_count_query) {
    $product_count_query->execute();
    $product_count = $product_count_query->get_result()->fetch_assoc()['product_count'] ?? 0;
}

$today = date('Y-m-d');
$transaction_count = 0;
$transaction_count_query = $conn->prepare("SELECT COUNT(*) AS transaction_count FROM transactions WHERE DATE(date) = ?");
if ($transaction_count_query) {
    $transaction_count_query->bind_param("s", $today);
    $transaction_count_query->execute();
    $transaction_count = $transaction_count_query->get_result()->fetch_assoc()['transaction_count'] ?? 0;
}

$low_stock = [];
$low_stock_query = $conn->prepare("SELECT name, current_stock, min_stock FROM products WHERE current_stock <= min_stock");
if ($low_stock_query) {
    $low_stock_query->execute();
    $low_stock = $low_stock_query->get_result()->fetch_all(MYSQLI_ASSOC);
}

$recent_transactions = [];
$recent_transactions_query = $conn->prepare("
    SELECT t.id, t.type, t.quantity, t.created_at, p.name AS product_name
    FROM transactions t
    JOIN products p ON t.product_id = p.id
    ORDER BY t.created_at DESC
    LIMIT 5
");
if ($recent_transactions_query) {
    $recent_transactions_query->execute();
    $recent_transactions = $recent_transactions_query->get_result()->fetch_all(MYSQLI_ASSOC);
    // Tambahkan debug untuk memastikan data diambil
    if (empty($recent_transactions)) {
        error_log("No recent transactions found at " . date('Y-m-d H:i:s'));
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Penko</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Auto-refresh setiap 60 detik untuk memastikan data terupdate
        setInterval(() => location.reload(), 60000);
    </script>
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
                <a href="dashboard.php" class="flex items-center px-4 py-2 rounded-lg text-white bg-blue-500 hover:bg-blue-600 transition">
                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
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
                <a href="reports.php" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition">
                    <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
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

    <main class="flex-1 p-6">
        <div class="mb-6">
            <h2 class="text-2xl font-semibold text-gray-800">Dashboard Admin</h2>
            <p class="text-sm text-gray-500">Selamat datang kembali, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>!</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white p-4 rounded-xl shadow hover:shadow-md transition">
                <h4 class="text-sm text-gray-500">Total Stok</h4>
                <p class="text-2xl font-bold text-blue-600"><?php echo number_format(get_total_stock($conn), 0, ',', '.'); ?> pcs</p>
            </div>
            <div class="bg-white p-4 rounded-xl shadow hover:shadow-md transition">
                <h4 class="text-sm text-gray-500">Jumlah Produk</h4>
                <p class="text-2xl font-bold text-green-600"><?php echo number_format($product_count, 0, ',', '.'); ?></p>
            </div>
            <div class="bg-white p-4 rounded-xl shadow hover:shadow-md transition">
                <h4 class="text-sm text-gray-500">Transaksi Hari Ini</h4>
                <p class="text-2xl font-bold text-yellow-600"><?php echo number_format($transaction_count, 0, ',', '.'); ?></p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Stok Rendah</h3>
            <?php if (!empty($low_stock)): ?>
                <table class="min-w-full text-sm text-gray-600">
                    <thead>
                        <tr>
                            <th class="text-left py-2">Produk</th>
                            <th class="text-left py-2">Stok</th>
                            <th class="text-left py-2">Min. Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_stock as $row): ?>
                            <tr class="border-t">
                                <td class="py-2"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="py-2"><?php echo number_format($row['current_stock'], 0, ',', '.'); ?></td>
                                <td class="py-2"><?php echo number_format($row['min_stock'], 0, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-gray-500">Tidak ada produk dengan stok rendah.</p>
            <?php endif; ?>
        </div>

        <div class="bg-white p-6 rounded-xl shadow">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Transaksi Terbaru</h3>
            <?php if (!empty($recent_transactions)): ?>
                <table class="min-w-full text-sm text-gray-600">
                    <thead>
                        <tr>
                            <th class="text-left py-2">ID</th>
                            <th class="text-left py-2">Produk</th>
                            <th class="text-left py-2">Jenis</th>
                            <th class="text-left py-2">Jumlah</th>
                            <th class="text-left py-2">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_transactions as $row): ?>
                            <tr class="border-t">
                                <td class="py-2">#<?php echo $row['id']; ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($row['product_name']); ?></td>
                                <td class="py-2"><?php echo ucfirst($row['type']); ?></td>
                                <td class="py-2"><?php echo number_format($row['quantity'], 0, ',', '.'); ?></td>
                                <td class="py-2"><?php echo date('d M Y, H:i', strtotime($row['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-gray-500">Tidak ada transaksi terbaru.</p>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
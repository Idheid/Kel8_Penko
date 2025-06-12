<?php
session_start();
require_once '../koneksi/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$action = $_POST['action'] ?? '';
$search_type = $_POST['type'] ?? '';
$date = $_POST['date'] ?? '';
$error = '';
$success = '';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fetch suppliers for dropdown
$suppliers_query = $conn->query("SELECT id, name FROM suppliers");
if ($suppliers_query === false) {
    die("Query suppliers gagal: " . $conn->error);
}
$suppliers = $suppliers_query->fetch_all(MYSQLI_ASSOC);

// Fetch products for dropdown and check stock
$products_query = $conn->query("SELECT id, name, unit, min_stock, current_stock FROM products");
if ($products_query === false) {
    die("Query products gagal: " . $conn->error);
}
$products = $products_query->fetch_all(MYSQLI_ASSOC);
$low_stock_alert = [];
foreach ($products as $product) {
    if ($product['current_stock'] <= $product['min_stock']) {
        $low_stock_alert[] = "Stok " . $product['name'] . " (" . $product['unit'] . ") mendekati batas minimum (" . $product['current_stock'] . "/" . $product['min_stock'] . ").";
    }
}

// Handle transactions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $transactions_sql = "SELECT * FROM transactions WHERE 1=1";
    $params = [];
    $types = '';

    if ($search_type && in_array($search_type, ['masuk', 'keluar'])) {
        $transactions_sql .= " AND type = ?";
        $params[] = $search_type;
        $types .= "s";
    }

    if ($date) {
        $transactions_sql .= " AND DATE(date) = ?";
        $params[] = $date;
        $types .= "s";
    }

    $transactions_query = $conn->prepare($transactions_sql);
    if ($transactions_query === false) {
        die("Prepare gagal: " . $conn->error);
    }
    if (!empty($params)) {
        $transactions_query->bind_param($types, ...$params);
    }
    if (!$transactions_query->execute()) {
        die("Execute gagal: " . $conn->error);
    }
    $transactions_result = $transactions_query->get_result();
} else {
    $transactions_query = $conn->query("SELECT * FROM transactions");
    if ($transactions_query === false) {
        die("Query gagal: " . $conn->error);
    }
    $transactions_result = $transactions_query;
}

// Handle add stock in
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'add_in') {
    $conn->begin_transaction();
    try {
        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'];
        $date = $_POST['date'];
        $supplier_id = $_POST['supplier_id'];
        $reference = $_POST['reference'] ?? '';
        $notes = $_POST['notes'] ?? '';

        // Update product stock
        $product_query = $conn->prepare("UPDATE products SET current_stock = current_stock + ? WHERE id = ?");
        $product_query->bind_param("ii", $quantity, $product_id);
        $product_query->execute();

        // Insert transaction
        $user_id = $_SESSION['user_id'];
        $transaction_query = $conn->prepare("INSERT INTO transactions (product_id, type, quantity, user_id, date, reference, notes, created_at) VALUES (?, 'masuk', ?, ?, ?, ?, ?, NOW())");
        $transaction_query->bind_param("iissss", $product_id, $quantity, $user_id, $date, $reference, $notes);
        $transaction_query->execute();

        $conn->commit();
        $success = "Stok masuk berhasil ditambahkan!";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Gagal menambahkan stok masuk: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - Penko</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleAddInForm = document.getElementById('toggleAddInForm');
            const addInForm = document.getElementById('addInForm');
            const cancelAddInForm = document.getElementById('cancelAddInForm');

            toggleAddInForm.addEventListener('click', function() {
                addInForm.classList.toggle('hidden');
            });

            cancelAddInForm.addEventListener('click', function() {
                addInForm.classList.add('hidden');
            });
        });
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
                    <a href="transactions.php" class="flex items-center px-4 py-2 rounded-lg text-white bg-blue-500 hover:bg-blue-600 transition">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
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

    <!-- Main Content -->
    <main class="flex-1 p-6">
        <div class="mb-6">
            <h2 class="text-2xl font-semibold text-gray-800">Kelola Transaksi</h2>
            <p class="text-sm text-gray-500">Tambah dan lihat daftar transaksi.</p>
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
        <div class="mb-6 bg-white p-6 rounded-xl shadow">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Transaksi</h3>
            <form method="POST" action="transactions.php" class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
                <div>
                    <label class="block text-sm text-gray-600 mb-2" for="type">Jenis Transaksi</label>
                    <select name="type" id="type" class="w-full p-2 border rounded-lg">
                        <option value="" <?php echo !$search_type ? 'selected' : ''; ?>>Semua</option>
                        <option value="masuk" <?php echo $search_type == 'masuk' ? 'selected' : ''; ?>>Masuk</option>
                        <option value="keluar" <?php echo $search_type == 'keluar' ? 'selected' : ''; ?>>Keluar</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-2" for="date">Tanggal</label>
                    <input type="date" name="date" id="date" value="<?php echo htmlspecialchars($date); ?>" class="w-full p-2 border rounded-lg">
                </div>
                <div class="flex items-end space-x-2">
                    <button type="submit" name="search" class="bg-blue-500 text-white p-2 rounded-lg hover:bg-blue-600">Cari</button>
                    <a href="transactions.php" class="bg-gray-300 text-gray-700 p-2 rounded-lg hover:bg-gray-400">Reset</a>
                </div>
            </form>
        </div>

        <!-- Add Transaction Buttons -->
        <div class="mb-6 flex space-x-4">
            <button id="toggleAddInForm" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Tambah Stok Masuk</button>
        </div>

        <!-- Form for Add Stock In -->
        <div id="addInForm" class="bg-white p-6 rounded-xl shadow mb-6 hidden">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Tambah Stok Masuk</h3>
            <form method="POST" action="transactions.php">
                <input type="hidden" name="action" value="add_in">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="product_id">Nama Barang</label>
                        <select name="product_id" id="product_id" class="w-full p-2 border rounded-lg" required>
                            <option value="">Pilih Barang</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']) . " (" . $product['unit'] . ")"; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="quantity">Jumlah</label>
                        <input type="number" name="quantity" id="quantity" class="w-full p-2 border rounded-lg" min="1" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="date">Tanggal Masuk</label>
                        <input type="date" name="date" id="date" class="w-full p-2 border rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="supplier_id">Supplier</label>
                        <select name="supplier_id" id="supplier_id" class="w-full p-2 border rounded-lg" required>
                            <option value="">Pilih Supplier</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?php echo $supplier['id']; ?>"><?php echo htmlspecialchars($supplier['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="reference">Referensi</label>
                        <input type="text" name="reference" id="reference" class="w-full p-2 border rounded-lg" placeholder="e.g., PO-001">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="notes">Catatan</label>
                        <input type="text" name="notes" id="notes" class="w-full p-2 border rounded-lg">
                    </div>
                </div>
                <div class="mt-4 flex space-x-2">
                    <button type="submit" class="bg-blue-500 text-white p-2 rounded-lg hover:bg-blue-600">Simpan Stok Masuk</button>
                    <button type="button" id="cancelAddInForm" class="bg-gray-300 text-gray-700 p-2 rounded-lg hover:bg-gray-400">Batal</button>
                </div>
            </form>
        </div>

        <style>
            .type-masuk { @apply bg-green-500 text-white px-2 py-1 rounded; }
            .type-keluar { @apply bg-red-500 text-white px-2 py-1 rounded; }
        </style>

        <!-- Transaction List -->
        <div class="bg-white p-6 rounded-xl shadow">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Daftar Transaksi</h3>
            <table class="min-w-full text-sm text-gray-600">
                <thead>
                    <tr>
                        <th class="text-left py-2">ID</th>
                        <th class="text-left py-2">Produk ID</th>
                        <th class="text-left py-2">Jenis</th>
                        <th class="text-left py-2">Jumlah</th>
                        <th class="text-left py-2">Tanggal</th>
                        <th class="text-left py-2">User ID</th>
                        <th class="text-left py-2">Referensi</th>
                        <th class="text-left py-2">Catatan</th>
                        <th class="text-left py-2">Dibuat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($transactions_result) && $transactions_result->num_rows > 0): ?>
                        <?php while ($row = $transactions_result->fetch_assoc()): ?>
                            <tr class="border-t">
                                <td class="py-2"><?php echo $row['id']; ?></td>
                                <td class="py-2"><?php echo $row['product_id']; ?></td>
                                <td class="py-2">
                                    <span class="type-<?php echo strtolower($row['type']); ?>">
                                        <?php echo htmlspecialchars($row['type']); ?>
                                    </span>
                                </td>
                                <td class="py-2"><?php echo $row['quantity']; ?></td>
                                <td class="py-2"><?php echo date('d M Y', strtotime($row['date'])); ?></td>
                                <td class="py-2"><?php echo $row['user_id']; ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($row['reference'] ?? '-'); ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($row['notes'] ?? '-'); ?></td>
                                <td class="py-2"><?php echo date('d M Y H:i', strtotime($row['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="py-2 text-center text-gray-500">Tidak ada transaksi ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
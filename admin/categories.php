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
$search_name = $_POST['name'] ?? '';
$error = '';
$success = '';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle add category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'add_category') {
    $conn->begin_transaction();
    try {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        if (empty($name)) {
            $error = "Nama kategori tidak boleh kosong!";
        } else {
            $check_query = $conn->prepare("SELECT id FROM categories WHERE name = ?");
            $check_query->bind_param("s", $name);
            $check_query->execute();
            if ($check_query->get_result()->num_rows > 0) {
                $error = "Kategori dengan nama tersebut sudah ada!";
            } else {
                $insert_query = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                $insert_query->bind_param("ss", $name, $description);
                $insert_query->execute();
                $conn->commit();
                $success = "Kategori baru berhasil ditambahkan!";
            }
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Gagal menambahkan kategori: " . $e->getMessage();
    }
}

// Fetch categories
$categories_sql = "SELECT * FROM categories WHERE 1=1";
if ($search_name) {
    $categories_sql .= " AND name LIKE ?";
    $query = $conn->prepare($categories_sql);
    $search_name = "%" . $search_name . "%";
    $query->bind_param("s", $search_name);
} else {
    $query = $conn->prepare($categories_sql);
}
$query->execute();
$categories_result = $query->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori - Penko</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleAddForm = document.getElementById('toggleAddForm');
            const addForm = document.getElementById('addForm');
            const cancelAddForm = document.getElementById('cancelAddForm');

            toggleAddForm.addEventListener('click', function() {
                addForm.classList.toggle('hidden');
            });

            cancelAddForm.addEventListener('click', function() {
                addForm.classList.add('hidden');
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
                    <a href="transactions.php" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition">
                        <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="ml-3 text-sm font-medium">Transaksi</span>
                    </a>
                </li>
                <li>
                    <a href="categories.php" class="flex items-center px-4 py-2 rounded-lg text-white bg-blue-500 hover:bg-blue-600 transition">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
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
            <h2 class="text-2xl font-semibold text-gray-800">Kelola Kategori</h2>
            <p class="text-sm text-gray-500">Tambah dan lihat daftar kategori produk.</p>
        </div>

        <?php if ($error): ?>
            <p class="text-red-500 text-sm mb-4"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="text-green-500 text-sm mb-4"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <!-- Filter Form -->
        <div class="mb-6 bg-white p-6 rounded-xl shadow">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Kategori</h3>
            <form method="POST" action="categories.php" class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
                <div>
                    <label class="block text-sm text-gray-600 mb-2" for="name">Nama Kategori</label>
                    <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($search_name); ?>" class="w-full p-2 border rounded-lg" placeholder="Cari kategori...">
                </div>
                <div class="flex items-end space-x-2">
                    <button type="submit" name="search" class="bg-blue-500 text-white p-2 rounded-lg hover:bg-blue-600">Cari</button>
                    <a href="categories.php" class="bg-gray-300 text-gray-700 p-2 rounded-lg hover:bg-gray-400">Reset</a>
                </div>
            </form>
        </div>

        <!-- Add Category Button -->
        <div class="mb-6 flex space-x-4">
            <button id="toggleAddForm" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Tambah Kategori</button>
        </div>

        <!-- Form for Add Category -->
        <div id="addForm" class="bg-white p-6 rounded-xl shadow mb-6 hidden">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Tambah Kategori Baru</h3>
            <form method="POST" action="categories.php">
                <input type="hidden" name="action" value="add_category">
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="category_name">Nama Kategori</label>
                        <input type="text" name="name" id="category_name" class="w-full p-2 border rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="category_description">Deskripsi</label>
                        <textarea name="description" id="category_description" class="w-full p-2 border rounded-lg" rows="3" placeholder="Masukkan deskripsi kategori..."></textarea>
                    </div>
                </div>
                <div class="mt-4 flex space-x-2">
                    <button type="submit" class="bg-blue-500 text-white p-2 rounded-lg hover:bg-blue-600">Simpan Kategori</button>
                    <button type="button" id="cancelAddForm" class="bg-gray-300 text-gray-700 p-2 rounded-lg hover:bg-gray-400">Batal</button>
                </div>
            </form>
        </div>

        <!-- Category List -->
        <div class="bg-white p-6 rounded-xl shadow">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Daftar Kategori</h3>
            <table class="min-w-full text-sm text-gray-600">
                <thead>
                    <tr>
                        <th class="text-left py-2">ID</th>
                        <th class="text-left py-2">Nama Kategori</th>
                        <th class="text-left py-2">Deskripsi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($categories_result->num_rows > 0): ?>
                        <?php while ($row = $categories_result->fetch_assoc()): ?>
                            <tr class="border-t">
                                <td class="py-2"><?php echo $row['id']; ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($row['description'] ?? '-'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="py-2 text-center text-gray-500">Tidak ada kategori ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
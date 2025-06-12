<?php
session_start();
require_once '../koneksi/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$current_stock_filter = $_GET['current_stock'] ?? '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'add') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $supplier_id = $_POST['supplier_id'];
    $current_stock = $_POST['current_stock'];
    $unit = $_POST['unit'];
    $price = $_POST['price'];
    $image = $_POST['image'];
    $min_stock = $_POST['min_stock'];

    $query = $conn->prepare("INSERT INTO products (name, description, category_id, supplier_id, current_stock, unit, price, image, min_stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $query->bind_param("ssiisidsi", $name, $description, $category_id, $supplier_id, $current_stock, $unit, $price, $image, $min_stock);
    if ($query->execute()) {
        $success = "Produk berhasil ditambahkan!";
    } else {
        $error = "Gagal menambahkan produk: " . $conn->error;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'edit' && $id) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $supplier_id = $_POST['supplier_id'];
    $current_stock = $_POST['current_stock'];
    $unit = $_POST['unit'];
    $price = $_POST['price'];
    $image = $_POST['image'];
    $min_stock = $_POST['min_stock'];

    $query = $conn->prepare("UPDATE products SET name = ?, description = ?, category_id = ?, supplier_id = ?, current_stock = ?, unit = ?, price = ?, image = ?, min_stock = ? WHERE id = ?");
    $query->bind_param("ssiisidsii", $name, $description, $category_id, $supplier_id, $current_stock, $unit, $price, $image, $min_stock, $id);
    if ($query->execute()) {
        $success = "Produk berhasil diperbarui!";
    } else {
        $error = "Gagal memperbarui produk: " . $conn->error;
    }
}

if ($action == 'delete' && $id) {
    $query = $conn->prepare("DELETE FROM products WHERE id = ?");
    $query->bind_param("i", $id);
    if ($query->execute()) {
        $success = "Produk berhasil dihapus!";
    } else {
        $error = "Gagal menghapus produk: " . $conn->error;
    }
}

$edit_product = null;
if ($action == 'edit' && $id) {
    $query = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $query->bind_param("i", $id);
    $query->execute();
    $edit_product = $query->get_result()->fetch_assoc();
}

$search_query = $search ? $conn->real_escape_string($search) : '';
$products_sql = "SELECT p.*, c.name AS category_name, s.name AS supplier_name 
                 FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.id 
                 LEFT JOIN suppliers s ON p.supplier_id = s.id 
                 WHERE 1=1";
if ($search_query) {
    $products_sql .= " AND (p.name LIKE '%$search_query%' 
                         OR c.name LIKE '%$search_query%' 
                         OR s.name LIKE '%$search_query%')";
}
if ($category_filter) {
    $products_sql .= " AND p.category_id = " . (int)$category_filter;
}
if ($current_stock_filter === 'low') {
    $products_sql .= " AND p.current_stock <= p.min_stock";
} elseif ($current_stock_filter === 'sufficient') {
    $products_sql .= " AND p.current_stock > p.min_stock";
}
$products_query = $conn->query($products_sql);

$categories_query = $conn->query("SELECT * FROM categories");
$suppliers_query = $conn->query("SELECT * FROM suppliers");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk - Penko</title>
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
                <a href="products.php" class="flex items-center px-4 py-2 rounded-lg text-white bg-blue-500 hover:bg-blue-600 transition">
                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
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

    <!-- Main Content -->
    <main class="flex-1 p-6">
        <div class="mb-6">
            <h2 class="text-2xl font-semibold text-gray-800">Kelola Produk</h2>
            <p class="text-sm text-gray-500">Tambah, edit, atau hapus produk di gudang.</p>
        </div>

        <?php if ($error): ?>
            <p class="text-red-500 text-sm mb-4"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="text-green-500 text-sm mb-4"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <!-- Add Product Button -->
        <?php if ($action != 'edit'): ?>
        <div class="mb-6">
            <button id="toggleAddForm" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Tambah Produk</button>
        </div>
        <?php endif; ?>

        <!-- Form for Add Product -->
        <?php if ($action != 'edit'): ?>
        <div id="addForm" class="bg-white p-6 rounded-xl shadow mb-6 hidden">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Tambah Produk</h3>
            <form method="POST" action="?action=add">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="name">Nama Produk</label>
                        <input type="text" name="name" id="name" class="w-full p-2 border rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="category_id">Kategori</label>
                        <select name="category_id" id="category_id" class="w-full p-2 border rounded-lg" required>
                            <option value="">Pilih Kategori</option>
                            <?php while ($category = $categories_query->fetch_assoc()): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endwhile; $categories_query->data_seek(0); ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="supplier_id">Supplier</label>
                        <select name="supplier_id" id="supplier_id" class="w-full p-2 border rounded-lg" required>
                            <option value="">Pilih Supplier</option>
                            <?php while ($supplier = $suppliers_query->fetch_assoc()): ?>
                                <option value="<?php echo $supplier['id']; ?>"><?php echo htmlspecialchars($supplier['name']); ?></option>
                            <?php endwhile; $suppliers_query->data_seek(0); ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="current_stock">Stok</label>
                        <input type="number" name="current_stock" id="current_stock" value="0" class="w-full p-2 border rounded-lg" required min="0">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="unit">Satuan</label>
                        <input type="text" name="unit" id="unit" class="w-full p-2 border rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="price">Harga</label>
                        <input type="number" name="price" id="price" step="0.01" value="0.00" class="w-full p-2 border rounded-lg" required min="0">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="image">URL Gambar</label>
                        <input type="url" name="image" id="image" class="w-full p-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="min_stock">Stok Minimum</label>
                        <input type="number" name="min_stock" id="min_stock" value="10" class="w-full p-2 border rounded-lg" required min="0">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm text-gray-600 mb-2" for="description">Deskripsi</label>
                        <textarea name="description" id="description" class="w-full p-2 border rounded-lg"></textarea>
                    </div>
                </div>
                <div class="mt-4 flex space-x-2">
                    <button type="submit" class="bg-blue-500 text-white p-2 rounded-lg hover:bg-blue-600">Tambah Produk</button>
                    <button type="button" id="cancelAddForm" class="bg-gray-300 text-gray-700 p-2 rounded-lg hover:bg-gray-400">Batal</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Form for Edit Product -->
        <?php if ($action == 'edit' && $edit_product): ?>
        <div class="bg-white p-6 rounded-xl shadow mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Edit Produk</h3>
            <form method="POST" action="?action=edit&id=<?php echo $id; ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="name">Nama Produk</label>
                        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($edit_product['name']); ?>" class="w-full p-2 border rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="category_id">Kategori</label>
                        <select name="category_id" id="category_id" class="w-full p-2 border rounded-lg" required>
                            <option value="">Pilih Kategori</option>
                            <?php while ($category = $categories_query->fetch_assoc()): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $edit_product['category_id'] == $category['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endwhile; $categories_query->data_seek(0); ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="supplier_id">Supplier</label>
                        <select name="supplier_id" id="supplier_id" class="w-full p-2 border rounded-lg" required>
                            <option value="">Pilih Supplier</option>
                            <?php while ($supplier = $suppliers_query->fetch_assoc()): ?>
                                <option value="<?php echo $supplier['id']; ?>" <?php echo $edit_product['supplier_id'] == $supplier['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($supplier['name']); ?></option>
                            <?php endwhile; $suppliers_query->data_seek(0); ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="current_stock">Stok</label>
                        <input type="number" name="current_stock" id="current_stock" value="<?php echo $edit_product['current_stock']; ?>" class="w-full p-2 border rounded-lg" required min="0">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="unit">Satuan</label>
                        <input type="text" name="unit" id="unit" value="<?php echo htmlspecialchars($edit_product['unit']); ?>" class="w-full p-2 border rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="price">Harga</label>
                        <input type="number" name="price" id="price" step="0.01" value="<?php echo $edit_product['price']; ?>" class="w-full p-2 border rounded-lg" required min="0">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="image">URL Gambar</label>
                        <input type="url" name="image" id="image" value="<?php echo htmlspecialchars($edit_product['image']); ?>" class="w-full p-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="min_stock">Stok Minimum</label>
                        <input type="number" name="min_stock" id="min_stock" value="<?php echo $edit_product['min_stock']; ?>" class="w-full p-2 border rounded-lg" required min="0">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm text-gray-600 mb-2" for="description">Deskripsi</label>
                        <textarea name="description" id="description" class="w-full p-2 border rounded-lg"><?php echo htmlspecialchars($edit_product['description']); ?></textarea>
                    </div>
                </div>
                <div class="mt-4 flex space-x-2">
                    <button type="submit" class="bg-blue-500 text-white p-2 rounded-lg hover:bg-blue-600">Perbarui Produk</button>
                    <a href="products.php" class="bg-gray-300 text-gray-700 p-2 rounded-lg hover:bg-gray-400">Batal</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Search and Filter -->
        <div class="bg-white p-6 rounded-xl shadow mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Cari dan Filter Produk</h3>
            <form method="GET" action="products.php" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm text-gray-600 mb-2">Cari Nama</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari nama, kategori, atau supplier..." class="w-full p-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-2">Kategori</label>
                    <select name="category" class="w-full p-2 border rounded-lg">
                        <option value="">Semua Kategori</option>
                        <?php while ($cat = $categories_query->fetch_assoc()): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endwhile; $categories_query->data_seek(0); ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-2">Stok</label>
                    <select name="current_stock" class="w-full p-2 border rounded-lg">
                        <option value="">Semua Stok</option>
                        <option value="low" <?php echo $current_stock_filter === 'low' ? 'selected' : ''; ?>>Stok Rendah</option>
                        <option value="sufficient" <?php echo $current_stock_filter === 'sufficient' ? 'selected' : ''; ?>>Stok Cukup</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <button type="submit" class="bg-blue-500 text-white p-2 rounded-lg hover:bg-blue-600">Cari</button>
                    <a href="products.php" class="ml-2 text-blue-500 hover:underline">Reset</a>
                </div>
            </form>
        </div>

        <!-- Product List -->
        <div class="bg-white p-6 rounded-xl shadow">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Daftar Produk</h3>
            <table class="min-w-full text-sm text-gray-600">
                <thead>
                    <tr>
                        <th class="text-left py-2">Nama</th>
                        <th class="text-left py-2">Kategori</th>
                        <th class="text-left py-2">Supplier</th>
                        <th class="text-left py-2">Stok</th>
                        <th class="text-left py-2">Min. Stok</th>
                        <th class="text-left py-2">Harga</th>
                        <th class="text-left py-2">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products_query->num_rows > 0): ?>
                        <?php while ($row = $products_query->fetch_assoc()): ?>
                            <tr class="border-t">
                                <td class="py-2"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($row['category_name'] ?? 'Tidak ada'); ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($row['supplier_name'] ?? 'Tidak ada'); ?></td>
                                <td class="py-2"><?php echo $row['current_stock']; ?></td>
                                <td class="py-2"><?php echo $row['min_stock']; ?></td>
                                <td class="py-2"><?php echo number_format($row['price'], 2); ?></td>
                                <td class="py-2">
                                    <a href="?action=edit&id=<?php echo $row['id']; ?>" class="text-blue-500 hover:underline">Edit</a>
                                    <a href="?action=delete&id=<?php echo $row['id']; ?>" class="text-red-500 hover:underline ml-2" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="py-2 text-center text-gray-500">Tidak ada produk ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
    const toggleButton = document.getElementById('toggleAddForm');
    const addForm = document.getElementById('addForm');
    const cancelButton = document.getElementById('cancelAddForm');

    if (toggleButton && addForm && cancelButton) {
        toggleButton.addEventListener('click', () => {
            addForm.classList.toggle('hidden');
        });
        cancelButton.addEventListener('click', () => {
            addForm.classList.add('hidden');
        });
    }
</script>
</body>
</html>
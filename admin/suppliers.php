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
$error = '';
$success = '';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'add') {
    $name = trim($_POST['name']);
    $contact_person = trim($_POST['contact_person']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if (empty($name) || empty($contact_person) || empty($email) || empty($phone)) {
        $error = "Nama, kontak person, email, dan nomor telepon wajib diisi!";
    } else {
        $query = $conn->prepare("INSERT INTO suppliers (name, contact_person, email, phone, address, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $query->bind_param("sssss", $name, $contact_person, $email, $phone, $address);
        if ($query->execute()) {
            $success = "Supplier berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan supplier: " . $conn->error;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'edit' && $id) {
    $name = trim($_POST['name']);
    $contact_person = trim($_POST['contact_person']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if (empty($name) || empty($contact_person) || empty($email) || empty($phone)) {
        $error = "Nama, kontak person, email, dan nomor telepon wajib diisi!";
    } else {
        $check_query = $conn->prepare("SELECT id FROM suppliers WHERE id = ?");
        $check_query->bind_param("i", $id);
        $check_query->execute();
        if ($check_query->get_result()->num_rows === 0) {
            $error = "Supplier tidak ditemukan!";
        } else {
            $query = $conn->prepare("UPDATE suppliers SET name = ?, contact_person = ?, email = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?");
            $query->bind_param("sssssi", $name, $contact_person, $email, $phone, $address, $id);
            if ($query->execute()) {
                if ($query->affected_rows > 0) {
                    $success = "Supplier berhasil diperbarui!";
                } else {
                    $error = "Tidak ada perubahan data!";
                }
            } else {
                $error = "Gagal memperbarui supplier: " . $conn->error;
            }
        }
    }
}

if ($action == 'delete' && $id) {
    $query = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
    $query->bind_param("i", $id);
    if ($query->execute()) {
        $success = "Supplier berhasil dihapus!";
    } else {
        $error = "Gagal menghapus supplier: " . $conn->error;
    }
}

$edit_supplier = null;
if ($action == 'edit' && $id) {
    $query = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");
    $query->bind_param("i", $id);
    $query->execute();
    $edit_supplier = $query->get_result()->fetch_assoc();
    if (!$edit_supplier) {
        $error = "Supplier tidak ditemukan!";
    }
}

$search_query = $search ? $conn->real_escape_string($search) : '';
$suppliers_sql = "SELECT s.*, MAX(t.created_at) AS latest_transaction 
                 FROM suppliers s 
                 LEFT JOIN products p ON s.id = p.supplier_id 
                 LEFT JOIN transactions t ON p.id = t.product_id 
                 WHERE 1=1";
if ($search_query) {
    $suppliers_sql .= " AND (s.name LIKE '%$search_query%' OR s.contact_person LIKE '%$search_query%' OR s.email LIKE '%$search_query%' OR s.phone LIKE '%$search_query%' OR s.address LIKE '%$search_query%')";
}
$suppliers_sql .= " GROUP BY s.id, s.name, s.contact_person, s.email, s.phone, s.address, s.created_at, s.updated_at";
$suppliers_query = $conn->query($suppliers_sql);

// Fetch purchase history if supplier ID is provided
$supplier_history = [];
if ($id && !$action) {
    $history_query = $conn->prepare("
        SELECT t.id, p.name AS product_name, t.quantity, t.date, t.created_at
        FROM transactions t
        JOIN products p ON t.product_id = p.id
        WHERE p.supplier_id = ?
        ORDER BY t.created_at DESC
    ");
    $history_query->bind_param("i", $id);
    $history_query->execute();
    $supplier_history = $history_query->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier - Penko</title>
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
                <a href="categories.php" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition">
                    <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span class="ml-3 text-sm font-medium">Kategori</span>
                </a>
            </li>
            <li>
                <a href="suppliers.php" class="flex items-center px-4 py-2 rounded-lg text-white bg-blue-500 hover:bg-blue-600 transition">
                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
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
            <h2 class="text-2xl font-semibold text-gray-800">Kelola Supplier</h2>
            <p class="text-sm text-gray-500">Tambah, edit, atau hapus supplier.</p>
        </div>

        <?php if ($error): ?>
            <p class="text-red-500 text-sm mb-4"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="text-green-500 text-sm mb-4"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <!-- Search Bar -->
        <div class="mb-6">
            <form method="GET" action="suppliers.php" class="flex space-x-2">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari supplier..." class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="bg-blue-500 text-white p-2 rounded-lg hover:bg-blue-600">Cari</button>
                <a href="suppliers.php" class="text-blue-500 hover:underline self-center">Reset</a>
            </form>
        </div>

        <!-- Add Supplier Button -->
        <?php if ($action != 'edit'): ?>
        <div class="mb-6">
            <button id="toggleAddForm" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Tambah Supplier</button>
        </div>
        <?php endif; ?>

        <!-- Form for Add Supplier -->
        <?php if ($action != 'edit'): ?>
        <div id="addForm" class="bg-white p-6 rounded-xl shadow mb-6 hidden">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Tambah Supplier</h3>
            <form method="POST" action="?action=add">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="name">Nama Supplier</label>
                        <input type="text" name="name" id="name" class="w-full p-2 border rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="contact_person">Kontak Person</label>
                        <input type="text" name="contact_person" id="contact_person" class="w-full p-2 border rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="email">Email</label>
                        <input type="email" name="email" id="email" class="w-full p-2 border rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="phone">Nomor Telepon</label>
                        <input type="text" name="phone" id="phone" class="w-full p-2 border rounded-lg" required>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm text-gray-600 mb-2" for="address">Alamat</label>
                        <textarea name="address" id="address" class="w-full p-2 border rounded-lg" required></textarea>
                    </div>
                </div>
                <div class="mt-4 flex space-x-2">
                    <button type="submit" class="bg-blue-500 text-white p-2 rounded-lg hover:bg-blue-600">Tambah Supplier</button>
                    <button type="button" id="cancelAddForm" class="bg-gray-300 text-gray-700 p-2 rounded-lg hover:bg-gray-400">Batal</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Form for Edit Supplier -->
        <?php if ($action == 'edit' && $edit_supplier): ?>
        <div class="bg-white p-6 rounded-xl shadow mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Edit Supplier</h3>
            <form method="POST" action="?action=edit&id=<?php echo $edit_supplier['id']; ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="name">Nama Supplier</label>
                        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($edit_supplier['name']); ?>" class="w-full p-2 border rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="contact_person">Kontak Person</label>
                        <input type="text" name="contact_person" id="contact_person" value="<?php echo htmlspecialchars($edit_supplier['contact_person']); ?>" class="w-full p-2 border rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="email">Email</label>
                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($edit_supplier['email']); ?>" class="w-full p-2 border rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2" for="phone">Nomor Telepon</label>
                        <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($edit_supplier['phone']); ?>" class="w-full p-2 border rounded-lg" required>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm text-gray-600 mb-2" for="address">Alamat</label>
                        <textarea name="address" id="address" class="w-full p-2 border rounded-lg" required><?php echo htmlspecialchars($edit_supplier['address']); ?></textarea>
                    </div>
                </div>
                <div class="mt-4 flex space-x-2">
                    <button type="submit" class="bg-blue-500 text-white p-2 rounded-lg hover:bg-blue-600">Simpan Perubahan</button>
                    <a href="suppliers.php" class="bg-gray-300 text-gray-700 p-2 rounded-lg hover:bg-gray-400 text-center">Batal</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Supplier List -->
        <div class="bg-white p-6 rounded-xl shadow">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Daftar Supplier</h3>
            <table class="min-w-full text-sm text-gray-600">
                <thead>
                    <tr>
                        <th class="text-left py-2">ID</th>
                        <th class="text-left py-2">Nama Supplier</th>
                        <th class="text-left py-2">Kontak Person</th>
                        <th class="text-left py-2">Email</th>
                        <th class="text-left py-2">Telepon</th>
                        <th class="text-left py-2">Alamat</th>
                        <th class="text-left py-2">Dibuat</th>
                        <th class="text-left py-2">Riwayat Terbaru</th>
                        <th class="text-left py-2">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($suppliers_query->num_rows > 0): ?>
                        <?php while ($row = $suppliers_query->fetch_assoc()): ?>
                            <tr class="border-t">
                                <td class="py-2"><?php echo $row['id']; ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($row['contact_person']); ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($row['email']); ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td class="py-2"><?php echo htmlspecialchars($row['address']); ?></td>
                                <td class="py-2"><?php echo date('d M Y H:i', strtotime($row['created_at'])); ?></td>
                                <td class="py-2"><?php echo $row['latest_transaction'] ? date('d M Y H:i', strtotime($row['latest_transaction'])) : '-'; ?></td>
                                <td class="py-2">
                                    <a href="?action=edit&id=<?php echo $row['id']; ?>" class="text-blue-500 hover:underline">Edit</a>
                                    <a href="?action=delete&id=<?php echo $row['id']; ?>" class="text-red-500 hover:underline ml-2" onclick="return confirm('Yakin ingin menghapus supplier ini?')">Hapus</a>
                                    <a href="?id=<?php echo $row['id']; ?>" class="text-green-500 hover:underline ml-2">Lihat Histori</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="py-2 text-center text-gray-500">Tidak ada supplier ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Purchase History -->
        <?php if ($id && !$action && !empty($supplier_history)): ?>
        <div class="bg-white p-6 rounded-xl shadow mt-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Histori Pembelian dari <?php echo htmlspecialchars($suppliers_query->fetch_assoc()['name'] ?? 'Supplier'); ?></h3>
            <table class="min-w-full text-sm text-gray-600">
                <thead>
                    <tr>
                        <th class="text-left py-2">ID Transaksi</th>
                        <th class="text-left py-2">Nama Produk</th>
                        <th class="text-left py-2">Jumlah</th>
                        <th class="text-left py-2">Tanggal Transaksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($supplier_history as $row): ?>
                        <tr class="border-t">
                            <td class="py-2">#<?php echo $row['id']; ?></td>
                            <td class="py-2"><?php echo htmlspecialchars($row['product_name']); ?></td>
                            <td class="py-2"><?php echo number_format($row['quantity'], 0, ',', '.'); ?></td>
                            <td class="py-2"><?php echo date('d M Y, H:i', strtotime($row['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (empty($supplier_history)): ?>
                <p class="text-gray-500 text-center mt-4">Tidak ada histori pembelian untuk supplier ini.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
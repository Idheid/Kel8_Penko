<?php
session_start();
require_once '../koneksi/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$showForm = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['show_form'])) {
        $showForm = true;
    } elseif (isset($_POST['submit_request'])) {
        $product_id = $_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        $purpose = trim($_POST['purpose']);

        if ($quantity <= 0 || empty($purpose)) {
            $error = "Jumlah dan keperluan harus diisi!";
        } else {
            $check_stock_query = $conn->prepare("SELECT current_stock FROM products WHERE id = ?");
            $check_stock_query->bind_param("i", $product_id);
            $check_stock_query->execute();
            $current_stock = $check_stock_query->get_result()->fetch_assoc()['current_stock'] ?? 0;

            if ($current_stock < $quantity) {
                $error = "Stok tidak cukup untuk jumlah yang diminta! Stok tersedia: $current_stock";
            } else {
                $check_duplicate = $conn->prepare("SELECT id FROM requests WHERE user_id = ? AND product_id = ? AND quantity = ? AND purpose = ? AND status = 'pending'");
                $check_duplicate->bind_param("iiis", $user_id, $product_id, $quantity, $purpose);
                $check_duplicate->execute();
                $duplicate = $check_duplicate->get_result()->fetch_assoc();

                if (!$duplicate) {
                    $query = $conn->prepare("INSERT INTO requests (user_id, product_id, quantity, purpose, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
                    $query->bind_param("iiis", $user_id, $product_id, $quantity, $purpose);
                    if ($query->execute()) {
                        $success = "Permintaan berhasil diajukan dan sedang menunggu persetujuan!";
                        $showForm = false; // Hide form after successful submission
                    } else {
                        $error = "Gagal mengajukan permintaan: " . $conn->error;
                    }
                } else {
                    $error = "Permintaan dengan detail yang sama sudah ada!";
                }
            }
        }
    }
}

// Fetch pending requests for monitoring
$pending_requests = $conn->prepare("SELECT r.id, p.name AS product_name, r.quantity, r.created_at 
                                 FROM requests r 
                                 JOIN products p ON r.product_id = p.id 
                                 WHERE r.user_id = ? AND r.status = 'pending'");
$pending_requests->bind_param("i", $user_id);
$pending_requests->execute();
$result = $pending_requests->get_result();
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
        .hidden { display: none; }
    </style>
    <script>
        function showForm() {
            document.getElementById('requestForm').classList.remove('hidden');
            document.getElementById('showFormButton').classList.add('hidden');
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
                <li>
                    <a href="dashboard.php" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition">
                        <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l9-6 9 6v10a2 2 0 01-2 2H5a2 2 0 01-2-2V8z" />
                        </svg>
                        <span class="ml-3 text-sm font-medium">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="requests.php" class="flex items-center px-4 py-2 rounded-lg text-white bg-blue-500 hover:bg-blue-600 transition">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-4 0v4m0 0H7m4 0h4" />
                        </svg>
                        <span class="ml-3 text-sm font-medium">Permintaan</span>
                    </a>
                </li>
                <li>
                    <a href="request_history.php" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition">
                        <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="ml-3 text-sm font-medium">Riwayat Permintaan</span>
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
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Ajukan Permintaan Barang</h2>

        <?php if ($error): ?>
            <p class="text-red-500 text-sm mb-4"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="text-green-500 text-sm mb-4"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <!-- Button to Show Form -->
        <?php if (!$showForm): ?>
            <form method="POST" action="">
                <button type="submit" name="show_form" id="showFormButton" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">Ajukan Permintaan</button>
            </form>
        <?php endif; ?>

        <!-- Request Form -->
        <?php if ($showForm): ?>
            <div id="requestForm" class="bg-white p-6 rounded-xl mb-6 shadow hover:shadow-md transition">
                <form method="POST" action="" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-2" for="product_id">Jenis Barang</label>
                            <select name="product_id" id="product_id" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500" required>
                                <?php
                                $products_query = $conn->query("SELECT id, name FROM products");
                                while ($product = $products_query->fetch_assoc()) {
                                    echo "<option value='{$product['id']}'>{$product['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-2" for="quantity">Jumlah</label>
                            <input type="number" name="quantity" id="quantity" min="1" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm text-gray-600 mb-2" for="purpose">Keperluan Penggunaan</label>
                            <textarea name="purpose" id="purpose" class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500" required></textarea>
                        </div>
                    </div>
                    <div>
                        <button type="submit" name="submit_request" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">Ajukan Permintaan</button>
                        <button type="button" onclick="window.location.href='requests.php'" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors ml-2">Batal</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Monitor Pending Requests -->
        <div class="bg-white p-6 rounded-xl shadow hover:shadow-md transition">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Status Permintaan Anda</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-gray-700 border-collapse">
                    <thead>
                        <tr class="bg-gray-100 border-b">
                            <th class="py-2 px-4 text-left">ID</th>
                            <th class="py-2 px-4 text-left">Produk</th>
                            <th class="py-2 px-4 text-left">Jumlah</th>
                            <th class="py-2 px-4 text-left">Tanggal Permintaan</th>
                            <th class="py-2 px-4 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-4"><?php echo $row['id']; ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($row['product_name']); ?></td>
                                    <td class="py-2 px-4"><?php echo number_format($row['quantity'], 0, ',', '.'); ?></td>
                                    <td class="py-2 px-4"><?php echo date('d M Y H:i', strtotime($row['created_at'])); ?></td>
                                    <td class="py-2 px-4"><span class="status-pending">Menunggu</span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="py-4 text-center text-gray-500">Tidak ada permintaan yang menunggu persetujuan.</td>
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
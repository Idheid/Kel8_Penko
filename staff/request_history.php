<?php
session_start();
require_once '../koneksi/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$requests = $conn->query("SELECT r.id, r.quantity, r.status, r.created_at, r.processed_at, p.name AS product_name, r.purpose 
                         FROM requests r 
                         JOIN products p ON r.product_id = p.id 
                         WHERE r.user_id = $user_id ORDER BY r.created_at DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Permintaan - Penko</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .status-pending { @apply bg-yellow-400 text-white px-2 py-1 rounded; }
        .status-approved { @apply bg-green-500 text-white px-2 py-1 rounded; }
        .status-rejected { @apply bg-red-500 text-white px-2 py-1 rounded; }
    </style>
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
                <a href="requests.php" class="flex items-center px-4 py-2 rounded-lg text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition">
                    <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-4 0v4m0 0H7m4 0h4" />
                    </svg>
                    <span class="ml-3 text-sm font-medium">Permintaan</span>
                </a>
            </li>
            <li>
                <a href="request_history.php" class="flex items-center px-4 py-2 rounded-lg text-white bg-blue-500 hover:bg-blue-600 transition">
                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
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
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Riwayat Permintaan</h2>

        <div class="bg-white p-6 rounded-xl shadow hover:shadow-md transition">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-gray-700 border-collapse">
                    <thead>
                        <tr class="bg-gray-100 border-b">
                            <th class="py-2 px-4 text-left">ID</th>
                            <th class="py-2 px-4 text-left">Produk</th>
                            <th class="py-2 px-4 text-left">Jumlah</th>
                            <th class="py-2 px-4 text-left">Keperluan</th>
                            <th class="py-2 px-4 text-left">Status</th>
                            <th class="py-2 px-4 text-left">Tanggal Permintaan</th>
                            <th class="py-2 px-4 text-left">Tanggal Diproses</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($requests->num_rows > 0): ?>
                            <?php while ($row = $requests->fetch_assoc()): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-4"><?php echo $row['id']; ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($row['product_name']); ?></td>
                                    <td class="py-2 px-4"><?php echo number_format($row['quantity'], 0, ',', '.'); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($row['purpose']); ?></td>
                                    <td class="py-2 px-4">
                                        <?php
                                        $status_text = '';
                                        switch (strtolower($row['status'])) {
                                            case 'pending':
                                                $status_text = 'Menunggu';
                                                break;
                                            case 'approved':
                                                $status_text = 'Disetujui';
                                                break;
                                            case 'rejected':
                                                $status_text = 'Ditolak';
                                                break;
                                        }
                                        ?>
                                        <span class="status-<?php echo strtolower($row['status']); ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td class="py-2 px-4"><?php echo date('d M Y H:i', strtotime($row['created_at'])); ?></td>
                                    <td class="py-2 px-4"><?php echo $row['processed_at'] ? date('d M Y H:i', strtotime($row['processed_at'])) : '-'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="py-4 text-center text-gray-500">Tidak ada riwayat permintaan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($requests->num_rows > 0 && $requests->num_rows > 0): ?>
                <p class="mt-4 text-sm text-gray-600">Jika permintaan Anda disetujui, ambil barang di bagian logistik sesuai jumlah yang disetujui.</p>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
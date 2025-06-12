<?php
session_start();
require_once 'koneksi/database.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: staff/dashboard.php');
    }
    exit();
}

// Proses login
if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Validasi input
    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi!";
    } else {
        // Cek kredensial menggunakan prepared statement
        $stmt = $conn->prepare("SELECT id, username, password, name, email, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verifikasi password
            if (password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect berdasarkan role
                if ($user['role'] == 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: staff/dashboard.php');
                }
                exit();
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Username tidak ditemukan!";
        }
        $stmt->close();
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login - Penko</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-200/80 backdrop-blur-sm min-h-screen flex items-center justify-center px-4">

  <!-- Form Container -->
  <div class="w-full max-w-5xl bg-white rounded-3xl shadow-lg overflow-hidden grid grid-cols-1 md:grid-cols-2">

    <!-- Form Login -->
    <div class="p-10 flex flex-col justify-center">
      <div class="mb-6">
        <h2 class="text-gray-700 text-sm">Welcome back</h2>
        <h1 class="text-4xl font-bold mt-1 mb-2 text-gray-900">Login to Penko<span class="text-blue-500">.</span></h1>
      </div>

      <?php if (isset($error)): ?>
        <div class="bg-red-100 text-red-600 p-3 rounded mb-4">
          <?= $error ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-4">
        <input type="text" name="username" placeholder="Username" class="w-full px-4 py-3 rounded-md bg-gray-100 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500" required>

        <input type="password" name="password" placeholder="Password" class="w-full px-4 py-3 rounded-md bg-gray-100 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500" required>

        <button type="submit" class="w-full py-3 bg-blue-500 text-white font-semibold rounded-md hover:bg-blue-600 transition duration-200">Login</button>
      </form>
    </div>

    <!-- Image Section -->
      <div class="hidden md:block relative">
        <img src="img/gedung.jpg" alt="Login Background" class="object-cover h-full w-full rounded-r-3xl" />
        <div class="absolute bottom-4 right-4 text-white font-semibold text-sm">© Penko App</div>
      </div>

  </div>

</body>
</html>

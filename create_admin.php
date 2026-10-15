<?php
// create_admin.php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

// --- KONFIGURASI ADMIN BARU ---
$username = getenv("ADMIN_USERNAME");          // Username Admin
$password = getenv("ADMIN_PASSWORD");       // Password Admin
// ------------------------------

if (!$username || !$password) { http_response_code(400); exit("Set ADMIN_USERNAME and ADMIN_PASSWORD before provisioning."); }

// 1. Cek apakah admin sudah ada biar tidak dobel
$check = $conn->prepare("SELECT id FROM users WHERE username = :username");
$check->execute(['username' => $username]);

if ($check->rowCount() > 0) {
    echo "<h1>GAGAL: User '$username' sudah ada.</h1>";
    echo "<p>Silakan login atau gunakan username lain.</p>";
} else {
    // 2. Hash Password (PENTING!)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 3. Masukkan ke Database dengan role 'admin'
    $sql = "INSERT INTO users (username, password, role) VALUES (:u, :p, 'admin')";
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute(['u' => $username, 'p' => $hashed_password])) {
        echo "<h1 style='color: green;'>SUKSES!</h1>";
        echo "<p>Akun Admin berhasil dibuat.</p>";
        echo "<ul>
                <li>Username: <strong>$username</strong></li>
                <li>Password: <strong>$password</strong></li>
              </ul>";
        echo "<p><a href='login.php'>Klik disini untuk Login</a></p>";
        echo "<hr><p style='color: red;'>PENTING: Segera hapus file create_admin.php ini setelah berhasil!</p>";
    } else {
        echo "<h1>ERROR: Gagal membuat akun.</h1>";
    }
}
?>
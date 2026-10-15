<?php
// controllers/AuthController.php

// Pastikan session dimulai sebelum baris kode lainnya
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hubungkan dengan konfigurasi database
// Asumsi: File ini ada di folder /controllers, jadi kita naik satu level (../) ke config
require_once __DIR__ . '/../config/database.php';

class AuthController {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * REGISTER: Mendaftarkan member baru
     */
    public function register($username, $password) {
        // 1. Sanitasi input dasar
        $username = trim($username);
        
        if(empty($username) || empty($password)) {
            return "Username dan Password tidak boleh kosong.";
        }

        // 2. Cek apakah username sudah ada
        try {
            $checkQuery = "SELECT id FROM users WHERE username = :username";
            $stmt = $this->conn->prepare($checkQuery);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                return "Username '$username' sudah terdaftar. Silakan pilih yang lain.";
            }

            // 3. Enkripsi Password (Hashing)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // 4. Insert ke Database (Default role: reader)
            $insertQuery = "INSERT INTO users (username, password, role) VALUES (:username, :password, 'reader')";
            $stmt = $this->conn->prepare($insertQuery);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $hashed_password);
            
            if($stmt->execute()) {
                return true; // Berhasil
            } else {
                return "Gagal menyimpan data ke sistem.";
            }

        } catch(PDOException $e) {
            return "Database Error: " . $e->getMessage();
        }
    }

    /**
     * LOGIN: Masuk sebagai Member atau Admin
     */
    public function login($username, $password) {
        try {
            $query = "SELECT * FROM users WHERE username = :username LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verifikasi password hash
            if($user && password_verify($password, $user['password'])) {
                // Set Session Variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role']; // 'admin' atau 'reader'
                $_SESSION['is_logged_in'] = true;
                return true;
            } else {
                return false; // Username atau password salah
            }
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * GUEST LOGIN: Masuk tanpa daftar (Limitasi 5 Halaman)
     */
    public function loginAsGuest() {
        // Generate ID Unik: GUEST-JK92 (Contoh)
        // Menggunakan uniqid dan random number agar tidak bentrok
        $randomCode = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
        $guestId = 'GUEST-' . $randomCode;
        
        // Set Session Khusus Guest
        $_SESSION['user_id'] = 0; // 0 menandakan tidak ada di database user
        $_SESSION['username'] = $guestId;
        $_SESSION['role'] = 'guest'; // Role khusus untuk logic pembatasan halaman nanti
        $_SESSION['is_logged_in'] = true;
        
        return true;
    }

    /**
     * LOGOUT: Hapus sesi
     */
    public function logout() {
        // Hapus semua variabel session
        $_SESSION = array();

        // Hapus cookie session jika ada
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Hancurkan session
        session_destroy();

        // Redirect ke halaman utama atau login
        header("Location: ../index.php");
        exit;
    }
}

// --- ROUTER SECTION ---
// Bagian ini menangani request dari Form (POST) atau URL (GET)

$auth = new AuthController();

// 1. Handle POST Requests (Form Submit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // -- REGISTER --
    if ($_POST['action'] === 'register') {
        $result = $auth->register($_POST['username'], $_POST['password']);
        
        if ($result === true) {
            // Redirect ke login dengan pesan sukses
            header("Location: ../login.php?success=1");
        } else {
            // Redirect kembali ke register dengan pesan error
            header("Location: ../register.php?error=" . urlencode($result));
        }
        exit;
    }
    
    // -- LOGIN --
    elseif ($_POST['action'] === 'login') {
        $loginSuccess = $auth->login($_POST['username'], $_POST['password']);
        
        if ($loginSuccess) {
            // Cek role untuk redirect
            if ($_SESSION['role'] === 'admin') {
                header("Location: ../admin/index.php");
            } else {
                header("Location: ../index.php");
            }
        } else {
            header("Location: ../login.php?error=Kombinasi Username/Password Salah");
        }
        exit;
    }

    // -- GUEST MODE --
    elseif ($_POST['action'] === 'guest') {
        $auth->loginAsGuest();
        header("Location: ../index.php"); // Guest selalu ke home
        exit;
    }
}

// 2. Handle GET Requests (Link Logout)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    if ($_GET['action'] === 'logout') {
        $auth->logout();
    }
}
?>
<?php
// admin/index.php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit;
}
require_once '../config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Ambil Statistik Sederhana
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_comics = $conn->query("SELECT COUNT(*) FROM comics")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Command Center - HoloPage</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-grid {
            display: grid;
            grid-template-columns: 250px 1fr;
            height: 100vh;
        }
        .sidebar {
            background: rgba(255, 255, 255, 0.05);
            border-right: 1px solid var(--neon-cyan);
            padding: 20px;
        }
        .content { padding: 40px; overflow-y: auto; }
        .stat-card {
            background: rgba(0, 243, 255, 0.1);
            border: 1px solid var(--neon-cyan);
            padding: 20px;
            margin-right: 20px;
            display: inline-block;
            width: 200px;
        }
        .menu-item {
            display: block;
            padding: 15px;
            color: #888;
            text-decoration: none;
            border-bottom: 1px solid #333;
            transition: 0.3s;
        }
        .menu-item:hover { color: var(--neon-cyan); padding-left: 20px; }
    </style>
</head>
<body>

<div class="admin-grid">
    <div class="sidebar">
        <h2 style="color: var(--neon-cyan); margin-bottom: 30px;">HOLOPAGE<br><span style="font-size: 0.6em; color: #fff;">COMMAND CENTER</span></h2>
        <a href="index.php" class="menu-item" style="color: var(--neon-cyan);">DASHBOARD</a>
        <a href="upload_comic.php" class="menu-item">+ NEW COMIC</a>
        <a href="upload_chapter.php" class="menu-item">+ UPLOAD CHAPTER</a>
        <a href="../index.php" class="menu-item">VISIT SITE</a>
        <a href="../controllers/AuthController.php?action=logout" class="menu-item" style="color: #ff4d4d;">LOGOUT</a>
    </div>

    <div class="content">
        <h1 style="margin-bottom: 30px;">SYSTEM OVERVIEW</h1>
        
        <div class="stat-card">
            <h3>TOTAL USERS</h3>
            <div style="font-size: 3rem; color: var(--neon-cyan);"><?php echo $total_users; ?></div>
        </div>
        
        <div class="stat-card" style="border-color: var(--neon-pink); background: rgba(188, 19, 254, 0.1);">
            <h3>TOTAL COMICS</h3>
            <div style="font-size: 3rem; color: var(--neon-pink);"><?php echo $total_comics; ?></div>
        </div>

        </div>
</div>

</body>
</html>
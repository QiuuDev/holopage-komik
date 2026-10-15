<?php
// index.php
session_start();
require_once 'config/database.php';

// Koneksi Database
$db = new Database();
$conn = $db->getConnection();

// Logika Pencarian Sederhana
$search = isset($_GET['q']) ? $_GET['q'] : '';
$sql = "SELECT * FROM comics";
if ($search) {
    $sql .= " WHERE title LIKE :search";
}
$sql .= " ORDER BY created_at DESC"; // Komik terbaru muncul duluan

$stmt = $conn->prepare($sql);
if ($search) {
    $term = "%$search%";
    $stmt->bindParam(':search', $term);
}
$stmt->execute();
$comics = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HoloPage - Arsip Komik Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <nav class="navbar">
        <div class="brand-logo">HoloPage_</div>
        
        <form action="" method="GET" style="display: flex; gap: 10px;">
            <input type="text" name="q" placeholder="SEARCH COMICS..." value="<?php echo htmlspecialchars($search); ?>" 
                   style="background: rgba(0,0,0,0.5); border: 1px solid var(--neon-cyan); padding: 8px; color: var(--neon-cyan); outline: none;">
            <button type="submit" class="btn-holo" style="padding: 5px 15px;">SCAN</button>
        </form>

        <div class="nav-links" style="display: flex; gap: 15px; align-items: center;">
            <?php if(isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
                <a href="profile.php" style="color: var(--neon-cyan); font-size: 0.9rem; text-decoration: none; border: 1px solid var(--neon-cyan); padding: 5px 10px;">
                    ID Profil: <?php echo $_SESSION['username']; ?>
                </a>
                <?php if($_SESSION['role'] == 'admin'): ?>
                    <a href="admin/index.php" class="btn-holo" style="font-size: 0.8rem;">ADMIN PANEL</a>
                <?php endif; ?>
                <a href="controllers/AuthController.php?action=logout" style="color: #ff4d4d; text-decoration: none; font-size: 0.9rem;">LOGOUT</a>
            <?php else: ?>
                <a href="login.php" class="btn-holo">LOGIN SYSTEM</a>
            <?php endif; ?>
        </div>
    </nav>

    <div style="text-align: center; padding: 40px 20px;">
        <h1 style="font-size: 2.5rem; text-shadow: 0 0 20px var(--neon-cyan);">AVAILABLE ARCHIVES</h1>
        <p style="color: #888;">Select a file to begin simulation.</p>
    </div>

    <main class="comic-grid">
        
        <?php if (count($comics) > 0): ?>
            <?php foreach ($comics as $comic): ?>
                <a href="details.php?id=<?php echo $comic['id']; ?>" class="comic-card">
                    <img src="uploads/covers/<?php echo htmlspecialchars($comic['cover_image']); ?>"
                         alt="<?php echo htmlspecialchars($comic['title']); ?>"
                         class="comic-cover">
                    
                    <div class="comic-info">
                        <h3 class="comic-title"><?php echo htmlspecialchars($comic['title']); ?></h3>
                        
                        <span class="comic-status <?php echo ($comic['status'] == 'completed') ? 'status-completed' : 'status-ongoing'; ?>">
                            <?php echo strtoupper($comic['status']); ?>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 50px; color: #555;">
                <h2>DATA NOT FOUND</h2>
                <p>Arsip kosong atau belum ada komik yang diupload.</p>
            </div>
        <?php endif; ?>

    </main>

    <?php include 'includes/footer.php'; ?>

</body>
</html>
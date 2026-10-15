<?php
// profile.php - Guest Safe Version
session_start();
require_once 'config/database.php';

// 1. Cek Login Dasar
if (!isset($_SESSION['is_logged_in'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$db = new Database();
$conn = $db->getConnection();

// Variabel Default (Untuk mencegah error undefind variable pada mode Guest)
$user = null;
$histories = [];

// 2. LOGIKA PENGAMBILAN DATA (Hanya jika BUKAN Guest)
if ($role !== 'guest') {
    // Ambil Data User
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ambil History (Hanya Chapter Terakhir per Komik)
    $queryHistory = "
        SELECT h.*, 
               c.chapter_number, c.title as chapter_title, 
               com.title as comic_title, com.cover_image, com.id as comic_id 
        FROM history h
        JOIN chapters c ON h.chapter_id = c.id
        JOIN comics com ON c.comic_id = com.id
        WHERE h.user_id = :uid
        AND h.last_read_at = (
            SELECT MAX(h2.last_read_at)
            FROM history h2
            JOIN chapters c2 ON h2.chapter_id = c2.id
            WHERE c2.comic_id = com.id
            AND h2.user_id = :uid
        )
        ORDER BY h.last_read_at DESC
    ";

    $stmtH = $conn->prepare($queryHistory);
    $stmtH->execute(['uid' => $user_id]);
    $histories = $stmtH->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dossier</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="brand-logo" style="text-decoration: none;">HoloPage_</a>
        <div class="nav-links">
            <a href="index.php" class="btn-holo">KEMBALI KE ARSIP</a>
        </div>
    </nav>

    <div class="profile-container">
        
        <div class="dossier-card">
            
            <?php if ($role === 'guest'): ?>
                <div class="avatar-frame" style="border-color: var(--neon-pink);">
                    <i class="fas fa-user-secret" style="color: var(--neon-pink);"></i>
                </div>
                
                <h2 style="color: var(--neon-pink); letter-spacing: 2px;">UNKNOWN</h2>
                
                <div class="user-rank" style="background: var(--neon-pink); color: #fff;">
                    RESTRICTED ACCESS
                </div>

                <div style="margin-top: 30px; text-align: left; border-top: 1px solid #333; padding-top: 20px;">
                    <p style="color: #777; font-size: 0.8rem; margin-bottom: 5px;">ID SESSION:</p>
                    <p style="color: #fff; font-family: monospace; margin-bottom: 15px;">
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </p>

                    <p style="color: #777; font-size: 0.8rem; margin-bottom: 5px;">STATUS:</p>
                    <p style="color: #ff4d4d; font-family: monospace;">
                        <i class="fas fa-exclamation-triangle"></i> NOT SAVED
                    </p>
                </div>
            
            <?php else: ?>
                <div class="avatar-frame">
                    <i class="fas fa-user-astronaut"></i>
                </div>
                
                <h2 style="color: var(--neon-cyan); letter-spacing: 2px;">
                    <?php echo strtoupper(htmlspecialchars($user['username'])); ?>
                </h2>
                
                <div class="user-rank">
                    <?php echo ($user['role'] == 'admin') ? 'SYSTEM ADMIN' : 'READER CLASS-A'; ?>
                </div>

                <div style="margin-top: 30px; text-align: left; border-top: 1px solid #333; padding-top: 20px;">
                    <p style="color: #777; font-size: 0.8rem; margin-bottom: 5px;">ID REGISTRATION:</p>
                    <p style="color: #fff; font-family: monospace; margin-bottom: 15px;">
                        #USER-<?php echo str_pad($user['id'], 6, '0', STR_PAD_LEFT); ?>
                    </p>

                    <p style="color: #777; font-size: 0.8rem; margin-bottom: 5px;">JOIN DATE:</p>
                    <p style="color: #fff; font-family: monospace; margin-bottom: 15px;">
                        <?php echo date('Y-m-d', strtotime($user['created_at'])); ?>
                    </p>
                    
                    <p style="color: #777; font-size: 0.8rem; margin-bottom: 5px;">STATUS:</p>
                    <p style="color: #25D366; font-family: monospace;">
                        <i class="fas fa-circle" style="font-size: 0.6rem;"></i> ACTIVE
                    </p>
                </div>
            <?php endif; ?>

            <div style="margin-top: 30px;">
                <a href="controllers/AuthController.php?action=logout" class="btn-holo" style="border-color: #ff4d4d; color: #ff4d4d; width: 100%; display: block;">
                    <i class="fas fa-power-off"></i> <?php echo ($role === 'guest') ? 'EXIT GUEST MODE' : 'TERMINATE SESSION'; ?>
                </a>
            </div>
        </div>

        <div class="history-panel">
            
            <div class="history-header">
                <h3 style="color: var(--neon-cyan); font-family: 'Orbitron';">
                    <i class="fas fa-bookmark"></i> LAST READ PROGRESS
                </h3>
                <?php if ($role !== 'guest'): ?>
                    <span style="font-size: 0.8rem; color: #666;">
                        <?php echo count($histories); ?> COMICS TRACKED
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($role === 'guest'): ?>
                
                <div style="text-align: center; padding: 50px 20px; color: #777;">
                    <i class="fas fa-lock" style="font-size: 4rem; color: #333; margin-bottom: 20px;"></i>
                    <h2 style="color: #555; margin-bottom: 10px;">DATA ENCRYPTED</h2>
                    <p style="font-size: 0.9rem; margin-bottom: 30px; max-width: 400px; margin-left: auto; margin-right: auto;">
                        Guest users do not have access to persistent reading logs or bookmarks. 
                        Your progress will be lost upon exit.
                    </p>
                    
                    <a href="register.php" class="btn-holo" style="background: rgba(0, 243, 255, 0.1);">
                        INITIALIZE REGISTRATION
                    </a>
                </div>

            <?php else: ?>
                
                <?php if (count($histories) > 0): ?>
                    <div class="history-list">
                        <?php foreach ($histories as $h): ?>
                            <div class="history-item">
                                <img src="uploads/covers/<?php echo htmlspecialchars($h['cover_image']); ?>" class="h-thumb" alt="Cover">
                                
                                <div class="h-info">
                                    <a href="details.php?id=<?php echo $h['comic_id']; ?>" class="h-title">
                                        <?php echo htmlspecialchars($h['comic_title']); ?>
                                    </a>
                                    
                                    <div class="h-meta" style="color: var(--neon-cyan);">
                                        <i class="fas fa-map-marker-alt"></i> Stopped at: 
                                        <strong>Chapter <?php echo $h['chapter_number']; ?></strong> 
                                        <?php if($h['chapter_title']) echo "- " . htmlspecialchars($h['chapter_title']); ?>
                                    </div>
                                    
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                                        <span style="font-size: 0.8rem; color: #555;">
                                            <i class="far fa-clock"></i> 
                                            <?php echo date('d M Y, H:i', strtotime($h['last_read_at'])); ?>
                                        </span>
                                        
                                        <a href="read.php?chapter_id=<?php echo $h['chapter_id']; ?>" class="h-progress" style="background: rgba(0, 243, 255, 0.1);">
                                            CONTINUE READING <i class="fas fa-play" style="font-size: 0.6rem; margin-left: 5px;"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 50px; color: #555;">
                        <i class="fas fa-database" style="font-size: 3rem; margin-bottom: 20px;"></i>
                        <p>NO DATA FOUND.</p>
                        <p style="font-size: 0.9rem;">Start reading to populate your mission logs.</p>
                        <br>
                        <a href="index.php" class="btn-holo">BROWSE ARCHIVES</a>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

</body>
</html>
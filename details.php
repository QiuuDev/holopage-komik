<?php
// details.php
session_start();
require_once 'config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { header("Location: index.php"); exit; }

$comic_id = $_GET['id'];
$db = new Database(); $conn = $db->getConnection();

// Ambil Data Komik
$query = "SELECT * FROM comics WHERE id = :id";
$stmt = $conn->prepare($query);
$stmt->execute(['id' => $comic_id]);
$comic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$comic) { echo "<h1>404: DATA CORRUPTED.</h1>"; exit; }

// Ambil Chapters
$queryCh = "SELECT * FROM chapters WHERE comic_id = :id ORDER BY chapter_number DESC";
$stmtCh = $conn->prepare($queryCh);
$stmtCh->execute(['id' => $comic_id]);
$chapters = $stmtCh->fetchAll(PDO::FETCH_ASSOC);
$first_chapter_id = end($chapters)['id'] ?? null;

// CEK STATUS FAVORIT USER
$is_fav = false;
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0) {
    $cekFav = $conn->prepare("SELECT id FROM favorites WHERE user_id = :uid AND comic_id = :cid");
    $cekFav->execute(['uid' => $_SESSION['user_id'], 'cid' => $comic_id]);
    if ($cekFav->rowCount() > 0) $is_fav = true;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($comic['title']); ?> - HoloPage</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="brand-logo" style="text-decoration: none;">HoloPage_</a>
        <div class="nav-links"><a href="index.php" class="btn-holo">KEMBALI KE ARSIP</a></div>
    </nav>

    <div class="detail-container">
        <div>
            <img src="uploads/covers/<?php echo htmlspecialchars($comic['cover_image']); ?>" alt="Cover" class="detail-cover">
            
            <div style="margin-top: 20px; display: grid; gap: 10px;">
                <?php if ($first_chapter_id): ?>
                    <a href="read.php?chapter_id=<?php echo $first_chapter_id; ?>" class="btn-holo" style="text-align: center; background: var(--neon-cyan); color: #000;">
                        MULAI BACA (CH. 1)
                    </a>
                <?php else: ?>
                    <button class="btn-holo" disabled style="opacity: 0.5;">BELUM ADA CHAPTER</button>
                <?php endif; ?>
                
                <button id="btnFav" class="btn-holo" onclick="toggleFav(<?php echo $comic_id; ?>)" style="text-align: center; <?php echo $is_fav ? 'border-color: var(--neon-pink); color: var(--neon-pink);' : ''; ?>">
                    <i class="<?php echo $is_fav ? 'fas' : 'far'; ?> fa-heart"></i> 
                    <span id="txtFav"><?php echo $is_fav ? 'DI SIMPAN KE FAVORIT' : 'TAMBAH KE FAVORIT'; ?></span>
                </button>
            </div>
        </div>

        <div class="detail-info">
            <h1><?php echo htmlspecialchars($comic['title']); ?></h1>
            <div style="margin-bottom: 20px; font-size: 0.9rem; color: #888;">
                <span style="margin-right: 20px;"><i class="fas fa-layer-group"></i> Total Chapter: <?php echo count($chapters); ?></span>
                <span class="comic-status <?php echo ($comic['status'] == 'completed') ? 'status-completed' : 'status-ongoing'; ?>">
                    <?php echo strtoupper($comic['status']); ?>
                </span>
            </div>
            <div class="synopsis">
                <h3><i class="fas fa-info-circle"></i> SINOPSIS</h3>
                <p><?php echo nl2br(htmlspecialchars($comic['description'])); ?></p>
            </div>

            <h3 style="margin-top: 40px; border-bottom: 1px solid #333; padding-bottom: 10px; color: var(--neon-cyan);">
                <i class="fas fa-list"></i> AKSES CHAPTER
            </h3>
            
            <div class="chapter-list-container">
                <?php if (count($chapters) > 0): ?>
                    <?php foreach ($chapters as $ch): ?>
                        <a href="read.php?chapter_id=<?php echo $ch['id']; ?>" class="chapter-item">
                            <span>
                                <strong>Chapter <?php echo $ch['chapter_number']; ?></strong> 
                                <?php if($ch['title']) echo "- " . htmlspecialchars($ch['title']); ?>
                            </span>
                            <span class="chapter-date"><?php echo date('d M Y', strtotime($ch['created_at'])); ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 20px; text-align: center; color: #555;">DATA KOSONG.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
    function toggleFav(comicId) {
        <?php if(!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0): ?>
            alert("Guest Access: Silakan Login untuk menyimpan favorit.");
            window.location.href = "login.php";
            return;
        <?php endif; ?>

        const btn = document.getElementById('btnFav');
        const txt = document.getElementById('txtFav');
        const icon = btn.querySelector('i');

        fetch('controllers/InteractionController.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=toggle_favorite&comic_id=' + comicId
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                if(data.is_favorite) {
                    txt.innerText = "DI SIMPAN KE FAVORIT";
                    icon.classList.remove('far'); icon.classList.add('fas');
                    btn.style.borderColor = "var(--neon-pink)";
                    btn.style.color = "var(--neon-pink)";
                } else {
                    txt.innerText = "TAMBAH KE FAVORIT";
                    icon.classList.remove('fas'); icon.classList.add('far');
                    btn.style.borderColor = "var(--neon-cyan)";
                    btn.style.color = "var(--neon-cyan)";
                }
            }
        });
    }
    </script>
</body>
</html>
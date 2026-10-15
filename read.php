<?php
// read.php - HoloPage Ultimate Version
session_start();
require_once 'config/database.php';

// 1. Validasi Login Dasar
if (!isset($_SESSION['is_logged_in'])) {
    header("Location: login.php?error=" . urlencode("Silakan login untuk mengakses arsip."));
    exit;
}

// 2. Validasi ID Chapter
$chapter_id = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : 0;
if ($chapter_id === 0) die("Error: Chapter ID Invalid.");

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// 3. Ambil Data Chapter & Komik
$stmt = $conn->prepare("
    SELECT c.id, c.chapter_number, c.title as c_title, 
           com.id as com_id, com.title as com_title 
    FROM chapters c 
    JOIN comics com ON c.comic_id = com.id 
    WHERE c.id = :id
");
$stmt->execute(['id' => $chapter_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) die("Error: Data Chapter Tidak Ditemukan.");

// --- [LOGIKA BARU] LIMIT GUEST (CHAPTER > 5 DIBLOKIR) ---
if ($role === 'guest' && $data['chapter_number'] > 5) {
    header("Location: login.php?error=" . urlencode("Akses Ditolak: Tamu hanya dapat membaca hingga Chapter 5. Silakan Register/Login untuk akses penuh."));
    exit;
}
// --------------------------------------------------------

// 4. Navigasi Next/Prev Chapter
// Prev
$prevQ = $conn->prepare("SELECT id FROM chapters WHERE comic_id = :cid AND chapter_number < :cnum ORDER BY chapter_number DESC LIMIT 1");
$prevQ->execute(['cid' => $data['com_id'], 'cnum' => $data['chapter_number']]);
$prev_chapter = $prevQ->fetchColumn();

// Next
$nextQ = $conn->prepare("SELECT id FROM chapters WHERE comic_id = :cid AND chapter_number > :cnum ORDER BY chapter_number ASC LIMIT 1");
$nextQ->execute(['cid' => $data['com_id'], 'cnum' => $data['chapter_number']]);
$next_chapter = $nextQ->fetchColumn();

// 5. Ambil Pages (Gambar)
$stmtPage = $conn->prepare("SELECT * FROM pages WHERE chapter_id = :cid ORDER BY page_order ASC");
$stmtPage->execute(['cid' => $chapter_id]);
$all_pages = $stmtPage->fetchAll(PDO::FETCH_ASSOC);

// 6. Cek Status Like User Saat Ini
$is_liked = false;
$total_likes = 0;

if ($user_id != 0) { // Hanya cek jika bukan guest murni (id 0)
    $like_check = $conn->prepare("SELECT id FROM likes WHERE user_id = :uid AND chapter_id = :cid");
    $like_check->execute(['uid' => $user_id, 'cid' => $chapter_id]);
    $is_liked = ($like_check->rowCount() > 0);
}

// Hitung Total Like
$like_count = $conn->prepare("SELECT COUNT(*) FROM likes WHERE chapter_id = :cid");
$like_count->execute(['cid' => $chapter_id]);
$total_likes = $like_count->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reading: <?php echo htmlspecialchars($data['com_title']); ?></title>
    
    <link rel="stylesheet" href="assets/css/style.css">
    
    <script src="https://cdn.jsdelivr.net/npm/page-flip/dist/js/page-flip.browser.js"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="reader-body">

    <div class="cyber-background">
        <div class="cyber-grid"></div> <div class="cyber-particles" id="particles"></div> </div>

    <div class="reader-header">
        <a href="details.php?id=<?php echo $data['com_id']; ?>" class="back-btn">
            <i class="fas fa-chevron-left"></i> <span style="font-size: 0.9rem;">ARSIP UTAMA</span>
        </a>
        <div class="reader-title">
            <?php echo htmlspecialchars($data['com_title']); ?> 
            <span style="color: #555; margin: 0 5px;">//</span> 
            CH. <?php echo $data['chapter_number']; ?>
        </div>
        <div style="width: 80px;"></div> </div>

    <div class="book-wrapper">
        <div class="holo-emitter">
            <div id="book">
                
                <div class="page special-page" data-density="hard">
                    <i class="fas fa-fingerprint" style="font-size: 3rem; margin-bottom: 20px;"></i>
                    <h1>CH. <?php echo $data['chapter_number']; ?></h1>
                    <p style="letter-spacing: 2px; font-size: 0.8rem; color: var(--text-main);">LOADING VISUAL ASSETS...</p>
                    
                    <div style="margin-top: 30px; width: 50%; height: 2px; background: #333; position: relative; overflow: hidden;">
                        <div style="position: absolute; width: 30%; height: 100%; background: var(--neon-cyan); animation: loadingBar 2s infinite;"></div>
                    </div>
                    <style>@keyframes loadingBar { 0% {left: -30%;} 100% {left: 100%;} }</style>
                </div>

                <?php foreach ($all_pages as $page): ?>
                    <div class="page">
                        <img src="uploads/pages/<?php echo htmlspecialchars($page['image_url']); ?>" loading="lazy">
                        <div class="page-footer"><?php echo $page['page_order']; ?></div>
                    </div>
                <?php endforeach; ?>

                <div class="page special-page" data-density="hard">
                    <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 20px;"></i>
                    <h2>COMPLETE</h2>
                    <p style="color: #666; margin-bottom: 20px;">End of transmission.</p>

                    <?php if ($next_chapter): ?>
                        <a href="read.php?chapter_id=<?php echo $next_chapter; ?>" class="btn-holo" style="margin-top: 20px;">
                            NEXT DATA <i class="fas fa-arrow-right"></i>
                        </a>
                    <?php else: ?>
                        <p style="color: #888;">Latest entry reached.</p>
                        <a href="details.php?id=<?php echo $data['com_id']; ?>" class="btn-holo" style="margin-top: 20px;">EXIT</a>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <div class="interaction-zone">
        <div class="terminal-box">
            <div class="terminal-header">
                <span><i class="fas fa-satellite-dish"></i> TRANSMISSION LOGS</span>
                
                <button id="btnLike" class="like-btn <?php echo $is_liked ? 'liked' : ''; ?>" onclick="toggleLike(<?php echo $chapter_id; ?>)">
                    <i class="<?php echo $is_liked ? 'fas' : 'far'; ?> fa-heart"></i> 
                    <span id="likeCount"><?php echo $total_likes; ?></span> ACKNOWLEDGMENTS
                </button>
            </div>

            <div class="terminal-body" id="commentList">
                <div style="text-align:center; padding:20px; color:#555;">
                    <i class="fas fa-spinner fa-spin"></i> ESTABLISHING CONNECTION...
                </div>
            </div>

            <div class="cmd-input-group">
                <span class="cmd-prompt">usr@holopage:~$</span>
                <input type="text" id="commentInput" class="cmd-input" placeholder="Type transmission message..." autocomplete="off">
                <button onclick="postComment(<?php echo $chapter_id; ?>)" class="cmd-submit">SEND</button>
            </div>
        </div>
    </div>

    <div class="reader-controls">
        <a href="<?php echo $prev_chapter ? "read.php?chapter_id=$prev_chapter" : '#'; ?>" 
           class="control-btn <?php echo !$prev_chapter ? 'disabled' : ''; ?>" title="Previous Chapter">
            <i class="fas fa-step-backward"></i>
        </a>

        <div style="display: flex; gap: 15px; border-left: 1px solid #333; border-right: 1px solid #333; padding: 0 20px;">
            <a href="#" id="btnPrevPage" class="control-btn"><i class="fas fa-chevron-left"></i></a>
            <a href="#" id="btnNextPage" class="control-btn"><i class="fas fa-chevron-right"></i></a>
        </div>

        <a href="<?php echo $next_chapter ? "read.php?chapter_id=$next_chapter" : '#'; ?>" 
           class="control-btn <?php echo !$next_chapter ? 'disabled' : ''; ?>" title="Next Chapter">
            <i class="fas fa-step-forward"></i>
        </a>
    </div>

    <script>
        // --- 1. VISUAL EFFECT: PARTICLES ---
        const particlesContainer = document.getElementById('particles');
        for(let i=0; i<25; i++) {
            let span = document.createElement('span');
            span.style.left = Math.random() * 100 + '%';
            span.style.animationDelay = Math.random() * 5 + 's';
            span.style.animationDuration = (Math.random() * 5 + 5) + 's';
            particlesContainer.appendChild(span);
        }

        // --- 2. BOOK PAGE FLIP LOGIC ---
        document.addEventListener('DOMContentLoaded', function() {
            const pageFlip = new St.PageFlip(document.getElementById('book'), {
                width: 600, // Lebar Dasar
                height: 900, // Tinggi Dasar
                size: 'fixed', // Fixed size agar layout stabil saat scroll
                minWidth: 300,
                maxWidth: 1000,
                minHeight: 400,
                maxHeight: 1600,
                maxShadowOpacity: 0.4,
                showCover: true,
                mobileScrollSupport: false, 
                usePortrait: true // Single page view di Mobile
            });

            pageFlip.loadFromHTML(document.querySelectorAll('.page'));

            // Button Events
            document.getElementById('btnPrevPage').addEventListener('click', (e) => { e.preventDefault(); pageFlip.flipPrev(); });
            document.getElementById('btnNextPage').addEventListener('click', (e) => { e.preventDefault(); pageFlip.flipNext(); });
            
            // Keyboard Events
            document.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowRight') pageFlip.flipNext();
                if (e.key === 'ArrowLeft') pageFlip.flipPrev();
                if (e.key === 'Enter') {
                    // Opsional: Fokus ke input jika user mau ngetik (bisa diaktifkan jika perlu)
                    // document.getElementById('commentInput').focus();
                }
            });

            // --- 3. AUTO HISTORY & LOAD DATA ---
            loadComments(<?php echo $chapter_id; ?>);
            
            // Catat History Diam-diam (Background Process)
            <?php if ($user_id != 0): ?>
            fetch('controllers/InteractionController.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=update_history&chapter_id=<?php echo $chapter_id; ?>'
            });
            <?php endif; ?>
        });

        // --- 4. INTERACTION LOGIC (AJAX) ---

        // Fungsi Toggle Like
        function toggleLike(chapterId) {
            // Cek Login untuk Guest
            <?php if($user_id == 0): ?>
                alert("ACCESS DENIED: Guest identity detected. Login required for acknowledgement.");
                return;
            <?php endif; ?>

            const btn = document.getElementById('btnLike');
            const countSpan = document.getElementById('likeCount');
            const icon = btn.querySelector('i');

            fetch('controllers/InteractionController.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=toggle_like&chapter_id=' + chapterId
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    if(data.liked) {
                        btn.classList.add('liked');
                        icon.classList.remove('far'); icon.classList.add('fas');
                    } else {
                        btn.classList.remove('liked');
                        icon.classList.remove('fas'); icon.classList.add('far');
                    }
                    countSpan.innerText = data.total;
                }
            });
        }

        // Fungsi Load Komentar
        function loadComments(chapterId) {
            fetch('controllers/InteractionController.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=load_comments&chapter_id=' + chapterId
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    document.getElementById('commentList').innerHTML = data.html;
                }
            });
        }

        // Fungsi Kirim Komentar
        function postComment(chapterId) {
            // Cek Login untuk Guest
            <?php if($user_id == 0): ?>
                alert("ACCESS DENIED: Login required to transmit data.");
                return;
            <?php endif; ?>

            const input = document.getElementById('commentInput');
            const text = input.value;

            if(text.trim() === '') return;

            fetch('controllers/InteractionController.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=post_comment&chapter_id=' + chapterId + '&comment=' + encodeURIComponent(text)
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    input.value = ''; // Reset input
                    loadComments(chapterId); // Refresh list
                    // Scroll ke atas list komentar agar pesan baru terlihat
                    document.getElementById('commentList').scrollTop = 0;
                }
            });
        }

        // Kirim komentar dengan tombol Enter
        document.getElementById('commentInput').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                postComment(<?php echo $chapter_id; ?>);
            }
        });

    </script>
</body>
</html>
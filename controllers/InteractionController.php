<?php
// controllers/InteractionController.php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json'); // Output wajib JSON untuk AJAX

$db = new Database();
$conn = $db->getConnection();

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

// Tolak jika belum login (kecuali guest mau baca komen doang, tapi guest ID 0)
if ($user_id == 0 && $action != 'load_comments') {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied']);
    exit;
}

// 1. FITUR LIKE (TOGGLE)
if ($action == 'toggle_like') {
    $chapter_id = $_POST['chapter_id'];
    
    // Cek apakah sudah like?
    $check = $conn->prepare("SELECT id FROM likes WHERE user_id = :uid AND chapter_id = :cid");
    $check->execute(['uid' => $user_id, 'cid' => $chapter_id]);
    
    if ($check->rowCount() > 0) {
        // Kalau sudah, UNLIKE (Hapus)
        $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = :uid AND chapter_id = :cid");
        $stmt->execute(['uid' => $user_id, 'cid' => $chapter_id]);
        $liked = false;
    } else {
        // Kalau belum, LIKE (Insert)
        $stmt = $conn->prepare("INSERT INTO likes (user_id, chapter_id) VALUES (:uid, :cid)");
        $stmt->execute(['uid' => $user_id, 'cid' => $chapter_id]);
        $liked = true;
    }

    // Hitung total like baru
    $count = $conn->prepare("SELECT COUNT(*) FROM likes WHERE chapter_id = :cid");
    $count->execute(['cid' => $chapter_id]);
    $total = $count->fetchColumn();

    echo json_encode(['status' => 'success', 'liked' => $liked, 'total' => $total]);
}

// 2. FITUR POST KOMENTAR
elseif ($action == 'post_comment') {
    $chapter_id = $_POST['chapter_id'];
    $text = trim($_POST['comment']);

    if (!empty($text)) {
        $stmt = $conn->prepare("INSERT INTO comments (user_id, chapter_id, comment_text) VALUES (:uid, :cid, :txt)");
        if ($stmt->execute(['uid' => $user_id, 'cid' => $chapter_id, 'txt' => $text])) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error']);
        }
    }
}

// 3. FITUR LOAD KOMENTAR
elseif ($action == 'load_comments') {
    $chapter_id = $_POST['chapter_id'];
    
    $sql = "SELECT c.*, u.username, u.role FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.chapter_id = :cid 
            ORDER BY c.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['cid' => $chapter_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Render HTML Komentar langsung di sini biar JS tinggal tempel
    $html = '';
    foreach ($comments as $c) {
        $badge = ($c['role'] == 'admin') ? '<span style="color:var(--neon-pink); font-size:0.7em;">[ADMIN]</span>' : '';
        $date = date('H:i | d M', strtotime($c['created_at']));
        
        $html .= '
        <div class="log-entry">
            <div class="log-meta">
                <span class="log-user">> ' . htmlspecialchars($c['username']) . ' ' . $badge . '</span>
                <span class="log-date">' . $date . '</span>
            </div>
            <div class="log-text">' . nl2br(htmlspecialchars($c['comment_text'])) . '</div>
        </div>';
    }
    
    if (empty($html)) $html = '<div style="text-align:center; color:#555; padding:20px;">NO TRANSMISSION DATA FOUND.</div>';

    echo json_encode(['status' => 'success', 'html' => $html]);
}

// 4. FITUR UPDATE HISTORY (Otomatis dipanggil saat buka read.php)
elseif ($action == 'update_history') {
    $chapter_id = $_POST['chapter_id'];
    
    // Cek history lama
    $check = $conn->prepare("SELECT id FROM history WHERE user_id = :uid AND chapter_id = :cid");
    $check->execute(['uid' => $user_id, 'cid' => $chapter_id]);
    
    if ($check->rowCount() > 0) {
        // Update waktu baca terakhir
        $stmt = $conn->prepare("UPDATE history SET last_read_at = NOW() WHERE user_id = :uid AND chapter_id = :cid");
    } else {
        // Insert history baru
        $stmt = $conn->prepare("INSERT INTO history (user_id, chapter_id, last_read_at) VALUES (:uid, :cid, NOW())");
    }
    $stmt->execute(['uid' => $user_id, 'cid' => $chapter_id]);
    
    echo json_encode(['status' => 'success']);
}

// 5. FITUR FAVORITE (TOGGLE)
elseif ($action == 'toggle_favorite') {
    if ($user_id == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Login Required']);
        exit;
    }

    $comic_id = $_POST['comic_id'];
    
    // Cek apakah sudah favorit?
    $check = $conn->prepare("SELECT id FROM favorites WHERE user_id = :uid AND comic_id = :cid");
    $check->execute(['uid' => $user_id, 'cid' => $comic_id]);
    
    if ($check->rowCount() > 0) {
        // UN-FAVORITE
        $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = :uid AND comic_id = :cid");
        $stmt->execute(['uid' => $user_id, 'cid' => $comic_id]);
        $is_fav = false;
    } else {
        // ADD FAVORITE
        $stmt = $conn->prepare("INSERT INTO favorites (user_id, comic_id) VALUES (:uid, :cid)");
        $stmt->execute(['uid' => $user_id, 'cid' => $comic_id]);
        $is_fav = true;
    }

    echo json_encode(['status' => 'success', 'is_favorite' => $is_fav]);
}
?>
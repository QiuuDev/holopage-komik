<?php
// admin/upload_chapter.php
session_start();
if ($_SESSION['role'] !== 'admin') { header("Location: ../login.php"); exit; }
require_once '../config/database.php';
$db = new Database(); $conn = $db->getConnection();

// Ambil daftar komik untuk Dropdown
$comics = $conn->query("SELECT id, title FROM comics ORDER BY title ASC")->fetchAll();

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $comic_id = $_POST['comic_id'];
    $chapter_num = $_POST['chapter_number'];
    $chap_title = $_POST['chapter_title'];

    try {
        $conn->beginTransaction(); // Mulai transaksi database (Agar kalau gagal, tidak ada data sampah masuk)

        // 1. Insert ke Tabel Chapters
        $sql = "INSERT INTO chapters (comic_id, chapter_number, title) VALUES (:cid, :cnum, :ctitle)";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['cid'=>$comic_id, 'cnum'=>$chapter_num, 'ctitle'=>$chap_title]);
        $chapter_id = $conn->lastInsertId(); // Dapatkan ID Chapter yang baru dibuat

        // 2. Loop Upload Multiple Images (Pages)
        // $_FILES['pages']['name'] berbentuk array jika multiple
        $total_files = count($_FILES['pages']['name']);
        
        for ($i = 0; $i < $total_files; $i++) {
            $tmp_name = $_FILES['pages']['tmp_name'][$i];
            $file_name = $_FILES['pages']['name'][$i];
            
            // Generate nama file unik: chapterID_urutan_timestamp.jpg
            $new_filename = "ch{$chapter_id}_pg" . ($i + 1) . "_" . time() . ".jpg";
            $target = "../uploads/pages/" . $new_filename;

            if (move_uploaded_file($tmp_name, $target)) {
                // Insert ke Tabel Pages
                $sql_page = "INSERT INTO pages (chapter_id, image_url, page_order) VALUES (:chid, :img, :ord)";
                $stmt_page = $conn->prepare($sql_page);
                // Kita asumsikan urutan file yang dipilih user sudah benar (1, 2, 3...)
                $stmt_page->execute(['chid'=>$chapter_id, 'img'=>$new_filename, 'ord'=>($i + 1)]);
            }
        }

        $conn->commit(); // Simpan semua perubahan
        $message = "SUKSES! Chapter $chapter_num dan $total_files halaman berhasil diupload.";

    } catch (Exception $e) {
        $conn->rollBack(); // Batalkan semua jika ada error
        $message = "GAGAL: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Upload Chapter</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="padding: 50px;">
    <a href="index.php" class="btn-holo">KEMBALI KE DASHBOARD</a>
    <h1 style="margin: 20px 0; color: var(--neon-pink);">UPLOAD CHAPTER BARU</h1>
    
    <?php if($message): ?><div style="border: 1px solid white; padding: 15px; margin-bottom: 20px;"><?php echo $message; ?></div><?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" style="max-width: 600px;">
        
        <div style="margin-bottom: 15px;">
            <label>PILIH KOMIK</label><br>
            <select name="comic_id" required style="width: 100%; padding: 10px; background: #222; color: white;">
                <?php foreach($comics as $c): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['title']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label>NOMOR CHAPTER (Misal: 1, 1.5, 2)</label><br>
            <input type="number" step="0.1" name="chapter_number" required style="width: 100%; padding: 10px; background: #222; border: 1px solid #444; color: white;">
        </div>

        <div style="margin-bottom: 15px;">
            <label>JUDUL CHAPTER (Opsional)</label><br>
            <input type="text" name="chapter_title" placeholder="Misal: Pertarungan Dimulai" style="width: 100%; padding: 10px; background: #222; border: 1px solid #444; color: white;">
        </div>

        <div style="margin-bottom: 30px; border: 1px dashed var(--neon-cyan); padding: 20px;">
            <label style="color: var(--neon-cyan);">FILE HALAMAN KOMIK (Pilih Banyak Sekaligus)</label><br>
            <small style="color: #888;">Urutan halaman akan disesuaikan dengan urutan file yang dipilih.</small><br><br>
            <input type="file" name="pages[]" multiple required accept="image/*" style="color: white;">
        </div>

        <button type="submit" class="btn-holo" style="width: 100%;">START UPLOAD SEQUENCE</button>
    </form>
</body>
</html>
<?php
// admin/upload_comic.php
session_start();
if ($_SESSION['role'] !== 'admin') { header("Location: ../login.php"); exit; }
require_once '../config/database.php';

// Logic Proses Form
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = new Database(); $conn = $db->getConnection();
    
    $title = $_POST['title'];
    $desc = $_POST['description'];
    
    // Upload Cover Logic
    $cover_name = time() . '_' . $_FILES['cover']['name']; // Rename biar unik
    $target = "../uploads/covers/" . $cover_name;
    
    if (move_uploaded_file($_FILES['cover']['tmp_name'], $target)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title))); // Buat slug URL friendly
        
        $sql = "INSERT INTO comics (title, slug, description, cover_image) VALUES (:title, :slug, :desc, :cover)";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['title'=>$title, 'slug'=>$slug, 'desc'=>$desc, 'cover'=>$cover_name]);
        $message = "KOMIK BERHASIL DITAMBAHKAN KE DATABASE.";
    } else {
        $message = "ERROR: Gagal upload gambar cover.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Add New Comic</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="padding: 50px;">
    <a href="index.php" class="btn-holo">KEMBALI KE DASHBOARD</a>
    <h1 style="margin: 20px 0; color: var(--neon-cyan);">INPUT DATA KOMIK BARU</h1>
    
    <?php if($message): ?><div style="color: var(--neon-pink); margin-bottom: 20px; border: 1px solid var(--neon-pink); padding: 10px;"><?php echo $message; ?></div><?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" style="max-width: 600px;">
        <div style="margin-bottom: 15px;">
            <label>JUDUL KOMIK</label><br>
            <input type="text" name="title" required style="width: 100%; padding: 10px; background: #222; border: 1px solid #444; color: white;">
        </div>
        
        <div style="margin-bottom: 15px;">
            <label>SINOPSIS / DESKRIPSI</label><br>
            <textarea name="description" rows="5" style="width: 100%; padding: 10px; background: #222; border: 1px solid #444; color: white;"></textarea>
        </div>

        <div style="margin-bottom: 15px;">
            <label>COVER IMAGE</label><br>
            <input type="file" name="cover" required accept="image/*" style="color: white;">
        </div>

        <button type="submit" class="btn-holo">UPLOAD DATA</button>
    </form>
</body>
</html>
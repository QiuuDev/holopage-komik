<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - HoloPage Identity</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* (Sama seperti login.php di atas) */
        .auth-container { height: 100vh; display: flex; justify-content: center; align-items: center; background: radial-gradient(circle at center, #1a1a1d 0%, #000 100%); }
        .holo-card { background: rgba(255, 255, 255, 0.03); border: 1px solid var(--neon-cyan); padding: 2.5rem; width: 100%; max-width: 400px; backdrop-filter: blur(10px); }
        .form-input { width: 100%; padding: 12px; background: rgba(0,0,0,0.5); border: 1px solid #333; color: var(--neon-cyan); margin-bottom: 1.5rem; }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="holo-card">
        <h2 style="text-align: center; margin-bottom: 2rem; color: var(--neon-cyan);">NEW IDENTITY</h2>
        
        <?php if(isset($_GET['error'])): ?>
            <div style="color: #ff4d4d; text-align: center; margin-bottom: 1rem;">ERROR: <?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <form action="controllers/AuthController.php" method="POST">
            <input type="hidden" name="action" value="register">
            <input type="text" name="username" class="form-input" placeholder="CHOOSE USERNAME" required>
            <input type="password" name="password" class="form-input" placeholder="SET PASSWORD" required>
            
            <button type="submit" class="btn-holo" style="width: 100%;">INITIALIZE REGISTRATION</button>
        </form>

        <p style="text-align: center; margin-top: 20px; font-size: 0.9rem;">
            Already have ID? <a href="login.php" style="color: var(--neon-cyan); text-decoration: none;">Login Here</a>
        </p>
    </div>
</div>

</body>
</html>
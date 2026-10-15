<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HoloPage Access</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* CSS Khusus Halaman Auth */
        .auth-container {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: radial-gradient(circle at center, #1a1a1d 0%, #000 100%);
        }
        
        .holo-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--neon-cyan);
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 0 15px rgba(0, 243, 255, 0.2);
            backdrop-filter: blur(10px);
            position: relative;
        }

        .holo-card::before {
            content: '';
            position: absolute;
            top: -2px; left: -2px; right: -2px; bottom: -2px;
            z-index: -1;
            background: linear-gradient(45deg, var(--neon-cyan), transparent, var(--neon-pink));
            opacity: 0.3;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            background: rgba(0,0,0,0.5);
            border: 1px solid #333;
            color: var(--neon-cyan);
            font-family: 'Rajdhani', sans-serif;
            font-size: 1rem;
            outline: none;
            transition: 0.3s;
        }

        .form-input:focus {
            border-color: var(--neon-cyan);
            box-shadow: 0 0 8px rgba(0, 243, 255, 0.3);
        }

        .divider {
            text-align: center;
            margin: 20px 0;
            color: #555;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .alert {
            color: #ff4d4d;
            text-align: center;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="holo-card">
        <h2 style="text-align: center; margin-bottom: 2rem; color: var(--neon-cyan);">ACCESS TERMINAL</h2>
        
        <?php if(isset($_GET['error'])): ?>
            <div class="alert">ACCESS DENIED: <?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        
        <?php if(isset($_GET['success'])): ?>
            <div class="alert" style="color: var(--neon-cyan);">REGISTRATION COMPLETE. LOGIN NOW.</div>
        <?php endif; ?>

        <form action="controllers/AuthController.php" method="POST">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <input type="text" name="username" class="form-input" placeholder="USERNAME" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-input" placeholder="PASSWORD" required>
            </div>
            <button type="submit" class="btn-holo" style="width: 100%;">AUTHORIZE LOGIN</button>
        </form>

        <div class="divider">OR</div>

        <form action="controllers/AuthController.php" method="POST">
            <input type="hidden" name="action" value="guest">
            <button type="submit" class="btn-holo" style="width: 100%; border-color: var(--neon-pink); color: var(--neon-pink);">
                CONTINUE AS GUEST (RESTRICTED)
            </button>
        </form>

        <p style="text-align: center; margin-top: 20px; font-size: 0.9rem;">
            No ID? <a href="register.php" style="color: var(--neon-cyan); text-decoration: none;">Create New Identity</a>
        </p>
    </div>
</div>

</body>
</html>
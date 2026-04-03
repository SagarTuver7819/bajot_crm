<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND status = 1 LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['theme'] = 'dark'; // Default
            redirect('index.php');
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found or inactive.";
    }
}
$s = get_settings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authorized Login | Bajot CRM</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --gold: #C9A14A;
            --dark-bg: #0a0a0a;
            --dark-card: rgba(20, 20, 20, 0.85);
            --gold-gradient: linear-gradient(135deg, #C9A14A 0%, #E8C88A 100%);
        }

        body {
            background: linear-gradient(rgba(0,0,0,0.75), rgba(0,0,0,0.85)), url('<?php echo "aluminum_extrusion_bg_1775054428375.png"; ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            font-family: 'Outfit', sans-serif;
            overflow: hidden;
        }

        .login-card {
            background: var(--dark-card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(201, 161, 74, 0.3);
            border-radius: 2px; /* Architectural sharp corners like website */
            padding: 4rem 3rem;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.8);
            position: relative;
        }

        /* Architectural Border Accent */
        .login-card::before {
            content: '';
            position: absolute;
            top: 10px; left: 10px; right: 10px; bottom: 10px;
            border: 1px solid rgba(201, 161, 74, 0.1);
            pointer-events: none;
        }

        .logo-box {
            margin-bottom: 3rem;
            text-align: center;
        }

        .logo-box img {
            max-height: 100px;
            margin-bottom: 1.5rem;
            filter: drop-shadow(0 0 10px rgba(201, 161, 74, 0.3));
        }

        .logo-box h1 {
            font-family: 'Montserrat', sans-serif;
            color: var(--gold);
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: 3px;
            margin: 0;
            text-transform: uppercase;
        }

        .logo-box p {
            color: #aaa;
            letter-spacing: 5px;
            font-size: 0.7rem;
            text-transform: uppercase;
            margin-top: 8px;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.03) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 0 !important;
            color: #fff !important;
            padding: 0.9rem 1.2rem !important;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--gold) !important;
            background: rgba(255, 255, 255, 0.07) !important;
            box-shadow: none !important;
        }

        .input-group-text {
            background: rgba(255, 255, 255, 0.03) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 0 !important;
            color: var(--gold) !important;
        }

        .btn-login {
            background: var(--gold);
            border: 1px solid var(--gold);
            border-radius: 0;
            color: #000;
            width: 100%;
            padding: 1rem;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 1.5rem;
            transition: all 0.4s;
        }

        .btn-login:hover {
            background: transparent;
            color: var(--gold);
            box-shadow: 0 0 20px rgba(201, 161, 74, 0.3);
        }

        .error-msg {
            background: rgba(217, 48, 37, 0.1);
            color: #ff6b6b;
            padding: 12px;
            font-size: 0.8rem;
            margin-top: 1.5rem;
            border: 1px solid rgba(217, 48, 37, 0.2);
            text-align: center;
        }

        .footer-text {
            margin-top: 3rem;
            color: #666;
            font-size: 0.65rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-card" data-aos="fade-up" data-aos-duration="1200">
        <div class="logo-box">
            <?php if (!empty($s['company_logo']) && file_exists($s['company_logo'])): ?>
                <img src="<?php echo $s['company_logo']; ?>" alt="Logo">
            <?php endif; ?>
            <h1>BAJOT EXTRUSION</h1>
            <p>Authorized Software Access</p>
        </div>
        
        <form method="POST">
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-user-shield"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="USERNAME" required autocomplete="off">
                </div>
            </div>
            <div class="mb-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-key"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="PASSWORD" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-login">
                ENTER SYSTEM <i class="fa fa-lock-open ms-2"></i>
            </button>

            <?php if ($error): ?>
                <div class="error-msg">
                    <i class="fa fa-times-circle me-2"></i> <?php echo strtoupper($error); ?>
                </div>
            <?php endif; ?>
        </form>

        <div class="footer-text">
            &copy; <?php echo date('Y'); ?> Bajot Extrusion Pvt. Ltd. | Industrial CRM
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>
</html>

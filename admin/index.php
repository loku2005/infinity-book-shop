<?php
require_once '../includes/functions.php';

// Handle logout
if (isset($_GET['logout'])) {
    adminLogout();
    redirectTo('index.php');
}

// Handle login
if ($_POST) {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        if (adminLogin($_POST['username'], $_POST['password'])) {
            redirectTo('dashboard.php');
        } else {
            $error = 'Invalid username or password';
        }
    }
}

// Redirect if already logged in
if (isAdminLoggedIn()) {
    redirectTo('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .logo-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 15px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            border-radius: 15px;
            padding: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }
        .alert {
            border-radius: 15px;
            border: none;
        }
        .back-to-site {
            text-align: center;
            margin-top: 1rem;
        }
        .back-to-site a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        .back-to-site a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="logo-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <h3 class="mb-1">INFINITY</h3>
            <p class="mb-0">Admin Panel</p>
        </div>
        
        <div class="login-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Username</label>
                    <div class="input-group">
                        <span class="input-group-text" style="border-radius: 15px 0 0 15px; border: 2px solid #e9ecef; border-right: none;">
                            <i class="fas fa-user text-muted"></i>
                        </span>
                        <input type="text" class="form-control" name="username" required 
                               placeholder="Enter username" style="border-radius: 0 15px 15px 0; border-left: none;">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-bold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text" style="border-radius: 15px 0 0 15px; border: 2px solid #e9ecef; border-right: none;">
                            <i class="fas fa-lock text-muted"></i>
                        </span>
                        <input type="password" class="form-control" name="password" required 
                               placeholder="Enter password" style="border-radius: 0 15px 15px 0; border-left: none;">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Sign In to Admin Panel
                </button>
            </form>
            
            <div class="back-to-site">
                <a href="../index.php">
                    <i class="fas fa-arrow-left me-1"></i>
                    Back to Bookshop
                </a>
            </div>
            
            <div class="mt-4 text-center">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Default: admin / admin123
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
session_start();

// Data login yang sudah di-hash (simulasi)
$storedUsername = 'admin';
$storedPasswordHash = 'admin123'; // Hash untuk 'admin123'

// Cek apakah form login disubmit
// Cek apakah form login disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {          
    // Username dan password hardcoded untuk admin
    if ($_POST['username'] === 'admin' && $_POST['password'] === 'admin123') {         
        $_SESSION['admin'] = true; // Menandakan admin sudah login           
        header('Location: index.php'); // Redirect ke dashboard admin
        exit();     
    } else {         
        $error = 'Username atau password salah!'; // Pesan error jika login gagal
    } 
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    
    <!-- Bootstrap 5.3.2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <style>
        body {
            background-color: #f4f6f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-card {
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border: none;
            border-radius: 12px;
        }
        .login-card .card-header {
            background-color: #007bff;
            color: white;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }
        .login-btn {
            transition: all 0.3s ease;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card login-card">
                    <div class="card-header text-center py-3">
                        <h3 class="mb-0">Admin Login</h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="login.php" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 login-btn py-2">Login</button>
                        </form>
                    </div>
                    <div class="card-footer text-center bg-white border-0">
                        <a href="#" class="text-muted">Forgot Password?</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3.2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if(isset($_SESSION['customer_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    if(empty($email) || empty($password)) {
        $error = 'ກະລຸນາປ້ອນອີເມວແລະລະຫັດຜ່ານ';
    } else {
        $customer = authenticateCustomer($db, $email, $password);
        if($customer) {
            $_SESSION['customer_id'] = $customer['id'];
            $_SESSION['customer_name'] = $customer['first_name'] . ' ' . $customer['last_name'];
            $_SESSION['customer_email'] = $customer['email'];
            
            header('Location: index.php');
            exit();
        } else {
            $error = 'ອີເມວຫຼືລະຫັດຜ່ານບໍ່ຖືກຕ້ອງ';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ເຂົ້າສູ່ລະບົບ - POS IT Online</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Google Fonts - Noto Sans Lao -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Noto Sans Lao', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            max-width: 400px;
            width: 100%;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            background: linear-gradient(45deg, #5a6fd8, #6a4190);
            transform: translateY(-2px);
        }
        
        .social-login {
            margin-top: 2rem;
            text-align: center;
        }
        
        .social-btn {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f8f9fa;
            color: #333;
            text-align: center;
            line-height: 40px;
            margin: 0 10px;
            transition: all 0.3s ease;
        }
        
        .social-btn:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-card">
                    <div class="login-header">
                        <i class="fas fa-user-circle"></i>
                        <h3>ເຂົ້າສູ່ລະບົບ</h3>
                        <p class="text-muted">ຍິນດີຕ້ອນຮັບກັບຄືນ</p>
                    </div>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">ອີເມວ</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                       required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">ລະຫັດຜ່ານ</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">
                                ຈົດຈໍາຂ້ອຍ
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-login text-white">
                            <i class="fas fa-sign-in-alt me-2"></i>ເຂົ້າສູ່ລະບົບ
                        </button>
                    </form>
                    
                    <div class="social-login">
                        <p class="text-muted mb-3">ຫຼື ເຂົ້າສູ່ລະບົບດ້ວຍ</p>
                        <a href="#" class="social-btn" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-btn" title="Google">
                            <i class="fab fa-google"></i>
                        </a>
                        <a href="#" class="social-btn" title="Apple">
                            <i class="fab fa-apple"></i>
                        </a>
                    </div>
                    
                    <div class="register-link">
                        <p>ຍັງບໍ່ມີບັນຊີ? <a href="register.php">ລົງທະບຽນຕອນນີ້</a></p>
                        <p><a href="forgot-password.php">ລືມລະຫັດຜ່ານ?</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Show welcome message if redirected from registration
        <?php if(isset($_GET['registered'])): ?>
        Swal.fire({
            icon: 'success',
            title: 'ສໍາເລັດ!',
            text: 'ການລົງທະບຽນສໍາເລັດແລ້ວ ກະລຸນາເຂົ້າສູ່ລະບົບ',
            confirmButtonText: 'ຕົກລົງ'
        });
        <?php endif; ?>
    </script>
</body>
</html> 
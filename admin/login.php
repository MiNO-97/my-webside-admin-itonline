<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Redirect if already logged in as admin
if(isset($_SESSION['employee_id'])) {
    header('Location: dashboard.php');
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
        $employee = authenticateEmployee($db, $email, $password);
        if($employee) {
            $_SESSION['employee_id'] = $employee['id'];
            $_SESSION['employee_name'] = $employee['first_name'] . ' ' . $employee['last_name'];
            $_SESSION['employee_email'] = $employee['email'];
            $_SESSION['employee_position'] = $employee['position'];
            
            // Log activity
            logActivity($db, $employee['id'], 'employee', 'login', 'Admin logged in');
            
            header('Location: dashboard.php');
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
    <title>ເຂົ້າສູ່ລະບົບຜູ້ບໍລິຫານ - POS IT Online</title>
    
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
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .admin-login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            max-width: 400px;
            width: 100%;
        }
        
        .admin-login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .admin-login-header i {
            font-size: 3rem;
            color: #1e3c72;
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
            border-color: #1e3c72;
            box-shadow: 0 0 0 0.2rem rgba(30, 60, 114, 0.25);
        }
        
        .btn-admin-login {
            background: linear-gradient(45deg, #1e3c72, #2a5298);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-admin-login:hover {
            background: linear-gradient(45deg, #1a3464, #244a88);
            transform: translateY(-2px);
        }
        
        .back-to-site {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-to-site a {
            color: #1e3c72;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-to-site a:hover {
            text-decoration: underline;
        }
        
        .admin-badge {
            background: linear-gradient(45deg, #1e3c72, #2a5298);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="admin-login-card">
                    <div class="admin-login-header">
                        <div class="admin-badge">
                            <i class="fas fa-shield-alt me-2"></i>ຜູ້ບໍລິຫານລະບົບ
                        </div>
                        <i class="fas fa-user-shield"></i>
                        <h3>ເຂົ້າສູ່ລະບົບຜູ້ບໍລິຫານ</h3>
                        <p class="text-muted">ກະລຸນາເຂົ້າສູ່ລະບົບດ້ວຍບັນຊີຜູ້ບໍລິຫານ</p>
                    </div>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">ອີເມວຜູ້ບໍລິຫານ</label>
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
                        
                        <button type="submit" class="btn btn-admin-login text-white">
                            <i class="fas fa-sign-in-alt me-2"></i>ເຂົ້າສູ່ລະບົບ
                        </button>
                    </form>
                    
                    <div class="back-to-site">
                        <p><a href="../index.php">
                            <i class="fas fa-arrow-left me-2"></i>ກັບຄືນໜ້າຫຼັກ
                        </a></p>
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
        
        // Show security notice
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'info',
                title: 'ການເຂົ້າສູ່ລະບົບຜູ້ບໍລິຫານ',
                text: 'ກະລຸນາໃຊ້ບັນຊີຜູ້ບໍລິຫານທີ່ຖືກຕ້ອງເພື່ອເຂົ້າສູ່ລະບົບ',
                confirmButtonText: 'ຕົກລົງ',
                allowOutsideClick: false
            });
        });
    </script>
</body>
</html> 
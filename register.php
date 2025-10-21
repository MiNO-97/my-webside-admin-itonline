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
$success = '';

if($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if(empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = 'ກະລຸນາປ້ອນຂໍ້ມູນທີ່ຈໍາເປັນ';
    } elseif(!isValidEmail($email)) {
        $error = 'ອີເມວບໍ່ຖືກຕ້ອງ';
    } elseif(strlen($password) < 6) {
        $error = 'ລະຫັດຜ່ານຕ້ອງມີຢ່າງໜ້ອຍ 6 ຕົວອັກສອນ';
    } elseif($password !== $confirm_password) {
        $error = 'ລະຫັດຜ່ານບໍ່ກົງກັນ';
    } else {
        // Check if email already exists
        try {
            $query = "SELECT id FROM customers WHERE email = :email";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $error = 'ອີເມວນີ້ມີຜູ້ໃຊ້ແລ້ວ';
            } else {
                // Create new customer
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $query = "INSERT INTO customers (first_name, last_name, email, phone, address, password) 
                          VALUES (:first_name, :last_name, :email, :phone, :address, :password)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':first_name', $first_name);
                $stmt->bindParam(':last_name', $last_name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':address', $address);
                $stmt->bindParam(':password', $hashed_password);
                
                if($stmt->execute()) {
                    $success = 'ການລົງທະບຽນສໍາເລັດແລ້ວ ກະລຸນາເຂົ້າສູ່ລະບົບ';
                    // Redirect to login after 2 seconds
                    header("refresh:2;url=login.php?registered=1");
                } else {
                    $error = 'ເກີດຂໍ້ຜິດພາດໃນການລົງທະບຽນ';
                }
            }
        } catch(PDOException $e) {
            $error = 'ເກີດຂໍ້ຜິດພາດໃນການລົງທະບຽນ';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ລົງທະບຽນ - POS IT Online</title>
    
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
            padding: 2rem 0;
        }
        
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-header i {
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
        
        .btn-register {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-register:hover {
            background: linear-gradient(45deg, #5a6fd8, #6a4190);
            transform: translateY(-2px);
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }
        
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="register-card">
                    <div class="register-header">
                        <i class="fas fa-user-plus"></i>
                        <h3>ລົງທະບຽນບັນຊີ</h3>
                        <p class="text-muted">ສ້າງບັນຊີໃໝ່ສໍາລັບການຊື້ສິນຄ້າ</p>
                    </div>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($success): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="registerForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">ຊື່ <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-user"></i>
                                        </span>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" 
                                               required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">ນາມສະກຸນ <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-user"></i>
                                        </span>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" 
                                               required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">ອີເມວ <span class="text-danger">*</span></label>
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
                            <label for="phone" class="form-label">ເບີໂທລະສັບ</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-phone"></i>
                                </span>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">ທີ່ຢູ່</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-map-marker-alt"></i>
                                </span>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">ລະຫັດຜ່ານ <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                            <i class="fas fa-eye" id="toggleIcon1"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength" id="passwordStrength"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">ຢືນຢັນລະຫັດຜ່ານ <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye" id="toggleIcon2"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength" id="passwordMatch"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="agree" required>
                            <label class="form-check-label" for="agree">
                                ຂ້ອຍຍອມຮັບ <a href="#" class="text-primary">ຂໍ້ກໍານົດແລະເງື່ອນໄຂ</a> ຂອງການໃຊ້ບໍລິການ
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-register text-white">
                            <i class="fas fa-user-plus me-2"></i>ລົງທະບຽນ
                        </button>
                    </form>
                    
                    <div class="login-link">
                        <p>ມີບັນຊີແລ້ວ? <a href="login.php">ເຂົ້າສູ່ລະບົບ</a></p>
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
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(fieldId === 'password' ? 'toggleIcon1' : 'toggleIcon2');
            
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
        
        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            let strength = 0;
            let message = '';
            let className = '';
            
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            switch(strength) {
                case 0:
                case 1:
                    message = 'ລະຫັດຜ່ານອ່ອນ';
                    className = 'strength-weak';
                    break;
                case 2:
                case 3:
                    message = 'ລະຫັດຜ່ານປານກາງ';
                    className = 'strength-medium';
                    break;
                case 4:
                case 5:
                    message = 'ລະຫັດຜ່ານເຂັ້ມແຂງ';
                    className = 'strength-strong';
                    break;
            }
            
            strengthDiv.textContent = message;
            strengthDiv.className = 'password-strength ' + className;
        });
        
        // Password match checker
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirmPassword === '') {
                matchDiv.textContent = '';
                matchDiv.className = 'password-strength';
            } else if (password === confirmPassword) {
                matchDiv.textContent = 'ລະຫັດຜ່ານກົງກັນ';
                matchDiv.className = 'password-strength strength-strong';
            } else {
                matchDiv.textContent = 'ລະຫັດຜ່ານບໍ່ກົງກັນ';
                matchDiv.className = 'password-strength strength-weak';
            }
        });
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const agree = document.getElementById('agree').checked;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'ຜິດພາດ!',
                    text: 'ລະຫັດຜ່ານບໍ່ກົງກັນ',
                    confirmButtonText: 'ຕົກລົງ'
                });
                return false;
            }
            
            if (!agree) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'ກະລຸນາຍອມຮັບຂໍ້ກໍານົດ',
                    text: 'ທ່ານຕ້ອງຍອມຮັບຂໍ້ກໍານົດແລະເງື່ອນໄຂກ່ອນ',
                    confirmButtonText: 'ຕົກລົງ'
                });
                return false;
            }
        });
    </script>
</body>
</html> 
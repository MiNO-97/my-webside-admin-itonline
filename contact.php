<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();

// Handle contact form submission
if ($_POST) {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $subject = sanitizeInput($_POST['subject']);
    $message = sanitizeInput($_POST['message']);
    
    $errors = [];
    
    // Validation
    if (empty($name)) {
        $errors[] = 'ກະລຸນາປ້ອນຊື່';
    }
    
    if (empty($email) || !isValidEmail($email)) {
        $errors[] = 'ກະລຸນາປ້ອນອີເມວທີ່ຖືກຕ້ອງ';
    }
    
    if (empty($subject)) {
        $errors[] = 'ກະລຸນາປ້ອນຫົວຂໍ້';
    }
    
    if (empty($message)) {
        $errors[] = 'ກະລຸນາປ້ອນຂໍ້ຄວາມ';
    }
    
    if (empty($errors)) {
        // Here you would typically save to database or send email
        $success = 'ຂອບໃຈທີ່ຕິດຕໍ່ພວກເຮົາ! ພວກເຮົາຈະຕິດຕໍ່ກັບທ່ານໃນໄວນີ້.';
    }
}
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຕິດຕໍ່ - ລະບົບຂາຍສິນຄ້າ IT</title>
    
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
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        
        .main-content {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 2rem 0;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 3rem 2rem;
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .contact-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }
        
        .contact-card:hover {
            transform: translateY(-5px);
        }
        
        .contact-icon {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .form-control {
            border-radius: 15px;
            border: 2px solid #e9ecef;
            padding: 12px 20px;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, #5a6fd8, #6a4190);
            transform: translateY(-2px);
        }
        
        .map-container {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .map-placeholder {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #6c757d;
        }
        
        .social-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .social-link {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: transform 0.3s ease;
        }
        
        .social-link:hover {
            transform: translateY(-3px);
            color: white;
        }
        
        .contact-info-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .contact-info-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-laptop me-2"></i>ລະບົບຂາຍສິນຄ້າ IT
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home me-1"></i>ໜ້າຫຼັກ
                </a>
                <a class="nav-link" href="products.php">
                    <i class="fas fa-box me-1"></i>ສິນຄ້າ
                </a>
                <a class="nav-link" href="about.php">
                    <i class="fas fa-info-circle me-1"></i>ກ່ຽວກັບ
                </a>
                <a class="nav-link active" href="contact.php">
                    <i class="fas fa-envelope me-1"></i>ຕິດຕໍ່
                </a>
                <?php if (isset($_SESSION['customer_id'])): ?>
                    <a class="nav-link" href="cart.php">
                        <i class="fas fa-shopping-cart me-1"></i>ກະຕ່າ
                        <?php $cart_count = getCartItemCount($db, $_SESSION['customer_id']); ?>
                        <?php if ($cart_count > 0): ?>
                            <span class="badge bg-primary"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['customer_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">ໂປຣໄຟລ໌</a></li>
                            <li><a class="dropdown-item" href="orders.php">ຄໍາສັ່ງຊື້ຂອງຂ້ອຍ</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">ອອກຈາກລະບົບ</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="nav-link" href="login.php">
                        <i class="fas fa-sign-in-alt me-1"></i>ເຂົ້າສູ່ລະບົບ
                    </a>
                    <a class="nav-link" href="register.php">
                        <i class="fas fa-user-plus me-1"></i>ລົງທະບຽນ
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="main-content">
            <!-- Hero Section -->
            <div class="hero-section">
                <h1 class="display-4 fw-bold mb-4">ຕິດຕໍ່ພວກເຮົາ</h1>
                <p class="lead mb-4">ພວກເຮົາພ້ອມຊ່ວຍແກ້ໄຂຄໍາຖາມ ແລະ ຮອງຮັບຄໍາຄິດເຫັນຂອງທ່ານ</p>
                <p class="mb-0">ຕິດຕໍ່ພວກເຮົາໄດ້ຕະຫຼອດ 24 ຊົ່ວໂມງ</p>
            </div>
            
            <div class="p-4">
                <!-- Contact Information -->
                <div class="row mb-5">
                    <div class="col-md-4 mb-4">
                        <div class="contact-card text-center">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <h5>ທີ່ຢູ່</h5>
                            <p class="text-muted">123 ຖະໜົນສະເໜີສະເໜີ<br>ເມືອງຈັນທະບູລີ<br>ນະຄອນຫຼວງວຽງຈັນ, ລາວ</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="contact-card text-center">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <h5>ເບີໂທ</h5>
                            <p class="text-muted">020 1234 5678<br>030 9876 5432<br>ຈັດການ: 8:00 - 18:00</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="contact-card text-center">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h5>ອີເມວ</h5>
                            <p class="text-muted">info@positonline.com<br>support@positonline.com<br>sales@positonline.com</p>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Form and Map -->
                <div class="row">
                    <div class="col-lg-8 mb-4">
                        <div class="contact-card">
                            <h4 class="mb-4">
                                <i class="fas fa-paper-plane me-2"></i>ສົ່ງຂໍ້ຄວາມຫາພວກເຮົາ
                            </h4>
                            
                            <?php if (isset($success)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">ຊື່ *</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">ອີເມວ *</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">ເບີໂທ</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="subject" class="form-label">ຫົວຂໍ້ *</label>
                                        <input type="text" class="form-control" id="subject" name="subject" 
                                               value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="message" class="form-label">ຂໍ້ຄວາມ *</label>
                                    <textarea class="form-control" id="message" name="message" rows="5" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>ສົ່ງຂໍ້ຄວາມ
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 mb-4">
                        <div class="contact-card">
                            <h4 class="mb-4">
                                <i class="fas fa-map me-2"></i>ທີ່ຢູ່ຂອງພວກເຮົາ
                            </h4>
                            
                            <div class="map-container mb-4">
                                <div class="map-placeholder">
                                    <i class="fas fa-map-marked-alt me-2"></i>
                                    ແຜນທີ່ຈະສະແດງທີ່ນີ້
                                </div>
                            </div>
                            
                            <div class="contact-info-item">
                                <div class="contact-info-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div>
                                    <strong>ທີ່ຢູ່:</strong><br>
                                    <small class="text-muted">123 ຖະໜົນສະເໜີສະເໜີ, ເມືອງຈັນທະບູລີ</small>
                                </div>
                            </div>
                            
                            <div class="contact-info-item">
                                <div class="contact-info-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div>
                                    <strong>ເວລາເປີດ:</strong><br>
                                    <small class="text-muted">ຈັນ - ສຸກ: 8:00 - 18:00<br>ສະບາ - ອາທິດ: 9:00 - 16:00</small>
                                </div>
                            </div>
                            
                            <div class="contact-info-item">
                                <div class="contact-info-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div>
                                    <strong>ເບີໂທ:</strong><br>
                                    <small class="text-muted">020 1234 5678<br>030 9876 5432</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Social Media -->
                <div class="row">
                    <div class="col-12">
                        <div class="contact-card text-center">
                            <h4 class="mb-4">
                                <i class="fas fa-share-alt me-2"></i>ຕິດຕາມພວກເຮົາ
                            </h4>
                            <p class="text-muted mb-4">ຕິດຕາມພວກເຮົາໃນສື່ສັງຄົມຕ່າງໆ ເພື່ອຮູ້ຂໍ້ມູນໃໝ່ໆ</p>
                            
                            <div class="social-links">
                                <a href="#" class="social-link">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="social-link">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="#" class="social-link">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <a href="#" class="social-link">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <a href="#" class="social-link">
                                    <i class="fab fa-youtube"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- FAQ Section -->
                <div class="row mt-5">
                    <div class="col-12">
                        <div class="contact-card">
                            <h4 class="mb-4">
                                <i class="fas fa-question-circle me-2"></i>ຄໍາຖາມທີ່ຖາມບ່ອນ
                            </h4>
                            
                            <div class="accordion" id="faqAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="faq1">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                                            ການຈັດສົ່ງໃຊ້ເວລາດົນປານໃດ?
                                        </button>
                                    </h2>
                                    <div id="collapse1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            ການຈັດສົ່ງຈະໃຊ້ເວລາ 2-3 ວັນເຮືອນ ສໍາລັບພື້ນທີ່ນະຄອນຫຼວງວຽງຈັນ ແລະ 5-7 ວັນເຮືອນ ສໍາລັບພື້ນທີ່ອື່ນໆ.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="faq2">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                                            ມີວິທີການຊໍາລະໃດແດ່?
                                        </button>
                                    </h2>
                                    <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            ພວກເຮົາຮອງຮັບການຊໍາລະເງິນປາຍທາງ (COD) ແລະ ໂອນເງິນທະນາຄານ.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="faq3">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                                            ສິນຄ້າມີການຮັບປະກັນບໍ່?
                                        </button>
                                    </h2>
                                    <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            ສິນຄ້າທັງໝົດມີການຮັບປະກັນ 1 ປີ ສໍາລັບອຸປະກອນທີ່ໃໝ່ ແລະ ການຮັບປະກັນຕາມຜູ້ຜະລິດສໍາລັບສິນຄ້າມືສອງ.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>POS IT Online</h5>
                    <p>ລະບົບສັ່ງຊື້ອຸປະກອນ IT ທີ່ທັນສະໄໝ ແລະ ສະດວກສະບາຍ</p>
                </div>
                <div class="col-md-4">
                    <h5>ລິ້ງດ່ວນ</h5>
                    <ul class="list-unstyled">
                        <li><a href="products.php" class="text-white">ສິນຄ້າ</a></li>
                        <li><a href="about.php" class="text-white">ກ່ຽວກັບ</a></li>
                        <li><a href="contact.php" class="text-white">ຕິດຕໍ່</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>ຕິດຕໍ່</h5>
                    <p><i class="fas fa-phone me-2"></i>020 1234 5678</p>
                    <p><i class="fas fa-envelope me-2"></i>info@positonline.com</p>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p>&copy; 2024 POS IT Online. ສະຫງວນລິຂະສິດ.</p>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html> 
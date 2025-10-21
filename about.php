<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ກ່ຽວກັບ - ລະບົບຂາຍສິນຄ້າ IT</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            padding: 4rem 2rem;
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .team-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .team-card:hover {
            transform: translateY(-5px);
        }
        
        .team-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            margin: 0 auto 1rem;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .timeline {
            position: relative;
            padding: 2rem 0;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #667eea;
            transform: translateX(-50%);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 3rem;
        }
        
        .timeline-content {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            width: 45%;
        }
        
        .timeline-item:nth-child(odd) .timeline-content {
            margin-left: 0;
        }
        
        .timeline-item:nth-child(even) .timeline-content {
            margin-left: 55%;
        }
        
        .timeline-dot {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 20px;
            height: 20px;
            background: #667eea;
            border-radius: 50%;
            transform: translate(-50%, -50%);
            z-index: 1;
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
                <a class="nav-link active" href="about.php">
                    <i class="fas fa-info-circle me-1"></i>ກ່ຽວກັບ
                </a>
                <a class="nav-link" href="contact.php">
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
                <h1 class="display-4 fw-bold mb-4">ກ່ຽວກັບ POS IT Online</h1>
                <p class="lead mb-4">ລະບົບສັ່ງຊື້ອຸປະກອນ IT ທີ່ທັນສະໄໝ ແລະ ສະດວກສະບາຍ</p>
                <p class="mb-0">ພວກເຮົາເປັນຜູ້ນໍາດ້ານການຂາຍສິນຄ້າ IT ອອນລາຍໃນລາວ</p>
            </div>
            
            <div class="p-4">
                <!-- Mission & Vision -->
                <div class="row mb-5">
                    <div class="col-md-6 mb-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-bullseye"></i>
                            </div>
                            <h4>ພາລະກິດ</h4>
                            <p class="text-muted">ສະເໜີສິນຄ້າ IT ທີ່ມີຄຸນນະພາບສູງ ພ້ອມກັບບໍລິການທີ່ເດັ່ນເດື່ອນ ເພື່ອສ້າງປະສົບການການຊື້ສິນຄ້າທີ່ດີເລີດໃຫ້ກັບລູກຄ້າ</p>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-eye"></i>
                            </div>
                            <h4>ວິໄສທັດ</h4>
                            <p class="text-muted">ເປັນຜູ້ນໍາດ້ານການຂາຍສິນຄ້າ IT ອອນລາຍໃນລາວ ແລະ ເປັນຄູ່ຮ່ວມງານທີ່ໄວ້ວາງໃຈຂອງລູກຄ້າ</p>
                        </div>
                    </div>
                </div>
                
                <!-- Statistics -->
                <div class="row mb-5">
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="stats-card">
                            <div class="stats-number">500+</div>
                            <p class="mb-0">ລູກຄ້າພໍໃຈ</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="stats-card">
                            <div class="stats-number">1000+</div>
                            <p class="mb-0">ສິນຄ້າຄຸນນະພາບ</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="stats-card">
                            <div class="stats-number">24/7</div>
                            <p class="mb-0">ບໍລິການສະໜັບສະໜູນ</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="stats-card">
                            <div class="stats-number">5+</div>
                            <p class="mb-0">ປີປະສົບການ</p>
                        </div>
                    </div>
                </div>
                
                <!-- Our Story -->
                <div class="row mb-5">
                    <div class="col-lg-8 mx-auto">
                        <h3 class="text-center mb-4">
                            <i class="fas fa-history me-2"></i>ເລື່ອງລາວຂອງພວກເຮົາ
                        </h3>
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-content">
                                    <h5>2019 - ການເລີ່ມຕົ້ນ</h5>
                                    <p class="text-muted">ການສ້າງຕັ້ງບໍລິສັດ ແລະ ການພັດທະນາລະບົບຂາຍສິນຄ້າອອນລາຍ</p>
                                </div>
                                <div class="timeline-dot"></div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-content">
                                    <h5>2020 - ການຂະຫຍາຍຕົວ</h5>
                                    <p class="text-muted">ເພີ່ມສິນຄ້າໃໝ່ ແລະ ປັບປຸງລະບົບບໍລິການ</p>
                                </div>
                                <div class="timeline-dot"></div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-content">
                                    <h5>2021 - ການປັບປຸງ</h5>
                                    <p class="text-muted">ການເພີ່ມຄຸນສົມບັດໃໝ່ ແລະ ການປັບປຸງປະສົບການຜູ້ໃຊ້</p>
                                </div>
                                <div class="timeline-dot"></div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-content">
                                    <h5>2024 - ປັດຈຸບັນ</h5>
                                    <p class="text-muted">ເປັນຜູ້ນໍາດ້ານການຂາຍສິນຄ້າ IT ອອນລາຍໃນລາວ</p>
                                </div>
                                <div class="timeline-dot"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Our Values -->
                <div class="row mb-5">
                    <div class="col-12">
                        <h3 class="text-center mb-4">
                            <i class="fas fa-heart me-2"></i>ຄ່ານິຍົມຂອງພວກເຮົາ
                        </h3>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <h5>ຄຸນນະພາບ</h5>
                            <p class="text-muted">ສະເໜີສິນຄ້າທີ່ມີຄຸນນະພາບສູງ ແລະ ໄວ້ວາງໃຈໄດ້</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <h5>ຄວາມໄວ້ວາງໃຈ</h5>
                            <p class="text-muted">ສ້າງຄວາມໄວ້ວາງໃຈກັບລູກຄ້າຜ່ານການບໍລິການທີ່ເດັ່ນເດື່ອນ</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-lightbulb"></i>
                            </div>
                            <h5>ນະວັດຕະກໍາ</h5>
                            <p class="text-muted">ຄົ້ນຄວ້າ ແລະ ພັດທະນາລະບົບໃໝ່ໆຢູ່ສະເໝີ</p>
                        </div>
                    </div>
                </div>
                
                <!-- Team -->
                <div class="row mb-5">
                    <div class="col-12">
                        <h3 class="text-center mb-4">
                            <i class="fas fa-users me-2"></i>ທີມງານຂອງພວກເຮົາ
                        </h3>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="team-card">
                            <div class="team-avatar">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <h5>ທ້າວ ສົມສະໄໝ ສົມສະຫວັນ</h5>
                            <p class="text-muted">ຜູ້ອໍານວຍການບໍລິສັດ</p>
                            <p class="small text-muted">ມີປະສົບການດ້ານ IT ຫຼາຍກວ່າ 10 ປີ</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="team-card">
                            <div class="team-avatar">
                                <i class="fas fa-user-cog"></i>
                            </div>
                            <h5>ນາງ ສົມສະຫວັນ ສົມສະໄໝ</h5>
                            <p class="text-muted">ຜູ້ຈັດການດ້ານການຂາຍ</p>
                            <p class="small text-muted">ມີປະສົບການດ້ານການຂາຍຫຼາຍກວ່າ 8 ປີ</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="team-card">
                            <div class="team-avatar">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <h5>ທ້າວ ສົມສະຫວັນ ສົມສະໄໝ</h5>
                            <p class="text-muted">ຜູ້ພັດທະນາລະບົບ</p>
                            <p class="small text-muted">ມີປະສົບການດ້ານການພັດທະນາລະບົບ 6 ປີ</p>
                        </div>
                    </div>
                </div>
                
                <!-- Call to Action -->
                <div class="text-center">
                    <h4 class="mb-4">ພ້ອມກັບພວກເຮົາໃນການສ້າງປະສົບການການຊື້ສິນຄ້າທີ່ດີເລີດ</h4>
                    <a href="products.php" class="btn btn-primary btn-lg me-3">
                        <i class="fas fa-shopping-bag me-2"></i>ຊື້ສິນຄ້າຕອນນີ້
                    </a>
                    <a href="contact.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-envelope me-2"></i>ຕິດຕໍ່ພວກເຮົາ
                    </a>
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
</body>
</html> 
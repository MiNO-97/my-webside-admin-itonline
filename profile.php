<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if customer is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$customer_id = $_SESSION['customer_id'];

// Get customer information
$customer = getCustomerById($db, $customer_id);

// Handle profile update
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $first_name = sanitizeInput($_POST['first_name']);
        $email = sanitizeInput($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);
        $address = sanitizeInput($_POST['address']);

        $errors = [];

        if (empty($first_name)) {
            $errors[] = 'ກະລຸນາປ້ອນຊື່';
        }

        if (empty($email) || !isValidEmail($email)) {
            $errors[] = 'ກະລຸນາປ້ອນອີເມວທີ່ຖືກຕ້ອງ';
        }

        if (empty($errors)) {
            try {
                $stmt = $db->prepare("UPDATE customers SET first_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                $stmt->execute([$first_name, $email, $phone, $address, $customer_id]);

                // Update session
                $_SESSION['customer_name'] = $first_name;

                $success = 'ອັບເດດໂປຣໄຟລ໌ສໍາເລັດແລ້ວ';

                // Refresh customer data
                $customer = getCustomerById($db, $customer_id);
            } catch (PDOException $e) {
                $error = 'ເກີດຂໍ້ຜິດພາດໃນການອັບເດດ: ' . $e->getMessage();
            }
        }
    }

    if ($_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        $errors = [];

        if (empty($current_password)) {
            $errors[] = 'ກະລຸນາປ້ອນລະຫັດປັດຈຸບັນ';
        }

        if (empty($new_password)) {
            $errors[] = 'ກະລຸນາປ້ອນລະຫັດໃໝ່';
        }

        if (strlen($new_password) < 6) {
            $errors[] = 'ລະຫັດຕ້ອງມີຢ່າງໜ້ອຍ 6 ຕົວອັກສອນ';
        }

        if ($new_password !== $confirm_password) {
            $errors[] = 'ລະຫັດໃໝ່ບໍ່ກົງກັນ';
        }

        if (empty($errors)) {
            // Verify current password
            if (password_verify($current_password, $customer['password'])) {
                try {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE customers SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $customer_id]);

                    $success = 'ປ່ຽນລະຫັດຜ່ານສໍາເລັດແລ້ວ';
                } catch (PDOException $e) {
                    $error = 'ເກີດຂໍ້ຜິດພາດໃນການປ່ຽນລະຫັດຜ່ານ: ' . $e->getMessage();
                }
            } else {
                $errors[] = 'ລະຫັດປັດຈຸບັນບໍ່ຖືກຕ້ອງ';
            }
        }
    }
}

// Get customer orders
$orders = getCustomerOrders($db, $customer_id);
?>

<!DOCTYPE html>
<html lang="lo">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ໂປຣໄຟລ໌ - ລະບົບຂາຍສິນຄ້າ IT</title>

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

        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 3rem 2rem;
            text-align: center;
            margin-bottom: 3rem;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 1rem;
        }

        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }

        .profile-card:hover {
            transform: translateY(-5px);
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

        .order-card {
            border: 1px solid #e9ecef;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .order-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background: #cce5ff;
            color: #004085;
        }

        .status-shipped {
            background: #d4edda;
            color: #155724;
        }

        .status-delivered {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .nav-pills .nav-link {
            border-radius: 25px;
            margin: 0 0.25rem;
        }

        .nav-pills .nav-link.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
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
                <a class="nav-link" href="cart.php">
                    <i class="fas fa-shopping-cart me-1"></i>ກະຕ່າ
                    <?php $cart_count = getCartItemCount($db, $_SESSION['customer_id']); ?>
                    <?php if ($cart_count > 0): ?>
                        <span class="badge bg-primary"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['customer_name']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item active" href="profile.php">ໂປຣໄຟລ໌</a></li>
                        <li><a class="dropdown-item" href="orders.php">ຄໍາສັ່ງຊື້ຂອງຂ້ອຍ</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="logout.php">ອອກຈາກລະບົບ</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="main-content">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h2><?php echo htmlspecialchars($customer['first_name']); ?></h2>
                <p class="mb-0">ສະມາຊິກຕັ້ງແຕ່: <?php echo date('d/m/Y', strtotime($customer['created_at'])); ?></p>
            </div>

            <div class="p-4">
                <!-- Navigation Tabs -->
                <ul class="nav nav-pills mb-4" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="profile-tab" data-bs-toggle="pill" data-bs-target="#profile" type="button" role="tab">
                            <i class="fas fa-user-edit me-2"></i>ໂປຣໄຟລ໌
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="password-tab" data-bs-toggle="pill" data-bs-target="#password" type="button" role="tab">
                            <i class="fas fa-lock me-2"></i>ປ່ຽນລະຫັດຜ່ານ
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="orders-tab" data-bs-toggle="pill" data-bs-target="#orders" type="button" role="tab">
                            <i class="fas fa-shopping-bag me-2"></i>ຄໍາສັ່ງຊື້ (<?php echo count($orders); ?>)
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="profileTabContent">
                    <!-- Profile Tab -->
                    <div class="tab-pane fade show active" id="profile" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-8 mx-auto">
                                <div class="profile-card">
                                    <h4 class="mb-4">
                                        <i class="fas fa-user-edit me-2"></i>ແກ້ໄຂຂໍ້ມູນໂປຣໄຟລ໌
                                    </h4>

                                    <?php if (isset($success)): ?>
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($error)): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
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
                                        <input type="hidden" name="action" value="update_profile">

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="first_name" class="form-label">ຊື່ *</label>
                                                <input type="text" class="form-control" id="first_name" name="first_name"
                                                    value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="email" class="form-label">ອີເມວ *</label>
                                                <input type="email" class="form-control" id="email" name="email"
                                                    value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="phone" class="form-label">ເບີໂທ</label>
                                                <input type="tel" class="form-control" id="phone" name="phone"
                                                    value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="address" class="form-label">ທີ່ຢູ່</label>
                                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                                            </div>
                                        </div>

                                        <div class="text-center">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>ບັນທຶກການປ່ຽນແປງ
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Password Tab -->
                    <div class="tab-pane fade" id="password" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-6 mx-auto">
                                <div class="profile-card">
                                    <h4 class="mb-4">
                                        <i class="fas fa-lock me-2"></i>ປ່ຽນລະຫັດຜ່ານ
                                    </h4>

                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="change_password">

                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">ລະຫັດປັດຈຸບັນ *</label>
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">ລະຫັດໃໝ່ *</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">ຢືນຢັນລະຫັດໃໝ່ *</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>

                                        <div class="text-center">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-key me-2"></i>ປ່ຽນລະຫັດຜ່ານ
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Tab -->
                    <div class="tab-pane fade" id="orders" role="tabpanel">
                        <div class="row">
                            <div class="col-12">
                                <div class="profile-card">
                                    <h4 class="mb-4">
                                        <i class="fas fa-shopping-bag me-2"></i>ປະຫວັດຄໍາສັ່ງຊື້
                                    </h4>

                                    <?php if (empty($orders)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                                            <h5>ຍັງບໍ່ມີຄໍາສັ່ງຊື້</h5>
                                            <p class="text-muted">ເລີ່ມຊື້ສິນຄ້າຕອນນີ້!</p>
                                            <a href="products.php" class="btn btn-primary">
                                                <i class="fas fa-shopping-bag me-2"></i>ຊື້ສິນຄ້າ
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($orders as $order): ?>
                                            <div class="order-card">
                                                <div class="row align-items-center">
                                                    <div class="col-md-3">
                                                        <h6 class="mb-1">ຄໍາສັ່ງຊື້ #<?php echo htmlspecialchars($order['order_number']); ?></h6>
                                                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></small>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <strong><?php echo formatCurrency($order['total_amount']); ?></strong>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                                            <?php
                                                            $status_text = match ($order['status']) {
                                                                'pending' => 'ລໍຖ້າ',
                                                                'processing' => 'ກໍາລັງດໍາເນີນການ',
                                                                'shipped' => 'ສົ່ງແລ້ວ',
                                                                'delivered' => 'ຈັດສົ່ງແລ້ວ',
                                                                'cancelled' => 'ຍົກເລີກ',
                                                                default => $order['status']
                                                            };
                                                            echo $status_text;
                                                            ?>
                                                        </span>
                                                    </div>
                                                    <div class="col-md-3 text-end">
                                                        <a href="order_details.php?order_id=<?php echo $order['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-eye me-1"></i>ເບິ່ງລາຍລະອຽດ
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
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
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;

            if (newPassword !== confirmPassword) {
                this.setCustomValidity('ລະຫັດບໍ່ກົງກັນ');
            } else {
                this.setCustomValidity('');
            }
        });

        document.getElementById('new_password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value) {
                confirmPassword.dispatchEvent(new Event('input'));
            }
        });
    </script>
</body>

</html>
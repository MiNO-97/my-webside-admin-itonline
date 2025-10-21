<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if customer is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$order_id = $_GET['order_id'] ?? '';
if (empty($order_id)) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$customer_id = $_SESSION['customer_id'];

// Get order details
$order_query = "SELECT o.*, c.first_name, c.last_name, c.email, c.phone 
                FROM orders o 
                LEFT JOIN customers c ON o.customer_id = c.id 
                WHERE o.id = ? AND o.customer_id = ?";
$stmt = $db->prepare($order_query);
$stmt->execute([$order_id, $customer_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: index.php");
    exit();
}

// Get order items
$items_query = "SELECT oi.*, p.name, p.image_url 
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?";
$stmt = $db->prepare($items_query);
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="lo">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຢືນຢັນຄໍາສັ່ງຊື້ - ລະບົບຂາຍສິນຄ້າ IT</title>

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

        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #28a745, #20c997);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin: 0 auto 2rem;
        }

        .order-details {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
        }

        .order-item {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            background: white;
        }

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
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

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-shipped {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-delivered {
            background-color: #d4edda;
            color: #155724;
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
                </a>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['customer_name']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php">ໂປຣໄຟລ໌</a></li>
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
            <div class="text-center p-5">
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>

                <h2 class="mb-3">ຂອບໃຈທີ່ຊື້ສິນຄ້າກັບພວກເຮົາ!</h2>
                <p class="lead text-muted mb-4">ຄໍາສັ່ງຊື້ຂອງທ່ານໄດ້ຮັບການຢືນຢັນແລ້ວ</p>

                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="order-details">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="mb-3">ລາຍລະອຽດຄໍາສັ່ງຊື້</h5>
                                    <p><strong>ລະຫັດຄໍາສັ່ງຊື້:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                                    <p><strong>ວັນທີ:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                                    <p><strong>ສະຖານະ:</strong>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php
                                            $status_text = [
                                                'pending' => 'ລໍຖ້າ',
                                                'processing' => 'ກໍາລັງດໍາເນີນການ',
                                                'shipped' => 'ສົ່ງແລ້ວ',
                                                'delivered' => 'ຈັດສົ່ງແລ້ວ'
                                            ];
                                            echo $status_text[$order['status']] ?? $order['status'];
                                            ?>
                                        </span>
                                    </p>
                                    <p><strong>ວິທີການຊໍາລະ:</strong>
                                        <?php echo $order['payment_method'] === 'cod' ? 'ຊໍາລະເງິນປາຍທາງ' : 'ໂອນເງິນທະນາຄານ'; ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="mb-3">ຂໍ້ມູນການຈັດສົ່ງ</h5>
                                    <p><strong>ທີ່ຢູ່:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
                                    <p><strong>ເມືອງ:</strong> <?php echo htmlspecialchars($order['shipping_city']); ?></p>
                                    <p><strong>ເບີໂທ:</strong> <?php echo htmlspecialchars($order['shipping_phone']); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <h5 class="mb-3">ສິນຄ້າທີ່ສັ່ງຊື້</h5>
                        <?php foreach ($order_items as $item): ?>
                            <div class="order-item">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <?php if ($item['image_url']): ?>
                                            <img src="uploads/products/<?php echo htmlspecialchars($item['image_url']); ?>"
                                                class="product-image" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <?php else: ?>
                                            <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <small class="text-muted">ຈໍານວນ: <?php echo $item['quantity']; ?></small>
                                    </div>
                                    <div class="col-md-2">
                                        <strong><?php echo formatCurrency($item['unit_price']); ?></strong>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <strong><?php echo formatCurrency($item['unit_price'] * $item['quantity']); ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Order Total -->
                        <div class="text-end mt-4">
                            <h5>ລວມ: <span class="text-primary"><?php echo formatCurrency($order['total_amount']); ?></span></h5>
                        </div>

                        <?php if (!empty($order['notes'])): ?>
                            <div class="mt-4">
                                <h6>ໝາຍເຫດ:</h6>
                                <p class="text-muted"><?php echo htmlspecialchars($order['notes']); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="mt-5">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>ຂໍ້ມູນເພີ່ມເຕີມ:</strong><br>
                                • ການຈັດສົ່ງຈະໃຊ້ເວລາ 2-3 ວັນເຮືອນ<br>
                                • ທ່ານຈະໄດ້ຮັບການແຈ້ງເຕືອນຜ່ານອີເມວເມື່ອສິນຄ້າຖືກສົ່ງ<br>
                                • ສາມາດຕິດຕາມສະຖານະຄໍາສັ່ງຊື້ໄດ້ໃນໜ້າ "ຄໍາສັ່ງຊື້ຂອງຂ້ອຍ"
                            </div>
                        </div>

                        <div class="d-flex justify-content-center gap-3 mt-4">
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-shopping-bag me-2"></i>ຊື້ສິນຄ້າຕໍ່
                            </a>
                            <a href="orders.php" class="btn btn-outline-secondary">
                                <i class="fas fa-list me-2"></i>ເບິ່ງຄໍາສັ່ງຊື້ຂອງຂ້ອຍ
                            </a>
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
        // Show success message
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'ສໍາເລັດ!',
                text: 'ຄໍາສັ່ງຊື້ຂອງທ່ານໄດ້ຮັບການຢືນຢັນແລ້ວ',
                confirmButtonText: 'ຕົກລົງ',
                timer: 3000,
                timerProgressBar: true
            });
        });
    </script>
</body>

</html>
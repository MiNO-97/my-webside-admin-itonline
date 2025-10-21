<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if customer is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$order_id = $_GET['id'] ?? '';
if (empty($order_id)) {
    header("Location: orders.php");
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
    header("Location: orders.php");
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
    <title>ລາຍລະອຽດຄໍາສັ່ງຊື້ - ລະບົບຂາຍສິນຄ້າ IT</title>

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

        .order-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .order-item {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: white;
        }

        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
        }

        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            padding: 10px 20px;
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

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .info-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
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
                    <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['customer_name']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php">ໂປຣໄຟລ໌</a></li>
                        <li><a class="dropdown-item active" href="orders.php">ຄໍາສັ່ງຊື້ຂອງຂ້ອຍ</a></li>
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
            <div class="p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-file-alt me-2"></i>ລາຍລະອຽດຄໍາສັ່ງຊື້</h2>
                    <a href="orders.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>ກັບຄືນ
                    </a>
                </div>

                <!-- Order Header -->
                <div class="order-header">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-3">ຂໍ້ມູນຄໍາສັ່ງຊື້</h5>
                            <p><strong>ລະຫັດຄໍາສັ່ງຊື້:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                            <p><strong>ວັນທີສັ່ງຊື້:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                            <p><strong>ສະຖານະ:</strong>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php
                                    $status_text = [
                                        'pending' => 'ລໍຖ້າ',
                                        'processing' => 'ກໍາລັງດໍາເນີນການ',
                                        'shipped' => 'ສົ່ງແລ້ວ',
                                        'delivered' => 'ຈັດສົ່ງແລ້ວ',
                                        'cancelled' => 'ຍົກເລີກ'
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
                            <div class="col-md-4">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <?php if ($item['name']): ?>
                                    <small class="text-muted">ລະຫັດສິນຄ້າ: <?php echo htmlspecialchars($item['name']); ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-2">
                                <small class="text-muted">ຈໍານວນ:</small><br>
                                <strong><?php echo $item['quantity']; ?></strong>
                            </div>
                            <div class="col-md-2">
                                <small class="text-muted">ລາຄາຕໍ່ຊິ້ນ:</small><br>
                                <strong><?php echo formatCurrency($item['unit_price']); ?></strong>
                            </div>
                            <div class="col-md-2 text-end">
                                <small class="text-muted">ລວມ:</small><br>
                                <strong class="text-primary"><?php echo formatCurrency($item['unit_price'] * $item['quantity']); ?></strong>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Order Summary -->
                <div class="row mt-4">
                    <div class="col-md-8">
                        <?php if (!empty($order['notes'])): ?>
                            <div class="info-card">
                                <h6>ໝາຍເຫດ:</h6>
                                <p class="mb-0"><?php echo htmlspecialchars($order['notes']); ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Order Status Timeline -->
                        <div class="info-card">
                            <h6>ສະຖານະຄໍາສັ່ງຊື້:</h6>
                            <div class="timeline">
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-success"></div>
                                    <div class="timeline-content">
                                        <strong>ສັ່ງຊື້ແລ້ວ</strong>
                                        <small class="text-muted d-block"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></small>
                                    </div>
                                </div>

                                <?php if ($order['status'] !== 'pending'): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-primary"></div>
                                        <div class="timeline-content">
                                            <strong>ກໍາລັງດໍາເນີນການ</strong>
                                            <small class="text-muted d-block">ກໍາລັງກວດສອບຄໍາສັ່ງຊື້</small>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (in_array($order['status'], ['shipped', 'delivered'])): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-info"></div>
                                        <div class="timeline-content">
                                            <strong>ສົ່ງແລ້ວ</strong>
                                            <small class="text-muted d-block">ສິນຄ້າຖືກສົ່ງແລ້ວ</small>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($order['status'] === 'delivered'): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-success"></div>
                                        <div class="timeline-content">
                                            <strong>ຈັດສົ່ງແລ້ວ</strong>
                                            <small class="text-muted d-block">ສິນຄ້າຖືກຈັດສົ່ງແລ້ວ</small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="info-card">
                            <h6>ສະຫຼຸບຄໍາສັ່ງຊື້</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span>ສິນຄ້າ (<?php echo count($order_items); ?>):</span>
                                <span><?php echo formatCurrency($order['total_amount']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>ຄ່າສົ່ງ:</span>
                                <span>ຟຣີ</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>ລວມ:</strong>
                                <strong class="text-primary fs-5"><?php echo formatCurrency($order['total_amount']); ?></strong>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-3">
                            <a href="orders.php" class="btn btn-outline-secondary">
                                <i class="fas fa-list me-2"></i>ເບິ່ງຄໍາສັ່ງຊື້ທັງໝົດ
                            </a>
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-shopping-bag me-2"></i>ຊື້ສິນຄ້າຕໍ່
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

    <style>
        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 1rem;
        }

        .timeline-marker {
            position: absolute;
            left: -35px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .timeline-item:not(:last-child)::after {
            content: '';
            position: absolute;
            left: -29px;
            top: 17px;
            width: 2px;
            height: calc(100% + 10px);
            background-color: #dee2e6;
        }
    </style>
</body>

</html>
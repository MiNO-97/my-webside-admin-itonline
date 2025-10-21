<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if customer is logged in (after including functions.php)
if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (empty($order_id)) {
    header("Location: orders.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$customer_id = $_SESSION['customer_id'];

// Get order details with better error handling
try {
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

} catch(PDOException $e) {
    // Log error and redirect
    error_log("Database error in order_tracking.php: " . $e->getMessage());
    header("Location: orders.php");
    exit();
}

// Define order status steps
$status_steps = [
    'pending' => ['step' => 1, 'title' => 'ລໍຖ້າການຢືນຢັນ', 'icon' => 'clock', 'color' => 'warning'],
    'confirmed' => ['step' => 2, 'title' => 'ຢືນຢັນແລ້ວ', 'icon' => 'check-circle', 'color' => 'info'],
    'processing' => ['step' => 3, 'title' => 'ກໍາລັງຈັດການ', 'icon' => 'cog', 'color' => 'primary'],
    'shipped' => ['step' => 4, 'title' => 'ສົ່ງແລ້ວ', 'icon' => 'truck', 'color' => 'info'],
    'delivered' => ['step' => 5, 'title' => 'ສົ່ງເຖິງແລ້ວ', 'icon' => 'home', 'color' => 'success'],
    'cancelled' => ['step' => 0, 'title' => 'ຍົກເລີກແລ້ວ', 'icon' => 'times-circle', 'color' => 'danger'],
    'refund_requested' => ['step' => 0, 'title' => 'ຂໍຄືນເງິນ', 'icon' => 'undo', 'color' => 'warning']
];

$current_step = $status_steps[$order['status']]['step'] ?? 0;
$current_status_info = $status_steps[$order['status']] ?? $status_steps['pending'];
?>

<!DOCTYPE html>
<html lang="lo">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຕິດຕາມຄໍາສັ່ງຊື້ #<?php echo htmlspecialchars($order['order_number']); ?> - ລະບົບຂາຍສິນຄ້າ IT</title>

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

        .tracking-steps {
            position: relative;
            margin: 3rem 0;
        }

        .tracking-steps::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }

        .step {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .step-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: white;
            background: #e9ecef;
            transition: all 0.3s ease;
        }

        .step.active .step-icon {
            background: #007bff;
            transform: scale(1.1);
        }

        .step.completed .step-icon {
            background: #28a745;
        }

        .step-title {
            font-size: 0.9rem;
            font-weight: 500;
            color: #6c757d;
        }

        .step.active .step-title {
            color: #007bff;
            font-weight: 600;
        }

        .step.completed .step-title {
            color: #28a745;
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
            transition: all 0.3s ease;
        }

        .order-item:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 0.5rem 1rem;
        }

        .loading {
            display: none;
        }

        .loading.show {
            display: block;
        }

        @media (max-width: 768px) {
            .tracking-steps .step-title {
                font-size: 0.8rem;
            }
            
            .step-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .order-header {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-laptop me-2"></i>POS IT Online
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home me-1"></i>ໜ້າຫຼັກ
                </a>
                <a class="nav-link" href="orders.php">
                    <i class="fas fa-shopping-bag me-1"></i>ຄໍາສັ່ງຊື້
                </a>
                <a class="nav-link" href="cart.php">
                    <i class="fas fa-shopping-cart me-1"></i>ກະຕ່າ
                    <?php if (function_exists('getCartItemCount')): ?>
                        <span class="badge bg-primary rounded-pill"><?php echo getCartItemCount($db, $customer_id); ?></span>
                    <?php endif; ?>
                </a>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['customer_name'] ?? 'Customer'); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user-edit me-2"></i>ໂປຣໄຟລ໌
                            </a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>ອອກຈາກລະບົບ
                            </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="main-content p-4">
            <!-- Order Header -->
            <div class="order-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="mb-2">
                            <i class="fas fa-shopping-bag me-2"></i>ຄໍາສັ່ງຊື້ #<?php echo htmlspecialchars($order['order_number']); ?>
                        </h4>
                        <p class="text-muted mb-0">
                            <i class="fas fa-calendar me-1"></i>
                            ວັນທີ: <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="badge bg-<?php echo $current_status_info['color']; ?> status-badge">
                            <i class="fas fa-<?php echo $current_status_info['icon']; ?> me-1"></i>
                            <?php echo $current_status_info['title']; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tracking Steps -->
            <div class="tracking-steps">
                <div class="row">
                    <?php
                    $steps = [
                        ['title' => 'ລໍຖ້າການຢືນຢັນ', 'icon' => 'clock'],
                        ['title' => 'ຢືນຢັນແລ້ວ', 'icon' => 'check-circle'],
                        ['title' => 'ກໍາລັງຈັດການ', 'icon' => 'cog'],
                        ['title' => 'ສົ່ງແລ້ວ', 'icon' => 'truck'],
                        ['title' => 'ສົ່ງເຖິງແລ້ວ', 'icon' => 'home']
                    ];

                    foreach ($steps as $index => $step) {
                        $step_number = $index + 1;
                        $is_active = $step_number === $current_step;
                        $is_completed = $step_number < $current_step;
                        $step_class = $is_active ? 'active' : ($is_completed ? 'completed' : '');
                    ?>
                        <div class="col step <?php echo $step_class; ?>">
                            <div class="step-icon">
                                <i class="fas fa-<?php echo $step['icon']; ?>"></i>
                            </div>
                            <div class="step-title"><?php echo $step['title']; ?></div>
                        </div>
                    <?php
                    }
                    ?>
                </div>
            </div>

            <!-- Order Details -->
            <div class="row">
                <div class="col-md-8">
                    <h5 class="mb-3">
                        <i class="fas fa-box me-2"></i>ລາຍການສິນຄ້າ
                    </h5>
                    <?php if (empty($order_items)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ບໍ່ມີລາຍການສິນຄ້າ
                        </div>
                    <?php else: ?>
                        <?php foreach ($order_items as $item): ?>
                            <div class="order-item">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <?php if ($item['image_url']): ?>
                                            <img src="uploads/products/<?php echo htmlspecialchars($item['image_url']); ?>"
                                                alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                class="product-image">
                                        <?php else: ?>
                                            <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <p class="text-muted mb-0">
                                            Product ID: <?php echo htmlspecialchars($item['product_id']); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <span class="badge bg-light text-dark"><?php echo $item['quantity']; ?> ຊິ້ນ</span>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <strong><?php echo number_format($item['unit_price'] * $item['quantity'], 0); ?> ກີບ</strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>ຂໍ້ມູນການຈັດສົ່ງ
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">
                                <strong>ທີ່ຢູ່:</strong><br>
                                <?php echo htmlspecialchars($order['shipping_address'] ?? 'N/A'); ?><br>
                                <?php echo htmlspecialchars($order['shipping_city'] ?? ''); ?>
                            </p>
                            <p class="mb-2">
                                <strong>ໂທລະສັບ:</strong><br>
                                <?php echo htmlspecialchars($order['shipping_phone'] ?? 'N/A'); ?>
                            </p>
                            <p class="mb-2">
                                <strong>ວິທີການຊໍາລະ:</strong><br>
                                <?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?>
                            </p>
                            <?php if (!empty($order['notes'])): ?>
                                <p class="mb-0">
                                    <strong>ໝາຍເຫດ:</strong><br>
                                    <?php echo htmlspecialchars($order['notes']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-calculator me-2"></i>ລາຍການຄ່າໃຊ້ຈ່າຍ
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>ລາຄາສິນຄ້າ:</span>
                                <span><?php echo number_format($order['total_amount'], 0); ?> ກີບ</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>ຄ່າຈັດສົ່ງ:</span>
                                <span>0 ກີບ</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>ລວມ:</span>
                                <span><?php echo number_format($order['total_amount'], 0); ?> ກີບ</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-3">
                        <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                            <button class="btn btn-danger w-100 mb-2" onclick="cancelOrder(<?php echo $order['id']; ?>)" id="cancelBtn">
                                <i class="fas fa-times me-2"></i>ຍົກເລີກຄໍາສັ່ງຊື້
                                <span class="loading" id="cancelLoading">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </button>
                        <?php endif; ?>

                        <!-- <?php if ($order['status'] === 'delivered'): ?>
                            <button class="btn btn-warning w-100 mb-2" onclick="requestRefund(<?php echo $order['id']; ?>)" id="refundBtn">
                                <i class="fas fa-undo me-2"></i>ຂໍຄືນເງິນ
                                <span class="loading" id="refundLoading">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </button>
                        <?php endif; ?> -->

                        <a href="orders.php" class="btn btn-secondary w-100">
                            <i class="fas fa-arrow-left me-2"></i>ກັບໄປ
                        </a>
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
        function showLoading(buttonId, loadingId) {
            const button = document.getElementById(buttonId);
            const loading = document.getElementById(loadingId);
            if (button && loading) {
                button.disabled = true;
                loading.classList.add('show');
            }
        }

        function hideLoading(buttonId, loadingId) {
            const button = document.getElementById(buttonId);
            const loading = document.getElementById(loadingId);
            if (button && loading) {
                button.disabled = false;
                loading.classList.remove('show');
            }
        }

        function cancelOrder(orderId) {
            Swal.fire({
                title: 'ຢືນຢັນການຍົກເລີກ',
                text: 'ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການຍົກເລີກຄໍາສັ່ງຊື້ນີ້?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'ຍົກເລີກ',
                cancelButtonText: 'ຍົກເລີກ',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoading('cancelBtn', 'cancelLoading');
                    
                    fetch('ajax/update_order_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                order_id: orderId,
                                action: 'cancel'
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            hideLoading('cancelBtn', 'cancelLoading');
                            if (data.success) {
                                Swal.fire('ສໍາເລັດ!', data.message, 'success').then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('ຜິດພາດ!', data.message, 'error');
                            }
                        })
                        .catch(error => {
                            hideLoading('cancelBtn', 'cancelLoading');
                            Swal.fire('ຜິດພາດ!', 'ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່', 'error');
                        });
                }
            });
        }

        function requestRefund(orderId) {
            Swal.fire({
                title: 'ຂໍຄືນເງິນ',
                text: 'ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການຂໍຄືນເງິນສໍາລັບຄໍາສັ່ງຊື້ນີ້?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'ຂໍຄືນເງິນ',
                cancelButtonText: 'ຍົກເລີກ',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    showLoading('refundBtn', 'refundLoading');
                    
                    fetch('ajax/update_order_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                order_id: orderId,
                                action: 'request_refund'
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            hideLoading('refundBtn', 'refundLoading');
                            if (data.success) {
                                Swal.fire('ສໍາເລັດ!', data.message, 'success').then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('ຜິດພາດ!', data.message, 'error');
                            }
                        })
                        .catch(error => {
                            hideLoading('refundBtn', 'refundLoading');
                            Swal.fire('ຜິດພາດ!', 'ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່', 'error');
                        });
                }
            });
        }
    </script>
</body>

</html>
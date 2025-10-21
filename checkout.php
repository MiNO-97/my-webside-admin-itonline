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

// Get cart items
$cart_items = getCartItems($db, $customer_id);

if (empty($cart_items)) {
    header("Location: cart.php");
    exit();
}

// Calculate totals
$subtotal = 0;
$total_items = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $total_items += $item['quantity'];
}

$shipping = 0; // Free shipping for now

// COD fee calculation
$cod_fee = 0;
if (isset($_POST['payment_method']) && $_POST['payment_method'] === 'cod') {
    $cod_fee = 5000; // 5,000 LAK COD fee
}

$total = $subtotal + $shipping + $cod_fee;

// Handle checkout submission
if ($_POST) {
    $shipping_address = sanitizeInput($_POST['shipping_address']);
    $shipping_city = sanitizeInput($_POST['shipping_city']);
    $shipping_phone = sanitizeInput($_POST['shipping_phone']);
    $payment_method = sanitizeInput($_POST['payment_method']);
    $notes = sanitizeInput($_POST['notes']);
    
    // Handle bank transfer slip upload
    $transfer_slip = null;
    if ($payment_method === 'bank_transfer') {
        if (!isset($_FILES['transfer_slip']) || $_FILES['transfer_slip']['error'] !== UPLOAD_ERR_OK) {
            $error = 'ກະລຸນາອັບໂຫລດສະລິບໂອນເງິນ';
        } else {
            $file = $_FILES['transfer_slip'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file['type'], $allowed_types)) {
                $error = 'ຮູບແບບໄຟລ໌ບໍ່ຖືກຕ້ອງ (ຮູບພາບ ຫຼື PDF ເທົ່ານັ້ນ)';
            } elseif ($file['size'] > $max_size) {
                $error = 'ຂະໜາດໄຟລ໌ໃຫຍ່ເກີນໄປ (ສູງສຸດ 5MB)';
            } else {
                // Create uploads directory if it doesn't exist
                $uploads_dir = 'uploads/transfer_slips';
                if (!is_dir($uploads_dir)) {
                    mkdir($uploads_dir, 0755, true);
                }
                
                // Generate unique filename
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'transfer_' . time() . '_' . uniqid() . '.' . $file_extension;
                $filepath = $uploads_dir . '/' . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $transfer_slip = $filepath;
                } else {
                    $error = 'ເກີດຂໍ້ຜິດພາດໃນການອັບໂຫລດໄຟລ໌';
                }
            }
        }
    }
    
    if (empty($shipping_address) || empty($shipping_city) || empty($shipping_phone)) {
        $error = 'ກະລຸນາປ້ອນຂໍ້ມູນການຈັດສົ່ງທີ່ຈໍາເປັນ';
    } elseif ($payment_method === 'bank_transfer' && !$transfer_slip) {
        $error = 'ກະລຸນາອັບໂຫລດສະລິບໂອນເງິນ';
    } else {
        try {
            $db->beginTransaction();
            
            // Create order
            $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());
            $order_query = "INSERT INTO orders (customer_id, order_number, total_amount, shipping_address, shipping_city, shipping_phone, payment_method, transfer_slip, notes, status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = $db->prepare($order_query);
            $stmt->execute([$customer_id, $order_number, $total, $shipping_address, $shipping_city, $shipping_phone, $payment_method, $transfer_slip, $notes]);
            $order_id = $db->lastInsertId();
            
            // Create order items
            $order_item_query = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($order_item_query);
            
            foreach ($cart_items as $item) {
                $total_price = $item['price'] * $item['quantity'];
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price'], $total_price]);
                
                // Update product stock
                $new_stock = $item['stock_quantity'] - $item['quantity'];
                $update_stock_query = "UPDATE products SET stock_quantity = ? WHERE id = ?";
                $stmt2 = $db->prepare($update_stock_query);
                $stmt2->execute([$new_stock, $item['product_id']]);
            }
            
            // Clear cart
            $clear_cart_query = "DELETE FROM cart WHERE customer_id = ?";
            $stmt = $db->prepare($clear_cart_query);
            $stmt->execute([$customer_id]);
            
            $db->commit();
            
            // Send order confirmation email
            require_once 'includes/email_notifications.php';
            sendOrderConfirmationEmail($db, $order_id);
            
            // Redirect to order confirmation
            header("Location: order_confirmation.php?order_id=" . $order_id);
            exit();
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'ເກີດຂໍ້ຜິດພາດໃນການສໍາເລັດການຊື້: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ສືບຕໍ່ການຊື້ - ລະບົບຂາຍສິນຄ້າ IT</title>
    
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
        
        .checkout-step {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .checkout-step::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .checkout-step:hover::before {
            opacity: 1;
        }
        
        .checkout-step.active {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
        
        .checkout-step.active::before {
            background: linear-gradient(45deg, rgba(33, 150, 243, 0.1), rgba(102, 126, 234, 0.1));
        }
        
        .checkout-step > * {
            position: relative;
            z-index: 1;
        }
        
        .order-summary {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 2rem;
            position: sticky;
            top: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .order-summary::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .order-summary:hover::before {
            opacity: 1;
        }
        
        .order-summary > * {
            position: relative;
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
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
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
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">ອອກຈາກລະບົບ</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="main-content">
            <div class="row">
                <div class="col-lg-8">
                    <div class="p-4">
                        <h2 class="mb-4">
                            <i class="fas fa-credit-card me-2"></i>ສືບຕໍ່ການຊື້
                        </h2>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <!-- Shipping Information -->
                            <div class="checkout-step active">
                                <h5 class="mb-3">
                                    <i class="fas fa-shipping-fast me-2"></i>ຂໍ້ມູນການຈັດສົ່ງ
                                </h5>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="shipping_address" class="form-label">ທີ່ຢູ່ຈັດສົ່ງ *</label>
                                        <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="shipping_city" class="form-label">ເມືອງ *</label>
                                        <input type="text" class="form-control" id="shipping_city" name="shipping_city" value="" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="shipping_phone" class="form-label">ເບີໂທຈັດສົ່ງ *</label>
                                        <input type="tel" class="form-control" id="shipping_phone" name="shipping_phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Payment Method -->
                            <div class="checkout-step">
                                <h5 class="mb-3">
                                    <i class="fas fa-credit-card me-2"></i>ວິທີການຊໍາລະ
                                </h5>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod" checked>
                                            <label class="form-check-label" for="cod">
                                                <i class="fas fa-money-bill-wave me-2"></i>ຊໍາລະເງິນປາຍທາງ
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" id="bank_transfer" value="bank_transfer">
                                            <label class="form-check-label" for="bank_transfer">
                                                <i class="fas fa-university me-2"></i>ໂອນເງິນທະນາຄານ
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Bank Transfer Details -->
                                <div id="bank-transfer-details" class="mt-3" style="display: none;">
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-info-circle me-2"></i>ຂໍ້ມູນບັນຊີທະນາຄານ</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong>ຊື່ທະນາຄານ:</strong> BCEL Bank<br>
                                                <strong>ຊື່ບັນຊີ:</strong> POS IT Online<br>
                                                <strong>ເບີບັນຊີ:</strong> 010-12-345-678-901
                                            </div>
                                            <div class="col-md-6">
                                                <strong>ຈໍານວນເງິນ:</strong> <?php echo formatCurrency($total); ?><br>
                                                <strong>ໝາຍເຫດ:</strong> <?php echo 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid()); ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="transfer_slip" class="form-label">
                                            <i class="fas fa-upload me-2"></i>ອັບໂຫລດສະລິບໂອນເງິນ *
                                        </label>
                                        <input type="file" class="form-control" id="transfer_slip" name="transfer_slip" accept="image/*,.pdf" required>
                                        <div class="form-text">
                                            <small>ຮູບພາບຫຼື PDF ຂອງສະລິບໂອນເງິນ (ຂະໜາດສູງສຸດ 5MB)</small>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>ໝາຍເຫດ:</strong> ກະລຸນາໂອນເງິນຕາມຈໍານວນທີ່ລະບຸ ແລະ ອັບໂຫລດສະລິບໂອນເງິນກ່ອນສໍາເລັດການຊື້
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Additional Notes -->
                            <div class="checkout-step">
                                <h5 class="mb-3">
                                    <i class="fas fa-sticky-note me-2"></i>ໝາຍເຫດເພີ່ມເຕີມ
                                </h5>
                                <textarea class="form-control" name="notes" rows="3" placeholder="ຂໍ້ຄວາມເພີ່ມເຕີມສໍາລັບການຈັດສົ່ງ..."></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="cart.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>ກັບຄືນກະຕ່າ
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check me-2"></i>ສໍາເລັດການຊື້
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="order-summary">
                        <h4 class="mb-3">ສະຫຼຸບຄໍາສັ່ງຊື້</h4>
                        
                        <!-- Order Items -->
                        <div class="mb-3">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                        <br>
                                        <small class="text-muted">ຈໍານວນ: <?php echo $item['quantity']; ?></small>
                                    </div>
                                    <span><?php echo formatCurrency($item['price'] * $item['quantity']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <hr>
                        
                        <!-- Totals -->
                        <div class="d-flex justify-content-between mb-2">
                            <span>ສິນຄ້າ (<?php echo $total_items; ?>):</span>
                            <span><?php echo formatCurrency($subtotal); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>ຄ່າສົ່ງ:</span>
                            <span><?php echo $shipping > 0 ? formatCurrency($shipping) : 'ຟຣີ'; ?></span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <strong>ລວມ:</strong>
                            <strong class="text-primary fs-5"><?php echo formatCurrency($total); ?></strong>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <small>ການຈັດສົ່ງຈະໃຊ້ເວລາ 2-3 ວັນເຮືອນ</small>
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
        // Payment method selection
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const bankTransferDetails = document.getElementById('bank-transfer-details');
                const transferSlipInput = document.getElementById('transfer_slip');
                
                if (this.value === 'bank_transfer') {
                    bankTransferDetails.style.display = 'block';
                    transferSlipInput.required = true;
                } else {
                    bankTransferDetails.style.display = 'none';
                    transferSlipInput.required = false;
                }
            });
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = ['shipping_address', 'shipping_city', 'shipping_phone'];
            let isValid = true;
            
            // Check required fields
            requiredFields.forEach(field => {
                const element = document.getElementById(field);
                if (!element.value.trim()) {
                    isValid = false;
                    element.classList.add('is-invalid');
                } else {
                    element.classList.remove('is-invalid');
                }
            });
            
            // Check bank transfer slip if bank transfer is selected
            const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
            if (selectedPayment && selectedPayment.value === 'bank_transfer') {
                const transferSlip = document.getElementById('transfer_slip');
                if (!transferSlip.files || transferSlip.files.length === 0) {
                    isValid = false;
                    transferSlip.classList.add('is-invalid');
                } else {
                    transferSlip.classList.remove('is-invalid');
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'ຂໍ້ຜິດພາດ!',
                    text: 'ກະລຸນາປ້ອນຂໍ້ມູນທີ່ຈໍາເປັນ',
                    confirmButtonText: 'ຕົກລົງ'
                });
            }
        });
    </script>
</body>
</html> 
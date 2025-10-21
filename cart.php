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

// Handle cart actions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'update_quantity' && isset($_POST['cart_id']) && isset($_POST['quantity'])) {
        $cart_id = (int)$_POST['cart_id'];
        $quantity = (int)$_POST['quantity'];

        if ($quantity > 0 && $cart_id > 0) {
            try {
                $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND customer_id = ?");
                $stmt->execute([$quantity, $cart_id, $customer_id]);
                header("Location: cart.php?success=Cart updated successfully");
                exit();
            } catch (PDOException $e) {
                $error = "Error updating cart: " . $e->getMessage();
            }
        }
    }

    if ($_POST['action'] === 'remove_item' && isset($_POST['cart_id'])) {
        $cart_id = (int)$_POST['cart_id'];

        if ($cart_id > 0) {
            try {
                $stmt = $db->prepare("DELETE FROM cart WHERE id = ? AND customer_id = ?");
                $stmt->execute([$cart_id, $customer_id]);
                header("Location: cart.php?success=Item removed from cart");
                exit();
            } catch (PDOException $e) {
                $error = "Error removing item: " . $e->getMessage();
            }
        }
    }
}

// Get cart items with product details
try {
    $query = "SELECT c.*, p.name, p.price, p.image_url, p.stock_quantity, pc.name as category_name
              FROM cart c
              LEFT JOIN products p ON c.product_id = p.id
              LEFT JOIN product_categories pc ON p.category_id = pc.id
              WHERE c.customer_id = ? AND p.status = 'active'
              ORDER BY c.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$customer_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error loading cart: " . $e->getMessage();
    $cart_items = [];
}

// Calculate totals
$subtotal = 0;
$total_items = 0;

foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $total_items += $item['quantity'];
}

$shipping = 0; // Free shipping for now
$total = $subtotal + $shipping;
?>

<!DOCTYPE html>
<html lang="lo">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ກະຕ່າຊື້ສິນຄ້າ - ລະບົບຂາຍສິນຄ້າ IT</title>

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

        .cart-item {
            border: 1px solid #e9ecef;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            background: #ffffff;
        }

        .cart-item::before {
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

        .cart-item:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }

        .cart-item:hover::before {
            opacity: 1;
        }

        .cart-item .row {
            position: relative;
            z-index: 1;
        }

        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 12px;
            transition: transform 0.3s ease;
        }

        .product-image:hover {
            transform: scale(1.05);
        }

        .quantity-input {
            width: 70px;
            text-align: center;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            font-weight: 600;
            color: #495057;
        }

        .quantity-form {
            position: relative;
        }

        .cart-item .badge {
            padding: 0.5em 0.8em;
            font-weight: 500;
        }

        .cart-item .btn-outline-danger {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .cart-item .btn-outline-danger:hover {
            background-color: #dc3545;
            color: white;
            transform: translateY(-1px);
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

        .summary-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .summary-card::before {
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

        .summary-card:hover::before {
            opacity: 1;
        }

        .summary-card>* {
            position: relative;
            z-index: 1;
        }

        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-cart i {
            font-size: 4rem;
            color: #dee2e6;
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
                <a class="nav-link active" href="cart.php">
                    <i class="fas fa-shopping-cart me-1"></i>ກະຕ່າ
                    <?php if ($total_items > 0): ?>
                        <span class="badge bg-primary"><?php echo $total_items; ?></span>
                    <?php endif; ?>
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
            <div class="row">
                <div class="col-lg-8">
                    <div class="p-4">
                        <h2 class="mb-4">
                            <i class="fas fa-shopping-cart me-2"></i>ກະຕ່າຊື້ສິນຄ້າ
                        </h2>

                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($_GET['success']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($cart_items)): ?>
                            <div class="empty-cart">
                                <i class="fas fa-shopping-cart"></i>
                                <h3>ກະຕ່າຂອງທ່ານຫວ່າງເປົ່າ</h3>
                                <p class="text-muted">ເພີ່ມສິນຄ້າລົງໃນກະຕ່າເພື່ອເລີ່ມຕົ້ນ!</p>
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-shopping-bag me-2"></i>ຊື້ສິນຄ້າຕໍ່
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <?php if ($item['image_url']): ?>
                                                <img src="uploads/products/<?php echo htmlspecialchars($item['image_url']); ?>"
                                                    class="product-image shadow-sm" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            <?php else: ?>
                                                <div class="product-image bg-light d-flex align-items-center justify-content-center shadow-sm">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-md-4">
                                            <h5 class="mb-2 fw-bold"><?php echo htmlspecialchars($item['name']); ?></h5>
                                            <div class="d-flex align-items-center mb-1">
                                                <span class="badge bg-light text-dark me-2">
                                                    <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($item['category_name']); ?>
                                                </span>
                                                <small class="text-muted">
                                                    <i class="fas fa-barcode me-1"></i><?php echo htmlspecialchars($item['sku'] ?? 'ບໍ່ມີ'); ?>
                                                </small>
                                            </div>
                                        </div>

                                        <div class="col-md-2 text-center">
                                            <div class="text-primary fw-bold mb-1"><?php echo formatCurrency($item['price']); ?></div>
                                            <small class="text-muted">ລາຄາຕໍ່ຫົວໜ່ວຍ</small>
                                        </div>

                                        <div class="col-md-2">
                                            <form method="POST" class="quantity-form">
                                                <input type="hidden" name="action" value="update_quantity">
                                                <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                                <div class="input-group input-group-sm">
                                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>"
                                                        min="1" max="<?php echo $item['stock_quantity']; ?>"
                                                        class="form-control quantity-input text-center"
                                                        onchange="this.form.submit()">
                                                </div>
                                            </form>
                                            <small class="text-muted d-block text-center mt-1">
                                                <i class="fas fa-box me-1"></i><?php echo $item['stock_quantity']; ?> ໃນສະຕັອກ
                                            </small>
                                        </div>

                                        <div class="col-md-2">
                                            <div class="d-flex flex-column align-items-end">
                                                <div class="h5 mb-2 text-success fw-bold">
                                                    <?php echo formatCurrency($item['price'] * $item['quantity']); ?>
                                                </div>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="remove_item">
                                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('ທ່ານແນ່ໃຈບໍວ່າຕ້ອງການລຶບສິນຄ້ານີ້ອອກຈາກກະຕ່າ?')">
                                                        <i class="fas fa-trash-alt me-1"></i>ລຶບ
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="summary-card">
                        <h4 class="mb-3">ສະຫຼຸບຄໍາສັ່ງຊື້</h4>

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

                        <?php if (!empty($cart_items)): ?>
                            <a href="checkout.php" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-credit-card me-2"></i>ສືບຕໍ່ການຊື້
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-arrow-left me-2"></i>ຊື້ສິນຄ້າຕໍ່
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>
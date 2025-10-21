<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header('Location: products.php');
    exit();
}

// Get product details
$product = getProductById($db, $product_id);

if (!$product) {
    header('Location: products.php');
    exit();
}

// Get product reviews
$reviews = getProductReviews($db, $product_id, 10);
$rating_stats = getAverageRating($db, $product_id);

// Handle review submission
$review_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['customer_id'])) {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? sanitizeInput($_POST['comment']) : '';
    
    if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
        try {
            $query = "INSERT INTO reviews (customer_id, product_id, rating, comment, status) 
                      VALUES (:customer_id, :product_id, :rating, :comment, 'pending')";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':customer_id', $_SESSION['customer_id'], PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
            $stmt->bindParam(':comment', $comment);
            
            if ($stmt->execute()) {
                $review_message = 'success';
                // Refresh reviews
                $reviews = getProductReviews($db, $product_id, 10);
                $rating_stats = getAverageRating($db, $product_id);
            } else {
                $review_message = 'error';
            }
        } catch(PDOException $e) {
            $review_message = 'error';
        }
    } else {
        $review_message = 'invalid';
    }
}
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - ລະບົບຂາຍສິນຄ້າ IT</title>
    
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
        
        .product-image {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .product-image img {
            width: 100%;
            height: 400px;
            object-fit: cover;
        }
        
        .product-image i {
            width: 100%;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: #dee2e6;
            background: #f8f9fa;
        }
        
        .rating-stars {
            color: #ffc107;
            font-size: 1.2rem;
        }
        
        .review-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .review-author {
            font-weight: 600;
            color: #495057;
        }
        
        .review-date {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .review-rating {
            color: #ffc107;
        }
        
        .review-comment {
            color: #495057;
            line-height: 1.6;
        }
        
        .stock-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
        }
        
        .stock-high {
            background: #d4edda;
            color: #155724;
        }
        
        .stock-medium {
            background: #fff3cd;
            color: #856404;
        }
        
        .stock-low {
            background: #f8d7da;
            color: #721c24;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quantity-btn {
            width: 40px;
            height: 40px;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .quantity-btn:hover {
            background: #e9ecef;
        }
        
        .quantity-input {
            width: 60px;
            text-align: center;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 0.5rem;
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
            <div class="p-4">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">ໜ້າຫຼັກ</a></li>
                        <li class="breadcrumb-item"><a href="products.php">ສິນຄ້າ</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
                    </ol>
                </nav>

                <div class="row">
                    <!-- Product Image -->
                    <div class="col-lg-6 mb-4">
                        <div class="product-image">
                            <?php if ($product['image_url']): ?>
                                <img src="uploads/products/<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <i class="fas fa-laptop"></i>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Product Details -->
                    <div class="col-lg-6">
                        <h1 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
                        
                        <!-- Rating Display -->
                        <div class="mb-3">
                            <div class="rating-stars">
                                <?php
                                $avg_rating = $rating_stats['avg_rating'] ? round($rating_stats['avg_rating'], 1) : 0;
                                $total_reviews = $rating_stats['total_reviews'];
                                
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $avg_rating) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i - $avg_rating < 1) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                                <span class="ms-2 text-muted">(<?php echo $avg_rating; ?>/5 - <?php echo $total_reviews; ?> ຄຳຄິດເຫັນ)</span>
                            </div>
                        </div>

                        <p class="text-muted mb-3"><?php echo htmlspecialchars($product['description']); ?></p>

                        <div class="mb-3">
                            <span class="badge bg-secondary me-2"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            <?php if ($product['stock_quantity'] > 0): ?>
                                <span class="stock-badge <?php echo $product['stock_quantity'] > 10 ? 'stock-high' : ($product['stock_quantity'] > 5 ? 'stock-medium' : 'stock-low'); ?>">
                                    ສິນຄ້າໃນສິນຄ້າ: <?php echo $product['stock_quantity']; ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger">ໝົດສິນຄ້າ</span>
                            <?php endif; ?>
                        </div>

                        <div class="h2 text-primary mb-4"><?php echo formatCurrency($product['price']); ?></div>

                        <?php if ($product['stock_quantity'] > 0): ?>
                            <!-- Add to Cart Section -->
                            <div class="mb-4">
                                <div class="quantity-control mb-3">
                                    <button class="quantity-btn" onclick="changeQuantity(-1)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" class="quantity-input" id="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                                    <button class="quantity-btn" onclick="changeQuantity(1)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                
                                <button class="btn btn-primary btn-lg w-100" onclick="addToCart(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-cart-plus me-2"></i>ເພີ່ມກະຕ່າ
                                </button>
                            </div>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-lg w-100" disabled>
                                <i class="fas fa-times me-2"></i>ໝົດສິນຄ້າ
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Reviews Section -->
                <div class="mt-5">
                    <h3 class="mb-4">
                        <i class="fas fa-star me-2"></i>ຄຳຄິດເຫັນ (<?php echo $total_reviews; ?>)
                    </h3>

                    <!-- Add Review Form -->
                    <?php if (isset($_SESSION['customer_id'])): ?>
                        <?php 
                        $can_review = hasCustomerPurchasedProduct($db, $_SESSION['customer_id'], $product_id);
                        $has_reviewed = hasCustomerReviewedProduct($db, $_SESSION['customer_id'], $product_id);
                        ?>
                        
                        <?php if ($can_review && !$has_reviewed): ?>
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">ເພີ່ມຄຳຄິດເຫັນ</h5>
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">ຄະແນນ:</label>
                                            <div class="rating-input">
                                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                                    <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                                                    <label for="star<?php echo $i; ?>">
                                                        <i class="far fa-star"></i>
                                                    </label>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="comment" class="form-label">ຄຳຄິດເຫັນ:</label>
                                            <textarea class="form-control" id="comment" name="comment" rows="3" required 
                                                      placeholder="ຂຽນຄຳຄິດເຫັນຂອງທ່ານ..."></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane me-2"></i>ສົ່ງຄຳຄິດເຫັນ
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php elseif ($has_reviewed): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                ທ່ານໄດ້ຂຽນຄຳຄິດເຫັນສິນຄ້ານີ້ແລ້ວ
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                ທ່ານຕ້ອງຊື້ສິນຄ້ານີ້ກ່ອນຈຶ່ງຈະສາມາດຂຽນຄຳຄິດເຫັນໄດ້
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            ກະລຸນາ <a href="login.php">ເຂົ້າສູ່ລະບົບ</a> ເພື່ອເພີ່ມຄຳຄິດເຫັນ
                        </div>
                    <?php endif; ?>

                    <!-- Reviews List -->
                    <?php if (empty($reviews)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <p class="text-muted">ຍັງບໍ່ມີຄຳຄິດເຫັນ</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <div>
                                        <span class="review-author"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></span>
                                        <span class="review-date ms-2"><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></span>
                                    </div>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="review-comment">
                                    <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        function changeQuantity(delta) {
            const input = document.getElementById('quantity');
            const newValue = parseInt(input.value) + delta;
            const max = parseInt(input.getAttribute('max'));
            
            if (newValue >= 1 && newValue <= max) {
                input.value = newValue;
            }
        }

        function addToCart(productId) {
            <?php if (isset($_SESSION['customer_id'])): ?>
                const quantity = parseInt(document.getElementById('quantity').value);
                
                fetch('ajax/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: quantity
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'ສໍາເລັດ!',
                            text: 'ເພີ່ມສິນຄ້າລົງໃນກະຕ່າແລ້ວ',
                            confirmButtonText: 'ຕົກລົງ'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'ຜິດພາດ!',
                            text: data.message,
                            confirmButtonText: 'ຕົກລົງ'
                        });
                    }
                });
            <?php else: ?>
                Swal.fire({
                    icon: 'warning',
                    title: 'ກະລຸນາເຂົ້າສູ່ລະບົບ',
                    text: 'ທ່ານຕ້ອງເຂົ້າສູ່ລະບົບກ່ອນຈຶ່ງຈະສາມາດເພີ່ມສິນຄ້າລົງໃນກະຕ່າໄດ້',
                    confirmButtonText: 'ເຂົ້າສູ່ລະບົບ',
                    showCancelButton: true,
                    cancelButtonText: 'ຍົກເລີກ'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'login.php';
                    }
                });
            <?php endif; ?>
        }

        // Rating stars interaction
        document.addEventListener('DOMContentLoaded', function() {
            const ratingInputs = document.querySelectorAll('.rating-input input');
            const ratingLabels = document.querySelectorAll('.rating-input label i');

            ratingInputs.forEach((input, index) => {
                input.addEventListener('change', function() {
                    const rating = parseInt(this.value);
                    
                    ratingLabels.forEach((label, labelIndex) => {
                        if (labelIndex < rating) {
                            label.className = 'fas fa-star';
                        } else {
                            label.className = 'far fa-star';
                        }
                    });
                });
            });
        });

        // Show review submission message
        <?php if ($review_message === 'success'): ?>
            Swal.fire({
                icon: 'success',
                title: 'ສໍາເລັດ!',
                text: 'ຄຳຄິດເຫັນຂອງທ່ານຖືກສົ່ງແລ້ວ ກະລຸນາລໍຖ້າການອະນຸຍາດ',
                confirmButtonText: 'ຕົກລົງ'
            });
        <?php elseif ($review_message === 'error'): ?>
            Swal.fire({
                icon: 'error',
                title: 'ຜິດພາດ!',
                text: 'ບໍ່ສາມາດສົ່ງຄຳຄິດເຫັນໄດ້',
                confirmButtonText: 'ຕົກລົງ'
            });
        <?php elseif ($review_message === 'invalid'): ?>
            Swal.fire({
                icon: 'warning',
                title: 'ກະລຸນາກອບຂໍ້ມູນ',
                text: 'ກະລຸນາກອບຄະແນນແລະຄຳຄິດເຫັນ',
                confirmButtonText: 'ຕົກລົງ'
            });
        <?php endif; ?>
    </script>

    <style>
        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            gap: 0.25rem;
        }
        
        .rating-input input {
            display: none;
        }
        
        .rating-input label {
            cursor: pointer;
            font-size: 1.5rem;
            color: #dee2e6;
        }
        
        .rating-input label:hover,
        .rating-input label:hover ~ label,
        .rating-input input:checked ~ label {
            color: #ffc107;
        }
    </style>
</body>
</html> 
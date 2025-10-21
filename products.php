<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'newest';

// Get categories for filter
$categories = getCategories($db);

// Build query based on filters
$where_conditions = ["p.status = 'active'"];
$params = [];

if ($category_id > 0) {
    $where_conditions[] = "p.category_id = :category_id";
    $params[':category_id'] = $category_id;
}

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE :search OR p.description LIKE :search OR p.sku LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_clause = implode(' AND ', $where_conditions);

// Build order by clause
$order_by = match ($sort) {
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'name' => 'p.name ASC',
    default => 'p.created_at DESC'
};

try {
    $query = "SELECT p.*, pc.name as category_name 
              FROM products p 
              LEFT JOIN product_categories pc ON p.category_id = pc.id 
              WHERE $where_clause 
              ORDER BY $order_by";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error loading products: " . $e->getMessage();
    $products = [];
}
?>

<!DOCTYPE html>
<html lang="lo">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ສິນຄ້າ - ລະບົບຂາຍສິນຄ້າ IT</title>

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

        .filter-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.15);
        }

        .product-image {
            height: 220px;
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #6c757d;
            position: relative;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 10px;
            transition: transform 0.3s ease;
        }

        .product-image img:hover {
            transform: scale(1.05);
        }

        .product-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.95);
            padding: 8px 15px;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(5px);
        }

        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #5a6fd8, #6a4190);
            transform: translateY(-2px);
        }

        .search-box {
            border-radius: 25px;
            border: 2px solid #e9ecef;
            padding: 12px 20px;
        }

        .search-box:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .category-filter {
            border-radius: 15px;
            border: 2px solid #e9ecef;
        }

        .category-filter:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .sort-select {
            border-radius: 15px;
            border: 2px solid #e9ecef;
        }

        .sort-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .stock-badge {
            font-size: 0.85rem;
            padding: 5px 12px;
            border-radius: 15px;
            display: inline-flex;
            align-items: center;
            font-weight: 500;
        }

        .stock-high {
            background: rgba(25, 135, 84, 0.1);
            color: #198754;
        }

        .stock-medium {
            background: rgba(255, 193, 7, 0.1);
            color: #997404;
        }

        .stock-low {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .card-text {
            line-height: 1.5;
        }

        .button-group {
            display: flex;
            align-items: center;
        }

        .button-group .btn {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .button-group .btn-outline-primary {
            padding: 0.5rem;
            width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        .empty-state i {
            font-size: 4rem;
            color: #adb5bd;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .rating-stars {
            color: #ffc107;
            display: inline-flex;
            align-items: center;
        }

        .rating-stars.small {
            font-size: 0.9rem;
        }

        .rating-stars i {
            margin-right: 2px;
        }

        .description-truncate {
            height: 48px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            box-orient: vertical;
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
                <a class="nav-link active" href="products.php">
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
                            <li>
                                <hr class="dropdown-divider">
                            </li>
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
                <h2 class="mb-4">
                    <i class="fas fa-box me-2"></i>ສິນຄ້າທັງໝົດ
                </h2>

                <!-- Filter Section -->
                <div class="filter-section">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">
                                <i class="fas fa-search me-2"></i>ຄົ້ນຫາ
                            </label>
                            <input type="text" class="form-control search-box" id="search" name="search"
                                value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="ຄົ້ນຫາສິນຄ້າ...">
                        </div>

                        <div class="col-md-3">
                            <label for="category" class="form-label">
                                <i class="fas fa-tags me-2"></i>ໝວດໝູ່
                            </label>
                            <select class="form-select category-filter" id="category" name="category">
                                <option value="0">ທັງໝົດ</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"
                                        <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="sort" class="form-label">
                                <i class="fas fa-sort me-2"></i>ຈັດຮຽງ
                            </label>
                            <select class="form-select sort-select" id="sort" name="sort">
                                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>ໃໝ່ສຸດ</option>
                                <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>ລາຄາຕ່ຳສຸດ</option>
                                <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>ລາຄາສູງສຸດ</option>
                                <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>ຊື່ A-Z</option>
                            </select>
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-2"></i>ກັ່ນຕອງ
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Products Grid -->
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>ບໍ່ພົບສິນຄ້າ</h3>
                        <p class="text-muted">ບໍ່ມີສິນຄ້າທີ່ກົງກັບການຄົ້ນຫາຂອງທ່ານ</p>
                        <a href="products.php" class="btn btn-primary">
                            <i class="fas fa-refresh me-2"></i>ລຶບການກັ່ນຕອງ
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($products as $product): ?>
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                <div class="product-card">
                                    <div class="product-image">
                                        <?php if ($product['image_url']): ?>
                                            <img src="uploads/products/<?php echo htmlspecialchars($product['image_url']); ?>"
                                                alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-laptop"></i>
                                        <?php endif; ?>

                                        <?php if ($product['stock_quantity'] <= 0): ?>
                                            <div class="product-badge bg-danger text-white">ໝົດສິນຄ້າ</div>
                                        <?php elseif ($product['stock_quantity'] <= 5): ?>
                                            <div class="product-badge bg-warning text-dark">ໃກ້ໝົດ</div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="card-body p-4">
                                        <h5 class="card-title mb-3">
                                            <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark fw-bold">
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </a>
                                        </h5>

                                        <div class="d-flex align-items-center mb-3">
                                            <span class="badge bg-primary bg-opacity-10 text-primary me-2">
                                                <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($product['category_name']); ?>
                                            </span>
                                            <?php if ($product['stock_quantity'] > 0): ?>
                                                <span class="stock-badge <?php echo $product['stock_quantity'] > 10 ? 'stock-high' : ($product['stock_quantity'] > 5 ? 'stock-medium' : 'stock-low'); ?>">
                                                    <i class="fas fa-box me-1"></i><?php echo $product['stock_quantity']; ?> ຊິ້ນ
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <p class="card-text text-muted small mb-3 description-truncate">
                                            <?php echo htmlspecialchars($product['description']); ?>
                                        </p>

                                        <!-- Rating Display -->
                                        <?php
                                        $rating_stats = getAverageRating($db, $product['id']);
                                        $avg_rating = $rating_stats['avg_rating'] ? round($rating_stats['avg_rating'], 1) : 0;
                                        $total_reviews = $rating_stats['total_reviews'];
                                        ?>
                                        <div class="mb-3">
                                            <div class="rating-stars small">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="<?php echo $i <= $avg_rating ? 'fas' : 'far'; ?> fa-star"></i>
                                                <?php endfor; ?>
                                                <span class="ms-1 text-muted small"><?php echo $avg_rating; ?>/5 (<?php echo $total_reviews; ?> ຄໍາເຫັນ)</span>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="price-section">
                                                <span class="h4 text-primary mb-0 fw-bold"><?php echo formatCurrency($product['price']); ?></span>
                                            </div>
                                            <div class="button-group">
                                                <?php if ($product['stock_quantity'] > 0): ?>
                                                    <button class="btn btn-primary btn-sm rounded-pill" onclick="addToCart(<?php echo $product['id']; ?>)">
                                                        <i class="fas fa-cart-plus me-1"></i>ເພີ່ມກະຕ່າ
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary btn-sm rounded-pill" disabled>
                                                        <i class="fas fa-times me-1"></i>ໝົດສິນຄ້າ
                                                    </button>
                                                <?php endif; ?>
                                                <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill ms-2">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Results Summary -->
                    <div class="text-center mt-4">
                        <p class="text-muted">
                            <i class="fas fa-info-circle me-2"></i>
                            ພົບສິນຄ້າທັງໝົດ <?php echo count($products); ?> ລາຍການ
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function addToCart(productId) {
            <?php if (isset($_SESSION['customer_id'])): ?>
                fetch('ajax/add_to_cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            product_id: productId,
                            quantity: 1
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
    </script>
</body>

</html>
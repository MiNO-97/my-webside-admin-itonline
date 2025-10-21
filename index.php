<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();

// Get featured products
$featured_products = getFeaturedProducts($db);
$categories = getCategories($db);
?>
<!DOCTYPE html>
<html lang="lo">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS IT Online - ລະບົບສັ່ງຊື້ອຸປະກອນ IT ອອນລາຍ</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Google Fonts - Noto Sans Lao -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/styles.css" rel="stylesheet">
</head>

<body>
    <!-- Loading Animation -->
    <div class="loading">
        <div class="loading-spinner"></div>
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-laptop me-2"></i>POS IT Online
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Search Form -->
                <form class="d-flex me-auto ms-3" action="products.php" method="GET">
                    <div class="input-group">
                        <input type="search" class="form-control" placeholder="ຄົ້ນຫາສິນຄ້າ..." name="search" aria-label="Search">
                        <button class="btn btn-outline-light" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                <ul class="navbar-nav ms-3">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="index.php">
                            <i class="fas fa-home me-1"></i> ໜ້າຫຼັກ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : ''; ?>" href="products.php">
                            <i class="fas fa-laptop me-1"></i> ສິນຄ້າ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'about.php' ? 'active' : ''; ?>" href="about.php">
                            <i class="fas fa-info-circle me-1"></i> ກ່ຽວກັບ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'contact.php' ? 'active' : ''; ?>" href="contact.php">
                            <i class="fas fa-envelope me-1"></i> ຕິດຕໍ່
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['customer_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">
                                <i class="fas fa-shopping-cart me-1"></i> ກະຕ່າ
                                <span class="badge bg-danger"><?php echo getCartItemCount($db, $_SESSION['customer_id']); ?></span>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i> <?php echo $_SESSION['customer_name']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="profile.php">
                                        <i class="fas fa-user-circle me-2"></i> ໂປຣໄຟລ໌
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="orders.php">
                                        <i class="fas fa-shopping-bag me-2"></i> ຄໍາສັ່ງຊື້
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger" href="logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i> ອອກຈາກລະບົບ
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt me-1"></i> ເຂົ້າສູ່ລະບົບ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">
                                <i class="fas fa-user-plus me-1"></i> ລົງທະບຽນ
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Carousel -->
    <div class="container-fluid px-0">
        <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
            </div>
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <div class="hero-slide d-flex align-items-center justify-content-center text-center">
                        <div class="hero-content">
                            <h1 class="display-4 fw-bold mb-4 text-white">ຍິນດີຕ້ອນຮັບສູ່ POS IT Online</h1>
                            <p class="lead mb-4 text-white">ລະບົບສັ່ງຊື້ອຸປະກອນ IT ທີ່ທັນສະໄໝ ແລະ ສະດວກສະບາຍ</p>
                            <a href="products.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-shopping-bag me-2"></i>ຊື້ສິນຄ້າຕອນນີ້
                            </a>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="hero-slide d-flex align-items-center justify-content-center text-center">
                        <div class="hero-content">
                            <h2 class="display-4 fw-bold mb-4 text-white">ໂປຣໂມຊັ່ນພິເສດ</h2>
                            <p class="lead mb-4 text-white">ລົດລາຄາສູງສຸດເຖິງ 50% ສຳລັບສິນຄ້າທີ່ເຂົ້າຮ່ວມລາຍການ</p>
                            <a href="products.php?sale=1" class="btn btn-danger btn-lg">
                                <i class="fas fa-tag me-2"></i>ເບິ່ງສິນຄ້າໂປຣໂມຊັ່ນ
                            </a>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="hero-slide d-flex align-items-center justify-content-center text-center">
                        <div class="hero-content">
                            <h2 class="display-4 fw-bold mb-4 text-white">ສິນຄ້າມາໃໝ່</h2>
                            <p class="lead mb-4 text-white">ອັບເດດສິນຄ້າໃໝ່ປະຈຳອາທິດ</p>
                            <a href="products.php?new=1" class="btn btn-success btn-lg">
                                <i class="fas fa-box-open me-2"></i>ເບິ່ງສິນຄ້າມາໃໝ່
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div>

    <div class="container mt-5">
        <!-- Categories Section -->
        <section class="mb-5">
            <h2 class="text-center text-white mb-4">ໝວດໝູ່ສິນຄ້າ</h2>
            <div class="row">
                <?php foreach ($categories as $category): ?>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <div class="category-card">
                            <i class="fas fa-laptop fa-3x text-primary mb-3"></i>
                            <h5><?php echo htmlspecialchars($category['name']); ?></h5>
                            <p class="text-muted"><?php echo htmlspecialchars($category['description']); ?></p>
                            <a href="products.php?category=<?php echo $category['id']; ?>" class="btn btn-outline-primary">
                                ເບິ່ງສິນຄ້າ
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Featured Products -->
        <section class="mb-5">
            <h2 class="text-center text-white mb-4">ສິນຄ້າແນະນໍາ</h2>
            <div class="row">
                <?php foreach ($featured_products as $product): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="product-card">
                            <div class="product-badges">
                                <?php if ($product['stock_quantity'] <= 5 && $product['stock_quantity'] > 0): ?>
                                    <span class="badge bg-warning">ເຫຼືອ <?php echo $product['stock_quantity']; ?> ຊິ້ນ</span>
                                <?php elseif ($product['stock_quantity'] == 0): ?>
                                    <span class="badge bg-danger">ໝົດແລ້ວ</span>
                                <?php endif; ?>
                                <?php if (isset($product['is_new']) && $product['is_new']): ?>
                                    <span class="badge bg-success">ໃໝ່</span>
                                <?php endif; ?>
                            </div>
                            <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="text-decoration-none">
                                <div class="product-image">
                                    <?php if (!empty($product['image_url'])): ?>
                                        <img src="uploads/products/<?php echo htmlspecialchars($product['image_url']); ?>"
                                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                                            class="img-fluid product-img"
                                            onerror="this.onerror=null; this.src='assets/images/no-image.png';">
                                    <?php else: ?>
                                        <i class="fas fa-laptop"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title text-truncate"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <p class="card-text text-muted description-truncate"><?php echo htmlspecialchars($product['description']); ?></p>
                                    <div class="product-info">
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-box me-1"></i> <?php echo htmlspecialchars($product['category_name']); ?>
                                            </small>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="h5 text-primary mb-0"><?php echo number_format($product['price']); ?> ກີບ</span>
                                                <?php if (isset($product['old_price']) && $product['old_price'] > $product['price']): ?>
                                                    <small class="text-muted text-decoration-line-through ms-2">
                                                        <?php echo number_format($product['old_price']); ?> ກີບ
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($product['stock_quantity'] > 0): ?>
                                                <button class="btn btn-primary btn-sm" onclick="event.preventDefault(); addToCart(<?php echo $product['id']; ?>)">
                                                    <i class="fas fa-cart-plus"></i> ເພີ່ມກະຕ່າ
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-secondary btn-sm" disabled>
                                                    <i class="fas fa-times"></i> ໝົດແລ້ວ
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="footer">
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
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>

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
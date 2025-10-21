<?php
function isCurrentPage($page)
{
    return basename($_SERVER['PHP_SELF']) === $page ? 'active' : '';
}
?>

<!-- Sidebar Toggle Button -->
<button class="sidebar-toggle">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-header text-center mb-4">
        <h4 class="mb-3">POS IT Online</h4>
        <div class="user-info mb-3">
            <?php if (isset($_SESSION['customer_id'])): ?>
                <div class="user-avatar mb-2">
                    <img src="assets/images/default-avatar.png" alt="User Avatar"
                        class="rounded-circle" style="width: 64px; height: 64px; object-fit: cover;">
                </div>
                <h6 class="mb-1"><?php echo $_SESSION['customer_name']; ?></h6>
                <p class="small text-muted mb-0">ສະມາຊິກ</p>
            <?php else: ?>
                <div class="guest-info">
                    <i class="fas fa-user-circle fa-3x mb-2"></i>
                    <h6>ຜູ້ໃຊ້ທົ່ວໄປ</h6>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <nav class="sidebar-nav mb-4">
        <div class="nav-section mb-3">
            <h6 class="nav-section-title text-muted mb-2">ເມນູຫຼັກ</h6>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo isCurrentPage('index.php'); ?>" href="index.php">
                        <i class="fas fa-home fa-fw me-2"></i> ໜ້າຫຼັກ
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isCurrentPage('products.php'); ?>" href="products.php">
                        <i class="fas fa-laptop fa-fw me-2"></i> ສິນຄ້າ
                    </a>
                </li>
            </ul>
        </div>

        <?php if (isset($_SESSION['customer_id'])): ?>
            <div class="nav-section mb-3">
                <h6 class="nav-section-title text-muted mb-2">ບັນຊີຂອງຂ້ອຍ</h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo isCurrentPage('cart.php'); ?>" href="cart.php">
                            <i class="fas fa-shopping-cart fa-fw me-2"></i>
                            ກະຕ່າ
                            <span class="badge bg-primary ms-2"><?php echo getCartItemCount($db, $_SESSION['customer_id']); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isCurrentPage('orders.php'); ?>" href="orders.php">
                            <i class="fas fa-shopping-bag fa-fw me-2"></i> ຄໍາສັ່ງຊື້
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isCurrentPage('profile.php'); ?>" href="profile.php">
                            <i class="fas fa-user fa-fw me-2"></i> ໂປຣໄຟລ໌
                        </a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>

        <div class="nav-section">
            <h6 class="nav-section-title text-muted mb-2">ອື່ນໆ</h6>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo isCurrentPage('about.php'); ?>" href="about.php">
                        <i class="fas fa-info-circle fa-fw me-2"></i> ກ່ຽວກັບ
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isCurrentPage('contact.php'); ?>" href="contact.php">
                        <i class="fas fa-envelope fa-fw me-2"></i> ຕິດຕໍ່
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="sidebar-footer mt-auto">
        <?php if (isset($_SESSION['customer_id'])): ?>
            <a href="logout.php" class="btn btn-danger w-100">
                <i class="fas fa-sign-out-alt me-2"></i> ອອກຈາກລະບົບ
            </a>
        <?php else: ?>
            <div class="d-grid gap-2">
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt me-2"></i> ເຂົ້າສູ່ລະບົບ
                </a>
                <a href="register.php" class="btn btn-outline-primary">
                    <i class="fas fa-user-plus me-2"></i> ລົງທະບຽນ
                </a>
            </div>
        <?php endif; ?>
    </div>
</aside>

<!-- Sidebar -->
<div class="sidebar">
    <div class="text-center mb-4">
        <h4 class="mb-3">POS IT Online</h4>
        <div class="user-info mb-3">
            <?php if (isset($_SESSION['customer_id'])): ?>
                <img src="assets/images/avatar.png" alt="User Avatar" class="rounded-circle mb-2" style="width: 64px; height: 64px;">
                <h6><?php echo $_SESSION['customer_name']; ?></h6>
            <?php else: ?>
                <div class="guest-info">
                    <i class="fas fa-user-circle fa-3x mb-2"></i>
                    <h6>ຜູ້ໃຊ້ທົ່ວໄປ</h6>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo isCurrentPage('index.php'); ?>" href="index.php">
                    <i class="fas fa-home"></i> ໜ້າຫຼັກ
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo isCurrentPage('products.php'); ?>" href="products.php">
                    <i class="fas fa-laptop"></i> ສິນຄ້າ
                </a>
            </li>
            <?php if (isset($_SESSION['customer_id'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo isCurrentPage('cart.php'); ?>" href="cart.php">
                        <i class="fas fa-shopping-cart"></i> ກະຕ່າ
                        <span class="badge bg-primary"><?php echo getCartItemCount($db, $_SESSION['customer_id']); ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isCurrentPage('orders.php'); ?>" href="orders.php">
                        <i class="fas fa-shopping-bag"></i> ຄໍາສັ່ງຊື້
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isCurrentPage('profile.php'); ?>" href="profile.php">
                        <i class="fas fa-user"></i> ໂປຣໄຟລ໌
                    </a>
                </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link <?php echo isCurrentPage('about.php'); ?>" href="about.php">
                    <i class="fas fa-info-circle"></i> ກ່ຽວກັບ
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo isCurrentPage('contact.php'); ?>" href="contact.php">
                    <i class="fas fa-envelope"></i> ຕິດຕໍ່
                </a>
            </li>
        </ul>
    </nav>

    <?php if (isset($_SESSION['customer_id'])): ?>
        <div class="sidebar-footer mt-auto pt-3">
            <a href="logout.php" class="btn btn-danger w-100">
                <i class="fas fa-sign-out-alt"></i> ອອກຈາກລະບົບ
            </a>
        </div>
    <?php else: ?>
        <div class="sidebar-footer mt-auto pt-3">
            <a href="login.php" class="btn btn-primary w-100 mb-2">
                <i class="fas fa-sign-in-alt"></i> ເຂົ້າສູ່ລະບົບ
            </a>
            <a href="register.php" class="btn btn-outline-primary w-100">
                <i class="fas fa-user-plus"></i> ລົງທະບຽນ
            </a>
        </div>
    <?php endif; ?>
</div>
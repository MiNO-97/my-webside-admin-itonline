<?php
// Get current page name for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="col-md-3 col-lg-2 sidebar p-0">
    <div class="p-3">
        <h4 class="text-white mb-4">ລະບົບຂາຍສິນຄ້າ IT</h4>
        <nav class="nav flex-column">
            <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i> ໜ້າຫຼັກ
            </a>
            <a class="nav-link <?php echo $current_page === 'products.php' ? 'active' : ''; ?>" href="products.php">
                <i class="fas fa-box me-2"></i> ສິນຄ້າ
            </a>
            <a class="nav-link <?php echo $current_page === 'categories.php' ? 'active' : ''; ?>" href="categories.php">
                <i class="fas fa-tags me-2"></i> ໝວດໝູ່
            </a>
            <a class="nav-link <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>" href="orders.php">
                <i class="fas fa-shopping-cart me-2"></i> ຄໍາສັ່ງຊື້
            </a>
            <a class="nav-link <?php echo $current_page === 'reviews.php' ? 'active' : ''; ?>" href="reviews.php">
                <i class="fas fa-star me-2"></i> ຄຳຄິດເຫັນ
            </a>
            <a class="nav-link <?php echo $current_page === 'purchase_orders.php' ? 'active' : ''; ?>" href="purchase_orders.php">
                <i class="fas fa-truck me-2"></i> ການຈັດຊື້ສິນຄ້າ
            </a>
            <a class="nav-link <?php echo $current_page === 'customers.php' ? 'active' : ''; ?>" href="customers.php">
                <i class="fas fa-users me-2"></i> ລູກຄ້າ
            </a>
            <a class="nav-link <?php echo $current_page === 'suppliers.php' ? 'active' : ''; ?>" href="suppliers.php">
                <i class="fas fa-users me-2"></i> ຜູ້ສະໜອງ
            </a>
            <a class="nav-link <?php echo $current_page === 'employees.php' ? 'active' : ''; ?>" href="employees.php">
                <i class="fas fa-user-tie me-2"></i> ພະນັກງານ
            </a>
            <a class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                <i class="fas fa-chart-bar me-2"></i> ລາຍງານ
            </a>
            <!-- <a class="nav-link <?php echo $current_page === 'expenses.php' ? 'active' : ''; ?>" href="expenses.php">
                <i class="fas fa-money-bill-alt me-2"></i> ຄ່າໃຊ້ຈ່າຍ
            </a> -->
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt me-2"></i> ອອກຈາກລະບົບ
            </a>
        </nav>
    </div>
</div>
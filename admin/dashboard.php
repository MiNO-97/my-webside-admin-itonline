<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if(!isset($_SESSION['employee_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get statistics
$monthly_stats = getSalesStatistics($db, 'month');
$weekly_stats = getSalesStatistics($db, 'week');
$top_products = getTopSellingProducts($db, 5);

// Get recent orders
$recent_orders = [];
try {
    $query = "SELECT o.*, c.first_name, c.last_name, c.email 
              FROM orders o 
              LEFT JOIN customers c ON o.customer_id = c.id 
              ORDER BY o.created_at DESC 
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Handle error silently
}

// Get low stock products
$low_stock_products = [];
try {
    $query = "SELECT * FROM products WHERE stock_quantity <= 5 AND status = 'active' ORDER BY stock_quantity ASC LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $low_stock_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Handle error silently
}
?>
<?php $page_title = 'ໜ້າຫຼັກຜູ້ບໍລິຫານ'; ?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <!-- Welcome Section -->
                    <div class="welcome-section">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2>ຍິນດີຕ້ອນຮັບ, <?php echo $_SESSION['employee_name']; ?>!</h2>
                                <p class="mb-0">ນີ້ແມ່ນການສະຫຼຸບລະບົບຂອງທ່ານ</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <i class="fas fa-chart-line fa-3x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-primary-gradient me-3">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-1"><?php echo number_format($monthly_stats['total_orders']); ?></h4>
                                        <p class="text-muted mb-0">ຄໍາສັ່ງຊື້ປະຈໍາເດືອນ</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-success-gradient me-3">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-1"><?php echo formatCurrency($monthly_stats['total_sales']); ?></h4>
                                        <p class="text-muted mb-0">ລາຍໄດ້ປະຈໍາເດືອນ</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-warning-gradient me-3">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-1"><?php echo number_format($monthly_stats['total_orders']); ?></h4>
                                        <p class="text-muted mb-0">ລູກຄ້າໃໝ່</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-info-gradient me-3">
                                        <i class="fas fa-box"></i>
                                    </div>
                                    <div>
                                        <h4 class="mb-1"><?php echo count($low_stock_products); ?></h4>
                                        <p class="text-muted mb-0">ສິນຄ້າໃກ້ໝົດ</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="chart-container">
                                <h5 class="mb-3">ຍອດຂາຍປະຈໍາອາທິດ</h5>
                                <canvas id="salesChart" height="100"></canvas>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="chart-container">
                                <h5 class="mb-3">ສິນຄ້າຂາຍດີ</h5>
                                <canvas id="productsChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tables Row -->
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="table-card">
                                <h5 class="mb-3">ຄໍາສັ່ງຊື້ຫຼ້າສຸດ</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ລະຫັດ</th>
                                                <th>ລູກຄ້າ</th>
                                                <th>ຈໍານວນ</th>
                                                <th>ສະຖານະ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($recent_orders as $order): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                                <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                                <td><?php echo formatCurrency($order['total_amount']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $order['status'] === 'pending' ? 'warning' : ($order['status'] === 'delivered' ? 'success' : 'primary'); ?>">
                                                        <?php echo htmlspecialchars($order['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="table-card">
                                <h5 class="mb-3">ສິນຄ້າໃກ້ໝົດ</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ສິນຄ້າ</th>
                                                <th>ລາຄາ</th>
                                                <th>ສິນຄ້າໃນສິນຄ້າ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($low_stock_products as $product): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                <td><?php echo formatCurrency($product['price']); ?></td>
                                                <td>
                                                    <span class="badge bg-danger"><?php echo $product['stock_quantity']; ?></span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
$custom_scripts = '
<script>
    // Sales Chart
    const salesCtx = document.getElementById("salesChart").getContext("2d");
    new Chart(salesCtx, {
        type: "line",
        data: {
            labels: ["ຈັນ", "ອັງຄານ", "ພຸດ", "ພະຫັດ", "ສຸກ", "ເສົາ", "ອາທິດ"],
            datasets: [{
                label: "ຍອດຂາຍ (ກີບ)",
                data: [1200000, 1900000, 1500000, 2100000, 1800000, 2500000, 2200000],
                borderColor: "#667eea",
                backgroundColor: "rgba(102, 126, 234, 0.1)",
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: "top",
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Products Chart
    const productsCtx = document.getElementById("productsChart").getContext("2d");
    new Chart(productsCtx, {
        type: "doughnut",
        data: {
            labels: ["Laptop", "Phone", "Router", "Printer"],
            datasets: [{
                data: [35, 25, 20, 20],
                backgroundColor: [
                    "#667eea",
                    "#764ba2",
                    "#f093fb",
                    "#f5576c"
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: "bottom",
                }
            }
        }
    });
    
    // Auto refresh dashboard every 5 minutes
    setInterval(function() {
        location.reload();
    }, 300000);
</script>
';

include 'includes/footer.php';
?> 
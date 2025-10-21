<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isAdmin()) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get date range for filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'sales';

// Get report data based on type
$report_data = [];
$summary_data = [];

switch ($report_type) {
    case 'sales':
        // Sales report data with detailed order information
        $query = "SELECT 
                    o.id as order_id,
                    o.created_at,
                    CONCAT(COALESCE(c.first_name, ''), ' ', COALESCE(c.last_name, ''), ' (', c.status, ')') as customer_name,
                    o.total_amount,
                    o.payment_method,
                    o.status,
                    GROUP_CONCAT(
                        CONCAT(
                            p.name, 
                            ' (', oi.quantity, ' x ', oi.unit_price, ' = ', oi.total_price, ')'
                        ) 
                        SEPARATOR '\n'
                    ) as order_items,
                    COUNT(oi.id) as total_items,
                    SUM(oi.quantity) as total_quantity
                  FROM orders o 
                  LEFT JOIN customers c ON o.customer_id = c.id
                  LEFT JOIN order_items oi ON o.id = oi.order_id
                  LEFT JOIN products p ON oi.product_id = p.id
                  WHERE o.status = 'delivered' 
                  AND DATE(o.created_at) BETWEEN :start_date AND :end_date
                  GROUP BY o.id, o.created_at, c.first_name, c.last_name, o.total_amount, o.payment_method, o.status
                  ORDER BY o.created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Summary data
        $summary_query = "SELECT 
                           COUNT(*) as total_orders,
                           SUM(total_amount) as total_sales,
                           AVG(total_amount) as avg_order_value,
                           COUNT(DISTINCT customer_id) as unique_customers
                         FROM orders 
                         WHERE status = 'delivered' 
                         AND DATE(created_at) BETWEEN :start_date AND :end_date";

        $stmt = $db->prepare($summary_query);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        $summary_data = $stmt->fetch(PDO::FETCH_ASSOC);
        break;

    case 'inventory':
        // Inventory report data
        $query = "SELECT 
                    p.name,
                
                    p.stock_quantity,
                    p.price,
                    c.name as category,
                    (p.stock_quantity * p.price) as stock_value
                  FROM products p
                  LEFT JOIN product_categories c ON p.category_id = c.id
                  ORDER BY p.stock_quantity ASC";

        $stmt = $db->prepare($query);
        $stmt->execute();
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Summary data
        $summary_query = "SELECT 
                           COUNT(*) as total_products,
                           SUM(stock_quantity) as total_stock,
                           SUM(stock_quantity * price) as total_stock_value,
                           COUNT(CASE WHEN stock_quantity <= 10 THEN 1 END) as low_stock_items
                         FROM products";

        $stmt = $db->prepare($summary_query);
        $stmt->execute();
        $summary_data = $stmt->fetch(PDO::FETCH_ASSOC);
        break;

    case 'customers':
        // Customer report data
        $query = "SELECT 
                    CONCAT(c.first_name, ' ', c.last_name) as name,
                    c.email,
                    c.phone,
                    COUNT(o.id) as total_orders,
                    SUM(o.total_amount) as total_spent,
                    MAX(o.created_at) as last_order_date,
                    c.status
                  FROM customers c
                  LEFT JOIN orders o ON c.id = o.customer_id AND o.status = 'delivered'
                  GROUP BY c.id
                  ORDER BY total_spent DESC";

        $stmt = $db->prepare($query);
        $stmt->execute();
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Summary data
        $summary_query = "SELECT 
                           COUNT(*) as total_customers,
                           COUNT(DISTINCT o.customer_id) as active_customers,
                           AVG(customer_totals.total_spent) as avg_customer_value
                         FROM customers c
                         LEFT JOIN orders o ON c.id = o.customer_id AND o.status = 'delivered'
                         LEFT JOIN (
                           SELECT customer_id, SUM(total_amount) as total_spent
                           FROM orders 
                           WHERE status = 'delivered'
                           GROUP BY customer_id
                         ) customer_totals ON c.id = customer_totals.customer_id";

        $stmt = $db->prepare($summary_query);
        $stmt->execute();
        $summary_data = $stmt->fetch(PDO::FETCH_ASSOC);
        break;

    case 'products':
        // Product performance report
        $query = "SELECT 
                    p.name,
                    c.name as category,
                    p.price,
                    p.stock_quantity,
                    COUNT(oi.id) as times_ordered,
                    SUM(oi.quantity) as total_quantity_sold,
                    SUM(oi.quantity * oi.unit_price) as total_revenue
                  FROM products p
                  LEFT JOIN product_categories c ON p.category_id = c.id
                  LEFT JOIN order_items oi ON p.id = oi.product_id
                  LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'delivered'
                  GROUP BY p.id
                  ORDER BY total_revenue DESC";

        $stmt = $db->prepare($query);
        $stmt->execute();
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Summary data
        $summary_query = "SELECT 
                           COUNT(*) as total_products,
                           AVG(price) as avg_price,
                           SUM(stock_quantity) as total_stock,
                           COUNT(CASE WHEN stock_quantity = 0 THEN 1 END) as out_of_stock
                         FROM products";

        $stmt = $db->prepare($summary_query);
        $stmt->execute();
        $summary_data = $stmt->fetch(PDO::FETCH_ASSOC);
        break;
}

// Log activity
// logActivity($db, $_SESSION['user_id'], 'viewed_reports', "Viewed $report_type report");
?>

<?php $page_title = 'ລາຍງານ'; ?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="col-md-9 col-lg-10">
    <div class="main-content">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-chart-bar me-2"></i>ລາຍງານ</h2>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary" onclick="exportReport()">
                    <i class="fas fa-download me-2"></i>ສົ່ງອອກ
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="printReport()">
                    <i class="fas fa-print me-2"></i>ພິມ
                </button>
            </div>
        </div>

        <!-- Report Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">ປະເພດລາຍງານ</label>
                        <select class="form-select" name="report_type" onchange="this.form.submit()">
                            <option value="sales" <?php echo $report_type === 'sales' ? 'selected' : ''; ?>>ລາຍງານການຂາຍ</option>
                            <option value="inventory" <?php echo $report_type === 'inventory' ? 'selected' : ''; ?>>ລາຍງານສິນຄ້າມີຢູ່ໃນຄັງ</option>
                            <option value="customers" <?php echo $report_type === 'customers' ? 'selected' : ''; ?>>ລາຍງານລູກຄ້າ</option>
                            <option value="products" <?php echo $report_type === 'products' ? 'selected' : ''; ?>>ລາຍງານສິນຄ້າ</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">ວັນທີເລີ່ມ</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">ວັນທີສິ້ນສຸດ</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block">
                            <i class="fas fa-search me-2"></i>ຄົ້ນຫາ
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <?php if ($report_type === 'sales'): ?>
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">ຄໍາສັ່ງຊື້ທັງໝົດ</h5>
                            <h3><?php echo number_format($summary_data['total_orders'] ?? 0); ?> ລາຍການ</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">ລາຍໄດ້ທັງໝົດ</h5>
                            <h3><?php echo formatCurrency($summary_data['total_sales'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">ຄ່າສະເລ່ຍຕໍ່ຄໍາສັ່ງ</h5>
                            <h3><?php echo formatCurrency($summary_data['avg_order_value'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5 class="card-title">ລູກຄ້າທີ່ຊື້</h5>
                            <h3><?php echo number_format($summary_data['unique_customers'] ?? 0); ?> ໄອດີ</h3>
                        </div>
                    </div>
                <?php elseif ($report_type === 'inventory'): ?>
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">ສິນຄ້າທັງໝົດ</h5>
                                <h3><?php echo number_format($summary_data['total_products'] ?? 0); ?> ລາຍການ</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">ສິນຄ້າມີຢູ່ໃນຄັງ</h5>
                                <h3><?php echo number_format($summary_data['total_stock'] ?? 0); ?> ລາຍການ</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">ມູນຄ່າສິນຄ້າມີຢູ່ໃນຄັງ</h5>
                                <h3><?php echo formatCurrency($summary_data['total_stock_value'] ?? 0); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">ສິນຄ້າຕ່ໍາກວ່າ 10</h5>
                                <h3><?php echo number_format($summary_data['low_stock_items'] ?? 0); ?> ລາຍການ</h3>
                            </div>
                        </div>
                    <?php elseif ($report_type === 'customers'): ?>
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">ຍອດລູກຄ້າຊື້ທັງໝົດ</h5>
                                    <h3><?php echo number_format($summary_data['total_customers'] ?? 0); ?> ລາຍການ</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">ລູກຄ້າທີ່ຊື້</h5>
                                    <h3><?php echo number_format($summary_data['active_customers'] ?? 0); ?> ໄອດີ</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">ຄ່າສະເລ່ຍຕໍ່ລູກຄ້າ</h5>
                                    <h3><?php echo formatCurrency($summary_data['avg_customer_value'] ?? 0); ?></h3>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($report_type === 'products'): ?>
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">ສິນຄ້າທັງໝົດ</h5>
                                    <h3><?php echo number_format($summary_data['total_products'] ?? 0); ?> ລາຍການ</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">ຄ່າສະເລ່ຍ</h5>
                                    <h3><?php echo formatCurrency($summary_data['avg_price'] ?? 0); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">ສິນຄ້າມີຢູ່ໃນຄັງ</h5>
                                    <h3><?php echo number_format($summary_data['total_stock'] ?? 0); ?> ລາຍການ</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">ສິນຄ້າທີ່ຂາດສະຕ໋ອກ</h5>
                                    <h3><?php echo number_format($summary_data['out_of_stock'] ?? 0); ?> ລາຍການ</h3>
                                </div>
                            </div>
                        <?php endif; ?>
                        </div>

                        <!-- Report Data Table -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <?php
                                    switch ($report_type) {
                                        case 'sales':
                                            echo 'ລາຍງານການຂາຍ';
                                            break;
                                        case 'inventory':
                                            echo 'ລາຍງານສິນຄ້າຄົງຄັງ';
                                            break;
                                        case 'customers':
                                            echo 'ລາຍງານລູກຄ້າ';
                                            break;
                                        case 'products':
                                            echo 'ລາຍງານສິນຄ້າ';
                                            break;
                                    }
                                    ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <?php if ($report_type === 'sales'): ?>
                                                <tr>
                                                    <th>ລະຫັດຄໍາສັ່ງ</th>
                                                    <th>ວັນທີ/ເວລາ</th>
                                                    <th>ລູກຄ້າ</th>
                                                    <th>ລາຍລະອຽດສິນຄ້າ</th>
                                                    <th>ຈໍານວນລາຍການ</th>
                                                    <th>ຈໍານວນທັງໝົດ</th>
                                                    <th>ວິທີຊໍາລະ</th>
                                                    <th>ຍອດລວມ</th>
                                                    <th>ສະຖານະ</th>
                                                </tr>
                                            <?php elseif ($report_type === 'inventory'): ?>
                                                <tr>
                                                    <th>ສິນຄ້າ</th>
                                                    <!-- <th>SKU</th> -->
                                                    <th>ໝວດໝູ່</th>
                                                    <th>ສິນຄ້າຄົງຄັງ</th>
                                                    <th>ລາຄາ</th>
                                                    <th>ມູນຄ່າຄົງຄັງ</th>
                                                </tr>
                                            <?php elseif ($report_type === 'customers'): ?>
                                                <tr>
                                                    <th>ລູກຄ້າ</th>
                                                    <th>ອີເມວ</th>
                                                    <th>ເບີໂທ</th>
                                                    <th>ຄໍາສັ່ງຊື້</th>
                                                    <th>ຍອດຊື້ທັງໝົດ</th>
                                                    <th>ຄໍາສັ່ງສຸດທ້າຍ</th>
                                                </tr>
                                            <?php elseif ($report_type === 'products'): ?>
                                                <tr>
                                                    <th>ສິນຄ້າ</th>
                                                    <!-- <th>SKU</th> -->
                                                    <th>ໝວດໝູ່</th>
                                                    <th>ລາຄາ</th>
                                                    <th>ສິນຄ້າຄົງຄັງ</th>
                                                    <th>ຄັ້ງທີ່ສັ່ງ</th>
                                                    <th>ຈໍານວນຂາຍ</th>
                                                    <th>ລາຍໄດ້</th>
                                                </tr>
                                            <?php endif; ?>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($report_data as $row): ?>
                                                <?php if ($report_type === 'sales'): ?>
                                                    <tr>
                                                        <td>
                                                            <a href="view_order.php?id=<?php echo $row['order_id']; ?>" class="text-primary">
                                                                #<?php echo htmlspecialchars($row['order_id']); ?>
                                                                <i class="fas fa-eye ms-1"></i>
                                                            </a>
                                                        </td>
                                                        <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                                        <td class="text-start">
                                                            <small>
                                                                <?php echo nl2br(htmlspecialchars($row['order_items'])); ?>
                                                            </small>
                                                        </td>
                                                        <td class="text-center"><?php echo number_format($row['total_items']); ?></td>
                                                        <td class="text-center"><?php echo number_format($row['total_quantity']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                                                        <td class="text-end"><?php echo formatCurrency($row['total_amount']); ?></td>
                                                        <td>
                                                            <span class="badge bg-success"><?php echo htmlspecialchars($row['status']); ?></span>
                                                        </td>
                                                    </tr>
                                                <?php elseif ($report_type === 'inventory'): ?>
                                                    <tr class="<?php echo $row['stock_quantity'] <= 10 ? 'table-warning' : ''; ?>">
                                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                        <!-- <td><?php echo htmlspecialchars($row['sku']); ?></td> -->
                                                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                                                        <td>
                                                            <span class="badge <?php echo $row['stock_quantity'] <= 10 ? 'bg-warning' : 'bg-success'; ?>">
                                                                <?php echo number_format($row['stock_quantity']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo formatCurrency($row['price']); ?></td>
                                                        <td><?php echo formatCurrency($row['stock_value']); ?></td>
                                                    </tr>
                                                <?php elseif ($report_type === 'customers'): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                                        <td><?php echo number_format($row['total_orders']); ?></td>
                                                        <td><?php echo formatCurrency($row['total_spent']); ?></td>
                                                        <td><?php echo $row['last_order_date'] ? date('d/m/Y', strtotime($row['last_order_date'])) : '-'; ?></td>
                                                    </tr>
                                                <?php elseif ($report_type === 'products'): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                        <!-- <td><?php echo htmlspecialchars($row['sku']); ?></td> -->
                                                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                                                        <td><?php echo formatCurrency($row['price']); ?></td>
                                                        <td>
                                                            <span class="badge <?php echo $row['stock_quantity'] <= 10 ? 'bg-warning' : 'bg-success'; ?>">
                                                                <?php echo number_format($row['stock_quantity']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo number_format($row['times_ordered']); ?></td>
                                                        <td><?php echo number_format($row['total_quantity_sold']); ?></td>
                                                        <td><?php echo formatCurrency($row['total_revenue']); ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <?php if (empty($report_data)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">ບໍ່ມີຂໍ້ມູນໃນຊ່ວງວັນທີທີ່ເລືອກ</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    function exportReport() {
                        // Create a simple CSV export
                        const table = document.querySelector('table');
                        const rows = table.querySelectorAll('tr');
                        let csv = [];

                        rows.forEach(row => {
                            const cols = row.querySelectorAll('td, th');
                            const rowData = [];
                            cols.forEach(col => {
                                rowData.push('"' + col.textContent.trim() + '"');
                            });
                            csv.push(rowData.join(','));
                        });

                        const csvContent = csv.join('\n');
                        const blob = new Blob([csvContent], {
                            type: 'text/csv;charset=utf-8;'
                        });
                        const link = document.createElement('a');
                        const url = URL.createObjectURL(blob);
                        link.setAttribute('href', url);
                        link.setAttribute('download', '<?php echo $report_type; ?>_report.csv');
                        link.style.visibility = 'hidden';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }

                    function printReport() {
                        window.print();
                    }
                </script>

                <?php include 'includes/footer.php'; ?>
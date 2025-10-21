<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdmin()) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle status updates
if (isset($_POST['action']) && $_POST['action'] === 'update_status' && isset($_POST['order_id']) && isset($_POST['status'])) {
    try {
        $stmt = $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['order_id']]);

        logActivity(
            $db,
            $_SESSION['admin_id'],
            'employee',
            'Updated order status',
            "Order ID: {$_POST['order_id']}, Status: {$_POST['status']}"
        );

        header("Location: orders.php?success=Order status updated successfully");
        exit();
    } catch (PDOException $e) {
        $error = "Error updating order: " . $e->getMessage();
    }
}

// Get orders with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(o.order_number LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if ($status_filter) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

if ($date_filter) {
    $where_conditions[] = "DATE(o.created_at) = ?";
    $params[] = $date_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_query = "SELECT COUNT(*) FROM orders o 
                LEFT JOIN customers c ON o.customer_id = c.id 
                $where_clause";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_orders = $stmt->fetchColumn();
$total_pages = ceil($total_orders / $limit);

// Get orders with customer info
$query = "SELECT o.*, c.first_name, c.last_name, c.email, c.phone,
          (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
          FROM orders o 
          LEFT JOIN customers c ON o.customer_id = c.id 
          $where_clause 
          ORDER BY o.created_at DESC 
          LIMIT ? OFFSET ?";
$stmt = $db->prepare($query);

// Bind parameters including LIMIT and OFFSET as integers
$param_count = 1;
foreach ($params as $param) {
    $stmt->bindValue($param_count++, $param);
}
$stmt->bindValue($param_count++, $limit, PDO::PARAM_INT);
$stmt->bindValue($param_count++, $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php $page_title = 'Order Management'; ?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="col-md-9 col-lg-10 main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-shopping-cart me-2"></i>ການຈັດການອໍເດີ້</h2>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search"
                        placeholder="ຄົ້ນຫາອໍເດີ້..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="status">
                        <option value="">ສະຖານະທັງໝົດ</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>ລໍຖ້າ</option>
                        <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>ກຳລັງດຳເນີນການ</option>
                        <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>ຈັດສົ່ງແລ້ວ</option>
                        <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>ສົ່ງແລ້ວ</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>ຍົກເລີກ</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" name="date"
                        value="<?php echo htmlspecialchars($date_filter); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">ກັ່ນຕອງ</button>
                </div>
                <div class="col-md-2">
                    <a href="orders.php" class="btn btn-outline-secondary w-100">ລ້າງຂໍ້ມູນ</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ເລກທີອໍເດີ້</th>
                            <th>ລູກຄ້າ</th>
                            <th>ລາຍການ</th>
                            <th>ລວມ</th>
                            <th>ສະຖານະ</th>
                            <th>ວັນທີ</th>
                            <th>ການດຳເນີນການ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No orders found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <span class="order-number"><?php echo htmlspecialchars($order['order_number']); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $order['item_count']; ?> items</span>
                                    </td>
                                    <td>
                                        <strong><?php echo formatCurrency($order['total_amount']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="view_order.php?id=<?php echo $order['id']; ?>"
                                                class="btn btn-sm btn-outline-primary" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-warning"
                                                onclick="updateStatus(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['order_number']); ?>', '<?php echo $order['status']; ?>')"
                                                title="Update Status">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="print_order.php?id=<?php echo $order['id']; ?>"
                                                class="btn btn-sm btn-outline-info" title="Print Order" target="_blank">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Order pagination">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date=<?php echo urlencode($date_filter); ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date=<?php echo urlencode($date_filter); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date=<?php echo urlencode($date_filter); ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>

            <div class="text-muted text-center">
                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_orders); ?> of <?php echo $total_orders; ?> orders
            </div>
        </div>
    </div>
</div>
</div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ປັບປຸງສະຖານະອໍເດີ້</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" id="orderId">

                    <p>ອໍເດີ້: <strong id="orderNumber"></strong></p>

                    <div class="mb-3">
                        <label for="status" class="form-label">ສະຖານະໃໝ່</label>
                        <select class="form-select" name="status" id="status" required>
                            <option value="pending">ລໍຖ້າ</option>
                            <option value="processing">ກຳລັງດຳເນີນການ</option>
                            <option value="shipped">ຈັດສົ່ງແລ້ວ</option>
                            <option value="delivered">ສົ່ງແລ້ວ</option>
                            <option value="cancelled">ຍົກເລີກ</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ຍົກເລີກ</button>
                    <button type="submit" class="btn btn-primary">ປັບປຸງສະຖານະ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$custom_scripts = '
     <script>
         function updateStatus(orderId, orderNumber, currentStatus) {
             document.getElementById("orderId").value = orderId;
             document.getElementById("orderNumber").textContent = orderNumber;
             document.getElementById("status").value = currentStatus;
             
             new bootstrap.Modal(document.getElementById("statusModal")).show();
         }
     </script>';
?>
<?php include 'includes/footer.php'; ?>
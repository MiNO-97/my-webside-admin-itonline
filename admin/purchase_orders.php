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

// Handle purchase order actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                try {
                    $supplier_id = (int)$_POST['supplier_id'];
                    $notes = sanitizeInput($_POST['notes']);

                    // Start transaction
                    $db->beginTransaction();

                    try {
                        // Calculate total from items
                        $total_amount = 0;
                        if (isset($_POST['items']) && is_array($_POST['items'])) {
                            foreach ($_POST['items'] as $item) {
                                if (!empty($item['quantity']) && !empty($item['unit_price'])) {
                                    $total_amount += (float)$item['quantity'] * (float)$item['unit_price'];
                                }
                            }
                        }

                        $stmt = $db->prepare("INSERT INTO purchase_orders (supplier_id, total_amount, notes, created_by, status) VALUES (?, ?, ?, ?, 'pending')");
                        $stmt->execute([$supplier_id, $total_amount, $notes, $_SESSION['employee_id']]);
                        $purchase_order_id = $db->lastInsertId();

                        // Add purchase order items
                        if (isset($_POST['items']) && is_array($_POST['items'])) {
                            foreach ($_POST['items'] as $item) {
                                if (!empty($item['product_name']) && $item['quantity'] > 0) {
                                    $total_price = (float)$item['quantity'] * (float)$item['unit_price'];

                                    // Check if the product_id column exists
                                    try {
                                        $check_column = $db->query("SHOW COLUMNS FROM purchase_order_items LIKE 'product_id'");
                                        $column_exists = $check_column->rowCount() > 0;

                                        if ($column_exists && !empty($item['product_id'])) {
                                            $stmt = $db->prepare("INSERT INTO purchase_order_items 
                                                (purchase_order_id, product_id, product_name, quantity, unit_price, total_price) 
                                                VALUES (?, ?, ?, ?, ?, ?)");
                                            $stmt->execute([
                                                $purchase_order_id,
                                                (int)$item['product_id'],
                                                sanitizeInput($item['product_name']),
                                                (int)$item['quantity'],
                                                (float)$item['unit_price'],
                                                $total_price
                                            ]);
                                        } else {
                                            $stmt = $db->prepare("INSERT INTO purchase_order_items 
                                                (purchase_order_id, product_name, quantity, unit_price, total_price) 
                                                VALUES (?, ?, ?, ?, ?)");
                                            $stmt->execute([
                                                $purchase_order_id,
                                                sanitizeInput($item['product_name']),
                                                (int)$item['quantity'],
                                                (float)$item['unit_price'],
                                                $total_price
                                            ]);
                                        }
                                    } catch (PDOException $e) {
                                        // If there's an error, try the old schema
                                        $stmt = $db->prepare("INSERT INTO purchase_order_items 
                                            (purchase_order_id, product_name, quantity, unit_price, total_price) 
                                            VALUES (?, ?, ?, ?, ?)");
                                        $stmt->execute([
                                            $purchase_order_id,
                                            sanitizeInput($item['product_name']),
                                            (int)$item['quantity'],
                                            (float)$item['unit_price'],
                                            $total_price
                                        ]);
                                    }
                                }
                            }
                        }

                        // Get supplier name for logging
                        $stmt = $db->prepare("SELECT name FROM suppliers WHERE id = ?");
                        $stmt->execute([$supplier_id]);
                        $supplier_name = $stmt->fetchColumn();

                        // Log activity
                        logActivity(
                            $db,
                            $_SESSION['employee_id'],
                            'employee',
                            'Created purchase order',
                            "Purchase Order ID: $purchase_order_id, Supplier: $supplier_name"
                        );

                        $db->commit();
                        header("Location: purchase_orders.php?success=Purchase order created successfully");
                        exit();
                    } catch (Exception $e) {
                        $db->rollBack();
                        $error = "Error creating purchase order: " . $e->getMessage();
                    }
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
                break;

            case 'update_status':
                try {
                    $purchase_order_id = (int)$_POST['purchase_order_id'];
                    $status = $_POST['status'];

                    $stmt = $db->prepare("UPDATE purchase_orders SET status = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$status, $purchase_order_id]);

                    logActivity(
                        $db,
                        $_SESSION['employee_id'],
                        'employee',
                        'Updated purchase order status',
                        "Purchase Order ID: $purchase_order_id, Status: $status"
                    );

                    header("Location: purchase_orders.php?success=Purchase order status updated successfully");
                    exit();
                } catch (PDOException $e) {
                    $error = "Error updating purchase order: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get purchase orders with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(po.supplier_name LIKE ? OR po.notes LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
}

if ($status_filter) {
    $where_conditions[] = "po.status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_query = "SELECT COUNT(*) FROM purchase_orders po $where_clause";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_orders = $stmt->fetchColumn();
$total_pages = ceil($total_orders / $limit);

// Get purchase orders with employee info
$query = "SELECT po.*, s.name as supplier_name, e.first_name, e.last_name,
          (SELECT COUNT(*) FROM purchase_order_items WHERE purchase_order_id = po.id) as item_count
          FROM purchase_orders po 
          LEFT JOIN employees e ON po.created_by = e.id 
          LEFT JOIN suppliers s ON po.supplier_id = s.id
          $where_clause 
          ORDER BY po.created_at DESC 
          LIMIT ? OFFSET ?";
$stmt = $db->prepare($query);

// Bind parameters
$param_count = 1;
foreach ($params as $param) {
    $stmt->bindValue($param_count++, $param);
}
$stmt->bindValue($param_count++, $limit, PDO::PARAM_INT);
$stmt->bindValue($param_count++, $offset, PDO::PARAM_INT);
$stmt->execute();
$purchase_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php $page_title = 'ການຈັດຊື້ສິນຄ້າ'; ?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="col-md-9 col-lg-10">
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>ການຈັດຊື້ສິນຄ້າ</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPurchaseOrderModal">
                <i class="fas fa-plus"></i> ສ້າງຄໍາສັ່ງຊື້
            </button>
        </div>

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

        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search"
                            placeholder="ຄົ້ນຫາຜູ້ສະໜອງ..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">ສະຖານະທັງໝົດ</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>ລໍຖ້າ</option>
                            <option value="ordered" <?php echo $status_filter === 'ordered' ? 'selected' : ''; ?>>ສັ່ງຊື້ແລ້ວ</option>
                            <option value="received" <?php echo $status_filter === 'received' ? 'selected' : ''; ?>>ໄດ້ຮັບແລ້ວ</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>ຍົກເລີກ</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">ກອງ</button>
                    </div>
                    <div class="col-md-2">
                        <a href="purchase_orders.php" class="btn btn-outline-secondary w-100">ລືມ</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Purchase Orders Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ລະຫັດ</th>
                                <th>ຜູ້ສະໜອງ</th>
                                <th>ຈໍານວນເງິນ</th>
                                <th>ສະຖານະ</th>
                                <th>ຈໍານວນລາຍການ</th>
                                <th>ຜູ້ສ້າງ</th>
                                <th>ວັນທີ</th>
                                <th>ການດໍາເນີນການ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($purchase_orders)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">ບໍ່ມີຂໍ້ມູນການຈັດຊື້ສິນຄ້າ</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($purchase_orders as $order): ?>
                                    <tr>
                                        <td><span class="order-number">#<?php echo $order['id']; ?></span></td>
                                        <td><?php echo htmlspecialchars($order['supplier_name']); ?></td>
                                        <td><?php echo formatCurrency($order['total_amount']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php
                                                $status_labels = [
                                                    'pending' => 'ລໍຖ້າ',
                                                    'ordered' => 'ສັ່ງຊື້ແລ້ວ',
                                                    'received' => 'ໄດ້ຮັບແລ້ວ',
                                                    'cancelled' => 'ຍົກເລີກ'
                                                ];
                                                echo $status_labels[$order['status']] ?? $order['status'];
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo $order['item_count']; ?> ລາຍການ</td>
                                        <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="print_purchase_order.php?id=<?php echo $order['id']; ?>"
                                                    class="btn btn-sm btn-outline-primary" title="ເບິ່ງລາຍລະອຽດ">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-warning"
                                                    onclick="updateStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')"
                                                    title="ປ່ຽນສະຖານະ">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="print_purchase_order.php?id=<?php echo $order['id']; ?>"
                                                    class="btn btn-sm btn-outline-info" title="ພິມໃບສັ່ງຊື້"
                                                    onclick="window.print(); return false;">
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
                    <nav aria-label="Purchase orders pagination">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

                <div class="text-muted text-center">
                    ສະແດງ <?php echo $offset + 1; ?> ຫາ <?php echo min($offset + $limit, $total_orders); ?> ຈາກ <?php echo $total_orders; ?> ຄໍາສັ່ງຊື້
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>

<!-- Create Purchase Order Modal -->
<div class="modal fade" id="createPurchaseOrderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ສ້າງຄໍາສັ່ງຊື້</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="supplier_id" class="form-label">ຜູ້ສະໜອງ</label>
                                <select class="form-select" name="supplier_id" required>
                                    <option value="">ເລືອກຜູ້ສະໜອງ</option>
                                    <?php
                                    $supplier_stmt = $db->query("SELECT id, name FROM suppliers ORDER BY name");
                                    while ($supplier = $supplier_stmt->fetch()) {
                                        echo '<option value="' . $supplier['id'] . '">' . htmlspecialchars($supplier['name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="total_amount" class="form-label">ຈໍານວນເງິນທັງໝົດ</label>
                                <input type="number" class="form-control" name="total_amount" step="0.01" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">ໝາຍເຫດ</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ລາຍການສິນຄ້າ</label>
                        <div id="items-container">
                            <div class="item-row row mb-2">
                                <div class="col-md-4">
                                    <select class="form-select product-select" name="items[0][product_id]" required
                                        onchange="updateProductDetails(this, 0)">
                                        <option value="">ເລືອກສິນຄ້າ</option>
                                        <?php
                                        $product_stmt = $db->query("SELECT id, name, price FROM products ORDER BY name");
                                        while ($product = $product_stmt->fetch()) {
                                            echo '<option value="' . $product['id'] . '" data-price="' . $product['price'] . '">'
                                                . htmlspecialchars($product['name']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <input type="hidden" name="items[0][product_name]">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control item-quantity" name="items[0][quantity]"
                                        placeholder="ຈໍານວນ" min="1" required onchange="calculateItemTotal(0)">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control item-price" name="items[0][unit_price]"
                                        placeholder="ລາຄາຕໍ່ໜ່ວຍ" step="0.01" required onchange="calculateItemTotal(0)">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control item-total" name="items[0][total_price]"
                                        placeholder="ລາຄາທັງໝົດ" step="0.01" readonly>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addItem()">
                            <i class="fas fa-plus"></i> ເພີ່ມລາຍການ
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ຍົກເລີກ</button>
                    <button type="submit" class="btn btn-primary">ສ້າງຄໍາສັ່ງຊື້</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ອັບເດດສະຖານະ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="purchase_order_id" id="purchaseOrderId">

                    <p>ຄໍາສັ່ງຊື້: <strong id="purchaseOrderNumber"></strong></p>

                    <div class="mb-3">
                        <label for="status" class="form-label">ສະຖານະໃໝ່</label>
                        <select class="form-select" name="status" id="status" required>
                            <option value="pending">ລໍຖ້າ</option>
                            <option value="ordered">ສັ່ງຊື້ແລ້ວ</option>
                            <option value="received">ໄດ້ຮັບແລ້ວ</option>
                            <option value="cancelled">ຍົກເລີກ</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ຍົກເລີກ</button>
                    <button type="submit" class="btn btn-primary">ອັບເດດ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let itemIndex = 1;

    function addItem() {
        const container = document.getElementById('items-container');
        const newItem = document.createElement('div');
        newItem.className = 'item-row row mb-2';
        newItem.innerHTML = `
                <div class="col-md-4">
                    <select class="form-select product-select" name="items[${itemIndex}][product_id]" required
                            onchange="updateProductDetails(this, ${itemIndex})">
                        <option value="">ເລືອກສິນຄ້າ</option>
                        <?php
                        $product_stmt = $db->query("SELECT id, name, price FROM products ORDER BY name");
                        while ($product = $product_stmt->fetch()) {
                            echo '<option value="' . $product['id'] . '" data-price="' . $product['price'] . '">'
                                . htmlspecialchars($product['name']) . '</option>';
                        }
                        ?>
                    </select>
                    <input type="hidden" name="items[${itemIndex}][product_name]">
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control item-quantity" name="items[${itemIndex}][quantity]" 
                           placeholder="ຈໍານວນ" min="1" required onchange="calculateItemTotal(${itemIndex})">
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control item-price" name="items[${itemIndex}][unit_price]" 
                           placeholder="ລາຄາຕໍ່ໜ່ວຍ" step="0.01" required onchange="calculateItemTotal(${itemIndex})">
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control item-total" name="items[${itemIndex}][total_price]" 
                           placeholder="ລາຄາທັງໝົດ" step="0.01" readonly>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
        container.appendChild(newItem);
        itemIndex++;
        updateTotalAmount();
    }

    function updateProductDetails(selectElement, index) {
        const row = selectElement.closest('.item-row');
        const option = selectElement.options[selectElement.selectedIndex];
        const price = option.dataset.price;
        const productName = option.text;

        row.querySelector(`input[name="items[${index}][product_name]"]`).value = productName;
        row.querySelector(`input[name="items[${index}][unit_price]"]`).value = price;

        calculateItemTotal(index);
    }

    function calculateItemTotal(index) {
        const row = document.querySelector(`.item-row:nth-child(${index + 1})`);
        const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        const total = quantity * price;

        row.querySelector('.item-total').value = total.toFixed(2);
        updateTotalAmount();
    }

    function updateTotalAmount() {
        let totalAmount = 0;
        document.querySelectorAll('.item-total').forEach(input => {
            totalAmount += parseFloat(input.value) || 0;
        });
        document.querySelector('input[name="total_amount"]').value = totalAmount.toFixed(2);
    }

    function removeItem(button) {
        button.closest('.item-row').remove();
    }

    function updateStatus(orderId, currentStatus) {
        document.getElementById('purchaseOrderId').value = orderId;
        document.getElementById('purchaseOrderNumber').textContent = '#' + orderId;
        document.getElementById('status').value = currentStatus;
        new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
    }

    function viewPurchaseOrder(orderId) {
        window.location.href = `print_purchase_order.php?id=${orderId}`;
    }
</script>

<?php include 'includes/footer.php'; ?>
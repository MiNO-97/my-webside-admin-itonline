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

// Get order ID
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    header("Location: orders.php");
    exit();
}

// Get order details with customer info
$query = "SELECT o.*, c.first_name, c.last_name, c.email, c.phone, c.address
          FROM orders o 
          LEFT JOIN customers c ON o.customer_id = c.id 
          WHERE o.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: orders.php");
    exit();
}

// Get order items with product details
$query = "SELECT oi.*, p.name, p.image_url 
          FROM order_items oi
          LEFT JOIN products p ON oi.product_id = p.id
          WHERE oi.order_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle status updates
if (isset($_POST['action']) && $_POST['action'] === 'update_status' && isset($_POST['status'])) {
    try {
        $stmt = $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$_POST['status'], $order_id]);

        logActivity(
            $db,
            $_SESSION['admin_id'],
            'employee',
            'Updated order status',
            "Order ID: $order_id, Status: {$_POST['status']}"
        );

        header("Location: view_order.php?id=$order_id&success=Order status updated successfully");
        exit();
    } catch (PDOException $e) {
        $error = "Error updating order: " . $e->getMessage();
    }
}
?>

<?php $page_title = 'Order Details'; ?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="col-md-9 col-lg-10 main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-shopping-cart me-2"></i>ລາຍລະອຽດອໍເດີ້</h2>
        <a href="orders.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>ກັບໄປໜ້າອໍເດີ້
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Order Information -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>ຂໍ້ມູນອໍເດີ້</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>ເລກທີອໍເດີ້:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                            <p><strong>ວັນທີ:</strong> <?php echo date('F d, Y H:i', strtotime($order['created_at'])); ?></p>
                            <p><strong>ສະຖານະ:</strong>
                                <span class="badge bg-<?php echo $order['status'] === 'pending' ? 'warning' : ($order['status'] === 'delivered' ? 'success' : 'info'); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </p>
                            <p><strong>ວິທີການຊຳລະ:</strong>
                                <?php
                                $payment_methods = [
                                    'cod' => 'ຈ່າຍເງິນປາຍທາງ',
                                    'bank_transfer' => 'ໂອນເງິນຜ່ານທະນາຄານ'
                                ];
                                echo $payment_methods[$order['payment_method']] ?? ucfirst($order['payment_method']);
                                ?>
                            </p>
                            <!-- <p><strong>Payment Status:</strong> 
                                            <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($order['payment_status']); ?>
                                            </span>
                                        </p> -->
                        </div>
                        <div class="col-md-6">
                            <p><strong>ຈຳນວນເງິນລວມ:</strong> <span class="text-primary fw-bold"><?php echo formatCurrency($order['total_amount']); ?></span></p>
                            <p><strong>ທີ່ຢູ່ຈັດສົ່ງ:</strong> <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                            <p><strong>ເມືອງ:</strong> <?php echo htmlspecialchars($order['shipping_city']); ?></p>
                            <p><strong>ເບີໂທ:</strong> <?php echo htmlspecialchars($order['shipping_phone']); ?></p>
                            <?php if ($order['notes']): ?>
                                <p><strong>ໝາຍເຫດ:</strong> <?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transfer Slip Section -->
            <?php if ($order['payment_method'] === 'bank_transfer' && $order['transfer_slip']): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-upload me-2"></i>Transfer Slip</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Transfer Slip:</strong></p>
                                <?php
                                $file_extension = pathinfo($order['transfer_slip'], PATHINFO_EXTENSION);
                                $is_image = in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif']);
                                ?>

                                <?php if ($is_image): ?>
                                    <img src="../<?php echo htmlspecialchars($order['transfer_slip']); ?>"
                                        class="img-fluid border rounded"
                                        alt="Transfer Slip"
                                        style="max-width: 100%; max-height: 400px;">
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-file-pdf me-2"></i>
                                        <strong>PDF Transfer Slip</strong>
                                        <br>
                                        <a href="../<?php echo htmlspecialchars($order['transfer_slip']); ?>"
                                            target="_blank" class="btn btn-sm btn-primary mt-2">
                                            <i class="fas fa-download me-1"></i>Download PDF
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-info-circle me-2"></i>Transfer Slip Information</h6>
                                    <p><strong>File:</strong> <?php echo basename($order['transfer_slip']); ?></p>
                                    <p><strong>Type:</strong> <?php echo strtoupper($file_extension); ?></p>
                                    <p><strong>Uploaded:</strong> <?php echo date('F d, Y H:i', strtotime($order['created_at'])); ?></p>
                                </div>

                                <div class="mt-3">
                                    <button type="button" class="btn btn-success btn-sm" onclick="markAsPaid()">
                                        <i class="fas fa-check me-1"></i>Mark as Paid
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm" onclick="requestMoreInfo()">
                                        <i class="fas fa-question me-1"></i>Request More Info
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif ($order['payment_method'] === 'bank_transfer' && !$order['transfer_slip']): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Transfer Slip Missing</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>No transfer slip uploaded for this bank transfer order.</strong>
                            <br>
                            Please contact the customer to request the transfer slip.
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Order Items -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-box me-2"></i>ລາຍການສິນຄ້າ</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ສິນຄ້າ</th>
                                    <th>ຈຳນວນ</th>
                                    <th>ລາຄາຕໍ່ໜ່ວຍ</th>
                                    <th>ລວມ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($item['image_url']): ?>
                                                    <img src="../uploads/products/<?php echo htmlspecialchars($item['image_url']); ?>"
                                                        class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                                    <?php if ($item['name']): ?>
                                                        <?php if ($item['name']): ?>
                                                            <br><small class="text-muted"> <?php echo htmlspecialchars($item['name']); ?></small>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo formatCurrency($item['unit_price']); ?></td>
                                        <td><strong><?php echo formatCurrency($item['total_price']); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Information & Actions -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>ຂໍ້ມູນລູກຄ້າ</h5>
                </div>
                <div class="card-body">
                    <p><strong>ຊື່:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                    <p><strong>ອີເມວ:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                    <p><strong>ເບີໂທ:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                    <p><strong>ທີ່ຢູ່:</strong> <?php echo nl2br(htmlspecialchars($order['address'])); ?></p>
                </div>
            </div>

            <!-- Status Update -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>ປັບປຸງສະຖານະ</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_status">
                        <div class="mb-3">
                            <label for="status" class="form-label">ສະຖານະອໍເດີ້</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>ລໍຖ້າ</option>
                                <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>ຢືນຢັນແລ້ວ</option>
                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>ກຳລັງດຳເນີນການ</option>
                                <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>ຈັດສົ່ງແລ້ວ</option>
                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>ສົ່ງແລ້ວ</option>
                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>ຍົກເລີກ</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i>ປັບປຸງສະຖານະ
                        </button>
                    </form>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tools me-2"></i>ການດຳເນີນການດ່ວນ</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="print_order.php?id=<?php echo $order['id']; ?>"
                            class="btn btn-outline-info" target="_blank">
                            <i class="fas fa-print me-2"></i>ພິມອໍເດີ້
                        </a>
                        <a href="mailto:<?php echo htmlspecialchars($order['email']); ?>"
                            class="btn btn-outline-secondary">
                            <i class="fas fa-envelope me-2"></i>ສົ່ງອີເມວຫາລູກຄ້າ
                        </a>
                        <button type="button" class="btn btn-outline-warning" onclick="sendNotification()">
                            <i class="fas fa-bell me-2"></i>ສົ່ງການແຈ້ງເຕືອນ
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>

<script>
    function markAsPaid() {
        if (confirm('Mark this order as paid?')) {
            // Update payment status
            fetch('update_payment_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'order_id=<?php echo $order_id; ?>&status=paid'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Order marked as paid successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
        }
    }

    function requestMoreInfo() {
        const message = prompt('Enter your message to the customer:');
        if (message) {
            // Send notification to customer
            fetch('send_notification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'order_id=<?php echo $order_id; ?>&message=' + encodeURIComponent(message)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Notification sent successfully!');
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
        }
    }

    function sendNotification() {
        const message = prompt('Enter notification message:');
        if (message) {
            // Send notification
            fetch('send_notification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'order_id=<?php echo $order_id; ?>&message=' + encodeURIComponent(message)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Notification sent successfully!');
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
        }
    }
</script>

<?php include 'includes/footer.php'; ?>
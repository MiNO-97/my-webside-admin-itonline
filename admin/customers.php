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

// Handle actions
$action = $_GET['action'] ?? '';
$customer_id = $_GET['id'] ?? '';

if ($action === 'delete' && $customer_id) {
    try {
        $stmt = $db->prepare("UPDATE customers SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$customer_id]);
        logActivity($db, $_SESSION['admin_id'], 'employee', 'Deleted customer', "Customer ID: $customer_id");
        header("Location: customers.php?success=Customer deleted successfully");
        exit();
    } catch(PDOException $e) {
        $error = "Error deleting customer: " . $e->getMessage();
    }
}

// Get customers with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_query = "SELECT COUNT(*) FROM customers $where_clause";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_customers = $stmt->fetchColumn();
$total_pages = ceil($total_customers / $limit);

// Get customers
$query = "SELECT * FROM customers $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $db->prepare($query);

// Bind parameters including LIMIT and OFFSET as integers
$param_count = 1;
foreach ($params as $param) {
    $stmt->bindValue($param_count++, $param);
}
$stmt->bindValue($param_count++, $limit, PDO::PARAM_INT);
$stmt->bindValue($param_count++, $offset, PDO::PARAM_INT);
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php $page_title = 'ການຈັດການລູກຄ້າ'; ?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-users me-2"></i>ການຈັດການລູກຄ້າ</h2>
                    <a href="add_customer.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>ເພີ່ມລູກຄ້າໃໝ່
                    </a>
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
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="ຄົ້ນຫາລູກຄ້າ..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="status">
                                    <option value="">ສະຖານະທັງໝົດ</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>ໃຊ້ງານ</option>
                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>ບໍ່ໃຊ້ງານ</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">ກັ່ນຕອງ</button>
                            </div>
                            <div class="col-md-2">
                                <a href="customers.php" class="btn btn-outline-secondary w-100">ລ້າງ</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Customers Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ລະຫັດ</th>
                                        <th>ຊື່</th>
                                        <th>ອີເມວ</th>
                                        <th>ເບີໂທ</th>
                                        <th>ສະຖານະ</th>
                                        <th>ລົງທະບຽນ</th>
                                        <th>ການດໍາເນີນການ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($customers)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">ບໍ່ພົບລູກຄ້າ</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($customers as $customer): ?>
                                            <tr>
                                                <td><?php echo $customer['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                                <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                                <td>
                                                    <span class="status-badge <?php echo $customer['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                        <?php echo ucfirst($customer['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="view_customer.php?id=<?php echo $customer['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="edit_customer.php?id=<?php echo $customer['id']; ?>" 
                                                           class="btn btn-sm btn-outline-warning" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="deleteCustomer(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>')" 
                                                                title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
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
                            <nav aria-label="Customer pagination">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                        
                        <div class="text-muted text-center">
                            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_customers); ?> of <?php echo $total_customers; ?> customers
                        </div>
                    </div>
                </div>
                         </div>
         </div>
     </div>
     
     <?php 
     $custom_scripts = '
     <script>
         function deleteCustomer(customerId, customerName) {
             Swal.fire({
                 title: "Delete Customer?",
                 text: `Are you sure you want to delete ${customerName}? This action cannot be undone.`,
                 icon: "warning",
                 showCancelButton: true,
                 confirmButtonColor: "#d33",
                 cancelButtonColor: "#3085d6",
                 confirmButtonText: "Yes, delete!",
                 cancelButtonText: "Cancel"
             }).then((result) => {
                 if (result.isConfirmed) {
                     window.location.href = `customers.php?action=delete&id=${customerId}`;
                 }
             });
         }
     </script>';
     ?>
     <?php include 'includes/footer.php'; ?> 
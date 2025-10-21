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
$product_id = $_GET['id'] ?? '';

if ($action === 'delete' && $product_id) {
    try {
        $stmt = $db->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$product_id]);
        logActivity($db, $_SESSION['admin_id'], 'employee', 'Deleted product', "Product ID: $product_id");
        header("Location: products.php?success=Product deleted successfully");
        exit();
    } catch (PDOException $e) {
        $error = "Error deleting product: " . $e->getMessage();
    }
}

// Get products with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if ($category_filter) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if ($status_filter) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_query = "SELECT COUNT(*) FROM products p $where_clause";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_products = $stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

// Get products with category names
$query = "SELECT p.*, pc.name as category_name 
          FROM products p 
          LEFT JOIN product_categories pc ON p.category_id = pc.id 
          $where_clause 
          ORDER BY p.created_at DESC 
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
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$categories = getCategories($db);
?>

<?php $page_title = 'Product Management'; ?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="col-md-9 col-lg-10 main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-box me-2"></i>ການຈັດການສິນຄ້າ</h2>
        <a href="add_product.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>ເພີ່ມສິນຄ້າໃໝ່
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
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search"
                        placeholder="ຄົ້ນຫາສິນຄ້າ..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="category">
                        <option value="">ໝວດໝູ່ທັງໝົດ</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"
                                <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
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
                    <a href="products.php" class="btn btn-outline-secondary w-100">ລ້າງ</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ຮູບພາບ</th>
                            <th>ຊື່</th>
                            <th>ຄຳອະທິບາຍ</th>
                            <th>ໝວດໝູ່</th>
                            <th>ລາຄາ</th>
                            <th>ສະຕ໋ອກ</th>
                            <th>ສະຖານະ</th>
                            <th>ດຳເນີນການ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No products found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if ($product['image_url']): ?>
                                            <img src="../uploads/products/<?php echo htmlspecialchars($product['image_url']); ?>"
                                                class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php else: ?>
                                            <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars(substr($product['description'], 0, 50)) . '...'; ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['description']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td><?php echo formatCurrency($product['price']); ?></td>
                                    <td>
                                        <span class="<?php echo $product['stock_quantity'] <= 10 ? 'stock-low' : 'stock-ok'; ?>">
                                            <?php echo $product['stock_quantity']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $product['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo ucfirst($product['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="view_product.php?id=<?php echo $product['id']; ?>"
                                                class="btn btn-sm btn-outline-primary" title="ເບິ່ງ">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_product.php?id=<?php echo $product['id']; ?>"
                                                class="btn btn-sm btn-outline-warning" title="ແກ້ໄຂ">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')"
                                                title="ລຶບ">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-success"
                                                onclick="quickAddStock(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')"
                                                title="ເພີ່ມສະຕ໋ອກ">
                                                <i class="fas fa-plus"></i>
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
                <nav aria-label="Product pagination">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>

            <div class="text-muted text-center">
                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_products); ?> of <?php echo $total_products; ?> products
            </div>
        </div>
    </div>
</div>
</div>
</div>

<?php
$custom_scripts = '
     <script>
         function deleteProduct(productId, productName) {
             Swal.fire({
                 title: "ລຶບສິນຄ້າ?",
                 text: `ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລຶບ ${productName}? ການກະທຳນີ້ບໍ່ສາມາດຍ້ອນກັບໄດ້.`,
                 icon: "warning",
                 showCancelButton: true,
                 confirmButtonColor: "#d33",
                 cancelButtonColor: "#3085d6",
                 confirmButtonText: "ແມ່ນ, ລຶບ!",
                 cancelButtonText: "ຍົກເລີກ"
             }).then((result) => {
                 if (result.isConfirmed) {
                     window.location.href = `products.php?action=delete&id=${productId}`;
                 }
             });
         }

         function quickAddStock(productId, productName) {
             Swal.fire({
                 title: "ເພີ່ມສະຕ໋ອກ",
                 text: `ເພີ່ມສະຕ໋ອກສຳລັບ ${productName}`,
                 input: "number",
                 inputLabel: "ຈຳນວນທີ່ຈະເພີ່ມ",
                 inputPlaceholder: "ປ້ອນຈຳນວນ",
                 showCancelButton: true,
                 confirmButtonText: "ເພີ່ມ",
                 cancelButtonText: "ຍົກເລີກ",
                 inputValidator: (value) => {
                     if (!value || value <= 0) {
                         return "ກະລຸນາປ້ອນຈຳນວນທີ່ຖືກຕ້ອງ!";
                     }
                 }
             }).then((result) => {
                 if (result.isConfirmed) {
                     // Send AJAX request to update stock
                     fetch("ajax/update_stock.php", {
                         method: "POST",
                         headers: {
                             "Content-Type": "application/json",
                         },
                         body: JSON.stringify({
                             product_id: productId,
                             quantity: result.value
                         })
                     })
                     .then(response => response.json())
                     .then(data => {
                         if (data.success) {
                             Swal.fire({
                                 title: "ສຳເລັດ!",
                                 text: "ອັບເດດສະຕ໋ອກສຳເລັດແລ້ວ",
                                 icon: "success"
                             }).then(() => {
                                 location.reload();
                             });
                         } else {
                             Swal.fire({
                                 title: "ຜິດພາດ!",
                                 text: data.message || "ເກີດຂໍ້ຜິດພາດໃນການອັບເດດສະຕ໋ອກ",
                                 icon: "error"
                             });
                         }
                     })
                     .catch(error => {
                         Swal.fire({
                             title: "ຜິດພາດ!",
                             text: "ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່",
                             icon: "error"
                         });
                     });
                 }
             });
         }
     </script>';
?>
<?php include 'includes/footer.php'; ?>
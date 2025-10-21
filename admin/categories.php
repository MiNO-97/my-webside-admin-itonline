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

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                try {
                    $name = sanitizeInput($_POST['name']);
                    $description = sanitizeInput($_POST['description']);
                    
                    $stmt = $db->prepare("INSERT INTO product_categories (name, description) VALUES (?, ?)");
                    $stmt->execute([$name, $description]);
                    
                    logActivity($db, $_SESSION['employee_id'], 'employee', 'Created category', "Category: $name");
                    
                    header("Location: categories.php?success=Category created successfully");
                    exit();
                } catch(PDOException $e) {
                    $error = "Error creating category: " . $e->getMessage();
                }
                break;
                
            case 'update':
                try {
                    $category_id = (int)$_POST['category_id'];
                    $name = sanitizeInput($_POST['name']);
                    $description = sanitizeInput($_POST['description']);
                    $status = $_POST['status'];
                    
                    $stmt = $db->prepare("UPDATE product_categories SET name = ?, description = ?, status = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$name, $description, $status, $category_id]);
                    
                    logActivity($db, $_SESSION['employee_id'], 'employee', 'Updated category', "Category ID: $category_id, Name: $name");
                    
                    header("Location: categories.php?success=Category updated successfully");
                    exit();
                } catch(PDOException $e) {
                    $error = "Error updating category: " . $e->getMessage();
                }
                break;
                
            case 'delete':
                try {
                    $category_id = (int)$_POST['category_id'];
                    
                    // Check if category has products
                    $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                    $stmt->execute([$category_id]);
                    $product_count = $stmt->fetchColumn();
                    
                    if ($product_count > 0) {
                        $error = "Cannot delete category: It has $product_count associated products";
                    } else {
                        $stmt = $db->prepare("DELETE FROM product_categories WHERE id = ?");
                        $stmt->execute([$category_id]);
                        
                        logActivity($db, $_SESSION['employee_id'], 'employee', 'Deleted category', "Category ID: $category_id");
                        
                        header("Location: categories.php?success=Category deleted successfully");
                        exit();
                    }
                } catch(PDOException $e) {
                    $error = "Error deleting category: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get categories with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
}

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_query = "SELECT COUNT(*) FROM product_categories $where_clause";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_categories = $stmt->fetchColumn();
$total_pages = ceil($total_categories / $limit);

// Get categories with product count
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count
          FROM product_categories c 
          $where_clause 
          ORDER BY c.name ASC 
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
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php $page_title = 'ການຈັດການໝວດໝູ່'; ?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>ການຈັດການໝວດໝູ່</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                            <i class="fas fa-plus"></i> ສ້າງໝວດໝູ່ໃໝ່
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
                                           placeholder="ຄົ້ນຫາໝວດໝູ່..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="status">
                                        <option value="">ສະຖານະທັງໝົດ</option>
                                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>ເປີດໃຊ້ງານ</option>
                                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>ປິດໃຊ້ງານ</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">ກອງ</button>
                                </div>
                                <div class="col-md-2">
                                    <a href="categories.php" class="btn btn-outline-secondary w-100">ລືມ</a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Categories Table -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ລະຫັດ</th>
                                            <th>ຊື່ໝວດໝູ່</th>
                                            <th>ຄໍາອະທິບາຍ</th>
                                            <th>ຈໍານວນສິນຄ້າ</th>
                                            <th>ສະຖານະ</th>
                                            <th>ວັນທີສ້າງ</th>
                                            <th>ການດໍາເນີນການ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($categories)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">ບໍ່ມີຂໍ້ມູນໝວດໝູ່</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($categories as $category): ?>
                                                <tr>
                                                    <td><?php echo $category['id']; ?></td>
                                                    <td><strong><?php echo htmlspecialchars($category['name']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($category['description']); ?></td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo $category['product_count']; ?> ສິນຄ້າ</span>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $category['status']; ?>">
                                                            <?php echo $category['status'] === 'active' ? 'ເປີດໃຊ້ງານ' : 'ປິດໃຊ້ງານ'; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($category['created_at'])); ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-outline-warning" 
                                                                    onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <?php if ($category['product_count'] == 0): ?>
                                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                        onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            <?php endif; ?>
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
                                <nav aria-label="Categories pagination">
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
                                ສະແດງ <?php echo $offset + 1; ?> ຫາ <?php echo min($offset + $limit, $total_categories); ?> ຈາກ <?php echo $total_categories; ?> ໝວດໝູ່
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Create Category Modal -->
    <div class="modal fade" id="createCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ສ້າງໝວດໝູ່ໃໝ່</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">ຊື່ໝວດໝູ່</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">ຄໍາອະທິບາຍ</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ຍົກເລີກ</button>
                        <button type="submit" class="btn btn-primary">ສ້າງໝວດໝູ່</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ແກ້ໄຂໝວດໝູ່</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="category_id" id="editCategoryId">
                        
                        <div class="mb-3">
                            <label for="editName" class="form-label">ຊື່ໝວດໝູ່</label>
                            <input type="text" class="form-control" name="name" id="editName" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editDescription" class="form-label">ຄໍາອະທິບາຍ</label>
                            <textarea class="form-control" name="description" id="editDescription" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editStatus" class="form-label">ສະຖານະ</label>
                            <select class="form-select" name="status" id="editStatus" required>
                                <option value="active">ເປີດໃຊ້ງານ</option>
                                <option value="inactive">ປິດໃຊ້ງານ</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ຍົກເລີກ</button>
                        <button type="submit" class="btn btn-primary">ບັນທຶກ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Category Modal -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ລຶບໝວດໝູ່</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="category_id" id="deleteCategoryId">
                        
                        <p>ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລຶບໝວດໝູ່: <strong id="deleteCategoryName"></strong>?</p>
                        <p class="text-danger">ການດໍາເນີນການນີ້ບໍ່ສາມາດຍົກເລີກໄດ້</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ຍົກເລີກ</button>
                        <button type="submit" class="btn btn-danger">ລຶບ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editCategory(category) {
            document.getElementById('editCategoryId').value = category.id;
            document.getElementById('editName').value = category.name;
            document.getElementById('editDescription').value = category.description;
            document.getElementById('editStatus').value = category.status;
            new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
        }
        
        function deleteCategory(categoryId, categoryName) {
            document.getElementById('deleteCategoryId').value = categoryId;
            document.getElementById('deleteCategoryName').textContent = categoryName;
            new bootstrap.Modal(document.getElementById('deleteCategoryModal')).show();
        }
    </script>

<?php include 'includes/footer.php'; ?> 
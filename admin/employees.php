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

// Handle employee actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                try {
                    $first_name = sanitizeInput($_POST['first_name']);
                    $last_name = sanitizeInput($_POST['last_name']);
                    $email = sanitizeInput($_POST['email']);
                    $phone = sanitizeInput($_POST['phone']);
                    $position = sanitizeInput($_POST['position']);
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    
                    // Check if email already exists
                    $stmt = $db->prepare("SELECT COUNT(*) FROM employees WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = "Email already exists";
                    } else {
                        $stmt = $db->prepare("INSERT INTO employees (first_name, last_name, email, phone, position, password) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$first_name, $last_name, $email, $phone, $position, $password]);
                        
                        logActivity($db, $_SESSION['employee_id'], 'employee', 'Created employee', "Employee: $first_name $last_name");
                        
                        header("Location: employees.php?success=Employee created successfully");
                        exit();
                    }
                } catch(PDOException $e) {
                    $error = "Error creating employee: " . $e->getMessage();
                }
                break;
                
            case 'update':
                try {
                    $employee_id = (int)$_POST['employee_id'];
                    $first_name = sanitizeInput($_POST['first_name']);
                    $last_name = sanitizeInput($_POST['last_name']);
                    $email = sanitizeInput($_POST['email']);
                    $phone = sanitizeInput($_POST['phone']);
                    $position = sanitizeInput($_POST['position']);
                    $status = $_POST['status'];
                    
                    // Check if email already exists for other employees
                    $stmt = $db->prepare("SELECT COUNT(*) FROM employees WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $employee_id]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = "Email already exists";
                    } else {
                        $stmt = $db->prepare("UPDATE employees SET first_name = ?, last_name = ?, email = ?, phone = ?, position = ?, status = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$first_name, $last_name, $email, $phone, $position, $status, $employee_id]);
                        
                        logActivity($db, $_SESSION['employee_id'], 'employee', 'Updated employee', "Employee ID: $employee_id, Name: $first_name $last_name");
                        
                        header("Location: employees.php?success=Employee updated successfully");
                        exit();
                    }
                } catch(PDOException $e) {
                    $error = "Error updating employee: " . $e->getMessage();
                }
                break;
                
            case 'change_password':
                try {
                    $employee_id = (int)$_POST['employee_id'];
                    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                    
                    $stmt = $db->prepare("UPDATE employees SET password = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$new_password, $employee_id]);
                    
                    logActivity($db, $_SESSION['employee_id'], 'employee', 'Changed employee password', "Employee ID: $employee_id");
                    
                    header("Location: employees.php?success=Password changed successfully");
                    exit();
                } catch(PDOException $e) {
                    $error = "Error changing password: " . $e->getMessage();
                }
                break;
                
            case 'delete':
                try {
                    $employee_id = (int)$_POST['employee_id'];
                    
                    // Prevent deleting self
                    if ($employee_id == $_SESSION['employee_id']) {
                        $error = "Cannot delete your own account";
                    } else {
                        $stmt = $db->prepare("DELETE FROM employees WHERE id = ?");
                        $stmt->execute([$employee_id]);
                        
                        logActivity($db, $_SESSION['employee_id'], 'employee', 'Deleted employee', "Employee ID: $employee_id");
                        
                        header("Location: employees.php?success=Employee deleted successfully");
                        exit();
                    }
                } catch(PDOException $e) {
                    $error = "Error deleting employee: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get employees with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ? OR position LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_query = "SELECT COUNT(*) FROM employees $where_clause";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_employees = $stmt->fetchColumn();
$total_pages = ceil($total_employees / $limit);

// Get employees
$query = "SELECT * FROM employees $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $db->prepare($query);

// Bind parameters
$param_count = 1;
foreach ($params as $param) {
    $stmt->bindValue($param_count++, $param);
}
$stmt->bindValue($param_count++, $limit, PDO::PARAM_INT);
$stmt->bindValue($param_count++, $offset, PDO::PARAM_INT);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php $page_title = 'ການຈັດການພະນັກງານ'; ?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>ການຈັດການພະນັກງານ</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEmployeeModal">
                            <i class="fas fa-plus"></i> ເພີ່ມພະນັກງານ
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
                                           placeholder="ຄົ້ນຫາພະນັກງານ..." value="<?php echo htmlspecialchars($search); ?>">
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
                                    <a href="employees.php" class="btn btn-outline-secondary w-100">ລືມ</a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Employees Table -->
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
                                            <th>ຕໍາແໜ່ງ</th>
                                            <th>ສະຖານະ</th>
                                            <th>ວັນທີສ້າງ</th>
                                            <th>ການດໍາເນີນການ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($employees)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted">ບໍ່ມີຂໍ້ມູນພະນັກງານ</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($employees as $employee): ?>
                                                <tr>
                                                    <td><?php echo $employee['id']; ?></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></strong>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($employee['phone']); ?></td>
                                                    <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $employee['status']; ?>">
                                                            <?php echo $employee['status'] === 'active' ? 'ເປີດໃຊ້ງານ' : 'ປິດໃຊ້ງານ'; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($employee['created_at'])); ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-outline-warning" 
                                                                    onclick="editEmployee(<?php echo htmlspecialchars(json_encode($employee)); ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-info" 
                                                                    onclick="changePassword(<?php echo $employee['id']; ?>, '<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>')">
                                                                <i class="fas fa-key"></i>
                                                            </button>
                                                            <?php if ($employee['id'] != $_SESSION['employee_id']): ?>
                                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                        onclick="deleteEmployee(<?php echo $employee['id']; ?>, '<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>')">
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
                                <nav aria-label="Employees pagination">
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
                                ສະແດງ <?php echo $offset + 1; ?> ຫາ <?php echo min($offset + $limit, $total_employees); ?> ຈາກ <?php echo $total_employees; ?> ພະນັກງານ
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Create Employee Modal -->
    <div class="modal fade" id="createEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ເພີ່ມພະນັກງານ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">ຊື່</label>
                                    <input type="text" class="form-control" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">ນາມສະກຸນ</label>
                                    <input type="text" class="form-control" name="last_name" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">ອີເມວ</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">ເບີໂທ</label>
                                    <input type="text" class="form-control" name="phone">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="position" class="form-label">ຕໍາແໜ່ງ</label>
                                    <input type="text" class="form-control" name="position" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">ລະຫັດຜ່ານ</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ຍົກເລີກ</button>
                        <button type="submit" class="btn btn-primary">ເພີ່ມພະນັກງານ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Employee Modal -->
    <div class="modal fade" id="editEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ແກ້ໄຂພະນັກງານ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="employee_id" id="editEmployeeId">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editFirstName" class="form-label">ຊື່</label>
                                    <input type="text" class="form-control" name="first_name" id="editFirstName" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editLastName" class="form-label">ນາມສະກຸນ</label>
                                    <input type="text" class="form-control" name="last_name" id="editLastName" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">ອີເມວ</label>
                            <input type="email" class="form-control" name="email" id="editEmail" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editPhone" class="form-label">ເບີໂທ</label>
                                    <input type="text" class="form-control" name="phone" id="editPhone">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editPosition" class="form-label">ຕໍາແໜ່ງ</label>
                                    <input type="text" class="form-control" name="position" id="editPosition" required>
                                </div>
                            </div>
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
    
    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ປ່ຽນລະຫັດຜ່ານ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="change_password">
                        <input type="hidden" name="employee_id" id="changePasswordEmployeeId">
                        
                        <p>ປ່ຽນລະຫັດຜ່ານສໍາລັບ: <strong id="changePasswordEmployeeName"></strong></p>
                        
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">ລະຫັດຜ່ານໃໝ່</label>
                            <input type="password" class="form-control" name="new_password" id="newPassword" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ຍົກເລີກ</button>
                        <button type="submit" class="btn btn-primary">ປ່ຽນລະຫັດຜ່ານ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Employee Modal -->
    <div class="modal fade" id="deleteEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ລຶບພະນັກງານ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="employee_id" id="deleteEmployeeId">
                        
                        <p>ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລຶບພະນັກງານ: <strong id="deleteEmployeeName"></strong>?</p>
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
        function editEmployee(employee) {
            document.getElementById('editEmployeeId').value = employee.id;
            document.getElementById('editFirstName').value = employee.first_name;
            document.getElementById('editLastName').value = employee.last_name;
            document.getElementById('editEmail').value = employee.email;
            document.getElementById('editPhone').value = employee.phone;
            document.getElementById('editPosition').value = employee.position;
            document.getElementById('editStatus').value = employee.status;
            new bootstrap.Modal(document.getElementById('editEmployeeModal')).show();
        }
        
        function changePassword(employeeId, employeeName) {
            document.getElementById('changePasswordEmployeeId').value = employeeId;
            document.getElementById('changePasswordEmployeeName').textContent = employeeName;
            new bootstrap.Modal(document.getElementById('changePasswordModal')).show();
        }
        
        function deleteEmployee(employeeId, employeeName) {
            document.getElementById('deleteEmployeeId').value = employeeId;
            document.getElementById('deleteEmployeeName').textContent = employeeName;
            new bootstrap.Modal(document.getElementById('deleteEmployeeModal')).show();
        }
    </script>

<?php include 'includes/footer.php'; ?> 
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

// Handle supplier actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                try {
                    $name = sanitizeInput($_POST['name']);
                    $contact_person = sanitizeInput($_POST['contact_person']);
                    $phone = sanitizeInput($_POST['phone']);
                    $email = sanitizeInput($_POST['email']);
                    $address = sanitizeInput($_POST['address']);

                    $stmt = $db->prepare("INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $contact_person, $phone, $email, $address]);

                    header("Location: suppliers.php?success=Supplier created successfully");
                    exit();
                } catch (PDOException $e) {
                    $error = "Error creating supplier: " . $e->getMessage();
                }
                break;

            case 'update':
                try {
                    $id = (int)$_POST['id'];
                    $name = sanitizeInput($_POST['name']);
                    $contact_person = sanitizeInput($_POST['contact_person']);
                    $phone = sanitizeInput($_POST['phone']);
                    $email = sanitizeInput($_POST['email']);
                    $address = sanitizeInput($_POST['address']);

                    $stmt = $db->prepare("UPDATE suppliers SET name = ?, contact_person = ?, phone = ?, email = ?, address = ? WHERE id = ?");
                    $stmt->execute([$name, $contact_person, $phone, $email, $address, $id]);

                    header("Location: suppliers.php?success=Supplier updated successfully");
                    exit();
                } catch (PDOException $e) {
                    $error = "Error updating supplier: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get suppliers with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';

$where_clause = "";
$params = [];

if ($search) {
    $where_clause = "WHERE name LIKE ? OR contact_person LIKE ? OR phone LIKE ? OR email LIKE ?";
    $search_param = "%$search%";
    $params = array_fill(0, 4, $search_param);
}

// Get total count
$count_query = "SELECT COUNT(*) FROM suppliers $where_clause";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_suppliers = $stmt->fetchColumn();
$total_pages = ceil($total_suppliers / $limit);

// Get suppliers
$query = "SELECT * FROM suppliers $where_clause ORDER BY name LIMIT ? OFFSET ?";
$stmt = $db->prepare($query);

// Bind search parameters if they exist
$param_index = 1;
foreach ($params as $param) {
    $stmt->bindValue($param_index++, $param);
}
$stmt->bindValue($param_index++, $limit, PDO::PARAM_INT);
$stmt->bindValue($param_index++, $offset, PDO::PARAM_INT);
$stmt->execute();
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php $page_title = 'ຜູ້ສະໜອງ'; ?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="col-md-9 col-lg-10">
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>ຜູ້ສະໜອງ</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSupplierModal">
                <i class="fas fa-plus"></i> ເພີ່ມຜູ້ສະໜອງ
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

        <!-- Search -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <input type="text" class="form-control" name="search"
                            placeholder="ຄົ້ນຫາຜູ້ສະໜອງ..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">ຄົ້ນຫາ</button>
                    </div>
                    <div class="col-md-2">
                        <a href="suppliers.php" class="btn btn-outline-secondary w-100">ລືມ</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Suppliers Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ຊື່</th>
                                <th>ຜູ້ຕິດຕໍ່</th>
                                <th>ເບີໂທ</th>
                                <th>ອີເມວ</th>
                                <th>ທີ່ຢູ່</th>
                                <th>ການດໍາເນີນການ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($suppliers)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">ບໍ່ມີຂໍ້ມູນຜູ້ສະໜອງ</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                                        <td><?php echo htmlspecialchars($supplier['contact_person']); ?></td>
                                        <td><?php echo htmlspecialchars($supplier['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($supplier['email']); ?></td>
                                        <td><?php echo htmlspecialchars($supplier['address']); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                onclick="editSupplier(<?php echo htmlspecialchars(json_encode($supplier)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Suppliers pagination">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

                <div class="text-muted text-center">
                    ສະແດງ <?php echo $offset + 1; ?> ຫາ <?php echo min($offset + $limit, $total_suppliers); ?> ຈາກ <?php echo $total_suppliers; ?> ຜູ້ສະໜອງ
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>

<!-- Create Supplier Modal -->
<div class="modal fade" id="createSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ເພີ່ມຜູ້ສະໜອງ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">

                    <div class="mb-3">
                        <label for="name" class="form-label">ຊື່ຜູ້ສະໜອງ</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="contact_person" class="form-label">ຜູ້ຕິດຕໍ່</label>
                        <input type="text" class="form-control" name="contact_person">
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">ເບີໂທ</label>
                        <input type="text" class="form-control" name="phone">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">ອີເມວ</label>
                        <input type="email" class="form-control" name="email">
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">ທີ່ຢູ່</label>
                        <textarea class="form-control" name="address" rows="3"></textarea>
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

<!-- Edit Supplier Modal -->
<div class="modal fade" id="editSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ແກ້ໄຂຜູ້ສະໜອງ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="mb-3">
                        <label for="edit_name" class="form-label">ຊື່ຜູ້ສະໜອງ</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_contact_person" class="form-label">ຜູ້ຕິດຕໍ່</label>
                        <input type="text" class="form-control" name="contact_person" id="edit_contact_person">
                    </div>

                    <div class="mb-3">
                        <label for="edit_phone" class="form-label">ເບີໂທ</label>
                        <input type="text" class="form-control" name="phone" id="edit_phone">
                    </div>

                    <div class="mb-3">
                        <label for="edit_email" class="form-label">ອີເມວ</label>
                        <input type="email" class="form-control" name="email" id="edit_email">
                    </div>

                    <div class="mb-3">
                        <label for="edit_address" class="form-label">ທີ່ຢູ່</label>
                        <textarea class="form-control" name="address" id="edit_address" rows="3"></textarea>
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

<script>
    function editSupplier(supplier) {
        document.getElementById('edit_id').value = supplier.id;
        document.getElementById('edit_name').value = supplier.name;
        document.getElementById('edit_contact_person').value = supplier.contact_person;
        document.getElementById('edit_phone').value = supplier.phone;
        document.getElementById('edit_email').value = supplier.email;
        document.getElementById('edit_address').value = supplier.address;
        new bootstrap.Modal(document.getElementById('editSupplierModal')).show();
    }
</script>

<?php include 'includes/footer.php'; ?>
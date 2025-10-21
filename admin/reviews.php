<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['employee_id']) || !isAdmin()) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle review status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $review_id = (int)$_POST['review_id'];
    $action = $_POST['action'];

    if ($action === 'approve' || $action === 'reject') {
        $status = $action === 'approve' ? 'approved' : 'rejected';

        try {
            $query = "UPDATE reviews SET status = :status WHERE id = :review_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':review_id', $review_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $success_message = "ຄຳຄິດເຫັນຖືກ" . ($action === 'approve' ? 'ອະນຸຍາດ' : 'ປະຕິເສດ') . "ແລ້ວ";
            } else {
                $error_message = "ບໍ່ສາມາດອັບເດດສະຖານະໄດ້";
            }
        } catch (PDOException $e) {
            $error_message = "ຜິດພາດ: " . $e->getMessage();
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$product_filter = isset($_GET['product']) ? (int)$_GET['product'] : 0;

// Build query
$where_conditions = [];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "r.status = :status";
    $params[':status'] = $status_filter;
}

if ($product_filter > 0) {
    $where_conditions[] = "r.product_id = :product_id";
    $params[':product_id'] = $product_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    $query = "SELECT r.*, c.first_name, c.last_name, p.name as product_name, p.price as product_price
              FROM reviews r 
              LEFT JOIN customers c ON r.customer_id = c.id 
              LEFT JOIN products p ON r.product_id = p.id 
              $where_clause 
              ORDER BY r.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error loading reviews: " . $e->getMessage();
    $reviews = [];
}

// Get products for filter
try {
    $query = "SELECT id, name FROM products ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
}

// Get review statistics
try {
    $query = "SELECT 
                COUNT(id) as total_reviews,
                COALESCE(COUNT(CASE WHEN status = 'pending' THEN 1 END), 0) as pending_reviews,
                COALESCE(COUNT(CASE WHEN status = 'approved' THEN 1 END), 0) as approved_reviews,
                COALESCE(COUNT(CASE WHEN status = 'rejected' THEN 1 END), 0) as rejected_reviews,
                COALESCE(AVG(CASE WHEN status = 'approved' THEN rating ELSE NULL END), 0) as avg_rating
              FROM reviews";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ensure we have numeric values
    $stats['total_reviews'] = (int)$stats['total_reviews'];
    $stats['pending_reviews'] = (int)$stats['pending_reviews'];
    $stats['approved_reviews'] = (int)$stats['approved_reviews'];
    $stats['rejected_reviews'] = (int)$stats['rejected_reviews'];
    $stats['avg_rating'] = round(floatval($stats['avg_rating']), 1);
} catch (PDOException $e) {
    error_log("Error fetching review statistics: " . $e->getMessage());
    $stats = ['total_reviews' => 0, 'pending_reviews' => 0, 'approved_reviews' => 0, 'rejected_reviews' => 0, 'avg_rating' => 0];
}
?>

<!DOCTYPE html>
<html lang="lo">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຈັດການຄຳຄິດເຫັນ - ລະບົບຂາຍສິນຄ້າ IT</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Google Fonts - Noto Sans Lao -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Noto Sans Lao', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .main-content {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 2rem 0;
        }

        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: black;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .review-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #dee2e6;
        }

        .review-card.pending {
            border-left-color: #ffc107;
        }

        .review-card.approved {
            border-left-color: #28a745;
        }

        .review-card.rejected {
            border-left-color: #dc3545;
        }

        .rating-stars {
            color: #ffc107;
        }

        .status-badge {
            font-size: 0.8rem;
        }

        .filter-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="main-content">
                    <div class="p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>
                                <i class="fas fa-star me-2"></i>ຈັດການຄຳຄິດເຫັນ
                            </h2>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0"><?php echo isset($stats['total_reviews']) ? $stats['total_reviews'] : '0'; ?></h4>
                                            <small>ຄຳຄິດເຫັນທັງໝົດ</small>
                                        </div>
                                        <i class="fas fa-comments fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0"><?php echo $stats['pending_reviews']; ?></h4>
                                            <small>ລໍຖ້າການອະນຸຍາດ</small>
                                        </div>
                                        <i class="fas fa-clock fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0"><?php echo $stats['approved_reviews']; ?></h4>
                                            <small>ອະນຸຍາດແລ້ວ</small>
                                        </div>
                                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0"><?php echo round($stats['avg_rating'], 1); ?></h4>
                                            <small>ຄະແນນໂດຍສະເລ່ຍ</small>
                                        </div>
                                        <i class="fas fa-star fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Section -->
                        <div class="filter-section">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <label for="status" class="form-label">
                                        <i class="fas fa-filter me-2"></i>ສະຖານະ
                                    </label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">ທັງໝົດ</option>
                                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>ລໍຖ້າການອະນຸຍາດ</option>
                                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>ອະນຸຍາດແລ້ວ</option>
                                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>ປະຕິເສດແລ້ວ</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label for="product" class="form-label">
                                        <i class="fas fa-box me-2"></i>ສິນຄ້າ
                                    </label>
                                    <select class="form-select" id="product" name="product">
                                        <option value="0">ທັງໝົດ</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?php echo $product['id']; ?>"
                                                <?php echo $product_filter == $product['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-search me-2"></i>ກັ່ນຕອງ
                                    </button>
                                    <a href="reviews.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-refresh me-2"></i>ລຶບ
                                    </a>
                                </div>
                            </form>
                        </div>

                        <!-- Success/Error Messages -->
                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Reviews List -->
                        <?php if (empty($reviews)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">ບໍ່ມີຄຳຄິດເຫັນ</h4>
                                <p class="text-muted">ບໍ່ມີຄຳຄິດເຫັນທີ່ກົງກັບການກັ່ນຕອງຂອງທ່ານ</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-card <?php echo $review['status']; ?>">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></h6>
                                                    <small class="text-muted">
                                                        <i class="fas fa-box me-1"></i><?php echo htmlspecialchars($review['product_name']); ?>
                                                        <span class="ms-2">
                                                            <i class="fas fa-calendar me-1"></i><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?>
                                                        </span>
                                                    </small>
                                                </div>
                                                <div>
                                                    <span class="badge bg-<?php echo $review['status'] === 'pending' ? 'warning' : ($review['status'] === 'approved' ? 'success' : 'danger'); ?> status-badge">
                                                        <?php echo $review['status'] === 'pending' ? 'ລໍຖ້າ' : ($review['status'] === 'approved' ? 'ອະນຸຍາດ' : 'ປະຕິເສດ'); ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="rating-stars mb-2">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                                <?php endfor; ?>
                                                <span class="ms-2 text-muted">(<?php echo $review['rating']; ?>/5)</span>
                                            </div>

                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                        </div>

                                        <div class="col-md-4">
                                            <?php if ($review['status'] === 'pending'): ?>
                                                <div class="d-flex gap-2">
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" class="btn btn-success btn-sm"
                                                            onclick="return confirm('ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການອະນຸຍາດຄຳຄິດເຫັນນີ້?')">
                                                            <i class="fas fa-check me-1"></i>ອະນຸຍາດ
                                                        </button>
                                                    </form>

                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <button type="submit" class="btn btn-danger btn-sm"
                                                            onclick="return confirm('ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການປະຕິເສດຄຳຄິດເຫັນນີ້?')">
                                                            <i class="fas fa-times me-1"></i>ປະຕິເສດ
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-muted small">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    ຄຳຄິດເຫັນຖືກ<?php echo $review['status'] === 'approved' ? 'ອະນຸຍາດ' : 'ປະຕິເສດ'; ?>ແລ້ວ
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <!-- Results Summary -->
                            <div class="text-center mt-4">
                                <p class="text-muted">
                                    <i class="fas fa-info-circle me-2"></i>
                                    ພົບຄຳຄິດເຫັນທັງໝົດ <?php echo count($reviews); ?> ລາຍການ
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>
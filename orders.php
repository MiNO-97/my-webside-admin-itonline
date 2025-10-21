<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if customer is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$customer_id = $_SESSION['customer_id'];

// Get orders with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$status_filter = $_GET['status'] ?? '';

$where_conditions = ["o.customer_id = ?"];
$params = [$customer_id];

if ($status_filter) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get total count
$count_query = "SELECT COUNT(*) FROM orders o $where_clause";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_orders = $stmt->fetchColumn();
$total_pages = ceil($total_orders / $limit);

// Get orders
$query = "SELECT o.*, 
          (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
          FROM orders o 
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

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຄໍາສັ່ງຊື້ຂອງຂ້ອຍ - ລະບົບຂາຍສິນຄ້າ IT</title>
    
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
        
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        
        .main-content {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 2rem 0;
        }
        
        .order-card {
            border: 1px solid #e9ecef;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .order-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            padding: 10px 20px;
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, #5a6fd8, #6a4190);
            transform: translateY(-2px);
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-shipped {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-laptop me-2"></i>ລະບົບຂາຍສິນຄ້າ IT
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home me-1"></i>ໜ້າຫຼັກ
                </a>
                <a class="nav-link" href="products.php">
                    <i class="fas fa-box me-1"></i>ສິນຄ້າ
                </a>
                <a class="nav-link" href="cart.php">
                    <i class="fas fa-shopping-cart me-1"></i>ກະຕ່າ
                </a>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['customer_name']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php">ໂປຣໄຟລ໌</a></li>
                        <li><a class="dropdown-item active" href="orders.php">ຄໍາສັ່ງຊື້ຂອງຂ້ອຍ</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">ອອກຈາກລະບົບ</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="main-content">
            <div class="p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-list me-2"></i>ຄໍາສັ່ງຊື້ຂອງຂ້ອຍ</h2>
                    
                    <!-- Filter -->
                    <div class="d-flex gap-2">
                        <select class="form-select" onchange="window.location.href='?status=' + this.value">
                            <option value="">ສະຖານະທັງໝົດ</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>ລໍຖ້າ</option>
                            <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>ກໍາລັງດໍາເນີນການ</option>
                            <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>ສົ່ງແລ້ວ</option>
                            <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>ຈັດສົ່ງແລ້ວ</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>ຍົກເລີກ</option>
                        </select>
                    </div>
                </div>
                
                <?php if (empty($orders)): ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-bag"></i>
                        <h3>ບໍ່ມີຄໍາສັ່ງຊື້</h3>
                        <p class="text-muted">ທ່ານຍັງບໍ່ໄດ້ສັ່ງຊື້ສິນຄ້າໃດໆ</p>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-shopping-bag me-2"></i>ຊື້ສິນຄ້າຕອນນີ້
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($order['order_number']); ?></h6>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                    </small>
                                </div>
                                
                                <div class="col-md-2">
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php 
                                        $status_text = [
                                            'pending' => 'ລໍຖ້າ',
                                            'processing' => 'ກໍາລັງດໍາເນີນການ',
                                            'shipped' => 'ສົ່ງແລ້ວ',
                                            'delivered' => 'ຈັດສົ່ງແລ້ວ',
                                            'cancelled' => 'ຍົກເລີກ'
                                        ];
                                        echo $status_text[$order['status']] ?? $order['status'];
                                        ?>
                                    </span>
                                </div>
                                
                                <div class="col-md-2">
                                    <small class="text-muted">ສິນຄ້າ:</small><br>
                                    <strong><?php echo $order['item_count']; ?> ລາຍການ</strong>
                                </div>
                                
                                <div class="col-md-2">
                                    <small class="text-muted">ວິທີການຊໍາລະ:</small><br>
                                    <strong><?php echo $order['payment_method'] === 'cod' ? 'ຊໍາລະເງິນປາຍທາງ' : 'ໂອນເງິນທະນາຄານ'; ?></strong>
                                </div>
                                
                                <div class="col-md-2">
                                    <small class="text-muted">ລວມ:</small><br>
                                    <strong class="text-primary"><?php echo formatCurrency($order['total_amount']); ?></strong>
                                </div>
                                
                                <div class="col-md-2 text-end">
                                    <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="order_tracking.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-truck"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Order pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>">
                                            <i class="fas fa-chevron-left"></i> ກ່ອນໜ້າ
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>">
                                            ຕໍ່ໄປ <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                    
                    <div class="text-muted text-center mt-3">
                        ສະແດງ <?php echo $offset + 1; ?> ຫາ <?php echo min($offset + $limit, $total_orders); ?> ຈາກ <?php echo $total_orders; ?> ຄໍາສັ່ງຊື້
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html> 
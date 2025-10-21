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

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header("Location: products.php");
    exit();
}

// Get product details with category name
$query = "SELECT p.*, pc.name as category_name 
          FROM products p 
          LEFT JOIN product_categories pc ON p.category_id = pc.id 
          WHERE p.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $product_id);
$stmt->execute();
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: products.php");
    exit();
}

// Get product statistics
$stats_query = "SELECT 
                    COUNT(DISTINCT o.id) as total_orders,
                    SUM(oi.quantity) as total_sold,
                    SUM(oi.total_price) as total_revenue
                FROM order_items oi 
                JOIN orders o ON oi.order_id = o.id 
                WHERE oi.product_id = :product_id AND o.status != 'cancelled'";
$stmt = $db->prepare($stats_query);
$stmt->bindParam(':product_id', $product_id);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get product reviews
$reviews_query = "SELECT r.*, c.first_name, c.last_name 
                  FROM reviews r 
                  JOIN customers c ON r.customer_id = c.id 
                  WHERE r.product_id = :product_id AND r.status = 'approved' 
                  ORDER BY r.created_at DESC 
                  LIMIT 10";
$stmt = $db->prepare($reviews_query);
$stmt->bindParam(':product_id', $product_id);
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get average rating
$rating_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                 FROM reviews 
                 WHERE product_id = :product_id AND status = 'approved'";
$stmt = $db->prepare($rating_query);
$stmt->bindParam(':product_id', $product_id);
$stmt->execute();
$rating_stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<?php $page_title = 'View Product'; ?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="col-md-9 col-lg-10 main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-eye me-2"></i>View Product</h2>
        <div class="btn-group">
            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-warning">
                <i class="fas fa-edit me-2"></i>Edit
            </a>
            <a href="products.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Products
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- Product Details -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-box me-2"></i>Product Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="product-image-container text-center">
                                <?php if ($product['image_url']): ?>
                                    <img src="../uploads/products/<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         class="img-fluid rounded" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <div class="no-image-placeholder">
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                        <p class="text-muted mt-2">No image</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h3 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h3>
                            
                            <div class="row mb-3">
                                <div class="col-sm-6">
                                    <strong>Category:</strong>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
                                </div>
                                <div class="col-sm-6">
                                    <strong>Status:</strong>
                                    <span class="badge <?php echo $product['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo ucfirst($product['status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-sm-6">
                                    <strong>Price:</strong>
                                    <span class="text-primary fw-bold"><?php echo formatCurrency($product['price']); ?></span>
                                </div>
                                <div class="col-sm-6">
                                    <strong>Stock:</strong>
                                    <span class="<?php echo $product['stock_quantity'] <= 10 ? 'text-danger' : 'text-success'; ?> fw-bold">
                                        <?php echo $product['stock_quantity']; ?> units
                                    </span>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Description:</strong>
                                <p class="text-muted mt-1"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                            </div>
                            
                            <div class="row">
                                <div class="col-sm-6">
                                    <strong>Product ID:</strong>
                                    <span class="text-muted">#<?php echo $product['id']; ?></span>
                                </div>
                                <div class="col-sm-6">
                                    <strong>Created:</strong>
                                    <span class="text-muted"><?php echo date('d/m/Y H:i', strtotime($product['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Product Statistics -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Sales Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="stat-card text-center">
                                <div class="stat-icon bg-primary">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <h4 class="stat-value"><?php echo $stats['total_orders'] ?? 0; ?></h4>
                                <p class="stat-label">Total Orders</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card text-center">
                                <div class="stat-icon bg-success">
                                    <i class="fas fa-box"></i>
                                </div>
                                <h4 class="stat-value"><?php echo $stats['total_sold'] ?? 0; ?></h4>
                                <p class="stat-label">Units Sold</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card text-center">
                                <div class="stat-icon bg-warning">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <h4 class="stat-value"><?php echo formatCurrency($stats['total_revenue'] ?? 0); ?></h4>
                                <p class="stat-label">Total Revenue</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reviews and Rating -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-star me-2"></i>Rating & Reviews
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="rating-display">
                            <?php
                            $avg_rating = $rating_stats['avg_rating'] ? round($rating_stats['avg_rating'], 1) : 0;
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $avg_rating) {
                                    echo '<i class="fas fa-star text-warning"></i>';
                                } elseif ($i - $avg_rating < 1) {
                                    echo '<i class="fas fa-star-half-alt text-warning"></i>';
                                } else {
                                    echo '<i class="far fa-star text-warning"></i>';
                                }
                            }
                            ?>
                        </div>
                        <h4 class="mt-2"><?php echo $avg_rating; ?>/5</h4>
                        <p class="text-muted"><?php echo $rating_stats['total_reviews'] ?? 0; ?> reviews</p>
                    </div>
                    
                    <?php if (!empty($reviews)): ?>
                        <div class="reviews-list">
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item border-bottom pb-2 mb-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></strong>
                                            <div class="rating-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star text-warning"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <small class="text-muted"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></small>
                                    </div>
                                    <p class="review-comment mt-1 mb-0"><?php echo htmlspecialchars($review['comment']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-comments fa-2x mb-2"></i>
                            <p>No reviews yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .product-image-container img {
        max-height: 300px;
        object-fit: cover;
    }
    
    .no-image-placeholder {
        padding: 2rem;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .stat-card {
        padding: 1rem;
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        color: white;
        font-size: 1.5rem;
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        color: #6c757d;
        margin-bottom: 0;
    }
    
    .rating-display {
        font-size: 1.5rem;
    }
    
    .reviews-list {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .review-item:last-child {
        border-bottom: none !important;
    }
    
    .rating-stars {
        font-size: 0.8rem;
    }
    
    .review-comment {
        font-size: 0.9rem;
        color: #495057;
    }
</style>

<?php include 'includes/footer.php'; ?> 
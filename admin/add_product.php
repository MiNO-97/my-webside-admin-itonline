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

$message = '';
$error = '';

// Get categories for dropdown
$categories = getCategories($db);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    
    // Validation
    if (empty($name)) {
        $error = 'Product name is required';
    } elseif (empty($description)) {
        $error = 'Product description is required';
    } elseif ($price <= 0) {
        $error = 'Price must be greater than 0';
    } elseif ($stock_quantity < 0) {
        $error = 'Stock quantity cannot be negative';
    } elseif ($category_id <= 0) {
        $error = 'Please select a category';
    } else {
        try {
            // Handle image upload
            $image_url = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/products/';
                
                // Create directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_info = pathinfo($_FILES['image']['name']);
                $extension = strtolower($file_info['extension']);
                
                // Validate file type
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($extension, $allowed_types)) {
                    $error = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.';
                } else {
                    // Generate unique filename
                    $filename = uniqid() . '_' . time() . '.' . $extension;
                    $filepath = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                        $image_url = $filename;
                    } else {
                        $error = 'Failed to upload image';
                    }
                }
            }
            
            if (empty($error)) {
                // Insert product
                $query = "INSERT INTO products (name, description, price, stock_quantity, category_id, image_url, status) 
                          VALUES (:name, :description, :price, :stock_quantity, :category_id, :image_url, :status)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':stock_quantity', $stock_quantity);
                $stmt->bindParam(':category_id', $category_id);
                $stmt->bindParam(':image_url', $image_url);
                $stmt->bindParam(':status', $status);
                
                if ($stmt->execute()) {
                    $product_id = $db->lastInsertId();
                    logActivity($db, $_SESSION['admin_id'], 'employee', 'Added new product', "Product: $name (ID: $product_id)");
                    header("Location: products.php?success=Product added successfully");
                    exit();
                } else {
                    $error = 'Failed to add product';
                }
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<?php $page_title = 'Add New Product'; ?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="col-md-9 col-lg-10 main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-plus me-2"></i>Add New Product</h2>
        <a href="products.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Products
        </a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <!-- Basic Information -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="price" class="form-label">Price *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₭</span>
                                        <input type="number" class="form-control" id="price" name="price" 
                                               step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="stock_quantity" class="form-label">Stock Quantity *</label>
                                    <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" 
                                           min="0" value="<?php echo htmlspecialchars($_POST['stock_quantity'] ?? '0'); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category *</label>
                                    <select class="form-select" id="category_id" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                    <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <!-- Image Upload -->
                        <div class="mb-3">
                            <label for="image" class="form-label">Product Image</label>
                            <div class="image-upload-container">
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <div class="image-preview mt-2" id="imagePreview">
                                    <div class="preview-placeholder">
                                        <i class="fas fa-image"></i>
                                        <p>No image selected</p>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted">Supported formats: JPG, PNG, GIF, WebP. Max size: 5MB</small>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-flex justify-content-end gap-2">
                    <a href="products.php" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
$custom_scripts = '
<script>
    // Image preview functionality
    document.getElementById("image").addEventListener("change", function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById("imagePreview");
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded" style="max-height: 200px;">`;
            };
            reader.readAsDataURL(file);
        } else {
            preview.innerHTML = `
                <div class="preview-placeholder">
                    <i class="fas fa-image"></i>
                    <p>No image selected</p>
                </div>
            `;
        }
    });
</script>

<style>
    .image-upload-container {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
        transition: border-color 0.3s ease;
    }
    
    .image-upload-container:hover {
        border-color: #007bff;
    }
    
    .preview-placeholder {
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .preview-placeholder i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
</style>
';
?>

<?php include 'includes/footer.php'; ?> 
<?php
require_once 'config/database.php';

// Function to create admin account
function createAdminAccount($firstName, $lastName, $email, $phone, $position, $password) {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        echo "Database connection failed!";
        return false;
    }
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if email already exists
    $checkQuery = "SELECT id FROM employees WHERE email = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$email]);
    
    if ($checkStmt->rowCount() > 0) {
        echo "Email already exists in the system!";
        return false;
    }
    
    // Insert new admin account
    $query = "INSERT INTO employees (first_name, last_name, email, phone, position, password, status) 
              VALUES (?, ?, ?, ?, ?, ?, 'active')";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$firstName, $lastName, $email, $phone, $position, $hashedPassword])) {
        echo "Admin account created successfully!<br>";
        echo "Email: $email<br>";
        echo "Password: $password<br>";
        return true;
    } else {
        echo "Error creating admin account!";
        return false;
    }
}

// Example usage - uncomment and modify the details below to create a new admin
/*
createAdminAccount(
    'ຊື່ທີ່ 1',           // First Name
    'ນາມສະກຸນທີ່ 1',      // Last Name
    'admin2@positonline.com', // Email
    '02087654321',           // Phone
    'ຜູ້ບໍລິຫານລະບົບ',    // Position
    'password123'            // Password
);
*/
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ສ້າງບັນຊີຜູ້ບໍລິຫານ - POS IT Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Lao', sans-serif; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="text-center">ສ້າງບັນຊີຜູ້ບໍລິຫານໃໝ່</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="firstName" class="form-label">ຊື່ທີ່ 1</label>
                                <input type="text" class="form-control" id="firstName" name="firstName" required>
                            </div>
                            <div class="mb-3">
                                <label for="lastName" class="form-label">ນາມສະກຸນທີ່ 1</label>
                                <input type="text" class="form-control" id="lastName" name="lastName" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">ອີເມວ</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">ເບີໂທລະສັບ</label>
                                <input type="text" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="mb-3">
                                <label for="position" class="form-label">ຕຳແໜ່ງ</label>
                                <input type="text" class="form-control" id="position" name="position" value="ຜູ້ບໍລິຫານລະບົບ" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">ລະຫັດຜ່ານ</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirmPassword" class="form-label">ຢືນຢັນລະຫັດຜ່ານ</label>
                                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">ສ້າງບັນຊີ</button>
                        </form>
                        
                        <?php
                        if ($_POST) {
                            if ($_POST['password'] !== $_POST['confirmPassword']) {
                                echo '<div class="alert alert-danger mt-3">ລະຫັດຜ່ານບໍ່ກົງກັນ!</div>';
                            } else {
                                createAdminAccount(
                                    $_POST['firstName'],
                                    $_POST['lastName'],
                                    $_POST['email'],
                                    $_POST['phone'],
                                    $_POST['position'],
                                    $_POST['password']
                                );
                            }
                        }
                        ?>
                    </div>
                </div>
                
                <div class="mt-3 text-center">
                    <a href="admin/login.php" class="btn btn-secondary">ກັບໄປໜ້າເຂົ້າສູ່ລະບົບ</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 
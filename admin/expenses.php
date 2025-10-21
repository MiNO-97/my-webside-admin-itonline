<?php
session_start();
require_once('../config/database.php');
require_once('../includes/functions.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['employee_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Database connection failed");
}

$page_title = "Expense Management";
include('includes/header.php');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Expense Management</h1>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addExpenseModal">
            Add New Expense
        </button>
    </div>

    <!-- Expense Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Expenses (This Month)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE MONTH(expense_date) = MONTH(CURRENT_DATE()) AND YEAR(expense_date) = YEAR(CURRENT_DATE()) AND status = 'approved'");
                                $stmt->execute();
                                $result = $stmt->fetch();
                                echo number_format($result['total'], 2) . " ₭";
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Pending Approvals</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM expenses WHERE status = 'pending'");
                                $stmt->execute();
                                $result = $stmt->fetch();
                                echo $result['count'];
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Expenses Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Expense List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="expensesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Title</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Added By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("
                            SELECT e.*, ec.name as category_name, 
                                   CONCAT(emp.first_name, ' ', emp.last_name) as employee_name
                            FROM expenses e
                            LEFT JOIN expense_categories ec ON e.category_id = ec.id
                            LEFT JOIN employees emp ON e.employee_id = emp.id
                            ORDER BY e.expense_date DESC
                        ");
                        $stmt->execute();
                        while ($row = $stmt->fetch()) {
                            $status_class = '';
                            switch ($row['status']) {
                                case 'approved':
                                    $status_class = 'success';
                                    break;
                                case 'pending':
                                    $status_class = 'warning';
                                    break;
                                case 'rejected':
                                    $status_class = 'danger';
                                    break;
                            }
                        ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($row['expense_date'])); ?></td>
                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo number_format($row['amount'], 2) . " ₭"; ?></td>
                                <td><span class="badge badge-<?php echo $status_class; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                <td><?php echo htmlspecialchars($row['employee_name']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info view-expense" data-id="<?php echo $row['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($row['status'] == 'pending'): ?>
                                        <button class="btn btn-sm btn-success approve-expense" data-id="<?php echo $row['id']; ?>">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger reject-expense" data-id="<?php echo $row['id']; ?>">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1" role="dialog" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addExpenseModalLabel">Add New Expense</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addExpenseForm" action="ajax/save_expense.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Category</label>
                        <select class="form-control" name="category_id" required>
                            <?php
                            $stmt = $conn->prepare("SELECT * FROM expense_categories WHERE status = 'active'");
                            $stmt->execute();
                            while ($category = $stmt->fetch()) {
                                echo "<option value='" . $category['id'] . "'>" . htmlspecialchars($category['name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Amount</label>
                        <input type="number" class="form-control" name="amount" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" class="form-control" name="expense_date" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select class="form-control" name="payment_method">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Receipt Image</label>
                        <input type="file" class="form-control-file" name="receipt_image">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#expensesTable').DataTable();

        // Handle form submission
        $('#addExpenseForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    var data = JSON.parse(response);
                    if (data.status === 'success') {
                        alert('Expense added successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                },
                error: function() {
                    alert('Error occurred while saving the expense.');
                }
            });
        });

        // Handle approve/reject actions
        $('.approve-expense, .reject-expense').click(function() {
            var action = $(this).hasClass('approve-expense') ? 'approve' : 'reject';
            var expenseId = $(this).data('id');

            if (confirm('Are you sure you want to ' + action + ' this expense?')) {
                $.post('ajax/update_expense_status.php', {
                    expense_id: expenseId,
                    action: action
                }, function(response) {
                    var data = JSON.parse(response);
                    if (data.status === 'success') {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        });
    });
</script>

<?php include('includes/footer.php'); ?>
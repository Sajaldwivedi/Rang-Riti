<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$user_id = getCurrentUserId();
$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $item_name = trim($_POST['item_name'] ?? '');
                $category = trim($_POST['category'] ?? '');
                $estimated_cost = floatval($_POST['estimated_cost'] ?? 0);
                $actual_cost = !empty($_POST['actual_cost']) ? floatval($_POST['actual_cost']) : null;
                $paid_amount = floatval($_POST['paid_amount'] ?? 0);
                $notes = trim($_POST['notes'] ?? '');

                if (empty($item_name)) {
                    $errors[] = "Item name is required";
                } elseif (empty($category)) { 
                    $errors[] = "Category is required";
                } elseif ($estimated_cost <= 0) {
                    $errors[] = "Estimated cost must be greater than 0";        
                } else {
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO budget_items (user_id, item_name, category, estimated_cost, actual_cost, paid_amount, notes)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$user_id, $item_name, $category, $estimated_cost, $actual_cost, $paid_amount, $notes]);
                        $success = "Budget item added successfully!";
                    } catch (PDOException $e) {
                        $errors[] = "Failed to add budget item. Please try again.";
                    }
                }
                break;

            case 'update':
                $item_id = (int)$_POST['item_id'];
                $actual_cost = floatval($_POST['actual_cost'] ?? 0);
                $paid_amount = floatval($_POST['paid_amount'] ?? 0);
                
                try {
                    $stmt = $pdo->prepare("
                        UPDATE budget_items 
                        SET actual_cost = ?, paid_amount = ?
                        WHERE id = ? AND user_id = ?
                    ");
                    $stmt->execute([$actual_cost, $paid_amount, $item_id, $user_id]);
                    $success = "Budget item updated!";
                } catch (PDOException $e) {
                    $errors[] = "Failed to update budget item.";
                }
                break;

            case 'delete':
                $item_id = (int)$_POST['item_id'];
                
                try {
                    $stmt = $pdo->prepare("DELETE FROM budget_items WHERE id = ? AND user_id = ?");
                    $stmt->execute([$item_id, $user_id]);
                    $success = "Budget item deleted successfully!";
                } catch (PDOException $e) {
                    $errors[] = "Failed to delete budget item.";
                }
                break;
        }
    }
}

// Get budget summary
$budget_summary = getBudgetSummary($pdo, $user_id);

// Get all budget items grouped by category
try {
    $stmt = $pdo->prepare("
        SELECT * FROM budget_items 
        WHERE user_id = ?
        ORDER BY category, item_name
    ");
    $stmt->execute([$user_id]);
    $budget_items = $stmt->fetchAll();

    // Group items by category
    $categorized_items = [];
    foreach ($budget_items as $item) {
        $categorized_items[$item['category']][] = $item;
    }
} catch (PDOException $e) {
    $errors[] = "Failed to load budget items.";
    $categorized_items = [];
}

// Get unique categories for the dropdown
$categories = [
    'Venue',
    'Catering',
    'Decor',
    'Photography',
    'Videography',
    'Attire',
    'Music',
    'Transportation',
    'Invitations',
    'Flowers',
    'Cake',
    'Favors',
    'Other'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Budget - RangRiti</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/budget-guests.css">
</head>
<body>
    <header class="header">
        <nav class="nav-container">
            <a href="index.php" class="logo">RangRiti</a>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="tasks.php">Tasks</a>
                <a href="budget.php" class="active">Budget</a>
                <a href="guests.php">Guests</a>
                <a href="vendors.php">Vendors</a>
            </div>
        </nav>
    </header>

    <main class="budget-page">
        <div class="container">
            <div class="page-header">
                <h1>Wedding Budget Planner</h1>
                <p class="subtitle">Track and manage your wedding expenses with ease</p>
                <button class="btn btn-primary" onclick="showAddBudgetModal()">
                    <i class="fas fa-plus"></i> Add Budget Item
                </button>
            </div>

            <!-- Budget Overview -->
            <div class="budget-overview">
                <div class="budget-card total">
                    <h3>Total Budget</h3>
                    <div class="amount"><?php echo formatCurrency($budget_summary['total_estimated']); ?></div>
                    <p class="subtitle">Estimated total cost</p>
                    <?php if ($budget_summary['total_actual'] > 0): ?>
                        <div class="budget-progress">
                            <div class="progress-bar" style="width: <?php echo min(100, ($budget_summary['total_actual'] / $budget_summary['total_estimated']) * 100); ?>%"></div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="budget-card">
                    <h3>Actual Spent</h3>
                    <div class="amount"><?php echo formatCurrency($budget_summary['total_actual']); ?></div>
                    <p class="subtitle">Current expenses</p>
                </div>
                <div class="budget-card">
                    <h3>Amount Paid</h3>
                    <div class="amount"><?php echo formatCurrency($budget_summary['total_paid']); ?></div>
                    <p class="subtitle">Total payments made</p>
                </div>
                <div class="budget-card">
                    <h3>Remaining</h3>
                    <div class="amount"><?php echo formatCurrency(max(0, $budget_summary['total_estimated'] - $budget_summary['total_actual'])); ?></div>
                    <p class="subtitle">Budget remaining</p>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Budget Items -->
            <div class="budget-items">
                <?php if (empty($categorized_items)): ?>
                    <div class="empty-state">
                        <img src="assets/images/empty-budget.svg" alt="No budget items">
                        <h3>No Budget Items Yet</h3>
                        <p>Start adding items to track your wedding expenses!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($categorized_items as $category => $items): ?>
                        <div class="category-section">
                            <h2><?php echo escape($category); ?></h2>
                            <div class="budget-grid">
                                <?php foreach ($items as $item): ?>
                                    <div class="budget-card item" data-item-id="<?php echo $item['id']; ?>">
                                        <div class="item-header">
                                            <h3><?php echo escape($item['item_name']); ?></h3>
                                            <div class="item-actions">
                                                <button class="btn-icon" onclick="editBudgetItem(<?php echo $item['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-icon" onclick="deleteBudgetItem(<?php echo $item['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="item-details">
                                            <p><strong>Estimated:</strong> <?php echo formatCurrency($item['estimated_cost']); ?></p>
                                            <?php if ($item['actual_cost']): ?>
                                                <p><strong>Actual:</strong> <?php echo formatCurrency($item['actual_cost']); ?></p>
                                            <?php endif; ?>
                                            <p><strong>Paid:</strong> <?php echo formatCurrency($item['paid_amount']); ?></p>
                                            <?php if (!empty($item['notes'])): ?>
                                                <p class="notes"><strong>Notes:</strong> <?php echo escape($item['notes']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Add Budget Item Modal -->
    <div id="addBudgetModal" class="modal">
        <div class="modal-content">
            <button type="button" class="modal-close" onclick="closeAddBudgetModal()">&times;</button>
            <h2>Add Budget Item</h2>
            <form method="POST" id="addBudgetForm">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="item_name">Item Name</label>
                    <input type="text" id="item_name" name="item_name" required>
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo escape($category); ?>"><?php echo escape($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="estimated_cost">Estimated Cost</label>
                    <input type="number" id="estimated_cost" name="estimated_cost" min="0" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="actual_cost">Actual Cost (if known)</label>
                    <input type="number" id="actual_cost" name="actual_cost" min="0" step="0.01">
                </div>

                <div class="form-group">
                    <label for="paid_amount">Amount Paid</label>
                    <input type="number" id="paid_amount" name="paid_amount" min="0" step="0.01" value="0" required>
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeAddBudgetModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Budget Item Modal -->
    <div id="editBudgetModal" class="modal">
        <div class="modal-content">
            <button type="button" class="modal-close" onclick="closeEditBudgetModal()">&times;</button>
            <h2>Edit Budget Item</h2>
            <form method="POST" id="editBudgetForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="item_id" id="edit_item_id">
                
                <div class="form-group">
                    <label for="edit_actual_cost">Actual Cost</label>
                    <input type="number" id="edit_actual_cost" name="actual_cost" min="0" step="0.01">
                </div>

                <div class="form-group">
                    <label for="edit_paid_amount">Amount Paid</label>
                    <input type="number" id="edit_paid_amount" name="paid_amount" min="0" step="0.01" required>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeEditBudgetModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Item</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function showAddBudgetModal() {
            document.getElementById('addBudgetModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeAddBudgetModal() {
            document.getElementById('addBudgetModal').style.display = 'none';
            document.body.style.overflow = '';
            document.getElementById('addBudgetForm').reset();
        }

        function showEditBudgetModal() {
            document.getElementById('editBudgetModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeEditBudgetModal() {
            document.getElementById('editBudgetModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        // Edit budget item
        function editBudgetItem(itemId) {
            document.getElementById('edit_item_id').value = itemId;
            showEditBudgetModal();
        }

        // Delete budget item
        function deleteBudgetItem(itemId) {
            if (confirm('Are you sure you want to delete this budget item?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';

                const itemIdInput = document.createElement('input');
                itemIdInput.type = 'hidden';
                itemIdInput.name = 'item_id';
                itemIdInput.value = itemId;

                form.appendChild(actionInput);
                form.appendChild(itemIdInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addBudgetModal');
            const editModal = document.getElementById('editBudgetModal');
            if (event.target === addModal) {
                closeAddBudgetModal();
            } else if (event.target === editModal) {
                closeEditBudgetModal();
            }
        }
    </script>
</body>
</html>

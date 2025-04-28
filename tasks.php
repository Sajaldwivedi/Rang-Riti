<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$user_id = getCurrentUserId();
$success = '';
$error = '';

// Priority mapping
$priority_map = [
    'low' => 1,
    'medium' => 2,
    'high' => 3
];

$priority_map_reverse = [
    1 => 'low',
    2 => 'medium',
    3 => 'high'
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $task_name = trim($_POST['task_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $due_date = $_POST['due_date'] ?? '';
        $priority_text = $_POST['priority'] ?? 'medium';
        $priority = $priority_map[$priority_text] ?? 2; // Default to medium (2)
        $category = $_POST['category'] ?? 'Other';
        
        if (empty($task_name)) {
            $error = 'Task name is required';
        } else {
            try {
                // First, check if the tasks table exists and has the correct structure
                $check_table = $pdo->query("SHOW TABLES LIKE 'tasks'");
                if ($check_table->rowCount() === 0) {
                    // Create the tasks table if it doesn't exist
                    $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        task_name VARCHAR(255) NOT NULL,
                        description TEXT,
                        due_date DATE,
                        priority INT DEFAULT 2,
                        category VARCHAR(100) DEFAULT 'Other',
                        status ENUM('pending', 'completed') DEFAULT 'pending',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                    )");
                }

                // Now insert the task
                $stmt = $pdo->prepare("
                    INSERT INTO tasks (user_id, task_name, description, due_date, priority, category, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')
                ");
                
                if ($stmt->execute([$user_id, $task_name, $description, $due_date, $priority, $category])) {
                    header('Location: tasks.php?success=1');
                    exit;
                } else {
                    $error = 'Failed to add task: ' . implode(', ', $stmt->errorInfo());
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'update') {
        $task_id = (int)$_POST['task_id'];
        $task_name = trim($_POST['task_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $due_date = $_POST['due_date'] ?? '';
        $priority_text = $_POST['priority'] ?? 'medium';
        $priority = $priority_map[$priority_text] ?? 2; // Default to medium (2)
        $category = $_POST['category'] ?? 'Other';
        
        if (empty($task_name)) {
            $error = 'Task name is required';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE tasks 
                    SET task_name = ?, description = ?, due_date = ?, priority = ?, category = ?
                    WHERE id = ? AND user_id = ?
                ");
                if ($stmt->execute([$task_name, $description, $due_date, $priority, $category, $task_id, $user_id])) {
                    header('Location: tasks.php?success=4');
                    exit;
                } else {
                    $error = 'Failed to update task';
                }
            } catch (PDOException $e) {
                $error = 'Failed to update task';
            }
        }
    } elseif ($action === 'update_status') {
        $task_id = (int)$_POST['task_id'];
        $status = $_POST['status'];
        
        if (!in_array($status, ['pending', 'completed'])) {
            $error = 'Invalid status';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE tasks 
                    SET status = ?
                    WHERE id = ? AND user_id = ?
                ");
                if ($stmt->execute([$status, $task_id, $user_id])) {
                    header('Location: tasks.php?success=3');
                    exit;
                } else {
                    $error = 'Failed to update task status';
                }
            } catch (PDOException $e) {
                $error = 'Failed to update task status';
            }
        }
    } elseif ($action === 'delete') {
        $task_id = (int)$_POST['task_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$task_id, $user_id])) {
                header('Location: tasks.php?success=2');
                exit;
            } else {
                $error = 'Failed to delete task';
            }
        } catch (PDOException $e) {
            $error = 'Failed to delete task';
        }
    }
}

// Handle success messages from redirects
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case '1':
            $success = 'Task added successfully!';
            break;
        case '2':
            $success = 'Task deleted successfully!';
            break;
        case '3':
            $success = 'Task status updated successfully!';
            break;
        case '4':
            $success = 'Task updated successfully!';
            break;
    }
}

// Fetch tasks grouped by status and category
try {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY category, due_date ASC");
    $stmt->execute([$user_id]);
    $tasks = $stmt->fetchAll();
    
    // Group tasks by status and then by category
    $pending_tasks_by_category = [];
    $completed_tasks_by_category = [];
    
    foreach ($tasks as $task) {
        $task['priority'] = $priority_map_reverse[$task['priority']] ?? 'medium';
        $category = $task['category'] ?: 'Other';
        
        if ($task['status'] === 'completed') {
            if (!isset($completed_tasks_by_category[$category])) {
                $completed_tasks_by_category[$category] = [];
            }
            $completed_tasks_by_category[$category][] = $task;
        } else {
            if (!isset($pending_tasks_by_category[$category])) {
                $pending_tasks_by_category[$category] = [];
            }
            $pending_tasks_by_category[$category][] = $task;
        }
    }
} catch (PDOException $e) {
    $error = 'Failed to load tasks';
    $pending_tasks_by_category = [];
    $completed_tasks_by_category = [];
}

// Calculate statistics
$total_tasks = count($tasks);
$completed_tasks = count(array_filter($tasks, function($task) { return $task['status'] === 'completed'; }));
$pending_tasks = $total_tasks - $completed_tasks;
$completion_rate = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Tasks - RangRiti</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/tasks-vendors.css">
</head>
<body>
    <header class="header">
        <nav class="nav-container">
            <a href="index.php" class="logo">RangRiti</a>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="tasks.php" class="active">Tasks</a>
                <a href="budget.php">Budget</a>
                <a href="guests.php">Guests</a>
                <a href="vendors.php">Vendors</a>
            </div>
        </nav>
    </header>

    <div class="tasks-page">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">Wedding Tasks</h1>
            </div>
            <div class="add-task-button">
                <button class="btn btn-primary" onclick="showAddTaskModal()">
                    <i class="fas fa-plus"></i> Add New Task
                </button>
            </div>

            <!-- Task Statistics -->
            <div class="task-statistics">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_tasks; ?></div>
                    <div class="stat-label">Total Tasks</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $pending_tasks; ?></div>
                    <div class="stat-label">Pending Tasks</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $completed_tasks; ?></div>
                    <div class="stat-label">Completed Tasks</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $completion_rate; ?>%</div>
                    <div class="stat-label">Completion Rate</div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Pending Tasks -->
            <div class="tasks-section">
                <h2 class="section-title">Pending Tasks</h2>
                <?php if (empty($pending_tasks_by_category)): ?>
                    <div class="no-tasks">No pending tasks found.</div>
                <?php else: ?>
                    <?php foreach ($pending_tasks_by_category as $category => $tasks): ?>
                        <div class="category-section">
                            <h3 class="category-title"><?php echo htmlspecialchars($category); ?></h3>
                            <div class="task-cards">
                                <?php foreach ($tasks as $task): ?>
                                    <div class="task-card <?php echo $task['status'] === 'completed' ? 'completed' : ''; ?>">
                                        <div class="task-header">
                                            <h4 class="task-name"><?php echo htmlspecialchars($task['task_name']); ?></h4>
                                            <div class="task-actions">
                                                <button class="btn-icon <?php echo $task['status'] === 'completed' ? 'active' : ''; ?>" 
                                                        onclick="toggleTaskStatus(<?php echo $task['id']; ?>, '<?php echo $task['status']; ?>')"
                                                        title="<?php echo $task['status'] === 'completed' ? 'Mark Incomplete' : 'Mark Complete'; ?>">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn-icon" onclick="editTask(<?php echo $task['id']; ?>, '<?php echo htmlspecialchars($task['task_name']); ?>', '<?php echo htmlspecialchars($task['description']); ?>', '<?php echo $task['due_date']; ?>', '<?php echo $task['priority']; ?>', '<?php echo htmlspecialchars($task['category']); ?>')" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-icon" onclick="deleteTask(<?php echo $task['id']; ?>)" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="task-details">
                                            <?php if (!empty($task['description'])): ?>
                                                <p class="task-description"><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                                            <?php endif; ?>
                                            <div class="task-meta">
                                                <?php if (!empty($task['due_date'])): ?>
                                                    <span class="due-date">
                                                        <i class="far fa-calendar"></i>
                                                        <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span class="priority priority-<?php echo strtolower($task['priority']); ?>">
                                                    <i class="fas fa-flag"></i>
                                                    <?php echo ucfirst($task['priority']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Completed Tasks -->
            <div class="tasks-section completed-section">
                <h2 class="section-title">Completed Tasks</h2>
                <?php if (empty($completed_tasks_by_category)): ?>
                    <div class="no-tasks">No completed tasks found.</div>
                <?php else: ?>
                    <?php foreach ($completed_tasks_by_category as $category => $tasks): ?>
                        <div class="category-section">
                            <h3 class="category-title"><?php echo htmlspecialchars($category); ?></h3>
                            <div class="task-cards">
                                <?php foreach ($tasks as $task): ?>
                                    <div class="task-card completed">
                                        <div class="task-header">
                                            <h4 class="task-name"><?php echo htmlspecialchars($task['task_name']); ?></h4>
                                            <div class="task-actions">
                                                <button class="btn-icon active" 
                                                        onclick="toggleTaskStatus(<?php echo $task['id']; ?>, '<?php echo $task['status']; ?>')"
                                                        title="Mark Incomplete">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn-icon" onclick="deleteTask(<?php echo $task['id']; ?>)" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="task-details">
                                            <?php if (!empty($task['description'])): ?>
                                                <p class="task-description"><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                                            <?php endif; ?>
                                            <div class="task-meta">
                                                <?php if (!empty($task['due_date'])): ?>
                                                    <span class="due-date">
                                                        <i class="far fa-calendar"></i>
                                                        <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span class="priority priority-<?php echo strtolower($task['priority']); ?>">
                                                    <i class="fas fa-flag"></i>
                                                    <?php echo ucfirst($task['priority']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Task Modal -->
    <div id="addTaskModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Task</h2>
                <button class="close-modal" onclick="closeAddTaskModal()">&times;</button>
            </div>
            <form id="addTaskForm" action="tasks.php" method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="task_name">Task Name</label>
                    <input type="text" id="task_name" name="task_name" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="due_date">Due Date</label>
                    <input type="date" id="due_date" name="due_date" required>
                </div>

                <div class="form-group">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <option value="Ceremony">Ceremony</option>
                        <option value="Reception">Reception</option>
                        <option value="Vendors">Vendors</option>
                        <option value="Attire">Attire</option>
                        <option value="Decor">Decor</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeAddTaskModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Task</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Task Modal -->
    <div id="editTaskModal" class="modal">
        <div class="modal-content">
            <button type="button" class="modal-close" onclick="closeEditTaskModal()">&times;</button>
            <h2>Edit Task</h2>
            <form method="POST" id="editTaskForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="task_id" id="edit_task_id">
                
                <div class="form-group">
                    <label for="edit_task_name">Task Name</label>
                    <input type="text" id="edit_task_name" name="task_name" required>
                </div>

                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_due_date">Due Date</label>
                    <input type="date" id="edit_due_date" name="due_date" required>
                </div>

                <div class="form-group">
                    <label for="edit_priority">Priority</label>
                    <select id="edit_priority" name="priority" required>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_category">Category</label>
                    <select id="edit_category" name="category" required>
                        <option value="Ceremony">Ceremony</option>
                        <option value="Reception">Reception</option>
                        <option value="Vendors">Vendors</option>
                        <option value="Attire">Attire</option>
                        <option value="Decor">Decor</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeEditTaskModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Task</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function showAddTaskModal() {
            document.getElementById('addTaskModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeAddTaskModal() {
            document.getElementById('addTaskModal').style.display = 'none';
            document.body.style.overflow = '';
            document.getElementById('addTaskForm').reset();
        }

        function showEditTaskModal() {
            document.getElementById('editTaskModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeEditTaskModal() {
            document.getElementById('editTaskModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        // Edit task
        function editTask(id, name, description, dueDate, priority, category) {
            document.getElementById('edit_task_id').value = id;
            document.getElementById('edit_task_name').value = name;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_due_date').value = dueDate;
            document.getElementById('edit_priority').value = priority;
            document.getElementById('edit_category').value = category;
            showEditTaskModal();
        }

        // Delete task
        function deleteTask(taskId) {
            if (confirm('Are you sure you want to delete this task?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';

                const taskIdInput = document.createElement('input');
                taskIdInput.type = 'hidden';
                taskIdInput.name = 'task_id';
                taskIdInput.value = taskId;

                form.appendChild(actionInput);
                form.appendChild(taskIdInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Toggle task status
        function toggleTaskStatus(taskId, currentStatus) {
            const newStatus = currentStatus === 'completed' ? 'pending' : 'completed';
            
            const form = document.createElement('form');
            form.method = 'POST';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'update_status';

            const taskIdInput = document.createElement('input');
            taskIdInput.type = 'hidden';
            taskIdInput.name = 'task_id';
            taskIdInput.value = taskId;

            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = newStatus;

            form.appendChild(actionInput);
            form.appendChild(taskIdInput);
            form.appendChild(statusInput);
            document.body.appendChild(form);
            form.submit();
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addTaskModal');
            const editModal = document.getElementById('editTaskModal');
            if (event.target === addModal) {
                closeAddTaskModal();
            } else if (event.target === editModal) {
                closeEditTaskModal();
            }
        }
    </script>
</body>
</html>

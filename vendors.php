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
                $name = trim($_POST['name'] ?? '');
                $service_type = trim($_POST['service_type'] ?? '');
                $contact_person = trim($_POST['contact_person'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $website = trim($_POST['website'] ?? '');
                $budget = trim($_POST['budget'] ?? '');
                $address = trim($_POST['address'] ?? '');
                $notes = trim($_POST['notes'] ?? '');

                if (empty($name)) {
                    $errors[] = "Vendor name is required";
                } elseif (empty($service_type)) {
                    $errors[] = "Service type is required";
                } else {
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO vendors (user_id, name, service_type, contact_person, email, phone, website, budget, address, notes)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$user_id, $name, $service_type, $contact_person, $email, $phone, $website, $budget, $address, $notes]);
                        $success = "Vendor added successfully!";
                    } catch (PDOException $e) {
                        $errors[] = "Failed to add vendor. Please try again.";
                    }
                }
                break;

            case 'edit':
                $vendor_id = (int)$_POST['vendor_id'];
                $name = trim($_POST['name'] ?? '');
                $service_type = trim($_POST['service_type'] ?? '');
                $contact_person = trim($_POST['contact_person'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $website = trim($_POST['website'] ?? '');
                $budget = trim($_POST['budget'] ?? '');
                $address = trim($_POST['address'] ?? '');
                $notes = trim($_POST['notes'] ?? '');

                if (empty($name)) {
                    $errors[] = "Vendor name is required";
                } elseif (empty($service_type)) {
                    $errors[] = "Service type is required";
                } else {
                    try {
                        $stmt = $pdo->prepare("
                            UPDATE vendors 
                            SET name = ?, service_type = ?, contact_person = ?, email = ?, phone = ?, website = ?, budget = ?, address = ?, notes = ?
                            WHERE id = ? AND user_id = ?
                        ");
                        $stmt->execute([$name, $service_type, $contact_person, $email, $phone, $website, $budget, $address, $notes, $vendor_id, $user_id]);
                        $success = "Vendor updated successfully!";
                    } catch (PDOException $e) {
                        $errors[] = "Failed to update vendor. Please try again.";
                    }
                }
                break;

            case 'delete':
                $vendor_id = (int)$_POST['vendor_id'];
                
                try {
                    $stmt = $pdo->prepare("DELETE FROM vendors WHERE id = ? AND user_id = ?");
                    $stmt->execute([$vendor_id, $user_id]);
                    $success = "Vendor removed successfully!";
                } catch (PDOException $e) {
                    $errors[] = "Failed to remove vendor.";
                }
                break;
        }
    }
}

// Get all vendors grouped by service type
try {
    $stmt = $pdo->prepare("
        SELECT * FROM vendors 
        WHERE user_id = ?
        ORDER BY service_type, name
    ");
    $stmt->execute([$user_id]);
    $vendors = $stmt->fetchAll();

    // Group vendors by service type
    $categorized_vendors = [];
    foreach ($vendors as $vendor) {
        $categorized_vendors[$vendor['service_type']][] = $vendor;
    }
} catch (PDOException $e) {
    $errors[] = "Failed to load vendors.";
    $categorized_vendors = [];
}

// Service types for dropdown
$service_types = [
    'Venue',
    'Catering',
    'Photography',
    'Videography',
    'Florist',
    'Music',
    'Cake',
    'Decor',
    'Transportation',
    'Hair & Makeup',
    'Wedding Planner',
    'Other'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendors - RangRiti</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/tasks-vendors.css">
    <style>
        .vendors-page {
            padding: 6rem 0 2rem;
            background-color: #fff;
            min-height: calc(100vh - 60px);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-right {
            margin-left: auto;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: #2c3e50;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: #ff6b6b;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            color: #2c3e50;
            font-size: 2.5rem;
            margin: 0;
            position: relative;
            display: inline-block;
        }

        .page-title:after {
            content: '';
            display: block;
            width: 100px;
            height: 3px;
            background: linear-gradient(to right, rgba(255, 107, 107, 0.1), rgba(255, 107, 107, 0.5), rgba(255, 107, 107, 0.1));
            margin: 0.5rem auto 0;
        }

        .add-vendor-button {
            text-align: center;
            margin-bottom: 2rem;
        }

        .vendor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .vendor-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #eee;
        }

        .vendor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .vendor-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .vendor-name {
            font-size: 1.25rem;
            color: #2c3e50;
            margin: 0;
            font-weight: 600;
        }

        .vendor-actions {
            display: flex;
            gap: 0.5rem;
        }

        .vendor-service {
            color: #ff6b6b;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .vendor-service i {
            color: #ff6b6b;
        }

        .vendor-contact {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: #666;
            font-size: 0.9rem;
        }

        .contact-item i {
            color: #ff6b6b;
            width: 16px;
        }

        .vendor-notes {
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #666;
            line-height: 1.5;
        }

        .service-category {
            margin-top: 2rem;
        }

        .category-title {
            font-family: 'Playfair Display', serif;
            color: #2c3e50;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid rgba(255, 107, 107, 0.1);
        }

        .empty-vendors {
            text-align: center;
            padding: 3rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 2rem 0;
        }

        .empty-vendors h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .empty-vendors p {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }

        .btn-primary {
            background: #ff6b6b;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-size: 1rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(255, 107, 107, 0.1);
        }

        .btn-primary:hover {
            background: #ff5252;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(255, 107, 107, 0.2);
        }

        .btn-icon {
            background: none;
            border: none;
            padding: 0.5rem;
            cursor: pointer;
            color: #6c757d;
            transition: color 0.3s ease;
        }

        .btn-icon:hover {
            color: #ff6b6b;
        }

        .vendor-budget {
            color: #28a745;
            font-weight: 500;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="nav-container">
            <a href="dashboard.php" class="logo">RangRiti</a>
            <div class="nav-right">
                <nav class="nav-links">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="tasks.php">Tasks</a>
                    <a href="budget.php">Budget</a>
                    <a href="guests.php">Guests</a>
                    <a href="vendors.php" class="active">Vendors</a>
                </nav>
            </div>
        </div>
    </header>

    <main class="vendors-page">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">Wedding Vendors</h1>
            </div>

            <div class="add-vendor-button">
                <button class="btn btn-primary" onclick="showAddVendorModal()">
                    <i class="fas fa-plus"></i> Add New Vendor
                </button>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger"><?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?></div>
            <?php endif; ?>

            <?php if (empty($categorized_vendors)): ?>
                <div class="empty-vendors">
                    <h3>No Vendors Added Yet</h3>
                    <p>Start adding vendors to keep track of all your wedding service providers.</p>
                </div>
            <?php else: ?>
                <?php foreach ($service_types as $service_type): ?>
                    <?php if (!empty($categorized_vendors[$service_type])): ?>
                        <div class="service-category">
                            <h2 class="category-title"><?php echo htmlspecialchars($service_type); ?></h2>
                            <div class="vendor-grid">
                                <?php foreach ($categorized_vendors[$service_type] as $vendor): ?>
                                    <div class="vendor-card">
                                        <div class="vendor-header">
                                            <h3 class="vendor-name"><?php echo htmlspecialchars($vendor['name']); ?></h3>
                                            <div class="vendor-actions">
                                                <button class="btn-icon" onclick="editVendor(<?php echo $vendor['id']; ?>)" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-icon" onclick="deleteVendor(<?php echo $vendor['id']; ?>)" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="vendor-service">
                                            <i class="fas fa-tag"></i>
                                            <?php echo htmlspecialchars($vendor['service_type']); ?>
                                        </div>
                                        
                                        <?php if (!empty($vendor['budget'])): ?>
                                            <div class="vendor-budget">
                                                <i class="fas fa-rupee-sign"></i> <?php echo number_format($vendor['budget']); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="vendor-contact">
                                            <?php if (!empty($vendor['contact_person'])): ?>
                                                <div class="contact-item">
                                                    <i class="fas fa-user"></i>
                                                    <?php echo htmlspecialchars($vendor['contact_person']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($vendor['email'])): ?>
                                                <div class="contact-item">
                                                    <i class="fas fa-envelope"></i>
                                                    <?php echo htmlspecialchars($vendor['email']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($vendor['phone'])): ?>
                                                <div class="contact-item">
                                                    <i class="fas fa-phone"></i>
                                                    <?php echo htmlspecialchars($vendor['phone']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($vendor['website'])): ?>
                                                <div class="contact-item">
                                                    <i class="fas fa-globe"></i>
                                                    <a href="<?php echo htmlspecialchars($vendor['website']); ?>" target="_blank" rel="noopener noreferrer">Visit Website</a>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($vendor['address'])): ?>
                                                <div class="contact-item">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <?php echo htmlspecialchars($vendor['address']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (!empty($vendor['notes'])): ?>
                                            <div class="vendor-notes">
                                                <?php echo nl2br(htmlspecialchars($vendor['notes'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Add Vendor Modal -->
    <div id="addVendorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Vendor</h2>
                <button class="close-modal" onclick="closeAddVendorModal()">&times;</button>
            </div>
            <form id="addVendorForm" action="vendors.php" method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="name">Vendor Name</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="service_type">Service Type</label>
                    <select id="service_type" name="service_type" required>
                        <option value="">Select a service type</option>
                        <?php foreach ($service_types as $type): ?>
                            <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="contact_person">Contact Person</label>
                    <input type="text" id="contact_person" name="contact_person">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone">
                </div>

                <div class="form-group">
                    <label for="website">Website</label>
                    <input type="url" id="website" name="website" placeholder="https://">
                </div>

                <div class="form-group">
                    <label for="budget">Budget</label>
                    <input type="number" id="budget" name="budget">
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeAddVendorModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Vendor</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function showAddVendorModal() {
            document.getElementById('addVendorModal').style.display = 'flex';
        }

        function closeAddVendorModal() {
            document.getElementById('addVendorModal').style.display = 'none';
        }

        // Delete vendor
        function deleteVendor(vendorId) {
            if (confirm('Are you sure you want to remove this vendor?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';

                const vendorIdInput = document.createElement('input');
                vendorIdInput.type = 'hidden';
                vendorIdInput.name = 'vendor_id';
                vendorIdInput.value = vendorId;

                form.appendChild(actionInput);
                form.appendChild(vendorIdInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addVendorModal');
            if (event.target === modal) {
                closeAddVendorModal();
            }
        }

        // Website URL validation
        document.getElementById('website').addEventListener('blur', function() {
            let url = this.value.trim();
            if (url && !url.match(/^https?:\/\//)) {
                this.value = 'https://' + url;
            }
        });
    </script>
</body>
</html>

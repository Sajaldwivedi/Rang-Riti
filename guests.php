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
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $plus_ones = (int)($_POST['plus_ones'] ?? 0);
                $dietary_restrictions = trim($_POST['dietary_restrictions'] ?? '');
                $notes = trim($_POST['notes'] ?? '');

                if (empty($name)) {
                    $errors[] = "Guest name is required";
                } else {
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO guests (user_id, name, email, phone, plus_ones, dietary_restrictions, notes)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$user_id, $name, $email, $phone, $plus_ones, $dietary_restrictions, $notes]);
                        $success = "Guest added successfully!";
                    } catch (PDOException $e) {
                        $errors[] = "Failed to add guest. Please try again.";
                    }
                }
                break;

            case 'update_rsvp':
                $guest_id = (int)$_POST['guest_id'];
                $rsvp_status = $_POST['rsvp_status'];
                
                try {
                    $stmt = $pdo->prepare("
                        UPDATE guests 
                        SET rsvp_status = ?
                        WHERE id = ? AND user_id = ?
                    ");
                    $stmt->execute([$rsvp_status, $guest_id, $user_id]);
                    $success = "RSVP status updated!";
                } catch (PDOException $e) {
                    $errors[] = "Failed to update RSVP status.";
                }
                break;

            case 'delete':
                $guest_id = (int)$_POST['guest_id'];
                
                try {
                    $stmt = $pdo->prepare("DELETE FROM guests WHERE id = ? AND user_id = ?");
                    $stmt->execute([$guest_id, $user_id]);
                    $success = "Guest removed successfully!";
                } catch (PDOException $e) {
                    $errors[] = "Failed to remove guest.";
                }
                break;
        }
    }
}

// Get guest summary
$guest_summary = getGuestCountSummary($pdo, $user_id);

// Get all guests
try {
    $stmt = $pdo->prepare("
        SELECT * FROM guests 
        WHERE user_id = ?
        ORDER BY name ASC
    ");
    $stmt->execute([$user_id]);
    $guests = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = "Failed to load guest list.";
    $guests = [];
}

// Calculate total guest count including plus ones
$total_guests_with_plus_ones = array_reduce($guests, function($carry, $guest) {
    return $carry + 1 + ($guest['plus_ones'] ?? 0);
}, 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest List - RangRiti</title>
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
                <a href="budget.php">Budget</a>
                <a href="guests.php" class="active">Guests</a>
                <a href="vendors.php">Vendors</a>
            </div>
        </nav>
    </header>

    <main class="guests-page">
        <div class="container">
            <div class="page-header">
                <h1>Your Wedding Guest List</h1>
                <p class="subtitle">Keep track of your special guests and their responses</p>
                <button class="btn btn-primary" onclick="showAddGuestModal()">
                    <i class="fas fa-plus"></i> Add Guest
                </button>
            </div>

            <!-- Guest Summary Cards -->
            <div class="budget-overview">
                <div class="budget-card">
                    <h3>Total Guests</h3>
                    <div class="amount"><?php echo $total_guests_with_plus_ones; ?></div>
                    <p class="subtitle">Including plus ones</p>
                </div>
                <div class="budget-card">
                    <h3>Confirmed</h3>
                    <div class="amount"><?php echo $guest_summary['confirmed'] ?? 0; ?></div>
                    <p class="subtitle">RSVP accepted</p>
                </div>
                <div class="budget-card">
                    <h3>Pending</h3>
                    <div class="amount"><?php echo $guest_summary['pending'] ?? 0; ?></div>
                    <p class="subtitle">Awaiting response</p>
                </div>
                <div class="budget-card">
                    <h3>Declined</h3>
                    <div class="amount"><?php echo $guest_summary['declined'] ?? 0; ?></div>
                    <p class="subtitle">Cannot attend</p>
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

            <!-- Guest List -->
            <div class="guest-list">
                <?php if (empty($guests)): ?>
                    <div class="empty-state">
                        <img src="assets/images/empty-guests.svg" alt="No guests">
                        <h3>No Guests Added Yet</h3>
                        <p>Start adding guests to your wedding list!</p>
                    </div>
                <?php else: ?>
                    <div class="guest-grid">
                        <?php foreach ($guests as $guest): ?>
                            <div class="guest-card <?php echo $guest['rsvp_status']; ?>">
                                <div class="guest-header">
                                    <h3 class="guest-name"><?php echo escape($guest['name']); ?></h3>
                                    <div class="guest-actions">
                                        <div class="rsvp-actions">
                                            <button class="btn-icon <?php echo $guest['rsvp_status'] === 'confirmed' ? 'active' : ''; ?>" 
                                                    onclick="updateRSVPStatus(<?php echo $guest['id']; ?>, 'confirmed')" 
                                                    title="Attending">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn-icon <?php echo $guest['rsvp_status'] === 'declined' ? 'active' : ''; ?>" 
                                                    onclick="updateRSVPStatus(<?php echo $guest['id']; ?>, 'declined')" 
                                                    title="Not Attending">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <button class="btn-icon" onclick="deleteGuest(<?php echo $guest['id']; ?>)" title="Remove Guest">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="guest-info">
                                    <?php if (!empty($guest['email'])): ?>
                                        <div class="info-row">
                                            <i class="fas fa-envelope"></i>
                                            <?php echo escape($guest['email']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($guest['phone'])): ?>
                                        <div class="info-row">
                                            <i class="fas fa-phone"></i>
                                            <?php echo escape($guest['phone']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($guest['plus_ones'] > 0): ?>
                                        <div class="info-row">
                                            <i class="fas fa-user-plus"></i>
                                            Plus <?php echo $guest['plus_ones']; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($guest['dietary_restrictions'])): ?>
                                        <div class="info-row">
                                            <i class="fas fa-utensils"></i>
                                            <?php echo escape($guest['dietary_restrictions']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="rsvp-status">
                                    <?php
                                        $status_text = 'Awaiting RSVP';
                                        $status_class = 'pending';
                                        if ($guest['rsvp_status'] === 'confirmed') {
                                            $status_text = 'Attending';
                                            $status_class = 'confirmed';
                                        } elseif ($guest['rsvp_status'] === 'declined') {
                                            $status_text = 'Not Attending';
                                            $status_class = 'declined';
                                        }
                                    ?>
                                    <span class="status <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Add Guest Modal -->
    <div id="addGuestModal" class="modal">
        <div class="modal-content">
            <button type="button" class="modal-close" onclick="closeAddGuestModal()">&times;</button>
            <h2>Add New Guest</h2>
            <form method="POST" id="addGuestForm">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="name">Guest Name</label>
                    <input type="text" id="name" name="name" required>
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
                    <label for="plus_ones">Plus Ones</label>
                    <input type="number" id="plus_ones" name="plus_ones" min="0" value="0">
                </div>

                <div class="form-group">
                    <label for="dietary_restrictions">Dietary Restrictions</label>
                    <textarea id="dietary_restrictions" name="dietary_restrictions" rows="2"></textarea>
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="2"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeAddGuestModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Guest</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function showAddGuestModal() {
            document.getElementById('addGuestModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeAddGuestModal() {
            document.getElementById('addGuestModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        // Update RSVP status
        function updateRSVPStatus(guestId, status) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'update_rsvp';

            const guestIdInput = document.createElement('input');
            guestIdInput.type = 'hidden';
            guestIdInput.name = 'guest_id';
            guestIdInput.value = guestId;

            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'rsvp_status';
            statusInput.value = status;

            form.appendChild(actionInput);
            form.appendChild(guestIdInput);
            form.appendChild(statusInput);
            document.body.appendChild(form);
            form.submit();
        }

        // Delete guest
        function deleteGuest(guestId) {
            if (confirm('Are you sure you want to remove this guest?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';

                const guestIdInput = document.createElement('input');
                guestIdInput.type = 'hidden';
                guestIdInput.name = 'guest_id';
                guestIdInput.value = guestId;

                form.appendChild(actionInput);
                form.appendChild(guestIdInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addGuestModal');
            if (event.target === modal) {
                closeAddGuestModal();
            }
        }
    </script>
</body>
</html>

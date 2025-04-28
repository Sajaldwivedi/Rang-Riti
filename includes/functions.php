<?php

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect if user is not logged in
 * @param string $redirect_url Optional URL to redirect to
 */
function requireLogin($redirect_url = 'login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect_url");
        exit;
    }
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user's full name
 * @return string|null
 */
function getCurrentUserName() {
    return $_SESSION['full_name'] ?? null;
}

/**
 * Sanitize output
 * @param string $str
 * @return string
 */
function escape($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format date for display
 * @param string $date
 * @return string
 */
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

/**
 * Format currency
 * @param float $amount
 * @return string
 */
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Get user's wedding date
 * @param PDO $pdo
 * @param int $user_id
 * @return string|null
 */
function getWeddingDate($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT wedding_date FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result ? $result['wedding_date'] : null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Calculate days until wedding
 * @param string $wedding_date
 * @return int
 */
function daysUntilWedding($wedding_date) {
    $wedding = new DateTime($wedding_date);
    $today = new DateTime('today');
    $interval = $today->diff($wedding);
    return $interval->days;
}

/**
 * Get task completion percentage
 * @param PDO $pdo
 * @param int $user_id
 * @return float
 */
function getTaskCompletionPercentage($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
            FROM tasks 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        if ($result['total'] > 0) {
            return round(($result['completed'] / $result['total']) * 100, 1);
        }
        return 0;
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get budget summary
 * @param PDO $pdo
 * @param int $user_id
 * @return array
 */
function getBudgetSummary($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                SUM(estimated_cost) as total_estimated,
                SUM(actual_cost) as total_actual,
                SUM(paid_amount) as total_paid
            FROM budget_items 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return [
            'total_estimated' => 0,
            'total_actual' => 0,
            'total_paid' => 0
        ];
    }
}

/**
 * Get guest count summary
 * @param PDO $pdo
 * @param int $user_id
 * @return array
 */
function getGuestCountSummary($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_guests,
                SUM(CASE WHEN rsvp_status = 'attending' THEN 1 ELSE 0 END) as attending,
                SUM(CASE WHEN rsvp_status = 'not_attending' THEN 1 ELSE 0 END) as not_attending,
                SUM(CASE WHEN rsvp_status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM guests 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return [
            'total_guests' => 0,
            'attending' => 0,
            'not_attending' => 0,
            'pending' => 0
        ];
    }
}
?>

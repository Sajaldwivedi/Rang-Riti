<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Require login
requireLogin();

$user_id = getCurrentUserId();
$user_name = getCurrentUserName();

// Get wedding date and days until wedding
$wedding_date = getWeddingDate($pdo, $user_id);
$days_until = $wedding_date ? daysUntilWedding($wedding_date) : null;

// Get task completion percentage
$task_completion = getTaskCompletionPercentage($pdo, $user_id);

// Get budget summary
$budget_summary = getBudgetSummary($pdo, $user_id);

// Get guest count summary
$guest_summary = getGuestCountSummary($pdo, $user_id);

// Get upcoming tasks
try {
    $stmt = $pdo->prepare("
        SELECT * FROM tasks 
        WHERE user_id = ? AND status != 'completed'
        ORDER BY due_date ASC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $upcoming_tasks = $stmt->fetchAll();
} catch (PDOException $e) {
    $upcoming_tasks = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - RangRiti</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Add CSS Variables */
        :root {
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            --card-shadow-hover: 0 15px 35px rgba(0, 0, 0, 0.1);
            --card-border-radius: 20px;
            --transition-speed: 0.3s;
        }

        /* Header and Navigation Styles */
        .header {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            transition: color var(--transition-speed) ease;
        }

        .logo:hover {
            color: var(--secondary-color);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 500;
            transition: color var(--transition-speed) ease;
        }

        .nav-links a:hover {
            color: var(--primary-color);
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-menu span {
            color: var(--text-color);
            font-weight: 500;
        }

        /* Dashboard Layout */
        .dashboard {
            padding: 2rem;
            margin-top: 80px;
            min-height: calc(100vh - 80px);
            background: linear-gradient(135deg, var(--light-bg) 0%, #fff 100%);
            position: relative;
            overflow: hidden;
        }

        .dashboard-header {
            max-width: 1200px;
            margin: 2rem auto;
            text-align: center;
            padding: 2.5rem;
            background: white;
            border-radius: var(--card-border-radius);
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
            z-index: 1;
            animation: slideDown 0.5s ease-out;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        }

        .dashboard-header h1 {
            font-size: 2.5rem;
            color: var(--text-color);
            margin-bottom: 1rem;
            font-family: 'Playfair Display', serif;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: fadeIn 0.5s ease-out 0.2s both;
        }

        .wedding-countdown {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--light-bg);
            border-radius: 30px;
            color: var(--primary-color);
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(255, 140, 148, 0.1);
            animation: pulse 2s infinite, fadeIn 0.5s ease-out 0.4s both;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
            position: relative;
            z-index: 1;
        }

        .dashboard-card {
            background: white;
            padding: 2rem;
            border-radius: var(--card-border-radius);
            box-shadow: var(--card-shadow);
            transition: all var(--transition-speed) ease;
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.5s ease-out both;
        }

        .dashboard-card:nth-child(1) { animation-delay: 0.2s; }
        .dashboard-card:nth-child(2) { animation-delay: 0.4s; }
        .dashboard-card:nth-child(3) { animation-delay: 0.6s; }
        .dashboard-card:nth-child(4) { animation-delay: 0.8s; }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255, 140, 148, 0.03), rgba(255, 182, 185, 0.03));
            opacity: 0;
            transition: opacity var(--transition-speed) ease;
        }

        .dashboard-card:hover::before {
            opacity: 1;
        }

        .task-item {
            animation: slideIn 0.3s ease-out both;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .task-item:nth-child(1) { animation-delay: 0.1s; }
        .task-item:nth-child(2) { animation-delay: 0.2s; }
        .task-item:nth-child(3) { animation-delay: 0.3s; }
        .task-item:nth-child(4) { animation-delay: 0.4s; }
        .task-item:nth-child(5) { animation-delay: 0.5s; }

        .task-status {
            position: relative;
            overflow: hidden;
        }

        .task-status::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: currentColor;
            opacity: 0.1;
            border-radius: inherit;
        }

        .budget-item, .guest-stat {
            position: relative;
            overflow: hidden;
        }

        .budget-item::after, .guest-stat::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .budget-item:hover::after, .guest-stat:hover::after {
            transform: translateX(100%);
        }

        .btn-outline {
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn-outline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--primary-color);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform var(--transition-speed) ease;
            z-index: -1;
        }

        .btn-outline:hover::before {
            transform: scaleX(1);
            transform-origin: left;
        }

        /* Enhanced scrollbar */
        .task-list::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, var(--primary-color), var(--secondary-color));
        }

        /* Improved mobile responsiveness */
        @media (max-width: 768px) {
            .dashboard-header {
                margin: 1rem;
                padding: 1.5rem;
            }

            .dashboard-grid {
                padding: 0 1rem;
            }

            .dashboard-card {
                padding: 1.5rem;
            }

            .guest-summary {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .guest-summary {
                grid-template-columns: 1fr;
            }

            .dashboard-header h1 {
                font-size: 1.8rem;
            }

            .wedding-countdown {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }

        .dashboard {
            padding: 2rem;
            margin-top: 80px;
            min-height: calc(100vh - 80px);
            background: linear-gradient(135deg, var(--light-bg) 0%, #fff 100%);
        }

        .dashboard-header {
            max-width: 1200px;
            margin: 0 auto 2rem;
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .dashboard-header h1 {
            font-size: 2.5rem;
            color: var(--text-color);
            margin-bottom: 1rem;
            font-family: 'Playfair Display', serif;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .wedding-countdown {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--light-bg);
            border-radius: 30px;
            color: var(--primary-color);
            font-weight: 500;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
        }

        .dashboard-card {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .dashboard-card h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--text-color);
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .dashboard-card h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 2px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        }

        /* Progress Circle Styles */
        .progress-circle {
            width: 150px;
            height: 150px;
            margin: 0 auto 1.5rem;
        }

        .circular-chart {
            width: 150px;
            height: 150px;
            transform: rotate(-90deg);
        }

        .circle-bg {
            fill: none;
            stroke: #eee;
            stroke-width: 2.8;
        }

        .circle {
            fill: none;
            stroke: url(#gradient);
            stroke-width: 2.8;
            stroke-linecap: round;
            transition: stroke-dashoffset 0.5s ease;
        }

        .percentage {
            fill: var(--text-color);
            font-family: 'Roboto', sans-serif;
            font-size: 0.5em;
            text-anchor: middle;
            font-weight: 500;
            transform: rotate(90deg);
        }

        /* Budget Summary Styles */
        .budget-summary {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .budget-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: var(--light-bg);
            border-radius: 10px;
            transition: transform 0.3s ease;
        }

        .budget-item:hover {
            transform: translateX(5px);
        }

        .budget-item .label {
            color: var(--text-color);
            font-weight: 500;
        }

        .budget-item .amount {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Guest Summary Styles */
        .guest-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .guest-stat {
            text-align: center;
            padding: 1rem;
            background: var(--light-bg);
            border-radius: 10px;
            transition: transform 0.3s ease;
        }

        .guest-stat:hover {
            transform: translateY(-3px);
        }

        .guest-stat .number {
            display: block;
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .guest-stat .label {
            color: var(--text-color);
            font-size: 0.9rem;
        }

        /* Task List Styles */
        .task-list {
            margin-bottom: 1.5rem;
            max-height: 300px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--primary-color) #eee;
        }

        .task-list::-webkit-scrollbar {
            width: 6px;
        }

        .task-list::-webkit-scrollbar-track {
            background: #eee;
            border-radius: 3px;
        }

        .task-list::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 3px;
        }

        .task-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-radius: 10px;
            background: var(--light-bg);
            margin-bottom: 0.75rem;
            transition: transform 0.3s ease;
        }

        .task-item:hover {
            transform: translateX(5px);
        }

        .task-info h3 {
            font-size: 1rem;
            color: var(--text-color);
            margin-bottom: 0.25rem;
        }

        .due-date {
            font-size: 0.9rem;
            color: var(--light-text);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .task-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .task-status.pending {
            background: #FFF5F5;
            color: #E53E3E;
        }

        .task-status.in-progress {
            background: #FEFCBF;
            color: #B7791F;
        }

        .task-status.completed {
            background: #F0FFF4;
            color: #38A169;
        }

        .btn-outline {
            width: 100%;
            text-align: center;
            border-color: var(--primary-color);
            color: var(--primary-color);
            transition: all 0.3s ease;
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .no-tasks {
            text-align: center;
            color: var(--light-text);
            padding: 2rem;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .dashboard {
                padding: 1rem;
            }

            .dashboard-header {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .dashboard-header h1 {
                font-size: 2rem;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .guest-summary {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav-container">
            <a href="index.php" class="logo">RangRiti</a>
            <div class="nav-links">
                <a href="tasks.php">Tasks</a>
                <a href="budget.php">Budget</a>
                <a href="guests.php">Guests</a>
                <a href="vendors.php">Vendors</a>
                <div class="user-menu">
                    <span><?php echo escape($user_name); ?></span>
                    <a href="includes/logout.php" class="btn btn-primary">Logout</a>
                </div>
            </div>
        </nav>
    </header>

    <main class="dashboard">
        <div class="dashboard-header">
            <h1>Welcome back, <?php echo escape($user_name); ?>!</h1>
            <?php if ($wedding_date && $days_until > 0): ?>
                <p class="wedding-countdown">
                    <i class="fas fa-calendar-alt"></i>
                    <?php echo $days_until; ?> days until your wedding!
                </p>
            <?php endif; ?>
        </div>

        <div class="dashboard-grid">
            <!-- Progress Overview -->
            <div class="dashboard-card">
                <h2>Planning Progress</h2>
                <div class="progress-circle" data-progress="<?php echo $task_completion; ?>">
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <defs>
                            <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" style="stop-color: var(--primary-color)" />
                                <stop offset="100%" style="stop-color: var(--secondary-color)" />
                            </linearGradient>
                        </defs>
                        <path class="circle-bg"
                            d="M18 2.0845
                            a 15.9155 15.9155 0 0 1 0 31.831
                            a 15.9155 15.9155 0 0 1 0 -31.831"/>
                        <path class="circle"
                            stroke-dasharray="<?php echo $task_completion; ?>, 100"
                            d="M18 2.0845
                            a 15.9155 15.9155 0 0 1 0 31.831
                            a 15.9155 15.9155 0 0 1 0 -31.831"/>
                        <text x="18" y="20.35" class="percentage"><?php echo $task_completion; ?>%</text>
                    </svg>
                </div>
                <p>Tasks completed</p>
            </div>

            <!-- Budget Overview -->
            <div class="dashboard-card">
                <h2>Budget Overview</h2>
                <div class="budget-summary">
                    <div class="budget-item">
                        <span class="label">Estimated Total:</span>
                        <span class="amount"><?php echo formatCurrency($budget_summary['total_estimated'] ?? 0); ?></span>
                    </div>
                    <div class="budget-item">
                        <span class="label">Actual Spent:</span>
                        <span class="amount"><?php echo formatCurrency($budget_summary['total_actual'] ?? 0); ?></span>
                    </div>
                    <div class="budget-item">
                        <span class="label">Paid:</span>
                        <span class="amount"><?php echo formatCurrency($budget_summary['total_paid'] ?? 0); ?></span>
                    </div>
                </div>
                <a href="budget.php" class="btn btn-outline">View Budget</a>
            </div>

            <!-- Guest List Overview -->
            <div class="dashboard-card">
                <h2>Guest List</h2>
                <div class="guest-summary">
                    <div class="guest-stat">
                        <span class="number"><?php echo $guest_summary['total_guests']; ?></span>
                        <span class="label">Total Guests</span>
                    </div>
                    <div class="guest-stat">
                        <span class="number"><?php echo $guest_summary['attending']; ?></span>
                        <span class="label">Attending</span>
                    </div>
                    <div class="guest-stat">
                        <span class="number"><?php echo $guest_summary['pending']; ?></span>
                        <span class="label">Awaiting RSVP</span>
                    </div>
                </div>
                <a href="guests.php" class="btn btn-outline">Manage Guest List</a>
            </div>

            <!-- Upcoming Tasks -->
            <div class="dashboard-card">
                <h2>Upcoming Tasks</h2>
                <div class="task-list">
                    <?php if (empty($upcoming_tasks)): ?>
                        <p class="no-tasks">No upcoming tasks</p>
                    <?php else: ?>
                        <?php foreach ($upcoming_tasks as $task): ?>
                            <div class="task-item">
                                <div class="task-info">
                                    <h3><?php echo escape($task['task_name']); ?></h3>
                                    <p class="due-date">
                                        <i class="fas fa-calendar"></i>
                                        Due: <?php echo formatDate($task['due_date']); ?>
                                    </p>
                                </div>
                                <div class="task-status <?php echo $task['status']; ?>">
                                    <?php echo ucfirst($task['status']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <a href="tasks.php" class="btn btn-outline">View All Tasks</a>
            </div>
        </div>
    </main>

    <script src="assets/js/main.js"></script>
    <script>
        // Initialize progress circles
        document.querySelectorAll('.progress-circle').forEach(circle => {
            const progress = circle.dataset.progress;
            const circumference = 2 * Math.PI * 15.9155;
            const offset = circumference - (progress / 100 * circumference);
            circle.querySelector('.circle').style.strokeDashoffset = offset;
        });
    </script>
</body>
</html>

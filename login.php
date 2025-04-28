<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = "Both email and password are required";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, full_name, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($user = $stmt->fetch()) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $errors[] = "Invalid email or password";
                }
            } else {
                $errors[] = "Invalid email or password";
            }
        } catch (PDOException $e) {
            $errors[] = "Login failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RangRiti</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #FFE4E6 0%, #FFF 100%);
            display: flex;
            flex-direction: column;
        }
        
        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 420px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(to right, #FF8C94, #FFB6B9);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #2D3748;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #718096;
            font-size: 1.1rem;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #A0AEC0;
            transition: color 0.3s ease;
        }
        
        .input-group input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 2px solid #E2E8F0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .input-group input:focus {
            border-color: #FF8C94;
            box-shadow: 0 0 0 3px rgba(255, 140, 148, 0.1);
        }
        
        .input-group input:focus + i {
            color: #FF8C94;
        }
        
        .remember-forgot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
            border-radius: 4px;
        }
        
        .forgot-password {
            color: #FF8C94;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .forgot-password:hover {
            color: #FF6B74;
        }
        
        .login-button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(to right, #FF8C94, #FFB6B9);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 140, 148, 0.3);
        }
        
        .signup-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #718096;
        }
        
        .signup-link a {
            color: #FF8C94;
            text-decoration: none;
            font-weight: 500;
            margin-left: 0.25rem;
        }
        
        .signup-link a:hover {
            text-decoration: underline;
        }
        
        .error-messages {
            background: #FFF5F5;
            border-left: 4px solid #FF8C94;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .error-messages p {
            color: #E53E3E;
            margin: 0;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <div class="login-page">
        <header class="header">
            <nav class="nav-container">
                <a href="index.php" class="logo">RangRiti</a>
                <div class="nav-links">
                    <a href="register.php" class="btn btn-outline">Create an account</a>
                </div>
            </nav>
        </header>

        <main class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <h1>Welcome Back</h1>
                    <p>Sign in to continue planning your perfect wedding</p>
                </div>
            
            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <?php foreach ($errors as $error): ?>
                        <p class="error"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="error-messages">
                        <?php foreach ($errors as $error): ?>
                            <p class="error"><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" class="login-form">
                    <div class="input-group">
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                               placeholder="Enter your email" required>
                        <i class="fas fa-envelope"></i>
                    </div>

                    <div class="input-group">
                        <input type="password" id="password" name="password" 
                               placeholder="Enter your password" required>
                        <i class="fas fa-lock"></i>
                    </div>

                    <div class="remember-forgot">
                        <div class="checkbox-group">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Remember me</label>
                        </div>
                        <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                    </div>

                    <button type="submit" class="login-button">Sign In</button>
                </form>

                <p class="signup-link">
                    New to RangRiti?<a href="register.php">Create an account</a>
                </p>
            </div>
        </main>
    </div>

    <script src="assets/js/validation.js"></script>
    <script>
        // Add input focus animations
        document.querySelectorAll('.input-group input').forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', () => {
                if (!input.value) {
                    input.parentElement.classList.remove('focused');
                }
            });
            
            // Check initial state
            if (input.value) {
                input.parentElement.classList.add('focused');
            }
        });
    </script>
</body>
</html>

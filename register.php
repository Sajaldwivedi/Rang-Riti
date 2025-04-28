<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $wedding_date = $_POST['wedding_date'] ?? '';

    // Validation
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }

    if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $password)) {
        $errors[] = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "Email already registered";
    }

    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, wedding_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $hashed_password, $wedding_date]);
            
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['full_name'] = $full_name;
            
            header("Location: dashboard.php");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - RangRiti</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --primary-color: #FF8C94;
            --secondary-color: #FFB6B9;
        }
        
        .register-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #FFE4E6 0%, #FFF 100%);
            display: flex;
            flex-direction: column;
            padding-top: 80px; /* Add space for fixed header */
        }
        
        .register-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 480px;
            padding: 3rem 2.5rem;
            position: relative;
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .register-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .register-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #2D3748;
            margin-bottom: 0.5rem;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .register-header p {
            color: #718096;
            font-size: 1.1rem;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-group input {
            width: 100%;
            padding: 1rem 1rem 1rem 2.5rem;
            border: 2px solid #E2E8F0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #F7FAFC;
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #A0AEC0;
            transition: color 0.3s ease;
            z-index: 2;
            pointer-events: none;
        }

        .input-group input:focus + i {
            color: var(--primary-color);
        }

        .password-requirements {
            display: none;
            font-size: 0.85rem;
            color: #718096;
            margin-top: 0.5rem;
            padding: 0.75rem;
            background: #F7FAFC;
            border-radius: 8px;
            border-left: 3px solid var(--primary-color);
            position: relative;
            z-index: 1;
        }

        .input-group input:focus ~ .password-requirements {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .register-button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .register-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 140, 148, 0.2);
        }

        .register-button:active {
            transform: translateY(1px);
        }

        .register-button.loading {
            color: transparent;
        }

        .register-button.loading::after {
            content: "";
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin: -10px 0 0 -10px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #718096;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            margin-left: 0.5rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: var(--secondary-color);
        }

        .error-messages {
            background: #FFF5F5;
            border-left: 4px solid #E53E3E;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .error-messages p {
            color: #E53E3E;
            margin: 0;
            font-size: 0.95rem;
        }

        .error-messages p + p {
            margin-top: 0.5rem;
        }

        /* Password strength indicators */
        .password-strength {
            display: flex;
            gap: 5px;
            margin-top: 0.5rem;
        }

        .strength-bar {
            height: 4px;
            flex: 1;
            background: #E2E8F0;
            border-radius: 2px;
            transition: background-color 0.3s ease;
        }

        .strength-bar.weak { background-color: #E53E3E; }
        .strength-bar.medium { background-color: #ECC94B; }
        .strength-bar.strong { background-color: #48BB78; }
    </style>
</head>
<body>
    <div class="register-page">
        <header class="header">
            <nav class="nav-container">
                <a href="index.php" class="logo">RangRiti</a>
                <div class="nav-links">
                    <a href="login.php" class="btn btn-outline">Already have an account?</a>
                </div>
            </nav>
        </header>

        <main class="register-container">
            <div class="register-card">
                <div class="register-header">
                    <h1>Create Your Account</h1>
                    <p>Join RangRiti to start planning your perfect wedding</p>
                </div>
            
            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <?php foreach ($errors as $error): ?>
                        <p class="error"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

                <form action="register.php" method="POST" class="register-form" id="registerForm">
                    <div class="input-group">
                        <input type="text" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($full_name ?? ''); ?>" 
                               placeholder="Enter your full name" required>
                        <i class="fas fa-user"></i>
                    </div>

                    <div class="input-group">
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                               placeholder="Enter your email" required>
                        <i class="fas fa-envelope"></i>
                    </div>

                    <div class="input-group">
                        <input type="password" id="password" name="password" 
                               placeholder="Choose a password" required>
                        <i class="fas fa-lock"></i>
                        <div class="password-requirements">
                            <div>Password must contain:</div>
                            <div id="length-check">✗ At least 8 characters</div>
                            <div id="uppercase-check">✗ One uppercase letter</div>
                            <div id="lowercase-check">✗ One lowercase letter</div>
                            <div id="number-check">✗ One number</div>
                            <div id="special-check">✗ One special character</div>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar"></div>
                            <div class="strength-bar"></div>
                            <div class="strength-bar"></div>
                            <div class="strength-bar"></div>
                        </div>
                    </div>

                    <div class="input-group">
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="Confirm your password" required>
                        <i class="fas fa-lock"></i>
                    </div>

                    <div class="input-group">
                        <input type="date" id="wedding_date" name="wedding_date"
                               value="<?php echo htmlspecialchars($wedding_date ?? ''); ?>"
                               placeholder="Your Wedding Date" required>
                        <i class="fas fa-calendar"></i>
                    </div>

                    <button type="submit" class="register-button" id="submitBtn">Create Account</button>
                </form>

                <p class="login-link">
                    Already have an account? <a href="login.php">Sign in</a>
                </p>
            </div>
        </main>
    </div>

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

        // Password validation and strength indicator
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const lengthCheck = document.getElementById('length-check');
        const uppercaseCheck = document.getElementById('uppercase-check');
        const lowercaseCheck = document.getElementById('lowercase-check');
        const numberCheck = document.getElementById('number-check');
        const specialCheck = document.getElementById('special-check');
        const strengthBars = document.querySelectorAll('.strength-bar');
        
        function updatePasswordStrength(password) {
            let strength = 0;
            
            // Length check
            if (password.length >= 8) {
                strength++;
                lengthCheck.innerHTML = '✓ At least 8 characters';
                lengthCheck.style.color = '#48BB78';
            } else {
                lengthCheck.innerHTML = '✗ At least 8 characters';
                lengthCheck.style.color = '#E53E3E';
            }
            
            // Uppercase check
            if (/[A-Z]/.test(password)) {
                strength++;
                uppercaseCheck.innerHTML = '✓ One uppercase letter';
                uppercaseCheck.style.color = '#48BB78';
            } else {
                uppercaseCheck.innerHTML = '✗ One uppercase letter';
                uppercaseCheck.style.color = '#E53E3E';
            }
            
            // Lowercase check
            if (/[a-z]/.test(password)) {
                strength++;
                lowercaseCheck.innerHTML = '✓ One lowercase letter';
                lowercaseCheck.style.color = '#48BB78';
            } else {
                lowercaseCheck.innerHTML = '✗ One lowercase letter';
                lowercaseCheck.style.color = '#E53E3E';
            }
            
            // Number check
            if (/\d/.test(password)) {
                strength++;
                numberCheck.innerHTML = '✓ One number';
                numberCheck.style.color = '#48BB78';
            } else {
                numberCheck.innerHTML = '✗ One number';
                numberCheck.style.color = '#E53E3E';
            }
            
            // Special character check
            if (/[@$!%*?&]/.test(password)) {
                strength++;
                specialCheck.innerHTML = '✓ One special character';
                specialCheck.style.color = '#48BB78';
            } else {
                specialCheck.innerHTML = '✗ One special character';
                specialCheck.style.color = '#E53E3E';
            }
            
            // Update strength bars
            strengthBars.forEach((bar, index) => {
                bar.className = 'strength-bar';
                if (index < strength) {
                    bar.classList.add(strength <= 2 ? 'weak' : strength <= 3 ? 'medium' : 'strong');
                }
            });
            
            return strength === 5;
        }
        
        function validateConfirmPassword() {
            const isMatch = passwordInput.value === confirmInput.value;
            confirmInput.style.borderColor = isMatch ? 'var(--primary-color)' : '#E53E3E';
            return isMatch;
        }
        
        passwordInput.addEventListener('input', () => {
            updatePasswordStrength(passwordInput.value);
        });
        
        confirmInput.addEventListener('input', validateConfirmPassword);

        // Form submission
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const isPasswordValid = updatePasswordStrength(passwordInput.value);
            const isConfirmValid = validateConfirmPassword();
            
            if (!isPasswordValid || !isConfirmValid) {
                e.preventDefault();
                return;
            }
            
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>

<?php
// login.php - User Authentication with Security
require 'config.php';
require 'functions.php';
require 'security.php';

init_secure_session();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$errors = [];
$rate_limited = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!csrf_verify(false)) {
        $errors[] = 'Security token validation failed. Please refresh and try again.';
        log_security_event('CSRF validation failed on login', ['email' => $_POST['email'] ?? 'unknown']);
    }
    // Rate Limiting - 5 attempts per 5 minutes
    else if (is_rate_limited('login', 5, 300)) {
        $rate_limited = true;
        $reset_time = rate_limit_reset_time('login', 300);
        $minutes = ceil($reset_time / 60);
        $errors[] = "Too many login attempts. Please try again in {$minutes} minute(s).";
        log_security_event('Login rate limit exceeded', ['email' => $_POST['email'] ?? 'unknown']);
    }
    else {
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $errors[] = 'Both email and password are required.';
        } else if (!validate_email($email)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            // Fetch user from the database
            $stmt = $pdo->prepare("SELECT id, school_id, name, email, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify user and password
            if ($user && password_verify($password, $user['password'])) {
                // Password is correct, reset rate limit
                reset_rate_limit('login');

                // Start a new session
                session_regenerate_id(true); // Prevent session fixation

                // Store user data in session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['school_id'] = $user['school_id'];
                $_SESSION['user_name'] = $user['name'];

                log_security_event('Successful login', ['user_id' => $user['id'], 'email' => $email]);

                // Redirect to the main dashboard
                header("Location: index.php");
                exit();
            } else {
                // Invalid credentials
                $errors[] = 'Invalid email or password.';
                log_security_event('Failed login attempt', ['email' => $email]);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School Finance System</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a365d',
                        secondary: '#2c5282',
                        accent: '#3182ce',
                        success: '#38a169',
                        warning: '#d69e2e',
                        danger: '#e53e3e',
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e7eb 100%);
        }
        .auth-container {
            width: 100%;
            max-width: 420px;
            padding: 2rem;
        }
        .auth-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: var(--shadow);
            text-align: center;
        }
        .auth-header h1 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        .auth-header p {
            color: var(--dark);
            margin-bottom: 2rem;
        }
        .form-group {
            text-align: left;
            margin-bottom: 1.25rem;
        }
        .form-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: block;
        }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 15px;
            background: var(--light);
            transition: all 0.3s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        .btn-submit {
            width: 100%;
            padding: 15px;
            font-size: 1rem;
            margin-top: 1rem;
        }
        .auth-footer {
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }
        .auth-footer a {
            color: var(--secondary);
            font-weight: 600;
            text-decoration: none;
        }
        .auth-footer a:hover {
            text-decoration: underline;
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: var(--border-radius);
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-danger {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger);
            border: 1px solid var(--danger);
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Welcome Back</h1>
                <p>Log in to manage your school's finances.</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form action="login.php" method="post">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary btn-submit">Log In</button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Register Now</a></p>
            </div>
        </div>
    </div>
</body>
</html>
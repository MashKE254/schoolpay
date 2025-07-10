<?php
// register.php - School Registration and User Signup
require 'config.php';
require 'functions.php';

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve form data
    $school_name = trim($_POST['school_name']);
    $user_name = trim($_POST['user_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // --- Validation ---
    if (empty($school_name)) {
        $errors[] = 'School name is required.';
    }
    if (empty($user_name)) {
        $errors[] = 'Your name is required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    // Check if email is already in use
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = 'This email address is already registered.';
    }

    // --- Process Registration if no errors ---
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // 1. Create the school
            $stmt = $pdo->prepare("INSERT INTO schools (name) VALUES (?)");
            $stmt->execute([$school_name]);
            $school_id = $pdo->lastInsertId();

            // 2. Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // 3. Create the user
            $stmt = $pdo->prepare("INSERT INTO users (school_id, name, email, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$school_id, $user_name, $email, $hashed_password]);

            $pdo->commit();

            $success_message = 'Registration successful! You can now log in.';
            // Optionally, redirect to login page after a delay
            // header("refresh:3;url=login.php");

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'A database error occurred. Please try again later.';
            // For debugging: error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - School Finance System</title>
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
            max-width: 450px;
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
        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success);
            border: 1px solid var(--success);
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Create Your Account</h1>
                <p>Set up your school's financial dashboard in minutes.</p>
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

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                </div>
                <div class="auth-footer">
                    <a href="login.php" class="btn btn-primary btn-submit">Proceed to Login</a>
                </div>
            <?php else: ?>
                <form action="register.php" method="post">
                    <div class="form-group">
                        <label for="school_name">School Name</label>
                        <input type="text" id="school_name" name="school_name" class="form-control" required value="<?php echo isset($_POST['school_name']) ? htmlspecialchars($_POST['school_name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="user_name">Your Name</label>
                        <input type="text" id="user_name" name="user_name" class="form-control" required value="<?php echo isset($_POST['user_name']) ? htmlspecialchars($_POST['user_name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-submit">Register</button>
                </form>

                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php">Log In</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php
// header.php - Secure Session Management & Dynamic Header

// Check if a session is not already active before starting one
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php'; // Use require_once to prevent re-declaration errors

// --- Page & User Authentication ---
$public_pages = ['login.php', 'register.php'];
$is_public_page = in_array(basename($_SERVER['PHP_SELF']), $public_pages);

if (!isset($_SESSION['user_id']) && !$is_public_page) {
    header("Location: login.php");
    exit();
}

// --- Dynamic Data Loading ---
$user_id = $_SESSION['user_id'] ?? null;
$school_id = $_SESSION['school_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? 'Guest';
$current_school_name = 'School Finance System'; // Default name

// If a user is logged in, fetch their school's name
if ($school_id) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM schools WHERE id = ?");
        $stmt->execute([$school_id]);
        $school = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($school && !empty($school['name'])) {
            $current_school_name = $school['name'];
        }
    } catch (PDOException $e) {
        // Log the error but don't stop the page from loading. The default name will be used.
        error_log("Header school name fetch error: " . $e->getMessage());
    }
}

// Detect current page for body class
$pageClass = (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'dashboard-page' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($current_school_name); ?> - Finance System</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script>
      // Enhanced JavaScript for better UX
      function openTab(evt, tabName) {
          var i, tabcontent, tablinks;
          tabcontent = document.getElementsByClassName("tab-content");
          for (i = 0; i < tabcontent.length; i++) {
              tabcontent[i].classList.remove("active");
              tabcontent[i].style.display = "none";
          }
          tablinks = document.getElementsByClassName("tab-link");
          for (i = 0; i < tablinks.length; i++) {
              tablinks[i].classList.remove("active");
          }
          var selectedTab = document.getElementById(tabName);
          if (selectedTab) {
              selectedTab.classList.add("active");
              selectedTab.style.display = "block";
          }
          if(evt) evt.currentTarget.classList.add("active");
      }

      // Mobile menu toggle
      function toggleMobileMenu() {
          const navLinks = document.querySelector('.nav-links');
          navLinks.classList.toggle('active');
      }
    </script>
</head>
<body class="<?php echo $pageClass; ?>">
<?php if (!$is_public_page): // Only show header if not on login/register page ?>
<header>
    <div class="header-container">
        <div class="header-top">
            <div class="header-brand">
                <div class="brand-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="brand-text">
                    <h1><?php echo htmlspecialchars($current_school_name); ?></h1>
                    <div class="subtitle">Finance Management Dashboard</div>
                </div>
            </div>
            <div class="header-actions">
                <div class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 2)); ?></div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                        <a href="logout.php" style="font-size: 0.8rem; color: #ecf0f1; text-decoration: none;">Logout</a>
                    </div>
                    <i class="fas fa-chevron-down user-dropdown"></i>
                </div>
            </div>
        </div>
        <nav>
            <div class="nav-container">
                <div class="nav-links">
                    <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="customer_center.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'customer_center.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Customer Center
                    </a>
                    <a href="expense_management.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'expense_management.php' ? 'active' : ''; ?>">
                        <i class="fas fa-receipt"></i> Expense Management
                    </a>
                    <a href="banking.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'banking.php' ? 'active' : ''; ?>">
                        <i class="fas fa-university"></i> Banking
                    </a>
                    <a href="payroll.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'payroll.php' ? 'active' : ''; ?>">
                        <i class="fas fa-money-check-alt"></i> Payroll
                    </a>
                    <a href="budget.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'budget.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calculator"></i> Budgeting
                    </a>
                    <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                    <a href="bulk_actions.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'bulk_actions.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cogs"></i> Bulk Actions
                    </a>
                    <a href="transport_management.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'transport_management.php' ? 'active' : ''; ?>">
                        <i class="fas fa-bus"></i> Transport
                    </a>
                    <a href="activities_management.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'activities_management.php' ? 'active' : ''; ?>">
                        <i class="fas fa-running"></i> Activities
                    </a>
                    <a href="inventory.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>">
                        <i class="fas fa-boxes"></i> Inventory
                    </a>
                    <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-cog"></i> My Profile
                    </a>
                </div>
                <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </nav>
    </div>
</header>
<?php endif; ?>
<div class="container">
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

// If a user is logged in, fetch their school's name and currency
if ($school_id) {
    try {
        $stmt = $pdo->prepare("SELECT s.name, sd.currency_symbol FROM schools s LEFT JOIN school_details sd ON s.id = sd.school_id WHERE s.id = ?");
        $stmt->execute([$school_id]);
        $school = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($school && !empty($school['name'])) {
            $current_school_name = $school['name'];
        }
        // Set currency symbol in session (default to $ if not set)
        $_SESSION['currency_symbol'] = $school['currency_symbol'] ?? '$';
    } catch (PDOException $e) {
        // Log the error but don't stop the page from loading. The default name will be used.
        error_log("Header school data fetch error: " . $e->getMessage());
        $_SESSION['currency_symbol'] = '$'; // Fallback currency
    }
} else {
    // Default currency for public pages
    $_SESSION['currency_symbol'] = '$';
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
    <!-- Custom Styles (for backwards compatibility) -->
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
<body class="bg-gray-50 <?php echo $pageClass; ?>">
<?php if (!$is_public_page): // Only show header if not on login/register page ?>
<!-- Modern Header with Tailwind -->
<header class="bg-gradient-to-r from-slate-800 via-slate-900 to-slate-800 shadow-xl">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Top Bar -->
        <div class="flex items-center justify-between h-16">
            <!-- Brand -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-graduation-cap text-white text-lg"></i>
                </div>
                <div>
                    <h1 class="text-white font-bold text-lg leading-tight"><?= htmlspecialchars($current_school_name) ?></h1>
                    <p class="text-slate-400 text-xs">Finance Management</p>
                </div>
            </div>

            <!-- Right Actions -->
            <div class="flex items-center gap-4">
                <!-- Notifications -->
                <button class="relative p-2 text-slate-400 hover:text-white hover:bg-slate-700 rounded-lg transition-colors">
                    <i class="fas fa-bell text-lg"></i>
                    <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center">3</span>
                </button>

                <!-- User Menu -->
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-full flex items-center justify-center text-white font-bold text-sm">
                        <?= strtoupper(substr($user_name, 0, 2)) ?>
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-white text-sm font-medium"><?= htmlspecialchars($user_name) ?></p>
                        <a href="logout.php" class="text-slate-400 text-xs hover:text-red-400 transition-colors">Logout</a>
                    </div>
                </div>

                <!-- Mobile Menu Toggle -->
                <button class="lg:hidden p-2 text-slate-400 hover:text-white hover:bg-slate-700 rounded-lg" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="hidden lg:block border-t border-slate-700">
            <div class="flex items-center gap-1 py-2 overflow-x-auto">
                <?php
                $nav_items = [
                    ['href' => 'index.php', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
                    ['href' => 'customer_center.php', 'icon' => 'fa-users', 'label' => 'Customers'],
                    ['href' => 'expense_management.php', 'icon' => 'fa-receipt', 'label' => 'Expenses'],
                    ['href' => 'banking.php', 'icon' => 'fa-university', 'label' => 'Banking'],
                    ['href' => 'payroll.php', 'icon' => 'fa-money-check-alt', 'label' => 'Payroll'],
                    ['href' => 'budget.php', 'icon' => 'fa-calculator', 'label' => 'Budget'],
                    ['href' => 'reports.php', 'icon' => 'fa-chart-bar', 'label' => 'Reports'],
                    ['href' => 'bulk_actions.php', 'icon' => 'fa-cogs', 'label' => 'Bulk Actions'],
                    ['href' => 'inventory.php', 'icon' => 'fa-boxes', 'label' => 'Inventory'],
                    ['href' => 'profile.php', 'icon' => 'fa-user-cog', 'label' => 'Profile'],
                ];
                foreach ($nav_items as $item):
                    $is_active = basename($_SERVER['PHP_SELF']) == $item['href'];
                ?>
                <a href="<?= $item['href'] ?>" class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all whitespace-nowrap <?= $is_active ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-700 hover:text-white' ?>">
                    <i class="fas <?= $item['icon'] ?>"></i>
                    <span><?= $item['label'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </nav>

        <!-- Mobile Navigation -->
        <nav class="nav-links lg:hidden hidden flex-col gap-1 py-4 border-t border-slate-700">
            <?php foreach ($nav_items as $item):
                $is_active = basename($_SERVER['PHP_SELF']) == $item['href'];
            ?>
            <a href="<?= $item['href'] ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition-all <?= $is_active ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white' ?>">
                <i class="fas <?= $item['icon'] ?> w-5"></i>
                <span><?= $item['label'] ?></span>
            </a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>
<?php endif; ?>

<!-- Main Container -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
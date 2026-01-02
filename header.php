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
<?php if (!$is_public_page): // Only show sidebar if not on login/register page ?>
<?php
$nav_items = [
    ['href' => 'index.php', 'icon' => 'fa-home', 'label' => 'Home'],
    ['href' => 'customer_center.php', 'icon' => 'fa-users', 'label' => 'Customers'],
    ['href' => 'expense_management.php', 'icon' => 'fa-receipt', 'label' => 'Expenses'],
    ['href' => 'banking.php', 'icon' => 'fa-university', 'label' => 'Banking'],
    ['href' => 'payroll.php', 'icon' => 'fa-money-check-alt', 'label' => 'Payroll'],
    ['href' => 'budget.php', 'icon' => 'fa-calculator', 'label' => 'Budget'],
    ['href' => 'reports.php', 'icon' => 'fa-chart-bar', 'label' => 'Reports'],
    ['href' => 'bulk_actions.php', 'icon' => 'fa-cogs', 'label' => 'Bulk Actions'],
    ['href' => 'inventory.php', 'icon' => 'fa-boxes', 'label' => 'Inventory'],
];
$settings_items = [
    ['href' => 'profile.php', 'icon' => 'fa-user-cog', 'label' => 'Profile'],
];
?>

<!-- Mobile Header -->
<header class="lg:hidden fixed top-0 left-0 right-0 z-50 bg-white border-b border-gray-200 px-4 h-14 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 bg-gradient-to-br from-violet-500 to-purple-600 rounded-lg flex items-center justify-center">
            <i class="fas fa-graduation-cap text-white text-sm"></i>
        </div>
        <span class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($current_school_name) ?></span>
    </div>
    <button onclick="toggleSidebar()" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg">
        <i class="fas fa-bars text-lg"></i>
    </button>
</header>

<!-- Sidebar Overlay (Mobile) -->
<div id="sidebarOverlay" onclick="toggleSidebar()" class="lg:hidden fixed inset-0 bg-black/50 z-40 hidden"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed top-0 left-0 z-50 h-screen w-64 bg-white border-r border-gray-200 flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out">

    <!-- Logo -->
    <div class="p-5 border-b border-gray-100">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-gradient-to-br from-violet-500 to-purple-600 rounded-lg flex items-center justify-center shadow-sm">
                <i class="fas fa-graduation-cap text-white"></i>
            </div>
            <span class="font-bold text-gray-900"><?= htmlspecialchars($current_school_name) ?></span>
        </div>
    </div>

    <!-- User Profile -->
    <div class="p-4 border-b border-gray-100">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-full flex items-center justify-center text-white font-semibold text-sm shadow-sm">
                <?= strtoupper(substr($user_name, 0, 2)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($user_name) ?></p>
                <p class="text-xs text-gray-500">Administrator</p>
            </div>
            <button class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-chevron-down text-xs"></i>
            </button>
        </div>
    </div>

    <!-- Search -->
    <div class="p-4">
        <div class="relative">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="text" placeholder="Search" class="w-full pl-9 pr-4 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition-all">
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto px-3 py-2">
        <ul class="space-y-1">
            <?php foreach ($nav_items as $item):
                $is_active = basename($_SERVER['PHP_SELF']) == $item['href'];
            ?>
            <li>
                <a href="<?= $item['href'] ?>" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all <?= $is_active ? 'bg-violet-50 text-violet-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <i class="fas <?= $item['icon'] ?> w-5 text-center <?= $is_active ? 'text-violet-600' : 'text-gray-400' ?>"></i>
                    <span><?= $item['label'] ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <!-- Settings Section -->
        <div class="mt-6 pt-6 border-t border-gray-100">
            <p class="px-3 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Settings</p>
            <ul class="space-y-1">
                <?php foreach ($settings_items as $item):
                    $is_active = basename($_SERVER['PHP_SELF']) == $item['href'];
                ?>
                <li>
                    <a href="<?= $item['href'] ?>" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all <?= $is_active ? 'bg-violet-50 text-violet-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                        <i class="fas <?= $item['icon'] ?> w-5 text-center <?= $is_active ? 'text-violet-600' : 'text-gray-400' ?>"></i>
                        <span><?= $item['label'] ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>

    <!-- Logout -->
    <div class="p-4 border-t border-gray-100">
        <a href="logout.php" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition-all">
            <i class="fas fa-sign-out-alt w-5 text-center"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}
</script>
<?php endif; ?>

<!-- Main Container -->
<div class="<?= !$is_public_page ? 'lg:ml-64 pt-14 lg:pt-0' : '' ?>">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
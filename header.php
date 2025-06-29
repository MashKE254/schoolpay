<?php
// Detect current page for body class
$pageClass = '';
if (basename($_SERVER['PHP_SELF']) == 'index.php') {
    $pageClass = 'dashboard-page';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Finance System</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script>
      // Enhanced JavaScript for better UX
      function openTab(evt, tabName) {
          var i, tabcontent, tablinks;
          
          // Hide all tab content
          tabcontent = document.getElementsByClassName("tab-content");
          for (i = 0; i < tabcontent.length; i++) {
              tabcontent[i].classList.remove("active");
              tabcontent[i].style.display = "none";
          }
          
          // Remove active class from all tab links
          tablinks = document.getElementsByClassName("tab-link");
          for (i = 0; i < tablinks.length; i++) {
              tablinks[i].classList.remove("active");
          }
          
          // Show selected tab and mark button as active
          var selectedTab = document.getElementById(tabName);
          if (selectedTab) {
              selectedTab.classList.add("active");
              selectedTab.style.display = "block";
          }
          evt.currentTarget.classList.add("active");
      }

      // Mobile menu toggle
      function toggleMobileMenu() {
          const navLinks = document.querySelector('.nav-links');
          navLinks.classList.toggle('active');
      }

      // Enhanced alert system
      function showAlert(message, type = 'success') {
          const alertContainer = document.createElement('div');
          alertContainer.className = `alert alert-${type}`;
          alertContainer.innerHTML = `
              <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
              <span>${message}</span>
              <button class="alert-close" onclick="this.parentElement.remove()">
                  <i class="fas fa-times"></i>
              </button>
          `;
          
          const container = document.querySelector('.container');
          container.insertBefore(alertContainer, container.firstChild);
          
          // Auto-remove after 5 seconds
          setTimeout(() => {
              if (alertContainer.parentElement) {
                  alertContainer.remove();
              }
          }, 5000);
      }

      // Auto-open first tab on page load
      window.addEventListener('DOMContentLoaded', function() {
          const firstTab = document.querySelector(".tab-link");
          if (firstTab) {
              firstTab.click();
          }
          
          // Add loading states to buttons
          document.querySelectorAll('.btn').forEach(btn => {
              btn.addEventListener('click', function() {
                  const originalText = this.innerHTML;
                  this.innerHTML = '<span class="loading"></span> Processing...';
                  this.disabled = true;
                  
                  // Re-enable after 2 seconds (adjust based on your needs)
                  setTimeout(() => {
                      this.innerHTML = originalText;
                      this.disabled = false;
                  }, 2000);
              });
          });
      });

      // Smooth scroll for anchor links
      document.querySelectorAll('a[href^="#"]').forEach(anchor => {
          anchor.addEventListener('click', function (e) {
              e.preventDefault();
              const target = document.querySelector(this.getAttribute('href'));
              if (target) {
                  target.scrollIntoView({
                      behavior: 'smooth',
                      block: 'start'
                  });
              }
          });
      });

      // Add ripple effect to buttons
      function addRippleEffect(e) {
          const button = e.currentTarget;
          const circle = document.createElement("span");
          const diameter = Math.max(button.clientWidth, button.clientHeight);
          const radius = diameter / 2;
          
          circle.style.width = circle.style.height = `${diameter}px`;
          circle.style.left = `${e.clientX - (button.offsetLeft + radius)}px`;
          circle.style.top = `${e.clientY - (button.offsetTop + radius)}px`;
          circle.classList.add("ripple");
          
          const ripple = button.getElementsByClassName("ripple")[0];
          if (ripple) {
              ripple.remove();
          }
          
          button.appendChild(circle);
      }

      // Apply ripple effect to all buttons
      document.addEventListener('DOMContentLoaded', function() {
          document.querySelectorAll('.btn').forEach(btn => {
              btn.addEventListener('click', addRippleEffect);
          });
      });
    </script>
</head>
<body class="<?php echo $pageClass; ?>">
<header>
    <div class="header-container">
        <div class="header-top">
            <div class="header-brand">
                <div class="brand-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="brand-text">
                    <h1>Bloomsfield Kindergarten and School</h1>
                    <div class="subtitle">School Finance Management System</div>
                </div>
            </div>
            <div class="header-actions">
                <div class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
                <div class="user-info">
                    <div class="user-avatar">AD</div>
                    <div class="user-details">
                        <div class="user-name">Admin User</div>
                        <div class="user-role">Administrator</div>
                    </div>
                    <i class="fas fa-chevron-down user-dropdown"></i>
                </div>
            </div>
        </div>
        <nav>
            <div class="nav-container">
                <div class="nav-links">
                    <a href="index.php" class="active">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                    <a href="customer_center.php">
                        <i class="fas fa-users"></i>
                        Customer Center
                    </a>
                    <a href="expense_management.php">
                        <i class="fas fa-receipt"></i>
                        Expense Management
                    </a>
                    <a href="payroll.php">
                        <i class="fas fa-money-check-alt"></i>
                        Payroll
                    </a>
                    <a href="reports.php">
                        <i class="fas fa-chart-bar"></i>
                        Reports
                    </a>
                </div>
                <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </nav>
    </div>
</header>
<div class="container">
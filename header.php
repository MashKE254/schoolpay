<?php
// header.php: common header for all pages
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>School Finance System</title>
    <link rel="stylesheet" href="styles.css">
    <style>
      /* Basic styling */
      header { background: #003366; color: #fff; padding: 1em; }
      nav a { color: #fff; margin-right: 1em; text-decoration: none; }
      .container { 
        padding: 1em 2em;
        flex: 1;
      }
      footer { 
        background: #003366; 
        color: #fff; 
        text-align: center; 
        padding: 1em;
        margin-top: auto;
      }      
      
      /* Enhanced CSS for Payroll System */
      :root {
        --primary-color: #4a6fa5;
        --primary-light: #6789bd;
        --primary-dark: #365785;
        --secondary-color: #67a57f;
        --danger-color: #d9534f;
        --warning-color: #f0ad4e;
        --success-color: #5cb85c;
        --gray-light: #f8f9fa;
        --gray: #e9ecef;
        --gray-dark: #343a40;
        --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        --border-radius: 8px;
      }

      /* Global Styles */
      body {
        font-family: 'Roboto', 'Segoe UI', sans-serif;
        line-height: 1.6;
        color: #333;
        background-color: #f5f7fa;
        margin: 0;
        padding: 0;
      }

      h2 {
        color: var(--primary-dark);
        margin: 1.5rem 0;
        font-weight: 600;
        font-size: 2rem;
        text-align: center;
        position: relative;
        padding-bottom: 10px;
      }

      h2:after {
        content: '';
        position: absolute;
        width: 80px;
        height: 3px;
        background-color: var(--primary-color);
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
      }

      h3 {
        color: var(--primary-dark);
        margin-top: 0;
        font-weight: 500;
        font-size: 1.5rem;
      }

      h4 {
        color: var(--primary-color);
        border-bottom: 1px solid var(--gray);
        padding-bottom: 0.5rem;
        margin: 1.5rem 0 1rem;
      }

      /* Tab Container and Tabs */
      .tab-container {
        max-width: 1200px;
        margin: 0 auto 2rem;
        background-color: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        overflow: hidden;
      }

      .tabs {
        display: flex;
        background-color: var(--gray-light);
        border-bottom: 1px solid var(--gray);
      }

      .tab-link {
        padding: 15px 25px;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 500;
        color: var(--gray-dark);
        transition: all 0.3s ease;
        flex: 1;
        text-align: center;
        border-bottom: 3px solid transparent;
        position: relative;
        overflow: hidden;
      }

      .tab-link:hover {
        background-color: rgba(74, 111, 165, 0.1);
        color: var(--primary-color);
      }

      .tab-link.active {
        color: var(--primary-color);
        border-bottom: 3px solid var(--primary-color);
        background-color: white;
      }

      .tab-link:focus {
        outline: none;
      }

      .tab-content {
        padding: 20px;
        display: none;
        animation: fadeIn 0.5s ease;
      }

      @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
      }

      /* Card Styling */
      .card {
        padding: 1.5rem;
        border-radius: var(--border-radius);
        background-color: white;
      }

      /* Form Styling */
      .form-group {
        margin-bottom: 1.25rem;
      }

      label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: var(--gray-dark);
      }

      input[type="text"],
      input[type="number"],
      input[type="date"],
      select {
        width: 100%;
        padding: 12px;
        border: 1px solid var(--gray);
        border-radius: var(--border-radius);
        font-size: 1rem;
        transition: border 0.3s ease, box-shadow 0.3s ease;
        box-sizing: border-box;
      }

      input[type="text"]:focus,
      input[type="number"]:focus,
      input[type="date"]:focus,
      select:focus {
        outline: none;
        border-color: var(--primary-light);
        box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.2);
      }

      input[readonly] {
        background-color: var(--gray-light);
        cursor: not-allowed;
      }

      /* Button Styling */
      .btn {
        padding: 12px 20px;
        border: none;
        border-radius: var(--border-radius);
        cursor: pointer;
        font-size: 1rem;
        font-weight: 500;
        transition: all 0.3s ease;
        text-align: center;
        display: inline-block;
        margin-right: 10px;
        text-decoration: none;
      }

      .btn-primary {
        background-color: var(--primary-color);
        color: white;
      }

      .btn-primary:hover {
        background-color: var(--primary-dark);
      }

      .btn-success {
        background-color: var(--success-color);
        color: white;
      }

      .btn-success:hover {
        background-color: #4cae4c;
      }

      .btn-secondary {
        background-color: var(--gray);
        color: var(--gray-dark);
      }

      .btn-secondary:hover {
        background-color: #d1d7dd;
      }

      .btn-danger {
        background-color: var(--danger-color);
        color: white;
      }

      .btn-danger:hover {
        background-color: #c9302c;
      }

      .btn-sm {
        padding: 6px 12px;
        font-size: 0.875rem;
      }

      /* Table Styling */
      table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        font-size: 0.95rem;
      }

      th, td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid var(--gray);
      }

      th {
        background-color: var(--gray-light);
        color: var(--primary-dark);
        font-weight: 600;
        white-space: nowrap;
      }

      tr:hover {
        background-color: rgba(74, 111, 165, 0.05);
      }

      /* Special Styling for Add Payroll Tab */
      #addPayrollTab .card {
        max-width: 800px;
        margin: 0 auto;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        border-radius: 12px;
      }

      #addPayrollTab h3 {
        text-align: center;
        margin-bottom: 1.5rem;
        color: var(--primary-color);
        font-size: 1.8rem;
      }

      #addPayrollTab .form-group {
        display: flex;
        flex-direction: column;
      }

      @media (min-width: 768px) {
        #addPayrollTab form {
          display: grid;
          grid-template-columns: repeat(2, 1fr);
          gap: 1.5rem;
        }
        
        #addPayrollTab h4,
        #addPayrollTab .form-group:last-child {
          grid-column: span 2;
        }
      }

      /* Alert Styling */
      .alert {
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: var(--border-radius);
        animation: slideDown 0.5s ease;
        position: relative;
      }

      .alert-success {
        color: #3c763d;
        background-color: #dff0d8;
        border-color: #d6e9c6;
      }

      .alert-danger {
        color: #a94442;
        background-color: #f2dede;
        border-color: #ebccd1;
      }

      .alert-close {
        position: absolute;
        right: 10px;
        top: 10px;
        cursor: pointer;
        font-weight: bold;
      }

      @keyframes slideDown {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
      }

      /* Highlight Fields */
      .highlight-field {
        animation: highlightPulse 1s ease;
      }

      @keyframes highlightPulse {
        0% { background-color: transparent; }
        50% { background-color: rgba(92, 184, 92, 0.2); }
        100% { background-color: transparent; }
      }

      /* Responsive Adjustments */
      @media (max-width: 768px) {
        .tabs {
          flex-direction: column;
        }
        
        .tab-link {
          width: 100%;
        }
        
        table {
          display: block;
          overflow-x: auto;
          white-space: nowrap;
        }
        
        .btn {
          display: block;
          width: 100%;
          margin-bottom: 10px;
          margin-right: 0;
        }
      }

      /* Custom Deductions Section */
      .deductions-section {
        border: 1px solid var(--gray);
        padding: 1rem;
        border-radius: var(--border-radius);
        margin-bottom: 1.5rem;
        background-color: var(--gray-light);
      }

      .deduction-summary {
        display: flex;
        justify-content: space-between;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px dashed var(--gray);
      }

      .deduction-summary div {
        text-align: right;
      }

      .deduction-summary strong {
        color: var(--primary-dark);
        font-size: 1.1rem;
      }
      .deduction-group {
    margin-bottom: 15px;
      }
    .deduction-controls {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .deduction-controls select {
        width: 130px;
    }
    .calculated-amount {
        color: #666;
        font-style: italic;
    }
    .save-template-toggle {
        margin-bottom: 10px;
    }
    #templateNameField {
        display: flex;
        gap: 10px;
        align-items: center;
        margin-bottom: 15px;
    }
      /* Tab navigation styles */
      .tab-container { margin-top: 1em; }
      .tabs { overflow: hidden; background: #e0e0e0; border-bottom: 1px solid #ccc; }
      .tabs button {
          background: none;
          border: none;
          outline: none;
          padding: 14px 16px;
          float: left;
          cursor: pointer;
          font-size: 16px;
      }
      .tabs button:hover { background: #d0d0d0; }
      .tabs button.active { background: #fff; border-bottom: 2px solid #003366; }
      .tab-content { display: none; padding: 1em; background: #fff; }
      .tab-content.active { display: block; }
    </style>
    <script>
      // JavaScript for tab switching functionality
      function openTab(evt, tabName) {
          var i, tabcontent, tablinks;
          tabcontent = document.getElementsByClassName("tab-content");
          for (i = 0; i < tabcontent.length; i++) {
              tabcontent[i].classList.remove("active");
          }
          tablinks = document.getElementsByClassName("tab-link");
          for (i = 0; i < tablinks.length; i++) {
              tablinks[i].classList.remove("active");
          }
          document.getElementById(tabName).classList.add("active");
          evt.currentTarget.classList.add("active");
      }
      // Optional: Open the first tab automatically on page load
      window.onload = function() {
          var firstTab = document.querySelector(".tab-link");
          if(firstTab) {
              firstTab.click();
          }
      }
      function showAlert(message) {
          alert(message);
      }
    </script>
</head>
<body>
<header>
    <h1>School Finance System</h1>
    <nav>
        <a href="index.php">Dashboard</a>
        <a href="customer_center.php">Customer Center</a>
        <a href="expense_management.php">Expense Management</a>
        <a href="payroll.php">Payroll</a>
        <a href="reports.php">Reports</a>
    </nav>
</header>
<div class="container">

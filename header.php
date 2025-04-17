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
      body { 
        font-family: Arial, sans-serif; 
        margin: 0; 
        padding: 0; 
        background: #f7f7f7;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
      }
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
      .card { background: #fff; padding: 1em; margin-bottom: 1em; border-radius: 5px; box-shadow: 0 0 5px rgba(0,0,0,0.1); }
      table { width: 100%; border-collapse: collapse; margin-bottom: 1em; }
      table, th, td { border: 1px solid #ddd; }
      th, td { padding: 8px; text-align: left; }
      
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

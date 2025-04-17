<?php
// index.php - Dashboard
require 'config.php';
require 'functions.php';
include 'header.php';

$summary = getDashboardSummary($pdo);
?>
<style>
body {
    background-color: #f4f6f9;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.dashboard-container {
    padding: 30px 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.dashboard-container h2 {
    font-size: 28px;
    margin-bottom: 20px;
    color: #343a40;
}

.tab-container {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.tabs {
    display: flex;
    background: #f1f3f5;
    border-bottom: 1px solid #dee2e6;
}

.tab-link {
    padding: 18px 30px;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 17px;
    font-weight: 500;
    color: #495057;
    transition: all 0.3s ease;
}

.tab-link:hover,
.tab-link.active {
    background: #ffffff;
    color: #0d6efd;
    border-bottom: 3px solid #0d6efd;
}

.tab-content {
    padding: 30px;
    display: none;
    animation: fadeIn 0.4s ease-in-out;
}

.tab-content.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 25px;
    margin-top: 20px;
}

.summary-card {
    background: #ffffff;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
    text-align: center;
    transition: all 0.3s ease-in-out;
}

.summary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
}

.summary-card h3 {
    margin: 0 0 12px 0;
    color: #0d6efd;
    font-size: 20px;
    font-weight: 600;
}

.summary-card p {
    font-size: 18px;
    color: #343a40;
    margin: 0;
    font-weight: 500;
}

.chart-container {
    background: #ffffff;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
}
</style>

<div class="dashboard-container">
    <h2>Dashboard Overview</h2>
    <div class="tab-container">
        <div class="tabs">
            <button class="tab-link active" onclick="openTab(event, 'summaryTab')">Financial Summary</button>
            <button class="tab-link" onclick="openTab(event, 'chartsTab')">Charts</button>
        </div>

        <div id="summaryTab" class="tab-content active">
            <div class="summary-grid">
                <div class="summary-card">
                    <h3>Total Income</h3>
                    <p>$<?php echo number_format($summary['total_income'], 2); ?></p>
                </div>
                <div class="summary-card">
                    <h3>Total Expenses</h3>
                    <p>$<?php echo number_format($summary['total_expenses'], 2); ?></p>
                </div>
                <div class="summary-card">
                    <h3>Current Balance</h3>
                    <p>$<?php echo number_format($summary['current_balance'], 2); ?></p>
                </div>
                <div class="summary-card">
                    <h3>Total Students</h3>
                    <p><?php echo number_format($summary['total_students']); ?></p>
                </div>
            </div>
        </div>

        <div id="chartsTab" class="tab-content">
            <div class="chart-container">
                <h3>Income & Expenses Chart</h3>
                <p>[Chart will render here. Use Chart.js or similar.]</p>
            </div>
        </div>
    </div>
</div>

<script>
function openTab(evt, tabName) {
    const tabcontent = document.getElementsByClassName("tab-content");
    const tablinks = document.getElementsByClassName("tab-link");

    for (let i = 0; i < tabcontent.length; i++) {
        tabcontent[i].classList.remove("active");
    }
    for (let i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
    }

    document.getElementById(tabName).classList.add("active");
    evt.currentTarget.classList.add("active");
}
</script>

<?php include 'footer.php'; ?>

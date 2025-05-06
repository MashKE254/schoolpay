<?php if(isset($_SESSION['user_id'])): ?>
<div class="sidebar bg-light">
    <div class="p-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="expense.php">Expense Management</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="reports.php">Reports</a>
            </li>
            <?php if(in_array($_SESSION['role'], ['admin'])): ?>
            <li class="nav-item">
                <a class="nav-link" href="admin.php">Admin Panel</a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</div>
<?php endif; ?>
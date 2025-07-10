<?php
require 'config.php';
require 'functions.php';
include 'header.php'; // Handles session and sets $school_id

// Filters
$filters = [];
$params = ['school_id' => $school_id];

if (!empty($_GET['user_id'])) {
    $filters[] = "user_id = :user_id";
    $params['user_id'] = $_GET['user_id'];
}
if (!empty($_GET['date_from'])) {
    $filters[] = "created_at >= :date_from";
    $params['date_from'] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $filters[] = "created_at <= :date_to";
    $params['date_to'] = date('Y-m-d', strtotime($_GET['date_to'] . ' +1 day'));
}

$where_clause = empty($filters) ? "" : "AND " . implode(" AND ", $filters);

// Fetch logs
$stmt = $pdo->prepare("SELECT * FROM audit_log WHERE school_id = :school_id $where_clause ORDER BY created_at DESC LIMIT 100");
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch users for the filter dropdown
$user_stmt = $pdo->prepare("SELECT id, name FROM users WHERE school_id = ? ORDER BY name");
$user_stmt->execute([$school_id]);
$school_users = $user_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="page-header">
    <h1><i class="fas fa-history"></i> Audit Trail</h1>
    <p>Review all financial and administrative changes made in the system.</p>
</div>

<div class="card">
    <h3>Filter Logs</h3>
    <form method="GET" class="filter-controls">
        <div class="form-group">
            <label for="date_from">From</label>
            <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="date_to">To</label>
            <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="user_id">User</label>
            <select name="user_id">
                <option value="">All Users</option>
                <?php foreach($school_users as $user): ?>
                <option value="<?= $user['id'] ?>" <?= (isset($_GET['user_id']) && $_GET['user_id'] == $user['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($user['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-primary">Filter</button>
    </form>
</div>

<div class="card">
    <h3>Log History</h3>
    <div class="table-container">
        <table class="audit-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Target</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="5" class="text-center">No log entries found for the selected criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr class="log-<?= strtolower(htmlspecialchars($log['action_type'])) ?>">
                        <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($log['created_at']))) ?></td>
                        <td><?= htmlspecialchars($log['user_name']) ?></td>
                        <td>
                            <span class="status-badge status-<?= strtolower(htmlspecialchars($log['action_type'])) ?>"><?= htmlspecialchars($log['action_type']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($log['target_table']) ?> #<?= htmlspecialchars($log['target_id']) ?></td>
                        <td>
                            <?= format_audit_details($log) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
<?php
/**
 * transport_management.php - Transport Zone Management
 *
 * Manages transport zones, pricing, and student assignments
 * Supports round-trip and one-way pricing per zone
 */

// Start output buffering to prevent "headers already sent" errors
ob_start();

require_once 'config.php';
require_once 'functions.php';
require_once 'header.php';

$school_id = $_SESSION['school_id'];
$active_tab = $_GET['tab'] ?? 'zones';

// ============================================================================
// FORM HANDLERS
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Create/Update Transport Zone
        if (isset($_POST['save_zone'])) {
            $zone_id = !empty($_POST['zone_id']) ? intval($_POST['zone_id']) : null;
            $zone_name = trim($_POST['zone_name']);
            $description = trim($_POST['description']);
            $round_trip_amount = floatval($_POST['round_trip_amount']);
            $one_way_amount = floatval($_POST['one_way_amount']);
            $status = $_POST['status'] ?? 'active';

            if (empty($zone_name)) {
                throw new Exception("Zone name is required.");
            }

            if ($zone_id) {
                // Update existing zone
                $stmt = $pdo->prepare("UPDATE transport_zones SET zone_name=?, description=?, round_trip_amount=?, one_way_amount=?, status=? WHERE id=? AND school_id=?");
                $stmt->execute([$zone_name, $description, $round_trip_amount, $one_way_amount, $status, $zone_id, $school_id]);
                $_SESSION['success_message'] = "Transport zone updated successfully.";
            } else {
                // Create new zone
                $stmt = $pdo->prepare("INSERT INTO transport_zones (school_id, zone_name, description, round_trip_amount, one_way_amount, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$school_id, $zone_name, $description, $round_trip_amount, $one_way_amount, $status]);
                $_SESSION['success_message'] = "Transport zone created successfully.";
            }
            $active_tab = 'zones';
        }

        // Delete Transport Zone
        elseif (isset($_POST['delete_zone'])) {
            $zone_id = intval($_POST['zone_id']);

            // Check if any students are assigned
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM student_transport WHERE transport_zone_id=? AND school_id=?");
            $stmt->execute([$zone_id, $school_id]);
            $student_count = $stmt->fetchColumn();

            if ($student_count > 0) {
                throw new Exception("Cannot delete zone - $student_count students are currently assigned to it.");
            }

            $stmt = $pdo->prepare("DELETE FROM transport_zones WHERE id=? AND school_id=?");
            $stmt->execute([$zone_id, $school_id]);
            $_SESSION['success_message'] = "Transport zone deleted successfully.";
            $active_tab = 'zones';
        }

        // Assign Student to Transport Zone
        elseif (isset($_POST['assign_student_transport'])) {
            $student_id = intval($_POST['student_id']);
            $transport_zone_id = intval($_POST['transport_zone_id']);
            $trip_type = $_POST['trip_type'];
            $academic_year = trim($_POST['academic_year']);
            $term = trim($_POST['term']);

            $stmt = $pdo->prepare("INSERT INTO student_transport (school_id, student_id, transport_zone_id, trip_type, academic_year, term, status)
                                   VALUES (?, ?, ?, ?, ?, ?, 'active')
                                   ON DUPLICATE KEY UPDATE transport_zone_id=VALUES(transport_zone_id), trip_type=VALUES(trip_type), status='active'");
            $stmt->execute([$school_id, $student_id, $transport_zone_id, $trip_type, $academic_year, $term]);

            $_SESSION['success_message'] = "Student transport assignment saved successfully.";
            $active_tab = 'assignments';
        }

        // Remove Student Transport Assignment
        elseif (isset($_POST['remove_student_transport'])) {
            $assignment_id = intval($_POST['assignment_id']);

            $stmt = $pdo->prepare("DELETE FROM student_transport WHERE id=? AND school_id=?");
            $stmt->execute([$assignment_id, $school_id]);

            $_SESSION['success_message'] = "Transport assignment removed successfully.";
            $active_tab = 'assignments';
        }

        $pdo->commit();
        header("Location: transport_management.php?tab=$active_tab&success=1");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// ============================================================================
// DATA RETRIEVAL
// ============================================================================

// Get all transport zones
$stmt = $pdo->prepare("SELECT * FROM transport_zones WHERE school_id=? ORDER BY zone_name ASC");
$stmt->execute([$school_id]);
$zones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all students for dropdown
$students = getStudents($pdo, $school_id, null, null, 'active');

// Get current academic year and term
$current_academic_year = date('Y') . '-' . (date('Y') + 1);
$current_term = 'Term 1';

// Get student transport assignments
$stmt = $pdo->prepare("
    SELECT st.*, s.name as student_name, c.name as class_name, tz.zone_name, tz.round_trip_amount, tz.one_way_amount
    FROM student_transport st
    JOIN students s ON st.student_id = s.id
    LEFT JOIN classes c ON s.class_id = c.id
    JOIN transport_zones tz ON st.transport_zone_id = tz.id
    WHERE st.school_id = ?
    ORDER BY st.academic_year DESC, st.term DESC, s.name ASC
");
$stmt->execute([$school_id]);
$transport_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<style>
.transport-container {
    max-width: 1400px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.tabs {
    display: flex;
    gap: 1rem;
    border-bottom: 2px solid #d8e2ef;
    margin-bottom: 2rem;
}

.tab {
    padding: 1rem 2rem;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    color: #50668d;
    transition: all 0.3s;
}

.tab.active {
    color: #2c7be5;
    border-bottom-color: #2c7be5;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.zones-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.zone-card {
    background: white;
    border: 1px solid #d8e2ef;
    border-radius: 0.5rem;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.zone-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1rem;
}

.zone-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: #12263f;
}

.zone-pricing {
    margin: 1rem 0;
}

.price-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f1f4f8;
}

.zone-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
    font-weight: 600;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-danger {
    background: #f8d7da;
    color: #721c24;
}

.table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 0.5rem;
    overflow: hidden;
}

.table th {
    background: #f1f4f8;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #12263f;
}

.table td {
    padding: 1rem;
    border-bottom: 1px solid #d8e2ef;
}

.table tr:hover {
    background: #f8f9fa;
}
</style>

<div class="transport-container">
    <h1><i class="fas fa-bus"></i> Transport Management</h1>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab <?= $active_tab === 'zones' ? 'active' : '' ?>" onclick="switchTab('zones')">
            <i class="fas fa-map-marked-alt"></i> Transport Zones
        </button>
        <button class="tab <?= $active_tab === 'assignments' ? 'active' : '' ?>" onclick="switchTab('assignments')">
            <i class="fas fa-users"></i> Student Assignments
        </button>
    </div>

    <!-- Transport Zones Tab -->
    <div id="zones-tab" class="tab-content <?= $active_tab === 'zones' ? 'active' : '' ?>">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2>Transport Zones</h2>
            <button class="btn-primary" onclick="openModal('zoneModal')">
                <i class="fas fa-plus"></i> Add New Zone
            </button>
        </div>

        <div class="zones-grid">
            <?php foreach ($zones as $zone): ?>
                <div class="zone-card">
                    <div class="zone-header">
                        <div>
                            <div class="zone-name"><?= htmlspecialchars($zone['zone_name']) ?></div>
                            <span class="badge badge-<?= $zone['status'] === 'active' ? 'success' : 'danger' ?>">
                                <?= ucfirst($zone['status']) ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($zone['description']): ?>
                        <p style="color: #50668d; margin: 0.5rem 0;"><?= htmlspecialchars($zone['description']) ?></p>
                    <?php endif; ?>

                    <div class="zone-pricing">
                        <div class="price-row">
                            <span>Round Trip:</span>
                            <strong>KSH <?= number_format($zone['round_trip_amount'], 2) ?></strong>
                        </div>
                        <div class="price-row">
                            <span>One Way:</span>
                            <strong>KSH <?= number_format($zone['one_way_amount'], 2) ?></strong>
                        </div>
                    </div>

                    <div class="zone-actions">
                        <button class="btn-secondary" onclick='editZone(<?= json_encode($zone) ?>)'>
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <form method="post" style="display: inline;" onsubmit="return confirm('Delete this zone?');">
                            <input type="hidden" name="delete_zone" value="1">
                            <input type="hidden" name="zone_id" value="<?= $zone['id'] ?>">
                            <button type="submit" class="btn-danger">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Student Assignments Tab -->
    <div id="assignments-tab" class="tab-content <?= $active_tab === 'assignments' ? 'active' : '' ?>">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2>Student Transport Assignments</h2>
            <button class="btn-primary" onclick="openModal('assignModal')">
                <i class="fas fa-plus"></i> Assign Student to Transport
            </button>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Transport Zone</th>
                    <th>Trip Type</th>
                    <th>Amount</th>
                    <th>Academic Year</th>
                    <th>Term</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transport_assignments as $assignment): ?>
                    <tr>
                        <td><?= htmlspecialchars($assignment['student_name']) ?></td>
                        <td><?= htmlspecialchars($assignment['class_name']) ?></td>
                        <td><?= htmlspecialchars($assignment['zone_name']) ?></td>
                        <td><?= ucwords(str_replace('_', ' ', $assignment['trip_type'])) ?></td>
                        <td><strong>KSH <?= number_format(
                                $assignment['trip_type'] === 'round_trip' ?
                                $assignment['round_trip_amount'] :
                                $assignment['one_way_amount'], 2
                            ) ?></strong></td>
                        <td><?= htmlspecialchars($assignment['academic_year']) ?></td>
                        <td><?= htmlspecialchars($assignment['term']) ?></td>
                        <td>
                            <form method="post" style="display: inline;" onsubmit="return confirm('Remove this transport assignment?');">
                                <input type="hidden" name="remove_student_transport" value="1">
                                <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                                <button type="submit" class="btn-icon btn-danger" title="Remove">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Zone Modal -->
<div id="zoneModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="zoneModalTitle">Add Transport Zone</h3>
            <span class="close" onclick="closeModal('zoneModal')">&times;</span>
        </div>
        <form method="post" id="zoneForm">
            <input type="hidden" name="save_zone" value="1">
            <input type="hidden" name="zone_id" id="zone_id">

            <div class="modal-body">
                <div class="form-group">
                    <label for="zone_name">Zone Name *</label>
                    <input type="text" name="zone_name" id="zone_name" class="form-control" required
                           placeholder="e.g., ZONE-1">
                </div>

                <div class="form-group">
                    <label for="description">Areas Covered</label>
                    <textarea name="description" id="description" class="form-control" rows="3"
                              placeholder="e.g., Delta, Ruaka town, Joyland"></textarea>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="form-group">
                        <label for="round_trip_amount">Round Trip Amount (KSH) *</label>
                        <input type="number" name="round_trip_amount" id="round_trip_amount"
                               class="form-control" step="0.01" required value="0.00">
                    </div>

                    <div class="form-group">
                        <label for="one_way_amount">One Way Amount (KSH) *</label>
                        <input type="number" name="one_way_amount" id="one_way_amount"
                               class="form-control" step="0.01" required value="0.00">
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('zoneModal')">Cancel</button>
                <button type="submit" class="btn-primary">Save Zone</button>
            </div>
        </form>
    </div>
</div>

<!-- Assign Student Modal -->
<div id="assignModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Assign Student to Transport</h3>
            <span class="close" onclick="closeModal('assignModal')">&times;</span>
        </div>
        <form method="post">
            <input type="hidden" name="assign_student_transport" value="1">

            <div class="modal-body">
                <div class="form-group">
                    <label for="student_id">Student *</label>
                    <select name="student_id" id="student_id" class="form-control" required>
                        <option value="">Select Student...</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= $student['id'] ?>">
                                <?= htmlspecialchars($student['name']) ?>
                                <?= $student['class_name'] ? ' - ' . htmlspecialchars($student['class_name']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="transport_zone_id">Transport Zone *</label>
                    <select name="transport_zone_id" id="transport_zone_id" class="form-control" required>
                        <option value="">Select Zone...</option>
                        <?php foreach ($zones as $zone): ?>
                            <?php if ($zone['status'] === 'active'): ?>
                                <option value="<?= $zone['id'] ?>">
                                    <?= htmlspecialchars($zone['zone_name']) ?>
                                    (RT: <?= number_format($zone['round_trip_amount']) ?>,
                                     OW: <?= number_format($zone['one_way_amount']) ?>)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="trip_type">Trip Type *</label>
                    <select name="trip_type" id="trip_type" class="form-control" required>
                        <option value="round_trip">Round Trip</option>
                        <option value="one_way">One Way</option>
                    </select>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="form-group">
                        <label for="academic_year">Academic Year *</label>
                        <input type="text" name="academic_year" id="academic_year" class="form-control"
                               value="<?= $current_academic_year ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="term">Term *</label>
                        <select name="term" id="term" class="form-control" required>
                            <option value="Term 1">Term 1</option>
                            <option value="Term 2">Term 2</option>
                            <option value="Term 3">Term 3</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('assignModal')">Cancel</button>
                <button type="submit" class="btn-primary">Assign Transport</button>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

    document.querySelector(`[onclick="switchTab('${tab}')"]`).classList.add('active');
    document.getElementById(`${tab}-tab`).classList.add('active');

    history.pushState(null, '', `?tab=${tab}`);
}

function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    if (modalId === 'zoneModal') {
        document.getElementById('zoneForm').reset();
        document.getElementById('zone_id').value = '';
        document.getElementById('zoneModalTitle').textContent = 'Add Transport Zone';
    }
}

function editZone(zone) {
    document.getElementById('zone_id').value = zone.id;
    document.getElementById('zone_name').value = zone.zone_name;
    document.getElementById('description').value = zone.description || '';
    document.getElementById('round_trip_amount').value = zone.round_trip_amount;
    document.getElementById('one_way_amount').value = zone.one_way_amount;
    document.getElementById('status').value = zone.status;
    document.getElementById('zoneModalTitle').textContent = 'Edit Transport Zone';
    openModal('zoneModal');
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.className === 'modal') {
        event.target.style.display = 'none';
    }
}
</script>

<?php
include 'footer.php';
// Flush output buffer
if (ob_get_level() > 0) ob_end_flush();
?>

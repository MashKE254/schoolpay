<?php
/**
 * activities_management.php - Extra-Curricular Activities Management
 *
 * Manages school activities/programs and student enrollments
 * Each activity has a fee per term charged to enrolled students
 */

require_once 'config.php';
require_once 'functions.php';
require_once 'header.php';

$school_id = $_SESSION['school_id'];
$active_tab = $_GET['tab'] ?? 'activities';

// ============================================================================
// FORM HANDLERS
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Create/Update Activity
        if (isset($_POST['save_activity'])) {
            $activity_id = !empty($_POST['activity_id']) ? intval($_POST['activity_id']) : null;
            $activity_name = trim($_POST['activity_name']);
            $description = trim($_POST['description']);
            $fee_per_term = floatval($_POST['fee_per_term']);
            $category = trim($_POST['category']);
            $status = $_POST['status'] ?? 'active';

            if (empty($activity_name)) {
                throw new Exception("Activity name is required.");
            }

            if ($activity_id) {
                // Update existing activity
                $stmt = $pdo->prepare("UPDATE activities SET activity_name=?, description=?, fee_per_term=?, category=?, status=? WHERE id=? AND school_id=?");
                $stmt->execute([$activity_name, $description, $fee_per_term, $category, $status, $activity_id, $school_id]);
                $_SESSION['success_message'] = "Activity updated successfully.";
            } else {
                // Create new activity
                $stmt = $pdo->prepare("INSERT INTO activities (school_id, activity_name, description, fee_per_term, category, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$school_id, $activity_name, $description, $fee_per_term, $category, $status]);
                $_SESSION['success_message'] = "Activity created successfully.";
            }
            $active_tab = 'activities';
        }

        // Delete Activity
        elseif (isset($_POST['delete_activity'])) {
            $activity_id = intval($_POST['activity_id']);

            // Check if any students are enrolled
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM student_activities WHERE activity_id=? AND school_id=?");
            $stmt->execute([$activity_id, $school_id]);
            $enrollment_count = $stmt->fetchColumn();

            if ($enrollment_count > 0) {
                throw new Exception("Cannot delete activity - $enrollment_count students are currently enrolled.");
            }

            $stmt = $pdo->prepare("DELETE FROM activities WHERE id=? AND school_id=?");
            $stmt->execute([$activity_id, $school_id]);
            $_SESSION['success_message'] = "Activity deleted successfully.";
            $active_tab = 'activities';
        }

        // Enroll Student in Activity
        elseif (isset($_POST['enroll_student'])) {
            $student_id = intval($_POST['student_id']);
            $activity_id = intval($_POST['activity_id']);
            $academic_year = trim($_POST['academic_year']);
            $term = trim($_POST['term']);
            $enrolled_date = date('Y-m-d');

            $stmt = $pdo->prepare("INSERT INTO student_activities (school_id, student_id, activity_id, academic_year, term, enrolled_date, status)
                                   VALUES (?, ?, ?, ?, ?, ?, 'active')
                                   ON DUPLICATE KEY UPDATE status='active', enrolled_date=VALUES(enrolled_date)");
            $stmt->execute([$school_id, $student_id, $activity_id, $academic_year, $term, $enrolled_date]);

            $_SESSION['success_message'] = "Student enrolled in activity successfully.";
            $active_tab = 'enrollments';
        }

        // Remove Student Enrollment
        elseif (isset($_POST['remove_enrollment'])) {
            $enrollment_id = intval($_POST['enrollment_id']);

            $stmt = $pdo->prepare("DELETE FROM student_activities WHERE id=? AND school_id=?");
            $stmt->execute([$enrollment_id, $school_id]);

            $_SESSION['success_message'] = "Student enrollment removed successfully.";
            $active_tab = 'enrollments';
        }

        // Bulk Enroll Class
        elseif (isset($_POST['bulk_enroll_class'])) {
            $class_id = intval($_POST['class_id']);
            $activity_id = intval($_POST['activity_id']);
            $academic_year = trim($_POST['academic_year']);
            $term = trim($_POST['term']);
            $enrolled_date = date('Y-m-d');

            // Get all active students in class
            $stmt = $pdo->prepare("SELECT id FROM students WHERE class_id=? AND school_id=? AND status='active'");
            $stmt->execute([$class_id, $school_id]);
            $students = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($students)) {
                throw new Exception("No active students found in the selected class.");
            }

            $enrolled_count = 0;
            $stmt_enroll = $pdo->prepare("INSERT INTO student_activities (school_id, student_id, activity_id, academic_year, term, enrolled_date, status)
                                          VALUES (?, ?, ?, ?, ?, ?, 'active')
                                          ON DUPLICATE KEY UPDATE status='active'");

            foreach ($students as $student_id) {
                $stmt_enroll->execute([$school_id, $student_id, $activity_id, $academic_year, $term, $enrolled_date]);
                $enrolled_count++;
            }

            $_SESSION['success_message'] = "$enrolled_count students enrolled in activity successfully.";
            $active_tab = 'enrollments';
        }

        $pdo->commit();
        header("Location: activities_management.php?tab=$active_tab&success=1");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// ============================================================================
// DATA RETRIEVAL
// ============================================================================

// Get all activities
$stmt = $pdo->prepare("SELECT * FROM activities WHERE school_id=? ORDER BY category ASC, activity_name ASC");
$stmt->execute([$school_id]);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group activities by category
$activities_by_category = [];
foreach ($activities as $activity) {
    $cat = $activity['category'] ?: 'Uncategorized';
    $activities_by_category[$cat][] = $activity;
}

// Get all students for dropdown
$students = getStudents($pdo, $school_id, null, null, 'active');

// Get all classes for bulk enrollment
$classes = getClasses($pdo, $school_id);

// Get current academic year and term
$current_academic_year = date('Y') . '-' . (date('Y') + 1);
$current_term = 'Term 1';

// Get student activity enrollments
$stmt = $pdo->prepare("
    SELECT sa.*, s.name as student_name, c.name as class_name, a.activity_name, a.fee_per_term, a.category
    FROM student_activities sa
    JOIN students s ON sa.student_id = s.id
    LEFT JOIN classes c ON s.class_id = c.id
    JOIN activities a ON sa.activity_id = a.id
    WHERE sa.school_id = ?
    ORDER BY sa.academic_year DESC, sa.term DESC, s.name ASC, a.activity_name ASC
");
$stmt->execute([$school_id]);
$enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<style>
.activities-container {
    max-width: 1400px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.category-section {
    margin-bottom: 2rem;
}

.category-header {
    background: linear-gradient(135deg, #2c7be5 0%, #1e5bb8 100%);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 0.5rem 0.5rem 0 0;
    font-size: 1.25rem;
    font-weight: 700;
}

.activities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
    padding: 1.5rem;
    background: white;
    border: 1px solid #d8e2ef;
    border-radius: 0 0 0.5rem 0.5rem;
}

.activity-card {
    background: #f8f9fa;
    border: 1px solid #d8e2ef;
    border-radius: 0.5rem;
    padding: 1.5rem;
    transition: transform 0.2s, box-shadow 0.2s;
}

.activity-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.activity-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1rem;
}

.activity-name {
    font-size: 1.125rem;
    font-weight: 700;
    color: #12263f;
    margin-bottom: 0.5rem;
}

.activity-fee {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2c7be5;
    margin: 1rem 0;
}

.activity-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.stats-card {
    background: white;
    border: 1px solid #d8e2ef;
    border-radius: 0.5rem;
    padding: 1.5rem;
    text-align: center;
}

.stats-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c7be5;
}

.stats-label {
    color: #50668d;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>

<div class="activities-container">
    <h1><i class="fas fa-running"></i> Activities Management</h1>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab <?= $active_tab === 'activities' ? 'active' : '' ?>" onclick="switchTab('activities')">
            <i class="fas fa-list"></i> Activities
        </button>
        <button class="tab <?= $active_tab === 'enrollments' ? 'active' : '' ?>" onclick="switchTab('enrollments')">
            <i class="fas fa-users"></i> Student Enrollments
        </button>
    </div>

    <!-- Activities Tab -->
    <div id="activities-tab" class="tab-content <?= $active_tab === 'activities' ? 'active' : '' ?>">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2>Extra-Curricular Activities</h2>
            <button class="btn-primary" onclick="openModal('activityModal')">
                <i class="fas fa-plus"></i> Add New Activity
            </button>
        </div>

        <!-- Summary Stats -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
            <div class="stats-card">
                <div class="stats-number"><?= count($activities) ?></div>
                <div class="stats-label">Total Activities</div>
            </div>
            <div class="stats-card">
                <div class="stats-number"><?= count(array_filter($activities, fn($a) => $a['status'] === 'active')) ?></div>
                <div class="stats-label">Active Activities</div>
            </div>
            <div class="stats-card">
                <div class="stats-number"><?= count($enrollments) ?></div>
                <div class="stats-label">Total Enrollments</div>
            </div>
        </div>

        <!-- Activities by Category -->
        <?php foreach ($activities_by_category as $category => $category_activities): ?>
            <div class="category-section">
                <div class="category-header">
                    <i class="fas fa-folder"></i> <?= htmlspecialchars($category) ?>
                    <span style="float: right; font-size: 0.875rem; opacity: 0.9;">
                        <?= count($category_activities) ?> <?= count($category_activities) === 1 ? 'activity' : 'activities' ?>
                    </span>
                </div>
                <div class="activities-grid">
                    <?php foreach ($category_activities as $activity): ?>
                        <div class="activity-card">
                            <div class="activity-header">
                                <div>
                                    <div class="activity-name"><?= htmlspecialchars($activity['activity_name']) ?></div>
                                    <span class="badge badge-<?= $activity['status'] === 'active' ? 'success' : 'danger' ?>">
                                        <?= ucfirst($activity['status']) ?>
                                    </span>
                                </div>
                            </div>

                            <?php if ($activity['description']): ?>
                                <p style="color: #50668d; font-size: 0.875rem; margin: 0.5rem 0;">
                                    <?= htmlspecialchars($activity['description']) ?>
                                </p>
                            <?php endif; ?>

                            <div class="activity-fee">
                                KSH <?= number_format($activity['fee_per_term'], 2) ?>
                                <div style="font-size: 0.75rem; font-weight: normal; color: #50668d;">per term</div>
                            </div>

                            <div class="activity-actions">
                                <button class="btn-secondary" onclick='editActivity(<?= json_encode($activity) ?>)'>
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="post" style="display: inline;" onsubmit="return confirm('Delete this activity?');">
                                    <input type="hidden" name="delete_activity" value="1">
                                    <input type="hidden" name="activity_id" value="<?= $activity['id'] ?>">
                                    <button type="submit" class="btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Enrollments Tab -->
    <div id="enrollments-tab" class="tab-content <?= $active_tab === 'enrollments' ? 'active' : '' ?>">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; gap: 1rem;">
            <h2>Student Activity Enrollments</h2>
            <div style="display: flex; gap: 1rem;">
                <button class="btn-secondary" onclick="openModal('bulkEnrollModal')">
                    <i class="fas fa-users-cog"></i> Bulk Enroll Class
                </button>
                <button class="btn-primary" onclick="openModal('enrollModal')">
                    <i class="fas fa-plus"></i> Enroll Student
                </button>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Activity</th>
                    <th>Category</th>
                    <th>Fee per Term</th>
                    <th>Academic Year</th>
                    <th>Term</th>
                    <th>Enrolled Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($enrollments as $enrollment): ?>
                    <tr>
                        <td><?= htmlspecialchars($enrollment['student_name']) ?></td>
                        <td><?= htmlspecialchars($enrollment['class_name']) ?></td>
                        <td><?= htmlspecialchars($enrollment['activity_name']) ?></td>
                        <td><span class="badge badge-info"><?= htmlspecialchars($enrollment['category'] ?: 'N/A') ?></span></td>
                        <td><strong>KSH <?= number_format($enrollment['fee_per_term'], 2) ?></strong></td>
                        <td><?= htmlspecialchars($enrollment['academic_year']) ?></td>
                        <td><?= htmlspecialchars($enrollment['term']) ?></td>
                        <td><?= date('M j, Y', strtotime($enrollment['enrolled_date'])) ?></td>
                        <td>
                            <form method="post" style="display: inline;" onsubmit="return confirm('Remove this enrollment?');">
                                <input type="hidden" name="remove_enrollment" value="1">
                                <input type="hidden" name="enrollment_id" value="<?= $enrollment['id'] ?>">
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

<!-- Activity Modal -->
<div id="activityModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="activityModalTitle">Add Activity</h3>
            <span class="close" onclick="closeModal('activityModal')">&times;</span>
        </div>
        <form method="post" id="activityForm">
            <input type="hidden" name="save_activity" value="1">
            <input type="hidden" name="activity_id" id="activity_id">

            <div class="modal-body">
                <div class="form-group">
                    <label for="activity_name">Activity Name *</label>
                    <input type="text" name="activity_name" id="activity_name" class="form-control" required
                           placeholder="e.g., Swimming">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="3"
                              placeholder="Brief description of the activity"></textarea>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="form-group">
                        <label for="fee_per_term">Fee per Term (KSH) *</label>
                        <input type="number" name="fee_per_term" id="fee_per_term"
                               class="form-control" step="0.01" required value="5000.00">
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <select name="category" id="category" class="form-control">
                            <option value="Sports">Sports</option>
                            <option value="Arts">Arts</option>
                            <option value="Music">Music</option>
                            <option value="Languages">Languages</option>
                            <option value="Academic">Academic</option>
                            <option value="Games">Games</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="activity_status">Status</label>
                    <select name="status" id="activity_status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('activityModal')">Cancel</button>
                <button type="submit" class="btn-primary">Save Activity</button>
            </div>
        </form>
    </div>
</div>

<!-- Enroll Student Modal -->
<div id="enrollModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Enroll Student in Activity</h3>
            <span class="close" onclick="closeModal('enrollModal')">&times;</span>
        </div>
        <form method="post">
            <input type="hidden" name="enroll_student" value="1">

            <div class="modal-body">
                <div class="form-group">
                    <label for="enroll_student_id">Student *</label>
                    <select name="student_id" id="enroll_student_id" class="form-control" required>
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
                    <label for="enroll_activity_id">Activity *</label>
                    <select name="activity_id" id="enroll_activity_id" class="form-control" required>
                        <option value="">Select Activity...</option>
                        <?php foreach ($activities as $activity): ?>
                            <?php if ($activity['status'] === 'active'): ?>
                                <option value="<?= $activity['id'] ?>">
                                    <?= htmlspecialchars($activity['activity_name']) ?>
                                    (<?= number_format($activity['fee_per_term']) ?> KSH/term)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="form-group">
                        <label for="enroll_academic_year">Academic Year *</label>
                        <input type="text" name="academic_year" id="enroll_academic_year" class="form-control"
                               value="<?= $current_academic_year ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="enroll_term">Term *</label>
                        <select name="term" id="enroll_term" class="form-control" required>
                            <option value="Term 1">Term 1</option>
                            <option value="Term 2">Term 2</option>
                            <option value="Term 3">Term 3</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('enrollModal')">Cancel</button>
                <button type="submit" class="btn-primary">Enroll Student</button>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Enroll Modal -->
<div id="bulkEnrollModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Bulk Enroll Entire Class</h3>
            <span class="close" onclick="closeModal('bulkEnrollModal')">&times;</span>
        </div>
        <form method="post">
            <input type="hidden" name="bulk_enroll_class" value="1">

            <div class="modal-body">
                <div class="form-group">
                    <label for="bulk_class_id">Class *</label>
                    <select name="class_id" id="bulk_class_id" class="form-control" required>
                        <option value="">Select Class...</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= $class['id'] ?>">
                                <?= htmlspecialchars($class['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="bulk_activity_id">Activity *</label>
                    <select name="activity_id" id="bulk_activity_id" class="form-control" required>
                        <option value="">Select Activity...</option>
                        <?php foreach ($activities as $activity): ?>
                            <?php if ($activity['status'] === 'active'): ?>
                                <option value="<?= $activity['id'] ?>">
                                    <?= htmlspecialchars($activity['activity_name']) ?>
                                    (<?= number_format($activity['fee_per_term']) ?> KSH/term)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="form-group">
                        <label for="bulk_academic_year">Academic Year *</label>
                        <input type="text" name="academic_year" id="bulk_academic_year" class="form-control"
                               value="<?= $current_academic_year ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="bulk_term">Term *</label>
                        <select name="term" id="bulk_term" class="form-control" required>
                            <option value="Term 1">Term 1</option>
                            <option value="Term 2">Term 2</option>
                            <option value="Term 3">Term 3</option>
                        </select>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> This will enroll ALL active students in the selected class.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('bulkEnrollModal')">Cancel</button>
                <button type="submit" class="btn-primary">Enroll Entire Class</button>
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
    if (modalId === 'activityModal') {
        document.getElementById('activityForm').reset();
        document.getElementById('activity_id').value = '';
        document.getElementById('activityModalTitle').textContent = 'Add Activity';
    }
}

function editActivity(activity) {
    document.getElementById('activity_id').value = activity.id;
    document.getElementById('activity_name').value = activity.activity_name;
    document.getElementById('description').value = activity.description || '';
    document.getElementById('fee_per_term').value = activity.fee_per_term;
    document.getElementById('category').value = activity.category || 'Other';
    document.getElementById('activity_status').value = activity.status;
    document.getElementById('activityModalTitle').textContent = 'Edit Activity';
    openModal('activityModal');
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.className === 'modal') {
        event.target.style.display = 'none';
    }
}
</script>

<?php include 'footer.php'; ?>

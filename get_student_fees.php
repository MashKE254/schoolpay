<?php
// get_student_fees.php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'functions.php'; // Ensure your functions can be accessed
session_start();

if (!isset($_SESSION['school_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$school_id = $_SESSION['school_id'];
$student_id = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT);
$class_id_param = filter_input(INPUT_GET, 'class_id', FILTER_VALIDATE_INT); // New: Get class_id directly
$academic_year = trim($_GET['academic_year'] ?? '');
$term = trim($_GET['term'] ?? '');

$class_id = null;

// **MODIFICATION START**: Determine the class_id to use
if ($class_id_param) {
    // If a class_id is passed directly, verify it belongs to the school and use it.
    $stmt_class_verify = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND school_id = ?");
    $stmt_class_verify->execute([$class_id_param, $school_id]);
    if ($stmt_class_verify->fetch()) {
        $class_id = $class_id_param;
    } else {
        echo json_encode(['success' => false, 'error' => 'Class not found or unauthorized.']);
        exit();
    }
} elseif ($student_id) {
    // If no class_id is passed, fall back to the original method of finding it via the student.
    $stmt_student = $pdo->prepare("SELECT class_id FROM students WHERE id = ? AND school_id = ?");
    $stmt_student->execute([$student_id, $school_id]);
    $class_id = $stmt_student->fetchColumn();
}
// **MODIFICATION END**

// Now, proceed with the determined $class_id
if (!$class_id || !$academic_year || !$term) {
    echo json_encode(['success' => false, 'error' => 'Missing required student/class, academic year, or term.']);
    exit();
}

try {
    // 1. Get all fee structure items for that class and term
    // EXCLUDE transport and activity items since they're automatically added from their respective tables
    $stmt_fees = $pdo->prepare(
        "SELECT fsi.*, i.name as item_name, i.fee_frequency
         FROM fee_structure_items fsi
         JOIN items i ON fsi.item_id = i.id
         WHERE fsi.school_id = ?
           AND fsi.class_id = ?
           AND fsi.academic_year = ?
           AND fsi.term = ?
           AND i.name NOT LIKE '%Transport%'
           AND i.name NOT LIKE '%transport%'
           AND i.name NOT LIKE 'Activity:%'
           AND i.name NOT LIKE 'Activity :%'"
    );
    $stmt_fees->execute([$school_id, $class_id, $academic_year, $term]);
    $all_fees = $stmt_fees->fetchAll(PDO::FETCH_ASSOC);

    // 2. Separate into mandatory and optional fees
    $mandatory_items = [];
    $optional_items = [];

    foreach ($all_fees as $fee) {
        // Check if one-time fees have already been billed to this student
        if ($fee['fee_frequency'] === 'one_time' && $student_id) {
            $stmt_check = $pdo->prepare("SELECT id FROM one_time_fees_billed WHERE student_id=? AND item_id=?");
            $stmt_check->execute([$student_id, $fee['item_id']]);
            if ($stmt_check->fetch()) {
                continue; // Skip - already billed
            }
        }

        // Check if annual fees have already been billed for this academic year
        if ($fee['fee_frequency'] === 'annual' && $student_id) {
            $stmt_check = $pdo->prepare("SELECT id FROM annual_fees_billed WHERE student_id=? AND item_id=? AND academic_year=?");
            $stmt_check->execute([$student_id, $fee['item_id'], $academic_year]);
            if ($stmt_check->fetch()) {
                continue; // Skip - already billed this year
            }
        }

        if ($fee['is_mandatory']) {
            $mandatory_items[] = $fee;
        } else {
            $optional_items[] = $fee;
        }
    }

    // 3. Add student transport fee if assigned (MANDATORY)
    if ($student_id) {
        $stmt_transport = $pdo->prepare("
            SELECT st.*, tz.zone_name, tz.round_trip_amount, tz.one_way_amount
            FROM student_transport st
            JOIN transport_zones tz ON st.transport_zone_id = tz.id
            WHERE st.student_id = ?
              AND st.school_id = ?
              AND st.academic_year = ?
              AND st.term = ?
              AND st.status = 'active'
        ");
        $stmt_transport->execute([$student_id, $school_id, $academic_year, $term]);
        $transport = $stmt_transport->fetch(PDO::FETCH_ASSOC);

        if ($transport) {
            $transport_amount = ($transport['trip_type'] === 'round_trip') ?
                                 $transport['round_trip_amount'] :
                                 $transport['one_way_amount'];

            $mandatory_items[] = [
                'item_id' => 0, // Special ID for transport
                'item_name' => 'Transport - ' . $transport['zone_name'] . ' (' . ucwords(str_replace('_', ' ', $transport['trip_type'])) . ')',
                'amount' => $transport_amount,
                'quantity' => 1,
                'is_mandatory' => 1,
                'fee_frequency' => 'recurring',
                'is_transport' => true,
                'transport_id' => $transport['id']
            ];
        }
    }

    // 4. Add student enrolled activities (OPTIONAL - but auto-added)
    if ($student_id) {
        $stmt_activities = $pdo->prepare("
            SELECT sa.*, a.activity_name, a.fee_per_term, a.category
            FROM student_activities sa
            JOIN activities a ON sa.activity_id = a.id
            WHERE sa.student_id = ?
              AND sa.school_id = ?
              AND sa.academic_year = ?
              AND sa.term = ?
              AND sa.status = 'active'
        ");
        $stmt_activities->execute([$student_id, $school_id, $academic_year, $term]);
        $activities = $stmt_activities->fetchAll(PDO::FETCH_ASSOC);

        foreach ($activities as $activity) {
            $optional_items[] = [
                'item_id' => 0, // Special ID for activities
                'item_name' => 'Activity: ' . $activity['activity_name'] . ' (' . $activity['category'] . ')',
                'amount' => $activity['fee_per_term'],
                'quantity' => 1,
                'is_mandatory' => 0, // Optional but enrolled
                'fee_frequency' => 'recurring',
                'is_activity' => true,
                'activity_id' => $activity['activity_id'],
                'enrollment_id' => $activity['id']
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'mandatory_items' => $mandatory_items,
        'optional_items' => $optional_items
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
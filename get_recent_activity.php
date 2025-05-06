<?php
// get_recent_activity.php
require 'config.php';
require 'functions.php';

// Get recent activities for the dashboard
try {
    $activities = [];
    
    // Get recent payments
    $stmt = $pdo->prepare("
        SELECT p.id, p.amount, p.payment_date, p.payment_method, 
               s.name as student_name
        FROM payments p
        JOIN students s ON p.student_id = s.id
        ORDER BY p.payment_date DESC
        LIMIT 5
    ");
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($payments as $payment) {
        $activities[] = [
            'type' => 'payment',
            'description' => 'Payment of $' . number_format($payment['amount'], 2) . ' received from ' . $payment['student_name'],
            'date' => date('M d, Y', strtotime($payment['payment_date']))
        ];
    }
    
    // Get recently added students
    $stmt = $pdo->prepare("
        SELECT id, name, created_at
        FROM students
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($students as $student) {
        $activities[] = [
            'type' => 'student',
            'description' => 'New student ' . $student['name'] . ' was added',
            'date' => date('M d, Y', strtotime($student['created_at']))
        ];
    }
    
    // Sort all activities by date (newest first)
    usort($activities, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    // Return only the 10 most recent
    $activities = array_slice($activities, 0, 10);
    
    header('Content-Type: application/json');
    echo json_encode($activities);
    
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}
?>
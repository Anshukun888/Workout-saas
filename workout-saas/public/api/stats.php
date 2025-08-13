<?php
// public/api/stats.php
require "../../src/db.php";
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Not authorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$range = $_GET['range'] ?? '30'; // days or 'day','week','month' or numeric
$routineOnly = isset($_GET['routineOnly']) && $_GET['routineOnly'] == '1';

// detect if routine columns exist to allow routineOnly filter safely
$hasRoutineCols = false;
try {
    $c = $pdo->query("SHOW COLUMNS FROM workout_history LIKE 'routine_id'");
    $hasRoutineCols = (bool)$c->fetch();
} catch (Exception $e) { $hasRoutineCols = false; }

// determine start date (inclusive window)
if ($range === 'day') {
    $from = date('Y-m-d');
} elseif ($range === 'week') {
    $from = date('Y-m-d', strtotime('-6 days'));
} elseif ($range === 'month') {
    $from = date('Y-m-d', strtotime('-29 days'));
} elseif (is_numeric($range)) {
    $n = max(1, intval($range));
    $from = $n === 1 ? date('Y-m-d') : date('Y-m-d', strtotime('-'.($n-1).' days'));
} else {
    $from = date('Y-m-d', strtotime('-29 days'));
}

$to = date('Y-m-d');

try {
    if ($routineOnly && $hasRoutineCols) {
        $stmt = $pdo->prepare("
            SELECT IFNULL(type,'Unknown') AS type, COUNT(*) AS cnt
            FROM workout_history
            WHERE user_id = ? AND DATE(completed_at) BETWEEN ? AND ? AND routine_id IS NOT NULL
            GROUP BY type
            ORDER BY cnt DESC
        ");
        $stmt->execute([$user_id, $from, $to]);
    } else {
        $stmt = $pdo->prepare("
            SELECT IFNULL(type,'Unknown') AS type, COUNT(*) AS cnt
            FROM workout_history
            WHERE user_id = ? AND DATE(completed_at) BETWEEN ? AND ?
            GROUP BY type
            ORDER BY cnt DESC
        ");
        $stmt->execute([$user_id, $from, $to]);
    }
    $rows = $stmt->fetchAll();

    // Fallback to active workouts when no history yet (ensure heatmap still functions)
    if ((!$rows || count($rows) === 0)) {
        $hasRoutineColsActive = false;
        try { $c2 = $pdo->query("SHOW COLUMNS FROM workout LIKE 'routine_id'"); $hasRoutineColsActive = (bool)$c2->fetch(); } catch (Exception $e) {}
        if ($routineOnly && $hasRoutineCols && $hasRoutineColsActive) {
            $stmt2 = $pdo->prepare("
                SELECT IFNULL(type,'Unknown') AS type, COUNT(*) AS cnt
                FROM workout
                WHERE user_id = ? AND date BETWEEN ? AND ? AND routine_id IS NOT NULL
                GROUP BY type
                ORDER BY cnt DESC
            ");
            $stmt2->execute([$user_id, $from, $to]);
            $rows = $stmt2->fetchAll();
        } else {
            $stmt2 = $pdo->prepare("
                SELECT IFNULL(type,'Unknown') AS type, COUNT(*) AS cnt
                FROM workout
                WHERE user_id = ? AND date BETWEEN ? AND ?
                GROUP BY type
                ORDER BY cnt DESC
            ");
            $stmt2->execute([$user_id, $from, $to]);
            $rows = $stmt2->fetchAll();
        }
    }
    echo json_encode(['success'=>true,'from'=>$from,'to'=>$to,'data'=>$rows]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error']);
}

<?php
// public/api/search_workout.php
require "../../src/db.php";
session_start();
header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if ($limit <= 0) $limit = 10;

if ($q === '') {
    echo json_encode([]);
    exit;
}

try {
    // Prefer prefix matches first, then contains. Uses two parameters.
    $prefix = $q . '%';
    $contains = '%' . $q . '%';
    $stmt = $pdo->prepare("
        SELECT name, type
        FROM workout_names
        WHERE name LIKE ?
           OR name LIKE ?
        ORDER BY (name LIKE ?) DESC, name ASC
        LIMIT ?
    ");
    $stmt->execute([$prefix, $contains, $prefix, $limit]);
    $rows = $stmt->fetchAll();
    // If DB empty or few, enrich with built-in list (prefix-first) up to limit
    if ((count($rows) < $limit) && isset($_GET['fallback']) && $_GET['fallback'] === '1') {
        $builtin = include __DIR__ . '/workout_builtin.php'; // returns array of [name=>type]
        $lower = mb_strtolower($q);
        $pref = [];
        $cont = [];
        foreach ($builtin as $n => $t) {
            $ln = mb_strtolower($n);
            if (strpos($ln, $lower) === 0) $pref[] = ['name'=>$n,'type'=>$t];
            elseif (strpos($ln, $lower) !== false) $cont[] = ['name'=>$n,'type'=>$t];
        }
        $merged = $rows;
        $have = [];
        foreach ($merged as $r) { $have[strtolower($r['name'])] = true; }
        foreach (array_merge($pref, $cont) as $r) {
            $key = strtolower($r['name']);
            if (!isset($have[$key])) { $merged[] = $r; $have[$key] = true; }
            if (count($merged) >= $limit) break;
        }
        $rows = $merged;
    }
    echo json_encode($rows);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([]);
}

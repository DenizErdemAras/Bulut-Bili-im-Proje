<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header('Content-Type: application/json');

// DİKKAT: Host adı 'localhost' değil 'db' olmalı!
$host = 'db';
$db   = 'tododb';
$user = 'root';
$pass = 'password'; // Şifre orijinal projeyle aynı değil
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo json_encode(['error' => 'DB Bağlantı Hatası: ' . $e->getMessage()]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$method = $_SERVER['REQUEST_METHOD'];

// GET İŞLEMLERİ
if ($method === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] == 'get_categories') {
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE user_id = ?');
        $stmt->execute([$_GET['user_id']]);
        echo json_encode($stmt->fetchAll());
    } else {
        $stmt = $pdo->prepare('SELECT * FROM todos WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$_GET['user_id']]);
        echo json_encode($stmt->fetchAll());
    }
}

// POST İŞLEMLERİ
elseif ($method === 'POST' && isset($input['action'])) {
    
    if ($input['action'] == 'add_category') {
        $stmt = $pdo->prepare('INSERT INTO categories (user_id, name, color) VALUES (?, ?, ?)');
        $stmt->execute([$input['user_id'], $input['name'], $input['color']]);
        echo json_encode(['status' => 'success']);
    }
    elseif ($input['action'] == 'delete_category') {
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$input['id']]);
        echo json_encode(['status' => 'deleted']);
    }
    elseif ($input['action'] == 'create') {
        $stmt = $pdo->prepare('INSERT INTO todos (user_id, text, category, color, due_date, status) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$input['user_id'], $input['text'], $input['category'], $input['color'], $input['due_date'], 'todo']);
        echo json_encode(['status' => 'success']);
    }
    elseif ($input['action'] == 'edit_task') {
        $stmt = $pdo->prepare('UPDATE todos SET text = ?, category = ?, color = ?, due_date = ? WHERE id = ?');
        $stmt->execute([$input['text'], $input['category'], $input['color'], $input['due_date'], $input['id']]);
        echo json_encode(['status' => 'edited']);
    }
    elseif ($input['action'] == 'update') {
        $stmt = $pdo->prepare('UPDATE todos SET status = ? WHERE id = ?');
        $stmt->execute([$input['status'], $input['id']]);
        echo json_encode(['status' => 'updated']);
    }
    elseif ($input['action'] == 'delete') {
        $stmt = $pdo->prepare('DELETE FROM todos WHERE id = ?');
        $stmt->execute([$input['id']]);
        echo json_encode(['status' => 'deleted']);
    }
}
?>
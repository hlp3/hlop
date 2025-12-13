<?php
session_start();
$host = 'infrastructure.mariadb:3306';
$db   = 'lotkov.s.a';
$user = 'lotkov.s.a';
$pass = '1667';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$opt = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $opt);
} catch (PDOException $e) {
    die('Подключение не удалось: ' . $e->getMessage());
}

// Функция проверки прав администратора
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 2;
}

// Функция проверки авторизации
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

// Функция получения количества товаров в корзине
function getBucketItemCount($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM Purchase_products 
        WHERE Account_ID = ? AND Bucket_ID = 0
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result['total'] ?: 0;
}

// Функция для хеширования пароля
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Функция для проверки пароля
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Функция для генерации нового ID для Purchase_products
function getNewPurchaseId($pdo) {
    $stmt = $pdo->query("SELECT MAX(ID) as max_id FROM Purchase_products");
    $result = $stmt->fetch();
    $new_id = ($result['max_id'] ?? 0) + 1;
    return $new_id;
}
?>
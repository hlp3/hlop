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

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 2;
}

function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

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

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
?>
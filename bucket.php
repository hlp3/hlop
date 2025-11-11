<?php
require 'config.php';
checkAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $product_id = intval($_POST['product_id']);
    
    $stmt = $pdo->prepare("
        DELETE FROM Purchase_products 
        WHERE Account_ID = ? AND Product_ID = ? AND Bucket_ID = 0
    ");
    $stmt->execute([$_SESSION['user_id'], $product_id]);
    
    $success_message = "Товар удален из корзины";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_bucket'])) {
    $stmt = $pdo->prepare("
        DELETE FROM Purchase_products 
        WHERE Account_ID = ? AND Bucket_ID = 0
    ");
    $stmt->execute([$_SESSION['user_id']]);
    
    $success_message = "Корзина очищена";
}

$stmt = $pdo->prepare("
    SELECT p.Product_ID, p.Name, p.Price, p.Amount 
    FROM Purchase_products pp 
    JOIN Products p ON pp.Product_ID = p.Product_ID 
    WHERE pp.Account_ID = ? AND pp.Bucket_ID = 0
");
$stmt->execute([$_SESSION['user_id']]);
$bucket_items = $stmt->fetchAll();

$total_amount = 0;
foreach ($bucket_items as $item) {
    $total_amount += $item['Price'];
}

$bucket_count = getBucketItemCount($pdo, $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Корзина</title>
    <style>
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        .menu { margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px; }
        .menu a { margin-right: 15px; text-decoration: none; color: #007bff; }
        .bucket-count { background: #007bff; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; }
        .bucket-item { border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; display: flex; justify-content: space-between; align-items: center; }
        .item-info { flex-grow: 1; }
        .item-actions { display: flex; align-items: center; gap: 10px; }
        .total { font-size: 1.5em; font-weight: bold; margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .empty-bucket { text-align: center; padding: 40px; color: #666; }
        .remove-btn { background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
        .clear-btn { background: #ffc107; color: black; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-right: 10px; }
        .continue-shopping { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; }
        .success { color: green; padding: 10px; background: #e6ffe6; border: 1px solid #ccffcc; border-radius: 4px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="menu">
            <a href="index.php">Главная</a>
            <a href="edit_profile.php">Профиль</a>
            <a href="bucket.php">Корзина <span class="bucket-count"><?= $bucket_count ?></span></a>
            <?php if (isAdmin()): ?>
                <a href="admin.php">Админ-панель</a>
            <?php endif; ?>
            <a href="logout.php">Выйти</a>
        </div>

        <h2>Ваша корзина</h2>

        <?php if (isset($success_message)): ?>
            <div class="success"><?= $success_message ?></div>
        <?php endif; ?>

        <?php if (count($bucket_items) > 0): ?>
            <?php foreach ($bucket_items as $item): ?>
                <div class="bucket-item">
                    <div class="item-info">
                        <h4><?= htmlspecialchars($item['Name']) ?></h4>
                        <p>Цена: <strong><?= $item['Price'] ?> руб.</strong></p>
                    </div>
                    <div class="item-actions">
                        <form method="post">
                            <input type="hidden" name="product_id" value="<?= $item['Product_ID'] ?>">
                            <button type="submit" name="remove_item" class="remove-btn" 
                                    onclick="return confirm('Удалить товар из корзины?')">
                                Удалить
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="total">Общая сумма: <?= $total_amount ?> руб.</div>
            <div class="total">Количество товаров: <?= count($bucket_items) ?> шт.</div>

            <form method="post" style="display: inline;">
                <button type="submit" name="clear_bucket" class="clear-btn" 
                        onclick="return confirm('Очистить всю корзину?')">
                    Очистить корзину
                </button>
            </form>

            <div style="margin-top: 20px;">
                <a href="index.php" class="continue-shopping">Продолжить покупки</a>
            </div>

        <?php else: ?>
            <div class="empty-bucket">
                <h3>Ваша корзина пуста</h3>
                <p>Добавьте товары из каталога</p>
                <a href="index.php" class="continue-shopping">Перейти к покупкам</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
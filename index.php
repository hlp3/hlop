<?php
require 'config.php';
checkAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_bucket'])) {
    $product_id = intval($_POST['product_id']);
    
    $stmt = $pdo->prepare("SELECT * FROM Products WHERE Product_ID = ? AND Amount > 0");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if ($product) {
        $stmt = $pdo->prepare("
            SELECT * FROM Purchase_products 
            WHERE Account_ID = ? AND Product_ID = ? AND Bucket_ID = 0
        ");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        $existing_item = $stmt->fetch();
        
        if (!$existing_item) {
            $new_id = getNewPurchaseId($pdo);
            $stmt = $pdo->prepare("
                INSERT INTO Purchase_products (ID, Account_ID, Build_a_PC, Product_ID, Bucket_ID) 
                VALUES (?, ?, 0, ?, 0)
            ");
            $stmt->execute([$new_id, $_SESSION['user_id'], $product_id]);
            $success_message = "Товар добавлен в корзину!";
        } else {
            $info_message = "Товар уже в корзине";
        }
    } else {
        $error_message = "Товар не найден или отсутствует на складе";
    }
}

$bucket_count = getBucketItemCount($pdo, $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Главная страница</title>
    <style>
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        .user-info { background: #f0f0f0; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .products { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
        .product { border: 1px solid #ddd; padding: 15px; border-radius: 5px; text-align: center; }
        .menu { margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px; }
        .menu a { margin-right: 15px; text-decoration: none; color: #007bff; }
        .menu a:hover { text-decoration: underline; }
        .admin-badge { background: #dc3545; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; }
        .bucket-count { background: #007bff; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; }
        .success { color: green; padding: 10px; background: #e6ffe6; border: 1px solid #ccffcc; border-radius: 4px; margin: 10px 0; }
        .info { color: #007bff; padding: 10px; background: #e6f3ff; border: 1px solid #b3d9ff; border-radius: 4px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffe6e6; border: 1px solid #ffcccc; border-radius: 4px; margin: 10px 0; }
        .add-to-bucket { background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
        .add-to-bucket:hover { background: #218838; }
        .add-to-bucket:disabled { background: #6c757d; cursor: not-allowed; }
        .product-price { font-size: 1.2em; font-weight: bold; color: #e44d26; margin: 10px 0; }
        .in-bucket { color: #28a745; font-weight: bold; margin-top: 10px; }
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

        <div class="user-info">
            <h2>Добро пожаловать, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h2>
            <p>
                <strong>Логин:</strong> <?= htmlspecialchars($_SESSION['user_login']) ?>
                <?php if (isAdmin()): ?>
                    <span class="admin-badge">Администратор</span>
                <?php endif; ?>
            </p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="success"><?= $success_message ?></div>
        <?php endif; ?>

        <?php if (isset($info_message)): ?>
            <div class="info"><?= $info_message ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error"><?= $error_message ?></div>
        <?php endif; ?>

        <h3>Каталог товаров:</h3>
        <div class="products">
            <?php
            $products = $pdo->query("SELECT * FROM Products WHERE Amount > 0 ORDER BY Product_ID")->fetchAll();
            
            if (count($products) > 0) {
                $bucket_items_stmt = $pdo->prepare("
                    SELECT Product_ID 
                    FROM Purchase_products 
                    WHERE Account_ID = ? AND Bucket_ID = 0
                ");
                $bucket_items_stmt->execute([$_SESSION['user_id']]);
                $bucket_items = $bucket_items_stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($products as $product) {
                    $in_bucket = in_array($product['Product_ID'], $bucket_items);
                    
                    echo "
                    <div class='product'>
                        <h4>" . htmlspecialchars($product['Name']) . "</h4>
                        <div class='product-price'>{$product['Price']} руб.</div>
                        <p><strong>В наличии:</strong> {$product['Amount']} шт.</p>
                        
                        <form method='post'>
                            <input type='hidden' name='product_id' value='{$product['Product_ID']}'>";
                    
                    if ($in_bucket) {
                        echo "
                            <div class='in-bucket'>✓ В корзине</div>
                            <button type='submit' name='add_to_bucket' class='add-to-bucket' disabled>Добавлено</button>";
                    } else {
                        echo "
                            <button type='submit' name='add_to_bucket' class='add-to-bucket'>Добавить в корзину</button>";
                    }
                    
                    echo "
                        </form>
                    </div>";
                }
            } else {
                echo "<p>Товары отсутствуют. " . (isAdmin() ? 'Добавьте товары через <a href="admin.php">админ-панель</a>.' : '') . "</p>";
            }
            ?>
        </div>
    </div>
</body>
</html>
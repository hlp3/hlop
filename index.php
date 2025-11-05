<?php
require 'config.php';
checkAuth();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Главная страница</title>
    <style>
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        .user-info { background: #f0f0f0; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .products { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
        .product { border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .menu { margin-bottom: 20px; }
        .menu a { margin-right: 15px; text-decoration: none; color: #007bff; }
        .menu a:hover { text-decoration: underline; }
        .admin-badge { background: #dc3545; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; }
    </style>
</head>
<body>
    <div class="container">
        <div class="menu">
            <a href="index.php">Главная</a>
            <a href="edit_profile.php">Редактировать профиль</a>
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
            <p><strong>ID:</strong> <?= $_SESSION['user_id'] ?></p>
            <p><strong>Роль:</strong> <?= isAdmin() ? 'Администратор' : 'Пользователь' ?></p>
        </div>

        <h3>Каталог товаров:</h3>
        <div class="products">
            <?php
            $products = $pdo->query("SELECT * FROM Products WHERE Amount > 0 ORDER BY Product_ID")->fetchAll();
            if (count($products) > 0) {
                foreach ($products as $product) {
                    echo "
                    <div class='product'>
                        <h4>" . htmlspecialchars($product['Name']) . "</h4>
                        <p><strong>Цена:</strong> {$product['Price']} руб.</p>
                        <p><strong>В наличии:</strong> {$product['Amount']} шт.</p>
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
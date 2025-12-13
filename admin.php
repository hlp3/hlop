<?php
require 'config.php';
checkAuth();

// Проверка прав администратора
if (!isAdmin()) {
    header('Location: index.php');
    exit();
}

$action = $_GET['action'] ?? '';
$message = '';

// Обработка действий администратора
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['action']) {
        case 'add_product':
            $name = trim($_POST['name']);
            $price = intval($_POST['price']);
            $amount = intval($_POST['amount']);
            
            if ($name && $price > 0) {
                $stmt = $pdo->prepare("INSERT INTO Products (Name, Price, Amount) VALUES (?, ?, ?)");
                $stmt->execute([$name, $price, $amount]);
                $message = 'Товар успешно добавлен!';
            }
            break;
            
        case 'delete_product':
            $product_id = intval($_POST['product_id']);
            $stmt = $pdo->prepare("DELETE FROM Products WHERE Product_ID = ?");
            $stmt->execute([$product_id]);
            $message = 'Товар успешно удален!';
            break;
            
        case 'update_user_role':
            $user_id = intval($_POST['user_id']);
            $new_role = intval($_POST['role']);
            $stmt = $pdo->prepare("UPDATE Account SET Account_role = ? WHERE Account_ID = ?");
            $stmt->execute([$new_role, $user_id]);
            $message = 'Роль пользователя обновлена!';
            break;
            
        case 'delete_user':
            $user_id = intval($_POST['user_id']);
            
            // Проверяем, не пытаемся ли удалить самого себя
            if ($user_id == $_SESSION['user_id']) {
                $message = 'Ошибка: Нельзя удалить свой собственный аккаунт!';
                break;
            }
            
            // Проверяем, существует ли пользователь
            $stmt = $pdo->prepare("SELECT * FROM Account WHERE Account_ID = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $message = 'Ошибка: Пользователь не найден!';
                break;
            }
            
            try {
                // Сначала удаляем связанные записи в Purchase_products
                $stmt = $pdo->prepare("DELETE FROM Purchase_products WHERE Account_ID = ?");
                $stmt->execute([$user_id]);
                
                // Затем удаляем самого пользователя
                $stmt = $pdo->prepare("DELETE FROM Account WHERE Account_ID = ?");
                $stmt->execute([$user_id]);
                
                $message = 'Пользователь успешно удален!';
            } catch (PDOException $e) {
                $message = 'Ошибка при удалении пользователя: ' . $e->getMessage();
            }
            break;
    }
}

// Получаем список пользователей
$users = $pdo->query("SELECT * FROM Account ORDER BY Account_ID")->fetchAll();

// Получаем список товаров
$products = $pdo->query("SELECT * FROM Products ORDER BY Product_ID")->fetchAll();

// Получаем количество товаров в корзине для меню
$bucket_count = getBucketItemCount($pdo, $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Админ-панель</title>
    <style>
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; }
        .menu { margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px; }
        .menu a { margin-right: 15px; text-decoration: none; color: #007bff; }
        .bucket-count { background: #007bff; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; }
        .section { margin: 30px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .message { color: green; margin: 10px 0; padding: 10px; background: #e6ffe6; border: 1px solid #ccffcc; border-radius: 4px; }
        .error-message { color: red; margin: 10px 0; padding: 10px; background: #ffe6e6; border: 1px solid #ffcccc; border-radius: 4px; }
        form { margin: 10px 0; }
        input, select, button { padding: 5px; margin: 2px; }
        .delete-user-btn { background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; }
        .delete-user-btn:hover { background: #c82333; }
        .role-form { display: inline; }
        .user-actions { display: flex; gap: 5px; flex-wrap: wrap; }
        .current-user { background-color: #fff3cd; }
        .stats { display: flex; gap: 20px; margin-top: 20px; }
        .stat-box { padding: 15px; background: #f8f9fa; border-radius: 5px; flex: 1; text-align: center; }
        .stat-number { font-size: 1.5em; font-weight: bold; color: #007bff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="menu">
            <a href="index.php">Главная</a>
            <a href="edit_profile.php">Профиль</a>
            <a href="bucket.php">Корзина <span class="bucket-count"><?= $bucket_count ?></span></a>
            <a href="admin.php">Админ-панель</a>
            <a href="logout.php">Выйти</a>
        </div>

        <h1>Админ-панель</h1>
        
        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>

        <!-- Статистика -->
        <div class="section">
            <h2>Статистика системы</h2>
            <div class="stats">
                <div class="stat-box">
                    <div class="stat-number"><?= count($users) ?></div>
                    <div>Всего пользователей</div>
                </div>
                <div class="stat-box">
                    <?php 
                    $admin_count = 0;
                    foreach ($users as $user) {
                        if ($user['Account_role'] == 2) $admin_count++;
                    }
                    ?>
                    <div class="stat-number"><?= $admin_count ?></div>
                    <div>Администраторов</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= count($products) ?></div>
                    <div>Товаров в каталоге</div>
                </div>
            </div>
        </div>

        <!-- Управление пользователями -->
        <div class="section">
            <h2>Управление пользователями</h2>
            <p><a href="register.php">Добавить нового пользователя</a></p>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Логин</th>
                        <th>Роль</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr class="<?= $user['Account_ID'] == $_SESSION['user_id'] ? 'current-user' : '' ?>">
                        <td><?= $user['Account_ID'] ?></td>
                        <td><?= htmlspecialchars($user['Full_name']) ?></td>
                        <td><?= htmlspecialchars($user['Login']) ?></td>
                        <td>
                            <form method="post" class="role-form">
                                <input type="hidden" name="user_id" value="<?= $user['Account_ID'] ?>">
                                <select name="role" onchange="this.form.submit()" <?= $user['Account_ID'] == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                    <option value="1" <?= $user['Account_role'] == 1 ? 'selected' : '' ?>>Пользователь</option>
                                    <option value="2" <?= $user['Account_role'] == 2 ? 'selected' : '' ?>>Администратор</option>
                                </select>
                                <input type="hidden" name="action" value="update_user_role">
                            </form>
                        </td>
                        <td>
                            <div class="user-actions">
                                <?php if ($user['Account_ID'] != $_SESSION['user_id']): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?= $user['Account_ID'] ?>">
                                        <button type="submit" name="action" value="delete_user" 
                                                class="delete-user-btn"
                                                onclick="return confirm('Вы уверены, что хотите удалить пользователя <?= htmlspecialchars($user['Full_name']) ?>? Это действие нельзя отменить.')">
                                            Удалить
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #666; font-style: italic;">Это ваш аккаунт</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Управление товарами -->
        <div class="section">
            <h2>Управление товарами</h2>
            
            <h3>Добавить новый товар</h3>
            <form method="post">
                <input type="text" name="name" placeholder="Название товара" required style="width: 300px;">
                <input type="number" name="price" placeholder="Цена" min="0" required>
                <input type="number" name="amount" placeholder="Количество" min="0" required>
                <button type="submit" name="action" value="add_product">Добавить товар</button>
            </form>

            <h3>Список товаров</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Цена</th>
                        <th>Количество</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= $product['Product_ID'] ?></td>
                        <td><?= htmlspecialchars($product['Name']) ?></td>
                        <td><?= $product['Price'] ?> руб.</td>
                        <td><?= $product['Amount'] ?> шт.</td>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="product_id" value="<?= $product['Product_ID'] ?>">
                                <button type="submit" name="action" value="delete_product" 
                                        onclick="return confirm('Удалить товар \"<?= htmlspecialchars($product['Name']) ?>\"?')">
                                    Удалить
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
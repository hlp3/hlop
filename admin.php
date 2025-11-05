<?php
require 'config.php';
checkAuth();

if (!isAdmin()) {
    header('Location: index.php');
    exit();
}

$action = $_GET['action'] ?? '';
$message = '';

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
    }
}

$users = $pdo->query("SELECT * FROM Account ORDER BY Account_ID")->fetchAll();

$products = $pdo->query("SELECT * FROM Products ORDER BY Product_ID")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Админ-панель</title>
    <style>
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; }
        .menu { margin-bottom: 20px; }
        .menu a { margin-right: 15px; text-decoration: none; }
        .section { margin: 30px 0; padding: 20px; border: 1px solid #ddd; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .message { color: green; margin: 10px 0; }
        form { margin: 10px 0; }
        input, select, button { padding: 5px; margin: 2px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="menu">
            <a href="index.php">Главная</a>
            <a href="edit_profile.php">Профиль</a>
            <a href="admin.php">Админ-панель</a>
            <a href="logout.php">Выйти</a>
        </div>

        <h1>Админ-панель</h1>
        
        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>

        <div class="section">
            <h2>Управление пользователями</h2>
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
                    <tr>
                        <td><?= $user['Account_ID'] ?></td>
                        <td><?= htmlspecialchars($user['Full_name']) ?></td>
                        <td><?= htmlspecialchars($user['Login']) ?></td>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?= $user['Account_ID'] ?>">
                                <select name="role">
                                    <option value="1" <?= $user['Account_role'] == 1 ? 'selected' : '' ?>>Пользователь</option>
                                    <option value="2" <?= $user['Account_role'] == 2 ? 'selected' : '' ?>>Администратор</option>
                                </select>
                                <button type="submit" name="action" value="update_user_role">Изменить</button>
                            </form>
                        </td>
                        <td>
                            <?php if ($user['Account_ID'] != $_SESSION['user_id']): ?>
                                <span style="color: #666;">Удаление временно отключено</span>
                            <?php else: ?>
                                <span style="color: #666;">Это ваш аккаунт</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Управление товарами</h2>
            
            <h3>Добавить новый товар</h3>
            <form method="post">
                <input type="text" name="name" placeholder="Название товара" required>
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
                                        onclick="return confirm('Удалить товар?')">Удалить</button>
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
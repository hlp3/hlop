<?php
require 'config.php';
checkAuth();

$error = '';
$success = '';

// Получаем текущие данные пользователя
$stmt = $pdo->prepare("SELECT * FROM Account WHERE Account_ID = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Обработка удаления товара из корзины
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $item_id = intval($_POST['item_id']);
    $stmt = $pdo->prepare("DELETE FROM Purchase_products WHERE ID = ? AND Account_ID = ?");
    $stmt->execute([$item_id, $_SESSION['user_id']]);
    $success = 'Товар удален из корзины';
}

// Обработка очистки всей корзины
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_bucket'])) {
    $stmt = $pdo->prepare("DELETE FROM Purchase_products WHERE Account_ID = ? AND Bucket_ID = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $success = 'Корзина очищена';
}

// Обработка изменения профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Валидация
    if (empty($full_name)) {
        $error = 'ФИО не может быть пустым';
    } elseif (!empty($new_password) && strlen($new_password) < 6) {
        $error = 'Новый пароль должен быть не менее 6 символов';
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error = 'Новые пароли не совпадают';
    } elseif (!empty($new_password) && !verifyPassword($current_password, $user['Password'])) {
        $error = 'Текущий пароль неверен';
    } else {
        // Обновляем данные
        if (!empty($new_password)) {
            // Хешируем новый пароль
            $hashed_password = hashPassword($new_password);
            
            // Обновляем ФИО и пароль
            $stmt = $pdo->prepare("UPDATE Account SET Full_name = ?, Password = ? WHERE Account_ID = ?");
            $stmt->execute([$full_name, $hashed_password, $_SESSION['user_id']]);
            $success = 'Профиль и пароль успешно обновлены!';
        } else {
            // Обновляем только ФИО
            $stmt = $pdo->prepare("UPDATE Account SET Full_name = ? WHERE Account_ID = ?");
            $stmt->execute([$full_name, $_SESSION['user_id']]);
            $success = 'Профиль успешно обновлен!';
        }
        
        // Обновляем данные в сессии
        $_SESSION['user_name'] = $full_name;
        
        // Обновляем данные пользователя
        $user['Full_name'] = $full_name;
    }
}

// Получаем товары в корзине пользователя
$stmt = $pdo->prepare("
    SELECT pp.ID as item_id, pp.Product_ID, p.Name, p.Price, p.Amount 
    FROM Purchase_products pp 
    JOIN Products p ON pp.Product_ID = p.Product_ID 
    WHERE pp.Account_ID = ? AND pp.Bucket_ID = 0
");
$stmt->execute([$_SESSION['user_id']]);
$bucket_items = $stmt->fetchAll();

// Подсчет общей суммы корзины
$total_amount = 0;
foreach ($bucket_items as $item) {
    $total_amount += $item['Price'];
}

// Получаем количество товаров в корзине для меню
$bucket_count = getBucketItemCount($pdo, $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Профиль и корзина</title>
    <style>
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .menu { margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px; }
        .menu a { margin-right: 15px; text-decoration: none; color: #007bff; }
        .bucket-count { background: #007bff; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; }
        .profile-section, .bucket-section { 
            margin-bottom: 30px; 
            padding: 25px; 
            border: 1px solid #ddd; 
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section-title { 
            margin-top: 0; 
            margin-bottom: 20px; 
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
            color: #333;
        }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input, select, button { 
            width: 100%; 
            padding: 12px; 
            margin: 5px 0; 
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }
        input:focus, select:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        .btn-primary { 
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            padding: 12px;
        }
        .btn-primary:hover { background-color: #0056b3; }
        .btn-danger { 
            background-color: #dc3545;
            color: white;
            border: none;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn-danger:hover { background-color: #c82333; }
        .btn-warning { 
            background-color: #ffc107;
            color: black;
            border: none;
            cursor: pointer;
            padding: 10px 15px;
            border-radius: 4px;
            font-size: 14px;
            margin-right: 10px;
        }
        .btn-warning:hover { background-color: #e0a800; }
        .error { 
            color: red; 
            margin-bottom: 15px; 
            padding: 12px;
            background-color: #ffe6e6;
            border: 1px solid #ffcccc;
            border-radius: 4px;
        }
        .success { 
            color: green; 
            margin-bottom: 15px; 
            padding: 12px;
            background-color: #e6ffe6;
            border: 1px solid #ccffcc;
            border-radius: 4px;
        }
        .bucket-item { 
            border: 1px solid #eee; 
            padding: 15px; 
            margin-bottom: 10px; 
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f9f9f9;
        }
        .item-info { flex-grow: 1; }
        .item-actions { display: flex; align-items: center; gap: 10px; }
        .total { 
            font-size: 1.5em; 
            font-weight: bold; 
            margin: 20px 0; 
            padding: 15px; 
            background: #f8f9fa; 
            border-radius: 5px;
            text-align: right;
        }
        .empty-bucket { 
            text-align: center; 
            padding: 40px; 
            color: #666;
            background: #f8f9fa;
            border-radius: 5px;
            border: 1px dashed #ddd;
        }
        .bucket-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 15px;
            background: #e9f7fe;
            border-radius: 5px;
        }
        .stat-box { text-align: center; }
        .stat-number { font-size: 1.8em; font-weight: bold; color: #007bff; }
        .stat-label { font-size: 0.9em; color: #666; }
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .tab {
            padding: 12px 25px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            margin-right: 5px;
            background: #f8f9fa;
            border-radius: 5px 5px 0 0;
        }
        .tab.active {
            background: white;
            border-color: #ddd;
            border-bottom: 1px solid white;
            margin-bottom: -1px;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .continue-shopping { 
            display: inline-block; 
            margin-top: 20px; 
            padding: 10px 20px; 
            background: #28a745; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
        }
        .continue-shopping:hover { background: #218838; }
        .disabled-input {
            background-color: #e9ecef;
            color: #6c757d;
            cursor: not-allowed;
        }
        .small-text {
            font-size: 0.85em;
            color: #666;
            margin-top: 5px;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Переключение между вкладками
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Убираем активный класс у всех вкладок
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(tc => tc.classList.remove('active'));
                    
                    // Добавляем активный класс текущей вкладке
                    this.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                });
            });
            
            // Подтверждение очистки корзины
            const clearBtn = document.querySelector('.btn-warning');
            if (clearBtn) {
                clearBtn.addEventListener('click', function(e) {
                    if (!confirm('Вы уверены, что хотите очистить всю корзину?')) {
                        e.preventDefault();
                    }
                });
            }
            
            // Подтверждение удаления товара
            const deleteBtns = document.querySelectorAll('.btn-danger');
            deleteBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    if (!confirm('Удалить товар из корзины?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="menu">
            <a href="index.php">Главная</a>
            <a href="edit_profile.php">Профиль и корзина <span class="bucket-count"><?= $bucket_count ?></span></a>
            <?php if (isAdmin()): ?>
                <a href="admin.php">Админ-панель</a>
            <?php endif; ?>
            <a href="logout.php">Выйти</a>
        </div>

        <h1>Личный кабинет</h1>
        
        <div class="tabs">
            <div class="tab active" data-tab="profile">Профиль</div>
            <div class="tab" data-tab="bucket">Корзина (<?= count($bucket_items) ?>)</div>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <!-- Вкладка профиля -->
        <div id="profile" class="tab-content active">
            <div class="profile-section">
                <h2 class="section-title">Редактирование профиля</h2>

                <form method="post">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label>Логин:</label>
                        <input type="text" value="<?= htmlspecialchars($user['Login']) ?>" class="disabled-input" disabled>
                        <div class="small-text">Логин нельзя изменить</div>
                    </div>
                    
                    <div class="form-group">
                        <label>ФИО:</label>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($user['Full_name']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Текущий пароль (требуется для смены пароля):</label>
                        <input type="password" name="current_password" placeholder="Введите текущий пароль">
                    </div>
                    
                    <div class="form-group">
                        <label>Новый пароль (оставьте пустым, если не хотите менять):</label>
                        <input type="password" name="new_password" placeholder="Новый пароль">
                    </div>
                    
                    <div class="form-group">
                        <label>Подтвердите новый пароль:</label>
                        <input type="password" name="confirm_password" placeholder="Повторите новый пароль">
                    </div>
                    
                    <button type="submit" class="btn-primary">Сохранить изменения</button>
                </form>
            </div>
        </div>

        <!-- Вкладка корзины -->
        <div id="bucket" class="tab-content">
            <div class="bucket-section">
                <h2 class="section-title">Моя корзина</h2>
                
                <?php if (count($bucket_items) > 0): ?>
                    <div class="bucket-stats">
                        <div class="stat-box">
                            <div class="stat-number"><?= count($bucket_items) ?></div>
                            <div class="stat-label">Товаров в корзине</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-number"><?= $total_amount ?> руб.</div>
                            <div class="stat-label">Общая сумма</div>
                        </div>
                    </div>

                    <?php foreach ($bucket_items as $item): ?>
                        <div class="bucket-item">
                            <div class="item-info">
                                <h4><?= htmlspecialchars($item['Name']) ?></h4>
                                <p>Цена: <strong><?= $item['Price'] ?> руб.</strong></p>
                            </div>
                            <div class="item-actions">
                                <form method="post">
                                    <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                                    <button type="submit" name="remove_item" class="btn-danger">Удалить</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="total">Итого к оплате: <?= $total_amount ?> руб.</div>

                    <form method="post">
                        <button type="submit" name="clear_bucket" class="btn-warning">Очистить всю корзину</button>
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
        </div>
    </div>
</body>
</html>